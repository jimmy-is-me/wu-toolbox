<?php
/**
 * Hide Login Page Module
 * 隱藏WordPress後台登入位置工具
 * 版本:2.0 - 安全防禦強化、效能優化、相容性改善
 */
if (!defined('ABSPATH')) exit;

class WU_Hide_Login_Page {
    
    private $options;
    private $option_name = 'wu_hide_login_page_options';
    
    /**
     * WordPress 保留字
     */
    private $reserved_slugs = array(
        'admin', 'login', 'wp-admin', 'wp-login', 'wp-content', 'wp-includes',
        'search', 'feed', 'rss', 'atom', 'rdf', 'comments', 'trackback',
        'page', 'pages', 'category', 'tag', 'author', 'attachment'
    );
    
    public function __construct() {
        // 後台功能
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_submenu_page'), 50);
            add_action('admin_init', array($this, 'init_settings'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        }
        
        // 前台功能
        add_action('init', array($this, 'init_hide_login'));
        add_action('wp_loaded', array($this, 'redirect_login_pages'));
        add_action('template_redirect', array($this, 'handle_custom_login'));
        
        // URL 修改
        add_filter('site_url', array($this, 'modify_login_url'), 10, 4);
        add_filter('wp_redirect', array($this, 'modify_redirect_url'), 10, 2);
        add_filter('network_site_url', array($this, 'modify_login_url'), 10, 3);
        
        // 載入選項
        $this->options = get_option($this->option_name, array(
            'enabled' => false,
            'custom_slug' => 'loginwu',
            'redirect_url' => home_url(),
            'redirect_mode' => 'redirect', // redirect 或 404
            'last_slug' => '' // 記錄上次的 slug
        ));
    }
    
    /**
     * 添加子選單頁面
     */
    public function add_submenu_page() {
        add_submenu_page(
            'wumetax-toolkit',
            '變更登入網址',
            '變更登入網址',
            'manage_options',
            'wu-hide-login-page',
            array($this, 'settings_page')
        );
    }
    
    /**
     * 載入後台資源
     */
    public function enqueue_admin_assets($hook) {
        // 僅在設定頁面載入
        if ($hook !== 'wumetaxtoolkit_page_wu-hide-login-page') {
            return;
        }
        
        // 註冊並載入 CSS
        wp_enqueue_style(
            'wu-hide-login-admin',
            false,
            array(),
            '2.0'
        );
        
        wp_add_inline_style('wu-hide-login-admin', $this->get_admin_css());
        
        // 註冊並載入 JavaScript
        wp_enqueue_script(
            'wu-hide-login-admin',
            false,
            array('jquery'),
            '2.0',
            true
        );
        
        // 傳遞保留字到 JavaScript
        wp_localize_script('wu-hide-login-admin', 'wuHideLoginData', array(
            'reservedSlugs' => $this->reserved_slugs
        ));
        
        wp_add_inline_script('wu-hide-login-admin', $this->get_admin_js());
    }
    
    /**
     * 取得後台 CSS
     */
    private function get_admin_css() {
        return '
        .wu-termius-input {
            background-color: #1e1e1e !important;
            color: #00ff41 !important;
            border: 1px solid #333 !important;
            border-radius: 4px !important;
            font-family: "Monaco", "Menlo", "Ubuntu Mono", monospace !important;
            font-size: 14px !important;
            padding: 12px 16px !important;
            line-height: 1.4 !important;
            transition: all 0.2s ease !important;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.3) !important;
        }
        
        .wu-termius-input:focus {
            background-color: #2a2a2a !important;
            border-color: #00ff41 !important;
            box-shadow: 0 0 10px rgba(0, 255, 65, 0.3), inset 0 1px 3px rgba(0, 0, 0, 0.3) !important;
            outline: none !important;
        }
        
        .wu-termius-input::placeholder {
            color: #666 !important;
        }
        
        .wu-termius-input::selection {
            background-color: #00ff41 !important;
            color: #1e1e1e !important;
        }
        
        .wu-termius-input.error {
            border-color: #ff4444 !important;
            box-shadow: 0 0 10px rgba(255, 68, 68, 0.3) !important;
        }
        
        .wu-error-message {
            color: #ff4444;
            font-size: 13px;
            margin-top: 5px;
            display: none;
        }
        
        .wu-error-message.show {
            display: block;
        }
        
        .wu-status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 600;
            margin-left: 10px;
        }
        
