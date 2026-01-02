<?php
if (!defined('ABSPATH')) exit;

/*
 * WumetaxToolkit - Professional Dashboard Widgets
 * Version: 6.0 - Full-Width First Row Layout
 * 
 * FEATURES:
 * - Full-width first row dashboard
 * - Add/delete service items
 * - Admin-only login tracking
 * - Lightweight media stats
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
	add_option('wu_dashboard_manager_name', 'WUMETAX 末特數位科技');
	add_option('wu_dashboard_manager_contact', "聯絡信箱: contact@wumetax.com\nLINE: https://lin.ee/Lut7wCe");
});

// ===== Dashboard Widgets =====

add_action('wp_dashboard_setup', function() {
	if (!get_option('wu_dashboard_enabled', 1)) {
		return;
	}
	
	// 1. 全寬綜合儀表板 (第一行)
	wp_add_dashboard_widget(
		'wu_overview_widget',
		'<span class="dashicons dashicons-dashboard"></span> 網站綜合儀表板',
		'wu_render_overview_widget',
		null,
		null,
		'normal',
		'high'
	);
	
	// 2. 登入統計
	wp_add_dashboard_widget(
		'wu_login_widget',
		'<span class="dashicons dashicons-admin-users"></span> 登入統計',
		'wu_render_login_widget'
	);
	
	// 3. 媒體分析
	wp_add_dashboard_widget(
		'wu_media_widget',
		'<span class="dashicons dashicons-format-gallery"></span> 媒體分析',
		'wu_render_media_widget'
	);
	
	// 4. 服務內容
	wp_add_dashboard_widget(
		'wu_service_widget',
		'<span class="dashicons dashicons-yes-alt"></span> 維運服務項目',
		'wu_render_service_widget'
	);
	
	// 5. 最近處理
	if (!empty(get_option('wu_dashboard_recent_work', array()))) {
		wp_add_dashboard_widget(
			'wu_work_widget',
			'<span class="dashicons dashicons-update"></span> 最近處理紀錄',
			'wu_render_work_widget'
		);
	}
	
	// 6. 款項紀錄 (僅管理員)
	if (current_user_can('manage_options') && !empty(get_option('wu_dashboard_payments', array()))) {
		wp_add_dashboard_widget(
			'wu_payment_widget',
			'<span class="dashicons dashicons-money-alt"></span> 款項紀錄',
			'wu_render_payment_widget'
		);
	}
	
	// 7. 聯絡資訊
	wp_add_dashboard_widget(
		'wu_contact_widget',
		'<span class="dashicons dashicons-phone"></span> 聯絡資訊',
		'wu_render_contact_widget'
	);
});

// ===== Widget: 全寬綜合儀表板 =====

function wu_render_overview_widget() {
	$status = get_option('wu_dashboard_site_status', 'normal');
	$status_note = get_option('wu_dashboard_status_note', '');
	$ssl_valid = wu_check_ssl_status();
	$php_version = PHP_VERSION;
	$hosting_plan = get_option('wu_dashboard_hosting_plan', 'image');
	$hosting_rating = get_option('wu_dashboard_hosting_rating', '優良運作');
	$disk_total = get_option('wu_dashboard_disk_total', 5120);
	$disk_used = wu_get_disk_usage();
	$disk_percentage = ($disk_used / $disk_total) * 100;
	$wp_memory_limit = WP_MEMORY_LIMIT;
	
	$status_config = array(
		'normal' => array('label' => '正常運作', 'color' => '#46b450'),
		'watching' => array('label' => '觀察中', 'color' => '#f0b849'),
		'handling' => array('label' => '處理中', 'color' => '#00a0d2')
	);
	$current = $status_config[$status] ?? $status_config['normal'];
	
	$plan_names = array(
		'onepage' => '一頁式主機',
		'image' => '形象網站主機',
		'ecommerce' => '電商主機'
	);
	$plan_name = $plan_names[$hosting_plan] ?? '標準主機';
	
	?>
	<div class="wu-overview-container">
		<!-- 狀態區 -->
		<div class="wu-overview-section">
			<div class="wu-section-title">網站狀態</div>
			<div class="wu-widget-grid">
				<div class="wu-stat-card" style="border-left:3px solid <?php echo $current['color']; ?>;">
					<div class="wu-stat-label">狀態</div>
					<div class="wu-stat-value" style="color:<?php echo $current['color']; ?>;">
						<?php echo esc_html($current['label']); ?>
					</div>
				</div>
				
				<div class="wu-stat-card">
					<div class="wu-stat-label">SSL/HTTPS</div>
					<div class="wu-stat-value" style="color:<?php echo $ssl_valid ? '#46b450' : '#f0b849'; ?>;">
						<?php echo $ssl_valid ? '正常' : '未啟用'; ?>
					</div>
				</div>
				
				<div class="wu-stat-card">
					<div class="wu-stat-label">PHP 版本</div>
					<div class="wu-stat-value"><?php echo esc_html($php_version); ?></div>
				</div>
				
				<div class="wu-stat-card">
					<div class="wu-stat-label">主機方案</div>
					<div class="wu-stat-value" style="font-size:16px;"><?php echo esc_html($plan_name); ?></div>
					<div class="wu-stat-meta">評估: <?php echo esc_html($hosting_rating); ?></div>
				</div>
			</div>
			
			<?php if (!empty($status_note)): ?>
			<div class="wu-note"><?php echo nl2br(esc_html($status_note)); ?></div>
			<?php endif; ?>
		</div>
		
		<!-- 系統資源區 -->
		<div class="wu-overview-section">
			<div class="wu-section-title">系統資源</div>
			<div class="wu-widget-grid">
				<div class="wu-stat-card">
					<div class="wu-stat-label">磁碟使用</div>
					<div class="wu-stat-value">
						<?php echo number_format($disk_used, 0); ?> MB
					</div>
					<div class="wu-stat-meta">
						/ <?php echo number_format($disk_total, 0); ?> MB
						<span style="color:<?php echo $disk_percentage > 80 ? '#dc3232' : '#46b450'; ?>;">
							(<?php echo number_format($disk_percentage, 1); ?>%)
						</span>
					</div>
					<div class="wu-progress-bar">
						<div class="wu-progress-fill" style="width:<?php echo min($disk_percentage, 100); ?>%;background:<?php echo $disk_percentage > 80 ? '#dc3232' : '#46b450'; ?>;"></div>
					</div>
				</div>
				
				<div class="wu-stat-card">
					<div class="wu-stat-label">WordPress 記憶體</div>
					<div class="wu-stat-value"><?php echo esc_html($wp_memory_limit); ?></div>
				</div>
				
				<div class="wu-stat-card">
					<div class="wu-stat-label">剩餘空間</div>
					<div class="wu-stat-value">
						<?php echo number_format($disk_total - $disk_used, 0); ?> MB
					</div>
				</div>
			</div>
		</div>
	</div>
	
	<div class="wu-info-note">
		<span class="dashicons dashicons-info"></span>
		所有統計資料每 6-12 小時自動更新
	</div>
	<?php
}

// ===== Widget: 登入統計 =====

function wu_render_login_widget() {
	$stats = wu_get_login_stats();
	
	?>
	<div class="wu-widget-grid">
		<div class="wu-stat-card">
			<div class="wu-stat-label">管理員數</div>
			<div class="wu-stat-value"><?php echo number_format($stats['total_admins']); ?></div>
		</div>
		
		<div class="wu-stat-card">
			<div class="wu-stat-label">今日登入</div>
			<div class="wu-stat-value"><?php echo number_format($stats['today_logins']); ?></div>
		</div>
		
		<div class="wu-stat-card">
			<div class="wu-stat-label">本週登入</div>
			<div class="wu-stat-value"><?php echo number_format($stats['week_logins']); ?></div>
		</div>
		
		<div class="wu-stat-card">
			<div class="wu-stat-label">本月登入</div>
			<div class="wu-stat-value"><?php echo number_format($stats['month_logins']); ?></div>
		</div>
	</div>
	
	<?php if (!empty($stats['recent_admins'])): ?>
	<div class="wu-table-wrap">
		<table class="wu-mini-table">
			<thead>
				<tr>
					<th>管理員</th>
					<th>最近登入</th>
					<th>IP 位址</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach (array_slice($stats['recent_admins'], 0, 5) as $admin): ?>
				<tr>
					<td><?php echo esc_html($admin['name']); ?></td>
					<td><?php echo esc_html($admin['time']); ?></td>
					<td><?php echo esc_html($admin['ip']); ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php endif; ?>
	<?php
}

// ===== Widget: 媒體分析 =====

function wu_render_media_widget() {
	$stats = wu_get_media_stats();
	
	?>
	<div class="wu-widget-grid" style="grid-template-columns: repeat(2, 1fr);">
		<div class="wu-stat-card">
			<div class="wu-stat-label">總檔案數</div>
			<div class="wu-stat-value"><?php echo number_format($stats['total_files']); ?></div>
		</div>
		
		<div class="wu-stat-card">
			<div class="wu-stat-label">總容量</div>
			<div class="wu-stat-value"><?php echo esc_html($stats['total_size']); ?></div>
		</div>
	</div>
	<?php
}

// ===== Widget: 服務內容 =====

function wu_render_service_widget() {
	$services = get_option('wu_dashboard_services', array());
	
	if (empty($services)) {
		echo '<p style="text-align:center;color:#999;padding:20px 0;">尚未設定服務項目</p>';
		return;
	}
	
	?>
	<ul class="wu-service-list">
		<?php foreach ($services as $service): ?>
		<li>
			<span class="wu-check">✓</span>
			<?php echo esc_html($service); ?>
		</li>
		<?php endforeach; ?>
	</ul>
	<?php
}

// ===== Widget: 最近處理 =====

function wu_render_work_widget() {
	$recent_work = get_option('wu_dashboard_recent_work', array());
	
	if (empty($recent_work)) {
		return;
	}
	
	usort($recent_work, function($a, $b) {
		return strtotime($b['date']) - strtotime($a['date']);
	});
	
	?>
	<div class="wu-timeline">
		<?php foreach (array_slice($recent_work, 0, 5) as $work): ?>
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
	<?php
}

// ===== Widget: 款項紀錄 =====

function wu_render_payment_widget() {
	$payments = get_option('wu_dashboard_payments', array());
	
	if (empty($payments)) {
		return;
	}
	
	usort($payments, function($a, $b) {
		return strtotime($b['date']) - strtotime($a['date']);
	});
	
	?>
	<div class="wu-table-wrap">
		<table class="wu-mini-table">
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
	<?php
}

// ===== Widget: 聯絡資訊 =====

function wu_render_contact_widget() {
	$manager_name = get_option('wu_dashboard_manager_name', '');
	$manager_contact = get_option('wu_dashboard_manager_contact', '');
	
	?>
	<div class="wu-contact-card">
		<div class="wu-contact-title"><?php echo esc_html($manager_name); ?></div>
		<div class="wu-contact-sub">網站維運管理單位</div>
		<?php if (!empty($manager_contact)): ?>
		<div class="wu-contact-info">
			<?php
			$lines = explode("\n", $manager_contact);
			foreach ($lines as $line) {
				$line = trim($line);
				if (preg_match('/^(.*?):\s*(.+)$/', $line, $matches)) {
					$label = $matches[1];
					$value = $matches[2];
					
					if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
						echo esc_html($label) . ': <a href="mailto:' . esc_attr($value) . '" style="color:#fff;text-decoration:underline;">' . esc_html($value) . '</a><br>';
					} elseif (filter_var($value, FILTER_VALIDATE_URL)) {
						echo esc_html($label) . ': <a href="' . esc_url($value) . '" target="_blank" style="color:#fff;text-decoration:underline;">' . esc_html($value) . '</a><br>';
					} else {
						echo esc_html($line) . '<br>';
					}
				} else {
					echo esc_html($line) . '<br>';
				}
			}
			?>
		</div>
		<?php endif; ?>
	</div>
	<?php
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

function wu_get_media_stats() {
	$cache_key = 'wu_media_stats';
	$cached = get_transient($cache_key);
	
	if ($cached !== false) {
		return $cached;
	}
	
	global $wpdb;
	
	$total_files = $wpdb->get_var("
		SELECT COUNT(*) 
		FROM {$wpdb->posts} 
		WHERE post_type = 'attachment'
	");
	
	$upload_dir = wp_upload_dir();
	$total_size = 0;
	
	try {
		$path = $upload_dir['basedir'];
		if (is_dir($path)) {
			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
			);
			
			foreach ($iterator as $file) {
				if ($file->isFile()) {
					$total_size += $file->getSize();
				}
			}
		}
	} catch (Exception $e) {
		$total_size = 0;
	}
	
	$stats = array(
		'total_files' => $total_files ?: 0,
		'total_size' => wu_format_size($total_size)
	);
	
	set_transient($cache_key, $stats, HOUR_IN_SECONDS * 12);
	
	return $stats;
}

function wu_format_size($bytes) {
	$units = array('B', 'KB', 'MB', 'GB', 'TB');
	$bytes = max($bytes, 0);
	$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
	$pow = min($pow, count($units) - 1);
	$bytes /= (1 << (10 * $pow));
	
	return round($bytes, 2) . ' ' . $units[$pow];
}

// ===== Login Tracking (Admin + IP) =====

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
		
		update_option('wu_dashboard_manager_name', sanitize_text_field($_POST['manager_name'] ?? ''));
		update_option('wu_dashboard_manager_contact', sanitize_textarea_field($_POST['manager_contact'] ?? ''));
		
		delete_transient('wu_ssl_check');
		delete_transient('wu_disk_usage');
		delete_transient('wu_login_stats');
		delete_transient('wu_media_stats');
		
		echo '<div class="notice notice-success is-dismissible"><p><strong>✅ 設定已儲存</strong></p></div>';
	}
	
	$enabled = get_option('wu_dashboard_enabled', 1);
	$status = get_option('wu_dashboard_site_status', 'normal');
	$status_note = get_option('wu_dashboard_status_note', '');
	$recent_work = get_option('wu_dashboard_recent_work', array());
	$services = get_option('wu_dashboard_services', array());
	$hosting_plan = get_option('wu_dashboard_hosting_plan', 'image');
	$hosting_rating = get_option('wu_dashboard_hosting_rating', '優良運作');
	$disk_total = get_option('wu_dashboard_disk_total', 5120);
	$payments = get_option('wu_dashboard_payments', array());
	$manager_name = get_option('wu_dashboard_manager_name', '');
	$manager_contact = get_option('wu_dashboard_manager_contact', '');
	
	?>
	<div class="wrap">
		<h1>儀表板設定</h1>
		
		<div class="notice notice-info" style="padding:15px;">
			<p style="margin:0;"><strong>功能說明</strong></p>
			<ul style="margin:8px 0 0 20px;line-height:1.8;">
				<li>儀表板整合在 WordPress 原始後台首頁</li>
				<li>所有統計資料使用快取,每 6-12 小時更新</li>
				<li>登入統計僅追蹤管理員及 IP</li>
				<li>媒體分析僅計算總量,不影響效能</li>
			</ul>
		</div>
		
		<form method="post" style="background:#fff;padding:25px;border:1px solid #ddd;margin-top:20px;">
			<?php wp_nonce_field('wu_dash_settings'); ?>
			
			<table class="form-table">
				
				<tr>
					<th><label>啟用儀表板</label></th>
					<td>
						<label>
							<input type="checkbox" name="enabled" value="1" <?php checked(1, $enabled); ?>>
							<strong>啟用客戶儀表板小工具</strong>
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
				
				<tr>
					<th><label>管理單位資訊</label></th>
					<td>
						<input type="text" name="manager_name" value="<?php echo esc_attr($manager_name); ?>" placeholder="單位名稱" class="regular-text">
						<br>
						<textarea name="manager_contact" rows="3" class="large-text" style="margin-top:10px;" placeholder="聯絡方式 (支援自動連結)"><?php echo esc_textarea($manager_contact); ?></textarea>
						<p class="description">格式: 標籤: 內容 (Email 與 URL 自動轉為連結)</p>
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

// ===== Widget Styles =====

add_action('admin_head', function() {
	if (!get_option('wu_dashboard_enabled', 1)) {
		return;
	}
	
	?>
	<style>
	/* Full Width Override */
	#wu_overview_widget {
		width: 100% !important;
	}
	
	#normal-sortables #wu_overview_widget {
		grid-column: 1 / -1;
	}
	
	/* Widget Container Reset */
	#wu_overview_widget .inside,
	#wu_login_widget .inside,
	#wu_media_widget .inside,
	#wu_service_widget .inside,
	#wu_work_widget .inside,
	#wu_payment_widget .inside,
	#wu_contact_widget .inside {
		padding: 12px !important;
		margin: 0 !important;
	}
	
	/* Overview Container */
	.wu-overview-container {
		display: grid;
		grid-template-columns: repeat(2, 1fr);
		gap: 20px;
	}
	
	.wu-overview-section {
		background: #fafafa;
		padding: 15px;
		border: 1px solid #e0e0e0;
	}
	
	.wu-section-title {
		font-size: 13px;
		font-weight: 600;
		color: #333;
		margin-bottom: 12px;
		text-transform: uppercase;
		letter-spacing: 0.5px;
	}
	
	/* Grid Layout */
	.wu-widget-grid {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
		gap: 12px;
		margin-bottom: 12px;
	}
	
	/* Stat Card */
	.wu-stat-card {
		background: #fff;
		padding: 12px;
		border-left: 3px solid #0073aa;
		border: 1px solid #e0e0e0;
	}
	
	.wu-stat-label {
		font-size: 11px;
		color: #666;
		text-transform: uppercase;
		margin-bottom: 6px;
		letter-spacing: 0.3px;
		font-weight: 500;
	}
	
	.wu-stat-value {
		font-size: 22px;
		font-weight: 700;
		color: #111;
		line-height: 1.2;
	}
	
	.wu-stat-meta {
		font-size: 11px;
		color: #999;
		margin-top: 4px;
	}
	
	/* Progress Bar */
	.wu-progress-bar {
		width: 100%;
		height: 4px;
		background: #e0e0e0;
		margin-top: 8px;
		overflow: hidden;
	}
	
	.wu-progress-fill {
		height: 100%;
		transition: width 0.3s;
	}
	
	/* Note */
	.wu-note {
		padding: 10px;
		background: #fff3cd;
		border-left: 3px solid #f0b849;
		font-size: 12px;
		color: #333;
		line-height: 1.5;
		margin-top: 12px;
	}
	
	.wu-info-note {
		margin-top: 12px;
		padding: 8px 12px;
		background: #e7f3ff;
		border-left: 3px solid #0073aa;
		font-size: 12px;
		color: #555;
		display: flex;
		align-items: center;
		gap: 8px;
	}
	
	.wu-info-note .dashicons {
		color: #0073aa;
		font-size: 16px;
	}
	
	/* Service List */
	.wu-service-list {
		margin: 0;
		padding: 0;
		list-style: none;
	}
	
	.wu-service-list li {
		padding: 10px 0;
		border-bottom: 1px solid #f0f0f0;
		font-size: 13px;
		color: #333;
	}
	
	.wu-service-list li:last-child {
		border-bottom: none;
	}
	
	.wu-check {
		color: #46b450;
		font-weight: 700;
		margin-right: 8px;
	}
	
	/* Timeline */
	.wu-timeline {
		display: grid;
		gap: 12px;
	}
	
	.wu-timeline-item {
		display: grid;
		grid-template-columns: 80px 1fr;
		gap: 12px;
		padding-bottom: 12px;
		border-bottom: 1px solid #f0f0f0;
	}
	
	.wu-timeline-item:last-child {
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
	
	/* Table */
	.wu-table-wrap {
		overflow-x: auto;
		margin-top: 12px;
	}
	
	.wu-mini-table {
		width: 100%;
		border-collapse: collapse;
		font-size: 12px;
	}
	
	.wu-mini-table th {
		padding: 8px;
		background: #f9f9f9;
		border-bottom: 2px solid #e0e0e0;
		text-align: left;
		font-weight: 600;
		color: #666;
		font-size: 11px;
		text-transform: uppercase;
	}
	
	.wu-mini-table td {
		padding: 8px;
		border-bottom: 1px solid #f0f0f0;
		color: #333;
		font-size: 12px;
	}
	
	.wu-mini-table tr:last-child td {
		border-bottom: none;
	}
	
	/* Badge */
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
	
	/* Contact Card */
	.wu-contact-card {
		background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
		padding: 20px;
		text-align: center;
		color: #fff;
	}
	
	.wu-contact-title {
		font-size: 16px;
		font-weight: 700;
		margin-bottom: 4px;
	}
	
	.wu-contact-sub {
		font-size: 12px;
		color: rgba(255, 255, 255, 0.9);
		margin-bottom: 12px;
	}
	
	.wu-contact-info {
		font-size: 12px;
		line-height: 1.6;
		color: rgba(255, 255, 255, 0.95);
	}
	
	/* Mobile */
	@media (max-width: 768px) {
		.wu-overview-container {
			grid-template-columns: 1fr;
		}
		
		.wu-widget-grid {
			grid-template-columns: 1fr;
		}
		
		.wu-timeline-item {
			grid-template-columns: 60px 1fr;
		}
	}
	</style>
	<?php
});
