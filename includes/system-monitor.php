<?php
/**
 * 系統監控模組
 * 功能：顯示系統資訊和記憶體使用情況
 */

if (!defined('ABSPATH')) exit;

class WU_System_Monitor {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        
        // 如果啟用了系統監控，則顯示資訊
        if (get_option('wu_enable_system_monitor', false)) {
            $this->enable_system_monitor();
        }
    }
    
    /**
     * 添加管理頁面
     */
    public function add_admin_menu() {
        add_submenu_page(
            'wumetax-toolkit',
            '系統監控',
            '系統監控',
            'manage_options',
            'wumetax-system-monitor',
            array($this, 'admin_page')
        );
    }
    
    /**
     * 初始化設定
     */
    public function admin_init() {
        register_setting('wu_system_monitor_settings', 'wu_enable_system_monitor');
        register_setting('wu_system_monitor_settings', 'wu_show_memory_footer');
        register_setting('wu_system_monitor_settings', 'wu_memory_warning_threshold');
        register_setting('wu_system_monitor_settings', 'wu_memory_critical_threshold');
        
        add_settings_section(
            'wu_system_monitor_section',
            '系統監控設定',
            array($this, 'settings_section_callback'),
            'wu_system_monitor_settings'
        );
        
        add_settings_field(
            'wu_enable_system_monitor',
            '啟用系統監控',
            array($this, 'enable_monitor_callback'),
            'wu_system_monitor_settings',
            'wu_system_monitor_section'
        );
        
        add_settings_field(
            'wu_show_memory_footer',
            '在頁腳顯示記憶體資訊',
            array($this, 'show_memory_footer_callback'),
            'wu_system_monitor_settings',
            'wu_system_monitor_section'
        );
        
        add_settings_field(
            'wu_memory_warning_threshold',
            '記憶體警告閾值',
            array($this, 'memory_warning_callback'),
            'wu_system_monitor_settings',
            'wu_system_monitor_section'
        );
        
        add_settings_field(
            'wu_memory_critical_threshold',
            '記憶體嚴重警告閾值',
            array($this, 'memory_critical_callback'),
            'wu_system_monitor_settings',
            'wu_system_monitor_section'
        );
    }
    
    /**
     * 設定區域說明
     */
    public function settings_section_callback() {
        echo '<p>系統監控功能可以即時顯示 WordPress 安裝的記憶體使用情況和系統資訊，幫助您監控網站效能。</p>';
        echo '<p><strong>建議：</strong>啟用此功能可以幫助您及時發現效能問題並進行優化。</p>';
    }
    
    /**
     * 啟用系統監控選項回調
     */
    public function enable_monitor_callback() {
        $value = get_option('wu_enable_system_monitor', false);
        echo '<input type="checkbox" id="wu_enable_system_monitor" name="wu_enable_system_monitor" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_enable_system_monitor">啟用系統監控功能</label>';
        echo '<p class="description">啟用後將在管理頁面顯示系統資訊和效能監控。</p>';
    }
    
    /**
     * 顯示記憶體頁腳選項回調
     */
    public function show_memory_footer_callback() {
        $value = get_option('wu_show_memory_footer', true);
        echo '<input type="checkbox" id="wu_show_memory_footer" name="wu_show_memory_footer" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_show_memory_footer">在管理頁面頁腳顯示記憶體使用資訊</label>';
        echo '<p class="description">在每個管理頁面的頁腳顯示當前記憶體使用情況。</p>';
    }
    
    /**
     * 記憶體警告閾值選項回調
     */
    public function memory_warning_callback() {
        $value = get_option('wu_memory_warning_threshold', 75);
        echo '<input type="number" id="wu_memory_warning_threshold" name="wu_memory_warning_threshold" value="' . esc_attr($value) . '" min="50" max="95" step="5" />';
        echo '<label for="wu_memory_warning_threshold">%</label>';
        echo '<p class="description">當記憶體使用率超過此百分比時，以淺紅色顯示警告。</p>';
    }
    
    /**
     * 記憶體嚴重警告閾值選項回調
     */
    public function memory_critical_callback() {
        $value = get_option('wu_memory_critical_threshold', 90);
        echo '<input type="number" id="wu_memory_critical_threshold" name="wu_memory_critical_threshold" value="' . esc_attr($value) . '" min="80" max="99" step="5" />';
        echo '<label for="wu_memory_critical_threshold">%</label>';
        echo '<p class="description">當記憶體使用率超過此百分比時，以紅色顯示嚴重警告。</p>';
    }
    
    /**
     * 管理頁面
     */
    public function admin_page() {
        if (isset($_POST['submit'])) {
            check_admin_referer('wu_system_monitor_settings-options');
            
            // 處理表單提交
            update_option('wu_enable_system_monitor', isset($_POST['wu_enable_system_monitor']) ? 1 : 0);
            update_option('wu_show_memory_footer', isset($_POST['wu_show_memory_footer']) ? 1 : 0);
            update_option('wu_memory_warning_threshold', intval($_POST['wu_memory_warning_threshold']));
            update_option('wu_memory_critical_threshold', intval($_POST['wu_memory_critical_threshold']));
            
            echo '<div class="notice notice-success"><p>設定已儲存！</p></div>';
        }
        
        $system_info = $this->get_system_info();
        $memory_info = $this->get_memory_info();
        ?>
        <div class="wrap">
            <h1>系統監控設定</h1>
            
            <div class="card">
                <h2>當前系統狀態</h2>
                <div class="system-info-grid">
                    <div class="info-item">
                        <strong>記憶體使用率：</strong>
                        <span class="memory-usage <?php echo $memory_info['status_class']; ?>">
                            <?php echo $memory_info['percentage']; ?>%
                        </span>
                        <div class="memory-bar">
                            <div class="memory-progress <?php echo $memory_info['status_class']; ?>" style="width: <?php echo $memory_info['percentage']; ?>%"></div>
                        </div>
                        <small><?php echo $memory_info['used_formatted']; ?> / <?php echo $memory_info['total_formatted']; ?></small>
                    </div>
                    
                    <div class="info-item">
                        <strong>PHP 版本：</strong> <?php echo $system_info['php_version']; ?>
                    </div>
                    
                    <div class="info-item">
                        <strong>MySQL 版本：</strong> <?php echo $system_info['mysql_version']; ?>
                    </div>
                    
                    <div class="info-item">
                        <strong>WordPress 版本：</strong> <?php echo $system_info['wp_version']; ?>
                    </div>
                    
                    <div class="info-item">
                        <strong>永久連結結構：</strong> <?php echo $system_info['permalink_structure']; ?>
                    </div>
                    
                    <div class="info-item">
                        <strong>搜尋引擎可見性：</strong> 
                        <span class="<?php echo $system_info['search_visibility'] ? 'visibility-hidden' : 'visibility-visible'; ?>">
                            <?php echo $system_info['search_visibility'] ? '已隱藏' : '可見'; ?>
                        </span>
                    </div>
                    
                    <div class="info-item">
                        <strong>時區：</strong> <?php echo $system_info['timezone']; ?>
                    </div>
                    
                    <div class="info-item">
                        <strong>當前時間：</strong> <?php echo $system_info['current_time']; ?>
                    </div>
                    
                    <div class="info-item">
                        <strong>您的 IP 位址：</strong> <?php echo $system_info['user_ip']; ?>
                    </div>
                    
                    <div class="info-item">
                        <strong>伺服器 IP：</strong> <?php echo $system_info['server_ip']; ?>
                    </div>
                </div>
            </div>
            
            <form method="post" action="">
                <?php
                settings_fields('wu_system_monitor_settings');
                do_settings_sections('wu_system_monitor_settings');
                wp_nonce_field('wu_system_monitor_settings-options');
                submit_button();
                ?>
            </form>
            
            <div class="card">
                <h2>詳細系統資訊</h2>
                
                <h3>伺服器環境</h3>
                <table class="wp-list-table widefat fixed striped">
                    <tbody>
                        <tr><td><strong>作業系統</strong></td><td><?php echo $system_info['os']; ?></td></tr>
                        <tr><td><strong>伺服器軟體</strong></td><td><?php echo $system_info['server_software']; ?></td></tr>
                        <tr><td><strong>PHP 記憶體限制</strong></td><td><?php echo $system_info['php_memory_limit']; ?></td></tr>
                        <tr><td><strong>最大執行時間</strong></td><td><?php echo $system_info['max_execution_time']; ?> 秒</td></tr>
                        <tr><td><strong>最大上傳檔案大小</strong></td><td><?php echo $system_info['max_upload_size']; ?></td></tr>
                        <tr><td><strong>最大 POST 大小</strong></td><td><?php echo $system_info['post_max_size']; ?></td></tr>
                    </tbody>
                </table>
                
                <h3>WordPress 配置</h3>
                <table class="wp-list-table widefat fixed striped">
                    <tbody>
                        <tr><td><strong>WordPress 記憶體限制</strong></td><td><?php echo $system_info['wp_memory_limit']; ?></td></tr>
                        <tr><td><strong>調試模式</strong></td><td><?php echo $system_info['wp_debug'] ? '啟用' : '禁用'; ?></td></tr>
                        <tr><td><strong>多站點</strong></td><td><?php echo $system_info['multisite'] ? '是' : '否'; ?></td></tr>
                        <tr><td><strong>已安裝外掛程式</strong></td><td><?php echo $system_info['plugin_count']; ?> 個</td></tr>
                        <tr><td><strong>已安裝主題</strong></td><td><?php echo $system_info['theme_count']; ?> 個</td></tr>
                        <tr><td><strong>當前主題</strong></td><td><?php echo $system_info['current_theme']; ?></td></tr>
                    </tbody>
                </table>
                
                <h3>資料庫資訊</h3>
                <table class="wp-list-table widefat fixed striped">
                    <tbody>
                        <tr><td><strong>資料庫大小</strong></td><td><?php echo $system_info['db_size']; ?></td></tr>
                        <tr><td><strong>資料表數量</strong></td><td><?php echo $system_info['db_tables']; ?> 個</td></tr>
                        <tr><td><strong>字符集</strong></td><td><?php echo $system_info['db_charset']; ?></td></tr>
                        <tr><td><strong>排序規則</strong></td><td><?php echo $system_info['db_collate']; ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <style>
        .system-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .info-item {
            padding: 10px;
            background: #f9f9f9;
            border-left: 3px solid #0073aa;
            border-radius: 3px;
        }
        .memory-usage {
            font-weight: bold;
            font-size: 1.1em;
        }
        .memory-usage.normal { color: #00a32a; }
        .memory-usage.warning { color: #f56e28; }
        .memory-usage.critical { color: #d63638; }
        .memory-bar {
            width: 100%;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            margin: 5px 0;
            overflow: hidden;
        }
        .memory-progress {
            height: 100%;
            transition: width 0.3s ease;
        }
        .memory-progress.normal { background: #00a32a; }
        .memory-progress.warning { background: #f56e28; }
        .memory-progress.critical { background: #d63638; }
        .visibility-visible { color: #00a32a; font-weight: bold; }
        .visibility-hidden { color: #d63638; font-weight: bold; }
        .card { background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin: 20px 0; }
        .card h2 { margin-top: 0; }
        .card h3 { color: #23282d; margin-top: 30px; }
        .wp-list-table td { padding: 8px 10px; }
        </style>
        <?php
    }
    
    /**
     * 啟用系統監控
     */
    private function enable_system_monitor() {
        // 在管理頁面添加系統狀態顯示
        add_action('admin_notices', array($this, 'show_system_status_notice'));
        
        // 在頁腳顯示記憶體資訊（如果啟用）
        if (get_option('wu_show_memory_footer', true)) {
            add_filter('admin_footer_text', array($this, 'add_memory_info_to_footer'));
        }
    }
    
    /**
     * 顯示系統狀態通知
     */
    public function show_system_status_notice() {
        $memory_info = $this->get_memory_info();
        $system_info = $this->get_system_info();
        
        $status_class = $memory_info['status_class'];
        $status_text = $status_class === 'normal' ? '正常' : ($status_class === 'warning' ? '警告' : '嚴重');
        
        echo '<div class="notice notice-info is-dismissible">';
        echo '<h4 style="margin-top: 0;">🖥️ 系統監控狀態</h4>';
        echo '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 10px 0;">';
        echo '<div><strong>記憶體使用率：</strong><span class="memory-usage ' . $status_class . '">' . $memory_info['percentage'] . '%</span> (' . $memory_info['used_formatted'] . ' / ' . $memory_info['total_formatted'] . ')</div>';
        echo '<div><strong>PHP 版本：</strong>' . $system_info['php_version'] . '</div>';
        echo '<div><strong>WordPress 版本：</strong>' . $system_info['wp_version'] . '</div>';
        echo '<div><strong>已安裝外掛：</strong>' . $system_info['plugin_count'] . ' 個</div>';
        echo '</div>';
        echo '<div class="memory-bar" style="width: 100%; height: 8px; background: #e0e0e0; border-radius: 4px; margin: 5px 0; overflow: hidden;">';
        echo '<div class="memory-progress ' . $status_class . '" style="width: ' . $memory_info['percentage'] . '%; height: 100%; transition: width 0.3s ease; background: ' . ($status_class === 'normal' ? '#00a32a' : ($status_class === 'warning' ? '#f56e28' : '#d63638')) . ';"></div>';
        echo '</div>';
        echo '</div>';
        
        // 添加樣式
        echo '<style>
        .memory-usage.normal { color: #00a32a; font-weight: bold; }
        .memory-usage.warning { color: #f56e28; font-weight: bold; }
        .memory-usage.critical { color: #d63638; font-weight: bold; }
        </style>';
    }
    
    /**
     * 在頁腳添加記憶體資訊
     */
    public function add_memory_info_to_footer($text) {
        $memory_info = $this->get_memory_info();
        
        $memory_text = sprintf(
            '<span style="margin-left: 20px;">記憶體使用: <span class="memory-usage %s">%s</span> (%s / %s)</span>',
            $memory_info['status_class'],
            $memory_info['percentage'] . '%',
            $memory_info['used_formatted'],
            $memory_info['total_formatted']
        );
        
        // 添加 CSS 樣式
        $memory_text .= '
        <style>
        .memory-usage.normal { color: #00a32a; font-weight: bold; }
        .memory-usage.warning { color: #f56e28; font-weight: bold; }
        .memory-usage.critical { color: #d63638; font-weight: bold; }
        </style>';
        
        return $text . $memory_text;
    }
    
    /**
     * 獲取記憶體資訊
     */
    private function get_memory_info() {
        $memory_limit = $this->get_memory_limit();
        $memory_used = memory_get_peak_usage(true);
        $memory_percentage = ($memory_used / $memory_limit) * 100;
        
        $warning_threshold = get_option('wu_memory_warning_threshold', 75);
        $critical_threshold = get_option('wu_memory_critical_threshold', 90);
        
        // 確定狀態類別
        $status_class = 'normal';
        if ($memory_percentage >= $critical_threshold) {
            $status_class = 'critical';
        } elseif ($memory_percentage >= $warning_threshold) {
            $status_class = 'warning';
        }
        
        return array(
            'used' => $memory_used,
            'total' => $memory_limit,
            'percentage' => round($memory_percentage, 1),
            'used_formatted' => $this->format_bytes($memory_used),
            'total_formatted' => $this->format_bytes($memory_limit),
            'status_class' => $status_class
        );
    }
    
    /**
     * 獲取記憶體限制
     */
    private function get_memory_limit() {
        $memory_limit = ini_get('memory_limit');
        
        if (empty($memory_limit) || $memory_limit == -1) {
            return 128 * 1024 * 1024; // 預設 128MB
        }
        
        // 轉換為位元組
        $unit = strtolower($memory_limit[strlen($memory_limit) - 1]);
        $value = (int) $memory_limit;
        
        switch ($unit) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }
    
    /**
     * 獲取系統資訊
     */
    private function get_system_info() {
        global $wpdb;
        
        // 獲取資料庫大小
        $db_size = $this->get_database_size();
        
        // 獲取用戶 IP
        $user_ip = $this->get_user_ip();
        
        // 獲取伺服器 IP
        $server_ip = $_SERVER['SERVER_ADDR'] ?? gethostbyname($_SERVER['SERVER_NAME'] ?? 'localhost');
        
        return array(
            'php_version' => PHP_VERSION,
            'mysql_version' => $wpdb->db_version(),
            'wp_version' => get_bloginfo('version'),
            'permalink_structure' => get_option('permalink_structure') ?: '預設',
            'search_visibility' => get_option('blog_public') == 0,
            'timezone' => get_option('timezone_string') ?: get_option('gmt_offset'),
            'current_time' => current_time('Y-m-d H:i:s'),
            'user_ip' => $user_ip,
            'server_ip' => $server_ip,
            'os' => PHP_OS,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'php_memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'max_upload_size' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'wp_memory_limit' => WP_MEMORY_LIMIT,
            'wp_debug' => defined('WP_DEBUG') && WP_DEBUG,
            'multisite' => is_multisite(),
            'plugin_count' => count(get_plugins()),
            'theme_count' => count(wp_get_themes()),
            'current_theme' => wp_get_theme()->get('Name'),
            'db_size' => $this->format_bytes($db_size),
            'db_tables' => count($wpdb->get_results("SHOW TABLES")),
            'db_charset' => $wpdb->charset,
            'db_collate' => $wpdb->collate
        );
    }
    
    /**
     * 獲取資料庫大小
     */
    private function get_database_size() {
        global $wpdb;
        
        $result = $wpdb->get_var("
            SELECT SUM(data_length + index_length) 
            FROM information_schema.TABLES 
            WHERE table_schema = '" . DB_NAME . "'
        ");
        
        return $result ?: 0;
    }
    
    /**
     * 獲取用戶 IP 位址
     */
    private function get_user_ip() {
        $ip_keys = array(
            'HTTP_CF_CONNECTING_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    }
    
    /**
     * 格式化位元組
     */
    private function format_bytes($size, $precision = 2) {
        if ($size === 0) return '0 B';
        
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $base = log($size, 1024);
        
        return round(pow(1024, $base - floor($base)), $precision) . ' ' . $units[floor($base)];
    }
}

// 初始化模組
new WU_System_Monitor();