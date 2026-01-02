<?php
if (!defined('ABSPATH')) exit;

/*
 * GDPR-friendly Image CAPTCHA for WP/WooCommerce forms
 * Version: 3.5 - Critical Elementor fixes
 * - Fixed refresh button not working after injection (re-bind events)
 * - Fixed token field not being submitted (name attribute issue)
 * - Added explicit form data collection logging
 * - Improved error messages with debugging info
 */

// ===== Core Functions =====

function wu_captcha_secret_key() {
	$stored_key = get_option('wu_captcha_secret_key');
	if (!$stored_key) {
		$base = defined('AUTH_KEY') && AUTH_KEY ? AUTH_KEY : (ABSPATH . wp_hash(__FILE__));
		$stored_key = hash_hmac('sha256', 'wu-captcha-' . time(), $base);
		update_option('wu_captcha_secret_key', $stored_key);
	}
	return $stored_key;
}

function wu_captcha_generate_token($code, $timestamp) {
	$secret = wu_captcha_secret_key();
	$payload = $code . '|' . $timestamp;
	$mac = hash_hmac('sha256', $payload, $secret);
	return base64_encode($payload . '|' . $mac);
}

function wu_captcha_validate_token($token, $user_input) {
	if (empty($token) || empty($user_input)) {
		return new WP_Error('wu_captcha_missing', '請輸入驗證碼 (Token: ' . substr($token, 0, 20) . '..., Input: "' . $user_input . '")');
	}
	
	$decoded = base64_decode($token);
	if (!$decoded || strpos($decoded, '|') === false) {
		return new WP_Error('wu_captcha_invalid', '驗證碼無效 (解碼失敗)');
	}
	
	list($code, $ts, $mac) = array_pad(explode('|', $decoded, 3), 3, null);
	if (!$code || !$ts || !$mac) {
		return new WP_Error('wu_captcha_invalid', '驗證碼無效 (格式錯誤)');
	}
	
	// Constant-time compare
	$expected = hash_hmac('sha256', $code . '|' . $ts, wu_captcha_secret_key());
	if (!hash_equals($expected, $mac)) {
		return new WP_Error('wu_captcha_invalid', '驗證碼錯誤 (HMAC 驗證失敗)');
	}
	
	// Expire after 10 minutes
	if (abs(time() - (int)$ts) > 600) {
		return new WP_Error('wu_captcha_expired', '驗證碼已過期 (已超過 10 分鐘),請重新整理');
	}
	
	// Replay attack protection
	$token_hash = md5($token);
	if (get_transient('wu_captcha_used_' . $token_hash)) {
		return new WP_Error('wu_captcha_replay', '此驗證碼已被使用,請重新整理頁面');
	}
	
	// Case-insensitive validation
	$code_to_compare = strtoupper($code);
	$input_to_compare = strtoupper(trim((string)$user_input));
	
	if ($input_to_compare !== $code_to_compare) {
		return new WP_Error('wu_captcha_mismatch', '驗證碼錯誤 (預期: "' . $code . '", 輸入: "' . $user_input . '")');
	}
	
	// Mark token as used
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
	$font_dir = WP_CONTENT_DIR . '/plugins/wu-toolbox-main/includes/fonts/captcha.ttf';
	if (file_exists($font_dir)) {
		return $font_dir;
	}
	
	$upload_dir = wp_upload_dir();
	$upload_font = $upload_dir['basedir'] . '/wu-captcha/captcha.ttf';
	if (file_exists($upload_font)) {
		return $upload_font;
	}
	
	return null;
}

