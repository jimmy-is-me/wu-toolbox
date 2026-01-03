<?php
if (!defined('ABSPATH')) exit;

/*
 * WumetaxToolkit - Professional Client Dashboard
 * Version: 13.0 - UI/UX Enhancement
 * 
 * CHANGELOG:
 * - Referral section redesigned with feedback-oriented copy
 * - Ticket history changed to vertical card layout
 * - Referral records changed to vertical card layout
 * - Added trust-building messaging
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
	add_option('wu_dashboard_backup_status', 'normal');
	add_option('wu_dashboard_security_status', 'normal');
	add_option('wu_dashboard_recent_work', array());
	add_option('wu_dashboard_services', array(
		'定期備份採每日備份,保留三份,緊急還原使用',
		'效能與速度優化:圖片最佳化 WebP、快取設定',
		'24 小時網站狀態監測',
		'網站異常處理與救援',
		'SEO 與基本分析支援(Google Search Console 錯誤排除、網站結構與索引問題檢查)',
		'使用 Cloudflare CDN 加速全球訪問',
		'99% 正常運轉時間保證',
		'企業級防火牆防護(7G Firewall / AI Bot Protection)'
	));
	add_option('wu_dashboard_value_services', array());
	add_option('wu_dashboard_hosting_plan', 'image');
	add_option('wu_dashboard_hosting_rating', '優良運作');
	add_option('wu_dashboard_disk_quota', 5120);
	add_option('wu_dashboard_payments', array());
	add_option('wu_dashboard_referrals', array());
	add_option('wu_dashboard_advanced_plan', 0);
	add_option('wu_dashboard_support_tickets', array());
});

// ===== Dashboard Widget =====

add_action('wp_dashboard_setup', function() {
	if (!get_option('wu_dashboard_enabled', 1)) {
		return;
	}
	
	wp_add_dashboard_widget(
		'wu_unified_dashboard',
		'網站維運管理儀表板',
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
	$backup_status = get_option('wu_dashboard_backup_status', 'normal');
	$security_status = get_option('wu_dashboard_security_status', 'normal');
	$ssl_info = wu_get_ssl_info();
	$php_info = wu_get_php_info();
	$hosting_plan = get_option('wu_dashboard_hosting_plan', 'image');
	$hosting_rating = get_option('wu_dashboard_hosting_rating', '優良運作');
	$disk_info = wu_get_disk_info();
	$services = get_option('wu_dashboard_services', array());
	$value_services = get_option('wu_dashboard_value_services', array());
	$recent_work = get_option('wu_dashboard_recent_work', array());
	$payments = get_option('wu_dashboard_payments', array());
	$referrals = get_option('wu_dashboard_referrals', array());
	$advanced_plan = get_option('wu_dashboard_advanced_plan', 0);
	$support_tickets = get_option('wu_dashboard_support_tickets', array());
	$domain_name = parse_url(home_url(), PHP_URL_HOST);
	
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
	
	if (!empty($support_tickets)) {
		usort($support_tickets, function($a, $b) {
			return $b['timestamp'] - $a['timestamp'];
		});
	}
	
	?>
	<div class="wu-dashboard-container">
		
		<!-- 網站狀態總覽 -->
		<div class="wu-section">
			<h3 class="wu-section-title">網站狀態總覽</h3>
			<div class="wu-status-card" style="border-left-color:<?php echo $current_status['color']; ?>;">
				<div class="wu-status-main">
					<div class="wu-status-badge" style="background:<?php echo $current_status['color']; ?>;">
						<?php echo esc_html($current_status['label']); ?>
					</div>
					<?php if (!empty($status_note)): ?>
					<div class="wu-status-note"><?php echo nl2br(esc_html($status_note)); ?></div>
					<?php endif; ?>
				</div>
			</div>
			
			<table class="wu-info-table">
				<tbody>
					<tr>
						<th>網域名稱</th>
						<td><strong><?php echo esc_html($domain_name); ?></strong></td>
						<td class="wu-info-meta">DNS 託管:Cloudflare 管理</td>
					</tr>
					<tr>
						<th>SSL 安全憑證</th>
						<td>
							<span class="wu-ssl-status" style="color:<?php echo $ssl_info['color']; ?>;">
								<?php echo esc_html($ssl_info['status']); ?>
							</span>
						</td>
						<td class="wu-info-meta"><?php echo esc_html($ssl_info['description']); ?></td>
					</tr>
					<tr>
						<th>PHP 版本</th>
						<td>
							<strong><?php echo esc_html($php_info['version']); ?></strong>
							<span class="<?php echo esc_attr($php_info['badge_class']); ?>">
								<?php echo esc_html($php_info['badge_text']); ?>
							</span>
						</td>
						<td class="wu-info-meta"><?php echo esc_html($php_info['description']); ?></td>
					</tr>
					<tr>
						<th>主機方案</th>
						<td><strong><?php echo esc_html($plan_name); ?></strong></td>
						<td class="wu-info-meta">評估:<?php echo esc_html($hosting_rating); ?></td>
					</tr>
					<tr>
						<th>備份狀態</th>
						<td>
							<span class="wu-status-indicator" style="color:<?php echo $backup_status === 'normal' ? '#46b450' : '#dc3232'; ?>;">
								<?php echo $backup_status === 'normal' ? '正常' : '異常'; ?>
							</span>
						</td>
						<td class="wu-info-meta">每日自動備份,保留 3 份</td>
					</tr>
					<tr>
						<th>安全狀態</th>
						<td>
							<span class="wu-status-indicator" style="color:<?php echo $security_status === 'normal' ? '#46b450' : '#dc3232'; ?>;">
								<?php echo $security_status === 'normal' ? '正常' : '異常'; ?>
							</span>
						</td>
						<td class="wu-info-meta">防火牆與安全監控運作中</td>
					</tr>
				</tbody>
			</table>
		</div>
		
		<!-- 磁碟使用狀況 -->
		<div class="wu-section">
			<h3 class="wu-section-title">磁碟使用狀況</h3>
			<div class="wu-disk-grid">
				<div class="wu-disk-item">
					<div class="wu-disk-label">已使用</div>
					<div class="wu-disk-value" style="color:<?php echo $disk_info['color']; ?>;">
						<?php echo esc_html($disk_info['used_formatted']); ?>
					</div>
				</div>
				<div class="wu-disk-item">
					<div class="wu-disk-label">總配額</div>
					<div class="wu-disk-value"><?php echo esc_html($disk_info['quota_formatted']); ?></div>
				</div>
				<div class="wu-disk-item">
					<div class="wu-disk-label">剩餘空間</div>
					<div class="wu-disk-value" style="color:#46b450;">
						<?php echo esc_html($disk_info['remaining_formatted']); ?>
					</div>
				</div>
				<div class="wu-disk-item">
					<div class="wu-disk-label">使用率</div>
					<div class="wu-disk-value" style="color:<?php echo $disk_info['color']; ?>;">
						<?php echo esc_html($disk_info['percentage']); ?>%
					</div>
				</div>
			</div>
			<div class="wu-disk-bar">
				<div class="wu-disk-bar-fill" style="width:<?php echo min($disk_info['percentage'], 100); ?>%;background:<?php echo $disk_info['color']; ?>;"></div>
			</div>
			<div class="wu-disk-status <?php echo esc_attr($disk_info['status_class']); ?>">
				<?php echo esc_html($disk_info['status_text']); ?>
			</div>
			
			<?php if ($disk_info['percentage'] >= 100): ?>
			<div class="wu-notice wu-notice-error">
				<strong><?php echo $disk_info['percentage'] == 100 ? '磁碟已滿' : '磁碟容量超出配額'; ?></strong><br>
				立即聯繫升級 NVMe SSD 硬碟空間:<br>
				• +5 GB:NT$ 2,000 / 年<br>
				• +10 GB:NT$ 3,500 / 年
				<div style="margin-top:10px;">
					<button type="button" class="wu-button wu-button-danger" onclick="wuRequestDiskUpgrade()">
						立即申請磁碟升級
					</button>
				</div>
			</div>
			<?php endif; ?>
		</div>
		
		<!-- 維運服務項目 -->
		<?php if (!empty($services)): ?>
		<div class="wu-section">
			<h3 class="wu-section-title">
				維運服務項目
				<span class="wu-section-label">目前方案</span>
			</h3>
			<ul class="wu-service-list">
				<?php foreach ($services as $service): ?>
				<li><?php echo esc_html($service); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php endif; ?>
		
		<!-- 加值服務 -->
		<?php if (!empty($value_services)): ?>
		<div class="wu-section">
			<h3 class="wu-section-title">
				加值服務
				<span class="wu-section-label" style="background:#00a32a;">已訂購</span>
			</h3>
			<ul class="wu-service-list">
				<?php foreach ($value_services as $service): ?>
				<li><?php echo esc_html($service); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php endif; ?>
		
		<!-- 進階維護方案 -->
		<?php if ($advanced_plan): ?>
		<div class="wu-section wu-section-highlight">
			<h3 class="wu-section-title">進階維護方案(已啟用)</h3>
			<ul class="wu-feature-list">
				<li>
					<strong>Object Storage 異地資料備援</strong>
					<span class="wu-feature-desc">最多保留 30 份系統備份,僅作系統還原使用</span>
				</li>
				<li>
					<strong>定期網站垃圾清理與資料庫基礎優化</strong>
					<span class="wu-feature-desc">維持網站高效運作</span>
				</li>
				<li>
					<strong>主機與網站狀態定期檢視</strong>
					<span class="wu-feature-desc">屬內部維運作業,未另行提供書面檢測報告</span>
				</li>
				<li>
					<strong>定期更新、漏洞修補</strong>
					<span class="wu-feature-desc">確保系統安全性</span>
				</li>
				<li>
					<strong>網站問題諮詢與技術回覆</strong>
					<span class="wu-feature-desc">於工作日 24 小時內回覆</span>
				</li>
				<li>
					<strong>提供所需模組授權金鑰並協助定期更新</strong>
					<span class="wu-feature-desc">保持功能最新狀態</span>
				</li>
			</ul>
		</div>
		<?php else: ?>
		<div class="wu-section wu-section-promo">
			<h3 class="wu-section-title">升級進階維護方案</h3>
			<div class="wu-promo-box">
				<div class="wu-promo-header">
					<div class="wu-promo-title">進階維護方案</div>
					<div class="wu-promo-price">NT$ 8,000 <span>/年(未稅)</span></div>
				</div>
				<ul class="wu-promo-list">
					<li>Object Storage 異地資料備援(保留 30 份備份)</li>
					<li>定期網站垃圾清理與資料庫基礎優化</li>
					<li>主機與網站狀態定期檢視</li>
					<li>定期更新、漏洞修補</li>
					<li>網站問題諮詢與技術回覆(工作日 24 小時內)</li>
					<li>提供所需模組授權金鑰並協助定期更新</li>
				</ul>
				<p class="wu-promo-note">升級進階維護方案,享受更完整的技術支援與資料安全保障</p>
				<button type="button" class="wu-button" onclick="wuRequestAdvancedPlan()">立即諮詢升級</button>
			</div>
		</div>
		<?php endif; ?>
		
		<!-- 推薦回饋專區(全新設計) -->
		<div class="wu-section wu-section-referral">
			<h3 class="wu-section-title">推薦回饋專區</h3>
			
			<div class="wu-referral-hero">
				<div class="wu-referral-title">成功推薦新客戶</div>
				<div class="wu-referral-subtitle">被推薦人每續約一年主機</div>
				<div class="wu-referral-benefit">即可為推薦者累積 <strong>1 個月</strong>免費主機使用權</div>
				<div class="wu-referral-promise">只要被推薦人持續續約,回饋會持續累積</div>
			</div>
			
			<div class="wu-referral-features">
				<div class="wu-referral-feature">
					<div class="wu-feature-icon">✓</div>
					<div class="wu-feature-text">沒有上限</div>
				</div>
				<div class="wu-referral-feature">
					<div class="wu-feature-icon">✓</div>
					<div class="wu-feature-text">不會過期</div>
				</div>
				<div class="wu-referral-feature">
					<div class="wu-feature-icon">✓</div>
					<div class="wu-feature-text">不用自己申請</div>
				</div>
			</div>
			
			<div class="wu-referral-howto">
				<div class="wu-howto-title">怎麼推薦</div>
				<p>當您的朋友與我們聯繫時,請直接告知是由您推薦<br>我們確認後將自動為您累積回饋</p>
			</div>
			
			<div class="wu-referral-trust">
				不追蹤、不發連結、不自動歸因<br>
				用「人工確認 + 系統累積結果」建立信任
			</div>
			
			<!-- 推薦紀錄(改為上下排列) -->
			<?php if (!empty($referrals)): ?>
			<div class="wu-referral-records">
				<h4 class="wu-records-title">您的推薦紀錄</h4>
				<div class="wu-referral-cards">
					<?php foreach ($referrals as $referral): ?>
					<div class="wu-referral-card">
						<div class="wu-referral-card-header">
							<div class="wu-referral-name"><?php echo esc_html($referral['name']); ?></div>
							<div class="wu-referral-badge <?php echo $referral['rewarded'] ? 'wu-badge-success' : 'wu-badge-pending'; ?>">
								<?php echo
