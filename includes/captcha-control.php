<?php
/**
 * 註冊/登入驗證碼模組
 * 為 WordPress 網站前端表單新增符合 GDPR 規範的人機驗證反垃圾訊息功能
 */
if (!defined('ABSPATH')) exit;

class WU_Captcha_Control {
    
    private $settings;
    private $captcha_displayed = false;
    
    public function __construct() {
        $this->settings = get_option('wu_captcha_control_settings', $this->get_default_settings());
        
        add_action('admin_menu', array($this, 'add_admin_menu'), 52);
        add_action('init', array($this, 'init_captcha'));
        
        // 優化 session 處理
        add_action('init', array($this, 'maybe_start_session'), 1);
        
        // 載入驗證碼功能
        if ($this->settings['enabled']) {
            $this->init_captcha_hooks();
        }
        
        // 登入/登出重定向處理
        $this->init_redirect_hooks();
    }
    
    public function maybe_start_session() {
        // 改善 session 啟動邏輯
        if (!session_id() && !headers_sent() && !is_admin()) {
            @session_start();
        }
        
        // 確保 WooCommerce 頁面也能正常啟動 session
        if (class_exists('WooCommerce') && (is_account_page() || is_checkout() || is_cart())) {
            if (!session_id() && !headers_sent()) {
                @session_start();
            }
        }
    }
    
    private function get_default_settings() {
        return array(
            'enabled' => false,
            'captcha_type' => 'mixed',
            'captcha_length' => 6,
            'case_sensitive' => false,
            'enable_login' => true,
            'enable_register' => true,
            'enable_lost_password' => true,
            'enable_woocommerce' => true,
            'image_width' => 120,
            'image_height' => 40,
            'font_size' => 28,
            'text_color' => '#333333',
            'background_color' => '#ffffff',
            'noise_level' => 'low',
            'session_timeout' => 600,
            // 新增重定向設定
            'login_redirect_type' => 'homepage', // homepage, admin, custom
            'logout_redirect_type' => 'homepage', // homepage, admin, custom
            'custom_login_redirect' => '',
            'custom_logout_redirect' => ''
        );
    }
    
    private function init_redirect_hooks() {
        // 只在前端處理重定向，避免後台問題
        if (!is_admin()) {
            add_filter('login_redirect', array($this, 'redirect_after_login'), 10, 3);
            add_action('wp_logout', array($this, 'redirect_after_logout'));
            
            // WooCommerce 登入後重定向
            if (class_exists('WooCommerce')) {
                add_filter('woocommerce_login_redirect', array($this, 'woocommerce_login_redirect'), 10, 2);
            }
        }
    }
    
    public function redirect_after_login($redirect_to, $request, $user) {
        // 如果是 AJAX 請求或管理員直接進入後台，不重定向
        if (wp_doing_ajax() || (isset($_REQUEST['action']) && $_REQUEST['action'] === 'login')) {
            return $redirect_to;
        }
        
        // 管理員訪問後台的處理
        if (isset($user->ID) && user_can($user->ID, 'manage_options')) {
            switch ($this->settings['login_redirect_type']) {
                case 'admin':
                    return admin_url();
                case 'custom':
                    if (!empty($this->settings['custom_login_redirect'])) {
                        return esc_url($this->settings['custom_login_redirect']);
                    }
                    return home_url();
                case 'homepage':
                default:
                    return home_url();
            }
        }
        
        // 一般用戶處理
        switch ($this->settings['login_redirect_type']) {
            case 'custom':
                if (!empty($this->settings['custom_login_redirect'])) {
                    return esc_url($this->settings['custom_login_redirect']);
                }
                return home_url();
            case 'homepage':
            default:
                return home_url();
        }
    }
    