        .wu-status-on {
            background: #d63638;
            color: #fff;
        }
        
        .wu-status-off {
            background: #00a32a;
            color: #fff;
        }
        
        .wu-info-box {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 15px;
            border-radius: 4px;
            margin-top: 15px;
        }
        
        .wu-info-box h3 {
            margin-top: 0;
            color: #155724;
        }
        
        .wu-info-box code {
            background: #fff;
            padding: 2px 6px;
            border-radius: 3px;
            color: #d63638;
            font-weight: bold;
        }
        ';
    }
    
    /**
     * 取得後台 JavaScript
     */
    private function get_admin_js() {
        return '
        jQuery(document).ready(function($) {
            var $slugInput = $("input[name=\\"' . $this->option_name . '[custom_slug]\\"]");
            var $errorMsg = $("<span class=\\"wu-error-message\\"></span>").insertAfter($slugInput);
            var reservedSlugs = wuHideLoginData.reservedSlugs;
            
            // Slug 即時驗證
            $slugInput.on("input", function() {
                var slug = $(this).val().toLowerCase().trim();
                var isValid = true;
                var errorText = "";
                
                // 檢查是否為空
                if (slug === "") {
                    isValid = false;
                    errorText = "❌ Slug 不能為空";
                }
                // 檢查是否包含特殊符號
                else if (!/^[a-z0-9-]+$/.test(slug)) {
                    isValid = false;
                    errorText = "❌ 只能使用小寫字母、數字和連字號(-)";
                }
                // 檢查是否為保留字
                else if (reservedSlugs.indexOf(slug) !== -1) {
                    isValid = false;
                    errorText = "❌ 此為 WordPress 保留字,無法使用";
                }
                
                if (!isValid) {
                    $(this).addClass("error");
                    $errorMsg.text(errorText).addClass("show");
                } else {
                    $(this).removeClass("error");
                    $errorMsg.removeClass("show");
                }
            });
            
            // 自動轉換為小寫
            $slugInput.on("blur", function() {
                $(this).val($(this).val().toLowerCase().trim());
            });
            
            // 表單提交驗證
            $("form").on("submit", function(e) {
                var slug = $slugInput.val().toLowerCase().trim();
                
                if (slug === "" || !/^[a-z0-9-]+$/.test(slug) || reservedSlugs.indexOf(slug) !== -1) {
                    e.preventDefault();
                    alert("請輸入有效的登入 Slug!");
                    $slugInput.focus();
                    return false;
                }
            });
            
            // 自定義重定向 URL 切換
            var $select = $("select[name=\\"' . $this->option_name . '[redirect_url]\\"]");
            var $toggle = $("#wu_custom_redirect_toggle");
            var $customInput = $("#wu_custom_redirect_url");
            
            $toggle.on("change", function() {
                if ($(this).is(":checked")) {
                    $customInput.show();
                    $select.hide();
                } else {
                    $customInput.hide();
                    $select.show();
                }
            });
            
            $customInput.on("blur", function() {
                var customUrl = $(this).val();
                if (customUrl) {
                    var exists = false;
                    $select.find("option").each(function() {
                        if ($(this).val() === customUrl) {
                            exists = true;
                            return false;
                        }
                    });
                    
                    if (!exists) {
                        $select.append($("<option>", {
                            value: customUrl,
                            text: "自定義網址:" + customUrl,
                            selected: true
                        }));
                    } else {
                        $select.val(customUrl);
                    }
                }
                
                $toggle.prop("checked", false);
                $customInput.hide();
                $select.show();
            });
        });
        ';
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
            'redirect_mode',
            '重定向模式',
            array($this, 'redirect_mode_callback'),
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
        // 驗證權限
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('您沒有足夠的權限訪問此頁面。', 'wumetax-toolkit'));
        }
        
        $status = !empty($this->options['enabled']) ? 'on' : 'off';
        
        ?>
        <div class="wrap">
            <h1>
                隱藏登入頁面設置
                <span class="wu-status-badge wu-status-<?php echo esc_attr($status); ?>">
                    <?php echo $status === 'on' ? '已啟用' : '已關閉'; ?>
                </span>
            </h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('wu_hide_login_page_group');
                do_settings_sections('wu-hide-login-page');
                submit_button();
                ?>
            </form>
            
            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2>功能說明</h2>
                <p><strong>此功能的作用:</strong></p>
                <ul>
                    <li>隱藏WordPress預設的後台登入位置</li>
                    <li>將登入頁面改為自定義網址(預設:/loginwu)</li>
                    <li>訪客無法使用 /wp-admin 或 /wp-login.php 登入</li>
                    <li>已登入的管理員不受影響</li>
                    <li>支援 404 偽裝模式,提升安全性</li>
                </ul>
                
                <p><strong>注意事項:</strong></p>
                <ul>
                    <li>啟用後,請記住新的登入網址</li>
                    <li>建議將新登入網址加入書籤</li>
                    <li>如果忘記新網址,可以通過資料庫或FTP停用此功能</li>
                    <li>避免使用 WordPress 保留字作為 Slug</li>
                    <li>變更 Slug 後會自動刷新重寫規則</li>
                </ul>
                
                <?php if (!empty($this->options['enabled'])): ?>
                <div class="wu-info-box">
                    <h3>功能已啟用</h3>
                    <p><strong>新的登入網址:</strong> <code><?php echo home_url('/' . $this->options['custom_slug'] . '/'); ?></code></p>
                    <p><strong>請將此網址加入書籤!</strong></p>
                    <p><strong>重定向模式:</strong> <?php echo $this->options['redirect_mode'] === '404' ? '404 偽裝模式' : '重定向模式'; ?></p>
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
        $enabled = !empty($this->options['enabled']) ? $this->options['enabled'] : false;
        ?>
        <label>
            <input type="checkbox" name="<?php echo $this->option_name; ?>[enabled]" value="1" <?php checked(1, $enabled); ?> />
            啟用隱藏登入頁面功能
        </label>
        <p class="description">啟用後,原本的 /wp-login.php 和 /wp-admin 將無法直接訪問</p>
        <?php
    }
    
    /**
     * 自定義網址回調
     */
    public function custom_slug_callback() {
        $custom_slug = !empty($this->options['custom_slug']) ? $this->options['custom_slug'] : 'loginwu';
        ?>
        <input type="text" 
               name="<?php echo $this->option_name; ?>[custom_slug]" 
               value="<?php echo esc_attr($custom_slug); ?>" 
               class="regular-text wu-termius-input" 
               placeholder="loginwu"
               pattern="[a-z0-9-]+"
               required />
        <p class="description">
            自定義登入頁面的網址結尾,預設為 "loginwu"。新的登入網址將是:<?php echo home_url('/'); ?><strong><?php echo esc_html($custom_slug); ?></strong>/
        </p>
        <p class="description" style="color: #d63638;">
            ⚠️ 只能使用小寫字母、數字和連字號(-),不可使用 WordPress 保留字
        </p>
        <?php
    }
    
    /**
     * 重定向模式回調
     */
    public function redirect_mode_callback() {
        $redirect_mode = !empty($this->options['redirect_mode']) ? $this->options['redirect_mode'] : 'redirect';
        ?>
        <p>
            <label>
                <input type="radio" 
                       name="<?php echo $this->option_name; ?>[redirect_mode]" 
                       value="redirect" 
                       <?php checked($redirect_mode, 'redirect'); ?>>
                <strong>重定向模式</strong><br>
                <span style="color: #666; font-size: 13px;">
                    將訪客重定向到指定頁面(下方設定)
                </span>
            </label>
        </p>
        <p>
            <label>
                <input type="radio" 
                       name="<?php echo $this->option_name; ?>[redirect_mode]" 
                       value="404" 
                       <?php checked($redirect_mode, '404'); ?>>
                <strong>404 偽裝模式(推薦)</strong><br>
                <span style="color: #666; font-size: 13px;">
                    顯示 404 錯誤頁面,讓掃描工具誤以為路徑不存在,安全性更高
                </span>
            </label>
        </p>
        <?php
    }
    
    /**
     * 重定向網址回調
     */
    public function redirect_url_callback() {
        $redirect_url = !empty($this->options['redirect_url']) ? $this->options['redirect_url'] : home_url();
        $redirect_mode = !empty($this->options['redirect_mode']) ? $this->options['redirect_mode'] : 'redirect';
        
        // 獲取所有已發布的頁面
        $pages = get_pages(array(
            'post_status' => 'publish',
            'sort_column' => 'menu_order, post_title'
        ));
        
        ?>
        <div id="redirect-url-wrapper" style="<?php echo $redirect_mode === '404' ? 'opacity: 0.5; pointer-events: none;' : ''; ?>">
            <select name="<?php echo $this->option_name; ?>[redirect_url]" class="regular-text">
                <option value="<?php echo esc_url(home_url()); ?>" <?php selected($redirect_url, home_url()); ?>>
                    首頁
                </option>
                <?php foreach ($pages as $page): 
                    $page_url = get_permalink($page->ID);
                ?>
                <option value="<?php echo esc_url($page_url); ?>" <?php selected($redirect_url, $page_url); ?>>
                    <?php echo esc_html($page->post_title); ?>
                </option>
                <?php endforeach; ?>
                
                <?php 
                // 如果當前設置的URL不在上述選項中,添加自定義選項
                $found_match = false;
                if ($redirect_url === home_url()) {
                    $found_match = true;
                } else {
                    foreach ($pages as $page) {
                        if ($redirect_url === get_permalink($page->ID)) {
                            $found_match = true;
                            break;
                        }
                    }
                }
                
                if (!$found_match && !empty($redirect_url)): ?>
                <option value="<?php echo esc_url($redirect_url); ?>" selected>
                    自定義網址:<?php echo esc_url($redirect_url); ?>
                </option>
                <?php endif; ?>
            </select>
            
            <p class="description">選擇當訪客嘗試訪問被隱藏的登入頁面時要重定向的頁面。</p>
            
            <div style="margin-top: 10px;">
                <label>
                    <input type="checkbox" id="wu_custom_redirect_toggle" /> 
                    使用自定義網址
                </label>
                <br>
                <input type="url" id="wu_custom_redirect_url" placeholder="輸入自定義網址" class="regular-text" style="margin-top: 5px; display: none;" />
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // 監聽重定向模式變更
            $('input[name="<?php echo $this->option_name; ?>[redirect_mode]"]').on('change', function() {
                var mode = $(this).val();
                var $wrapper = $('#redirect-url-wrapper');
                
                if (mode === '404') {
                    $wrapper.css({'opacity': '0.5', 'pointer-events': 'none'});
                } else {
                    $wrapper.css({'opacity': '1', 'pointer-events': 'auto'});
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * 清理選項
     */
    public function sanitize_options($input) {
        $old_options = $this->options;
        $sanitized = array();
        
        $sanitized['enabled'] = !empty($input['enabled']) ? true : false;
        
        // 清理並驗證 custom_slug
        $custom_slug = !empty($input['custom_slug']) ? sanitize_text_field($input['custom_slug']) : 'loginwu';
        $custom_slug = strtolower(trim($custom_slug));
        
        // 驗證 slug 格式
        if (!preg_match('/^[a-z0-9-]+$/', $custom_slug)) {
            add_settings_error(
                $this->option_name,
                'invalid_slug_format',
                'Slug 只能包含小寫字母、數字和連字號',
                'error'
            );
            $custom_slug = 'loginwu';
        }
        
        // 驗證是否為保留字
        if (in_array($custom_slug, $this->reserved_slugs)) {
            add_settings_error(
                $this->option_name,
                'reserved_slug',
                '此為 WordPress 保留字,請使用其他 Slug',
                'error'
            );
            $custom_slug = 'loginwu';
        }
        
        $sanitized['custom_slug'] = $custom_slug;
        $sanitized['redirect_mode'] = !empty($input['redirect_mode']) && in_array($input['redirect_mode'], array('redirect', '404')) 
            ? $input['redirect_mode'] 
            : 'redirect';
        $sanitized['redirect_url'] = !empty($input['redirect_url']) ? esc_url_raw($input['redirect_url']) : home_url();
        $sanitized['last_slug'] = $old_options['custom_slug'];
        
        // 檢查 slug 是否變更,如果變更則刷新重寫規則
        if ($old_options['custom_slug'] !== $sanitized['custom_slug']) {
            // 設定標記,稍後刷新重寫規則
            set_transient('wu_hide_login_flush_rewrite', true, 60);
        }
        
        return $sanitized;
    }
    
    /**
     * 初始化隱藏登入功能
     */
    public function init_hide_login() {
        if (empty($this->options['enabled'])) {
            return;
        }
        
        // 添加自定義重寫規則
        add_rewrite_rule(
            '^' . $this->options['custom_slug'] . '/?$',
            'index.php?wu_custom_login=1',
            'top'
        );
        
        add_rewrite_rule(
            '^' . $this->options['custom_slug'] . '/(.*)$',
            'index.php?wu_custom_login=1&wu_login_args=$matches[1]',
            'top'
        );
        
        // 添加查詢變數
        add_filter('query_vars', function($vars) {
            $vars[] = 'wu_custom_login';
            $vars[] = 'wu_login_args';
            return $vars;
        });
        
        // 如果需要刷新重寫規則
        if (get_transient('wu_hide_login_flush_rewrite')) {
            flush_rewrite_rules();
            delete_transient('wu_hide_login_flush_rewrite');
        }
    }
    
    /**
     * 重定向登入頁面
     */
    public function redirect_login_pages() {
        if (empty($this->options['enabled'])) {
            return;
        }
        
        // 如果用戶已登入,不執行重定向
        if (is_user_logged_in()) {
            return;
        }
        
        // 排除特殊請求
        if ($this->is_excluded_request()) {
            return;
        }
        
        // 檢查是否與404重定向功能衝突
        if ($this->is_404_redirector_handling()) {
            return;
        }
        
        $current_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        
        // 檢查是否訪問被隱藏的登入頁面
        if (strpos($current_url, '/wp-login.php') !== false || 
            (strpos($current_url, '/wp-admin') !== false && strpos($current_url, '/admin-ajax.php') === false)) {
            
            // 允許訪問自定義登入頁面
            if (strpos($current_url, '/' . $this->options['custom_slug']) !== false) {
                return;
            }
            
            // 根據模式執行不同動作
            if ($this->options['redirect_mode'] === '404') {
                // 404 偽裝模式
                $this->show_404_page();
            } else {
                // 重定向模式
                wp_redirect($this->options['redirect_url'], 301);
                exit;
            }
        }
    }
    
    /**
     * 檢查是否為排除的請求
     */
    private function is_excluded_request() {
        // 排除 AJAX
        if ((function_exists('wp_doing_ajax') && wp_doing_ajax()) || 
            (defined('DOING_AJAX') && DOING_AJAX)) {
            return true;
        }
        
        // 排除 REST API
        if (function_exists('wp_is_json_request') && wp_is_json_request()) {
            return true;
        }
        
        // 排除 XML-RPC
        if (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) {
            return true;
        }
        
        // 排除 Cron
        if (defined('DOING_CRON') && DOING_CRON) {
            return true;
        }
        
        // 檢查請求 URI
        $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $excluded_paths = array(
            'admin-ajax.php',
            'wp-cron.php',
            'async-upload.php'
        );
        
        foreach ($excluded_paths as $path) {
            if (strpos($request_uri, $path) !== false) {
                return true;
            }
        }
        
        // CORS 預檢請求
        if (isset($_SERVER['REQUEST_METHOD']) && 
            strtoupper($_SERVER['REQUEST_METHOD']) === 'OPTIONS') {
            return true;
        }
        
        return false;
    }
    
    /**
     * 檢查是否404重定向功能正在處理
     */
    private function is_404_redirector_handling() {
        $redirect_404_enabled = get_option('wu_enable_404_redirect', false);
        
        if (!$redirect_404_enabled) {
            return false;
        }
        
        if (is_404()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * 顯示 404 頁面
     */
    private function show_404_page() {
        global $wp_query;
        
        $wp_query->set_404();
        status_header(404);
        nocache_headers();
        
        // 載入主題的 404 範本
        if (file_exists(get_template_directory() . '/404.php')) {
            include(get_template_directory() . '/404.php');
        } else {
            // 簡單的 404 頁面
            ?>
            <!DOCTYPE html>
            <html <?php language_attributes(); ?>>
            <head>
                <meta charset="<?php bloginfo('charset'); ?>">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <title>404 Not Found</title>
                <style>
                    body {
                        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        height: 100vh;
                        margin: 0;
                        background: #f5f5f5;
                        color: #333;
                    }
                    .error-container {
                        text-align: center;
                    }
                    h1 {
                        font-size: 6em;
                        margin: 0;
                        color: #d63638;
                    }
                    p {
                        font-size: 1.2em;
                        color: #666;
                    }
                </style>
            </head>
            <body>
                <div class="error-container">
                    <h1>404</h1>
                    <p>找不到頁面</p>
                    <p><a href="<?php echo home_url(); ?>">返回首頁</a></p>
                </div>
            </body>
            </html>
            <?php
        }
        exit;
    }
    
    /**
     * 處理自定義登入頁面
     */
    public function handle_custom_login() {
        if (empty($this->options['enabled'])) {
            return;
        }
        
        // 檢查是否為自定義登入頁面
        $custom_login = get_query_var('wu_custom_login');
        
        if ($custom_login !== '1') {
            return;
        }
        
        // 添加快取排除標頭
        nocache_headers();
        header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        
        // 完整的變數初始化
        global $error, $interim_login, $action, $user_login, $user_email, 
               $redirect_to, $rememberme, $user_id, $errors;
        
        $error = '';
        $errors = new WP_Error();
        $interim_login = isset($_REQUEST['interim-login']);
        $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'login';
        $user_login = isset($_POST['log']) ? wp_unslash($_POST['log']) : '';
        $user_email = isset($_POST['user_email']) ? wp_unslash($_POST['user_email']) : '';
        $redirect_to = isset($_REQUEST['redirect_to']) ? $_REQUEST['redirect_to'] : admin_url();
        $rememberme = !empty($_POST['rememberme']);
        $user_id = 0;
        
        // 載入 WordPress 登入頁面
        require_once(ABSPATH . 'wp-login.php');
        exit;
    }
    
    /**
     * 修改登入URL
     */
    public function modify_login_url($url, $path = '', $scheme = null, $blog_id = null) {
        if (empty($this->options['enabled'])) {
            return $url;
        }
        
        if ($path === 'wp-login.php' || strpos($url, 'wp-login.php') !== false) {
            return home_url('/' . $this->options['custom_slug'] . '/');
        }
        
        return $url;
    }
    
    /**
     * 修改重定向URL
     */
    public function modify_redirect_url($location, $status) {
        if (empty($this->options['enabled'])) {
            return $location;
        }
        
        if (strpos($location, 'wp-login.php') !== false) {
            $location = str_replace('wp-login.php', $this->options['custom_slug'], $location);
        }
        
        return $location;
    }
}

// 初始化類別
new WU_Hide_Login_Page();
