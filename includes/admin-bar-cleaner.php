<?php
/**
 * 後台設定模組
 * 功能:後台設定、WordPress 登入頁面美化、安全性強化版本
 * 版本:4.0 - 安全性強化、架構優化、AJAX 修正版
 */

if (!defined('ABSPATH')) exit;

/**
 * 主類別 - 後台管理介面清理與美化
 */
class WU_Admin_Bar_Cleaner {
    
    /**
     * 選項前綴
     */
    private $option_prefix = 'wu_';
    
    /**
     * 設定組名稱
     */
    private $settings_group = 'wu_admin_bar_settings';
    
    /**
     * Nonce action 名稱
     */
    private $nonce_action = 'wu_admin_bar_save_action';
    
    /**
     * Nonce 欄位名稱
     */
    private $nonce_field = 'wu_admin_bar_nonce';
    
    /**
     * 建構函數
     */
    public function __construct() {
        // 僅在後台註冊選單和設定
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'), 10);
            add_action('admin_init', array($this, 'admin_init'));
            add_action('wp_ajax_wu_admin_bar_save', array($this, 'ajax_save_settings'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        }
        
        // 載入所有啟用的功能
        add_action('init', array($this, 'load_features'), 10);
    }
    
    /**
     * 註冊並載入後台資源
     */
    public function enqueue_admin_assets($hook) {
        // 僅在外掛設定頁面載入
        if ($hook !== 'wumetaxtoolkit_page_wumetax-admin-bar-cleaner') {
            return;
        }
        
        // 註冊並載入 CSS
        wp_enqueue_style(
            'wu-admin-bar-cleaner-css',
            false,
            array(),
            '4.0'
        );
        
        // 內聯 CSS
        wp_add_inline_style('wu-admin-bar-cleaner-css', $this->get_admin_css());
        
        // 註冊並載入 JavaScript
        wp_enqueue_script(
            'wu-admin-bar-cleaner-js',
            false,
            array('jquery'),
            '4.0',
            true
        );
        
        // 傳遞 nonce 到 JavaScript
        wp_localize_script('wu-admin-bar-cleaner-js', 'wuAdminBarAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce($this->nonce_action)
        ));
        
        // 內聯 JavaScript
        wp_add_inline_script('wu-admin-bar-cleaner-js', $this->get_admin_js());
    }
    
    /**
     * 取得管理介面 CSS
     */
    private function get_admin_css() {
        return '
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
        .wu-security-notice { 
            background: #fff3cd; 
            border-left: 4px solid #ffc107; 
            padding: 15px; 
            margin: 20px 0;
            border-radius: 4px;
        }
        .wu-security-notice p { 
            margin: 0; 
            color: #856404; 
        }
        ';
    }
    
    /**
     * 取得管理介面 JavaScript
     */
    private function get_admin_js() {
        return '
        jQuery(document).ready(function($) {
            // 為表單添加 ID
            $("form").attr("id", "wu-admin-bar-form");
            
            // 自動儲存功能 (防抖動處理)
            var saveTimeout;
            $("input[type=\"checkbox\"], input[type=\"text\"]").on("change input", function() {
                clearTimeout(saveTimeout);
                saveTimeout = setTimeout(function() {
                    var formData = $("#wu-admin-bar-form").serialize();
                    formData += "&action=wu_admin_bar_save";
                    formData += "&" + wuAdminBarAjax.nonce_field + "=" + wuAdminBarAjax.nonce;
                    
                    $.post(wuAdminBarAjax.ajaxurl, formData, function(response) {
                        // 移除舊通知
                        $(".notice").remove();
                        
                        if (response.success) {
                            $(".wrap h1").after(
                                "<div class=\"notice notice-success is-dismissible\"><p>" + 
                                response.data.message + 
                                "</p></div>"
                            );
                        } else {
                            var errorMsg = response.data && response.data.message ? response.data.message : "儲存失敗,請重試。";
                            $(".wrap h1").after(
                                "<div class=\"notice notice-error is-dismissible\"><p>" + errorMsg + "</p></div>"
                            );
                        }
                        
                        // 3秒後自動移除通知
                        setTimeout(function() {
                            $(".notice").fadeOut(300, function() {
                                $(this).remove();
                            });
                        }, 3000);
                    }).fail(function(xhr, status, error) {
                        $(".notice").remove();
                        $(".wrap h1").after(
                            "<div class=\"notice notice-error is-dismissible\"><p>網路錯誤,請重試。(" + error + ")</p></div>"
                        );
                    });
                }, 800);
            });
        });
        ';
    }
    
    /**
     * 載入前台複製保護資源
     */
    public function enqueue_copy_protection() {
        if (is_admin()) {
            return;
        }
        
        if (!get_option('wu_enable_copy_protection', false)) {
            return;
        }
        
        $message = get_option('wu_copy_protection_message', '此網站已啟用內容保護,禁止複製與右鍵操作。');
        
        // 註冊並載入前台 CSS
        wp_enqueue_style('wu-copy-protection-css', false);
        wp_add_inline_style('wu-copy-protection-css', 'body{user-select:none;-webkit-user-select:none;-ms-user-select:none}');
        
        // 註冊並載入前台 JavaScript
        wp_enqueue_script('wu-copy-protection-js', false, array(), '4.0', true);
        wp_add_inline_script('wu-copy-protection-js', $this->get_copy_protection_js($message));
    }
    
