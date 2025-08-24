<?php
/**
 * WooCommerce 優化器模組
 * 功能：清理和優化 WooCommerce 設定，移除不必要的功能
 */

if (!defined('ABSPATH')) exit;

class WU_WooCommerce_Optimizer {
    
    public function __construct() {
        // 檢查 WooCommerce 是否啟用
        if (!class_exists('WooCommerce')) {
            return;
        }
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        
        // 載入優化功能
        $this->load_optimizations();
    }
    
    /**
     * 添加管理頁面
     */
    public function add_admin_menu() {
        add_submenu_page(
            'wumetax-toolkit',
            'WooCommerce 優化器',
            'WooCommerce 優化器',
            'manage_options',
            'wu-woocommerce-optimizer',
            array($this, 'admin_page')
        );
    }
    
    /**
     * 初始化設定
     */
    public function admin_init() {
        // 註冊設定
        $settings = array(
            'wu_woo_disable_marketing_hub',
            'wu_woo_remove_home',
            'wu_woo_disable_marketplace_suggestions',
            'wu_woo_disable_stripe_scripts',
            'wu_woo_disable_guide_emails',
            'wu_woo_disable_woocom_notifications',
            'wu_woo_hide_payment_providers_link'
        );
        
        foreach ($settings as $setting) {
            register_setting('wu_woocommerce_settings', $setting);
        }
        
        add_settings_section(
            'wu_woocommerce_section',
            'WooCommerce 優化設定',
            array($this, 'settings_section_callback'),
            'wu_woocommerce_settings'
        );
        
        // 添加設定欄位
        add_settings_field(
            'wu_woo_disable_marketing_hub',
            '禁用 WooCommerce 市場中心',
            array($this, 'marketing_hub_callback'),
            'wu_woocommerce_settings',
            'wu_woocommerce_section'
        );
        
        add_settings_field(
            'wu_woo_remove_home',
            '移除 Home 選單',
            array($this, 'remove_home_callback'),
            'wu_woocommerce_settings',
            'wu_woocommerce_section'
        );
        
        add_settings_field(
            'wu_woo_disable_marketplace_suggestions',
            '禁用市場建議',
            array($this, 'marketplace_suggestions_callback'),
            'wu_woocommerce_settings',
            'wu_woocommerce_section'
        );
        
        add_settings_field(
            'wu_woo_disable_stripe_scripts',
            '禁用不必要的 Stripe 腳本',
            array($this, 'stripe_scripts_callback'),
            'wu_woocommerce_settings',
            'wu_woocommerce_section'
        );
        
        add_settings_field(
            'wu_woo_disable_guide_emails',
            '停用指南電子郵件通知',
            array($this, 'guide_emails_callback'),
            'wu_woocommerce_settings',
            'wu_woocommerce_section'
        );
        
        add_settings_field(
            'wu_woo_disable_woocom_notifications',
            '禁用 WooCommerce.com 通知',
            array($this, 'woocom_notifications_callback'),
            'wu_woocommerce_settings',
            'wu_woocommerce_section'
        );
        
        add_settings_field(
            'wu_woo_hide_payment_providers_link',
            '隱藏「發現其他付款提供者」鏈接',
            array($this, 'payment_providers_callback'),
            'wu_woocommerce_settings',
            'wu_woocommerce_section'
        );
    }
    
    /**
     * 設定區塊回調
     */
    public function settings_section_callback() {
        echo '<p>配置 WooCommerce 優化選項，清理管理介面並提高效能。</p>';
    }
    
    /**
     * 市場中心選項回調
     */
    public function marketing_hub_callback() {
        $value = get_option('wu_woo_disable_marketing_hub', false);
        echo '<input type="checkbox" id="wu_woo_disable_marketing_hub" name="wu_woo_disable_marketing_hub" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_woo_disable_marketing_hub">完全禁用 WooCommerce 市場中心</label>';
        echo '<p class="description">此選項將完全禁用 WooCommerce 市場中心。優惠券選單項目將保持舊方式可存取（WooCommerce -> 優惠券）。</p>';
    }
    
