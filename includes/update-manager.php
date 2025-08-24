<?php
/**
 * 更新管理模組
 * 功能：控制 WordPress、主題和外掛的自動更新功能
 */

if (!defined('ABSPATH')) exit;

class WU_Update_Manager {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        
        // 載入更新控制功能
        $this->load_update_controls();
    }
    
    /**
     * 添加管理頁面
     */
    public function add_admin_menu() {
        add_submenu_page(
            'wumetax-toolkit',
            '更新管理',
            '更新管理',
            'manage_options',
            'wu-update-manager',
            array($this, 'admin_page')
        );
    }
    
    /**
     * 初始化設定
     */
    public function admin_init() {
        // 註冊設定
        register_setting('wu_update_settings', 'wu_disable_theme_auto_updates');
        register_setting('wu_update_settings', 'wu_disable_plugin_auto_updates');
        register_setting('wu_update_settings', 'wu_disable_core_updates');
        
        add_settings_section(
            'wu_update_section',
            '更新管理設定',
            array($this, 'settings_section_callback'),
            'wu_update_settings'
        );
        
        // 添加設定欄位
        add_settings_field(
            'wu_disable_theme_auto_updates',
            '主題自動更新',
            array($this, 'theme_updates_callback'),
            'wu_update_settings',
            'wu_update_section'
        );
        
        add_settings_field(
            'wu_disable_plugin_auto_updates',
            '外掛自動更新',
            array($this, 'plugin_updates_callback'),
            'wu_update_settings',
            'wu_update_section'
        );
        
        add_settings_field(
            'wu_disable_core_updates',
            'WordPress 核心更新',
            array($this, 'core_updates_callback'),
            'wu_update_settings',
            'wu_update_section'
        );
    }
    
    /**
     * 設定區塊回調
     */
    public function settings_section_callback() {
        echo '<p>控制 WordPress、主題和外掛的自動更新行為。這些設定可以提高網站穩定性並避免意外的更新問題。</p>';
    }
    
    /**
     * 主題更新選項回調
     */
    public function theme_updates_callback() {
        $value = get_option('wu_disable_theme_auto_updates', false);
        echo '<input type="checkbox" id="wu_disable_theme_auto_updates" name="wu_disable_theme_auto_updates" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_disable_theme_auto_updates">禁用主題自動更新</label>';
        echo '<p class="description">此選項將停用主題的自動更新功能。您仍然可以透過手動點擊 Update 按鈕來更新主題，但主題將永遠不會自動更新。</p>';
    }
    
    /**
     * 外掛更新選項回調
     */
    public function plugin_updates_callback() {
        $value = get_option('wu_disable_plugin_auto_updates', false);
        echo '<input type="checkbox" id="wu_disable_plugin_auto_updates" name="wu_disable_plugin_auto_updates" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_disable_plugin_auto_updates">停用外掛自動更新</label>';
        echo '<p class="description">此選項將停用外掛的自動更新功能。您仍然可以透過手動點擊 Update 按鈕來更新外掛，但外掛將永遠不會自動更新。</p>';
    }
    
    /**
     * 核心更新選項回調
     */
    public function core_updates_callback() {
        $value = get_option('wu_disable_core_updates', false);
        echo '<input type="checkbox" id="wu_disable_core_updates" name="wu_disable_core_updates" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_disable_core_updates">停用所有 WordPress 核心更新</label>';
        echo '<p class="description">此選項可讓您停用所有 WordPress 核心更新，包括自動更新。WordPress 將不會檢查核心更新，也不會通知用戶更新可用。</p>';
        echo '<p class="description" style="color: #d63638; font-weight: bold;">⚠️ 重要提醒：停用此項目將自動禁用主題及外掛更新功能，以確保系統穩定性。同時可能會造成安全風險，請謹慎使用！</p>';
    }
    
    /**
     * 管理頁面
     */
    public function admin_page() {
        if (isset($_POST['submit'])) {
            check_admin_referer('wu_update_settings-options');
            
            // 處理表單提交
            $disable_core = isset($_POST['wu_disable_core_updates']) ? 1 : 0;
            
            // 如果停用核心更新，自動停用主題和外掛更新
            if ($disable_core) {
                update_option('wu_disable_theme_auto_updates', 1);
                update_option('wu_disable_plugin_auto_updates', 1);
                update_option('wu_disable_core_updates', 1);
            } else {
                update_option('wu_disable_theme_auto_updates', isset($_POST['wu_disable_theme_auto_updates']) ? 1 : 0);
                update_option('wu_disable_plugin_auto_updates', isset($_POST['wu_disable_plugin_auto_updates']) ? 1 : 0);
                update_option('wu_disable_core_updates', 0);
            }
            
            echo '<div class="notice notice-success"><p>設定已儲存！</p></div>';
        }
        
        // 獲取當前更新狀態
        $theme_auto_updates = get_option('wu_disable_theme_auto_updates', false);
        $plugin_auto_updates = get_option('wu_disable_plugin_auto_updates', false);
        $core_updates = get_option('wu_disable_core_updates', false);
        
        ?>
        <div class="wrap">
            <h1>更新管理設定</h1>
            
            <div class="card">
                <h2>當前更新狀態</h2>
                <table class="wu-status-table">
                    <tr>
                        <td><strong>WordPress 版本</strong></td>
                        <td><?php echo get_bloginfo('version'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>主題自動更新</strong></td>
                        <td>
                            <span class="<?php echo $theme_auto_updates ? 'wu-status-disabled' : 'wu-status-enabled'; ?>">
                                <?php echo $theme_auto_updates ? '已禁用' : '已啟用'; ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>外掛自動更新</strong></td>
                        <td>
                            <span class="<?php echo $plugin_auto_updates ? 'wu-status-disabled' : 'wu-status-enabled'; ?>">
                                <?php echo $plugin_auto_updates ? '已禁用' : '已啟用'; ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>核心更新</strong></td>
                        <td>
                            <span class="<?php echo $core_updates ? 'wu-status-disabled' : 'wu-status-enabled'; ?>">
                                <?php echo $core_updates ? '已禁用' : '已啟用'; ?>
                            </span>
                        </td>
                    </tr>
                </table>
                
                <?php
                // 顯示待更新項目
                $this->display_pending_updates();
                ?>
            </div>
            
            <form method="post" action="">
                <?php
                settings_fields('wu_update_settings');
                do_settings_sections('wu_update_settings');
                wp_nonce_field('wu_update_settings-options');
                submit_button();
                ?>
            </form>
            
            <div class="card">
                <h2>功能說明</h2>
                <h3>什麼是更新管理？</h3>
                <ul>
                    <li>控制 WordPress 網站的自動更新行為</li>
                    <li>包括核心、主題和外掛的更新控制</li>
                    <li>提供細粒度的更新控制選項</li>
                    <li>幫助維護網站穩定性</li>
                </ul>
                
                <h3>更新類型說明</h3>
                <h4>主題自動更新</h4>
                <ul>
                    <li>控制主題的自動更新功能</li>
                    <li>禁用後仍可手動更新</li>
                    <li>避免主題更新導致的版面問題</li>
                    <li>保持網站外觀的穩定性</li>
                </ul>
                
                <h4>外掛自動更新</h4>
                <ul>
                    <li>控制外掛的自動更新功能</li>
                    <li>禁用後仍可手動更新</li>
                    <li>避免外掛更新導致的功能衝突</li>
                    <li>確保網站功能的穩定性</li>
                </ul>
                
                <h4>WordPress 核心更新</h4>
                <ul>
                    <li>控制 WordPress 核心的更新檢查</li>
                    <li>包括自動更新和手動更新通知</li>
                    <li>適用於需要嚴格版本控制的網站</li>
                    <li><strong>注意：</strong>禁用核心更新可能影響安全性</li>
                </ul>
                
                <h3>為什麼要管理更新？</h3>
                <ul>
                    <li><strong>穩定性：</strong>避免自動更新導致的網站問題</li>
                    <li><strong>控制性：</strong>在合適的時間進行更新</li>
                    <li><strong>測試機會：</strong>在更新前進行充分測試</li>
                    <li><strong>版本一致性：</strong>保持開發和生產環境一致</li>
                    <li><strong>避免衝突：</strong>防止外掛間的相容性問題</li>
                </ul>
                
                <h3>最佳實踐建議</h3>
                <ul>
                    <li>定期在測試環境中測試更新</li>
                    <li>在更新前備份網站</li>
                    <li>閱讀更新日誌了解變更內容</li>
                    <li>不要完全忽略安全性更新</li>
                    <li>考慮在維護窗口期間進行更新</li>
                </ul>
                
                <h3>注意事項</h3>
                <ul>
                    <li>禁用核心更新可能導致安全風險</li>
                    <li>某些更新包含重要的安全修補</li>
                    <li>長期不更新可能導致相容性問題</li>
                    <li>建議定期檢查和應用重要更新</li>
                    <li>在生產環境中謹慎使用這些設定</li>
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
        .wu-status-table { width: 100%; margin-top: 15px; border-collapse: collapse; }
        .wu-status-table th, .wu-status-table td { padding: 8px; border: 1px solid #ddd; text-align: left; }
        .wu-status-table th { background-color: #f5f5f5; }
        .wu-pending-updates { margin-top: 20px; }
        .wu-update-item { padding: 10px; margin: 5px 0; background: #f9f9f9; border-left: 4px solid #0073aa; }
        .wu-security-update { border-left-color: #d63638; }
        </style>
        <?php
    }
    
    /**
     * 顯示待更新項目
     */
    private function display_pending_updates() {
        echo '<div class="wu-pending-updates">';
        echo '<h3>待更新項目</h3>';
        
        // 檢查核心更新
        $core_updates = get_core_updates();
        if (!empty($core_updates) && $core_updates[0]->response !== 'latest') {
            echo '<div class="wu-update-item wu-security-update">';
            echo '<strong>WordPress 核心：</strong> 可更新至版本 ' . $core_updates[0]->version;
            echo '</div>';
        }
        
        // 檢查主題更新
        $theme_updates = get_theme_updates();
        if (!empty($theme_updates)) {
            foreach ($theme_updates as $theme_file => $theme_data) {
                echo '<div class="wu-update-item">';
                echo '<strong>主題：</strong> ' . $theme_data->get('Name') . ' 可更新';
                echo '</div>';
            }
        }
        
        // 檢查外掛更新
        $plugin_updates = get_plugin_updates();
        if (!empty($plugin_updates)) {
            foreach ($plugin_updates as $plugin_file => $plugin_data) {
                echo '<div class="wu-update-item">';
                echo '<strong>外掛：</strong> ' . $plugin_data->Name . ' 可更新';
                echo '</div>';
            }
        }
        
        if (empty($core_updates) && empty($theme_updates) && empty($plugin_updates)) {
            echo '<p>目前沒有可用的更新。</p>';
        }
        
        echo '</div>';
    }
    
    /**
     * 載入更新控制功能
     */
    private function load_update_controls() {
        // 禁用主題自動更新
        if (get_option('wu_disable_theme_auto_updates', false)) {
            add_filter('auto_update_theme', '__return_false');
            add_filter('themes_auto_update_enabled', '__return_false');
        }
        
        // 禁用外掛自動更新
        if (get_option('wu_disable_plugin_auto_updates', false)) {
            add_filter('auto_update_plugin', '__return_false');
            add_filter('plugins_auto_update_enabled', '__return_false');
        }
        
        // 禁用核心更新
        if (get_option('wu_disable_core_updates', false)) {
            $this->disable_core_updates();
        }
    }
    
    /**
     * 禁用核心更新
     */
    private function disable_core_updates() {
        // 禁用自動核心更新
        add_filter('automatic_updater_disabled', '__return_true');
        add_filter('auto_update_core', '__return_false');
        
        // 禁用更新檢查
        remove_action('init', 'wp_version_check');
        remove_action('admin_init', '_maybe_update_core');
        
        // 移除更新檢查的定時任務
        wp_clear_scheduled_hook('wp_version_check');
        
        // 禁用更新通知
        add_action('admin_menu', array($this, 'remove_core_update_menu'));
        add_action('network_admin_menu', array($this, 'remove_core_update_menu'));
        
        // 移除更新通知
        add_filter('pre_site_transient_update_core', array($this, 'remove_core_updates'));
        add_filter('pre_transient_update_core', array($this, 'remove_core_updates'));
        
        // 隱藏更新通知
        add_action('admin_head', array($this, 'hide_update_notices'));
    }
    
    /**
     * 移除核心更新選單
     */
    public function remove_core_update_menu() {
        remove_submenu_page('index.php', 'update-core.php');
    }
    
    /**
     * 移除核心更新數據
     */
    public function remove_core_updates() {
        return null;
    }
    
    /**
     * 隱藏更新通知
     */
    public function hide_update_notices() {
        echo '<style>
            .update-nag,
            .update-php,
            #update-nag,
            .notice.notice-warning.notice-alt,
            div.updated p:contains("WordPress"),
            .core-update-nag {
                display: none !important;
            }
        </style>';
    }
}

// 初始化模組
new WU_Update_Manager();