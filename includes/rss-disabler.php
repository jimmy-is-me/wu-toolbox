<?php
/**
 * RSS 禁用模組
 * 功能：停用 WordPress RSS 來源功能
 */

if (!defined('ABSPATH')) exit;

class WU_RSS_Disabler {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        
        // 如果啟用了 RSS 禁用功能，則執行相關動作
        if (get_option('wu_disable_rss_feeds', false)) {
            $this->disable_rss_feeds();
        }
    }
    
    /**
     * 添加管理頁面
     */
    public function add_admin_menu() {
        add_submenu_page(
            'wumetax-toolkit',
            'RSS 禁用管理',
            'RSS 禁用管理',
            'manage_options',
            'wu-rss-disabler',
            array($this, 'admin_page')
        );
    }
    
    /**
     * 初始化設定
     */
    public function admin_init() {
        register_setting('wu_rss_settings', 'wu_disable_rss_feeds');
        register_setting('wu_rss_settings', 'wu_rss_redirect_type');
        register_setting('wu_rss_settings', 'wu_rss_custom_message');
        
        add_settings_section(
            'wu_rss_section',
            'RSS 禁用設定',
            array($this, 'settings_section_callback'),
            'wu_rss_settings'
        );
        
        add_settings_field(
            'wu_disable_rss_feeds',
            '禁用 RSS 來源',
            array($this, 'disable_rss_callback'),
            'wu_rss_settings',
            'wu_rss_section'
        );
        
        add_settings_field(
            'wu_rss_redirect_type',
            'RSS 請求處理方式',
            array($this, 'redirect_type_callback'),
            'wu_rss_settings',
            'wu_rss_section'
        );
        
        add_settings_field(
            'wu_rss_custom_message',
            '自訂訊息',
            array($this, 'custom_message_callback'),
            'wu_rss_settings',
            'wu_rss_section'
        );
    }
    
    /**
     * 設定區域說明
     */
    public function settings_section_callback() {
        echo '<p>RSS 禁用功能可以完全停用 WordPress 的 RSS 來源，防止內容被未授權抓取和聚合。</p>';
        echo '<p><strong>建議：</strong>如果您不希望內容被 RSS 閱讀器或聚合網站抓取，可以啟用此功能。</p>';
    }
    
    /**
     * 禁用 RSS 選項回調
     */
    public function disable_rss_callback() {
        $value = get_option('wu_disable_rss_feeds', false);
        echo '<input type="checkbox" id="wu_disable_rss_feeds" name="wu_disable_rss_feeds" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_disable_rss_feeds">禁用所有 RSS 來源</label>';
        echo '<p class="description">勾選此選項將禁用網站的所有 RSS 來源，包括文章、評論、分類等。</p>';
    }
    
    /**
     * 重新導向類型選項回調
     */
    public function redirect_type_callback() {
        $value = get_option('wu_rss_redirect_type', '403');
        echo '<select id="wu_rss_redirect_type" name="wu_rss_redirect_type">';
        echo '<option value="403"' . selected('403', $value, false) . '>返回 403 禁止存取錯誤</option>';
        echo '<option value="404"' . selected('404', $value, false) . '>返回 404 找不到頁面錯誤</option>';
        echo '<option value="homepage"' . selected('homepage', $value, false) . '>重新導向到首頁</option>';
        echo '<option value="message"' . selected('message', $value, false) . '>顯示自訂訊息</option>';
        echo '</select>';
        echo '<p class="description">選擇當有人嘗試存取 RSS 來源時的處理方式。</p>';
    }
    
    /**
     * 自訂訊息選項回調
     */
    public function custom_message_callback() {
        $value = get_option('wu_rss_custom_message', 'RSS feeds are disabled on this site.');
        echo '<textarea id="wu_rss_custom_message" name="wu_rss_custom_message" rows="3" cols="50" class="large-text">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">當選擇「顯示自訂訊息」時顯示的內容。支援 HTML 標籤。</p>';
    }
    
    /**
     * 管理頁面
     */
    public function admin_page() {
        if (isset($_POST['submit'])) {
            check_admin_referer('wu_rss_settings-options');
            
            // 處理表單提交
            update_option('wu_disable_rss_feeds', isset($_POST['wu_disable_rss_feeds']) ? 1 : 0);
            update_option('wu_rss_redirect_type', sanitize_text_field($_POST['wu_rss_redirect_type']));
            update_option('wu_rss_custom_message', wp_kses_post($_POST['wu_rss_custom_message']));
            
            echo '<div class="notice notice-success"><p>設定已儲存！</p></div>';
        }
        
        $rss_disabled = get_option('wu_disable_rss_feeds', false);
        $redirect_type = get_option('wu_rss_redirect_type', '403');
        
        // 獲取 RSS 來源 URL 列表
        $rss_urls = $this->get_rss_urls();
        ?>
        <div class="wrap">
            <h1>RSS 禁用管理設定</h1>
            
            <div class="card">
                <h2>當前狀態</h2>
                <p><strong>RSS 來源狀態：</strong> 
                    <span class="<?php echo $rss_disabled ? 'wu-status-disabled' : 'wu-status-enabled'; ?>">
                        <?php echo $rss_disabled ? '已禁用' : '已啟用'; ?>
                    </span>
                </p>
                <p><strong>處理方式：</strong> 
                    <?php
                    $type_labels = array(
                        '403' => '返回 403 錯誤',
                        '404' => '返回 404 錯誤',
                        'homepage' => '重新導向到首頁',
                        'message' => '顯示自訂訊息'
                    );
                    echo $type_labels[$redirect_type] ?? $redirect_type;
                    ?>
                </p>
            </div>
            
            <form method="post" action="">
                <?php
                settings_fields('wu_rss_settings');
                do_settings_sections('wu_rss_settings');
                wp_nonce_field('wu_rss_settings-options');
                submit_button();
                ?>
            </form>
            
            <div class="card">
                <h2>RSS 來源 URL</h2>
                <p>以下是您網站的主要 RSS 來源 URL：</p>
                <ul>
                    <?php foreach ($rss_urls as $label => $url): ?>
                    <li>
                        <strong><?php echo esc_html($label); ?>：</strong>
                        <a href="<?php echo esc_url($url); ?>" target="_blank" <?php echo $rss_disabled ? 'style="text-decoration: line-through; color: #999;"' : ''; ?>>
                            <?php echo esc_html($url); ?>
                        </a>
                        <?php if ($rss_disabled): ?>
                        <span style="color: #d63638; font-weight: bold;">（已禁用）</span>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="card">
                <h2>功能說明</h2>
                <h3>什麼是 RSS 來源？</h3>
                <ul>
                    <li>RSS（Really Simple Syndication）是一種內容聚合格式</li>
                    <li>允許用戶使用 RSS 閱讀器訂閱網站內容</li>
                    <li>WordPress 預設提供多種 RSS 來源（文章、評論、分類等）</li>
                </ul>
                
                <h3>為什麼要禁用 RSS？</h3>
                <ul>
                    <li><strong>防止內容抓取：</strong>阻止其他網站未授權抓取您的內容</li>
                    <li><strong>保護版權：</strong>減少內容被盜用的風險</li>
                    <li><strong>控制流量：</strong>確保用戶直接造訪您的網站</li>
                    <li><strong>減少伺服器負載：</strong>RSS 請求會消耗伺服器資源</li>
                    <li><strong>SEO 考量：</strong>避免內容在其他地方出現造成重複內容問題</li>
                </ul>
                
                <h3>處理方式說明</h3>
                <h4>403 禁止存取錯誤</h4>
                <ul>
                    <li>返回 HTTP 403 狀態碼，明確表示訪問被禁止</li>
                    <li>對搜尋引擎和 RSS 閱讀器最明確的信號</li>
                    <li>建議的預設選項</li>
                </ul>
                
                <h4>404 找不到頁面錯誤</h4>
                <ul>
                    <li>返回 HTTP 404 狀態碼，假裝 RSS 來源不存在</li>
                    <li>可能讓某些爬蟲認為這是暫時性問題</li>
                    <li>較不明確的禁用信號</li>
                </ul>
                
                <h4>重新導向到首頁</h4>
                <ul>
                    <li>將 RSS 請求重新導向到網站首頁</li>
                    <li>保持用戶在您的網站上</li>
                    <li>可能對某些 RSS 閱讀器造成混淆</li>
                </ul>
                
                <h4>顯示自訂訊息</h4>
                <ul>
                    <li>顯示您自定義的訊息頁面</li>
                    <li>可以解釋為什麼禁用 RSS 或提供替代方案</li>
                    <li>提供最好的用戶體驗</li>
                </ul>
                
                <h3>技術實現</h3>
                <ul>
                    <li>攔截所有主要的 RSS 來源請求</li>
                    <li>移除 HTML head 中的 RSS 連結</li>
                    <li>禁用 WordPress 預設的 RSS 路由</li>
                    <li>不會影響網站的其他功能</li>
                </ul>
                
                <h3>注意事項</h3>
                <ul>
                    <li>禁用 RSS 可能會影響某些外掛程式的功能</li>
                    <li>如果您使用社交媒體自動發佈工具，可能需要重新配置</li>
                    <li>某些 SEO 工具可能依賴 RSS 來源進行分析</li>
                    <li>可以隨時重新啟用，不會遺失任何資料</li>
                </ul>
            </div>
        </div>
        
        <style>
        .wu-status-enabled { color: #d63638; font-weight: bold; }
        .wu-status-disabled { color: #00a32a; font-weight: bold; }
        .card { background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin: 20px 0; }
        .card h2 { margin-top: 0; }
        .card h3, .card h4 { color: #23282d; }
        .card ul { margin-left: 20px; }
        .card li { margin-bottom: 5px; }
        </style>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var redirectType = document.getElementById('wu_rss_redirect_type');
            var customMessageField = document.getElementById('wu_rss_custom_message').parentNode;
            
            function toggleCustomMessage() {
                if (redirectType.value === 'message') {
                    customMessageField.style.display = '';
                } else {
                    customMessageField.style.display = 'none';
                }
            }
            
            redirectType.addEventListener('change', toggleCustomMessage);
            toggleCustomMessage(); // 初始化顯示狀態
        });
        </script>
        <?php
    }
    
    /**
     * 獲取 RSS 來源 URL 列表
     */
    private function get_rss_urls() {
        return array(
            '主要文章 RSS' => get_feed_link(),
            '評論 RSS' => get_feed_link('comments_rss2'),
            'Atom 來源' => get_feed_link('atom'),
            'RDF 來源' => get_feed_link('rdf'),
            'RSS 0.92' => get_feed_link('rss'),
        );
    }
    
    /**
     * 禁用 RSS 來源
     */
    private function disable_rss_feeds() {
        // 移除 RSS 來源連結
        remove_action('wp_head', 'feed_links_extra', 3);
        remove_action('wp_head', 'feed_links', 2);
        
        // 禁用所有 RSS 來源
        add_action('do_feed', array($this, 'handle_feed_request'), 1);
        add_action('do_feed_rdf', array($this, 'handle_feed_request'), 1);
        add_action('do_feed_rss', array($this, 'handle_feed_request'), 1);
        add_action('do_feed_rss2', array($this, 'handle_feed_request'), 1);
        add_action('do_feed_atom', array($this, 'handle_feed_request'), 1);
        add_action('do_feed_rss2_comments', array($this, 'handle_feed_request'), 1);
        add_action('do_feed_atom_comments', array($this, 'handle_feed_request'), 1);
        
        // 移除 RSS 重寫規則
        add_filter('rewrite_rules_array', array($this, 'remove_feed_rewrite_rules'));
    }
    
    /**
     * 處理 RSS 來源請求
     */
    public function handle_feed_request() {
        $redirect_type = get_option('wu_rss_redirect_type', '403');
        
        switch ($redirect_type) {
            case '403':
                $this->send_403_error();
                break;
                
            case '404':
                $this->send_404_error();
                break;
                
            case 'homepage':
                wp_redirect(home_url(), 301);
                exit;
                
            case 'message':
                $this->show_custom_message();
                break;
                
            default:
                $this->send_403_error();
        }
    }
    
    /**
     * 發送 403 錯誤
     */
    private function send_403_error() {
        status_header(403);
        nocache_headers();
        
        echo '<!DOCTYPE html>
<html>
<head>
    <title>403 Forbidden</title>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; text-align: center; margin-top: 100px; }
        h1 { color: #d63638; }
        p { color: #666; }
    </style>
</head>
<body>
    <h1>403 - Forbidden</h1>
    <p>RSS feeds are disabled on this site.</p>
    <p><a href="' . home_url() . '">Return to homepage</a></p>
</body>
</html>';
        exit;
    }
    
    /**
     * 發送 404 錯誤
     */
    private function send_404_error() {
        global $wp_query;
        $wp_query->set_404();
        status_header(404);
        nocache_headers();
        
        // 載入 404 模板
        include(get_query_template('404'));
        exit;
    }
    
    /**
     * 顯示自訂訊息
     */
    private function show_custom_message() {
        $custom_message = get_option('wu_rss_custom_message', 'RSS feeds are disabled on this site.');
        
        status_header(200);
        nocache_headers();
        header('Content-Type: text/html; charset=utf-8');
        
        echo '<!DOCTYPE html>
<html>
<head>
    <title>RSS Feeds Disabled - ' . get_bloginfo('name') . '</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
            line-height: 1.6; 
            margin: 0; 
            padding: 40px 20px; 
            background-color: #f1f1f1; 
        }
        .container { 
            max-width: 600px; 
            margin: 0 auto; 
            background: white; 
            padding: 40px; 
            border-radius: 8px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
        }
        h1 { 
            color: #23282d; 
            margin-bottom: 20px; 
            text-align: center;
        }
        .message { 
            color: #555; 
            margin-bottom: 30px; 
        }
        .back-link { 
            text-align: center; 
        }
        .back-link a { 
            color: #0073aa; 
            text-decoration: none; 
            padding: 10px 20px; 
            border: 1px solid #0073aa; 
            border-radius: 4px; 
            display: inline-block; 
        }
        .back-link a:hover { 
            background-color: #0073aa; 
            color: white; 
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>RSS Feeds Disabled</h1>
        <div class="message">' . wp_kses_post($custom_message) . '</div>
        <div class="back-link">
            <a href="' . home_url() . '">Return to ' . get_bloginfo('name') . '</a>
        </div>
    </div>
</body>
</html>';
        exit;
    }
    
    /**
     * 移除 RSS 重寫規則
     */
    public function remove_feed_rewrite_rules($rules) {
        foreach ($rules as $rule => $rewrite) {
            if (strpos($rewrite, 'feed=') !== false) {
                unset($rules[$rule]);
            }
        }
        return $rules;
    }
}

// 初始化模組
new WU_RSS_Disabler();