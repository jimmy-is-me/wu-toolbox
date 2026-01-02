<?php
if (!defined('ABSPATH')) exit;

/*
 * WumetaxToolkit - Premium Client Dashboard
 * Version: 3.0 - Professional Full-Screen Dashboard
 * 
 * FEATURES:
 * - Cloudflare Analytics Integration (cached)
 * - SSL/HTTPS Status Check
 * - Disk Space Monitor
 * - Service Records & Payment History
 * - Zero performance impact (all cached)
 */

// ===== Constants =====

define('WU_CF_TOKEN', 'F1X1B2v5Q5E43lCb1gIej0LNpgUtnhKkFZLDHA-u');
define('WU_CF_CACHE_TIME', HOUR_IN_SECONDS * 12); // 12 hours

// ===== Menu Registration =====

add_action('admin_menu', function() {
	// 主儀表板頁面
	add_menu_page(
		'網站儀表板',
		'網站儀表板',
		'read',
		'wu-client-dashboard',
		'wu_render_client_dashboard_page',
		'dashicons-dashboard',
		2
	);
	
	// 管理設定頁
	add_submenu_page(
		'wumetax-toolkit',
		'儀表板設定',
		'儀表板設定',
		'manage_options',
		'wu-dashboard-settings',
		'wu_dashboard_settings_page'
	);
}, 5);

// ===== Options Initialization =====

add_action('admin_init', function() {
	add_option('wu_dashboard_enabled', 1);
	add_option('wu_dashboard_site_status', 'normal');
	add_option('wu_dashboard_status_note', '');
	add_option('wu_dashboard_recent_work', array());
	add_option('wu_dashboard_services', array(
		array('name' => '定期備份', 'detail' => '每日備份，預設僅保留3天', 'enabled' => true),
		array('name' => '系統安全監控', 'detail' => '24/7 自動監控', 'enabled' => true),
		array('name' => '功能更新維護', 'detail' => '每月檢查更新', 'enabled' => true),
		array('name' => '效能優化', 'detail' => '持續監控優化', 'enabled' => true),
	));
	add_option('wu_dashboard_hosting_plan', 'image');
	add_option('wu_dashboard_hosting_cpu', 'Intel Xeon E5-2680v4');
	add_option('wu_dashboard_hosting_ram', '4096'); // MB
	add_option('wu_dashboard_hosting_rating', '優良');
	add_option('wu_dashboard_disk_total', '5120'); // MB
	add_option('wu_dashboard_payments', array());
});

// ===== Main Dashboard Page =====

