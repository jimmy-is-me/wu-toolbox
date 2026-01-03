<?php
if (!defined('ABSPATH')) exit;

/*
 * WumetaxToolkit - Unified Dashboard Widget
 * Version: 8.0 - Performance Optimized Media Quota
 * 
 * FEATURES:
 * - Incremental media size tracking (no directory scan)
 * - Thumbnail/derivative images calculation
 * - Real-time quota monitoring
 * - Zero performance impact
 */

// ===== Menu Registration =====

add_action('admin_menu', function() {
	add_submenu_page(
		'wumetax-toolkit',
		'儀表板設定',
		'儀表板設定',
		'manage_options',
		'wu-dashboard-settings',
		'wu_dashboard_settings_page'
	);
}, 999);

// ===== Options Initialization =====

add_action('admin_init', function() {
	add_option('wu_dashboard_enabled', 1);
	add_option('wu_dashboard_site_status', 'normal');
	add_option('wu_dashboard_status_note', '');
	add_option('wu_dashboard_recent_work', array());
	add_option('wu_dashboard_services', array(
		'主機與系統維運',
		'定期備份 (每日備份，保留3天)',
		'基礎資安防護',
		'系統更新管理',
		'效能監控優化',
		'技術支援服務'
	));
	add_option('wu_dashboard_hosting_plan', 'image');
	add_option('wu_dashboard_hosting_rating', '優良運作');
	add_option('wu_dashboard_disk_total', '5120');
	add_option('wu_dashboard_payments', array());
	
	// 媒體配額設定
	add_option('wu_media_used_bytes', 0);
	add_option('wu_media_quota_bytes', 10737418240); // 10 GB
	add_option('wu_media_last_sync', 0);
});

// ===== Dashboard Widget =====

add_action('wp_dashboard_setup', function() {
	if (!get_option('wu_dashboard_enabled', 1)) {
		return;
	}
	
	wp_add_dashboard_widget(
		'wu_unified_dashboard',
		'<span class="dashicons dashicons-dashboard"></span> 網站綜合儀表板',
		'wu_render_unified_dashboard',
		null,
		null,
		'normal',
		'high'
	);
});

// ===== Unified Dashboard Renderer =====

