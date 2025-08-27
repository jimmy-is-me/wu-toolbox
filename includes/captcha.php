<?php
if (!defined('ABSPATH')) exit;

/*
 * GDPR-friendly Image CAPTCHA for WP/WooCommerce forms
 * - Stateless: HMAC token (code + timestamp), no sessions/cookies/storage
 * - Image rendered on the fly from token; no external services
 * - Supports character sets: uppercase, lowercase, mixed; and types: alnum, alpha, numeric
 * - Low-noise, high-contrast, large characters for readability
 * - Hidden for logged-in users
 */

function wu_captcha_secret_key() {
	// Prefer WordPress salts; fallback to plugin path
	$base = defined('AUTH_KEY') && AUTH_KEY ? AUTH_KEY : (ABSPATH . wp_hash(__FILE__));
	return hash_hmac('sha256', 'wu-captcha', $base);
}

function wu_captcha_generate_token($code, $timestamp) {
	$secret = wu_captcha_secret_key();
	$payload = $code . '|' . $timestamp;
	$mac = hash_hmac('sha256', $payload, $secret);
	return base64_encode($payload . '|' . $mac);
}

function wu_captcha_validate_token($token, $user_input) {
	if (empty($token) || empty($user_input)) return new WP_Error('wu_captcha_missing', '請輸入驗證碼');
	$decoded = base64_decode($token);
	if (!$decoded || strpos($decoded, '|') === false) return new WP_Error('wu_captcha_invalid', '驗證碼無效');
	list($code, $ts, $mac) = array_pad(explode('|', $decoded, 3), 3, null);
	if (!$code || !$ts || !$mac) return new WP_Error('wu_captcha_invalid', '驗證碼無效');
	// Constant-time compare
	$expected = hash_hmac('sha256', $code . '|' . $ts, wu_captcha_secret_key());
	if (!hash_equals($expected, $mac)) return new WP_Error('wu_captcha_invalid', '驗證碼錯誤');
	// Expire after 10 minutes
	if (abs(time() - (int)$ts) > 600) return new WP_Error('wu_captcha_expired', '驗證碼已過期，請重新提交');
	// Code must match user input exactly
	if (trim((string)$user_input) !== (string)$code) return new WP_Error('wu_captcha_mismatch', '驗證碼錯誤');
	return true;
}

function wu_captcha_get_charset() {
	$type = get_option('wu_captcha_type', 'alnum'); // alnum|alpha|numeric
	$case = get_option('wu_captcha_case', 'mixed'); // upper|lower|mixed
	$letters = 'abcdefghijklmnopqrstuvwxyz';
	$digits = '0123456789';
	if ($case === 'upper') { $letters = strtoupper($letters); }
	elseif ($case === 'lower') { $letters = strtolower($letters); }
	else { $letters = $letters . strtoupper($letters); }
	if ($type === 'numeric') return $digits;
	if ($type === 'alpha') return $letters;
	return $letters . $digits;
}

function wu_captcha_generate_code($length = 5) {
	$charset = wu_captcha_get_charset();
	$len = max(3, min(8, intval(get_option('wu_captcha_length', $length))));
	$code = '';
	for ($i = 0; $i < $len; $i++) {
		$code .= $charset[ wp_rand(0, strlen($charset) - 1) ];
	}
	return $code;
}

function wu_captcha_render_image_from_code($code) {
	// Output PNG image with large, clear characters
	$char_count = strlen($code);
	$width = max(140, 26 * $char_count + 28);
	$height = 48;
	$img = imagecreatetruecolor($width, $height);
	$bg = imagecolorallocate($img, 255, 255, 255);
	$fg = imagecolorallocate($img, 20, 20, 20);
	$accent = imagecolorallocate($img, 200, 200, 200);
	imagefilledrectangle($img, 0, 0, $width, $height, $bg);
	// Light grid noise (low noise)
	for ($x = 0; $x < $width; $x += 14) { imageline($img, $x, 0, $x, $height, $accent); }
	for ($y = 0; $y < $height; $y += 14) { imageline($img, 0, $y, $width, $y, $accent); }
	$use_ttf = function_exists('imagettftext');
	$font = null;
	$font_size = 22;
	$baseline_y = 34;
	// Attempt to use a common system font if available
	$try_fonts = array(
		'/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
		'/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
		'/usr/share/fonts/truetype/freefont/FreeSans.ttf',
	);
	foreach ($try_fonts as $f) { if (file_exists($f)) { $font = $f; break; } }
	$spacing = ($width - 20) / max(1, $char_count);
	for ($i = 0; $i < $char_count; $i++) {
		$ch = $code[$i];
		$x = 10 + intval($i * $spacing + 4);
		if ($use_ttf && $font) {
			imagettftext($img, $font_size, wp_rand(-6, 6), $x, $baseline_y, $fg, $font, $ch);
		} else {
			imagestring($img, 5, $x, 16, $ch, $fg);
		}
	}
	// Output
	header('Content-Type: image/png');
	header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
	imagepng($img);
	imagedestroy($img);
	exit;
}

