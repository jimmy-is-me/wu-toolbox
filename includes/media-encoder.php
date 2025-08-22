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
        'replace_original' => 'on', // 強制啟用以節省主機容量
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
    // 移除 replace_original 選項儲存，因為強制啟用
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
        <p>自動將上傳的圖像轉換為 WebP，以獲得更佳效能與更小檔案。系統會自動替換原圖以節省主機容量。</p>

        <div style="display:flex;gap:40px;flex-wrap:wrap;align-items:flex-start;">
            <form method="post" style="flex:1;min-width:320px;max-width:560px;">
                <?php wp_nonce_field('media_encoder_save', 'media_encoder_nonce'); ?>
                <h2>設定</h2>
                <p>
                    <label>
                        <input type="checkbox" name="media_encoder_enabled" value="on" <?php checked($settings['enabled'], 'on'); ?>> 啟用媒體編碼器
                    </label><br>
                    <small>啟用後，系統會在圖片上傳時自動轉換為 WebP 並替換原檔案。</small>
                </p>
                <p>
                    <label>品質（1–100）：<input type="number" name="media_encoder_quality" min="1" max="100" value="<?php echo esc_attr($quality); ?>" style="width:90px;"></label>
                    <br><small>建議 75–90。數值越高品質越好、檔案越大。</small>
                </p>
                <!-- 移除替換原圖選項，因為強制啟用 -->
                <div style="background:#e7f3ff;border:1px solid #0073aa;padding:10px;border-radius:4px;margin:10px 0;">
                    <strong>📁 檔案處理模式：</strong>自動替換原圖為 WebP 格式以節省主機容量
                </div>
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
                            'posts_per_page' => 5,
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
                <p>將目前媒體庫中的 JPEG/PNG 批量轉換為 WebP。系統會自動略過已經是 WebP 格式的檔案。</p>
                
                <!-- 批次設定 -->
                <div style="background:#f0f6fc;border:1px solid #0969da;padding:12px;border-radius:4px;margin:10px 0;">
                    <p><strong>⚙️ 批次設定：</strong></p>
                    <label>每批次處理數量：
                        <select id="media-encoder-batch-size" style="margin-left:8px;">
                            <option value="5">5 張</option>
                            <option value="10" selected>10 張</option>
                            <option value="20">20 張</option>
                            <option value="50">50 張</option>
                        </select>
                    </label>
                    <span style="margin-left:20px;">
                        <label>每批次預估時間：
                            <input type="number" id="media-encoder-batch-time" value="3" min="1" max="60" style="width:50px;margin-left:8px;"> 秒
                        </label>
                    </span>
                </div>
                
                <p>
                    <button type="button" class="button button-primary" id="media-encoder-bulk-start">開始批次轉換</button>
                    <button type="button" class="button" id="media-encoder-bulk-pause" style="display:none;">暫停</button>
                    <button type="button" class="button" id="media-encoder-bulk-resume" style="display:none;">繼續</button>
                </p>

                <!-- 進度顯示區域 -->
                <div id="media-encoder-progress-container" style="display:none;background:#f9f9f9;border:1px solid #ddd;padding:15px;border-radius:6px;margin:15px 0;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                        <strong>轉換進度</strong>
                        <div id="media-encoder-countdown" style="font-weight:bold;color:#0073aa;"></div>
                    </div>
                    
                    <!-- 進度條 -->
                    <div style="background:#e0e0e0;height:20px;border-radius:10px;overflow:hidden;margin-bottom:10px;">
                        <div id="media-encoder-progress-bar" style="background:linear-gradient(90deg, #00a0d2, #0073aa);height:100%;width:0%;transition:width 0.3s ease;"></div>
                    </div>
                    
                    <!-- 統計資訊 -->
                    <div style="display:grid;grid-template-columns:repeat(auto-fit, minwidth(120px, 1fr));gap:10px;margin-bottom:10px;">
                        <div><strong>已處理：</strong><span id="stats-processed">0</span></div>
                        <div><strong>已轉換：</strong><span id="stats-converted">0</span></div>
                        <div><strong>已略過：</strong><span id="stats-skipped">0</span></div>
                        <div><strong>錯誤：</strong><span id="stats-errors">0</span></div>
                        <div><strong>節省空間：</strong><span id="stats-saved-space">0 KB</span></div>
                    </div>
                    
                    <!-- 目前處理的檔案詳情 -->
                    <div id="media-encoder-current-files" style="max-height:200px;overflow-y:auto;background:#fff;border:1px solid #e0e0e0;border-radius:4px;padding:8px;"></div>
                </div>
            </form>

            <div style="flex:1;min-width:320px;">
                <h2>系統資訊</h2>
                <div style="background:#f9f9f9;padding:12px;border-radius:4px;margin-bottom:20px;">
                    <h4>WebP 支援狀態</h4>
                    <p>
                        Imagick: <?php echo class_exists('Imagick') ? '<span style="color:green;">✅ 可用</span>' : '<span style="color:red;">❌ 不可用</span>'; ?><br>
                        GD WebP: <?php echo function_exists('imagewebp') ? '<span style="color:green;">✅ 可用</span>' : '<span style="color:red;">❌ 不可用</span>'; ?>
                    </p>
                    <?php if (!media_encoder_can_convert()): ?>
                    <p style="color:red;"><strong>⚠️ 警告：</strong>您的伺服器不支援 WebP 轉換。請聯繫主機商啟用 Imagick 或 GD WebP 支援。</p>
                    <?php endif; ?>
                </div>

                <h2>檔案管理說明</h2>
                <div style="background:#fff3cd;border:1px solid #ffeaa7;padding:12px;border-radius:4px;">
                    <h4>💾 節省空間模式</h4>
                    <p>系統採用<strong>替換原檔案</strong>模式運作，所有 JPEG/PNG 檔案轉換後會直接替換為 WebP 格式，有效節省主機儲存空間。</p>
                    <ul style="margin:10px 0 10px 20px;">
                        <li>✅ 原檔案會被 WebP 完全取代</li>
                        <li>✅ 所有縮圖尺寸同步轉換</li>
                        <li>✅ 媒體庫資訊自動更新</li>
                        <li>✅ 最大化節省儲存空間</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
    jQuery(function($){
        const nonce = '<?php echo esc_js(wp_create_nonce('media_encoder_ajax')); ?>';
        let bulkRunning = false;
        let bulkPaused = false;
        let countdownInterval = null;
        let totalEstimatedTime = 0;
        let remainingTime = 0;
        let totalImages = 0;
        let processedImages = 0;
        
        // 預覽功能
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
                    html += ' <strong style="color:green;">（節省 ' + d.saving_percent + '%）</strong>';
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

        // 格式化檔案大小
        function formatBytes(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // 格式化時間
        function formatTime(seconds) {
            if (seconds < 60) return seconds + ' 秒';
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = seconds % 60;
            return minutes + ' 分 ' + remainingSeconds + ' 秒';
        }

        // 更新倒數計時
        function updateCountdown() {
            if (remainingTime <= 0) {
                $('#media-encoder-countdown').text('處理中...');
                if (countdownInterval) {
                    clearInterval(countdownInterval);
                    countdownInterval = null;
                }
                return;
            }
            
            $('#media-encoder-countdown').text('預估剩餘時間：' + formatTime(remainingTime));
            remainingTime--;
        }

        // 開始倒數計時
        function startCountdown(estimatedSeconds) {
            remainingTime = estimatedSeconds;
            if (countdownInterval) clearInterval(countdownInterval);
            
            updateCountdown();
            countdownInterval = setInterval(updateCountdown, 1000);
        }

        // 停止倒數計時
        function stopCountdown() {
            if (countdownInterval) {
                clearInterval(countdownInterval);
                countdownInterval = null;
            }
            $('#media-encoder-countdown').text('');
        }

        // 更新進度條
        function updateProgress() {
            if (totalImages === 0) return;
            
            const percentage = Math.round((processedImages / totalImages) * 100);
            $('#media-encoder-progress-bar').css('width', percentage + '%');
        }

        // 添加檔案處理結果到顯示區域
        function addFileResult(fileData) {
            const $container = $('#media-encoder-current-files');
            
            let html = '<div style="border-bottom:1px solid #eee;padding:8px;margin-bottom:8px;font-size:13px;">';
            html += '<div style="font-weight:bold;color:#333;">' + fileData.filename + '</div>';
            
            if (fileData.converted) {
                html += '<div style="color:green;">✅ 轉換成功：' + fileData.original_size + ' → ' + fileData.webp_size;
                if (fileData.saving_percent) {
                    html += ' <span style="font-weight:bold;">（節省 ' + fileData.saving_percent + '%）</span>';
                }
                html += '</div>';
            } else if (fileData.skipped) {
                html += '<div style="color:orange;">⚠️ 已略過：' + (fileData.reason || '已是 WebP 格式') + '</div>';
            } else if (fileData.error) {
                html += '<div style="color:red;">❌ 轉換失敗：' + fileData.error + '</div>';
            }
            
            html += '</div>';
            
            $container.prepend(html);
            
            // 限制顯示最近的 20 個結果
            const $items = $container.children();
            if ($items.length > 20) {
                $items.slice(20).remove();
            }
            
            // 滾動到頂部顯示最新結果
            $container.scrollTop(0);
        }

        // 批次轉換主函數
        $('#media-encoder-bulk-start').on('click', function(){
            if (bulkRunning) return;
            
            const batchSize = parseInt($('#media-encoder-batch-size').val()) || 10;
            const batchTime = parseInt($('#media-encoder-batch-time').val()) || 3;
            
            // 初始化變數
            let offset = 0;
            let processed = 0;
            let converted = 0;
            let skipped = 0;
            let errors = 0;
            let totalSavedSpace = 0;
            
            bulkRunning = true;
            bulkPaused = false;
            
            const $startBtn = $(this);
            const $pauseBtn = $('#media-encoder-bulk-pause');
            const $resumeBtn = $('#media-encoder-bulk-resume');
            const $progressContainer = $('#media-encoder-progress-container');
            
            $startBtn.prop('disabled', true).text('處理中...');
            $pauseBtn.show();
            $progressContainer.show();
            
            // 清空之前的結果
            $('#media-encoder-current-files').empty();
            
            // 首先獲取總圖片數量來計算進度
            $.post(ajaxurl, {
                action: 'media_encoder_get_total_count',
                _wpnonce: nonce
            }, function(countRes) {
                if (countRes && countRes.success) {
                    totalImages = countRes.data.total;
                    processedImages = 0;
                    
                    // 計算總預估時間
                    const estimatedBatches = Math.ceil(totalImages / batchSize);
                    totalEstimatedTime = estimatedBatches * batchTime;
                    
                    if (totalImages === 0) {
                        $('#media-encoder-current-files').html('<div style="text-align:center;color:#666;padding:20px;">沒有需要轉換的 JPEG/PNG 圖片</div>');
                        resetBulkUI();
                        return;
                    }
                    
                    startCountdown(totalEstimatedTime);
                    $('#media-encoder-current-files').html('<div style="text-align:center;color:#0073aa;padding:10px;">找到 ' + totalImages + ' 張圖片需要轉換，預估時間：' + formatTime(totalEstimatedTime) + '</div>');
                    
                    // 開始處理
                    setTimeout(step, 1000);
                } else {
                    resetBulkUI();
                    alert('無法獲取圖片總數，請重試');
                }
            });

            function step(){
                if (!bulkRunning || bulkPaused) return;
                
                $.post(ajaxurl, { 
                    action: 'media_encoder_bulk', 
                    _wpnonce: nonce, 
                    offset: offset, 
                    limit: batchSize 
                }, function(res){
                    if(!res || !res.success){ 
                        const errorMsg = res && res.data ? res.data : '批次失敗';
                        $('#media-encoder-current-files').prepend('<div style="color:red;padding:10px;background:#ffe6e6;border-radius:4px;margin-bottom:10px;">❌ ' + errorMsg + '</div>');
                        resetBulkUI();
                        return; 
                    }
                    
                    processed += res.data.processed; 
                    converted += res.data.converted; 
                    skipped += res.data.skipped;
                    errors += res.data.errors || 0;
                    totalSavedSpace += res.data.saved_space || 0;
                    processedImages = processed;
                    offset += batchSize;
                    
                    // 更新統計顯示
                    $('#stats-processed').text(processed);
                    $('#stats-converted').text(converted);
                    $('#stats-skipped').text(skipped);
                    $('#stats-errors').text(errors);
                    $('#stats-saved-space').text(formatBytes(totalSavedSpace));
                    
                    // 更新進度條
                    updateProgress();
                    
                    // 顯示本批次處理的檔案詳情
                    if (res.data.files && res.data.files.length > 0) {
                        res.data.files.forEach(function(file) {
                            addFileResult(file);
                        });
                    }
                    
                    if(res.data.done) {
                        stopCountdown();
                        let completionMsg = '<div style="color:green;padding:15px;background:#e8f5e8;border-radius:4px;margin:10px 0;text-align:center;">';
                        completionMsg += '<h4 style="margin:0 0 10px 0;">🎉 批次轉換完成！</h4>';
                        completionMsg += '<div>總共處理 ' + processed + ' 張圖片</div>';
                        completionMsg += '<div>成功轉換 ' + converted + ' 張</div>';
                        completionMsg += '<div>略過 ' + skipped + ' 張</div>';
                        if (errors > 0) completionMsg += '<div>錯誤 ' + errors + ' 張</div>';
                        if (totalSavedSpace > 0) completionMsg += '<div><strong>總共節省空間：' + formatBytes(totalSavedSpace) + '</strong></div>';
                        completionMsg += '</div>';
                        
                        $('#media-encoder-current-files').prepend(completionMsg);
                        resetBulkUI();
                    } else {
                        // 繼續下一批次，調整倒數時間
                        const remainingBatches = Math.ceil((totalImages - processed) / batchSize);
                        remainingTime = Math.max(0, remainingBatches * batchTime);
                        
                        setTimeout(step, batchTime * 1000);
                    }
                }).fail(function(){
                    $('#media-encoder-current-files').prepend('<div style="color:red;padding:10px;background:#ffe6e6;border-radius:4px;margin-bottom:10px;">❌ 網路錯誤，請重試</div>');
                    resetBulkUI();
                });
            }
        });

        // 暫停功能
        $('#media-encoder-bulk-pause').on('click', function() {
            bulkPaused = true;
            stopCountdown();
            $(this).hide();
            $('#media-encoder-bulk-resume').show();
            $('#media-encoder-countdown').text('⏸️ 已暫停');
        });

        // 繼續功能
        $('#media-encoder-bulk-resume').on('click', function() {
            bulkPaused = false;
            $(this).hide();
            $('#media-encoder-bulk-pause').show();
            
            // 重新開始倒數
            if (remainingTime > 0) {
                startCountdown(remainingTime);
            }
        });

        // 重置批次轉換 UI
        function resetBulkUI() {
            bulkRunning = false;
            bulkPaused = false;
            stopCountdown();
            
            $('#media-encoder-bulk-start').prop('disabled', false).text('開始批次轉換');
            $('#media-encoder-bulk-pause').hide();
            $('#media-encoder-bulk-resume').hide();
        }
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
}
add_action('init', 'media_encoder_maybe_register_hooks');

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

    // 轉換原圖並強制替換
    $res = media_encoder_convert_file_to_webp($file, $settings['quality']);
    if (!is_wp_error($res) && file_exists($res['path'])) {
        // 刪除原檔案並更新附件資訊
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
    } else if (is_wp_error($res)) {
        media_encoder_log_error('原圖轉換失敗', array('error' => $res->get_error_message(), 'file' => $file));
    }

    // 轉換各尺寸並強制替換
    if (!empty($metadata['sizes']) && is_array($metadata['sizes'])) {
        $upload_dir = wp_upload_dir();
        $base_dir = trailingslashit($upload_dir['basedir']);
        $base_file_dir = trailingslashit(pathinfo($metadata['file'], PATHINFO_DIRNAME));
        
        foreach ($metadata['sizes'] as $size_key => $size_info) {
            $size_path = $base_dir . $base_file_dir . $size_info['file'];
            if (file_exists($size_path)) {
                $r = media_encoder_convert_file_to_webp($size_path, $settings['quality']);
                if (!is_wp_error($r) && file_exists($r['path'])) {
                    if (@unlink($size_path)) {
                        $metadata['sizes'][$size_key]['file'] = basename($r['path']);
                        $metadata['sizes'][$size_key]['mime-type'] = 'image/webp';
                    } else {
                        media_encoder_log_error('無法刪除尺寸檔案', array('file' => $size_path));
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

/* === AJAX：獲取需轉換的圖片總數 === */
function media_encoder_ajax_get_total_count() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('權限不足');
    }
    
    if (!check_ajax_referer('media_encoder_ajax', false, false)) {
        wp_send_json_error('安全驗證失敗');
    }
    
    $count_query = new WP_Query(array(
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'posts_per_page' => -1,
        'post_mime_type' => array('image/jpeg','image/png'),
        'fields' => 'ids',
    ));
    
    wp_send_json_success(array(
        'total' => $count_query->found_posts
    ));
}
add_action('wp_ajax_media_encoder_get_total_count', 'media_encoder_ajax_get_total_count');

/* === AJAX：批次轉換（增強版） === */
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
    $files_details = array();
    $total_saved_space = 0;
    
    foreach ($q->posts as $id) {
        $file = get_attached_file($id);
        $filename = basename($file);
        $file_detail = array('id' => $id, 'filename' => $filename);
        
        if (!$file || !file_exists($file)) { 
            $skipped++;
            $file_detail['skipped'] = true;
            $file_detail['reason'] = '檔案不存在';
            $files_details[] = $file_detail;
            continue; 
        }
        
        $mime = get_post_mime_type($id);
        if ($mime === 'image/webp') {
            $skipped++;
            $file_detail['skipped'] = true;
            $file_detail['reason'] = '已是 WebP 格式';
            $files_details[] = $file_detail;
            continue;
        }
        
        $original_size = filesize($file);
        $r = media_encoder_convert_file_to_webp($file, $settings['quality']);
        
        if (is_wp_error($r)) { 
            $errors++;
            $file_detail['error'] = $r->get_error_message();
            $files_details[] = $file_detail;
            media_encoder_log_error('批次轉換失敗', array('id' => $id, 'error' => $r->get_error_message()));
            continue; 
        }

        $meta = wp_get_attachment_metadata($id);
        
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
                                $meta['sizes'][$k]['mime-type'] = 'image/webp';
                            }
                        }
                    }
                }
            }
            
            if (isset($meta['file'])) {
                $meta['file'] = str_replace(basename($meta['file']), basename($r['path']), $meta['file']);
            }
            
            wp_update_attachment_metadata($id, $meta);
            
            $converted++;
            $webp_size = filesize($r['path']);
            $saved_space = $original_size - $webp_size;
            $total_saved_space += $saved_space;
            
            $file_detail['converted'] = true;
            $file_detail['original_size'] = size_format($original_size, 2);
            $file_detail['webp_size'] = size_format($webp_size, 2);
            $file_detail['saving_percent'] = $original_size > 0 ? round(($saved_space / $original_size) * 100) : 0;
            
        } else {
            $errors++;
            $file_detail['error'] = '無法刪除原檔案';
            media_encoder_log_error('無法刪除原檔案進行替換', array('id' => $id, 'file' => $file));
        }
        
        $files_details[] = $file_detail;
    }

    $done = (count($q->posts) < $limit);
    
    wp_send_json_success(array(
        'processed' => count($q->posts),
        'converted' => $converted,
        'skipped' => $skipped,
        'errors' => $errors,
        'saved_space' => $total_saved_space,
        'files' => $files_details,
        'done' => $done,
    ));
}
add_action('wp_ajax_media_encoder_bulk', 'media_encoder_ajax_bulk');
