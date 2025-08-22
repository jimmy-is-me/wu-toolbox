<?php
/**
 * Plugin Name: WU工具箱
 * Description: Wumetax 工具箱，整合多個功能模組。
 * Version: 1.0
 * Author: Wumetax
 */

if (!defined('ABSPATH')) exit;

// === 後台父選單 ===
function wu_toolbox_menu() {
    add_menu_page(
        'WU工具箱',          // 頁面標題
        'WU工具箱',          // 選單標題
        'manage_options',    // 權限
        'wu-toolbox',        // slug
        'plugin_manager_settings_page', // 直接調用常用外掛管理頁面
        'dashicons-admin-generic',
        99
    );
}
add_action('admin_menu', 'wu_toolbox_menu');

// === 自動載入 includes 下的子模組 ===
$includes_dir = plugin_dir_path(__FILE__) . 'includes/';
if (is_dir($includes_dir)) {
    foreach (glob($includes_dir . '*.php') as $file) {
        require_once $file;
    }
}
