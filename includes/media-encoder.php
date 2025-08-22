<?php
if (!defined('ABSPATH')) exit;

/* === 媒體編碼器：選單 === */
function media_encoder_menu() {
    add_submenu_page(
        'wu-toolbox',
        '媒體編碼器',
        '媒體編碼器',
        'manage_options',
        'media-encoder',
        'media_encoder_settings_page'
    );
}
add_action('admin_menu', 'media_encoder_menu', 20);

/* === 取得與預設設定 === */
function media_encoder_get_settings() {
    return array(
        'enabled' => get_option('media_encoder_enabled', 'off'),
        'quality' => intval(get_option('media_encoder_quality', 82)),
        'replace_original' => get_option('media_encoder_replace_original', 'off'),
        'enable_logging' => get_option('media_encoder_enable_logging', 'off'),
    );
}

/* === 錯誤日誌記錄 === */
function media_encoder_log_error($message, $context = array()) {
    $settings = media_encoder_get_settings();
    if ($settings['enable_logging'] !== 'on') return;
    
    $log_message = '[Media Encoder] ' . $message;
    if (!empty($context)) {
        $log_message .= ' Context: ' . wp_json_encode($context);
    }
    error_log($log_message);
}

/* === 儲存設定 === */
function media_encoder_save_settings() {
    if (!current_user_can('manage_options')) return;
    if (!isset($_POST['media_encoder_save']) || !check_admin_referer('media_encoder_save', 'media_encoder_nonce')) return;

    update_option('media_encoder_enabled', isset($_POST['media_encoder_enabled']) ? sanitize_text_field($_POST['media_encoder_enabled']) : 'off');
    $quality = isset($_POST['media_encoder_quality']) ? max(1, min(100, intval($_POST['media_encoder_quality']))) : 82;
    update_option('media_encoder_quality', $quality);
    update_option('media_encoder_replace_original', isset($_POST['media_encoder_replace_original']) ? 'on' : 'off');
    update_option('media_encoder_enable_logging', isset($_POST['media_encoder_enable_logging']) ? 'on' : 'off');
    
    echo '<div class="updated"><p>媒體編碼器設定已更新 ✅</p></div>';
}

