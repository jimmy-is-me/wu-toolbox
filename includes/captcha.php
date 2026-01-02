<?php
if (!defined('ABSPATH')) exit;

/*
 * GDPR-friendly Image CAPTCHA for WP/WooCommerce forms
 * Version: 2.0 - Enhanced with font fallback, refresh, replay protection, and integrations
 * - Stateless: HMAC token (code + timestamp), no sessions/cookies/storage
 * - Image rendered on the fly from token; no external services
 * - Supports character sets: uppercase, lowercase, mixed; and types: alnum, alpha, numeric
 * - Anti-OCR: noise, interference lines, pixel dots
 * - Replay attack protection via transient cache
 * - Mobile-responsive with refresh button
 * - Integrated with Fluent Forms & Elementor Pro
 */

// ===== Core Functions =====

function wu_captcha_secret_key() {
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
	if (empty($token) || empty($user_input)) {
		return new WP_Error('wu_captcha_missing', '請輸入驗證碼');
	}
	
	$decoded = base64_decode($token);
	if (!$decoded || strpos($decoded, '|') === false) {
		return new WP_Error('wu_captcha_invalid', '驗證碼無效');
	}
	
	list($code, $ts, $mac) = array_pad(explode('|', $decoded, 3), 3, null);
	if (!$code || !$ts || !$mac) {
		return new WP_Error('wu_captcha_invalid', '驗證碼無效');
	}
	
	// Constant-time compare
	$expected = hash_hmac('sha256', $code . '|' . $ts, wu_captcha_secret_key());
	if (!hash_equals($expected, $mac)) {
		return new WP_Error('wu_captcha_invalid', '驗證碼錯誤');
	}
	
	// Expire after 10 minutes
	if (abs(time() - (int)$ts) > 600) {
		return new WP_Error('wu_captcha_expired', '驗證碼已過期，請重新提交');
	}
	
	// Replay attack protection (check if token was already used)
	$token_hash = md5($token);
	if (get_transient('wu_captcha_used_' . $token_hash)) {
		return new WP_Error('wu_captcha_replay', '此驗證碼已被使用，請重新整理');
	}
	
	// Code must match user input exactly
	if (trim((string)$user_input) !== (string)$code) {
		return new WP_Error('wu_captcha_mismatch', '驗證碼錯誤');
	}
	
	// Mark token as used (expire in 10 minutes)
	set_transient('wu_captcha_used_' . $token_hash, 1, 600);
	
	return true;
}

function wu_captcha_get_charset() {
	$type = get_option('wu_captcha_type', 'alnum');
	$case = get_option('wu_captcha_case', 'mixed');
	$letters = 'abcdefghijklmnopqrstuvwxyz';
	$digits = '0123456789';
	
	if ($case === 'upper') {
		$letters = strtoupper($letters);
	} elseif ($case === 'lower') {
		$letters = strtolower($letters);
	} else {
		$letters = $letters . strtoupper($letters);
	}
	
	if ($type === 'numeric') return $digits;
	if ($type === 'alpha') return $letters;
	return $letters . $digits;
}

function wu_captcha_generate_code($length = 5) {
	$charset = wu_captcha_get_charset();
	$len = max(3, min(8, intval(get_option('wu_captcha_length', $length))));
	$code = '';
	for ($i = 0; $i < $len; $i++) {
		$code .= $charset[wp_rand(0, strlen($charset) - 1)];
	}
	return $code;
}

