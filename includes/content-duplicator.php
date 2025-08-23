<?php
/**
 * 內容重複模組
 * 一鍵複製頁面、貼文和自訂貼文的功能
 */

if (!defined('ABSPATH')) exit;

class WU_Content_Duplicator {
    
    private $settings;
    
    public function __construct() {
        $this->settings = get_option('wu_content_duplicator_settings', $this->get_default_settings());
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('post_row_actions', array($this, 'add_duplicate_link'), 10, 2);
        add_action('page_row_actions', array($this, 'add_duplicate_link'), 10, 2);
        add_action('admin_action_wu_duplicate_post', array($this, 'duplicate_post'));
        add_action('wp_ajax_wu_duplicate_post', array($this, 'ajax_duplicate_post'));
        
        // 支援自訂貼文類型
        add_action('init', array($this, 'add_custom_post_type_support'));
    }
    
    private function get_default_settings() {
        return array(
            'enabled' => false,
            'copy_metadata' => true,
            'copy_taxonomies' => true,
            'copy_featured_image' => true,
            'excluded_post_types' => array('attachment', 'elementor_library', 'wc_product_tab', 'shop_order', 'shop_coupon'),
            'excluded_meta_keys' => array('_edit_lock', '_edit_last'),
            'title_prefix' => '副本 - ',
            'status_after_duplicate' => 'draft'
        );
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'wu-toolbox',
            '內容重複',
            '內容重複',
            'manage_options',
            'wu-content-duplicator',
            array($this, 'admin_page')
        );
    }
    
    public function admin_page() {
        if (isset($_POST['submit'])) {
            $this->save_settings();
        }
        
        $this->settings = get_option('wu_content_duplicator_settings', $this->get_default_settings());
        $post_types = $this->get_supported_post_types();
        ?>
        <div class="wrap">
            <h1>內容重複設定</h1>
            
            <div class="notice notice-info">
                <h3>功能說明</h3>
                <p><strong>內容重複功能</strong>讓您可以快速複製現有的頁面、貼文和自訂貼文，並保留所有相關的元數據和分類法資訊。</p>
                
                <h4>包含的內容：</h4>
                <ul>
                    <li><strong>基本內容</strong>：標題、內容、摘要、發佈日期</li>
                    <li><strong>元數據</strong>：所有自訂欄位和外掛相關設定</li>
                    <li><strong>分類法</strong>：類別、標籤和自訂分類法</li>
                    <li><strong>特色圖片</strong>：保留原文章的特色圖片</li>
                </ul>
                
                <h4>安全措施：</h4>
                <ul>
                    <li>自動排除敏感的元數據（如編輯鎖定）</li>
                    <li>可設定排除特定貼文類型</li>
                    <li>複製後預設為草稿狀態</li>
                </ul>
            </div>
            
            <form method="post" action="">
                <?php wp_nonce_field('wu_content_duplicator_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">啟用功能</th>
                        <td>
                            <label>
                                <input type="checkbox" name="enabled" value="1" <?php checked($this->settings['enabled']); ?>>
                                啟用內容重複功能
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">複製元數據</th>
                        <td>
                            <label>
                                <input type="checkbox" name="copy_metadata" value="1" <?php checked($this->settings['copy_metadata']); ?>>
                                複製所有自訂欄位和元數據
                            </label>
                            <p class="description">包含所有外掛設定、自訂欄位等</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">複製分類法</th>
                        <td>
                            <label>
                                <input type="checkbox" name="copy_taxonomies" value="1" <?php checked($this->settings['copy_taxonomies']); ?>>
                                複製類別、標籤和自訂分類法
                            </label>
                            <p class="description">保留原文章的所有分類關聯</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">複製特色圖片</th>
                        <td>
                            <label>
                                <input type="checkbox" name="copy_featured_image" value="1" <?php checked($this->settings['copy_featured_image']); ?>>
                                複製特色圖片
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">標題前綴</th>
                        <td>
                            <input type="text" name="title_prefix" value="<?php echo esc_attr($this->settings['title_prefix']); ?>" class="regular-text">
                            <p class="description">複製後新文章標題的前綴</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">複製後狀態</th>
                        <td>
                            <select name="status_after_duplicate">
                                <option value="draft" <?php selected($this->settings['status_after_duplicate'], 'draft'); ?>>草稿</option>
                                <option value="pending" <?php selected($this->settings['status_after_duplicate'], 'pending'); ?>>待審核</option>
                                <option value="private" <?php selected($this->settings['status_after_duplicate'], 'private'); ?>>私人</option>
                                <option value="same" <?php selected($this->settings['status_after_duplicate'], 'same'); ?>>與原文相同</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">排除的貼文類型</th>
                        <td>
                            <?php foreach ($post_types as $post_type => $label): ?>
                            <label style="display: block; margin: 5px 0;">
                                <input type="checkbox" name="excluded_post_types[]" value="<?php echo esc_attr($post_type); ?>" 
                                       <?php checked(in_array($post_type, $this->settings['excluded_post_types'])); ?>>
                                <?php echo esc_html($label); ?> (<?php echo esc_html($post_type); ?>)
                            </label>
                            <?php endforeach; ?>
                            <p class="description">選中的貼文類型將不會顯示複製功能</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">排除的元數據鍵</th>
                        <td>
                            <textarea name="excluded_meta_keys" rows="5" cols="50" placeholder="每行一個鍵值，例如：&#10;_edit_lock&#10;_edit_last"><?php echo esc_textarea(implode("\n", $this->settings['excluded_meta_keys'])); ?></textarea>
                            <p class="description">這些元數據不會被複製（一行一個）</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('儲存設定'); ?>
            </form>
            
            <hr>
            
            <h2>使用說明</h2>
            <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
                <h3>如何使用內容重複功能：</h3>
                <ol>
                    <li><strong>在文章列表中</strong>：前往「文章」→「所有文章」，您會在每個文章的操作選項中看到「複製」連結</li>
                    <li><strong>在頁面列表中</strong>：前往「頁面」→「所有頁面」，同樣會有「複製」連結</li>
                    <li><strong>自訂貼文類型</strong>：如果啟用了其他貼文類型，也會有相同的複製功能</li>
                    <li><strong>編輯時複製</strong>：在編輯文章時，也可以使用快速複製功能</li>
                </ol>
                
                <h3>複製範圍：</h3>
                <div style="display: flex; gap: 30px; margin-top: 15px;">
                    <div style="flex: 1;">
                        <h4>會被複製的內容：</h4>
                        <ul>
                            <li>文章標題、內容、摘要</li>
                            <li>所有自訂欄位</li>
                            <li>分類法（類別、標籤等）</li>
                            <li>特色圖片</li>
                            <li>文章格式</li>
                            <li>外掛相關設定</li>
                        </ul>
                    </div>
                    <div style="flex: 1;">
                        <h4>不會被複製的內容：</h4>
                        <ul>
                            <li>文章ID（自動產生新的）</li>
                            <li>發佈日期（使用當前時間）</li>
                            <li>瀏覽次數、留言數等統計</li>
                            <li>編輯鎖定資訊</li>
                            <li>被排除的元數據</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <h2>統計資訊</h2>
            <div style="display: flex; gap: 20px; margin: 20px 0; flex-wrap: wrap;">
                <div style="background: #f9f9f9; padding: 15px; border-radius: 5px; min-width: 200px;">
                    <strong>支援的貼文類型：</strong><br>
                    <span style="font-size: 24px; color: #0073aa;"><?php echo count($this->get_enabled_post_types()); ?></span>
                </div>
                <div style="background: #f9f9f9; padding: 15px; border-radius: 5px; min-width: 200px;">
                    <strong>本月複製次數：</strong><br>
                    <span style="font-size: 24px; color: #46b450;"><?php echo $this->get_duplicate_count('month'); ?></span>
                </div>
                <div style="background: #f9f9f9; padding: 15px; border-radius: 5px; min-width: 200px;">
                    <strong>總複製次數：</strong><br>
                    <span style="font-size: 24px; color: #ff8c00;"><?php echo $this->get_duplicate_count('total'); ?></span>
                </div>
            </div>
        </div>
        
        <style>
        .form-table th {
            width: 200px;
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
        if (!wp_verify_nonce($_POST['_wpnonce'], 'wu_content_duplicator_settings')) {
            wp_die('安全驗證失敗');
        }
        
        $excluded_post_types = isset($_POST['excluded_post_types']) ? array_map('sanitize_text_field', $_POST['excluded_post_types']) : array();
        $excluded_meta_keys = array_filter(array_map('trim', explode("\n", $_POST['excluded_meta_keys'])));
        
        $settings = array(
            'enabled' => isset($_POST['enabled']),
            'copy_metadata' => isset($_POST['copy_metadata']),
            'copy_taxonomies' => isset($_POST['copy_taxonomies']),
            'copy_featured_image' => isset($_POST['copy_featured_image']),
            'excluded_post_types' => $excluded_post_types,
            'excluded_meta_keys' => $excluded_meta_keys,
            'title_prefix' => sanitize_text_field($_POST['title_prefix']),
            'status_after_duplicate' => sanitize_text_field($_POST['status_after_duplicate'])
        );
        
        update_option('wu_content_duplicator_settings', $settings);
        $this->settings = $settings;
        
        echo '<div class="notice notice-success"><p>設定已儲存！</p></div>';
    }
    
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
    
    public function add_custom_post_type_support() {
        $post_types = get_post_types(array('public' => true), 'names');
        
        foreach ($post_types as $post_type) {
            if (!in_array($post_type, $this->settings['excluded_post_types'])) {
                add_filter("{$post_type}_row_actions", array($this, 'add_duplicate_link'), 10, 2);
            }
        }
    }
    
    public function duplicate_post() {
        if (!wp_verify_nonce($_GET['_wpnonce'], 'wu_duplicate_post_' . $_GET['post'])) {
            wp_die('安全驗證失敗');
        }
        
        if (!current_user_can('edit_posts')) {
            wp_die('權限不足');
        }
        
        $post_id = intval($_GET['post']);
        $new_post_id = $this->create_duplicate($post_id);
        
        if ($new_post_id) {
            $redirect_url = admin_url('post.php?action=edit&post=' . $new_post_id);
            wp_redirect($redirect_url);
            exit;
        } else {
            wp_die('複製失敗');
        }
    }
    
    public function ajax_duplicate_post() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'wu_duplicate_post')) {
            wp_die('安全驗證失敗');
        }
        
        if (!current_user_can('edit_posts')) {
            wp_die('權限不足');
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
    
    private function create_duplicate($post_id) {
        $original_post = get_post($post_id);
        
        if (!$original_post) {
            return false;
        }
        
        // 準備新文章資料
        $new_post_data = array(
            'post_title' => $this->settings['title_prefix'] . $original_post->post_title,
            'post_content' => $original_post->post_content,
            'post_excerpt' => $original_post->post_excerpt,
            'post_type' => $original_post->post_type,
            'post_status' => $this->settings['status_after_duplicate'] === 'same' ? $original_post->post_status : $this->settings['status_after_duplicate'],
            'post_author' => get_current_user_id(),
            'post_parent' => $original_post->post_parent,
            'menu_order' => $original_post->menu_order,
            'comment_status' => $original_post->comment_status,
            'ping_status' => $original_post->ping_status,
            'post_password' => $original_post->post_password,
            'post_format' => get_post_format($post_id)
        );
        
        // 插入新文章
        $new_post_id = wp_insert_post($new_post_data);
        
        if (is_wp_error($new_post_id)) {
            return false;
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
        
        // 記錄複製操作
        $this->log_duplicate_action($post_id, $new_post_id);
        
        return $new_post_id;
    }
    
    private function copy_post_metadata($source_id, $target_id) {
        $meta_data = get_post_meta($source_id);
        
        foreach ($meta_data as $key => $values) {
            // 跳過被排除的元數據
            if (in_array($key, $this->settings['excluded_meta_keys'])) {
                continue;
            }
            
            // 跳過系統相關的元數據
            if (in_array($key, array('_edit_lock', '_edit_last', '_wp_old_slug'))) {
                continue;
            }
            
            foreach ($values as $value) {
                add_post_meta($target_id, $key, maybe_unserialize($value));
            }
        }
    }
    
    private function copy_post_taxonomies($source_id, $target_id) {
        $taxonomies = get_object_taxonomies(get_post_type($source_id));
        
        foreach ($taxonomies as $taxonomy) {
            $terms = wp_get_object_terms($source_id, $taxonomy, array('fields' => 'slugs'));
            
            if (!empty($terms) && !is_wp_error($terms)) {
                wp_set_object_terms($target_id, $terms, $taxonomy);
            }
        }
    }
    
    private function copy_featured_image($source_id, $target_id) {
        $featured_image_id = get_post_thumbnail_id($source_id);
        
        if ($featured_image_id) {
            set_post_thumbnail($target_id, $featured_image_id);
        }
    }
    
    private function log_duplicate_action($source_id, $target_id) {
        $log_data = get_option('wu_content_duplicator_log', array());
        
        $log_entry = array(
            'date' => current_time('mysql'),
            'user_id' => get_current_user_id(),
            'source_id' => $source_id,
            'target_id' => $target_id,
            'source_title' => get_the_title($source_id),
            'target_title' => get_the_title($target_id)
        );
        
        array_unshift($log_data, $log_entry);
        
        // 只保留最近100筆記錄
        if (count($log_data) > 100) {
            $log_data = array_slice($log_data, 0, 100);
        }
        
        update_option('wu_content_duplicator_log', $log_data);
    }
    
    private function get_supported_post_types() {
        $post_types = get_post_types(array('public' => true), 'objects');
        $result = array();
        
        foreach ($post_types as $post_type => $object) {
            $result[$post_type] = $object->labels->name;
        }
        
        return $result;
    }
    
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
    
    private function get_duplicate_count($period = 'total') {
        $log_data = get_option('wu_content_duplicator_log', array());
        
        if ($period === 'total') {
            return count($log_data);
        }
        
        $count = 0;
        $current_time = current_time('timestamp');
        
        foreach ($log_data as $entry) {
            $entry_time = strtotime($entry['date']);
            
            switch ($period) {
                case 'month':
                    if ($entry_time >= strtotime('-1 month', $current_time)) {
                        $count++;
                    }
                    break;
                case 'week':
                    if ($entry_time >= strtotime('-1 week', $current_time)) {
                        $count++;
                    }
                    break;
                case 'day':
                    if ($entry_time >= strtotime('-1 day', $current_time)) {
                        $count++;
                    }
                    break;
            }
        }
        
        return $count;
    }
}

// 初始化模組
new WU_Content_Duplicator();