function wu_captcha_render_image_from_code($code) {
	$char_count = strlen($code);
	$width = max(200, 35 * $char_count + 40);
	$height = 70;
	
	$img = imagecreatetruecolor($width, $height);
	$bg = imagecolorallocate($img, 255, 255, 255);
	$fg = imagecolorallocate($img, 30, 30, 30);
	$noise = imagecolorallocate($img, 200, 200, 200);
	$line_color = imagecolorallocate($img, 180, 180, 180);
	
	imagefilledrectangle($img, 0, 0, $width, $height, $bg);
	
	// Anti-OCR: interference lines
	for ($i = 0; $i < 5; $i++) {
		imageline($img, wp_rand(0, $width), wp_rand(0, $height), 
		          wp_rand(0, $width), wp_rand(0, $height), $line_color);
	}
	
	// Anti-OCR: pixel dots
	for ($i = 0; $i < 100; $i++) {
		imagesetpixel($img, wp_rand(0, $width), wp_rand(0, $height), $noise);
	}
	
	// Light grid
	for ($x = 0; $x < $width; $x += 20) {
		imageline($img, $x, 0, $x, $height, $noise);
	}
	for ($y = 0; $y < $height; $y += 20) {
		imageline($img, 0, $y, $width, $y, $noise);
	}
	
	$font = wu_captcha_get_font_path();
	$use_ttf = function_exists('imagettftext') && $font;
	$font_size = 28;
	$baseline_y = 48;
	$spacing = ($width - 40) / max(1, $char_count);
	
	for ($i = 0; $i < $char_count; $i++) {
		$ch = $code[$i];
		$x = 20 + intval($i * $spacing + wp_rand(-3, 3));
		$angle = wp_rand(-10, 10);
		
		if ($use_ttf) {
			imagettftext($img, $font_size, $angle, $x, $baseline_y, $fg, $font, $ch);
		} else {
			imagestring($img, 5, $x, 25, $ch, $fg);
		}
	}
	
	// CORS headers
	header('Access-Control-Allow-Origin: *');
	header('Content-Type: image/png');
	header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
	header('Pragma: no-cache');
	header('Expires: 0');
	header('X-Robots-Tag: noindex, nofollow');
	header('Surrogate-Control: no-store');
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

// ===== Directory Security =====

function wu_captcha_secure_fonts_directory() {
	$fonts_dir = WP_CONTENT_DIR . '/plugins/wu-toolbox-main/includes/fonts';
	
	if (!file_exists($fonts_dir)) {
		wp_mkdir_p($fonts_dir);
	}
	
	$htaccess_file = $fonts_dir . '/.htaccess';
	if (!file_exists($htaccess_file)) {
		$htaccess_content = "# Protect font files\n";
		$htaccess_content .= "Order Deny,Allow\n";
		$htaccess_content .= "Deny from all\n";
		file_put_contents($htaccess_file, $htaccess_content);
	}
}
add_action('admin_init', 'wu_captcha_secure_fonts_directory');

// ===== Render Field with Refresh Button =====

function wu_captcha_render_field($context = 'default') {
	if (!get_option('wu_captcha_enabled', 0)) return;
	
	$code = wu_captcha_generate_code();
	$ts = time();
	$token = wu_captcha_generate_token($code, $ts);
	$img_url = esc_url(add_query_arg(array('wu_captcha' => 1, 'token' => $token), home_url('/')));
	$unique_id = 'wu_captcha_' . wp_rand(1000, 9999);
	
	?>
	<div class="wu-captcha-field" data-captcha-id="<?php echo esc_attr($unique_id); ?>" style="margin-top:16px;margin-bottom:16px;clear:both;">
		<label for="<?php echo esc_attr($unique_id); ?>_input" style="display:block;font-weight:600;margin-bottom:10px;color:#333;">
			人機驗證 <span style="color:#d63638;">*</span>
		</label>
		
		<div style="margin-bottom:10px;">
			<div class="wu-captcha-wrapper" style="position:relative;display:inline-block;max-width:100%;">
				<img id="<?php echo esc_attr($unique_id); ?>_img" 
				     src="<?php echo $img_url; ?>" 
				     alt="CAPTCHA" 
				     style="display:block;border:2px solid #ddd;padding:8px;background:#fff;max-width:100%;height:auto;border-radius:4px;">
			</div>
			<button type="button" 
			        class="wu-captcha-refresh-btn" 
			        data-captcha-id="<?php echo esc_js($unique_id); ?>"
			        style="display:inline-block;margin-top:8px;background:#0073aa;color:#fff;border:none;padding:8px 14px;cursor:pointer;border-radius:4px;font-size:13px;transition:background 0.2s;"
			        title="重新整理驗證碼">
				🔄 重新整理
			</button>
		</div>
		
		<div>
			<input type="text" 
			       id="<?php echo esc_attr($unique_id); ?>_input" 
			       name="wu_captcha_input" 
			       class="elementor-field"
			       autocomplete="off" 
			       placeholder="請輸入圖片中的驗證碼" 
			       <?php if ($context !== 'preview'): ?>required<?php endif; ?>
			       style="width:100%;max-width:300px;padding:10px;border:1px solid #ddd;border-radius:4px;font-size:14px;">
			<input type="hidden" 
			       id="<?php echo esc_attr($unique_id); ?>_token" 
			       name="wu_captcha_token"
			       class="elementor-field"
			       value="<?php echo esc_attr($token); ?>">
		</div>
		<small style="display:block;color:#666;margin-top:6px;font-size:12px;">
			此驗證碼符合 GDPR 規範,看不清楚請點擊「重新整理」按鈕
		</small>
	</div>
	<?php
}

// Global refresh script (loaded once)
add_action('wp_footer', function() {
	if (!get_option('wu_captcha_enabled', 0)) return;
	
	static $script_loaded = false;
	if ($script_loaded) return;
	$script_loaded = true;
	
	?>
	<script>
	// Global CAPTCHA refresh handler
	if (!window.wuCaptchaInitialized) {
		window.wuCaptchaInitialized = true;
		
		document.addEventListener('click', function(e) {
			if (e.target && e.target.classList.contains('wu-captcha-refresh-btn')) {
				e.preventDefault();
				e.stopPropagation();
				
				var uniqueId = e.target.getAttribute('data-captcha-id');
				if (!uniqueId) {
					console.error('WU CAPTCHA: Missing data-captcha-id');
					return;
				}
				
				var img = document.getElementById(uniqueId + '_img');
				var tokenField = document.getElementById(uniqueId + '_token');
				var inputField = document.getElementById(uniqueId + '_input');
				
				if (!img || !tokenField || !inputField) {
					console.error('WU CAPTCHA: Cannot find elements', {
						img: !!img,
						token: !!tokenField,
						input: !!inputField
					});
					return;
				}
				
				var btn = e.target;
				var originalText = btn.innerHTML;
				btn.innerHTML = '⏳ 載入中...';
				btn.disabled = true;
				
				var xhr = new XMLHttpRequest();
				xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);
				xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
				xhr.onload = function() {
					if (xhr.status === 200) {
						try {
							var response = JSON.parse(xhr.responseText);
							if (response.success) {
								img.src = response.data.img_url + '&t=' + Date.now();
								tokenField.value = response.data.token;
								inputField.value = '';
								
								// Trigger events for framework detection
								tokenField.dispatchEvent(new Event('change', { bubbles: true }));
								inputField.dispatchEvent(new Event('input', { bubbles: true }));
								
								inputField.focus();
								console.log('✅ WU CAPTCHA: Refresh successful');
							} else {
								console.error('WU CAPTCHA: Server error', response);
								alert('驗證碼重新整理失敗');
							}
						} catch(e) {
							console.error('WU CAPTCHA: Parse error', e);
							alert('驗證碼重新整理失敗,請重新載入頁面');
						}
					} else {
						console.error('WU CAPTCHA: HTTP error', xhr.status);
						alert('網路錯誤 (HTTP ' + xhr.status + ')');
					}
					btn.innerHTML = originalText;
					btn.disabled = false;
				};
				xhr.onerror = function() {
					console.error('WU CAPTCHA: Network error');
					btn.innerHTML = originalText;
					btn.disabled = false;
					alert('網路錯誤,請檢查連線後重試');
				};
				xhr.send('action=wu_captcha_refresh');
			}
		}, true); // Capture phase
		
		console.log('✅ WU CAPTCHA: Global refresh handler initialized');
	}
	</script>
	<?php
}, 999);