function wu_captcha_get_font_path() {
	// Priority 1: Custom font in plugin/theme directory
	$custom_font = __DIR__ . '/fonts/captcha.ttf';
	if (file_exists($custom_font)) {
		return $custom_font;
	}
	
	// Priority 2: WordPress uploads directory
	$upload_dir = wp_upload_dir();
	$upload_font = $upload_dir['basedir'] . '/wu-captcha/captcha.ttf';
	if (file_exists($upload_font)) {
		return $upload_font;
	}
	
	// Priority 3: System fonts (Linux)
	$system_fonts = array(
		'/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
		'/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
		'/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
		'/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
		'/usr/share/fonts/truetype/freefont/FreeSansBold.ttf',
		'/usr/share/fonts/truetype/freefont/FreeSans.ttf',
	);
	
	foreach ($system_fonts as $font) {
		if (file_exists($font)) {
			return $font;
		}
	}
	
	return null; // Fallback to imagestring
}

function wu_captcha_render_image_from_code($code) {
	$char_count = strlen($code);
	$width = max(180, 32 * $char_count + 40);
	$height = 60;
	
	$img = imagecreatetruecolor($width, $height);
	$bg = imagecolorallocate($img, 255, 255, 255);
	$fg = imagecolorallocate($img, 30, 30, 30);
	$noise = imagecolorallocate($img, 200, 200, 200);
	$line_color = imagecolorallocate($img, 180, 180, 180);
	
	imagefilledrectangle($img, 0, 0, $width, $height, $bg);
	
	// Anti-OCR: Random interference lines
	for ($i = 0; $i < 5; $i++) {
		$x1 = wp_rand(0, $width);
		$y1 = wp_rand(0, $height);
		$x2 = wp_rand(0, $width);
		$y2 = wp_rand(0, $height);
		imageline($img, $x1, $y1, $x2, $y2, $line_color);
	}
	
	// Anti-OCR: Random pixel dots
	for ($i = 0; $i < 100; $i++) {
		imagesetpixel($img, wp_rand(0, $width), wp_rand(0, $height), $noise);
	}
	
	// Light grid (subtle)
	for ($x = 0; $x < $width; $x += 20) {
		imageline($img, $x, 0, $x, $height, $noise);
	}
	for ($y = 0; $y < $height; $y += 20) {
		imageline($img, 0, $y, $width, $y, $noise);
	}
	
	$font = wu_captcha_get_font_path();
	$use_ttf = function_exists('imagettftext') && $font;
	$font_size = 26;
	$baseline_y = 42;
	$spacing = ($width - 30) / max(1, $char_count);
	
	for ($i = 0; $i < $char_count; $i++) {
		$ch = $code[$i];
		$x = 15 + intval($i * $spacing + wp_rand(-3, 3));
		$angle = wp_rand(-10, 10);
		
		if ($use_ttf) {
			imagettftext($img, $font_size, $angle, $x, $baseline_y, $fg, $font, $ch);
		} else {
			// Fallback to GD built-in font
			imagestring($img, 5, $x, 20, $ch, $fg);
		}
	}
	
	header('Content-Type: image/png');
	header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
	header('Pragma: no-cache');
	header('Expires: 0');
	imagepng($img);
	imagedestroy($img);
	exit;
}

// Public image endpoint
add_action('template_redirect', function() {
	if (!isset($_GET['wu_captcha']) || !isset($_GET['token'])) return;
	
	$token = sanitize_text_field(wp_unslash($_GET['token']));
	$decoded = base64_decode($token);
	if (!$decoded) exit;
	
	list($code) = array_pad(explode('|', $decoded, 2), 2, null);
	$code = preg_replace('/[^A-Za-z0-9]/', '', (string)$code);
	if (!$code) exit;
	
	wu_captcha_render_image_from_code($code);
});

// ===== Render Field with Refresh Button =====

