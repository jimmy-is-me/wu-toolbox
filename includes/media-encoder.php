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
	);
}

/* === 儲存設定 === */
function media_encoder_save_settings() {
	if (!current_user_can('manage_options')) return;
	if (!isset($_POST['media_encoder_save']) || !check_admin_referer('media_encoder_save', 'media_encoder_nonce')) return;

	update_option('media_encoder_enabled', isset($_POST['media_encoder_enabled']) ? sanitize_text_field($_POST['media_encoder_enabled']) : 'off');
	$quality = isset($_POST['media_encoder_quality']) ? max(1, min(100, intval($_POST['media_encoder_quality']))) : 82;
	update_option('media_encoder_quality', $quality);
	update_option('media_encoder_replace_original', isset($_POST['media_encoder_replace_original']) ? 'on' : 'off');
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
				<p><input type="submit" class="button-primary" name="media_encoder_save" value="儲存設定"></p>

				<h2>預覽模式</h2>
				<p>在啟動全域轉換前，先對單一影像進行測試壓縮。</p>
				<p>
					<select id="media-encoder-preview-attachment" style="min-width:260px;">
						<option value="">選擇媒體庫影像…（僅 JPEG/PNG）</option>
						<?php
						$imgs = get_posts(array(
							'post_type' => 'attachment',
							'posts_per_page' => 50,
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
}
				</pre>
			</div>
		</div>
	</div>

	<script>
	jQuery(function($){
		const nonce = '<?php echo wp_create_nonce('media_encoder_ajax'); ?>';
		$('#media-encoder-run-preview').on('click', function(){
			const id = $('#media-encoder-preview-attachment').val();
			if(!id){ alert('請先選擇一張圖片'); return; }
			$('#media-encoder-preview-result').show().text('處理中…');
			$.post(ajaxurl, { action: 'media_encoder_preview', _wpnonce: nonce, id: id }, function(res){
				if(!res || !res.success){ $('#media-encoder-preview-result').text(res && res.data ? res.data : '預覽失敗'); return; }
				const d = res.data;
				$('#media-encoder-preview-result').html(
					'原圖：' + d.original_size_human + ' → WebP：' + d.webp_size_human +
					(d.saving_percent !== null ? '（節省 ' + d.saving_percent + '%）' : '') +
					(d.preview_url ? '<div style="margin-top:8px;"><img src="' + d.preview_url + '" style="max-width:100%;height:auto;border:1px solid #eee;padding:4px;border-radius:6px;"></div>' : '')
				);
			});
		});

		$('#media-encoder-bulk-start').on('click', function(){
			let offset = 0; const limit = 10; let processed = 0; let converted = 0; let skipped = 0;
			$('#media-encoder-bulk-status').text('掃描中…');
			function step(){
				$.post(ajaxurl, { action: 'media_encoder_bulk', _wpnonce: nonce, offset: offset, limit: limit }, function(res){
					if(!res || !res.success){ $('#media-encoder-bulk-status').text(res && res.data ? res.data : '批次失敗'); return; }
					processed += res.data.processed; converted += res.data.converted; skipped += res.data.skipped; offset += limit;
					$('#media-encoder-bulk-status').text('已處理 ' + processed + '，轉換 ' + converted + '，略過 ' + skipped + (res.data.done ? '（完成）' : '…'));
					if(!res.data.done){ step(); }
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
	add_filter('wp_generate_attachment_metadata', 'media_encoder_convert_on_upload', 10, 2);
}
add_action('init', 'media_encoder_maybe_register_hooks');

/* === 轉換工具：GD 或 Imagick === */
function media_encoder_can_convert() {
	return (function_exists('imagewebp') || class_exists('Imagick'));
}

function media_encoder_convert_file_to_webp($src_path, $quality = 82) {
	$quality = max(1, min(100, intval($quality)));
	if (!file_exists($src_path)) return new WP_Error('missing_file', '來源檔案不存在');
	$ext = strtolower(pathinfo($src_path, PATHINFO_EXTENSION));
	if (!in_array($ext, array('jpg','jpeg','png'))) return new WP_Error('bad_type', '僅支援 JPEG/PNG');
	$dest_path = preg_replace('/\.(jpe?g|png)$/i', '.webp', $src_path);

	// 使用 Imagick 優先
	if (class_exists('Imagick')) {
		try {
			$im = new Imagick($src_path);
			$im->setImageFormat('webp');
			$im->setImageCompressionQuality($quality);
			$im->writeImage($dest_path);
			$im->clear();
			$im->destroy();
			return array('path' => $dest_path);
		} catch (Exception $e) {
			return new WP_Error('imagick_error', $e->getMessage());
		}
	}

	// 後備：GD
	if (!function_exists('imagewebp')) return new WP_Error('no_encoder', '伺服器未啟用 WebP 編碼（缺少 Imagick 或 GD imagewebp）');
	if (in_array($ext, array('jpg','jpeg'))) {
		$img = imagecreatefromjpeg($src_path);
	} else {
		$img = imagecreatefrompng($src_path);
		imagepalettetotruecolor($img);
		imagealphablending($img, true);
		imagesavealpha($img, true);
	}
	if (!$img) return new WP_Error('decode_failed', '影像解碼失敗');
	$result = imagewebp($img, $dest_path, $quality);
	imagedestroy($img);
	if (!$result) return new WP_Error('encode_failed', 'WebP 編碼失敗');
	return array('path' => $dest_path);
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
			@unlink($file);
			update_attached_file($attachment_id, $res['path']);
			wp_update_post(array('ID' => $attachment_id, 'post_mime_type' => 'image/webp'));
			$metadata['file'] = str_replace(basename($metadata['file']), basename($res['path']), $metadata['file']);
		}
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
				if (!is_wp_error($r) && file_exists($r['path']) && $settings['replace_original'] === 'on') {
					@unlink($size_path);
					$metadata['sizes'][$size_key]['file'] = basename($r['path']);
				}
			}
		}
	}

	return $metadata;
}

/* === AJAX：預覽 === */
function media_encoder_ajax_preview() {
	if (!current_user_can('manage_options')) wp_send_json_error('權限不足');
	check_ajax_referer('media_encoder_ajax');
	$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
	if (!$id) wp_send_json_error('缺少附件 ID');
	$file = get_attached_file($id);
	if (!$file || !file_exists($file)) wp_send_json_error('附件不存在');
	$mime = get_post_mime_type($id);
	if (!in_array($mime, array('image/jpeg','image/png'))) wp_send_json_error('僅支援 JPEG/PNG');

	$settings = media_encoder_get_settings();
	$quality = $settings['quality'];
	$preview_path = preg_replace('/\.(jpe?g|png)$/i', '.preview.webp', $file);
	if (file_exists($preview_path)) @unlink($preview_path);
	$r = media_encoder_convert_file_to_webp($file, $quality);
	if (is_wp_error($r)) wp_send_json_error($r->get_error_message());

	// 複製到預覽路徑，避免覆蓋正式 webp
	@copy($r['path'], $preview_path);
	$orig_size = filesize($file);
	$webp_size = file_exists($preview_path) ? filesize($preview_path) : (file_exists($r['path']) ? filesize($r['path']) : 0);
	$saving_percent = ($orig_size > 0 && $webp_size > 0) ? round((1 - ($webp_size / $orig_size)) * 100) : null;
	$upload_dir = wp_upload_dir();
	$preview_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $preview_path);

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
	if (!current_user_can('manage_options')) wp_send_json_error('權限不足');
	check_ajax_referer('media_encoder_ajax');
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
	$converted = 0; $skipped = 0;
	foreach ($q->posts as $id) {
		$file = get_attached_file($id);
		if (!$file || !file_exists($file)) { $skipped++; continue; }
		$webp_path = preg_replace('/\.(jpe?g|png)$/i', '.webp', $file);
		$need = (!file_exists($webp_path) || $settings['replace_original'] === 'on');
		if (!$need) { $skipped++; continue; }
		$r = media_encoder_convert_file_to_webp($file, $settings['quality']);
		if (is_wp_error($r)) { $skipped++; continue; }

		if ($settings['replace_original'] === 'on') {
			$meta = wp_get_attachment_metadata($id);
			@unlink($file);
			update_attached_file($id, $r['path']);
			wp_update_post(array('ID' => $id, 'post_mime_type' => 'image/webp'));
			if (!empty($meta['sizes'])) {
				$upload_dir = wp_upload_dir();
				$base_dir = trailingslashit($upload_dir['basedir']);
				$base_file_dir = trailingslashit(pathinfo($meta['file'], PATHINFO_DIRNAME));
				foreach ($meta['sizes'] as $k => $info) {
					$size_path = $base_dir . $base_file_dir . $info['file'];
					if (file_exists($size_path)) {
						$rr = media_encoder_convert_file_to_webp($size_path, $settings['quality']);
						if (!is_wp_error($rr) && file_exists($rr['path'])) {
							@unlink($size_path);
							$meta['sizes'][$k]['file'] = basename($rr['path']);
						}
					}
				}
				$meta['file'] = str_replace(basename($meta['file']), basename($r['path']), $meta['file']);
				wp_update_attachment_metadata($id, $meta);
			}
		}
		$converted++;
	}

	$done = (count($q->posts) < $limit);
	wp_send_json_success(array(
		'processed' => count($q->posts),
		'converted' => $converted,
		'skipped' => $skipped,
		'done' => $done,
	));
}
add_action('wp_ajax_media_encoder_bulk', 'media_encoder_ajax_bulk');