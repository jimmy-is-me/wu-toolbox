<?php
/**
 * 增強使用者列表模組
 * 在使用者列表中添加上次登入時間、註冊日期等資訊
 */

if (!defined('ABSPATH')) exit;

class WU_Enhanced_User_List {
    
    private $settings;
    
    public function __construct() {
        $this->settings = get_option('wu_enhanced_user_list_settings', $this->get_default_settings());
        // 確保所有設定項目都有預設值
        $this->settings = array_merge($this->get_default_settings(), $this->settings);
        
        add_action('admin_menu', array($this, 'add_admin_menu'), 15);
        
        // 用戶列表欄位
        add_filter('manage_users_columns', array($this, 'add_user_columns'));
        add_filter('manage_users_custom_column', array($this, 'show_user_column_content'), 10, 3);
        add_filter('manage_users_sortable_columns', array($this, 'make_columns_sortable'));
        
        // 排序功能
        add_action('pre_get_users', array($this, 'handle_column_sorting'));
        
        // 記錄用戶登入時間
        add_action('wp_login', array($this, 'record_user_last_login'), 10, 2);
        
        // 添加篩選器
        add_action('restrict_manage_users', array($this, 'add_user_filters'));
        add_filter('pre_get_users', array($this, 'filter_users_by_login_date'));
        
        // 用戶資料增強
        add_action('show_user_profile', array($this, 'show_additional_user_info'));
        add_action('edit_user_profile', array($this, 'show_additional_user_info'));
        
        // 統計資訊
        add_action('admin_notices', array($this, 'show_user_statistics'));
        
        // 隱藏用戶設定選項（新的管理方式）
        if ($this->get_setting('hide_profile_options', false)) {
            add_action('admin_head', array($this, 'hide_user_profile_options'));
        }
        
        // 用戶匯出功能
        if ($this->get_setting('enable_user_export', false)) {
            add_filter('user_row_actions', array($this, 'add_export_link'), 10, 2);
            add_filter('bulk_actions-users', array($this, 'add_bulk_export_action'));
            add_filter('handle_bulk_actions-users', array($this, 'handle_bulk_export'), 10, 3);
            add_action('admin_action_wu_export_user', array($this, 'export_single_user'));
        }
        
        // 自訂頭像功能
        if ($this->get_setting('enable_custom_avatar', false)) {
            add_action('show_user_profile', array($this, 'show_custom_avatar_field'));
            add_action('edit_user_profile', array($this, 'show_custom_avatar_field'));
            add_action('personal_options_update', array($this, 'save_custom_avatar'));
            add_action('edit_user_profile_update', array($this, 'save_custom_avatar'));
            add_filter('get_avatar', array($this, 'custom_avatar'), 10, 5);
            add_filter('get_avatar_url', array($this, 'custom_avatar_url'), 10, 3);
            add_action('admin_enqueue_scripts', array($this, 'enqueue_media_assets'));
        }
    }
    
    /**
     * 安全地取得設定值
     */
    private function get_setting($key, $default = false) {
        return isset($this->settings[$key]) ? $this->settings[$key] : $default;
    }
    
    /**
     * 隱藏用戶設定選項（統一管理）
     */
    public function hide_user_profile_options() {
        global $pagenow;
        if (is_admin() && ($pagenow == 'profile.php' || $pagenow == 'user-edit.php')) {
            echo '<style>
            /* 隱藏 Personal Options */
            .user-admin-color-wrap,
            .user-syntax-highlighting-wrap,
            .user-comment-shortcuts-wrap,
            .user-admin-bar-front-wrap,
            .user-locale-wrap,
            .user-language-wrap,
            tr.user-admin-color-wrap,
            tr.user-syntax-highlighting-wrap,
            tr.user-comment-shortcuts-wrap,
            tr.user-admin-bar-front-wrap,
            tr.user-locale-wrap,
            tr.user-language-wrap,
            /* 隱藏 About the user */
            .user-description-wrap,
            tr.user-description-wrap,
            /* 隱藏 Application Passwords */
            .application-passwords,
            .application-passwords-section,
            .user-application-passwords-wrap,
            tr.application-passwords,
            /* 隱藏 Elementor AI */
            .elementor-ai-wrap,
            .elementor-ai-settings,
            tr.elementor-ai-wrap,
            tr.elementor-ai-settings,
            /* 隱藏社交媒體設定 */
            .user-url-wrap,
            .user-facebook-wrap,
            .user-twitter-wrap,
            .user-linkedin-wrap,
            .user-mastodon-wrap,
            .user-tiktok-wrap,
            .user-odnoklassniki-wrap,
            .user-vkontakte-wrap,
            .user-vimeo-wrap,
            .user-youtube-wrap,
            .user-medium-wrap,
            .user-github-wrap,
            .user-wordpress-wrap,
            .user-pinterest-wrap,
            .user-instagram-wrap,
            .user-dribbble-wrap {
                display: none !important;
            }
            </style>';
            
            echo '<script>
            jQuery(document).ready(function($) {
                // 隱藏包含特定標題的區塊
                $("h2, h3, label").each(function() {
                    var text = $(this).text().trim().toLowerCase();
                    if (text.includes("personal options") || 
                        text.includes("about the user") || 
                        text.includes("application passwords") ||
                        text.includes("elementor") ||
                        text.includes("ai") ||
                        text.includes("個人選項") ||
                        text.includes("關於使用者") ||
                        text.includes("應用程式密碼") ||
                        text.includes("使用者自我介紹") ||
                        text.includes("biographical") ||
                        text.includes("biography")) {
                        $(this).hide();
                        $(this).nextUntil("h2, h3").hide();
                        $(this).closest("tr").hide();
                        $(this).parent().hide();
                    }
                });
                
                // 隱藏特定的表格行和區塊
                $(".form-table tr").each(function() {
                    var text = $(this).text().toLowerCase();
                    if (text.includes("elementor") || 
                        text.includes("ai") ||
                        text.includes("biographical") ||
                        text.includes("biography") ||
                        text.includes("使用者自我介紹")) {
                        $(this).hide();
                    }
                });
                
                // 隱藏 Elementor AI 相關所有內容
                $("*").filter(function() {
                    var text = $(this).text().toLowerCase();
                    return text.includes("elementor") && text.includes("ai");
                }).each(function() {
                    $(this).hide();
                    $(this).closest("tr").hide();
                    $(this).parent().hide();
                });
            });
            </script>';
        }
    }
    