/* === 設定頁面 === */
function media_encoder_settings_page() {
    media_encoder_save_settings();
    $settings = media_encoder_get_settings();
    $quality = $settings['quality'];
    ?>
    <div class="wrap">
        <h1>媒體編碼器（JPEG/PNG → WebP）</h1>
        <p>自動將上傳的圖像轉換為 WebP，以獲得更佳效能與更小檔案。僅當您啟用本功能時才會載入相關 PHP 類與掛鉤，以節省伺服器資源。</p>

        <div style="display:flex;gap:40px;flex-wrap:wrap;align-items:flex-start;">
            <form method="post" style="flex:1;min-width:320px;max-width:560px;">
                <?php wp_nonce_field('media_encoder_save', 'media_encoder_nonce'); ?>
                <h2>設定</h2>
                <p>
                    <label>
                        <input type="checkbox" name="media_encoder_enabled" value="on" <?php checked($settings['enabled'], 'on'); ?>> 啟用媒體編碼器
                    </label><br>
                    <small>啟用後，系統會在圖片上傳時自動轉換為 WebP。</small>
                </p>
                <p>
                    <label>品質（1–100）：<input type="number" name="media_encoder_quality" min="1" max="100" value="<?php echo esc_attr($quality); ?>" style="width:90px;"></label>
                    <br><small>建議 75–90。數值越高品質越好、檔案越大。</small>
                </p>
                <p>
                    <label>
                        <input type="checkbox" name="media_encoder_replace_original" <?php checked($settings['replace_original'], 'on'); ?>> 將原圖與尺寸皆替換為 WebP
                    </label><br>
                    <small>啟用後會以 .webp 覆蓋附件檔案與各尺寸；停用則僅在旁生成 .webp。</small>
                </p>
                <p>
                    <label>
                        <input type="checkbox" name="media_encoder_enable_logging" <?php checked($settings['enable_logging'], 'on'); ?>> 啟用錯誤日誌記錄
                    </label><br>
                    <small>啟用後會將轉換錯誤記錄到 WordPress 錯誤日誌中。</small>
                </p>
                <p><input type="submit" class="button-primary" name="media_encoder_save" value="儲存設定"></p>

                <h2>預覽模式</h2>
                <p>在啟動全域轉換前，先對單一影像進行測試壓縮。</p>
                <p>
                    <select id="media-encoder-preview-attachment" style="min-width:260px;">
                        <option value="">選擇媒體庫影像…（僅 JPEG/PNG）</option>
                        <?php
                        $imgs = get_posts(array(
                            'post_type' => 'attachment',
                            'posts_per_page' => 5, // 限制為 5 張圖片
                            'post_mime_type' => array('image/jpeg', 'image/png'),
                            'orderby' => 'date',
                            'order' => 'DESC',
                        ));
                        foreach ($imgs as $img) {
                            $meta = wp_get_attachment_metadata($img->ID);
                            $label = get_the_title($img->ID);
                            if (!$label) $label = basename(get_attached_file($img->ID));
                            echo '<option value="' . esc_attr($img->ID) . '">' . esc_html($label) . ' (#' . intval($img->ID) . ")</option>";
                        }
                        ?>
                    </select>
                    <button type="button" class="button" id="media-encoder-run-preview">開始預覽</button>
                </p>
                <div id="media-encoder-preview-result" style="display:none;border:1px solid #ddd;padding:12px;border-radius:8px;"></div>

                <h2>批次轉換（舊有圖片 → WebP）</h2>
                <p>將目前媒體庫中的 JPEG/PNG 批量轉換為 WebP。為避免逾時，將以分批 AJAX 方式處理。</p>
                <p>
                    <button type="button" class="button button-primary" id="media-encoder-bulk-start">開始批次轉換</button>
                    <span id="media-encoder-bulk-status" style="margin-left:10px;"></span>
                </p>
            </form>

            <div style="flex:1;min-width:320px;">
                <h2>伺服器設定（Apache / Nginx）</h2>
                <p>若您未啟用「替換原圖」，建議透過伺服器規則在瀏覽器支援時優先提供 .webp 版本。</p>
                <h3>Apache（.htaccess）</h3>
                <pre style="background:#f6f6f6;padding:12px;white-space:pre-wrap;"># 將支援 WebP 的瀏覽器導向對應的 .webp
RewriteEngine On
RewriteCond %{HTTP_ACCEPT} image/webp
RewriteCond %{REQUEST_FILENAME} \.(jpe?g|png)$ [NC]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME}webp -f
RewriteRule ^(.+)\.(jpe?g|png)$ $1.$0webp [T=image/webp,E=accept:1]
AddType image/webp .webp</pre>
                <h3>Nginx</h3>
                <pre style="background:#f6f6f6;padding:12px;white-space:pre-wrap;"># 在 server { } 內加入