// Public image endpoint: ?wu_captcha=1&token=...
add_action('template_redirect', function(){
	if (!isset($_GET['wu_captcha']) || !isset($_GET['token'])) return;
	$token = sanitize_text_field(wp_unslash($_GET['token']));
	$decoded = base64_decode($token);
	if (!$decoded) exit;
	list($code) = array_pad(explode('|', $decoded, 2), 2, null);
	$code = preg_replace('/[^A-Za-z0-9]/', '', (string) $code);
	if (!$code) exit;
	wu_captcha_render_image_from_code($code);
});

function wu_captcha_render_field() {
	if (!get_option('wu_captcha_enabled', 0)) return; // disabled globally
	if (is_user_logged_in()) return; // hide for logged-in users
	$code = wu_captcha_generate_code();
	$ts = time();
	$token = wu_captcha_generate_token($code, $ts);
	?>
	<p class="wu-captcha-field" style="margin-top:8px;">
		<label for="wu_captcha_input" style="display:block;font-weight:600;margin-bottom:6px;">人機驗證</label>
		<img src="<?php echo esc_url( add_query_arg(array('wu_captcha'=>1,'token'=>$token), home_url('/')) ); ?>" alt="CAPTCHA" style="display:block;border:1px solid #ddd;padding:4px;background:#fff;margin-bottom:6px;max-width:100%;height:auto;">
		<input type="text" id="wu_captcha_input" name="wu_captcha_input" autocomplete="off" placeholder="請輸入圖片中的驗證碼" required style="width:100%;max-width:220px;">
		<input type="hidden" name="wu_captcha_token" value="<?php echo esc_attr($token); ?>">
		<small style="display:block;color:#666;margin-top:4px;">此驗證碼不使用外部服務，不儲存個資，符合 GDPR 規範。</small>
	</p>
	<?php
}

// ===== Integrations: Render fields =====
add_action('login_form', 'wu_captcha_render_field');
add_action('register_form', 'wu_captcha_render_field');
add_action('lostpassword_form', 'wu_captcha_render_field');

// WooCommerce account forms
add_action('woocommerce_login_form', 'wu_captcha_render_field');
add_action('woocommerce_register_form', 'wu_captcha_render_field');
add_action('woocommerce_lostpassword_form', 'wu_captcha_render_field');

// Comments form (front-end)
add_action('comment_form_after_fields', 'wu_captcha_render_field');
add_action('comment_form_logged_in_after', 'wu_captcha_render_field');

// ===== Validations =====
function wu_captcha_validate_login($user) {
	if (is_wp_error($user)) return $user;
	if (!get_option('wu_captcha_enabled', 0)) return $user;
	if (is_user_logged_in()) return $user;
	$token = isset($_POST['wu_captcha_token']) ? sanitize_text_field(wp_unslash($_POST['wu_captcha_token'])) : '';
	$input = isset($_POST['wu_captcha_input']) ? sanitize_text_field(wp_unslash($_POST['wu_captcha_input'])) : '';
	$result = wu_captcha_validate_token($token, $input);
	if ($result === true) return $user;
	return new WP_Error('wu_captcha_error', $result->get_error_message());
}
add_filter('authenticate', 'wu_captcha_validate_login', 30, 1);

function wu_captcha_validate_registration($errors, $sanitized_user_login, $user_email) {
	if (!get_option('wu_captcha_enabled', 0)) return $errors;
	if (is_user_logged_in()) return $errors;
	$token = isset($_POST['wu_captcha_token']) ? sanitize_text_field(wp_unslash($_POST['wu_captcha_token'])) : '';
	$input = isset($_POST['wu_captcha_input']) ? sanitize_text_field(wp_unslash($_POST['wu_captcha_input'])) : '';
	$result = wu_captcha_validate_token($token, $input);
	if ($result !== true) {
		$errors->add('wu_captcha_error', $result->get_error_message());
	}
	return $errors;
}
add_filter('registration_errors', 'wu_captcha_validate_registration', 30, 3);

function wu_captcha_validate_lostpassword($errors) {
	if (!get_option('wu_captcha_enabled', 0)) return $errors;
	if (is_user_logged_in()) return $errors;
	$token = isset($_POST['wu_captcha_token']) ? sanitize_text_field(wp_unslash($_POST['wu_captcha_token'])) : '';
	$input = isset($_POST['wu_captcha_input']) ? sanitize_text_field(wp_unslash($_POST['wu_captcha_input'])) : '';
	$result = wu_captcha_validate_token($token, $input);
	if ($result !== true) {
		$errors->add('wu_captcha_error', $result->get_error_message());
	}
	return $errors;
}
add_filter('lostpassword_errors', 'wu_captcha_validate_lostpassword');

// Validate comment submission
add_filter('preprocess_comment', function($commentdata){
	if (!get_option('wu_captcha_enabled', 0)) return $commentdata;
	if (is_user_logged_in()) return $commentdata;
	$token = isset($_POST['wu_captcha_token']) ? sanitize_text_field(wp_unslash($_POST['wu_captcha_token'])) : '';
	$input = isset($_POST['wu_captcha_input']) ? sanitize_text_field(wp_unslash($_POST['wu_captcha_input'])) : '';
	$result = wu_captcha_validate_token($token, $input);
	if ($result !== true) {
		wp_die( esc_html($result->get_error_message()), 400 );
	}
	return $commentdata;
});