function wu_render_client_dashboard_page() {
	if (!get_option('wu_dashboard_enabled', 1)) {
		echo '<div class="wrap"><h1>儀表板未啟用</h1><p>請聯絡管理員啟用此功能。</p></div>';
		return;
	}
	
	// 載入設定
	$status = get_option('wu_dashboard_site_status', 'normal');
	$status_note = get_option('wu_dashboard_status_note', '');
	$recent_work = get_option('wu_dashboard_recent_work', array());
	$services = get_option('wu_dashboard_services', array());
	$hosting_plan = get_option('wu_dashboard_hosting_plan', 'image');
	$hosting_cpu = get_option('wu_dashboard_hosting_cpu', '');
	$hosting_ram = get_option('wu_dashboard_hosting_ram', '4096');
	$hosting_rating = get_option('wu_dashboard_hosting_rating', '優良');
	$disk_total = get_option('wu_dashboard_disk_total', '5120');
	$payments = get_option('wu_dashboard_payments', array());
	
	// 系統資訊
	$php_version = PHP_VERSION;
	$wp_memory = WP_MEMORY_LIMIT;
	
	// SSL 檢查
	$ssl_status = wu_check_ssl_status();
	
	// 磁碟空間
	$disk_used = wu_get_disk_usage();
	$disk_percentage = ($disk_used / $disk_total) * 100;
	
	// Cloudflare 流量
	$cf_data = wu_get_cloudflare_analytics();
	
	// 主機方案名稱
	$plan_names = array(
		'onepage' => '一頁式主機方案',
		'image' => '形象網站主機方案',
		'ecommerce' => '電商主機方案'
	);
	$plan_name = $plan_names[$hosting_plan] ?? '標準主機方案';
	
	// 狀態配置
	$status_config = array(
		'normal' => array('label' => '正常運作中', 'color' => '#10b981', 'icon' => '✓', 'bg' => '#ecfdf5'),
		'watching' => array('label' => '觀察中', 'color' => '#f59e0b', 'icon' => '👁', 'bg' => '#fef3c7'),
		'handling' => array('label' => '處理中', 'color' => '#3b82f6', 'icon' => '🔧', 'bg' => '#dbeafe')
	);
	$current_status = $status_config[$status] ?? $status_config['normal'];
	
	// 載入樣式
	wu_dashboard_styles();
	
	?>
	<div class="wu-dashboard-wrapper">
		
		<!-- 頁首 -->
		<div class="wu-header">
			<h1 class="wu-title">網站管理儀表板</h1>
			<div class="wu-subtitle">即時監控與服務透明化</div>
		</div>
		
		<!-- 狀態總覽區 -->
		<div class="wu-section wu-status-section" style="background:<?php echo $current_status['bg']; ?>;border-left:4px solid <?php echo $current_status['color']; ?>;">
			<div class="wu-status-grid">
				<div class="wu-status-main">
					<div class="wu-status-icon" style="color:<?php echo $current_status['color']; ?>;">
						<?php echo $current_status['icon']; ?>
					</div>
					<div>
						<h2 class="wu-status-label" style="color:<?php echo $current_status['color']; ?>;">
							<?php echo esc_html($current_status['label']); ?>
						</h2>
						<p class="wu-status-desc">網站整體狀態</p>
					</div>
				</div>
				
				<div class="wu-status-indicators">
					<!-- SSL 狀態 -->
					<div class="wu-indicator">
						<span class="wu-indicator-icon" style="color:<?php echo $ssl_status['color']; ?>;">🔒</span>
						<div>
							<div class="wu-indicator-label">SSL 憑證</div>
							<div class="wu-indicator-value" style="color:<?php echo $ssl_status['color']; ?>;">
								<?php echo $ssl_status['label']; ?>
							</div>
						</div>
					</div>
					
					<!-- 磁碟空間 -->
					<div class="wu-indicator">
						<span class="wu-indicator-icon" style="color:<?php echo $disk_percentage > 80 ? '#f59e0b' : '#10b981'; ?>;">💾</span>
						<div>
							<div class="wu-indicator-label">磁碟空間</div>
							<div class="wu-indicator-value">
								<?php echo number_format($disk_used / 1024, 2); ?> / <?php echo number_format($disk_total / 1024, 2); ?> GB
							</div>
							<div class="wu-progress-bar">
								<div class="wu-progress-fill" style="width:<?php echo min($disk_percentage, 100); ?>%;background:<?php echo $disk_percentage > 80 ? '#f59e0b' : '#10b981'; ?>;"></div>
							</div>
						</div>
					</div>
				</div>
			</div>
			
			<?php if (!empty($status_note)): ?>
			<div class="wu-status-note">
				<?php echo nl2br(esc_html($status_note)); ?>
			</div>
			<?php endif; ?>
		</div>
		
		<!-- 流量統計區 (Cloudflare) -->
		<div class="wu-section">
			<h2 class="wu-section-title">
				<span class="wu-section-icon">📊</span>
				流量統計
				<span class="wu-section-badge">過去 30 天</span>
			</h2>
			
			<?php if ($cf_data['success']): ?>
			<div class="wu-metrics-grid">
				<div class="wu-metric-card">
					<div class="wu-metric-icon" style="background:#dbeafe;color:#3b82f6;">📈</div>
					<div class="wu-metric-content">
						<div class="wu-metric-value"><?php echo number_format($cf_data['requests']); ?></div>
						<div class="wu-metric-label">總請求數</div>
					</div>
				</div>
				
				<div class="wu-metric-card">
					<div class="wu-metric-icon" style="background:#ecfdf5;color:#10b981;">👥</div>
					<div class="wu-metric-content">
						<div class="wu-metric-value"><?php echo number_format($cf_data['visitors']); ?></div>
						<div class="wu-metric-label">獨立訪客</div>
					</div>
				</div>
				
				<div class="wu-metric-card">
					<div class="wu-metric-icon" style="background:#fef3c7;color:#f59e0b;">📄</div>
					<div class="wu-metric-content">
						<div class="wu-metric-value"><?php echo number_format($cf_data['pageviews']); ?></div>
						<div class="wu-metric-label">頁面瀏覽</div>
					</div>
				</div>
				
				<div class="wu-metric-card">
					<div class="wu-metric-icon" style="background:#f3e8ff;color:#a855f7;">🌍</div>
					<div class="wu-metric-content">
						<div class="wu-metric-value"><?php echo number_format($cf_data['bandwidth'] / 1024 / 1024 / 1024, 2); ?> GB</div>
						<div class="wu-metric-label">總流量</div>
					</div>
				</div>
			</div>
			<?php else: ?>
			<div class="wu-info-box">
				<span class="wu-info-icon">ℹ️</span>
				資料尚未同步，請稍後重新整理
			</div>
			<?php endif; ?>
		</div>
		
		<!-- 主機規格 -->
		<div class="wu-section">
			<h2 class="wu-section-title">
				<span class="wu-section-icon">🖥️</span>
				主機規格
				<span class="wu-section-badge" style="background:#10b981;"><?php echo esc_html($plan_name); ?></span>
			</h2>
			
			<div class="wu-info-grid">
				<div class="wu-info-item">
					<div class="wu-info-label">處理器</div>
					<div class="wu-info-value"><?php echo esc_html($hosting_cpu); ?></div>
				</div>
				<div class="wu-info-item">
					<div class="wu-info-label">WordPress 記憶體</div>
					<div class="wu-info-value"><?php echo esc_html($wp_memory); ?></div>
				</div>
				<div class="wu-info-item">
					<div class="wu-info-label">PHP 版本</div>
					<div class="wu-info-value"><?php echo esc_html($php_version); ?></div>
				</div>
				<div class="wu-info-item">
					<div class="wu-info-label">評估等級</div>
					<div class="wu-info-value">
						<span class="wu-badge wu-badge-success"><?php echo esc_html($hosting_rating); ?></span>
					</div>
				</div>
			</div>
		</div>
		
		<!-- WooCommerce 訂單 (如有安裝) -->
		<?php if (class_exists('WooCommerce')): ?>
		<div class="wu-section">
			<h2 class="wu-section-title">
				<span class="wu-section-icon">🛒</span>
				訂單統計
			</h2>
			
			<?php
			$today_orders = wu_safe_get_orders_count_today();
			$month_orders = wu_safe_get_orders_count_month();
			$processing = wu_safe_get_processing_orders_count();
			?>
			
			<div class="wu-metrics-grid">
				<div class="wu-metric-card">
					<div class="wu-metric-icon" style="background:#dbeafe;color:#3b82f6;">📦</div>
					<div class="wu-metric-content">
						<div class="wu-metric-value"><?php echo number_format($today_orders); ?></div>
						<div class="wu-metric-label">今日訂單</div>
					</div>
				</div>
				
				<div class="wu-metric-card">
					<div class="wu-metric-icon" style="background:#fef3c7;color:#f59e0b;">⏳</div>
					<div class="wu-metric-content">
						<div class="wu-metric-value"><?php echo number_format($processing); ?></div>
						<div class="wu-metric-label">處理中</div>
					</div>
				</div>
				
				<div class="wu-metric-card">
					<div class="wu-metric-icon" style="background:#ecfdf5;color:#10b981;">✅</div>
					<div class="wu-metric-content">
						<div class="wu-metric-value"><?php echo number_format($month_orders); ?></div>
						<div class="wu-metric-label">本月訂單</div>
					</div>
				</div>
			</div>
		</div>
		<?php endif; ?>
		
		<!-- 最近處理紀錄 -->
		<?php if (!empty($recent_work)): ?>
		<div class="wu-section">
			<h2 class="wu-section-title">
				<span class="wu-section-icon">🔄</span>
				最近處理紀錄
			</h2>
			
			<div class="wu-timeline">
				<?php 
				usort($recent_work, function($a, $b) {
					return strtotime($b['date']) - strtotime($a['date']);
				});
				
				foreach (array_slice($recent_work, 0, 5) as $work): 
				?>
				<div class="wu-timeline-item">
					<div class="wu-timeline-date"><?php echo esc_html(date('Y/m/d', strtotime($work['date']))); ?></div>
					<div class="wu-timeline-content">
						<div class="wu-timeline-title"><?php echo esc_html($work['title']); ?></div>
						<?php if (!empty($work['note'])): ?>
						<div class="wu-timeline-note"><?php echo nl2br(esc_html($work['note'])); ?></div>
						<?php endif; ?>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>
		
		<!-- 服務項目 -->
		<div class="wu-section">
			<h2 class="wu-section-title">
				<span class="wu-section-icon">📋</span>
				目前包含的服務
			</h2>
			
			<div class="wu-services-grid">
				<?php foreach ($services as $service): ?>
					<?php if (!empty($service['enabled'])): ?>
					<div class="wu-service-card">
						<span class="wu-service-check">✓</span>
						<div>
							<div class="wu-service-name"><?php echo esc_html($service['name']); ?></div>
							<div class="wu-service-detail"><?php echo esc_html($service['detail']); ?></div>
						</div>
					</div>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		</div>
		
		<!-- 款項紀錄 (僅管理員) -->
		<?php if (!empty($payments) && current_user_can('manage_options')): ?>
		<div class="wu-section">
			<h2 class="wu-section-title">
				<span class="wu-section-icon">💰</span>
				款項收費紀錄
			</h2>
			
			<div class="wu-table-wrapper">
				<table class="wu-table">
					<thead>
						<tr>
							<th>日期</th>
							<th>項目</th>
							<th style="text-align:right;">金額</th>
							<th style="text-align:center;">狀態</th>
						</tr>
					</thead>
					<tbody>
						<?php 
						usort($payments, function($a, $b) {
							return strtotime($b['date']) - strtotime($a['date']);
						});
						
						foreach ($payments as $payment): 
						?>
						<tr>
							<td><?php echo esc_html(date('Y/m/d', strtotime($payment['date']))); ?></td>
							<td><?php echo esc_html($payment['item']); ?></td>
							<td style="text-align:right;font-weight:600;">NT$ <?php echo number_format($payment['amount']); ?></td>
							<td style="text-align:center;">
								<?php if ($payment['status'] === 'paid'): ?>
									<span class="wu-badge wu-badge-success">已付款</span>
								<?php else: ?>
									<span class="wu-badge wu-badge-warning">待付款</span>
								<?php endif; ?>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php endif; ?>
		
		<!-- 聯絡資訊 -->
		<div class="wu-section wu-contact-section">
			<div class="wu-contact-header">
				<h3 class="wu-contact-title">WUMETAX 末特數位科技</h3>
				<p class="wu-contact-subtitle">網站維運管理單位</p>
			</div>
			
			<div class="wu-contact-links">
				<a href="https://wumetax.com/contact-us/" target="_blank" class="wu-contact-btn">
					<span class="wu-contact-icon">🌐</span>
					聯絡我們表單
				</a>
				
				<a href="https://line.me/R/ti/p/@081pjqol" target="_blank" class="wu-contact-btn">
					<span class="wu-contact-icon">💬</span>
					LINE 官方帳號
				</a>
			</div>
			
			<p class="wu-contact-footer">有任何問題歡迎隨時與我們聯絡</p>
		</div>
		
	</div>
	<?php
}