location ~* ^(.+)\.(jpg|jpeg|png)$ {
    set $webp "$1.$2webp";
    if ($http_accept ~* "webp") {
        try_files $webp $uri =404;
    }
}</pre>
            </div>
        </div>
    </div>

    <script>
    jQuery(function($){
        // 使用 esc_js() 避免 XSS 攻擊
        const nonce = '<?php echo esc_js(wp_create_nonce('media_encoder_ajax')); ?>';
        
        $('#media-encoder-run-preview').on('click', function(){
            const id = $('#media-encoder-preview-attachment').val();
            if(!id){ 
                alert('請先選擇一張圖片'); 
                return; 
            }
            
            const $result = $('#media-encoder-preview-result');
            $result.show().text('處理中…');
            
            $.post(ajaxurl, { 
                action: 'media_encoder_preview', 
                _wpnonce: nonce, 
                id: id 
            }, function(res){
                if(!res || !res.success){ 
                    const errorMsg = res && res.data ? res.data : '預覽失敗';
                    $result.html('<div style="color:red;">❌ ' + errorMsg + '</div>');
                    return; 
                }
                
                const d = res.data;
                let html = '<div style="color:green;">✅ 轉換成功</div>';
                html += '<div>原圖：' + d.original_size_human + ' → WebP：' + d.webp_size_human;
                if(d.saving_percent !== null) {
                    html += '（節省 ' + d.saving_percent + '%）';
                }
                html += '</div>';
                
                if(d.preview_url) {
                    html += '<div style="margin-top:8px;"><img src="' + d.preview_url + '" style="max-width:100%;height:auto;border:1px solid #eee;padding:4px;border-radius:6px;"></div>';
                }
                
                $result.html(html);
            }).fail(function(){
                $result.html('<div style="color:red;">❌ 網路錯誤，請重試</div>');
            });
        });

        $('#media-encoder-bulk-start').on('click', function(){
            let offset = 0; 
            const limit = 10; 
            let processed = 0; 
            let converted = 0; 
            let skipped = 0;
            let errors = 0;
            
            const $status = $('#media-encoder-bulk-status');
            const $button = $(this);
            
            $button.prop('disabled', true).text('處理中...');
            $status.text('掃描中…');
            
            function step(){
                $.post(ajaxurl, { 
                    action: 'media_encoder_bulk', 
                    _wpnonce: nonce, 
                    offset: offset, 
                    limit: limit 
                }, function(res){
                    if(!res || !res.success){ 
                        const errorMsg = res && res.data ? res.data : '批次失敗';
                        $status.html('<span style="color:red;">❌ ' + errorMsg + '</span>');
                        $button.prop('disabled', false).text('開始批次轉換');
                        return; 
                    }
                    
                    processed += res.data.processed; 
                    converted += res.data.converted; 
                    skipped += res.data.skipped;
                    errors += res.data.errors || 0;
                    offset += limit;
                    
                    let statusText = '已處理 ' + processed + '，轉換 ' + converted + '，略過 ' + skipped;
                    if(errors > 0) {
                        statusText += '，錯誤 ' + errors;
                    }
                    statusText += res.data.done ? '（完成）' : '…';
                    
                    $status.text(statusText);
                    
                    if(res.data.done) {
                        $button.prop('disabled', false).text('開始批次轉換');
                        if(converted > 0) {
                            $status.prepend('<span style="color:green;">✅ </span>');
                        }
                    } else {
                        step();
                    }
                }).fail(function(){
                    $status.html('<span style="color:red;">❌ 網路錯誤，請重試</span>');
                    $button.prop('disabled', false).text('開始批次轉換');
                });
            }
            step();
        });
    });
    </script>
    <?php
}

/* === 條件式註冊：僅啟用時才掛鉤 === */
function media_encoder_maybe_register_hooks() {
    $settings = media_encoder_get_settings();
    if ($settings['enabled'] !== 'on') return;
    
    // 掛鉤上傳時轉換
    add_filter('wp_generate_attachment_metadata', 'media_encoder_convert_on_upload', 10, 2);
    
    // 掛鉤前台 WebP 過濾器（僅在未替換原圖時）
    if ($settings['replace_original'] !== 'on') {
        add_filter('wp_get_attachment_url', 'media_encoder_filter_attachment_url', 10, 2);
        add_filter('wp_calculate_image_srcset', 'media_encoder_filter_srcset', 10, 5);
    }
}
add_action('init', 'media_encoder_maybe_register_hooks');

/* === 前台 WebP 過濾器 === */
function media_encoder_filter_attachment_url($url, $attachment_id) {
    // 檢查瀏覽器是否支援 WebP
    if (!media_encoder_browser_supports_webp()) return $url;
    
    $file = get_attached_file($attachment_id);
    if (!$file) return $url;
    
    $mime = get_post_mime_type($attachment_id);
    if (!in_array($mime, array('image/jpeg', 'image/png'))) return $url;
    
    $webp_file = preg_replace('/\.(jpe?g|png)$/i', '.webp', $file);
    
    // 檢查 WebP 檔案是否實際存在
    if (!file_exists($webp_file)) return $url;
    
    // 將 URL 替換為 WebP 版本
    $upload_dir = wp_upload_dir();
    $webp_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $webp_file);
    
    return $webp_url;
}

