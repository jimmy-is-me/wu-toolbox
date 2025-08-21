<?php
if (!defined('ABSPATH')) exit;

/* === 後台子選單：開發常用外掛 === */
function dev_common_plugins_menu() {
    add_submenu_page(
        'wu-toolbox',            // 父選單 slug
        '開發常用外掛',           // 頁面標題
        '開發常用外掛',           // 選單標題
        'manage_options',        // 權限
        'dev-common-plugins',    // slug
        'dev_common_plugins_page' // 回調函數
    );
}
add_action('admin_menu', 'dev_common_plugins_menu');

/* === 後台頁面內容 === */
function dev_common_plugins_page() {
    ?>
    <div class="wrap">
        <h1>開發常用外掛</h1>
        <p>這裡列出開發中常用的 WordPress 外掛，方便快速安裝與管理：</p>
        <ul>
            <li><strong>ACF Pro</strong> - 高級自訂欄位管理</li>
            <li><strong>WP All Import</strong> - 匯入資料工具</li>
            <li><strong>Query Monitor</strong> - 偵錯與效能分析</li>
            <li><strong>WooCommerce</strong> - 電商功能</li>
            <li><strong>WP Rocket</strong> - 網站快取與優化</li>
        </ul>
        <p>你可以依照專案需求自行增加更多常用外掛連結或安裝程式碼。</p>
    </div>
    <?php
}
