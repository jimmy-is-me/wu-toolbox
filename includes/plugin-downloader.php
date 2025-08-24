<?php
if (!defined('ABSPATH')) exit;

/* === 後台子選單 === */
function plugin_downloader_menu() {
    add_submenu_page(
        'wumetax-toolkit',        // 父選單 slug
        '常用外掛管理',
        '常用外掛管理',
        'manage_options',
        'wumetax-toolkit',        // 與父選單相同的 slug
        'plugin_manager_settings_page'
    );
}
add_action('admin_menu', 'plugin_downloader_menu', 5);

/* === 常用外掛清單 === */
function get_popular_plugins_list() {
    return array(
        // 內容管理
        'advanced-custom-fields' => array(
            'name' => 'Advanced Custom Fields',
            'description' => '當完成安裝與建立客製化新型態後，會發現那還只是很陽春的文章/頁面結構－樣，只是在後台選單上多了一個新的選項，這時候要搭配這款工具，指定新型態格式，自定義該新型態內容使用那些欄位。',
            'slug' => 'advanced-custom-fields',
            'wp_url' => 'https://wordpress.org/plugins/advanced-custom-fields/',
            'category' => '內容管理'
        ),
        'classic-editor' => array(
            'name' => 'Classic Editor',
            'description' => '恢復經典編輯器介面，適合習慣舊版編輯器的用戶。',
            'slug' => 'classic-editor',
            'wp_url' => 'https://wordpress.org/plugins/classic-editor/',
            'category' => '內容管理'
        ),
        'classic-widgets' => array(
            'name' => 'Classic Widgets',
            'description' => '恢復經典小工具介面，移除區塊編輯器的小工具功能。',
            'slug' => 'classic-widgets',
            'wp_url' => 'https://wordpress.org/plugins/classic-widgets/',
            'category' => '內容管理'
        ),
        'tinymce-advanced' => array(
            'name' => 'Advanced Editor Tools',
            'description' => '增強經典編輯器功能，提供更多編輯選項和工具列。',
            'slug' => 'tinymce-advanced',
            'wp_url' => 'https://wordpress.org/plugins/tinymce-advanced/',
            'category' => '內容管理'
        ),

        // 用戶管理
        'user-role-editor' => array(
            'name' => 'User Role Editor',
            'description' => '從最基礎的權限（Capabilities）出發，可以配置給哪種的使用者或是角色，介面搞微簡單。',
            'slug' => 'user-role-editor',
            'wp_url' => 'https://wordpress.org/plugins/user-role-editor/',
            'category' => '用戶管理'
        ),
        'user-switching' => array(
            'name' => 'User Switching',
            'description' => '更改使用者權限後會希望能夠預覽該使用者權限的設定是否正確，使用這外掛快速切換。',
            'slug' => 'user-switching',
            'wp_url' => 'https://wordpress.org/plugins/user-switching/',
            'category' => '用戶管理'
        ),
        'username-changer' => array(
            'name' => 'Username Changer',
            'description' => '更改使用者名稱的小工具。',
            'slug' => 'username-changer',
            'wp_url' => 'https://wordpress.org/plugins/username-changer/',
            'category' => '用戶管理'
        ),
        'prevent-concurrent-logins' => array(
            'name' => 'Loggedin – Limit Active Logins',
            'description' => '限制同一帳號的併發登入數量，提升網站安全性。',
            'slug' => 'prevent-concurrent-logins',
            'wp_url' => 'https://wordpress.org/plugins/prevent-concurrent-logins/',
            'category' => '用戶管理'
        ),
        'heateor-social-login' => array(
            'name' => 'Heateor Social Login WordPress',
            'description' => '提供社群媒體登入功能，支援多種社群平台。',
            'slug' => 'heateor-social-login',
            'wp_url' => 'https://wordpress.org/plugins/heateor-social-login/',
            'category' => '用戶管理'
        ),
        'nextend-social-login' => array(
            'name' => 'Nextend Social Login',
            'description' => '專業的社群登入外掛，支援多種社群平台登入。',
            'slug' => 'nextend-social-login',
            'wp_url' => 'https://wordpress.org/plugins/nextend-social-login/',
            'category' => '用戶管理'
        ),

        // 效能優化
        'wp-super-cache' => array(
            'name' => 'WP Super Cache',
            'description' => '提升網站速度的快取外掛，減少伺服器負載。',
            'slug' => 'wp-super-cache',
            'wp_url' => 'https://wordpress.org/plugins/wp-super-cache/',
            'category' => '效能優化'
        ),
        'auto-upload-images' => array(
            'name' => 'Auto Upload Images',
            'description' => '自動下載外部圖片到本地媒體庫，提升載入速度。',
            'slug' => 'auto-upload-images',
            'wp_url' => 'https://wordpress.org/plugins/auto-upload-images/',
            'category' => '效能優化'
        ),
        'thumbnails-regenerate' => array(
            'name' => 'ThumbPress',
            'description' => '重新產生縮圖，優化圖片顯示和載入效能。',
            'slug' => 'thumbnails-regenerate',
            'wp_url' => 'https://wordpress.org/plugins/thumbnails-regenerate/',
            'category' => '效能優化'
        ),

        // 郵件管理
        'fluent-smtp' => array(
            'name' => 'FluentSMTP',
            'description' => '專業的 SMTP 郵件發送外掛，確保郵件送達率。',
            'slug' => 'fluent-smtp',
            'wp_url' => 'https://wordpress.org/plugins/fluent-smtp/',
            'category' => '郵件管理'
        ),

        // SEO優化
        'seo-by-rank-math' => array(
            'name' => 'Rank Math SEO',
            'description' => '全方位 SEO 優化外掛，提升網站搜尋引擎排名。',
            'slug' => 'seo-by-rank-math',
            'wp_url' => 'https://wordpress.org/plugins/seo-by-rank-math/',
            'category' => 'SEO優化'
        ),
        'breadcrumb-navxt' => array(
            'name' => 'Breadcrumb NavXT',
            'description' => '建立網站麵包屑導航，改善用戶體驗和 SEO。',
            'slug' => 'breadcrumb-navxt',
            'wp_url' => 'https://wordpress.org/plugins/breadcrumb-navxt/',
            'category' => 'SEO優化'
        ),

        // 功能管理
        'disable-comments' => array(
            'name' => 'Disable Comments',
            'description' => '徹底停用網站評論功能，清理相關選單和數據。',
            'slug' => 'disable-comments',
            'wp_url' => 'https://wordpress.org/plugins/disable-comments/',
            'category' => '功能管理'
        ),

        // 電商功能
        'woocommerce' => array(
            'name' => 'WooCommerce',
            'description' => 'WordPress 最受歡迎的電商外掛，建立完整的線上商店。',
            'slug' => 'woocommerce',
            'wp_url' => 'https://wordpress.org/plugins/woocommerce/',
            'category' => '電商功能'
        ),
        'woo-order-export-lite' => array(
            'name' => 'Advanced Order Export For WooCommerce',
            'description' => '匯出 WooCommerce 訂單數據，支援多種格式。',
            'slug' => 'woo-order-export-lite',
            'wp_url' => 'https://wordpress.org/plugins/woo-order-export-lite/',
            'category' => '電商功能'
        ),
        'wc-sale-notifications-for-discord' => array(
            'name' => 'WC Sale Discord Notifications',
            'description' => '將 WooCommerce 銷售通知發送到 Discord 頻道。',
            'slug' => 'wc-sale-notifications-for-discord',
            'wp_url' => 'https://wordpress.org/plugins/wc-sale-notifications-for-discord/',
            'category' => '電商功能'
        ),
        'ajax-search-for-woocommerce' => array(
            'name' => 'FiboSearch - AJAX Search for WooCommerce',
            'description' => '為 WooCommerce 提供即時搜尋功能，提升購物體驗。',
            'slug' => 'ajax-search-for-woocommerce',
            'wp_url' => 'https://wordpress.org/plugins/ajax-search-for-woocommerce/',
            'category' => '電商功能'
        ),
        'flexible-checkout-fields' => array(
            'name' => 'Flexible Checkout Fields',
            'description' => '自訂 WooCommerce 結帳頁面欄位，靈活配置購物流程。',
            'slug' => 'flexible-checkout-fields',
            'wp_url' => 'https://wordpress.org/plugins/flexible-checkout-fields/',
            'category' => '電商功能'
        ),

        // 網站建構
        'elementor' => array(
            'name' => 'Elementor',
            'description' => '專業的頁面建構器，拖拉即可建立美觀的網頁。',
            'slug' => 'elementor',
            'wp_url' => 'https://wordpress.org/plugins/elementor/',
            'category' => '網站建構'
        ),
        'greenshift-animation-and-page-builder-blocks' => array(
            'name' => 'Greenshift',
            'description' => '動畫和頁面建構區塊，增強 Gutenberg 編輯器功能。',
            'slug' => 'greenshift-animation-and-page-builder-blocks',
            'wp_url' => 'https://wordpress.org/plugins/greenshift-animation-and-page-builder-blocks/',
            'category' => '網站建構'
        ),

        // 表單功能
        'fluentform' => array(
            'name' => 'Fluent Forms',
            'description' => '強大的表單建構器，建立各種互動表單。',
            'slug' => 'fluentform',
            'wp_url' => 'https://wordpress.org/plugins/fluentform/',
            'category' => '表單功能'
        ),

        // 翻譯本地化
        'loco-translate' => array(
            'name' => 'Loco Translate',
            'description' => '在 WordPress 後台直接翻譯外掛和主題，支援多語言。',
            'slug' => 'loco-translate',
            'wp_url' => 'https://wordpress.org/plugins/loco-translate/',
            'category' => '翻譯本地化'
        ),
        'translatepress-multilingual' => array(
            'name' => 'TranslatePress',
            'description' => '視覺化多語言外掛，前台即時翻譯網站內容。',
            'slug' => 'translatepress-multilingual',
            'wp_url' => 'https://wordpress.org/plugins/translatepress-multilingual/',
            'category' => '翻譯本地化'
        ),

        // 媒體管理
        'instant-images' => array(
            'name' => 'Instant Images',
            'description' => '快速搜尋和插入免費圖片到 WordPress 媒體庫。',
            'slug' => 'instant-images',
            'wp_url' => 'https://wordpress.org/plugins/instant-images/',
            'category' => '媒體管理'
        ),

        // 備份還原
        'updraftplus' => array(
            'name' => 'UpdraftPlus',
            'description' => '最受歡迎的 WordPress 備份外掛，支援多種雲端儲存。',
            'slug' => 'updraftplus',
            'wp_url' => 'https://wordpress.org/plugins/updraftplus/',
            'category' => '備份還原'
        ),
        'wpvivid-backuprestore' => array(
            'name' => 'wpvivid',
            'description' => '免費的備份和還原外掛，支援網站遷移功能。',
            'slug' => 'wpvivid-backuprestore',
            'wp_url' => 'https://wordpress.org/plugins/wpvivid-backuprestore/',
            'category' => '備份還原'
        ),

        // 系統維護
        'wp-downgrade' => array(
            'name' => 'WP Downgrade',
            'description' => '降級 WordPress 版本，解決相容性問題。',
            'slug' => 'wp-downgrade',
            'wp_url' => 'https://wordpress.org/plugins/wp-downgrade/',
            'category' => '系統維護'
        ),

        // 彈出視窗
        'popup-trigger-url-for-elementor-pro' => array(
            'name' => 'Popup Trigger URL for Elementor Pro',
            'description' => '為 Elementor Pro 彈出視窗添加 URL 觸發功能。',
            'slug' => 'popup-trigger-url-for-elementor-pro',
            'wp_url' => 'https://wordpress.org/plugins/popup-trigger-url-for-elementor-pro/',
            'category' => '彈出視窗'
        )
    );
}

