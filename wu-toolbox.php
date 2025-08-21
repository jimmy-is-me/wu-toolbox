<?php
/**
 * Plugin Name: WU工具箱
 * Description: Wumetax 工具箱，整合多個功能模組，自動載入 includes 下的子模組。
 * Version: 1.0
 * Author: Wumetax
 */

if (!defined('ABSPATH')) exit;

// === 自動載入 includes 下的子模組 ===
$includes_dir = plugin_dir_path(__FILE__) . 'includes/';
$includes_files = [];
if (is_dir($includes_dir)) {
    $includes_files = glob($includes_dir . '*.php');
    foreach ($includes_files as $file) {
        require_once $file;
    }
}

// === 後台父選單 ===
function wu_toolbox_menu() {
    global $includes_files;

    if (empty($includes_files)) return;

    // 取第一個模組檔名作為 slug
    $first_file = basename($includes_files[0], '.php');

    add_menu_page(
        '',                    // 頁面標題
        'WU工具箱',             // 選單標題
        'manage_options',      // 權限
        'wu-toolbox',          // 父選單 slug
        function() use ($first_file) {
            // 點擊父選單直接跳轉到第一個模組
            wp_safe_redirect(admin_url('admin.php?page=' . $first_file));
            exit;
        },
        'dashicons-admin-generic',
        99
    );
}
add_action('admin_menu', 'wu_toolbox_menu');
