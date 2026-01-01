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

/**
 * 外掛主類別
 */
class WumetaxToolkit {
    
    /**
     * 外掛版本
     */
    const VERSION = '3.3';
    
    /**
     * 外掛路徑
     */
    private $plugin_path;
    
    /**
     * 除錯模式（開發用）
     */
    private $debug_mode;
    
    /**
     * 已載入的模組記錄
     */
    private $loaded_modules = array();
    
    /**
     * 單例實例
     */
    private static $instance = null;
    
    /**
     * 取得單例實例
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * 建構子
     */
    private function __construct() {
        $this->plugin_path = plugin_dir_path(__FILE__);
        $this->debug_mode = defined('WP_DEBUG') && WP_DEBUG;
        
        // 根據請求類型載入對應模組
        $this->load_modules();
        
        // 註冊後台選單（僅後台）
        if (is_admin()) {
            add_action('admin_menu', array($this, 'register_admin_menu'));
            add_action('admin_init', array($this, 'check_activation_redirect'));
        }
    }
    
    /**
     * 根據環境載入對應模組
     */
    private function load_modules() {
        $includes_dir = $this->plugin_path . 'includes/';
        
        // 檢查目錄是否存在且可讀
        if (!is_dir($includes_dir) || !is_readable($includes_dir)) {
            if ($this->debug_mode) {
                error_log('WumetaxToolkit: includes 目錄不存在或無法讀取');
            }
            return;
        }
        
        // 共用模組（所有請求都載入）
        $this->load_module_group($includes_dir, 'common');
        
        // 後台專用模組
        if (is_admin()) {
            // 排除 AJAX 與 Cron
            if (!$this->is_ajax_request() && !$this->is_cron_request()) {
                $this->load_module_group($includes_dir, 'admin');
            }
            
            // Admin AJAX 專用模組
            if ($this->is_ajax_request()) {
                $this->load_module_group($includes_dir, 'admin-ajax');
            }
        }
        
        // 前台專用模組
        if (!is_admin()) {
            $this->load_module_group($includes_dir, 'frontend');
            
            // 前台 AJAX 專用模組
            if ($this->is_ajax_request()) {
                $this->load_module_group($includes_dir, 'frontend-ajax');
            }
        }
        
        // REST API 專用模組
        if ($this->is_rest_request()) {
            $this->load_module_group($includes_dir, 'rest');
        }
        
        // WooCommerce 專用模組（僅在 WooCommerce 啟用時載入）
        if ($this->is_woocommerce_active()) {
            $this->load_module_group($includes_dir, 'woocommerce');
        }
        
        // 除錯模式：記錄已載入模組
        if ($this->debug_mode && !empty($this->loaded_modules)) {
            error_log('WumetaxToolkit 已載入模組: ' . implode(', ', $this->loaded_modules));
        }
    }
    
    /**
     * 載入特定群組的模組
     * 
     * @param string $dir 模組目錄路徑
     * @param string $group 模組群組名稱
     */
    private function load_module_group($dir, $group) {
        $pattern = $dir . $group . '-*.php';
        $files = glob($pattern);
        
        if (empty($files)) {
            return;
        }
        
        foreach ($files as $file) {
            if (!is_readable($file)) {
                if ($this->debug_mode) {
                    error_log("WumetaxToolkit: 無法讀取模組檔案 {$file}");
                }
                continue;
            }
            
            require_once $file;
            $this->loaded_modules[] = basename($file);
            
            if ($this->debug_mode) {
                error_log("WumetaxToolkit: 已載入 [{$group}] " . basename($file));
            }
        }
    }
    
    /**
     * 判斷是否為 AJAX 請求
     * 
     * @return bool
     */
    private function is_ajax_request() {
        if (function_exists('wp_doing_ajax')) {
            return wp_doing_ajax();
        }
        return (defined('DOING_AJAX') && DOING_AJAX);
    }
    
    /**
     * 判斷是否為 Cron 請求
     * 
     * @return bool
     */
    private function is_cron_request() {
        if (function_exists('wp_doing_cron')) {
            return wp_doing_cron();
        }
        return (defined('DOING_CRON') && DOING_CRON);
    }
    
    /**
     * 判斷是否為 REST API 請求
     * 
     * @return bool
     */
    private function is_rest_request() {
        // WordPress 5.0+ 方法
        if (function_exists('wp_is_json_request') && wp_is_json_request()) {
            return true;
        }
        
        // 檢查 REST_REQUEST 常數
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return true;
        }
        
        // 檢查 URL 是否包含 wp-json
        if (isset($_SERVER['REQUEST_URI'])) {
            $rest_prefix = rest_get_url_prefix();
            if (strpos($_SERVER['REQUEST_URI'], $rest_prefix) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 判斷 WooCommerce 是否啟用
     * 
     * @return bool
     */
    private function is_woocommerce_active() {
        return class_exists('WooCommerce');
    }
    
    /**
     * 註冊後台選單
     */
    public function register_admin_menu() {
        add_menu_page(
            'WumetaxToolkit',                    // 頁面標題
            'WumetaxToolkit',                    // 選單標題
            'manage_options',                    // 權限
            'wumetax-toolkit',                   // slug
            array($this, 'render_main_page'),    // callback（語意清晰的命名）
            'dashicons-admin-generic',
            99
        );
    }
    
    /**
     * 渲染主頁面（常用外掛管理）
     */
    public function render_main_page() {
        // 如果模組有定義 plugin_manager_settings_page，則呼叫它
        if (function_exists('plugin_manager_settings_page')) {
            plugin_manager_settings_page();
        } else {
            echo '<div class="wrap">';
            echo '<h1>WumetaxToolkit</h1>';
            echo '<p>歡迎使用 WumetaxToolkit！相關模組尚未載入。</p>';
            echo '</div>';
        }
    }
    
    /**
     * 檢查是否需要啟用後重定向
     */
    public function check_activation_redirect() {
        // 避免在 AJAX / Cron / REST 期間重定向
        if ($this->is_ajax_request() || $this->is_cron_request() || $this->is_rest_request()) {
            return;
        }
        
        // 避免在 WP-CLI 執行時重定向
        if (defined('WP_CLI') && WP_CLI) {
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
        
        // 避免在除錯模式下重定向（方便開發）
        if ($this->debug_mode) {
            return;
        }
        
        // 執行重定向
        wp_safe_redirect(admin_url('admin.php?page=wumetax-toolkit'));
        exit;
    }
    
    /**
     * 取得已載入的模組清單（除錯用）
     * 
     * @return array
     */
    public function get_loaded_modules() {
        return $this->loaded_modules;
    }
}

/**
 * 外掛啟用時的處理
 */
function wumetax_toolkit_activation() {
    // 設定重定向標記
    add_option('wumetax_toolkit_activation_redirect', true);
    
    // 清除任何快取
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
}
register_activation_hook(__FILE__, 'wumetax_toolkit_activation');

/**
 * 外掛停用時的處理
 */
function wumetax_toolkit_deactivation() {
    // 清理重定向標記（以防萬一）
    delete_option('wumetax_toolkit_activation_redirect');
    
    // 清除快取
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
}
register_deactivation_hook(__FILE__, 'wumetax_toolkit_deactivation');

/**
 * 初始化外掛
 */
function wumetax_toolkit_init() {
    return WumetaxToolkit::get_instance();
}

// 啟動外掛
wumetax_toolkit_init();
