<?php
/**
 * 內容重複模組
 * 一鍵複製頁面、貼文和自訂貼文的功能
 * 版本:2.1 - Elementor CSS 更新、WooCommerce 支援、SEO 優化
 */

if (!defined('ABSPATH')) exit;

class WU_Content_Duplicator {
    
    private $settings;
    private $option_name = 'wu_content_duplicator_settings';
    
    /**
     * Elementor 相關的元數據鍵值
     */
    private $elementor_meta_keys = array(
        '_elementor_data',
        '_elementor_edit_mode',
        '_elementor_template_type',
        '_elementor_version',
        '_elementor_pro_version',
        '_elementor_page_settings',
        '_elementor_controls_usage',
        '_elementor_css'
    );
    
    /**
     * WooCommerce 產品需要排除的元數據
     */
    private $woocommerce_excluded_meta = array(
        '_sku',
        'total_sales',
        '_wc_average_rating',
        '_wc_rating_count',
        '_wc_review_count'
    );
    
    /**
     * SEO 外掛需要排除的統計數據
     */
    private $seo_excluded_meta = array(
        '_yoast_wpseo_linkdex',
        '_yoast_wpseo_content_score',
        'rank_math_internal_links_processed',
        'rank_math_analytic_object_id'
    );
    
    public function __construct() {
        $this->settings = get_option($this->option_name, $this->get_default_settings());
        
        // 後台功能
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'), 35);
            add_action('admin_init', array($this, 'register_settings'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        }
        
        // 複製功能
        add_action('post_row_actions', array($this, 'add_duplicate_link'), 10, 2);
        add_action('page_row_actions', array($this, 'add_duplicate_link'), 10, 2);
        add_action('admin_action_wu_duplicate_post', array($this, 'duplicate_post'));
        add_action('wp_ajax_wu_duplicate_post', array($this, 'ajax_duplicate_post'));
        
        // 支援自訂貼文類型
        add_action('init', array($this, 'add_custom_post_type_support'));
    }
    
    /**
     * 預設設定
     */
    private function get_default_settings() {
        return array(
            'enabled' => false,
            'copy_metadata' => true,
            'copy_taxonomies' => true,
            'copy_featured_image' => true,
            'excluded_post_types' => array('attachment', 'elementor_library', 'wc_product_tab', 'shop_order', 'shop_coupon'),
            'excluded_meta_keys' => array('_edit_lock', '_edit_last', '_wp_old_slug'),
            'title_prefix' => '副本 - ',
            'slug_suffix' => '-copy',
            'status_after_duplicate' => 'draft',
            'regenerate_elementor_css' => true,
            'exclude_woocommerce_data' => true,
            'exclude_seo_stats' => true
        );
    }
    
    /**
     * 註冊設定
     */
    public function register_settings() {
        register_setting(
            'wu_content_duplicator_group',
            $this->option_name,
            array($this, 'sanitize_settings')
        );
        
        add_settings_section(
            'wu_content_duplicator_section',
            '內容重複設定',
            array($this, 'section_callback'),
            'wu-content-duplicator'
        );
        
        add_settings_field(
            'enabled',
            '啟用功能',
            array($this, 'enabled_field_callback'),
            'wu-content-duplicator',
            'wu_content_duplicator_section'
        );
        
        add_settings_field(
            'copy_options',
            '複製選項',
            array($this, 'copy_options_field_callback'),
            'wu-content-duplicator',
            'wu_content_duplicator_section'
        );
        
        add_settings_field(
            'title_settings',
            '標題與網址設定',
            array($this, 'title_settings_field_callback'),
            'wu-content-duplicator',
            'wu_content_duplicator_section'
        );
        
        add_settings_field(
            'status_after_duplicate',
            '複製後狀態',
            array($this, 'status_field_callback'),
            'wu-content-duplicator',
            'wu_content_duplicator_section'
        );
        
        add_settings_field(
            'advanced_options',
            '進階選項',
            array($this, 'advanced_options_field_callback'),
            'wu-content-duplicator',
            'wu_content_duplicator_section'
        );
        
        add_settings_field(
            'excluded_post_types',
            '排除的貼文類型',
            array($this, 'excluded_post_types_field_callback'),
            'wu-content-duplicator',
            'wu_content_duplicator_section'
        );
        
        add_settings_field(
            'excluded_meta_keys',
            '排除的元數據鍵',
            array($this, 'excluded_meta_keys_field_callback'),
            'wu-content-duplicator',
            'wu_content_duplicator_section'
        );
    }
    
    /**
     * 清理設定
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        $sanitized['enabled'] = !empty($input['enabled']) ? true : false;
        $sanitized['copy_metadata'] = !empty($input['copy_metadata']) ? true : false;
        $sanitized['copy_taxonomies'] = !empty($input['copy_taxonomies']) ? true : false;
        $sanitized['copy_featured_image'] = !empty($input['copy_featured_image']) ? true : false;
        $sanitized['regenerate_elementor_css'] = !empty($input['regenerate_elementor_css']) ? true : false;
        $sanitized['exclude_woocommerce_data'] = !empty($input['exclude_woocommerce_data']) ? true : false;
        $sanitized['exclude_seo_stats'] = !empty($input['exclude_seo_stats']) ? true : false;
        
        $sanitized['excluded_post_types'] = !empty($input['excluded_post_types']) 
            ? array_map('sanitize_text_field', $input['excluded_post_types']) 
            : array();
        
        $excluded_meta_keys = !empty($input['excluded_meta_keys']) 
            ? $input['excluded_meta_keys'] 
            : '';
        $sanitized['excluded_meta_keys'] = array_filter(array_map('trim', explode("\n", $excluded_meta_keys)));
        
        $sanitized['title_prefix'] = sanitize_text_field($input['title_prefix']);
        $sanitized['slug_suffix'] = sanitize_title($input['slug_suffix']);
        $sanitized['status_after_duplicate'] = sanitize_text_field($input['status_after_duplicate']);
        
        return $sanitized;
    }
    
    /**
     * 載入後台資源
     */
    public function enqueue_admin_assets($hook) {
        // 僅在特定頁面載入
        if (!in_array($hook, array('edit.php', 'post.php', 'post-new.php', 'wumetaxtoolkit_page_wu-content-duplicator'))) {
            return;
        }
        
        // 載入 CSS
        wp_enqueue_style(
            'wu-content-duplicator',
            false,
            array(),
            '2.1'
        );
        
        wp_add_inline_style('wu-content-duplicator', $this->get_admin_css());
        
        // 在列表頁載入 JavaScript
        if ($hook === 'edit.php') {
            wp_enqueue_script(
                'wu-content-duplicator',
                false,
                array('jquery'),
                '2.1',
                true
            );
            
            wp_add_inline_script('wu-content-duplicator', $this->get_admin_js());
        }
    }
    
    /**
     * 取得後台 CSS
     */
    private function get_admin_css() {
        return '
        .wu-duplicator-stats {
            display: flex;
            gap: 20px;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        
        .wu-stat-box {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            min-width: 200px;
            border-left: 4px solid #0073aa;
        }
        
        .wu-stat-box strong {
            display: block;
            margin-bottom: 5px;
            color: #333;
        }
        
        .wu-stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #0073aa;
        }
        
        .wu-info-panel {
            background: #fff;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin: 20px 0;
        }
        
        .wu-info-panel h3 {
            margin-top: 0;
            color: #0073aa;
        }
        
        .wu-two-column {
            display: flex;
            gap: 30px;
            margin-top: 15px;
        }
        
        .wu-two-column > div {
            flex: 1;
        }
        
        .wu-two-column ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .wu-two-column li {
            margin: 5px 0;
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
        
        .notice-warning {
            border-left-color: #ffb900;
        }
        
        .form-table th {
            width: 200px;
        }
        
        .wu-status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 600;
            margin-left: 10px;
        }
        
        .wu-status-on {
            background: #00a32a;
            color: #fff;
        }
        
        .wu-status-off {
            background: #d63638;
            color: #fff;
        }
        
        .wu-duplicate-loading {
            display: inline-block;
            margin-left: 5px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .wu-duplicate-loading.show {
            opacity: 1;
        }
        ';
    }
    
    /**
     * 取得後台 JavaScript
     */
    private function get_admin_js() {
        return '
        jQuery(document).ready(function($) {
            // 為複製連結添加 Loading 提示
            $(document).on("click", ".row-actions .duplicate a", function(e) {
                var $link = $(this);
                
                // 防止重複點擊
                if ($link.data("duplicating")) {
                    e.preventDefault();
                    return false;
                }
                
                $link.data("duplicating", true);
                $link.css("opacity", "0.5");
                
                // 添加 Loading 圖示
                $link.after(\'<span class="wu-duplicate-loading show spinner is-active" style="float: none; margin: 0 0 0 5px;"></span>\');
            });
        });
        ';
    }
    
    /**
     * 添加子選單頁面
     */
    public function add_admin_menu() {
        add_submenu_page(
            'wumetax-toolkit',
            '內容重覆設定',
            '內容重覆設定',
            'manage_options',
            'wu-content-duplicator',
            array($this, 'admin_page')
        );
    }
    
    /**
     * 設定區段回調
     */
    public function section_callback() {
        echo '<p>配置內容重複功能的相關設置。</p>';
    }
    
    /**
     * 啟用功能欄位
     */
    public function enabled_field_callback() {
        $enabled = !empty($this->settings['enabled']) ? $this->settings['enabled'] : false;
        ?>
        <label>
            <input type="checkbox" 
                   name="<?php echo $this->option_name; ?>[enabled]" 
                   value="1" 
                   <?php checked(1, $enabled); ?>>
            啟用內容重複功能
        </label>
        <p class="description">啟用後,在文章列表中會顯示「複製」連結</p>
        <?php
    }
    
    /**
     * 複製選項欄位
     */
    public function copy_options_field_callback() {
        ?>
        <p>
            <label>
                <input type="checkbox" 
                       name="<?php echo $this->option_name; ?>[copy_metadata]" 
                       value="1" 
                       <?php checked(1, $this->settings['copy_metadata']); ?>>
                <strong>複製所有元數據</strong><br>
                <span style="color: #666; font-size: 13px;">
                    包含自訂欄位、外掛設定、Elementor 設計資料等
                </span>
            </label>
        </p>
        <p>
            <label>
                <input type="checkbox" 
                       name="<?php echo $this->option_name; ?>[copy_taxonomies]" 
                       value="1" 
                       <?php checked(1, $this->settings['copy_taxonomies']); ?>>
                <strong>複製分類法</strong><br>
                <span style="color: #666; font-size: 13px;">
                    複製類別、標籤和自訂分類法
                </span>
            </label>
        </p>
        <p>
            <label>
                <input type="checkbox" 
                       name="<?php echo $this->option_name; ?>[copy_featured_image]" 
                       value="1" 
                       <?php checked(1, $this->settings['copy_featured_image']); ?>>
                <strong>複製特色圖片</strong>
            </label>
        </p>
        <?php
    }
    
    /**
     * 標題與網址設定欄位
     */
    public function title_settings_field_callback() {
        ?>
        <p>
            <label for="title_prefix">標題前綴</label><br>
            <input type="text" 
                   id="title_prefix"
                   name="<?php echo $this->option_name; ?>[title_prefix]" 
                   value="<?php echo esc_attr($this->settings['title_prefix']); ?>" 
                   class="regular-text">
            <p class="description">複製後新文章標題的前綴</p>
        </p>
        <p>
            <label for="slug_suffix">網址後綴</label><br>
            <input type="text" 
                   id="slug_suffix"
                   name="<?php echo $this->option_name; ?>[slug_suffix]" 
                   value="<?php echo esc_attr($this->settings['slug_suffix']); ?>" 
                   class="regular-text">
            <p class="description">複製後新文章網址的後綴（例如:-copy）</p>
        </p>
        <?php
    }
    
    /**
     * 狀態欄位
     */
    public function status_field_callback() {
        ?>
        <select name="<?php echo $this->option_name; ?>[status_after_duplicate]">
            <option value="draft" <?php selected($this->settings['status_after_duplicate'], 'draft'); ?>>草稿</option>
            <option value="pending" <?php selected($this->settings['status_after_duplicate'], 'pending'); ?>>待審核</option>
            <option value="private" <?php selected($this->settings['status_after_duplicate'], 'private'); ?>>私人</option>
            <option value="same" <?php selected($this->settings['status_after_duplicate'], 'same'); ?>>與原文相同</option>
        </select>
        <p class="description">複製後新文章的發佈狀態</p>
        <?php
    }
    
    /**
     * 進階選項欄位
     */
    public function advanced_options_field_callback() {
        ?>
        <p>
            <label>
                <input type="checkbox" 
                       name="<?php echo $this->option_name; ?>[regenerate_elementor_css]" 
                       value="1" 
                       <?php checked(1, $this->settings['regenerate_elementor_css']); ?>>
                <strong>重新產生 Elementor CSS</strong><br>
                <span style="color: #666; font-size: 13px;">
                    複製後自動清除 Elementor CSS 快取,確保前台樣式正常顯示
                </span>
            </label>
        </p>
        <p>
            <label>
                <input type="checkbox" 
                       name="<?php echo $this->option_name; ?>[exclude_woocommerce_data]" 
                       value="1" 
                       <?php checked(1, $this->settings['exclude_woocommerce_data']); ?>>
                <strong>排除 WooCommerce 統計資料</strong><br>
                <span style="color: #666; font-size: 13px;">
                    自動排除 SKU、銷售數量、評分等資料,避免產生重複或錯誤
                </span>
            </label>
        </p>
        <p>
            <label>
                <input type="checkbox" 
                       name="<?php echo $this->option_name; ?>[exclude_seo_stats]" 
                       value="1" 
                       <?php checked(1, $this->settings['exclude_seo_stats']); ?>>
                <strong>排除 SEO 統計資料</strong><br>
                <span style="color: #666; font-size: 13px;">
                    排除 Yoast SEO 或 Rank Math 的統計分數,讓新文章重新計算
                </span>
            </label>
        </p>
        <?php
    }
    
    /**
     * 排除貼文類型欄位
     */
    public function excluded_post_types_field_callback() {
        $post_types = $this->get_supported_post_types();
        
        foreach ($post_types as $post_type => $label) {
            ?>
            <label style="display: block; margin: 5px 0;">
                <input type="checkbox" 
                       name="<?php echo $this->option_name; ?>[excluded_post_types][]" 
                       value="<?php echo esc_attr($post_type); ?>" 
                       <?php checked(in_array($post_type, $this->settings['excluded_post_types'])); ?>>
                <?php echo esc_html($label); ?> (<?php echo esc_html($post_type); ?>)
            </label>
            <?php
        }
        ?>
        <p class="description">選中的貼文類型將不會顯示複製功能</p>
        <?php
    }
    
    /**
     * 排除元數據鍵欄位
     */
    public function excluded_meta_keys_field_callback() {
        ?>
        <textarea name="<?php echo $this->option_name; ?>[excluded_meta_keys]" 
                  rows="5" 
                  cols="50" 
                  placeholder="每行一個鍵值，例如：
_edit_lock
_edit_last"><?php echo esc_textarea(implode("\n", $this->settings['excluded_meta_keys'])); ?></textarea>
        <p class="description">這些元數據不會被複製（一行一個）</p>
        <?php
    }
    
    /**
     * 管理頁面
     */
    public function admin_page() {
        // 驗證權限
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('您沒有足夠的權限訪問此頁面。', 'wumetax-toolkit'));
        }
        
        $this->settings = get_option($this->option_name, $this->get_default_settings());
        $status = !empty($this->settings['enabled']) ? 'on' : 'off';
        
        ?>
        <div class="wrap">
            <h1>
                內容重複設定
                <span class="wu-status-badge wu-status-<?php echo esc_attr($status); ?>">
                    <?php echo $status === 'on' ? '已啟用' : '已關閉'; ?>
                </span>
            </h1>
            
            <div class="notice notice-info">
                <h3>功能說明</h3>
                <p><strong>內容重複功能</strong>讓您可以快速複製現有的頁面、貼文和自訂貼文，並保留所有相關的元數據和分類法資訊。</p>
                
                <h4>包含的內容：</h4>
                <ul>
                    <li><strong>基本內容</strong>：標題、內容、摘要</li>
                    <li><strong>元數據</strong>：所有自訂欄位和外掛相關設定</li>
                    <li><strong>分類法</strong>：類別、標籤和自訂分類法</li>
                    <li><strong>特色圖片</strong>：保留原文章的特色圖片</li>
                    <li><strong>Elementor 設計</strong>：完整保留 Elementor 頁面設計資料並自動更新 CSS</li>
                </ul>
                
                <h4>安全措施：</h4>
                <ul>
                    <li>自動排除敏感的元數據（如編輯鎖定）</li>
                    <li>可設定排除特定貼文類型</li>
                    <li>複製後預設為草稿狀態</li>
                    <li>使用 WordPress Settings API 確保安全性</li>
                    <li>自動排除 WooCommerce SKU 避免重複</li>
                    <li>自動排除 SEO 統計資料讓新文章重新計算</li>
                </ul>
            </div>
            
            <?php if (class_exists('Elementor\Plugin')): ?>
            <div class="notice notice-warning">
                <h4>⚠️ Elementor 使用者注意事項</h4>
                <p>複製 Elementor 頁面後，建議:</p>
                <ul>
                    <li>啟用「重新產生 Elementor CSS」選項</li>
                    <li>複製後檢查前台顯示是否正常</li>
                    <li>如果樣式異常，請在 Elementor → 工具 → 重新產生 CSS 與資料</li>
                </ul>
            </div>
            <?php endif; ?>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('wu_content_duplicator_group');
                do_settings_sections('wu-content-duplicator');
                submit_button('儲存設定');
                ?>
            </form>
            
            <hr>
            
            <h2>使用說明</h2>
            <div class="wu-info-panel">
                <h3>如何使用內容重複功能：</h3>
                <ol>
                    <li><strong>在文章列表中</strong>：前往「文章」→「所有文章」，您會在每個文章的操作選項中看到「複製」連結</li>
                    <li><strong>在頁面列表中</strong>：前往「頁面」→「所有頁面」，同樣會有「複製」連結</li>
                    <li><strong>自訂貼文類型</strong>：如果啟用了其他貼文類型，也會有相同的複製功能</li>
                    <li><strong>Elementor 頁面</strong>：完整支援 Elementor 設計資料的複製，並自動更新 CSS</li>
                    <li><strong>WooCommerce 產品</strong>：自動排除 SKU 等不可重複的資料</li>
                </ol>
                
                <h3>複製範圍：</h3>
                <div class="wu-two-column">
                    <div>
                        <h4>會被複製的內容：</h4>
                        <ul>
                            <li>文章標題、內容、摘要</li>
                            <li>所有自訂欄位</li>
                            <li>分類法（類別、標籤等）</li>
                            <li>特色圖片</li>
                            <li>文章格式</li>
                            <li>外掛相關設定</li>
                            <li>Elementor 設計資料（JSON 格式完整保留）</li>
                            <li>ACF 自訂欄位</li>
                        </ul>
                    </div>
                    <div>
                        <h4>不會被複製的內容：</h4>
                        <ul>
                            <li>文章ID（自動產生新的）</li>
                            <li>發佈日期（使用當前時間）</li>
                            <li>瀏覽次數、留言數等統計</li>
                            <li>編輯鎖定資訊</li>
                            <li>WooCommerce SKU 和銷售統計</li>
                            <li>SEO 外掛的統計分數</li>
                            <li>被排除的元數據</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <h2>統計資訊</h2>
            <div class="wu-duplicator-stats">
                <div class="wu-stat-box">
                    <strong>支援的貼文類型</strong>
                    <div class="wu-stat-number"><?php echo count($this->get_enabled_post_types()); ?></div>
                </div>
                <div class="wu-stat-box">
                    <strong>本月複製次數</strong>
                    <div class="wu-stat-number"><?php echo $this->get_duplicate_count('month'); ?></div>
                </div>
                <div class="wu-stat-box">
                    <strong>總複製次數</strong>
                    <div class="wu-stat-number"><?php echo $this->get_duplicate_count('total'); ?></div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * 添加複製連結
     */
    public function add_duplicate_link($actions, $post) {
        if (!$this->settings['enabled']) {
            return $actions;
        }
        
        if (in_array($post->post_type, $this->settings['excluded_post_types'])) {
            return $actions;
        }
        
        if (!current_user_can('edit_posts')) {
            return $actions;
        }
        
        $duplicate_url = wp_nonce_url(
            admin_url('admin.php?action=wu_duplicate_post&post=' . $post->ID),
            'wu_duplicate_post_' . $post->ID
        );
        
        $actions['duplicate'] = sprintf(
            '<a href="%s" title="%s">%s</a>',
            esc_url($duplicate_url),
            esc_attr__('複製這個項目'),
            __('複製')
        );
        
        return $actions;
    }
    
    /**
     * 添加自訂貼文類型支援
     */
    public function add_custom_post_type_support() {
        $post_types = get_post_types(array('public' => true), 'names');
        
        foreach ($post_types as $post_type) {
            if (!in_array($post_type, $this->settings['excluded_post_types'])) {
                add_filter("{$post_type}_row_actions", array($this, 'add_duplicate_link'), 10, 2);
            }
        }
    }
    
    /**
     * 複製文章
     */
    public function duplicate_post() {
        // 驗證 Nonce
        if (!isset($_GET['_wpnonce']) || 
            !wp_verify_nonce($_GET['_wpnonce'], 'wu_duplicate_post_' . $_GET['post'])) {
            wp_die(esc_html__('安全驗證失敗', 'wumetax-toolkit'));
        }
        
        // 驗證權限
        if (!current_user_can('edit_posts')) {
            wp_die(esc_html__('權限不足', 'wumetax-toolkit'));
        }
        
        $post_id = intval($_GET['post']);
        $new_post_id = $this->create_duplicate($post_id);
        
        if ($new_post_id) {
            $redirect_url = admin_url('post.php?action=edit&post=' . $new_post_id);
            wp_redirect($redirect_url);
            exit;
        } else {
            wp_die(esc_html__('複製失敗', 'wumetax-toolkit'));
        }
    }
    
    /**
     * AJAX 複製文章
     */
    public function ajax_duplicate_post() {
        // 驗證 Nonce
        if (!isset($_POST['_wpnonce']) || 
            !wp_verify_nonce($_POST['_wpnonce'], 'wu_duplicate_post')) {
            wp_send_json_error('安全驗證失敗');
        }
        
        // 驗證權限
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('權限不足');
        }
        
        $post_id = intval($_POST['post_id']);
        $new_post_id = $this->create_duplicate($post_id);
        
        if ($new_post_id) {
            wp_send_json_success(array(
                'new_post_id' => $new_post_id,
                'edit_url' => admin_url('post.php?action=edit&post=' . $new_post_id),
                'message' => '複製成功！'
            ));
        } else {
            wp_send_json_error('複製失敗');
        }
    }
    
    /**
     * 建立複本
     */
    private function create_duplicate($post_id) {
        $original_post = get_post($post_id);
        
        if (!$original_post) {
            return false;
        }
        
        // 產生新的 Slug
        $new_slug = $original_post->post_name . $this->settings['slug_suffix'];
        $new_slug = wp_unique_post_slug(
            $new_slug,
            0,
            $original_post->post_status,
            $original_post->post_type,
            $original_post->post_parent
        );
        
        // 準備新文章資料
        $new_post_data = array(
            'post_title' => $this->settings['title_prefix'] . $original_post->post_title,
            'post_name' => $new_slug,
            'post_content' => $original_post->post_content,
            'post_excerpt' => $original_post->post_excerpt,
            'post_type' => $original_post->post_type,
            'post_status' => $this->settings['status_after_duplicate'] === 'same' 
                ? $original_post->post_status 
                : $this->settings['status_after_duplicate'],
            'post_author' => get_current_user_id(),
            'post_parent' => $original_post->post_parent,
            'menu_order' => $original_post->menu_order,
            'comment_status' => $original_post->comment_status,
            'ping_status' => $original_post->ping_status,
            'post_password' => $original_post->post_password
        );
        
        // 插入新文章
        $new_post_id = wp_insert_post($new_post_data);
        
        if (is_wp_error($new_post_id)) {
            return false;
        }
        
        // 複製文章格式
        $post_format = get_post_format($post_id);
        if ($post_format) {
            set_post_format($new_post_id, $post_format);
        }
        
        // 複製元數據
        if ($this->settings['copy_metadata']) {
            $this->copy_post_metadata($post_id, $new_post_id);
        }
        
        // 複製分類法
        if ($this->settings['copy_taxonomies']) {
            $this->copy_post_taxonomies($post_id, $new_post_id);
        }
        
        // 複製特色圖片
        if ($this->settings['copy_featured_image']) {
            $this->copy_featured_image($post_id, $new_post_id);
        }
        
        // Elementor 後處理
        if ($this->settings['regenerate_elementor_css']) {
            $this->regenerate_elementor_css($new_post_id);
        }
        
        // 記錄複製操作
        $this->log_duplicate_action($post_id, $new_post_id);
        
        return $new_post_id;
    }
    
    /**
     * 複製文章元數據（完整優化版本）
     */
    private function copy_post_metadata($source_id, $target_id) {
        $meta_data = get_post_meta($source_id);
        $post_type = get_post_type($source_id);
        
        // 建立完整的排除列表
        $excluded_keys = array_merge(
            $this->settings['excluded_meta_keys'],
            array('_edit_lock', '_edit_last', '_wp_old_slug', '_wp_old_date')
        );
        
        // 如果啟用 WooCommerce 排除
        if ($this->settings['exclude_woocommerce_data'] && $post_type === 'product') {
            $excluded_keys = array_merge($excluded_keys, $this->woocommerce_excluded_meta);
        }
        
        // 如果啟用 SEO 統計排除
        if ($this->settings['exclude_seo_stats']) {
            $excluded_keys = array_merge($excluded_keys, $this->seo_excluded_meta);
        }
        
        // 如果不複製特色圖片,排除 _thumbnail_id
        if (!$this->settings['copy_featured_image']) {
            $excluded_keys[] = '_thumbnail_id';
        }
        
        foreach ($meta_data as $key => $values) {
            // 跳過被排除的元數據
            if (in_array($key, $excluded_keys)) {
                continue;
            }
            
            foreach ($values as $value) {
                $unserialized_value = maybe_unserialize($value);
                
                // Elementor 特殊處理：確保 JSON 格式正確
                if (in_array($key, $this->elementor_meta_keys)) {
                    if ($key === '_elementor_data') {
                        // 解碼並重新編碼，確保格式正確
                        if (is_string($unserialized_value)) {
                            $decoded = json_decode($unserialized_value, true);
                            if (json_last_error() === JSON_ERROR_NONE) {
                                $unserialized_value = wp_slash(wp_json_encode($decoded));
                            } else {
                                $unserialized_value = wp_slash($unserialized_value);
                            }
                        }
                    } else {
                        $unserialized_value = wp_slash($unserialized_value);
                    }
                    
                    // 直接更新，不使用 add_post_meta
                    update_post_meta($target_id, $key, $unserialized_value);
                } else {
                    // 一般元數據正常處理
                    add_post_meta($target_id, $key, $unserialized_value);
                }
            }
        }
    }
    
    /**
     * 複製文章分類法
     */
    private function copy_post_taxonomies($source_id, $target_id) {
        $taxonomies = get_object_taxonomies(get_post_type($source_id));
        
        foreach ($taxonomies as $taxonomy) {
            $terms = wp_get_object_terms($source_id, $taxonomy, array('fields' => 'slugs'));
            
            if (!empty($terms) && !is_wp_error($terms)) {
                wp_set_object_terms($target_id, $terms, $taxonomy);
            }
        }
    }
    
    /**
     * 複製特色圖片
     */
    private function copy_featured_image($source_id, $target_id) {
        $featured_image_id = get_post_thumbnail_id($source_id);
        
        if ($featured_image_id) {
            set_post_thumbnail($target_id, $featured_image_id);
        }
    }
    
    /**
     * 重新產生 Elementor CSS
     */
    private function regenerate_elementor_css($post_id) {
        // 檢查 Elementor 是否啟用
        if (!class_exists('Elementor\Plugin')) {
            return;
        }
        
        try {
            // 清除 Elementor CSS 快取
            delete_post_meta($post_id, '_elementor_css');
            
            // 取得文件並觸發 CSS 重新產生
            $document = \Elementor\Plugin::$instance->documents->get($post_id);
            
            if ($document) {
                // 儲存範本類型以觸發 CSS 重新產生
                $document->save(array());
                
                // 清除 Elementor 快取
                \Elementor\Plugin::$instance->files_manager->clear_cache();
            }
        } catch (Exception $e) {
            // 靜默失敗，不中斷複製流程
            error_log('Elementor CSS regeneration failed: ' . $e->getMessage());
        }
    }
    
    /**
     * 記錄複製操作（優化版本）
     */
    private function log_duplicate_action($source_id, $target_id) {
        // 更新總計數器（獨立選項）
        $total_count = get_option('wu_content_duplicator_total_count', 0);
        update_option('wu_content_duplicator_total_count', $total_count + 1);
        
        // 如果有 Audit Logger，使用它記錄
        if (class_exists('WU_Audit_Logger')) {
            $logger = new WU_Audit_Logger();
            $logger->log_action(
                'content_duplicate',
                'success',
                sprintf(
                    '複製文章：%s (#%d) → %s (#%d)',
                    get_the_title($source_id),
                    $source_id,
                    get_the_title($target_id),
                    $target_id
                ),
                array(
                    'source_id' => $source_id,
                    'target_id' => $target_id,
                    'post_type' => get_post_type($source_id)
                )
            );
        } else {
            // 備用方案：簡化的日誌記錄
            $log_data = get_option('wu_content_duplicator_log', array());
            
            $log_entry = array(
                'date' => current_time('mysql'),
                'user_id' => get_current_user_id(),
                'source_id' => $source_id,
                'target_id' => $target_id
            );
            
            array_unshift($log_data, $log_entry);
            
            // 只保留最近50筆記錄
            if (count($log_data) > 50) {
                $log_data = array_slice($log_data, 0, 50);
            }
            
            update_option('wu_content_duplicator_log', $log_data);
        }
    }
    
    /**
     * 取得支援的貼文類型
     */
    private function get_supported_post_types() {
        $post_types = get_post_types(array('public' => true), 'objects');
        $result = array();
        
        foreach ($post_types as $post_type => $object) {
            $result[$post_type] = $object->labels->name;
        }
        
        return $result;
    }
    
    /**
     * 取得已啟用的貼文類型
     */
    private function get_enabled_post_types() {
        $all_types = $this->get_supported_post_types();
        $enabled_types = array();
        
        foreach ($all_types as $type => $label) {
            if (!in_array($type, $this->settings['excluded_post_types'])) {
                $enabled_types[$type] = $label;
            }
        }
        
        return $enabled_types;
    }
    
    /**
     * 取得複製次數（優化版本）
     */
    private function get_duplicate_count($period = 'total') {
        if ($period === 'total') {
            // 使用獨立計數器
            return get_option('wu_content_duplicator_total_count', 0);
        }
        
        // 月度統計仍需讀取日誌
        $log_data = get_option('wu_content_duplicator_log', array());
        $count = 0;
        $current_time = current_time('timestamp');
        
        foreach ($log_data as $entry) {
            $entry_time = strtotime($entry['date']);
            
            if ($period === 'month' && $entry_time >= strtotime('-1 month', $current_time)) {
                $count++;
            }
        }
        
        return $count;
    }
}

// 初始化模組
new WU_Content_Duplicator();
