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
        
        // 確保 session 在適當時機啟動
        add_action('init', array($this, 'maybe_start_session'), 1);
        
        // 載入驗證碼功能
        if ($this->settings['enabled']) {
            $this->init_captcha_hooks();
        }
    }
    
    public function maybe_start_session() {
        if (!session_id() && !headers_sent()) {
            session_start();
        }
    }
    
    private function get_default_settings() {
        return array(
            'enabled' => false,
            'captcha_type' => 'mixed', // mixed, letters, numbers
            'captcha_length' => 6,
            'case_sensitive' => false,
            'enable_login' => true,
            'enable_register' => true,
            'enable_lost_password' => true,
            'enable_woocommerce' => true,
            'image_width' => 120,
            'image_height' => 40,
            'font_size' => 16,
            'text_color' => '#333333',
            'background_color' => '#ffffff',
            'noise_level' => 'medium', // low, medium, high
            'session_timeout' => 600 // 10 minutes
        );
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
                </ul>
                
                <h4>安全優勢：</h4>
                <ul>
                    <li>防止自動漫遊器的垃圾訊息攻擊</li>
                    <li>增加網站安全性</li>
                    <li>減少惡意註冊和登入嘗試</li>
                    <li>不依賴第三方服務，確保隱私</li>
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
                            <input type="number" name="font_size" value="<?php echo $this->settings['font_size']; ?>" min="10" max="24" style="width: 60px;"> px
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
                                <option value="low" <?php selected($this->settings['noise_level'], 'low'); ?>>低</option>
                                <option value="medium" <?php selected($this->settings['noise_level'], 'medium'); ?>>中</option>
                                <option value="high" <?php selected($this->settings['noise_level'], 'high'); ?>>高</option>
                            </select>
                            <p class="description">增加噪點可提高安全性，但可能影響可讀性</p>
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
            
            <h2>使用說明</h2>
            <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
                <h3>GDPR 合規性：</h3>
                <ul>
                    <li>所有驗證碼在本地生成，不使用外部服務</li>
                    <li>不會收集或儲存任何個人識別資訊</li>
                    <li>不需要 API 金鑰或第三方帳戶</li>
                    <li>驗證碼資料僅在 Session 中暫存</li>
                </ul>
                
                <h3>安全機制：</h3>
                <ul>
                    <li>每次請求產生新的隨機驗證碼</li>
                    <li>設定工作階段逾時防止重複使用</li>
                    <li>支援大小寫敏感提高安全性</li>
                    <li>視覺噪點干擾機器人識別</li>
                </ul>
                
                <h3>注意事項：</h3>
                <ul>
                    <li>請確保 PHP 支援 GD 函式庫以生成圖片</li>
                    <li>驗證碼無法顯示時會提供文字替代方案</li>
                    <li>建議定期測試表單功能確保正常運作</li>
                    <li>可在測試模式下檢查各表單的顯示效果</li>
                </ul>
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
        <?php
    }
    
    private function save_settings() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'wu_captcha_control_settings')) {
            wp_die('安全驗證失敗');
        }
        
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
            'session_timeout' => intval($_POST['session_timeout'])
        );
        
        update_option('wu_captcha_control_settings', $settings);
        $this->settings = $settings;
        
        echo '<div class="notice notice-success"><p>設定已儲存！</p></div>';
    }
    
    public function init_captcha() {
        // AJAX 處理 - 只註冊一次
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
        
        // WooCommerce 整合
        if ($this->settings['enable_woocommerce'] && class_exists('WooCommerce')) {
            add_action('woocommerce_login_form', array($this, 'display_captcha_field'));
            add_action('woocommerce_register_form', array($this, 'display_captcha_field'));
            add_filter('woocommerce_process_login_errors', array($this, 'validate_woocommerce_login_captcha'), 10, 3);
            add_filter('woocommerce_registration_errors', array($this, 'validate_woocommerce_register_captcha'), 10, 3);
        }
        
        // 前端腳本和樣式
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('login_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
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
        
        $this->maybe_start_session();
        
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
    
    public function ajax_refresh_captcha() {
        $this->maybe_start_session();
        
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
                $characters = 'ABCDEFGHIJKLMNPQRSTUVWXYZ'; // 移除容易混淆的字母
                break;
            case 'numbers':
                $characters = '23456789'; // 移除容易混淆的數字
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
            // 如果 GD 不可用，返回文字驗證碼
            header('Content-Type: text/plain');
            echo 'GD Library not available';
            exit;
        }
        
        $code = isset($_GET['code']) ? sanitize_text_field($_GET['code']) : $this->generate_captcha_code();
        
        $width = $this->settings['image_width'];
        $height = $this->settings['image_height'];
        
        // 建立圖片
        $image = imagecreate($width, $height);
        
        // 設定顏色
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
        
        // 添加噪點
        $this->add_noise($image, $width, $height, $text_color);
        
        // 添加文字
        $font_size = $this->settings['font_size'];
        $text_length = strlen($code);
        $char_width = $font_size * 0.7;
        $total_text_width = $text_length * $char_width;
        $start_x = ($width - $total_text_width) / 2;
        
        for ($i = 0; $i < $text_length; $i++) {
            $x = $start_x + ($i * $char_width) + wp_rand(-5, 5);
            $y = ($height + $font_size) / 2 + wp_rand(-3, 3);
            
            // 添加隨機角度
            $angle = wp_rand(-15, 15);
            
            if (function_exists('imagettftext') && $this->get_font_file()) {
                imagettftext($image, $font_size, $angle, $x, $y, $text_color, $this->get_font_file(), $code[$i]);
            } else {
                imagestring($image, 5, $x, $y - $font_size/2, $code[$i], $text_color);
            }
        }
        
        // 輸出圖片
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
        
        $multiplier = array(
            'low' => 10,
            'medium' => 25,
            'high' => 50
        );
        
        $noise_count = $multiplier[$noise_level];
        
        // 添加點
        for ($i = 0; $i < $noise_count; $i++) {
            imagesetpixel($image, wp_rand(0, $width-1), wp_rand(0, $height-1), $color);
        }
        
        // 添加線條
        $line_count = intval($noise_count / 5);
        for ($i = 0; $i < $line_count; $i++) {
            imageline($image, wp_rand(0, $width-1), wp_rand(0, $height-1), 
                     wp_rand(0, $width-1), wp_rand(0, $height-1), $color);
        }
    }
    
    private function get_font_file() {
        // 嘗試使用系統字體，這裡返回 null 使用預設字體
        return null;
    }
    
    private function validate_captcha() {
        $this->maybe_start_session();
        
        // 檢查是否有驗證碼輸入
        if (!isset($_POST['wu_captcha']) || empty(trim($_POST['wu_captcha']))) {
            return new WP_Error('wu_captcha_empty', '請輸入驗證碼');
        }
        
        // 檢查 Session 中是否有驗證碼
        if (!isset($_SESSION['wu_captcha_code']) || !isset($_SESSION['wu_captcha_time'])) {
            return new WP_Error('wu_captcha_missing', '驗證碼已過期，請重新整理頁面');
        }
        
        // 檢查驗證碼是否過期
        $current_time = time();
        $captcha_time = intval($_SESSION['wu_captcha_time']);
        $timeout = intval($this->settings['session_timeout']);
        
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
        // 只在有輸入帳號密碼時才驗證驗證碼
        if (empty($username) || empty($password)) {
            return $user;
        }
        
        // 如果已經有錯誤，先讓其他驗證通過
        if (is_wp_error($user)) {
            // 但仍需要驗證驗證碼以清除 session
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
        // 只在有輸入帳號密碼時才驗證
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