// ===== Helper Functions =====

// SSL 狀態檢查 (使用快取)
function wu_check_ssl_status() {
	$cache_key = 'wu_ssl_status';
	$cached = get_transient($cache_key);
	
	if ($cached !== false) {
		return $cached;
	}
	
	$result = array(
		'valid' => is_ssl(),
		'label' => is_ssl() ? '有效' : '未啟用',
		'color' => is_ssl() ? '#10b981' : '#f59e0b'
	);
	
	set_transient($cache_key, $result, DAY_IN_SECONDS);
	
	return $result;
}

// 磁碟使用量 (使用快取)
function wu_get_disk_usage() {
	$cache_key = 'wu_disk_usage';
	$cached = get_transient($cache_key);
	
	if ($cached !== false) {
		return $cached;
	}
	
	$upload_dir = wp_upload_dir();
	$size = 0;
	
	try {
		$path = $upload_dir['basedir'];
		if (is_dir($path)) {
			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
			);
			
			foreach ($iterator as $file) {
				if ($file->isFile()) {
					$size += $file->getSize();
				}
			}
		}
	} catch (Exception $e) {
		$size = 0;
	}
	
	$size_mb = round($size / 1024 / 1024, 2);
	
	set_transient($cache_key, $size_mb, HOUR_IN_SECONDS * 12);
	
	return $size_mb;
}