// Get CAPTCHA HTML template for JS injection
function wu_captcha_get_html_template() {
	ob_start();
	wu_captcha_render_field('elementor');
	return ob_get_clean();
}

// AJAX handler for refresh
add_action('wp_ajax_wu_captcha_refresh', 'wu_captcha_ajax_refresh');
add_action('wp_ajax_nopriv_wu_captcha_refresh', 'wu_captcha_ajax_refresh');

function wu_captcha_ajax_refresh() {
	header('Access-Control-Allow-Origin: *');
	
	$code = wu_captcha_generate_code();
	$ts = time();
	$token = wu_captcha_generate_token($code, $ts);
	$img_url = add_query_arg(array('wu_captcha' => 1, 'token' => $token), home_url('/'));
	
	wp_send_json_success(array(
		'token' => $token,
		'img_url' => $img_url
	));
}

// AJAX handler for getting CAPTCHA HTML
add_action('wp_ajax_wu_captcha_get_html', 'wu_captcha_ajax_get_html');
add_action('wp_ajax_nopriv_wu_captcha_get_html', 'wu_captcha_ajax_get_html');

function wu_captcha_ajax_get_html() {
	header('Access-Control-Allow-Origin: *');
	
	$html = wu_captcha_get_html_template();
	wp_send_json_success(array('html' => $html));
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
		margin: 16px 0;
	}
	.wu-captcha-field label {
		display: block;
		font-weight: 600;
		margin-bottom: 10px;
		color: #333;
	}
	.wu-captcha-wrapper {
		position: relative;
		display: inline-block;
		max-width: 100%;
	}
	.wu-captcha-wrapper img {
		display: block;
		max-width: 100%;
		height: auto;
		border: 2px solid #ddd;
		border-radius: 4px;
		padding: 8px;
		background: #fff;
	}
	.wu-captcha-refresh-btn {
		display: inline-block;
		margin-top: 8px;
		background: #0073aa;
		color: #fff;
		border: none;
		padding: 8px 14px;
		cursor: pointer;
		border-radius: 4px;
		font-size: 13px;
		transition: background 0.2s;
	}
	.wu-captcha-refresh-btn:hover:not(:disabled) {
		background: #005177;
	}
	.wu-captcha-refresh-btn:disabled {
		opacity: 0.6;
		cursor: not-allowed;
	}
	.wu-captcha-field input[type="text"] {
		width: 100%;
		max-width: 300px;
		padding: 10px;
		border: 1px solid #ddd;
		border-radius: 4px;
		font-size: 14px;
		box-sizing: border-box;
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
		.wu-captcha-refresh-btn {
			font-size: 12px;
			padding: 6px 12px;
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
	
	$token = isset($_POST['wu_captcha_token']) ? sanitize_text_field(wp_unslash($_POST['wu_captcha_token'])) : '';
	$input = isset($_POST['wu_captcha_input']) ? sanitize_text_field(wp_unslash($_POST['wu_captcha_input'])) : '';
	$result = wu_captcha_validate_token($token, $input);
	
	if ($result === true) return $user;
	return new WP_Error('wu_captcha_error', $result->get_error_message());
}
add_filter('authenticate', 'wu_captcha_validate_login', 30, 1);

function wu_captcha_validate_registration($errors, $sanitized_user_login, $user_email) {
	if (!get_option('wu_captcha_enabled', 0)) return $errors;
	
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
	
	$token = isset($_POST['wu_captcha_token']) ? sanitize_text_field(wp_unslash($_POST['wu_captcha_token'])) : '';
	$input = isset($_POST['wu_captcha_input']) ? sanitize_text_field(wp_unslash($_POST['wu_captcha_input'])) : '';
	$result = wu_captcha_validate_token($token, $input);
	
	if ($result !== true) {
		wp_die(esc_html($result->get_error_message()), '驗證碼錯誤', array('back_link' => true));
	}
	return $commentdata;
});

