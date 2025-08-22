<?php
/**
 * Hide Login Page Module
 * 隱藏WordPress後台登入位置工具
 */

if (!defined('ABSPATH')) exit;

class WU_Hide_Login_Page {
    
    private $options;
    private $option_name = 'wu_hide_login_page_options';
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_submenu_page'));
        add_action('admin_init', array($this, 'init_settings'));
        add_action('init', array($this, 'init_hide_login'));
        add_action('wp_loaded', array($this, 'redirect_login_pages'));
        add_filter('site_url', array($this, 'modify_login_url'), 10, 4);
        add_filter('wp_redirect', array($this, 'modify_redirect_url'), 10, 2);
        add_filter('network_site_url', array($this, 'modify_login_url'), 10, 3);
        
        // 載入選項
        $this->options = get_option($this->option_name, array(
            'enabled' => false,
            'custom_slug' => 'loginwu',
            'redirect_url' => home_url()
        ));
    }
    
    /**
     * 添加子選單頁面
     */
    public function add_submenu_page() {
        add_submenu_page(
            'wu-toolbox',
            '隱藏登入頁面',
            '隱藏登入頁面',
            'manage_options',
            'wu-hide-login-page',
            array($this, 'settings_page')
        );
    }
    
    /**
     * 初始化設置
     */
    public function init_settings() {
        register_setting(
            'wu_hide_login_page_group',
            $this->option_name,
            array($this, 'sanitize_options')
        );
        
        add_settings_section(
            'wu_hide_login_page_section',
            '隱藏登入頁面設置',
            array($this, 'section_callback'),
            'wu-hide-login-page'
        );
        
        add_settings_field(
            'enabled',
            '啟用功能',
            array($this, 'enabled_callback'),
            'wu-hide-login-page',
            'wu_hide_login_page_section'
        );
        
        add_settings_field(
            'custom_slug',
            '自定義登入網址',
            array($this, 'custom_slug_callback'),
            'wu-hide-login-page',
            'wu_hide_login_page_section'
        );
        
        add_settings_field(
            'redirect_url',
            '重定向網址',
            array($this, 'redirect_url_callback'),
            'wu-hide-login-page',
            'wu_hide_login_page_section'
        );
    }
    
    /**
     * 設置頁面HTML
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>隱藏登入頁面設置</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('wu_hide_login_page_group');
                do_settings_sections('wu-hide-login-page');
                submit_button();
                ?>
            </form>
            
            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2>功能說明</h2>
                <p><strong>此功能的作用：</strong></p>
                <ul>
                    <li>隱藏WordPress預設的後台登入位置</li>
                    <li>將登入頁面改為自定義網址（預設：/loginwu）</li>
                    <li>訪客無法使用 /wp-admin 或 /wp-login.php 登入</li>
                    <li>已登入的管理員不受影響</li>
                </ul>
                
                <p><strong>注意事項：</strong></p>
                <ul>
                    <li>啟用後，請記住新的登入網址</li>
                    <li>建議將新登入網址加入書籤</li>
                    <li>如果忘記新網址，可以通過資料庫或FTP停用此功能</li>
                </ul>
                
                <?php if ($this->options['enabled']): ?>
                <div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 4px; margin-top: 15px;">
                    <h3 style="margin-top: 0; color: #155724;">功能已啟用</h3>
                    <p><strong>新的登入網址：</strong> <code><?php echo home_url('/' . $this->options['custom_slug'] . '/'); ?></code></p>
                    <p><strong>請將此網址加入書籤！</strong></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * 設置區段回調
     */
    public function section_callback() {
        echo '<p>配置隱藏登入頁面功能的相關設置。</p>';
    }
    
    /**
     * 啟用功能回調
     */
    public function enabled_callback() {
        $enabled = isset($this->options['enabled']) ? $this->options['enabled'] : false;
        ?>
        <label>
            <input type="checkbox" name="<?php echo $this->option_name; ?>[enabled]" value="1" <?php checked(1, $enabled); ?> />
            啟用隱藏登入頁面功能
        </label>
        <?php
    }
    
    /**
     * 自定義網址回調
     */
    public function custom_slug_callback() {
        $custom_slug = isset($this->options['custom_slug']) ? $this->options['custom_slug'] : 'loginwu';
        ?>
        <input type="text" name="<?php echo $this->option_name; ?>[custom_slug]" value="<?php echo esc_attr($custom_slug); ?>" class="regular-text" />
        <p class="description">自定義登入頁面的網址結尾，預設為 "loginwu"。新的登入網址將是：<?php echo home_url('/'); ?><strong><?php echo esc_html($custom_slug); ?></strong>/</p>
        <?php
    }
    
    /**
     * 重定向網址回調
     */
    public function redirect_url_callback() {
        $redirect_url = isset($this->options['redirect_url']) ? $this->options['redirect_url'] : home_url();
        ?>
        <input type="url" name="<?php echo $this->option_name; ?>[redirect_url]" value="<?php echo esc_url($redirect_url); ?>" class="regular-text" />
        <p class="description">當訪客嘗試訪問被隱藏的登入頁面時，將重定向到此網址。</p>
        <?php
    }
    
    /**
     * 清理選項
     */
    public function sanitize_options($input) {
        $sanitized = array();
        
        $sanitized['enabled'] = isset($input['enabled']) ? true : false;
        $sanitized['custom_slug'] = sanitize_text_field($input['custom_slug']);
        $sanitized['redirect_url'] = esc_url_raw($input['redirect_url']);
        
        // 確保custom_slug不為空
        if (empty($sanitized['custom_slug'])) {
            $sanitized['custom_slug'] = 'loginwu';
        }
        
        // 確保redirect_url不為空
        if (empty($sanitized['redirect_url'])) {
            $sanitized['redirect_url'] = home_url();
        }
        
        return $sanitized;
    }
    
    /**
     * 初始化隱藏登入功能
     */
    public function init_hide_login() {
        if (!$this->options['enabled']) {
            return;
        }
        
        // 添加自定義登入頁面
        add_action('init', array($this, 'add_custom_login_endpoint'));
    }
    
    /**
     * 添加自定義登入端點
     */
    public function add_custom_login_endpoint() {
        add_rewrite_rule(
            '^' . $this->options['custom_slug'] . '/?$',
            'index.php?pagename=wp-login',
            'top'
        );
        
        add_rewrite_rule(
            '^' . $this->options['custom_slug'] . '/wp-admin/?$',
            'index.php?pagename=wp-login&redirect_to=' . admin_url(),
            'top'
        );
        
        // 刷新重寫規則（僅在管理員頁面）
        if (is_admin() && isset($_GET['page']) && $_GET['page'] === 'wu-hide-login-page') {
            flush_rewrite_rules();
        }
    }
    
    /**
     * 重定向登入頁面
     */
    public function redirect_login_pages() {
        if (!$this->options['enabled']) {
            return;
        }
        
        // 如果用戶已登入，不執行重定向
        if (is_user_logged_in()) {
            return;
        }
        
        $current_url = $_SERVER['REQUEST_URI'];
        
        // 檢查是否訪問被隱藏的登入頁面
        if (strpos($current_url, '/wp-login.php') !== false || 
            strpos($current_url, '/wp-admin') !== false) {
            
            // 允許訪問自定義登入頁面
            if (strpos($current_url, '/' . $this->options['custom_slug']) !== false) {
                return;
            }
            
            // 重定向到設定的網址
            wp_redirect($this->options['redirect_url']);
            exit;
        }
    }
    
    /**
     * 修改登入URL
     */
    public function modify_login_url($url, $path = '', $scheme = null, $blog_id = null) {
        if (!$this->options['enabled']) {
            return $url;
        }
        
        if ($path === 'wp-login.php') {
            return home_url('/' . $this->options['custom_slug'] . '/');
        }
        
        return $url;
    }
    
    /**
     * 修改重定向URL
     */
    public function modify_redirect_url($location, $status) {
        if (!$this->options['enabled']) {
            return $location;
        }
        
        // 如果重定向到wp-login.php，改為自定義登入頁面
        if (strpos($location, 'wp-login.php') !== false) {
            $location = str_replace('wp-login.php', $this->options['custom_slug'], $location);
        }
        
        return $location;
    }
}

// 初始化類別
new WU_Hide_Login_Page();

// 添加重寫標籤
function wu_hide_login_query_vars($vars) {
    $vars[] = 'pagename';
    return $vars;
}
add_filter('query_vars', 'wu_hide_login_query_vars');

// 處理自定義登入頁面
function wu_hide_login_template_redirect() {
    $options = get_option('wu_hide_login_page_options', array());
    
    if (!$options['enabled']) {
        return;
    }
    
    $custom_slug = isset($options['custom_slug']) ? $options['custom_slug'] : 'loginwu';
    
    if (strpos($_SERVER['REQUEST_URI'], '/' . $custom_slug) !== false) {
        // 載入WordPress登入頁面
        require_once(ABSPATH . 'wp-login.php');
        exit;
    }
}
add_action('template_redirect', 'wu_hide_login_template_redirect');