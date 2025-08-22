<?php
/**
 * WooCommerce 清理模組
 * 功能：移除 WooCommerce 不必要的通知和儀表板項目
 */

if (!defined('ABSPATH')) exit;

class WU_WooCommerce_Cleaner {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        
        // 檢查是否安裝了 WooCommerce
        if (class_exists('WooCommerce')) {
            // 如果啟用了清理功能，則執行相關動作
            if (get_option('wu_remove_wc_connect_notice', false)) {
                $this->remove_connect_notices();
            }
            
            if (get_option('wu_remove_wc_skyverge_dashboard', false)) {
                $this->remove_skyverge_dashboard();
            }
            
            if (get_option('wu_remove_wc_marketing_hub', false)) {
                $this->remove_marketing_hub();
            }
        }
    }
    
    /**
     * 添加管理頁面
     */
    public function add_admin_menu() {
        add_submenu_page(
            'wu-toolbox',
            'WooCommerce 清理',
            'WooCommerce 清理',
            'manage_options',
            'wu-woocommerce-cleaner',
            array($this, 'admin_page')
        );
    }
    
    /**
     * 初始化設定
     */
    public function admin_init() {
        register_setting('wu_wc_cleaner_settings', 'wu_remove_wc_connect_notice');
        register_setting('wu_wc_cleaner_settings', 'wu_remove_wc_skyverge_dashboard');
        register_setting('wu_wc_cleaner_settings', 'wu_remove_wc_marketing_hub');
        
        add_settings_section(
            'wu_wc_cleaner_section',
            'WooCommerce 清理設定',
            array($this, 'settings_section_callback'),
            'wu_wc_cleaner_settings'
        );
        
        add_settings_field(
            'wu_remove_wc_connect_notice',
            '移除連接商店通知',
            array($this, 'remove_connect_notice_callback'),
            'wu_wc_cleaner_settings',
            'wu_wc_cleaner_section'
        );
        
        add_settings_field(
            'wu_remove_wc_skyverge_dashboard',
            '移除 SkyVerge 儀表板',
            array($this, 'remove_skyverge_callback'),
            'wu_wc_cleaner_settings',
            'wu_wc_cleaner_section'
        );
        
        add_settings_field(
            'wu_remove_wc_marketing_hub',
            '移除行銷中心',
            array($this, 'remove_marketing_hub_callback'),
            'wu_wc_cleaner_settings',
            'wu_wc_cleaner_section'
        );
    }
    
    /**
     * 設定區域說明
     */
    public function settings_section_callback() {
        if (!class_exists('WooCommerce')) {
            echo '<div class="notice notice-warning"><p><strong>注意：</strong>未檢測到 WooCommerce 外掛程式。此模組僅在安裝 WooCommerce 時有效。</p></div>';
            return;
        }
        
        echo '<p>WooCommerce 清理功能可以幫助您移除不必要的通知和儀表板項目，讓管理界面更加簡潔。</p>';
        echo '<p><strong>建議：</strong>移除這些項目可以減少視覺干擾，提高管理效率。</p>';
    }
    
    /**
     * 移除連接商店通知選項回調
     */
    public function remove_connect_notice_callback() {
        $value = get_option('wu_remove_wc_connect_notice', false);
        echo '<input type="checkbox" id="wu_remove_wc_connect_notice" name="wu_remove_wc_connect_notice" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_remove_wc_connect_notice">移除「連接您的商店」通知</label>';
        echo '<p class="description">移除 WooCommerce 要求連接商店到 WooCommerce.com 的通知。</p>';
    }
    
    /**
     * 移除 SkyVerge 儀表板選項回調
     */
    public function remove_skyverge_callback() {
        $value = get_option('wu_remove_wc_skyverge_dashboard', false);
        echo '<input type="checkbox" id="wu_remove_wc_skyverge_dashboard" name="wu_remove_wc_skyverge_dashboard" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_remove_wc_skyverge_dashboard">移除 SkyVerge 儀表板項目</label>';
        echo '<p class="description">移除由 SkyVerge 外掛程式添加的儀表板小工具和通知。</p>';
    }
    
    /**
     * 移除行銷中心選項回調
     */
    public function remove_marketing_hub_callback() {
        $value = get_option('wu_remove_wc_marketing_hub', false);
        echo '<input type="checkbox" id="wu_remove_wc_marketing_hub" name="wu_remove_wc_marketing_hub" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_remove_wc_marketing_hub">移除行銷中心</label>';
        echo '<p class="description">移除 WooCommerce 行銷中心和相關的促銷通知。</p>';
    }
    
    /**
     * 管理頁面
     */
    public function admin_page() {
        if (isset($_POST['submit'])) {
            check_admin_referer('wu_wc_cleaner_settings-options');
            
            // 處理表單提交
            update_option('wu_remove_wc_connect_notice', isset($_POST['wu_remove_wc_connect_notice']) ? 1 : 0);
            update_option('wu_remove_wc_skyverge_dashboard', isset($_POST['wu_remove_wc_skyverge_dashboard']) ? 1 : 0);
            update_option('wu_remove_wc_marketing_hub', isset($_POST['wu_remove_wc_marketing_hub']) ? 1 : 0);
            
            echo '<div class="notice notice-success"><p>設定已儲存！</p></div>';
        }
        
        $wc_installed = class_exists('WooCommerce');
        $wc_version = $wc_installed ? WC()->version : 'N/A';
        ?>
        <div class="wrap">
            <h1>WooCommerce 清理設定</h1>
            
            <div class="card">
                <h2>當前狀態</h2>
                <p><strong>WooCommerce 狀態：</strong> 
                    <span class="<?php echo $wc_installed ? 'wu-status-installed' : 'wu-status-not-installed'; ?>">
                        <?php echo $wc_installed ? '已安裝' : '未安裝'; ?>
                    </span>
                </p>
                <?php if ($wc_installed): ?>
                <p><strong>WooCommerce 版本：</strong> <?php echo esc_html($wc_version); ?></p>
                <?php endif; ?>
            </div>
            
            <?php if ($wc_installed): ?>
            <form method="post" action="">
                <?php
                settings_fields('wu_wc_cleaner_settings');
                do_settings_sections('wu_wc_cleaner_settings');
                wp_nonce_field('wu_wc_cleaner_settings-options');
                submit_button();
                ?>
            </form>
            <?php endif; ?>
            
            <div class="card">
                <h2>功能說明</h2>
                <h3>什麼是 WooCommerce 清理？</h3>
                <ul>
                    <li>移除 WooCommerce 產生的不必要通知和儀表板項目</li>
                    <li>減少管理界面的視覺干擾</li>
                    <li>提高管理效率和用戶體驗</li>
                </ul>
                
                <h3>清理項目說明</h3>
                <h4>連接商店通知</h4>
                <ul>
                    <li>WooCommerce 經常顯示要求連接到 WooCommerce.com 的通知</li>
                    <li>這些通知對於不需要官方服務的用戶來說是多餘的</li>
                    <li>移除後不會影響商店的基本功能</li>
                </ul>
                
                <h4>SkyVerge 儀表板</h4>
                <ul>
                    <li>SkyVerge 是 WooCommerce 的擴展開發商</li>
                    <li>他們的外掛程式會添加儀表板小工具和通知</li>
                    <li>移除這些項目可以讓儀表板更加簡潔</li>
                </ul>
                
                <h4>行銷中心</h4>
                <ul>
                    <li>WooCommerce 的行銷中心包含各種促銷和廣告</li>
                    <li>對於不需要這些服務的用戶來說是干擾</li>
                    <li>移除後可以專注於核心電商功能</li>
                </ul>
                
                <h3>技術實現</h3>
                <ul>
                    <li>使用 WordPress 鉤子系統安全移除通知</li>
                    <li>通過 CSS 隱藏或 JavaScript 移除不必要的元素</li>
                    <li>不會修改 WooCommerce 核心檔案</li>
                    <li>可隨時啟用或禁用，不影響商店功能</li>
                </ul>
            </div>
        </div>
        
        <style>
        .wu-status-installed { color: #00a32a; font-weight: bold; }
        .wu-status-not-installed { color: #d63638; font-weight: bold; }
        .card { background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin: 20px 0; }
        .card h2 { margin-top: 0; }
        .card h3, .card h4 { color: #23282d; }
        .card ul { margin-left: 20px; }
        </style>
        <?php
    }
    
    /**
     * 移除連接商店通知
     */
    private function remove_connect_notices() {
        // 移除 WooCommerce 連接通知
        add_action('admin_init', array($this, 'remove_wc_connect_notices'));
        
        // 移除 WooCommerce 設定助手
        add_filter('woocommerce_enable_setup_wizard', '__return_false');
        
        // 移除 WooCommerce 狀態元框
        add_action('wp_dashboard_setup', array($this, 'remove_wc_dashboard_widgets'));
    }
    
    /**
     * 移除 WooCommerce 連接通知
     */
    public function remove_wc_connect_notices() {
        // 移除各種 WooCommerce 通知
        remove_action('admin_notices', array('WC_Admin_Notices', 'show_notices'));
        
        // 移除特定通知
        if (class_exists('WC_Admin_Notices')) {
            WC_Admin_Notices::remove_notice('install');
            WC_Admin_Notices::remove_notice('update');
            WC_Admin_Notices::remove_notice('template_files');
        }
    }
    
    /**
     * 移除 WooCommerce 儀表板小工具
     */
    public function remove_wc_dashboard_widgets() {
        remove_meta_box('woocommerce_dashboard_status', 'dashboard', 'normal');
        remove_meta_box('woocommerce_dashboard_recent_reviews', 'dashboard', 'normal');
    }
    
    /**
     * 移除 SkyVerge 儀表板
     */
    private function remove_skyverge_dashboard() {
        // 移除 SkyVerge 儀表板小工具
        add_action('wp_dashboard_setup', array($this, 'remove_skyverge_widgets'));
        
        // 隱藏 SkyVerge 相關元素
        add_action('admin_head', array($this, 'hide_skyverge_elements'));
    }
    
    /**
     * 移除 SkyVerge 小工具
     */
    public function remove_skyverge_widgets() {
        // 移除常見的 SkyVerge 儀表板小工具
        $skyverge_widgets = array(
            'skyverge_dashboard_widget',
            'wc_skyverge_dashboard_widget',
            'skyverge_news_widget'
        );
        
        foreach ($skyverge_widgets as $widget) {
            remove_meta_box($widget, 'dashboard', 'normal');
            remove_meta_box($widget, 'dashboard', 'side');
        }
    }
    
    /**
     * 隱藏 SkyVerge 元素
     */
    public function hide_skyverge_elements() {
        echo '<style>
        .skyverge-dashboard-widget,
        .wc-skyverge-dashboard-widget,
        [id*="skyverge"],
        [class*="skyverge"] {
            display: none !important;
        }
        </style>';
    }
    
    /**
     * 移除行銷中心
     */
    private function remove_marketing_hub() {
        // 移除行銷選單項目
        add_action('admin_menu', array($this, 'remove_marketing_menu'), 999);
        
        // 移除行銷通知
        add_action('admin_init', array($this, 'remove_marketing_notices'));
        
        // 隱藏行銷相關元素
        add_action('admin_head', array($this, 'hide_marketing_elements'));
    }
    
    /**
     * 移除行銷選單
     */
    public function remove_marketing_menu() {
        remove_submenu_page('woocommerce', 'wc-admin&path=/marketing');
        remove_menu_page('woocommerce-marketing');
    }
    
    /**
     * 移除行銷通知
     */
    public function remove_marketing_notices() {
        // 移除行銷相關的管理通知
        add_filter('woocommerce_admin_features', array($this, 'disable_wc_admin_features'));
    }
    
    /**
     * 禁用 WooCommerce 管理功能
     */
    public function disable_wc_admin_features($features) {
        // 移除行銷功能
        $marketing_features = array('marketing', 'remote-inbox-notifications', 'remote-free-extensions');
        
        return array_diff($features, $marketing_features);
    }
    
    /**
     * 隱藏行銷元素
     */
    public function hide_marketing_elements() {
        echo '<style>
        .woocommerce-marketing,
        .wc-admin-marketing,
        [href*="wc-admin&path=/marketing"],
        .woocommerce-inbox-message {
            display: none !important;
        }
        </style>';
    }
}

// 初始化模組
new WU_WooCommerce_Cleaner();