// Cloudflare Analytics (使用快取)
function wu_get_cloudflare_analytics() {
	$cache_key = 'wu_cf_analytics';
	$cached = get_transient($cache_key);
	
	if ($cached !== false) {
		return $cached;
	}
	
	$result = array(
		'success' => false,
		'requests' => 0,
		'visitors' => 0,
		'pageviews' => 0,
		'bandwidth' => 0
	);
	
	// 僅在管理後台執行
	if (!is_admin()) {
		return $result;
	}
	
	$domain = parse_url(home_url(), PHP_URL_HOST);
	$zone_id = wu_get_cloudflare_zone_id($domain);
	
	if (!$zone_id) {
		set_transient($cache_key, $result, HOUR_IN_SECONDS * 6);
		return $result;
	}
	
	$end_date = date('Y-m-d');
	$start_date = date('Y-m-d', strtotime('-30 days'));
	
	$url = "https://api.cloudflare.com/client/v4/zones/{$zone_id}/analytics/dashboard?since={$start_date}&until={$end_date}";
	
	$response = wp_remote_get($url, array(
		'timeout' => 10,
		'headers' => array(
			'Authorization' => 'Bearer ' . WU_CF_TOKEN,
			'Content-Type' => 'application/json'
		)
	));
	
	if (is_wp_error($response)) {
		set_transient($cache_key, $result, HOUR_IN_SECONDS * 6);
		return $result;
	}
	
	$body = json_decode(wp_remote_retrieve_body($response), true);
	
	if (!empty($body['success']) && !empty($body['result'])) {
		$data = $body['result']['totals'];
		
		$result = array(
			'success' => true,
			'requests' => $data['requests']['all'] ?? 0,
			'visitors' => $data['uniques']['all'] ?? 0,
			'pageviews' => $data['pageviews']['all'] ?? 0,
			'bandwidth' => $data['bandwidth']['all'] ?? 0
		);
	}
	
	set_transient($cache_key, $result, WU_CF_CACHE_TIME);
	
	return $result;
}

// 取得 Cloudflare Zone ID (使用快取)
function wu_get_cloudflare_zone_id($domain) {
	$cache_key = 'wu_cf_zone_id_' . md5($domain);
	$cached = get_transient($cache_key);
	
	if ($cached !== false) {
		return $cached;
	}
	
	$url = "https://api.cloudflare.com/client/v4/zones?name={$domain}";
	
	$response = wp_remote_get($url, array(
		'timeout' => 10,
		'headers' => array(
			'Authorization' => 'Bearer ' . WU_CF_TOKEN,
			'Content-Type' => 'application/json'
		)
	));
	
	if (is_wp_error($response)) {
		return false;
	}
	
	$body = json_decode(wp_remote_retrieve_body($response), true);
	
	if (!empty($body['success']) && !empty($body['result'][0]['id'])) {
		$zone_id = $body['result'][0]['id'];
		set_transient($cache_key, $zone_id, DAY_IN_SECONDS * 7);
		return $zone_id;
	}
	
	return false;
}

