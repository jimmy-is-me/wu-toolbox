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
$first_child_page = ''; // 先記錄第一個模組的 slug
if (is_dir($includes_dir)) {
    foreach (glob($includes_dir . '*.php') as $file) {
        require_once $file;

        // 嘗試抓取第一個模組的 slug (假設模組有設定全域 $module_slug)
        if (empty($first_child_page) && isset($module_slug)) {
            $first_child_page = $module_slug;
        }
    }
}

// === 後台父選單 ===
function wu_toolbox_menu() {
    global $first_child_page;

    // 沒有子模組就不加選單
    if (empty($first_child_page)) return;

    add_menu_page(
        '',                    // 頁面標題，空白
        'WU工具箱',             // 選單標題
        'manage_options',      // 權限
        'wu-toolbox',          // slug
        function() use ($first_child_page) {
            // 點選父選單直接跳轉到第一個子模組
            wp_safe_redirect(admin_url('admin.php?page=' . $first_child_page));
            exit;
        },
        'dashicons-admin-generic',
        99
    );
}
add_action('admin_menu', 'wu_toolbox_menu');
