# WU工具箱 (WU Toolbox)

![Version](https://img.shields.io/badge/version-3.2-blue.svg)
![License](https://img.shields.io/badge/license-GPL--2.0-green.svg)
![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)

一個功能強大且全面的 WordPress 工具箱插件，由 **Wumetax** 開發，整合了多個實用的管理功能模組，幫助您更好地管理和優化您的 WordPress 網站。

## 🚀 主要功能

### 後台設定與美化
- **後台設定管理**：隱藏不必要的後台選單項目
- **WordPress 登入頁面美化**：採用 Apple Liquid Glass 風格的透明白色設計
- **Tools 選單隱藏**：可選擇隱藏後台 Tools 選單
- **General Settings 選項隱藏**：可隱藏 WordPress Address (URL)、Site Address (URL)、Writing Settings、Privacy 設定
- **自訂頁尾文本**：自訂前台和後台頁尾顯示文字

### 安全功能
- **隱藏登入頁面**：自訂登入 URL，隱藏預設的 wp-login.php
- **登入限制器**：防止暴力破解攻擊
- **XML-RPC 安全**：停用或保護 XML-RPC 功能
- **稽核日誌**：記錄重要的系統活動

### WooCommerce 優化
- **WooCommerce 優化器**：清理和優化 WooCommerce 設定，顯示商品購買量（真實 + 調整），短代碼 [wu_sales]
- **移除不必要的功能**：停用行銷中心、市場建議等
- **效能提升**：減少不必要的腳本載入

### 內容管理
- **評論管理器**：批量管理和清理評論
- **版本管理器**：控制文章修訂版本數量
- **內容複製器**：快速複製文章和頁面
- **媒體編碼器**：JPEG/PNG → WebP 自動轉換，包含智能回退機制與縮圖管理（重新產生縮圖、停用尺寸、清理未使用圖像）
- **文章瀏覽量**：真實瀏覽量與管理員調整，短代碼 [wu_views]，後台欄位顯示

### 系統管理
- **更新管理器**：控制 WordPress 核心、主題和外掛更新
- **暫存管理器**：清理 WordPress 暫存資料
- **系統監控器**：監控網站效能和狀態
- **使用者管理**：增強的使用者管理功能

### 其他實用功能
- **404 錯誤重新導向**：自動重新導向 404 錯誤頁面
- **RSS 停用器**：控制 RSS 摘要功能
- **頭部/頁尾代碼**：輕鬆新增自訂代碼
- **外掛下載器**：從 WordPress.org 下載外掛
- **搬家模式**：網站維護模式

## 📋 系統需求

- WordPress 5.0 或更高版本
- PHP 7.4 或更高版本
- MySQL 5.6 或更高版本

## 🔧 安裝說明

1. 將整個 `wu-toolbox` 資料夾上傳到 `/wp-content/plugins/` 目錄
2. 在 WordPress 後台啟用「WU工具箱」外掛
3. 在後台選單中找到「WU工具箱」開始使用各項功能

## 🎨 主要特色

### Apple Liquid Glass 風格登入頁面
採用現代化的玻璃質感設計，包含：
- 透明白色背景與模糊效果
- 漸變色彩配置
- 流暢的動畫過渡
- 響應式設計，支援各種螢幕尺寸

### 智能衝突解決
- 自動檢測並解決 404 重新導向與隱藏登入頁面之間的衝突
- 優化的模組載入順序
- 相容性增強

### 使用者友好的介面
- 直觀的設定頁面
- 即時狀態顯示
- 詳細的功能說明
- 一鍵啟用/停用功能

## 🛡️ 安全性

本插件經過嚴格的安全性測試，包含：
- 輸入資料驗證和清理
- SQL 注入防護
- XSS 攻擊防護
- 適當的權限檢查

## 🔄 更新日誌

### 版本 3.2
- **使用者角色管理增強**：admin-bar-cleaner.php 新增自動偵測所有使用者角色功能，可選擇停用除了 subscriber 和 administrator 之外的所有角色
- **一鍵隱藏使用者設定選項**：enhanced-user-list.php 新增一鍵隱藏功能，可快速隱藏 Personal Options、About the user、Application Passwords、Elementor AI、社交媒體設定等選項
- **外掛效能監控**：system-monitor.php 新增外掛程式效能監控功能，可即時監測各外掛的載入時間和記憶體使用量
- **常用外掛管理優化**：移除 Disable Comments、Loggedin – Limit Active Logins、ThumbPress、User Switching、Username Changer 等外掛管理，優化核心功能
- **程式碼優化**：提升整體程式碼品質，減少衝突，改善網站效能表現

### 版本 3.1
- 新增 4 位數字驗證碼於登入/註冊/重設密碼/WooCommerce 帳戶表單（無 Session、無 Cookie、隱私友善）
- 媒體編碼器：新增縮圖尺寸管理（停用尺寸）、重新產生縮圖、掃描/刪除未使用圖像
- 後台介面：新增內容複製保護（禁用右鍵與常見快捷鍵，提示訊息）
- 使用者：修正自訂頭像選擇器（確保媒體庫彈窗可用），強化隱藏個人設定
- 文章：新增瀏覽量統計（真實 + 調整），短代碼與後台欄顯示
- WooCommerce：商品購買量（真實 + 調整），商品頁顯示與短代碼、後台欄位

### 版本 3.0
- **新增 WebP 自動回退功能**：媒體編碼器現在支援智能圖片回退，當請求 PNG/JPG 但只有 WebP 存在時自動重新導向
- **管理員權限控制**：新增向其他管理員隱藏 WumetaxToolkit 外掛的功能
- **即時設定儲存**：所有功能設定現在支援即時儲存，無需重新整理頁面
- **登入頁面美化優化**：改進 Apple Liquid Glass 風格，已停用狀態現在顯示紅色標記
- **自動外掛導向**：啟用外掛後自動導向常用外掛管理頁面
- **設定提醒優化**：自訂管理頁尾文本現在包含重要使用說明
- **WordPress 更新控制增強**：核心更新控制現在會自動影響主題和外掛更新
- **使用者介面改進**：增強的使用者列表現在正確隱藏 profile.php 中的個人選項
- **代碼輸入框美化**：隱藏登入頁面的代碼輸入框採用 Termius 黑色風格

### 版本 2.0
- 重新設計後台設定介面
- 新增 Apple Liquid Glass 風格登入頁面美化
- 改進 404 重新導向與隱藏登入頁面的相容性
- 修復 WooCommerce 優化器的系統狀態載入錯誤
- 新增前台頁尾自訂功能
- 優化程式碼結構和效能

## 👨‍💻 作者資訊

**Wumetax**
- 官方網站：[https://wumetax.com/](https://wumetax.com/)
- 專業的 WordPress 開發者
- 致力於創造高品質的 WordPress 解決方案

## 📞 支援與回饋

如果您在使用過程中遇到任何問題或有改進建議，歡迎聯繫我們：

- 🌐 官方網站：[https://wumetax.com/](https://wumetax.com/)
- 📧 技術支援：透過官方網站聯繫表單

## 📄 授權條款

本插件採用 GPL-2.0+ 授權條款。詳細資訊請參閱 [LICENSE](https://www.gnu.org/licenses/gpl-2.0.txt) 文件。

## 🙏 致謝

感謝所有使用本插件的用戶，您的回饋和建議是我們持續改進的動力。

---

**讓 WU工具箱 成為您 WordPress 網站管理的得力助手！**