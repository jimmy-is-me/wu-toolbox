<?php
/**
 * Plugin Name: WU工具箱
 * Description: Wumetax 工具箱，整合多個功能模組。
 * Version: 1.1
 * Author: Wumetax
 */

if (!defined('ABSPATH')) exit;

// === 後台父選單 ===
function wu_toolbox_menu() {
    add_menu_page(
        'WU 工具箱',      // 頁面標題
        'WU 工具箱',      // 選單標題
        'manage_options',  // 權限
        'wu-toolbox',      // slug
        '__return_null',   // 不顯示頁面
        'dashicons-admin-generic',
        99
    );
}
add_action('admin_menu', 'wu_toolbox_menu');

// === 開發常用外掛子選單 ===
function dev_common_plugins_menu() {
    add_submenu_page(
        'wu-toolbox',
        '開發常用外掛',        // 頁面標題
        '開發常用外掛',        // 選單標題
        'manage_options',       // 權限
        'dev-common-plugins',   // slug
        'dev_common_plugins_page' // 回調函數
    );
}
add_action('admin_menu', 'dev_common_plugins_menu');

// === 開發常用外掛頁面內容 ===
function dev_common_plugins_page() {
    ?>
    <div class="wrap">
        <h1>開發常用外掛</h1>
        <p>這裡列出開發中常用的 WordPress 外掛，方便快速安裝與管理：</p>
        <ul>
            <li><strong>ACF Pro</strong> - 高級自訂欄位管理</li>
            <li><strong>WP All Import</strong> - 匯入資料工具</li>
            <li><strong>Query Monitor</strong> - 偵錯與效能分析</li>
            <li><strong>WooCommerce</strong> - 電商功能</li>
            <li><strong>WP Rocket</strong> - 網站快取與優化</li>
        </ul>
        <p>你可以依照專案需求自行增加更多常用外掛連結或安裝程式碼。</p>
    </div>
    <?php
}

// === 自動載入 includes 下的子模組 ===
$includes_dir = plugin_dir_path(__FILE__) . 'includes/';
if (is_dir($includes_dir)) {
    foreach (glob($includes_dir . '*.php') as $file) {
        require_once $file;
    }
}
