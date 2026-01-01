<?php
/**
 * Plugin Name: WumetaxToolkit
 * Description: Wumetax 工具箱，整合多個功能模組。包含管理列清理、評論管理、WooCommerce優化器、更新管理等強大功能。
 * Version: 3.3
 * Author: Wumetax
 * Author URI: https://wumetax.com/
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: wumetax-toolkit
 */

if (!defined('ABSPATH')) exit;

// 定義外掛常數
define('WUMETAX_TOOLKIT_VERSION', '3.3');
define('WUMETAX_TOOLKIT_PATH', plugin_dir_path(__FILE__));
define('WUMETAX_TOOLKIT_URL', plugin_dir_url(__FILE__));
define('WUMETAX_TOOLKIT_DEBUG', defined('WP_DEBUG') && WP_DEBUG);

/**
 * 外掛啟用時的重定向處理
 */
register_activation_hook(__FILE__, 'wumetax_toolkit_activation_redirect');

function wumetax_toolkit_activation_redirect() {
    // 設定重定向標記
    add_option('wumetax_toolkit_activation_redirect', true);
    
    // 清除快取
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
}

/**
 * 外掛停用時的清理
 */
register_deactivation_hook(__FILE__, 'wumetax_toolkit_deactivation');

function wumetax_toolkit_deactivation() {
    // 清理重定向標記
    delete_option('wumetax_toolkit_activation_redirect');
}

/**
 * 檢查是否需要重定向（僅在後台執行）
 */
if (is_admin()) {
    add_action('admin_init', 'wumetax_toolkit_check_redirect');
}

function wumetax_toolkit_check_redirect() {
    // 避免在 AJAX 請求時重定向
    if ((function_exists('wp_doing_ajax') && wp_doing_ajax()) || (defined('DOING_AJAX') && DOING_AJAX)) {
        return;
    }
    
    // 避免在 Cron 執行時重定向
    if ((function_exists('wp_doing_cron') && wp_doing_cron()) || (defined('DOING_CRON') && DOING_CRON)) {
        return;
    }
    
    // 避免在 WP-CLI 執行時重定向
    if (defined('WP_CLI') && WP_CLI) {
        return;
    }
    
    // 避免在 REST API 請求時重定向
    if (function_exists('wp_is_json_request') && wp_is_json_request()) {
        return;
    }
    
    // 檢查重定向標記
    if (!get_option('wumetax_toolkit_activation_redirect', false)) {
        return;
    }
    
    // 刪除標記
    delete_option('wumetax_toolkit_activation_redirect');
    
    // 避免在多站點網路啟用時重定向
    if (is_network_admin()) {
        return;
    }
    
    // 避免在批次啟用多個外掛時重定向
    if (isset($_GET['activate-multi'])) {
        return;
    }
    
    // 執行安全重定向到設定頁面
    wp_safe_redirect(admin_url('admin.php?page=wumetax-toolkit'));
    exit;
}

/**
 * 註冊後台選單（僅在後台執行）
 */
if (is_admin()) {
    add_action('admin_menu', 'wumetax_toolkit_register_menu');
}

function wumetax_toolkit_register_menu() {
    add_menu_page(
        'WumetaxToolkit',                      // 頁面標題
        'WumetaxToolkit',                      // 選單標題
        'manage_options',                      // 權限
        'wumetax-toolkit',                     // slug
        'wumetax_toolkit_render_settings_page', // 統一命名的 callback
        'dashicons-admin-generic',
        99
    );
}

/**
 * 渲染設定頁面（語意清晰的命名）
 */
function wumetax_toolkit_render_settings_page() {
    // 如果模組有定義 plugin_manager_settings_page，則呼叫它
    if (function_exists('plugin_manager_settings_page')) {
        plugin_manager_settings_page();
    } else {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('WumetaxToolkit', 'wumetax-toolkit') . '</h1>';
        echo '<p>' . esc_html__('歡迎使用 WumetaxToolkit！相關模組尚未載入或未正確配置。', 'wumetax-toolkit') . '</p>';
        echo '</div>';
    }
}

/**
 * 智能載入 includes 模組
 * 根據請求類型條件式載入，降低 PHP 載入成本
 */
function wumetax_toolkit_load_modules() {
    $includes_dir = WUMETAX_TOOLKIT_PATH . 'includes/';
    
    // 檢查目錄是否存在且可讀
    if (!is_dir($includes_dir) || !is_readable($includes_dir)) {
        if (WUMETAX_TOOLKIT_DEBUG) {
            error_log('WumetaxToolkit: includes 目錄不存在或無法讀取 - ' . $includes_dir);
        }
        return;
    }
    
    // 判斷當前請求類型
    $is_admin = is_admin();
    $is_ajax = (function_exists('wp_doing_ajax') && wp_doing_ajax()) || (defined('DOING_AJAX') && DOING_AJAX);
    $is_rest = (function_exists('wp_is_json_request') && wp_is_json_request()) || (defined('REST_REQUEST') && REST_REQUEST);
    $is_cron = (function_exists('wp_doing_cron') && wp_doing_cron()) || (defined('DOING_CRON') && DOING_CRON);
    
    // 取得所有模組檔案
    $module_files = glob($includes_dir . '*.php');
    
    if (empty($module_files)) {
        if (WUMETAX_TOOLKIT_DEBUG) {
            error_log('WumetaxToolkit: includes 目錄下沒有找到任何 PHP 模組');
        }
        return;
    }
    
    // 載入模組
    foreach ($module_files as $file) {
        $filename = basename($file);
        
        // 檢查檔案是否可讀（安全性與權限檢查）
        if (!is_readable($file)) {
            if (WUMETAX_TOOLKIT_DEBUG) {
                error_log('WumetaxToolkit: 無法讀取模組檔案 - ' . $filename);
            }
            continue;
        }
        
        // 根據檔案命名規則決定是否載入
        $should_load = wumetax_toolkit_should_load_module($filename, $is_admin, $is_ajax, $is_rest, $is_cron);
        
        if ($should_load) {
            require_once $file;
            
            if (WUMETAX_TOOLKIT_DEBUG) {
                error_log('WumetaxToolkit: 已載入模組 - ' . $filename);
            }
        } else {
            if (WUMETAX_TOOLKIT_DEBUG) {
                error_log('WumetaxToolkit: 跳過載入模組 - ' . $filename . ' (不符合當前請求環境)');
            }
        }
    }
}

