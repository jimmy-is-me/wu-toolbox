<?php
/**
 * XML-RPC 安全模組
 * 功能：禁用 XML-RPC 以減少暴力攻擊和漏洞利用
 */

if (!defined('ABSPATH')) exit;

class WU_XMLRPC_Security {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        
        // 如果啟用了禁用 XML-RPC，則執行相關動作
        if (get_option('wu_disable_xmlrpc', false)) {
            $this->disable_xmlrpc();
        }
    }
    
    /**
     * 添加管理頁面
     */
    public function add_admin_menu() {
        add_submenu_page(
            'wu-toolbox',
            'XML-RPC 安全',
            'XML-RPC 安全',
            'manage_options',
            'wu-xmlrpc-security',
            array($this, 'admin_page')
        );
    }
    
    /**
     * 初始化設定
     */
    public function admin_init() {
        register_setting('wu_xmlrpc_settings', 'wu_disable_xmlrpc');
        
        add_settings_section(
            'wu_xmlrpc_section',
            'XML-RPC 安全設定',
            array($this, 'settings_section_callback'),
            'wu_xmlrpc_settings'
        );
        
        add_settings_field(
            'wu_disable_xmlrpc',
            '禁用 XML-RPC',
            array($this, 'disable_xmlrpc_callback'),
            'wu_xmlrpc_settings',
            'wu_xmlrpc_section'
        );
    }
    
    /**
     * 設定區域說明
     */
    public function settings_section_callback() {
        echo '<p>XML-RPC 是 WordPress 的一個功能，允許遠程發布和管理內容。但它也可能被惡意利用進行暴力攻擊。</p>';
        echo '<p><strong>建議：</strong>如果您不需要遠程發布功能，建議禁用 XML-RPC 以提高安全性。</p>';
    }
    
    /**
     * 禁用 XML-RPC 選項回調
     */
    public function disable_xmlrpc_callback() {
        $value = get_option('wu_disable_xmlrpc', false);
        echo '<input type="checkbox" id="wu_disable_xmlrpc" name="wu_disable_xmlrpc" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="wu_disable_xmlrpc">啟用 XML-RPC 禁用功能</label>';
        echo '<p class="description">勾選此選項將完全禁用 WordPress 的 XML-RPC 功能，防止相關的安全漏洞和暴力攻擊。</p>';
    }
    
    /**
     * 管理頁面
     */
    public function admin_page() {
        if (isset($_POST['submit'])) {
            check_admin_referer('wu_xmlrpc_settings-options');
            
            // 處理表單提交
            update_option('wu_disable_xmlrpc', isset($_POST['wu_disable_xmlrpc']) ? 1 : 0);
            
            echo '<div class="notice notice-success"><p>設定已儲存！</p></div>';
        }
        
        $xmlrpc_status = $this->check_xmlrpc_status();
        ?>
        <div class="wrap">
            <h1>XML-RPC 安全設定</h1>
            
            <div class="card">
                <h2>當前狀態</h2>
                <p><strong>XML-RPC 狀態：</strong> 
                    <span class="<?php echo $xmlrpc_status['enabled'] ? 'wu-status-enabled' : 'wu-status-disabled'; ?>">
                        <?php echo $xmlrpc_status['status']; ?>
                    </span>
                </p>
                <p><strong>XML-RPC URL：</strong> <?php echo site_url('/xmlrpc.php'); ?></p>
            </div>
            
            <form method="post" action="">
                <?php
                settings_fields('wu_xmlrpc_settings');
                do_settings_sections('wu_xmlrpc_settings');
                wp_nonce_field('wu_xmlrpc_settings-options');
                submit_button();
                ?>
            </form>
            
            <div class="card">
                <h2>功能說明</h2>
                <h3>什麼是 XML-RPC？</h3>
                <ul>
                    <li>XML-RPC 是 WordPress 的遠程程序調用協議</li>
                    <li>允許第三方應用程式與 WordPress 進行通信</li>
                    <li>支持遠程發布、管理文章和評論等功能</li>
                </ul>
                
                <h3>為什麼要禁用？</h3>
                <ul>
                    <li><strong>防止暴力攻擊：</strong>攻擊者可能利用 XML-RPC 進行密碼暴力破解</li>
                    <li><strong>減少 DDoS 攻擊：</strong>XML-RPC 可能被用於放大 DDoS 攻擊</li>
                    <li><strong>防止資訊洩露：</strong>某些 XML-RPC 方法可能洩露敏感資訊</li>
                    <li><strong>減少伺服器負載：</strong>惡意請求會增加伺服器負擔</li>
                </ul>
                
                <h3>技術實現</h3>
                <ul>
                    <li>使用 <code>xmlrpc_enabled</code> 過濾器完全禁用 XML-RPC</li>
                    <li>移除 <code>wp_xmlrpc_server_class</code> 以防止加載 XML-RPC 服務器</li>
                    <li>乾淨、有效的實現，不會影響網站其他功能</li>
                </ul>
            </div>
        </div>
        
        <style>
        .wu-status-enabled { color: #d63638; font-weight: bold; }
        .wu-status-disabled { color: #00a32a; font-weight: bold; }
        .card { background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin: 20px 0; }
        .card h2 { margin-top: 0; }
        .card h3 { color: #23282d; }
        .card ul { margin-left: 20px; }
        </style>
        <?php
    }
    
    /**
     * 檢查 XML-RPC 狀態
     */
    private function check_xmlrpc_status() {
        $enabled = apply_filters('xmlrpc_enabled', true);
        
        return array(
            'enabled' => $enabled,
            'status' => $enabled ? '啟用' : '已禁用'
        );
    }
    
    /**
     * 禁用 XML-RPC 功能
     */
    private function disable_xmlrpc() {
        // 禁用 XML-RPC
        add_filter('xmlrpc_enabled', '__return_false');
        
        // 移除 XML-RPC 服務器類
        add_filter('wp_xmlrpc_server_class', '__return_false');
        
        // 移除 RSD 鏈接
        remove_action('wp_head', 'rsd_link');
        
        // 移除 Windows Live Writer 支持
        remove_action('wp_head', 'wlwmanifest_link');
        
        // 阻止 XML-RPC 請求
        add_action('init', array($this, 'block_xmlrpc_requests'));
    }
    
    /**
     * 阻止 XML-RPC 請求
     */
    public function block_xmlrpc_requests() {
        if (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) {
            http_response_code(403);
            exit('XML-RPC services are disabled on this site.');
        }
    }
}

// 初始化模組
new WU_XMLRPC_Security();