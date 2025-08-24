<?php
/**
 * Plugin Name: WumetaxToolkit
 * Description: Wumetax 工具箱，整合多個功能模組。包含管理列清理、評論管理、WooCommerce優化器、更新管理等強大功能。
 * Version: 2.0
 * Author: Wumetax
 * Author URI: https://wumetax.com/
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 */

if (!defined('ABSPATH')) exit;

// === 後台父選單 ===
function wumetax_toolkit_menu() {
    add_menu_page(
        'WumetaxToolkit',    // 頁面標題
        'WumetaxToolkit',    // 選單標題
        'manage_options',    // 權限
        'wumetax-toolkit',   // slug
        'plugin_manager_settings_page', // 直接調用常用外掛管理頁面
        'dashicons-admin-generic',
        99
    );
}
add_action('admin_menu', 'wumetax_toolkit_menu');

// === 自動載入 includes 下的子模組 ===
$includes_dir = plugin_dir_path(__FILE__) . 'includes/';
if (is_dir($includes_dir)) {
    foreach (glob($includes_dir . '*.php') as $file) {
        require_once $file;
    }
}