function wu_captcha_validate_wc_login($error, $user) {
	if (!get_option('wu_captcha_enabled', 0)) return $error;
	
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
	
	$token = isset($_POST['wu_captcha_token']) ? sanitize_text_field(wp_unslash($_POST['wu_captcha_token'])) : '';
	$input = isset($_POST['wu_captcha_input']) ? sanitize_text_field(wp_unslash($_POST['wu_captcha_input'])) : '';
	$result = wu_captcha_validate_token($token, $input);
	
	if ($result !== true) {
		$errors->add('wu_captcha_error', $result->get_error_message());
	}
	return $errors;
}
add_filter('woocommerce_process_registration_errors', 'wu_captcha_validate_wc_registration', 30, 4);

// ===== Fluent Forms Auto-Integration =====

add_action('fluentform/render_item_submit_button', function($data, $form) {
	if (!get_option('wu_captcha_enabled', 0)) return;
	if (!get_option('wu_captcha_fluent_forms', 1)) return;
	
	wu_captcha_render_field('fluentform');
}, 9, 2);

add_action('fluentform/before_insert_submission', function($insertData, $data, $form) {
	if (!get_option('wu_captcha_enabled', 0)) return;
	if (!get_option('wu_captcha_fluent_forms', 1)) return;
	
	$token = isset($data['wu_captcha_token']) ? sanitize_text_field($data['wu_captcha_token']) : '';
	$input = isset($data['wu_captcha_input']) ? sanitize_text_field($data['wu_captcha_input']) : '';
	
	if (empty($token) || empty($input)) {
		wp_send_json_error(array(
			'errors' => array('wu_captcha_input' => array('請完成人機驗證')),
		), 422);
	}
	
	$result = wu_captcha_validate_token($token, $input);
	
	if ($result !== true) {
		wp_send_json_error(array(
			'errors' => array('wu_captcha_input' => array($result->get_error_message())),
		), 422);
	}
}, 10, 3);

