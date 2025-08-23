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

        if (class_exists('WooCommerce')) {
            if (get_option('wu_remove_wc_connect_notice', false)) {
                $this->remove_connect_notices();
            }
            if (get_option('wu_remove_wc_skyverge_dashboard', false)) {
                $this->remove_skyverge_dashboard();
            }
            if (get_option('wu_remove_wc_marketing_hub', false)) {
                $this->remove_marketing_hub();
            }
            if (get_option('wu_remove_wc_marketplace', false)) {
                $this->remove_marketplace();
            }
            if (get_option('wu_remove_wc_status', false)) {
                $this->remove_status_menu();
            }
            if (get_option('wu_split_wc_settings', false)) {
                $this->split_wc_settings();
            }
            if (get_option('wu_remove_wc_reports', false)) {
                $this->remove_reports();
            }
            if (get_option('wu_remove_marketing_overview', false)) {
                $this->remove_marketing_overview();
            }
            if (get_option('wu_remove_wc_ui_elements', false)) {
                add_action('admin_head', array($this, 'hide_wc_ui_elements'));
            }
        }
    }

    /** ----------------
     * 註冊設定
     ------------------*/
    public function admin_init() {
        $options = array(
            'wu_remove_wc_connect_notice' => '移除連接商店通知',
            'wu_remove_wc_skyverge_dashboard' => '移除 Home (SkyVerge 儀表板)',
            'wu_remove_wc_marketing_hub' => '移除行銷中心',
            'wu_remove_wc_marketplace' => '移除 Extensions → WooCommerce Marketplace',
            'wu_remove_wc_status' => '移除 WooCommerce 狀態 (Status)',
            'wu_split_wc_settings' => '將 Settings 拆分成 General, Products, Shipping, Payments, Emails 子選單',
            'wu_remove_wc_reports' => '移除 Reports',
            'wu_remove_marketing_overview' => '移除 Marketing → Overview',
            'wu_remove_wc_ui_elements' => '隱藏提醒/活動面板/Marketplace 建議區塊'
        );

        register_setting('wu_wc_cleaner_settings', 'wu_wc_cleaner_settings_options');
        add_settings_section('wu_wc_cleaner_section', 'WooCommerce 清理設定', null, 'wu_wc_cleaner_settings');

        foreach ($options as $id => $label) {
            add_settings_field(
                $id, 
                $label, 
                function() use ($id) { 
                    $options = get_option('wu_wc_cleaner_settings_options', array());
                    $checked = isset($options[$id]) ? 'checked' : '';
                    echo '<input type="checkbox" name="wu_wc_cleaner_settings_options['.$id.']" value="1" '.$checked.' />';
                }, 
                'wu_wc_cleaner_settings', 
                'wu_wc_cleaner_section'
            );
        }
    }

    /** ----------------
     * 後台設定頁
     ------------------*/
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

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>WooCommerce 清理設定</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('wu_wc_cleaner_settings');
                do_settings_sections('wu_wc_cleaner_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /** ----------------
     * 功能實作
     ------------------*/

    // 1. 移除連接商店通知
    private function remove_connect_notices() {
        remove_action('admin_notices', array('WC_Admin_Notices', 'show_notices'));
        add_filter('woocommerce_enable_setup_wizard', '__return_false');
        add_action('wp_dashboard_setup', function() {
            remove_meta_box('woocommerce_dashboard_status', 'dashboard', 'normal');
        });
    }

    // 2. 移除 WooCommerce → Home (SkyVerge 儀表板)
    private function remove_skyverge_dashboard() {
        add_action('admin_menu', function() {
            remove_submenu_page('woocommerce', 'wc-admin');
            // 直接導向訂單
            global $submenu;
            if (isset($submenu['woocommerce'][0])) {
                $submenu['woocommerce'][0][2] = 'edit.php?post_type=shop_order';
            }
        }, 999);
    }

    // 3. 移除行銷中心
    private function remove_marketing_hub() {
        add_action('admin_menu', function() {
            remove_submenu_page('woocommerce', 'wc-admin&path=/marketing');
            remove_menu_page('woocommerce-marketing');
        }, 999);
    }

    // 4. 移除 Marketplace
    private function remove_marketplace() {
        add_action('admin_menu', function() {
            remove_submenu_page('woocommerce', 'wc-addons');
        }, 999);
    }

    // 5. 移除 Status
    private function remove_status_menu() {
        add_action('admin_menu', function() {
            remove_submenu_page('woocommerce', 'wc-status');
        }, 999);
    }

    // 6. 拆分 Settings
    private function split_wc_settings() {
        add_action('admin_menu', function() {
            remove_submenu_page('woocommerce', 'wc-settings');
            add_submenu_page('woocommerce', 'General Settings', 'General', 'manage_woocommerce', 'admin.php?page=wc-settings&tab=general');
            add_submenu_page('woocommerce', 'Products Settings', 'Products', 'manage_woocommerce', 'admin.php?page=wc-settings&tab=products');
            add_submenu_page('woocommerce', 'Shipping Settings', 'Shipping', 'manage_woocommerce', 'admin.php?page=wc-settings&tab=shipping');
            add_submenu_page('woocommerce', 'Payments Settings', 'Payments', 'manage_woocommerce', 'admin.php?page=wc-settings&tab=checkout');
            add_submenu_page('woocommerce', 'Emails Settings', 'Emails', 'manage_woocommerce', 'admin.php?page=wc-settings&tab=email');
        }, 999);
    }

    // 7. 移除 Reports
    private function remove_reports() {
        add_action('admin_menu', function() {
            remove_submenu_page('woocommerce', 'wc-reports');
        }, 999);
    }

    // 8. 移除 Marketing Overview
    private function remove_marketing_overview() {
        add_action('admin_menu', function() {
            remove_submenu_page('woocommerce-marketing', 'wc-admin&path=/marketing/overview');
        }, 999);
    }

    // 9. 隱藏 UI 元素
    public function hide_wc_ui_elements() {
        echo '<style>
        .woocommerce-layout__header-tasks-reminder-bar,
        #woocommerce-activity-panel,
        .marketplace-suggestions-container.showing-suggestion {
            display: none !important;
        }
        </style>';
    }
}

// 初始化模組
new WU_WooCommerce_Cleaner();