function wu_render_unified_dashboard() {
	$status = get_option('wu_dashboard_site_status', 'normal');
	$status_note = get_option('wu_dashboard_status_note', '');
	$ssl_valid = wu_check_ssl_status();
	$php_version = PHP_VERSION;
	$hosting_plan = get_option('wu_dashboard_hosting_plan', 'image');
	$hosting_rating = get_option('wu_dashboard_hosting_rating', '優良運作');
	$disk_total = get_option('wu_dashboard_disk_total', 5120);
	$disk_used = wu_get_site_disk_usage();
	$disk_percentage = ($disk_used / $disk_total) * 100;
	$wp_memory_limit = WP_MEMORY_LIMIT;
	$login_stats = wu_get_login_stats();
	$media_quota = wu_get_media_quota_info();
	$services = get_option('wu_dashboard_services', array());
	$recent_work = get_option('wu_dashboard_recent_work', array());
	$payments = get_option('wu_dashboard_payments', array());
	
	$status_config = array(
		'normal' => array('label' => '正常運作', 'color' => '#46b450'),
		'watching' => array('label' => '觀察中', 'color' => '#f0b849'),
		'handling' => array('label' => '處理中', 'color' => '#00a0d2')
	);
	$current_status = $status_config[$status] ?? $status_config['normal'];
	
	$plan_names = array(
		'onepage' => '一頁式主機',
		'image' => '形象網站主機',
		'ecommerce' => '電商主機'
	);
	$plan_name = $plan_names[$hosting_plan] ?? '標準主機';
	
	if (!empty($recent_work)) {
		usort($recent_work, function($a, $b) {
			return strtotime($b['date']) - strtotime($a['date']);
		});
	}
	
	if (!empty($payments)) {
		usort($payments, function($a, $b) {
			return strtotime($b['date']) - strtotime($a['date']);
		});
	}
	
	?>
	<div class="wu-unified-container">
		
		<!-- Row 1: 網站狀態 + 系統資源 -->
		<div class="wu-dashboard-row">
			<div class="wu-dashboard-section">
				<div class="wu-section-header">網站狀態</div>
				<div class="wu-grid-4">
					<div class="wu-stat-box" style="border-left-color:<?php echo $current_status['color']; ?>;">
						<div class="wu-label">狀態</div>
						<div class="wu-value" style="color:<?php echo $current_status['color']; ?>;">
							<?php echo esc_html($current_status['label']); ?>
						</div>
					</div>
					
					<div class="wu-stat-box">
						<div class="wu-label">SSL/HTTPS</div>
						<div class="wu-value" style="color:<?php echo $ssl_valid ? '#46b450' : '#f0b849'; ?>;">
							<?php echo $ssl_valid ? '正常' : '未啟用'; ?>
						</div>
					</div>
					
					<div class="wu-stat-box">
						<div class="wu-label">PHP 版本</div>
						<div class="wu-value"><?php echo esc_html($php_version); ?></div>
					</div>
					
					<div class="wu-stat-box">
						<div class="wu-label">主機方案</div>
						<div class="wu-value" style="font-size:16px;"><?php echo esc_html($plan_name); ?></div>
						<div class="wu-meta">評估: <?php echo esc_html($hosting_rating); ?></div>
					</div>
				</div>
				
				<?php if (!empty($status_note)): ?>
				<div class="wu-alert"><?php echo nl2br(esc_html($status_note)); ?></div>
				<?php endif; ?>
			</div>
			
			<div class="wu-dashboard-section">
				<div class="wu-section-header">系統資源</div>
				<div class="wu-grid-3">
					<div class="wu-stat-box">
						<div class="wu-label">磁碟使用</div>
						<div class="wu-value"><?php echo number_format($disk_used, 0); ?> MB</div>
						<div class="wu-meta">
							/ <?php echo number_format($disk_total, 0); ?> MB
							<span style="color:<?php echo $disk_percentage > 80 ? '#dc3232' : '#46b450'; ?>;">
								(<?php echo number_format($disk_percentage, 1); ?>%)
							</span>
						</div>
						<div class="wu-progress">
							<div class="wu-progress-bar" style="width:<?php echo min($disk_percentage, 100); ?>%;background:<?php echo $disk_percentage > 80 ? '#dc3232' : '#46b450'; ?>;"></div>
						</div>
					</div>
					
					<div class="wu-stat-box">
						<div class="wu-label">WordPress 記憶體</div>
						<div class="wu-value"><?php echo esc_html($wp_memory_limit); ?></div>
					</div>
					
					<div class="wu-stat-box">
						<div class="wu-label">剩餘空間</div>
						<div class="wu-value"><?php echo number_format($disk_total - $disk_used, 0); ?> MB</div>
					</div>
				</div>
			</div>
		</div>
		
		<!-- Row 2: 登入統計 + 媒體配額 -->
		<div class="wu-dashboard-row">
			<div class="wu-dashboard-section">
				<div class="wu-section-header">登入統計</div>
				<div class="wu-grid-4">
					<div class="wu-stat-box">
						<div class="wu-label">管理員數</div>
						<div class="wu-value"><?php echo number_format($login_stats['total_admins']); ?></div>
					</div>
					<div class="wu-stat-box">
						<div class="wu-label">今日登入</div>
						<div class="wu-value"><?php echo number_format($login_stats['today_logins']); ?></div>
					</div>
					<div class="wu-stat-box">
						<div class="wu-label">本週登入</div>
						<div class="wu-value"><?php echo number_format($login_stats['week_logins']); ?></div>
					</div>
					<div class="wu-stat-box">
						<div class="wu-label">本月登入</div>
						<div class="wu-value"><?php echo number_format($login_stats['month_logins']); ?></div>
					</div>
				</div>
				
				<?php if (!empty($login_stats['recent_admins'])): ?>
				<table class="wu-table">
					<thead>
						<tr>
							<th>管理員</th>
							<th>最近登入</th>
							<th>IP 位址</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach (array_slice($login_stats['recent_admins'], 0, 3) as $admin): ?>
						<tr>
							<td><?php echo esc_html($admin['name']); ?></td>
							<td><?php echo esc_html($admin['time']); ?></td>
							<td><?php echo esc_html($admin['ip']); ?></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php endif; ?>
			</div>
			
			<div class="wu-dashboard-section">
				<div class="wu-section-header">媒體配額</div>
				<div class="wu-media-quota-display">
					<div class="wu-quota-main">
						<div class="wu-quota-status <?php echo esc_attr($media_quota['status_class']); ?>">
							<?php echo esc_html($media_quota['status_icon']); ?>
							<span><?php echo esc_html($media_quota['status_text']); ?></span>
						</div>
						<div class="wu-quota-usage">
							<div class="wu-quota-label">使用中</div>
							<div class="wu-quota-value">
								<?php echo esc_html($media_quota['used_formatted']); ?> 
								<span class="wu-quota-total">/ <?php echo esc_html($media_quota['quota_formatted']); ?></span>
							</div>
							<div class="wu-quota-percentage">使用率: <?php echo esc_html($media_quota['percentage']); ?>%</div>
						</div>
					</div>
					<div class="wu-progress">
						<div class="wu-progress-bar" style="width:<?php echo min($media_quota['percentage'], 100); ?>%;background:<?php echo $media_quota['bar_color']; ?>;"></div>
					</div>
					<div class="wu-quota-meta">
						<div>檔案數量: <?php echo number_format($media_quota['file_count']); ?></div>
						<div>剩餘空間: <?php echo esc_html($media_quota['remaining_formatted']); ?></div>
					</div>
				</div>
			</div>
		</div>
		
		<!-- Row 3: 服務項目 + 最近處理 -->
		<div class="wu-dashboard-row">
			<?php if (!empty($services)): ?>
			<div class="wu-dashboard-section">
				<div class="wu-section-header">維運服務項目</div>
				<ul class="wu-list">
					<?php foreach ($services as $service): ?>
					<li><span class="wu-icon">✓</span><?php echo esc_html($service); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php endif; ?>
			
			<?php if (!empty($recent_work)): ?>
			<div class="wu-dashboard-section">
				<div class="wu-section-header">最近處理紀錄</div>
				<div class="wu-timeline">
					<?php foreach (array_slice($recent_work, 0, 5) as $work): ?>
					<div class="wu-timeline-row">
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
		</div>
		
		<!-- Row 4: 款項紀錄 -->
		<?php if (current_user_can('manage_options') && !empty($payments)): ?>
		<div class="wu-dashboard-row">
			<div class="wu-dashboard-section wu-full-width">
				<div class="wu-section-header">款項紀錄</div>
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
						<?php foreach (array_slice($payments, 0, 10) as $payment): ?>
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
		
		<!-- Row 5: 聯絡資訊 -->
		<div class="wu-dashboard-row">
			<div class="wu-dashboard-section wu-full-width">
				<div class="wu-contact-box">
					<div class="wu-contact-name">WUMETAX 末特數位科技</div>
					<div class="wu-contact-role">網站維運管理單位</div>
					<div class="wu-contact-links">
						<span>聯絡信箱: <a href="mailto:contact@wumetax.com">contact@wumetax.com</a></span>
						<span>LINE: <a href="https://lin.ee/Lut7wCe" target="_blank">https://lin.ee/Lut7wCe</a></span>
					</div>
				</div>
			</div>
		</div>
		
	</div>
	
	<div class="wu-footer-note">
		<span class="dashicons dashicons-info"></span>
		所有統計資料每 6-12 小時自動更新
	</div>
	<?php
}