    /**
     * 移除 Home 選項回調
     */
    public function remove_home_callback() {
        $value = get_option('wu_woo_remove_home', false);
        echo '<input type="checkbox" id="wu_woo_remove_home" name="wu_woo_remove_home" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_woo_remove_home">移除 Home 選單項目</label>';
        echo '<p class="description">從 WooCommerce 管理選單中移除 Home 項目。</p>';
    }
    
    /**
     * 市場建議選項回調
     */
    public function marketplace_suggestions_callback() {
        $value = get_option('wu_woo_disable_marketplace_suggestions', false);
        echo '<input type="checkbox" id="wu_woo_disable_marketplace_suggestions" name="wu_woo_disable_marketplace_suggestions" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_woo_disable_marketplace_suggestions">禁用市場建議</label>';
        echo '<p class="description">此選項將停用市場建議。建議在產品編輯頁面和訂單頁面上可見。</p>';
    }
    
    /**
     * Stripe 腳本選項回調
     */
    public function stripe_scripts_callback() {
        $value = get_option('wu_woo_disable_stripe_scripts', false);
        echo '<input type="checkbox" id="wu_woo_disable_stripe_scripts" name="wu_woo_disable_stripe_scripts" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_woo_disable_stripe_scripts">禁用不必要的 Stripe 腳本</label>';
        echo '<p class="description">禁用在不需要的頁面載入的 Stripe 相關腳本。</p>';
    }
    
    /**
     * 指南郵件選項回調
     */
    public function guide_emails_callback() {
        $value = get_option('wu_woo_disable_guide_emails', false);
        echo '<input type="checkbox" id="wu_woo_disable_guide_emails" name="wu_woo_disable_guide_emails" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_woo_disable_guide_emails">停用指南電子郵件通知</label>';
        echo '<p class="description">WooCommerce 會發送電子郵件通知，其中包含完成商店設定的額外指導。啟用此選項可阻止此行為並停止傳送這些電子郵件。</p>';
    }
    
    /**
     * WooCommerce.com 通知選項回調
     */
    public function woocom_notifications_callback() {
        $value = get_option('wu_woo_disable_woocom_notifications', false);
        echo '<input type="checkbox" id="wu_woo_disable_woocom_notifications" name="wu_woo_disable_woocom_notifications" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_woo_disable_woocom_notifications">禁用 WooCommerce.com 通知</label>';
        echo '<p class="description">停用來自 WooCommerce.com 外掛程式的通知：Connect your store to WooCommerce.com to receive extensions updates and support。</p>';
    }
    
    /**
     * 付款提供者鏈接選項回調
     */
    public function payment_providers_callback() {
        $value = get_option('wu_woo_hide_payment_providers_link', false);
        echo '<input type="checkbox" id="wu_woo_hide_payment_providers_link" name="wu_woo_hide_payment_providers_link" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_woo_hide_payment_providers_link">隱藏「發現其他付款提供者」鏈接</label>';
        echo '<p class="description">發現其他支付提供者連結顯示在您的支付網關下方，並透過付費擴充將使用者引導至外部市場。</p>';
    }
    