function media_encoder_filter_srcset($sources, $size_array, $image_src, $image_meta, $attachment_id) {
    if (!media_encoder_browser_supports_webp()) return $sources;
    
    $upload_dir = wp_upload_dir();
    $base_dir = trailingslashit($upload_dir['basedir']);
    $base_url = trailingslashit($upload_dir['baseurl']);
    
    foreach ($sources as $width => $source) {
        $file_path = str_replace($base_url, $base_dir, $source['url']);
        $webp_path = preg_replace('/\.(jpe?g|png)$/i', '.webp', $file_path);
        
        if (file_exists($webp_path)) {
            $sources[$width]['url'] = str_replace($base_dir, $base_url, $webp_path);
        }
    }
    
    return $sources;
}

function media_encoder_browser_supports_webp() {
    return isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false;
}

/* === 轉換工具：GD 或 Imagick === */
function media_encoder_can_convert() {
    return (function_exists('imagewebp') || class_exists('Imagick'));
}

function media_encoder_convert_file_to_webp($src_path, $quality = 82) {
    $quality = max(1, min(100, intval($quality)));
    
    // 檢查來源檔案
    if (!file_exists($src_path)) {
        media_encoder_log_error('來源檔案不存在', array('path' => $src_path));
        return new WP_Error('missing_file', '來源檔案不存在');
    }
    
    if (!is_readable($src_path)) {
        media_encoder_log_error('來源檔案無法讀取', array('path' => $src_path));
        return new WP_Error('unreadable_file', '來源檔案無法讀取');
    }
    
    $ext = strtolower(pathinfo($src_path, PATHINFO_EXTENSION));
    if (!in_array($ext, array('jpg','jpeg','png'))) {
        return new WP_Error('bad_type', '僅支援 JPEG/PNG');
    }
    
    $dest_path = preg_replace('/\.(jpe?g|png)$/i', '.webp', $src_path);
    
    // 檢查目標目錄是否可寫
    $dest_dir = dirname($dest_path);
    if (!is_writable($dest_dir)) {
        media_encoder_log_error('目標目錄無法寫入', array('dir' => $dest_dir));
        return new WP_Error('unwritable_dir', '目標目錄無法寫入');
    }

    // 使用 Imagick 優先
    if (class_exists('Imagick')) {
        try {
            $im = new Imagick($src_path);
            $im->setImageFormat('webp');
            $im->setImageCompressionQuality($quality);
            
            $write_result = $im->writeImage($dest_path);
            $im->clear();
            $im->destroy();
            
            if (!$write_result || !file_exists($dest_path)) {
                media_encoder_log_error('Imagick 寫入失敗', array('src' => $src_path, 'dest' => $dest_path));
                return new WP_Error('imagick_write_failed', 'Imagick 寫入失敗');
            }
            
            return array('path' => $dest_path);
        } catch (Exception $e) {
            media_encoder_log_error('Imagick 轉換錯誤', array('error' => $e->getMessage(), 'src' => $src_path));
            return new WP_Error('imagick_error', $e->getMessage());
        }
    }

    // 後備：GD
    if (!function_exists('imagewebp')) {
        return new WP_Error('no_encoder', '伺服器未啟用 WebP 編碼（缺少 Imagick 或 GD imagewebp）');
    }
    
    try {
        if (in_array($ext, array('jpg','jpeg'))) {
            $img = imagecreatefromjpeg($src_path);
        } else {
            $img = imagecreatefrompng($src_path);
            if ($img) {
                imagepalettetotruecolor($img);
                imagealphablending($img, true);
                imagesavealpha($img, true);
            }
        }
        
        if (!$img) {
            media_encoder_log_error('GD 影像解碼失敗', array('src' => $src_path));
            return new WP_Error('decode_failed', '影像解碼失敗');
        }
        
        $result = imagewebp($img, $dest_path, $quality);
        imagedestroy($img);
        
        if (!$result || !file_exists($dest_path)) {
            media_encoder_log_error('GD WebP 編碼失敗', array('src' => $src_path, 'dest' => $dest_path));
            return new WP_Error('encode_failed', 'WebP 編碼失敗');
        }
        
        return array('path' => $dest_path);
    } catch (Exception $e) {
        media_encoder_log_error('GD 轉換錯誤', array('error' => $e->getMessage(), 'src' => $src_path));
        return new WP_Error('gd_error', $e->getMessage());
    }
}

