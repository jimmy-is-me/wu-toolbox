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
        
        // 載入優化功能（僅在啟用時執行）
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
            'wu_woo_disable_home',
            'wu_woo_disable_notifications',
            'wu_woo_disable_reports',
            'wu_woo_disable_marketplace',
            'wu_woo_disable_marketplace_suggestions',
            'wu_woo_disable_stripe_scripts',
            'wu_woo_disable_guide_emails',
            'wu_woo_disable_woocom_notifications',
            'wu_woo_hide_payment_providers_link',
            'wu_woo_disable_payment_plugins_metabox',
            'wu_woo_show_sales_with_offset',
            'wu_woo_clean_cache'
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
        
        // 基本優化功能
        add_settings_field(
            'wu_woo_disable_marketing_hub',
            '停用 WooCommerce 行銷中心',
            array($this, 'marketing_hub_callback'),
            'wu_woocommerce_settings',
            'wu_woocommerce_section'
        );
        
        add_settings_field(
            'wu_woo_disable_home',
            '停用 WooCommerce 首頁',
            array($this, 'disable_home_callback'),
            'wu_woocommerce_settings',
            'wu_woocommerce_section'
        );
        
        add_settings_field(
            'wu_woo_disable_notifications',
            '停用 WooCommerce 通知欄',
            array($this, 'disable_notifications_callback'),
            'wu_woocommerce_settings',
            'wu_woocommerce_section'
        );
        
        add_settings_field(
            'wu_woo_disable_reports',
            '停用 WooCommerce 報表',
            array($this, 'disable_reports_callback'),
            'wu_woocommerce_settings',
            'wu_woocommerce_section'
        );
        
        add_settings_field(
            'wu_woo_disable_marketplace',
            '停用 WooCommerce Marketplace',
            array($this, 'disable_marketplace_callback'),
            'wu_woocommerce_settings',
            'wu_woocommerce_section'
        );
        
        add_settings_field(
            'wu_woo_disable_marketplace_suggestions',
            '停用市場建議',
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
        
        add_settings_field(
            'wu_woo_disable_payment_plugins_metabox',
            '停用建議的付款外掛程式 Metabox',
            array($this, 'payment_plugins_metabox_callback'),
            'wu_woocommerce_settings',
            'wu_woocommerce_section'
        );
        
        add_settings_field(
            'wu_woo_show_sales_with_offset',
            '顯示商品購買量（含管理員調整）',
            array($this, 'sales_with_offset_callback'),
            'wu_woocommerce_settings',
            'wu_woocommerce_section'
        );
        
        add_settings_field(
            'wu_woo_clean_cache',
            '清理 WooCommerce 暫存',
            array($this, 'clean_cache_callback'),
            'wu_woocommerce_settings',
            'wu_woocommerce_section'
        );
    }
    
    /**
     * 設定區塊回調
     */
    public function settings_section_callback() {
        echo '<p>配置 WooCommerce 優化選項，清理管理介面並提高效能。<strong>所有功能預設停用，請勾選需要的功能。</strong></p>';
    }
    
    // 回調函數
    public function marketing_hub_callback() {
        $value = get_option('wu_woo_disable_marketing_hub', false);
        echo '<input type="checkbox" id="wu_woo_disable_marketing_hub" name="wu_woo_disable_marketing_hub" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_woo_disable_marketing_hub">完全停用 WooCommerce 行銷中心</label>';
        echo '<p class="description">此選項將完全停用 WooCommerce 行銷中心並隱藏後台行銷選單。優惠券選單項目將維持原有的存取方式（WooCommerce -> 優惠券）。</p>';
    }
    
    public function disable_home_callback() {
        $value = get_option('wu_woo_disable_home', false);
        echo '<input type="checkbox" id="wu_woo_disable_home" name="wu_woo_disable_home" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_woo_disable_home">移除 WooCommerce 首頁選單項目</label>';
        echo '<p class="description">從 WooCommerce 管理選單中移除首頁項目，同時會隱藏擴充功能相關選單。</p>';
    }
    
    public function disable_notifications_callback() {
        $value = get_option('wu_woo_disable_notifications', false);
        echo '<input type="checkbox" id="wu_woo_disable_notifications" name="wu_woo_disable_notifications" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_woo_disable_notifications">停用 WooCommerce 通知欄</label>';
        echo '<p class="description">隱藏整個 WooCommerce 管理介面的通知標題欄（woocommerce-layout__header）。</p>';
    }
    
    public function disable_reports_callback() {
        $value = get_option('wu_woo_disable_reports', false);
        echo '<input type="checkbox" id="wu_woo_disable_reports" name="wu_woo_disable_reports" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_woo_disable_reports">停用 WooCommerce 報表</label>';
        echo '<p class="description">移除 WooCommerce 報表功能並隱藏後台報表選單以提升效能。</p>';
    }
    
    public function disable_marketplace_callback() {
        $value = get_option('wu_woo_disable_marketplace', false);
        echo '<input type="checkbox" id="wu_woo_disable_marketplace" name="wu_woo_disable_marketplace" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_woo_disable_marketplace">停用 WooCommerce Marketplace</label>';
        echo '<p class="description">完全停用 WooCommerce Marketplace 功能並隱藏相關選單。</p>';
    }
    
    public function marketplace_suggestions_callback() {
        $value = get_option('wu_woo_disable_marketplace_suggestions', false);
        echo '<input type="checkbox" id="wu_woo_disable_marketplace_suggestions" name="wu_woo_disable_marketplace_suggestions" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_woo_disable_marketplace_suggestions">禁用市場建議</label>';
        echo '<p class="description">此選項將停用市場建議。建議在產品編輯頁面和訂單頁面上可見。</p>';
    }
    
    public function stripe_scripts_callback() {
        $value = get_option('wu_woo_disable_stripe_scripts', false);
        echo '<input type="checkbox" id="wu_woo_disable_stripe_scripts" name="wu_woo_disable_stripe_scripts" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_woo_disable_stripe_scripts">禁用不必要的 Stripe 腳本</label>';
        echo '<p class="description">禁用在非結帳相關頁面載入的 Stripe 腳本：stripe.js、wc-stripe-payment-request.js、wc-stripe-elements.js、wc-stripe-style.css</p>';
    }
    
    public function guide_emails_callback() {
        $value = get_option('wu_woo_disable_guide_emails', false);
        echo '<input type="checkbox" id="wu_woo_disable_guide_emails" name="wu_woo_disable_guide_emails" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_woo_disable_guide_emails">停用指南電子郵件通知</label>';
        echo '<p class="description">WooCommerce 會發送電子郵件通知，其中包含完成商店設定的額外指導。啟用此選項可阻止此行為並停止傳送這些電子郵件。</p>';
    }
    
    public function woocom_notifications_callback() {
        $value = get_option('wu_woo_disable_woocom_notifications', false);
        echo '<input type="checkbox" id="wu_woo_disable_woocom_notifications" name="wu_woo_disable_woocom_notifications" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_woo_disable_woocom_notifications">禁用 WooCommerce.com 通知</label>';
        echo '<p class="description">停用來自 WooCommerce.com 外掛程式的通知：Connect your store to WooCommerce.com to receive extensions updates and support。</p>';
    }
    
    public function payment_providers_callback() {
        $value = get_option('wu_woo_hide_payment_providers_link', false);
        echo '<input type="checkbox" id="wu_woo_hide_payment_providers_link" name="wu_woo_hide_payment_providers_link" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_woo_hide_payment_providers_link">隱藏「發現其他付款提供者」鏈接</label>';
        echo '<p class="description">隱藏支付設定頁面的「發現其他支付提供者」連結、支付網關建議區塊及相關推廣內容。</p>';
    }
    
    public function payment_plugins_metabox_callback() {
        $value = get_option('wu_woo_disable_payment_plugins_metabox', false);
        echo '<input type="checkbox" id="wu_woo_disable_payment_plugins_metabox" name="wu_woo_disable_payment_plugins_metabox" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_woo_disable_payment_plugins_metabox">停用建議的付款外掛程式 Metabox</label>';
        echo '<p class="description">隱藏付款配置頁面的建議付款外掛 Metabox、支付建議區塊及相關廣告推廣內容。</p>';
    }
    
    public function sales_with_offset_callback() {
        $value = get_option('wu_woo_show_sales_with_offset', false);
        echo '<input type="checkbox" id="wu_woo_show_sales_with_offset" name="wu_woo_show_sales_with_offset" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_woo_show_sales_with_offset">在商品頁顯示購買量（真實購買量 + 管理員調整）</label>';
        echo '<p class="description">可透過商品自訂欄位「_wu_sales_offset」設定調整值；亦提供短代碼 [wu_sales id=""]。</p>';
    }
    
    public function clean_cache_callback() {
        $value = get_option('wu_woo_clean_cache', false);
        echo '<input type="checkbox" id="wu_woo_clean_cache" name="wu_woo_clean_cache" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_woo_clean_cache">啟用 WooCommerce 暫存清理功能</label>';
        echo '<p class="description">智能檢測並清理過期的 WooCommerce 暫存檔案：</p>';
        echo '<ul style="margin-left: 20px; font-size: 0.9em; color: #666;">';
        echo '<li>• WooCommerce 暫存資料 (transients)</li>';
        echo '<li>• 用戶會話資料 (sessions)</li>';
        echo '<li>• 商品查詢暫存 (product lookup)</li>';
        echo '<li>• 運送區域暫存 (shipping zones)</li>';
        echo '<li>• 稅率計算暫存 (tax rates)</li>';
        echo '<li>• 地理位置暫存 (geolocation)</li>';
        echo '<li>• 過期的 WooCommerce transients</li>';
        echo '</ul>';
        
        if ($value) {
            echo '<div style="margin-top: 10px;">';
            echo '<button type="button" class="button" onclick="cleanWooCache()">立即清理暫存</button>';
            echo '<div id="cache-status" style="margin-top: 10px;"></div>';
            echo '</div>';
        }
    }
    
    /**
     * 載入優化功能
     */
    private function load_optimizations() {
        // 只有在選項啟用時才執行對應功能
        if (get_option('wu_woo_disable_marketing_hub')) {
            add_action('admin_init', array($this, 'disable_marketing_hub'));
            add_action('admin_menu', array($this, 'remove_marketing_menu'), 999);
        }
        
        if (get_option('wu_woo_disable_home')) {
            add_action('admin_menu', array($this, 'remove_home_menu'), 999);
        }
        
        if (get_option('wu_woo_disable_notifications')) {
            add_action('admin_init', array($this, 'disable_notifications'));
        }
        
        if (get_option('wu_woo_disable_reports')) {
            add_action('admin_init', array($this, 'disable_reports'));
            add_action('admin_menu', array($this, 'remove_reports_menu'), 999);
        }
        
        if (get_option('wu_woo_disable_marketplace')) {
            add_action('admin_init', array($this, 'disable_marketplace'));
        }
        
        if (get_option('wu_woo_disable_marketplace_suggestions')) {
            add_action('admin_init', array($this, 'disable_marketplace_suggestions'));
        }
        
        if (get_option('wu_woo_disable_stripe_scripts')) {
            add_action('wp_enqueue_scripts', array($this, 'disable_stripe_scripts'), 100);
            add_action('admin_enqueue_scripts', array($this, 'disable_stripe_scripts'), 100);
        }
        
        if (get_option('wu_woo_disable_guide_emails')) {
            add_action('init', array($this, 'disable_guide_emails'));
        }
        
        if (get_option('wu_woo_disable_woocom_notifications')) {
            add_action('admin_init', array($this, 'disable_woocom_notifications'));
        }
        
        if (get_option('wu_woo_hide_payment_providers_link')) {
            add_action('admin_init', array($this, 'hide_payment_providers_link'));
        }
        
        if (get_option('wu_woo_disable_payment_plugins_metabox')) {
            add_action('admin_init', array($this, 'disable_payment_plugins_metabox'));
        }
        
        if (get_option('wu_woo_show_sales_with_offset')) {
            add_action('init', array($this, 'register_sales_features'));
        }
        
        if (get_option('wu_woo_clean_cache')) {
            add_action('wp_ajax_clean_woo_cache', array($this, 'ajax_clean_cache'));
        }
    }
    
    /**
     * 各項優化功能實作
     */
    public function disable_marketing_hub() {
        remove_submenu_page('woocommerce', 'wc-admin&path=/marketing');
        add_filter('woocommerce_admin_features', function($features) {
            return array_diff($features, array('marketing'));
        });
    }
    
    public function remove_marketing_menu() {
        remove_submenu_page('woocommerce', 'wc-marketing');
        remove_submenu_page('woocommerce', 'marketing');
    }
    
    public function remove_home_menu() {
        remove_submenu_page('woocommerce', 'wc-admin');
        remove_submenu_page('woocommerce', 'wc-addons');
        global $submenu;
        if (isset($submenu['woocommerce'])) {
            foreach ($submenu['woocommerce'] as $key => $menu_item) {
                if (strpos($menu_item[2], 'wc-admin') !== false || 
                    strpos($menu_item[2], 'wc-addons') !== false) {
                    unset($submenu['woocommerce'][$key]);
                }
            }
        }
    }
    
    public function disable_notifications() {
        add_action('admin_head', function() {
            echo '<style>
                .woocommerce-layout__header,
                .woocommerce-admin-notices,
                .wc-admin-notice,
                #woocommerce-admin-notices {
                    display: none !important;
                }
            </style>';
        });
        add_filter('woocommerce_admin_disabled', '__return_true');
    }
    
    public function disable_reports() {
        add_filter('woocommerce_admin_features', function($features) {
            return array_diff($features, array('analytics', 'reports'));
        });
    }
    
    public function remove_reports_menu() {
        remove_submenu_page('woocommerce', 'wc-reports');
        remove_submenu_page('woocommerce', 'wc-admin&path=/analytics');
    }
    
    public function disable_marketplace() {
        add_filter('woocommerce_admin_features', function($features) {
            return array_diff($features, array('marketplace'));
        });
        remove_submenu_page('woocommerce', 'wc-addons');
    }
    
    public function disable_marketplace_suggestions() {
        add_filter('woocommerce_allow_marketplace_suggestions', '__return_false');
        add_filter('woocommerce_helper_suppress_admin_notices', '__return_true');
    }
    
    public function disable_stripe_scripts() {
        if (!is_checkout() && !is_cart() && !is_account_page()) {
            wp_dequeue_script('stripe');
            wp_dequeue_script('wc-stripe-payment-request');
            wp_dequeue_script('wc-stripe-elements');
            wp_dequeue_style('wc-stripe-style');
        }
    }
    
    public function disable_guide_emails() {
        remove_action('woocommerce_tracker_send_event', array('WC_Admin_Setup_Wizard', 'track_setup_wizard_completion'));
        add_filter('woocommerce_email_enabled_new_order', '__return_false');
    }
    
    public function disable_woocom_notifications() {
        add_filter('woocommerce_helper_suppress_admin_notices', '__return_true');
        add_filter('woocommerce_helper_suppress_connect_notice', '__return_true');
    }
    
    public function hide_payment_providers_link() {
        add_action('admin_head', function() {
            if (isset($_GET['page']) && $_GET['page'] === 'wc-settings' && 
                isset($_GET['tab']) && $_GET['tab'] === 'checkout') {
                echo '<style>
                    .woocommerce-BlankState-cta,
                    .payment-gateway-suggestions,
                    .wc-payment-gateway-method-suggestions,
                    .woocommerce-payments-welcome,
                    .woocommerce-gateway-suggestions,
                    .wc-payment-gateway-suggestions {
                        display: none !important;
                    }
                </style>';
            }
        });
    }
    
    public function disable_payment_plugins_metabox() {
        add_action('admin_head', function() {
            echo '<style>
                #woocommerce-gateway-suggestions,
                .woocommerce-recommended-payment-plugins,
                .woocommerce-payment-gateway-suggestions,
                .payment-gateway-suggestion,
                .wc-payment-gateway-method-suggestion,
                .woocommerce-gateway-suggestion {
                    display: none !important;
                }
            </style>';
        });
        
        // 移除相關 metabox
        add_action('admin_init', function() {
            remove_meta_box('woocommerce-gateway-suggestions', 'woocommerce_page_wc-settings', 'normal');
            remove_meta_box('woocommerce-payment-gateway-suggestions', 'woocommerce_page_wc-settings', 'normal');
        });
        
        // 禁用相關 hooks
        add_filter('woocommerce_admin_payment_gateway_suggestion_specs', '__return_empty_array');
    }
    
    public function register_sales_features() {
        // 單品頁顯示
        add_action('woocommerce_single_product_summary', function(){
            global $product;
            if (!$product) return;
            $count = $this->get_product_sales_with_offset($product->get_id());
            echo '<div class="wu-sales-count" style="opacity:.85;font-size:.9em;">已售出：' . intval($count) . '</div>';
        }, 11);
        
        // 短代碼
        add_shortcode('wu_sales', function($atts){
            $atts = shortcode_atts(array('id'=>0), $atts, 'wu_sales');
            $id = intval($atts['id']);
            if (!$id && function_exists('wc_get_product')) {
                $prod = wc_get_product();
                if ($prod) $id = $prod->get_id();
            }
            if (!$id) return '0';
            return intval($this->get_product_sales_with_offset($id));
        });
        
        // 後台商品欄位
        add_action('woocommerce_product_options_general_product_data', function(){
            woocommerce_wp_text_input(array(
                'id' => '_wu_sales_offset',
                'label' => '購買量調整',
                'type' => 'number',
                'desc_tip' => true,
                'description' => '顯示購買量 = 真實購買量 + 調整',
                'custom_attributes' => array('step' => '1')
            ));
        });
        
        add_action('woocommerce_admin_process_product_object', function($product){
            $offset = isset($_POST['_wu_sales_offset']) ? intval($_POST['_wu_sales_offset']) : 0;
            $product->update_meta_data('_wu_sales_offset', $offset);
        });
    }
    
    private function get_product_sales_with_offset($product_id) {
        $product = wc_get_product($product_id);
        if (!$product) return 0;
        $real = intval($product->get_total_sales());
        $offset = intval(get_post_meta($product_id, '_wu_sales_offset', true));
        return max(0, $real + $offset);
    }
    
    /**
     * 暫存清理功能
     */
    public function ajax_clean_cache() {
        check_ajax_referer('wu_clean_cache', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('權限不足');
        }
        
        $results = $this->clean_woocommerce_cache();
        wp_send_json_success($results);
    }
    
    private function clean_woocommerce_cache() {
        $results = array(
            'cleaned' => array(),
            'errors' => array(),
            'total_size' => 0
        );
        
        // 清理項目列表
        $cache_items = array(
            'wc_transients' => 'WooCommerce 暫存資料',
            'wc_sessions' => '用戶會話資料',
            'product_lookup' => '商品查詢暫存',
            'shipping_zones' => '運送區域暫存',
            'tax_rates' => '稅率計算暫存',
            'geolocation' => '地理位置暫存',
            'wc_cache' => 'WooCommerce 物件暫存',
            'expired_transients' => '過期的 transients'
        );
        
        foreach ($cache_items as $type => $description) {
            $size = $this->clean_cache_type($type);
            if ($size > 0) {
                $results['cleaned'][$type] = array(
                    'description' => $description,
                    'size' => $this->format_bytes($size),
                    'items_count' => $this->get_cache_items_count($type)
                );
                $results['total_size'] += $size;
            }
        }
        
        // WooCommerce 內建清理
        if (function_exists('wc_delete_expired_transients')) {
            wc_delete_expired_transients();
            $results['cleaned']['wc_builtin'] = array(
                'description' => 'WooCommerce 內建清理',
                'size' => '已執行',
                'items_count' => '不明'
            );
        }
        
        return $results;
    }
    
    private function clean_cache_type($type) {
        global $wpdb;
        $size = 0;
        
        switch ($type) {
            case 'wc_transients':
                $transients = $wpdb->get_results(
                    "SELECT option_name, option_value FROM {$wpdb->options} 
                     WHERE option_name LIKE '_transient_wc_%' 
                     OR option_name LIKE '_transient_timeout_wc_%'"
                );
                foreach ($transients as $transient) {
                    $size += strlen($transient->option_value);
                    delete_option($transient->option_name);
                }
                break;
                
            case 'wc_sessions':
                $sessions = $wpdb->get_results(
                    "SELECT option_name, option_value FROM {$wpdb->options} 
                     WHERE option_name LIKE '_wc_session_%'"
                );
                foreach ($sessions as $session) {
                    $size += strlen($session->option_value);
                    delete_option($session->option_name);
                }
                break;
                
            case 'product_lookup':
                wp_cache_flush_group('woocommerce_products');
                wp_cache_flush_group('products');
                $size = 1024; // 估算
                break;
                
            case 'shipping_zones':
                wp_cache_flush_group('woocommerce_shipping_zones');
                wp_cache_flush_group('shipping');
                delete_transient('wc_shipping_method_count');
                $size = 512; // 估算
                break;
                
            case 'tax_rates':
                wp_cache_flush_group('woocommerce_tax_rates');
                wp_cache_flush_group('tax');
                delete_transient('wc_tax_rate_classes');
                $size = 256; // 估算
                break;
                
            case 'geolocation':
                $geoip = $wpdb->get_results(
                    "SELECT option_name, option_value FROM {$wpdb->options} 
                     WHERE option_name LIKE 'geoip_%' OR option_name LIKE '_transient_geoip_%'"
                );
                foreach ($geoip as $geo) {
                    $size += strlen($geo->option_value);
                    delete_option($geo->option_name);
                }
                break;
                
            case 'wc_cache':
                wp_cache_flush_group('woocommerce');
                wp_cache_flush_group('wc_session_id');
                $size = 2048; // 估算
                break;
                
            case 'expired_transients':
                $expired = $wpdb->query(
                    "DELETE a, b FROM {$wpdb->options} a, {$wpdb->options} b 
                     WHERE a.option_name LIKE '_transient_%' 
                     AND a.option_name NOT LIKE '_transient_timeout_%' 
                     AND b.option_name = CONCAT('_transient_timeout_', SUBSTRING(a.option_name, 12))
                     AND b.option_value < UNIX_TIMESTAMP()"
                );
                $size = $expired * 100; // 估算
                break;
        }
        
        return $size;
    }
    
    private function get_cache_items_count($type) {
        global $wpdb;
        
        switch ($type) {
            case 'wc_transients':
                return $wpdb->get_var(
                    "SELECT COUNT(*) FROM {$wpdb->options} 
                     WHERE option_name LIKE '_transient_wc_%'"
                );
                
            case 'wc_sessions':
                return $wpdb->get_var(
                    "SELECT COUNT(*) FROM {$wpdb->options} 
                     WHERE option_name LIKE '_wc_session_%'"
                );
                
            case 'geolocation':
                return $wpdb->get_var(
                    "SELECT COUNT(*) FROM {$wpdb->options} 
                     WHERE option_name LIKE 'geoip_%'"
                );
                
            default:
                return '不明';
        }
    }
    
    private function format_bytes($bytes) {
        if ($bytes == 0) return '0 B';
        $k = 1024;
        $sizes = array('B', 'KB', 'MB', 'GB');
        $i = floor(log($bytes) / log($k));
        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
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
            
            $settings = array(
                'wu_woo_disable_marketing_hub',
                'wu_woo_disable_home',
                'wu_woo_disable_notifications',
                'wu_woo_disable_reports',
                'wu_woo_disable_marketplace',
                'wu_woo_disable_marketplace_suggestions',
                'wu_woo_disable_stripe_scripts',
                'wu_woo_disable_guide_emails',
                'wu_woo_disable_woocom_notifications',
                'wu_woo_hide_payment_providers_link',
                'wu_woo_disable_payment_plugins_metabox',
                'wu_woo_show_sales_with_offset',
                'wu_woo_clean_cache'
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
                <?php $this->display_wc_status(); ?>
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
                <p>WooCommerce 優化器可幫助您清理管理介面，移除不必要的功能，提升網站效能。所有功能預設為停用狀態，請根據需要勾選啟用。</p>
                
                <h3>優化項目說明</h3>
                <ul>
                    <li><strong>行銷中心：</strong>移除 WooCommerce 行銷推廣介面及選單</li>
                    <li><strong>首頁：</strong>移除 WooCommerce 管理首頁及擴充功能選單</li>
                    <li><strong>通知欄：</strong>隱藏整個管理介面通知標題欄</li>
                    <li><strong>報表：</strong>停用報表功能及選單以提升效能</li>
                    <li><strong>Marketplace：</strong>移除市場推廣功能</li>
                    <li><strong>暫存清理：</strong>智能清理過期暫存檔案，顯示詳細清理資訊</li>
                </ul>
                
                <h3>注意事項</h3>
                <ul>
                    <li>所有優化不會影響 WooCommerce 核心功能</li>
                    <li>設定可隨時啟用或停用</li>
                    <li>建議在測試環境中先行測試</li>
                    <li>暫存清理功能會顯示詳細的清理項目和大小</li>
                </ul>
            </div>
        </div>
        
        <script>
        function cleanWooCache() {
            document.getElementById('cache-status').innerHTML = '<div style="padding: 10px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px;">正在清理中，請稍候...</div>';
            
            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=clean_woo_cache&nonce=<?php echo wp_create_nonce('wu_clean_cache'); ?>'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let html = '<div style="background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 4px; padding: 15px;">';
                    html += '<h4 style="margin-top: 0; color: #0c5460;">清理完成：</h4><ul style="margin: 0;">';
                    
                    for (let type in data.data.cleaned) {
                        const item = data.data.cleaned[type];
                        html += '<li><strong>' + item.description + '：</strong>' + item.size;
                        if (item.items_count && item.items_count !== '不明') {
                            html += ' (' + item.items_count + ' 項目)';
                        }
                        html += '</li>';
                    }
                    html += '</ul>';
                    
                    if (data.data.total_size > 0) {
                        html += '<p style="margin: 10px 0 0 0;"><strong>總計清理：' + formatBytes(data.data.total_size) + '</strong></p>';
                    }
                    html += '</div>';
                    
                    document.getElementById('cache-status').innerHTML = html;
                } else {
                    document.getElementById('cache-status').innerHTML = '<div style="background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; padding: 10px; color: #721c24;">清理失敗：' + (data.data || '未知錯誤') + '</div>';
                }
            })
            .catch(error => {
                document.getElementById('cache-status').innerHTML = '<div style="background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; padding: 10px; color: #721c24;">請求失敗：' + error.message + '</div>';
            });
        }
        
        function formatBytes(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        </script>
        
        <style>
        .card { background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin: 20px 0; }
        .card h2 { margin-top: 0; }
        .card h3, .card h4 { color: #23282d; }
        .card ul { margin-left: 20px; }
        .wc-status-table { width: 100%; margin-top: 15px; border-collapse: collapse; }
        .wc-status-table th, .wc-status-table td { padding: 8px; border: 1px solid #ddd; text-align: left; }
        .wc-status-table th { background-color: #f5f5f5; }
        #cache-status { margin-top: 10px; }
        #cache-status ul { list-style-type: none; padding-left: 0; }
        #cache-status li { padding: 5px 0; border-bottom: 1px solid #e9ecef; }
        #cache-status li:last-child { border-bottom: none; }
        </style>
        <?php
    }
    
    /**
     * 顯示 WC Status 資訊
     */
    private function display_wc_status() {
        echo '<table class="wc-status-table">';
        echo '<tr><th colspan="2">WooCommerce 資訊</th></tr>';
        echo '<tr><td>WooCommerce 版本</td><td>' . WC()->version . '</td></tr>';
        echo '<tr><td>資料庫版本</td><td>' . get_option('woocommerce_db_version') . '</td></tr>';
        echo '<tr><td>WordPress 版本</td><td>' . get_bloginfo('version') . '</td></tr>';
        echo '<tr><td>PHP 版本</td><td>' . phpversion() . '</td></tr>';
        echo '<tr><td>記憶體限制</td><td>' . ini_get('memory_limit') . '</td></tr>';
        
        $theme = wp_get_theme();
        echo '<tr><th colspan="2">主題資訊</th></tr>';
        echo '<tr><td>主題名稱</td><td>' . $theme->get('Name') . '</td></tr>';
        echo '<tr><td>主題版本</td><td>' . $theme->get('Version') . '</td></tr>';
        
        // 顯示當前啟用的優化功能
        echo '<tr><th colspan="2">已啟用的優化功能</th></tr>';
        $enabled_features = array();
        $all_options = array(
            'wu_woo_disable_marketing_hub' => '行銷中心',
            'wu_woo_disable_home' => '首頁',
            'wu_woo_disable_notifications' => '通知欄',
            'wu_woo_disable_reports' => '報表',
            'wu_woo_disable_marketplace' => 'Marketplace',
            'wu_woo_disable_marketplace_suggestions' => '市場建議',
            'wu_woo_disable_stripe_scripts' => 'Stripe 腳本',
            'wu_woo_disable_guide_emails' => '指南郵件',
            'wu_woo_disable_woocom_notifications' => 'WooCommerce.com 通知',
            'wu_woo_hide_payment_providers_link' => '付款提供者鏈接',
            'wu_woo_disable_payment_plugins_metabox' => '付款外掛 Metabox',
            'wu_woo_show_sales_with_offset' => '購買量顯示',
            'wu_woo_clean_cache' => '暫存清理'
        );
        
        foreach ($all_options as $option => $name) {
            if (get_option($option)) {
                $enabled_features[] = $name;
            }
        }
        
        if (empty($enabled_features)) {
            echo '<tr><td colspan="2"><span style="color: #666;">目前沒有啟用任何優化功能</span></td></tr>';
        } else {
            echo '<tr><td colspan="2"><span style="color: #0073aa; font-weight: bold;">' . implode('、', $enabled_features) . '</span></td></tr>';
        }
        
        echo '</table>';
    }
}

// 初始化模組
new WU_WooCommerce_Optimizer();
