<?php
if (!defined('ABSPATH')) exit;

/* === 後台子選單 === */
function hide_login_menu() {
    add_submenu_page(
        'wu-toolbox',
        '隱藏後台登入',
        '隱藏後台登入',
        'manage_options',
        'hide-login',
        'hide_login_settings_page'
    );
}
add_action('admin_menu','hide_login_menu');

/* === 後台設定頁 === */
function hide_login_settings_page() {
    if (isset($_POST['hide_login_save']) && check_admin_referer('hide_login_save','hide_login_nonce')) {
        update_option('hide_login_status', sanitize_text_field($_POST['hide_login_status']));
        update_option('hide_login_url', sanitize_text_field($_POST['hide_login_url']));
        echo '<div class="updated"><p>設定已更新 ✅</p></div>';
    }

    $status = get_option('hide_login_status', 'off');
    $url    = get_option('hide_login_url', 'loginwu');
    ?>
    <div class="wrap">
        <h1>隱藏後台登入設定</h1>
        <form method="post" style="max-width:400px;">
            <?php wp_nonce_field('hide_login_save','hide_login_nonce'); ?>
            <p>
                <label><input type="radio" name="hide_login_status" value="on" <?php checked($status,'on'); ?>> 啟用隱藏登入（前台預設登入將被隱藏）</label><br>
                <label><input type="radio" name="hide_login_status" value="off" <?php checked($status,'off'); ?>> 停用隱藏登入（恢復原始登入頁）</label>
            </p>
            <p>
                登入網址：<input type="text" name="hide_login_url" value="<?php echo esc_attr($url); ?>" class="regular-text">
                <br><small>請輸入您想要的登入路徑，例如 loginwu、adminlogin 等，不要加 / </small>
            </p>
            <p><input type="submit" name="hide_login_save" class="button-primary" value="儲存設定"></p>
        </form>
    </div>
    <?php
}

/* === 前台登入重寫功能 === */
function hide_login_redirect() {
    $status = get_option('hide_login_status', 'off');
    $login_url = get_option('hide_login_url', 'loginwu');

    if ($status !== 'on') return;

    $request_uri = $_SERVER['REQUEST_URI'];

    // 允許訪問自訂登入頁
    if (strpos($request_uri, '/' . $login_url . '/') !== false) return;

    // 嘗試訪問 wp-login.php 或 wp-admin
    if (strpos($request_uri, 'wp-login.php') !== false || strpos($request_uri, 'wp-admin') !== false) {
        wp_redirect(home_url());
        exit;
    }
}
add_action('init', 'hide_login_redirect', 1);

/* === 改變登入 URL === */
function custom_login_url($login_url, $redirect, $force_reauth) {
    $status = get_option('hide_login_status', 'off');
    $url = get_option('hide_login_url', 'loginwu');

    if ($status === 'on') {
        return home_url('/' . $url . '/');
    }
    return $login_url;
}
add_filter('login_url', 'custom_login_url', 10, 3);
add_filter('logout_url', function($logout_url, $redirect){
    $status = get_option('hide_login_status', 'off');
    $url = get_option('hide_login_url', 'loginwu');
    if ($status === 'on') {
        return home_url('/' . $url . '/?action=logout');
    }
    return $logout_url;
}, 10, 2);

/* === 自訂登入頁面路由 === */
function custom_login_page_route() {
    $status = get_option('hide_login_status', 'off');
    $url = get_option('hide_login_url', 'loginwu');
    if ($status !== 'on') return;

    $request_uri = $_SERVER['REQUEST_URI'];
    if (strpos($request_uri, '/' . $url . '/') !== false) {
        require_once ABSPATH . 'wp-login.php';
        exit;
    }
}
add_action('init', 'custom_login_page_route');
