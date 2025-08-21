<?php
if (!defined('ABSPATH')) exit;

/* === 後台子選單：開發常用外掛 === */
function dev_common_plugins_menu() {
    add_submenu_page(
        'wu-toolbox',
        '開發常用外掛',
        '開發常用外掛',
        'manage_options',
        'dev-common-plugins',
        'dev_common_plugins_page'
    );
}
add_action('admin_menu', 'dev_common_plugins_menu');

/* === 後台頁面內容 === */
function dev_common_plugins_page() {
    if (!current_user_can('install_plugins')) return;

    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

    // 常用外掛列表，key = slug, value = 顯示名稱
    $plugins = [
        'advanced-custom-fields-pro/acf.php' => 'ACF Pro',
        'wp-all-import-pro/wp-all-import.php' => 'WP All Import',
        'query-monitor/query-monitor.php' => 'Query Monitor',
        'woocommerce/woocommerce.php' => 'WooCommerce',
        'wp-rocket/wp-rocket.php' => 'WP Rocket',
    ];

    // 處理啟用
    if (isset($_POST['plugin_action'], $_POST['plugin_slug']) && check_admin_referer('dev_common_plugins','dev_common_plugins_nonce')) {
        $slug = sanitize_text_field($_POST['plugin_slug']);
        $action = sanitize_text_field($_POST['plugin_action']);

        if ($action === 'activate') {
            activate_plugin($slug);
        }
        echo '<div class="updated"><p>操作完成 ✅</p></div>';
    }

    ?>
    <div class="wrap">
        <h1>開發常用外掛</h1>
        <form method="post">
            <?php wp_nonce_field('dev_common_plugins','dev_common_plugins_nonce'); ?>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th>外掛名稱</th>
                        <th>狀態</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($plugins as $plugin_file => $name): 
                        $active = is_plugin_active($plugin_file);
                        ?>
                        <tr>
                            <td><?php echo esc_html($name); ?></td>
                            <td><?php echo $active ? '<span style="color:green;">已啟用</span>' : '<span style="color:red;">未啟用</span>'; ?></td>
                            <td>
                                <?php if (!$active): ?>
                                    <button type="submit" name="plugin_action" value="activate">啟用</button>
                                <?php else: ?>
                                    <em>已啟用</em>
                                <?php endif; ?>
                                <input type="hidden" name="plugin_slug" value="<?php echo esc_attr($plugin_file); ?>">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>
        <p>注意：部分付費外掛需先上傳至 plugins 資料夾，或修改安裝 URL。</p>
    </div>
    <?php
}
