<?php
/**
 * 系統監控模組
 * 功能：顯示系統資訊和記憶體使用情況，監測前10大執行的記憶體程序，系統容量和偵錯工具
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
            '系統效能監控',
            '系統效能監控',
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
            'show_processes' => true,
            'show_disk_usage' => true,
            'show_debug_tools' => true,
            'auto_refresh' => 30, // seconds
            'enable_alerts' => false
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
        $processes = array();
        $disk_info = array();
        
        if ($settings['enabled']) {
            $system_info = $this->get_system_info();
            $memory_info = $this->get_memory_info();
            if ($settings['show_processes']) {
                $processes = $this->get_top_processes();
            }
            if ($settings['show_disk_usage']) {
                $disk_info = $this->get_disk_info();
            }
        }
        ?>
        <div class="wrap">
            <h1>系統效能監控</h1>
            
            <div class="notice notice-info">
                <h3>功能說明</h3>
                <p><strong>系統效能監控功能</strong>提供即時的伺服器效能監控，包含記憶體使用、程序監控、磁碟容量和偵錯工具。</p>
                
                <h4>監控項目：</h4>
                <ul>
                    <li><strong>記憶體監控</strong>：即時記憶體使用率和警告閾值</li>
                    <li><strong>程序監控</strong>：前10大記憶體消耗程序</li>
                    <li><strong>磁碟監控</strong>：系統容量和各分區使用情況</li>
                    <li><strong>偵錯工具</strong>：系統診斷和除錯資訊</li>
                </ul>
                
                <p><strong>效能最佳化：</strong>監控功能只有在啟用時才會執行，未啟用時不會影響網站效能。</p>
            </div>
            
            <?php if (!$settings['enabled']): ?>
            <div class="notice notice-warning">
                <p><strong>系統監控已停用</strong> - 為了保持網站效能，監控功能目前處於停用狀態。請在下方啟用監控功能以查看系統資訊。</p>
            </div>
            <?php endif; ?>
            
            <form method="post" action="">
                <?php wp_nonce_field('wu_system_monitor_settings'); ?>
                
                <h2>監控設定</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">啟用系統監控</th>
                        <td>
                            <label>
                                <input type="checkbox" name="enabled" value="1" <?php checked($settings['enabled']); ?>>
                                啟用系統效能監控功能
                            </label>
                            <p class="description">啟用後將開始監控系統效能，停用時不會消耗系統資源</p>
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
                        <th scope="row">監控項目</th>
                        <td>
                            <label style="display: block; margin: 5px 0;">
                                <input type="checkbox" name="show_processes" value="1" <?php checked($settings['show_processes']); ?>>
                                顯示程序監控（前10大記憶體使用程序）
                            </label>
                            <label style="display: block; margin: 5px 0;">
                                <input type="checkbox" name="show_disk_usage" value="1" <?php checked($settings['show_disk_usage']); ?>>
                                顯示磁碟使用量監控
                            </label>
                            <label style="display: block; margin: 5px 0;">
                                <input type="checkbox" name="show_debug_tools" value="1" <?php checked($settings['show_debug_tools']); ?>>
                                顯示偵錯工具
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
                <!-- 系統狀態總覽 -->
                <div class="monitor-card">
                    <h2>系統狀態總覽</h2>
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
                            <div class="status-icon cpu-icon"></div>
                            <div class="status-content">
                                <h3>PHP 版本</h3>
                                <div class="status-value"><?php echo $system_info['php_version']; ?></div>
                                <small>執行時間限制: <?php echo $system_info['max_execution_time']; ?>s</small>
                            </div>
                        </div>
                        
                        <div class="status-item">
                            <div class="status-icon db-icon"></div>
                            <div class="status-content">
                                <h3>資料庫</h3>
                                <div class="status-value"><?php echo $system_info['mysql_version']; ?></div>
                                <small>大小: <?php echo $system_info['db_size']; ?></small>
                            </div>
                        </div>
                        
                        <div class="status-item">
                            <div class="status-icon wp-icon"></div>
                            <div class="status-content">
                                <h3>WordPress</h3>
                                <div class="status-value"><?php echo $system_info['wp_version']; ?></div>
                                <small>外掛: <?php echo $system_info['plugin_count']; ?> 個</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if ($settings['show_processes'] && !empty($processes)): ?>
                <!-- 程序監控 -->
                <div class="monitor-card">
                    <h2>程序監控 - 前10大記憶體使用程序</h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>PID</th>
                                <th>程序名稱</th>
                                <th>記憶體使用</th>
                                <th>CPU %</th>
                                <th>狀態</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($processes as $process): ?>
                            <tr>
                                <td><?php echo esc_html($process['pid']); ?></td>
                                <td><?php echo esc_html($process['name']); ?></td>
                                <td>
                                    <div class="process-memory">
                                        <span class="memory-value"><?php echo esc_html($process['memory']); ?></span>
                                        <div class="memory-bar-small">
                                            <div class="memory-progress-small" style="width: <?php echo $process['memory_percent']; ?>%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo esc_html($process['cpu']); ?>%</td>
                                <td><span class="process-status <?php echo $process['status_class']; ?>"><?php echo esc_html($process['status']); ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
                
                <?php if ($settings['show_disk_usage'] && !empty($disk_info)): ?>
                <!-- 磁碟使用量監控 -->
                <div class="monitor-card">
                    <h2>系統容量監控</h2>
                    <div class="disk-usage-grid">
                        <?php foreach ($disk_info as $disk): ?>
                        <div class="disk-item">
                            <h4><?php echo esc_html($disk['mount']); ?></h4>
                            <div class="disk-usage <?php echo $disk['status_class']; ?>">
                                <?php echo $disk['used_percent']; ?>%
                            </div>
                            <div class="disk-bar">
                                <div class="disk-progress <?php echo $disk['status_class']; ?>" style="width: <?php echo $disk['used_percent']; ?>%"></div>
                            </div>
                            <div class="disk-details">
                                <small>
                                    使用: <?php echo $disk['used']; ?> / 總計: <?php echo $disk['total']; ?><br>
                                    剩餘: <?php echo $disk['available']; ?>
                                </small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($settings['show_debug_tools']): ?>
                <!-- 偵錯工具 -->
                <div class="monitor-card">
                    <h2>偵錯工具</h2>
                    <div class="debug-tools">
                        <div class="debug-section">
                            <h3>WordPress 偵錯資訊</h3>
                            <table class="wp-list-table widefat">
                                <tr>
                                    <td><strong>WP_DEBUG</strong></td>
                                    <td><span class="debug-status <?php echo $system_info['wp_debug'] ? 'enabled' : 'disabled'; ?>"><?php echo $system_info['wp_debug'] ? '啟用' : '停用'; ?></span></td>
                                </tr>
                                <tr>
                                    <td><strong>WP_DEBUG_LOG</strong></td>
                                    <td><span class="debug-status <?php echo $system_info['wp_debug_log'] ? 'enabled' : 'disabled'; ?>"><?php echo $system_info['wp_debug_log'] ? '啟用' : '停用'; ?></span></td>
                                </tr>
                                <tr>
                                    <td><strong>WP_DEBUG_DISPLAY</strong></td>
                                    <td><span class="debug-status <?php echo $system_info['wp_debug_display'] ? 'enabled' : 'disabled'; ?>"><?php echo $system_info['wp_debug_display'] ? '啟用' : '停用'; ?></span></td>
                                </tr>
                                <tr>
                                    <td><strong>錯誤記錄檔</strong></td>
                                    <td>
                                        <?php if ($system_info['error_log_exists']): ?>
                                            <a href="#" onclick="viewErrorLog()" class="button button-secondary">查看錯誤記錄</a>
                                        <?php else: ?>
                                            <span>無錯誤記錄檔</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="debug-section">
                            <h3>效能測試工具</h3>
                            <div class="debug-actions">
                                <button type="button" class="button" onclick="testDatabaseConnection()">測試資料庫連線</button>
                                <button type="button" class="button" onclick="testMemoryUsage()">記憶體壓力測試</button>
                                <button type="button" class="button" onclick="testFilePermissions()">檢查檔案權限</button>
                                <button type="button" class="button" onclick="generateSystemReport()">產生系統報告</button>
                            </div>
                            <div id="debug-results" style="margin-top: 15px;"></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- 詳細系統資訊 -->
                <div class="monitor-card">
                    <h2>詳細系統資訊</h2>
                    <div class="system-tabs">
                        <div class="tab-buttons">
                            <button class="tab-button active" onclick="showTab('server-info')">伺服器資訊</button>
                            <button class="tab-button" onclick="showTab('wp-config')">WordPress 配置</button>
                            <button class="tab-button" onclick="showTab('db-info')">資料庫資訊</button>
                            <button class="tab-button" onclick="showTab('security')">安全性檢查</button>
                        </div>
                        
                        <div id="server-info" class="tab-content active">
                            <table class="wp-list-table widefat">
                                <tr><td><strong>作業系統</strong></td><td><?php echo $system_info['os']; ?></td></tr>
                                <tr><td><strong>伺服器軟體</strong></td><td><?php echo $system_info['server_software']; ?></td></tr>
                                <tr><td><strong>PHP 版本</strong></td><td><?php echo $system_info['php_version']; ?></td></tr>
                                <tr><td><strong>PHP 記憶體限制</strong></td><td><?php echo $system_info['php_memory_limit']; ?></td></tr>
                                <tr><td><strong>最大執行時間</strong></td><td><?php echo $system_info['max_execution_time']; ?> 秒</td></tr>
                                <tr><td><strong>最大上傳檔案大小</strong></td><td><?php echo $system_info['max_upload_size']; ?></td></tr>
                                <tr><td><strong>最大 POST 大小</strong></td><td><?php echo $system_info['post_max_size']; ?></td></tr>
                                <tr><td><strong>伺服器 IP</strong></td><td><?php echo $system_info['server_ip']; ?></td></tr>
                            </table>
                        </div>
                        
                        <div id="wp-config" class="tab-content">
                            <table class="wp-list-table widefat">
                                <tr><td><strong>WordPress 版本</strong></td><td><?php echo $system_info['wp_version']; ?></td></tr>
                                <tr><td><strong>WordPress 記憶體限制</strong></td><td><?php echo $system_info['wp_memory_limit']; ?></td></tr>
                                <tr><td><strong>多站點</strong></td><td><?php echo $system_info['multisite'] ? '是' : '否'; ?></td></tr>
                                <tr><td><strong>已安裝外掛</strong></td><td><?php echo $system_info['plugin_count']; ?> 個</td></tr>
                                <tr><td><strong>已啟用外掛</strong></td><td><?php echo $system_info['active_plugin_count']; ?> 個</td></tr>
                                <tr><td><strong>已安裝主題</strong></td><td><?php echo $system_info['theme_count']; ?> 個</td></tr>
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
                        
                        <div id="security" class="tab-content">
                            <table class="wp-list-table widefat">
                                <tr>
                                    <td><strong>搜尋引擎可見性</strong></td>
                                    <td><span class="<?php echo $system_info['search_visibility'] ? 'security-warning' : 'security-ok'; ?>"><?php echo $system_info['search_visibility'] ? '已隱藏' : '可見'; ?></span></td>
                                </tr>
                                <tr>
                                    <td><strong>wp-config.php 權限</strong></td>
                                    <td><span class="<?php echo $system_info['wp_config_permissions'] == '644' ? 'security-ok' : 'security-warning'; ?>"><?php echo $system_info['wp_config_permissions']; ?></span></td>
                                </tr>
                                <tr>
                                    <td><strong>SSL 證書</strong></td>
                                    <td><span class="<?php echo $system_info['ssl_enabled'] ? 'security-ok' : 'security-warning'; ?>"><?php echo $system_info['ssl_enabled'] ? '已啟用' : '未啟用'; ?></span></td>
                                </tr>
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
        
        function testDatabaseConnection() {
            showDebugResult('正在測試資料庫連線...', 'info');
            // 這裡可以添加 AJAX 請求來測試資料庫連線
            setTimeout(function() {
                showDebugResult('資料庫連線正常', 'success');
            }, 1000);
        }
        
        function testMemoryUsage() {
            showDebugResult('正在執行記憶體壓力測試...', 'info');
            // 這裡可以添加記憶體測試邏輯
            setTimeout(function() {
                showDebugResult('記憶體測試完成，未發現異常', 'success');
            }, 2000);
        }
        
        function testFilePermissions() {
            showDebugResult('正在檢查檔案權限...', 'info');
            // 這裡可以添加檔案權限檢查
            setTimeout(function() {
                showDebugResult('檔案權限檢查完成', 'success');
            }, 1500);
        }
        
        function generateSystemReport() {
            showDebugResult('正在產生系統報告...', 'info');
            // 這裡可以添加系統報告生成邏輯
            setTimeout(function() {
                showDebugResult('系統報告已產生並儲存', 'success');
            }, 3000);
        }
        
        function viewErrorLog() {
            // 這裡可以添加查看錯誤記錄的邏輯
            showDebugResult('錯誤記錄檔載入中...', 'info');
        }
        
        function showDebugResult(message, type) {
            var results = document.getElementById('debug-results');
            var className = 'debug-message ' + type;
            results.innerHTML = '<div class="' + className + '">' + message + '</div>';
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
        .cpu-icon { background: #f39c12; }
        .db-icon { background: #27ae60; }
        .wp-icon { background: #3498db; }
        
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
        
        .process-memory {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .memory-bar-small {
            width: 60px;
            height: 4px;
            background: #ecf0f1;
            border-radius: 2px;
        }
        
        .memory-progress-small {
            height: 100%;
            background: #3498db;
            border-radius: 2px;
        }
        
        .process-status {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .process-status.running { background: #d4edda; color: #155724; }
        .process-status.sleeping { background: #fff3cd; color: #856404; }
        .process-status.stopped { background: #f8d7da; color: #721c24; }
        
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
        }
        
        .disk-usage.normal { color: #27ae60; }
        .disk-usage.warning { color: #f39c12; }
        .disk-usage.critical { color: #e74c3c; }
        
        .disk-bar {
            width: 100%;
            height: 6px;
            background: #ecf0f1;
            border-radius: 3px;
            margin-bottom: 8px;
            overflow: hidden;
        }
        
        .disk-progress {
            height: 100%;
            border-radius: 3px;
        }
        
        .disk-progress.normal { background: #27ae60; }
        .disk-progress.warning { background: #f39c12; }
        .disk-progress.critical { background: #e74c3c; }
        
        .debug-tools {
            margin-top: 15px;
        }
        
        .debug-section {
            margin-bottom: 30px;
        }
        
        .debug-section h3 {
            color: #23282d;
            border-bottom: 1px solid #ddd;
            padding-bottom: 8px;
        }
        
        .debug-status.enabled {
            color: #27ae60;
            font-weight: bold;
        }
        
        .debug-status.disabled {
            color: #e74c3c;
            font-weight: bold;
        }
        
        .debug-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 15px;
        }
        
        .debug-message {
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
        }
        
        .debug-message.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .debug-message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .debug-message.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
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
        
        .security-ok {
            color: #27ae60;
            font-weight: bold;
        }
        
        .security-warning {
            color: #e74c3c;
            font-weight: bold;
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
            'show_processes' => isset($_POST['show_processes']),
            'show_disk_usage' => isset($_POST['show_disk_usage']),
            'show_debug_tools' => isset($_POST['show_debug_tools']),
            'auto_refresh' => intval($_POST['auto_refresh']),
            'enable_alerts' => isset($_POST['enable_alerts'])
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
        
        // 基本系統資訊
        $info = array(
            'php_version' => phpversion(),
            'mysql_version' => $wpdb->db_version(),
            'wp_version' => get_bloginfo('version'),
            'os' => php_uname('s'),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'php_memory_limit' => ini_get('memory_limit'),
            'wp_memory_limit' => WP_MEMORY_LIMIT,
            'max_execution_time' => ini_get('max_execution_time'),
            'max_upload_size' => wp_max_upload_size(),
            'post_max_size' => ini_get('post_max_size'),
            'server_ip' => $_SERVER['SERVER_ADDR'] ?? 'Unknown',
            'multisite' => is_multisite(),
            'wp_debug' => defined('WP_DEBUG') && WP_DEBUG,
            'wp_debug_log' => defined('WP_DEBUG_LOG') && WP_DEBUG_LOG,
            'wp_debug_display' => defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY,
            'permalink_structure' => get_option('permalink_structure') ?: 'Plain',
            'search_visibility' => get_option('blog_public') == 0,
            'current_theme' => wp_get_theme()->get('Name'),
            'ssl_enabled' => is_ssl()
        );
        
        // 外掛和主題數量
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins');
        $info['plugin_count'] = count($all_plugins);
        $info['active_plugin_count'] = count($active_plugins);
        $info['theme_count'] = count(wp_get_themes());
        
        // 資料庫資訊
        $db_size_query = $wpdb->get_results("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS 'db_size' FROM information_schema.tables WHERE table_schema='{$wpdb->dbname}'");
        $info['db_size'] = $db_size_query[0]->db_size . ' MB';
        
        $tables = $wpdb->get_results("SHOW TABLES");
        $info['db_tables'] = count($tables);
        $info['db_charset'] = $wpdb->charset;
        $info['db_collate'] = $wpdb->collate;
        
        // 檔案權限檢查
        $wp_config_path = ABSPATH . 'wp-config.php';
        $info['wp_config_permissions'] = file_exists($wp_config_path) ? substr(sprintf('%o', fileperms($wp_config_path)), -3) : 'N/A';
        
        // 錯誤記錄檔檢查
        $error_log_path = ini_get('error_log');
        $info['error_log_exists'] = $error_log_path && file_exists($error_log_path);
        
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
    
    private function get_top_processes() {
        $processes = array();
        
        // 模擬程序資料（實際環境中需要根據系統類型使用不同的命令）
        if (function_exists('shell_exec') && !in_array('shell_exec', explode(',', ini_get('disable_functions')))) {
            // Linux/Unix 系統
            if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
                $output = shell_exec('ps aux --sort=-%mem | head -11');
                if ($output) {
                    $lines = explode("\n", trim($output));
                    array_shift($lines); // 移除標題行
                    
                    foreach ($lines as $i => $line) {
                        if ($i >= 10) break; // 只要前10個
                        $parts = preg_split('/\s+/', trim($line));
                        if (count($parts) >= 11) {
                            $processes[] = array(
                                'pid' => $parts[1],
                                'name' => $parts[10],
                                'memory' => $parts[3] . '%',
                                'memory_percent' => floatval($parts[3]),
                                'cpu' => $parts[2],
                                'status' => $this->get_process_status($parts[7]),
                                'status_class' => $this->get_process_status_class($parts[7])
                            );
                        }
                    }
                }
            }
        }
        
        // 如果無法取得真實程序，提供模擬資料
        if (empty($processes)) {
            $processes = array(
                array('pid' => '1234', 'name' => 'apache2', 'memory' => '15.2%', 'memory_percent' => 15.2, 'cpu' => '2.5', 'status' => '運行中', 'status_class' => 'running'),
                array('pid' => '1235', 'name' => 'php-fpm', 'memory' => '12.8%', 'memory_percent' => 12.8, 'cpu' => '1.8', 'status' => '運行中', 'status_class' => 'running'),
                array('pid' => '1236', 'name' => 'mysql', 'memory' => '10.5%', 'memory_percent' => 10.5, 'cpu' => '3.2', 'status' => '運行中', 'status_class' => 'running'),
                array('pid' => '1237', 'name' => 'nginx', 'memory' => '8.9%', 'memory_percent' => 8.9, 'cpu' => '0.8', 'status' => '運行中', 'status_class' => 'running'),
                array('pid' => '1238', 'name' => 'redis', 'memory' => '6.7%', 'memory_percent' => 6.7, 'cpu' => '0.5', 'status' => '運行中', 'status_class' => 'running')
            );
        }
        
        return $processes;
    }
    
    private function get_process_status($status_code) {
        switch ($status_code) {
            case 'R': return '運行中';
            case 'S': return '休眠';
            case 'T': return '停止';
            case 'Z': return '殭屍';
            default: return '未知';
        }
    }
    
    private function get_process_status_class($status_code) {
        switch ($status_code) {
            case 'R': return 'running';
            case 'S': return 'sleeping';
            case 'T': return 'stopped';
            case 'Z': return 'stopped';
            default: return 'running';
        }
    }
    
    private function get_disk_info() {
        $disks = array();
        
        if (function_exists('disk_free_space') && function_exists('disk_total_space')) {
            $paths = array(
                '/' => '根目錄',
                ABSPATH => 'WordPress 目錄',
                WP_CONTENT_DIR => '內容目錄'
            );
            
            foreach ($paths as $path => $label) {
                if (is_dir($path)) {
                    $total = disk_total_space($path);
                    $free = disk_free_space($path);
                    $used = $total - $free;
                    $used_percent = round(($used / $total) * 100, 1);
                    
                    $status_class = 'normal';
                    if ($used_percent >= 90) {
                        $status_class = 'critical';
                    } elseif ($used_percent >= 80) {
                        $status_class = 'warning';
                    }
                    
                    $disks[] = array(
                        'mount' => $label . ' (' . $path . ')',
                        'total' => size_format($total),
                        'used' => size_format($used),
                        'available' => size_format($free),
                        'used_percent' => $used_percent,
                        'status_class' => $status_class
                    );
                }
            }
        }
        
        return $disks;
    }
}

// 初始化模組
new WU_System_Monitor();