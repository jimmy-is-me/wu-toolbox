/* === 後台設定頁 === */
function plugin_manager_settings_page() {
    $messages = array();

    // === 外掛安裝、啟用、停用、刪除處理 ===
    if (isset($_POST['install_plugin']) && !empty($_POST['plugin_slug']) && check_admin_referer('install_plugin_' . $_POST['plugin_slug'])) {
        $result = install_plugin_from_repo(sanitize_text_field($_POST['plugin_slug']));
        $messages[] = $result['message'];
    }

    if (isset($_POST['activate_plugin']) && !empty($_POST['plugin_file']) && check_admin_referer('activate_plugin_' . $_POST['plugin_file'])) {
        $result = activate_plugin(sanitize_text_field($_POST['plugin_file']));
        $messages[] = is_wp_error($result) ? '啟用失敗：' . $result->get_error_message() : '外掛已啟用';
    }

    if (isset($_POST['deactivate_plugin']) && !empty($_POST['plugin_file']) && check_admin_referer('deactivate_plugin_' . $_POST['plugin_file'])) {
        deactivate_plugins(sanitize_text_field($_POST['plugin_file']));
        $messages[] = '外掛已停用';
    }

    if (isset($_POST['delete_plugin']) && !empty($_POST['plugin_file']) && check_admin_referer('delete_plugin_' . $_POST['plugin_file'])) {
        $result = delete_plugin_from_site(sanitize_text_field($_POST['plugin_file']));
        $messages[] = $result['message'];
    }

    // === 顯示訊息 ===
    if (!empty($messages)) {
        echo '<div class="updated notice"><ul>';
        foreach ($messages as $msg) {
            echo '<li>' . esc_html($msg) . '</li>';
        }
        echo '</ul></div>';
    }

    // === 外掛區塊 ===
    echo '<div class="wrap"><h1>常用外掛管理</h1>';
    echo '<h2>已安裝外掛</h2>';

    $installed_plugins = get_all_installed_plugins();
    if (!empty($installed_plugins)) {
        echo '<table class="widefat striped"><thead><tr><th>名稱</th><th>描述</th><th>版本</th><th>狀態</th><th>操作</th></tr></thead><tbody>';
        foreach ($installed_plugins as $plugin) {
            echo '<tr>';
            echo '<td><a href="' . esc_url($plugin['wp_url']) . '" target="_blank">' . esc_html($plugin['name']) . '</a></td>';
            echo '<td>' . esc_html($plugin['description']) . '</td>';
            echo '<td>' . esc_html($plugin['version']) . '</td>';
            echo '<td>' . esc_html($plugin['status']) . '</td>';
            echo '<td>';
            
            if ($plugin['status'] === 'active') {
                echo '<form method="post" style="display:inline">';
                wp_nonce_field('deactivate_plugin_' . $plugin['file']);
                echo '<input type="hidden" name="plugin_file" value="' . esc_attr($plugin['file']) . '">';
                echo '<button type="submit" name="deactivate_plugin" class="button">停用</button></form> ';
            } elseif ($plugin['status'] === 'installed') {
                echo '<form method="post" style="display:inline">';
                wp_nonce_field('activate_plugin_' . $plugin['file']);
                echo '<input type="hidden" name="plugin_file" value="' . esc_attr($plugin['file']) . '">';
                echo '<button type="submit" name="activate_plugin" class="button-primary">啟用</button></form> ';
            }

            if ($plugin['status'] !== 'active') {
                echo '<form method="post" style="display:inline">';
                wp_nonce_field('delete_plugin_' . $plugin['file']);
                echo '<input type="hidden" name="plugin_file" value="' . esc_attr($plugin['file']) . '">';
                echo '<button type="submit" name="delete_plugin" class="button">刪除</button></form>';
            }

            echo '</td></tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>尚未安裝任何外掛。</p>';
    }

    // === 主題區塊 ===
    echo '<h2 style="margin-top:40px;">已安裝主題</h2>';

    $installed_themes = get_all_installed_themes();
    if (!empty($installed_themes)) {
        echo '<div style="display:flex;flex-wrap:wrap;gap:20px;">';
        foreach ($installed_themes as $theme) {
            echo '<div style="width:250px;border:1px solid #ddd;padding:10px;background:#fff;">';
            if ($theme['screenshot']) {
                echo '<img src="' . esc_url($theme['screenshot']) . '" style="width:100%;height:auto;margin-bottom:10px;">';
            }
            echo '<h3>' . esc_html($theme['name']) . '</h3>';
            echo '<p>版本：' . esc_html($theme['version']) . '</p>';
            echo '<p>作者：' . esc_html($theme['author']) . '</p>';
            echo '<p>狀態：' . ($theme['status'] === 'active' ? '使用中' : '已安裝') . '</p>';

            echo '<a href="' . wp_nonce_url(admin_url('admin.php?page=wu-toolbox&download_theme=1&theme_slug=' . $theme['slug']), 'download_theme_' . $theme['slug']) . '" class="button">下載 ZIP</a>';

            echo '</div>';
        }
        echo '</div>';
    } else {
        echo '<p>尚未安裝任何主題。</p>';
    }

    echo '</div>'; // end wrap
}