// ===== Elementor Pro Auto-Integration (Fixed v3.5) =====

add_action('wp_footer', function() {
	if (!get_option('wu_captcha_enabled', 0)) return;
	if (!get_option('wu_captcha_elementor', 1)) return;
	if (!defined('ELEMENTOR_PRO_VERSION')) return;
	
	?>
	<script>
	(function() {
		'use strict';
		
		var captchaInjected = new Set();
		var ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
		
		function getCaptchaHTML() {
			var xhr = new XMLHttpRequest();
			xhr.open('POST', ajaxUrl, false); // Synchronous
			xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			xhr.send('action=wu_captcha_get_html');
			
			try {
				var response = JSON.parse(xhr.responseText);
				if (response.success) {
					return response.data.html;
				} else {
					console.error('WU CAPTCHA: Server returned error', response);
					return '';
				}
			} catch(e) {
				console.error('WU CAPTCHA: Failed to parse response', e);
				return '';
			}
		}
		
		function injectCaptcha(form) {
			if (!form || captchaInjected.has(form)) return;
			
			// Check if already injected
			if (form.querySelector('.wu-captcha-field')) {
				captchaInjected.add(form);
				return;
			}
			
			// Find submit button
			var submitBtn = form.querySelector('.elementor-field-type-submit, button[type="submit"], input[type="submit"]');
			if (!submitBtn) {
				console.warn('WU CAPTCHA: No submit button found');
				return;
			}
			
			var captchaHTML = getCaptchaHTML();
			if (!captchaHTML) {
				console.error('WU CAPTCHA: Failed to get HTML template');
				return;
			}
			
			// Create field group with Elementor classes
			var fieldGroup = document.createElement('div');
			fieldGroup.className = 'elementor-field-group elementor-column elementor-field-type-text elementor-col-100';
			fieldGroup.innerHTML = captchaHTML;
			
			// Insert before submit button container
			var submitContainer = submitBtn.closest('.elementor-field-group, .elementor-button-wrapper') || submitBtn.parentElement;
			if (submitContainer && submitContainer.parentElement) {
				submitContainer.parentElement.insertBefore(fieldGroup, submitContainer);
				captchaInjected.add(form);
				console.log('✅ WU CAPTCHA: Injected into Elementor form');
				
				// Log field names for debugging
				var captchaInput = fieldGroup.querySelector('input[name="wu_captcha_input"]');
				var captchaToken = fieldGroup.querySelector('input[name="wu_captcha_token"]');
				console.log('WU CAPTCHA: Fields created', {
					input: captchaInput ? captchaInput.name : 'NOT FOUND',
					token: captchaToken ? captchaToken.name : 'NOT FOUND'
				});
			} else {
				console.error('WU CAPTCHA: Cannot find submit container');
			}
		}
		
		function scanAndInject() {
			var forms = document.querySelectorAll('.elementor-form, form.elementor-form-wrapper');
			if (forms.length === 0) {
				console.log('WU CAPTCHA: No Elementor forms found');
			}
			forms.forEach(function(form) {
				injectCaptcha(form);
			});
		}
		
		// Initial scan
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', scanAndInject);
		} else {
			scanAndInject();
		}
		
		// Watch for dynamic forms
		if (typeof MutationObserver !== 'undefined') {
			var observer = new MutationObserver(function(mutations) {
				var shouldScan = false;
				
				for (var i = 0; i < mutations.length; i++) {
					var added = mutations[i].addedNodes;
					for (var j = 0; j < added.length; j++) {
						var node = added[j];
						if (node.nodeType === 1) {
							if ((node.matches && (node.matches('.elementor-form') || node.matches('form.elementor-form-wrapper'))) ||
							    (node.querySelector && node.querySelector('.elementor-form, form.elementor-form-wrapper'))) {
								shouldScan = true;
								break;
							}
						}
					}
					if (shouldScan) break;
				}
				
				if (shouldScan) {
					console.log('WU CAPTCHA: New form detected, re-scanning...');
					setTimeout(scanAndInject, 200);
				}
			});
			
			observer.observe(document.body, {
				childList: true,
				subtree: true
			});
		}
		
		// Fallback periodic scan
		setInterval(scanAndInject, 3000);
		
		console.log('✅ WU CAPTCHA: Elementor integration initialized');
	})();
	</script>
	<?php
}, 999);