/* === 上傳時轉換 === */
function media_encoder_convert_on_upload($metadata, $attachment_id) {
    $settings = media_encoder_get_settings();
    if ($settings['enabled'] !== 'on') return $metadata;
    if (!media_encoder_can_convert()) return $metadata;

    $file = get_attached_file($attachment_id);
    $mime = get_post_mime_type($attachment_id);
    
    if (!in_array($mime, array('image/jpeg','image/png'))) return $metadata;

    // 轉換原圖
    $res = media_encoder_convert_file_to_webp($file, $settings['quality']);
    if (!is_wp_error($res) && file_exists($res['path'])) {
        if ($settings['replace_original'] === 'on') {
            // 以 webp 覆蓋附件檔案
            if (@unlink($file)) {
                update_attached_file($attachment_id, $res['path']);
                wp_update_post(array('ID' => $attachment_id, 'post_mime_type' => 'image/webp'));
                
                // 更新元數據檔案路徑
                if (isset($metadata['file'])) {
                    $metadata['file'] = str_replace(basename($metadata['file']), basename($res['path']), $metadata['file']);
                }
            } else {
                media_encoder_log_error('無法刪除原始檔案', array('file' => $file));
            }
        }
    } else if (is_wp_error($res)) {
        media_encoder_log_error('原圖轉換失敗', array('error' => $res->get_error_message(), 'file' => $file));
    }

    // 轉換各尺寸
    if (!empty($metadata['sizes']) && is_array($metadata['sizes'])) {
        $upload_dir = wp_upload_dir();
        $base_dir = trailingslashit($upload_dir['basedir']);
        $base_file_dir = trailingslashit(pathinfo($metadata['file'], PATHINFO_DIRNAME));
        
        foreach ($metadata['sizes'] as $size_key => $size_info) {
            $size_path = $base_dir . $base_file_dir . $size_info['file'];
            if (file_exists($size_path)) {
                $r = media_encoder_convert_file_to_webp($size_path, $settings['quality']);
                if (!is_wp_error($r) && file_exists($r['path'])) {
                    if ($settings['replace_original'] === 'on') {
                        if (@unlink($size_path)) {
                            $metadata['sizes'][$size_key]['file'] = basename($r['path']);
                        } else {
                            media_encoder_log_error('無法刪除尺寸檔案', array('file' => $size_path));
                        }
                    }
                } else if (is_wp_error($r)) {
                    media_encoder_log_error('尺寸轉換失敗', array('error' => $r->get_error_message(), 'file' => $size_path));
                }
            }
        }
    }

    return $metadata;
}

/* === AJAX：預覽 === */
function media_encoder_ajax_preview() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('權限不足');
    }
    
    if (!check_ajax_referer('media_encoder_ajax', false, false)) {
        wp_send_json_error('安全驗證失敗');
    }
    
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if (!$id) {
        wp_send_json_error('缺少附件 ID');
    }
    
    $file = get_attached_file($id);
    if (!$file || !file_exists($file)) {
        wp_send_json_error('附件不存在');
    }
    
    $mime = get_post_mime_type($id);
    if (!in_array($mime, array('image/jpeg','image/png'))) {
        wp_send_json_error('僅支援 JPEG/PNG');
    }

    $settings = media_encoder_get_settings();
    $quality = $settings['quality'];
    $preview_path = preg_replace('/\.(jpe?g|png)$/i', '.preview.webp', $file);
    
    // 清理舊的預覽檔案
    if (file_exists($preview_path)) {
        @unlink($preview_path);
    }
    
    $r = media_encoder_convert_file_to_webp($file, $quality);
    if (is_wp_error($r)) {
        wp_send_json_error($r->get_error_message());
    }

    // 複製到預覽路徑，避免覆蓋正式 webp
    $copy_success = @copy($r['path'], $preview_path);
    if (!$copy_success) {
        media_encoder_log_error('預覽檔案複製失敗', array('src' => $r['path'], 'dest' => $preview_path));
        wp_send_json_error('預覽檔案建立失敗');
    }
    
    $orig_size = filesize($file);
    $webp_size = file_exists($preview_path) ? filesize($preview_path) : (file_exists($r['path']) ? filesize($r['path']) : 0);
    $saving_percent = ($orig_size > 0 && $webp_size > 0) ? round((1 - ($webp_size / $orig_size)) * 100) : null;
    
    $upload_dir = wp_upload_dir();
    $preview_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $preview_path);
    
    // 確保 URL 正確
    if (strpos($preview_url, $upload_dir['baseurl']) !== 0) {
        media_encoder_log_error('預覽 URL 產生錯誤', array('url' => $preview_url, 'baseurl' => $upload_dir['baseurl']));
        $preview_url = null;
    }

    wp_send_json_success(array(
        'original_size' => $orig_size,
        'webp_size' => $webp_size,
        'original_size_human' => size_format($orig_size, 2),
        'webp_size_human' => size_format($webp_size, 2),
        'saving_percent' => $saving_percent,
        'preview_url' => $preview_url,
    ));
}
add_action('wp_ajax_media_encoder_preview', 'media_encoder_ajax_preview');