// WooCommerce 訂單統計 (快取)
function wu_safe_get_orders_count_today() {
	if (!class_exists('WooCommerce')) return 0;
	
	$cache_key = 'wu_orders_today_' . date('Ymd');
	$cached = get_transient($cache_key);
	
	if ($cached !== false) {
		return $cached;
	}
	
	global $wpdb;
	$count = $wpdb->get_var("
		SELECT COUNT(ID)
		FROM {$wpdb->prefix}posts
		WHERE post_type = 'shop_order'
		AND post_status IN ('wc-processing', 'wc-completed', 'wc-pending')
		AND DATE(post_date) = CURDATE()
	");
	
	$count = $count ?: 0;
	set_transient($cache_key, $count, HOUR_IN_SECONDS);
	
	return $count;
}

function wu_safe_get_orders_count_month() {
	if (!class_exists('WooCommerce')) return 0;
	
	$cache_key = 'wu_orders_month_' . date('Ym');
	$cached = get_transient($cache_key);
	
	if ($cached !== false) {
		return $cached;
	}
	
	global $wpdb;
	$count = $wpdb->get_var("
		SELECT COUNT(ID)
		FROM {$wpdb->prefix}posts
		WHERE post_type = 'shop_order'
		AND post_status IN ('wc-processing', 'wc-completed', 'wc-pending')
		AND YEAR(post_date) = YEAR(CURDATE())
		AND MONTH(post_date) = MONTH(CURDATE())
	");
	
	$count = $count ?: 0;
	set_transient($cache_key, $count, HOUR_IN_SECONDS * 12);
	
	return $count;
}

function wu_safe_get_processing_orders_count() {
	if (!class_exists('WooCommerce')) return 0;
	
	global $wpdb;
	return (int) $wpdb->get_var("
		SELECT COUNT(ID)
		FROM {$wpdb->prefix}posts
		WHERE post_type = 'shop_order'
		AND post_status = 'wc-processing'
	");
}

// ===== Settings Page =====

function wu_dashboard_settings_page() {
	if (!current_user_can('manage_options')) {
		wp_die('權限不足');
	}
	
	// 儲存設定
	if (isset($_POST['wu_dashboard_save'])) {
		check_admin_referer('wu_dashboard_settings');
		
		update_option('wu_dashboard_enabled', isset($_POST['enabled']) ? 1 : 0);
		update_option('wu_dashboard_site_status', sanitize_text_field($_POST['site_status'] ?? 'normal'));
		update_option('wu_dashboard_status_note', sanitize_textarea_field($_POST['status_note'] ?? ''));
		
		// 處理紀錄
		$recent_work = array();
		if (!empty($_POST['work_titles'])) {
			foreach ($_POST['work_titles'] as $index => $title) {
				if (!empty($title)) {
					$recent_work[] = array(
						'title' => sanitize_text_field($title),
						'date' => sanitize_text_field($_POST['work_dates'][$index] ?? ''),
						'note' => sanitize_textarea_field($_POST['work_notes'][$index] ?? '')
					);
				}
			}
		}
		update_option('wu_dashboard_recent_work', $recent_work);
		
		// 服務項目
		$services = array();
		if (!empty($_POST['service_names'])) {
			foreach ($_POST['service_names'] as $index => $name) {
				if (!empty($name)) {
					$services[] = array(
						'name' => sanitize_text_field($name),
						'detail' => sanitize_text_field($_POST['service_details'][$index] ?? ''),
						'enabled' => isset($_POST['service_enabled'][$index])
					);
				}
			}
		}
		update_option('wu_dashboard_services', $services);
		
		// 主機規格
		update_option('wu_dashboard_hosting_plan', sanitize_text_field($_POST['hosting_plan'] ?? 'image'));
		update_option('wu_dashboard_hosting_cpu', sanitize_text_field($_POST['hosting_cpu'] ?? ''));
		update_option('wu_dashboard_hosting_ram', sanitize_text_field($_POST['hosting_ram'] ?? ''));
		update_option('wu_dashboard_hosting_rating', sanitize_text_field($_POST['hosting_rating'] ?? ''));
		update_option('wu_dashboard_disk_total', intval($_POST['disk_total'] ?? 5120));
		
		// 款項紀錄
		$payments = array();
		if (!empty($_POST['payment_items'])) {
			foreach ($_POST['payment_items'] as $index => $item) {
				if (!empty($item)) {
					$payments[] = array(
						'date' => sanitize_text_field($_POST['payment_dates'][$index] ?? ''),
						'item' => sanitize_text_field($item),
						'amount' => intval($_POST['payment_amounts'][$index] ?? 0),
						'status' => sanitize_text_field($_POST['payment_statuses'][$index] ?? 'pending')
					);
				}
			}
		}
		update_option('wu_dashboard_payments', $payments);
		
		// 清除快取
		delete_transient('wu_cf_analytics');
		delete_transient('wu_ssl_status');
		delete_transient('wu_disk_usage');
		
		echo '<div class="notice notice-success is-dismissible"><p><strong>✅ 設定已儲存並清除快取</strong></p></div>';
	}
	
	$enabled = get_option('wu_dashboard_enabled', 1);
	$site_status = get_option('wu_dashboard_site_status', 'normal');
	$status_note = get_option('wu_dashboard_status_note', '');
	$recent_work = get_option('wu_dashboard_recent_work', array());
	$services = get_option('wu_dashboard_services', array());
	$hosting_plan = get_option('wu_dashboard_hosting_plan', 'image');
	$hosting_cpu = get_option('wu_dashboard_hosting_cpu', '');
	$hosting_ram = get_option('wu_dashboard_hosting_ram', '');
	$hosting_rating = get_option('wu_dashboard_hosting_rating', '');
	$disk_total = get_option('wu_dashboard_disk_total', 5120);
	$payments = get_option('wu_dashboard_payments', array());
	
	// 隱藏 TOKEN
	$token_display = substr(WU_CF_TOKEN, 0, 8) . str_repeat('*', 20) . substr(WU_CF_TOKEN, -4);
	
	?>
	<div class="wrap">
		<h1>⚙️ 客戶儀表板設定</h1>
		
		<div class="notice notice-info" style="padding:15px;">
			<p style="margin:0;"><strong>💡 功能說明</strong></p>
			<ul style="margin:8px 0 0 20px;line-height:1.8;">
				<li>此頁面僅管理員可見</li>
				<li>Cloudflare Token: <code><?php echo $token_display; ?></code></li>
				<li>流量數據每 12 小時更新一次</li>
				<li>磁碟空間每 12 小時掃描一次</li>
			</ul>
		</div>
		
		<form method="post" style="background:#fff;padding:25px;border:1px solid #ddd;border-radius:5px;margin-top:20px;">
			<?php wp_nonce_field('wu_dashboard_settings'); ?>
			
			<table class="form-table">
				
				<!-- 啟用控制 -->
				<tr>
					<th scope="row"><label>啟用儀表板</label></th>
					<td>
						<label>
							<input type="checkbox" name="enabled" value="1" <?php checked(1, $enabled); ?>>
							<strong>啟用客戶儀表板功能</strong>
						</label>
					</td>
				</tr>
				
				<!-- 網站狀態 -->
				<tr>
					<th scope="row"><label>網站狀態</label></th>
					<td>
						<select name="site_status">
							<option value="normal" <?php selected($site_status, 'normal'); ?>>✓ 正常運作中</option>
							<option value="watching" <?php selected($site_status, 'watching'); ?>>👁 觀察中</option>
							<option value="handling" <?php selected($site_status, 'handling'); ?>>🔧 處理中</option>
						</select>
						<br>
						<textarea name="status_note" rows="2" class="large-text" style="margin-top:10px;" placeholder="選填:狀態說明"><?php echo esc_textarea($status_note); ?></textarea>
					</td>
				</tr>
				
				<!-- 最近處理紀錄 -->
				<tr>
					<th scope="row"><label>最近處理紀錄</label></th>
					<td>
						<div id="work-records-container">
							<?php 
							if (empty($recent_work)) {
								$recent_work = array(array('title' => '', 'date' => '', 'note' => ''));
							}
							foreach ($recent_work as $work): 
							?>
							<div style="background:#f9f9f9;padding:15px;margin-bottom:10px;border-radius:4px;">
								<input type="text" name="work_titles[]" value="<?php echo esc_attr($work['title']); ?>" placeholder="處理項目" class="regular-text" style="margin-bottom:8px;">
								<input type="date" name="work_dates[]" value="<?php echo esc_attr($work['date']); ?>" style="margin-bottom:8px;">
								<textarea name="work_notes[]" rows="2" class="large-text" placeholder="說明"><?php echo esc_textarea($work['note']); ?></textarea>
							</div>
							<?php endforeach; ?>
						</div>
						<button type="button" class="button" onclick="addWorkRecord()">新增紀錄</button>
					</td>
				</tr>
				
				<!-- 服務項目 -->
				<tr>
					<th scope="row"><label>服務項目</label></th>
					<td>
						<div id="services-container">
							<?php 
							if (empty($services)) {
								$services = array(array('name' => '', 'detail' => '', 'enabled' => true));
							}
							foreach ($services as $index => $service): 
							?>
							<div style="background:#f9f9f9;padding:15px;margin-bottom:10px;border-radius:4px;">
								<label style="display:block;margin-bottom:8px;">
									<input type="checkbox" name="service_enabled[<?php echo $index; ?>]" <?php checked(!empty($service['enabled'])); ?>>
									<strong>啟用</strong>
								</label>
								<input type="text" name="service_names[]" value="<?php echo esc_attr($service['name']); ?>" placeholder="服務名稱" class="regular-text" style="margin-bottom:8px;">
								<input type="text" name="service_details[]" value="<?php echo esc_attr($service['detail']); ?>" placeholder="細節" class="large-text">
							</div>
							<?php endforeach; ?>
						</div>
						<button type="button" class="button" onclick="addService()">新增服務</button>
					</td>
				</tr>
				
				<!-- 主機規格 -->
				<tr>
					<th scope="row"><label>主機規格</label></th>
					<td>
						<select name="hosting_plan" style="margin-bottom:10px;">
							<option value="onepage" <?php selected($hosting_plan, 'onepage'); ?>>一頁式主機</option>
							<option value="image" <?php selected($hosting_plan, 'image'); ?>>形象網站主機</option>
							<option value="ecommerce" <?php selected($hosting_plan, 'ecommerce'); ?>>電商主機</option>
						</select>
						<br>
						<input type="text" name="hosting_cpu" value="<?php echo esc_attr($hosting_cpu); ?>" placeholder="處理器名稱" class="regular-text">
						<br>
						<input type="text" name="hosting_ram" value="<?php echo esc_attr($hosting_ram); ?>" placeholder="記憶體 (MB)" class="regular-text" style="margin-top:8px;">
						<br>
						<input type="text" name="hosting_rating" value="<?php echo esc_attr($hosting_rating); ?>" placeholder="評估" class="regular-text" style="margin-top:8px;">
						<br>
						<input type="number" name="disk_total" value="<?php echo esc_attr($disk_total); ?>" placeholder="磁碟總量 (MB)" class="regular-text" style="margin-top:8px;">
						<p class="description">預設 5120 MB = 5 GB</p>
					</td>
				</tr>
				
				<!-- 款項紀錄 -->
				<tr>
					<th scope="row"><label>款項紀錄</label></th>
					<td>
						<div id="payments-container">
							<?php 
							if (empty($payments)) {
								$payments = array(array('date' => '', 'item' => '', 'amount' => '', 'status' => 'pending'));
							}
							foreach ($payments as $payment): 
							?>
							<div style="background:#f9f9f9;padding:15px;margin-bottom:10px;border-radius:4px;display:grid;grid-template-columns:120px 1fr 120px 100px;gap:10px;">
								<input type="date" name="payment_dates[]" value="<?php echo esc_attr($payment['date']); ?>">
								<input type="text" name="payment_items[]" value="<?php echo esc_attr($payment['item']); ?>" placeholder="項目">
								<input type="number" name="payment_amounts[]" value="<?php echo esc_attr($payment['amount']); ?>" placeholder="金額">
								<select name="payment_statuses[]">
									<option value="pending" <?php selected($payment['status'], 'pending'); ?>>待付款</option>
									<option value="paid" <?php selected($payment['status'], 'paid'); ?>>已付款</option>
								</select>
							</div>
							<?php endforeach; ?>
						</div>
						<button type="button" class="button" onclick="addPayment()">新增款項</button>
					</td>
				</tr>
				
			</table>
			
			<?php submit_button('儲存設定', 'primary large', 'wu_dashboard_save'); ?>
		</form>
	</div>
	
	<script>
	function addWorkRecord() {
		document.getElementById('work-records-container').insertAdjacentHTML('beforeend', 
			'<div style="background:#f9f9f9;padding:15px;margin-bottom:10px;border-radius:4px;">' +
			'<input type="text" name="work_titles[]" placeholder="處理項目" class="regular-text" style="margin-bottom:8px;">' +
			'<input type="date" name="work_dates[]" style="margin-bottom:8px;">' +
			'<textarea name="work_notes[]" rows="2" class="large-text" placeholder="說明"></textarea>' +
			'</div>'
		);
	}
	
	function addService() {
		var index = document.querySelectorAll('#services-container > div').length;
		document.getElementById('services-container').insertAdjacentHTML('beforeend',
			'<div style="background:#f9f9f9;padding:15px;margin-bottom:10px;border-radius:4px;">' +
			'<label style="display:block;margin-bottom:8px;">' +
			'<input type="checkbox" name="service_enabled[' + index + ']" checked><strong>啟用</strong>' +
			'</label>' +
			'<input type="text" name="service_names[]" placeholder="服務名稱" class="regular-text" style="margin-bottom:8px;">' +
			'<input type="text" name="service_details[]" placeholder="細節" class="large-text">' +
			'</div>'
		);
	}
	
	function addPayment() {
		document.getElementById('payments-container').insertAdjacentHTML('beforeend',
			'<div style="background:#f9f9f9;padding:15px;margin-bottom:10px;border-radius:4px;display:grid;grid-template-columns:120px 1fr 120px 100px;gap:10px;">' +
			'<input type="date" name="payment_dates[]">' +
			'<input type="text" name="payment_items[]" placeholder="項目">' +
			'<input type="number" name="payment_amounts[]" placeholder="金額">' +
			'<select name="payment_statuses[]"><option value="pending">待付款</option><option value="paid">已付款</option></select>' +
			'</div>'
		);
	}
	</script>
	<?php
}

// ===== CSS Styles =====

function wu_dashboard_styles() {
	?>
	<style>
	.wu-dashboard-wrapper {
		max-width: 1400px;
		margin: 20px auto;
		padding: 0 20px;
		font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
	}
	
	.wu-header {
		margin-bottom: 40px;
	}
	
	.wu-title {
		font-size: 36px;
		font-weight: 700;
		margin: 0 0 8px;
		color: #111827;
	}
	
	.wu-subtitle {
		font-size: 16px;
		color: #6b7280;
	}
	
	.wu-section {
		background: #fff;
		border: 1px solid #e5e7eb;
		border-radius: 12px;
		padding: 30px;
		margin-bottom: 24px;
	}
	
	.wu-section-title {
		font-size: 20px;
		font-weight: 600;
		margin: 0 0 24px;
		color: #111827;
		display: flex;
		align-items: center;
		gap: 8px;
	}
	
	.wu-section-icon {
		font-size: 24px;
	}
	
	.wu-section-badge {
		font-size: 12px;
		padding: 4px 12px;
		background: #3b82f6;
		color: #fff;
		border-radius: 20px;
		font-weight: 500;
		margin-left: auto;
	}
	
	.wu-status-section {
		border-width: 1px 1px 1px 4px;
	}
	
	.wu-status-grid {
		display: grid;
		grid-template-columns: 1fr 2fr;
		gap: 30px;
		align-items: center;
	}
	
	.wu-status-main {
		display: flex;
		align-items: center;
		gap: 20px;
	}
	
	.wu-status-icon {
		font-size: 72px;
		line-height: 1;
	}
	
	.wu-status-label {
		font-size: 32px;
		font-weight: 700;
		margin: 0 0 4px;
	}
	
	.wu-status-desc {
		font-size: 14px;
		color: #6b7280;
		margin: 0;
	}
	
	.wu-status-indicators {
		display: grid;
		grid-template-columns: repeat(2, 1fr);
		gap: 20px;
	}
	
	.wu-indicator {
		display: flex;
		align-items: flex-start;
		gap: 12px;
	}
	
	.wu-indicator-icon {
		font-size: 28px;
	}
	
	.wu-indicator-label {
		font-size: 13px;
		color: #6b7280;
		margin-bottom: 4px;
	}
	
	.wu-indicator-value {
		font-size: 16px;
		font-weight: 600;
		color: #111827;
	}
	
	.wu-progress-bar {
		width: 100%;
		height: 6px;
		background: #e5e7eb;
		border-radius: 3px;
		margin-top: 8px;
		overflow: hidden;
	}
	
	.wu-progress-fill {
		height: 100%;
		border-radius: 3px;
		transition: width 0.3s;
	}
	
	.wu-status-note {
		margin-top: 20px;
		padding: 16px;
		background: rgba(255, 255, 255, 0.8);
		border-radius: 8px;
		color: #374151;
		font-size: 14px;
		line-height: 1.6;
	}
	
	.wu-metrics-grid {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
		gap: 20px;
	}
	
	.wu-metric-card {
		display: flex;
		align-items: center;
		gap: 16px;
		padding: 20px;
		background: #f9fafb;
		border: 1px solid #e5e7eb;
		border-radius: 10px;
		transition: transform 0.2s, box-shadow 0.2s;
	}
	
	.wu-metric-card:hover {
		transform: translateY(-2px);
		box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
	}
	
	.wu-metric-icon {
		width: 56px;
		height: 56px;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 28px;
		border-radius: 12px;
	}
	
	.wu-metric-value {
		font-size: 28px;
		font-weight: 700;
		color: #111827;
		line-height: 1;
		margin-bottom: 4px;
	}
	
	.wu-metric-label {
		font-size: 13px;
		color: #6b7280;
	}
	
	.wu-info-grid {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
		gap: 20px;
	}
	
	.wu-info-item {
		padding: 16px;
		background: #f9fafb;
		border: 1px solid #e5e7eb;
		border-radius: 8px;
	}
	
	.wu-info-label {
		font-size: 13px;
		color: #6b7280;
		margin-bottom: 8px;
	}
	
	.wu-info-value {
		font-size: 18px;
		font-weight: 600;
		color: #111827;
	}
	
	.wu-badge {
		display: inline-block;
		padding: 4px 12px;
		border-radius: 20px;
		font-size: 12px;
		font-weight: 600;
	}
	
	.wu-badge-success {
		background: #d1fae5;
		color: #065f46;
	}
	
	.wu-badge-warning {
		background: #fef3c7;
		color: #92400e;
	}
	
	.wu-timeline {
		position: relative;
	}
	
	.wu-timeline-item {
		display: grid;
		grid-template-columns: 100px 1fr;
		gap: 20px;
		padding: 20px 0;
		border-bottom: 1px solid #e5e7eb;
	}
	
	.wu-timeline-item:last-child {
		border-bottom: none;
	}
	
	.wu-timeline-date {
		font-size: 14px;
		color: #6b7280;
		font-weight: 500;
	}
	
	.wu-timeline-title {
		font-size: 16px;
		font-weight: 600;
		color: #111827;
		margin-bottom: 8px;
	}
	
	.wu-timeline-note {
		font-size: 14px;
		color: #6b7280;
		line-height: 1.6;
	}
	
	.wu-services-grid {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
		gap: 16px;
	}
	
	.wu-service-card {
		display: flex;
		align-items: flex-start;
		gap: 12px;
		padding: 20px;
		background: #f9fafb;
		border: 1px solid #e5e7eb;
		border-radius: 8px;
	}
	
	.wu-service-check {
		color: #10b981;
		font-size: 24px;
		font-weight: 700;
	}
	
	.wu-service-name {
		font-size: 16px;
		font-weight: 600;
		color: #111827;
		margin-bottom: 4px;
	}
	
	.wu-service-detail {
		font-size: 13px;
		color: #6b7280;
		line-height: 1.5;
	}
	
	.wu-table-wrapper {
		overflow-x: auto;
	}
	
	.wu-table {
		width: 100%;
		border-collapse: collapse;
	}
	
	.wu-table th {
		padding: 12px;
		background: #f9fafb;
		border-bottom: 2px solid #e5e7eb;
		text-align: left;
		font-size: 13px;
		font-weight: 600;
		color: #6b7280;
	}
	
	.wu-table td {
		padding: 12px;
		border-bottom: 1px solid #f3f4f6;
		font-size: 14px;
		color: #374151;
	}
	
	.wu-table tr:last-child td {
		border-bottom: none;
	}
	
	.wu-contact-section {
		background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
		border: none;
		color: #fff;
		text-align: center;
	}
	
	.wu-contact-header {
		margin-bottom: 30px;
	}
	
	.wu-contact-title {
		font-size: 28px;
		font-weight: 700;
		margin: 0 0 8px;
		color: #fff;
	}
	
	.wu-contact-subtitle {
		font-size: 15px;
		color: rgba(255, 255, 255, 0.9);
		margin: 0;
	}
	
	.wu-contact-links {
		display: flex;
		justify-content: center;
		gap: 20px;
		flex-wrap: wrap;
		margin-bottom: 20px;
	}
	
	.wu-contact-btn {
		display: inline-flex;
		align-items: center;
		gap: 8px;
		padding: 12px 24px;
		background: rgba(255, 255, 255, 0.2);
		color: #fff;
		text-decoration: none;
		border-radius: 30px;
		font-size: 15px;
		font-weight: 600;
		transition: all 0.3s;
	}
	
	.wu-contact-btn:hover {
		background: rgba(255, 255, 255, 0.3);
		transform: translateY(-2px);
		color: #fff;
	}
	
	.wu-contact-icon {
		font-size: 20px;
	}
	
	.wu-contact-footer {
		font-size: 14px;
		color: rgba(255, 255, 255, 0.85);
		margin: 0;
	}
	
	.wu-info-box {
		padding: 16px 20px;
		background: #fef3c7;
		border: 1px solid #fcd34d;
		border-radius: 8px;
		color: #92400e;
		display: flex;
		align-items: center;
		gap: 12px;
	}
	
	.wu-info-icon {
		font-size: 24px;
	}
	
	@media (max-width: 768px) {
		.wu-status-grid {
			grid-template-columns: 1fr;
		}
		
		.wu-status-indicators {
			grid-template-columns: 1fr;
		}
		
		.wu-metrics-grid {
			grid-template-columns: 1fr;
		}
		
		.wu-timeline-item {
			grid-template-columns: 80px 1fr;
		}
	}
	</style>
	<?php
}
?>