add_action('elementor_pro/forms/validation', function($record, $ajax_handler) {
	if (!get_option('wu_captcha_enabled', 0)) return;
	if (!get_option('wu_captcha_elementor', 1)) return;
	
	$raw_fields = $record->get('sent_data');
	
	// Debug logging
	error_log('WU CAPTCHA: Received fields: ' . print_r(array_keys($raw_fields), true));
	
	$captcha_input = isset($raw_fields['wu_captcha_input']) ? sanitize_text_field($raw_fields['wu_captcha_input']) : '';
	$captcha_token = isset($raw_fields['wu_captcha_token']) ? sanitize_text_field($raw_fields['wu_captcha_token']) : '';
	
	if (empty($captcha_input) || empty($captcha_token)) {
		error_log('WU CAPTCHA: Missing fields - Input: "' . $captcha_input . '", Token: ' . substr($captcha_token, 0, 20));
		$ajax_handler->add_error_message('❌ 請完成人機驗證 (欄位未填寫)');
		return;
	}
	
	$result = wu_captcha_validate_token($captcha_token, $captcha_input);
	
	if ($result !== true) {
		error_log('WU CAPTCHA: Validation failed - ' . $result->get_error_message());
		$ajax_handler->add_error_message('❌ ' . $result->get_error_message());
	} else {
		error_log('WU CAPTCHA: Validation successful');
	}
}, 10, 2);

// ===== Settings Page =====