// ===== Media Quota Functions =====

function wu_get_media_quota_info() {
	$used_bytes = get_option('wu_media_used_bytes', 0);
	$quota_bytes = get_option('wu_media_quota_bytes', 10737418240);
	$file_count = wp_count_posts('attachment')->inherit ?? 0;
	
	$used_gb = $used_bytes / 1073741824;
	$quota_gb = $quota_bytes / 1073741824;
	$percentage = ($used_bytes / $quota_bytes) * 100;
	$remaining_bytes = max(0, $quota_bytes - $used_bytes);
	
	// 狀態判斷
	if ($percentage < 70) {
		$status_text = '正常';
		$status_icon = '🟢';
		$status_class = 'wu-status-normal';
		$bar_color = '#46b450';
	} elseif ($percentage < 90) {
		$status_text = '即將達上限';
		$status_icon = '🟡';
		$status_class = 'wu-status-warning';
		$bar_color = '#f0b849';
	} else {
		$status_text = '已接近上限';
		$status_icon = '🔴';
		$status_class = 'wu-status-danger';
		$bar_color = '#dc3232';
	}
	
	return array(
		'used_bytes' => $used_bytes,
		'quota_bytes' => $quota_bytes,
		'used_formatted' => number_format($used_gb, 2) . ' GB',
		'quota_formatted' => number_format($quota_gb, 0) . ' GB',
		'remaining_formatted' => wu_format_bytes($remaining_bytes),
		'percentage' => number_format($percentage, 1),
		'file_count' => $file_count,
		'status_text' => $status_text,
		'status_icon' => $status_icon,
		'status_class' => $status_class,
		'bar_color' => $bar_color
	);
}

function wu_format_bytes($bytes) {
	if ($bytes >= 1073741824) {
		return number_format($bytes / 1073741824, 2) . ' GB';
	} elseif ($bytes >= 1048576) {
		return number_format($bytes / 1048576, 2) . ' MB';
	} elseif ($bytes >= 1024) {
		return number_format($bytes / 1024, 2) . ' KB';
	}
	return $bytes . ' B';
}

// ===== Media Upload Hook (累加) =====

add_action('add_attachment', 'wu_media_add_size', 10, 1);

function wu_media_add_size($attachment_id) {
	$file_path = get_attached_file($attachment_id);
	
	if (!$file_path || !file_exists($file_path)) {
		return;
	}
	
	$total_size = 0;
	
	// 主檔案
	$total_size += filesize($file_path);
	
	// 取得所有衍生圖片 (thumbnail, medium, large, webp 等)
	$metadata = wp_get_attachment_metadata($attachment_id);
	
	if (!empty($metadata['sizes']) && is_array($metadata['sizes'])) {
		$upload_dir = wp_upload_dir();
		$base_dir = dirname($file_path);
		
		foreach ($metadata['sizes'] as $size_data) {
			if (!empty($size_data['file'])) {
				$derivative_path = $base_dir . '/' . $size_data['file'];
				if (file_exists($derivative_path)) {
					$total_size += filesize($derivative_path);
				}
			}
		}
	}
	
	// 累加到 option
	$current_used = get_option('wu_media_used_bytes', 0);
	$new_used = $current_used + $total_size;
	update_option('wu_media_used_bytes', $new_used);
}

// ===== Media Delete Hook (扣除) =====

add_action('delete_attachment', 'wu_media_subtract_size', 10, 1);

function wu_media_subtract_size($attachment_id) {
	$file_path = get_attached_file($attachment_id);
	
	if (!$file_path || !file_exists($file_path)) {
		return;
	}
	
	$total_size = 0;
	
	// 主檔案
	$total_size += filesize($file_path);
	
	// 取得所有衍生圖片
	$metadata = wp_get_attachment_metadata($attachment_id);
	
	if (!empty($metadata['sizes']) && is_array($metadata['sizes'])) {
		$base_dir = dirname($file_path);
		
		foreach ($metadata['sizes'] as $size_data) {
			if (!empty($size_data['file'])) {
				$derivative_path = $base_dir . '/' . $size_data['file'];
				if (file_exists($derivative_path)) {
					$total_size += filesize($derivative_path);
				}
			}
		}
	}
	
	// 從 option 扣除
	$current_used = get_option('wu_media_used_bytes', 0);
	$new_used = max(0, $current_used - $total_size);
	update_option('wu_media_used_bytes', $new_used);
}

