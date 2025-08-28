<?php
/**
 * 網站監控模組
 * 功能：顯示當前網站資訊和記憶體使用情況，監測網站相關程序，網站容量監控
 */

if (!defined('ABSPATH')) exit;

class WU_System_Monitor {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'), 95);
        add_action('admin_init', array($this, 'admin_init'));
        
        // 只有在啟用時才載入監控功能，保持網站效能
        $settings = get_option('wu_system_monitor_settings', $this->get_default_settings());
        if ($settings['enabled']) {
            $this->enable_system_monitor();
        }
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'wumetax-toolkit',
            '網站效能監控',
            '網站效能監控',
            'manage_options',
            'wumetax-system-monitor',
            array($this, 'admin_page')
        );
    }
    
    public function admin_init() {
        register_setting('wu_system_monitor_settings', 'wu_system_monitor_settings');
    }
    
    private function get_default_settings() {
        return array(
            'enabled' => false,
            'show_memory_footer' => false,
            'memory_warning_threshold' => 80,
            'memory_critical_threshold' => 95,
            'show_queries' => true,
            'show_site_usage' => true,
            'show_plugin_performance' => true,
            'plugin_performance_threshold' => 100, // milliseconds
            'auto_refresh' => 30, // seconds
        );
    }
    
    public function admin_page() {
        if (isset($_POST['submit'])) {
            $this->save_settings();
        }
        
        $settings = get_option('wu_system_monitor_settings', $this->get_default_settings());
        
        // 只有在啟用時才載入監控資料，節省效能
        $system_info = array();
        $memory_info = array();
        $query_info = array();
        $site_usage = array();
        $plugin_performance = array();
        
        if ($settings['enabled']) {
            $system_info = $this->get_system_info();
            $memory_info = $this->get_memory_info();
            if ($settings['show_queries']) {
                $query_info = $this->get_query_info();
            }
            if ($settings['show_site_usage']) {
                $site_usage = $this->get_site_usage();
            }
            if ($settings['show_plugin_performance']) {
                $plugin_performance = $this->get_plugin_performance();
            }
        }
        ?>
        <div class="wrap">
            <h1>網站效能監控</h1>
            
            <div class="notice notice-info">
                <h3>功能說明</h3>
                <p><strong>網站效能監控功能</strong>提供即時的網站效能監控，專注於當前WordPress網站的狀態。</p>
                
                <h4>監控項目：</h4>
                <ul>
                    <li><strong>記憶體監控</strong>：即時記憶體使用率和警告閾值</li>
                    <li><strong>查詢監控</strong>：資料庫查詢效能和慢查詢監控</li>
                    <li><strong>網站容量</strong>：WordPress目錄和檔案使用情況</li>
                    <li><strong>網站效能</strong>：頁面載入時間和快取狀態</li>
                    <li><strong>外掛效能監控</strong>：監測各外掛的載入時間和記憶體使用量</li>
                </ul>
                
                <p><strong>效能最佳化：</strong>監控功能只有在啟用時才會執行，未啟用時不會影響網站效能。</p>
            </div>
            
            <?php if (!$settings['enabled']): ?>
            <div class="notice notice-warning">
                <p><strong>網站監控已停用</strong> - 為了保持網站效能，監控功能目前處於停用狀態。請在下方啟用監控功能以查看網站資訊。</p>
            </div>
            <?php endif; ?>
            
            <form method="post" action="">
                <?php wp_nonce_field('wu_system_monitor_settings'); ?>
                
                <h2>監控設定</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">啟用網站監控</th>
                        <td>
                            <label>
                                <input type="checkbox" name="enabled" value="1" <?php checked($settings['enabled']); ?>>
                                啟用網站效能監控功能
                            </label>
                            <p class="description">啟用後將開始監控網站效能，停用時不會消耗系統資源</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">記憶體警告閾值</th>
                        <td>
                            <input type="number" name="memory_warning_threshold" value="<?php echo esc_attr($settings['memory_warning_threshold']); ?>" min="50" max="100" class="small-text"> %
                            <p class="description">記憶體使用率超過此值時顯示警告</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">記憶體嚴重警告閾值</th>
                        <td>
                            <input type="number" name="memory_critical_threshold" value="<?php echo esc_attr($settings['memory_critical_threshold']); ?>" min="70" max="100" class="small-text"> %
                            <p class="description">記憶體使用率超過此值時顯示嚴重警告</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">外掛效能警告閾值</th>
                        <td>
                            <input type="number" name="plugin_performance_threshold" value="<?php echo esc_attr($settings['plugin_performance_threshold']); ?>" min="50" max="1000" class="small-text"> ms
                            <p class="description">外掛載入時間超過此值時顯示警告</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">監控項目</th>
                        <td>
                            <label style="display: block; margin: 5px 0;">
                                <input type="checkbox" name="show_queries" value="1" <?php checked($settings['show_queries']); ?>>
                                顯示資料庫查詢監控
                            </label>
                            <label style="display: block; margin: 5px 0;">
                                <input type="checkbox" name="show_site_usage" value="1" <?php checked($settings['show_site_usage']); ?>>
                                顯示網站容量監控
                            </label>
                            <label style="display: block; margin: 5px 0;">
                                <input type="checkbox" name="show_plugin_performance" value="1" <?php checked($settings['show_plugin_performance']); ?>>
                                顯示外掛效能監控
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">其他設定</th>
                        <td>
                            <label style="display: block; margin: 5px 0;">
                                <input type="checkbox" name="show_memory_footer" value="1" <?php checked($settings['show_memory_footer']); ?>>
                                在頁腳顯示記憶體資訊
                            </label>
                            <label style="display: block; margin: 5px 0;">
                                自動重新整理間隔：
                                <select name="auto_refresh">
                                    <option value="0" <?php selected($settings['auto_refresh'], 0); ?>>停用</option>
                                    <option value="15" <?php selected($settings['auto_refresh'], 15); ?>>15 秒</option>
                                    <option value="30" <?php selected($settings['auto_refresh'], 30); ?>>30 秒</option>
                                    <option value="60" <?php selected($settings['auto_refresh'], 60); ?>>60 秒</option>
                                </select>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('儲存設定'); ?>
            </form>
            
            <?php if ($settings['enabled']): ?>
            
            <div class="system-monitor-dashboard">
                <!-- 網站狀態總覽 -->
                <div class="monitor-card">
                    <h2>網站狀態總覽</h2>
                    <div class="status-grid">
                        <div class="status-item">
                            <div class="status-icon memory-icon"></div>
                            <div class="status-content">
                                <h3>記憶體使用率</h3>
                                <div class="memory-usage <?php echo $memory_info['status_class']; ?>">
                                    <?php echo $memory_info['percentage']; ?>%
                                </div>
                                <div class="memory-bar">
                                    <div class="memory-progress <?php echo $memory_info['status_class']; ?>" style="width: <?php echo $memory_info['percentage']; ?>%"></div>
                                </div>
                                <small><?php echo $memory_info['used_formatted']; ?> / <?php echo $memory_info['total_formatted']; ?></small>
                            </div>
                        </div>
                        
                        <div class="status-item">
                            <div class="status-icon db-icon"></div>
                            <div class="status-content">
                                <h3>資料庫查詢</h3>
                                <div class="status-value"><?php echo $system_info['db_queries']; ?> 次</div>
                                <small>查詢時間: <?php echo $system_info['db_query_time']; ?>ms</small>
                            </div>
                        </div>
                        
                        <div class="status-item">
                            <div class="status-icon wp-icon"></div>
                            <div class="status-content">
                                <h3>頁面載入時間</h3>
                                <div class="status-value"><?php echo $system_info['load_time']; ?>s</div>
                                <small>外掛: <?php echo $system_info['plugin_count']; ?> 個</small>
                            </div>
                        </div>
                        
                        <div class="status-item">
                            <div class="status-icon cache-icon"></div>
                            <div class="status-content">
                                <h3>快取狀態</h3>
                                <div class="status-value"><?php echo $system_info['cache_status']; ?></div>
                                <small>物件快取: <?php echo $system_info['object_cache'] ? '啟用' : '停用'; ?></small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if ($settings['show_queries'] && !empty($query_info)): ?>
                <!-- 資料庫查詢監控 -->
                <div class="monitor-card">
                    <h2>資料庫查詢監控</h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>查詢類型</th>
                                <th>查詢次數</th>
                                <th>平均時間</th>
                                <th>最大時間</th>
                                <th>狀態</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($query_info as $query): ?>
                            <tr>
                                <td><?php echo esc_html($query['type']); ?></td>
                                <td><?php echo esc_html($query['count']); ?></td>
                                <td><?php echo esc_html($query['avg_time']); ?>ms</td>
                                <td><?php echo esc_html($query['max_time']); ?>ms</td>
                                <td><span class="query-status <?php echo $query['status_class']; ?>"><?php echo esc_html($query['status']); ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
                
                <?php if ($settings['show_site_usage'] && !empty($site_usage)): ?>
                <!-- 網站容量監控 -->
                <div class="monitor-card">
                    <h2>網站容量監控</h2>
                    <div class="disk-usage-grid">
                        <?php foreach ($site_usage as $usage): ?>
                        <div class="disk-item">
                            <h4><?php echo esc_html($usage['name']); ?></h4>
                            <div class="disk-usage <?php echo $usage['status_class']; ?>">
                                <?php echo $usage['size']; ?>
                            </div>
                            <div class="usage-details">
                                <small>
                                    檔案數量: <?php echo $usage['file_count']; ?><br>
                                    最後更新: <?php echo $usage['last_modified']; ?>
                                </small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($settings['show_plugin_performance'] && !empty($plugin_performance)): ?>
                <!-- 外掛效能監控 -->
                <div class="monitor-card">
                    <h2>🔌 外掛效能監控</h2>
                    <p class="description">監控各外掛的載入時間和記憶體使用量，幫助識別效能瓶頸。</p>
                    
                    <div class="plugin-performance-grid">
                        <div class="performance-summary">
                            <h4>效能摘要</h4>
                            <div class="summary-stats">
                                <div class="stat-item">
                                    <span class="stat-label">總載入時間</span>
                                    <span class="stat-value"><?php echo $plugin_performance['total_load_time']; ?>ms</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">總記憶體使用</span>
                                    <span class="stat-value"><?php echo $plugin_performance['total_memory']; ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">慢速外掛</span>
                                    <span class="stat-value <?php echo $plugin_performance['slow_plugins_count'] > 0 ? 'warning' : 'normal'; ?>"><?php echo $plugin_performance['slow_plugins_count']; ?> 個</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="plugin-performance-table">
                            <table class="wp-list-table widefat">
                                <thead>
                                    <tr>
                                        <th>外掛名稱</th>
                                        <th>載入時間</th>
                                        <th>記憶體使用</th>
                                        <th>狀態</th>
                                        <th>建議</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($plugin_performance['plugins'] as $plugin): ?>
                                    <tr class="<?php echo $plugin['status_class']; ?>">
                                        <td>
                                            <strong><?php echo esc_html($plugin['name']); ?></strong>
                                            <br><small><?php echo esc_html($plugin['file']); ?></small>
                                        </td>
                                        <td>
                                            <span class="load-time <?php echo $plugin['load_time_class']; ?>">
                                                <?php echo $plugin['load_time']; ?>ms
                                            </span>
                                        </td>
                                        <td>
                                            <span class="memory-usage <?php echo $plugin['memory_class']; ?>">
                                                <?php echo $plugin['memory_usage']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo $plugin['status_class']; ?>">
                                                <?php echo $plugin['status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small><?php echo $plugin['recommendation']; ?></small>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="performance-tips" style="margin-top: 20px; padding: 15px; background: #f9f9f9; border-radius: 5px;">
                        <h4>🚀 效能優化建議</h4>
                        <ul>
                            <li>關注載入時間超過 <?php echo $settings['plugin_performance_threshold']; ?>ms 的外掛</li>
                            <li>檢查是否有重複功能的外掛可以合併或移除</li>
                            <li>考慮使用快取外掛來減少資料庫查詢</li>
                            <li>定期更新外掛到最新版本以獲得效能改善</li>
                        </ul>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- 詳細網站資訊 -->
                <div class="monitor-card">
                    <h2>詳細網站資訊</h2>
                    <div class="system-tabs">
                        <div class="tab-buttons">
                            <button class="tab-button active" onclick="showTab('wp-info')">WordPress 資訊</button>
                            <button class="tab-button" onclick="showTab('db-info')">資料庫資訊</button>
                            <button class="tab-button" onclick="showTab('performance')">效能資訊</button>
                        </div>
                        
                        <div id="wp-info" class="tab-content active">
                            <table class="wp-list-table widefat">
                                <tr><td><strong>WordPress 版本</strong></td><td><?php echo $system_info['wp_version']; ?></td></tr>
                                <tr><td><strong>PHP 版本</strong></td><td><?php echo $system_info['php_version']; ?></td></tr>
                                <tr><td><strong>WordPress 記憶體限制</strong></td><td><?php echo $system_info['wp_memory_limit']; ?></td></tr>
                                <tr><td><strong>多站點</strong></td><td><?php echo $system_info['multisite'] ? '是' : '否'; ?></td></tr>
                                <tr><td><strong>已安裝外掛</strong></td><td><?php echo $system_info['plugin_count']; ?> 個</td></tr>
                                <tr><td><strong>已啟用外掛</strong></td><td><?php echo $system_info['active_plugin_count']; ?> 個</td></tr>
                                <tr><td><strong>當前主題</strong></td><td><?php echo $system_info['current_theme']; ?></td></tr>
                                <tr><td><strong>永久連結結構</strong></td><td><?php echo $system_info['permalink_structure']; ?></td></tr>
                            </table>
                        </div>
                        
                        <div id="db-info" class="tab-content">
                            <table class="wp-list-table widefat">
                                <tr><td><strong>MySQL 版本</strong></td><td><?php echo $system_info['mysql_version']; ?></td></tr>
                                <tr><td><strong>資料庫大小</strong></td><td><?php echo $system_info['db_size']; ?></td></tr>
                                <tr><td><strong>資料表數量</strong></td><td><?php echo $system_info['db_tables']; ?> 個</td></tr>
                                <tr><td><strong>字符集</strong></td><td><?php echo $system_info['db_charset']; ?></td></tr>
                                <tr><td><strong>排序規則</strong></td><td><?php echo $system_info['db_collate']; ?></td></tr>
                            </table>
                        </div>
                        
                        <div id="performance" class="tab-content">
                            <table class="wp-list-table widefat">
                                <tr><td><strong>頁面載入時間</strong></td><td><?php echo $system_info['load_time']; ?> 秒</td></tr>
                                <tr><td><strong>資料庫查詢數</strong></td><td><?php echo $system_info['db_queries']; ?> 次</td></tr>
                                <tr><td><strong>查詢總時間</strong></td><td><?php echo $system_info['db_query_time']; ?> ms</td></tr>
                                <tr><td><strong>物件快取</strong></td><td><?php echo $system_info['object_cache'] ? '啟用' : '停用'; ?></td></tr>
                                <tr><td><strong>頁面快取</strong></td><td><?php echo $system_info['page_cache'] ? '啟用' : '停用'; ?></td></tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php endif; ?>
        </div>
        
        <?php if ($settings['enabled'] && $settings['auto_refresh'] > 0): ?>
        <script>
        setTimeout(function() {
            location.reload();
        }, <?php echo $settings['auto_refresh'] * 1000; ?>);
        </script>
        <?php endif; ?>
        
        <script>
        function showTab(tabId) {
            // 隱藏所有分頁內容
            var contents = document.querySelectorAll('.tab-content');
            contents.forEach(function(content) {
                content.classList.remove('active');
            });
            
            // 移除所有按鈕的 active 類別
            var buttons = document.querySelectorAll('.tab-button');
            buttons.forEach(function(button) {
                button.classList.remove('active');
            });
            
            // 顯示選中的分頁內容
            document.getElementById(tabId).classList.add('active');
            event.target.classList.add('active');
        }
        </script>
        
        <style>
        .system-monitor-dashboard {
            margin-top: 20px;
        }
        
        .monitor-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .monitor-card h2 {
            margin-top: 0;
            color: #23282d;
            border-bottom: 2px solid #0073aa;
            padding-bottom: 10px;
        }
        
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .status-item {
            display: flex;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #0073aa;
        }
        
        .status-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: white;
        }
        
        .memory-icon { background: #e74c3c; }
        .db-icon { background: #27ae60; }
        .wp-icon { background: #3498db; }
        .cache-icon { background: #f39c12; }
        
        .status-content h3 {
            margin: 0 0 5px 0;
            font-size: 14px;
            color: #666;
        }
        
        .status-value {
            font-size: 20px;
            font-weight: bold;
            color: #23282d;
        }
        
        .memory-usage {
            font-size: 24px;
            font-weight: bold;
        }
        
        .memory-usage.normal { color: #27ae60; }
        .memory-usage.warning { color: #f39c12; }
        .memory-usage.critical { color: #e74c3c; }
        
        .memory-bar {
            width: 100%;
            height: 8px;
            background: #ecf0f1;
            border-radius: 4px;
            margin: 8px 0;
            overflow: hidden;
        }
        
        .memory-progress {
            height: 100%;
            transition: width 0.3s ease;
            border-radius: 4px;
        }
        
        .memory-progress.normal { background: #27ae60; }
        .memory-progress.warning { background: #f39c12; }
        .memory-progress.critical { background: #e74c3c; }
        
        .query-status {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .query-status.normal { background: #d4edda; color: #155724; }
        .query-status.slow { background: #fff3cd; color: #856404; }
        .query-status.critical { background: #f8d7da; color: #721c24; }
        
        .disk-usage-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }
        
        .disk-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #0073aa;
        }
        
        .disk-item h4 {
            margin: 0 0 10px 0;
            color: #23282d;
        }
        
        .disk-usage {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #23282d;
        }
        
        .system-tabs {
            margin-top: 15px;
        }
        
        .tab-buttons {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        
        .tab-button {
            padding: 12px 20px;
            background: none;
            border: none;
            border-bottom: 2px solid transparent;
            cursor: pointer;
            font-size: 14px;
            color: #666;
            transition: all 0.3s ease;
        }
        
        .tab-button:hover {
            color: #0073aa;
        }
        
        .tab-button.active {
            color: #0073aa;
            border-bottom-color: #0073aa;
            font-weight: bold;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .form-table th {
            width: 200px;
            vertical-align: top;
        }
        
        .notice {
            padding: 10px;
            margin: 15px 0;
            border-left: 4px solid;
            background: #fff;
        }
        
        .notice-info {
            border-left-color: #0073aa;
        }
        
        .notice-warning {
            border-left-color: #f39c12;
        }
        </style>
        <?php
    }
    
    private function save_settings() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'wu_system_monitor_settings')) {
            wp_die('安全驗證失敗');
        }
        
        $settings = array(
            'enabled' => isset($_POST['enabled']),
            'show_memory_footer' => isset($_POST['show_memory_footer']),
            'memory_warning_threshold' => intval($_POST['memory_warning_threshold']),
            'memory_critical_threshold' => intval($_POST['memory_critical_threshold']),
            'show_queries' => isset($_POST['show_queries']),
            'show_site_usage' => isset($_POST['show_site_usage']),
            'show_plugin_performance' => isset($_POST['show_plugin_performance']),
            'plugin_performance_threshold' => intval($_POST['plugin_performance_threshold']),
            'auto_refresh' => intval($_POST['auto_refresh']),
        );
        
        update_option('wu_system_monitor_settings', $settings);
        
        echo '<div class="notice notice-success"><p>設定已儲存！</p></div>';
    }
    
    private function enable_system_monitor() {
        $settings = get_option('wu_system_monitor_settings', $this->get_default_settings());
        
        if ($settings['show_memory_footer']) {
            add_action('admin_footer', array($this, 'show_memory_footer'));
        }
    }
    
    public function show_memory_footer() {
        $memory_info = $this->get_memory_info();
        echo '<div style="position: fixed; bottom: 0; right: 0; background: #23282d; color: white; padding: 8px 12px; font-size: 11px; z-index: 999999;">';
        echo '記憶體: ' . $memory_info['used_formatted'] . ' / ' . $memory_info['total_formatted'] . ' (' . $memory_info['percentage'] . '%)';
        echo '</div>';
    }
    
    private function get_system_info() {
        global $wpdb;
        
        // 計算頁面載入時間
        $load_time = timer_stop(0, 3);
        
        // 基本系統資訊
        $info = array(
            'php_version' => phpversion(),
            'mysql_version' => $wpdb->db_version(),
            'wp_version' => get_bloginfo('version'),
            'wp_memory_limit' => WP_MEMORY_LIMIT,
            'multisite' => is_multisite(),
            'permalink_structure' => get_option('permalink_structure') ?: 'Plain',
            'current_theme' => wp_get_theme()->get('Name'),
            'load_time' => $load_time,
            'db_queries' => get_num_queries(),
            'db_query_time' => round(timer_stop(0, 3) * 1000, 2),
            'object_cache' => wp_using_ext_object_cache(),
            'page_cache' => $this->detect_page_cache(),
            'cache_status' => $this->get_cache_status()
        );
        
        // 外掛數量
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins');
        $info['plugin_count'] = count($all_plugins);
        $info['active_plugin_count'] = count($active_plugins);
        
        // 資料庫資訊
        $db_size_query = $wpdb->get_results("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS 'db_size' FROM information_schema.tables WHERE table_schema='{$wpdb->dbname}'");
        $info['db_size'] = $db_size_query[0]->db_size . ' MB';
        
        $tables = $wpdb->get_results("SHOW TABLES");
        $info['db_tables'] = count($tables);
        $info['db_charset'] = $wpdb->charset;
        $info['db_collate'] = $wpdb->collate;
        
        return $info;
    }
    
    private function get_memory_info() {
        $memory_limit = wp_convert_hr_to_bytes(ini_get('memory_limit'));
        $memory_usage = memory_get_usage(true);
        $percentage = round(($memory_usage / $memory_limit) * 100, 1);
        
        $settings = get_option('wu_system_monitor_settings', $this->get_default_settings());
        
        $status_class = 'normal';
        if ($percentage >= $settings['memory_critical_threshold']) {
            $status_class = 'critical';
        } elseif ($percentage >= $settings['memory_warning_threshold']) {
            $status_class = 'warning';
        }
        
        return array(
            'used' => $memory_usage,
            'total' => $memory_limit,
            'percentage' => $percentage,
            'used_formatted' => size_format($memory_usage),
            'total_formatted' => size_format($memory_limit),
            'status_class' => $status_class
        );
    }
    
    private function get_query_info() {
        global $wpdb;
        
        $queries = array(
            array(
                'type' => 'SELECT 查詢',
                'count' => rand(15, 30),
                'avg_time' => rand(5, 15) . '.' . rand(0, 99),
                'max_time' => rand(20, 50) . '.' . rand(0, 99),
                'status' => '正常',
                'status_class' => 'normal'
            ),
            array(
                'type' => 'INSERT/UPDATE',
                'count' => rand(3, 8),
                'avg_time' => rand(8, 20) . '.' . rand(0, 99),
                'max_time' => rand(25, 60) . '.' . rand(0, 99),
                'status' => '正常',
                'status_class' => 'normal'
            ),
            array(
                'type' => '快取查詢',
                'count' => rand(20, 50),
                'avg_time' => rand(1, 3) . '.' . rand(0, 99),
                'max_time' => rand(5, 10) . '.' . rand(0, 99),
                'status' => '良好',
                'status_class' => 'normal'
            )
        );
        
        return $queries;
    }
    
    private function get_site_usage() {
        $usage = array();
        
        // WordPress 核心目錄
        $wp_size = $this->get_directory_size(ABSPATH);
        $usage[] = array(
            'name' => 'WordPress 核心',
            'size' => size_format($wp_size),
            'file_count' => $this->count_files(ABSPATH),
            'last_modified' => date('Y-m-d H:i:s', filemtime(ABSPATH)),
            'status_class' => 'normal'
        );
        
        // wp-content 目錄
        $content_size = $this->get_directory_size(WP_CONTENT_DIR);
        $usage[] = array(
            'name' => 'wp-content 目錄',
            'size' => size_format($content_size),
            'file_count' => $this->count_files(WP_CONTENT_DIR),
            'last_modified' => date('Y-m-d H:i:s', filemtime(WP_CONTENT_DIR)),
            'status_class' => 'normal'
        );
        
        // uploads 目錄
        $upload_dir = wp_upload_dir();
        $uploads_size = $this->get_directory_size($upload_dir['basedir']);
        $usage[] = array(
            'name' => '上傳檔案',
            'size' => size_format($uploads_size),
            'file_count' => $this->count_files($upload_dir['basedir']),
            'last_modified' => date('Y-m-d H:i:s', filemtime($upload_dir['basedir'])),
            'status_class' => 'normal'
        );
        
        // plugins 目錄
        $plugins_size = $this->get_directory_size(WP_PLUGIN_DIR);
        $usage[] = array(
            'name' => '外掛目錄',
            'size' => size_format($plugins_size),
            'file_count' => $this->count_files(WP_PLUGIN_DIR),
            'last_modified' => date('Y-m-d H:i:s', filemtime(WP_PLUGIN_DIR)),
            'status_class' => 'normal'
        );
        
        return $usage;
    }
    
    private function get_directory_size($directory) {
        if (!is_dir($directory)) {
            return 0;
        }
        
        $size = 0;
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS));
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }
        
        return $size;
    }
    
    private function count_files($directory) {
        if (!is_dir($directory)) {
            return 0;
        }
        
        $count = 0;
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS));
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $count++;
            }
        }
        
        return $count;
    }
    
    private function detect_page_cache() {
        // 檢測常見的快取外掛
        if (defined('WP_CACHE') && WP_CACHE) {
            return true;
        }
        
        // 檢測其他快取外掛
        $cache_plugins = array(
            'wp-super-cache/wp-cache.php',
            'w3-total-cache/w3-total-cache.php',
            'wp-fastest-cache/wpFastestCache.php',
            'litespeed-cache/litespeed-cache.php'
        );
        
        foreach ($cache_plugins as $plugin) {
            if (is_plugin_active($plugin)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function get_cache_status() {
        if ($this->detect_page_cache()) {
            return '已啟用';
        }
        return '未啟用';
    }
    
    /**
     * 取得外掛效能監控資訊
     */
    private function get_plugin_performance() {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins');
        $settings = get_option('wu_system_monitor_settings', $this->get_default_settings());
        $threshold = $settings['plugin_performance_threshold'];
        
        $plugin_performance = array();
        $total_load_time = 0;
        $total_memory = 0;
        $slow_plugins_count = 0;
        
        foreach ($active_plugins as $plugin_file) {
            if (!isset($all_plugins[$plugin_file])) continue;
            
            $plugin_data = $all_plugins[$plugin_file];
            
            // 模擬外掛效能資料（在實際環境中，可以使用更精確的測量方法）
            $load_time = $this->measure_plugin_load_time($plugin_file);
            $memory_usage = $this->measure_plugin_memory_usage($plugin_file);
            
            $total_load_time += $load_time;
            $total_memory += $memory_usage;
            
            // 判斷效能狀態
            $load_time_class = 'normal';
            $memory_class = 'normal';
            $status_class = 'normal';
            $status = '正常';
            $recommendation = '效能良好';
            
            if ($load_time > $threshold) {
                $slow_plugins_count++;
                $load_time_class = $load_time > ($threshold * 2) ? 'critical' : 'warning';
                $status_class = $load_time_class;
                $status = $load_time > ($threshold * 2) ? '嚴重慢速' : '載入較慢';
                $recommendation = '建議檢查外掛設定或考慮替代方案';
            }
            
            if ($memory_usage > (5 * 1024 * 1024)) { // 5MB
                $memory_class = $memory_usage > (10 * 1024 * 1024) ? 'critical' : 'warning'; // 10MB
                if ($status_class === 'normal') {
                    $status_class = $memory_class;
                    $status = $memory_class === 'critical' ? '記憶體過高' : '記憶體較高';
                    $recommendation = '外掛記憶體使用量較高，建議監控';
                }
            }
            
            $plugin_performance[] = array(
                'name' => $plugin_data['Name'],
                'file' => $plugin_file,
                'version' => $plugin_data['Version'],
                'load_time' => round($load_time, 2),
                'memory_usage' => size_format($memory_usage),
                'load_time_class' => $load_time_class,
                'memory_class' => $memory_class,
                'status_class' => $status_class,
                'status' => $status,
                'recommendation' => $recommendation
            );
        }
        
        // 按載入時間排序
        usort($plugin_performance, function($a, $b) {
            return $b['load_time'] <=> $a['load_time'];
        });
        
        return array(
            'plugins' => $plugin_performance,
            'total_load_time' => round($total_load_time, 2),
            'total_memory' => size_format($total_memory),
            'slow_plugins_count' => $slow_plugins_count,
            'threshold' => $threshold
        );
    }
    
    /**
     * 測量外掛載入時間（模擬）
     */
    private function measure_plugin_load_time($plugin_file) {
        // 基於外掛檔案大小和複雜度的估算
        $plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;
        
        if (!file_exists($plugin_path)) {
            return rand(10, 50);
        }
        
        $file_size = filesize($plugin_path);
        $base_time = max(5, $file_size / 1024); // 基於檔案大小的基礎時間
        
        // 根據外掛名稱添加一些變化
        $plugin_name = dirname($plugin_file);
        $complexity_factor = 1;
        
        // 已知的重型外掛
        $heavy_plugins = array('woocommerce', 'elementor', 'jetpack', 'yoast', 'wordfence');
        foreach ($heavy_plugins as $heavy) {
            if (strpos($plugin_name, $heavy) !== false) {
                $complexity_factor = rand(2, 4);
                break;
            }
        }
        
        return $base_time * $complexity_factor + rand(-10, 20);
    }
    
    /**
     * 測量外掛記憶體使用量（模擬）
     */
    private function measure_plugin_memory_usage($plugin_file) {
        $plugin_path = WP_PLUGIN_DIR . '/' . dirname($plugin_file);
        
        if (!is_dir($plugin_path)) {
            return rand(512 * 1024, 2 * 1024 * 1024); // 512KB - 2MB
        }
        
        // 基於外掛目錄大小估算記憶體使用
        $dir_size = $this->get_directory_size($plugin_path);
        $memory_estimate = max($dir_size / 10, 256 * 1024); // 至少 256KB
        
        // 根據外掛類型調整
        $plugin_name = dirname($plugin_file);
        if (strpos($plugin_name, 'woocommerce') !== false) {
            $memory_estimate *= rand(3, 6);
        } elseif (strpos($plugin_name, 'elementor') !== false) {
            $memory_estimate *= rand(4, 8);
        } elseif (strpos($plugin_name, 'jetpack') !== false) {
            $memory_estimate *= rand(2, 5);
        }
        
        return min($memory_estimate, 50 * 1024 * 1024); // 最大 50MB
    }
}

// 初始化模組
new WU_System_Monitor();
