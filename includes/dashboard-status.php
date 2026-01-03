<?php
if (!defined('ABSPATH')) exit;

/*
 * WumetaxToolkit - Professional Client Dashboard
 * Version: 10.0 - Clean & Professional Design
 * 
 * FEATURES:
 * - Single column layout (WordPress native style)
 * - No emoji, professional text only
 * - Disk quota upgrade notice
 * - Support ticket system (Discord webhook)
 * - Simplified login tracking
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
	$ssl_info = wu_get_ssl_info();
	$php_info = wu_get_php_info();
	$hosting_plan = get_option('wu_dashboard_hosting_plan', 'image');
	$hosting_rating = get_option('wu_dashboard_hosting_rating', '優良運作');
	$disk_info = wu_get_disk_info();
	$login_stats = wu_get_login_stats();
	$services = get_option('wu_dashboard_services', array());
	$recent_work = get_option('wu_dashboard_recent_work', array());
	$payments = get_option('wu_dashboard_payments', array());
	$referrals = get_option('wu_dashboard_referrals', array());
	$advanced_plan = get_option('wu_dashboard_advanced_plan', 0);
	$domain_name = get_option('wu_dashboard_domain_name', parse_url(home_url(), PHP_URL_HOST));
	
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
						<td class="wu-info-meta">DNS 託管：Cloudflare 管理</td>
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
						<td class="wu-info-meta">評估：<?php echo esc_html($hosting_rating); ?></td>
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
			
			<?php if ($disk_info['over_quota']): ?>
			<div class="wu-notice wu-notice-error">
				<strong>磁碟空間已超過配額</strong><br>
				如需增加磁碟配額，請聯繫我們：<br>
				• 增加 5 GB：NT$ 2,000 / 年<br>
				• 增加 10 GB：NT$ 3,500 / 年
			</div>
			<?php endif; ?>
		</div>
		
		<!-- 管理員登入紀錄 -->
		<div class="wu-section">
			<h3 class="wu-section-title">管理員登入紀錄</h3>
			<?php if (!empty($login_stats['recent_logins'])): ?>
			<table class="wu-table">
				<thead>
					<tr>
						<th>管理員</th>
						<th>登入時間</th>
						<th>IP 位址</th>
						<th style="text-align:center;">登入次數</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($login_stats['recent_logins'] as $login): ?>
					<tr>
						<td><strong><?php echo esc_html($login['name']); ?></strong></td>
						<td><?php echo esc_html($login['time']); ?></td>
						<td><code class="wu-code"><?php echo esc_html($login['ip']); ?></code></td>
						<td style="text-align:center;">
							<span class="wu-count-badge"><?php echo esc_html($login['count']); ?></span>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php else: ?>
			<p class="wu-empty-state">尚無登入紀錄</p>
			<?php endif; ?>
		</div>
		
		<!-- 維運服務項目 -->
		<?php if (!empty($services)): ?>
		<div class="wu-section">
			<h3 class="wu-section-title">維運服務項目</h3>
			<ul class="wu-service-list">
				<?php foreach ($services as $service): ?>
				<li><?php echo esc_html($service); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php endif; ?>
		
		<!-- 進階維護方案 -->
		<?php if ($advanced_plan): ?>
		<div class="wu-section wu-section-highlight">
			<h3 class="wu-section-title">進階維護方案（已啟用）</h3>
			<ul class="wu-feature-list">
				<li>
					<strong>Object Storage 異地資料備援</strong>
					<span class="wu-feature-desc">最多保留 30 份系統備份，僅作系統還原使用</span>
				</li>
				<li>
					<strong>定期網站垃圾清理與資料庫基礎優化</strong>
					<span class="wu-feature-desc">維持網站高效運作</span>
				</li>
				<li>
					<strong>主機與網站狀態定期檢視</strong>
					<span class="wu-feature-desc">屬內部維運作業，未另行提供書面檢測報告</span>
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
					<div class="wu-promo-price">NT$ 8,000 <span>/年（未稅）</span></div>
				</div>
				<ul class="wu-promo-list">
					<li>Object Storage 異地資料備援（保留 30 份備份）</li>
					<li>定期網站垃圾清理與資料庫基礎優化</li>
					<li>主機與網站狀態定期檢視</li>
					<li>定期更新、漏洞修補</li>
					<li>網站問題諮詢與技術回覆（工作日 24 小時內）</li>
					<li>提供所需模組授權金鑰並協助定期更新</li>
				</ul>
				<p class="wu-promo-note">升級進階維護方案，享受更完整的技術支援與資料安全保障</p>
				<a href="mailto:contact@wumetax.com?subject=進階維護方案諮詢" class="wu-button">立即諮詢升級</a>
			</div>
		</div>
		<?php endif; ?>
		
		<!-- 推薦回饋專區 -->
		<?php if (!empty($referrals)): ?>
		<div class="wu-section">
			<h3 class="wu-section-title">推薦回饋專區</h3>
			<div class="wu-referral-rules">
				<p><strong>推薦回饋辦法：</strong></p>
				<ul>
					<li>成功推薦新客戶</li>
					<li>被推薦人每續約一年主機</li>
					<li>推薦者即可額外獲得 1 個月主機使用權</li>
					<li>只要被推薦人持續續約，回饋就會持續累積</li>
				</ul>
			</div>
			<table class="wu-table">
				<thead>
					<tr>
						<th>被推薦人</th>
						<th>成功續費時間</th>
						<th style="text-align:center;">獎勵狀態</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($referrals as $referral): ?>
					<tr>
						<td><strong><?php echo esc_html($referral['name']); ?></strong></td>
						<td><?php echo esc_html(date('Y/m/d', strtotime($referral['date']))); ?></td>
						<td style="text-align:center;">
							<?php if ($referral['rewarded']): ?>
								<span class="wu-badge wu-badge-success">已發放</span>
							<?php else: ?>
								<span class="wu-badge wu-badge-pending">處理中</span>
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
		<div class="wu-section">
			<h3 class="wu-section-title">最近處理紀錄</h3>
			<table class="wu-table">
				<thead>
					<tr>
						<th width="100">日期</th>
						<th>處理項目</th>
						<th>說明</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach (array_slice($recent_work, 0, 10) as $work): ?>
					<tr>
						<td><?php echo esc_html(date('Y/m/d', strtotime($work['date']))); ?></td>
						<td><strong><?php echo esc_html($work['title']); ?></strong></td>
						<td><?php echo !empty($work['note']) ? nl2br(esc_html($work['note'])) : '-'; ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php endif; ?>
		
		<!-- 款項紀錄 -->
		<?php if (current_user_can('manage_options') && !empty($payments)): ?>
		<div class="wu-section">
			<h3 class="wu-section-title">款項紀錄</h3>
			<table class="wu-table">
				<thead>
					<tr>
						<th width="100">日期</th>
						<th>項目</th>
						<th style="text-align:right;" width="120">金額</th>
						<th style="text-align:center;" width="80">狀態</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach (array_slice($payments, 0, 10) as $payment): ?>
					<tr>
						<td><?php echo esc_html(date('Y/m/d', strtotime($payment['date']))); ?></td>
						<td><?php echo esc_html($payment['item']); ?></td>
						<td style="text-align:right;"><strong>NT$ <?php echo number_format($payment['amount']); ?></strong></td>
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
		
		<!-- 技術支援工單 -->
		<div class="wu-section">
			<h3 class="wu-section-title">技術支援工單</h3>
			<form id="wu-support-form" class="wu-support-form">
				<?php wp_nonce_field('wu_support_ticket', 'wu_support_nonce'); ?>
				<div class="wu-form-group">
					<label for="wu_support_email">您的 Email <span class="required">*</span></label>
					<input type="email" id="wu_support_email" name="email" class="wu-input" required 
						value="<?php echo esc_attr(wp_get_current_user()->user_email); ?>" placeholder="your@email.com">
				</div>
				<div class="wu-form-group">
					<label for="wu_support_subject">問題類型 <span class="required">*</span></label>
					<select id="wu_support_subject" name="subject" class="wu-select" required>
						<option value="">請選擇問題類型</option>
						<option value="網站異常">網站異常</option>
						<option value="效能問題">效能問題</option>
						<option value="功能諮詢">功能諮詢</option>
						<option value="備份還原">備份還原</option>
						<option value="帳號權限">帳號權限</option>
						<option value="其他問題">其他問題</option>
					</select>
				</div>
				<div class="wu-form-group">
					<label for="wu_support_message">問題描述 <span class="required">*</span></label>
					<textarea id="wu_support_message" name="message" class="wu-textarea" rows="6" required placeholder="請詳細描述您遇到的問題..."></textarea>
				</div>
				<div class="wu-form-actions">
					<button type="submit" class="wu-button wu-button-primary">
						<span class="wu-button-text">提交工單</span>
						<span class="wu-button-loading" style="display:none;">處理中...</span>
					</button>
				</div>
				<div id="wu-support-result"></div>
			</form>
			<div class="wu-support-note">
				<p><strong>注意事項：</strong></p>
				<ul>
					<li>收到工單後，我們將盡快安排處理（工作日 24 小時內回覆）</li>
					<li>若問題超出現有服務範疇，將另行報價</li>
					<li>如長時間無回覆，請聯繫 <a href="https://lin.ee/Lut7wCe" target="_blank">LINE 官方帳號</a></li>
				</ul>
			</div>
		</div>
		
		<!-- 聯絡資訊 -->
		<div class="wu-section wu-contact-section">
			<h3 class="wu-section-title">聯絡資訊</h3>
			<div class="wu-contact-grid">
				<div class="wu-contact-item">
					<div class="wu-contact-label">公司名稱</div>
					<div class="wu-contact-value">WUMETAX 末特數位科技</div>
				</div>
				<div class="wu-contact-item">
					<div class="wu-contact-label">Email</div>
					<div class="wu-contact-value">
						<a href="mailto:contact@wumetax.com">contact@wumetax.com</a>
					</div>
				</div>
				<div class="wu-contact-item">
					<div class="wu-contact-label">LINE 官方帳號</div>
					<div class="wu-contact-value">
						<a href="https://lin.ee/Lut7wCe" target="_blank">https://lin.ee/Lut7wCe</a>
					</div>
				</div>
			</div>
		</div>
		
	</div>
	
	<div class="wu-footer-meta">
		統計資料每 6-12 小時自動更新 | 資料更新不影響後台載入速度
	</div>
	
	<script>
	jQuery(document).ready(function($) {
		$('#wu-support-form').on('submit', function(e) {
			e.preventDefault();
			
			var $form = $(this);
			var $button = $form.find('button[type="submit"]');
			var $buttonText = $button.find('.wu-button-text');
			var $buttonLoading = $button.find('.wu-button-loading');
			var $result = $('#wu-support-result');
			
			$button.prop('disabled', true);
			$buttonText.hide();
			$buttonLoading.show();
			$result.html('');
			
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'wu_submit_support_ticket',
					nonce: $form.find('#wu_support_nonce').val(),
					email: $form.find('[name="email"]').val(),
					subject: $form.find('[name="subject"]').val(),
					message: $form.find('[name="message"]').val(),
					domain: '<?php echo esc_js(home_url()); ?>'
				},
				success: function(response) {
					if (response.success) {
						$result.html('<div class="wu-notice wu-notice-success">' + response.data.message + '</div>');
						$form[0].reset();
					} else {
						$result.html('<div class="wu-notice wu-notice-error">' + response.data.message + '</div>');
					}
				},
				error: function() {
					$result.html('<div class="wu-notice wu-notice-error">提交失敗，請稍後再試或聯繫 LINE 官方帳號</div>');
				},
				complete: function() {
					$button.prop('disabled', false);
					$buttonText.show();
					$buttonLoading.hide();
				}
			});
		});
	});
	</script>
	<?php
}

// ===== Support Ticket Handler =====

add_action('wp_ajax_wu_submit_support_ticket', 'wu_handle_support_ticket');

function wu_handle_support_ticket() {
	check_ajax_referer('wu_support_ticket', 'nonce');
	
	if (!current_user_can('read')) {
		wp_send_json_error(array('message' => '權限不足'));
	}
	
	$email = sanitize_email($_POST['email'] ?? '');
	$subject = sanitize_text_field($_POST['subject'] ?? '');
	$message = sanitize_textarea_field($_POST['message'] ?? '');
	$domain = sanitize_text_field($_POST['domain'] ?? '');
	
	if (empty($email) || empty($subject) || empty($message)) {
		wp_send_json_error(array('message' => '請填寫所有必填欄位'));
	}
	
	// 發送到 Discord
	$webhook_url = 'https://discordapp.com/api/webhooks/1456920175335968858/p6yPCrxqVwTozOEJwIiXkxS8lSe4K4xq1noRLPeYsLXYT8AOqUjllca2rsiClzbamJF2';
	
	$discord_message = array(
		'embeds' => array(
			array(
				'title' => '新的技術支援工單',
				'color' => 3447003,
				'fields' => array(
					array(
						'name' => '網站',
						'value' => $domain,
						'inline' => false
					),
					array(
						'name' => 'Email',
						'value' => $email,
						'inline' => true
					),
					array(
						'name' => '問題類型',
						'value' => $subject,
						'inline' => true
					),
					array(
						'name' => '問題描述',
						'value' => $message,
						'inline' => false
					),
					array(
						'name' => '提交時間',
						'value' => current_time('Y-m-d H:i:s'),
						'inline' => false
					)
				)
			)
		)
	);
	
	$response = wp_remote_post($webhook_url, array(
		'headers' => array('Content-Type' => 'application/json'),
		'body' => json_encode($discord_message),
		'timeout' => 15
	));
	
	if (is_wp_error($response)) {
		wp_send_json_error(array('message' => '提交失敗，請稍後再試或聯繫 LINE 官方帳號'));
	}
	
	wp_send_json_success(array(
		'message' => '工單已成功提交！我們收到後將盡快安排處理。若問題超出處理範疇則另行報價，若長時間無回覆請聯繫 LINE 官方帳號。'
	));
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
			'color' => '#46b450',
			'description' => 'SSL 憑證確保資料傳輸加密，保護用戶隱私與網站信譽，提升 SEO 排名'
		);
	} else {
		$info = array(
			'status' => 'HTTP 未加密',
			'color' => '#dc3232',
			'description' => '建議啟用 SSL 憑證以確保資料安全'
		);
	}
	
	set_transient($cache_key, $info, DAY_IN_SECONDS);
	
	return $info;
}

function wu_get_php_info() {
	$version = PHP_VERSION;
	$major = (int) PHP_MAJOR_VERSION;
	$minor = (int) PHP_MINOR_VERSION;
	
	// PHP 穩定版本判斷
	$stable_versions = array('8.1', '8.2', '8.3');
	$current_version = $major . '.' . $minor;
	
	if (in_array($current_version, $stable_versions)) {
		$badge_text = '穩定版本';
		$badge_class = 'wu-badge-stable';
		$description = '目前使用的 PHP 版本為長期支援的穩定版本';
	} elseif ($major >= 8) {
		$badge_text = '最新版本';
		$badge_class = 'wu-badge-latest';
		$description = '目前使用的是最新版本的 PHP';
	} else {
		$badge_text = '建議升級';
		$badge_class = 'wu-badge-upgrade';
		$description = '建議升級至 PHP 8.1 以上以獲得更好的效能與安全性';
	}
	
	return array(
		'version' => $version,
		'badge_text' => $badge_text,
		'badge_class' => $badge_class,
		'description' => $description
	);
}

function wu_get_disk_info() {
	$cache_key = 'wu_disk_info';
	$cached = get_transient($cache_key);
	
	if ($cached !== false) {
		return $cached;
	}
	
	$quota_mb = get_option('wu_dashboard_disk_quota', 5120);
	$used_mb = wu_calculate_site_size();
	$percentage = ($used_mb / $quota_mb) * 100;
	$remaining_mb = $quota_mb - $used_mb;
	$over_quota = $percentage > 100;
	
	// 狀態判斷
	if ($percentage < 70) {
		$status_text = '磁碟空間充足';
		$status_class = 'wu-disk-status-normal';
		$color = '#46b450';
	} elseif ($percentage < 90) {
		$status_text = '磁碟空間即將達上限，建議清理或升級配額';
		$status_class = 'wu-disk-status-warning';
		$color = '#f0b849';
	} else {
		$status_text = '磁碟空間已接近上限，請盡快處理';
		$status_class = 'wu-disk-status-danger';
		$color = '#dc3232';
	}
	
	$info = array(
		'used_mb' => $used_mb,
		'quota_mb' => $quota_mb,
		'remaining_mb' => $remaining_mb,
		'percentage' => number_format(min($percentage, 100), 1),
		'used_formatted' => number_format($used_mb, 0) . ' MB',
		'quota_formatted' => number_format($quota_mb, 0) . ' MB',
		'remaining_formatted' => number_format(max($remaining_mb, 0), 0) . ' MB',
		'status_text' => $status_text,
		'status_class' => $status_class,
		'color' => $color,
		'over_quota' => $over_quota
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
	
	$recent_logins = array();
	
	foreach ($admins as $admin) {
		$last_login = get_user_meta($admin->ID, 'wu_last_login', true);
		$last_ip = get_user_meta($admin->ID, 'wu_last_ip', true);
		$login_count = get_user_meta($admin->ID, 'wu_login_count', true);
		
		if (!empty($last_login)) {
			$recent_logins[] = array(
				'name' => $admin->display_name ?: $admin->user_login,
				'time' => date('Y-m-d H:i:s', $last_login),
				'ip' => $last_ip ?: '-',
				'count' => $login_count ?: 1,
				'timestamp' => $last_login
			);
		}
	}
	
	usort($recent_logins, function($a, $b) {
		return $b['timestamp'] - $a['timestamp'];
	});
	
	$stats = array(
		'recent_logins' => array_slice($recent_logins, 0, 10)
	);
	
	set_transient($cache_key, $stats, HOUR_IN_SECONDS * 6);
	
	return $stats;
}

// ===== Login Tracking =====

add_action('wp_login', function($user_login, $user) {
	if (user_can($user, 'manage_options')) {
		update_user_meta($user->ID, 'wu_last_login', current_time('timestamp'));
		update_user_meta($user->ID, 'wu_last_ip', $_SERVER['REMOTE_ADDR'] ?? '-');
		
		// 累計登入次數
		$current_count = get_user_meta($user->ID, 'wu_login_count', true);
		update_user_meta($user->ID, 'wu_login_count', intval($current_count) + 1);
		
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
		
		echo '<div class="notice notice-success is-dismissible"><p><strong>設定已儲存</strong></p></div>';
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
		<h1>儀表板設定</h1>
		
		<div class="notice notice-info" style="padding:15px;">
			<p style="margin:0;"><strong>系統說明</strong></p>
			<ul style="margin:8px 0 0 20px;line-height:1.8;">
				<li>儀表板採用 WordPress 原生風格設計，單欄式佈局</li>
				<li>磁碟使用僅計算 WordPress 網站本身，不影響後台載入速度</li>
				<li>所有統計資料使用快取機制，每 6-12 小時自動更新</li>
				<li>技術支援工單會自動發送到 Discord 通知</li>
			</ul>
		</div>
		
		<div style="background:#fff;padding:20px;border:1px solid #ddd;margin-top:20px;border-left:4px solid #0073aa;">
			<h2 style="margin-top:0;">當前磁碟使用狀態</h2>
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
							<option value="normal" <?php selected($status, 'normal'); ?>>正常運作</option>
							<option value="watching" <?php selected($status, 'watching'); ?>>觀察中</option>
							<option value="handling" <?php selected($status, 'handling'); ?>>處理中</option>
						</select>
						<br>
						<textarea name="status_note" rows="2" class="large-text" style="margin-top:10px;" placeholder="狀態說明 (選填)"><?php echo esc_textarea($status_note); ?></textarea>
					</td>
				</tr>
				
				<tr>
					<th><label>網域名稱</label></th>
					<td>
						<input type="text" name="domain_name" value="<?php echo esc_attr($domain_name); ?>" class="regular-text">
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
					</td>
				</tr>
				
				<tr>
					<th><label>進階維護方案</label></th>
					<td>
						<label>
							<input type="checkbox" name="advanced_plan" value="1" <?php checked(1, $advanced_plan); ?>>
							<strong>客戶已訂購進階維護方案</strong>
						</label>
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
							foreach ($services as $service): 
							?>
							<div style="display:flex;gap:10px;margin-bottom:8px;">
								<input type="text" name="services[]" value="<?php echo esc_attr($service); ?>" class="large-text">
								<button type="button" class="button" onclick="this.parentElement.remove()">刪除</button>
							</div>
							<?php endforeach; ?>
						</div>
						<button type="button" class="button" onclick="addService()">新增項目</button>
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
							<div style="background:#f9f9f9;padding:15px;margin-bottom:10px;display:grid;grid-template-columns:200px 150px 100px 80px;gap:10px;">
								<input type="text" name="referral_names[]" value="<?php echo esc_attr($referral['name']); ?>" placeholder="被推薦人姓名">
								<input type="date" name="referral_dates[]" value="<?php echo esc_attr($referral['date']); ?>">
								<label>
									<input type="checkbox" name="referral_rewarded[]" value="1" <?php checked(1, $referral['rewarded']); ?>>
									已發放
								</label>
								<button type="button" class="button" onclick="this.parentElement.remove()">刪除</button>
							</div>
							<?php endforeach; ?>
						</div>
						<button type="button" class="button" onclick="addReferral()">新增推薦</button>
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
					<th><label>款項紀錄</label></th>
					<td>
						<div id="payment-container">
							<?php 
							if (empty($payments)) {
								$payments = array(array('date' => '', 'item' => '', 'amount' => '', 'status' => 'pending'));
							}
							foreach ($payments as $payment): 
							?>
							<div style="background:#f9f9f9;padding:15px;margin-bottom:10px;display:grid;grid-template-columns:120px 1fr 120px 100px 80px;gap:10px;">
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
			'<div style="display:flex;gap:10px;margin-bottom:8px;">' +
			'<input type="text" name="services[]" class="large-text">' +
			'<button type="button" class="button" onclick="this.parentElement.remove()">刪除</button>' +
			'</div>'
		);
	}
	
	function addReferral() {
		document.getElementById('referral-container').insertAdjacentHTML('beforeend',
			'<div style="background:#f9f9f9;padding:15px;margin-bottom:10px;display:grid;grid-template-columns:200px 150px 100px 80px;gap:10px;">' +
			'<input type="text" name="referral_names[]" placeholder="被推薦人姓名">' +
			'<input type="date" name="referral_dates[]">' +
			'<label><input type="checkbox" name="referral_rewarded[]" value="1"> 已發放</label>' +
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
			'<div style="background:#f9f9f9;padding:15px;margin-bottom:10px;display:grid;grid-template-columns:120px 1fr 120px 100px 80px;gap:10px;">' +
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
	/* Widget Container */
	#wu_unified_dashboard {
		width: 100% !important;
		grid-column: 1 / -1 !important;
	}
	
	#wu_unified_dashboard .inside {
		padding: 0 !important;
		margin: 0 !important;
	}
	
	.wu-dashboard-container {
		font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
	}
	
	/* Section */
	.wu-section {
		padding: 20px;
		border-bottom: 1px solid #dcdcde;
	}
	
	.wu-section:last-child {
		border-bottom: none;
	}
	
	.wu-section-title {
		font-size: 14px;
		font-weight: 600;
		color: #1d2327;
		margin: 0 0 15px 0;
		padding-bottom: 10px;
		border-bottom: 1px solid #dcdcde;
	}
	
	/* Status Card */
	.wu-status-card {
		padding: 15px;
		background: #f6f7f7;
		border-left: 4px solid;
		margin-bottom: 15px;
	}
	
	.wu-status-main {
		display: flex;
		align-items: center;
		gap: 15px;
		flex-wrap: wrap;
	}
	
	.wu-status-badge {
		display: inline-block;
		padding: 6px 12px;
		color: #fff;
		font-size: 13px;
		font-weight: 600;
		border-radius: 3px;
	}
	
	.wu-status-note {
		flex: 1;
		font-size: 13px;
		color: #50575e;
		line-height: 1.6;
	}
	
	/* Info Table */
	.wu-info-table {
		width: 100%;
		border-collapse: collapse;
	}
	
	.wu-info-table th,
	.wu-info-table td {
		padding: 12px;
		border-bottom: 1px solid #f0f0f1;
		text-align: left;
	}
	
	.wu-info-table th {
		width: 140px;
		font-size: 13px;
		font-weight: 600;
		color: #50575e;
	}
	
	.wu-info-table td {
		font-size: 13px;
		color: #2c3338;
	}
	
	.wu-info-table tr:last-child th,
	.wu-info-table tr:last-child td {
		border-bottom: none;
	}
	
	.wu-info-meta {
		color: #787c82 !important;
		font-size: 12px !important;
	}
	
	.wu-ssl-status {
		font-weight: 600;
	}
	
	.wu-badge-stable,
	.wu-badge-latest,
	.wu-badge-upgrade {
		display: inline-block;
		padding: 2px 8px;
		margin-left: 8px;
		font-size: 11px;
		font-weight: 600;
		border-radius: 3px;
	}
	
	.wu-badge-stable {
		background: #d7f0dd;
		color: #1d8a3f;
	}
	
	.wu-badge-latest {
		background: #dbe5ff;
		color: #1d4ed8;
	}
	
	.wu-badge-upgrade {
		background: #fcf3cf;
		color: #996800;
	}
	
	/* Disk Grid */
	.wu-disk-grid {
		display: grid;
		grid-template-columns: repeat(4, 1fr);
		gap: 10px;
		margin-bottom: 15px;
	}
	
	.wu-disk-item {
		padding: 15px;
		background: #f6f7f7;
		border: 1px solid #dcdcde;
		text-align: center;
	}
	
	.wu-disk-label {
		font-size: 11px;
		color: #646970;
		text-transform: uppercase;
		margin-bottom: 8px;
	}
	
	.wu-disk-value {
		font-size: 20px;
		font-weight: 600;
		color: #1d2327;
	}
	
	.wu-disk-bar {
		width: 100%;
		height: 8px;
		background: #dcdcde;
		margin-bottom: 15px;
		border-radius: 4px;
		overflow: hidden;
	}
	
	.wu-disk-bar-fill {
		height: 100%;
		transition: width 0.3s;
	}
	
	.wu-disk-status {
		padding: 10px;
		text-align: center;
		font-size: 13px;
		font-weight: 600;
		border-radius: 3px;
	}
	
	.wu-disk-status-normal {
		background: #d7f0dd;
		color: #1d8a3f;
	}
	
	.wu-disk-status-warning {
		background: #fcf3cf;
		color: #996800;
	}
	
	.wu-disk-status-danger {
		background: #fcdbdb;
		color: #b32d2e;
	}
	
	/* Notice */
	.wu-notice {
		padding: 12px 15px;
		margin-top: 15px;
		border-left: 4px solid;
		border-radius: 3px;
		font-size: 13px;
		line-height: 1.6;
	}
	
	.wu-notice-error {
		background: #fcdbdb;
		border-color: #d63638;
		color: #50575e;
	}
	
	.wu-notice-success {
		background: #d7f0dd;
		border-color: #00a32a;
		color: #50575e;
	}
	
	/* Table */
	.wu-table {
		width: 100%;
		border-collapse: collapse;
		border: 1px solid #c3c4c7;
	}
	
	.wu-table thead th {
		padding: 10px 12px;
		background: #f6f7f7;
		border-bottom: 1px solid #c3c4c7;
		font-size: 12px;
		font-weight: 600;
		color: #50575e;
		text-align: left;
	}
	
	.wu-table tbody td {
		padding: 10px 12px;
		border-bottom: 1px solid #dcdcde;
		font-size: 13px;
		color: #2c3338;
	}
	
	.wu-table tbody tr:last-child td {
		border-bottom: none;
	}
	
	.wu-code {
		padding: 2px 6px;
		background: #f6f7f7;
		border: 1px solid #dcdcde;
		border-radius: 3px;
		font-family: "Courier New", Courier, monospace;
		font-size: 12px;
		color: #b32d2e;
	}
	
	.wu-count-badge {
		display: inline-block;
		padding: 2px 8px;
		background: #2271b1;
		color: #fff;
		font-size: 11px;
		font-weight: 600;
		border-radius: 10px;
	}
	
	.wu-badge {
		display: inline-block;
		padding: 3px 8px;
		font-size: 11px;
		font-weight: 600;
		border-radius: 3px;
	}
	
	.wu-badge-success {
		background: #d7f0dd;
		color: #1d8a3f;
	}
	
	.wu-badge-warning {
		background: #fcf3cf;
		color: #996800;
	}
	
	.wu-badge-pending {
		background: #dbe5ff;
		color: #1d4ed8;
	}
	
	.wu-empty-state {
		padding: 40px;
		text-align: center;
		color: #787c82;
		font-size: 13px;
	}
	
	/* Service List */
	.wu-service-list {
		margin: 0;
		padding: 0;
		list-style: none;
	}
	
	.wu-service-list li {
		padding: 10px 0;
		border-bottom: 1px solid #f0f0f1;
		font-size: 13px;
		color: #2c3338;
		line-height: 1.6;
	}
	
	.wu-service-list li:last-child {
		border-bottom: none;
	}
	
	/* Feature List */
	.wu-section-highlight {
		background: #f0f6fc;
	}
	
	.wu-feature-list {
		margin: 0;
		padding: 0;
		list-style: none;
	}
	
	.wu-feature-list li {
		padding: 12px 0;
		border-bottom: 1px solid #dcdcde;
	}
	
	.wu-feature-list li:last-child {
		border-bottom: none;
	}
	
	.wu-feature-list strong {
		display: block;
		font-size: 13px;
		color: #1d2327;
		margin-bottom: 4px;
	}
	
	.wu-feature-desc {
		display: block;
		font-size: 12px;
		color: #646970;
	}
	
	/* Promo */
	.wu-section-promo {
		background: #fffbf0;
	}
	
	.wu-promo-box {
		border: 1px solid #c3c4c7;
		background: #fff;
	}
	
	.wu-promo-header {
		padding: 15px 20px;
		background: #2271b1;
		color: #fff;
		display: flex;
		justify-content: space-between;
		align-items: center;
	}
	
	.wu-promo-title {
		font-size: 16px;
		font-weight: 600;
	}
	
	.wu-promo-price {
		font-size: 20px;
		font-weight: 700;
	}
	
	.wu-promo-price span {
		font-size: 12px;
		font-weight: 400;
		opacity: 0.9;
	}
	
	.wu-promo-list {
		margin: 0;
		padding: 20px;
		list-style: none;
	}
	
	.wu-promo-list li {
		padding: 8px 0;
		font-size: 13px;
		color: #2c3338;
		border-bottom: 1px solid #f0f0f1;
	}
	
	.wu-promo-list li:last-child {
		border-bottom: none;
	}
	
	.wu-promo-note {
		padding: 15px 20px;
		background: #f6f7f7;
		border-top: 1px solid #dcdcde;
		font-size: 13px;
		color: #50575e;
		margin: 0;
	}
	
	.wu-button {
		display: inline-block;
		margin: 15px 20px;
		padding: 8px 20px;
		background: #2271b1;
		color: #fff;
		text-decoration: none;
		border-radius: 3px;
		font-size: 13px;
		font-weight: 600;
		transition: background 0.2s;
	}
	
	.wu-button:hover {
		background: #135e96;
		color: #fff;
	}
	
	/* Referral */
	.wu-referral-rules {
		padding: 15px;
		background: #f6f7f7;
		border-left: 4px solid #2271b1;
		margin-bottom: 15px;
	}
	
	.wu-referral-rules p {
		margin: 0 0 10px 0;
		font-size: 13px;
		font-weight: 600;
		color: #1d2327;
	}
	
	.wu-referral-rules ul {
		margin: 0;
		padding-left: 20px;
	}
	
	.wu-referral-rules li {
		font-size: 13px;
		color: #50575e;
		line-height: 1.6;
	}
	
	/* Support Form */
	.wu-support-form {
		background: #f6f7f7;
		padding: 20px;
		border: 1px solid #dcdcde;
	}
	
	.wu-form-group {
		margin-bottom: 20px;
	}
	
	.wu-form-group label {
		display: block;
		margin-bottom: 8px;
		font-size: 13px;
		font-weight: 600;
		color: #1d2327;
	}
	
	.required {
		color: #d63638;
	}
	
	.wu-input,
	.wu-select,
	.wu-textarea {
		width: 100%;
		padding: 8px 12px;
		border: 1px solid #8c8f94;
		border-radius: 3px;
		font-size: 13px;
		box-shadow: inset 0 1px 2px rgba(0,0,0,0.04);
	}
	
	.wu-input:focus,
	.wu-select:focus,
	.wu-textarea:focus {
		border-color: #2271b1;
		outline: none;
		box-shadow: 0 0 0 1px #2271b1;
	}
	
	.wu-textarea {
		resize: vertical;
	}
	
	.wu-form-actions {
		margin-bottom: 15px;
	}
	
	.wu-button-primary {
		padding: 10px 24px;
		background: #2271b1;
		color: #fff;
		border: none;
		cursor: pointer;
		font-size: 13px;
		font-weight: 600;
		border-radius: 3px;
	}
	
	.wu-button-primary:hover {
		background: #135e96;
	}
	
	.wu-button-primary:disabled {
		background: #8c8f94;
		cursor: not-allowed;
	}
	
	.wu-button-loading {
		display: none;
	}
	
	.wu-support-note {
		margin-top: 15px;
		padding: 15px;
		background: #fff;
		border-left: 4px solid #2271b1;
	}
	
	.wu-support-note p {
		margin: 0 0 10px 0;
		font-size: 13px;
		font-weight: 600;
	}
	
	.wu-support-note ul {
		margin: 0;
		padding-left: 20px;
	}
	
	.wu-support-note li {
		font-size: 13px;
		color: #50575e;
		line-height: 1.6;
	}
	
	/* Contact */
	.wu-contact-section {
		background: #f0f6fc;
	}
	
	.wu-contact-grid {
		display: grid;
		grid-template-columns: repeat(3, 1fr);
		gap: 15px;
	}
	
	.wu-contact-item {
		padding: 15px;
		background: #fff;
		border: 1px solid #dcdcde;
		text-align: center;
	}
	
	.wu-contact-label {
		font-size: 11px;
		color: #646970;
		text-transform: uppercase;
		margin-bottom: 8px;
	}
	
	.wu-contact-value {
		font-size: 14px;
		font-weight: 600;
		color: #1d2327;
	}
	
	.wu-contact-value a {
		color: #2271b1;
		text-decoration: none;
	}
	
	.wu-contact-value a:hover {
		text-decoration: underline;
	}
	
	/* Footer */
	.wu-footer-meta {
		padding: 15px 20px;
		background: #f6f7f7;
		border-top: 1px solid #dcdcde;
		font-size: 12px;
		color: #646970;
		text-align: center;
	}
	
	/* Responsive */
	@media (max-width: 782px) {
		.wu-disk-grid,
		.wu-contact-grid {
			grid-template-columns: 1fr;
		}
		
		.wu-promo-header {
			flex-direction: column;
			gap: 10px;
			text-align: center;
		}
	}
	</style>
	<?php
});
