<?php
/**
 * Heartbeat 控制模組
 * 修改 WordPress heartbeat API 的間隔或停用它，減少伺服器負載
 */

if (!defined('ABSPATH')) exit;

class WU_Heartbeat_Control {
    
    private $settings;
    
    public function __construct() {
        $this->settings = get_option('wu_heartbeat_control_settings', $this->get_default_settings());
        
        add_action('admin_menu', array($this, 'add_admin_menu'), 45);
        
        // 載入 Heartbeat 控制功能
        $this->init_heartbeat_control();
    }
    
    private function get_default_settings() {
        return array(
            'enabled' => false,
            'dashboard_enabled' => true,
            'dashboard_interval' => 60,
            'editor_enabled' => true,
            'editor_interval' => 200,
            'frontend_enabled' => false,
            'frontend_interval' => 60,
            'smart_mode' => false,
            'smart_threshold_cpu' => 80,
            'smart_threshold_memory' => 512,
            'smart_fallback_interval' => 300,
            'disable_on_mobile' => true
        );
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'wumetax-toolkit',
            'Heartbeat 控制',
            'Heartbeat 控制',
            'manage_options',
            'wu-heartbeat-control',
            array($this, 'admin_page')
        );
    }
    
    public function admin_page() {
        if (isset($_POST['submit'])) {
            $this->save_settings();
        }
        
        $this->settings = get_option('wu_heartbeat_control_settings', $this->get_default_settings());
        ?>
        <div class="wrap">
            <h1>Heartbeat 控制設定</h1>
            
            <div class="notice notice-info">
                <h3>功能說明</h3>
                <p><strong>Heartbeat 控制功能</strong>讓您修改 WordPress heartbeat API 的間隔或在特定位置停用它，有助於減少伺服器的 CPU 負載。</p>
                
                <h4>WordPress Heartbeat API 介紹：</h4>
                <ul>
                    <li><strong>自動儲存</strong>：在文章編輯器中定期自動儲存草稿</li>
                    <li><strong>登入檢查</strong>：檢查用戶是否仍然處於登入狀態</li>
                    <li><strong>外掛通訊</strong>：某些外掛可能使用 Heartbeat 進行即時更新</li>
                    <li><strong>文章鎖定</strong>：多人協作時防止衝突的文章鎖定機制</li>
                </ul>
                
                <h4>位置說明：</h4>
                <ul>
                    <li><strong>後台頁面</strong>：WordPress 儀表板和一般管理頁面</li>
                    <li><strong>文章編輯器</strong>：建立或編輯文章/頁面的畫面</li>
                    <li><strong>前台頁面</strong>：網站的公開頁面（通常不需要）</li>
                </ul>
            </div>
            
            <form method="post" action="">
                <?php wp_nonce_field('wu_heartbeat_control_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">啟用 Heartbeat 控制</th>
                        <td>
                            <label>
                                <input type="checkbox" name="enabled" value="1" <?php checked($this->settings['enabled']); ?>>
                                啟用 Heartbeat 控制功能
                            </label>
                        </td>
                    </tr>
                </table>
                
                <h2>位置控制設定</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">後台頁面設定</th>
                        <td>
                            <label style="display: block; margin: 5px 0;">
                                <input type="checkbox" name="dashboard_enabled" value="1" <?php checked($this->settings['dashboard_enabled']); ?>>
                                在後台頁面啟用 Heartbeat
                            </label>
                            <label style="display: block; margin: 5px 0;">
                                間隔時間：
                                <select name="dashboard_interval">
                                    <?php
                                    $intervals = array(
                                        15 => '15 秒',
                                        30 => '30 秒',
                                        60 => '60 秒',
                                        120 => '120 秒',
                                        300 => '300 秒'
                                    );
                                    foreach ($intervals as $value => $label) {
                                        echo '<option value="' . $value . '" ' . selected($this->settings['dashboard_interval'], $value, false) . '>' . $label . '</option>';
                                    }
                                    ?>
                                </select>
                            </label>
                            <p class="description">後台儀表板和管理頁面的 Heartbeat 間隔</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">文章編輯器設定</th>
                        <td>
                            <label style="display: block; margin: 5px 0;">
                                <input type="checkbox" name="editor_enabled" value="1" <?php checked($this->settings['editor_enabled']); ?>>
                                在文章編輯器啟用 Heartbeat
                            </label>
                            <label style="display: block; margin: 5px 0;">
                                間隔時間：
                                <select name="editor_interval">
                                    <?php
                                    $editor_intervals = array(
                                        15 => '15 秒',
                                        30 => '30 秒',
                                        60 => '60 秒',
                                        120 => '120 秒',
                                        200 => '200 秒',
                                        300 => '300 秒'
                                    );
                                    foreach ($editor_intervals as $value => $label) {
                                        echo '<option value="' . $value . '" ' . selected($this->settings['editor_interval'], $value, false) . '>' . $label . '</option>';
                                    }
                                    ?>
                                </select>
                            </label>
                            <p class="description">文章編輯畫面的 Heartbeat 間隔（建議 200 秒以減少伺服器負載）</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">前台頁面設定</th>
                        <td>
                            <label style="display: block; margin: 5px 0;">
                                <input type="checkbox" name="frontend_enabled" value="1" <?php checked($this->settings['frontend_enabled']); ?>>
                                在前台頁面啟用 Heartbeat
                            </label>
                            <label style="display: block; margin: 5px 0;">
                                間隔時間：
                                <select name="frontend_interval">
                                    <?php
                                    foreach ($intervals as $value => $label) {
                                        echo '<option value="' . $value . '" ' . selected($this->settings['frontend_interval'], $value, false) . '>' . $label . '</option>';
                                    }
                                    ?>
                                </select>
                            </label>
                            <p class="description">前台頁面的 Heartbeat 間隔（一般建議停用）</p>
                        </td>
                    </tr>
                </table>
                
                <h2>智能模式設定</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">智能模式</th>
                        <td>
                            <label style="display: block; margin: 5px 0;">
                                <input type="checkbox" name="smart_mode" value="1" <?php checked($this->settings['smart_mode']); ?>>
                                啟用智能模式
                            </label>
                            <p class="description">根據伺服器負載自動調整 Heartbeat 間隔</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">智能模式閾值</th>
                        <td>
                            <label style="display: block; margin: 5px 0;">
                                CPU 使用率閾值：
                                <select name="smart_threshold_cpu">
                                    <?php
                                    $cpu_thresholds = array(
                                        50 => '50%',
                                        60 => '60%',
                                        70 => '70%',
                                        80 => '80%',
                                        90 => '90%'
                                    );
                                    foreach ($cpu_thresholds as $value => $label) {
                                        echo '<option value="' . $value . '" ' . selected($this->settings['smart_threshold_cpu'], $value, false) . '>' . $label . '</option>';
                                    }
                                    ?>
                                </select>
                            </label>
                            <label style="display: block; margin: 5px 0;">
                                記憶體使用閾值：
                                <select name="smart_threshold_memory">
                                    <?php
                                    $memory_thresholds = array(
                                        256 => '256 MB',
                                        512 => '512 MB',
                                        1024 => '1 GB',
                                        2048 => '2 GB'
                                    );
                                    foreach ($memory_thresholds as $value => $label) {
                                        echo '<option value="' . $value . '" ' . selected($this->settings['smart_threshold_memory'], $value, false) . '>' . $label . '</option>';
                                    }
                                    ?>
                                </select>
                            </label>
                            <label style="display: block; margin: 5px 0;">
                                回退間隔：
                                <select name="smart_fallback_interval">
                                    <?php
                                    $fallback_intervals = array(
                                        300 => '300 秒',
                                        600 => '600 秒',
                                        900 => '900 秒'
                                    );
                                    foreach ($fallback_intervals as $value => $label) {
                                        echo '<option value="' . $value . '" ' . selected($this->settings['smart_fallback_interval'], $value, false) . '>' . $label . '</option>';
                                    }
                                    ?>
                                </select>
                            </label>
                            <p class="description">當伺服器負載超過閾值時，將使用回退間隔</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">其他設定</th>
                        <td>
                            <label>
                                <input type="checkbox" name="disable_on_mobile" value="1" <?php checked($this->settings['disable_on_mobile']); ?>>
                                在行動裝置上停用 Heartbeat
                            </label>
                            <p class="description">在行動裝置上停用以節省流量和電池</p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <?php submit_button('儲存設定', 'primary', 'submit', false); ?>
                </p>
            </form>
            
            <h2>狀態資訊</h2>
            <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
                <?php $this->display_heartbeat_status(); ?>
            </div>
            
            <h2>使用說明</h2>
            <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
                <h3>建議設定：</h3>
                <ul>
                    <li><strong>文章編輯器</strong>：建議設定為 200 秒，平衡自動儲存和效能</li>
                    <li><strong>後台頁面</strong>：可設定為 60-120 秒</li>
                    <li><strong>前台頁面</strong>：建議停用，除非特殊需求</li>
                    <li><strong>智能模式</strong>：適合不穩定的主機環境</li>
                </ul>
                
                <h3>注意事項：</h3>
                <ul>
                    <li>間隔過長可能影響自動儲存功能</li>
                    <li>完全停用可能導致多人編輯衝突</li>
                    <li>智能模式需要系統支援負載監測</li>
                    <li>變更設定後建議測試文章編輯功能</li>
                </ul>
            </div>
        </div>
        
        <style>
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
        </style>
        <?php
    }
    
    private function save_settings() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'wu_heartbeat_control_settings')) {
            wp_die('安全驗證失敗');
        }
        
        $settings = array(
            'enabled' => isset($_POST['enabled']),
            'dashboard_enabled' => isset($_POST['dashboard_enabled']),
            'dashboard_interval' => intval($_POST['dashboard_interval']),
            'editor_enabled' => isset($_POST['editor_enabled']),
            'editor_interval' => intval($_POST['editor_interval']),
            'frontend_enabled' => isset($_POST['frontend_enabled']),
            'frontend_interval' => intval($_POST['frontend_interval']),
            'smart_mode' => isset($_POST['smart_mode']),
            'smart_threshold_cpu' => intval($_POST['smart_threshold_cpu']),
            'smart_threshold_memory' => intval($_POST['smart_threshold_memory']),
            'smart_fallback_interval' => intval($_POST['smart_fallback_interval']),
            'disable_on_mobile' => isset($_POST['disable_on_mobile'])
        );
        
        update_option('wu_heartbeat_control_settings', $settings);
        $this->settings = $settings;
        
        echo '<div class="notice notice-success"><p>設定已儲存！</p></div>';
    }
    
    private function init_heartbeat_control() {
        if (!$this->settings['enabled']) {
            return;
        }
        
        // 根據設定控制 Heartbeat
        add_filter('heartbeat_settings', array($this, 'modify_heartbeat_settings'));
        
        // 在特定位置停用 Heartbeat
        if (!$this->settings['dashboard_enabled']) {
            add_action('admin_init', array($this, 'disable_dashboard_heartbeat'));
        }
        
        if (!$this->settings['editor_enabled']) {
            add_action('admin_init', array($this, 'disable_editor_heartbeat'));
        }
        
        if (!$this->settings['frontend_enabled']) {
            add_action('init', array($this, 'disable_frontend_heartbeat'));
        }
        
        // 行動裝置停用
        if ($this->settings['disable_on_mobile'] && $this->is_mobile()) {
            add_action('init', array($this, 'disable_all_heartbeat'));
        }
    }
    
    public function modify_heartbeat_settings($settings) {
        $current_screen = get_current_screen();
        
        // 智能模式
        if ($this->settings['smart_mode']) {
            $load_stats = $this->get_server_load();
            if ($load_stats['cpu'] > $this->settings['smart_threshold_cpu'] || 
                $load_stats['memory'] > $this->settings['smart_threshold_memory']) {
                $settings['interval'] = $this->settings['smart_fallback_interval'];
                return $settings;
            }
        }
        
        // 根據當前頁面設定間隔
        if (is_admin()) {
            if ($current_screen && in_array($current_screen->base, array('post', 'page'))) {
                // 文章編輯器
                if ($this->settings['editor_enabled']) {
                    $settings['interval'] = $this->settings['editor_interval'];
                }
            } else {
                // 後台頁面
                if ($this->settings['dashboard_enabled']) {
                    $settings['interval'] = $this->settings['dashboard_interval'];
                }
            }
        } else {
            // 前台頁面
            if ($this->settings['frontend_enabled']) {
                $settings['interval'] = $this->settings['frontend_interval'];
            }
        }
        
        return $settings;
    }
    
    public function disable_dashboard_heartbeat() {
        $current_screen = get_current_screen();
        if ($current_screen && !in_array($current_screen->base, array('post', 'page'))) {
            wp_deregister_script('heartbeat');
        }
    }
    
    public function disable_editor_heartbeat() {
        $current_screen = get_current_screen();
        if ($current_screen && in_array($current_screen->base, array('post', 'page'))) {
            wp_deregister_script('heartbeat');
        }
    }
    
    public function disable_frontend_heartbeat() {
        if (!is_admin()) {
            wp_deregister_script('heartbeat');
        }
    }
    
    public function disable_all_heartbeat() {
        wp_deregister_script('heartbeat');
    }
    
    private function is_mobile() {
        if (function_exists('wp_is_mobile')) {
            return wp_is_mobile();
        }
        
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        return preg_match('/Mobile|Android|iPhone|iPad|iPod|BlackBerry|Windows Phone/i', $user_agent);
    }
    
    private function get_server_load() {
        $load = array('cpu' => 0, 'memory' => 0);
        
        // 取得 CPU 負載
        if (function_exists('sys_getloadavg')) {
            $load_avg = sys_getloadavg();
            $load['cpu'] = $load_avg[0] * 100; // 轉換為百分比
        }
        
        // 取得記憶體使用量
        if (function_exists('memory_get_usage')) {
            $memory_usage = memory_get_usage(true);
            $load['memory'] = round($memory_usage / 1024 / 1024); // MB
        }
        
        return $load;
    }
    
    private function display_heartbeat_status() {
        echo '<h4>目前狀態</h4>';
        
        if (!$this->settings['enabled']) {
            echo '<p><span style="color: #666;">❌ Heartbeat 控制已停用</span></p>';
            return;
        }
        
        echo '<p><span style="color: #46b450;">✅ Heartbeat 控制已啟用</span></p>';
        
        echo '<table class="widefat">';
        echo '<thead><tr><th>位置</th><th>狀態</th><th>間隔</th></tr></thead>';
        echo '<tbody>';
        
        echo '<tr>';
        echo '<td>後台頁面</td>';
        echo '<td>' . ($this->settings['dashboard_enabled'] ? '<span style="color: #46b450;">啟用</span>' : '<span style="color: #dc3232;">停用</span>') . '</td>';
        echo '<td>' . ($this->settings['dashboard_enabled'] ? $this->settings['dashboard_interval'] . ' 秒' : '-') . '</td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<td>文章編輯器</td>';
        echo '<td>' . ($this->settings['editor_enabled'] ? '<span style="color: #46b450;">啟用</span>' : '<span style="color: #dc3232;">停用</span>') . '</td>';
        echo '<td>' . ($this->settings['editor_enabled'] ? $this->settings['editor_interval'] . ' 秒' : '-') . '</td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<td>前台頁面</td>';
        echo '<td>' . ($this->settings['frontend_enabled'] ? '<span style="color: #46b450;">啟用</span>' : '<span style="color: #dc3232;">停用</span>') . '</td>';
        echo '<td>' . ($this->settings['frontend_enabled'] ? $this->settings['frontend_interval'] . ' 秒' : '-') . '</td>';
        echo '</tr>';
        
        echo '</tbody></table>';
        
        if ($this->settings['smart_mode']) {
            echo '<p><span style="color: #0073aa;">🧠 智能模式已啟用</span></p>';
            $load_stats = $this->get_server_load();
            echo '<p>目前伺服器負載：CPU ' . round($load_stats['cpu'], 1) . '%，記憶體 ' . $load_stats['memory'] . ' MB</p>';
        }
        
        if ($this->settings['disable_on_mobile']) {
            echo '<p><span style="color: #ff8c00;">📱 行動裝置停用已啟用</span></p>';
        }
    }
}

// 初始化模組
new WU_Heartbeat_Control();