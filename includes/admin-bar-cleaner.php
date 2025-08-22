<?php
/**
 * 管理列清理模組
 * 功能：刪除 WordPress 管理列的「W」項目
 */

if (!defined('ABSPATH')) exit;

class WU_Admin_Bar_Cleaner {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        
        // 如果啟用了清理功能，則執行相關動作
        if (get_option('wu_remove_wp_logo', false)) {
            $this->remove_wp_logo();
        }
    }
    
    /**
     * 添加管理頁面
     */
    public function add_admin_menu() {
        add_submenu_page(
            'wu-toolbox',
            '管理列清理',
            '管理列清理',
            'manage_options',
            'wu-admin-bar-cleaner',
            array($this, 'admin_page')
        );
    }
    
    /**
     * 初始化設定
     */
    public function admin_init() {
        register_setting('wu_admin_bar_settings', 'wu_remove_wp_logo');
        
        add_settings_section(
            'wu_admin_bar_section',
            '管理列清理設定',
            array($this, 'settings_section_callback'),
            'wu_admin_bar_settings'
        );
        
        add_settings_field(
            'wu_remove_wp_logo',
            '移除 WordPress 標誌',
            array($this, 'remove_wp_logo_callback'),
            'wu_admin_bar_settings',
            'wu_admin_bar_section'
        );
    }
    
    /**
     * 設定區域說明
     */
    public function settings_section_callback() {
        echo '<p>管理列清理功能可以幫助您移除不必要的 WordPress 預設項目，讓管理界面更加簡潔。</p>';
        echo '<p><strong>建議：</strong>移除 WordPress 標誌可以讓管理列看起來更專業，減少不必要的品牌展示。</p>';
    }
    
    /**
     * 移除 WordPress 標誌選項回調
     */
    public function remove_wp_logo_callback() {
        $value = get_option('wu_remove_wp_logo', false);
        echo '<input type="checkbox" id="wu_remove_wp_logo" name="wu_remove_wp_logo" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_remove_wp_logo">移除管理列中的 WordPress 標誌（W 圖示）</label>';
        echo '<p class="description">勾選此選項將從管理列中移除 WordPress 標誌及其下拉選單，讓界面更加簡潔。</p>';
    }
    
    /**
     * 管理頁面
     */
    public function admin_page() {
        if (isset($_POST['submit'])) {
            check_admin_referer('wu_admin_bar_settings-options');
            
            // 處理表單提交
            update_option('wu_remove_wp_logo', isset($_POST['wu_remove_wp_logo']) ? 1 : 0);
            
            echo '<div class="notice notice-success"><p>設定已儲存！請重新整理頁面以查看變更。</p></div>';
        }
        
        $current_status = get_option('wu_remove_wp_logo', false);
        ?>
        <div class="wrap">
            <h1>管理列清理設定</h1>
            
            <div class="card">
                <h2>當前狀態</h2>
                <p><strong>WordPress 標誌狀態：</strong> 
                    <span class="<?php echo $current_status ? 'wu-status-hidden' : 'wu-status-visible'; ?>">
                        <?php echo $current_status ? '已隱藏' : '顯示中'; ?>
                    </span>
                </p>
            </div>
            
            <form method="post" action="">
                <?php
                settings_fields('wu_admin_bar_settings');
                do_settings_sections('wu_admin_bar_settings');
                wp_nonce_field('wu_admin_bar_settings-options');
                submit_button();
                ?>
            </form>
            
            <div class="card">
                <h2>功能說明</h2>
                <h3>什麼是管理列？</h3>
                <ul>
                    <li>管理列是 WordPress 頂部的黑色工具列</li>
                    <li>包含快速訪問連結和實用工具</li>
                    <li>在前台和後台都會顯示（如果已登入）</li>
                </ul>
                
                <h3>為什麼要移除 WordPress 標誌？</h3>
                <ul>
                    <li><strong>專業外觀：</strong>移除品牌標識讓網站看起來更專業</li>
                    <li><strong>簡潔界面：</strong>減少不必要的視覺元素</li>
                    <li><strong>白標化：</strong>適合代理商或自定義品牌需求</li>
                    <li><strong>安全考量：</strong>不透露使用 WordPress 的資訊</li>
                </ul>
                
                <h3>技術實現</h3>
                <ul>
                    <li>使用 <code>admin_bar_menu</code> 鉤子移除指定項目</li>
                    <li>通過 <code>remove_node()</code> 方法安全移除節點</li>
                    <li>不影響其他管理列功能的正常運作</li>
                    <li>可隨時啟用或禁用，變更即時生效</li>
                </ul>
                
                <h3>影響範圍</h3>
                <ul>
                    <li>移除管理列中的 WordPress 標誌（W 圖示）</li>
                    <li>移除標誌下拉選單中的所有項目</li>
                    <li>包括：關於 WordPress、WordPress.org、文件、支援論壇、回饋等</li>
                </ul>
            </div>
        </div>
        
        <style>
        .wu-status-visible { color: #d63638; font-weight: bold; }
        .wu-status-hidden { color: #00a32a; font-weight: bold; }
        .card { background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin: 20px 0; }
        .card h2 { margin-top: 0; }
        .card h3 { color: #23282d; }
        .card ul { margin-left: 20px; }
        </style>
        <?php
    }
    
    /**
     * 移除 WordPress 標誌
     */
    private function remove_wp_logo() {
        add_action('admin_bar_menu', array($this, 'remove_wp_logo_from_admin_bar'), 999);
    }
    
    /**
     * 從管理列中移除 WordPress 標誌
     */
    public function remove_wp_logo_from_admin_bar($wp_admin_bar) {
        // 移除 WordPress 標誌
        $wp_admin_bar->remove_node('wp-logo');
        
        // 移除相關的子項目（以防萬一）
        $wp_admin_bar->remove_node('wp-logo-external');
        $wp_admin_bar->remove_node('about');
        $wp_admin_bar->remove_node('wporg');
        $wp_admin_bar->remove_node('documentation');
        $wp_admin_bar->remove_node('support-forums');
        $wp_admin_bar->remove_node('feedback');
    }
}

// 初始化模組
new WU_Admin_Bar_Cleaner();