function wu_captcha_render_field($context = 'default') {
	if (!get_option('wu_captcha_enabled', 0)) return;
	if (is_user_logged_in()) return;
	
	$code = wu_captcha_generate_code();
	$ts = time();
	$token = wu_captcha_generate_token($code, $ts);
	$img_url = esc_url(add_query_arg(array('wu_captcha' => 1, 'token' => $token), home_url('/')));
	$unique_id = 'wu_captcha_' . wp_rand(1000, 9999);
	
	?>
	<div class="wu-captcha-field" style="margin-top:12px;margin-bottom:12px;">
		<label for="<?php echo esc_attr($unique_id); ?>_input" style="display:block;font-weight:600;margin-bottom:8px;">
			人機驗證 <span style="color:#d63638;">*</span>
		</label>
		<div class="wu-captcha-image-container" style="position:relative;display:inline-block;margin-bottom:8px;">
			<img id="<?php echo esc_attr($unique_id); ?>_img" 
			     src="<?php echo $img_url; ?>" 
			     alt="CAPTCHA" 
			     style="display:block;border:2px solid #ddd;padding:6px;background:#fff;max-width:100%;height:auto;border-radius:4px;">
			<button type="button" 
			        class="wu-captcha-refresh" 
			        onclick="wuCaptchaRefresh('<?php echo esc_js($unique_id); ?>')"
			        style="position:absolute;top:8px;right:8px;background:#0073aa;color:#fff;border:none;padding:6px 10px;cursor:pointer;border-radius:3px;font-size:12px;line-height:1;"
			        title="重新整理驗證碼">
				🔄 重新整理
			</button>
		</div>
		<div>
			<input type="text" 
			       id="<?php echo esc_attr($unique_id); ?>_input" 
			       name="wu_captcha_input" 
			       autocomplete="off" 
			       placeholder="請輸入圖片中的驗證碼" 
			       required 
			       style="width:100%;max-width:280px;padding:8px;border:1px solid #ddd;border-radius:4px;">
			<input type="hidden" 
			       id="<?php echo esc_attr($unique_id); ?>_token" 
			       name="wu_captcha_token" 
			       value="<?php echo esc_attr($token); ?>">
		</div>
		<small style="display:block;color:#666;margin-top:6px;">
			此驗證碼符合 GDPR 規範，看不清楚可點擊重新整理
		</small>
	</div>
	<script>
	function wuCaptchaRefresh(uniqueId) {
		var img = document.getElementById(uniqueId + '_img');
		var tokenField = document.getElementById(uniqueId + '_token');
		var inputField = document.getElementById(uniqueId + '_input');
		
		// Generate new token via AJAX
		var xhr = new XMLHttpRequest();
		xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);
		xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		xhr.onload = function() {
			if (xhr.status === 200) {
				var response = JSON.parse(xhr.responseText);
				if (response.success) {
					img.src = response.data.img_url + '&t=' + Date.now();
					tokenField.value = response.data.token;
					inputField.value = '';
					inputField.focus();
				}
			}
		};
		xhr.send('action=wu_captcha_refresh');
	}
	</script>
	<?php
}

// AJAX handler for refresh
add_action('wp_ajax_wu_captcha_refresh', 'wu_captcha_ajax_refresh');
add_action('wp_ajax_nopriv_wu_captcha_refresh', 'wu_captcha_ajax_refresh');

function wu_captcha_ajax_refresh() {
	$code = wu_captcha_generate_code();
	$ts = time();
	$token = wu_captcha_generate_token($code, $ts);
	$img_url = add_query_arg(array('wu_captcha' => 1, 'token' => $token), home_url('/'));
	
	wp_send_json_success(array(
		'token' => $token,
		'img_url' => $img_url
	));
}

// ===== Shortcode for Manual Placement =====

add_shortcode('wu_captcha', function($atts) {
	ob_start();
	wu_captcha_render_field('shortcode');
	return ob_get_clean();
});

// ===== CSS for Mobile Responsiveness =====