// ===== Initial Sync (只執行一次) =====

add_action('admin_init', 'wu_maybe_init_media_quota');

function wu_maybe_init_media_quota() {
	$last_sync = get_option('wu_media_last_sync', 0);
	
	// 如果已同步過，跳出
	if ($last_sync > 0) {
		return;
	}
	
	// 使用 WP-Cron 背景執行（避免阻塞）
	if (!wp_next_scheduled('wu_media_quota_init_sync')) {
		wp_schedule_single_event(time() + 60, 'wu_media_quota_init_sync');
	}
}

add_action('wu_media_quota_init_sync', 'wu_perform_media_quota_init');

function wu_perform_media_quota_init() {
	global $wpdb;
	
	$attachments = $wpdb->get_results("
		SELECT ID 
		FROM {$wpdb->posts} 
		WHERE post_type = 'attachment' 
		LIMIT 1000
	");
	
	$total_size = 0;
	
	foreach ($attachments as $attachment) {
		$file_path = get_attached_file($attachment->ID);
		
		if (!$file_path || !file_exists($file_path)) {
			continue;
		}
		
		// 主檔案
		$total_size += filesize($file_path);
		
		// 衍生圖片
		$metadata = wp_get_attachment_metadata($attachment->ID);
		
		if (!empty($metadata['sizes']) && is_array($metadata['sizes'])) {
			$base_dir = dirname($file_path);
			
			foreach ($metadata['sizes'] as $size_data) {
				if (!empty($size_data['file'])) {
					$derivative_path = $base_dir . '/' . $size_data['file'];
					if (file_exists($derivative_path)) {
						$total_size += filesize($derivative_path);
					}
				}
			}
		}
	}
	
	update_option('wu_media_used_bytes', $total_size);
	update_option('wu_media_last_sync', time());
}

// ===== Helper Functions =====

function wu_check_ssl_status() {
	$cache_key = 'wu_ssl_check';
	$cached = get_transient($cache_key);
	
	if ($cached !== false) {
		return $cached;
	}
	
	$valid = is_ssl();
	set_transient($cache_key, $valid, DAY_IN_SECONDS);
	
	return $valid;
}

function wu_get_site_disk_usage() {
	$cache_key = 'wu_site_disk_usage';
	$cached = get_transient($cache_key);
	
	if ($cached !== false) {
		return $cached;
	}
	
	$site_path = ABSPATH;
	$size = 0;
	
	try {
		if (is_dir($site_path)) {
			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($site_path, RecursiveDirectoryIterator::SKIP_DOTS),
				RecursiveIteratorIterator::CATCH_GET_CHILD
			);
			
			foreach ($iterator as $file) {
				try {
					if ($file->isFile()) {
						$size += $file->getSize();
					}
				} catch (Exception $e) {
					continue;
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

function wu_get_login_stats() {
	$cache_key = 'wu_login_stats';
	$cached = get_transient($cache_key);
	
	if ($cached !== false) {
		return $cached;
	}
	
	$admins = get_users(array(
		'role' => 'administrator',
		'fields' => array('ID', 'display_name', 'user_login')
	));
	
	$today = strtotime('today');
	$week_ago = strtotime('-7 days');
	$month_ago = strtotime('-30 days');
	
	$stats = array(
		'total_admins' => count($admins),
		'today_logins' => 0,
		'week_logins' => 0,
		'month_logins' => 0,
		'recent_admins' => array()
	);
	
	foreach ($admins as $admin) {
		$last_login = get_user_meta($admin->ID, 'wu_last_login', true);
		$last_ip = get_user_meta($admin->ID, 'wu_last_ip', true);
		
		if (empty($last_login)) {
			continue;
		}
		
		if ($last_login >= $today) {
			$stats['today_logins']++;
		}
		if ($last_login >= $week_ago) {
			$stats['week_logins']++;
		}
		if ($last_login >= $month_ago) {
			$stats['month_logins']++;
		}
		
		$stats['recent_admins'][] = array(
			'name' => $admin->display_name ?: $admin->user_login,
			'time' => human_time_diff($last_login, current_time('timestamp')) . ' 前',
			'ip' => $last_ip ?: '-',
			'timestamp' => $last_login
		);
	}
	
	usort($stats['recent_admins'], function($a, $b) {
		return $b['timestamp'] - $a['timestamp'];
	});
	
	set_transient($cache_key, $stats, HOUR_IN_SECONDS * 6);
	
	return $stats;
}

// ===== Login Tracking =====

add_action('wp_login', function($user_login, $user) {
	if (user_can($user, 'manage_options')) {
		update_user_meta($user->ID, 'wu_last_login', current_time('timestamp'));
		update_user_meta($user->ID, 'wu_last_ip', $_SERVER['REMOTE_ADDR'] ?? '-');
		delete_transient('wu_login_stats');
	}
}, 10, 2);

// ===== Settings Page =====

function wu_dashboard_settings_page() {
	if (!current_user_can('manage_options')) {
		wp_die('權限不足');
	}
	
	if (isset($_POST['wu_save'])) {
		check_admin_referer('wu_dash_settings');
		
		update_option('wu_dashboard_enabled', isset($_POST['enabled']) ? 1 : 0);
		update_option('wu_dashboard_site_status', sanitize_text_field($_POST['status'] ?? 'normal'));
		update_option('wu_dashboard_status_note', sanitize_textarea_field($_POST['status_note'] ?? ''));
		
		$recent_work = array();
		if (!empty($_POST['work_titles'])) {
			foreach ($_POST['work_titles'] as $i => $title) {
				if (!empty($title)) {
					$recent_work[] = array(
						'title' => sanitize_text_field($title),
						'date' => sanitize_text_field($_POST['work_dates'][$i] ?? ''),
						'note' => sanitize_textarea_field($_POST['work_notes'][$i] ?? '')
					);
				}
			}
		}
		update_option('wu_dashboard_recent_work', $recent_work);
		
		$services = array();
		if (!empty($_POST['services'])) {
			$services = array_values(array_filter(array_map('sanitize_text_field', $_POST['services'])));
		}
		update_option('wu_dashboard_services', $services);
		
		update_option('wu_dashboard_hosting_plan', sanitize_text_field($_POST['hosting_plan'] ?? 'image'));
		update_option('wu_dashboard_hosting_rating', sanitize_text_field($_POST['hosting_rating'] ?? ''));
		update_option('wu_dashboard_disk_total', intval($_POST['disk_total'] ?? 5120));
		
		// 媒體配額設定
		$media_quota_gb = floatval($_POST['media_quota'] ?? 10);
		update_option('wu_media_quota_bytes', $media_quota_gb * 1073741824);
		
		$payments = array();
		if (!empty($_POST['payment_items'])) {
			foreach ($_POST['payment_items'] as $i => $item) {
				if (!empty($item)) {
					$payments[] = array(
						'date' => sanitize_text_field($_POST['payment_dates'][$i] ?? ''),
						'item' => sanitize_text_field($item),
						'amount' => intval($_POST['payment_amounts'][$i] ?? 0),
						'status' => sanitize_text_field($_POST['payment_statuses'][$i] ?? 'pending')
					);
				}
			}
		}
		update_option('wu_dashboard_payments', $payments);
		
		delete_transient('wu_ssl_check');
		delete_transient('wu_site_disk_usage');
		delete_transient('wu_login_stats');
		
		echo '<div class="notice notice-success is-dismissible"><p><strong>✅ 設定已儲存</strong></p></div>';
	}
	
	// 重新同步媒體配額
	if (isset($_POST['wu_resync_media'])) {
		check_admin_referer('wu_dash_settings');
		delete_option('wu_media_last_sync');
		wu_perform_media_quota_init();
		echo '<div class="notice notice-success is-dismissible"><p><strong>✅ 媒體配額已重新同步</strong></p></div>';
	}
	
	$enabled = get_option('wu_dashboard_enabled', 1);
	$status = get_option('wu_dashboard_site_status', 'normal');
	$status_note = get_option('wu_dashboard_status_note', '');
	$recent_work = get_option('wu_dashboard_recent_work', array());
	$services = get_option('wu_dashboard_services', array());
	$hosting_plan = get_option('wu_dashboard_hosting_plan', 'image');
	$hosting_rating = get_option('wu_dashboard_hosting_rating', '優良運作');
	$disk_total = get_option('wu_dashboard_disk_total', 5120);
	$media_quota = get_option('wu_media_quota_bytes', 10737418240) / 1073741824;
	$payments = get_option('wu_dashboard_payments', array());
	$media_quota_info = wu_get_media_quota_info();
	
	?>
	<div class="wrap">
		<h1>儀表板設定</h1>
		
		<div class="notice notice-info" style="padding:15px;">
			<p style="margin:0;"><strong>功能說明</strong></p>
			<ul style="margin:8px 0 0 20px;line-height:1.8;">
				<li>儀表板整合在 WordPress 原始後台首頁</li>
				<li>磁碟使用計算整個網站大小</li>
				<li>媒體配額採用增量追蹤,零效能影響</li>
				<li>所有統計資料使用快取,每 6-12 小時更新</li>
			</ul>
		</div>
		
		<!-- 媒體配額當前狀態 -->
		<div style="background:#fff;padding:20px;border:1px solid #ddd;margin-top:20px;">
			<h2 style="margin-top:0;">媒體配額當前狀態</h2>
			<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:15px;">
				<div style="padding:15px;background:#f9f9f9;border-left:3px solid #0073aa;">
					<div style="font-size:11px;color:#666;margin-bottom:5px;">使用中</div>
					<div style="font-size:20px;font-weight:700;"><?php echo esc_html($media_quota_info['used_formatted']); ?></div>
				</div>
				<div style="padding:15px;background:#f9f9f9;border-left:3px solid #0073aa;">
					<div style="font-size:11px;color:#666;margin-bottom:5px;">配額</div>
					<div style="font-size:20px;font-weight:700;"><?php echo esc_html($media_quota_info['quota_formatted']); ?></div>
				</div>
				<div style="padding:15px;background:#f9f9f9;border-left:3px solid #0073aa;">
					<div style="font-size:11px;color:#666;margin-bottom:5px;">使用率</div>
					<div style="font-size:20px;font-weight:700;color:<?php echo $media_quota_info['bar_color']; ?>;"><?php echo esc_html($media_quota_info['percentage']); ?>%</div>
				</div>
				<div style="padding:15px;background:#f9f9f9;border-left:3px solid #0073aa;">
					<div style="font-size:11px;color:#666;margin-bottom:5px;">狀態</div>
					<div style="font-size:20px;font-weight:700;"><?php echo esc_html($media_quota_info['status_icon'] . ' ' . $media_quota_info['status_text']); ?></div>
				</div>
			</div>
			<form method="post" style="margin-top:15px;">
				<?php wp_nonce_field('wu_dash_settings'); ?>
				<button type="submit" name="wu_resync_media" class="button button-secondary">🔄 重新同步媒體配額</button>
				<p class="description">重新計算所有現有媒體檔案的大小 (此操作可能需要數分鐘)</p>
			</form>
		</div>
		
		<form method="post" style="background:#fff;padding:25px;border:1px solid #ddd;margin-top:20px;">
			<?php wp_nonce_field('wu_dash_settings'); ?>
			
			<table class="form-table">
				
				<tr>
					<th><label>啟用儀表板</label></th>
					<td>
						<label>
							<input type="checkbox" name="enabled" value="1" <?php checked(1, $enabled); ?>>
							<strong>啟用客戶儀表板</strong>
						</label>
					</td>
				</tr>
				
				<tr>
					<th><label>網站狀態</label></th>
					<td>
						<select name="status">
							<option value="normal" <?php selected($status, 'normal'); ?>>正常運作</option>
							<option value="watching" <?php selected($status, 'watching'); ?>>觀察中</option>
							<option value="handling" <?php selected($status, 'handling'); ?>>處理中</option>
						</select>
						<br>
						<textarea name="status_note" rows="2" class="large-text" style="margin-top:10px;" placeholder="狀態說明 (選填)"><?php echo esc_textarea($status_note); ?></textarea>
					</td>
				</tr>
				
				<tr>
					<th><label>服務項目</label></th>
					<td>
						<div id="service-container">
							<?php 
							if (empty($services)) {
								$services = array('');
							}
							foreach ($services as $i => $service): 
							?>
							<div style="display:flex;gap:10px;margin-bottom:8px;align-items:center;">
								<input type="text" name="services[]" value="<?php echo esc_attr($service); ?>" placeholder="服務項目" class="large-text">
								<button type="button" class="button" onclick="this.parentElement.remove()">刪除</button>
							</div>
							<?php endforeach; ?>
						</div>
						<button type="button" class="button" onclick="addService()">新增項目</button>
					</td>
				</tr>
				
				<tr>
					<th><label>最近處理紀錄</label></th>
					<td>
						<div id="work-container">
							<?php 
							if (empty($recent_work)) {
								$recent_work = array(array('title' => '', 'date' => '', 'note' => ''));
							}
							foreach ($recent_work as $work): 
							?>
							<div style="background:#f9f9f9;padding:15px;margin-bottom:10px;">
								<input type="text" name="work_titles[]" value="<?php echo esc_attr($work['title']); ?>" placeholder="處理項目" class="regular-text" style="margin-bottom:8px;">
								<input type="date" name="work_dates[]" value="<?php echo esc_attr($work['date']); ?>" style="margin-bottom:8px;">
								<textarea name="work_notes[]" rows="2" class="large-text" placeholder="說明 (選填)"><?php echo esc_textarea($work['note']); ?></textarea>
								<button type="button" class="button" onclick="this.parentElement.remove()" style="margin-top:5px;">刪除</button>
							</div>
							<?php endforeach; ?>
						</div>
						<button type="button" class="button" onclick="addWork()">新增紀錄</button>
					</td>
				</tr>
				
				<tr>
					<th><label>主機規格</label></th>
					<td>
						<select name="hosting_plan" style="margin-bottom:10px;">
							<option value="onepage" <?php selected($hosting_plan, 'onepage'); ?>>一頁式主機</option>
							<option value="image" <?php selected($hosting_plan, 'image'); ?>>形象網站主機</option>
							<option value="ecommerce" <?php selected($hosting_plan, 'ecommerce'); ?>>電商主機</option>
						</select>
						<br>
						<input type="text" name="hosting_rating" value="<?php echo esc_attr($hosting_rating); ?>" placeholder="評估狀態" class="regular-text" style="margin-top:8px;">
						<br>
						<label style="margin-top:10px;display:block;">
							磁碟總量 (MB):
							<input type="number" name="disk_total" value="<?php echo esc_attr($disk_total); ?>" class="regular-text">
						</label>
						<p class="description">預設 5120 MB = 5 GB</p>
					</td>
				</tr>
				
				<tr>
					<th><label>媒體配額</label></th>
					<td>
						<input type="number" name="media_quota" value="<?php echo esc_attr($media_quota); ?>" step="0.1" min="1" class="regular-text">
						<span>GB</span>
						<p class="description">設定媒體庫容量上限 (預設 10 GB)</p>
					</td>
				</tr>
				
				<tr>
					<th><label>款項紀錄</label></th>
					<td>
						<div id="payment-container">
							<?php 
							if (empty($payments)) {
								$payments = array(array('date' => '', 'item' => '', 'amount' => '', 'status' => 'pending'));
							}
							foreach ($payments as $payment): 
							?>
							<div style="background:#f9f9f9;padding:15px;margin-bottom:10px;display:grid;grid-template-columns:120px 1fr 120px 100px 80px;gap:10px;align-items:center;">
								<input type="date" name="payment_dates[]" value="<?php echo esc_attr($payment['date']); ?>">
								<input type="text" name="payment_items[]" value="<?php echo esc_attr($payment['item']); ?>" placeholder="項目">
								<input type="number" name="payment_amounts[]" value="<?php echo esc_attr($payment['amount']); ?>" placeholder="金額">
								<select name="payment_statuses[]">
									<option value="pending" <?php selected($payment['status'], 'pending'); ?>>待付款</option>
									<option value="paid" <?php selected($payment['status'], 'paid'); ?>>已付款</option>
								</select>
								<button type="button" class="button" onclick="this.parentElement.remove()">刪除</button>
							</div>
							<?php endforeach; ?>
						</div>
						<button type="button" class="button" onclick="addPayment()">新增款項</button>
					</td>
				</tr>
				
			</table>
			
			<?php submit_button('儲存設定', 'primary large', 'wu_save'); ?>
		</form>
	</div>
	
	<script>
	function addService() {
		document.getElementById('service-container').insertAdjacentHTML('beforeend',
			'<div style="display:flex;gap:10px;margin-bottom:8px;align-items:center;">' +
			'<input type="text" name="services[]" placeholder="服務項目" class="large-text">' +
			'<button type="button" class="button" onclick="this.parentElement.remove()">刪除</button>' +
			'</div>'
		);
	}
	
	function addWork() {
		document.getElementById('work-container').insertAdjacentHTML('beforeend',
			'<div style="background:#f9f9f9;padding:15px;margin-bottom:10px;">' +
			'<input type="text" name="work_titles[]" placeholder="處理項目" class="regular-text" style="margin-bottom:8px;">' +
			'<input type="date" name="work_dates[]" style="margin-bottom:8px;">' +
			'<textarea name="work_notes[]" rows="2" class="large-text" placeholder="說明 (選填)"></textarea>' +
			'<button type="button" class="button" onclick="this.parentElement.remove()" style="margin-top:5px;">刪除</button>' +
			'</div>'
		);
	}
	
	function addPayment() {
		document.getElementById('payment-container').insertAdjacentHTML('beforeend',
			'<div style="background:#f9f9f9;padding:15px;margin-bottom:10px;display:grid;grid-template-columns:120px 1fr 120px 100px 80px;gap:10px;align-items:center;">' +
			'<input type="date" name="payment_dates[]">' +
			'<input type="text" name="payment_items[]" placeholder="項目">' +
			'<input type="number" name="payment_amounts[]" placeholder="金額">' +
			'<select name="payment_statuses[]"><option value="pending">待付款</option><option value="paid">已付款</option></select>' +
			'<button type="button" class="button" onclick="this.parentElement.remove()">刪除</button>' +
			'</div>'
		);
	}
	</script>
	<?php
}

// ===== Styles =====

add_action('admin_head', function() {
	if (!get_option('wu_dashboard_enabled', 1)) {
		return;
	}
	
	?>
	<style>
	#wu_unified_dashboard {
		width: 100% !important;
		grid-column: 1 / -1 !important;
	}
	
	#wu_unified_dashboard .inside {
		padding: 12px !important;
		margin: 0 !important;
	}
	
	.wu-unified-container {
		display: flex;
		flex-direction: column;
		gap: 15px;
	}
	
	.wu-dashboard-row {
		display: grid;
		grid-template-columns: repeat(2, 1fr);
		gap: 15px;
	}
	
	.wu-dashboard-section {
		background: #fafafa;
		border: 1px solid #e0e0e0;
		padding: 15px;
	}
	
	.wu-dashboard-section.wu-full-width {
		grid-column: 1 / -1;
	}
	
	.wu-section-header {
		font-size: 13px;
		font-weight: 600;
		color: #333;
		margin-bottom: 12px;
		text-transform: uppercase;
		letter-spacing: 0.5px;
		border-bottom: 2px solid #0073aa;
		padding-bottom: 8px;
	}
	
	.wu-grid-2 {
		display: grid;
		grid-template-columns: repeat(2, 1fr);
		gap: 10px;
	}
	
	.wu-grid-3 {
		display: grid;
		grid-template-columns: repeat(3, 1fr);
		gap: 10px;
	}
	
	.wu-grid-4 {
		display: grid;
		grid-template-columns: repeat(4, 1fr);
		gap: 10px;
	}
	
	.wu-stat-box {
		background: #fff;
		border: 1px solid #e0e0e0;
		border-left: 3px solid #0073aa;
		padding: 12px;
	}
	
	.wu-label {
		font-size: 10px;
		color: #666;
		text-transform: uppercase;
		margin-bottom: 6px;
		font-weight: 600;
		letter-spacing: 0.3px;
	}
	
	.wu-value {
		font-size: 20px;
		font-weight: 700;
		color: #111;
		line-height: 1.2;
	}
	
	.wu-meta {
		font-size: 11px;
		color: #999;
		margin-top: 4px;
	}
	
	.wu-progress {
		width: 100%;
		height: 4px;
		background: #e0e0e0;
		margin-top: 8px;
		overflow: hidden;
	}
	
	.wu-progress-bar {
		height: 100%;
		transition: width 0.3s;
	}
	
	.wu-alert {
		padding: 10px;
		background: #fff3cd;
		border-left: 3px solid #f0b849;
		font-size: 12px;
		color: #333;
		line-height: 1.5;
		margin-top: 10px;
	}
	
	/* Media Quota Display */
	.wu-media-quota-display {
		background: #fff;
		padding: 15px;
		border: 1px solid #e0e0e0;
	}
	
	.wu-quota-main {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 10px;
	}
	
	.wu-quota-status {
		display: flex;
		align-items: center;
		gap: 8px;
		font-size: 14px;
		font-weight: 600;
	}
	
	.wu-quota-status.wu-status-normal { color: #46b450; }
	.wu-quota-status.wu-status-warning { color: #f0b849; }
	.wu-quota-status.wu-status-danger { color: #dc3232; }
	
	.wu-quota-usage {
		text-align: right;
	}
	
	.wu-quota-label {
		font-size: 10px;
		color: #666;
		text-transform: uppercase;
		margin-bottom: 4px;
	}
	
	.wu-quota-value {
		font-size: 24px;
		font-weight: 700;
		color: #111;
	}
	
	.wu-quota-total {
		font-size: 16px;
		color: #999;
	}
	
	.wu-quota-percentage {
		font-size: 11px;
		color: #666;
		margin-top: 4px;
	}
	
	.wu-quota-meta {
		display: flex;
		justify-content: space-between;
		margin-top: 10px;
		padding-top: 10px;
		border-top: 1px solid #f0f0f0;
		font-size: 11px;
		color: #666;
	}
	
	.wu-table {
		width: 100%;
		border-collapse: collapse;
		font-size: 12px;
		margin-top: 10px;
		background: #fff;
	}
	
	.wu-table th {
		padding: 8px;
		background: #f0f0f0;
		border-bottom: 2px solid #ddd;
		text-align: left;
		font-weight: 600;
		color: #666;
		font-size: 11px;
		text-transform: uppercase;
	}
	
	.wu-table td {
		padding: 8px;
		border-bottom: 1px solid #f0f0f0;
		color: #333;
	}
	
	.wu-table tr:last-child td {
		border-bottom: none;
	}
	
	.wu-list {
		margin: 0;
		padding: 0;
		list-style: none;
	}
	
	.wu-list li {
		padding: 10px 0;
		border-bottom: 1px solid #f0f0f0;
		font-size: 13px;
		color: #333;
	}
	
	.wu-list li:last-child {
		border-bottom: none;
	}
	
	.wu-icon {
		color: #46b450;
		font-weight: 700;
		margin-right: 8px;
	}
	
	.wu-timeline {
		display: flex;
		flex-direction: column;
		gap: 10px;
	}
	
	.wu-timeline-row {
		display: grid;
		grid-template-columns: 80px 1fr;
		gap: 10px;
		padding-bottom: 10px;
		border-bottom: 1px solid #f0f0f0;
	}
	
	.wu-timeline-row:last-child {
		border-bottom: none;
		padding-bottom: 0;
	}
	
	.wu-timeline-date {
		font-size: 11px;
		color: #999;
		font-weight: 600;
	}
	
	.wu-timeline-title {
		font-size: 13px;
		font-weight: 600;
		color: #111;
		margin-bottom: 4px;
	}
	
	.wu-timeline-note {
		font-size: 12px;
		color: #666;
		line-height: 1.5;
	}
	
	.wu-badge {
		display: inline-block;
		padding: 3px 8px;
		font-size: 10px;
		font-weight: 600;
		text-transform: uppercase;
	}
	
	.wu-badge-success {
		background: #d4edda;
		color: #155724;
	}
	
	.wu-badge-warning {
		background: #fff3cd;
		color: #856404;
	}
	
	.wu-contact-box {
		background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
		padding: 20px;
		text-align: center;
		color: #fff;
	}
	
	.wu-contact-name {
		font-size: 18px;
		font-weight: 700;
		margin-bottom: 4px;
	}
	
	.wu-contact-role {
		font-size: 12px;
		color: rgba(255, 255, 255, 0.9);
		margin-bottom: 12px;
	}
	
	.wu-contact-links {
		font-size: 12px;
		display: flex;
		justify-content: center;
		gap: 20px;
		flex-wrap: wrap;
	}
	
	.wu-contact-links a {
		color: #fff;
		text-decoration: underline;
	}
	
	.wu-contact-links a:hover {
		color: #f0f0f0;
	}
	
	.wu-footer-note {
		margin-top: 10px;
		padding: 8px 12px;
		background: #e7f3ff;
		border-left: 3px solid #0073aa;
		font-size: 12px;
		color: #555;
		display: flex;
		align-items: center;
		gap: 8px;
	}
	
	.wu-footer-note .dashicons {
		color: #0073aa;
		font-size: 16px;
	}
	
	@media (max-width: 768px) {
		.wu-dashboard-row {
			grid-template-columns: 1fr;
		}
		
		.wu-grid-2,
		.wu-grid-3,
		.wu-grid-4 {
			grid-template-columns: 1fr;
		}
		
		.wu-timeline-row {
			grid-template-columns: 60px 1fr;
		}
		
		.wu-contact-links {
			flex-direction: column;
			gap: 10px;
		}
	}
	</style>
	<?php
});
