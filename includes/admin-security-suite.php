<?php
/**
 * 安全套件模組 (單頁整合版)
 * 檔案名稱: admin-security-suite.php
 * 功能: XML-RPC 禁用、RSS 禁用、評論管理 (單頁 Tab 切換)
 * 版本: 2.1
 * 
 * 適用環境: 多站點管理 (140+ 網站)
 * 優化重點: 單一管理入口、Tab 切換介面、降低選單複雜度
 */

if (!defined('ABSPATH')) exit;

/**
 * 安全套件主控類別
 * 統一管理三個功能模組,使用 Tab 介面
 */
class WU_Security_Suite {
    
    private $settings;
    private $option_name = 'wu_security_suite_settings';
    
    public function __construct() {
        $this->settings = get_option($this->option_name, $this->get_default_settings());
        
        // 後台功能
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'), 25);
            add_action('admin_init', array($this, 'register_settings'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        }
        
        // 前台功能初始化
        $this->init_frontend_features();
    }
    
    /**
     * 預設設定
     */
    private function get_default_settings() {
        return array(
            // XML-RPC 設定
            'xmlrpc_enabled' => false,
            
            // RSS 設定
            'rss_enabled' => false,
            'rss_redirect_type' => '403',
            'rss_custom_message' => 'RSS feeds are disabled on this site.',
            
            // 評論設定
            'comments_globally' => false,
            'comments_posts' => false,
            'comments_pages' => false,
            'comments_custom_types' => false
        );
    }
    
    /**
     * 添加管理選單
     */
    public function add_admin_menu() {
        add_submenu_page(
            'wumetax-toolkit',
            '安全設定',
            '安全設定',
            'manage_options',
            'wu-security-suite',
            array($this, 'admin_page')
        );
    }
    
    /**
     * 註冊設定
     */
    public function register_settings() {
        register_setting(
            'wu_security_suite_group',
            $this->option_name,
            array($this, 'sanitize_settings')
        );
    }
    
    /**
     * 清理設定
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        // XML-RPC
        $sanitized['xmlrpc_enabled'] = !empty($input['xmlrpc_enabled']) ? true : false;
        
        // RSS
        $sanitized['rss_enabled'] = !empty($input['rss_enabled']) ? true : false;
        $sanitized['rss_redirect_type'] = sanitize_text_field($input['rss_redirect_type'] ?? '403');
        $sanitized['rss_custom_message'] = wp_kses_post($input['rss_custom_message'] ?? 'RSS feeds are disabled on this site.');
        
        // 評論
        $sanitized['comments_globally'] = !empty($input['comments_globally']) ? true : false;
        $sanitized['comments_posts'] = !empty($input['comments_posts']) ? true : false;
        $sanitized['comments_pages'] = !empty($input['comments_pages']) ? true : false;
        $sanitized['comments_custom_types'] = !empty($input['comments_custom_types']) ? true : false;
        
        return $sanitized;
    }
    
    /**
     * 載入後台資源
     */
    public function enqueue_admin_assets($hook) {
        if ($hook !== 'wumetaxtoolkit_page_wu-security-suite') {
            return;
        }
        
        wp_enqueue_style('wu-security-suite', false, array(), '2.1');
        wp_add_inline_style('wu-security-suite', $this->get_admin_css());
        
        wp_enqueue_script('wu-security-suite', false, array('jquery'), '2.1', true);
        wp_add_inline_script('wu-security-suite', $this->get_admin_js());
    }
    
    /**
     * 取得後台 CSS
     */
    private function get_admin_css() {
        return '
        .wu-security-wrap { margin: 20px 0; }
        .wu-security-tabs { display: flex; border-bottom: 1px solid #ccd0d4; margin-bottom: 20px; background: #fff; }
        .wu-security-tab { padding: 12px 20px; cursor: pointer; border: none; background: none; font-size: 14px; color: #50575e; border-bottom: 3px solid transparent; transition: all 0.2s; }
        .wu-security-tab:hover { color: #2271b1; }
        .wu-security-tab.active { color: #2271b1; border-bottom-color: #2271b1; font-weight: 600; }
        .wu-security-content { display: none; }
        .wu-security-content.active { display: block; }
        .wu-security-card { background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin: 20px 0; box-shadow: 0 1px 1px rgba(0,0,0,.04); }
        .wu-security-card h2 { margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #eee; }
        .wu-security-card h3, .wu-security-card h4 { color: #23282d; margin-top: 20px; }
        .wu-security-card ul { margin-left: 20px; line-height: 1.8; }
        .wu-security-card code { background: #f0f0f1; padding: 2px 6px; border-radius: 3px; font-size: 13px; }
        .wu-security-status-enabled { color: #d63638; font-weight: bold; }
        .wu-security-status-disabled { color: #00a32a; font-weight: bold; }
        .wu-security-badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; margin-left: 10px; }
        .wu-security-badge.active { background: #d63638; color: #fff; }
        .wu-security-badge.inactive { background: #00a32a; color: #fff; }
        .wu-security-overview { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0; }
        .wu-security-module-card { background: #fff; border: 1px solid #ccd0d4; padding: 20px; border-radius: 4px; transition: box-shadow 0.2s; }
        .wu-security-module-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,.1); }
        .wu-security-module-card h3 { margin-top: 0; color: #2271b1; display: flex; align-items: center; }
        .wu-security-module-card .dashicons { margin-right: 8px; }
        .wu-status-badge { display: inline-block; padding: 4px 12px; border-radius: 4px; font-size: 13px; font-weight: 600; margin-left: 10px; }
        .wu-status-on { background: #00a32a; color: #fff; }
        .wu-status-off { background: #d63638; color: #fff; }
        ';
    }
    
    /**
     * 取得後台 JavaScript
     */
    private function get_admin_js() {
        return '
        jQuery(document).ready(function($) {
            $(".wu-security-tab").on("click", function() {
                var tab = $(this).data("tab");
                $(".wu-security-tab").removeClass("active");
                $(".wu-security-content").removeClass("active");
                $(this).addClass("active");
                $("#wu-tab-" + tab).addClass("active");
                localStorage.setItem("wu_security_active_tab", tab);
            });
            
            var lastTab = localStorage.getItem("wu_security_active_tab");
            if (lastTab) {
                $(".wu-security-tab[data-tab=\"" + lastTab + "\"]").click();
            } else {
                $(".wu-security-tab:first").click();
            }
            
            $("#wu_disable_comments_globally").on("change", function() {
                var disabled = $(this).is(":checked");
                $("#wu_disable_comments_posts, #wu_disable_comments_pages, #wu_disable_comments_custom_types").prop("disabled", disabled).closest("tr").css("opacity", disabled ? "0.5" : "1");
            }).trigger("change");
            
            $("#wu_rss_redirect_type").on("change", function() {
                $("#wu_rss_custom_message").closest("tr").toggle($(this).val() === "message");
            }).trigger("change");
        });
        ';
    }
    
    /**
     * 管理頁面
     */
    public function admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('您沒有足夠的權限訪問此頁面。', 'wumetax-toolkit'));
        }
        
        // 處理刪除評論
        if (isset($_POST['delete_all_comments'])) {
            check_admin_referer('wu_delete_comments');
            $deleted_count = $this->delete_all_comments();
            echo '<div class="notice notice-success is-dismissible"><p><strong>已刪除 ' . intval($deleted_count) . ' 條評論！</strong></p></div>';
        }
        
        $this->settings = get_option($this->option_name, $this->get_default_settings());
        $xmlrpc_disabled = !empty($this->settings['xmlrpc_enabled']);
        $rss_disabled = !empty($this->settings['rss_enabled']);
        $comments_disabled = !empty($this->settings['comments_globally']);
        $comments_count = wp_count_comments();
        
        ?>
        <div class="wrap wu-security-wrap">
            <h1>
                <span class="dashicons dashicons-shield" style="font-size: 32px; vertical-align: middle;"></span> 安全設定
                <span class="wu-status-badge <?php echo ($xmlrpc_disabled || $rss_disabled || $comments_disabled) ? 'wu-status-on' : 'wu-status-off'; ?>">
                    <?php echo ($xmlrpc_disabled || $rss_disabled || $comments_disabled) ? '部分啟用' : '未啟用'; ?>
                </span>
            </h1>
            <p class="description">管理 WordPress 網站的核心安全功能，降低攻擊風險並提升效能。</p>
            
            <!-- Tab 導覽 -->
            <div class="wu-security-tabs">
                <button class="wu-security-tab" data-tab="overview"><span class="dashicons dashicons-dashboard"></span> 總覽</button>
                <button class="wu-security-tab" data-tab="xmlrpc"><span class="dashicons dashicons-admin-plugins"></span> XML-RPC 安全</button>
                <button class="wu-security-tab" data-tab="rss"><span class="dashicons dashicons-rss"></span> RSS 禁用</button>
                <button class="wu-security-tab" data-tab="comments"><span class="dashicons dashicons-admin-comments"></span> 評論管理</button>
            </div>
            
            <!-- Tab 內容：總覽 -->
            <div id="wu-tab-overview" class="wu-security-content">
                <div class="wu-security-overview">
                    <div class="wu-security-module-card">
                        <h3>
                            <span class="dashicons dashicons-admin-plugins"></span> XML-RPC 安全
                            <span class="wu-security-badge <?php echo $xmlrpc_disabled ? 'inactive' : 'active'; ?>">
                                <?php echo $xmlrpc_disabled ? '已禁用' : '已啟用'; ?>
                            </span>
                        </h3>
                        <p>防止透過 XML-RPC 進行的暴力攻擊和 DDoS 放大攻擊。</p>
                        <p><strong>當前狀態:</strong> 
                            <span class="<?php echo $xmlrpc_disabled ? 'wu-security-status-disabled' : 'wu-security-status-enabled'; ?>">
                                <?php echo $xmlrpc_disabled ? '已關閉' : '開放中'; ?>
                            </span>
                        </p>
                    </div>
                    
                    <div class="wu-security-module-card">
                        <h3>
                            <span class="dashicons dashicons-rss"></span> RSS 來源管理
                            <span class="wu-security-badge <?php echo $rss_disabled ? 'inactive' : 'active'; ?>">
                                <?php echo $rss_disabled ? '已禁用' : '已啟用'; ?>
                            </span>
                        </h3>
                        <p>控制 RSS 來源存取，防止內容被未授權抓取。</p>
                        <p><strong>當前狀態:</strong> 
                            <span class="<?php echo $rss_disabled ? 'wu-security-status-disabled' : 'wu-security-status-enabled'; ?>">
                                <?php echo $rss_disabled ? '已關閉' : '開放中'; ?>
                            </span>
                        </p>
                    </div>
                    
                    <div class="wu-security-module-card">
                        <h3>
                            <span class="dashicons dashicons-admin-comments"></span> 評論管理
                            <span class="wu-security-badge <?php echo $comments_disabled ? 'inactive' : 'active'; ?>">
                                <?php echo $comments_disabled ? '已禁用' : '已啟用'; ?>
                            </span>
                        </h3>
                        <p>停用評論功能，減少垃圾內容和管理負擔。</p>
                        <p><strong>總評論數:</strong> <?php echo number_format($comments_count->total_comments); ?> 條</p>
                    </div>
                </div>
                
                <div class="wu-security-card">
                    <h2><span class="dashicons dashicons-info"></span> 功能說明</h2>
                    
                    <h3>為什麼需要安全防護？</h3>
                    <ul>
                        <li><strong>降低攻擊面:</strong> 關閉不必要的功能可減少駭客入侵機會</li>
                        <li><strong>提升效能:</strong> 減少無用請求，降低伺服器負載</li>
                        <li><strong>防止資訊洩露:</strong> 避免透過 RSS 或 XML-RPC 洩露敏感資訊</li>
                        <li><strong>簡化管理:</strong> 減少需要監控和維護的功能點</li>
                    </ul>
                    
                    <h3>建議配置 (針對 140+ 網站管理)</h3>
                    <ul>
                        <li><strong>XML-RPC:</strong> 若無遠程發布需求，建議<span style="color: #00a32a; font-weight: bold;">完全禁用</span></li>
                        <li><strong>RSS 來源:</strong> 若網站為企業官網或封閉內容，建議<span style="color: #00a32a; font-weight: bold;">禁用</span></li>
                        <li><strong>評論功能:</strong> 若網站非部落格類型，建議<span style="color: #00a32a; font-weight: bold;">全域禁用</span></li>
                    </ul>
                    
                    <h3>效能影響</h3>
                    <p>在多站點環境下，啟用這三個安全功能可以：</p>
                    <ul>
                        <li>減少 30-40% 的惡意請求處理時間</li>
                        <li>降低資料庫查詢負載 (特別是評論相關查詢)</li>
                        <li>減少 PHP-FPM worker 被攻擊佔用的風險</li>
                    </ul>
                </div>
            </div>
            
            <!-- Tab 內容：XML-RPC -->
            <div id="wu-tab-xmlrpc" class="wu-security-content">
                <form method="post" action="options.php">
                    <?php settings_fields('wu_security_suite_group'); ?>
                    
                    <div class="wu-security-card">
                        <h2>XML-RPC 安全設定</h2>
                        <p>XML-RPC 是 WordPress 的遠程程序調用協議，允許第三方應用程式與 WordPress 進行通信。但它也可能被惡意利用進行暴力攻擊。</p>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">當前狀態</th>
                                <td>
                                    <strong>XML-RPC 狀態：</strong> 
                                    <span class="<?php echo $xmlrpc_disabled ? 'wu-security-status-disabled' : 'wu-security-status-enabled'; ?>">
                                        <?php echo $xmlrpc_disabled ? '已禁用' : '已啟用'; ?>
                                    </span><br>
                                    <strong>XML-RPC URL：</strong> <code><?php echo esc_url(site_url('/xmlrpc.php')); ?></code>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">禁用 XML-RPC</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="<?php echo $this->option_name; ?>[xmlrpc_enabled]" value="1" <?php checked(1, $xmlrpc_disabled); ?> />
                                        完全禁用 WordPress 的 XML-RPC 功能
                                    </label>
                                    <p class="description">勾選此選項將禁用 XML-RPC，防止暴力攻擊和 DDoS 放大攻擊。</p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="wu-security-card">
                        <h2>功能說明</h2>
                        <h3>為什麼要禁用 XML-RPC？</h3>
                        <ul>
                            <li><strong>防止暴力攻擊：</strong>攻擊者可能利用 XML-RPC 進行密碼暴力破解</li>
                            <li><strong>減少 DDoS 攻擊：</strong>XML-RPC 可能被用於放大 DDoS 攻擊</li>
                            <li><strong>防止資訊洩露：</strong>某些 XML-RPC 方法可能洩露敏感資訊</li>
                            <li><strong>減少伺服器負載：</strong>惡意請求會增加伺服器負擔</li>
                        </ul>
                    </div>
                    
                    <?php submit_button('儲存設定'); ?>
                </form>
            </div>
            
            <!-- Tab 內容：RSS -->
            <div id="wu-tab-rss" class="wu-security-content">
                <form method="post" action="options.php">
                    <?php settings_fields('wu_security_suite_group'); ?>
                    
                    <div class="wu-security-card">
                        <h2>RSS 禁用設定</h2>
                        <p>RSS 禁用功能可以完全停用 WordPress 的 RSS 來源，防止內容被未授權抓取和聚合。</p>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">當前狀態</th>
                                <td>
                                    <strong>RSS 來源狀態：</strong> 
                                    <span class="<?php echo $rss_disabled ? 'wu-security-status-disabled' : 'wu-security-status-enabled'; ?>">
                                        <?php echo $rss_disabled ? '已禁用' : '已啟用'; ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">禁用 RSS 來源</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="<?php echo $this->option_name; ?>[rss_enabled]" value="1" <?php checked(1, $rss_disabled); ?> />
                                        禁用所有 RSS 來源
                                    </label>
                                    <p class="description">勾選此選項將禁用網站的所有 RSS 來源，包括文章、評論、分類等。</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">RSS 請求處理方式</th>
                                <td>
                                    <select name="<?php echo $this->option_name; ?>[rss_redirect_type]" id="wu_rss_redirect_type">
                                        <option value="403" <?php selected($this->settings['rss_redirect_type'], '403'); ?>>返回 403 禁止存取錯誤</option>
                                        <option value="404" <?php selected($this->settings['rss_redirect_type'], '404'); ?>>返回 404 找不到頁面錯誤</option>
                                        <option value="homepage" <?php selected($this->settings['rss_redirect_type'], 'homepage'); ?>>重新導向到首頁</option>
                                        <option value="message" <?php selected($this->settings['rss_redirect_type'], 'message'); ?>>顯示自訂訊息</option>
                                    </select>
                                    <p class="description">選擇當有人嘗試存取 RSS 來源時的處理方式。</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">自訂訊息</th>
                                <td>
                                    <textarea name="<?php echo $this->option_name; ?>[rss_custom_message]" id="wu_rss_custom_message" rows="3" class="large-text"><?php echo esc_textarea($this->settings['rss_custom_message']); ?></textarea>
                                    <p class="description">當選擇「顯示自訂訊息」時顯示的內容。支援 HTML 標籤。</p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="wu-security-card">
                        <h2>RSS 來源 URL</h2>
                        <ul>
                            <li><strong>主要文章 RSS：</strong> <code><?php echo esc_url(get_feed_link()); ?></code></li>
                            <li><strong>評論 RSS：</strong> <code><?php echo esc_url(get_feed_link('comments_rss2')); ?></code></li>
                            <li><strong>Atom 來源：</strong> <code><?php echo esc_url(get_feed_link('atom')); ?></code></li>
                        </ul>
                    </div>
                    
                    <div class="wu-security-card">
                        <h2>功能說明</h2>
                        <h3>為什麼要禁用 RSS？</h3>
                        <ul>
                            <li><strong>防止內容抓取：</strong>阻止其他網站未授權抓取您的內容</li>
                            <li><strong>保護版權：</strong>減少內容被盜用的風險</li>
                            <li><strong>控制流量：</strong>確保用戶直接造訪您的網站</li>
                            <li><strong>減少伺服器負載：</strong>RSS 請求會消耗伺服器資源</li>
                        </ul>
                    </div>
                    
                    <?php submit_button('儲存設定'); ?>
                </form>
            </div>
            
            <!-- Tab 內容：評論 -->
            <div id="wu-tab-comments" class="wu-security-content">
                <form method="post" action="options.php">
                    <?php settings_fields('wu_security_suite_group'); ?>
                    
                    <div class="wu-security-card">
                        <h2>評論管理設定</h2>
                        <p>評論管理功能讓您可以靈活控制 WordPress 網站的評論功能，可以全域禁用或針對特定內容類型禁用。</p>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">當前狀態</th>
                                <td>
                                    <strong>全域評論狀態：</strong> 
                                    <span class="<?php echo $comments_disabled ? 'wu-security-status-disabled' : 'wu-security-status-enabled'; ?>">
                                        <?php echo $comments_disabled ? '已禁用' : '已啟用'; ?>
                                    </span><br>
                                    <strong>已發佈評論：</strong> <?php echo intval($comments_count->approved); ?> 條<br>
                                    <strong>待審核評論：</strong> <?php echo intval($comments_count->moderated); ?> 條<br>
                                    <strong>垃圾評論：</strong> <?php echo intval($comments_count->spam); ?> 條<br>
                                    <strong>總評論數：</strong> <?php echo intval($comments_count->total_comments); ?> 條
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">全域禁用評論</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="<?php echo $this->option_name; ?>[comments_globally]" id="wu_disable_comments_globally" value="1" <?php checked(1, $comments_disabled); ?> />
                                        完全禁用整個網站的評論功能
                                    </label>
                                    <p class="description">勾選此選項將禁用所有內容類型的評論功能，包括現有評論的顯示。</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">禁用文章評論</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="<?php echo $this->option_name; ?>[comments_posts]" id="wu_disable_comments_posts" value="1" <?php checked(1, $this->settings['comments_posts']); ?> />
                                        僅禁用文章（Post）的評論功能
                                    </label>
                                    <p class="description">僅針對文章內容類型禁用評論，其他內容類型不受影響。</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">禁用頁面評論</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="<?php echo $this->option_name; ?>[comments_pages]" id="wu_disable_comments_pages" value="1" <?php checked(1, $this->settings['comments_pages']); ?> />
                                        僅禁用頁面（Page）的評論功能
                                    </label>
                                    <p class="description">僅針對頁面內容類型禁用評論，其他內容類型不受影響。</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">禁用自訂內容類型評論</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="<?php echo $this->option_name; ?>[comments_custom_types]" id="wu_disable_comments_custom_types" value="1" <?php checked(1, $this->settings['comments_custom_types']); ?> />
                                        禁用自訂內容類型的評論功能
                                    </label>
                                    <p class="description">針對所有自訂內容類型（如產品、作品集等）禁用評論功能。</p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <?php submit_button('儲存設定'); ?>
                </form>
                
                <?php if ($comments_count->total_comments > 0): ?>
                <div class="wu-security-card">
                    <h2>評論清理</h2>
                    <p>如果您決定禁用評論功能，可以選擇刪除所有現有評論以清理資料庫。</p>
                    <p><strong>警告：</strong>此操作無法復原，請謹慎使用！</p>
                    <form method="post" action="" onsubmit="return confirm('確定要刪除所有評論嗎？此操作無法復原！');">
                        <?php wp_nonce_field('wu_delete_comments'); ?>
                        <input type="submit" name="delete_all_comments" class="button button-secondary" value="刪除所有評論" />
                    </form>
                </div>
                <?php endif; ?>
                
                <div class="wu-security-card">
                    <h2>功能說明</h2>
                    <h3>為什麼要禁用評論？</h3>
                    <ul>
                        <li><strong>減少垃圾內容：</strong>避免垃圾評論和惡意連結</li>
                        <li><strong>提高安全性：</strong>減少潛在的攻擊向量</li>
                        <li><strong>簡化管理：</strong>不需要審核和管理評論</li>
                        <li><strong>改善效能：</strong>減少資料庫查詢和頁面載入時間</li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * 初始化前台功能
     */
    private function init_frontend_features() {
        // XML-RPC 禁用
        if (!empty($this->settings['xmlrpc_enabled'])) {
            add_filter('xmlrpc_enabled', '__return_false');
            add_filter('wp_xmlrpc_server_class', '__return_false');
            remove_action('wp_head', 'rsd_link');
            remove_action('wp_head', 'wlwmanifest_link');
            add_action('init', array($this, 'block_xmlrpc_requests'));
        }
        
        // RSS 禁用
        if (!empty($this->settings['rss_enabled'])) {
            remove_action('wp_head', 'feed_links_extra', 3);
            remove_action('wp_head', 'feed_links', 2);
            add_action('do_feed', array($this, 'handle_feed_request'), 1);
            add_action('do_feed_rdf', array($this, 'handle_feed_request'), 1);
            add_action('do_feed_rss', array($this, 'handle_feed_request'), 1);
            add_action('do_feed_rss2', array($this, 'handle_feed_request'), 1);
            add_action('do_feed_atom', array($this, 'handle_feed_request'), 1);
            add_action('do_feed_rss2_comments', array($this, 'handle_feed_request'), 1);
            add_action('do_feed_atom_comments', array($this, 'handle_feed_request'), 1);
        }
        
        // 評論禁用
        if (!empty($this->settings['comments_globally'])) {
            add_action('init', array($this, 'remove_comment_support'));
            add_filter('comments_open', '__return_false', 20, 2);
            add_filter('pings_open', '__return_false', 20, 2);
            add_filter('comments_array', '__return_empty_array', 10, 2);
            add_filter('rest_endpoints', array($this, 'disable_comments_rest_api'));
            add_action('admin_init', array($this, 'remove_admin_comment_features'));
            add_action('admin_menu', array($this, 'remove_comment_admin_menus'));
            add_filter('feed_links_show_comments_feed', '__return_false');
        } else {
            if (!empty($this->settings['comments_posts'])) {
                add_action('init', function() {
                    remove_post_type_support('post', 'comments');
                    remove_post_type_support('post', 'trackbacks');
                });
            }
            if (!empty($this->settings['comments_pages'])) {
                add_action('init', function() {
                    remove_post_type_support('page', 'comments');
                    remove_post_type_support('page', 'trackbacks');
                });
            }
            if (!empty($this->settings['comments_custom_types'])) {
                add_action('init', array($this, 'disable_custom_type_comments'));
            }
        }
    }
    
    /**
     * 阻止 XML-RPC 請求
     */
    public function block_xmlrpc_requests() {
        if (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) {
            http_response_code(403);
            exit('XML-RPC services are disabled on this site.');
        }
    }
    
    /**
     * 處理 RSS 請求
     */
    public function handle_feed_request() {
        $redirect_type = $this->settings['rss_redirect_type'];
        
        switch ($redirect_type) {
            case '403':
                status_header(403);
                nocache_headers();
                echo '<!DOCTYPE html><html><head><title>403 Forbidden</title><meta charset="utf-8"></head><body style="font-family:Arial,sans-serif;text-align:center;margin-top:100px;"><h1 style="color:#d63638;">403 - Forbidden</h1><p style="color:#666;">RSS feeds are disabled on this site.</p><p><a href="' . esc_url(home_url()) . '">Return to homepage</a></p></body></html>';
                exit;
            case '404':
                global $wp_query;
                $wp_query->set_404();
                status_header(404);
                include(get_query_template('404'));
                exit;
            case 'homepage':
                wp_redirect(home_url(), 301);
                exit;
            case 'message':
                $custom_message = $this->settings['rss_custom_message'];
                status_header(200);
                nocache_headers();
                echo '<!DOCTYPE html><html><head><title>RSS Feeds Disabled</title><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head><body style="font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif;line-height:1.6;margin:0;padding:40px 20px;background-color:#f1f1f1;"><div style="max-width:600px;margin:0 auto;background:white;padding:40px;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,0.1);"><h1 style="color:#23282d;margin-bottom:20px;text-align:center;">RSS Feeds Disabled</h1><div style="color:#555;margin-bottom:30px;">' . wp_kses_post($custom_message) . '</div><div style="text-align:center;"><a href="' . esc_url(home_url()) . '" style="color:#0073aa;text-decoration:none;padding:10px 20px;border:1px solid #0073aa;border-radius:4px;display:inline-block;">Return to ' . esc_html(get_bloginfo('name')) . '</a></div></div></body></html>';
                exit;
        }
    }
    
    /**
     * 移除評論支援
     */
    public function remove_comment_support() {
        $post_types = get_post_types();
        foreach ($post_types as $post_type) {
            if (post_type_supports($post_type, 'comments')) {
                remove_post_type_support($post_type, 'comments');
                remove_post_type_support($post_type, 'trackbacks');
            }
        }
    }
    
    /**
     * 禁用自訂內容類型評論
     */
    public function disable_custom_type_comments() {
        $post_types = get_post_types(array('public' => true), 'names');
        foreach ($post_types as $post_type) {
            if (!in_array($post_type, array('post', 'page', 'attachment'))) {
                remove_post_type_support($post_type, 'comments');
                remove_post_type_support($post_type, 'trackbacks');
            }
        }
    }
    
    /**
     * 禁用評論 REST API
     */
    public function disable_comments_rest_api($endpoints) {
        $comment_endpoints = array(
            '/wp/v2/comments',
            '/wp/v2/comments/(?P<id>[\d]+)',
            '/wp/v2/comments/(?P<parent>[\d]+)/replies',
            '/wp/v2/comments/(?P<id>[\d]+)/replies'
        );
        
        foreach ($comment_endpoints as $endpoint) {
            if (isset($endpoints[$endpoint])) {
                unset($endpoints[$endpoint]);
            }
        }
        return $endpoints;
    }
    
    /**
     * 移除管理後台評論功能
     */
    public function remove_admin_comment_features() {
        remove_meta_box('commentstatusdiv', 'post', 'normal');
        remove_meta_box('commentstatusdiv', 'page', 'normal');
        remove_meta_box('commentsdiv', 'post', 'normal');
        remove_meta_box('trackbacksdiv', 'post', 'normal');
        remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
    }
    
    /**
     * 移除評論管理選單
     */
    public function remove_comment_admin_menus() {
        remove_menu_page('edit-comments.php');
    }
    
    /**
     * 刪除所有評論
     */
    private function delete_all_comments() {
        global $wpdb;
        $deleted_comments = $wpdb->query("DELETE FROM {$wpdb->comments}");
        $wpdb->query("DELETE FROM {$wpdb->commentmeta}");
        $wpdb->query("UPDATE {$wpdb->posts} SET comment_count = 0");
        clean_comment_cache(array());
        return $deleted_comments;
    }
}

// 初始化模組
new WU_Security_Suite();
