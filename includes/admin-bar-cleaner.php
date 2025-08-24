<?php
/**
 * 後台設定模組
 * 功能：後台設定、WordPress 登入頁面美化(採用半透明風格、白底美化登入頁面)
 */

if (!defined('ABSPATH')) exit;

class WU_Admin_Bar_Cleaner {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        
        // AJAX 處理
        add_action('wp_ajax_wu_admin_bar_save', array($this, 'ajax_save_settings'));
        
        // 載入所有啟用的功能
        $this->load_features();
    }
    
    /**
     * 添加管理頁面
     */
    public function add_admin_menu() {
        add_submenu_page(
            'wumetax-toolkit',
            '後台設定',
            '後台設定',
            'manage_options',
            'wumetax-admin-bar-cleaner',
            array($this, 'admin_page')
        );
    }
    
    /**
     * 初始化設定
     */
    public function admin_init() {
        // 註冊所有設定
        $settings = array(
            'wu_remove_wp_logo',
            'wu_remove_new_content', // 新增
            'wu_hide_login_logo',
            'wu_disable_login_language_switcher',
            'wu_enable_login_beautify',
            'wu_disable_dashboard_widgets',
            'wu_custom_admin_footer_text',
            'wu_remove_admin_footer_text',
            'wu_hide_tools_menu',
            'wu_hide_wordpress_address',
            'wu_hide_site_address',
            'wu_hide_writing_settings',
            'wu_hide_privacy_settings',
            'wu_custom_frontend_footer_text',
            'wu_hide_wumetax_toolkit',
            'wu_hide_admin_updates' // 新增
        );
        
        foreach ($settings as $setting) {
            register_setting('wu_admin_bar_settings', $setting);
        }
        
        // 管理列設定區域
        add_settings_section(
            'wu_admin_bar_section',
            '後台設定',
            array($this, 'settings_section_callback'),
            'wu_admin_bar_settings'
        );
        
        // 登入頁面設定區域
        add_settings_section(
            'wu_login_section',
            'WordPress 登入頁面美化(採用半透明風格、白底美化登入頁面)',
            array($this, 'login_section_callback'),
            'wu_admin_bar_settings'
        );
        
        // 儀表板設定區域
        add_settings_section(
            'wu_dashboard_section',
            '儀表板設定',
            array($this, 'dashboard_section_callback'),
            'wu_admin_bar_settings'
        );
        
        // 後台隱藏設定區域
        add_settings_section(
            'wu_backend_section',
            '後台隱藏設定',
            array($this, 'backend_section_callback'),
            'wu_admin_bar_settings'
        );
        
        // 前台設定區域
        add_settings_section(
            'wu_frontend_section',
            '前台設定',
            array($this, 'frontend_section_callback'),
            'wu_admin_bar_settings'
        );
        
        // 添加管理列設定欄位
        add_settings_field(
            'wu_remove_wp_logo',
            '移除 WordPress 標誌',
            array($this, 'remove_wp_logo_callback'),
            'wu_admin_bar_settings',
            'wu_admin_bar_section'
        );
        
        // 新增：移除新增項目
        add_settings_field(
            'wu_remove_new_content',
            '移除管理列新增項目',
            array($this, 'remove_new_content_callback'),
            'wu_admin_bar_settings',
            'wu_admin_bar_section'
        );
        
        // 添加登入頁面設定欄位
        add_settings_field(
            'wu_hide_login_logo',
            '隱藏登入頁面 WordPress 標誌',
            array($this, 'hide_login_logo_callback'),
            'wu_admin_bar_settings',
            'wu_login_section'
        );
        
        add_settings_field(
            'wu_disable_login_language_switcher',
            '停用登入語言切換器',
            array($this, 'disable_login_language_switcher_callback'),
            'wu_admin_bar_settings',
            'wu_login_section'
        );
        
        add_settings_field(
            'wu_enable_login_beautify',
            'WordPress 登入頁面美化',
            array($this, 'enable_login_beautify_callback'),
            'wu_admin_bar_settings',
            'wu_login_section'
        );
        
        // 添加儀表板設定欄位
        add_settings_field(
            'wu_disable_dashboard_widgets',
            '停用 WordPress 儀表板小工具',
            array($this, 'disable_dashboard_widgets_callback'),
            'wu_admin_bar_settings',
            'wu_dashboard_section'
        );
        
        add_settings_field(
            'wu_remove_admin_footer_text',
            '移除管理頁尾文本',
            array($this, 'remove_admin_footer_text_callback'),
            'wu_admin_bar_settings',
            'wu_dashboard_section'
        );
        
        add_settings_field(
            'wu_custom_admin_footer_text',
            '自訂管理頁尾文本',
            array($this, 'custom_admin_footer_text_callback'),
            'wu_admin_bar_settings',
            'wu_dashboard_section'
        );
        
        // 添加後台隱藏設定欄位
        add_settings_field(
            'wu_hide_tools_menu',
            '隱藏後台 Tools 選單',
            array($this, 'hide_tools_menu_callback'),
            'wu_admin_bar_settings',
            'wu_backend_section'
        );
        
        add_settings_field(
            'wu_hide_wordpress_address',
            '隱藏 WordPress Address (URL)',
            array($this, 'hide_wordpress_address_callback'),
            'wu_admin_bar_settings',
            'wu_backend_section'
        );
        
        add_settings_field(
            'wu_hide_site_address',
            '隱藏 Site Address (URL)',
            array($this, 'hide_site_address_callback'),
            'wu_admin_bar_settings',
            'wu_backend_section'
        );
        
        add_settings_field(
            'wu_hide_writing_settings',
            '隱藏 Writing Settings',
            array($this, 'hide_writing_settings_callback'),
            'wu_admin_bar_settings',
            'wu_backend_section'
        );
        
        add_settings_field(
            'wu_hide_privacy_settings',
            '隱藏 Privacy 設定',
            array($this, 'hide_privacy_settings_callback'),
            'wu_admin_bar_settings',
            'wu_backend_section'
        );
        
        // 新增：隱藏後台更新
        add_settings_field(
            'wu_hide_admin_updates',
            '隱藏後台更新通知',
            array($this, 'hide_admin_updates_callback'),
            'wu_admin_bar_settings',
            'wu_backend_section'
        );
        
        // 添加前台設定欄位
        add_settings_field(
            'wu_custom_frontend_footer_text',
            '自訂前台頁尾文本',
            array($this, 'custom_frontend_footer_text_callback'),
            'wu_admin_bar_settings',
            'wu_frontend_section'
        );
        
        add_settings_field(
            'wu_hide_wumetax_toolkit',
            '向其他管理員隱藏 WumetaxToolkit',
            array($this, 'hide_wumetax_toolkit_callback'),
            'wu_admin_bar_settings',
            'wu_backend_section'
        );
    }
    
    /**
     * 設定區域說明
     */
    public function settings_section_callback() {
        echo '<p>後台設定功能可以幫助您移除不必要的 WordPress 預設項目，讓管理界面更加簡潔。</p>';
    }
    
    /**
     * 登入頁面設定區域說明
     */
    public function login_section_callback() {
        echo '<p>自訂登入頁面的外觀和功能，採用半透明風格、白底美化登入頁面，提供更專業的使用者體驗。</p>';
    }
    
    /**
     * 儀表板設定區域說明
     */
    public function dashboard_section_callback() {
        echo '<p>管理 WordPress 儀表板的小工具和介面元素，簡化管理體驗。</p>';
    }
    
    /**
     * 後台隱藏設定區域說明
     */
    public function backend_section_callback() {
        echo '<p>選擇性隱藏後台特定設定選項和選單，讓後台介面更加簡潔專業。</p>';
    }
    
    /**
     * 前台設定區域說明
     */
    public function frontend_section_callback() {
        echo '<p>自訂前台網站的顯示內容和外觀設定。</p>';
    }
    
    /**
     * 移除 WordPress 標誌選項回調
     */
    public function remove_wp_logo_callback() {
        $value = get_option('wu_remove_wp_logo', false);
        echo '<input type="checkbox" id="wu_remove_wp_logo" name="wu_remove_wp_logo" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_remove_wp_logo">移除管理列中的 WordPress 標誌（W 圖示）</label>';
        echo '<p class="description">勾選此選項將從管理列中移除 WordPress 標誌及其下拉選單，讓界面更加簡潔。</p>';
    }
    
    /**
     * 新增：移除新增項目選項回調
     */
    public function remove_new_content_callback() {
        $value = get_option('wu_remove_new_content', false);
        echo '<input type="checkbox" id="wu_remove_new_content" name="wu_remove_new_content" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_remove_new_content">移除管理列中的新增項目（+ 新增）</label>';
        echo '<p class="description">勾選此選項將從管理列中移除「+ 新增」項目及其下拉選單，包括新增文章、頁面、媒體等選項。</p>';
    }
    
    /**
     * 隱藏登入頁面標誌選項回調
     */
    public function hide_login_logo_callback() {
        $value = get_option('wu_hide_login_logo', false);
        echo '<input type="checkbox" id="wu_hide_login_logo" name="wu_hide_login_logo" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_hide_login_logo">隱藏登入頁面的 WordPress 標誌</label>';
        echo '<p class="description">從登入頁面隱藏標準 WordPress 徽標。</p>';
    }
    
    /**
     * 停用登入語言切換器選項回調
     */
    public function disable_login_language_switcher_callback() {
        $value = get_option('wu_disable_login_language_switcher', false);
        echo '<input type="checkbox" id="wu_disable_login_language_switcher" name="wu_disable_login_language_switcher" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_disable_login_language_switcher">停用 WordPress 登入語言切換器</label>';
        echo '<p class="description">如果您的 WordPress 安裝上啟用了多種語言，此選項將停用語言選擇器，該選擇器允許使用者從登入畫面上的下拉式選單中切換語言。</p>';
    }
    
    /**
     * 登入頁面美化選項回調
     */
    public function enable_login_beautify_callback() {
        $value = get_option('wu_enable_login_beautify', false);
        echo '<input type="checkbox" id="wu_enable_login_beautify" name="wu_enable_login_beautify" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_enable_login_beautify">啟用 WordPress 登入頁面美化</label>';
        echo '<p class="description">採用半透明風格、白底美化登入頁面。</p>';
    }
    
    /**
     * 停用儀表板小工具選項回調
     */
    public function disable_dashboard_widgets_callback() {
        $value = get_option('wu_disable_dashboard_widgets', false);
        echo '<input type="checkbox" id="wu_disable_dashboard_widgets" name="wu_disable_dashboard_widgets" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_disable_dashboard_widgets">停用 WordPress 儀表板小工具</label>';
        echo '<p class="description">停用站點健康狀況、概覽、活動、快速草稿、WordPress 活動和新聞、歡迎面板等小工具。WordPress 預設安裝了許多儀表板小工具，它們通常根本不會用到，但會增加後端和前端的負載。</p>';
    }
    
    /**
     * 移除管理頁尾文本選項回調
     */
    public function remove_admin_footer_text_callback() {
        $value = get_option('wu_remove_admin_footer_text', false);
        echo '<input type="checkbox" id="wu_remove_admin_footer_text" name="wu_remove_admin_footer_text" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_remove_admin_footer_text">移除預設管理頁尾文本</label>';
        echo '<p class="description">此選項隱藏 WordPress 管理員底部的文字：「Thank you for creating with WordPress」左下角，以及右下角的 WordPress 版本。</p>';
    }
    
    /**
     * 自訂管理頁尾文本選項回調
     */
    public function custom_admin_footer_text_callback() {
        $value = get_option('wu_custom_admin_footer_text', '');
        echo '<input type="text" id="wu_custom_admin_footer_text" name="wu_custom_admin_footer_text" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">輸入自訂的管理頁尾文本。留空則不顯示任何文本。</p>';
        echo '<p class="description" style="color: #d63638; font-weight: bold;">⚠️ 重要提醒：啟用此功能後，請勿同時勾選「移除管理頁尾文本」選項，否則自訂文本將無法顯示。</p>';
    }
    
    /**
     * 隱藏後台 Tools 選單選項回調
     */
    public function hide_tools_menu_callback() {
        $value = get_option('wu_hide_tools_menu', false);
        echo '<input type="checkbox" id="wu_hide_tools_menu" name="wu_hide_tools_menu" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_hide_tools_menu">隱藏後台 Tools 選單</label>';
        echo '<p class="description">勾選此選項將隱藏後台頂部的「Tools」選單，該選單通常包含許多不常用的工具。</p>';
    }
    
    /**
     * 隱藏 WordPress Address (URL) 選項回調
     */
    public function hide_wordpress_address_callback() {
        $value = get_option('wu_hide_wordpress_address', false);
        echo '<input type="checkbox" id="wu_hide_wordpress_address" name="wu_hide_wordpress_address" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_hide_wordpress_address">隱藏 WordPress Address (URL)</label>';
        echo '<p class="description">勾選此選項將隱藏後台「設定」頁面中的「WordPress 地址」選項。</p>';
    }
    
    /**
     * 隱藏 Site Address (URL) 選項回調
     */
    public function hide_site_address_callback() {
        $value = get_option('wu_hide_site_address', false);
        echo '<input type="checkbox" id="wu_hide_site_address" name="wu_hide_site_address" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_hide_site_address">隱藏 Site Address (URL)</label>';
        echo '<p class="description">勾選此選項將隱藏後台「設定」頁面中的「網站地址」選項。</p>';
    }
    
    /**
     * 隱藏 Writing Settings 選項回調
     */
    public function hide_writing_settings_callback() {
        $value = get_option('wu_hide_writing_settings', false);
        echo '<input type="checkbox" id="wu_hide_writing_settings" name="wu_hide_writing_settings" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_hide_writing_settings">隱藏 Writing Settings</label>';
        echo '<p class="description">勾選此選項將隱藏後台「設定」頁面中的「寫作設定」選項。</p>';
    }
    
    /**
     * 隱藏 Privacy 設定選項回調
     */
    public function hide_privacy_settings_callback() {
        $value = get_option('wu_hide_privacy_settings', false);
        echo '<input type="checkbox" id="wu_hide_privacy_settings" name="wu_hide_privacy_settings" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_hide_privacy_settings">隱藏 Privacy 設定</label>';
        echo '<p class="description">勾選此選項將隱藏後台「設定」頁面中的「隱私」選項。</p>';
    }
    
    /**
     * 新增：隱藏後台更新通知選項回調
     */
    public function hide_admin_updates_callback() {
        $value = get_option('wu_hide_admin_updates', false);
        echo '<input type="checkbox" id="wu_hide_admin_updates" name="wu_hide_admin_updates" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_hide_admin_updates">隱藏後台更新通知</label>';
        echo '<p class="description">勾選此選項將隱藏 WordPress 核心、外掛和佈景主題的更新通知，讓後台界面更加簡潔。</p>';
    }
    
    /**
     * 自訂前台頁尾文本選項回調
     */
    public function custom_frontend_footer_text_callback() {
        $value = get_option('wu_custom_frontend_footer_text', '');
        echo '<input type="text" id="wu_custom_frontend_footer_text" name="wu_custom_frontend_footer_text" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">輸入自訂的前台頁尾文本。留空則不顯示任何文本。</p>';
    }
    
    /**
     * AJAX 儲存設定
     */
    public function ajax_save_settings() {
        // 驗證權限
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        // 驗證 nonce
        if (!wp_verify_nonce($_POST['wu_admin_bar_nonce'], 'wu_admin_bar_settings-options')) {
            wp_die('Security check failed');
        }
        
        // 處理表單提交
        $settings = array(
            'wu_remove_wp_logo',
            'wu_remove_new_content', // 新增
            'wu_hide_login_logo',
            'wu_disable_login_language_switcher',
            'wu_enable_login_beautify',
            'wu_disable_dashboard_widgets',
            'wu_remove_admin_footer_text',
            'wu_hide_tools_menu',
            'wu_hide_wordpress_address',
            'wu_hide_site_address',
            'wu_hide_writing_settings',
            'wu_hide_privacy_settings',
            'wu_hide_wumetax_toolkit',
            'wu_hide_admin_updates' // 新增
        );
        
        foreach ($settings as $setting) {
            update_option($setting, isset($_POST[$setting]) ? 1 : 0);
        }
        
        // 處理自訂頁尾文本
        update_option('wu_custom_admin_footer_text', sanitize_text_field($_POST['wu_custom_admin_footer_text']));
        update_option('wu_custom_frontend_footer_text', sanitize_text_field($_POST['wu_custom_frontend_footer_text']));
        
        wp_send_json_success(array('message' => '設定已儲存！變更已立即生效。'));
    }
    
    /**
     * 向其他管理員隱藏 WumetaxToolkit 選項回調
     */
    public function hide_wumetax_toolkit_callback() {
        $value = get_option('wu_hide_wumetax_toolkit', false);
        $current_user = wp_get_current_user();
        echo '<input type="checkbox" id="wu_hide_wumetax_toolkit" name="wu_hide_wumetax_toolkit" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_hide_wumetax_toolkit">向其他管理員隱藏 WumetaxToolkit 外掛選單</label>';
        echo '<p class="description">啟用後，只有當前管理員能看到 WumetaxToolkit 選單。</p>';
        echo '<p class="description"><strong>當前管理員資訊：</strong> ID: ' . $current_user->ID . ' | 電子郵件: ' . esc_html($current_user->user_email) . '</p>';
    }
    
    /**
     * 管理頁面
     */
    public function admin_page() {
        if (isset($_POST['submit'])) {
            check_admin_referer('wu_admin_bar_settings-options');
            
            // 處理表單提交
            $settings = array(
                'wu_remove_wp_logo',
                'wu_remove_new_content', // 新增
                'wu_hide_login_logo',
                'wu_disable_login_language_switcher',
                'wu_enable_login_beautify',
                'wu_disable_dashboard_widgets',
                'wu_remove_admin_footer_text',
                'wu_hide_tools_menu',
                'wu_hide_wordpress_address',
                'wu_hide_site_address',
                'wu_hide_writing_settings',
                'wu_hide_privacy_settings',
                'wu_hide_wumetax_toolkit',
                'wu_hide_admin_updates' // 新增
            );
            
            foreach ($settings as $setting) {
                update_option($setting, isset($_POST[$setting]) ? 1 : 0);
            }
            
            // 處理自訂頁尾文本
            update_option('wu_custom_admin_footer_text', sanitize_text_field($_POST['wu_custom_admin_footer_text']));
            update_option('wu_custom_frontend_footer_text', sanitize_text_field($_POST['wu_custom_frontend_footer_text']));
            
            echo '<div class="notice notice-success"><p>設定已儲存！變更已立即生效。</p></div>';
        }
        
        // 獲取當前狀態
        $remove_wp_logo = get_option('wu_remove_wp_logo', false);
        $remove_new_content = get_option('wu_remove_new_content', false); // 新增
        $hide_login_logo = get_option('wu_hide_login_logo', false);
        $disable_language_switcher = get_option('wu_disable_login_language_switcher', false);
        $enable_login_beautify = get_option('wu_enable_login_beautify', false);
        $disable_dashboard_widgets = get_option('wu_disable_dashboard_widgets', false);
        $remove_admin_footer = get_option('wu_remove_admin_footer_text', false);
        $custom_footer_text = get_option('wu_custom_admin_footer_text', '');
        $hide_tools_menu = get_option('wu_hide_tools_menu', false);
        $hide_wordpress_address = get_option('wu_hide_wordpress_address', false);
        $hide_site_address = get_option('wu_hide_site_address', false);
        $hide_writing_settings = get_option('wu_hide_writing_settings', false);
        $hide_privacy_settings = get_option('wu_hide_privacy_settings', false);
        $custom_frontend_footer_text = get_option('wu_custom_frontend_footer_text', '');
        $hide_wumetax_toolkit = get_option('wu_hide_wumetax_toolkit', false);
        $hide_admin_updates = get_option('wu_hide_admin_updates', false); // 新增
        
        ?>
        <div class="wrap">
            <h1>後台設定、WordPress 登入頁面美化(採用半透明風格、白底美化登入頁面)</h1>
            
            <div class="card">
                <h2>當前狀態</h2>
                <table class="wu-status-table">
                    <tr>
                        <td><strong>管理列 WordPress 標誌</strong></td>
                        <td>
                            <span class="<?php echo $remove_wp_logo ? 'wu-status-hidden' : 'wu-status-visible'; ?>">
                                <?php echo $remove_wp_logo ? '已隱藏' : '顯示中'; ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>管理列新增項目</strong></td>
                        <td>
                            <span class="<?php echo $remove_new_content ? 'wu-status-hidden' : 'wu-status-visible'; ?>">
                                <?php echo $remove_new_content ? '已隱藏' : '顯示中'; ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>登入頁面標誌</strong></td>
                        <td>
                            <span class="<?php echo $hide_login_logo ? 'wu-status-hidden' : 'wu-status-visible'; ?>">
                                <?php echo $hide_login_logo ? '已隱藏' : '顯示中'; ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>登入語言切換器</strong></td>
                        <td>
                            <span class="<?php echo $disable_language_switcher ? 'wu-status-disabled' : 'wu-status-enabled'; ?>">
                                <?php echo $disable_language_switcher ? '已停用' : '已啟用'; ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>登入頁面美化</strong></td>
                        <td>
                            <span class="<?php echo $enable_login_beautify ? 'wu-status-enabled' : 'wu-status-disabled'; ?>">
                                <?php echo $enable_login_beautify ? '已啟用' : '已停用'; ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>儀表板小工具</strong></td>
                        <td>
                            <span class="<?php echo $disable_dashboard_widgets ? 'wu-status-disabled' : 'wu-status-enabled'; ?>">
                                <?php echo $disable_dashboard_widgets ? '已停用' : '已啟用'; ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>管理頁尾文本</strong></td>
                        <td>
                            <?php if ($remove_admin_footer): ?>
                                <span class="wu-status-hidden">已隱藏</span>
                            <?php elseif (!empty($custom_footer_text)): ?>
                                <span class="wu-status-custom">自訂：<?php echo esc_html($custom_footer_text); ?></span>
                            <?php else: ?>
                                <span class="wu-status-visible">預設</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>後台 Tools 選單</strong></td>
                        <td>
                            <span class="<?php echo $hide_tools_menu ? 'wu-status-hidden' : 'wu-status-visible'; ?>">
                                <?php echo $hide_tools_menu ? '已隱藏' : '顯示中'; ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>後台 WordPress Address (URL)</strong></td>
                        <td>
                            <span class="<?php echo $hide_wordpress_address ? 'wu-status-hidden' : 'wu-status-visible'; ?>">
                                <?php echo $hide_wordpress_address ? '已隱藏' : '顯示中'; ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>後台 Site Address (URL)</strong></td>
                        <td>
                            <span class="<?php echo $hide_site_address ? 'wu-status-hidden' : 'wu-status-visible'; ?>">
                                <?php echo $hide_site_address ? '已隱藏' : '顯示中'; ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>後台 Writing Settings</strong></td>
                        <td>
                            <span class="<?php echo $hide_writing_settings ? 'wu-status-hidden' : 'wu-status-visible'; ?>">
                                <?php echo $hide_writing_settings ? '已隱藏' : '顯示中'; ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>後台 Privacy 設定</strong></td>
                        <td>
                            <span class="<?php echo $hide_privacy_settings ? 'wu-status-hidden' : 'wu-status-visible'; ?>">
                                <?php echo $hide_privacy_settings ? '已隱藏' : '顯示中'; ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>後台更新通知</strong></td>
                        <td>
                            <span class="<?php echo $hide_admin_updates ? 'wu-status-hidden' : 'wu-status-visible'; ?>">
                                <?php echo $hide_admin_updates ? '已隱藏' : '顯示中'; ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>前台頁尾文本</strong></td>
                        <td>
                            <?php if (!empty($custom_frontend_footer_text)): ?>
                                <span class="wu-status-custom">自訂：<?php echo esc_html($custom_frontend_footer_text); ?></span>
                            <?php else: ?>
                                <span class="wu-status-visible">預設</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>WumetaxToolkit 選單隱藏</strong></td>
                        <td>
                            <span class="<?php echo $hide_wumetax_toolkit ? 'wu-status-enabled' : 'wu-status-disabled'; ?>">
                                <?php echo $hide_wumetax_toolkit ? '已啟用（僅當前管理員可見）' : '已停用（所有管理員可見）'; ?>
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
            
            <form method="post" action="">
                <?php
                settings_fields('wu_admin_bar_settings');
                do_settings_sections('wu_admin_bar_settings');
                wp_nonce_field('wu_admin_bar_settings-options');
                submit_button();
                ?>
            </form>
            
            <script>
            jQuery(document).ready(function($) {
                // 為表單添加 ID
                $('form').attr('id', 'wu-admin-bar-form');
                
                // 自動儲存功能
                $('input[type="checkbox"], input[type="text"]').on('change input', function() {
                    // 防抖動：延遲執行，避免連續觸發
                    clearTimeout(window.wuSaveTimeout);
                    window.wuSaveTimeout = setTimeout(function() {
                        var formData = $('#wu-admin-bar-form').serialize();
                        formData += '&action=wu_admin_bar_save';
                        
                        $.post(ajaxurl, formData, function(response) {
                            if (response.success) {
                                // 移除舊的通知
                                $('.notice').remove();
                                // 顯示新的成功訊息
                                $('.wrap h1').after('<div class="notice notice-success is-dismissible"><p>' + response.data.message + '</p></div>');
                                // 3秒後自動隱藏
                                setTimeout(function() {
                                    $('.notice').fadeOut();
                                }, 3000);
                            }
                        }).fail(function() {
                            $('.notice').remove();
                            $('.wrap h1').after('<div class="notice notice-error is-dismissible"><p>儲存失敗，請重試。</p></div>');
                            setTimeout(function() {
                                $('.notice').fadeOut();
                            }, 3000);
                        });
                    }, 800); // 800ms 延遲
                });
            });
            </script>
            
            <div class="card">
                <h2>功能說明</h2>
                <h3>什麼是管理列清理？</h3>
                <ul>
                    <li>移除 WordPress 管理列中不必要的項目</li>
                    <li>自訂登入頁面外觀和功能</li>
                    <li>管理儀表板小工具</li>
                    <li>讓管理介面更加簡潔專業</li>
                </ul>
                
                <h3>管理列功能</h3>
                <ul>
                    <li>移除管理列左上角的「W」圖示</li>
                    <li>移除管理列左上角的「+ 新增」項目</li>
                    <li>移除相關的下拉選單項目</li>
                    <li>包括：WordPress.org、文檔、支援論壇等鏈接</li>
                </ul>
                
                <h3>登入頁面功能</h3>
                <ul>
                    <li><strong>隱藏標誌：</strong>移除登入頁面的 WordPress 標誌</li>
                    <li><strong>語言切換器：</strong>停用多語言環境下的語言選擇器</li>
                    <li><strong>頁面美化：</strong>採用半透明風格、白底設計</li>
                    <li><strong>專業外觀：</strong>提供更專業的登入體驗</li>
                </ul>
                
                <h3>儀表板功能</h3>
                <ul>
                    <li><strong>小工具管理：</strong>停用不必要的儀表板小工具</li>
                    <li><strong>頁尾自訂：</strong>移除或自訂管理頁尾文本</li>
                    <li><strong>效能提升：</strong>減少後端載入時間</li>
                    <li><strong>界面簡化：</strong>專注於核心管理功能</li>
                </ul>
                
                <h3>後台隱藏功能</h3>
                <ul>
                    <li><strong>隱藏 Tools 選單：</strong>隱藏後台頂部的「Tools」選單，減少不必要的選項。</li>
                    <li><strong>隱藏地址設定：</strong>隱藏後台「設定」頁面中的「WordPress 地址」和「網站地址」選項，避免客戶修改。</li>
                    <li><strong>隱藏寫作設定：</strong>隱藏後台「設定」頁面中的「寫作設定」選項，避免客戶修改。</li>
                    <li><strong>隱藏隱私設定：</strong>隱藏後台「設定」頁面中的「隱私」選項，避免客戶修改。</li>
                    <li><strong>隱藏更新通知：</strong>隱藏 WordPress 核心、外掛和佈景主題的更新通知。</li>
                </ul>
                
                <h3>前台自訂功能</h3>
                <ul>
                    <li><strong>自訂頁尾文本：</strong>自訂前台網站底部顯示的文字，例如版權資訊。</li>
                </ul>
                
                <h3>為什麼要使用這些功能？</h3>
                <ul>
                    <li><strong>專業外觀：</strong>讓管理介面看起來更加專業</li>
                    <li><strong>品牌一致性：</strong>避免向客戶展示 WordPress 品牌</li>
                    <li><strong>簡化介面：</strong>移除不常用的功能和選項</li>
                    <li><strong>提高效率：</strong>減少視覺干擾，提高工作專注度</li>
                    <li><strong>效能優化：</strong>停用不必要的功能以提高載入速度</li>
                </ul>
                
                <h3>注意事項</h3>
                <ul>
                    <li>所有功能都可以隨時啟用或停用</li>
                    <li>不會影響網站的正常運作和核心功能</li>
                    <li>僅影響管理後台的外觀和功能</li>
                    <li>建議在客戶網站中使用這些功能</li>
                    <li>登入頁面美化可能需要瀏覽器支援現代 CSS 功能</li>
                </ul>
            </div>
        </div>
        
        <style>
        .wu-status-visible { color: #d63638; font-weight: bold; }
        .wu-status-hidden { color: #00a32a; font-weight: bold; }
        .wu-status-enabled { color: #00a32a; font-weight: bold; }
        .wu-status-disabled { color: #d63638; font-weight: bold; }
        .wu-status-custom { color: #0073aa; font-weight: bold; }
        .card { background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin: 20px 0; }
        .card h2 { margin-top: 0; }
        .card h3 { color: #23282d; }
        .card ul { margin-left: 20px; }
        .wu-status-table { width: 100%; margin-top: 15px; border-collapse: collapse; }
        .wu-status-table th, .wu-status-table td { padding: 8px; border: 1px solid #ddd; text-align: left; }
        .wu-status-table th { background-color: #f5f5f5; }
        </style>
        <?php
    }
    
    /**
     * 載入所有啟用的功能
     */
    private function load_features() {
        // 移除管理列 WordPress 標誌
        if (get_option('wu_remove_wp_logo', false)) {
            $this->remove_wp_logo();
        }
        
        // 新增：移除管理列新增項目
        if (get_option('wu_remove_new_content', false)) {
            $this->remove_new_content();
        }
        
        // 隱藏登入頁面標誌
        if (get_option('wu_hide_login_logo', false)) {
            add_action('login_head', array($this, 'hide_login_logo'));
        }
        
        // 停用登入語言切換器
        if (get_option('wu_disable_login_language_switcher', false)) {
            add_filter('login_display_language_dropdown', '__return_false');
        }
        
        // 啟用登入頁面美化
        if (get_option('wu_enable_login_beautify', false)) {
            add_action('login_head', array($this, 'beautify_login_page'));
        }
        
        // 停用儀表板小工具
        if (get_option('wu_disable_dashboard_widgets', false)) {
            add_action('wp_dashboard_setup', array($this, 'disable_dashboard_widgets'));
        }
        
        // 處理管理頁尾文本
        if (get_option('wu_remove_admin_footer_text', false)) {
            add_filter('admin_footer_text', '__return_empty_string');
            add_filter('update_footer', '__return_empty_string', 11);
        } elseif (!empty(get_option('wu_custom_admin_footer_text', ''))) {
            add_filter('admin_footer_text', array($this, 'custom_admin_footer_text'));
            add_filter('update_footer', '__return_empty_string', 11);
        }

        // 隱藏後台 Tools 選單
        if (get_option('wu_hide_tools_menu', false)) {
            add_action('admin_menu', array($this, 'hide_tools_menu'));
        }

        // 隱藏後台設定頁面選項
        if (get_option('wu_hide_wordpress_address', false) || 
            get_option('wu_hide_site_address', false) || 
            get_option('wu_hide_writing_settings', false) || 
            get_option('wu_hide_privacy_settings', false)) {
            add_action('admin_head', array($this, 'hide_admin_settings'));
        }

        // 處理前台頁尾文本
        if (!empty(get_option('wu_custom_frontend_footer_text', ''))) {
            add_action('wp_footer', array($this, 'custom_frontend_footer_text'));
        }
        
        // 隱藏 WumetaxToolkit 選單（僅對其他管理員）
        if (get_option('wu_hide_wumetax_toolkit', false)) {
            add_action('admin_menu', array($this, 'hide_wumetax_toolkit_menu'), 999);
        }
        
        // 新增：隱藏後台更新通知
        if (get_option('wu_hide_admin_updates', false)) {
            $this->hide_admin_updates();
        }
    }
    
    /**
     * 隱藏登入頁面標誌
     */
    public function hide_login_logo() {
        echo '<style>
            .login h1 a {
                display: none !important;
            }
            .login h1 {
                display: none !important;
            }
        </style>';
    }
    
    /**
     * 修改：美化登入頁面 - 半透明風格、白底
     */
    public function beautify_login_page() {
        echo '<style>
            body.login {
                background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%) !important;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif !important;
            }
            
            .login #loginform {
                background: rgba(255, 255, 255, 0.9) !important;
                backdrop-filter: blur(10px) !important;
                -webkit-backdrop-filter: blur(10px) !important;
                border: 1px solid rgba(255, 255, 255, 0.5) !important;
                border-radius: 15px !important;
                box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.1) !important;
                padding: 40px !important;
                margin-top: 20px !important;
            }
            
            .login form .input, 
            .login input[type=text], 
            .login input[type=password] {
                background: rgba(255, 255, 255, 0.8) !important;
                border: 1px solid #ddd !important;
                border-radius: 8px !important;
                color: #333 !important;
                font-size: 16px !important;
                padding: 15px !important;
                margin-bottom: 20px !important;
                transition: all 0.3s ease !important;
            }
            
            .login form .input:focus, 
            .login input[type=text]:focus, 
            .login input[type=password]:focus {
                background: rgba(255, 255, 255, 1) !important;
                border-color: #0073aa !important;
                box-shadow: 0 0 10px rgba(0, 115, 170, 0.3) !important;
                outline: none !important;
            }
            
            .login form .input::placeholder,
            .login input[type=text]::placeholder,
            .login input[type=password]::placeholder {
                color: #999 !important;
            }
            
            .login .button-primary {
                background: linear-gradient(135deg, #0073aa 0%, #005177 100%) !important;
                border: none !important;
                border-radius: 8px !important;
                color: #fff !important;
                font-size: 16px !important;
                font-weight: 600 !important;
                padding: 15px 30px !important;
                text-shadow: none !important;
                box-shadow: 0 4px 15px 0 rgba(0, 115, 170, 0.3) !important;
                transition: all 0.3s ease !important;
                width: 100% !important;
                margin-top: 10px !important;
            }
            
            .login .button-primary:hover {
                background: linear-gradient(135deg, #005177 0%, #0073aa 100%) !important;
                transform: translateY(-2px) !important;
                box-shadow: 0 6px 20px 0 rgba(0, 115, 170, 0.4) !important;
            }
            
            .login .button-primary:focus {
                box-shadow: 0 0 0 3px rgba(0, 115, 170, 0.3) !important;
            }
            
            .login label {
                color: #333 !important;
                font-weight: 500 !important;
                margin-bottom: 8px !important;
                display: block !important;
            }
            
            .login #backtoblog a, 
            .login #nav a {
                color: #666 !important;
                text-decoration: none !important;
                transition: color 0.3s ease !important;
            }
            
            .login #backtoblog a:hover, 
            .login #nav a:hover {
                color: #0073aa !important;
            }
            
            .login .message, 
            .login .notice {
                background: rgba(255, 255, 255, 0.9) !important;
                border: 1px solid #ddd !important;
                border-radius: 8px !important;
                color: #333 !important;
                backdrop-filter: blur(5px) !important;
                -webkit-backdrop-filter: blur(5px) !important;
            }
            
            .login h1 a {
                background-image: none !important;
                color: #333 !important;
                font-size: 32px !important;
                font-weight: 300 !important;
                text-decoration: none !important;
                text-align: center !important;
                display: block !important;
                width: auto !important;
                height: auto !important;
            }
            
            .login h1 {
                text-align: center !important;
                margin-bottom: 30px !important;
            }
            
            .login form .forgetmenot {
                color: #333 !important;
            }
            
            .login form .forgetmenot input[type=checkbox] {
                margin-right: 8px !important;
            }
            
            @media screen and (max-width: 768px) {
                .login #loginform {
                    margin: 20px auto !important;
                    padding: 30px !important;
                }
                
                .login form .input, 
                .login input[type=text], 
                .login input[type=password] {
                    font-size: 16px !important;
                    padding: 12px !important;
                }
            }
        </style>';
    }
    
    /**
     * 停用儀表板小工具
     */
    public function disable_dashboard_widgets() {
        // 移除預設的儀表板小工具
        remove_meta_box('dashboard_site_health', 'dashboard', 'normal');      // 站點健康狀況
        remove_meta_box('dashboard_right_now', 'dashboard', 'normal');        // 概覽
        remove_meta_box('dashboard_activity', 'dashboard', 'normal');         // 活動
        remove_meta_box('dashboard_quick_press', 'dashboard', 'side');        // 快速草稿
        remove_meta_box('dashboard_primary', 'dashboard', 'side');            // WordPress 活動和新聞
        remove_meta_box('welcome-panel', 'dashboard', 'normal');              // 歡迎面板
        
        // 移除其他可能的小工具
        remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');  // 近期評論
        remove_meta_box('dashboard_incoming_links', 'dashboard', 'normal');   // 引用連結
        remove_meta_box('dashboard_plugins', 'dashboard', 'normal');          // 外掛
        remove_meta_box('dashboard_recent_drafts', 'dashboard', 'side');      // 近期草稿
        remove_meta_box('dashboard_secondary', 'dashboard', 'side');          // 其他 WordPress 新聞
        
        // 停用歡迎面板
        remove_action('welcome_panel', 'wp_welcome_panel');
    }
    
    /**
     * 自訂管理頁尾文本
     */
    public function custom_admin_footer_text() {
        return get_option('wu_custom_admin_footer_text', '');
    }

    /**
     * 隱藏後台 Tools 選單
     */
    public function hide_tools_menu() {
        remove_menu_page('tools.php');
    }

    /**
     * 隱藏後台設定頁面選項
     */
    public function hide_admin_settings() {
        $css = '<style>';
        
        if (get_option('wu_hide_wordpress_address', false)) {
            $css .= 'tr:has(th label[for="home"]) { display: none !important; }';
            $css .= '#home_row { display: none !important; }';
            $css .= 'tr.home { display: none !important; }';
        }
        
        if (get_option('wu_hide_site_address', false)) {
            $css .= 'tr:has(th label[for="siteurl"]) { display: none !important; }';
            $css .= '#siteurl_row { display: none !important; }';
            $css .= 'tr.siteurl { display: none !important; }';
        }
        
        if (get_option('wu_hide_writing_settings', false)) {
            $css .= '#menu-settings ul li:has(a[href="options-writing.php"]) { display: none !important; }';
        }
        
        if (get_option('wu_hide_privacy_settings', false)) {
            $css .= '#menu-settings ul li:has(a[href="options-privacy.php"]) { display: none !important; }';
        }
        
        $css .= '</style>';
        echo $css;
        
        // 使用 JavaScript 來更可靠地隱藏設定選項
        echo '<script>
        jQuery(document).ready(function($) {
            // 隱藏 WordPress 地址和網站地址
            if (' . (get_option('wu_hide_wordpress_address', false) ? 'true' : 'false') . ') {
                $("label[for=\'home\']").closest("tr").hide();
                $("#home").closest("tr").hide();
            }
            if (' . (get_option('wu_hide_site_address', false) ? 'true' : 'false') . ') {
                $("label[for=\'siteurl\']").closest("tr").hide();
                $("#siteurl").closest("tr").hide();
            }
            
            // 隱藏選單項目
            if (' . (get_option('wu_hide_writing_settings', false) ? 'true' : 'false') . ') {
                $("#menu-settings").find("a[href=\'options-writing.php\']").parent().hide();
            }
            if (' . (get_option('wu_hide_privacy_settings', false) ? 'true' : 'false') . ') {
                $("#menu-settings").find("a[href=\'options-privacy.php\']").parent().hide();
            }
        });
        </script>';
    }

    /**
     * 自訂前台頁尾文本
     */
    public function custom_frontend_footer_text() {
        $text = get_option('wu_custom_frontend_footer_text', '');
        if (!empty($text)) {
            echo '<div style="text-align: center; padding: 20px; margin-top: 30px; border-top: 1px solid #eee;">' . esc_html($text) . '</div>';
        }
    }
    
    /**
     * 隱藏 WumetaxToolkit 選單（僅對其他管理員）
     */
    public function hide_wumetax_toolkit_menu() {
        // 獲取設定此功能的管理員 ID
        $setting_admin_id = get_option('wu_hide_wumetax_toolkit_admin_id', 0);
        $current_user_id = get_current_user_id();
        
        // 如果還沒設定管理員 ID，則設定為當前用戶
        if (!$setting_admin_id) {
            update_option('wu_hide_wumetax_toolkit_admin_id', $current_user_id);
            $setting_admin_id = $current_user_id;
        }
        
        // 如果當前用戶不是設定此功能的管理員，則隱藏選單
        if ($current_user_id != $setting_admin_id) {
            remove_menu_page('wumetax-toolkit');
        }
    }
    
    /**
     * 移除 WordPress 標誌
     */
    private function remove_wp_logo() {
        add_action('admin_bar_menu', array($this, 'remove_wp_logo_from_admin_bar'), 999);
    }
    
    /**
     * 新增：移除新增項目
     */
    private function remove_new_content() {
        add_action('admin_bar_menu', array($this, 'remove_new_content_from_admin_bar'), 999);
    }
    
    /**
     * 新增：隱藏後台更新通知
     */
    private function hide_admin_updates() {
        // 隱藏核心更新
        add_filter('pre_site_transient_update_core', '__return_null');
        
        // 隱藏外掛更新
        add_filter('pre_site_transient_update_plugins', '__return_null');
        
        // 隱藏佈景主題更新
        add_filter('pre_site_transient_update_themes', '__return_null');
        
        // 移除更新通知
        add_action('admin_menu', array($this, 'remove_update_notifications'));
        
        // 隱藏管理列的更新通知
        add_action('wp_before_admin_bar_render', array($this, 'remove_admin_bar_updates'));
        
        // 移除更新相關的 CSS
        add_action('admin_head', array($this, 'hide_update_nag'));
    }
    
    /**
     * 從管理列中移除 WordPress 標誌
     */
    public function remove_wp_logo_from_admin_bar($wp_admin_bar) {
        // 移除 WordPress 標誌
        $wp_admin_bar->remove_node('wp-logo');
        
        // 移除相關的子項目（以防萬一）
        $wp_admin_bar->remove_node('wp-logo-external');
        $wp_admin_bar->remove_node('about');
        $wp_admin_bar->remove_node('wporg');
        $wp_admin_bar->remove_node('documentation');
        $wp_admin_bar->remove_node('support-forums');
        $wp_admin_bar->remove_node('feedback');
    }
    
    /**
     * 新增：從管理列中移除新增項目
     */
    public function remove_new_content_from_admin_bar($wp_admin_bar) {
        // 移除新增項目
        $wp_admin_bar->remove_node('new-content');
        
        // 移除相關的子項目
        $wp_admin_bar->remove_node('new-post');
        $wp_admin_bar->remove_node('new-media');
        $wp_admin_bar->remove_node('new-page');
        $wp_admin_bar->remove_node('new-user');
    }
    
    /**
     * 新增：移除更新通知
     */
    public function remove_update_notifications() {
        remove_action('admin_notices', 'update_nag', 3);
        remove_action('network_admin_notices', 'update_nag', 3);
        remove_action('admin_notices', 'maintenance_nag');
        remove_action('network_admin_notices', 'maintenance_nag');
    }
    
    /**
     * 新增：從管理列移除更新通知
     */
    public function remove_admin_bar_updates() {
        global $wp_admin_bar;
        $wp_admin_bar->remove_menu('updates');
    }
    
    /**
     * 新增：隱藏更新提示的 CSS
     */
    public function hide_update_nag() {
        echo '<style>
            .update-nag, 
            .updated, 
            .error, 
            .notice.notice-warning.is-dismissible,
            .notice-warning,
            #update-nag,
            .update-message {
                display: none !important;
            }
        </style>';
    }
}

// 初始化模組
new WU_Admin_Bar_Cleaner();