/* === 取得所有已安裝外掛（包含上傳的） === */
function get_all_installed_plugins() {
    if (!function_exists('get_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    
    $all_plugins = get_plugins();
    $installed_plugins = array();
    
    foreach ($all_plugins as $plugin_path => $plugin_data) {
        $plugin_slug = dirname($plugin_path);
        if ($plugin_slug === '.') {
            $plugin_slug = basename($plugin_path, '.php');
        }
        
        $is_active = is_plugin_active($plugin_path);
        $needs_update = false;
        
        // 檢查是否需要更新
        if (!function_exists('get_plugin_updates')) {
            require_once ABSPATH . 'wp-admin/includes/update.php';
        }
        
        $update_plugins = get_site_transient('update_plugins');
        if (isset($update_plugins->response[$plugin_path])) {
            $needs_update = true;
        }
        
        $installed_plugins[$plugin_slug] = array(
            'name' => $plugin_data['Name'],
            'description' => $plugin_data['Description'] ?: '手動上傳的外掛',
            'slug' => $plugin_slug,
            'wp_url' => $plugin_data['PluginURI'] ?: '#',
            'category' => '手動安裝',
            'status' => $is_active ? 'active' : 'installed',
            'file' => $plugin_path,
            'needs_update' => $needs_update,
            'version' => $plugin_data['Version'] ?: ''
        );
    }
    
    return $installed_plugins;
}

/* === 檢查外掛詳細狀態 === */
function get_plugin_detailed_status($plugin_slug) {
    if (!function_exists('get_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    
    $all_plugins = get_plugins();
    $plugin_file = '';
    $plugin_data = null;
    
    foreach ($all_plugins as $plugin_path => $plugin_info) {
        if (strpos($plugin_path, $plugin_slug . '/') === 0 || strpos($plugin_path, $plugin_slug . '.php') !== false) {
            $plugin_file = $plugin_path;
            $plugin_data = $plugin_info;
            break;
        }
    }
    
    if (empty($plugin_file)) {
        return array('status' => 'not_installed', 'file' => '', 'needs_update' => false, 'version' => '');
    }
    
    $is_active = is_plugin_active($plugin_file);
    $needs_update = false;
    $version = isset($plugin_data['Version']) ? $plugin_data['Version'] : '';
    
    if (!function_exists('get_plugin_updates')) {
        require_once ABSPATH . 'wp-admin/includes/update.php';
    }
    
    $update_plugins = get_site_transient('update_plugins');
    if (isset($update_plugins->response[$plugin_file])) {
        $needs_update = true;
    }
    
    return array(
        'status' => $is_active ? 'active' : 'installed',
        'file' => $plugin_file,
        'needs_update' => $needs_update,
        'version' => $version
    );
}

/* === 安裝外掛函數 === */
function install_plugin_from_repo($plugin_slug) {
    if (!current_user_can('install_plugins')) {
        return array('success' => false, 'message' => '權限不足');
    }
    
    require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/misc.php';
    require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    
    $api = plugins_api('plugin_information', array('slug' => $plugin_slug));
    
    if (is_wp_error($api)) {
        return array('success' => false, 'message' => '無法取得外掛資訊：' . $api->get_error_message());
    }
    
    $upgrader = new Plugin_Upgrader();
    $installed = $upgrader->install($api->download_link);
    
    if (is_wp_error($installed)) {
        return array('success' => false, 'message' => '安裝失敗：' . $installed->get_error_message());
    }
    
    return array('success' => true, 'message' => '安裝成功');
}

/* === 上傳並安裝外掛 === */
function install_uploaded_plugin($file_data) {
    if (!current_user_can('install_plugins')) {
        return array('success' => false, 'message' => '權限不足');
    }
    
    if ($file_data['error'] !== UPLOAD_ERR_OK) {
        return array('success' => false, 'message' => '檔案上傳失敗');
    }
    
    $file_type = wp_check_filetype($file_data['name']);
    if ($file_type['ext'] !== 'zip') {
        return array('success' => false, 'message' => '只支援ZIP檔案格式');
    }
    
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/misc.php';
    require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    
    $upload_overrides = array('test_form' => false);
    $movefile = wp_handle_upload($file_data, $upload_overrides);
    
    if ($movefile && !isset($movefile['error'])) {
        $upgrader = new Plugin_Upgrader();
        $installed = $upgrader->install($movefile['file']);
        
        // 清理上傳的檔案
        unlink($movefile['file']);
        
        if (is_wp_error($installed)) {
            return array('success' => false, 'message' => '安裝失敗：' . $installed->get_error_message());
        }
        
        return array('success' => true, 'message' => '檔案上傳並安裝成功');
    } else {
        return array('success' => false, 'message' => '檔案處理失敗：' . $movefile['error']);
    }
}

/* === 刪除外掛函數 === */
function delete_plugin_from_site($plugin_file) {
    if (!current_user_can('delete_plugins')) {
        return array('success' => false, 'message' => '權限不足');
    }
    
    if (is_plugin_active($plugin_file)) {
        return array('success' => false, 'message' => '外掛仍在啟用中，請先停用再刪除');
    }
    
    $deleted = delete_plugins(array($plugin_file));
    
    if (is_wp_error($deleted)) {
        return array('success' => false, 'message' => '刪除失敗：' . $deleted->get_error_message());
    }
    
    return array('success' => true, 'message' => '外掛已刪除');
}

/* === 後台設定頁 === */
function plugin_manager_settings_page() {
    $messages = array();
    $activated_plugins = array();
    $deactivated_plugins = array();
    $deleted_plugins = array();
    
    // 處理檔案上傳
    if (isset($_POST['upload_plugin']) && isset($_FILES['plugin_file']) && check_admin_referer('upload_plugin_action', 'upload_plugin_nonce')) {
        $result = install_uploaded_plugin($_FILES['plugin_file']);
        $messages[] = array('type' => $result['success'] ? 'success' : 'error', 'message' => $result['message']);
    }
    
    // 處理單個操作
    if (isset($_GET['action']) && isset($_GET['plugin']) && check_admin_referer('plugin_action_' . $_GET['plugin'])) {
        $plugin_slug = sanitize_text_field($_GET['plugin']);
        $action = sanitize_text_field($_GET['action']);
        $plugin_status = get_plugin_detailed_status($plugin_slug);
        
        switch ($action) {
            case 'install':
                $result = install_plugin_from_repo($plugin_slug);
                $messages[] = array('type' => $result['success'] ? 'success' : 'error', 'message' => $result['message']);
                break;
                
            case 'activate':
                if (!empty($plugin_status['file'])) {
                    $result = activate_plugin($plugin_status['file']);
                    if (is_wp_error($result)) {
                        $messages[] = array('type' => 'error', 'message' => '啟用失敗：' . $result->get_error_message());
                    } else {
                        $activated_plugins[] = $plugin_slug;
                        $messages[] = array('type' => 'success', 'message' => '外掛已啟用');
                    }
                }
                break;
                
            case 'deactivate':
                if (!empty($plugin_status['file'])) {
                    deactivate_plugins($plugin_status['file']);
                    $deactivated_plugins[] = $plugin_slug;
                    $messages[] = array('type' => 'success', 'message' => '外掛已停用');
                }
                break;
                
            case 'delete':
                if (!empty($plugin_status['file'])) {
                    $result = delete_plugin_from_site($plugin_status['file']);
                    if ($result['success']) {
                        $deleted_plugins[] = $plugin_slug;
                    }
                    $messages[] = array('type' => $result['success'] ? 'success' : 'error', 'message' => $result['message']);
                }
                break;
                
            case 'update':
                if (!empty($plugin_status['file'])) {
                    require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
                    $upgrader = new Plugin_Upgrader();
                    $result = $upgrader->upgrade($plugin_status['file']);
                    if (is_wp_error($result)) {
                        $messages[] = array('type' => 'error', 'message' => '更新失敗：' . $result->get_error_message());
                    } else {
                        $messages[] = array('type' => 'success', 'message' => '外掛已更新');
                    }
                }
                break;
        }
    }
    
    // 顯示提醒訊息
    if (!empty($activated_plugins)) {
        $count = count($activated_plugins);
        $message = $count > 1 ? "已成功啟用 {$count} 個外掛！為確保所有功能正常運作，建議您重新整理網站前台頁面或清除快取，新啟用的外掛功能才會完整顯示。" : "外掛已成功啟用！為確保功能正常運作，建議您重新整理網站前台頁面或清除快取，新功能才會完整顯示。";
        echo '<div class="notice notice-info is-dismissible" style="border-left:4px solid #2196F3;"><p><strong>🔄 重要提醒：</strong>' . esc_html($message) . '</p></div>';
    }
    
    if (!empty($deactivated_plugins)) {
        $count = count($deactivated_plugins);
        $message = $count > 1 ? "已成功停用 {$count} 個外掛！為確保網站正常運作，建議您重新整理網站前台頁面或清除快取，確認功能停用完整。" : "外掛已成功停用！為確保網站正常運作，建議您重新整理網站前台頁面或清除快取，確認功能停用完整。";
        echo '<div class="notice notice-warning is-dismissible" style="border-left:4px solid #ff6900;"><p><strong>⏸️ 停用提醒：</strong>' . esc_html($message) . '</p></div>';
    }
    
    if (!empty($deleted_plugins)) {
        $count = count($deleted_plugins);
        $message = $count > 1 ? "已成功刪除 {$count} 個外掛！為確保網站正常運作，建議您重新整理網站前台頁面或清除快取，確認功能移除完整。" : "外掛已成功刪除！為確保網站正常運作，建議您重新整理網站前台頁面或清除快取，確認功能移除完整。";
        echo '<div class="notice notice-error is-dismissible" style="border-left:4px solid #dc3545;"><p><strong>🗑️ 刪除提醒：</strong>' . esc_html($message) . '</p></div>';
    }
    
    foreach ($messages as $message) {
        echo '<div class="notice notice-' . $message['type'] . ' is-dismissible"><p>' . esc_html($message['message']) . '</p></div>';
    }
    
    // 取得外掛清單和篩選參數
    $plugins_list = get_popular_plugins_list();
    $installed_plugins = get_all_installed_plugins();
    $current_category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
    $order_by = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'name';
    $order_dir = isset($_GET['dir']) ? sanitize_text_field($_GET['dir']) : 'asc';
    
    // 合併常用外掛和已安裝外掛
    $all_plugins = array();
    
    // 先加入常用外掛清單
    foreach ($plugins_list as $slug => $plugin) {
        $plugin_info = get_plugin_detailed_status($slug);
        $all_plugins[$slug] = array_merge($plugin, array('slug' => $slug), $plugin_info);
    }
    
    // 再加入已安裝但不在常用清單中的外掛
    foreach ($installed_plugins as $slug => $plugin) {
        if (!isset($all_plugins[$slug])) {
            $all_plugins[$slug] = $plugin;
        }
    }
    
    // 取得所有分類
    $categories = array();
    foreach ($all_plugins as $plugin) {
        if (!in_array($plugin['category'], $categories)) {
            $categories[] = $plugin['category'];
        }
    }
    sort($categories);
    
    // 篩選外掛
    $filtered_plugins = array();
    foreach ($all_plugins as $slug => $plugin) {
        if (empty($current_category) || $plugin['category'] === $current_category) {
            $filtered_plugins[] = $plugin;
        }
    }
    
    // 排序外掛
    usort($filtered_plugins, function($a, $b) use ($order_by, $order_dir) {
        $result = strcasecmp($a['name'], $b['name']);
        return $order_dir === 'desc' ? -$result : $result;
    });
    ?>
    <div class="wrap">
        <h1>常用外掛管理</h1>
        <p>精選的常用 WordPress 外掛，您可以直接安裝、啟用或停用外掛。</p>
        
        <!-- 手動上傳外掛區域 -->
        <div style="max-width:500px;margin:20px 0;">
            <div style="background:#fff;border:1px solid #ccd0d4;border-radius:4px;padding:20px;">
                <h3 style="margin-top:0;">📁 上傳外掛檔案</h3>
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('upload_plugin_action', 'upload_plugin_nonce'); ?>
                    <p>選擇本地的外掛ZIP檔案進行安裝：</p>
                    <input type="file" name="plugin_file" accept=".zip" required style="width:100%;margin:10px 0;">
                    <input type="submit" name="upload_plugin" class="button button-primary" value="上傳並安裝" style="width:100%;">
                    <p style="color:#666;font-size:12px;margin:10px 0 0;">支援格式：ZIP 檔案，上傳後將在下方外掛列表中顯示</p>
                </form>
            </div>
        </div>
        
        <hr style="margin:30px 0;">
        
        <!-- 外掛管理 -->
        <h2>外掛清單</h2>
        
        <div class="tablenav top">
            <div class="alignleft actions">
                <select onchange="location.href='<?php echo admin_url('admin.php?page=wumetax-toolkit'); ?>&category=' + this.value;">
                    <option value="">所有分類</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo esc_attr($category); ?>" <?php selected($current_category, $category); ?>>
                            <?php echo esc_html($category); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="alignright">
                <span class="displaying-num">共 <?php echo count($filtered_plugins); ?> 個外掛</span>
            </div>
        </div>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th class="manage-column column-primary sortable <?php echo $order_dir; ?>">
                        <a href="<?php echo admin_url('admin.php?page=wumetax-toolkit&order=name&dir=' . ($order_dir === 'asc' ? 'desc' : 'asc') . ($current_category ? '&category=' . urlencode($current_category) : '')); ?>">
                            外掛名稱
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>
                    <th class="manage-column">狀態</th>
                    <th class="manage-column">操作</th>
                    <th class="manage-column">版本</th>
                    <th class="manage-column">分類</th>
                    <th class="manage-column">外掛描述</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($filtered_plugins as $plugin):
                    $status = $plugin['status'];
                    $needs_update = $plugin['needs_update'];
                    $version = $plugin['version'];
                    
                    // 判斷醒目標記
                    $row_class = '';
                    if ($status === 'active' || $status === 'installed') {
                        $row_class = $plugin['category'] === '手動安裝' ? 'manual-installed' : 'installed-plugin';
                    }
                    
                    $status_text = '';
                    $status_class = '';
                    
                    switch ($status) {
                        case 'active':
                            $status_text = $needs_update ? '已啟用 (有更新)' : '已啟用';
                            $status_class = $needs_update ? 'update-available' : 'active';
                            break;
                        case 'installed':
                            $status_text = '已安裝';
                            $status_class = 'installed';
                            break;
                        default:
                            $status_text = '尚未安裝';
                            $status_class = 'not-installed';
                    }
                ?>
                <tr class="<?php echo $row_class; ?>">
                    <td class="plugin-title column-primary">
                        <strong>
                            <?php if ($plugin['wp_url'] !== '#'): ?>
                                <a href="<?php echo esc_url($plugin['wp_url']); ?>" target="_blank">
                                    <?php echo esc_html($plugin['name']); ?>
                                </a>
                            <?php else: ?>
                                <?php echo esc_html($plugin['name']); ?>
                            <?php endif; ?>
                        </strong>
                    </td>
                    <td class="plugin-status">
                        <span class="status-<?php echo $status_class; ?>" style="padding:3px 8px;border-radius:3px;font-size:11px;font-weight:bold;<?php 
                            if ($status === 'active') {
                                echo $needs_update ? 'background:#ff6900;color:white;' : 'background:#46b450;color:white;';
                            } elseif ($status === 'installed') {
                                echo 'background:#ffb900;color:white;';
                            } else {
                                echo 'background:#ddd;color:#666;';
                            } ?>">
                            <?php echo esc_html($status_text); ?>
                        </span>
                    </td>
                    <td class="plugin-actions">
                        <?php
                        $nonce = wp_create_nonce('plugin_action_' . $plugin['slug']);
                        $base_url = admin_url('admin.php?page=wumetax-toolkit&plugin=' . $plugin['slug'] . '&_wpnonce=' . $nonce . '&action=');
                        
                        if ($status === 'not_installed'): ?>
                            <a href="<?php echo $base_url . 'install'; ?>" class="button button-primary">安裝</a>
                        <?php elseif ($status === 'installed'): ?>
                            <a href="<?php echo $base_url . 'activate'; ?>" class="button button-secondary">啟用</a>
                            <a href="<?php echo $base_url . 'delete'; ?>" class="button button-link-delete" onclick="return confirm('確定要刪除此外掛嗎？')">刪除</a>
                        <?php elseif ($status === 'active'): ?>
                            <a href="<?php echo $base_url . 'deactivate'; ?>" class="button">停用</a>
                            <?php if ($needs_update): ?>
                                <a href="<?php echo $base_url . 'update'; ?>" class="button button-primary">更新</a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td class="plugin-version">
                        <?php echo $version ? 'v' . esc_html($version) : '<span style="color:#999;">未安裝</span>'; ?>
                    </td>
                    <td class="plugin-category">
                        <span style="background:<?php echo $plugin['category'] === '手動安裝' ? '#e3f2fd' : '#f0f0f1'; ?>;padding:2px 6px;border-radius:3px;font-size:11px;<?php echo $plugin['category'] === '手動安裝' ? 'color:#1976d2;' : ''; ?>">
                            <?php echo esc_html($plugin['category']); ?>
                        </span>
                    </td>
                    <td class="plugin-description">
                        <?php echo esc_html($plugin['description']); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div style="margin-top:20px;padding:15px;background:#f1f1f1;border-left:4px solid #0073aa;">
            <h3>使用說明</h3>
            <ul>
                <li><strong>檔案上傳：</strong>上傳本地ZIP檔案直接安裝外掛，上傳後會在外掛列表中顯示</li>
                <li><strong>安裝：</strong>從 WordPress.org 安裝外掛到網站</li>
                <li><strong>啟用/停用：</strong>控制外掛執行狀態（操作後建議重新整理前台確保變更生效）</li>
                <li><strong>刪除：</strong>移除已停用的外掛檔案</li>
                <li><strong>更新：</strong>外掛有新版本時可更新</li>
                <li><strong>分類篩選：</strong>使用下拉選單快速篩選特定分類外掛</li>
                <li><strong>醒目標記：</strong>已安裝外掛顯示淺綠色背景，手動安裝外掛顯示淺藍色背景</li>
            </ul>
        </div>
    </div>

    <style>
    .wp-list-table .column-primary{width:22%;}
    .wp-list-table .plugin-status{width:12%;}
    .wp-list-table .plugin-actions{width:15%;}
    .wp-list-table .plugin-version{width:8%;}
    .wp-list-table .plugin-category{width:10%;}
    .wp-list-table .plugin-description{width:28%;}
    .plugin-actions .button{margin:1px;font-size:11px;}
    .button-link-delete{color:#d63638!important;text-decoration:none;}
    .button-link-delete:hover{color:#d63638!important;text-decoration:underline;}
    
    /* 醒目標記樣式 */
    .wp-list-table tr.installed-plugin{background-color:#f0fff4 !important;}
    .wp-list-table tr.manual-installed{background-color:#f3f8ff !important;}
    .wp-list-table tr.installed-plugin:hover,.wp-list-table tr.manual-installed:hover{opacity:0.8;}
    </style>
    <?php
}