    private function get_default_settings() {
        return array(
            'enabled' => false,
            'show_last_login' => true,
            'show_registration_date' => true,
            'show_user_id' => false,
            'show_role_since' => false,
            'date_format' => 'Y-m-d H:i:s',
            'show_filters' => true,
            'show_statistics' => true,
            'highlight_inactive_users' => 30, // 天數
            
            // 新的隱藏選項（統一管理）
            'hide_profile_options' => false,
            
            // 用戶匯出功能
            'enable_user_export' => false,
            'include_meta' => true,
            'include_roles' => true,
            'export_fields' => array(), // 動態獲取所有欄位
            'export_meta_fields' => array(), // 動態獲取所有中繼欄位
            
            // 自訂頭像功能
            'enable_custom_avatar' => false,
            'avatar_size_limit' => 2048, // KB
            'allowed_avatar_types' => array('jpg', 'jpeg', 'png', 'gif', 'webp')
        );
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'wumetax-toolkit',
            '強化使用者功能',
            '強化使用者功能',
            'manage_options',
            'wumetax-enhanced-user-list',
            array($this, 'admin_page')
        );
    }
    
    public function admin_page() {
        if (isset($_POST['submit'])) {
            $this->save_settings();
        }
        
        $this->settings = get_option('wu_enhanced_user_list_settings', $this->get_default_settings());
        $this->settings = array_merge($this->get_default_settings(), $this->settings);
        $user_stats = $this->get_user_statistics();
        ?>
        <div class="wrap">
            <h1>增強使用者列表設定</h1>
            
            <div class="notice notice-info">
                <h3>功能說明</h3>
                <p><strong>增強使用者列表功能</strong>為 WordPress 後台的用戶列表添加更多有用的資訊欄位，提升管理效率。</p>
                
                <h4>新增欄位：</h4>
                <ul>
                    <li><strong>上次登入</strong>：顯示用戶最後一次登入的時間</li>
                    <li><strong>註冊日期</strong>：以自訂格式顯示用戶註冊時間</li>
                    <li><strong>用戶 ID</strong>：顯示用戶的數據庫 ID</li>
                    <li><strong>角色指派時間</strong>：顯示用戶獲得當前角色的時間</li>
                </ul>
                
                <h4>增強功能：</h4>
                <ul>
                    <li>所有欄位支援排序功能</li>
                    <li>登入日期篩選器</li>
                    <li>用戶活動統計</li>
                    <li>非活躍用戶高亮顯示</li>
                    <li>統一隱藏用戶設定選項</li>
                </ul>
            </div>
            
            <form method="post" action="">
                <?php wp_nonce_field('wu_enhanced_user_list_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">啟用功能</th>
                        <td>
                            <label>
                                <input type="checkbox" name="enabled" value="1" <?php checked($this->get_setting('enabled', false)); ?>>
                                啟用增強使用者列表功能
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">顯示欄位</th>
                        <td>
                            <label style="display: block; margin: 5px 0;">
                                <input type="checkbox" name="show_last_login" value="1" <?php checked($this->get_setting('show_last_login', true)); ?>>
                                顯示上次登入時間
                            </label>
                            <label style="display: block; margin: 5px 0;">
                                <input type="checkbox" name="show_registration_date" value="1" <?php checked($this->get_setting('show_registration_date', true)); ?>>
                                顯示註冊日期
                            </label>
                            <label style="display: block; margin: 5px 0;">
                                <input type="checkbox" name="show_user_id" value="1" <?php checked($this->get_setting('show_user_id', false)); ?>>
                                顯示用戶 ID
                            </label>
                            <label style="display: block; margin: 5px 0;">
                                <input type="checkbox" name="show_role_since" value="1" <?php checked($this->get_setting('show_role_since', false)); ?>>
                                顯示角色指派時間
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">日期格式</th>
                        <td>
                            <select name="date_format">
                                <option value="Y-m-d H:i:s" <?php selected($this->get_setting('date_format', 'Y-m-d H:i:s'), 'Y-m-d H:i:s'); ?>>2024-01-15 14:30:00</option>
                                <option value="Y-m-d" <?php selected($this->get_setting('date_format', 'Y-m-d H:i:s'), 'Y-m-d'); ?>>2024-01-15</option>
                                <option value="d/m/Y" <?php selected($this->get_setting('date_format', 'Y-m-d H:i:s'), 'd/m/Y'); ?>>15/01/2024</option>
                                <option value="m/d/Y" <?php selected($this->get_setting('date_format', 'Y-m-d H:i:s'), 'm/d/Y'); ?>>01/15/2024</option>
                                <option value="F j, Y" <?php selected($this->get_setting('date_format', 'Y-m-d H:i:s'), 'F j, Y'); ?>>January 15, 2024</option>
                            </select>
                            <p class="description">選擇日期顯示格式</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">顯示篩選器</th>
                        <td>
                            <label>
                                <input type="checkbox" name="show_filters" value="1" <?php checked($this->get_setting('show_filters', true)); ?>>
                                在用戶列表頁面顯示篩選器
                            </label>
                            <p class="description">允許按登入日期等條件篩選用戶</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">顯示統計資訊</th>
                        <td>
                            <label>
                                <input type="checkbox" name="show_statistics" value="1" <?php checked($this->get_setting('show_statistics', true)); ?>>
                                在用戶列表頁面顯示統計資訊
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">非活躍用戶高亮</th>
                        <td>
                            <input type="number" name="highlight_inactive_users" value="<?php echo esc_attr($this->get_setting('highlight_inactive_users', 30)); ?>" min="0" max="365" class="small-text">
                            天未登入的用戶高亮顯示
                            <p class="description">設為 0 停用此功能</p>
                        </td>
                    </tr>
                </table>
                
                <h2>隱藏用戶設定選項</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">統一隱藏設定</th>
                        <td>
                            <label>
                                <input type="checkbox" name="hide_profile_options" value="1" <?php checked($this->get_setting('hide_profile_options', false)); ?>>
                                <strong>隱藏所有用戶設定選項</strong>
                            </label>
                            <p class="description">包含：Personal Options、About the user（含使用者自我介紹）、Application Passwords、Elementor AI、社交媒體設定、語言設定等</p>
                        </td>
                    </tr>
                </table>
                
                <h2>用戶匯出功能</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">啟用用戶匯出</th>
                        <td>
                            <label>
                                <input type="checkbox" name="enable_user_export" value="1" <?php checked($this->get_setting('enable_user_export', false)); ?>>
                                啟用用戶資料匯出功能
                            </label>
                            <p class="description">在用戶列表中新增匯出選項，支援單個和批量匯出，自動抓取所有用戶欄位</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">匯出選項</th>
                        <td>
                            <label style="display: block; margin: 5px 0;">
                                <input type="checkbox" name="include_meta" value="1" <?php checked($this->get_setting('include_meta', true)); ?>>
                                包含用戶中繼資料
                            </label>
                            <label style="display: block; margin: 5px 0;">
                                <input type="checkbox" name="include_roles" value="1" <?php checked($this->get_setting('include_roles', true)); ?>>
                                包含用戶角色資訊
                            </label>
                        </td>
                    </tr>
                </table>
                
                <h2>自訂頭像功能</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">啟用自訂頭像</th>
                        <td>
                            <label>
                                <input type="checkbox" name="enable_custom_avatar" value="1" <?php checked($this->get_setting('enable_custom_avatar', false)); ?>>
                                允許使用 WordPress 媒體庫中的任何圖像作為使用者頭像
                            </label>
                            <p class="description">用戶可以從媒體庫選擇或上傳新圖片作為頭像</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">檔案大小限制</th>
                        <td>
                            <input type="number" name="avatar_size_limit" value="<?php echo esc_attr($this->get_setting('avatar_size_limit', 2048)); ?>" min="512" max="10240" class="small-text">
                            KB
                            <p class="description">頭像檔案大小上限</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">允許的檔案類型</th>
                        <td>
                            <?php $allowed_types = $this->get_setting('allowed_avatar_types', array('jpg', 'jpeg', 'png', 'gif', 'webp')); ?>
                            <label style="display: block; margin: 5px 0;">
                                <input type="checkbox" name="allowed_avatar_types[]" value="jpg" <?php checked(in_array('jpg', $allowed_types)); ?>>
                                JPG
                            </label>
                            <label style="display: block; margin: 5px 0;">
                                <input type="checkbox" name="allowed_avatar_types[]" value="jpeg" <?php checked(in_array('jpeg', $allowed_types)); ?>>
                                JPEG
                            </label>
                            <label style="display: block; margin: 5px 0;">
                                <input type="checkbox" name="allowed_avatar_types[]" value="png" <?php checked(in_array('png', $allowed_types)); ?>>
                                PNG
                            </label>
                            <label style="display: block; margin: 5px 0;">
                                <input type="checkbox" name="allowed_avatar_types[]" value="gif" <?php checked(in_array('gif', $allowed_types)); ?>>
                                GIF
                            </label>
                            <label style="display: block; margin: 5px 0;">
                                <input type="checkbox" name="allowed_avatar_types[]" value="webp" <?php checked(in_array('webp', $allowed_types)); ?>>
                                WebP
                            </label>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('儲存設定'); ?>
            </form>
            
            <hr>
            
            <h2>用戶統計資訊</h2>
            <div style="display: flex; gap: 20px; margin: 20px 0; flex-wrap: wrap;">
                <div style="background: #f9f9f9; padding: 15px; border-radius: 5px; min-width: 180px;">
                    <strong>總用戶數：</strong><br>
                    <span style="font-size: 24px; color: #0073aa;"><?php echo number_format($user_stats['total_users']); ?></span>
                </div>
                <div style="background: #f9f9f9; padding: 15px; border-radius: 5px; min-width: 180px;">
                    <strong>今日活躍用戶：</strong><br>
                    <span style="font-size: 24px; color: #46b450;"><?php echo number_format($user_stats['active_today']); ?></span>
                </div>
                <div style="background: #f9f9f9; padding: 15px; border-radius: 5px; min-width: 180px;">
                    <strong>本週活躍用戶：</strong><br>
                    <span style="font-size: 24px; color: #0073aa;"><?php echo number_format($user_stats['active_week']); ?></span>
                </div>
                <div style="background: #f9f9f9; padding: 15px; border-radius: 5px; min-width: 180px;">
                    <strong>本月活躍用戶：</strong><br>
                    <span style="font-size: 24px; color: #0073aa;"><?php echo number_format($user_stats['active_month']); ?></span>
                </div>
                <div style="background: #f9f9f9; padding: 15px; border-radius: 5px; min-width: 180px;">
                    <strong>非活躍用戶：</strong><br>
                    <span style="font-size: 24px; color: #dc3232;"><?php echo number_format($user_stats['inactive_users']); ?></span>
                </div>
                <div style="background: #f9f9f9; padding: 15px; border-radius: 5px; min-width: 180px;">
                    <strong>從未登入：</strong><br>
                    <span style="font-size: 24px; color: #ff8c00;"><?php echo number_format($user_stats['never_logged_in']); ?></span>
                </div>
            </div>
            
            <h2>最近註冊的用戶</h2>
            <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
                <?php $recent_users = $this->get_recent_users(); ?>
                <?php if (!empty($recent_users)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>用戶名</th>
                            <th>顯示名稱</th>
                            <th>電子郵件</th>
                            <th>角色</th>
                            <th>註冊時間</th>
                            <th>上次登入</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_users as $user): ?>
                        <tr>
                            <td><strong><?php echo esc_html($user->user_login); ?></strong></td>
                            <td><?php echo esc_html($user->display_name); ?></td>
                            <td><?php echo esc_html($user->user_email); ?></td>
                            <td><?php echo esc_html(implode(', ', $user->roles)); ?></td>
                            <td><?php echo esc_html(date($this->get_setting('date_format', 'Y-m-d H:i:s'), strtotime($user->user_registered))); ?></td>
                            <td>
                                <?php 
                                $last_login = get_user_meta($user->ID, 'wu_last_login', true);
                                echo $last_login ? esc_html(date($this->get_setting('date_format', 'Y-m-d H:i:s'), $last_login)) : '從未登入';
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p>最近沒有新用戶註冊</p>
                <?php endif; ?>
            </div>
        </div>
        
        <style>
        .form-table th {
            width: 200px;
        }
        .wp-list-table {
            margin-top: 10px;
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
        if (!wp_verify_nonce($_POST['_wpnonce'], 'wu_enhanced_user_list_settings')) {
            wp_die('安全驗證失敗');
        }
        
        $settings = array_merge($this->get_default_settings(), array(
            'enabled' => isset($_POST['enabled']),
            'show_last_login' => isset($_POST['show_last_login']),
            'show_registration_date' => isset($_POST['show_registration_date']),
            'show_user_id' => isset($_POST['show_user_id']),
            'show_role_since' => isset($_POST['show_role_since']),
            'date_format' => sanitize_text_field($_POST['date_format']),
            'show_filters' => isset($_POST['show_filters']),
            'show_statistics' => isset($_POST['show_statistics']),
            'highlight_inactive_users' => intval($_POST['highlight_inactive_users']),
            
            // 隱藏選項
            'hide_profile_options' => isset($_POST['hide_profile_options']),
            
            // 用戶匯出功能
            'enable_user_export' => isset($_POST['enable_user_export']),
            'include_meta' => isset($_POST['include_meta']),
            'include_roles' => isset($_POST['include_roles']),
            
            // 自訂頭像功能
            'enable_custom_avatar' => isset($_POST['enable_custom_avatar']),
            'avatar_size_limit' => intval($_POST['avatar_size_limit']),
            'allowed_avatar_types' => isset($_POST['allowed_avatar_types']) ? $_POST['allowed_avatar_types'] : array('jpg', 'jpeg', 'png', 'webp')
        ));
        
        update_option('wu_enhanced_user_list_settings', $settings);
        $this->settings = $settings;
        
        echo '<div class="notice notice-success"><p>設定已儲存！</p></div>';
    }
    
    public function add_user_columns($columns) {
        if (!$this->get_setting('enabled', false)) {
            return $columns;
        }
        
        $new_columns = array();
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            // 在用戶名後添加用戶 ID
            if ($key === 'username' && $this->get_setting('show_user_id', false)) {
                $new_columns['wu_user_id'] = '用戶 ID';
            }
            
            // 在電子郵件後添加註冊日期
            if ($key === 'email' && $this->get_setting('show_registration_date', true)) {
                $new_columns['wu_registration_date'] = '註冊日期';
            }
        }
        
        // 添加其他欄位
        if ($this->get_setting('show_last_login', true)) {
            $new_columns['wu_last_login'] = '上次登入';
        }
        
        if ($this->get_setting('show_role_since', false)) {
            $new_columns['wu_role_since'] = '角色指派時間';
        }
        
        return $new_columns;
    }
    
    public function show_user_column_content($value, $column_name, $user_id) {
        if (!$this->get_setting('enabled', false)) {
            return $value;
        }
        
        $user = get_user_by('ID', $user_id);
        if (!$user) {
            return $value;
        }
        
        switch ($column_name) {
            case 'wu_user_id':
                return $user_id;
                
            case 'wu_registration_date':
                $registration_date = strtotime($user->user_registered);
                return date($this->get_setting('date_format', 'Y-m-d H:i:s'), $registration_date);
                
            case 'wu_last_login':
                $last_login = get_user_meta($user_id, 'wu_last_login', true);
                if ($last_login) {
                    $login_time = date($this->get_setting('date_format', 'Y-m-d H:i:s'), $last_login);
                    $days_ago = floor((time() - $last_login) / DAY_IN_SECONDS);
                    $highlight_days = $this->get_setting('highlight_inactive_users', 30);
                    if ($days_ago > $highlight_days && $highlight_days > 0) {
                        return '<span style="color: #dc3232;">' . $login_time . ' <small>(' . $days_ago . ' 天前)</small></span>';
                    } else {
                        return $login_time;
                    }
                } else {
                    return '<span style="color: #ff8c00;">從未登入</span>';
                }
                
            case 'wu_role_since':
                $role_since = get_user_meta($user_id, 'wu_role_assigned_date', true);
                if ($role_since) {
                    return date($this->get_setting('date_format', 'Y-m-d H:i:s'), $role_since);
                } else {
                    return date($this->get_setting('date_format', 'Y-m-d H:i:s'), strtotime($user->user_registered));
                }
        }
        
        return $value;
    }
    
    public function make_columns_sortable($columns) {
        if (!$this->get_setting('enabled', false)) {
            return $columns;
        }
        
        $sortable_columns = array(
            'wu_user_id' => 'wu_user_id',
            'wu_registration_date' => 'wu_registration_date',
            'wu_last_login' => 'wu_last_login',
            'wu_role_since' => 'wu_role_since'
        );
        
        return array_merge($columns, $sortable_columns);
    }
    
    public function handle_column_sorting($user_query) {
        if (!is_admin() || !$this->get_setting('enabled', false)) {
            return;
        }
        
        $screen = get_current_screen();
        if ($screen->base !== 'users') {
            return;
        }
        
        $orderby = $user_query->get('orderby');
        $order = $user_query->get('order');
        
        if (empty($order)) {
            $order = 'ASC';
        }
        
        switch ($orderby) {
            case 'wu_user_id':
                $user_query->set('orderby', 'ID');
                $user_query->set('order', $order);
                break;
                
            case 'wu_registration_date':
                $user_query->set('orderby', 'user_registered');
                $user_query->set('order', $order);
                break;
                
            case 'wu_last_login':
                $user_query->set('meta_key', 'wu_last_login');
                $user_query->set('orderby', 'meta_value_num');
                $user_query->set('order', $order);
                break;
                
            case 'wu_role_since':
                $user_query->set('meta_key', 'wu_role_assigned_date');
                $user_query->set('orderby', 'meta_value_num');
                $user_query->set('order', $order);
                break;
        }
    }
    
    public function record_user_last_login($user_login, $user) {
        if (!$this->get_setting('enabled', false)) {
            return;
        }
        
        update_user_meta($user->ID, 'wu_last_login', time());
    }
    
    public function add_user_filters() {
        if (!$this->get_setting('enabled', false) || !$this->get_setting('show_filters', true)) {
            return;
        }
        
        $screen = get_current_screen();
        if ($screen->base !== 'users') {
            return;
        }
        
        ?>
        <select name="wu_login_filter">
            <option value="">所有登入狀態</option>
            <option value="never_logged_in" <?php selected($_GET['wu_login_filter'] ?? '', 'never_logged_in'); ?>>從未登入</option>
            <option value="logged_in_today" <?php selected($_GET['wu_login_filter'] ?? '', 'logged_in_today'); ?>>今日登入</option>
            <option value="logged_in_week" <?php selected($_GET['wu_login_filter'] ?? '', 'logged_in_week'); ?>>本週登入</option>
            <option value="logged_in_month" <?php selected($_GET['wu_login_filter'] ?? '', 'logged_in_month'); ?>>本月登入</option>
            <option value="inactive_30" <?php selected($_GET['wu_login_filter'] ?? '', 'inactive_30'); ?>>30天未登入</option>
            <option value="inactive_90" <?php selected($_GET['wu_login_filter'] ?? '', 'inactive_90'); ?>>90天未登入</option>
        </select>
        
        <select name="wu_registration_filter">
            <option value="">所有註冊時間</option>
            <option value="registered_today" <?php selected($_GET['wu_registration_filter'] ?? '', 'registered_today'); ?>>今日註冊</option>
            <option value="registered_week" <?php selected($_GET['wu_registration_filter'] ?? '', 'registered_week'); ?>>本週註冊</option>
            <option value="registered_month" <?php selected($_GET['wu_registration_filter'] ?? '', 'registered_month'); ?>>本月註冊</option>
            <option value="registered_year" <?php selected($_GET['wu_registration_filter'] ?? '', 'registered_year'); ?>>本年註冊</option>
        </select>
        <?php
    }
    
    public function filter_users_by_login_date($user_query) {
        if (!is_admin() || !$this->get_setting('enabled', false)) {
            return;
        }
        
        $screen = get_current_screen();
        if ($screen->base !== 'users') {
            return;
        }
        
        $login_filter = $_GET['wu_login_filter'] ?? '';
        $registration_filter = $_GET['wu_registration_filter'] ?? '';
        
        // 登入狀態篩選
        if ($login_filter) {
            switch ($login_filter) {
                case 'never_logged_in':
                    $user_query->set('meta_query', array(
                        array(
                            'key' => 'wu_last_login',
                            'compare' => 'NOT EXISTS'
                        )
                    ));
                    break;
                    
                case 'logged_in_today':
                    $user_query->set('meta_query', array(
                        array(
                            'key' => 'wu_last_login',
                            'value' => strtotime('today'),
                            'compare' => '>='
                        )
                    ));
                    break;
                    
                case 'logged_in_week':
                    $user_query->set('meta_query', array(
                        array(
                            'key' => 'wu_last_login',
                            'value' => strtotime('-1 week'),
                            'compare' => '>='
                        )
                    ));
                    break;
                    
                case 'logged_in_month':
                    $user_query->set('meta_query', array(
                        array(
                            'key' => 'wu_last_login',
                            'value' => strtotime('-1 month'),
                            'compare' => '>='
                        )
                    ));
                    break;
                    
                case 'inactive_30':
                    $user_query->set('meta_query', array(
                        array(
                            'key' => 'wu_last_login',
                            'value' => strtotime('-30 days'),
                            'compare' => '<'
                        )
                    ));
                    break;
                    
                case 'inactive_90':
                    $user_query->set('meta_query', array(
                        array(
                            'key' => 'wu_last_login',
                            'value' => strtotime('-90 days'),
                            'compare' => '<'
                        )
                    ));
                    break;
            }
        }
        
        // 註冊時間篩選
        if ($registration_filter) {
            switch ($registration_filter) {
                case 'registered_today':
                    $user_query->set('date_query', array(
                        array(
                            'after' => 'today',
                            'column' => 'user_registered'
                        )
                    ));
                    break;
                    
                case 'registered_week':
                    $user_query->set('date_query', array(
                        array(
                            'after' => '1 week ago',
                            'column' => 'user_registered'
                        )
                    ));
                    break;
                    
                case 'registered_month':
                    $user_query->set('date_query', array(
                        array(
                            'after' => '1 month ago',
                            'column' => 'user_registered'
                        )
                    ));
                    break;
                    
                case 'registered_year':
                    $user_query->set('date_query', array(
                        array(
                            'after' => '1 year ago',
                            'column' => 'user_registered'
                        )
                    ));
                    break;
            }
        }
    }
    
    public function show_additional_user_info($user) {
        if (!$this->get_setting('enabled', false)) {
            return;
        }
        
        $last_login = get_user_meta($user->ID, 'wu_last_login', true);
        $role_since = get_user_meta($user->ID, 'wu_role_assigned_date', true);
        ?>
        <h3>用戶活動資訊</h3>
        <table class="form-table">
            <tr>
                <th><label>用戶 ID</label></th>
                <td><?php echo $user->ID; ?></td>
            </tr>
            <tr>
                <th><label>註冊日期</label></th>
                <td><?php echo date($this->get_setting('date_format', 'Y-m-d H:i:s'), strtotime($user->user_registered)); ?></td>
            </tr>
            <tr>
                <th><label>上次登入</label></th>
                <td>
                    <?php if ($last_login): ?>
                        <?php echo date($this->get_setting('date_format', 'Y-m-d H:i:s'), $last_login); ?>
                        <small>(<?php echo floor((time() - $last_login) / DAY_IN_SECONDS); ?> 天前)</small>
                    <?php else: ?>
                        從未登入
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th><label>角色指派時間</label></th>
                <td>
                    <?php if ($role_since): ?>
                        <?php echo date($this->get_setting('date_format', 'Y-m-d H:i:s'), $role_since); ?>
                    <?php else: ?>
                        未記錄（可能為註冊時指派）
                    <?php endif; ?>
                </td>
            </tr>
        </table>
        <?php
    }
    
    public function show_user_statistics() {
        if (!$this->get_setting('enabled', false) || !$this->get_setting('show_statistics', true)) {
            return;
        }
        
        $screen = get_current_screen();
        if ($screen->base !== 'users') {
            return;
        }
        
        $stats = $this->get_user_statistics();
        ?>
        <div class="notice notice-info">
            <p>
                <strong>用戶統計：</strong>
                總數：<?php echo number_format($stats['total_users']); ?> |
                今日活躍：<?php echo number_format($stats['active_today']); ?> |
                本週活躍：<?php echo number_format($stats['active_week']); ?> |
                本月活躍：<?php echo number_format($stats['active_month']); ?> |
                非活躍（30天+）：<?php echo number_format($stats['inactive_users']); ?> |
                從未登入：<?php echo number_format($stats['never_logged_in']); ?>
            </p>
        </div>
        
        <?php if ($this->get_setting('highlight_inactive_users', 30) > 0): ?>
        <style>
        .users-php .wp-list-table tbody tr {
            background: #fff;
        }
        .users-php .wp-list-table tbody tr.inactive-user {
            background: #ffeaa7;
        }
        </style>
        <script>
        jQuery(document).ready(function($) {
            // 高亮非活躍用戶
            $('.wp-list-table tbody tr').each(function() {
                var lastLoginCell = $(this).find('td.wu_last_login');
                if (lastLoginCell.length && lastLoginCell.find('span[style*="color: #dc3232"]').length) {
                    $(this).addClass('inactive-user');
                }
            });
        });
        </script>
        <?php endif; ?>
        <?php
    }
    
    private function get_user_statistics() {
        $stats = wp_cache_get('wu_user_statistics');
        if ($stats !== false) {
            return $stats;
        }
        
        $stats = array(
            'total_users' => 0,
            'active_today' => 0,
            'active_week' => 0,
            'active_month' => 0,
            'inactive_users' => 0,
            'never_logged_in' => 0
        );
        
        // 總用戶數
        $user_count = count_users();
        $stats['total_users'] = $user_count['total_users'];
        
        // 今日活躍用戶
        $stats['active_today'] = $this->count_users_by_login_date(strtotime('today'));
        
        // 本週活躍用戶
        $stats['active_week'] = $this->count_users_by_login_date(strtotime('-1 week'));
        
        // 本月活躍用戶
        $stats['active_month'] = $this->count_users_by_login_date(strtotime('-1 month'));
        
        // 非活躍用戶（30天以上未登入）
        $stats['inactive_users'] = $this->count_inactive_users(30);
        
        // 從未登入的用戶
        $stats['never_logged_in'] = $this->count_never_logged_in_users();
        
        wp_cache_set('wu_user_statistics', $stats, '', 300); // 緩存5分鐘
        
        return $stats;
    }
    
    private function count_users_by_login_date($since_timestamp) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta} 
             WHERE meta_key = 'wu_last_login' AND meta_value >= %d",
            $since_timestamp
        ));
        
        return intval($count);
    }
    
    private function count_inactive_users($days) {
        global $wpdb;
        
        $cutoff_timestamp = strtotime("-{$days} days");
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta} 
             WHERE meta_key = 'wu_last_login' AND meta_value < %d",
            $cutoff_timestamp
        ));
        
        return intval($count);
    }
    
    private function count_never_logged_in_users() {
        global $wpdb;
        
        // 所有用戶數 - 有登入記錄的用戶數
        $total_users = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}");
        $logged_in_users = $wpdb->get_var(
            "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta} 
             WHERE meta_key = 'wu_last_login'"
        );
        
        return intval($total_users) - intval($logged_in_users);
    }
    
    private function get_recent_users($limit = 10) {
        $users = get_users(array(
            'orderby' => 'user_registered',
            'order' => 'DESC',
            'number' => $limit
        ));
        
        return $users;
    }
    
    // === 用戶匯出功能 ===
    
    public function add_export_link($actions, $user_object) {
        if (current_user_can('manage_options')) {
            $nonce = wp_create_nonce('wu_export_user_' . $user_object->ID);
            $export_url = admin_url('admin.php?action=wu_export_user&user_id=' . $user_object->ID . '&_wpnonce=' . $nonce);
            $actions['wu_export'] = '<a href="' . $export_url . '">下載 CSV</a>';
        }
        return $actions;
    }
    
    public function add_bulk_export_action($bulk_actions) {
        $bulk_actions['wu_export'] = '下載 CSV';
        return $bulk_actions;
    }
    
    public function handle_bulk_export($redirect_to, $doaction, $user_ids) {
        if ($doaction !== 'wu_export') {
            return $redirect_to;
        }
        
        if (!current_user_can('manage_options')) {
            return $redirect_to;
        }
        
        $this->export_users($user_ids);
        exit;
    }
    
    public function export_single_user() {
        if (!isset($_GET['user_id']) || !isset($_GET['_wpnonce'])) {
            wp_die('缺少必要參數');
        }
        
        $user_id = intval($_GET['user_id']);
        if (!wp_verify_nonce($_GET['_wpnonce'], 'wu_export_user_' . $user_id)) {
            wp_die('安全驗證失敗');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('權限不足');
        }
        
        $this->export_users(array($user_id));
        exit;
    }
    
    /**
     * 自動獲取所有用戶欄位
     */
    private function get_all_user_fields() {
        global $wpdb;
        
        // 獲取用戶表的所有欄位
        $user_fields = array();
        $results = $wpdb->get_results("DESCRIBE {$wpdb->users}", ARRAY_A);
        foreach ($results as $field) {
            $user_fields[] = $field['Field'];
        }
        
        return $user_fields;
    }
    
    /**
     * 自動獲取所有用戶中繼欄位
     */
    private function get_all_user_meta_fields() {
        global $wpdb;
        
        // 獲取前 20 個最常用的中繼欄位
        $meta_keys = $wpdb->get_col("
            SELECT DISTINCT meta_key 
            FROM {$wpdb->usermeta} 
            WHERE meta_key NOT LIKE '\_%' 
            ORDER BY meta_key 
            LIMIT 20
        ");
        
        return $meta_keys;
    }
    
    private function export_users($user_ids) {
        // 設定 CSV 標頭
        $filename = 'users_export_' . date('Y-m-d_H-i-s') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // 開始輸出
        $output = fopen('php://output', 'w');
        
        // 添加 BOM 以確保中文正確顯示
        fputs($output, "\xEF\xBB\xBF");
        
        // 建立標題行
        $headers = array();
        
        // 基本用戶欄位
        $user_fields = $this->get_all_user_fields();
        foreach ($user_fields as $field) {
            $headers[] = ucfirst(str_replace('_', ' ', $field));
        }
        
        // 角色資訊
        if ($this->get_setting('include_roles', true)) {
            $headers[] = 'Roles';
        }
        
        // 中繼資料
        if ($this->get_setting('include_meta', true)) {
            $meta_fields = $this->get_all_user_meta_fields();
            foreach ($meta_fields as $meta_field) {
                $headers[] = ucfirst(str_replace('_', ' ', $meta_field));
            }
        }
        
        fputcsv($output, $headers);
        
        // 匯出用戶資料
        foreach ($user_ids as $user_id) {
            $user = get_userdata($user_id);
            if (!$user) continue;
            
            $row = array();
            
            // 基本欄位
            foreach ($user_fields as $field) {
                $value = isset($user->$field) ? $user->$field : '';
                if ($field === 'user_registered') {
                    $value = date($this->get_setting('date_format', 'Y-m-d H:i:s'), strtotime($value));
                }
                $row[] = $value;
            }
            
            // 角色資訊
            if ($this->get_setting('include_roles', true)) {
                $roles = implode(', ', $user->roles);
                $row[] = $roles;
            }
            
            // 中繼資料
            if ($this->get_setting('include_meta', true)) {
                $meta_fields = $this->get_all_user_meta_fields();
                foreach ($meta_fields as $meta_field) {
                    $meta_value = get_user_meta($user_id, $meta_field, true);
                    $row[] = $meta_value;
                }
            }
            
            fputcsv($output, $row);
        }
        
        fclose($output);
    }
    
    // === 自訂頭像功能 ===
    
    public function show_custom_avatar_field($user) {
        $custom_avatar = get_user_meta($user->ID, 'wu_custom_avatar', true);
        ?>
        <h3>自訂頭像</h3>
        <table class="form-table">
            <tr>
                <th><label for="wu_custom_avatar">選擇頭像</label></th>
                <td>
                    <div id="wu-avatar-preview" style="margin-bottom: 10px;">
                        <?php if ($custom_avatar): ?>
                            <img src="<?php echo esc_url($custom_avatar); ?>" alt="自訂頭像" style="width: 96px; height: 96px; border-radius: 48px; object-fit: cover;">
                        <?php else: ?>
                            <img src="<?php echo get_avatar_url($user->ID, 96); ?>" alt="預設頭像" style="width: 96px; height: 96px; border-radius: 48px; object-fit: cover;">
                        <?php endif; ?>
                    </div>
                    
                    <input type="hidden" id="wu_custom_avatar" name="wu_custom_avatar" value="<?php echo esc_attr($custom_avatar); ?>">
                    
                    <button type="button" class="button" id="wu-select-avatar">選擇圖片</button>
                    <?php if ($custom_avatar): ?>
                    <button type="button" class="button" id="wu-remove-avatar">移除自訂頭像</button>
                    <?php endif; ?>
                    
                    <p class="description">
                        點擊「選擇圖片」從媒體庫選擇頭像，或上傳新圖片。<br>
                        建議尺寸：至少 96x96 像素，檔案大小不超過 <?php echo $this->get_setting('avatar_size_limit', 2048); ?> KB<br>
                        支援格式：<?php echo implode(', ', array_map('strtoupper', $this->get_setting('allowed_avatar_types', array('jpg', 'jpeg', 'png', 'gif', 'webp')))); ?>
                    </p>
                </td>
            </tr>
        </table>
        
        <script>
        jQuery(document).ready(function($) {
            var frame;
            
            $('#wu-select-avatar').click(function(e) {
                e.preventDefault();
                
                if (frame) {
                    frame.open();
                    return;
                }
                
                frame = wp.media({
                    title: '選擇頭像',
                    button: {
                        text: '使用此圖片'
                    },
                    library: {
                        type: ['image']
                    },
                    multiple: false
                });
                
                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    
                    // 檢查檔案大小
                    if (attachment.filesizeInBytes > <?php echo $this->get_setting('avatar_size_limit', 2048) * 1024; ?>) {
                        alert('檔案太大！請選擇小於 <?php echo $this->get_setting('avatar_size_limit', 2048); ?> KB 的圖片。');
                        return;
                    }
                    
                    // 檢查檔案類型
                    var allowedTypes = <?php echo json_encode($this->get_setting('allowed_avatar_types', array('jpg', 'jpeg', 'png', 'gif', 'webp'))); ?>;
                    var fileExtension = attachment.filename.split('.').pop().toLowerCase();
                    if (allowedTypes.indexOf(fileExtension) === -1) {
                        alert('不支援的檔案格式！請選擇 ' + allowedTypes.join(', ').toUpperCase() + ' 格式的圖片。');
                        return;
                    }
                    
                    $('#wu_custom_avatar').val(attachment.url);
                    $('#wu-avatar-preview img').attr('src', attachment.url);
                    
                    if ($('#wu-remove-avatar').length === 0) {
                        $('#wu-select-avatar').after('<button type="button" class="button" id="wu-remove-avatar" style="margin-left: 10px;">移除自訂頭像</button>');
                    }
                });
                
                frame.open();
            });
            
            $(document).on('click', '#wu-remove-avatar', function(e) {
                e.preventDefault();
                
                if (confirm('確定要移除自訂頭像嗎？')) {
                    $('#wu_custom_avatar').val('');
                    $('#wu-avatar-preview img').attr('src', '<?php echo get_avatar_url($user->ID, 96); ?>');
                    $('#wu-remove-avatar').remove();
                }
            });
        });
        </script>
        <?php
    }
    
    public function save_custom_avatar($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }
        
        $custom_avatar = isset($_POST['wu_custom_avatar']) ? esc_url($_POST['wu_custom_avatar']) : '';
        
        if ($custom_avatar) {
            update_user_meta($user_id, 'wu_custom_avatar', $custom_avatar);
        } else {
            delete_user_meta($user_id, 'wu_custom_avatar');
        }
    }

    public function enqueue_media_assets($hook) {
        // 僅在個人資料與用戶編輯頁面載入
        if ($hook !== 'profile.php' && $hook !== 'user-edit.php') return;
        wp_enqueue_media();
    }
    
    public function custom_avatar($avatar, $id_or_email, $size, $default, $alt) {
        $user = false;
        
        if (is_numeric($id_or_email)) {
            $user = get_user_by('id', intval($id_or_email));
        } elseif (is_object($id_or_email)) {
            if (isset($id_or_email->user_id)) {
                $user = get_user_by('id', intval($id_or_email->user_id));
            }
        } else {
            $user = get_user_by('email', $id_or_email);
        }
        
        if ($user && is_object($user)) {
            $custom_avatar = get_user_meta($user->ID, 'wu_custom_avatar', true);
            if ($custom_avatar) {
                $avatar = sprintf(
                    '<img alt="%s" src="%s" class="avatar avatar-%d photo" height="%d" width="%d" />',
                    esc_attr($alt),
                    esc_url($custom_avatar),
                    esc_attr($size),
                    esc_attr($size),
                    esc_attr($size)
                );
            }
        }
        
        return $avatar;
    }
    
    public function custom_avatar_url($url, $id_or_email, $args) {
        $user = false;
        
        if (is_numeric($id_or_email)) {
            $user = get_user_by('id', intval($id_or_email));
        } elseif (is_object($id_or_email)) {
            if (isset($id_or_email->user_id)) {
                $user = get_user_by('id', intval($id_or_email->user_id));
            }
        } else {
            $user = get_user_by('email', $id_or_email);
        }
        
        if ($user && is_object($user)) {
            $custom_avatar = get_user_meta($user->ID, 'wu_custom_avatar', true);
            if ($custom_avatar) {
                return $custom_avatar;
            }
        }
        
        return $url;
    }
}

// 初始化模組
$wu_enhanced_user_list = new WU_Enhanced_User_List();
?>