add_action('wp_head', function() {
	if (!get_option('wu_captcha_enabled', 0)) return;
	?>
	<style>
	.wu-captcha-field {
		clear: both;
		margin: 12px 0;
	}
	.wu-captcha-field label {
		display: block;
		font-weight: 600;
		margin-bottom: 8px;
		color: #333;
	}
	.wu-captcha-image-container {
		position: relative;
		display: inline-block;
		margin-bottom: 8px;
		max-width: 100%;
	}
	.wu-captcha-image-container img {
		display: block;
		max-width: 100%;
		height: auto;
		border: 2px solid #ddd;
		border-radius: 4px;
		padding: 6px;
		background: #fff;
	}
	.wu-captcha-refresh {
		position: absolute;
		top: 8px;
		right: 8px;
		background: #0073aa;
		color: #fff;
		border: none;
		padding: 6px 10px;
		cursor: pointer;
		border-radius: 3px;
		font-size: 12px;
		line-height: 1;
		transition: background 0.2s;
	}
	.wu-captcha-refresh:hover {
		background: #005177;
	}
	.wu-captcha-field input[type="text"] {
		width: 100%;
		max-width: 280px;
		padding: 10px;
		border: 1px solid #ddd;
		border-radius: 4px;
		font-size: 14px;
	}
	.wu-captcha-field input[type="text"]:focus {
		outline: none;
		border-color: #0073aa;
		box-shadow: 0 0 0 1px #0073aa;
	}
	@media (max-width: 768px) {
		.wu-captcha-field input[type="text"] {
			max-width: 100%;
		}
		.wu-captcha-refresh {
			font-size: 11px;
			padding: 5px 8px;
		}
	}
	</style>
	<?php
});

// ===== Standard Form Integrations =====

add_action('login_form', 'wu_captcha_render_field');
add_action('register_form', 'wu_captcha_render_field');
add_action('lostpassword_form', 'wu_captcha_render_field');
add_action('woocommerce_login_form', 'wu_captcha_render_field');
add_action('woocommerce_register_form', 'wu_captcha_render_field');
add_action('woocommerce_lostpassword_form', 'wu_captcha_render_field');
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

add_filter('preprocess_comment', function($commentdata) {
	if (!get_option('wu_captcha_enabled', 0)) return $commentdata;
	if (is_user_logged_in()) return $commentdata;
	
	$token = isset($_POST['wu_captcha_token']) ? sanitize_text_field(wp_unslash($_POST['wu_captcha_token'])) : '';
	$input = isset($_POST['wu_captcha_input']) ? sanitize_text_field(wp_unslash($_POST['wu_captcha_input'])) : '';
	$result = wu_captcha_validate_token($token, $input);
	
	if ($result !== true) {
		wp_die(esc_html($result->get_error_message()), 400);
	}
	return $commentdata;
});

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

// ===== Fluent Forms Integration =====

add_action('fluentform/before_insert_submission', function($insertData, $data, $form) {
	if (!get_option('wu_captcha_enabled', 0)) return;
	
	// Check if captcha fields exist in the data
	if (!isset($data['wu_captcha_input']) || !isset($data['wu_captcha_token'])) {
		wp_send_json_error(array(
			'errors' => array('wu_captcha' => '請完成人機驗證'),
		), 422);
	}
	
	$token = sanitize_text_field($data['wu_captcha_token']);
	$input = sanitize_text_field($data['wu_captcha_input']);
	$result = wu_captcha_validate_token($token, $input);
	
	if ($result !== true) {
		wp_send_json_error(array(
			'errors' => array('wu_captcha' => $result->get_error_message()),
		), 422);
	}
}, 10, 3);

// ===== Elementor Pro Forms Integration =====

add_action('elementor_pro/forms/validation', function($record, $ajax_handler) {
	if (!get_option('wu_captcha_enabled', 0)) return;
	
	$fields = $record->get('fields');
	
	// Check if captcha fields exist
	$captcha_input = null;
	$captcha_token = null;
	
	foreach ($fields as $field_id => $field) {
		if ($field['id'] === 'wu_captcha_input') {
			$captcha_input = $field['value'];
		}
		if ($field['id'] === 'wu_captcha_token') {
			$captcha_token = $field['value'];
		}
	}
	
	if (!$captcha_input || !$captcha_token) {
		$ajax_handler->add_error_message('請完成人機驗證');
		return;
	}
	
	$result = wu_captcha_validate_token($captcha_token, $captcha_input);
	
	if ($result !== true) {
		$ajax_handler->add_error_message($result->get_error_message());
	}
}, 10, 2);