/* === AJAX：批次轉換 === */
function media_encoder_ajax_bulk() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('權限不足');
    }
    
    if (!check_ajax_referer('media_encoder_ajax', false, false)) {
        wp_send_json_error('安全驗證失敗');
    }
    
    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
    $limit = isset($_POST['limit']) ? max(1, min(100, intval($_POST['limit']))) : 10;

    $q = new WP_Query(array(
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'posts_per_page' => $limit,
        'offset' => $offset,
        'post_mime_type' => array('image/jpeg','image/png'),
        'fields' => 'ids',
        'orderby' => 'ID',
        'order' => 'ASC',
    ));

    $settings = media_encoder_get_settings();
    $converted = 0; 
    $skipped = 0;
    $errors = 0;
    
    foreach ($q->posts as $id) {
        $file = get_attached_file($id);
        if (!$file || !file_exists($file)) { 
            $skipped++; 
            continue; 
        }
        
        $webp_path = preg_replace('/\.(jpe?g|png)$/i', '.webp', $file);
        $need = (!file_exists($webp_path) || $settings['replace_original'] === 'on');
        
        if (!$need) { 
            $skipped++; 
            continue; 
        }
        
        $r = media_encoder_convert_file_to_webp($file, $settings['quality']);
        if (is_wp_error($r)) { 
            $errors++;
            media_encoder_log_error('批次轉換失敗', array('id' => $id, 'error' => $r->get_error_message()));
            continue; 
        }

        if ($settings['replace_original'] === 'on') {
            $meta = wp_get_attachment_metadata($id);
            
            // 刪除原檔案並更新附件
            if (@unlink($file)) {
                update_attached_file($id, $r['path']);
                wp_update_post(array('ID' => $id, 'post_mime_type' => 'image/webp'));
                
                // 處理各尺寸
                if (!empty($meta['sizes'])) {
                    $upload_dir = wp_upload_dir();
                    $base_dir = trailingslashit($upload_dir['basedir']);
                    $base_file_dir = trailingslashit(pathinfo($meta['file'], PATHINFO_DIRNAME));
                    
                    foreach ($meta['sizes'] as $k => $info) {
                        $size_path = $base_dir . $base_file_dir . $info['file'];
                        if (file_exists($size_path)) {
                            $rr = media_encoder_convert_file_to_webp($size_path, $settings['quality']);
                            if (!is_wp_error($rr) && file_exists($rr['path'])) {
                                if (@unlink($size_path)) {
                                    $meta['sizes'][$k]['file'] = basename($rr['path']);
                                }
                            }
                        }
                    }
                }
                
                // 更新檔案路徑並儲存元數據
                if (isset($meta['file'])) {
                    $meta['file'] = str_replace(basename($meta['file']), basename($r['path']), $meta['file']);
                    wp_update_attachment_metadata($id, $meta);
                }
            } else {
                $errors++;
                media_encoder_log_error('無法刪除原檔案進行替換', array('id' => $id, 'file' => $file));
                continue;
            }
        }
        
        $converted++;
    }

    $done = (count($q->posts) < $limit);
    
    wp_send_json_success(array(
        'processed' => count($q->posts),
        'converted' => $converted,
        'skipped' => $skipped,
        'errors' => $errors,
        'done' => $done,
    ));
}
add_action('wp_ajax_media_encoder_bulk', 'media_encoder_ajax_bulk');
