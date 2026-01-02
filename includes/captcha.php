<?php
if (!defined('ABSPATH')) exit;

/*
 * GDPR-friendly Image CAPTCHA for WP/WooCommerce forms
 * Version: 3.8 - Unified & Simplified Production Version
 * 
 * KEY IMPROVEMENTS:
 * - Unified field naming strategy (matches Fluent Forms approach)
 * - Cleaner admin interface with better explanations
 * - Simplified refresh button (no emoji, clean text)
 * - Consistent validation across all form types
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
		return new WP_Error('wu_captcha_missing', '請輸入驗證碼');
	}
	
	$decoded = base64_decode($token);
	if (!$decoded || strpos($decoded, '|') === false) {
		return new WP_Error('wu_captcha_invalid', '驗證碼無效');
	}
	
	list($code, $ts, $mac) = array_pad(explode('|', $decoded, 3), 3, null);
	if (!$code || !$ts || !$mac) {
		return new WP_Error('wu_captcha_invalid', '驗證碼格式錯誤');
	}
	
	$expected = hash_hmac('sha256', $code . '|' . $ts, wu_captcha_secret_key());
	if (!hash_equals($expected, $mac)) {
		return new WP_Error('wu_captcha_invalid', '驗證碼錯誤');
	}
	
	if (abs(time() - (int)$ts) > 600) {
		return new WP_Error('wu_captcha_expired', '驗證碼已過期,請重新整理');
	}
	
	$token_hash = md5($token);
	if (get_transient('wu_captcha_used_' . $token_hash)) {
		return new WP_Error('wu_captcha_replay', '此驗證碼已被使用');
	}
	
	if (strtoupper(trim($user_input)) !== strtoupper($code)) {
		return new WP_Error('wu_captcha_mismatch', '驗證碼錯誤');
	}
	
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
	
	for ($i = 0; $i < 5; $i++) {
		imageline($img, wp_rand(0, $width), wp_rand(0, $height), 
		          wp_rand(0, $width), wp_rand(0, $height), $line_color);
	}
	
	for ($i = 0; $i < 100; $i++) {
		imagesetpixel($img, wp_rand(0, $width), wp_rand(0, $height), $noise);
	}
	
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
	
	header('Access-Control-Allow-Origin: *');
	header('Content-Type: image/png');
	header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
	header('Pragma: no-cache');
	header('Expires: 0');
	header('X-Robots-Tag: noindex, nofollow');
	imagepng($img);
	imagedestroy($img);
	exit;
}

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
		$htaccess_content = "Order Deny,Allow\nDeny from all\n";
		file_put_contents($htaccess_file, $htaccess_content);
	}
}
add_action('admin_init', 'wu_captcha_secure_fonts_directory');

// ===== Render Field (Unified Naming) =====

function wu_captcha_render_field($context = 'default') {
	if (!get_option('wu_captcha_enabled', 0)) return;
	
	$code = wu_captcha_generate_code();
	$ts = time();
	$token = wu_captcha_generate_token($code, $ts);
	$img_url = esc_url(add_query_arg(array('wu_captcha' => 1, 'token' => $token), home_url('/')));
	$unique_id = 'wu_captcha_' . wp_rand(1000, 9999);
	
	// Unified naming for all forms
	$input_name = 'wu_captcha_input';
	$token_name = 'wu_captcha_token';
	
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
			        style="display:inline-block;margin-top:8px;background:#0073aa;color:#fff;border:none;padding:8px 14px;cursor:pointer;border-radius:4px;font-size:13px;"
			        title="重新整理驗證碼">
				重新整理
			</button>
		</div>
		
		<div>
			<input type="text" 
			       id="<?php echo esc_attr($unique_id); ?>_input" 
			       name="<?php echo esc_attr($input_name); ?>"
			       class="elementor-field elementor-size-sm"
			       autocomplete="off" 
			       placeholder="請輸入驗證碼" 
			       <?php if ($context !== 'preview'): ?>required<?php endif; ?>
			       style="width:100%;max-width:300px;padding:10px;border:1px solid #ddd;border-radius:4px;font-size:14px;">
			<input type="hidden" 
			       id="<?php echo esc_attr($unique_id); ?>_token" 
			       name="<?php echo esc_attr($token_name); ?>"
			       class="elementor-field"
			       value="<?php echo esc_attr($token); ?>">
		</div>
		<small style="display:block;color:#666;margin-top:6px;font-size:12px;">
			此驗證碼符合 GDPR 規範
		</small>
	</div>
	<?php
}

// ===== Optimized Script Loading =====

function wu_captcha_print_scripts() {
	if (!get_option('wu_captcha_enabled', 0)) return;
	
	static $script_loaded = false;
	if ($script_loaded) return;
	$script_loaded = true;
	
	?>
	<script>
	(function() {
		'use strict';
		
		if (window.wuCaptchaInitialized) return;
		window.wuCaptchaInitialized = true;
		
		document.addEventListener('click', function(e) {
			if (!e.target || !e.target.classList.contains('wu-captcha-refresh-btn')) return;
			
			e.preventDefault();
			e.stopPropagation();
			
			var uniqueId = e.target.getAttribute('data-captcha-id');
			if (!uniqueId) return;
			
			var img = document.getElementById(uniqueId + '_img');
			var tokenField = document.getElementById(uniqueId + '_token');
			var inputField = document.getElementById(uniqueId + '_input');
			
			if (!img || !tokenField || !inputField) return;
			
			var btn = e.target;
			var originalText = btn.innerHTML;
			btn.innerHTML = '載入中...';
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
							inputField.focus();
						}
					} catch(e) {
						alert('驗證碼重新整理失敗');
					}
				}
				btn.innerHTML = originalText;
				btn.disabled = false;
			};
			xhr.onerror = function() {
				btn.innerHTML = originalText;
				btn.disabled = false;
				alert('網路錯誤');
			};
			xhr.send('action=wu_captcha_refresh');
		}, true);
	})();
	</script>
	<?php
}

add_action('wp_footer', 'wu_captcha_print_scripts', 999);
add_action('login_footer', 'wu_captcha_print_scripts', 999);

// ===== AJAX Handlers =====

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

add_action('wp_ajax_wu_captcha_get_html', 'wu_captcha_ajax_get_html');
add_action('wp_ajax_nopriv_wu_captcha_get_html', 'wu_captcha_ajax_get_html');

function wu_captcha_ajax_get_html() {
	header('Access-Control-Allow-Origin: *');
	ob_start();
	wu_captcha_render_field('elementor');
	$html = ob_get_clean();
	wp_send_json_success(array('html' => $html));
}

// ===== Shortcode =====

add_shortcode('wu_captcha', function($atts) {
	ob_start();
	wu_captcha_render_field('shortcode');
	return ob_get_clean();
});

// ===== CSS =====

add_action('wp_head', function() {
	if (!get_option('wu_captcha_enabled', 0)) return;
	?>
	<style>
	.wu-captcha-field{clear:both;margin:16px 0}
	.wu-captcha-field label{display:block;font-weight:600;margin-bottom:10px;color:#333}
	.wu-captcha-wrapper{position:relative;display:inline-block;max-width:100%}
	.wu-captcha-wrapper img{display:block;max-width:100%;height:auto;border:2px solid #ddd;border-radius:4px;padding:8px;background:#fff}
	.wu-captcha-refresh-btn{display:inline-block;margin-top:8px;background:#0073aa;color:#fff;border:none;padding:8px 14px;cursor:pointer;border-radius:4px;font-size:13px;transition:background .2s}
	.wu-captcha-refresh-btn:hover:not(:disabled){background:#005177}
	.wu-captcha-refresh-btn:disabled{opacity:.6;cursor:not-allowed}
	.wu-captcha-field input[type="text"]{width:100%;max-width:300px;padding:10px;border:1px solid #ddd;border-radius:4px;font-size:14px;box-sizing:border-box}
	@media (max-width:768px){.wu-captcha-field input[type="text"]{max-width:100%}}
	</style>
	<?php
}, 1);

// ===== Standard Forms Integration =====

add_action('login_form', 'wu_captcha_render_field');
add_action('register_form', 'wu_captcha_render_field');
add_action('lostpassword_form', 'wu_captcha_render_field');
add_action('woocommerce_login_form', 'wu_captcha_render_field');
add_action('woocommerce_register_form', 'wu_captcha_render_field');
add_action('woocommerce_lostpassword_form', 'wu_captcha_render_field');
add_action('comment_form_after_fields', 'wu_captcha_render_field');
add_action('comment_form_logged_in_after', 'wu_captcha_render_field');

// ===== Validation Functions =====

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

// ===== Fluent Forms Integration =====

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

// ===== Elementor Pro Integration (Unified Approach) =====

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
		var debounceTimer;
		
		function getCaptchaHTML() {
			var xhr = new XMLHttpRequest();
			xhr.open('POST', ajaxUrl, false);
			xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			xhr.send('action=wu_captcha_get_html');
			
			try {
				var response = JSON.parse(xhr.responseText);
				return response.success ? response.data.html : '';
			} catch(e) {
				return '';
			}
		}
		
		function injectCaptcha(form) {
			if (!form || captchaInjected.has(form)) return;
			
			if (form.querySelector('.wu-captcha-field')) {
				captchaInjected.add(form);
				return;
			}
			
			var submitBtn = form.querySelector('.elementor-field-type-submit, button[type="submit"]');
			if (!submitBtn) return;
			
			var captchaHTML = getCaptchaHTML();
			if (!captchaHTML) return;
			
			var fieldGroup = document.createElement('div');
			fieldGroup.className = 'elementor-field-group elementor-column elementor-field-type-text elementor-col-100';
			fieldGroup.innerHTML = captchaHTML;
			
			var submitContainer = submitBtn.closest('.elementor-field-group, .elementor-button-wrapper') || submitBtn.parentElement;
			if (submitContainer && submitContainer.parentElement) {
				submitContainer.parentElement.insertBefore(fieldGroup, submitContainer);
				captchaInjected.add(form);
			}
		}
		
		function scanAndInject() {
			var forms = document.querySelectorAll('.elementor-form, form.elementor-form-wrapper');
			forms.forEach(function(form) {
				injectCaptcha(form);
			});
		}
		
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', scanAndInject);
		} else {
			scanAndInject();
		}
		
		if (typeof MutationObserver !== 'undefined') {
			var observer = new MutationObserver(function(mutations) {
				clearTimeout(debounceTimer);
				debounceTimer = setTimeout(function() {
					var shouldScan = mutations.some(function(mutation) {
						var addedNodes = Array.from(mutation.addedNodes);
						return addedNodes.some(function(node) {
							if (node.nodeType !== 1) return false;
							return (node.matches && node.matches('.elementor-form, form.elementor-form-wrapper')) ||
							       (node.querySelector && node.querySelector('.elementor-form, form.elementor-form-wrapper'));
						});
					});
					
					if (shouldScan) scanAndInject();
				}, 300);
			});
			
			observer.observe(document.body, {
				childList: true,
				subtree: true
			});
		}
	})();
	</script>
	<?php
}, 999);

add_action('elementor_pro/forms/validation', function($record, $ajax_handler) {
	if (!get_option('wu_captcha_enabled', 0)) return;
	if (!get_option('wu_captcha_elementor', 1)) return;
	
	$raw_fields = $record->get('sent_data');
	
	$captcha_input = isset($raw_fields['wu_captcha_input']) ? sanitize_text_field($raw_fields['wu_captcha_input']) : '';
	$captcha_token = isset($raw_fields['wu_captcha_token']) ? sanitize_text_field($raw_fields['wu_captcha_token']) : '';
	
	if (empty($captcha_input) || empty($captcha_token)) {
		$ajax_handler->add_error_message('請完成人機驗證');
		$ajax_handler->is_success = false;
		return;
	}
	
	$result = wu_captcha_validate_token($captcha_token, $captcha_input);
	
	if ($result !== true) {
		$ajax_handler->add_error_message($result->get_error_message());
		$ajax_handler->is_success = false;
	}
}, 10, 2);

// ===== Settings =====

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
		'驗證碼設定',
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
		<h1>🔐 驗證碼設定</h1>
		
		<div class="notice notice-info" style="padding:15px;">
			<h3 style="margin-top:0;">✨ v3.8 - 統一簡化版</h3>
			<ul style="margin-left:20px;line-height:2;">
				<li><strong>統一欄位命名</strong>:所有表單(包含 Elementor)使用相同驗證方式,參考 Fluent Forms 做法</li>
				<li><strong>簡化重新整理按鈕</strong>:移除表情符號,僅顯示「重新整理」文字</li>
				<li><strong>改進後台說明</strong>:更清楚的設定選項說明</li>
				<li><strong>阻止錯誤提交</strong>:驗證失敗時阻止 Elementor 發送郵件</li>
			</ul>
		</div>
		
		<form method="post" style="background:#fff;padding:20px;border:1px solid #ddd;border-radius:5px;margin-top:20px;">
			<?php wp_nonce_field('wu_captcha_settings'); ?>
			
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="wu_captcha_enabled">啟用驗證碼</label>
					</th>
					<td>
						<label>
							<input type="checkbox" id="wu_captcha_enabled" name="wu_captcha_enabled" value="1" <?php checked(1, get_option('wu_captcha_enabled', 0)); ?>>
							<strong>在所有原生表單啟用驗證碼</strong>
						</label>
						<p class="description">
							包含:WordPress 登入/註冊、WooCommerce 登入/註冊、留言表單等原生表單。<br>
							不包含第三方表單外掛(需使用下方「表單外掛整合」選項)。
						</p>
					</td>
				</tr>
				
				<tr>
					<th scope="row">表單外掛整合</th>
					<td>
						<fieldset>
							<label style="display:block;margin-bottom:12px;">
								<input type="checkbox" name="wu_captcha_fluent_forms" value="1" <?php checked(1, get_option('wu_captcha_fluent_forms', 1)); ?> <?php disabled(!$fluent_active); ?>>
								<strong>Fluent Forms</strong>
								<?php echo $fluent_active ? '<span style="color:#46b450;">(已安裝)</span>' : '<span style="color:#dc3232;">(未安裝)</span>'; ?>
							</label>
							<p class="description" style="margin:5px 0 15px 24px;">
								自動在所有 Fluent Forms 表單底部加入驗證碼。
							</p>
							
							<label style="display:block;margin-bottom:12px;">
								<input type="checkbox" name="wu_captcha_elementor" value="1" <?php checked(1, get_option('wu_captcha_elementor', 1)); ?> <?php disabled(!$elementor_active); ?>>
								<strong>Elementor Pro Forms</strong>
								<?php echo $elementor_active ? '<span style="color:#46b450;">(已安裝)</span>' : '<span style="color:#dc3232;">(未安裝)</span>'; ?>
							</label>
							<p class="description" style="margin:5px 0 0 24px;">
								自動在所有 Elementor 表單底部加入驗證碼。<br>
								<strong>驗證失敗時會阻止表單提交與郵件發送。</strong>
							</p>
						</fieldset>
					</td>
				</tr>
				
				<tr>
					<th scope="row"><label for="wu_captcha_type">驗證碼類型</label></th>
					<td>
						<select id="wu_captcha_type" name="wu_captcha_type">
							<?php foreach (array('alnum' => '英數混合 (建議)', 'alpha' => '僅英文字母', 'numeric' => '僅數字') as $k => $label): ?>
								<option value="<?php echo esc_attr($k); ?>" <?php selected(get_option('wu_captcha_type', 'alnum'), $k); ?>>
									<?php echo esc_html($label); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description">英數混合提供較高安全性</p>
					</td>
				</tr>
				
				<tr>
					<th scope="row"><label for="wu_captcha_case">大小寫設定</label></th>
					<td>
						<select id="wu_captcha_case" name="wu_captcha_case">
							<?php foreach (array('mixed' => '大小寫混合 (建議)', 'upper' => '僅大寫', 'lower' => '僅小寫') as $k => $label): ?>
								<option value="<?php echo esc_attr($k); ?>" <?php selected(get_option('wu_captcha_case', 'mixed'), $k); ?>>
									<?php echo esc_html($label); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description">混合大小寫提供較高安全性,驗證時不區分大小寫</p>
					</td>
				</tr>
				
				<tr>
					<th scope="row"><label for="wu_captcha_length">字元長度</label></th>
					<td>
						<input type="number" id="wu_captcha_length" name="wu_captcha_length" min="3" max="8" value="<?php echo intval(get_option('wu_captcha_length', 5)); ?>" style="width:80px;">
						<span class="description">字元 (3-8 個字元,建議 5-6 個)</span>
					</td>
				</tr>
			</table>
			
			<?php submit_button('儲存設定', 'primary large'); ?>
		</form>
		
		<hr style="margin:40px 0 30px;">
		
		<h2>🔒 安全性管理</h2>
		<div style="background:#fff;padding:20px;border:1px solid #ddd;border-radius:5px;">
			<h3 style="margin-top:0;">重設 HMAC 私鑰</h3>
			<p style="color:#666;">
				重設後所有現存的驗證碼 token 將立即失效,用戶需重新載入頁面。<br>
				建議在懷疑私鑰洩露時執行此操作。
			</p>
			<form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="margin-top:15px;">
				<?php wp_nonce_field('wu_captcha_reset_key'); ?>
				<input type="hidden" name="action" value="wu_captcha_reset_key">
				<button type="submit" class="button button-secondary" onclick="return confirm('確定要重設私鑰嗎?\n現存驗證碼將立即失效。');">
					🔑 重設 HMAC 私鑰
				</button>
			</form>
		</div>
		
		<hr style="margin:40px 0 30px;">
		
		<h2>🧪 驗證碼預覽</h2>
		<div style="background:#fff;padding:20px;border:1px solid #ddd;border-radius:5px;">
			<?php if (get_option('wu_captcha_enabled', 0)): ?>
				<p style="color:#666;margin-bottom:15px;">
					以下為驗證碼在前台的顯示效果(預覽模式,不進行驗證):
				</p>
				<?php wu_captcha_render_field('preview'); ?>
			<?php else: ?>
				<p style="color:#999;">驗證碼功能未啟用,無法顯示預覽。</p>
			<?php endif; ?>
		</div>
		
		<hr style="margin:40px 0 30px;">
		
		<h2>📋 技術說明</h2>
		<div style="background:#fff;padding:20px;border:1px solid #ddd;border-radius:5px;">
			<h4 style="margin-top:0;">特點</h4>
			<ul style="line-height:1.8;">
				<li><strong>符合 GDPR</strong>:無第三方服務,完全本地生成</li>
				<li><strong>防重放攻擊</strong>:每個 token 只能使用一次</li>
				<li><strong>自動過期</strong>:驗證碼 10 分鐘後自動失效</li>
				<li><strong>HMAC 簽章</strong>:使用 SHA-256 防止偽造</li>
				<li><strong>不區分大小寫</strong>:降低用戶輸入錯誤率</li>
			</ul>
			
			<h4>整合說明</h4>
			<ul style="line-height:1.8;">
				<li><strong>原生表單</strong>:自動整合,無需額外設定</li>
				<li><strong>Fluent Forms</strong>:自動在提交按鈕前插入驗證碼</li>
				<li><strong>Elementor Pro</strong>:自動在提交按鈕前插入驗證碼,驗證失敗阻止提交</li>
				<li><strong>其他表單</strong>:使用 shortcode <code>[wu_captcha]</code> 手動插入</li>
			</ul>
		</div>
	</div>
	<?php
}
