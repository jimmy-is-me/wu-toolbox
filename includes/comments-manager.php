<?php
/**
 * 評論管理模組
 * 功能：停用 WordPress 評論功能，支援細粒度控制
 */

if (!defined('ABSPATH')) exit;

class WU_Comments_Manager {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        
        // 如果啟用了評論禁用功能，則執行相關動作
        if (get_option('wu_disable_comments_globally', false)) {
            $this->disable_comments_globally();
        } else {
            // 檢查特定內容類型的設定
            $this->apply_selective_comment_settings();
        }
    }
    
    /**
     * 添加管理頁面
     */
    public function add_admin_menu() {
        add_submenu_page(
            'wumetax-toolkit',
            '評論管理',
            '評論管理',
            'manage_options',
            'wu-comments-manager',
            array($this, 'admin_page')
        );
    }
    
    /**
     * 初始化設定
     */
    public function admin_init() {
        register_setting('wu_comments_settings', 'wu_disable_comments_globally');
        register_setting('wu_comments_settings', 'wu_disable_comments_posts');
        register_setting('wu_comments_settings', 'wu_disable_comments_pages');
        register_setting('wu_comments_settings', 'wu_disable_comments_custom_types');
        
        add_settings_section(
            'wu_comments_section',
            '評論管理設定',
            array($this, 'settings_section_callback'),
            'wu_comments_settings'
        );
        
        add_settings_field(
            'wu_disable_comments_globally',
            '全域禁用評論',
            array($this, 'disable_globally_callback'),
            'wu_comments_settings',
            'wu_comments_section'
        );
        
        add_settings_field(
            'wu_disable_comments_posts',
            '禁用文章評論',
            array($this, 'disable_posts_callback'),
            'wu_comments_settings',
            'wu_comments_section'
        );
        
        add_settings_field(
            'wu_disable_comments_pages',
            '禁用頁面評論',
            array($this, 'disable_pages_callback'),
            'wu_comments_settings',
            'wu_comments_section'
        );
        
        add_settings_field(
            'wu_disable_comments_custom_types',
            '禁用自訂內容類型評論',
            array($this, 'disable_custom_types_callback'),
            'wu_comments_settings',
            'wu_comments_section'
        );
    }
    
    /**
     * 設定區域說明
     */
    public function settings_section_callback() {
        echo '<p>評論管理功能讓您可以靈活控制 WordPress 網站的評論功能，可以全域禁用或針對特定內容類型禁用。</p>';
        echo '<p><strong>建議：</strong>如果您的網站不需要評論功能，建議禁用以減少垃圾評論和提高安全性。</p>';
    }
    
    /**
     * 全域禁用評論選項回調
     */
    public function disable_globally_callback() {
        $value = get_option('wu_disable_comments_globally', false);
        echo '<input type="checkbox" id="wu_disable_comments_globally" name="wu_disable_comments_globally" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_disable_comments_globally">完全禁用整個網站的評論功能</label>';
        echo '<p class="description">勾選此選項將禁用所有內容類型的評論功能，包括現有評論的顯示。</p>';
    }
    
    /**
     * 禁用文章評論選項回調
     */
    public function disable_posts_callback() {
        $value = get_option('wu_disable_comments_posts', false);
        echo '<input type="checkbox" id="wu_disable_comments_posts" name="wu_disable_comments_posts" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_disable_comments_posts">僅禁用文章（Post）的評論功能</label>';
        echo '<p class="description">僅針對文章內容類型禁用評論，其他內容類型不受影響。</p>';
    }
    
    /**
     * 禁用頁面評論選項回調
     */
    public function disable_pages_callback() {
        $value = get_option('wu_disable_comments_pages', false);
        echo '<input type="checkbox" id="wu_disable_comments_pages" name="wu_disable_comments_pages" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_disable_comments_pages">僅禁用頁面（Page）的評論功能</label>';
        echo '<p class="description">僅針對頁面內容類型禁用評論，其他內容類型不受影響。</p>';
    }
    
    /**
     * 禁用自訂內容類型評論選項回調
     */
    public function disable_custom_types_callback() {
        $value = get_option('wu_disable_comments_custom_types', false);
        echo '<input type="checkbox" id="wu_disable_comments_custom_types" name="wu_disable_comments_custom_types" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_disable_comments_custom_types">禁用自訂內容類型的評論功能</label>';
        echo '<p class="description">針對所有自訂內容類型（如產品、作品集等）禁用評論功能。</p>';
    }
    
    /**
     * 管理頁面
     */
    public function admin_page() {
        if (isset($_POST['submit'])) {
            check_admin_referer('wu_comments_settings-options');
            
            // 處理表單提交
            update_option('wu_disable_comments_globally', isset($_POST['wu_disable_comments_globally']) ? 1 : 0);
            update_option('wu_disable_comments_posts', isset($_POST['wu_disable_comments_posts']) ? 1 : 0);
            update_option('wu_disable_comments_pages', isset($_POST['wu_disable_comments_pages']) ? 1 : 0);
            update_option('wu_disable_comments_custom_types', isset($_POST['wu_disable_comments_custom_types']) ? 1 : 0);
            
            echo '<div class="notice notice-success"><p>設定已儲存！</p></div>';
        }
        
        // 處理批量刪除評論
        if (isset($_POST['delete_all_comments'])) {
            check_admin_referer('wu_delete_comments');
            $deleted_count = $this->delete_all_comments();
            echo '<div class="notice notice-success"><p>已刪除 ' . $deleted_count . ' 條評論！</p></div>';
        }
        
        $comments_count = wp_count_comments();
        $global_disabled = get_option('wu_disable_comments_globally', false);
        ?>
        <div class="wrap">
            <h1>評論管理設定</h1>
            
            <div class="card">
                <h2>當前狀態</h2>
                <p><strong>全域評論狀態：</strong> 
                    <span class="<?php echo $global_disabled ? 'wu-status-disabled' : 'wu-status-enabled'; ?>">
                        <?php echo $global_disabled ? '已禁用' : '已啟用'; ?>
                    </span>
                </p>
                <p><strong>已發佈評論：</strong> <?php echo $comments_count->approved; ?> 條</p>
                <p><strong>待審核評論：</strong> <?php echo $comments_count->moderated; ?> 條</p>
                <p><strong>垃圾評論：</strong> <?php echo $comments_count->spam; ?> 條</p>
                <p><strong>總評論數：</strong> <?php echo $comments_count->total_comments; ?> 條</p>
            </div>
            
            <form method="post" action="">
                <?php
                settings_fields('wu_comments_settings');
                do_settings_sections('wu_comments_settings');
                wp_nonce_field('wu_comments_settings-options');
                submit_button();
                ?>
            </form>
            
            <?php if ($comments_count->total_comments > 0): ?>
            <div class="card">
                <h2>評論清理</h2>
                <p>如果您決定禁用評論功能，可以選擇刪除所有現有評論以清理資料庫。</p>
                <p><strong>警告：</strong>此操作無法復原，請謹慎使用！</p>
                
                <form method="post" action="" onsubmit="return confirm('確定要刪除所有評論嗎？此操作無法復原！');">
                    <?php wp_nonce_field('wu_delete_comments'); ?>
                    <input type="submit" name="delete_all_comments" class="button button-secondary" value="刪除所有評論" />
                </form>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <h2>功能說明</h2>
                <h3>什麼是評論管理？</h3>
                <ul>
                    <li>控制 WordPress 網站的評論功能開關</li>
                    <li>可以針對不同內容類型進行細粒度控制</li>
                    <li>包括禁用評論表單、隱藏現有評論等</li>
                </ul>
                
                <h3>禁用選項說明</h3>
                <h4>全域禁用</h4>
                <ul>
                    <li>完全禁用整個網站的評論功能</li>
                    <li>移除所有評論相關的介面元素</li>
                    <li>禁用評論 REST API 端點</li>
                    <li>隱藏管理後台的評論選單</li>
                </ul>
                
                <h4>選擇性禁用</h4>
                <ul>
                    <li><strong>文章評論：</strong>僅禁用部落格文章的評論功能</li>
                    <li><strong>頁面評論：</strong>僅禁用靜態頁面的評論功能</li>
                    <li><strong>自訂內容類型：</strong>禁用所有自訂內容類型的評論</li>
                </ul>
                
                <h3>技術實現</h3>
                <ul>
                    <li>使用 <code>remove_post_type_support()</code> 移除評論支援</li>
                    <li>通過 <code>comments_open</code> 過濾器控制評論開關</li>
                    <li>使用 <code>rest_endpoints</code> 過濾器禁用 REST API</li>
                    <li>移除評論相關的管理選單和元框</li>
                </ul>
                
                <h3>為什麼要禁用評論？</h3>
                <ul>
                    <li><strong>減少垃圾內容：</strong>避免垃圾評論和惡意連結</li>
                    <li><strong>提高安全性：</strong>減少潛在的攻擊向量</li>
                    <li><strong>簡化管理：</strong>不需要審核和管理評論</li>
                    <li><strong>改善效能：</strong>減少資料庫查詢和頁面載入時間</li>
                    <li><strong>專注內容：</strong>讓訪客專注於主要內容</li>
                </ul>
                
                <h3>注意事項</h3>
                <ul>
                    <li>禁用評論不會刪除現有評論，只是隱藏顯示</li>
                    <li>如果需要完全清理，可以使用「刪除所有評論」功能</li>
                    <li>某些主題可能需要調整以適應無評論的佈局</li>
                    <li>禁用後可以隨時重新啟用，現有評論會重新顯示</li>
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
        </style>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var globalCheckbox = document.getElementById('wu_disable_comments_globally');
            var otherCheckboxes = [
                document.getElementById('wu_disable_comments_posts'),
                document.getElementById('wu_disable_comments_pages'),
                document.getElementById('wu_disable_comments_custom_types')
            ];
            
            function toggleOtherOptions() {
                var disabled = globalCheckbox.checked;
                otherCheckboxes.forEach(function(checkbox) {
                    checkbox.disabled = disabled;
                    if (disabled) {
                        checkbox.parentNode.style.opacity = '0.5';
                    } else {
                        checkbox.parentNode.style.opacity = '1';
                    }
                });
            }
            
            globalCheckbox.addEventListener('change', toggleOtherOptions);
            toggleOtherOptions(); // 初始化狀態
        });
        </script>
        <?php
    }
    
    /**
     * 全域禁用評論
     */
    private function disable_comments_globally() {
        // 禁用評論支援
        add_action('init', array($this, 'remove_comment_support'));
        
        // 關閉評論
        add_filter('comments_open', '__return_false', 20, 2);
        add_filter('pings_open', '__return_false', 20, 2);
        
        // 隱藏現有評論
        add_filter('comments_array', '__return_empty_array', 10, 2);
        
        // 禁用評論 REST API
        add_filter('rest_endpoints', array($this, 'disable_comments_rest_api'));
        
        // 禁用評論相關的 WordPress API
        add_filter('xmlrpc_methods', array($this, 'disable_xmlrpc_comments'));
        add_action('wp_loaded', array($this, 'disable_comment_queries'));
        
        // 移除評論相關的管理介面
        add_action('admin_init', array($this, 'remove_admin_comment_features'));
        
        // 移除評論相關的 meta box
        add_action('admin_menu', array($this, 'remove_comment_admin_menus'));
        
        // 移除評論相關的工具列項目
        add_action('init', array($this, 'remove_comment_admin_bar'));
        
        // 移除評論 RSS feeds
        add_filter('feed_links_show_comments_feed', '__return_false');
    }
    
    /**
     * 應用選擇性評論設定
     */
    private function apply_selective_comment_settings() {
        if (get_option('wu_disable_comments_posts', false)) {
            add_action('init', function() {
                remove_post_type_support('post', 'comments');
                remove_post_type_support('post', 'trackbacks');
            });
        }
        
        if (get_option('wu_disable_comments_pages', false)) {
            add_action('init', function() {
                remove_post_type_support('page', 'comments');
                remove_post_type_support('page', 'trackbacks');
            });
        }
        
        if (get_option('wu_disable_comments_custom_types', false)) {
            add_action('init', array($this, 'disable_custom_type_comments'));
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
        // 移除所有評論相關的 REST API 端點
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
        // 移除評論元框
        remove_meta_box('commentstatusdiv', 'post', 'normal');
        remove_meta_box('commentstatusdiv', 'page', 'normal');
        remove_meta_box('commentsdiv', 'post', 'normal');
        remove_meta_box('trackbacksdiv', 'post', 'normal');
        
        // 移除儀表板評論小工具
        remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
    }
    
    /**
     * 移除評論管理選單
     */
    public function remove_comment_admin_menus() {
        remove_menu_page('edit-comments.php');
    }
    
    /**
     * 移除評論工具列項目
     */
    public function remove_comment_admin_bar() {
        if (is_admin_bar_showing()) {
            remove_action('admin_bar_menu', 'wp_admin_bar_comments_menu', 60);
        }
        
        // 添加更強的工具列評論移除
        add_action('wp_before_admin_bar_render', array($this, 'remove_admin_bar_comments'));
    }
    
    /**
     * 強制移除管理工具列的評論項目
     */
    public function remove_admin_bar_comments() {
        global $wp_admin_bar;
        if ($wp_admin_bar) {
            $wp_admin_bar->remove_menu('comments');
        }
    }
    
    /**
     * 禁用 XML-RPC 評論方法
     */
    public function disable_xmlrpc_comments($methods) {
        $comment_methods = array(
            'wp.newComment',
            'wp.getComments',
            'wp.getComment',
            'wp.editComment',
            'wp.deleteComment',
            'wp.getCommentStatusList',
            'wp.getCommentCount'
        );
        
        foreach ($comment_methods as $method) {
            if (isset($methods[$method])) {
                unset($methods[$method]);
            }
        }
        
        return $methods;
    }
    
    /**
     * 禁用評論查詢
     */
    public function disable_comment_queries() {
        // 移除評論查詢變數
        global $wp;
        if (isset($wp->public_query_vars)) {
            $wp->public_query_vars = array_diff($wp->public_query_vars, array(
                'withcomments',
                'cpage',
                'comments'
            ));
        }
        
        // 禁用評論重寫規則
        add_filter('comments_rewrite_rules', '__return_empty_array');
    }
    
    /**
     * 刪除所有評論
     */
    private function delete_all_comments() {
        global $wpdb;
        
        // 刪除所有評論
        $deleted_comments = $wpdb->query("DELETE FROM {$wpdb->comments}");
        
        // 刪除評論元數據
        $wpdb->query("DELETE FROM {$wpdb->commentmeta}");
        
        // 重置文章評論計數
        $wpdb->query("UPDATE {$wpdb->posts} SET comment_count = 0");
        
        // 清理評論緩存
        clean_comment_cache(array());
        
        return $deleted_comments;
    }
}

// 初始化模組
new WU_Comments_Manager();