    /**
     * 取得複製保護 JavaScript
     */
    private function get_copy_protection_js($message) {
        $safe_message = wp_json_encode($message);
        
        return '
        (function() {
            var msg = ' . $safe_message . ';
            
            function showAlert(e) {
                if (e) {
                    try { e.preventDefault(); } catch(_) {}
                }
                alert(msg);
            }
            
            // 右鍵選單
            document.addEventListener("contextmenu", function(e) {
                e.preventDefault();
                alert(msg);
            });
            
            // 複製、剪下、貼上
            document.addEventListener("copy", showAlert);
            document.addEventListener("cut", showAlert);
            document.addEventListener("paste", showAlert);
            
            // 鍵盤快捷鍵
            document.addEventListener("keydown", function(e) {
                var k = e.key.toLowerCase();
                if ((e.ctrlKey || e.metaKey) && ["a", "c", "x", "s", "v"].indexOf(k) !== -1) {
                    e.preventDefault();
                    alert(msg);
                }
            }, true);
        })();
        ';
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
    
    /**
     * 初始化設定
     */
    public function admin_init() {
        // 註冊所有設定選項
        $settings = $this->get_all_settings();
        
        foreach ($settings as $setting) {
            register_setting(
                $this->settings_group,
                $setting,
                array(
                    'sanitize_callback' => array($this, 'sanitize_setting'),
                    'default' => false
                )
            );
        }
        
        // 註冊設定區段
        $this->register_settings_sections();
        
        // 註冊設定欄位
        $this->register_settings_fields();
    }
    
    /**
     * 取得所有設定選項名稱
     */
    private function get_all_settings() {
        return array(
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
            'wu_enable_copy_protection',
            'wu_copy_protection_message'
        );
    }
    
    /**
     * 清理設定資料
     */
    public function sanitize_setting($value) {
        // 如果是陣列 (例如 wu_disabled_user_roles)
        if (is_array($value)) {
            return array_map('sanitize_text_field', $value);
        }
        
        // 如果是文字
        if (is_string($value)) {
            return sanitize_text_field($value);
        }
        
        // 如果是布林值或數字
        return (bool) $value;
    }
    
    /**
     * 註冊設定區段
     */
    private function register_settings_sections() {
        $sections = array(
            'wu_admin_bar_section' => array(
                'title' => '後台設定',
                'callback' => 'settings_section_callback'
            ),
            'wu_login_section' => array(
                'title' => 'WordPress 登入頁面美化',
                'callback' => 'login_section_callback'
            ),
            'wu_dashboard_section' => array(
                'title' => '儀表板設定',
                'callback' => 'dashboard_section_callback'
            ),
            'wu_backend_section' => array(
                'title' => '後台隱藏設定',
                'callback' => 'backend_section_callback'
            ),
            'wu_frontend_section' => array(
                'title' => '前台設定',
                'callback' => 'frontend_section_callback'
            ),
            'wu_user_roles_section' => array(
                'title' => '使用者角色管理',
                'callback' => 'user_roles_section_callback'
            )
        );
        
        foreach ($sections as $id => $section) {
            add_settings_section(
                $id,
                $section['title'],
                array($this, $section['callback']),
                $this->settings_group
            );
        }
    }
    
    /**
     * 註冊設定欄位
     */
    private function register_settings_fields() {
        // 管理列設定
        add_settings_field('wu_remove_wp_logo', '移除 WordPress 標誌', array($this, 'remove_wp_logo_callback'), $this->settings_group, 'wu_admin_bar_section');
        add_settings_field('wu_remove_new_content', '移除管理列新增項目', array($this, 'remove_new_content_callback'), $this->settings_group, 'wu_admin_bar_section');
        
        // 登入頁面設定
        add_settings_field('wu_hide_login_logo', '隱藏登入頁面 WordPress 標誌', array($this, 'hide_login_logo_callback'), $this->settings_group, 'wu_login_section');
        add_settings_field('wu_disable_login_language_switcher', '停用登入語言切換器', array($this, 'disable_login_language_switcher_callback'), $this->settings_group, 'wu_login_section');
        add_settings_field('wu_enable_login_beautify', 'WordPress 登入頁面美化', array($this, 'enable_login_beautify_callback'), $this->settings_group, 'wu_login_section');
        
        // 儀表板設定
        add_settings_field('wu_disable_all_dashboard_widgets', '儀表板小工具一鍵管理', array($this, 'dashboard_widgets_settings_callback'), $this->settings_group, 'wu_dashboard_section');
        add_settings_field('wu_remove_admin_footer_text', '移除管理頁尾文本', array($this, 'remove_admin_footer_text_callback'), $this->settings_group, 'wu_dashboard_section');
        add_settings_field('wu_custom_admin_footer_text', '自訂管理頁尾文本', array($this, 'custom_admin_footer_text_callback'), $this->settings_group, 'wu_dashboard_section');
        
        // 後台隱藏設定
        add_settings_field('wu_hide_tools_menu', '隱藏後台 Tools 選單', array($this, 'hide_tools_menu_callback'), $this->settings_group, 'wu_backend_section');
        add_settings_field('wu_hide_wordpress_address', '隱藏 WordPress Address (URL)', array($this, 'hide_wordpress_address_callback'), $this->settings_group, 'wu_backend_section');
        add_settings_field('wu_hide_site_address', '隱藏 Site Address (URL)', array($this, 'hide_site_address_callback'), $this->settings_group, 'wu_backend_section');
        add_settings_field('wu_hide_writing_settings', '隱藏 Writing Settings', array($this, 'hide_writing_settings_callback'), $this->settings_group, 'wu_backend_section');
        add_settings_field('wu_hide_privacy_settings', '隱藏 Privacy 設定', array($this, 'hide_privacy_settings_callback'), $this->settings_group, 'wu_backend_section');
        add_settings_field('wu_hide_admin_updates', '隱藏後台更新通知', array($this, 'hide_admin_updates_callback'), $this->settings_group, 'wu_backend_section');
        add_settings_field('wu_hide_wumetax_toolkit', '向其他管理員隱藏 WumetaxToolkit', array($this, 'hide_wumetax_toolkit_callback'), $this->settings_group, 'wu_backend_section');
        
        // 前台設定
        add_settings_field('wu_custom_frontend_footer_text', '自訂前台頁尾文本', array($this, 'custom_frontend_footer_text_callback'), $this->settings_group, 'wu_frontend_section');
        add_settings_field('wu_enable_copy_protection', '內容複製保護', array($this, 'copy_protection_callback'), $this->settings_group, 'wu_frontend_section');
        
        // 使用者角色管理
        add_settings_field('wu_disabled_user_roles', '停用使用者角色', array($this, 'user_roles_management_callback'), $this->settings_group, 'wu_user_roles_section');
    }
    
    /**
     * 區段說明回調函數
     */
    public function settings_section_callback() {
        echo '<p>' . esc_html__('後台設定功能可以幫助您移除不必要的 WordPress 預設項目,讓管理界面更加簡潔。', 'wumetax-toolkit') . '</p>';
    }
    
    public function login_section_callback() {
        echo '<p>' . esc_html__('自訂登入頁面的外觀和功能,採用現代化透明玻璃質感設計,提供更專業的使用者體驗。', 'wumetax-toolkit') . '</p>';
    }
    
    public function dashboard_section_callback() {
        echo '<p>' . esc_html__('管理 WordPress 儀表板的小工具和介面元素,簡化管理體驗。', 'wumetax-toolkit') . '</p>';
    }
    
    public function backend_section_callback() {
        echo '<p>' . esc_html__('選擇性隱藏後台特定設定選項和選單,讓後台介面更加簡潔專業。', 'wumetax-toolkit') . '</p>';
    }
    
    public function frontend_section_callback() {
        echo '<p>' . esc_html__('自訂前台網站的顯示內容和外觀設定。', 'wumetax-toolkit') . '</p>';
        echo '<div class="wu-security-notice">';
        echo '<p><strong>⚠️ 安全提醒:</strong> JavaScript 防複製功能僅具備<strong>基本防禦能力</strong>,無法完全阻止技術使用者透過瀏覽器開發工具、停用 JavaScript、或使用其他進階方法存取內容。建議配合其他安全措施(如浮水印、內容加密)來保護重要資料。</p>';
        echo '</div>';
    }
    
    public function user_roles_section_callback() {
        echo '<p>' . esc_html__('管理 WordPress 使用者角色的顯示和可用性,可選擇停用特定角色以簡化使用者管理。', 'wumetax-toolkit') . '</p>';
    }
    
    /**
     * 欄位回調函數 - 移除 WordPress 標誌
     */
    public function remove_wp_logo_callback() {
        $value = get_option('wu_remove_wp_logo', false);
        ?>
        <input type="checkbox" 
               id="wu_remove_wp_logo" 
               name="wu_remove_wp_logo" 
               value="1" 
               <?php checked(1, $value); ?> />
        <label for="wu_remove_wp_logo">
            <?php esc_html_e('移除管理列中的 WordPress 標誌(W 圖示)', 'wumetax-toolkit'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('勾選此選項將從管理列中移除 WordPress 標誌及其下拉選單,讓界面更加簡潔。', 'wumetax-toolkit'); ?>
        </p>
        <?php
    }
    
    /**
     * 欄位回調函數 - 移除新增項目
     */
    public function remove_new_content_callback() {
        $value = get_option('wu_remove_new_content', false);
        ?>
        <input type="checkbox" 
               id="wu_remove_new_content" 
               name="wu_remove_new_content" 
               value="1" 
               <?php checked(1, $value); ?> />
        <label for="wu_remove_new_content">
            <?php esc_html_e('移除管理列中的新增項目(+ 新增)', 'wumetax-toolkit'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('勾選此選項將從管理列中移除「+ 新增」項目及其下拉選單,包括新增文章、頁面、媒體等選項。', 'wumetax-toolkit'); ?>
        </p>
        <?php
    }
    
    /**
     * 欄位回調函數 - 隱藏登入頁面標誌
     */
    public function hide_login_logo_callback() {
        $value = get_option('wu_hide_login_logo', false);
        ?>
        <input type="checkbox" 
               id="wu_hide_login_logo" 
               name="wu_hide_login_logo" 
               value="1" 
               <?php checked(1, $value); ?> />
        <label for="wu_hide_login_logo">
            <?php esc_html_e('隱藏登入頁面的 WordPress 標誌', 'wumetax-toolkit'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('從登入頁面隱藏標準 WordPress 徽標。', 'wumetax-toolkit'); ?>
        </p>
        <?php
    }
    
    /**
     * 欄位回調函數 - 停用登入語言切換器
     */
    public function disable_login_language_switcher_callback() {
        $value = get_option('wu_disable_login_language_switcher', false);
        ?>
        <input type="checkbox" 
               id="wu_disable_login_language_switcher" 
               name="wu_disable_login_language_switcher" 
               value="1" 
               <?php checked(1, $value); ?> />
        <label for="wu_disable_login_language_switcher">
            <?php esc_html_e('停用 WordPress 登入語言切換器', 'wumetax-toolkit'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('如果您的 WordPress 安裝上啟用了多種語言,此選項將停用語言選擇器。', 'wumetax-toolkit'); ?>
        </p>
        <?php
    }
    
    /**
     * 欄位回調函數 - 登入頁面美化
     */
    public function enable_login_beautify_callback() {
        $value = get_option('wu_enable_login_beautify', false);
        ?>
        <input type="checkbox" 
               id="wu_enable_login_beautify" 
               name="wu_enable_login_beautify" 
               value="1" 
               <?php checked(1, $value); ?> />
        <label for="wu_enable_login_beautify">
            <?php esc_html_e('啟用 WordPress 登入頁面美化', 'wumetax-toolkit'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('採用現代化透明玻璃質感設計,漸層背景,簡潔按鈕,提供更專業的登入體驗。', 'wumetax-toolkit'); ?>
        </p>
        <?php
    }
    
    /**
     * 欄位回調函數 - 儀表板小工具一鍵停用
     */
    public function dashboard_widgets_settings_callback() {
        $disable_all_widgets = get_option('wu_disable_all_dashboard_widgets', false);
        ?>
        <div style="background: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <h4 style="margin-top: 0; color: #d63638;">
                <?php esc_html_e('儀表板小工具一鍵管理', 'wumetax-toolkit'); ?>
            </h4>
            <p><?php esc_html_e('一鍵停用所有儀表板小工具,讓後台載入更快速,界面更簡潔。', 'wumetax-toolkit'); ?></p>
            <label style="display: flex; align-items: center; gap: 10px; font-size: 16px; margin-top: 15px;">
                <input type="checkbox" 
                       id="wu_disable_all_dashboard_widgets" 
                       name="wu_disable_all_dashboard_widgets" 
                       value="1" 
                       <?php checked(1, $disable_all_widgets); ?> 
                       style="transform: scale(1.2);" />
                <span style="font-weight: 600;">
                    <?php esc_html_e('停用所有儀表板小工具', 'wumetax-toolkit'); ?>
                </span>
            </label>
            <div style="margin-top: 15px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
                <p style="margin: 0; color: #856404;">
                    <strong><?php esc_html_e('包含以下小工具:', 'wumetax-toolkit'); ?></strong>
                </p>
                <ul style="margin: 10px 0 0 20px; color: #856404;">
                    <li><?php esc_html_e('歡迎使用 WordPress', 'wumetax-toolkit'); ?></li>
                    <li><?php esc_html_e('PHP 執行環境建議更新', 'wumetax-toolkit'); ?></li>
                    <li><?php esc_html_e('WordPress 活動及新聞', 'wumetax-toolkit'); ?></li>
                    <li><?php esc_html_e('快速草稿', 'wumetax-toolkit'); ?></li>
                    <li><?php esc_html_e('網站概況', 'wumetax-toolkit'); ?></li>
                    <li><?php esc_html_e('網站活動', 'wumetax-toolkit'); ?></li>
                    <li><?php esc_html_e('網站狀態', 'wumetax-toolkit'); ?></li>
                    <li><?php esc_html_e('近期評論、引用連結、外掛等其他小工具', 'wumetax-toolkit'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }
    
    /**
     * 欄位回調函數 - 使用者角色管理
     */
    public function user_roles_management_callback() {
        $disabled_roles = get_option('wu_disabled_user_roles', array());
        
        // 使用 wp_roles() 函數取代全域變數
        $wp_roles = wp_roles();
        $all_roles = $wp_roles->get_names();
        $protected_roles = array('subscriber', 'administrator');
        ?>
        <div style="background: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <h4 style="margin-top: 0; color: #d63638;">
                <?php esc_html_e('使用者角色管理', 'wumetax-toolkit'); ?>
            </h4>
            <p><?php esc_html_e('自動偵測當前所有的使用者角色,可選擇停用除了 subscriber 和 administrator 之外的所有角色。', 'wumetax-toolkit'); ?></p>
            <p><strong><?php printf(esc_html__('偵測到 %d 個使用者角色', 'wumetax-toolkit'), count($all_roles)); ?></strong></p>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; margin-top: 15px;">
        <?php
        foreach ($all_roles as $role_key => $role_name) {
            $is_protected = in_array($role_key, $protected_roles);
            $checked = isset($disabled_roles[$role_key]) && $disabled_roles[$role_key];
            
            $role_obj = get_role($role_key);
            $capabilities_count = $role_obj ? count($role_obj->capabilities) : 0;
            
            $card_style = $is_protected ? 
                'background: #f0f6ff; padding: 20px; border: 2px solid #0073aa; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,115,170,0.1);' : 
                'background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);';
            ?>
            <div style="<?php echo esc_attr($card_style); ?>">
                <?php if ($is_protected): ?>
                    <div style="margin-bottom: 10px; padding: 8px 12px; background: #0073aa; color: white; border-radius: 4px; font-size: 12px; text-align: center;">
                        <?php esc_html_e('受保護角色', 'wumetax-toolkit'); ?>
                    </div>
                    <label style="display: block; cursor: not-allowed; opacity: 0.6;">
                        <input type="checkbox" disabled style="margin-right: 12px; transform: scale(1.2);" />
                <?php else: ?>
                    <label style="display: block; cursor: pointer;">
                        <input type="checkbox" 
                               name="wu_disabled_user_roles[<?php echo esc_attr($role_key); ?>]" 
                               value="1" 
                               <?php checked(1, $checked); ?> 
                               style="margin-right: 12px; transform: scale(1.2);" />
                <?php endif; ?>
                
                <strong style="font-size: 16px; color: #333;">
                    <?php echo esc_html($role_name); ?>
                </strong>
                <div style="margin-top: 5px; color: #999; font-size: 12px;">
                    <?php printf(esc_html__('角色代碼: %s', 'wumetax-toolkit'), esc_html($role_key)); ?>
                </div>
                <div style="margin-top: 8px; color: #666; font-size: 14px; line-height: 1.4;">
                    <?php printf(esc_html__('權限數量: %d 個', 'wumetax-toolkit'), $capabilities_count); ?>
                </div>
                
                <?php if ($is_protected): ?>
                    <div style="margin-top: 8px; color: #0073aa; font-size: 12px; font-style: italic;">
                        <?php esc_html_e('此角色受到保護,無法停用', 'wumetax-toolkit'); ?>
                    </div>
                <?php endif; ?>
                
                </label>
            </div>
            <?php
        }
        ?>
        </div>
        
        <div style="margin-top: 20px; padding: 15px; background: #d1ecf1; border-left: 4px solid #bee5eb; border-radius: 4px;">
            <p style="margin: 0; color: #0c5460;"><strong><?php esc_html_e('注意:', 'wumetax-toolkit'); ?></strong></p>
            <ul style="margin: 5px 0 0 20px; color: #0c5460;">
                <li><?php esc_html_e('停用角色不會影響現有使用者,只會阻止新建立該角色的使用者', 'wumetax-toolkit'); ?></li>
                <li><?php esc_html_e('受保護角色 (subscriber, administrator) 無法停用,確保網站基本功能正常運作', 'wumetax-toolkit'); ?></li>
                <li><?php esc_html_e('自動偵測功能會顯示所有註冊的使用者角色,包括外掛新增的自訂角色', 'wumetax-toolkit'); ?></li>
            </ul>
        </div>
        <?php
    }
    
    /**
     * 欄位回調函數 - 移除管理頁尾文本
     */
    public function remove_admin_footer_text_callback() {
        $value = get_option('wu_remove_admin_footer_text', false);
        ?>
        <input type="checkbox" 
               id="wu_remove_admin_footer_text" 
               name="wu_remove_admin_footer_text" 
               value="1" 
               <?php checked(1, $value); ?> />
        <label for="wu_remove_admin_footer_text">
            <?php esc_html_e('移除預設管理頁尾文本', 'wumetax-toolkit'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('此選項隱藏 WordPress 管理員底部的文字。', 'wumetax-toolkit'); ?>
        </p>
        <?php
    }
    
    /**
     * 欄位回調函數 - 自訂管理頁尾文本
     */
    public function custom_admin_footer_text_callback() {
        $value = get_option('wu_custom_admin_footer_text', '');
        ?>
        <input type="text" 
               id="wu_custom_admin_footer_text" 
               name="wu_custom_admin_footer_text" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text" />
        <p class="description">
            <?php esc_html_e('輸入自訂的管理頁尾文本。留空則不顯示任何文本。', 'wumetax-toolkit'); ?>
        </p>
        <p class="description" style="color: #d63638; font-weight: bold;">
            ⚠️ <?php esc_html_e('重要提醒:啟用此功能後,請勿同時勾選「移除管理頁尾文本」選項,否則自訂文本將無法顯示。', 'wumetax-toolkit'); ?>
        </p>
        <?php
    }
    
    /**
     * 欄位回調函數 - 隱藏後台 Tools 選單
     */
    public function hide_tools_menu_callback() {
        $value = get_option('wu_hide_tools_menu', false);
        ?>
        <input type="checkbox" 
               id="wu_hide_tools_menu" 
               name="wu_hide_tools_menu" 
               value="1" 
               <?php checked(1, $value); ?> />
        <label for="wu_hide_tools_menu">
            <?php esc_html_e('隱藏後台 Tools 選單', 'wumetax-toolkit'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('勾選此選項將隱藏後台頂部的「Tools」選單。', 'wumetax-toolkit'); ?>
        </p>
        <?php
    }
    
    /**
     * 欄位回調函數 - 隱藏 WordPress Address (URL)
     */
    public function hide_wordpress_address_callback() {
        $value = get_option('wu_hide_wordpress_address', false);
        ?>
        <input type="checkbox" 
               id="wu_hide_wordpress_address" 
               name="wu_hide_wordpress_address" 
               value="1" 
               <?php checked(1, $value); ?> />
        <label for="wu_hide_wordpress_address">
            <?php esc_html_e('隱藏 WordPress Address (URL)', 'wumetax-toolkit'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('勾選此選項將隱藏後台「設定」頁面中的「WordPress 地址」選項。', 'wumetax-toolkit'); ?>
        </p>
        <?php
    }
    
    /**
     * 欄位回調函數 - 隱藏 Site Address (URL)
     */
    public function hide_site_address_callback() {
        $value = get_option('wu_hide_site_address', false);
        ?>
        <input type="checkbox" 
               id="wu_hide_site_address" 
               name="wu_hide_site_address" 
               value="1" 
               <?php checked(1, $value); ?> />
        <label for="wu_hide_site_address">
            <?php esc_html_e('隱藏 Site Address (URL)', 'wumetax-toolkit'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('勾選此選項將隱藏後台「設定」頁面中的「網站地址」選項。', 'wumetax-toolkit'); ?>
        </p>
        <?php
    }
    
    /**
     * 欄位回調函數 - 隱藏 Writing Settings
     */
    public function hide_writing_settings_callback() {
        $value = get_option('wu_hide_writing_settings', false);
        ?>
        <input type="checkbox" 
               id="wu_hide_writing_settings" 
               name="wu_hide_writing_settings" 
               value="1" 
               <?php checked(1, $value); ?> />
        <label for="wu_hide_writing_settings">
            <?php esc_html_e('隱藏 Writing Settings', 'wumetax-toolkit'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('勾選此選項將隱藏後台「設定」頁面中的「寫作設定」選項。', 'wumetax-toolkit'); ?>
        </p>
        <?php
    }
    
    /**
     * 欄位回調函數 - 隱藏 Privacy 設定
     */
    public function hide_privacy_settings_callback() {
        $value = get_option('wu_hide_privacy_settings', false);
        ?>
        <input type="checkbox" 
               id="wu_hide_privacy_settings" 
               name="wu_hide_privacy_settings" 
               value="1" 
               <?php checked(1, $value); ?> />
        <label for="wu_hide_privacy_settings">
            <?php esc_html_e('隱藏 Privacy 設定', 'wumetax-toolkit'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('勾選此選項將隱藏後台「設定」頁面中的「隱私」選項。', 'wumetax-toolkit'); ?>
        </p>
        <?php
    }
    
    /**
     * 欄位回調函數 - 隱藏後台更新通知
     */
    public function hide_admin_updates_callback() {
        $value = get_option('wu_hide_admin_updates', false);
        ?>
        <input type="checkbox" 
               id="wu_hide_admin_updates" 
               name="wu_hide_admin_updates" 
               value="1" 
               <?php checked(1, $value); ?> />
        <label for="wu_hide_admin_updates">
            <?php esc_html_e('隱藏後台更新通知', 'wumetax-toolkit'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('勾選此選項將隱藏 WordPress 核心、外掛和佈景主題的更新通知。', 'wumetax-toolkit'); ?>
        </p>
        <?php
    }
    
    /**
     * 欄位回調函數 - 自訂前台頁尾文本
     */
    public function custom_frontend_footer_text_callback() {
        $value = get_option('wu_custom_frontend_footer_text', '');
        ?>
        <input type="text" 
               id="wu_custom_frontend_footer_text" 
               name="wu_custom_frontend_footer_text" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text" />
        <p class="description">
            <?php esc_html_e('輸入自訂的前台頁尾文本。留空則不顯示任何文本。', 'wumetax-toolkit'); ?>
        </p>
        <?php
    }
    
    /**
     * 欄位回調函數 - 內容複製保護
     */
    public function copy_protection_callback() {
        $enabled = get_option('wu_enable_copy_protection', false);
        $message = get_option('wu_copy_protection_message', '此網站已啟用內容保護,禁止複製與右鍵操作。');
        ?>
        <label>
            <input type="checkbox" 
                   name="wu_enable_copy_protection" 
                   value="1" 
                   <?php checked(1, $enabled); ?> />
            <?php esc_html_e('啟用前台內容複製保護', 'wumetax-toolkit'); ?>
        </label>
        <p style="margin-top:8px;">
            <label>
                <?php esc_html_e('警告訊息:', 'wumetax-toolkit'); ?><br>
                <input type="text" 
                       name="wu_copy_protection_message" 
                       value="<?php echo esc_attr($message); ?>" 
                       style="width:100%;max-width:520px;" />
            </label>
        </p>
        <p class="description">
            <?php esc_html_e('禁止右鍵、選取與常見快捷鍵(CTRL/⌘ + A/C/X/S/V),觸發時顯示警告。', 'wumetax-toolkit'); ?>
        </p>
        <?php
    }
    
    /**
     * 欄位回調函數 - 向其他管理員隱藏 WumetaxToolkit
     */
    public function hide_wumetax_toolkit_callback() {
        $value = get_option('wu_hide_wumetax_toolkit', false);
        $current_user = wp_get_current_user();
        ?>
        <input type="checkbox" 
               id="wu_hide_wumetax_toolkit" 
               name="wu_hide_wumetax_toolkit" 
               value="1" 
               <?php checked(1, $value); ?> />
        <label for="wu_hide_wumetax_toolkit">
            <?php esc_html_e('向其他管理員隱藏 WumetaxToolkit 外掛選單', 'wumetax-toolkit'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('啟用後,只有當前管理員能看到 WumetaxToolkit 選單。', 'wumetax-toolkit'); ?>
        </p>
        <p class="description">
            <strong><?php esc_html_e('當前管理員資訊:', 'wumetax-toolkit'); ?></strong> 
            <?php printf(
                esc_html__('ID: %1$s | 電子郵件: %2$s', 'wumetax-toolkit'),
                esc_html($current_user->ID),
                esc_html($current_user->user_email)
            ); ?>
        </p>
        <?php
    }
    
    /**
     * AJAX 儲存設定
     */
    public function ajax_save_settings() {
        // 驗證權限
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => '沒有權限執行此操作'));
            return;
        }
        
        // 驗證 nonce (修正欄位名稱)
        $nonce_value = isset($_POST[$this->nonce_field]) ? sanitize_text_field(wp_unslash($_POST[$this->nonce_field])) : '';
        
        if (empty($nonce_value) || !wp_verify_nonce($nonce_value, $this->nonce_action)) {
            wp_send_json_error(array('message' => '安全驗證失敗'));
            return;
        }
        
        // 處理布林值設定
        $boolean_settings = array(
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
        
        foreach ($boolean_settings as $setting) {
            update_option($setting, isset($_POST[$setting]) ? 1 : 0);
        }
        
        // 處理文字設定 (使用 sanitize_text_field)
        if (isset($_POST['wu_custom_admin_footer_text'])) {
            update_option('wu_custom_admin_footer_text', sanitize_text_field(wp_unslash($_POST['wu_custom_admin_footer_text'])));
        }
        
        if (isset($_POST['wu_custom_frontend_footer_text'])) {
            update_option('wu_custom_frontend_footer_text', sanitize_text_field(wp_unslash($_POST['wu_custom_frontend_footer_text'])));
        }
        
        if (isset($_POST['wu_copy_protection_message'])) {
            update_option('wu_copy_protection_message', sanitize_text_field(wp_unslash($_POST['wu_copy_protection_message'])));
        }
        
        // 處理使用者角色設定 (清理陣列)
        if (isset($_POST['wu_disabled_user_roles']) && is_array($_POST['wu_disabled_user_roles'])) {
            $sanitized_roles = array();
            foreach ($_POST['wu_disabled_user_roles'] as $role_key => $value) {
                $sanitized_roles[sanitize_key($role_key)] = 1;
            }
            update_option('wu_disabled_user_roles', $sanitized_roles);
        } else {
            update_option('wu_disabled_user_roles', array());
        }
        
        wp_send_json_success(array('message' => '設定已儲存!變更已立即生效。'));
    }
    
    /**
     * 管理頁面
     */
    public function admin_page() {
        // 驗證權限
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('您沒有足夠的權限訪問此頁面。', 'wumetax-toolkit'));
        }
        
        // 取得所有當前設定值 (使用 esc_html 輸出)
        $settings = $this->get_all_current_settings();
        
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('後台設定、WordPress 登入頁面美化(現代化透明玻璃質感設計)', 'wumetax-toolkit'); ?></h1>
            
            <div class="card">
                <h2><?php esc_html_e('當前狀態', 'wumetax-toolkit'); ?></h2>
                <table class="wu-status-table">
                    <?php $this->render_status_table($settings); ?>
                </table>
            </div>
            
            <form method="post" action="options.php" id="wu-admin-bar-form">
                <?php
                settings_fields($this->settings_group);
                do_settings_sections($this->settings_group);
                submit_button('儲存設定', 'primary', 'submit', false);
                ?>
            </form>
            
            <?php $this->render_feature_info(); ?>
        </div>
        <?php
    }
    
    /**
     * 取得所有當前設定值
     */
    private function get_all_current_settings() {
        return array(
            'remove_wp_logo' => get_option('wu_remove_wp_logo', false),
            'remove_new_content' => get_option('wu_remove_new_content', false),
            'hide_login_logo' => get_option('wu_hide_login_logo', false),
            'disable_language_switcher' => get_option('wu_disable_login_language_switcher', false),
            'enable_login_beautify' => get_option('wu_enable_login_beautify', false),
            'disable_all_widgets' => get_option('wu_disable_all_dashboard_widgets', false),
            'remove_admin_footer' => get_option('wu_remove_admin_footer_text', false),
            'custom_footer_text' => get_option('wu_custom_admin_footer_text', ''),
            'hide_tools_menu' => get_option('wu_hide_tools_menu', false),
            'hide_wordpress_address' => get_option('wu_hide_wordpress_address', false),
            'hide_site_address' => get_option('wu_hide_site_address', false),
            'hide_writing_settings' => get_option('wu_hide_writing_settings', false),
            'hide_privacy_settings' => get_option('wu_hide_privacy_settings', false),
            'custom_frontend_footer_text' => get_option('wu_custom_frontend_footer_text', ''),
            'hide_wumetax_toolkit' => get_option('wu_hide_wumetax_toolkit', false),
            'hide_admin_updates' => get_option('wu_hide_admin_updates', false),
            'disabled_roles' => get_option('wu_disabled_user_roles', array()),
            'enable_copy_protection' => get_option('wu_enable_copy_protection', false)
        );
    }
    
    /**
     * 渲染狀態表格
     */
    private function render_status_table($settings) {
        ?>
        <tr>
            <td><strong><?php esc_html_e('管理列 WordPress 標誌', 'wumetax-toolkit'); ?></strong></td>
            <td>
                <span class="<?php echo $settings['remove_wp_logo'] ? 'wu-status-hidden' : 'wu-status-visible'; ?>">
                    <?php echo $settings['remove_wp_logo'] ? esc_html__('已隱藏', 'wumetax-toolkit') : esc_html__('顯示中', 'wumetax-toolkit'); ?>
                </span>
            </td>
        </tr>
        <tr>
            <td><strong><?php esc_html_e('管理列新增項目', 'wumetax-toolkit'); ?></strong></td>
            <td>
                <span class="<?php echo $settings['remove_new_content'] ? 'wu-status-hidden' : 'wu-status-visible'; ?>">
                    <?php echo $settings['remove_new_content'] ? esc_html__('已隱藏', 'wumetax-toolkit') : esc_html__('顯示中', 'wumetax-toolkit'); ?>
                </span>
            </td>
        </tr>
        <tr>
            <td><strong><?php esc_html_e('登入頁面標誌', 'wumetax-toolkit'); ?></strong></td>
            <td>
                <span class="<?php echo $settings['hide_login_logo'] ? 'wu-status-hidden' : 'wu-status-visible'; ?>">
                    <?php echo $settings['hide_login_logo'] ? esc_html__('已隱藏', 'wumetax-toolkit') : esc_html__('顯示中', 'wumetax-toolkit'); ?>
                </span>
            </td>
        </tr>
        <tr>
            <td><strong><?php esc_html_e('登入語言切換器', 'wumetax-toolkit'); ?></strong></td>
            <td>
                <span class="<?php echo $settings['disable_language_switcher'] ? 'wu-status-disabled' : 'wu-status-enabled'; ?>">
                    <?php echo $settings['disable_language_switcher'] ? esc_html__('已停用', 'wumetax-toolkit') : esc_html__('已啟用', 'wumetax-toolkit'); ?>
                </span>
            </td>
        </tr>
        <tr>
            <td><strong><?php esc_html_e('登入頁面美化', 'wumetax-toolkit'); ?></strong></td>
            <td>
                <span class="<?php echo $settings['enable_login_beautify'] ? 'wu-status-enabled' : 'wu-status-disabled'; ?>">
                    <?php echo $settings['enable_login_beautify'] ? esc_html__('已啟用(現代化設計)', 'wumetax-toolkit') : esc_html__('已停用', 'wumetax-toolkit'); ?>
                </span>
            </td>
        </tr>
        <tr>
            <td><strong><?php esc_html_e('儀表板小工具', 'wumetax-toolkit'); ?></strong></td>
            <td>
                <span class="<?php echo $settings['disable_all_widgets'] ? 'wu-status-disabled' : 'wu-status-enabled'; ?>">
                    <?php echo $settings['disable_all_widgets'] ? esc_html__('已全部停用', 'wumetax-toolkit') : esc_html__('已啟用', 'wumetax-toolkit'); ?>
                </span>
            </td>
        </tr>
        <tr>
            <td><strong><?php esc_html_e('管理頁尾文本', 'wumetax-toolkit'); ?></strong></td>
            <td>
                <?php if ($settings['remove_admin_footer']): ?>
                    <span class="wu-status-hidden"><?php esc_html_e('已隱藏', 'wumetax-toolkit'); ?></span>
                <?php elseif (!empty($settings['custom_footer_text'])): ?>
                    <span class="wu-status-custom">
                        <?php printf(esc_html__('自訂:%s', 'wumetax-toolkit'), esc_html($settings['custom_footer_text'])); ?>
                    </span>
                <?php else: ?>
                    <span class="wu-status-visible"><?php esc_html_e('預設', 'wumetax-toolkit'); ?></span>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td><strong><?php esc_html_e('使用者角色管理', 'wumetax-toolkit'); ?></strong></td>
            <td>
                <?php if (!empty($settings['disabled_roles'])): ?>
                    <span class="wu-status-enabled">
                        <?php printf(esc_html__('已停用 %d 個角色', 'wumetax-toolkit'), count($settings['disabled_roles'])); ?>
                    </span>
                <?php else: ?>
                    <span class="wu-status-disabled"><?php esc_html_e('無停用角色', 'wumetax-toolkit'); ?></span>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td><strong><?php esc_html_e('後台 Tools 選單', 'wumetax-toolkit'); ?></strong></td>
            <td>
                <span class="<?php echo $settings['hide_tools_menu'] ? 'wu-status-hidden' : 'wu-status-visible'; ?>">
                    <?php echo $settings['hide_tools_menu'] ? esc_html__('已隱藏', 'wumetax-toolkit') : esc_html__('顯示中', 'wumetax-toolkit'); ?>
                </span>
            </td>
        </tr>
        <tr>
            <td><strong><?php esc_html_e('後台更新通知', 'wumetax-toolkit'); ?></strong></td>
            <td>
                <span class="<?php echo $settings['hide_admin_updates'] ? 'wu-status-hidden' : 'wu-status-visible'; ?>">
                    <?php echo $settings['hide_admin_updates'] ? esc_html__('已隱藏', 'wumetax-toolkit') : esc_html__('顯示中', 'wumetax-toolkit'); ?>
                </span>
            </td>
        </tr>
        <tr>
            <td><strong><?php esc_html_e('前台頁尾文本', 'wumetax-toolkit'); ?></strong></td>
            <td>
                <?php if (!empty($settings['custom_frontend_footer_text'])): ?>
                    <span class="wu-status-custom">
                        <?php printf(esc_html__('自訂:%s', 'wumetax-toolkit'), esc_html($settings['custom_frontend_footer_text'])); ?>
                    </span>
                <?php else: ?>
                    <span class="wu-status-visible"><?php esc_html_e('預設', 'wumetax-toolkit'); ?></span>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td><strong><?php esc_html_e('內容複製保護', 'wumetax-toolkit'); ?></strong></td>
            <td>
                <span class="<?php echo $settings['enable_copy_protection'] ? 'wu-status-enabled' : 'wu-status-disabled'; ?>">
                    <?php echo $settings['enable_copy_protection'] ? esc_html__('已啟用', 'wumetax-toolkit') : esc_html__('已停用', 'wumetax-toolkit'); ?>
                </span>
            </td>
        </tr>
        <tr>
            <td><strong><?php esc_html_e('WumetaxToolkit 選單隱藏', 'wumetax-toolkit'); ?></strong></td>
            <td>
                <span class="<?php echo $settings['hide_wumetax_toolkit'] ? 'wu-status-enabled' : 'wu-status-disabled'; ?>">
                    <?php echo $settings['hide_wumetax_toolkit'] ? esc_html__('已啟用(僅當前管理員可見)', 'wumetax-toolkit') : esc_html__('已停用(所有管理員可見)', 'wumetax-toolkit'); ?>
                </span>
            </td>
        </tr>
        <?php
    }
    
    /**
     * 渲染功能說明
     */
    private function render_feature_info() {
        ?>
        <div class="card">
            <h2><?php esc_html_e('功能說明', 'wumetax-toolkit'); ?></h2>
            
            <h3><?php esc_html_e('新增與更新功能', 'wumetax-toolkit'); ?></h3>
            <ul>
                <li><?php esc_html_e('登入頁面美化升級:採用現代化透明玻璃質感設計,漸層背景,流暢動畫效果', 'wumetax-toolkit'); ?></li>
                <li><?php esc_html_e('儀表板小工具一鍵管理:簡化操作,一鍵停用所有儀表板小工具', 'wumetax-toolkit'); ?></li>
                <li><?php esc_html_e('使用者角色管理:可選擇停用特定使用者角色', 'wumetax-toolkit'); ?></li>
                <li><?php esc_html_e('內容複製保護:防止前台內容被複製(僅基本防禦)', 'wumetax-toolkit'); ?></li>
            </ul>
            
            <h3><?php esc_html_e('注意事項', 'wumetax-toolkit'); ?></h3>
            <ul>
                <li><?php esc_html_e('所有功能都可以隨時啟用或停用', 'wumetax-toolkit'); ?></li>
                <li><?php esc_html_e('不會影響網站的正常運作和核心功能', 'wumetax-toolkit'); ?></li>
                <li><?php esc_html_e('僅影響管理後台的外觀和功能', 'wumetax-toolkit'); ?></li>
                <li><?php esc_html_e('建議在客戶網站中使用這些功能', 'wumetax-toolkit'); ?></li>
                <li><?php esc_html_e('停用使用者角色不會影響現有使用者', 'wumetax-toolkit'); ?></li>
            </ul>
        </div>
        <?php
    }
    
    /**
     * 載入所有啟用的功能
     */
    public function load_features() {
        // 僅在適當的環境載入功能
        
        // 管理列功能 (前台+後台)
        if (get_option('wu_remove_wp_logo', false)) {
            add_action('admin_bar_menu', array($this, 'remove_wp_logo_from_admin_bar'), 999);
        }
        
        if (get_option('wu_remove_new_content', false)) {
            add_action('admin_bar_menu', array($this, 'remove_new_content_from_admin_bar'), 999);
        }
        
        // 登入頁面功能
        if (get_option('wu_hide_login_logo', false)) {
            add_action('login_head', array($this, 'hide_login_logo'));
        }
        
        if (get_option('wu_disable_login_language_switcher', false)) {
            add_filter('login_display_language_dropdown', '__return_false');
        }
        
        if (get_option('wu_enable_login_beautify', false)) {
            add_action('login_enqueue_scripts', array($this, 'enqueue_login_beautify'));
        }
        
        // 儀表板功能 (僅後台)
        if (is_admin()) {
            if (get_option('wu_disable_all_dashboard_widgets', false)) {
                add_action('wp_dashboard_setup', array($this, 'disable_dashboard_widgets'));
            }
            
            // 管理頁尾文本
            if (get_option('wu_remove_admin_footer_text', false)) {
                add_filter('admin_footer_text', '__return_empty_string');
                add_filter('update_footer', '__return_empty_string', 11);
            } elseif (!empty(get_option('wu_custom_admin_footer_text', ''))) {
                add_filter('admin_footer_text', array($this, 'custom_admin_footer_text'));
                add_filter('update_footer', '__return_empty_string', 11);
            }
            
            // 隱藏後台選單和設定
            if (get_option('wu_hide_tools_menu', false)) {
                add_action('admin_menu', array($this, 'hide_tools_menu'));
            }
            
            if (get_option('wu_hide_wordpress_address', false) || 
                get_option('wu_hide_site_address', false) || 
                get_option('wu_hide_writing_settings', false) || 
                get_option('wu_hide_privacy_settings', false)) {
                add_action('admin_head', array($this, 'hide_admin_settings'));
            }
            
            if (get_option('wu_hide_wumetax_toolkit', false)) {
                add_action('admin_menu', array($this, 'hide_wumetax_toolkit_menu'), 999);
            }
            
            if (get_option('wu_hide_admin_updates', false)) {
                $this->hide_admin_updates();
            }
        }
        
        // 前台功能
        if (!is_admin()) {
            if (!empty(get_option('wu_custom_frontend_footer_text', ''))) {
                add_action('wp_footer', array($this, 'custom_frontend_footer_text'));
            }
            
            if (get_option('wu_enable_copy_protection', false)) {
                add_action('wp_enqueue_scripts', array($this, 'enqueue_copy_protection'));
            }
        }
        
        // 使用者角色限制 (前台+後台)
        $this->apply_user_role_restrictions();
    }
    
    /**
     * 隱藏登入頁面標誌
     */
    public function hide_login_logo() {
        echo '<style>
            .login h1 a,
            .login h1 {
                display: none !important;
            }
        </style>';
    }
    
    /**
     * 註冊並載入登入頁面美化樣式
     */
    public function enqueue_login_beautify() {
        wp_enqueue_style('wu-login-beautify', false);
        wp_add_inline_style('wu-login-beautify', $this->get_login_beautify_css());
    }
    
    /**
     * 取得登入頁面美化 CSS
     */
    private function get_login_beautify_css() {
        return '
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
            background: url("data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23e3e6ea\' fill-opacity=\'0.02\'%3E%3Ccircle cx=\'7\' cy=\'7\' r=\'7\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") !important;
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
        }
        
        .login form .input,
        .login input[type=text],
        .login input[type=password] {
            background: rgba(255, 255, 255, 0.95) !important;
            border: 1px solid rgba(0, 0, 0, 0.15) !important;
            border-radius: 5px !important;
            color: #333 !important;
            font-size: 16px !important;
            padding: 15px 18px !important;
            height: 48px !important;
            transition: all 0.3s ease !important;
        }
        
        .login form .input:focus,
        .login input[type=text]:focus,
        .login input[type=password]:focus {
            background: rgba(255, 255, 255, 1) !important;
            border-color: rgba(0, 0, 0, 0.3) !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
            outline: none !important;
        }
        
        .login .button-primary {
            background: #000000 !important;
            border: none !important;
            border-radius: 5px !important;
            color: #ffffff !important;
            font-size: 16px !important;
            font-weight: 500 !important;
            padding: 15px 30px !important;
            height: 48px !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2) !important;
            transition: all 0.3s ease !important;
            width: 100% !important;
        }
        
        .login .button-primary:hover {
            background: #333333 !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3) !important;
        }
        ';
    }
    
    /**
     * 一鍵停用所有儀表板小工具
     */
    public function disable_dashboard_widgets() {
        remove_meta_box('welcome-panel', 'dashboard', 'normal');
        remove_action('welcome_panel', 'wp_welcome_panel');
        remove_meta_box('dashboard_primary', 'dashboard', 'side');
        remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
        remove_meta_box('dashboard_right_now', 'dashboard', 'normal');
        remove_meta_box('dashboard_activity', 'dashboard', 'normal');
        remove_meta_box('dashboard_site_health', 'dashboard', 'normal');
        remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
        remove_meta_box('dashboard_incoming_links', 'dashboard', 'normal');
        remove_meta_box('dashboard_plugins', 'dashboard', 'normal');
        remove_meta_box('dashboard_recent_drafts', 'dashboard', 'side');
        remove_meta_box('dashboard_secondary', 'dashboard', 'side');
        remove_action('admin_notices', 'wp_dashboard_php_nag_notice');
    }
    
    /**
     * 應用使用者角色限制
     */
    private function apply_user_role_restrictions() {
        $disabled_roles = get_option('wu_disabled_user_roles', array());
        
        if (empty($disabled_roles)) {
            return;
        }
        
        // 阻止註冊時選擇被停用的角色
        add_filter('editable_roles', function($roles) use ($disabled_roles) {
            foreach ($disabled_roles as $role_key => $disabled) {
                if ($disabled) {
                    unset($roles[$role_key]);
                }
            }
            return $roles;
        });
        
        // 在後台隱藏被停用的角色選擇器
        if (is_admin()) {
            add_action('admin_head-users.php', function() use ($disabled_roles) {
                $this->output_role_restriction_js($disabled_roles);
            });
            
            add_action('admin_head-user-edit.php', function() use ($disabled_roles) {
                $this->output_role_restriction_js($disabled_roles);
            });
        }
    }
    
    /**
     * 輸出角色限制 JavaScript
     */
    private function output_role_restriction_js($disabled_roles) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            <?php foreach ($disabled_roles as $role_key => $disabled): ?>
                <?php if ($disabled): ?>
                    $('select[name="role"] option[value="<?php echo esc_js($role_key); ?>"]').remove();
                    $('select[name="new_role"] option[value="<?php echo esc_js($role_key); ?>"]').remove();
                <?php endif; ?>
            <?php endforeach; ?>
        });
        </script>
        <?php
    }
    
    /**
     * 自訂管理頁尾文本
     */
    public function custom_admin_footer_text() {
        return esc_html(get_option('wu_custom_admin_footer_text', ''));
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
        ?>
        <style>
        <?php if (get_option('wu_hide_wordpress_address', false)): ?>
            tr:has(th label[for="home"]),
            #home_row,
            tr.home {
                display: none !important;
            }
        <?php endif; ?>
        
        <?php if (get_option('wu_hide_site_address', false)): ?>
            tr:has(th label[for="siteurl"]),
            #siteurl_row,
            tr.siteurl {
                display: none !important;
            }
        <?php endif; ?>
        
        <?php if (get_option('wu_hide_writing_settings', false)): ?>
            #menu-settings ul li:has(a[href="options-writing.php"]) {
                display: none !important;
            }
        <?php endif; ?>
        
        <?php if (get_option('wu_hide_privacy_settings', false)): ?>
            #menu-settings ul li:has(a[href="options-privacy.php"]) {
                display: none !important;
            }
        <?php endif; ?>
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            <?php if (get_option('wu_hide_wordpress_address', false)): ?>
                $('label[for="home"]').closest('tr').hide();
                $('#home').closest('tr').hide();
            <?php endif; ?>
            
            <?php if (get_option('wu_hide_site_address', false)): ?>
                $('label[for="siteurl"]').closest('tr').hide();
                $('#siteurl').closest('tr').hide();
            <?php endif; ?>
            
            <?php if (get_option('wu_hide_writing_settings', false)): ?>
                $('#menu-settings').find('a[href="options-writing.php"]').parent().hide();
            <?php endif; ?>
            
            <?php if (get_option('wu_hide_privacy_settings', false)): ?>
                $('#menu-settings').find('a[href="options-privacy.php"]').parent().hide();
            <?php endif; ?>
        });
        </script>
        <?php
    }
    
    /**
     * 自訂前台頁尾文本
     */
    public function custom_frontend_footer_text() {
        $text = get_option('wu_custom_frontend_footer_text', '');
        if (!empty($text)) {
            echo '<div style="text-align: center; padding: 20px; margin-top: 30px; border-top: 1px solid #eee;">' 
                . esc_html($text) 
                . '</div>';
        }
    }
    
    /**
     * 隱藏 WumetaxToolkit 選單(僅對其他管理員)
     */
    public function hide_wumetax_toolkit_menu() {
        $setting_admin_id = get_option('wu_hide_wumetax_toolkit_admin_id', 0);
        $current_user_id = get_current_user_id();
        
        // 如果還沒設定管理員 ID,則設定為當前用戶
        if (!$setting_admin_id) {
            update_option('wu_hide_wumetax_toolkit_admin_id', $current_user_id);
            $setting_admin_id = $current_user_id;
        }
        
        // 如果當前用戶不是設定此功能的管理員,則隱藏選單
        if ($current_user_id != $setting_admin_id) {
            remove_menu_page('wumetax-toolkit');
        }
    }
    
    /**
     * 從管理列中移除 WordPress 標誌
     */
    public function remove_wp_logo_from_admin_bar($wp_admin_bar) {
        $wp_admin_bar->remove_node('wp-logo');
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
        $wp_admin_bar->remove_node('new-content');
        $wp_admin_bar->remove_node('new-post');
        $wp_admin_bar->remove_node('new-media');
        $wp_admin_bar->remove_node('new-page');
        $wp_admin_bar->remove_node('new-user');
    }
    
    /**
     * 隱藏後台更新通知
     */
    private function hide_admin_updates() {
        add_filter('pre_site_transient_update_core', '__return_null');
        add_filter('pre_site_transient_update_plugins', '__return_null');
        add_filter('pre_site_transient_update_themes', '__return_null');
        add_action('admin_menu', array($this, 'remove_update_notifications'));
        add_action('wp_before_admin_bar_render', array($this, 'remove_admin_bar_updates'));
        add_action('admin_head', array($this, 'hide_update_nag'));
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
        if ($wp_admin_bar) {
            $wp_admin_bar->remove_menu('updates');
        }
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
