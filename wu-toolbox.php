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
if (is_dir($includes_dir)) {
    foreach (glob($includes_dir . '*.php') as $file) {
        require_once $file;
    }
}
