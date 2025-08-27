<?php
/**
 * 後台設定模組
 * 功能：後台設定、WordPress 登入頁面美化(採用半透明風格、白底美化登入頁面)
 */

if (!defined('ABSPATH')) exit;

class WU_Admin_Bar_Cleaner {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'), 10);
        add_action('admin_init', array($this, 'admin_init'));
        
        // AJAX 處理
        add_action('wp_ajax_wu_admin_bar_save', array($this, 'ajax_save_settings'));
        
        // 載入所有啟用的功能
        $this->load_features();

        // 前台注入複製保護
        add_action('wp_enqueue_scripts', array($this, 'enqueue_copy_protection')); 
    }
    
    /**
     * 添加管理頁面
     */
    public function add_admin_menu() {
        add_submenu_page(
            'wumetax-toolkit',
            '後台介面管理',
            '後台介面管理',
            'manage_options',
            'wumetax-admin-bar-cleaner',
            array($this, 'admin_page')
        );
    }

    public function enqueue_copy_protection() {
        if (is_admin()) return;
        if (!get_option('wu_enable_copy_protection', false)) return;
        $message = get_option('wu_copy_protection_message', '此網站已啟用內容保護，禁止複製與右鍵操作。');
        add_action('wp_head', function() use ($message) {
            echo '<style>body{user-select:none;-webkit-user-select:none;-ms-user-select:none}</style>';
            echo '<script>(function(){
                var msg = ' . wp_json_encode($message) . ';
                function alertMsg(e){ try{ if(e) e.preventDefault(); }catch(_){} alert(msg); }
                document.addEventListener("contextmenu", function(e){ e.preventDefault(); alert(msg); });
                document.addEventListener("copy", alertMsg);
                document.addEventListener("cut", alertMsg);
                document.addEventListener("paste", alertMsg);
                document.addEventListener("keydown", function(e){
                    var k=e.key.toLowerCase();
                    if((e.ctrlKey||e.metaKey) && (k==="a"||k==="c"||k==="x"||k==="s"||k==="v")){ e.preventDefault(); alert(msg); }
                }, true);
            })();</script>';
        });
    }
    
    /**
     * 初始化設定
     */
    public function admin_init() {
        // 註冊所有設定
        $settings = array(
            'wu_remove_wp_logo',
            'wu_remove_new_content',
            'wu_hide_login_logo',
            'wu_disable_login_language_switcher',
            'wu_enable_login_beautify',
            'wu_disable_all_dashboard_widgets',
            'wu_custom_admin_footer_text',
            'wu_remove_admin_footer_text',
            'wu_hide_tools_menu',
            'wu_hide_wordpress_address',
            'wu_hide_site_address',
            'wu_hide_writing_settings',
            'wu_hide_privacy_settings',
            'wu_custom_frontend_footer_text',
            'wu_hide_wumetax_toolkit',
            'wu_hide_admin_updates',
            'wu_disabled_user_roles',
            // 新增：內容複製保護
            'wu_enable_copy_protection',
            'wu_copy_protection_message'
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
            'WordPress 登入頁面美化',
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
        
        // 使用者角色管理區域
        add_settings_section(
            'wu_user_roles_section',
            '使用者角色管理',
            array($this, 'user_roles_section_callback'),
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
            'wu_disable_all_dashboard_widgets',
            '儀表板小工具一鍵管理',
            array($this, 'dashboard_widgets_settings_callback'),
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
        
        add_settings_field(
            'wu_hide_admin_updates',
            '隱藏後台更新通知',
            array($this, 'hide_admin_updates_callback'),
            'wu_admin_bar_settings',
            'wu_backend_section'
        );
        
        add_settings_field(
            'wu_hide_wumetax_toolkit',
            '向其他管理員隱藏 WumetaxToolkit',
            array($this, 'hide_wumetax_toolkit_callback'),
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
        
        // 內容複製保護
        add_settings_field(
            'wu_enable_copy_protection',
            '內容複製保護',
            array($this, 'copy_protection_callback'),
            'wu_admin_bar_settings',
            'wu_frontend_section'
        );
        
        // 添加使用者角色管理欄位
        add_settings_field(
            'wu_disabled_user_roles',
            '停用使用者角色',
            array($this, 'user_roles_management_callback'),
            'wu_admin_bar_settings',
            'wu_user_roles_section'
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
        echo '<p>自訂登入頁面的外觀和功能，採用現代化透明玻璃質感設計，提供更專業的使用者體驗。</p>';
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
     * 使用者角色管理區域說明
     */
    public function user_roles_section_callback() {
        echo '<p>管理 WordPress 使用者角色的顯示和可用性，可選擇停用特定角色以簡化使用者管理。</p>';
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
     * 移除新增項目選項回調
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
        echo '<p class="description">採用現代化透明玻璃質感設計，漸層背景，簡潔按鈕，提供更專業的登入體驗。</p>';
    }
    
    /**
     * 儀表板小工具一鍵停用選項回調
     */
    public function dashboard_widgets_settings_callback() {
        $disable_all_widgets = get_option('wu_disable_all_dashboard_widgets', false);
        
        echo '<div style="background: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 20px;">';
        echo '<h4 style="margin-top: 0; color: #d63638;">儀表板小工具一鍵管理</h4>';
        echo '<p>一鍵停用所有儀表板小工具，讓後台載入更快速，界面更簡潔。</p>';
        echo '<label style="display: flex; align-items: center; gap: 10px; font-size: 16px; margin-top: 15px;">';
        echo '<input type="checkbox" id="wu_disable_all_dashboard_widgets" name="wu_disable_all_dashboard_widgets" value="1" ' . checked(1, $disable_all_widgets, false) . ' style="transform: scale(1.2);" />';
        echo '<span style="font-weight: 600;">停用所有儀表板小工具</span>';
        echo '</label>';
        echo '<div style="margin-top: 15px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">';
        echo '<p style="margin: 0; color: #856404;"><strong>包含以下小工具：</strong></p>';
        echo '<ul style="margin: 10px 0 0 20px; color: #856404;">';
        echo '<li>歡迎使用 WordPress</li>';
        echo '<li>PHP 執行環境建議更新</li>';
        echo '<li>WordPress 活動及新聞</li>';
        echo '<li>快速草稿</li>';
        echo '<li>網站概況</li>';
        echo '<li>網站活動</li>';
        echo '<li>網站狀態</li>';
        echo '<li>近期評論、引用連結、外掛等其他小工具</li>';
        echo '</ul>';
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * 使用者角色管理選項回調
     */
    public function user_roles_management_callback() {
        $disabled_roles = get_option('wu_disabled_user_roles', array());
        
        $roles = array(
            'contributor' => array(
                'name' => '投稿者',
                'description' => '可以撰寫和編輯自己的文章，但無法發布'
            ),
            'author' => array(
                'name' => '作者',
                'description' => '可以發布和管理自己的文章'
            ),
            'editor' => array(
                'name' => '編輯',
                'description' => '可以發布和管理所有文章，包括其他人的文章'
            ),
            'shop_manager' => array(
                'name' => '商店管理員',
                'description' => 'WooCommerce 商店管理員角色'
            ),
            'customer' => array(
                'name' => '顧客',
                'description' => 'WooCommerce 顧客角色'
            )
        );
        
        echo '<div style="background: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 20px;">';
        echo '<h4 style="margin-top: 0; color: #d63638;">使用者角色管理</h4>';
        echo '<p>選擇要停用的使用者角色。停用後，這些角色將無法在網站註冊或被指派給使用者。</p>';
        echo '</div>';
        
        echo '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; margin-top: 15px;">';
        
        foreach ($roles as $role_key => $role_info) {
            $checked = isset($disabled_roles[$role_key]) ? checked(1, $disabled_roles[$role_key], false) : '';
            
            echo '<div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">';
            echo '<label style="display: block; cursor: pointer;">';
            echo '<input type="checkbox" name="wu_disabled_user_roles[' . $role_key . ']" value="1" ' . $checked . ' style="margin-right: 12px; transform: scale(1.2);" />';
            echo '<strong style="font-size: 16px; color: #333;">' . esc_html($role_info['name']) . '</strong>';
            echo '<div style="margin-top: 8px; color: #666; font-size: 14px; line-height: 1.4;">' . esc_html($role_info['description']) . '</div>';
            echo '</label>';
            echo '</div>';
        }
        
        echo '</div>';
        
        echo '<div style="margin-top: 20px; padding: 15px; background: #d1ecf1; border-left: 4px solid #bee5eb; border-radius: 4px;">';
        echo '<p style="margin: 0; color: #0c5460;"><strong>注意：</strong>停用角色不會影響現有使用者，只會阻止新建立該角色的使用者。現有使用者需要手動更改角色。</p>';
        echo '</div>';
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
     * 隱藏後台更新通知選項回調
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
     * 內容複製保護選項回調
     */
    public function copy_protection_callback() {
        $enabled = get_option('wu_enable_copy_protection', false);
        $message = get_option('wu_copy_protection_message', '此網站已啟用內容保護，禁止複製與右鍵操作。');
        echo '<label><input type="checkbox" name="wu_enable_copy_protection" value="1" ' . checked(1, $enabled, false) . '> 啟用前台內容複製保護</label>';
        echo '<p style="margin-top:8px;"><label>警告訊息：<br><input type="text" name="wu_copy_protection_message" value="' . esc_attr($message) . '" style="width:100%;max-width:520px;"></label></p>';
        echo '<p class="description">禁止右鍵、選取與常見快捷鍵（CTRL/⌘ + A/C/X/S/V），觸發時顯示警告。</p>';
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
            'wu_remove_new_content',
            'wu_hide_login_logo',
            'wu_disable_login_language_switcher',
            'wu_enable_login_beautify',
            'wu_disable_all_dashboard_widgets',
            'wu_remove_admin_footer_text',
            'wu_hide_tools_menu',
            'wu_hide_wordpress_address',
            'wu_hide_site_address',
            'wu_hide_writing_settings',
            'wu_hide_privacy_settings',
            'wu_hide_wumetax_toolkit',
            'wu_hide_admin_updates',
            'wu_enable_copy_protection'
        );
        
        foreach ($settings as $setting) {
            update_option($setting, isset($_POST[$setting]) ? 1 : 0);
        }
        
        // 處理自訂頁尾文本
        update_option('wu_custom_admin_footer_text', sanitize_text_field($_POST['wu_custom_admin_footer_text']));
        update_option('wu_custom_frontend_footer_text', sanitize_text_field($_POST['wu_custom_frontend_footer_text']));
        update_option('wu_copy_protection_message', sanitize_text_field($_POST['wu_copy_protection_message']));
        
        // 處理使用者角色設定
        update_option('wu_disabled_user_roles', isset($_POST['wu_disabled_user_roles']) ? $_POST['wu_disabled_user_roles'] : array());
        
        wp_send_json_success(array('message' => '設定已儲存！變更已立即生效。'));
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
                'wu_remove_new_content',
                'wu_hide_login_logo',
                'wu_disable_login_language_switcher',
                'wu_enable_login_beautify',
                'wu_disable_all_dashboard_widgets',
                'wu_remove_admin_footer_text',
                'wu_hide_tools_menu',
                'wu_hide_wordpress_address',
                'wu_hide_site_address',
                'wu_hide_writing_settings',
                'wu_hide_privacy_settings',
                'wu_hide_wumetax_toolkit',
                'wu_hide_admin_updates',
                'wu_enable_copy_protection'
            );
            
            foreach ($settings as $setting) {
                update_option($setting, isset($_POST[$setting]) ? 1 : 0);
            }
            
            // 處理自訂頁尾文本
            update_option('wu_custom_admin_footer_text', sanitize_text_field($_POST['wu_custom_admin_footer_text']));
            update_option('wu_custom_frontend_footer_text', sanitize_text_field($_POST['wu_custom_frontend_footer_text']));
            update_option('wu_copy_protection_message', sanitize_text_field($_POST['wu_copy_protection_message']));
            
            // 處理使用者角色設定
            update_option('wu_disabled_user_roles', isset($_POST['wu_disabled_user_roles']) ? $_POST['wu_disabled_user_roles'] : array());
            
            echo '<div class="notice notice-success"><p>設定已儲存！變更已立即生效。</p></div>';
        }
        
        // 獲取當前狀態
        $remove_wp_logo = get_option('wu_remove_wp_logo', false);
        $remove_new_content = get_option('wu_remove_new_content', false);
        $hide_login_logo = get_option('wu_hide_login_logo', false);
        $disable_language_switcher = get_option('wu_disable_login_language_switcher', false);
        $enable_login_beautify = get_option('wu_enable_login_beautify', false);
        $disable_all_widgets = get_option('wu_disable_all_dashboard_widgets', false);
        $remove_admin_footer = get_option('wu_remove_admin_footer_text', false);
        $custom_footer_text = get_option('wu_custom_admin_footer_text', '');
        $hide_tools_menu = get_option('wu_hide_tools_menu', false);
        $hide_wordpress_address = get_option('wu_hide_wordpress_address', false);
        $hide_site_address = get_option('wu_hide_site_address', false);
        $hide_writing_settings = get_option('wu_hide_writing_settings', false);
        $hide_privacy_settings = get_option('wu_hide_privacy_settings', false);
        $custom_frontend_footer_text = get_option('wu_custom_frontend_footer_text', '');
        $hide_wumetax_toolkit = get_option('wu_hide_wumetax_toolkit', false);
        $hide_admin_updates = get_option('wu_hide_admin_updates', false);
        $disabled_roles = get_option('wu_disabled_user_roles', array());
        $enable_copy_protection = get_option('wu_enable_copy_protection', false);
        
        ?>
        <div class="wrap">
            <h1>後台設定、WordPress 登入頁面美化(現代化透明玻璃質感設計)</h1>
            
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
                                <?php echo $enable_login_beautify ? '已啟用（現代化設計）' : '已停用'; ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>儀表板小工具</strong></td>
                        <td>
                            <span class="<?php echo $disable_all_widgets ? 'wu-status-disabled' : 'wu-status-enabled'; ?>">
                                <?php echo $disable_all_widgets ? '已全部停用' : '已啟用'; ?>
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
                        <td><strong>使用者角色管理</strong></td>
                        <td>
                            <?php if (!empty($disabled_roles)): ?>
                                <span class="wu-status-enabled">已停用 <?php echo count($disabled_roles); ?> 個角色</span>
                            <?php else: ?>
                                <span class="wu-status-disabled">無停用角色</span>
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
                        <td><strong>內容複製保護</strong></td>
                        <td>
                            <span class="<?php echo $enable_copy_protection ? 'wu-status-enabled' : 'wu-status-disabled'; ?>">
                                <?php echo $enable_copy_protection ? '已啟用' : '已停用'; ?>
                            </span>
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
                <h3>新增與更新功能</h3>
                <ul>
                    <li><strong>登入頁面美化升級：</strong>採用現代化透明玻璃質感設計，漸層背景，流暢動畫效果</li>
                    <li><strong>儀表板小工具一鍵管理：</strong>簡化操作，一鍵停用所有儀表板小工具</li>
                    <li><strong>使用者角色管理：</strong>可選擇停用特定使用者角色，包括投稿者、作者、編輯、商店管理員、顧客、使用者</li>
                    <li><strong>內容複製保護：</strong>防止前台內容被複製，包括右鍵選單和常見快捷鍵</li>
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
                    <li><strong>現代化美化：</strong>透明玻璃質感、動態漸層背景、流暢動畫</li>
                    <li><strong>專業外觀：</strong>提供更專業的登入體驗</li>
                </ul>
                
                <h3>儀表板功能</h3>
                <ul>
                    <li><strong>一鍵小工具管理：</strong>快速停用所有儀表板小工具</li>
                    <li><strong>頁尾自訂：</strong>移除或自訂管理頁尾文本</li>
                    <li><strong>效能提升：</strong>減少後端載入時間</li>
                    <li><strong>界面簡化：</strong>專注於核心管理功能</li>
                </ul>
                
                <h3>使用者角色管理</h3>
                <ul>
                    <li><strong>角色停用：</strong>可選擇停用投稿者、作者、編輯、商店管理員、顧客、使用者角色</li>
                    <li><strong>簡化管理：</strong>減少不必要的使用者角色選項</li>
                    <li><strong>安全提升：</strong>限制特定角色的建立和指派</li>
                </ul>
                
                <h3>注意事項</h3>
                <ul>
                    <li>所有功能都可以隨時啟用或停用</li>
                    <li>不會影響網站的正常運作和核心功能</li>
                    <li>僅影響管理後台的外觀和功能</li>
                    <li>建議在客戶網站中使用這些功能</li>
                    <li>登入頁面美化需要瀏覽器支援現代 CSS 功能</li>
                    <li>停用使用者角色不會影響現有使用者</li>
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
        
        // 移除管理列新增項目
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
        
        // 停用所有儀表板小工具
        if (get_option('wu_disable_all_dashboard_widgets', false)) {
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
        
        // 隱藏後台更新通知
        if (get_option('wu_hide_admin_updates', false)) {
            $this->hide_admin_updates();
        }
        
        // 應用使用者角色限制
        $this->apply_user_role_restrictions();
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
     * 升級版：美化登入頁面 - 白底透明玻璃質感設計
     */
    public function beautify_login_page() {
        echo '<style>
            body.login {
                background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 50%, #f1f3f4 100%) !important;
                background-attachment: fixed !important;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
                position: relative !important;
                min-height: 100vh !important;
            }
            
            body.login::before {
                content: "";
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: url("data:image/svg+xml,%3Csvg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cg fill=\"none\" fill-rule=\"evenodd\"%3E%3Cg fill=\"%23e3e6ea\" fill-opacity=\"0.02\"%3E%3Ccircle cx=\"7\" cy=\"7\" r=\"7\"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") !important;
                z-index: -1 !important;
            }
            
            .login #loginform {
                background: rgba(255, 255, 255, 0.8) !important;
                backdrop-filter: blur(15px) !important;
                -webkit-backdrop-filter: blur(15px) !important;
                border: 1px solid rgba(0, 0, 0, 0.1) !important;
                border-radius: 5px !important;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08), 0 4px 15px rgba(0, 0, 0, 0.04) !important;
                padding: 40px !important;
                margin-top: 30px !important;
                position: relative !important;
            }
            
            .login #loginform::before {
                content: "";
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: linear-gradient(135deg, rgba(255, 255, 255, 0.3) 0%, rgba(255, 255, 255, 0.1) 100%);
                border-radius: 5px;
                z-index: -1;
            }
            
            .login form .input, 
            .login input[type=text], 
            .login input[type=password] {
                background: rgba(255, 255, 255, 0.95) !important;
                border: 1px solid rgba(0, 0, 0, 0.15) !important;
                border-radius: 5px !important;
                color: #333 !important;
                font-size: 16px !important;
                font-weight: 400 !important;
                padding: 15px 18px !important;
                height: 48px !important;
                box-sizing: border-box !important;
                margin-bottom: 18px !important;
                transition: all 0.3s ease !important;
                backdrop-filter: blur(8px) !important;
                -webkit-backdrop-filter: blur(8px) !important;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06) !important;
            }
            
            .login form .input:focus, 
            .login input[type=text]:focus, 
            .login input[type=password]:focus {
                background: rgba(255, 255, 255, 1) !important;
                border-color: rgba(0, 0, 0, 0.3) !important;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1), 0 0 0 2px rgba(0, 0, 0, 0.05) !important;
                outline: none !important;
                transform: translateY(-1px) !important;
            }
            
            .login .button-primary {
                background: #000000 !important;
                border: none !important;
                border-radius: 5px !important;
                color: #ffffff !important;
                font-size: 16px !important;
                font-weight: 500 !important;
                letter-spacing: 0.3px !important;
                padding: 15px 30px !important;
                height: 48px !important;
                text-shadow: none !important;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2) !important;
                transition: all 0.3s ease !important;
                width: 100% !important;
                margin-top: 15px !important;
                cursor: pointer !important;
                position: relative !important;
                overflow: hidden !important;
            }
            
            .login .button-primary::before {
                content: "";
                position: absolute;
                top: 0;
                left: -100%;
                width: 100%;
                height: 100%;
                background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
                transition: left 0.5s;
            }
            
            .login .button-primary:hover::before {
                left: 100%;
            }
            
            .login .button-primary:hover {
                background: #333333 !important;
                transform: translateY(-2px) !important;
                box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3) !important;
            }
            
            .login .button-primary:active {
                transform: translateY(0px) !important;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2) !important;
            }
            
            .login label {
                color: #333333 !important;
                font-weight: 500 !important;
                font-size: 14px !important;
                margin-bottom: 8px !important;
                display: block !important;
                text-shadow: none !important;
            }
            
            .login #backtoblog a, 
            .login #nav a {
                color: #666666 !important;
                text-decoration: none !important;
                transition: all 0.3s ease !important;
                font-size: 14px !important;
                text-shadow: none !important;
            }
            
            .login #backtoblog a:hover, 
            .login #nav a:hover {
                color: #000000 !important;
                text-decoration: underline !important;
            }
            
            .login .message, 
            .login .notice {
                background: rgba(255, 255, 255, 0.95) !important;
                border: 1px solid rgba(0, 0, 0, 0.1) !important;
                border-radius: 5px !important;
                color: #333 !important;
                backdrop-filter: blur(10px) !important;
                -webkit-backdrop-filter: blur(10px) !important;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06) !important;
            }
            
            .login h1 {
                text-align: center !important;
                margin-bottom: 40px !important;
            }
            
            .login h1 a {
                background-image: none !important;
                color: #333333 !important;
                font-size: 28px !important;
                font-weight: 300 !important;
                text-decoration: none !important;
                text-align: center !important;
                display: block !important;
                width: auto !important;
                height: auto !important;
                text-shadow: none !important;
            }
            
            .login form .forgetmenot {
                color: #666666 !important;
                font-size: 14px !important;
                text-shadow: none !important;
            }
            
            .login form .forgetmenot input[type=checkbox] {
                margin-right: 10px !important;
                transform: scale(1.1) !important;
            }
            
            @media screen and (max-width: 768px) {
                .login #loginform {
                    margin: 20px auto !important;
                    padding: 30px !important;
                    border-radius: 5px !important;
                }
                
                .login form .input, 
                .login input[type=text], 
                .login input[type=password] {
                    font-size: 16px !important;
                    padding: 12px 16px !important;
                    height: 44px !important;
                }
                
                .login .button-primary {
                    height: 44px !important;
                    padding: 12px 25px !important;
                }
            }
        </style>';
    }
    
    /**
     * 一鍵停用所有儀表板小工具
     */
    public function disable_dashboard_widgets() {
        // 移除歡迎面板
        remove_meta_box('welcome-panel', 'dashboard', 'normal');
        remove_action('welcome_panel', 'wp_welcome_panel');
        
        // 移除所有預設小工具
        remove_meta_box('dashboard_primary', 'dashboard', 'side');           // WordPress 活動及新聞
        remove_meta_box('dashboard_quick_press', 'dashboard', 'side');       // 快速草稿
        remove_meta_box('dashboard_right_now', 'dashboard', 'normal');       // 網站概況
        remove_meta_box('dashboard_activity', 'dashboard', 'normal');        // 網站活動
        remove_meta_box('dashboard_site_health', 'dashboard', 'normal');     // 網站狀態
        remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal'); // 近期評論
        remove_meta_box('dashboard_incoming_links', 'dashboard', 'normal');  // 引用連結
        remove_meta_box('dashboard_plugins', 'dashboard', 'normal');         // 外掛
        remove_meta_box('dashboard_recent_drafts', 'dashboard', 'side');     // 近期草稿
        remove_meta_box('dashboard_secondary', 'dashboard', 'side');         // 其他 WordPress 新聞
        
        // 移除 PHP Nag 通知
        remove_action('admin_notices', 'wp_dashboard_php_nag_notice');
        
        // 停用其他可能的小工具
        add_action('wp_dashboard_setup', function() {
            global $wp_meta_boxes;
            $wp_meta_boxes['dashboard'] = array();
        }, 999);
    }
    
    /**
     * 應用使用者角色限制
     */
    private function apply_user_role_restrictions() {
        $disabled_roles = get_option('wu_disabled_user_roles', array());
        
        if (!empty($disabled_roles)) {
            // 阻止註冊時選擇被停用的角色
            add_filter('editable_roles', function($roles) use ($disabled_roles) {
                foreach ($disabled_roles as $role_key => $disabled) {
                    if ($disabled) {
                        unset($roles[$role_key]);
                    }
                }
                return $roles;
            });
            
            // 隱藏使用者列表中的角色選擇器
            add_action('admin_head-users.php', function() use ($disabled_roles) {
                echo '<script>
                jQuery(document).ready(function($) {';
                foreach ($disabled_roles as $role_key => $disabled) {
                    if ($disabled) {
                        echo '$("select[name=\'role\'] option[value=\'' . $role_key . '\']").remove();';
                        echo '$("select[name=\'new_role\'] option[value=\'' . $role_key . '\']").remove();';
                    }
                }
                echo '});
                </script>';
            });
            
            // 在使用者個人資料頁面隱藏被停用的角色
            add_action('admin_head-user-edit.php', function() use ($disabled_roles) {
                echo '<script>
                jQuery(document).ready(function($) {';
                foreach ($disabled_roles as $role_key => $disabled) {
                    if ($disabled) {
                        echo '$("select[name=\'role\'] option[value=\'' . $role_key . '\']").remove();';
                    }
                }
                echo '});
                </script>';
            });
        }
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
     * 移除新增項目
     */
    private function remove_new_content() {
        add_action('admin_bar_menu', array($this, 'remove_new_content_from_admin_bar'), 999);
    }
    
    /**
     * 隱藏後台更新通知
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
     * 從管理列中移除新增項目
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
     * 移除更新通知
     */
    public function remove_update_notifications() {
        remove_action('admin_notices', 'update_nag', 3);
        remove_action('network_admin_notices', 'update_nag', 3);
        remove_action('admin_notices', 'maintenance_nag');
        remove_action('network_admin_notices', 'maintenance_nag');
    }
    
    /**
     * 從管理列移除更新通知
     */
    public function remove_admin_bar_updates() {
        global $wp_admin_bar;
        $wp_admin_bar->remove_menu('updates');
    }
    
    /**
     * 隱藏更新提示的 CSS
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
?>
