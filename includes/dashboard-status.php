<?php
if (!defined('ABSPATH')) exit;

/*
 * WumetaxToolkit - Professional Client Dashboard
 * Version: 9.0 - Enterprise Client Dashboard
 * 
 * FEATURES:
 * - Single column layout
 * - Disk usage monitoring (WordPress site only)
 * - DNS & SSL professional display
 * - Referral program tracking
 * - Advanced maintenance plan management
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
		'定期備份採每日備份，保留三份，緊急還原使用',
		'效能與速度優化：圖片最佳化 WebP、快取設定',
		'24 小時網站狀態監測',
		'網站異常處理與救援',
		'SEO 與基本分析支援（Google Search Console 錯誤排除、網站結構與索引問題檢查）',
		'使用 Cloudflare CDN 加速全球訪問',
		'99% 正常運轉時間保證',
		'企業級防火牆防護（7G Firewall / AI Bot Protection）'
	));
	add_option('wu_dashboard_hosting_plan', 'image');
	add_option('wu_dashboard_hosting_rating', '優良運作');
	add_option('wu_dashboard_disk_quota', 5120); // MB
	add_option('wu_dashboard_payments', array());
	add_option('wu_dashboard_referrals', array());
	add_option('wu_dashboard_advanced_plan', 0);
	add_option('wu_dashboard_domain_name', '');
});

// ===== Dashboard Widget =====

add_action('wp_dashboard_setup', function() {
	if (!get_option('wu_dashboard_enabled', 1)) {
		return;
	}
	
	wp_add_dashboard_widget(
		'wu_unified_dashboard',
		'<span class="dashicons dashicons-dashboard"></span> 網站維運管理儀表板',
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
	$ssl_info = wu_get_ssl_info();
	$php_version = PHP_VERSION;
	$hosting_plan = get_option('wu_dashboard_hosting_plan', 'image');
	$hosting_rating = get_option('wu_dashboard_hosting_rating', '優良運作');
	$disk_info = wu_get_disk_info();
	$wp_memory_limit = WP_MEMORY_LIMIT;
	$login_stats = wu_get_login_stats();
	$services = get_option('wu_dashboard_services', array());
	$recent_work = get_option('wu_dashboard_recent_work', array());
	$payments = get_option('wu_dashboard_payments', array());
	$referrals = get_option('wu_dashboard_referrals', array());
	$advanced_plan = get_option('wu_dashboard_advanced_plan', 0);
	$domain_name = get_option('wu_dashboard_domain_name', parse_url(home_url(), PHP_URL_HOST));
	
	$status_config = array(
		'normal' => array('label' => '正常運作', 'color' => '#46b450', 'icon' => '✓'),
		'watching' => array('label' => '觀察中', 'color' => '#f0b849', 'icon' => '⚠'),
		'handling' => array('label' => '處理中', 'color' => '#00a0d2', 'icon' => '🔧')
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
		
		<!-- 網站狀態總覽 -->
		<div class="wu-dashboard-section">
			<div class="wu-section-header">
				<span class="dashicons dashicons-admin-site"></span>
				網站狀態總覽
			</div>
			<div class="wu-status-overview">
				<div class="wu-status-main" style="border-color:<?php echo $current_status['color']; ?>;">
					<div class="wu-status-icon" style="background:<?php echo $current_status['color']; ?>;">
						<?php echo $current_status['icon']; ?>
					</div>
					<div class="wu-status-info">
						<div class="wu-status-label">當前狀態</div>
						<div class="wu-status-value" style="color:<?php echo $current_status['color']; ?>;">
							<?php echo esc_html($current_status['label']); ?>
						</div>
					</div>
				</div>
				<div class="wu-status-grid">
					<div class="wu-status-item">
						<div class="wu-status-item-icon">🌐</div>
						<div>
							<div class="wu-status-item-label">網域名稱</div>
							<div class="wu-status-item-value"><?php echo esc_html($domain_name); ?></div>
							<div class="wu-status-item-meta">DNS 託管：Cloudflare 管理</div>
						</div>
					</div>
					<div class="wu-status-item">
						<div class="wu-status-item-icon"><?php echo $ssl_info['icon']; ?></div>
						<div>
							<div class="wu-status-item-label">SSL 安全憑證</div>
							<div class="wu-status-item-value" style="color:<?php echo $ssl_info['color']; ?>;">
								<?php echo esc_html($ssl_info['status']); ?>
							</div>
							<div class="wu-status-item-meta"><?php echo esc_html($ssl_info['description']); ?></div>
						</div>
					</div>
					<div class="wu-status-item">
						<div class="wu-status-item-icon">⚙️</div>
						<div>
							<div class="wu-status-item-label">PHP 版本</div>
							<div class="wu-status-item-value"><?php echo esc_html($php_version); ?></div>
							<div class="wu-status-item-meta">系統環境</div>
						</div>
					</div>
					<div class="wu-status-item">
						<div class="wu-status-item-icon">🖥️</div>
						<div>
							<div class="wu-status-item-label">主機方案</div>
							<div class="wu-status-item-value"><?php echo esc_html($plan_name); ?></div>
							<div class="wu-status-item-meta">評估：<?php echo esc_html($hosting_rating); ?></div>
						</div>
					</div>
				</div>
			</div>
			<?php if (!empty($status_note)): ?>
			<div class="wu-alert">
				<span class="dashicons dashicons-info"></span>
				<?php echo nl2br(esc_html($status_note)); ?>
			</div>
			<?php endif; ?>
		</div>
		
		<!-- 磁碟使用狀況 -->
		<div class="wu-dashboard-section">
			<div class="wu-section-header">
				<span class="dashicons dashicons-chart-pie"></span>
				磁碟使用狀況
			</div>
			<div class="wu-disk-display">
				<div class="wu-disk-chart">
					<div class="wu-disk-circle">
						<svg width="180" height="180" viewBox="0 0 180 180">
							<circle cx="90" cy="90" r="70" fill="none" stroke="#e0e0e0" stroke-width="20"></circle>
							<circle cx="90" cy="90" r="70" fill="none" stroke="<?php echo $disk_info['color']; ?>" stroke-width="20" 
								stroke-dasharray="<?php echo $disk_info['percentage'] * 4.4; ?> 440" 
								stroke-linecap="round" 
								transform="rotate(-90 90 90)"></circle>
						</svg>
						<div class="wu-disk-percentage"><?php echo esc_html($disk_info['percentage']); ?>%</div>
					</div>
				</div>
				<div class="wu-disk-info">
					<div class="wu-disk-stats">
						<div class="wu-disk-stat">
							<div class="wu-disk-stat-label">已使用</div>
							<div class="wu-disk-stat-value" style="color:<?php echo $disk_info['color']; ?>;">
								<?php echo esc_html($disk_info['used_formatted']); ?>
							</div>
						</div>
						<div class="wu-disk-stat">
							<div class="wu-disk-stat-label">總配額</div>
							<div class="wu-disk-stat-value">
								<?php echo esc_html($disk_info['quota_formatted']); ?>
							</div>
						</div>
						<div class="wu-disk-stat">
							<div class="wu-disk-stat-label">剩餘空間</div>
							<div class="wu-disk-stat-value">
								<?php echo esc_html($disk_info['remaining_formatted']); ?>
							</div>
						</div>
					</div>
					<div class="wu-disk-status <?php echo esc_attr($disk_info['status_class']); ?>">
						<?php echo esc_html($disk_info['status_icon'] . ' ' . $disk_info['status_text']); ?>
					</div>
				</div>
			</div>
		</div>
		
		<!-- 登入統計 -->
		<div class="wu-dashboard-section">
			<div class="wu-section-header">
				<span class="dashicons dashicons-admin-users"></span>
				管理員登入統計
			</div>
			<div class="wu-login-grid">
				<div class="wu-login-stat">
					<div class="wu-login-stat-value"><?php echo number_format($login_stats['total_admins']); ?></div>
					<div class="wu-login-stat-label">管理員總數</div>
				</div>
				<div class="wu-login-stat">
					<div class="wu-login-stat-value"><?php echo number_format($login_stats['today_logins']); ?></div>
					<div class="wu-login-stat-label">今日登入</div>
				</div>
				<div class="wu-login-stat">
					<div class="wu-login-stat-value"><?php echo number_format($login_stats['week_logins']); ?></div>
					<div class="wu-login-stat-label">本週登入</div>
				</div>
				<div class="wu-login-stat">
					<div class="wu-login-stat-value"><?php echo number_format($login_stats['month_logins']); ?></div>
					<div class="wu-login-stat-label">本月登入</div>
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
					<?php foreach (array_slice($login_stats['recent_admins'], 0, 5) as $admin): ?>
					<tr>
						<td><?php echo esc_html($admin['name']); ?></td>
						<td><?php echo esc_html($admin['time']); ?></td>
						<td><code><?php echo esc_html($admin['ip']); ?></code></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php endif; ?>
		</div>
		
		<!-- 維運服務項目 -->
		<?php if (!empty($services)): ?>
		<div class="wu-dashboard-section">
			<div class="wu-section-header">
				<span class="dashicons dashicons-admin-tools"></span>
				維運服務項目
			</div>
			<div class="wu-services-grid">
				<?php foreach ($services as $service): ?>
				<div class="wu-service-item">
					<span class="wu-service-icon">✓</span>
					<span class="wu-service-text"><?php echo esc_html($service); ?></span>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>
		
		<!-- 進階維護方案 -->
		<?php if ($advanced_plan): ?>
		<div class="wu-dashboard-section wu-advanced-plan-active">
			<div class="wu-section-header">
				<span class="dashicons dashicons-star-filled"></span>
				進階維護方案（已啟用）
			</div>
			<div class="wu-advanced-features">
				<div class="wu-advanced-feature">
					<span class="wu-advanced-icon">☁️</span>
					<div>
						<div class="wu-advanced-title">Object Storage 異地資料備援</div>
						<div class="wu-advanced-desc">最多保留 30 份系統備份，僅作系統還原使用</div>
					</div>
				</div>
				<div class="wu-advanced-feature">
					<span class="wu-advanced-icon">🧹</span>
					<div>
						<div class="wu-advanced-title">定期網站垃圾清理與資料庫基礎優化</div>
						<div class="wu-advanced-desc">維持網站高效運作</div>
					</div>
				</div>
				<div class="wu-advanced-feature">
					<span class="wu-advanced-icon">📊</span>
					<div>
						<div class="wu-advanced-title">主機與網站狀態定期檢視</div>
						<div class="wu-advanced-desc">屬內部維運作業，未另行提供書面檢測報告</div>
					</div>
				</div>
				<div class="wu-advanced-feature">
					<span class="wu-advanced-icon">🔒</span>
					<div>
						<div class="wu-advanced-title">定期更新、漏洞修補</div>
						<div class="wu-advanced-desc">確保系統安全性</div>
					</div>
				</div>
				<div class="wu-advanced-feature">
					<span class="wu-advanced-icon">💬</span>
					<div>
						<div class="wu-advanced-title">網站問題諮詢與技術回覆</div>
						<div class="wu-advanced-desc">於工作日 24 小時內回覆</div>
					</div>
				</div>
				<div class="wu-advanced-feature">
					<span class="wu-advanced-icon">🔑</span>
					<div>
						<div class="wu-advanced-title">提供所需模組授權金鑰並協助定期更新</div>
						<div class="wu-advanced-desc">保持功能最新狀態</div>
					</div>
				</div>
			</div>
		</div>
		<?php else: ?>
		<div class="wu-dashboard-section wu-advanced-plan-promo">
			<div class="wu-section-header">
				<span class="dashicons dashicons-star-empty"></span>
				升級進階維護方案
			</div>
			<div class="wu-promo-content">
				<div class="wu-promo-header">
					<div class="wu-promo-title">進階維護方案</div>
					<div class="wu-promo-price">NT$ 8,000 <span>/年（未稅）</span></div>
				</div>
				<div class="wu-promo-features">
					<div class="wu-promo-feature">☁️ Object Storage 異地資料備援（保留 30 份備份）</div>
					<div class="wu-promo-feature">🧹 定期網站垃圾清理與資料庫基礎優化</div>
					<div class="wu-promo-feature">📊 主機與網站狀態定期檢視</div>
					<div class="wu-promo-feature">🔒 定期更新、漏洞修補</div>
					<div class="wu-promo-feature">💬 網站問題諮詢與技術回覆（工作日 24 小時內）</div>
					<div class="wu-promo-feature">🔑 提供所需模組授權金鑰並協助定期更新</div>
				</div>
				<div class="wu-promo-cta">
					<p>升級進階維護方案，享受更完整的技術支援與資料安全保障</p>
					<a href="mailto:contact@wumetax.com?subject=進階維護方案諮詢" class="wu-promo-button">立即諮詢升級</a>
				</div>
			</div>
		</div>
		<?php endif; ?>
		
		<!-- 推薦回饋專區 -->
		<?php if (!empty($referrals)): ?>
		<div class="wu-dashboard-section wu-referral-section">
			<div class="wu-section-header">
				<span class="dashicons dashicons-groups"></span>
				推薦回饋專區
			</div>
			<div class="wu-referral-info">
				<div class="wu-referral-rule">
					<div class="wu-referral-rule-item">✅ 成功推薦新客戶</div>
					<div class="wu-referral-rule-item">✅ 被推薦人每續約一年主機</div>
					<div class="wu-referral-rule-item">🎁 推薦者即可額外獲得 1 個月主機使用權</div>
					<div class="wu-referral-rule-item">🔁 只要被推薦人持續續約，回饋就會持續累積</div>
				</div>
			</div>
			<table class="wu-table wu-referral-table">
				<thead>
					<tr>
						<th>被推薦人</th>
						<th>成功續費時間</th>
						<th>獎勵狀態</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($referrals as $referral): ?>
					<tr>
						<td><strong><?php echo esc_html($referral['name']); ?></strong></td>
						<td><?php echo esc_html(date('Y/m/d', strtotime($referral['date']))); ?></td>
						<td>
							<?php if ($referral['rewarded']): ?>
								<span class="wu-badge wu-badge-success">✓ 已發放</span>
							<?php else: ?>
								<span class="wu-badge wu-badge-pending">⏳ 處理中</span>
							<?php endif; ?>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php endif; ?>
		
		<!-- 最近處理紀錄 -->
		<?php if (!empty($recent_work)): ?>
		<div class="wu-dashboard-section">
			<div class="wu-section-header">
				<span class="dashicons dashicons-list-view"></span>
				最近處理紀錄
			</div>
			<div class="wu-timeline">
				<?php foreach (array_slice($recent_work, 0, 10) as $work): ?>
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
		
		<!-- 款項紀錄 -->
		<?php if (current_user_can('manage_options') && !empty($payments)): ?>
		<div class="wu-dashboard-section">
			<div class="wu-section-header">
				<span class="dashicons dashicons-money-alt"></span>
				款項紀錄
			</div>
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
		<?php endif; ?>
		
		<!-- 聯絡資訊 -->
		<div class="wu-dashboard-section wu-contact-section">
			<div class="wu-contact-box">
				<div class="wu-contact-header">
					<div class="wu-contact-name">WUMETAX 末特數位科技</div>
					<div class="wu-contact-role">網站維運管理單位</div>
				</div>
				<div class="wu-contact-links">
					<a href="mailto:contact@wumetax.com" class="wu-contact-link">
						<span class="dashicons dashicons-email"></span>
						contact@wumetax.com
					</a>
					<a href="https://lin.ee/Lut7wCe" target="_blank" class="wu-contact-link">
						<span class="dashicons dashicons-format-chat"></span>
						LINE 線上客服
					</a>
				</div>
			</div>
		</div>
		
	</div>
	
	<div class="wu-footer-note">
		<span class="dashicons dashicons-info"></span>
		所有統計資料每 6-12 小時自動更新 | 資料更新不影響後台載入速度
	</div>
	<?php
}

// ===== Helper Functions =====

function wu_get_ssl_info() {
	$cache_key = 'wu_ssl_info';
	$cached = get_transient($cache_key);
	
	if ($cached !== false) {
		return $cached;
	}
	
	$is_ssl = is_ssl();
	
	if ($is_ssl) {
		$info = array(
			'status' => 'HTTPS 已啟用',
			'icon' => '🔒',
			'color' => '#46b450',
			'description' => 'SSL 憑證確保資料傳輸加密，保護用戶隱私與網站信譽，提升 SEO 排名'
		);
	} else {
		$info = array(
			'status' => 'HTTP 未加密',
			'icon' => '⚠️',
			'color' => '#dc3232',
			'description' => '建議啟用 SSL 憑證以確保資料安全'
		);
	}
	
	set_transient($cache_key, $info, DAY_IN_SECONDS);
	
	return $info;
}

function wu_get_disk_info() {
	$cache_key = 'wu_disk_info';
	$cached = get_transient($cache_key);
	
	if ($cached !== false) {
		return $cached;
	}
	
	$quota_mb = get_option('wu_dashboard_disk_quota', 5120);
	$used_mb = wu_calculate_site_size();
	$percentage = min(100, ($used_mb / $quota_mb) * 100);
	$remaining_mb = max(0, $quota_mb - $used_mb);
	
	// 狀態判斷
	if ($percentage < 70) {
		$status_text = '空間充足';
		$status_icon = '🟢';
		$status_class = 'wu-disk-status-normal';
		$color = '#46b450';
	} elseif ($percentage < 90) {
		$status_text = '即將達上限';
		$status_icon = '🟡';
		$status_class = 'wu-disk-status-warning';
		$color = '#f0b849';
	} else {
		$status_text = '已接近上限';
		$status_icon = '🔴';
		$status_class = 'wu-disk-status-danger';
		$color = '#dc3232';
	}
	
	$info = array(
		'used_mb' => $used_mb,
		'quota_mb' => $quota_mb,
		'remaining_mb' => $remaining_mb,
		'percentage' => number_format($percentage, 1),
		'used_formatted' => number_format($used_mb, 0) . ' MB',
		'quota_formatted' => number_format($quota_mb, 0) . ' MB',
		'remaining_formatted' => number_format($remaining_mb, 0) . ' MB',
		'status_text' => $status_text,
		'status_icon' => $status_icon,
		'status_class' => $status_class,
		'color' => $color
	);
	
	set_transient($cache_key, $info, HOUR_IN_SECONDS * 12);
	
	return $info;
}

function wu_calculate_site_size() {
	$cache_key = 'wu_site_size_mb';
	$cached = get_transient($cache_key);
	
	if ($cached !== false) {
		return $cached;
	}
	
	$size = 0;
	$site_path = ABSPATH;
	
	try {
		if (function_exists('exec') && @exec('du -sm ' . escapeshellarg($site_path) . ' 2>/dev/null', $output)) {
			$size = intval($output[0]);
		} else {
			// Fallback: 遞迴計算（僅在無法使用 exec 時）
			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($site_path, RecursiveDirectoryIterator::SKIP_DOTS),
				RecursiveIteratorIterator::CATCH_GET_CHILD
			);
			
			$bytes = 0;
			foreach ($iterator as $file) {
				try {
					if ($file->isFile()) {
						$bytes += $file->getSize();
					}
				} catch (Exception $e) {
					continue;
				}
			}
			$size = round($bytes / 1024 / 1024, 2);
		}
	} catch (Exception $e) {
		$size = 0;
	}
	
	set_transient($cache_key, $size, HOUR_IN_SECONDS * 12);
	
	return $size;
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
		update_option('wu_dashboard_domain_name', sanitize_text_field($_POST['domain_name'] ?? ''));
		
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
		update_option('wu_dashboard_disk_quota', intval($_POST['disk_quota'] ?? 5120));
		
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
		
		// 推薦紀錄
		$referrals = array();
		if (!empty($_POST['referral_names'])) {
			foreach ($_POST['referral_names'] as $i => $name) {
				if (!empty($name)) {
					$referrals[] = array(
						'name' => sanitize_text_field($name),
						'date' => sanitize_text_field($_POST['referral_dates'][$i] ?? ''),
						'rewarded' => isset($_POST['referral_rewarded'][$i]) ? 1 : 0
					);
				}
			}
		}
		update_option('wu_dashboard_referrals', $referrals);
		
		update_option('wu_dashboard_advanced_plan', isset($_POST['advanced_plan']) ? 1 : 0);
		
		delete_transient('wu_ssl_info');
		delete_transient('wu_disk_info');
		delete_transient('wu_site_size_mb');
		delete_transient('wu_login_stats');
		
		echo '<div class="notice notice-success is-dismissible"><p><strong>✅ 設定已儲存</strong></p></div>';
	}
	
	$enabled = get_option('wu_dashboard_enabled', 1);
	$status = get_option('wu_dashboard_site_status', 'normal');
	$status_note = get_option('wu_dashboard_status_note', '');
	$domain_name = get_option('wu_dashboard_domain_name', parse_url(home_url(), PHP_URL_HOST));
	$recent_work = get_option('wu_dashboard_recent_work', array());
	$services = get_option('wu_dashboard_services', array());
	$hosting_plan = get_option('wu_dashboard_hosting_plan', 'image');
	$hosting_rating = get_option('wu_dashboard_hosting_rating', '優良運作');
	$disk_quota = get_option('wu_dashboard_disk_quota', 5120);
	$payments = get_option('wu_dashboard_payments', array());
	$referrals = get_option('wu_dashboard_referrals', array());
	$advanced_plan = get_option('wu_dashboard_advanced_plan', 0);
	$disk_info = wu_get_disk_info();
	
	?>
	<div class="wrap">
		<h1>🎛️ 儀表板設定</h1>
		
		<div class="notice notice-info" style="padding:15px;">
			<p style="margin:0;"><strong>系統說明</strong></p>
			<ul style="margin:8px 0 0 20px;line-height:1.8;">
				<li>儀表板採用單欄式設計，整合在 WordPress 原始後台首頁</li>
				<li>磁碟使用僅計算 WordPress 網站本身，不影響後台載入速度</li>
				<li>所有統計資料使用快取機制，每 6-12 小時自動更新</li>
				<li>登入追蹤僅記錄管理員帳號，不影響一般用戶</li>
			</ul>
		</div>
		
		<!-- 當前磁碟使用狀態 -->
		<div style="background:#fff;padding:20px;border:1px solid #ddd;margin-top:20px;border-left:4px solid #0073aa;">
			<h2 style="margin-top:0;">📊 當前磁碟使用狀態</h2>
			<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:15px;">
				<div style="padding:15px;background:#f9f9f9;border-left:3px solid <?php echo $disk_info['color']; ?>;">
					<div style="font-size:11px;color:#666;margin-bottom:5px;">已使用</div>
					<div style="font-size:22px;font-weight:700;color:<?php echo $disk_info['color']; ?>;"><?php echo esc_html($disk_info['used_formatted']); ?></div>
				</div>
				<div style="padding:15px;background:#f9f9f9;border-left:3px solid #0073aa;">
					<div style="font-size:11px;color:#666;margin-bottom:5px;">配額</div>
					<div style="font-size:22px;font-weight:700;"><?php echo esc_html($disk_info['quota_formatted']); ?></div>
				</div>
				<div style="padding:15px;background:#f9f9f9;border-left:3px solid #46b450;">
					<div style="font-size:11px;color:#666;margin-bottom:5px;">剩餘</div>
					<div style="font-size:22px;font-weight:700;color:#46b450;"><?php echo esc_html($disk_info['remaining_formatted']); ?></div>
				</div>
				<div style="padding:15px;background:#f9f9f9;border-left:3px solid <?php echo $disk_info['color']; ?>;">
					<div style="font-size:11px;color:#666;margin-bottom:5px;">使用率</div>
					<div style="font-size:22px;font-weight:700;color:<?php echo $disk_info['color']; ?>;"><?php echo esc_html($disk_info['percentage']); ?>%</div>
				</div>
			</div>
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
							<option value="normal" <?php selected($status, 'normal'); ?>>✓ 正常運作</option>
							<option value="watching" <?php selected($status, 'watching'); ?>>⚠ 觀察中</option>
							<option value="handling" <?php selected($status, 'handling'); ?>>🔧 處理中</option>
						</select>
						<br>
						<textarea name="status_note" rows="2" class="large-text" style="margin-top:10px;" placeholder="狀態說明 (選填，會顯示在儀表板頂部)"><?php echo esc_textarea($status_note); ?></textarea>
					</td>
				</tr>
				
				<tr>
					<th><label>網域名稱</label></th>
					<td>
						<input type="text" name="domain_name" value="<?php echo esc_attr($domain_name); ?>" class="regular-text" placeholder="example.com">
						<p class="description">顯示在儀表板的網域名稱（自動偵測，可手動修改）</p>
					</td>
				</tr>
				
				<tr>
					<th><label>主機方案</label></th>
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
							磁碟配額 (MB):
							<input type="number" name="disk_quota" value="<?php echo esc_attr($disk_quota); ?>" class="regular-text" min="1024" step="512">
						</label>
						<p class="description">預設 5120 MB = 5 GB</p>
					</td>
				</tr>
				
				<tr>
					<th><label>進階維護方案</label></th>
					<td>
						<label>
							<input type="checkbox" name="advanced_plan" value="1" <?php checked(1, $advanced_plan); ?>>
							<strong>客戶已訂購進階維護方案</strong>
						</label>
						<p class="description">勾選後儀表板會顯示進階維護功能；未勾選則顯示升級方案推廣區</p>
					</td>
				</tr>
				
				<tr>
					<th><label>維運服務項目</label></th>
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
						<button type="button" class="button" onclick="addService()">➕ 新增服務項目</button>
					</td>
				</tr>
				
				<tr>
					<th><label>推薦回饋紀錄</label></th>
					<td>
						<div id="referral-container">
							<?php 
							if (empty($referrals)) {
								$referrals = array(array('name' => '', 'date' => '', 'rewarded' => 0));
							}
							foreach ($referrals as $referral): 
							?>
							<div style="background:#f9f9f9;padding:15px;margin-bottom:10px;display:grid;grid-template-columns:200px 150px 100px 80px;gap:10px;align-items:center;">
								<input type="text" name="referral_names[]" value="<?php echo esc_attr($referral['name']); ?>" placeholder="被推薦人姓名">
								<input type="date" name="referral_dates[]" value="<?php echo esc_attr($referral['date']); ?>">
								<label>
									<input type="checkbox" name="referral_rewarded[]" value="1" <?php checked(1, $referral['rewarded']); ?>>
									已發放獎勵
								</label>
								<button type="button" class="button" onclick="this.parentElement.remove()">刪除</button>
							</div>
							<?php endforeach; ?>
						</div>
						<button type="button" class="button" onclick="addReferral()">➕ 新增推薦紀錄</button>
						<p class="description">紀錄成功推薦的客戶及獎勵發放狀態</p>
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
						<button type="button" class="button" onclick="addWork()">➕ 新增處理紀錄</button>
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
						<button type="button" class="button" onclick="addPayment()">➕ 新增款項</button>
					</td>
				</tr>
				
			</table>
			
			<?php submit_button('💾 儲存設定', 'primary large', 'wu_save'); ?>
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
	
	function addReferral() {
		document.getElementById('referral-container').insertAdjacentHTML('beforeend',
			'<div style="background:#f9f9f9;padding:15px;margin-bottom:10px;display:grid;grid-template-columns:200px 150px 100px 80px;gap:10px;align-items:center;">' +
			'<input type="text" name="referral_names[]" placeholder="被推薦人姓名">' +
			'<input type="date" name="referral_dates[]">' +
			'<label><input type="checkbox" name="referral_rewarded[]" value="1"> 已發放獎勵</label>' +
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
	/* Full Width Single Column */
	#wu_unified_dashboard {
		width: 100% !important;
		grid-column: 1 / -1 !important;
	}
	
	#wu_unified_dashboard .inside {
		padding: 0 !important;
		margin: 0 !important;
	}
	
	.wu-unified-container {
		display: flex;
		flex-direction: column;
		gap: 0;
	}
	
	.wu-dashboard-section {
		background: #fff;
		border-bottom: 1px solid #e0e0e0;
		padding: 25px;
	}
	
	.wu-dashboard-section:last-child {
		border-bottom: none;
	}
	
	.wu-section-header {
		font-size: 16px;
		font-weight: 600;
		color: #1e1e1e;
		margin-bottom: 20px;
		display: flex;
		align-items: center;
		gap: 8px;
		padding-bottom: 12px;
		border-bottom: 2px solid #0073aa;
	}
	
	.wu-section-header .dashicons {
		color: #0073aa;
		font-size: 20px;
	}
	
	/* Status Overview */
	.wu-status-overview {
		display: grid;
		gap: 20px;
	}
	
	.wu-status-main {
		display: flex;
		align-items: center;
		gap: 20px;
		padding: 20px;
		background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
		border-left: 5px solid;
		border-radius: 4px;
	}
	
	.wu-status-icon {
		width: 60px;
		height: 60px;
		border-radius: 50%;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 28px;
		color: #fff;
		font-weight: 700;
		flex-shrink: 0;
	}
	
	.wu-status-info {
		flex: 1;
	}
	
	.wu-status-label {
		font-size: 12px;
		color: #666;
		text-transform: uppercase;
		margin-bottom: 4px;
		font-weight: 600;
	}
	
	.wu-status-value {
		font-size: 24px;
		font-weight: 700;
		line-height: 1.2;
	}
	
	.wu-status-grid {
		display: grid;
		grid-template-columns: repeat(4, 1fr);
		gap: 15px;
	}
	
	.wu-status-item {
		display: flex;
		gap: 12px;
		padding: 15px;
		background: #f9f9f9;
		border: 1px solid #e0e0e0;
		border-radius: 4px;
	}
	
	.wu-status-item-icon {
		font-size: 24px;
		flex-shrink: 0;
	}
	
	.wu-status-item-label {
		font-size: 11px;
		color: #666;
		text-transform: uppercase;
		margin-bottom: 4px;
		font-weight: 600;
	}
	
	.wu-status-item-value {
		font-size: 16px;
		font-weight: 700;
		color: #111;
		margin-bottom: 4px;
	}
	
	.wu-status-item-meta {
		font-size: 11px;
		color: #999;
	}
	
	/* Alert */
	.wu-alert {
		padding: 15px;
		background: #fff3cd;
		border-left: 4px solid #f0b849;
		font-size: 13px;
		color: #333;
		line-height: 1.6;
		margin-top: 20px;
		display: flex;
		gap: 10px;
		align-items: flex-start;
		border-radius: 4px;
	}
	
	.wu-alert .dashicons {
		color: #f0b849;
		flex-shrink: 0;
	}
	
	/* Disk Display */
	.wu-disk-display {
		display: grid;
		grid-template-columns: 200px 1fr;
		gap: 30px;
		align-items: center;
	}
	
	.wu-disk-chart {
		display: flex;
		justify-content: center;
		align-items: center;
	}
	
	.wu-disk-circle {
		position: relative;
		width: 180px;
		height: 180px;
	}
	
	.wu-disk-percentage {
		position: absolute;
		top: 50%;
		left: 50%;
		transform: translate(-50%, -50%);
		font-size: 32px;
		font-weight: 700;
		color: #111;
	}
	
	.wu-disk-info {
		flex: 1;
	}
	
	.wu-disk-stats {
		display: grid;
		grid-template-columns: repeat(3, 1fr);
		gap: 15px;
		margin-bottom: 20px;
	}
	
	.wu-disk-stat {
		padding: 15px;
		background: #f9f9f9;
		border: 1px solid #e0e0e0;
		border-radius: 4px;
	}
	
	.wu-disk-stat-label {
		font-size: 11px;
		color: #666;
		text-transform: uppercase;
		margin-bottom: 8px;
		font-weight: 600;
	}
	
	.wu-disk-stat-value {
		font-size: 24px;
		font-weight: 700;
		color: #111;
	}
	
	.wu-disk-status {
		padding: 12px 20px;
		text-align: center;
		font-size: 15px;
		font-weight: 600;
		border-radius: 4px;
	}
	
	.wu-disk-status-normal {
		background: #d4edda;
		color: #155724;
	}
	
	.wu-disk-status-warning {
		background: #fff3cd;
		color: #856404;
	}
	
	.wu-disk-status-danger {
		background: #f8d7da;
		color: #721c24;
	}
	
	/* Login Grid */
	.wu-login-grid {
		display: grid;
		grid-template-columns: repeat(4, 1fr);
		gap: 15px;
		margin-bottom: 20px;
	}
	
	.wu-login-stat {
		padding: 20px;
		background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
		border-radius: 4px;
		text-align: center;
		color: #fff;
	}
	
	.wu-login-stat-value {
		font-size: 32px;
		font-weight: 700;
		margin-bottom: 8px;
	}
	
	.wu-login-stat-label {
		font-size: 12px;
		opacity: 0.9;
	}
	
	/* Services Grid */
	.wu-services-grid {
		display: grid;
		grid-template-columns: repeat(2, 1fr);
		gap: 12px;
	}
	
	.wu-service-item {
		display: flex;
		align-items: flex-start;
		gap: 12px;
		padding: 15px;
		background: #f9f9f9;
		border: 1px solid #e0e0e0;
		border-radius: 4px;
		font-size: 14px;
		color: #333;
		line-height: 1.6;
	}
	
	.wu-service-icon {
		color: #46b450;
		font-weight: 700;
		font-size: 18px;
		flex-shrink: 0;
	}
	
	.wu-service-text {
		flex: 1;
	}
	
	/* Advanced Plan */
	.wu-advanced-plan-active {
		background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
		border: 2px solid #2196f3;
	}
	
	.wu-advanced-features {
		display: grid;
		gap: 12px;
	}
	
	.wu-advanced-feature {
		display: flex;
		gap: 15px;
		padding: 15px;
		background: rgba(255, 255, 255, 0.8);
		border-radius: 4px;
	}
	
	.wu-advanced-icon {
		font-size: 24px;
		flex-shrink: 0;
	}
	
	.wu-advanced-title {
		font-size: 14px;
		font-weight: 600;
		color: #111;
		margin-bottom: 4px;
	}
	
	.wu-advanced-desc {
		font-size: 12px;
		color: #666;
	}
	
	/* Promo */
	.wu-advanced-plan-promo {
		background: #f9f9f9;
		border: 2px dashed #ccc;
	}
	
	.wu-promo-content {
		background: #fff;
		border: 1px solid #e0e0e0;
		border-radius: 4px;
		overflow: hidden;
	}
	
	.wu-promo-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 20px;
		background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
		color: #fff;
	}
	
	.wu-promo-title {
		font-size: 20px;
		font-weight: 700;
	}
	
	.wu-promo-price {
		font-size: 28px;
		font-weight: 700;
	}
	
	.wu-promo-price span {
		font-size: 14px;
		font-weight: 400;
		opacity: 0.9;
	}
	
	.wu-promo-features {
		padding: 20px;
		display: grid;
		gap: 10px;
	}
	
	.wu-promo-feature {
		font-size: 14px;
		color: #333;
		padding: 10px;
		background: #f9f9f9;
		border-radius: 4px;
	}
	
	.wu-promo-cta {
		padding: 20px;
		background: #f9f9f9;
		text-align: center;
		border-top: 1px solid #e0e0e0;
	}
	
	.wu-promo-cta p {
		margin: 0 0 15px 0;
		font-size: 14px;
		color: #666;
	}
	
	.wu-promo-button {
		display: inline-block;
		padding: 12px 30px;
		background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
		color: #fff;
		text-decoration: none;
		border-radius: 4px;
		font-weight: 600;
		transition: transform 0.2s;
	}
	
	.wu-promo-button:hover {
		transform: translateY(-2px);
		color: #fff;
	}
	
	/* Referral Section */
	.wu-referral-section {
		background: linear-gradient(135deg, #fff9e6 0%, #ffeaa7 100%);
		border: 2px solid #fdcb6e;
	}
	
	.wu-referral-info {
		margin-bottom: 20px;
	}
	
	.wu-referral-rule {
		display: grid;
		grid-template-columns: repeat(2, 1fr);
		gap: 12px;
	}
	
	.wu-referral-rule-item {
		padding: 12px;
		background: rgba(255, 255, 255, 0.8);
		border-radius: 4px;
		font-size: 14px;
		font-weight: 500;
	}
	
	.wu-referral-table {
		background: #fff;
	}
	
	/* Table */
	.wu-table {
		width: 100%;
		border-collapse: collapse;
		font-size: 13px;
		background: #fff;
		border: 1px solid #e0e0e0;
	}
	
	.wu-table th {
		padding: 12px;
		background: #f0f0f0;
		border-bottom: 2px solid #ddd;
		text-align: left;
		font-weight: 600;
		color: #666;
		font-size: 12px;
		text-transform: uppercase;
	}
	
	.wu-table td {
		padding: 12px;
		border-bottom: 1px solid #f0f0f0;
		color: #333;
	}
	
	.wu-table tr:last-child td {
		border-bottom: none;
	}
	
	.wu-table code {
		padding: 2px 6px;
		background: #f5f5f5;
		border: 1px solid #e0e0e0;
		border-radius: 3px;
		font-size: 11px;
	}
	
	/* Timeline */
	.wu-timeline {
		display: flex;
		flex-direction: column;
		gap: 15px;
	}
	
	.wu-timeline-row {
		display: grid;
		grid-template-columns: 100px 1fr;
		gap: 15px;
		padding: 15px;
		background: #f9f9f9;
		border-left: 3px solid #0073aa;
		border-radius: 4px;
	}
	
	.wu-timeline-date {
		font-size: 12px;
		color: #0073aa;
		font-weight: 600;
	}
	
	.wu-timeline-title {
		font-size: 14px;
		font-weight: 600;
		color: #111;
		margin-bottom: 6px;
	}
	
	.wu-timeline-note {
		font-size: 13px;
		color: #666;
		line-height: 1.6;
	}
	
	/* Badge */
	.wu-badge {
		display: inline-block;
		padding: 4px 10px;
		font-size: 11px;
		font-weight: 600;
		text-transform: uppercase;
		border-radius: 3px;
	}
	
	.wu-badge-success {
		background: #d4edda;
		color: #155724;
	}
	
	.wu-badge-warning {
		background: #fff3cd;
		color: #856404;
	}
	
	.wu-badge-pending {
		background: #e7f3ff;
		color: #0073aa;
	}
	
	/* Contact Section */
	.wu-contact-section {
		background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
		border: none;
	}
	
	.wu-contact-box {
		text-align: center;
		color: #fff;
	}
	
	.wu-contact-header {
		margin-bottom: 20px;
	}
	
	.wu-contact-name {
		font-size: 24px;
		font-weight: 700;
		margin-bottom: 8px;
	}
	
	.wu-contact-role {
		font-size: 14px;
		opacity: 0.9;
	}
	
	.wu-contact-links {
		display: flex;
		justify-content: center;
		gap: 20px;
		flex-wrap: wrap;
	}
	
	.wu-contact-link {
		display: flex;
		align-items: center;
		gap: 8px;
		padding: 10px 20px;
		background: rgba(255, 255, 255, 0.2);
		color: #fff;
		text-decoration: none;
		border-radius: 4px;
		font-size: 14px;
		transition: background 0.2s;
	}
	
	.wu-contact-link:hover {
		background: rgba(255, 255, 255, 0.3);
		color: #fff;
	}
	
	.wu-contact-link .dashicons {
		font-size: 18px;
	}
	
	/* Footer Note */
	.wu-footer-note {
		margin-top: 0;
		padding: 15px 25px;
		background: #f0f0f0;
		border-top: 1px solid #e0e0e0;
		font-size: 12px;
		color: #666;
		display: flex;
		align-items: center;
		gap: 8px;
	}
	
	.wu-footer-note .dashicons {
		color: #0073aa;
		font-size: 16px;
	}
	
	/* Responsive */
	@media (max-width: 1200px) {
		.wu-status-grid,
		.wu-login-grid {
			grid-template-columns: repeat(2, 1fr);
		}
	}
	
	@media (max-width: 768px) {
		.wu-disk-display {
			grid-template-columns: 1fr;
		}
		
		.wu-status-grid,
		.wu-login-grid,
		.wu-services-grid,
		.wu-referral-rule {
			grid-template-columns: 1fr;
		}
		
		.wu-timeline-row {
			grid-template-columns: 80px 1fr;
		}
	}
	</style>
	<?php
});