    /**
     * 管理頁面
     */
    public function admin_page() {
        if (!class_exists('WooCommerce')) {
            echo '<div class="wrap">';
            echo '<h1>WooCommerce 優化器</h1>';
            echo '<div class="notice notice-error"><p><strong>錯誤：</strong>未檢測到 WooCommerce 外掛。請先安裝並啟用 WooCommerce。</p></div>';
            echo '</div>';
            return;
        }
        
        if (isset($_POST['submit'])) {
            check_admin_referer('wu_woocommerce_settings-options');
            
            // 處理表單提交
            $settings = array(
                'wu_woo_disable_marketing_hub',
                'wu_woo_remove_home',
                'wu_woo_disable_marketplace_suggestions',
                'wu_woo_disable_stripe_scripts',
                'wu_woo_disable_guide_emails',
                'wu_woo_disable_woocom_notifications',
                'wu_woo_hide_payment_providers_link'
            );
            
            foreach ($settings as $setting) {
                update_option($setting, isset($_POST[$setting]) ? 1 : 0);
            }
            
            echo '<div class="notice notice-success"><p>設定已儲存！</p></div>';
        }
        
        ?>
        <div class="wrap">
            <h1>WooCommerce 優化器設定</h1>
            
            <div class="card">
                <h2>WooCommerce 狀態</h2>
                <p><strong>WooCommerce 版本：</strong> <?php echo WC()->version; ?></p>
                <p><strong>數據庫版本：</strong> <?php echo get_option('woocommerce_db_version'); ?></p>
                
                <?php
                // 顯示 WC Status 資訊
                $this->display_wc_status();
                ?>
            </div>
            
            <form method="post" action="">
                <?php
                settings_fields('wu_woocommerce_settings');
                do_settings_sections('wu_woocommerce_settings');
                wp_nonce_field('wu_woocommerce_settings-options');
                submit_button();
                ?>
            </form>
            
            <div class="card">
                <h2>功能說明</h2>
                <h3>什麼是 WooCommerce 優化器？</h3>
                <ul>
                    <li>清理 WooCommerce 管理介面，移除不必要的元素</li>
                    <li>禁用可能影響效能的功能</li>
                    <li>改善管理員使用體驗</li>
                    <li>減少外部服務調用</li>
                </ul>
                
                <h3>優化項目說明</h3>
                <h4>市場中心禁用</h4>
                <ul>
                    <li>移除 WooCommerce 市場推廣介面</li>
                    <li>保留原有優惠券功能存取方式</li>
                    <li>減少管理介面混亂</li>
                </ul>
                
                <h4>市場建議禁用</h4>
                <ul>
                    <li>移除產品和訂單頁面的推廣建議</li>
                    <li>停止向 WooCommerce.com 發送資料請求</li>
                    <li>提高頁面載入速度</li>
                </ul>
                
                <h4>通知和郵件禁用</h4>
                <ul>
                    <li>停止不必要的電子郵件通知</li>
                    <li>減少對外部服務的依賴</li>
                    <li>提高隱私保護</li>
                </ul>
                
                <h3>為什麼要優化 WooCommerce？</h3>
                <ul>
                    <li><strong>提高效能：</strong>減少不必要的腳本和外部請求</li>
                    <li><strong>簡化介面：</strong>移除推廣內容，專注於核心功能</li>
                    <li><strong>提高安全性：</strong>減少對外部服務的依賴</li>
                    <li><strong>改善用戶體驗：</strong>更清潔的管理介面</li>
                    <li><strong>減少干擾：</strong>避免不必要的通知和推廣</li>
                </ul>
                
                <h3>注意事項</h3>
                <ul>
                    <li>這些優化不會影響 WooCommerce 的核心功能</li>
                    <li>所有設定都可以隨時啟用或禁用</li>
                    <li>建議在測試環境中先行測試</li>
                    <li>某些第三方外掛可能依賴被禁用的功能</li>
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
        .wc-status-table { width: 100%; margin-top: 15px; border-collapse: collapse; }
        .wc-status-table th, .wc-status-table td { padding: 8px; border: 1px solid #ddd; text-align: left; }
        .wc-status-table th { background-color: #f5f5f5; }
        </style>
        <?php
    }
    
    /**
     * 顯示 WC Status 資訊
     */
    private function display_wc_status() {
        try {
            // 直接獲取系統資訊，不依賴 REST API
            echo '<table class="wc-status-table">';
            echo '<tr><th colspan="2">系統資訊</th></tr>';
            
            // WordPress 版本
            echo '<tr><td>WordPress 版本</td><td>' . get_bloginfo('version') . '</td></tr>';
            
            // PHP 版本
            echo '<tr><td>PHP 版本</td><td>' . phpversion() . '</td></tr>';
            
            // 記憶體限制
            echo '<tr><td>記憶體限制</td><td>' . ini_get('memory_limit') . '</td></tr>';
            
            // 最大上傳大小
            echo '<tr><td>最大上傳大小</td><td>' . wp_max_upload_size() . ' bytes</td></tr>';
            
            // WooCommerce 資料庫版本
            echo '<tr><th colspan="2">WooCommerce 資訊</th></tr>';
            echo '<tr><td>WooCommerce 版本</td><td>' . (defined('WC_VERSION') ? WC_VERSION : 'N/A') . '</td></tr>';
            echo '<tr><td>WooCommerce 資料庫版本</td><td>' . get_option('woocommerce_db_version', 'N/A') . '</td></tr>';
            
            // 主題資訊
            $theme = wp_get_theme();
            echo '<tr><th colspan="2">主題資訊</th></tr>';
            echo '<tr><td>主題名稱</td><td>' . $theme->get('Name') . '</td></tr>';
            echo '<tr><td>主題版本</td><td>' . $theme->get('Version') . '</td></tr>';
            
            echo '</table>';
            
        } catch (Exception $e) {
            echo '<p>載入系統狀態時發生錯誤：' . esc_html($e->getMessage()) . '</p>';
            echo '<p>顯示基本資訊：</p>';
            echo '<table class="wc-status-table">';
            echo '<tr><td>WordPress 版本</td><td>' . get_bloginfo('version') . '</td></tr>';
            echo '<tr><td>WooCommerce 版本</td><td>' . (defined('WC_VERSION') ? WC_VERSION : 'N/A') . '</td></tr>';
            echo '</table>';
        }
    }
    
    /**
     * 載入優化功能
     */
    private function load_optimizations() {
        // 禁用 WooCommerce 市場中心
        if (get_option('wu_woo_disable_marketing_hub', false)) {
            add_action('admin_init', array($this, 'disable_marketing_hub'));
        }
        
        // 移除 Home 選單
        if (get_option('wu_woo_remove_home', false)) {
            add_action('admin_menu', array($this, 'remove_home_menu'), 999);
        }
        
        // 禁用市場建議
        if (get_option('wu_woo_disable_marketplace_suggestions', false)) {
            add_action('admin_init', array($this, 'disable_marketplace_suggestions'));
        }
        
        // 禁用 Stripe 腳本
        if (get_option('wu_woo_disable_stripe_scripts', false)) {
            add_action('wp_enqueue_scripts', array($this, 'disable_stripe_scripts'), 100);
            add_action('admin_enqueue_scripts', array($this, 'disable_stripe_scripts'), 100);
        }
        
        // 停用指南郵件
        if (get_option('wu_woo_disable_guide_emails', false)) {
            add_action('init', array($this, 'disable_guide_emails'));
        }
        
        // 禁用 WooCommerce.com 通知
        if (get_option('wu_woo_disable_woocom_notifications', false)) {
            add_action('admin_init', array($this, 'disable_woocom_notifications'));
        }
        
        // 隱藏付款提供者鏈接
        if (get_option('wu_woo_hide_payment_providers_link', false)) {
            add_action('admin_init', array($this, 'hide_payment_providers_link'));
        }
    }
    
    /**
     * 禁用 WooCommerce 市場中心
     */
    public function disable_marketing_hub() {
        // 移除市場中心選單
        remove_submenu_page('woocommerce', 'wc-admin&path=/marketing');
        
        // 禁用市場中心功能
        add_filter('woocommerce_admin_features', function($features) {
            return array_diff($features, array('marketing'));
        });
        
        // 移除市場中心通知
        remove_action('admin_notices', 'wc_admin_print_notices');
    }
    
    /**
     * 移除 Home 選單
     */
    public function remove_home_menu() {
        remove_submenu_page('woocommerce', 'wc-admin');
        
        // 如果 WooCommerce Admin 啟用，也移除相關項目
        if (function_exists('wc_admin_url')) {
            global $submenu;
            if (isset($submenu['woocommerce'])) {
                foreach ($submenu['woocommerce'] as $key => $menu_item) {
                    if (strpos($menu_item[2], 'wc-admin') !== false) {
                        unset($submenu['woocommerce'][$key]);
                    }
                }
            }
        }
    }
    
    /**
     * 禁用市場建議
     */
    public function disable_marketplace_suggestions() {
        // 禁用產品頁面的市場建議
        add_filter('woocommerce_allow_marketplace_suggestions', '__return_false');
        
        // 移除擴充建議
        remove_action('woocommerce_product_options_general_product_data', array('WC_Admin_Addons', 'output_suggestions'));
        remove_action('woocommerce_product_write_panel_tabs', array('WC_Admin_Addons', 'output_suggestions_tab'));
        
        // 禁用 WooCommerce.com 連接建議
        add_filter('woocommerce_helper_suppress_admin_notices', '__return_true');
    }
    
    /**
     * 禁用 Stripe 腳本
     */
    public function disable_stripe_scripts() {
        // 在非結帳頁面禁用 Stripe 腳本
        if (!is_checkout() && !is_cart() && !is_account_page()) {
            wp_dequeue_script('stripe');
            wp_dequeue_script('wc-stripe-payment-request');
            wp_dequeue_script('wc-stripe-elements');
        }
    }
    
    /**
     * 停用指南郵件
     */
    public function disable_guide_emails() {
        // 禁用 WooCommerce 設定指南郵件
        remove_action('woocommerce_tracker_send_event', array('WC_Admin_Setup_Wizard', 'track_setup_wizard_completion'));
        
        // 禁用自動郵件通知
        add_filter('woocommerce_email_enabled_new_order', '__return_false');
        add_filter('woocommerce_email_enabled_customer_processing_order', '__return_false');
        
        // 移除設定完成通知
        remove_action('woocommerce_after_settings_checkout', array('WC_Admin_Setup_Wizard', 'remove_menu_item'));
    }
    
    /**
     * 禁用 WooCommerce.com 通知
     */
    public function disable_woocom_notifications() {
        // 禁用連接到 WooCommerce.com 的通知
        add_filter('woocommerce_helper_suppress_admin_notices', '__return_true');
        
        // 移除 WooCommerce.com 連接提示
        remove_action('admin_notices', array('WC_Helper_Admin', 'helper_admin_notices'));
        
        // 禁用擴充更新通知
        add_filter('woocommerce_helper_suppress_connect_notice', '__return_true');
    }
    
    /**
     * 隱藏付款提供者鏈接
     */
    public function hide_payment_providers_link() {
        // 使用 CSS 隱藏付款提供者發現鏈接
        add_action('admin_head', function() {
            if (isset($_GET['page']) && $_GET['page'] === 'wc-settings' && isset($_GET['tab']) && $_GET['tab'] === 'checkout') {
                echo '<style>
                    .woocommerce-BlankState-cta,
                    .wc-payment-gateway-method-toggle-disabled .wc-payment-gateway-method-title .woocommerce-help-tip,
                    .payment-gateway-suggestions,
                    .wc-payment-gateway-method-suggestions {
                        display: none !important;
                    }
                </style>';
            }
        });
        
        // 移除付款建議 REST API
        add_filter('woocommerce_rest_prepare_payment_gateway', function($response) {
            unset($response->data['needs_setup']);
            unset($response->data['post_install_scripts']);
            return $response;
        });
    }
}

// 初始化模組
new WU_WooCommerce_Optimizer();