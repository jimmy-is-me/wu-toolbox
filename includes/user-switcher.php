<?php
/**
 * 使用者切換模組
 * 幫助管理員快速輕鬆地在 WordPress 使用者帳戶之間切換
 * 版本:3.1 - 修正 WordPress 6.7 翻譯載入問題
 */

if (!defined('ABSPATH')) exit;

class WU_User_Switcher {
    
    private $settings;
    private $option_name = 'wu_user_switcher_settings';
    private $cookie_name = 'wu_switched_user';
    private $counter_option = 'wu_user_switcher_counter';
    private $assets_version = '3.1';
    
    public function __construct() {
        // 延遲到 init 動作執行,避免翻譯載入過早
        add_action('init', array($this, 'init_hooks'), 1);
        
        // 安全措施必須在最早期執行
        add_action('wp_login', array($this, 'clear_switch_cookie'), 10, 2);
        add_action('wp_logout', array($this, 'clear_switch_cookie'));
        add_action('clear_auth_cookie', array($this, 'clear_switch_cookie'));
        
        // 會話驗證必須在 init 之前
        add_action('plugins_loaded', array($this, 'validate_switch_session'), 999);
        
        // 啟用/停用 Hook
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * 初始化 Hooks (延遲到 init)
     */
    public function init_hooks() {
        $this->settings = get_option($this->option_name, $this->get_default_settings());
        
        // 後台功能
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'), 90);
            add_action('admin_init', array($this, 'register_settings'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        }
        
        // 用戶列表整合
        add_filter('user_row_actions', array($this, 'add_switch_link'), 10, 2);
        
        // 切換操作
        add_action('admin_action_wu_switch_to_user', array($this, 'switch_to_user'));
        add_action('admin_action_wu_switch_back', array($this, 'switch_back'));
        add_action('wp_ajax_wu_switch_user', array($this, 'ajax_switch_user'));
        
        // 管理欄整合
        add_action('admin_bar_menu', array($this, 'add_admin_bar_menu'), 100);
        
        // 資源載入 (條件式)
        if ($this->is_switched_user()) {
            add_action('wp_enqueue_scripts', array($this, 'enqueue_switched_assets'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_switched_assets'));
            
            // 防止快取
            add_action('template_redirect', array($this, 'prevent_caching'), 1);
            add_action('send_headers', array($this, 'send_no_cache_headers'));
            
            // 通知快取外掛
            add_action('template_redirect', array($this, 'notify_cache_plugins'), 1);
        }
        
        // WooCommerce 相容性
        add_action('wu_user_switched', array($this, 'handle_woocommerce_session'), 10, 2);
    }
    
    /**
     * 預設設定
     */
    private function get_default_settings() {
        return array(
            'enabled' => false,
            'allow_switch_back' => true,
            'show_in_admin_bar' => true,
            'allowed_roles' => array('administrator'),
            'restricted_users' => array(),
            'clear_wc_session' => true,
            'log_switches' => true,
            'session_timeout' => 3600,
            'prevent_nested_switch' => true,
            'restore_wc_cart' => false,
            'strict_session_validation' => true
        );
    }
    
    /**
     * 啟用模組
     */
    public function activate() {
        // 初始化計數器
        if (!get_option($this->counter_option)) {
            update_option($this->counter_option, array(
                'total' => 0,
                'daily' => array(),
                'weekly' => array()
            ));
        }
        
        // 創建資源目錄
        $this->create_assets_directory();
    }
    
    /**
     * 停用模組
     */
    public function deactivate() {
        // 清理所有切換會話
        $this->cleanup_all_switch_sessions();
    }
    
    /**
     * 創建資源目錄並生成檔案
     */
    private function create_assets_directory() {
        $upload_dir = wp_upload_dir();
        $assets_dir = $upload_dir['basedir'] . '/wu-user-switcher';
        
        if (!file_exists($assets_dir)) {
            wp_mkdir_p($assets_dir);
        }
        
        // 生成 CSS 檔案
        $css_file = $assets_dir . '/switcher.css';
        if (!file_exists($css_file)) {
            file_put_contents($css_file, $this->get_switcher_css());
        }
        
        // 生成 JS 檔案
        $js_file = $assets_dir . '/switcher.js';
        if (!file_exists($js_file)) {
            file_put_contents($js_file, $this->get_switcher_js());
        }
    }
    
    /**
     * 取得切換器 CSS 內容
     */
    private function get_switcher_css() {
        return '/* User Switcher Styles */
#wp-admin-bar-wu-switch-back a {
    background: #dc3232 !important;
    color: #fff !important;
    font-weight: bold !important;
}

#wp-admin-bar-wu-switch-back:hover a {
    background: #c62828 !important;
}

#wp-admin-bar-wu-user-status.wu-switched-user a {
    background: #ff8c00 !important;
    color: #fff !important;
    font-weight: bold !important;
    animation: wu-pulse 2s infinite;
}

@keyframes wu-pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

#wp-admin-bar-wu-switch-back .ab-icon:before {
    content: "\\f341";
    top: 2px;
}

#wp-admin-bar-wu-user-status.wu-switched-user .ab-icon:before {
    content: "\\f110";
    top: 2px;
}

/* 前台警示橫幅 */
body.wu-user-switched::before {
    content: "⚠️ 您目前處於用戶切換模式";
    display: block;
    background: #ff8c00;
    color: #fff;
    text-align: center;
    padding: 10px;
    font-weight: bold;
    position: fixed;
    top: 32px;
    left: 0;
    right: 0;
    z-index: 99999;
}

@media screen and (max-width: 782px) {
    body.wu-user-switched::before {
        top: 46px;
    }
}

body.wu-user-switched {
    padding-top: 50px !important;
}';
    }
    
    /**
     * 取得切換器 JavaScript 內容
     */
    private function get_switcher_js() {
        return '/* User Switcher Scripts */
(function($) {
    "use strict";
    
    // 切換用戶函數
    window.wuSwitchUser = function(userId) {
        if (!userId || !confirm("確定要切換到此用戶嗎?")) {
            return false;
        }
        
        $.ajax({
            url: wuUserSwitcher.ajaxurl,
            type: "POST",
            data: {
                action: "wu_switch_user",
                user_id: userId,
                _wpnonce: wuUserSwitcher.nonce
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = response.data.redirect_url;
                } else {
                    alert("切換失敗：" + response.data);
                }
            },
            error: function() {
                alert("請求失敗,請重試");
            }
        });
    };
    
    // 當處於切換狀態時,定期檢查會話
    if (typeof wuUserSwitcher !== "undefined" && wuUserSwitcher.isSwitched) {
        setInterval(function() {
            $.ajax({
                url: wuUserSwitcher.ajaxurl,
                type: "POST",
                data: {
                    action: "wu_check_switch_session",
                    _wpnonce: wuUserSwitcher.nonce
                },
                success: function(response) {
                    if (!response.success) {
                        alert("切換會話已失效,將重新載入頁面");
                        window.location.reload();
                    }
                }
            });
        }, 300000); // 每5分鐘檢查一次
    }
    
})(jQuery);';
    }
    
    /**
     * 註冊設定
     */
    public function register_settings() {
        register_setting(
            'wu_user_switcher_group',
            $this->option_name,
            array($this, 'sanitize_settings')
        );
        
        add_settings_section(
            'wu_user_switcher_section',
            '使用者切換設定',
            array($this, 'section_callback'),
            'wu-user-switcher'
        );
    }
    
    /**
     * 清理設定
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        $sanitized['enabled'] = !empty($input['enabled']) ? true : false;
        $sanitized['allow_switch_back'] = !empty($input['allow_switch_back']) ? true : false;
        $sanitized['show_in_admin_bar'] = !empty($input['show_in_admin_bar']) ? true : false;
        $sanitized['allowed_roles'] = isset($input['allowed_roles']) ? array_map('sanitize_text_field', $input['allowed_roles']) : array();
        $sanitized['restricted_users'] = isset($input['restricted_users']) ? array_filter(array_map('intval', explode("\n", $input['restricted_users']))) : array();
        $sanitized['clear_wc_session'] = !empty($input['clear_wc_session']) ? true : false;
        $sanitized['log_switches'] = !empty($input['log_switches']) ? true : false;
        $sanitized['session_timeout'] = intval($input['session_timeout']);
        $sanitized['prevent_nested_switch'] = !empty($input['prevent_nested_switch']) ? true : false;
        $sanitized['restore_wc_cart'] = !empty($input['restore_wc_cart']) ? true : false;
        $sanitized['strict_session_validation'] = !empty($input['strict_session_validation']) ? true : false;
        
        return $sanitized;
    }
    
    /**
     * 載入後台資源
     */
    public function enqueue_admin_assets($hook) {
        // 在設定頁面或用戶列表頁面載入
        if ($hook !== 'wumetaxtoolkit_page_wu-user-switcher' && $hook !== 'users.php') {
            return;
        }
        
        wp_enqueue_style(
            'wu-user-switcher-admin',
            false,
            array(),
            $this->assets_version
        );
        
        wp_add_inline_style('wu-user-switcher-admin', $this->get_admin_css());
    }
    
    /**
     * 載入切換狀態資源 (條件式)
     */
    public function enqueue_switched_assets() {
        if (!is_admin_bar_showing()) {
            return;
        }
        
        $upload_dir = wp_upload_dir();
        $css_url = $upload_dir['baseurl'] . '/wu-user-switcher/switcher.css';
        $js_url = $upload_dir['baseurl'] . '/wu-user-switcher/switcher.js';
        
        // 載入 CSS
        wp_enqueue_style(
            'wu-user-switcher',
            $css_url,
            array(),
            $this->assets_version
        );
        
        // 載入 JavaScript
        wp_enqueue_script(
            'wu-user-switcher',
            $js_url,
            array('jquery'),
            $this->assets_version,
            true
        );
        
        wp_localize_script('wu-user-switcher', 'wuUserSwitcher', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wu_switch_user'),
            'isSwitched' => true
        ));
        
        // 添加 body class
        add_filter('body_class', function($classes) {
            $classes[] = 'wu-user-switched';
            return $classes;
        });
        
        add_filter('admin_body_class', function($classes) {
            return $classes . ' wu-user-switched';
        });
    }
    
    /**
     * 取得後台 CSS
     */
    private function get_admin_css() {
        return '
        .wu-user-switcher-stats {
            display: flex;
            gap: 20px;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        
        .wu-stat-box {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            min-width: 200px;
            border-left: 4px solid #0073aa;
        }
        
        .wu-stat-box strong {
            display: block;
            margin-bottom: 5px;
            color: #333;
        }
        
        .wu-stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #0073aa;
        }
        
        .wu-switch-warning {
            background: #fff3cd;
            border-left: 4px solid #ff8c00;
            padding: 15px;
            margin: 15px 0;
        }
        
        .wu-switch-warning h3 {
            margin-top: 0;
            color: #856404;
        }
        
        .wu-two-column {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }
        
        .wu-two-column > div {
            flex: 1;
            min-width: 250px;
        }
        
        .form-table th {
            width: 200px;
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
        
        .notice-warning {
            border-left-color: #ff8c00;
        }
        
        .notice-success {
            border-left-color: #46b450;
        }
        
        .notice-error {
            border-left-color: #dc3232;
        }
        ';
    }
    
    /**
     * 防止快取 (強化版本)
     */
    public function prevent_caching() {
        // 定義常數防止快取
        if (!defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }
        
        if (!defined('DONOTCACHEDB')) {
            define('DONOTCACHEDB', true);
        }
        
        if (!defined('DONOTMINIFY')) {
            define('DONOTMINIFY', true);
        }
        
        if (!defined('DONOTCDN')) {
            define('DONOTCDN', true);
        }
        
        if (!defined('DONOTCACHCEOBJECT')) {
            define('DONOTCACHCEOBJECT', true);
        }
    }
    
    /**
     * 通知快取外掛不要快取
     */
    public function notify_cache_plugins() {
        // WP Rocket
        if (function_exists('rocket_clean_domain')) {
            add_filter('rocket_cache_reject_uri', function($urls) {
                $urls[] = '.*';
                return $urls;
            });
        }
        
        // W3 Total Cache
        if (function_exists('w3tc_pgcache_flush')) {
            add_filter('w3tc_can_cache', '__return_false', 999);
        }
        
        // WP Super Cache
        if (function_exists('wp_cache_clear_cache')) {
            add_filter('wp_super_cache_disable_get_cookies', '__return_true');
        }
        
        // LiteSpeed Cache
        if (class_exists('LiteSpeed_Cache')) {
            if (method_exists('LiteSpeed_Cache', 'set_nocache')) {
                do_action('litespeed_control_set_nocache', 'user switched');
            }
        }
        
        // WP Fastest Cache
        if (class_exists('WpFastestCache')) {
            add_filter('wpfc_is_cache', '__return_false', 999);
        }
        
        // Autoptimize
        if (class_exists('autoptimizeCache')) {
            add_filter('autoptimize_filter_noptimize', '__return_true', 999);
        }
    }
    
    /**
     * 發送不快取標頭
     */
    public function send_no_cache_headers() {
        if (!headers_sent()) {
            nocache_headers();
            header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0, private');
            header('Pragma: no-cache');
            header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');
            header('X-Robots-Tag: noindex, nofollow', false);
        }
    }
    
    /**
     * 添加子選單頁面
     */
    public function add_admin_menu() {
        add_submenu_page(
            'wumetax-toolkit',
            '使用者切換器',
            '使用者切換器',
            'manage_options',
            'wu-user-switcher',
            array($this, 'admin_page')
        );
    }
    
    /**
     * 設定區段回調
     */
    public function section_callback() {
        echo '<p>配置使用者切換功能的相關設置。</p>';
    }
    
    /**
     * 管理頁面
     */
    public function admin_page() {
        // 驗證權限
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('您沒有足夠的權限訪問此頁面。', 'wumetax-toolkit'));
        }
        
        $this->settings = get_option($this->option_name, $this->get_default_settings());
        $recent_switches = $this->get_recent_switches();
        $stats = $this->get_switch_stats();
        
        ?>
        <div class="wrap">
            <h1>使用者切換設定</h1>
            
            <div class="notice notice-info">
                <h3>功能說明</h3>
                <p><strong>使用者切換功能</strong>讓管理員可以快速在不同用戶帳戶之間切換,無需重新登入,方便測試和支援。</p>
                
                <h4>主要功能:</h4>
                <ul>
                    <li><strong>即時切換</strong>:在用戶列表中點擊即可切換到該用戶</li>
                    <li><strong>快速返回</strong>:一鍵返回到原始管理員帳戶</li>
                    <li><strong>管理欄整合</strong>:在管理欄顯示切換選項</li>
                    <li><strong>權限保留</strong>:嚴格控制切換權限</li>
                </ul>
                
                <h4>安全措施 (v3.1 強化):</h4>
                <ul>
                    <li>Cookie 簽章驗證,防止偽造</li>
                    <li>會話令牌驗證,確保原始用戶在線</li>
                    <li>防止嵌套切換(切換狀態下無法再次切換)</li>
                    <li>自動記錄所有切換操作(可整合 Audit Logger)</li>
                    <li>會話超時自動清除</li>
                    <li>完整的快取防護(支援主流快取外掛)</li>
                    <li>WordPress 6.7 相容性修正</li>
                </ul>
            </div>
            
            <form method="post" action="options.php">
                <?php settings_fields('wu_user_switcher_group'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">啟用功能</th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo $this->option_name; ?>[enabled]" value="1" <?php checked($this->settings['enabled']); ?>>
                                啟用使用者切換功能
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">嚴格會話驗證</th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo $this->option_name; ?>[strict_session_validation]" value="1" <?php checked($this->settings['strict_session_validation']); ?>>
                                啟用嚴格的會話令牌驗證
                            </label>
                            <p class="description">驗證原始用戶的會話令牌是否仍然有效(強烈推薦)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">允許返回原用戶</th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo $this->option_name; ?>[allow_switch_back]" value="1" <?php checked($this->settings['allow_switch_back']); ?>>
                                顯示「返回原用戶」選項
                            </label>
                            <p class="description">允許用戶快速返回到原始帳戶</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">管理欄顯示</th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo $this->option_name; ?>[show_in_admin_bar]" value="1" <?php checked($this->settings['show_in_admin_bar']); ?>>
                                在管理欄中顯示切換選項
                            </label>
                            <p class="description">在前台和後台管理欄中顯示用戶切換功能</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">防止嵌套切換</th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo $this->option_name; ?>[prevent_nested_switch]" value="1" <?php checked($this->settings['prevent_nested_switch']); ?>>
                                禁止在切換狀態下再次切換
                            </label>
                            <p class="description">提高安全性,防止權限混亂(強烈推薦)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">清除 WooCommerce 會話</th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo $this->option_name; ?>[clear_wc_session]" value="1" <?php checked($this->settings['clear_wc_session']); ?>>
                                切換時清除 WooCommerce 會話資料
                            </label>
                            <p class="description">避免購物車和會話資料衝突</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">恢復購物車</th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo $this->option_name; ?>[restore_wc_cart]" value="1" <?php checked($this->settings['restore_wc_cart']); ?>>
                                返回時恢復原始購物車
                            </label>
                            <p class="description">切換回來時嘗試恢復管理員的購物車(實驗性功能)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">記錄切換操作</th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo $this->option_name; ?>[log_switches]" value="1" <?php checked($this->settings['log_switches']); ?>>
                                記錄所有用戶切換操作
                            </label>
                            <p class="description">為安全和審計目的記錄切換日誌 <?php if (class_exists('WU_Audit_Logger')): ?><span style="color: #46b450;">(✓ 已整合 Audit Logger)</span><?php endif; ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">會話超時時間(秒)</th>
                        <td>
                            <input type="number" name="<?php echo $this->option_name; ?>[session_timeout]" value="<?php echo esc_attr($this->settings['session_timeout']); ?>" min="300" max="86400" class="regular-text">
                            <p class="description">切換會話的超時時間(預設:3600秒 = 1小時)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">允許切換的角色</th>
                        <td>
                            <?php $roles = wp_roles()->get_names(); ?>
                            <?php foreach ($roles as $role_key => $role_name): ?>
                            <label style="display: block; margin: 5px 0;">
                                <input type="checkbox" name="<?php echo $this->option_name; ?>[allowed_roles][]" value="<?php echo esc_attr($role_key); ?>" 
                                       <?php checked(in_array($role_key, $this->settings['allowed_roles'])); ?>>
                                <?php echo esc_html($role_name); ?> (<?php echo esc_html($role_key); ?>)
                            </label>
                            <?php endforeach; ?>
                            <p class="description">只有這些角色的用戶可以執行切換操作</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">受限制的用戶 ID</th>
                        <td>
                            <textarea name="<?php echo $this->option_name; ?>[restricted_users]" rows="5" cols="50" placeholder="每行一個用戶ID,例如:
1
2
3"><?php echo esc_textarea(implode("\n", $this->settings['restricted_users'])); ?></textarea>
                            <p class="description">這些用戶無法被切換(一行一個ID)</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('儲存設定'); ?>
            </form>
            
            <hr>
            
            <h2>統計資訊</h2>
            <div class="wu-user-switcher-stats">
                <div class="wu-stat-box">
                    <strong>今日切換次數</strong>
                    <div class="wu-stat-number"><?php echo number_format($stats['today']); ?></div>
                </div>
                <div class="wu-stat-box">
                    <strong>本週切換次數</strong>
                    <div class="wu-stat-number" style="color: #46b450;"><?php echo number_format($stats['week']); ?></div>
                </div>
                <div class="wu-stat-box">
                    <strong>總切換次數</strong>
                    <div class="wu-stat-number" style="color: #ff8c00;"><?php echo number_format($stats['total']); ?></div>
                </div>
            </div>
            
            <?php if ($this->is_switched_user()): ?>
            <div class="wu-switch-warning">
                <h3>⚠️ 目前切換狀態</h3>
                <?php $original_user = $this->get_original_user(); ?>
                <p>您目前已從 <strong><?php echo $original_user ? esc_html($original_user->display_name) : '未知用戶'; ?></strong> 切換到 <strong><?php echo esc_html(wp_get_current_user()->display_name); ?></strong></p>
                <p>
                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?action=wu_switch_back'), 'wu_switch_back'); ?>" class="button button-primary">
                        返回原用戶
                    </a>
                </p>
            </div>
            <?php endif; ?>
            
            <h2>最近的切換記錄</h2>
            <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
                <?php if (!empty($recent_switches)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>時間</th>
                            <th>操作用戶</th>
                            <th>目標用戶</th>
                            <th>操作類型</th>
                            <th>IP 位址</th>
                            <th>狀態</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_switches as $switch): ?>
                        <tr>
                            <td><?php echo esc_html(date('Y-m-d H:i:s', strtotime($switch['timestamp']))); ?></td>
                            <td>
                                <?php
                                $operator = get_user_by('ID', $switch['operator_id']);
                                echo $operator ? esc_html($operator->display_name) : '未知用戶';
                                ?>
                            </td>
                            <td>
                                <?php
                                $target = get_user_by('ID', $switch['target_id']);
                                echo $target ? esc_html($target->display_name) : '未知用戶';
                                ?>
                            </td>
                            <td>
                                <span style="color: <?php echo $switch['action'] === 'switch_to' ? '#0073aa' : '#46b450'; ?>;">
                                    <?php echo $switch['action'] === 'switch_to' ? '切換到' : '返回'; ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($switch['ip_address']); ?></td>
                            <td>
                                <span style="color: <?php echo isset($switch['verified']) && $switch['verified'] ? '#46b450' : '#999'; ?>;">
                                    <?php echo isset($switch['verified']) && $switch['verified'] ? '✓ 已驗證' : '- 歷史記錄'; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p>暫無切換記錄</p>
                <?php endif; ?>
            </div>
            
            <h2>快取防護狀態</h2>
            <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
                <?php $cache_status = $this->detect_cache_plugins(); ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>快取外掛</th>
                            <th>狀態</th>
                            <th>防護</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cache_status as $plugin => $status): ?>
                        <tr>
                            <td><strong><?php echo esc_html($plugin); ?></strong></td>
                            <td>
                                <span style="color: <?php echo $status['active'] ? '#46b450' : '#999'; ?>;">
                                    <?php echo $status['active'] ? '✓ 已安裝' : '- 未安裝'; ?>
                                </span>
                            </td>
                            <td>
                                <span style="color: <?php echo $status['protected'] ? '#46b450' : '#ff8c00'; ?>;">
                                    <?php echo $status['protected'] ? '✓ 已防護' : '⚠️ 建議測試'; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p style="margin-top: 15px;"><em>當用戶處於切換狀態時,系統會自動通知這些快取外掛不要快取頁面。</em></p>
            </div>
            
            <h2>使用說明</h2>
            <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
                <h3>如何使用用戶切換功能:</h3>
                <ol>
                    <li><strong>在用戶列表中切換</strong>:前往「用戶」→「所有用戶」,點擊用戶旁邊的「切換到此用戶」連結</li>
                    <li><strong>透過管理欄切換</strong>:在管理欄中找到用戶切換選項</li>
                    <li><strong>返回原用戶</strong>:切換後可透過管理欄的紅色「返回原用戶」按鈕返回</li>
                    <li><strong>查看切換狀態</strong>:管理欄會以橙色閃爍顯示目前的切換狀態,前台會顯示警示橫幅</li>
                </ol>
                
                <div class="wu-two-column" style="margin-top: 15px;">
                    <div>
                        <h4>可以切換的用戶:</h4>
                        <ul>
                            <li>擁有允許角色的用戶</li>
                            <li>未被限制的用戶</li>
                            <li>具有適當權限的管理員</li>
                        </ul>
                    </div>
                    <div>
                        <h4>安全限制:</h4>
                        <ul>
                            <li>無法切換到受限制的用戶</li>
                            <li>無法切換到自己的帳戶</li>
                            <li>切換狀態下無法再次切換</li>
                            <li>會話令牌持續驗證</li>
                            <li>會話會自動超時</li>
                            <li>所有操作都會被記錄</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * 偵測快取外掛
     */
    private function detect_cache_plugins() {
        return array(
            'WP Rocket' => array(
                'active' => function_exists('rocket_clean_domain'),
                'protected' => function_exists('rocket_clean_domain')
            ),
            'W3 Total Cache' => array(
                'active' => function_exists('w3tc_pgcache_flush'),
                'protected' => function_exists('w3tc_pgcache_flush')
            ),
            'WP Super Cache' => array(
                'active' => function_exists('wp_cache_clear_cache'),
                'protected' => function_exists('wp_cache_clear_cache')
            ),
            'LiteSpeed Cache' => array(
                'active' => class_exists('LiteSpeed_Cache'),
                'protected' => class_exists('LiteSpeed_Cache')
            ),
            'WP Fastest Cache' => array(
                'active' => class_exists('WpFastestCache'),
                'protected' => class_exists('WpFastestCache')
            ),
            'Autoptimize' => array(
                'active' => class_exists('autoptimizeCache'),
                'protected' => class_exists('autoptimizeCache')
            )
        );
    }
    
    /**
     * 在用戶列表添加切換連結
     */
    public function add_switch_link($actions, $user) {
        if (!$this->settings['enabled']) {
            return $actions;
        }
        
        if (!$this->user_can_switch()) {
            return $actions;
        }
        
        // 如果已經在切換狀態且禁止嵌套切換
        if ($this->is_switched_user() && $this->settings['prevent_nested_switch']) {
            return $actions;
        }
        
        if (!$this->can_switch_to_user($user->ID)) {
            return $actions;
        }
        
        $switch_url = wp_nonce_url(
            admin_url('admin.php?action=wu_switch_to_user&user=' . $user->ID),
            'wu_switch_to_user_' . $user->ID
        );
        
        $actions['switch_to_user'] = sprintf(
            '<a href="%s" title="%s" style="color: #0073aa; font-weight: 600;">%s</a>',
            esc_url($switch_url),
            esc_attr__('切換到此用戶', 'wumetax-toolkit'),
            __('切換到此用戶', 'wumetax-toolkit')
        );
        
        return $actions;
    }
    
    /**
     * 添加管理欄選單
     */
    public function add_admin_bar_menu($wp_admin_bar) {
        if (!$this->settings['enabled'] || !$this->settings['show_in_admin_bar']) {
            return;
        }
        
        if (!$this->user_can_switch() && !$this->is_switched_user()) {
            return;
        }
        
        // 如果目前是切換狀態,顯示返回選項
        if ($this->is_switched_user()) {
            $original_user = $this->get_original_user();
            if ($original_user && $this->settings['allow_switch_back']) {
                $wp_admin_bar->add_node(array(
                    'id' => 'wu-switch-back',
                    'title' => '<span class="ab-icon"></span><span class="ab-label">返回 ' . esc_html($original_user->display_name) . '</span>',
                    'href' => wp_nonce_url(admin_url('admin.php?action=wu_switch_back'), 'wu_switch_back'),
                    'meta' => array(
                        'class' => 'wu-switch-back',
                        'title' => '返回到原始用戶帳戶'
                    )
                ));
            }
            
            // 顯示切換狀態
            $current_user = wp_get_current_user();
            $wp_admin_bar->add_node(array(
                'id' => 'wu-user-status',
                'title' => '<span class="ab-icon"></span><span class="ab-label">已切換到: ' . esc_html($current_user->display_name) . '</span>',
                'meta' => array(
                    'class' => 'wu-switched-user',
                    'title' => '您目前處於用戶切換狀態'
                )
            ));
        }
    }
    
    /**
     * 切換到用戶
     */
    public function switch_to_user() {
        // 驗證 Nonce
        if (!isset($_GET['_wpnonce']) || !isset($_GET['user'])) {
            wp_die('無效的請求');
        }
        
        $user_id = intval($_GET['user']);
        
        if (!wp_verify_nonce($_GET['_wpnonce'], 'wu_switch_to_user_' . $user_id)) {
            wp_die('安全驗證失敗');
        }
        
        // 檢查權限
        if (!$this->user_can_switch()) {
            wp_die('權限不足');
        }
        
        // 防止嵌套切換
        if ($this->is_switched_user() && $this->settings['prevent_nested_switch']) {
            wp_die('您目前已在切換狀態,請先返回原用戶');
        }
        
        // 驗證目標用戶
        if (!$this->can_switch_to_user($user_id)) {
            wp_die('無法切換到此用戶');
        }
        
        // 執行切換
        $result = $this->perform_switch($user_id);
        
        if (!$result) {
            wp_die('切換失敗');
        }
        
        // 重定向
        $redirect_url = isset($_GET['redirect_to']) ? $_GET['redirect_to'] : (is_admin() ? admin_url() : home_url());
        wp_safe_redirect($redirect_url);
        exit;
    }
    
    /**
     * 返回原用戶
     */
    public function switch_back() {
        // 驗證 Nonce
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'wu_switch_back')) {
            wp_die('安全驗證失敗');
        }
        
        // 檢查切換狀態
        if (!$this->is_switched_user()) {
            wp_die('您目前不在切換狀態');
        }
        
        // 取得原始用戶
        $original_user_id = $this->get_original_user_id();
        if (!$original_user_id) {
            wp_die('無法找到原始用戶');
        }
        
        // 執行返回
        $result = $this->perform_switch_back($original_user_id);
        
        if (!$result) {
            wp_die('返回失敗');
        }
        
        // 重定向
        $redirect_url = isset($_GET['redirect_to']) ? $_GET['redirect_to'] : admin_url();
        wp_safe_redirect($redirect_url);
        exit;
    }
    
    /**
     * AJAX 切換用戶
     */
    public function ajax_switch_user() {
        // 驗證 Nonce
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wu_switch_user')) {
            wp_send_json_error('安全驗證失敗');
        }
        
        // 檢查權限
        if (!$this->user_can_switch()) {
            wp_send_json_error('權限不足');
        }
        
        $user_id = intval($_POST['user_id']);
        
        // 防止嵌套切換
        if ($this->is_switched_user() && $this->settings['prevent_nested_switch']) {
            wp_send_json_error('您目前已在切換狀態,請先返回原用戶');
        }
        
        // 驗證目標用戶
        if (!$this->can_switch_to_user($user_id)) {
            wp_send_json_error('無法切換到此用戶');
        }
        
        // 執行切換
        $result = $this->perform_switch($user_id);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => '切換成功',
                'redirect_url' => admin_url()
            ));
        } else {
            wp_send_json_error('切換失敗');
        }
    }
    
    /**
     * 執行切換
     */
    private function perform_switch($user_id) {
        $target_user = get_user_by('ID', $user_id);
        if (!$target_user) {
            return false;
        }
        
        $current_user_id = get_current_user_id();
        
        // 保存原始用戶資訊(如果還沒有切換)
        if (!$this->is_switched_user()) {
            $original_user = wp_get_current_user();
            $this->set_original_user($original_user);
            
            // 保存 WooCommerce 購物車(如果需要)
            if ($this->settings['restore_wc_cart'] && class_exists('WooCommerce')) {
                $this->save_woocommerce_cart($original_user->ID);
            }
        }
        
        // 清除 WooCommerce 會話
        if ($this->settings['clear_wc_session']) {
            $this->clear_woocommerce_session();
        }
        
        // 設定新的用戶會話
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);
        
        // 更新計數器
        $this->increment_switch_counter();
        
        // 記錄切換操作
        if ($this->settings['log_switches']) {
            $this->log_switch_action('switch_to', $user_id, $current_user_id);
        }
        
        // 觸發切換事件
        do_action('wu_user_switched', $user_id, $current_user_id);
        
        return true;
    }
    
    /**
     * 執行返回
     */
    private function perform_switch_back($original_user_id) {
        // 清除 WooCommerce 會話
        if ($this->settings['clear_wc_session']) {
            $this->clear_woocommerce_session();
        }
        
        // 返回原始用戶
        wp_set_current_user($original_user_id);
        wp_set_auth_cookie($original_user_id);
        
        // 恢復購物車(如果需要)
        if ($this->settings['restore_wc_cart'] && class_exists('WooCommerce')) {
            $this->restore_woocommerce_cart($original_user_id);
        }
        
        // 記錄返回操作
        if ($this->settings['log_switches']) {
            $this->log_switch_action('switch_back', $original_user_id, get_current_user_id());
        }
        
        // 清除切換資訊
        $this->clear_switch_cookie();
        
        // 觸發返回事件
        do_action('wu_user_switched_back', $original_user_id);
        
        return true;
    }
    
    /**
     * 檢查用戶是否可以切換
     */
    private function user_can_switch() {
        $current_user = wp_get_current_user();
        
        if (!$current_user || !$current_user->exists()) {
            return false;
        }
        
        // 檢查角色權限
        $user_roles = $current_user->roles;
        $allowed_roles = $this->settings['allowed_roles'];
        
        // 如果用戶是切換狀態,需要檢查原始用戶的角色
        if ($this->is_switched_user()) {
            $original_user = $this->get_original_user();
            if ($original_user) {
                $user_roles = $original_user->roles;
            }
        }
        
        return !empty(array_intersect($user_roles, $allowed_roles));
    }
    
    /**
     * 檢查是否可以切換到指定用戶
     */
    private function can_switch_to_user($user_id) {
        $current_user_id = get_current_user_id();
        
        // 不能切換到自己
        if ($user_id === $current_user_id) {
            return false;
        }
        
        // 檢查是否在受限制列表中
        if (in_array($user_id, $this->settings['restricted_users'])) {
            return false;
        }
        
        // 檢查目標用戶是否存在
        $target_user = get_user_by('ID', $user_id);
        if (!$target_user) {
            return false;
        }
        
        return true;
    }
    
    /**
     * 檢查是否為切換用戶 (強化版本)
     */
    private function is_switched_user() {
        if (!isset($_COOKIE[$this->cookie_name])) {
            return false;
        }
        
        $data = $this->decode_switch_cookie($_COOKIE[$this->cookie_name]);
        
        if (!$data) {
            return false;
        }
        
        // 檢查是否過期
        if ($data['expires'] < time()) {
            $this->clear_switch_cookie();
            return false;
        }
        
        // 嚴格會話驗證
        if ($this->settings['strict_session_validation']) {
            if (!$this->validate_session_token($data)) {
                $this->clear_switch_cookie();
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * 驗證會話令牌 (新增)
     */
    private function validate_session_token($cookie_data) {
        if (!isset($cookie_data['original_user_id']) || !isset($cookie_data['original_session_token'])) {
            return false;
        }
        
        $original_user_id = intval($cookie_data['original_user_id']);
        $original_session_token = $cookie_data['original_session_token'];
        
        // 使用 WordPress API 驗證會話
        $sessions = WP_Session_Tokens::get_instance($original_user_id);
        $session = $sessions->get($original_session_token);
        
        // 如果會話不存在或已過期,返回 false
        if (!$session) {
            return false;
        }
        
        return true;
    }
    
    /**
     * 取得原始用戶
     */
    private function get_original_user() {
        $original_user_id = $this->get_original_user_id();
        return $original_user_id ? get_user_by('ID', $original_user_id) : null;
    }
    
    /**
     * 取得原始用戶 ID
     */
    private function get_original_user_id() {
        if (!isset($_COOKIE[$this->cookie_name])) {
            return null;
        }
        
        $data = $this->decode_switch_cookie($_COOKIE[$this->cookie_name]);
        
        if (!$data || $data['expires'] < time()) {
            return null;
        }
        
        return intval($data['original_user_id']);
    }
    
    /**
     * 設定原始用戶 Cookie (帶 HMAC 簽章)
     */
    private function set_original_user($user) {
        $expires = time() + $this->settings['session_timeout'];
        
        $data = array(
            'original_user_id' => $user->ID,
            'original_session_token' => wp_get_session_token(),
            'expires' => $expires
        );
        
        $cookie_value = $this->encode_switch_cookie($data);
        
        setcookie(
            $this->cookie_name,
            $cookie_value,
            $expires,
            COOKIEPATH,
            COOKIE_DOMAIN,
            is_ssl(),
            true
        );
    }
    
    /**
     * 編碼並簽章 Cookie 資料
     */
    private function encode_switch_cookie($data) {
        $json = wp_json_encode($data);
        $signature = wp_hash($json . AUTH_KEY);
        
        $payload = array(
            'data' => $json,
            'signature' => $signature
        );
        
        return base64_encode(wp_json_encode($payload));
    }
    
    /**
     * 解碼並驗證 Cookie 資料
     */
    private function decode_switch_cookie($cookie_value) {
        $payload = json_decode(base64_decode($cookie_value), true);
        
        if (!$payload || !isset($payload['data']) || !isset($payload['signature'])) {
            return null;
        }
        
        // 驗證簽章
        $expected_signature = wp_hash($payload['data'] . AUTH_KEY);
        if (!hash_equals($expected_signature, $payload['signature'])) {
            return null;
        }
        
        return json_decode($payload['data'], true);
    }
    
    /**
     * 清除切換 Cookie
     */
    public function clear_switch_cookie($user_login = null, $user = null) {
        if (isset($_COOKIE[$this->cookie_name])) {
            setcookie(
                $this->cookie_name,
                '',
                time() - 3600,
                COOKIEPATH,
                COOKIE_DOMAIN,
                is_ssl(),
                true
            );
            unset($_COOKIE[$this->cookie_name]);
        }
    }
    
    /**
     * 驗證切換會話 (強化版本)
     */
    public function validate_switch_session() {
        if (!$this->is_switched_user()) {
            return;
        }
        
        $data = $this->decode_switch_cookie($_COOKIE[$this->cookie_name]);
        
        if (!$data) {
            $this->clear_switch_cookie();
            return;
        }
        
        // 檢查原始用戶的會話是否仍然有效
        $original_user = $this->get_original_user();
        if (!$original_user) {
            $this->clear_switch_cookie();
            wp_logout();
            wp_die('原始用戶不存在,已自動登出');
        }
        
        // 嚴格會話驗證
        if ($this->settings['strict_session_validation']) {
            if (!$this->validate_session_token($data)) {
                $this->clear_switch_cookie();
                wp_logout();
                wp_die('原始會話已失效,已自動登出');
            }
        }
    }
    
    /**
     * 處理 WooCommerce 會話
     */
    public function handle_woocommerce_session($new_user_id, $old_user_id) {
        if ($this->settings['clear_wc_session']) {
            $this->clear_woocommerce_session();
        }
    }
    
    /**
     * 清除 WooCommerce 會話
     */
    private function clear_woocommerce_session() {
        if (!class_exists('WooCommerce')) {
            return;
        }
        
        if (function_exists('WC') && WC()->session) {
            WC()->session->destroy_session();
        }
        
        if (function_exists('WC') && WC()->cart) {
            WC()->cart->empty_cart();
        }
    }
    
    /**
     * 保存 WooCommerce 購物車
     */
    private function save_woocommerce_cart($user_id) {
        if (!class_exists('WooCommerce')) {
            return;
        }
        
        if (function_exists('WC') && WC()->cart) {
            $cart_contents = WC()->cart->get_cart_contents();
            update_user_meta($user_id, '_wu_saved_cart', $cart_contents);
        }
    }
    
    /**
     * 恢復 WooCommerce 購物車
     */
    private function restore_woocommerce_cart($user_id) {
        if (!class_exists('WooCommerce')) {
            return;
        }
        
        $saved_cart = get_user_meta($user_id, '_wu_saved_cart', true);
        
        if ($saved_cart && function_exists('WC') && WC()->cart) {
            WC()->cart->empty_cart();
            
            foreach ($saved_cart as $cart_item_key => $cart_item) {
                WC()->cart->add_to_cart(
                    $cart_item['product_id'],
                    $cart_item['quantity'],
                    $cart_item['variation_id'],
                    $cart_item['variation'],
                    $cart_item
                );
            }
            
            delete_user_meta($user_id, '_wu_saved_cart');
        }
    }
    
    /**
     * 記錄切換操作 (整合 Audit Logger 優先)
     */
    private function log_switch_action($action, $target_user_id, $operator_user_id) {
        // 優先使用 Audit Logger
        if (class_exists('WU_Audit_Logger')) {
            $logger = new WU_Audit_Logger();
            $logger->log_event(
                $action === 'switch_to' ? 'user_switched' : 'user_switched_back',
                array(
                    'operator_id' => $operator_user_id,
                    'target_id' => $target_user_id,
                    'ip_address' => $this->get_client_ip(),
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
                )
            );
        } else {
            // 備用簡單日誌(僅保留最近50筆)
            $log_data = get_option('wu_user_switcher_log', array());
            
            $log_entry = array(
                'timestamp' => current_time('mysql'),
                'operator_id' => $operator_user_id,
                'target_id' => $target_user_id,
                'action' => $action,
                'ip_address' => $this->get_client_ip(),
                'verified' => true
            );
            
            array_unshift($log_data, $log_entry);
            
            // 只保留最近50筆記錄(減少資料量)
            if (count($log_data) > 50) {
                $log_data = array_slice($log_data, 0, 50);
            }
            
            update_option('wu_user_switcher_log', $log_data, false); // 不自動載入
        }
    }
    
    /**
     * 取得最近的切換記錄
     */
    private function get_recent_switches($limit = 20) {
        // 如果使用 Audit Logger,從那裡讀取
        if (class_exists('WU_Audit_Logger')) {
            $logger = new WU_Audit_Logger();
            // 假設有 get_events 方法
            if (method_exists($logger, 'get_events')) {
                return $logger->get_events(array(
                    'event_type' => array('user_switched', 'user_switched_back'),
                    'limit' => $limit
                ));
            }
        }
        
        // 否則使用簡單日誌
        $log_data = get_option('wu_user_switcher_log', array());
        return array_slice($log_data, 0, $limit);
    }
    
    /**
     * 增加切換計數器 (優化版本)
     */
    private function increment_switch_counter() {
        $counter = get_option($this->counter_option, array(
            'total' => 0,
            'daily' => array(),
            'weekly' => array()
        ));
        
        $today = date('Y-m-d');
        $week = date('Y-W');
        
        // 增加總計數
        $counter['total']++;
        
        // 增加今日計數
        if (!isset($counter['daily'][$today])) {
            $counter['daily'][$today] = 0;
        }
        $counter['daily'][$today]++;
        
        // 增加本週計數
        if (!isset($counter['weekly'][$week])) {
            $counter['weekly'][$week] = 0;
        }
        $counter['weekly'][$week]++;
        
        // 清理舊資料 (保留30天)
        $counter['daily'] = array_slice($counter['daily'], -30, null, true);
        $counter['weekly'] = array_slice($counter['weekly'], -8, null, true);
        
        update_option($this->counter_option, $counter, false); // 不自動載入
    }
    
    /**
     * 取得切換統計
     */
    private function get_switch_stats() {
        $counter = get_option($this->counter_option, array(
            'total' => 0,
            'daily' => array(),
            'weekly' => array()
        ));
        
        $today = date('Y-m-d');
        $week = date('Y-W');
        
        return array(
            'total' => $counter['total'],
            'today' => isset($counter['daily'][$today]) ? $counter['daily'][$today] : 0,
            'week' => isset($counter['weekly'][$week]) ? $counter['weekly'][$week] : 0
        );
    }
    
    /**
     * 清理所有切換會話
     */
    private function cleanup_all_switch_sessions() {
        $this->clear_switch_cookie();
    }
    
    /**
     * 取得客戶端 IP
     */
    private function get_client_ip() {
        $ip_keys = array(
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}

// 初始化模組
new WU_User_Switcher();
