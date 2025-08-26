<?php
/**
 * 404 錯誤重新導向模組
 * 功能：將 404 錯誤透明地重新導向到網站主頁
 */

if (!defined('ABSPATH')) exit;

class WU_404_Redirector {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'), 20);
        add_action('admin_init', array($this, 'admin_init'));
        
        // 如果啟用了 404 重新導向，則執行相關動作
        if (get_option('wu_enable_404_redirect', false)) {
            $this->enable_404_redirect();
        }
    }
    
    /**
     * 添加管理頁面
     */
    public function add_admin_menu() {
        add_submenu_page(
            'wumetax-toolkit',
            '404重新導向',
            '404重新導向',
            'manage_options',
            'wu-404-redirector',
            array($this, 'admin_page')
        );
    }
    
    /**
     * 初始化設定
     */
    public function admin_init() {
        register_setting('wu_404_redirect_settings', 'wu_enable_404_redirect');
        register_setting('wu_404_redirect_settings', 'wu_404_redirect_type');
        register_setting('wu_404_redirect_settings', 'wu_404_custom_url');
        
        add_settings_section(
            'wu_404_redirect_section',
            '404 錯誤重新導向設定',
            array($this, 'settings_section_callback'),
            'wu_404_redirect_settings'
        );
        
        add_settings_field(
            'wu_enable_404_redirect',
            '啟用 404 重新導向',
            array($this, 'enable_redirect_callback'),
            'wu_404_redirect_settings',
            'wu_404_redirect_section'
        );
        
        add_settings_field(
            'wu_404_redirect_type',
            '重新導向類型',
            array($this, 'redirect_type_callback'),
            'wu_404_redirect_settings',
            'wu_404_redirect_section'
        );
        
        add_settings_field(
            'wu_404_custom_url',
            '自訂 URL',
            array($this, 'custom_url_callback'),
            'wu_404_redirect_settings',
            'wu_404_redirect_section'
        );
    }
    
    /**
     * 設定區域說明
     */
    public function settings_section_callback() {
        echo '<p>404 錯誤重新導向功能可以自動將訪客從不存在的頁面重新導向到指定頁面，改善用戶體驗並減少跳出率。</p>';
        echo '<p><strong>建議：</strong>啟用此功能可以避免訪客看到令人困惑的 404 錯誤頁面。</p>';
    }
    
    /**
     * 啟用重新導向選項回調
     */
    public function enable_redirect_callback() {
        $value = get_option('wu_enable_404_redirect', false);
        echo '<input type="checkbox" id="wu_enable_404_redirect" name="wu_enable_404_redirect" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_enable_404_redirect">啟用 404 錯誤自動重新導向</label>';
        echo '<p class="description">當檢測到 404 錯誤時，自動將訪客重新導向到指定頁面。</p>';
    }
    
    /**
     * 重新導向類型選項回調
     */
    public function redirect_type_callback() {
        $value = get_option('wu_404_redirect_type', 'homepage');
        echo '<select id="wu_404_redirect_type" name="wu_404_redirect_type">';
        echo '<option value="homepage"' . selected('homepage', $value, false) . '>網站首頁</option>';
        echo '<option value="custom"' . selected('custom', $value, false) . '>自訂 URL</option>';
        echo '</select>';
        echo '<p class="description">選擇 404 錯誤的重新導向目標。</p>';
    }
    
    /**
     * 自訂 URL 選項回調
     */
    public function custom_url_callback() {
        $value = get_option('wu_404_custom_url', '');
        echo '<input type="url" id="wu_404_custom_url" name="wu_404_custom_url" value="' . esc_attr($value) . '" class="regular-text" placeholder="https://example.com/page" />';
        echo '<p class="description">當選擇自訂 URL 時，請輸入完整的 URL 地址（包含 http:// 或 https://）。</p>';
    }
    
    /**
     * 管理頁面
     */
    public function admin_page() {
        if (isset($_POST['submit'])) {
            check_admin_referer('wu_404_redirect_settings-options');
            
            // 處理表單提交
            update_option('wu_enable_404_redirect', isset($_POST['wu_enable_404_redirect']) ? 1 : 0);
            update_option('wu_404_redirect_type', sanitize_text_field($_POST['wu_404_redirect_type']));
            update_option('wu_404_custom_url', esc_url_raw($_POST['wu_404_custom_url']));
            
            echo '<div class="notice notice-success"><p>設定已儲存！</p></div>';
        }
        
        $redirect_enabled = get_option('wu_enable_404_redirect', false);
        $redirect_type = get_option('wu_404_redirect_type', 'homepage');
        $custom_url = get_option('wu_404_custom_url', '');
        
        // 獲取重新導向目標 URL
        $redirect_url = '';
        if ($redirect_enabled) {
            if ($redirect_type === 'homepage') {
                $redirect_url = home_url();
            } elseif ($redirect_type === 'custom' && !empty($custom_url)) {
                $redirect_url = $custom_url;
            }
        }
        ?>
        <div class="wrap">
            <h1>404 錯誤重新導向設定</h1>
            
            <div class="card">
                <h2>當前狀態</h2>
                <p><strong>404 重新導向：</strong> 
                    <span class="<?php echo $redirect_enabled ? 'wu-status-enabled' : 'wu-status-disabled'; ?>">
                        <?php echo $redirect_enabled ? '已啟用' : '已禁用'; ?>
                    </span>
                </p>
                <?php if ($redirect_enabled && !empty($redirect_url)): ?>
                <p><strong>重新導向目標：</strong> <a href="<?php echo esc_url($redirect_url); ?>" target="_blank"><?php echo esc_html($redirect_url); ?></a></p>
                <?php endif; ?>
            </div>
            
            <form method="post" action="">
                <?php
                settings_fields('wu_404_redirect_settings');
                do_settings_sections('wu_404_redirect_settings');
                wp_nonce_field('wu_404_redirect_settings-options');
                submit_button();
                ?>
            </form>
            
            <div class="card">
                <h2>功能說明</h2>
                <h3>什麼是 404 錯誤？</h3>
                <ul>
                    <li>404 錯誤表示請求的頁面或資源不存在</li>
                    <li>常見原因包括：連結錯誤、頁面已刪除、URL 拼寫錯誤</li>
                    <li>預設情況下會顯示 404 錯誤頁面</li>
                </ul>
                
                <h3>為什麼要重新導向？</h3>
                <ul>
                    <li><strong>改善用戶體驗：</strong>避免訪客看到令人困惑的錯誤頁面</li>
                    <li><strong>減少跳出率：</strong>將訪客引導到有用的內容</li>
                    <li><strong>SEO 優化：</strong>減少 404 錯誤對搜尋引擎排名的負面影響</li>
                    <li><strong>保持流量：</strong>將迷路的訪客重新導向到主要頁面</li>
                </ul>
                
                <h3>重新導向選項</h3>
                <h4>網站首頁</h4>
                <ul>
                    <li>將所有 404 錯誤重新導向到網站首頁</li>
                    <li>適合大多數情況，確保訪客不會迷路</li>
                    <li>首頁通常包含網站的主要導航和內容</li>
                </ul>
                
                <h4>自訂 URL</h4>
                <ul>
                    <li>重新導向到您指定的任何頁面</li>
                    <li>可以是特殊的 404 頁面、聯繫頁面或其他重要頁面</li>
                    <li>提供更多的靈活性和控制</li>
                </ul>
                
                <h3>技術實現</h3>
                <ul>
                    <li>使用 WordPress <code>template_redirect</code> 鉤子檢測 404 錯誤</li>
                    <li>執行 301 永久重新導向，對 SEO 友好</li>
                    <li>透明重新導向，訪客不會察覺到錯誤</li>
                    <li>不會影響正常頁面的載入和功能</li>
                </ul>
                
                <h3>注意事項</h3>
                <ul>
                    <li>重新導向會改變 URL，可能影響某些分析工具的統計</li>
                    <li>建議定期檢查 404 錯誤日誌，修復常見的連結問題</li>
                    <li>自訂 URL 必須是有效的網址，否則可能造成無限重新導向</li>
                    <li>對於重要的已刪除頁面，建議使用更具體的重新導向規則</li>
                </ul>
            </div>
        </div>
        
        <style>
        .wu-status-enabled { color: #00a32a; font-weight: bold; }
        .wu-status-disabled { color: #d63638; font-weight: bold; }
        .card { background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin: 20px 0; }
        .card h2 { margin-top: 0; }
        .card h3, .card h4 { color: #23282d; }
        .card ul { margin-left: 20px; }
        </style>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var redirectType = document.getElementById('wu_404_redirect_type');
            var customUrlField = document.getElementById('wu_404_custom_url').parentNode;
            
            function toggleCustomUrl() {
                if (redirectType.value === 'custom') {
                    customUrlField.style.display = '';
                } else {
                    customUrlField.style.display = 'none';
                }
            }
            
            redirectType.addEventListener('change', toggleCustomUrl);
            toggleCustomUrl(); // 初始化顯示狀態
        });
        </script>
        <?php
    }
    
    /**
     * 啟用 404 重新導向
     */
    private function enable_404_redirect() {
        add_action('template_redirect', array($this, 'handle_404_redirect'));
    }
    
    /**
     * 處理 404 重新導向
     */
    public function handle_404_redirect() {
        if (is_404()) {
            // 檢查是否與隱藏登入頁面功能衝突
            if ($this->is_hide_login_conflict()) {
                return; // 不處理隱藏登入頁面相關的404錯誤
            }
            
            $redirect_type = get_option('wu_404_redirect_type', 'homepage');
            $redirect_url = '';
            
            if ($redirect_type === 'homepage') {
                $redirect_url = home_url();
            } elseif ($redirect_type === 'custom') {
                $custom_url = get_option('wu_404_custom_url', '');
                if (!empty($custom_url) && filter_var($custom_url, FILTER_VALIDATE_URL)) {
                    $redirect_url = $custom_url;
                } else {
                    // 如果自訂 URL 無效，回退到首頁
                    $redirect_url = home_url();
                }
            }
            
            if (!empty($redirect_url)) {
                // 記錄 404 錯誤（可選）
                $this->log_404_error();
                
                // 執行 301 重新導向
                wp_redirect($redirect_url, 301);
                exit;
            }
        }
    }
    
    /**
     * 檢查是否與隱藏登入頁面功能衝突
     */
    private function is_hide_login_conflict() {
        // 獲取隱藏登入頁面的設定
        $hide_login_options = get_option('wu_hide_login_page_options', array());
        
        // 如果隱藏登入頁面功能未啟用，沒有衝突
        if (!isset($hide_login_options['enabled']) || !$hide_login_options['enabled']) {
            return false;
        }
        
        $current_url = $_SERVER['REQUEST_URI'];
        $custom_slug = isset($hide_login_options['custom_slug']) ? $hide_login_options['custom_slug'] : 'loginwu';
        
        // 檢查是否是與登入相關的URL
        $login_related_urls = array(
            '/wp-login.php',
            '/wp-admin',
            '/' . $custom_slug,
            '/login',
            '/admin'
        );
        
        foreach ($login_related_urls as $login_url) {
            if (strpos($current_url, $login_url) !== false) {
                return true; // 有衝突，讓隱藏登入頁面功能處理
            }
        }
        
        return false; // 沒有衝突
    }
    
    /**
     * 記錄 404 錯誤
     */
    private function log_404_error() {
        // 獲取當前請求的 URL
        $requested_url = $_SERVER['REQUEST_URI'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        
        // 儲存到選項中（最多保留 100 條記錄）
        $log_entries = get_option('wu_404_log', array());
        
        $new_entry = array(
            'url' => $requested_url,
            'time' => current_time('mysql'),
            'ip' => $ip_address,
            'user_agent' => $user_agent,
            'referer' => $referer
        );
        
        array_unshift($log_entries, $new_entry);
        
        // 限制日誌條目數量
        if (count($log_entries) > 100) {
            $log_entries = array_slice($log_entries, 0, 100);
        }
        
        update_option('wu_404_log', $log_entries);
    }
}

// 初始化模組
new WU_404_Redirector();