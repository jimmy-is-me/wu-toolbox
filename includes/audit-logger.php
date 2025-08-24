private function render_logs_table() {
    global $wpdb;
    
    // 獲取篩選參數
    $selected_user = isset($_GET['filter_user']) ? intval($_GET['filter_user']) : 0;
    $selected_action = isset($_GET['filter_action']) ? sanitize_text_field($_GET['filter_action']) : '';
    $selected_type = isset($_GET['filter_type']) ? sanitize_text_field($_GET['filter_type']) : '';
    $selected_limit = isset($_GET['filter_limit']) ? sanitize_text_field($_GET['filter_limit']) : 'unlimited';
    
    // 獲取所有有紀錄的使用者
    $users_with_logs = $wpdb->get_results("
        SELECT DISTINCT user_id, 
               (SELECT user_login FROM {$wpdb->users} WHERE ID = l.user_id) as user_login
        FROM {$this->table_name} l 
        WHERE user_id IS NOT NULL 
        ORDER BY user_login
    ");

    // 獲取所有動作類型
    $actions = $wpdb->get_col("SELECT DISTINCT action FROM {$this->table_name} ORDER BY action");
    
    // 獲取所有物件類型
    $object_types = $wpdb->get_col("SELECT DISTINCT object_type FROM {$this->table_name} WHERE object_type IS NOT NULL ORDER BY object_type");

    // 篩選表單
    echo '<form method="get" action="" style="margin-bottom: 20px;">';
    echo '<input type="hidden" name="page" value="wu-audit-logger">';
    echo '<div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">';
    
    // 使用者篩選
    echo '<div>';
    echo '<label>篩選使用者: ';
    echo '<select name="filter_user" onchange="this.form.submit()" style="min-width: 120px;">';
    echo '<option value="0">所有使用者</option>';
    foreach ($users_with_logs as $user) {
        if ($user->user_login) {
            $selected = selected($selected_user, $user->user_id, false);
            echo '<option value="' . intval($user->user_id) . '" ' . $selected . '>' . esc_html($user->user_login) . '</option>';
        }
    }
    echo '</select>';
    echo '</label>';
    echo '</div>';

    // 動作篩選
    echo '<div>';
    echo '<label>篩選動作: ';
    echo '<select name="filter_action" onchange="this.form.submit()" style="min-width: 120px;">';
    echo '<option value="">所有動作</option>';
    foreach ($actions as $action) {
        $selected_attr = selected($selected_action, $action, false);
        echo '<option value="' . esc_attr($action) . '" ' . $selected_attr . '>' . esc_html($action) . '</option>';
    }
    echo '</select>';
    echo '</label>';
    echo '</div>';

    // 類型篩選
    echo '<div>';
    echo '<label>篩選類型: ';
    echo '<select name="filter_type" onchange="this.form.submit()" style="min-width: 120px;">';
    echo '<option value="">所有類型</option>';
    foreach ($object_types as $type) {
        $selected_attr = selected($selected_type, $type, false);
        echo '<option value="' . esc_attr($type) . '" ' . $selected_attr . '>' . esc_html($type) . '</option>';
    }
    echo '</select>';
    echo '</label>';
    echo '</div>';

    // 顯示數量篩選
    echo '<div>';
    echo '<label>顯示數量: ';
    echo '<select name="filter_limit" onchange="this.form.submit()" style="min-width: 100px;">';
    $limit_options = array(
        '50' => '50 筆',
        '100' => '100 筆', 
        '150' => '150 筆',
        '200' => '200 筆',
        'unlimited' => '不限制'
    );
    foreach ($limit_options as $value => $label) {
        $selected_attr = selected($selected_limit, $value, false);
        echo '<option value="' . esc_attr($value) . '" ' . $selected_attr . '>' . esc_html($label) . '</option>';
    }
    echo '</select>';
    echo '</label>';
    echo '</div>';

    // 清除篩選按鈕
    if ($selected_user || $selected_action || $selected_type || $selected_limit !== 'unlimited') {
        echo '<div>';
        echo '<a href="?page=wu-audit-logger" class="button">清除篩選</a>';
        echo '</div>';
    }
    
    echo '</div>';
    echo '</form>';

    // 準備查詢條件
    $where_conditions = array();
    $where_values = array();
    
    if ($selected_user) {
        $where_conditions[] = 'user_id = %d';
        $where_values[] = $selected_user;
    }
    
    if ($selected_action) {
        $where_conditions[] = 'action = %s';
        $where_values[] = $selected_action;
    }
    
    if ($selected_type) {
        $where_conditions[] = 'object_type = %s';
        $where_values[] = $selected_type;
    }

    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = ' WHERE ' . implode(' AND ', $where_conditions);
    }

    // 準備 LIMIT 子句
    $limit_clause = '';
    if ($selected_limit !== 'unlimited') {
        $limit = intval($selected_limit);
        $limit_clause = " LIMIT {$limit}";
    }

    // 執行查詢
    $sql = "SELECT * FROM {$this->table_name}{$where_clause} ORDER BY id DESC{$limit_clause}";
    
    if (!empty($where_values)) {
        $rows = $wpdb->get_results($wpdb->prepare($sql, $where_values));
    } else {
        $rows = $wpdb->get_results($sql);
    }
    
    // 獲取總記錄數（用於顯示統計資訊）
    $count_sql = "SELECT COUNT(*) FROM {$this->table_name}{$where_clause}";
    if (!empty($where_values)) {
        $total_count = $wpdb->get_var($wpdb->prepare($count_sql, $where_values));
    } else {
        $total_count = $wpdb->get_var($count_sql);
    }

    if (empty($rows)) {
        echo '<p>目前沒有符合條件的紀錄。</p>';
        return;
    }

    // 顯示統計資訊
    $showing_count = count($rows);
    if ($selected_limit !== 'unlimited') {
        echo '<p><strong>顯示 ' . $showing_count . ' 筆紀錄（共 ' . $total_count . ' 筆）</strong></p>';
    } else {
        echo '<p><strong>顯示全部 ' . $total_count . ' 筆紀錄</strong></p>';
    }

    echo '<table class="widefat striped"><thead><tr>';
    echo '<th>時間</th><th>使用者</th><th>動作</th><th>類型</th><th>目標</th><th>IP</th>';
    echo '</tr></thead><tbody>';
    
    foreach ($rows as $r) {
        $user_display = $r->user_id ? esc_html(get_the_author_meta('user_login', $r->user_id)) : '-';
        
        echo '<tr>';
        echo '<td>' . esc_html(get_date_from_gmt($r->log_time, 'Y-m-d H:i:s')) . '</td>';
        echo '<td>' . $user_display . '</td>';
        echo '<td>' . esc_html($r->action) . '</td>';
        echo '<td>' . esc_html($r->object_type ?: '-') . '</td>';
        echo '<td>' . esc_html(trim(($r->object_name ? $r->object_name . ' ' : '') . ($r->object_id ? "(#{$r->object_id})" : ''))) . '</td>';
        echo '<td>' . esc_html($r->ip_address ?: '-') . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
}