add_action('admin_init', function() {
	add_option('wu_captcha_enabled', 0);
	add_option('wu_captcha_type', 'alnum');
	add_option('wu_captcha_case', 'mixed');
	add_option('wu_captcha_length', 5);
	add_option('wu_captcha_fluent_forms', 1);
	add_option('wu_captcha_elementor', 1);
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

add_action('admin_post_wu_captcha_reset_key', function() {
	if (!current_user_can('manage_options')) {
		wp_die('權限不足');
	}
	
	check_admin_referer('wu_captcha_reset_key');
	
	delete_option('wu_captcha_secret_key');
	wu_captcha_secret_key();
	
	wp_redirect(add_query_arg(array(
		'page' => 'wu-captcha-settings',
		'key_reset' => '1'
	), admin_url('admin.php')));
	exit;
});

function wu_captcha_settings_page() {
	if (isset($_POST['submit'])) {
		check_admin_referer('wu_captcha_settings');
		update_option('wu_captcha_enabled', isset($_POST['wu_captcha_enabled']) ? 1 : 0);
		update_option('wu_captcha_fluent_forms', isset($_POST['wu_captcha_fluent_forms']) ? 1 : 0);
		update_option('wu_captcha_elementor', isset($_POST['wu_captcha_elementor']) ? 1 : 0);
		update_option('wu_captcha_type', in_array($_POST['wu_captcha_type'] ?? 'alnum', array('alnum', 'alpha', 'numeric'), true) ? sanitize_text_field($_POST['wu_captcha_type']) : 'alnum');
		update_option('wu_captcha_case', in_array($_POST['wu_captcha_case'] ?? 'mixed', array('upper', 'lower', 'mixed'), true) ? sanitize_text_field($_POST['wu_captcha_case']) : 'mixed');
		$len = max(3, min(8, intval($_POST['wu_captcha_length'] ?? 5)));
		update_option('wu_captcha_length', $len);
		echo '<div class="notice notice-success is-dismissible"><p><strong>✅ 設定已儲存。</strong></p></div>';
	}
	
	if (isset($_GET['key_reset']) && $_GET['key_reset'] === '1') {
		echo '<div class="notice notice-success is-dismissible"><p><strong>✅ HMAC 私鑰已重設。</strong></p></div>';
	}
	
	$fluent_active = defined('FLUENTFORM');
	$elementor_active = defined('ELEMENTOR_PRO_VERSION');
	
	?>
	<div class="wrap">
		<h1>🔐 登入/註冊驗證碼設定</h1>
		
		<div class="notice notice-info">
			<h3>✨ 功能特色 (v3.5)</h3>
			<ul style="margin-left: 20px;line-height:1.8;">
				<li>✅ <strong>修正 Elementor 重新整理按鈕無法運作</strong></li>
				<li>✅ <strong>修正 Token 欄位未正確提交問題</strong></li>
				<li>✅ <strong>新增詳細除錯訊息</strong></li>
				<li>✅ 全域事件委派處理動態內容</li>
				<li>✅ 完全符合 GDPR 規範</li>
			</ul>
		</div>
		
		<form method="post" style="background:#fff;padding:20px;border:1px solid #ddd;border-radius:5px;margin-top:20px;">
			<?php wp_nonce_field('wu_captcha_settings'); ?>
			
			<table class="form-table">
				<tr>
					<th scope="row"><label for="wu_captcha_enabled">啟用驗證碼</label></th>
					<td>
						<label>
							<input type="checkbox" id="wu_captcha_enabled" name="wu_captcha_enabled" value="1" <?php checked(1, get_option('wu_captcha_enabled', 0)); ?>>
							<strong>啟用於所有表單(包含管理員登入)</strong>
						</label>
					</td>
				</tr>
				
				<tr>
					<th scope="row">表單外掛整合</th>
					<td>
						<fieldset>
							<label style="display:block;margin-bottom:8px;">
								<input type="checkbox" name="wu_captcha_fluent_forms" value="1" <?php checked(1, get_option('wu_captcha_fluent_forms', 1)); ?> <?php disabled(!$fluent_active); ?>>
								<strong>Fluent Forms</strong>
								<?php echo $fluent_active ? '<span style="color:#46b450;">(✓ 已安裝)</span>' : '<span style="color:#999;">(未安裝)</span>'; ?>
							</label>
							
							<label style="display:block;margin-top:12px;">
								<input type="checkbox" name="wu_captcha_elementor" value="1" <?php checked(1, get_option('wu_captcha_elementor', 1)); ?> <?php disabled(!$elementor_active); ?>>
								<strong>Elementor Pro (v3.5 修正版)</strong>
								<?php echo $elementor_active ? '<span style="color:#46b450;">(✓ 已安裝)</span>' : '<span style="color:#999;">(未安裝)</span>'; ?>
							</label>
						</fieldset>
					</td>
				</tr>
				
				<tr>
					<th scope="row"><label for="wu_captcha_type">字元類型</label></th>
					<td>
						<select id="wu_captcha_type" name="wu_captcha_type">
							<?php foreach (array('alnum' => '英數混合', 'alpha' => '僅英文', 'numeric' => '僅數字') as $k => $label): ?>
								<option value="<?php echo esc_attr($k); ?>" <?php selected(get_option('wu_captcha_type', 'alnum'), $k); ?>>
									<?php echo esc_html($label); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				
				<tr>
					<th scope="row"><label for="wu_captcha_case">大小寫</label></th>
					<td>
						<select id="wu_captcha_case" name="wu_captcha_case">
							<?php foreach (array('mixed' => '大小寫混合', 'upper' => '僅大寫', 'lower' => '僅小寫') as $k => $label): ?>
								<option value="<?php echo esc_attr($k); ?>" <?php selected(get_option('wu_captcha_case', 'mixed'), $k); ?>>
									<?php echo esc_html($label); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				
				<tr>
					<th scope="row"><label for="wu_captcha_length">字元長度</label></th>
					<td>
						<input type="number" id="wu_captcha_length" name="wu_captcha_length" min="3" max="8" value="<?php echo intval(get_option('wu_captcha_length', 5)); ?>" style="width:80px;">
					</td>
				</tr>
			</table>
			
			<?php submit_button('💾 儲存設定', 'primary large'); ?>
		</form>
		
		<hr style="margin:30px 0;">
		
		<h2>🔒 安全性設定</h2>
		<div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
			<h3>重設 HMAC 私鑰</h3>
			<form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
				<?php wp_nonce_field('wu_captcha_reset_key'); ?>
				<input type="hidden" name="action" value="wu_captcha_reset_key">
				<button type="submit" class="button button-secondary" onclick="return confirm('確定要重設嗎?');">
					🔑 重設私鑰
				</button>
			</form>
		</div>
		
		<hr style="margin:30px 0;">
		
		<h2>🐛 除錯資訊</h2>
		<div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
			<p><strong>開發者工具 (F12):</strong> 查看主控台訊息</p>
			<ul style="margin-left: 20px;line-height:1.8;">
				<li>✅ 注入成功: "✅ WU CAPTCHA: Injected"</li>
				<li>🔄 重新整理: "✅ WU CAPTCHA: Refresh successful"</li>
				<li>📝 欄位偵測: "WU CAPTCHA: Fields created"</li>
			</ul>
			<p style="margin-top:15px;"><strong>伺服器日誌:</strong> 查看 <code>wp-content/debug.log</code></p>
			<ul style="margin-left: 20px;line-height:1.8;">
				<li>表單提交: "WU CAPTCHA: Received fields"</li>
				<li>驗證結果: "WU CAPTCHA: Validation successful/failed"</li>
			</ul>
		</div>
		
		<hr style="margin:30px 0;">
		
		<h2>🧪 預覽測試</h2>
		<div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
			<?php if (get_option('wu_captcha_enabled', 0)): ?>
				<p style="color:#666;margin-bottom:15px;">
					測試重新整理按鈕與輸入功能:
				</p>
				<?php wu_captcha_render_field('preview'); ?>
			<?php else: ?>
				<p><strong>驗證碼功能未啟用。</strong></p>
			<?php endif; ?>
		</div>
	</div>
	<?php
}
