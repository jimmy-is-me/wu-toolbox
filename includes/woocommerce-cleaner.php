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

        // WooCommerce 存在時才動作
        if (class_exists('WooCommerce')) {
            $this->apply_cleaning();
        }
    }

    /**
     * 套用清理動作
     */
    private function apply_cleaning() {
        // SkyVerge 儀表板 (直接把 WooCommerce > Home 移掉，預設顯示訂單)
        add_action('admin_menu', function () {
            remove_submenu_page('woocommerce', 'wc-admin');
            global $submenu;
            if (isset($submenu['woocommerce'])) {
                foreach ($submenu['woocommerce'] as $index => $item) {
                    if ($item[2] === 'wc-admin') {
                        unset($submenu['woocommerce'][$index]);
                    }
                }
            }
            // 進 WooCommerce 直接跳轉 Orders
            add_action('load-toplevel_page_woocommerce', function () {
                wp_safe_redirect(admin_url('edit.php?post_type=shop_order'));
                exit;
            });
        }, 999);

        // 移除行銷中心 + Marketplace
        add_action('admin_menu', function () {
            remove_submenu_page('woocommerce', 'wc-admin&path=/marketing');
            remove_submenu_page('woocommerce', 'wc-admin&path=/extensions');
        }, 999);

        add_filter('woocommerce_admin_features', function ($features) {
            $remove = array(
                'marketing', 
                'remote-inbox-notifications', 
                'remote-free-extensions',
                'navigation',
                'marketplace'
            );
            return array_diff($features, $remove);
        });

        // 移除 Reports
        add_action('admin_menu', function () {
            remove_submenu_page('woocommerce', 'wc-reports');
        }, 999);

        // Status 選單移除，但資訊顯示在 WooCommerce 清理設定頁
        add_action('admin_menu', function () {
            remove_submenu_page('woocommerce', 'wc-status');
        }, 999);

        // Settings 子選單展開：General、Products、Shipping、Payments、Emails
        add_action('admin_menu', function () {
            global $submenu;
            if (isset($submenu['woocommerce'])) {
                foreach ($submenu['woocommerce'] as $item) {
                    if ($item[2] === 'wc-settings') {
                        // 自動展開子頁面
                        add_submenu_page('woocommerce', 'General Settings', 'General', 'manage_options', 'admin.php?page=wc-settings&tab=general');
                        add_submenu_page('woocommerce', 'Products Settings', 'Products', 'manage_options', 'admin.php?page=wc-settings&tab=products');
                        add_submenu_page('woocommerce', 'Shipping Settings', 'Shipping', 'manage_options', 'admin.php?page=wc-settings&tab=shipping');
                        add_submenu_page('woocommerce', 'Payments Settings', 'Payments', 'manage_options', 'admin.php?page=wc-settings&tab=checkout');
                        add_submenu_page('woocommerce', 'Emails Settings', 'Emails', 'manage_options', 'admin.php?page=wc-settings&tab=email');
                    }
                }
            }
        }, 20);

        // 移除 Marketing > Overview
        add_action('admin_menu', function () {
            remove_submenu_page('woocommerce-marketing', 'wc-admin&path=/marketing');
        }, 999);

        // 隱藏 Header Tasks 與 Activity Panel
        add_action('admin_head', function () {
            echo '<style>
                .woocommerce-layout__header-tasks-reminder-bar,
                #woocommerce-activity-panel { display:none !important; }
            </style>';
        });
    }

    /**
     * WooCommerce 清理設定選單
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

    public function admin_init() {
        // 可選設定項目（如果你要讓它們可以勾選/取消）
        register_setting('wu_wc_cleaner_settings', 'wu_remove_wc_status');
    }

    /**
     * 設定頁面
     */
    public function admin_page() {
        $wc_installed = class_exists('WooCommerce');
        $wc_version = $wc_installed ? WC()->version : 'N/A';
        ?>
        <div class="wrap">
            <h1>WooCommerce 清理設定</h1>
            
            <div class="card">
                <h2>當前狀態</h2>
                <p><strong>WooCommerce 狀態：</strong> 
                    <span style="color:<?php echo $wc_installed ? '#00a32a' : '#d63638'; ?>">
                        <?php echo $wc_installed ? '已安裝' : '未安裝'; ?>
                    </span>
                </p>
                <?php if ($wc_installed): ?>
                <p><strong>WooCommerce 版本：</strong> <?php echo esc_html($wc_version); ?></p>
                <?php endif; ?>
            </div>

            <?php if ($wc_installed): ?>
            <div class="card">
                <h2>已移除的項目</h2>
                <ul>
                    <li>WooCommerce Home (SkyVerge 儀表板)</li>
                    <li>行銷中心 (Marketing Hub)</li>
                    <li>Marketplace</li>
                    <li>Reports</li>
                    <li>Status（資訊顯示在此頁面）</li>
                    <li>Marketing > Overview</li>
                    <li>Header Tasks Reminder Bar</li>
                    <li>Activity Panel</li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
}

// 初始化模組
new WU_WooCommerce_Cleaner();
