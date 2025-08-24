<?php
/**
 * 一鍵匯出用戶模組
 * 讓管理員可以快速匯出用戶資料為 CSV 格式
 */

if (!defined('ABSPATH')) exit;

class WU_User_Exporter {
    
    private $settings;
    
    public function __construct() {
        $this->settings = get_option('wu_user_exporter_settings', $this->get_default_settings());
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_filter('user_row_actions', array($this, 'add_export_link'), 10, 2);
        add_filter('bulk_actions-users', array($this, 'add_bulk_export_action'));
        add_filter('handle_bulk_actions-users', array($this, 'handle_bulk_export'), 10, 3);
        add_action('admin_action_wu_export_user', array($this, 'export_single_user'));
        add_action('wp_ajax_wu_export_users', array($this, 'ajax_export_users'));
        add_action('admin_notices', array($this, 'admin_notices'));
    }
    
    private function get_default_settings() {
        return array(
            'enabled' => false,
            'include_meta' => true,
            'include_roles' => true,
            'date_format' => 'Y-m-d H:i:s',
            'fields' => array(
                'ID' => true,
                'user_login' => true,
                'user_email' => true,
                'user_nicename' => true,
                'display_name' => true,
                'user_registered' => true,
                'user_status' => false,
                'user_url' => false
            ),
            'meta_fields' => array(
                'first_name' => true,
                'last_name' => true,
                'nickname' => true,
                'description' => false,
                'rich_editing' => false,
                'syntax_highlighting' => false,
                'comment_shortcuts' => false,
                'admin_color' => false,
                'use_ssl' => false,
                'show_admin_bar_front' => false,
                'locale' => false
            ),
            'excluded_roles' => array(),
            'custom_meta_fields' => array()
        );
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'wumetax-toolkit',
            '用戶匯出',
            '用戶匯出',
            'manage_options',
            'wu-user-exporter',
            array($this, 'admin_page')
        );
    }
    
    public function admin_page() {
        if (isset($_POST['submit'])) {
            $this->save_settings();
        }
        
        if (isset($_POST['export_all_users'])) {
            $this->export_all_users();
            return;
        }
        
        $this->settings = get_option('wu_user_exporter_settings', $this->get_default_settings());
        $user_count = count_users();
        ?>
        <div class="wrap">
            <h1>用戶匯出設定</h1>
            
            <div class="notice notice-info">
                <h3>功能說明</h3>
                <p><strong>用戶匯出功能</strong>讓管理員可以快速將用戶資料匯出為 CSV 格式，方便使用 Excel 等工具進行分析。</p>
                
                <h4>匯出方式：</h4>
                <ul>
                    <li><strong>單一用戶</strong>：在用戶列表中每個用戶旁邊的「下載 CSV」連結</li>
                    <li><strong>批量匯出</strong>：選擇多個用戶後使用批量操作「下載 CSV」</li>
                    <li><strong>全部匯出</strong>：在此頁面一鍵匯出所有用戶</li>
                </ul>
                
                <h4>安全措施：</h4>
                <ul>
                    <li>使用 Nonce 驗證確保操作安全</li>
                    <li>只有管理員可以執行匯出操作</li>
                    <li>敏感資料可選擇性排除</li>
                    <li>支援角色過濾功能</li>
                </ul>
            </div>
            
            <form method="post" action="">
                <?php wp_nonce_field('wu_user_exporter_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">啟用功能</th>
                        <td>
                            <label>
                                <input type="checkbox" name="enabled" value="1" <?php checked($this->settings['enabled']); ?>>
                                啟用用戶匯出功能
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">包含元數據</th>
                        <td>
                            <label>
                                <input type="checkbox" name="include_meta" value="1" <?php checked($this->settings['include_meta']); ?>>
                                匯出用戶元數據（自訂欄位）
                            </label>
                            <p class="description">包含用戶的額外資訊如姓名、描述等</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">包含角色資訊</th>
                        <td>
                            <label>
                                <input type="checkbox" name="include_roles" value="1" <?php checked($this->settings['include_roles']); ?>>
                                匯出用戶角色資訊
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">日期格式</th>
                        <td>
                            <select name="date_format">
                                <option value="Y-m-d H:i:s" <?php selected($this->settings['date_format'], 'Y-m-d H:i:s'); ?>>2024-01-15 14:30:00</option>
                                <option value="Y-m-d" <?php selected($this->settings['date_format'], 'Y-m-d'); ?>>2024-01-15</option>
                                <option value="d/m/Y" <?php selected($this->settings['date_format'], 'd/m/Y'); ?>>15/01/2024</option>
                                <option value="m/d/Y" <?php selected($this->settings['date_format'], 'm/d/Y'); ?>>01/15/2024</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">基本欄位</th>
                        <td>
                            <?php foreach ($this->settings['fields'] as $field => $enabled): ?>
                            <label style="display: block; margin: 5px 0;">
                                <input type="checkbox" name="fields[<?php echo esc_attr($field); ?>]" value="1" <?php checked($enabled); ?>>
                                <?php echo esc_html($this->get_field_label($field)); ?>
                            </label>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">元數據欄位</th>
                        <td>
                            <?php foreach ($this->settings['meta_fields'] as $field => $enabled): ?>
                            <label style="display: block; margin: 5px 0;">
                                <input type="checkbox" name="meta_fields[<?php echo esc_attr($field); ?>]" value="1" <?php checked($enabled); ?>>
                                <?php echo esc_html($this->get_meta_field_label($field)); ?>
                            </label>
                            <?php endforeach; ?>
                            <p class="description">選擇要匯出的用戶元數據</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">自訂元數據欄位</th>
                        <td>
                            <textarea name="custom_meta_fields" rows="5" cols="50" placeholder="每行一個欄位鍵，例如：&#10;phone_number&#10;company&#10;address"><?php echo esc_textarea(implode("\n", $this->settings['custom_meta_fields'])); ?></textarea>
                            <p class="description">匯出自訂的元數據欄位（一行一個鍵值）</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">排除的用戶角色</th>
                        <td>
                            <?php $roles = wp_roles()->get_names(); ?>
                            <?php foreach ($roles as $role_key => $role_name): ?>
                            <label style="display: block; margin: 5px 0;">
                                <input type="checkbox" name="excluded_roles[]" value="<?php echo esc_attr($role_key); ?>" 
                                       <?php checked(in_array($role_key, $this->settings['excluded_roles'])); ?>>
                                <?php echo esc_html($role_name); ?> (<?php echo esc_html($role_key); ?>)
                            </label>
                            <?php endforeach; ?>
                            <p class="description">選中的角色不會被匯出</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('儲存設定'); ?>
            </form>
            
            <hr>
            
            <h2>快速匯出</h2>
            <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
                <div style="display: flex; gap: 20px; align-items: center; margin-bottom: 20px;">
                    <div>
                        <h3 style="margin: 0;">匯出所有用戶</h3>
                        <p style="margin: 5px 0 0 0; color: #666;">根據目前設定匯出所有符合條件的用戶</p>
                    </div>
                    <form method="post" action="">
                        <?php wp_nonce_field('wu_export_all_users'); ?>
                        <input type="submit" name="export_all_users" value="匯出所有用戶 CSV" class="button button-primary">
                    </form>
                </div>
                
                <div style="display: flex; gap: 20px; margin: 20px 0; flex-wrap: wrap;">
                    <div style="background: #f9f9f9; padding: 15px; border-radius: 5px; min-width: 150px;">
                        <strong>總用戶數：</strong><br>
                        <span style="font-size: 24px; color: #0073aa;"><?php echo number_format($user_count['total_users']); ?></span>
                    </div>
                    <div style="background: #f9f9f9; padding: 15px; border-radius: 5px; min-width: 150px;">
                        <strong>可匯出用戶：</strong><br>
                        <span style="font-size: 24px; color: #46b450;"><?php echo number_format($this->get_exportable_user_count()); ?></span>
                    </div>
                    <div style="background: #f9f9f9; padding: 15px; border-radius: 5px; min-width: 150px;">
                        <strong>本月匯出次數：</strong><br>
                        <span style="font-size: 24px; color: #ff8c00;"><?php echo $this->get_export_count('month'); ?></span>
                    </div>
                </div>
                
                <h4>用戶角色分佈：</h4>
                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <?php foreach ($user_count['avail_roles'] as $role => $count): ?>
                    <?php if ($count > 0): ?>
                    <div style="background: #e7f3ff; padding: 10px; border-radius: 3px;">
                        <strong><?php echo esc_html($roles[$role] ?? $role); ?>:</strong> <?php echo number_format($count); ?>
                    </div>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <h2>使用說明</h2>
            <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
                <h3>如何匯出用戶資料：</h3>
                <ol>
                    <li><strong>單一用戶匯出</strong>：前往「用戶」→「所有用戶」，點擊用戶旁邊的「下載 CSV」連結</li>
                    <li><strong>批量匯出</strong>：在用戶列表中勾選多個用戶，然後選擇批量操作「下載 CSV」</li>
                    <li><strong>全部匯出</strong>：在此頁面使用「匯出所有用戶 CSV」按鈕</li>
                    <li><strong>自訂匯出</strong>：調整上述設定後再執行匯出</li>
                </ol>
                
                <h3>CSV 檔案內容：</h3>
                <div style="display: flex; gap: 30px; margin-top: 15px;">
                    <div style="flex: 1;">
                        <h4>基本資訊：</h4>
                        <ul>
                            <li>用戶 ID、用戶名、電子郵件</li>
                            <li>顯示名稱、註冊日期</li>
                            <li>用戶狀態、網站 URL</li>
                        </ul>
                    </div>
                    <div style="flex: 1;">
                        <h4>擴展資訊：</h4>
                        <ul>
                            <li>姓名、暱稱、個人描述</li>
                            <li>用戶角色和權限</li>
                            <li>自訂元數據欄位</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .form-table th {
            width: 200px;
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
        if (!wp_verify_nonce($_POST['_wpnonce'], 'wu_user_exporter_settings')) {
            wp_die('安全驗證失敗');
        }
        
        $fields = isset($_POST['fields']) ? $_POST['fields'] : array();
        $meta_fields = isset($_POST['meta_fields']) ? $_POST['meta_fields'] : array();
        $excluded_roles = isset($_POST['excluded_roles']) ? array_map('sanitize_text_field', $_POST['excluded_roles']) : array();
        $custom_meta_fields = array_filter(array_map('trim', explode("\n", $_POST['custom_meta_fields'])));
        
        // 確保欄位格式正確
        $processed_fields = array();
        foreach ($this->settings['fields'] as $field => $default) {
            $processed_fields[$field] = isset($fields[$field]);
        }
        
        $processed_meta_fields = array();
        foreach ($this->settings['meta_fields'] as $field => $default) {
            $processed_meta_fields[$field] = isset($meta_fields[$field]);
        }
        
        $settings = array(
            'enabled' => isset($_POST['enabled']),
            'include_meta' => isset($_POST['include_meta']),
            'include_roles' => isset($_POST['include_roles']),
            'date_format' => sanitize_text_field($_POST['date_format']),
            'fields' => $processed_fields,
            'meta_fields' => $processed_meta_fields,
            'excluded_roles' => $excluded_roles,
            'custom_meta_fields' => $custom_meta_fields
        );
        
        update_option('wu_user_exporter_settings', $settings);
        $this->settings = $settings;
        
        echo '<div class="notice notice-success"><p>設定已儲存！</p></div>';
    }
    
    public function add_export_link($actions, $user) {
        if (!$this->settings['enabled']) {
            return $actions;
        }
        
        if (!current_user_can('list_users')) {
            return $actions;
        }
        
        if (in_array_any($user->roles, $this->settings['excluded_roles'])) {
            return $actions;
        }
        
        $export_url = wp_nonce_url(
            admin_url('admin.php?action=wu_export_user&user=' . $user->ID),
            'wu_export_user_' . $user->ID
        );
        
        $actions['export_csv'] = sprintf(
            '<a href="%s" title="%s">%s</a>',
            esc_url($export_url),
            esc_attr__('下載此用戶的 CSV 文件'),
            __('下載 CSV')
        );
        
        return $actions;
    }
    
    public function add_bulk_export_action($actions) {
        if (!$this->settings['enabled']) {
            return $actions;
        }
        
        if (!current_user_can('list_users')) {
            return $actions;
        }
        
        $actions['export_csv'] = __('下載 CSV');
        return $actions;
    }
    
    public function handle_bulk_export($redirect_to, $doaction, $user_ids) {
        if ($doaction !== 'export_csv') {
            return $redirect_to;
        }
        
        if (!current_user_can('list_users')) {
            return $redirect_to;
        }
        
        $this->export_users($user_ids);
        exit;
    }
    
    public function export_single_user() {
        if (!wp_verify_nonce($_GET['_wpnonce'], 'wu_export_user_' . $_GET['user'])) {
            wp_die('安全驗證失敗');
        }
        
        if (!current_user_can('list_users')) {
            wp_die('權限不足');
        }
        
        $user_id = intval($_GET['user']);
        $this->export_users(array($user_id));
        exit;
    }
    
    public function export_all_users() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'wu_export_all_users')) {
            wp_die('安全驗證失敗');
        }
        
        if (!current_user_can('list_users')) {
            wp_die('權限不足');
        }
        
        $users = get_users(array(
            'fields' => 'ID',
            'role__not_in' => $this->settings['excluded_roles']
        ));
        
        $user_ids = wp_list_pluck($users, 'ID');
        $this->export_users($user_ids);
        exit;
    }
    
    public function ajax_export_users() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'wu_export_users')) {
            wp_die('安全驗證失敗');
        }
        
        if (!current_user_can('list_users')) {
            wp_die('權限不足');
        }
        
        $user_ids = array_map('intval', $_POST['user_ids']);
        $csv_data = $this->generate_csv_data($user_ids);
        
        wp_send_json_success(array(
            'csv_data' => $csv_data,
            'filename' => $this->generate_filename(count($user_ids))
        ));
    }
    
    private function export_users($user_ids) {
        if (empty($user_ids)) {
            wp_die('沒有選擇用戶');
        }
        
        $csv_data = $this->generate_csv_data($user_ids);
        $filename = $this->generate_filename(count($user_ids));
        
        // 記錄匯出操作
        $this->log_export_action($user_ids);
        
        // 輸出 CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        
        // 添加 BOM 以支援 Excel 正確顯示中文
        echo "\xEF\xBB\xBF";
        echo $csv_data;
    }
    
    private function generate_csv_data($user_ids) {
        $users = get_users(array(
            'include' => $user_ids,
            'fields' => 'all_with_meta'
        ));
        
        // 準備標題行
        $headers = array();
        
        // 基本欄位
        foreach ($this->settings['fields'] as $field => $enabled) {
            if ($enabled) {
                $headers[] = $this->get_field_label($field);
            }
        }
        
        // 角色資訊
        if ($this->settings['include_roles']) {
            $headers[] = '用戶角色';
        }
        
        // 元數據欄位
        if ($this->settings['include_meta']) {
            foreach ($this->settings['meta_fields'] as $field => $enabled) {
                if ($enabled) {
                    $headers[] = $this->get_meta_field_label($field);
                }
            }
            
            // 自訂元數據欄位
            foreach ($this->settings['custom_meta_fields'] as $field) {
                $headers[] = $field;
            }
        }
        
        // 開始建立 CSV
        $csv_data = '';
        
        // 寫入標題
        $csv_data .= $this->array_to_csv_line($headers);
        
        // 寫入數據
        foreach ($users as $user) {
            $row = array();
            
            // 基本欄位
            foreach ($this->settings['fields'] as $field => $enabled) {
                if ($enabled) {
                    $value = $this->get_user_field_value($user, $field);
                    $row[] = $value;
                }
            }
            
            // 角色資訊
            if ($this->settings['include_roles']) {
                $roles = implode(', ', $user->roles);
                $row[] = $roles;
            }
            
            // 元數據欄位
            if ($this->settings['include_meta']) {
                foreach ($this->settings['meta_fields'] as $field => $enabled) {
                    if ($enabled) {
                        $value = get_user_meta($user->ID, $field, true);
                        $row[] = $value;
                    }
                }
                
                // 自訂元數據欄位
                foreach ($this->settings['custom_meta_fields'] as $field) {
                    $value = get_user_meta($user->ID, $field, true);
                    $row[] = $value;
                }
            }
            
            $csv_data .= $this->array_to_csv_line($row);
        }
        
        return $csv_data;
    }
    
    private function array_to_csv_line($array) {
        $csv_line = '';
        $first = true;
        
        foreach ($array as $value) {
            if (!$first) {
                $csv_line .= ',';
            }
            
            // 處理包含逗號、引號或換行的值
            if (strpos($value, ',') !== false || strpos($value, '"') !== false || strpos($value, "\n") !== false) {
                $value = '"' . str_replace('"', '""', $value) . '"';
            }
            
            $csv_line .= $value;
            $first = false;
        }
        
        return $csv_line . "\n";
    }
    
    private function get_user_field_value($user, $field) {
        switch ($field) {
            case 'user_registered':
                return date($this->settings['date_format'], strtotime($user->user_registered));
            case 'user_status':
                return $user->user_status == 0 ? '啟用' : '停用';
            default:
                return isset($user->$field) ? $user->$field : '';
        }
    }
    
    private function get_field_label($field) {
        $labels = array(
            'ID' => '用戶 ID',
            'user_login' => '用戶名',
            'user_email' => '電子郵件',
            'user_nicename' => '用戶暱稱',
            'display_name' => '顯示名稱',
            'user_registered' => '註冊日期',
            'user_status' => '用戶狀態',
            'user_url' => '網站 URL'
        );
        
        return isset($labels[$field]) ? $labels[$field] : $field;
    }
    
    private function get_meta_field_label($field) {
        $labels = array(
            'first_name' => '名',
            'last_name' => '姓',
            'nickname' => '暱稱',
            'description' => '個人描述',
            'rich_editing' => '富文本編輯',
            'syntax_highlighting' => '語法高亮',
            'comment_shortcuts' => '留言快捷鍵',
            'admin_color' => '後台顏色方案',
            'use_ssl' => '使用 SSL',
            'show_admin_bar_front' => '前台顯示管理欄',
            'locale' => '語言設定'
        );
        
        return isset($labels[$field]) ? $labels[$field] : $field;
    }
    
    private function generate_filename($user_count) {
        $date = date('Y-m-d');
        if ($user_count === 1) {
            return "user-export-{$date}.csv";
        } else {
            return "users-export-{$user_count}-{$date}.csv";
        }
    }
    
    private function get_exportable_user_count() {
        $args = array(
            'fields' => 'ID',
            'role__not_in' => $this->settings['excluded_roles']
        );
        
        $users = get_users($args);
        return count($users);
    }
    
    private function get_export_count($period = 'total') {
        $log_data = get_option('wu_user_exporter_log', array());
        
        if ($period === 'total') {
            return count($log_data);
        }
        
        $count = 0;
        $current_time = current_time('timestamp');
        
        foreach ($log_data as $entry) {
            $entry_time = strtotime($entry['date']);
            
            switch ($period) {
                case 'month':
                    if ($entry_time >= strtotime('-1 month', $current_time)) {
                        $count++;
                    }
                    break;
                case 'week':
                    if ($entry_time >= strtotime('-1 week', $current_time)) {
                        $count++;
                    }
                    break;
                case 'day':
                    if ($entry_time >= strtotime('-1 day', $current_time)) {
                        $count++;
                    }
                    break;
            }
        }
        
        return $count;
    }
    
    private function log_export_action($user_ids) {
        $log_data = get_option('wu_user_exporter_log', array());
        
        $log_entry = array(
            'date' => current_time('mysql'),
            'user_id' => get_current_user_id(),
            'exported_users' => count($user_ids),
            'exported_user_ids' => $user_ids
        );
        
        array_unshift($log_data, $log_entry);
        
        // 只保留最近50筆記錄
        if (count($log_data) > 50) {
            $log_data = array_slice($log_data, 0, 50);
        }
        
        update_option('wu_user_exporter_log', $log_data);
    }
    
    public function admin_notices() {
        if (isset($_GET['bulk_action']) && $_GET['bulk_action'] === 'export_csv') {
            $exported_count = intval($_GET['exported']);
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>' . sprintf(__('成功匯出 %d 個用戶的資料。'), $exported_count) . '</p>';
            echo '</div>';
        }
    }
}

// 輔助函數
function in_array_any($needles, $haystack) {
    if (!is_array($needles)) {
        $needles = array($needles);
    }
    
    foreach ($needles as $needle) {
        if (in_array($needle, $haystack)) {
            return true;
        }
    }
    
    return false;
}

// 初始化模組
new WU_User_Exporter();