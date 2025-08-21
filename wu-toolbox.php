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
        'WU 工具箱',             // 頁面標題
        'WU 工具箱',             // 選單標題
        'manage_options',         // 權限
        'wu-toolbox',             // slug
        'wu_toolbox_redirect',    // 點選父選單時呼叫的函式
        'dashicons-admin-generic',
        99
    );
}
add_action('admin_menu', 'wu_toolbox_menu');

// === 點擊父選單直接跳轉到第一個子選單（外掛清單） ===
function wu_toolbox_redirect() {
    $first_child_slug = 'plugins.php'; // 第一個子選單 slug 改為外掛清單
    wp_safe_redirect(admin_url('admin.php?page=' . $first_child_slug));
    exit;
}

// === 自動載入 includes 下的子模組 ===
$includes_dir = plugin_dir_path(__FILE__) . 'includes/';
if (is_dir($includes_dir)) {
    foreach (glob($includes_dir . '*.php') as $file) {
        require_once $file;
    }
}