/**
 * 判斷模組是否應該在當前請求中載入
 * 
 * 命名規則：
 * - common-*.php    : 所有請求都載入（共用函數、常數等）
 * - admin-*.php     : 僅後台載入（排除 AJAX）
 * - admin-ajax-*.php: 僅後台 AJAX 載入
 * - frontend-*.php  : 僅前台載入（排除 AJAX）
 * - frontend-ajax-*.php: 僅前台 AJAX 載入
 * - rest-*.php      : 僅 REST API 請求載入
 * - cron-*.php      : 僅 Cron 執行時載入
 * - woocommerce-*.php: 僅 WooCommerce 啟用時載入
 * 
 * @param string $filename 檔案名稱
 * @param bool $is_admin 是否為後台
 * @param bool $is_ajax 是否為 AJAX 請求
 * @param bool $is_rest 是否為 REST 請求
 * @param bool $is_cron 是否為 Cron 執行
 * @return bool 是否應該載入
 */
function wumetax_toolkit_should_load_module($filename, $is_admin, $is_ajax, $is_rest, $is_cron) {
    // 共用模組：始終載入
    if (strpos($filename, 'common-') === 0) {
        return true;
    }
    
    // Cron 專用模組：僅在 Cron 執行時載入
    if (strpos($filename, 'cron-') === 0) {
        return $is_cron;
    }
    
    // REST API 專用模組：僅在 REST 請求時載入
    if (strpos($filename, 'rest-') === 0) {
        return $is_rest;
    }
    
    // 後台 AJAX 專用模組
    if (strpos($filename, 'admin-ajax-') === 0) {
        return $is_admin && $is_ajax;
    }
    
    // 後台專用模組：僅在後台且非 AJAX 時載入
    if (strpos($filename, 'admin-') === 0) {
        return $is_admin && !$is_ajax && !$is_rest;
    }
    
    // 前台 AJAX 專用模組
    if (strpos($filename, 'frontend-ajax-') === 0) {
        return !$is_admin && $is_ajax;
    }
    
    // 前台專用模組：僅在前台且非 AJAX 時載入
    if (strpos($filename, 'frontend-') === 0) {
        return !$is_admin && !$is_ajax && !$is_rest;
    }
    
    // WooCommerce 專用模組：僅在 WooCommerce 啟用時載入
    if (strpos($filename, 'woocommerce-') === 0) {
        return class_exists('WooCommerce');
    }
    
    // 預設：舊有模組（無前綴）僅在後台載入，保持向下相容
    // 但建議逐步將所有模組加上適當前綴
    if (WUMETAX_TOOLKIT_DEBUG) {
        error_log('WumetaxToolkit: 警告 - 模組 "' . $filename . '" 未使用標準命名規則，僅在後台載入');
    }
    return $is_admin && !$is_ajax;
}

/**
 * 在適當時機載入模組
 * 使用 plugins_loaded hook 確保在所有外掛載入後執行
 * 但早於 init、wp_loaded 等效能敏感的 hook
 */
add_action('plugins_loaded', 'wumetax_toolkit_load_modules', 5);

/**
 * 除錯資訊：顯示已載入模組（僅在 WP_DEBUG 開啟時）
 */
if (WUMETAX_TOOLKIT_DEBUG && is_admin()) {
    add_action('admin_footer', 'wumetax_toolkit_debug_info');
}

function wumetax_toolkit_debug_info() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $includes_dir = WUMETAX_TOOLKIT_PATH . 'includes/';
    $loaded_modules = array();
    
    if (is_dir($includes_dir)) {
        foreach (glob($includes_dir . '*.php') as $file) {
            if (in_array($file, get_included_files())) {
                $loaded_modules[] = basename($file);
            }
        }
    }
    
    echo '<!-- WumetaxToolkit 除錯資訊 -->';
    echo '<!-- 已載入模組: ' . implode(', ', $loaded_modules) . ' -->';
    echo '<!-- 請求類型: ';
    echo is_admin() ? 'Admin' : 'Frontend';
    echo (function_exists('wp_doing_ajax') && wp_doing_ajax()) ? ' + AJAX' : '';
    echo (function_exists('wp_is_json_request') && wp_is_json_request()) ? ' + REST' : '';
    echo ' -->';
}