// Elementor Widget for CAPTCHA
add_action('elementor/widgets/register', function($widgets_manager) {
	if (!class_exists('Elementor\Widget_Base')) return;
	
	class WU_Captcha_Elementor_Widget extends \Elementor\Widget_Base {
		
		public function get_name() {
			return 'wu_captcha';
		}
		
		public function get_title() {
			return '人機驗證碼';
		}
		
		public function get_icon() {
			return 'eicon-lock-user';
		}
		
		public function get_categories() {
			return ['general'];
		}
		
		protected function render() {
			wu_captcha_render_field('elementor');
		}
	}
	
	$widgets_manager->register(new WU_Captcha_Elementor_Widget());
});

// ===== Settings Page =====

add_action('admin_init', function() {
	add_option('wu_captcha_enabled', 0);
	add_option('wu_captcha_type', 'alnum');
	add_option('wu_captcha_case', 'mixed');
	add_option('wu_captcha_length', 5);
});

add_action('admin_menu', function() {
	add_submenu_page(
		'wumetax-toolkit',
		'驗證碼設定',
		'登入/註冊驗證碼',
		'manage_options',
		'wu-captcha-settings',
		'wu_captcha_settings_page'
	);
});

function wu_captcha_settings_page() {
	if (isset($_POST['submit'])) {
		check_admin_referer('wu_captcha_settings');
		update_option('wu_captcha_enabled', isset($_POST['wu_captcha_enabled']) ? 1 : 0);
		update_option('wu_captcha_type', in_array($_POST['wu_captcha_type'] ?? 'alnum', array('alnum', 'alpha', 'numeric'), true) ? sanitize_text_field($_POST['wu_captcha_type']) : 'alnum');
		update_option('wu_captcha_case', in_array($_POST['wu_captcha_case'] ?? 'mixed', array('upper', 'lower', 'mixed'), true) ? sanitize_text_field($_POST['wu_captcha_case']) : 'mixed');
		$len = max(3, min(8, intval($_POST['wu_captcha_length'] ?? 5)));
		update_option('wu_captcha_length', $len);
		echo '<div class="notice notice-success"><p>設定已儲存。</p></div>';
	}
	
	$font_status = wu_captcha_check_font_status();
	
	?>
	<div class="wrap">
		<h1>登入/註冊驗證碼設定</h1>
		
		<div class="notice notice-info">
			<h3>功能特色</h3>
			<ul style="margin-left: 20px;">
				<li>✅ 完全符合 GDPR 規範（無外部請求、無追蹤、無 Cookie）</li>
				<li>✅ 防重放攻擊（Replay Attack Protection）</li>
				<li>✅ 自動字體回退機制（支援 Windows/Linux/自訂字體）</li>
				<li>✅ 前端重新整理按鈕（無需重新載入頁面）</li>
				<li>✅ 響應式設計（支援手機與平板）</li>
				<li>✅ 抗 OCR 干擾（噪點、干擾線、隨機角度）</li>
				<li>✅ 整合 Fluent Forms 與 Elementor Pro</li>
			</ul>
		</div>
		
		<?php if (!$font_status['has_font']): ?>
		<div class="notice notice-warning">
			<h3>⚠️ 字體警告</h3>
			<p>系統未偵測到 TrueType 字體,目前使用 GD 內建字體(較不美觀)。</p>
			<p><strong>建議操作:</strong></p>
			<ol style="margin-left: 20px;">
				<li>下載免費字體: <a href="https://github.com/dejavu-fonts/dejavu-fonts/releases" target="_blank">DejaVu Sans</a> 或 <a href="https://fonts.google.com/specimen/Roboto" target="_blank">Google Roboto</a></li>
				<li>建立目錄: <code><?php echo esc_html(__DIR__ . '/fonts/'); ?></code></li>
				<li>上傳字體檔案並重新命名為: <code>captcha.ttf</code></li>
				<li>重新載入此頁面以確認</li>
			</ol>
		</div>
		<?php else: ?>
		<div class="notice notice-success">
			<p>✅ 字體狀態正常: <code><?php echo esc_html($font_status['font_path']); ?></code></p>
		</div>
		<?php endif; ?>
		
		<form method="post">
			<?php wp_nonce_field('wu_captcha_settings'); ?>
			
			<table class="form-table">
				<tr>
					<th scope="row">啟用驗證碼</th>
					<td>
						<label>
							<input type="checkbox" name="wu_captcha_enabled" value="1" <?php checked(1, get_option('wu_captcha_enabled', 0)); ?>>
							啟用於留言、登入、註冊、密碼重設與 WooCommerce 帳戶表單
						</label>
						<p class="description">已登入的用戶不會看到驗證碼</p>
					</td>
				</tr>
				
				<tr>
					<th scope="row">字元類型</th>
					<td>
						<select name="wu_captcha_type">
							<?php foreach (array('alnum' => '英數混合', 'alpha' => '僅英文字母', 'numeric' => '僅數字') as $k => $label): ?>
								<option value="<?php echo esc_attr($k); ?>" <?php selected(get_option('wu_captcha_type', 'alnum'), $k); ?>>
									<?php echo esc_html($label); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				
				<tr>
					<th scope="row">大小寫</th>
					<td>
						<select name="wu_captcha_case">
							<?php foreach (array('mixed' => '大小寫混合', 'upper' => '僅大寫', 'lower' => '僅小寫') as $k => $label): ?>
								<option value="<?php echo esc_attr($k); ?>" <?php selected(get_option('wu_captcha_case', 'mixed'), $k); ?>>
									<?php echo esc_html($label); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				
				<tr>
					<th scope="row">字元長度</th>
					<td>
						<input type="number" name="wu_captcha_length" min="3" max="8" value="<?php echo intval(get_option('wu_captcha_length', 5)); ?>">
						<p class="description">建議 4-6 個字元以平衡安全性與易用性</p>
					</td>
				</tr>
			</table>
			
			<?php submit_button('儲存設定'); ?>
		</form>
		
		<hr>
		
		<h2>使用說明</h2>
		
		<div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
			<h3>自動整合 (無需額外設定)</h3>
			<ul style="margin-left: 20px;">
				<li>WordPress 登入/註冊/忘記密碼表單</li>
				<li>WooCommerce 登入/註冊/忘記密碼表單</li>
				<li>WordPress 留言表單</li>
			</ul>
			
			<h3>手動整合方式</h3>
			
			<h4>1. Fluent Forms</h4>
			<p>在表單中新增「自訂 HTML」欄位,貼上以下短代碼:</p>
			<pre style="background: #f5f5f5; padding: 10px; border-radius: 3px;">[wu_captcha]</pre>
			
			<h4>2. Elementor Pro</h4>
			<p>方法 A: 在小工具列表中搜尋「人機驗證碼」並拖曳到表單中</p>
			<p>方法 B: 新增「短代碼」小工具,輸入: <code>[wu_captcha]</code></p>
			
			<h4>3. 其他表單外掛</h4>
			<p>在表單中插入短代碼: <code>[wu_captcha]</code></p>
		</div>
		
		<hr>
		
		<h2>預覽測試</h2>
		<div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
			<?php wu_captcha_render_field('preview'); ?>
		</div>
	</div>
	<?php
}

function wu_captcha_check_font_status() {
	$font_path = wu_captcha_get_font_path();
	return array(
		'has_font' => !empty($font_path),
		'font_path' => $font_path ?: 'GD 內建字體 (imagestring)'
	);
}
