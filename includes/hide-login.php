<?php
/**
 * Plugin Name: Hide WP Login Page - Simple
 * Description: 隱藏 WordPress 後台登入頁，並可自訂登入網址。
 * Version: 1.0
 * Author: Wu Wu
 */

if (!defined('ABSPATH')) exit;

/* === 後台子選單 === */
function hwlp_add_admin_menu() {
    add_submenu_page(
        'options-general.php',
        '隱藏後台登入',
        '隱藏後台登入',
        'manage_options',
        'hwlp-settings',
        'hwlp_settings_page'
    );
}
add_action('admin_menu','hwlp_add_admin_menu');

/* === 後台設定頁 === */
function hwlp_settings_page() {
    if (isset($_POST['hwlp_save']) && check_admin_referer('hwlp_save_nonce','hwlp_nonce')) {
        update_option('hwlp_status', sanitize_text_field($_POST['hwlp_status']));
        update_option('hwlp_url', sanitize_text_field($_POST['hwlp_url']));
        echo '<div class="updated"><p>設定已更新 ✅</p></div>';
    }

    $status = get_option('hwlp_status', 'off');
    $url    = get_option('hwlp_url', 'loginwu');
    ?>
    <div class="wrap">
        <h1>隱藏後台登入設定</h1>
        <form method="post" style="max-width:400px;">
            <?php wp_nonce_field('hwlp_save_nonce','hwlp_nonce'); ?>
            <p>
                <label><input type="radio" name="hwlp_status" value="on" <?php checked($status,'on'); ?>> 啟用隱藏登入</label><br>
                <label><input type="radio" name="hwlp_status" value="off" <?php checked($status,'off'); ?>> 停用隱藏登入</label>
            </p>
            <p>
                登入網址：<input type="text" name="hwlp_url" value="<?php echo esc_attr($url); ?>" class="regular-text">
                <br><small>請輸入自訂登入路徑，例如 loginwu、adminlogin，不要加 /</small>
            </p>
            <p><input type="submit" name="hwlp_save" class="button-primary" value="儲存設定"></p>
        </form>
    </div>
    <?php
}

/* === 前台登入重導 === */
function hwlp_redirect_login() {
    $status = get_option('hwlp_status', 'off');
    $login_path = get_option('hwlp_url', 'loginwu');

    if ($status !== 'on') return;

    $request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $request = untrailingslashit($request);
    $custom_login = '/' . $login_path;

    // 已登入不阻擋
    if (is_user_logged_in()) return;

    // 訪問自訂登入頁放行
    if ($request === $custom_login) return;

    // 嘗試訪問 wp-login.php 或 wp-admin
    if (strpos($request, 'wp-login.php') !== false || strpos($request, 'wp-admin') !== false) {
        wp_redirect(home_url($custom_login));
        exit;
    }
}
add_action('init', 'hwlp_redirect_login', 1);

/* === 改寫登入 URL === */
add_filter('login_url', function($login_url) {
    $status = get_option('hwlp_status', 'off');
    $url = get_option('hwlp_url', 'loginwu');
    if ($status === 'on') {
        return home_url('/' . $url . '/');
    }
    return $login_url;
}, 10, 1);

add_filter('logout_url', function($logout_url, $redirect) {
    $status = get_option('hwlp_status', 'off');
    $url = get_option('hwlp_url', 'loginwu');
    if ($status === 'on') {
        return home_url('/' . $url . '/?action=logout');
    }
    return $logout_url;
}, 10, 2);

/* === 自訂登入頁路由 === */
function hwlp_custom_login_page() {
    $status = get_option('hwlp_status', 'off');
    $login_path = get_option('hwlp_url', 'loginwu');

    if ($status !== 'on') return;

    $request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $request = untrailingslashit($request);
    $custom_login = '/' . $login_path;

    if ($request === $custom_login) {
        require_once ABSPATH . 'wp-login.php';
        exit;
    }
}
add_action('init', 'hwlp_custom_login_page');
