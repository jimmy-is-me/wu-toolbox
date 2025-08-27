<?php
if (!defined('ABSPATH')) exit;

/*
 * Stateless 4-digit captcha for WP core and WooCommerce forms
 * - No sessions, cookies, IP, or images
 * - Uses HMAC token with timestamp for validation
 * - Hidden for logged-in users
 * - Admin toggle: wu_captcha_enabled (default off)
 * - GDPR-friendly: no tracking, no external requests
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

function wu_captcha_render_field() {
	if (!get_option('wu_captcha_enabled', 0)) return; // disabled globally
	if (is_user_logged_in()) return; // hide for logged-in users
	$code = str_pad((string) wp_rand(0, 9999), 4, '0', STR_PAD_LEFT);
	$ts = time();
	$token = wu_captcha_generate_token($code, $ts);
	?>
	<p class="wu-captcha-field" style="margin-top:8px;">
		<label for="wu_captcha_input" style="display:block;font-weight:600;margin-bottom:4px;">驗證碼</label>
		<input type="text" id="wu_captcha_input" name="wu_captcha_input" inputmode="numeric" pattern="\\d{4}" maxlength="4" placeholder="請輸入 <?php echo esc_attr($code); ?>" required style="width:100%;max-width:220px;">
		<input type="hidden" name="wu_captcha_token" value="<?php echo esc_attr($token); ?>">
		<small style="display:block;color:#666;margin-top:4px;">請輸入上方提示中的 4 位數字</small>
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
				echo '<div class="notice notice-success"><p>設定已儲存。</p></div>';
			}
			echo '<div class="wrap"><h1>登入/註冊驗證碼</h1>';
			echo '<form method="post">';
			wp_nonce_field('wu_captcha_settings');
			echo '<table class="form-table">';
			echo '<tr><th scope="row">啟用驗證碼</th><td><label><input type="checkbox" name="wu_captcha_enabled" value="1" ' . checked(1, get_option('wu_captcha_enabled',0), false) . '> 啟用於登入、註冊、密碼重設與 WooCommerce 帳戶表單</label><p class="description">符合 GDPR，無外部請求與追蹤。</p></td></tr>';
			echo '</table>';
			submit_button();
			echo '</form></div>';
		}
	);
});