    public function redirect_after_logout() {
        // 避免在 AJAX 或管理頁面中重定向
        if (wp_doing_ajax() || is_admin()) {
            return;
        }
        
        $redirect_url = home_url();
        
        switch ($this->settings['logout_redirect_type']) {
            case 'custom':
                if (!empty($this->settings['custom_logout_redirect'])) {
                    $redirect_url = esc_url($this->settings['custom_logout_redirect']);
                }
                break;
            case 'homepage':
            default:
                $redirect_url = home_url();
                break;
        }
        
        wp_safe_redirect($redirect_url);
        exit;
    }
    
    public function woocommerce_login_redirect($redirect, $user) {
        return $this->redirect_after_login($redirect, '', $user);
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'wumetax-toolkit',
            '註冊/登入驗證碼',
            '註冊/登入驗證碼',
            'manage_options',
            'wu-captcha-control',
            array($this, 'admin_page')
        );
    }
    
    public function admin_page() {
        if (isset($_POST['submit'])) {
            $this->save_settings();
        }
        
        $this->settings = get_option('wu_captcha_control_settings', $this->get_default_settings());
        
        // 獲取所有頁面供選擇
        $pages = get_pages();
        ?>
        <div class="wrap">
            <h1>註冊/登入驗證碼設定</h1>
            
            <div class="notice notice-info">
                <h3>功能說明</h3>
                <p><strong>註冊/登入驗證碼功能</strong>為 WordPress 網站前端表單新增符合 GDPR 規範的人機驗證反垃圾訊息功能。</p>
                
                <h4>特色功能：</h4>
                <ul>
                    <li><strong>GDPR 合規</strong>：不需要外部服務，不儲存使用者辨別資料</li>
                    <li><strong>多種類型</strong>：支援英數字元混合、僅英文字母或僅數字</li>
                    <li><strong>廣泛支援</strong>：支援登入、註冊、忘記密碼表單</li>
                    <li><strong>WooCommerce 整合</strong>：自動支援 WooCommerce 表單</li>
                    <li><strong>自訂外觀</strong>：可調整顏色、大小、噪點等</li>
                    <li><strong>智慧重定向</strong>：登入/登出後可選擇重定向頁面</li>
                </ul>
            </div>
            
            <form method="post" action="">
                <?php wp_nonce_field('wu_captcha_control_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">啟用驗證碼</th>
                        <td>
                            <label>
                                <input type="checkbox" name="enabled" value="1" <?php checked($this->settings['enabled']); ?>>
                                啟用註冊/登入驗證碼功能
                            </label>
                        </td>
                    </tr>
                </table>
                
                <h2>驗證碼設定</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">驗證碼類型</th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text"><span>驗證碼類型</span></legend>
                                <label style="display: block; margin: 5px 0;">
                                    <input type="radio" name="captcha_type" value="mixed" <?php checked($this->settings['captcha_type'], 'mixed'); ?>>
                                    英數字元混合 (A-Z, 0-9)
                                </label>
                                <label style="display: block; margin: 5px 0;">
                                    <input type="radio" name="captcha_type" value="letters" <?php checked($this->settings['captcha_type'], 'letters'); ?>>
                                    僅英文字母 (A-Z)
                                </label>
                                <label style="display: block; margin: 5px 0;">
                                    <input type="radio" name="captcha_type" value="numbers" <?php checked($this->settings['captcha_type'], 'numbers'); ?>>
                                    僅數字 (0-9)
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">驗證碼長度</th>
                        <td>
                            <select name="captcha_length">
                                <?php
                                for ($i = 4; $i <= 8; $i++) {
                                    echo '<option value="' . $i . '" ' . selected($this->settings['captcha_length'], $i, false) . '>' . $i . ' 位數</option>';
                                }
                                ?>
                            </select>
                            <p class="description">建議使用 6 位數以平衡安全性和使用性</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">大小寫敏感</th>
                        <td>
                            <label>
                                <input type="checkbox" name="case_sensitive" value="1" <?php checked($this->settings['case_sensitive']); ?>>
                                區分大小寫（啟用後使用者必須輸入正確的大小寫）
                            </label>
                        </td>
                    </tr>
                </table>
                
                <h2>應用位置</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">WordPress 表單</th>
                        <td>
                            <label style="display: block; margin: 5px 0;">
                                <input type="checkbox" name="enable_login" value="1" <?php checked($this->settings['enable_login']); ?>>
                                登入表單
                            </label>
                            <label style="display: block; margin: 5px 0;">
                                <input type="checkbox" name="enable_register" value="1" <?php checked($this->settings['enable_register']); ?>>
                                註冊表單
                            </label>
                            <label style="display: block; margin: 5px 0;">
                                <input type="checkbox" name="enable_lost_password" value="1" <?php checked($this->settings['enable_lost_password']); ?>>
                                忘記密碼表單
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">WooCommerce 整合</th>
                        <td>
                            <label>
                                <input type="checkbox" name="enable_woocommerce" value="1" <?php checked($this->settings['enable_woocommerce']); ?>>
                                自動應用到 WooCommerce 登入/註冊表單（若有安裝）
                            </label>
                            <p class="description">包含結帳頁面的註冊和我的帳戶頁面的登入</p>
                        </td>
                    </tr>
                </table>
                
                <h2>重定向設定</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">登入後重定向</th>
                        <td>
                            <select name="login_redirect_type" onchange="toggleCustomField(this, 'custom_login_redirect_row')">
                                <option value="homepage" <?php selected($this->settings['login_redirect_type'], 'homepage'); ?>>首頁</option>
                                <option value="admin" <?php selected($this->settings['login_redirect_type'], 'admin'); ?>>管理後台（僅管理員）</option>
                                <option value="custom" <?php selected($this->settings['login_redirect_type'], 'custom'); ?>>自訂頁面</option>
                            </select>
                        </td>
                    </tr>
                    <tr id="custom_login_redirect_row" style="<?php echo ($this->settings['login_redirect_type'] !== 'custom') ? 'display:none;' : ''; ?>">
                        <th scope="row">自訂登入重定向頁面</th>
                        <td>
                            <select name="custom_login_redirect">
                                <option value="">選擇頁面...</option>
                                <option value="<?php echo home_url(); ?>" <?php selected($this->settings['custom_login_redirect'], home_url()); ?>>首頁</option>
                                <?php foreach ($pages as $page): ?>
                                    <option value="<?php echo get_permalink($page->ID); ?>" <?php selected($this->settings['custom_login_redirect'], get_permalink($page->ID)); ?>>
                                        <?php echo esc_html($page->post_title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">或輸入完整 URL：</p>
                            <input type="url" name="custom_login_redirect_url" value="<?php echo esc_url($this->settings['custom_login_redirect']); ?>" placeholder="https://example.com/page" style="width: 400px;">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">登出後重定向</th>
                        <td>
                            <select name="logout_redirect_type" onchange="toggleCustomField(this, 'custom_logout_redirect_row')">
                                <option value="homepage" <?php selected($this->settings['logout_redirect_type'], 'homepage'); ?>>首頁</option>
                                <option value="custom" <?php selected($this->settings['logout_redirect_type'], 'custom'); ?>>自訂頁面</option>
                            </select>
                        </td>
                    </tr>
                    <tr id="custom_logout_redirect_row" style="<?php echo ($this->settings['logout_redirect_type'] !== 'custom') ? 'display:none;' : ''; ?>">
                        <th scope="row">自訂登出重定向頁面</th>
                        <td>
                            <select name="custom_logout_redirect">
                                <option value="">選擇頁面...</option>
                                <option value="<?php echo home_url(); ?>" <?php selected($this->settings['custom_logout_redirect'], home_url()); ?>>首頁</option>
                                <?php foreach ($pages as $page): ?>
                                    <option value="<?php echo get_permalink($page->ID); ?>" <?php selected($this->settings['custom_logout_redirect'], get_permalink($page->ID)); ?>>
                                        <?php echo esc_html($page->post_title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">或輸入完整 URL：</p>
                            <input type="url" name="custom_logout_redirect_url" value="<?php echo esc_url($this->settings['custom_logout_redirect']); ?>" placeholder="https://example.com/page" style="width: 400px;">
                        </td>
                    </tr>
                </table>
                
                <h2>外觀設定</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">圖片尺寸</th>
                        <td>
                            <label style="display: inline-block; margin-right: 20px;">
                                寬度：
                                <input type="number" name="image_width" value="<?php echo $this->settings['image_width']; ?>" min="80" max="200" style="width: 60px;"> px
                            </label>
                            <label style="display: inline-block;">
                                高度：
                                <input type="number" name="image_height" value="<?php echo $this->settings['image_height']; ?>" min="30" max="80" style="width: 60px;"> px
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">字體大小</th>
                        <td>
                            <input type="number" name="font_size" value="<?php echo $this->settings['font_size']; ?>" min="10" max="32" style="width: 60px;"> px
                            <p class="description">預設為 28px，提供更好的可讀性</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">顏色設定</th>
                        <td>
                            <label style="display: block; margin: 5px 0;">
                                文字顏色：
                                <input type="color" name="text_color" value="<?php echo $this->settings['text_color']; ?>">
                            </label>
                            <label style="display: block; margin: 5px 0;">
                                背景顏色：
                                <input type="color" name="background_color" value="<?php echo $this->settings['background_color']; ?>">
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">噪點程度</th>
                        <td>
                            <select name="noise_level">
                                <option value="low" <?php selected($this->settings['noise_level'], 'low'); ?>>低（預設）</option>
                                <option value="medium" <?php selected($this->settings['noise_level'], 'medium'); ?>>中</option>
                                <option value="high" <?php selected($this->settings['noise_level'], 'high'); ?>>高</option>
                            </select>
                            <p class="description">預設為低噪點，確保最佳可讀性</p>
                        </td>
                    </tr>
                </table>
                
                <h2>安全設定</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">工作階段逾時</th>
                        <td>
                            <select name="session_timeout">
                                <option value="300" <?php selected($this->settings['session_timeout'], 300); ?>>5 分鐘</option>
                                <option value="600" <?php selected($this->settings['session_timeout'], 600); ?>>10 分鐘</option>
                                <option value="900" <?php selected($this->settings['session_timeout'], 900); ?>>15 分鐘</option>
                                <option value="1800" <?php selected($this->settings['session_timeout'], 1800); ?>>30 分鐘</option>
                            </select>
                            <p class="description">驗證碼的有效時間</p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <?php submit_button('儲存設定', 'primary', 'submit', false); ?>
                </p>
            </form>
            
            <h2>驗證碼預覽</h2>
            <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
                <?php if ($this->settings['enabled']): ?>
                    <p>預覽驗證碼：</p>
                    <div style="margin: 10px 0;">
                        <img id="preview-captcha" src="<?php echo admin_url('admin-ajax.php?action=wu_generate_captcha&preview=1'); ?>" alt="驗證碼預覽" style="border: 1px solid #ddd;">
                        <button type="button" onclick="refreshPreviewCaptcha()" class="button" style="margin-left: 10px;">重新產生</button>
                    </div>
                    <script>
                    function refreshPreviewCaptcha() {
                        var img = document.getElementById('preview-captcha');
                        img.src = '<?php echo admin_url('admin-ajax.php?action=wu_generate_captcha&preview=1'); ?>&t=' + new Date().getTime();
                    }
                    </script>
                <?php else: ?>
                    <p>啟用驗證碼功能以查看預覽</p>
                <?php endif; ?>
            </div>
        </div>
        
        <style>
        .form-table th {
            width: 200px;
            vertical-align: top;
        }
        .notice {
            padding: 10px;
            margin: 15px 0;
            border-left: 4px solid;
            background: #fff;
        }
        .notice-info {
            border-left-color: #0073aa;
        }
        fieldset {
            border: none;
            padding: 0;
        }
        </style>
        
        <script>
        function toggleCustomField(select, rowId) {
            var row = document.getElementById(rowId);
            if (select.value === 'custom') {
                row.style.display = 'table-row';
            } else {
                row.style.display = 'none';
            }
        }
        </script>
        <?php
    }
    
    private function save_settings() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'wu_captcha_control_settings')) {
            wp_die('安全驗證失敗');
        }
        
        // 處理自訂重定向 URL
        $custom_login_redirect = !empty($_POST['custom_login_redirect_url']) 
            ? sanitize_url($_POST['custom_login_redirect_url']) 
            : sanitize_url($_POST['custom_login_redirect']);
            
        $custom_logout_redirect = !empty($_POST['custom_logout_redirect_url']) 
            ? sanitize_url($_POST['custom_logout_redirect_url']) 
            : sanitize_url($_POST['custom_logout_redirect']);
        
        $settings = array(
            'enabled' => isset($_POST['enabled']),
            'captcha_type' => sanitize_text_field($_POST['captcha_type']),
            'captcha_length' => intval($_POST['captcha_length']),
            'case_sensitive' => isset($_POST['case_sensitive']),
            'enable_login' => isset($_POST['enable_login']),
            'enable_register' => isset($_POST['enable_register']),
            'enable_lost_password' => isset($_POST['enable_lost_password']),
            'enable_woocommerce' => isset($_POST['enable_woocommerce']),
            'image_width' => intval($_POST['image_width']),
            'image_height' => intval($_POST['image_height']),
            'font_size' => intval($_POST['font_size']),
            'text_color' => sanitize_hex_color($_POST['text_color']),
            'background_color' => sanitize_hex_color($_POST['background_color']),
            'noise_level' => sanitize_text_field($_POST['noise_level']),
            'session_timeout' => intval($_POST['session_timeout']),
            // 重定向設定
            'login_redirect_type' => sanitize_text_field($_POST['login_redirect_type']),
            'logout_redirect_type' => sanitize_text_field($_POST['logout_redirect_type']),
            'custom_login_redirect' => $custom_login_redirect,
            'custom_logout_redirect' => $custom_logout_redirect
        );
        
        update_option('wu_captcha_control_settings', $settings);
        $this->settings = $settings;
        
        echo '<div class="notice notice-success"><p>設定已儲存！</p></div>';
    }
    
    public function init_captcha() {
        // AJAX 處理
        add_action('wp_ajax_wu_generate_captcha', array($this, 'generate_captcha_image'));
        add_action('wp_ajax_nopriv_wu_generate_captcha', array($this, 'generate_captcha_image'));
        add_action('wp_ajax_wu_refresh_captcha', array($this, 'ajax_refresh_captcha'));
        add_action('wp_ajax_nopriv_wu_refresh_captcha', array($this, 'ajax_refresh_captcha'));
    }
    
    private function init_captcha_hooks() {
        // WordPress 登入表單
        if ($this->settings['enable_login']) {
            add_action('login_form', array($this, 'display_captcha_field'));
            add_filter('authenticate', array($this, 'validate_login_captcha'), 30, 3);
        }
        
        // WordPress 註冊表單
        if ($this->settings['enable_register']) {
            add_action('register_form', array($this, 'display_captcha_field'));
            add_filter('registration_errors', array($this, 'validate_register_captcha'), 10, 3);
        }
        
        // 忘記密碼表單
        if ($this->settings['enable_lost_password']) {
            add_action('lostpassword_form', array($this, 'display_captcha_field'));
            add_action('lostpassword_post', array($this, 'validate_lost_password_captcha'));
        }
        
        // WooCommerce 整合 - 改善處理邏輯
        if ($this->settings['enable_woocommerce'] && class_exists('WooCommerce')) {
            // 延遲到 WooCommerce 完全載入後
            add_action('woocommerce_loaded', array($this, 'init_woocommerce_hooks'));
        }
        
        // 前端腳本和樣式
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('login_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
    }
    
    public function init_woocommerce_hooks() {
        add_action('woocommerce_login_form', array($this, 'display_captcha_field'));
        add_action('woocommerce_register_form', array($this, 'display_captcha_field'));
        add_filter('woocommerce_process_login_errors', array($this, 'validate_woocommerce_login_captcha'), 10, 3);
        add_filter('woocommerce_registration_errors', array($this, 'validate_woocommerce_register_captcha'), 10, 3);
    }
    
    public function enqueue_frontend_assets() {
        if (!$this->settings['enabled']) return;
        
        wp_add_inline_script('jquery', '
            jQuery(document).ready(function($) {
                window.refreshWuCaptcha = function(button) {
                    var $button = $(button);
                    var $img = $button.siblings("img");
                    
                    $button.prop("disabled", true).text("重新產生中...");
                    
                    $.ajax({
                        url: "' . admin_url('admin-ajax.php') . '",
                        type: "GET",
                        data: {
                            action: "wu_refresh_captcha",
                            t: new Date().getTime()
                        },
                        success: function(response) {
                            if (response.success && response.data && response.data.image_url) {
                                $img.attr("src", response.data.image_url + "&t=" + new Date().getTime());
                            } else {
                                alert("驗證碼重新產生失敗，請重新整理頁面");
                            }
                        },
                        error: function() {
                            alert("網路錯誤，請重新整理頁面");
                        },
                        complete: function() {
                            $button.prop("disabled", false).text("重新產生");
                        }
                    });
                };
            });
        ');
        
        wp_add_inline_style('wp-admin', '
            .wu-captcha-field label { font-weight: bold; }
            .wu-captcha-field .required { color: #d63638; }
            .wu-captcha-field small { color: #666; font-style: italic; }
            .wu-captcha-field img { max-width: 100%; height: auto; }
        ');
    }
    
    public function display_captcha_field() {
        // 防止重複顯示
        if ($this->captcha_displayed) return;
        $this->captcha_displayed = true;
        
        // 強制啟動 session
        $this->force_start_session();
        
        $captcha_code = $this->generate_captcha_code();
        $_SESSION['wu_captcha_code'] = $this->settings['case_sensitive'] ? $captcha_code : strtolower($captcha_code);
        $_SESSION['wu_captcha_time'] = time();
        
        $unique_id = 'wu_captcha_' . wp_rand(1000, 9999);
        ?>
        <p class="wu-captcha-field">
            <label for="<?php echo $unique_id; ?>">驗證碼 <span class="required">*</span></label>
            <div style="margin: 5px 0;">
                <img src="<?php echo admin_url('admin-ajax.php?action=wu_generate_captcha&code=' . urlencode($captcha_code) . '&t=' . time()); ?>" 
                     alt="驗證碼" 
                     style="border: 1px solid #ddd; vertical-align: middle; display: block; margin-bottom: 5px;">
                <button type="button" onclick="refreshWuCaptcha(this)" 
                        style="padding: 5px 10px; background: #f1f1f1; border: 1px solid #ccc; cursor: pointer; border-radius: 3px;">
                    重新產生
                </button>
            </div>
            <input type="text" 
                   name="wu_captcha" 
                   id="<?php echo $unique_id; ?>" 
                   required 
                   autocomplete="off"
                   placeholder="請輸入上方圖片中的代碼"
                   style="width: 100%; max-width: 200px; margin-top: 5px; padding: 5px;">
            <br><small>請輸入圖片中顯示的代碼以驗證您不是機器人</small>
        </p>
        <?php
    }
    
    private function force_start_session() {
        if (!session_id()) {
            if (!headers_sent()) {
                @session_start();
            } else {
                // 如果 headers 已經發送，嘗試使用其他方式處理
                if (!isset($_SESSION)) {
                    $_SESSION = array();
                }
            }
        }
    }
    
    public function ajax_refresh_captcha() {
        $this->force_start_session();
        
        $captcha_code = $this->generate_captcha_code();
        $_SESSION['wu_captcha_code'] = $this->settings['case_sensitive'] ? $captcha_code : strtolower($captcha_code);
        $_SESSION['wu_captcha_time'] = time();
        
        wp_send_json_success(array(
            'image_url' => admin_url('admin-ajax.php?action=wu_generate_captcha&code=' . urlencode($captcha_code))
        ));
    }
    
    private function generate_captcha_code() {
        $length = $this->settings['captcha_length'];
        
        switch ($this->settings['captcha_type']) {
            case 'letters':
                $characters = 'ABCDEFGHIJKLMNPQRSTUVWXYZ';
                break;
            case 'numbers':
                $characters = '23456789';
                break;
            case 'mixed':
            default:
                $characters = 'ABCDEFGHIJKLMNPQRSTUVWXYZ23456789';
                break;
        }
        
        $code = '';
        $max = strlen($characters) - 1;
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[wp_rand(0, $max)];
        }
        
        return $code;
    }
    
    public function generate_captcha_image() {
        if (!function_exists('imagecreate')) {
            header('Content-Type: text/plain');
            echo 'GD Library not available';
            exit;
        }
        
        $code = isset($_GET['code']) ? sanitize_text_field($_GET['code']) : $this->generate_captcha_code();
        
        $width = $this->settings['image_width'];
        $height = $this->settings['image_height'];
        
        $image = imagecreate($width, $height);
        
        $bg_color = imagecolorallocate($image, 
            hexdec(substr($this->settings['background_color'], 1, 2)),
            hexdec(substr($this->settings['background_color'], 3, 2)),
            hexdec(substr($this->settings['background_color'], 5, 2))
        );
        
        $text_color = imagecolorallocate($image,
            hexdec(substr($this->settings['text_color'], 1, 2)),
            hexdec(substr($this->settings['text_color'], 3, 2)),
            hexdec(substr($this->settings['text_color'], 5, 2))
        );
        
        $this->add_noise($image, $width, $height, $text_color);
        
        $font_size = $this->settings['font_size'];
        $text_length = strlen($code);
        $char_width = $font_size * 0.8;
        $total_text_width = $text_length * $char_width;
        $start_x = max(10, ($width - $total_text_width) / 2);
        
        for ($i = 0; $i < $text_length; $i++) {
            $x = $start_x + ($i * $char_width) + wp_rand(-3, 3);
            $y = ($height + $font_size) / 2 + wp_rand(-2, 2);
            $angle = wp_rand(-8, 8);
            
            if (function_exists('imagettftext') && $this->get_font_file()) {
                imagettftext($image, $font_size, $angle, $x, $y, $text_color, $this->get_font_file(), $code[$i]);
            } else {
                imagestring($image, 5, $x, $y - $font_size/2, $code[$i], $text_color);
            }
        }
        
        header('Content-Type: image/png');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        imagepng($image);
        imagedestroy($image);
        exit;
    }
    
    private function add_noise($image, $width, $height, $color) {
        $noise_level = $this->settings['noise_level'];
        $multiplier = array('low' => 5, 'medium' => 15, 'high' => 30);
        $noise_count = $multiplier[$noise_level];
        
        $light_color = imagecolorallocate($image, 
            min(255, hexdec(substr($this->settings['text_color'], 1, 2)) + 60),
            min(255, hexdec(substr($this->settings['text_color'], 3, 2)) + 60),
            min(255, hexdec(substr($this->settings['text_color'], 5, 2)) + 60)
        );
        
        for ($i = 0; $i < $noise_count; $i++) {
            imagesetpixel($image, wp_rand(0, $width-1), wp_rand(0, $height-1), $light_color);
        }
        
        $line_count = max(1, intval($noise_count / 8));
        for ($i = 0; $i < $line_count; $i++) {
            imageline($image, wp_rand(0, $width-1), wp_rand(0, $height-1), 
                     wp_rand(0, $width-1), wp_rand(0, $height-1), $light_color);
        }
    }
    
    private function get_font_file() {
        return null;
    }
    
    private function validate_captcha() {
        $this->force_start_session();
        
        // 檢查是否有驗證碼輸入
        if (!isset($_POST['wu_captcha']) || empty(trim($_POST['wu_captcha']))) {
            return new WP_Error('wu_captcha_empty', '請輸入驗證碼');
        }
        
        // 檢查 Session 中是否有驗證碼
        if (!isset($_SESSION['wu_captcha_code']) || !isset($_SESSION['wu_captcha_time'])) {
            return new WP_Error('wu_captcha_missing', '驗證碼已過期，請重新整理頁面');
        }
        
        // 檢查驗證碼是否過期 - 放寬時間限制
        $current_time = time();
        $captcha_time = intval($_SESSION['wu_captcha_time']);
        $timeout = intval($this->settings['session_timeout']);
        
        // 對於 WooCommerce，給予額外的時間緩衝
        if (class_exists('WooCommerce') && (is_account_page() || is_checkout())) {
            $timeout = $timeout + 300; // 額外 5 分鐘
        }
        
        if (($current_time - $captcha_time) > $timeout) {
            unset($_SESSION['wu_captcha_code'], $_SESSION['wu_captcha_time']);
            return new WP_Error('wu_captcha_expired', '驗證碼已過期（有效時間：' . ($timeout/60) . ' 分鐘），請重新產生');
        }
        
        // 驗證驗證碼
        $input_code = $this->settings['case_sensitive'] ? trim($_POST['wu_captcha']) : strtolower(trim($_POST['wu_captcha']));
        $session_code = $_SESSION['wu_captcha_code'];
        
        // 清除 Session 中的驗證碼（一次性使用）
        unset($_SESSION['wu_captcha_code'], $_SESSION['wu_captcha_time']);
        
        if ($input_code !== $session_code) {
            return new WP_Error('wu_captcha_invalid', '驗證碼錯誤，請重新輸入');
        }
        
        return true;
    }
    
    public function validate_login_captcha($user, $username, $password) {
        // 避免在 AJAX 或管理請求中進行驗證
        if (wp_doing_ajax() || is_admin()) {
            return $user;
        }
        
        if (empty($username) || empty($password)) {
            return $user;
        }
        
        if (is_wp_error($user)) {
            $this->validate_captcha();
            return $user;
        }
        
        $validation = $this->validate_captcha();
        if (is_wp_error($validation)) {
            return $validation;
        }
        
        return $user;
    }
    
    public function validate_register_captcha($errors, $sanitized_user_login, $user_email) {
        $validation = $this->validate_captcha();
        if (is_wp_error($validation)) {
            $errors->add($validation->get_error_code(), $validation->get_error_message());
        }
        
        return $errors;
    }
    
    public function validate_lost_password_captcha() {
        $validation = $this->validate_captcha();
        if (is_wp_error($validation)) {
            wp_die($validation->get_error_message(), '驗證失敗', array('back_link' => true));
        }
    }
    
    public function validate_woocommerce_login_captcha($validation_error, $username, $password) {
        if (empty($username) || empty($password)) {
            return $validation_error;
        }
        
        $validation = $this->validate_captcha();
        if (is_wp_error($validation)) {
            $validation_error->add($validation->get_error_code(), $validation->get_error_message());
        }
        
        return $validation_error;
    }
    
    public function validate_woocommerce_register_captcha($validation_error, $username, $email) {
        $validation = $this->validate_captcha();
        if (is_wp_error($validation)) {
            $validation_error->add($validation->get_error_code(), $validation->get_error_message());
        }
        
        return $validation_error;
    }
}

// 初始化模組
new WU_Captcha_Control();
?>
