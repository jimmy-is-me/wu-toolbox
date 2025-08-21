<?php
if (!defined('ABSPATH')) exit;

/* === 後台子選單 === */
function wu_toolbox_hide_login_menu() {
    add_submenu_page(
        'wu-toolbox',
        '隱藏後台登入',
        '隱藏後台登入',
        'manage_options',
        'hide-login',
        'wu_toolbox_hide_login_settings_page'
    );
}
add_action('admin_menu','wu_toolbox_hide_login_menu');

/* === 後台設定頁 === */
function wu_toolbox_hide_login_settings_page() {
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
                <label><input type="radio" name="hide_login_status" value="on" <?php checked($status,'on'); ?>> 啟用隱藏登入</label><br>
                <label><input type="radio" name="hide_login_status" value="off" <?php checked($status,'off'); ?>> 停用隱藏登入</label>
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

/* === 安全檢查：允許管理員用 ?admin_key=xxxx 登入原始 wp-login.php === */
define('WU_TOOLBOX_ADMIN_KEY', '123456'); // 可自訂安全 key

/* === 前台登入重寫與保護 === */
function wu_toolbox_hide_login_redirect() {
    $status = get_option('hide_login_status', 'off');
    $login_path = get_option('hide_login_url', 'loginwu');

    if ($status !== 'on') return;

    $request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $request = untrailingslashit($request);
    $custom_login = '/' . $login_path;

    // 訪問自訂登入頁
    if ($request === $custom_login) {
        require_once ABSPATH . 'wp-login.php';
        exit;
    }

    // 如果是原始登入頁，但有安全 key，允許
    if (strpos($request, 'wp-login.php') !== false && isset($_GET['admin_key']) && $_GET['admin_key'] === WU_TOOLBOX_ADMIN_KEY) {
        return; // 允許登入
    }

    // 攔截 wp-login.php 或 wp-admin
    if (strpos($request, 'wp-login.php') !== false || strpos($request, 'wp-admin') !== false) {
        wp_redirect(home_url());
        exit;
    }
}
add_action('template_redirect', 'wu_toolbox_hide_login_redirect');

/* === 改變登入 URL === */
function wu_toolbox_custom_login_url($login_url, $redirect = '', $force_reauth = false) {
    $status = get_option('hide_login_status', 'off');
    $url = get_option('hide_login_url', 'loginwu');

    if ($status === 'on') {
        $login_url = home_url('/' . $url . '/');
        if (!empty($redirect)) $login_url = add_query_arg('redirect_to', urlencode($redirect), $login_url);
        if ($force_reauth) $login_url = add_query_arg('reauth', '1', $login_url);
    }
    return $login_url;
}
add_filter('login_url', 'wu_toolbox_custom_login_url', 10, 3);

/* === 登出導向首頁 === */
add_filter('logout_url', function($logout_url, $redirect){
    return home_url();
}, 10, 2);

/* === 後台未登入防護 === */
function wu_toolbox_admin_redirect_protect() {
    $status = get_option('hide_login_status', 'off');
    if ($status !== 'on') return;

    if (is_admin() && !is_user_logged_in() && !defined('DOING_AJAX')) {
        wp_redirect(home_url());
        exit;
    }
}
add_action('admin_init', 'wu_toolbox_admin_redirect_protect');