// WooCommerce specific error hooks
function wu_captcha_validate_wc_login($error, $user) {
	if (!get_option('wu_captcha_enabled', 0)) return $error;
	if (is_user_logged_in()) return $error;
	$token = isset($_POST['wu_captcha_token']) ? sanitize_text_field(wp_unslash($_POST['wu_captcha_token'])) : '';
	$input = isset($_POST['wu_captcha_input']) ? sanitize_text_field(wp_unslash($_POST['wu_captcha_input'])) : '';
	$result = wu_captcha_validate_token($token, $input);
	if ($result !== true) {
		return new WP_Error('wu_captcha_error', $result->get_error_message());
	}
	return $error;
}
add_filter('woocommerce_process_login_errors', 'wu_captcha_validate_wc_login', 30, 2);

function wu_captcha_validate_wc_registration($errors, $username, $password, $email) {
	if (!get_option('wu_captcha_enabled', 0)) return $errors;
	if (is_user_logged_in()) return $errors;
	$token = isset($_POST['wu_captcha_token']) ? sanitize_text_field(wp_unslash($_POST['wu_captcha_token'])) : '';
	$input = isset($_POST['wu_captcha_input']) ? sanitize_text_field(wp_unslash($_POST['wu_captcha_input'])) : '';
	$result = wu_captcha_validate_token($token, $input);
	if ($result !== true) {
		$errors->add('wu_captcha_error', $result->get_error_message());
	}
	return $errors;
}
add_filter('woocommerce_process_registration_errors', 'wu_captcha_validate_wc_registration', 30, 4);

// Settings page
add_action('admin_init', function(){
	add_option('wu_captcha_enabled', 0);
	add_option('wu_captcha_type', 'alnum');
	add_option('wu_captcha_case', 'mixed');
	add_option('wu_captcha_length', 5);
});

add_action('admin_menu', function(){
	add_submenu_page(
		'wumetax-toolkit',
		'驗證碼設定',
		'登入/註冊驗證碼',
		'manage_options',
		'wu-captcha-settings',
		function(){
			if (isset($_POST['submit'])) {
				check_admin_referer('wu_captcha_settings');
				update_option('wu_captcha_enabled', isset($_POST['wu_captcha_enabled']) ? 1 : 0);
				update_option('wu_captcha_type', in_array($_POST['wu_captcha_type'] ?? 'alnum', array('alnum','alpha','numeric'), true) ? sanitize_text_field($_POST['wu_captcha_type']) : 'alnum');
				update_option('wu_captcha_case', in_array($_POST['wu_captcha_case'] ?? 'mixed', array('upper','lower','mixed'), true) ? sanitize_text_field($_POST['wu_captcha_case']) : 'mixed');
				$len = max(3, min(8, intval($_POST['wu_captcha_length'] ?? 5)));
				update_option('wu_captcha_length', $len);
				echo '<div class="notice notice-success"><p>設定已儲存。</p></div>';
			}
			echo '<div class="wrap"><h1>登入/註冊驗證碼</h1>';
			echo '<form method="post">';
			wp_nonce_field('wu_captcha_settings');
			echo '<table class="form-table">';
			echo '<tr><th scope="row">啟用驗證碼</th><td><label><input type="checkbox" name="wu_captcha_enabled" value="1" ' . checked(1, get_option('wu_captcha_enabled',0), false) . '> 啟用於留言、登入、註冊、密碼重設與 WooCommerce 帳戶表單</label><p class="description">符合 GDPR，無外部請求與追蹤。</p></td></tr>';
			echo '<tr><th scope="row">字元類型</th><td>';
			echo '<select name="wu_captcha_type">';
			foreach (array('alnum'=>'英數混合','alpha'=>'僅英文字母','numeric'=>'僅數字') as $k=>$label) {
				$sel = selected(get_option('wu_captcha_type','alnum'), $k, false);
				echo "<option value='{$k}' {$sel}>{$label}</option>";
			}
			echo '</select>';
			echo '</td></tr>';
			echo '<tr><th scope="row">大小寫</th><td>';
			echo '<select name="wu_captcha_case">';
			foreach (array('mixed'=>'大小寫混合','upper'=>'僅大寫','lower'=>'僅小寫') as $k=>$label) {
				$sel = selected(get_option('wu_captcha_case','mixed'), $k, false);
				echo "<option value='{$k}' {$sel}>{$label}</option>";
			}
			echo '</select>';
			echo '</td></tr>';
			echo '<tr><th scope="row">字元長度</th><td><input type="number" name="wu_captcha_length" min="3" max="8" value="' . intval(get_option('wu_captcha_length',5)) . '" /></td></tr>';
			echo '</table>';
			submit_button();
			echo '</form></div>';
		}
	);
});

