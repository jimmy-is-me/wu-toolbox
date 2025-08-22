<?php
/**
 * Plugin Name: WU 增強下載器
 * Description: 增強下載器功能，可以下載外掛程式和主題
 * Version: 1.0
 * Author: WU Toolbox
 */

if (!defined('ABSPATH')) {
    exit;
}

class WU_Enhanced_Downloader {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    public function init() {
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_init', array($this, 'admin_init'));
            
            // 處理下載請求
            add_action('admin_post_wu_download_plugin', array($this, 'download_plugin'));
            add_action('admin_post_wu_download_theme', array($this, 'download_theme'));
            
            // 如果啟用了相應功能，則添加下載按鈕
            if (get_option('wu_enable_plugin_downloader', false)) {
                $this->enable_plugin_downloader();
            }
            if (get_option('wu_enable_theme_downloader', false)) {
                $this->enable_theme_downloader();
            }
        }
    }
    
    /**
     * 添加管理頁面
     */
    public function add_admin_menu() {
        add_management_page(
            '增強下載器',
            '增強下載器',
            'manage_options',
            'wu-enhanced-downloader',
            array($this, 'admin_page')
        );
    }
    
    /**
     * 初始化設定
     */
    public function admin_init() {
        register_setting('wu_downloader_settings', 'wu_enable_plugin_downloader');
        register_setting('wu_downloader_settings', 'wu_enable_theme_downloader');
        
        add_settings_section(
            'wu_downloader_section',
            '增強下載器設定',
            array($this, 'settings_section_callback'),
            'wu_downloader_settings'
        );
        
        add_settings_field(
            'wu_enable_plugin_downloader',
            '外掛程式下載器',
            array($this, 'plugin_downloader_callback'),
            'wu_downloader_settings',
            'wu_downloader_section'
        );
        
        add_settings_field(
            'wu_enable_theme_downloader',
            '主題下載器',
            array($this, 'theme_downloader_callback'),
            'wu_downloader_settings',
            'wu_downloader_section'
        );
    }
    
    /**
     * 設定區域說明
     */
    public function settings_section_callback() {
        echo '<p>增強下載器功能可以在外掛程式和主題管理頁面添加下載按鈕，讓您輕鬆備份外掛程式和主題。</p>';
    }
    
    /**
     * 外掛程式下載器選項回調
     */
    public function plugin_downloader_callback() {
        $value = get_option('wu_enable_plugin_downloader', false);
        echo '<input type="checkbox" name="wu_enable_plugin_downloader" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_enable_plugin_downloader">啟用外掛程式下載器</label>';
        echo '<p class="description">在每個外掛程式旁邊添加下載按鈕，可以將外掛程式打包為 ZIP 檔案下載。</p>';
    }
    
    /**
     * 主題下載器選項回調
     */
    public function theme_downloader_callback() {
        $value = get_option('wu_enable_theme_downloader', false);
        echo '<input type="checkbox" name="wu_enable_theme_downloader" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_enable_theme_downloader">啟用主題下載器</label>';
        echo '<p class="description">在每個主題旁邊添加下載按鈕，可以將主題打包為 ZIP 檔案下載。</p>';
    }
    
    /**
     * 管理頁面
     */
    public function admin_page() {
        if (isset($_POST['submit'])) {
            check_admin_referer('wu_downloader_settings-options');
            
            update_option('wu_enable_plugin_downloader', isset($_POST['wu_enable_plugin_downloader']) ? 1 : 0);
            update_option('wu_enable_theme_downloader', isset($_POST['wu_enable_theme_downloader']) ? 1 : 0);
            
            echo '<div class="updated"><p>設定已儲存！</p></div>';
        }
        
        $plugins = get_plugins();
        $themes = wp_get_themes();
        $plugin_enabled = get_option('wu_enable_plugin_downloader', false);
        $theme_enabled = get_option('wu_enable_theme_downloader', false);
        ?>
        
        <div class="wrap">
            <h1>增強下載器</h1>
            
            <div class="wu-stats" style="background: #fff; padding: 15px; margin-bottom: 20px; border: 1px solid #ddd;">
                <p><strong>已安裝外掛程式：</strong> <?php echo count($plugins); ?> 個</p>
                <p><strong>已安裝主題：</strong> <?php echo count($themes); ?> 個</p>
                <p><strong>外掛程式下載器：</strong> <?php echo $plugin_enabled ? '<span style="color: green;">已啟用</span>' : '<span style="color: red;">未啟用</span>'; ?></p>
                <p><strong>主題下載器：</strong> <?php echo $theme_enabled ? '<span style="color: green;">已啟用</span>' : '<span style="color: red;">未啟用</span>'; ?></p>
            </div>
            
            <form method="post" action="">
                <?php
                settings_fields('wu_downloader_settings');
                do_settings_sections('wu_downloader_settings');
                submit_button();
                ?>
            </form>
            
            <?php if ($theme_enabled): ?>
            <div class="wu-theme-list" style="margin-top: 30px;">
                <h2>當前佈景主題清單</h2>
                <div class="theme-browser">
                    <div class="themes wp-clearfix">
                        <?php foreach ($themes as $stylesheet => $theme): ?>
                        <div class="theme" style="border: 1px solid #ddd; margin-bottom: 15px; padding: 15px; background: #fff;">
                            <div class="theme-screenshot">
                                <?php if ($theme->get_screenshot()): ?>
                                <img src="<?php echo esc_url($theme->get_screenshot()); ?>" alt="<?php echo esc_attr($theme->get('Name')); ?>" style="max-width: 150px; height: auto; float: left; margin-right: 15px;" />
                                <?php endif; ?>
                            </div>
                            <div class="theme-info">
                                <h3 class="theme-name"><?php echo esc_html($theme->get('Name')); ?></h3>
                                <p class="theme-description"><?php echo esc_html($theme->get('Description')); ?></p>
                                <p><strong>版本：</strong><?php echo esc_html($theme->get('Version')); ?></p>
                                <p><strong>作者：</strong><?php echo esc_html($theme->get('Author')); ?></p>
                                <p><strong>狀態：</strong><?php echo (get_stylesheet() === $stylesheet) ? '<span style="color: green;">使用中</span>' : '未使用'; ?></p>
                                
                                <div class="theme-actions" style="margin-top: 15px;">
                                    <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=wu_download_theme&theme=' . urlencode($stylesheet)), 'download_theme_' . $stylesheet); ?>" 
                                       class="button button-primary">下載主題</a>
                                </div>
                            </div>
                            <div style="clear: both;"></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
        </div>
        
        <?php
    }
    
    /**
     * 啟用外掛程式下載器
     */
    public function enable_plugin_downloader() {
        add_filter('plugin_action_links', array($this, 'add_plugin_download_link'), 10, 2);
    }
    
    /**
     * 啟用主題下載器
     */
    public function enable_theme_downloader() {
        add_filter('theme_action_links', array($this, 'add_theme_download_link'), 10, 2);
    }
    
    /**
     * 添加外掛程式下載連結
     */
    public function add_plugin_download_link($links, $file) {
        $download_url = wp_nonce_url(
            admin_url('admin-post.php?action=wu_download_plugin&plugin=' . urlencode($file)),
            'download_plugin_' . $file
        );
        $links[] = '<a href="' . esc_url($download_url) . '">下載</a>';
        return $links;
    }
    
    /**
     * 添加主題下載連結
     */
    public function add_theme_download_link($links, $stylesheet) {
        $download_url = wp_nonce_url(
            admin_url('admin-post.php?action=wu_download_theme&theme=' . urlencode($stylesheet)),
            'download_theme_' . $stylesheet
        );
        $links[] = '<a href="' . esc_url($download_url) . '">下載</a>';
        return $links;
    }
    
    /**
     * 下載外掛程式
     */
    public function download_plugin() {
        if (!current_user_can('manage_options')) {
            wp_die('您沒有權限執行此操作。');
        }
        
        $plugin = sanitize_text_field($_GET['plugin']);
        
        if (!wp_verify_nonce($_GET['_wpnonce'], 'download_plugin_' . $plugin)) {
            wp_die('安全驗證失敗。');
        }
        
        $plugins = get_plugins();
        if (!isset($plugins[$plugin])) {
            wp_die('外掛程式不存在。');
        }
        
        $plugin_data = $plugins[$plugin];
        $plugin_slug = dirname($plugin);
        
        if ($plugin_slug === '.') {
            $plugin_slug = basename($plugin, '.php');
        }
        
        $plugin_path = WP_PLUGIN_DIR . '/' . $plugin_slug;
        
        if (!file_exists($plugin_path)) {
            wp_die('外掛程式檔案不存在。');
        }
        
        $this->create_and_download_zip($plugin_path, $plugin_slug . '.zip');
    }
    
    /**
     * 下載主題
     */
    public function download_theme() {
        if (!current_user_can('manage_options')) {
            wp_die('您沒有權限執行此操作。');
        }
        
        $theme = sanitize_text_field($_GET['theme']);
        
        if (!wp_verify_nonce($_GET['_wpnonce'], 'download_theme_' . $theme)) {
            wp_die('安全驗證失敗。');
        }
        
        $themes = wp_get_themes();
        if (!isset($themes[$theme])) {
            wp_die('主題不存在。');
        }
        
        $theme_data = $themes[$theme];
        $theme_path = $theme_data->get_stylesheet_directory();
        
        if (!file_exists($theme_path)) {
            wp_die('主題檔案不存在。');
        }
        
        $this->create_and_download_zip($theme_path, $theme . '.zip');
    }
    
    /**
     * 建立並下載 ZIP 檔案
     */
    private function create_and_download_zip($source_path, $filename) {
        if (!class_exists('ZipArchive')) {
            wp_die('伺服器不支援 ZipArchive 擴展。');
        }
        
        $zip = new ZipArchive();
        $temp_file = tempnam(sys_get_temp_dir(), 'wu_download_');
        
        if ($zip->open($temp_file, ZipArchive::CREATE) !== TRUE) {
            wp_die('無法建立壓縮檔案。');
        }
        
        if (is_file($source_path)) {
            $zip->addFile($source_path, basename($source_path));
        } else {
            $this->add_folder_to_zip($zip, $source_path, '');
        }
        
        $zip->close();
        
        if (!file_exists($temp_file)) {
            wp_die('壓縮檔案建立失敗。');
        }
        
        // 設定下載標頭
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($temp_file));
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');
        
        // 輸出檔案內容
        readfile($temp_file);
        
        // 清理暫存檔案
        unlink($temp_file);
        
        exit;
    }
    
    /**
     * 遞迴添加資料夾到 ZIP
     */
    private function add_folder_to_zip($zip, $folder_path, $parent_folder) {
        $files = new DirectoryIterator($folder_path);
        
        foreach ($files as $file) {
            if ($file->isDot()) {
                continue;
            }
            
            $file_path = $file->getPathname();
            $relative_path = $parent_folder . $file->getFilename();
            
            if ($file->isDir()) {
                $zip->addEmptyDir($relative_path);
                $this->add_folder_to_zip($zip, $file_path, $relative_path . '/');
            } else {
                $zip->addFile($file_path, $relative_path);
            }
        }
    }
}

// 初始化插件
new WU_Enhanced_Downloader();
?>
