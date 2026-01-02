<?php
if (!defined('ABSPATH')) exit;

/*
 * WumetaxToolkit - Unified Dashboard Widget
 * Version: 7.0 - Single Full-Width Dashboard
 * 
 * FEATURES:
 * - Single unified full-width dashboard
 * - Complete site disk usage calculation
 * - Admin-only login tracking with IP
 * - Lightweight performance
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
});

// ===== Dashboard Widget =====

add_action('wp_dashboard_setup', function() {
	if (!get_option('wu_dashboard_enabled', 1)) {
		return;
	}
	
	// 單一全寬綜合儀表板
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
	// 取得所有資料
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
	$media_stats = wu_get_media_stats();
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
	
	// 排序最近處理和款項
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
		
		<!-- Row 2: 登入統計 + 媒體分析 -->
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
				<div class="wu-section-header">媒體分析</div>
				<div class="wu-grid-2">
					<div class="wu-stat-box">
						<div class="wu-label">總檔案數</div>
						<div class="wu-value"><?php echo number_format($media_stats['total_files']); ?></div>
					</div>
					<div class="wu-stat-box">
						<div class="wu-label">總容量</div>
						<div class="wu-value"><?php echo esc_html($media_stats['total_size']); ?></div>
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
		
		<!-- Row 4: 款項紀錄 (僅管理員) -->
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
	
	// 計算整個網站根目錄大小
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
	
	?>
	<div class="wrap">
		<h1>儀表板設定</h1>
		
		<div class="notice notice-info" style="padding:15px;">
			<p style="margin:0;"><strong>功能說明</strong></p>
			<ul style="margin:8px 0 0 20px;line-height:1.8;">
				<li>儀表板整合在 WordPress 原始後台首頁</li>
				<li>磁碟使用計算整個網站大小</li>
				<li>所有統計資料使用快取,每 6-12 小時更新</li>
				<li>登入統計僅追蹤管理員及 IP</li>
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
	/* Full Width Widget */
	#wu_unified_dashboard {
		width: 100% !important;
		grid-column: 1 / -1 !important;
	}
	
	#wu_unified_dashboard .inside {
		padding: 12px !important;
		margin: 0 !important;
	}
	
	/* Unified Container */
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
	
	/* Grid Systems */
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
	
	/* Stat Box */
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
	
	/* Progress Bar */
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
	
	/* Alert */
	.wu-alert {
		padding: 10px;
		background: #fff3cd;
		border-left: 3px solid #f0b849;
		font-size: 12px;
		color: #333;
		line-height: 1.5;
		margin-top: 10px;
	}
	
	/* Table */
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
	
	/* List */
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
	
	/* Timeline */
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
	
	/* Contact Box */
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
	
	/* Footer Note */
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
	
	/* Mobile Responsive */
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
