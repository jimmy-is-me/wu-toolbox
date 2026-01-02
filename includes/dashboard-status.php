<?php
if (!defined('ABSPATH')) exit;

/*
 * WumetaxToolkit - Client Trust Dashboard
 * Version: 4.0 - Professional Read-Only Dashboard
 * 
 * FEATURES:
 * - Read-only client view (no external requests)
 * - Service transparency & trust building
 * - SSL status, disk space, hosting info
 * - Payment records (admin only)
 * - Zero performance impact
 */

// ===== Menu Registration =====

add_action('admin_menu', function() {
	add_menu_page(
		'網站儀表板',
		'網站儀表板',
		'read',
		'wu-client-dashboard',
		'wu_render_client_dashboard',
		'dashicons-dashboard',
		2
	);
	
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
		array('name' => '主機與系統維運', 'enabled' => true),
		array('name' => '定期備份 (每日備份，預設僅保留3天)', 'enabled' => true),
		array('name' => '基礎資安防護', 'enabled' => true),
		array('name' => '系統更新管理', 'enabled' => true),
	));
	add_option('wu_dashboard_hosting_plan', 'image');
	add_option('wu_dashboard_hosting_rating', '優良運作');
	add_option('wu_dashboard_disk_total', '5120');
	add_option('wu_dashboard_payments', array());
	add_option('wu_dashboard_manager_name', 'WUMETAX 末特數位科技');
	add_option('wu_dashboard_manager_contact', "聯絡表單: https://wumetax.com/contact-us/\nLINE: https://line.me/R/ti/p/@081pjqol");
});

// ===== Main Dashboard Page =====

function wu_render_client_dashboard() {
	if (!get_option('wu_dashboard_enabled', 1)) {
		echo '<div class="wrap"><h1>儀表板未啟用</h1></div>';
		return;
	}
	
	$status = get_option('wu_dashboard_site_status', 'normal');
	$status_note = get_option('wu_dashboard_status_note', '');
	$recent_work = get_option('wu_dashboard_recent_work', array());
	$services = get_option('wu_dashboard_services', array());
	$hosting_plan = get_option('wu_dashboard_hosting_plan', 'image');
	$hosting_rating = get_option('wu_dashboard_hosting_rating', '優良運作');
	$disk_total = get_option('wu_dashboard_disk_total', '5120');
	$payments = get_option('wu_dashboard_payments', array());
	$manager_name = get_option('wu_dashboard_manager_name', '');
	$manager_contact = get_option('wu_dashboard_manager_contact', '');
	
	$php_version = PHP_VERSION;
	$ssl_valid = wu_check_ssl_status();
	$disk_used = wu_get_disk_usage();
	$disk_percentage = ($disk_used / $disk_total) * 100;
	
	$plan_names = array(
		'onepage' => '一頁式主機方案',
		'image' => '形象網站主機方案',
		'ecommerce' => '電商主機方案'
	);
	$plan_name = $plan_names[$hosting_plan] ?? '標準主機方案';
	
	$status_config = array(
		'normal' => array('label' => '正常運作中', 'color' => '#10b981', 'icon' => '✓', 'bg' => '#ecfdf5'),
		'watching' => array('label' => '觀察中', 'color' => '#f59e0b', 'icon' => '👁', 'bg' => '#fef3c7'),
		'handling' => array('label' => '處理中', 'color' => '#3b82f6', 'icon' => '🔧', 'bg' => '#dbeafe')
	);
	$current_status = $status_config[$status] ?? $status_config['normal'];
	
	wu_dashboard_styles();
	
	?>
	<div class="wu-dash">
		
		<!-- 頁首 -->
		<div class="wu-header">
			<div class="wu-header-title">網站管理儀表板</div>
			<div class="wu-header-sub">即時狀態與服務透明化</div>
		</div>
		
		<!-- 狀態總覽 -->
		<div class="wu-status-hero" style="background:<?php echo $current_status['bg']; ?>;border-left:4px solid <?php echo $current_status['color']; ?>;">
			<div class="wu-status-main">
				<div class="wu-status-icon" style="color:<?php echo $current_status['color']; ?>;">
					<?php echo $current_status['icon']; ?>
				</div>
				<div>
					<div class="wu-status-label" style="color:<?php echo $current_status['color']; ?>;">
						<?php echo esc_html($current_status['label']); ?>
					</div>
					<div class="wu-status-desc">網站整體狀態</div>
				</div>
			</div>
			
			<div class="wu-status-info">
				<!-- SSL -->
				<div class="wu-info-item">
					<div class="wu-info-icon" style="color:<?php echo $ssl_valid ? '#10b981' : '#f59e0b'; ?>;">🔒</div>
					<div>
						<div class="wu-info-label">SSL 憑證</div>
						<div class="wu-info-value" style="color:<?php echo $ssl_valid ? '#10b981' : '#f59e0b'; ?>;">
							<?php echo $ssl_valid ? '正常運作中' : '未偵測到'; ?>
						</div>
					</div>
				</div>
				
				<!-- 磁碟空間 -->
				<div class="wu-info-item">
					<div class="wu-info-icon" style="color:<?php echo $disk_percentage > 80 ? '#f59e0b' : '#10b981'; ?>;">💾</div>
					<div>
						<div class="wu-info-label">磁碟空間</div>
						<div class="wu-info-value">
							<?php echo number_format($disk_used / 1024, 2); ?> / <?php echo number_format($disk_total / 1024, 2); ?> GB
						</div>
						<div class="wu-progress">
							<div class="wu-progress-bar" style="width:<?php echo min($disk_percentage, 100); ?>%;background:<?php echo $disk_percentage > 80 ? '#f59e0b' : '#10b981'; ?>;"></div>
						</div>
					</div>
				</div>
				
				<!-- PHP 版本 -->
				<div class="wu-info-item">
					<div class="wu-info-icon" style="color:#3b82f6;">🔧</div>
					<div>
						<div class="wu-info-label">PHP 版本</div>
						<div class="wu-info-value"><?php echo esc_html($php_version); ?></div>
					</div>
				</div>
			</div>
			
			<?php if (!empty($status_note)): ?>
			<div class="wu-status-note">
				<?php echo nl2br(esc_html($status_note)); ?>
			</div>
			<?php endif; ?>
		</div>
		
		<!-- 主機規格 -->
		<div class="wu-card">
			<div class="wu-card-header">
				<span class="wu-card-icon">🖥️</span>
				<span class="wu-card-title">主機規格</span>
				<span class="wu-card-badge"><?php echo esc_html($plan_name); ?></span>
			</div>
			<div class="wu-card-body">
				<div class="wu-specs">
					<div class="wu-spec">
						<span class="wu-spec-label">方案類型</span>
						<span class="wu-spec-value"><?php echo esc_html($plan_name); ?></span>
					</div>
					<div class="wu-spec">
						<span class="wu-spec-label">PHP 版本</span>
						<span class="wu-spec-value"><?php echo esc_html($php_version); ?></span>
					</div>
					<div class="wu-spec">
						<span class="wu-spec-label">評估狀態</span>
						<span class="wu-spec-badge"><?php echo esc_html($hosting_rating); ?></span>
					</div>
				</div>
			</div>
		</div>
		
		<!-- 目前包含的服務 -->
		<div class="wu-card">
			<div class="wu-card-header">
				<span class="wu-card-icon">📋</span>
				<span class="wu-card-title">目前包含的維運服務項目</span>
			</div>
			<div class="wu-card-body">
				<div class="wu-services">
					<?php 
					$enabled_services = array_filter($services, function($s) { return !empty($s['enabled']); });
					if (empty($enabled_services)): 
					?>
						<div class="wu-empty">尚未設定服務項目</div>
					<?php else: ?>
						<?php foreach ($enabled_services as $service): ?>
						<div class="wu-service">
							<span class="wu-service-check">✔</span>
							<span class="wu-service-name"><?php echo esc_html($service['name']); ?></span>
						</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
			</div>
		</div>
		
		<!-- 最近處理紀錄 -->
		<?php if (!empty($recent_work)): ?>
		<div class="wu-card">
			<div class="wu-card-header">
				<span class="wu-card-icon">🔄</span>
				<span class="wu-card-title">最近處理紀錄</span>
			</div>
			<div class="wu-card-body">
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
		</div>
		<?php endif; ?>
		
		<!-- 款項紀錄 (僅管理員) -->
		<?php if (!empty($payments) && current_user_can('manage_options')): ?>
		<div class="wu-card">
			<div class="wu-card-header">
				<span class="wu-card-icon">💰</span>
				<span class="wu-card-title">款項收費紀錄</span>
			</div>
			<div class="wu-card-body">
				<div class="wu-table-wrap">
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
		</div>
		<?php endif; ?>
		
		<!-- 聯絡資訊 -->
		<div class="wu-contact">
			<div class="wu-contact-title"><?php echo esc_html($manager_name); ?></div>
			<div class="wu-contact-sub">網站維運管理單位</div>
			<?php if (!empty($manager_contact)): ?>
			<div class="wu-contact-info"><?php echo nl2br(esc_html($manager_contact)); ?></div>
			<?php endif; ?>
			<div class="wu-contact-footer">有任何問題歡迎隨時與我們聯絡</div>
		</div>
		
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
		if (!empty($_POST['service_names'])) {
			foreach ($_POST['service_names'] as $i => $name) {
				if (!empty($name)) {
					$services[] = array(
						'name' => sanitize_text_field($name),
						'enabled' => isset($_POST['service_enabled'][$i])
					);
				}
			}
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
		<h1>⚙️ 儀表板設定</h1>
		
		<div class="notice notice-info" style="padding:15px;">
			<p style="margin:0;"><strong>💡 功能說明</strong></p>
			<ul style="margin:8px 0 0 20px;line-height:1.8;">
				<li>此頁面僅管理員可見</li>
				<li>儀表板完全 read-only，無外部請求</li>
				<li>磁碟空間每 12 小時掃描一次</li>
			</ul>
		</div>
		
		<form method="post" style="background:#fff;padding:25px;border:1px solid #ddd;border-radius:5px;margin-top:20px;">
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
							<option value="normal" <?php selected($status, 'normal'); ?>>✓ 正常運作中</option>
							<option value="watching" <?php selected($status, 'watching'); ?>>👁 觀察中</option>
							<option value="handling" <?php selected($status, 'handling'); ?>>🔧 處理中</option>
						</select>
						<br>
						<textarea name="status_note" rows="2" class="large-text" style="margin-top:10px;" placeholder="狀態說明"><?php echo esc_textarea($status_note); ?></textarea>
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
							<div style="background:#f9f9f9;padding:15px;margin-bottom:10px;border-radius:4px;">
								<input type="text" name="work_titles[]" value="<?php echo esc_attr($work['title']); ?>" placeholder="處理項目" class="regular-text" style="margin-bottom:8px;">
								<input type="date" name="work_dates[]" value="<?php echo esc_attr($work['date']); ?>" style="margin-bottom:8px;">
								<textarea name="work_notes[]" rows="2" class="large-text" placeholder="說明"><?php echo esc_textarea($work['note']); ?></textarea>
							</div>
							<?php endforeach; ?>
						</div>
						<button type="button" class="button" onclick="addWork()">新增</button>
					</td>
				</tr>
				
				<tr>
					<th><label>服務項目</label></th>
					<td>
						<div id="service-container">
							<?php 
							if (empty($services)) {
								$services = array(array('name' => '', 'enabled' => true));
							}
							foreach ($services as $i => $service): 
							?>
							<div style="background:#f9f9f9;padding:15px;margin-bottom:10px;border-radius:4px;">
								<label style="display:block;margin-bottom:8px;">
									<input type="checkbox" name="service_enabled[<?php echo $i; ?>]" <?php checked(!empty($service['enabled'])); ?>>
									<strong>啟用</strong>
								</label>
								<input type="text" name="service_names[]" value="<?php echo esc_attr($service['name']); ?>" placeholder="服務名稱" class="large-text">
							</div>
							<?php endforeach; ?>
						</div>
						<button type="button" class="button" onclick="addService()">新增</button>
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
						<input type="text" name="hosting_rating" value="<?php echo esc_attr($hosting_rating); ?>" placeholder="評估" class="regular-text" style="margin-top:8px;">
						<br>
						<input type="number" name="disk_total" value="<?php echo esc_attr($disk_total); ?>" placeholder="磁碟總量 (MB)" class="regular-text" style="margin-top:8px;">
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
						<button type="button" class="button" onclick="addPayment()">新增</button>
					</td>
				</tr>
				
				<tr>
					<th><label>管理單位</label></th>
					<td>
						<input type="text" name="manager_name" value="<?php echo esc_attr($manager_name); ?>" placeholder="單位名稱" class="regular-text">
						<br>
						<textarea name="manager_contact" rows="3" class="large-text" style="margin-top:10px;" placeholder="聯絡方式"><?php echo esc_textarea($manager_contact); ?></textarea>
					</td>
				</tr>
				
			</table>
			
			<?php submit_button('儲存設定', 'primary large', 'wu_save'); ?>
		</form>
	</div>
	
	<script>
	function addWork() {
		document.getElementById('work-container').insertAdjacentHTML('beforeend',
			'<div style="background:#f9f9f9;padding:15px;margin-bottom:10px;border-radius:4px;">' +
			'<input type="text" name="work_titles[]" placeholder="處理項目" class="regular-text" style="margin-bottom:8px;">' +
			'<input type="date" name="work_dates[]" style="margin-bottom:8px;">' +
			'<textarea name="work_notes[]" rows="2" class="large-text" placeholder="說明"></textarea>' +
			'</div>'
		);
	}
	
	function addService() {
		var i = document.querySelectorAll('#service-container > div').length;
		document.getElementById('service-container').insertAdjacentHTML('beforeend',
			'<div style="background:#f9f9f9;padding:15px;margin-bottom:10px;border-radius:4px;">' +
			'<label style="display:block;margin-bottom:8px;">' +
			'<input type="checkbox" name="service_enabled[' + i + ']" checked><strong>啟用</strong>' +
			'</label>' +
			'<input type="text" name="service_names[]" placeholder="服務名稱" class="large-text">' +
			'</div>'
		);
	}
	
	function addPayment() {
		document.getElementById('payment-container').insertAdjacentHTML('beforeend',
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
	.wu-dash {
		max-width: 1200px;
		margin: 20px auto;
		padding: 0 20px;
		font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
	}
	
	.wu-header {
		margin-bottom: 30px;
	}
	
	.wu-header-title {
		font-size: 32px;
		font-weight: 700;
		color: #111827;
		margin-bottom: 8px;
	}
	
	.wu-header-sub {
		font-size: 15px;
		color: #6b7280;
	}
	
	.wu-status-hero {
		background: #fff;
		border-radius: 12px;
		padding: 30px;
		margin-bottom: 24px;
		border: 1px solid #e5e7eb;
	}
	
	.wu-status-main {
		display: flex;
		align-items: center;
		gap: 20px;
		margin-bottom: 24px;
	}
	
	.wu-status-icon {
		font-size: 64px;
		line-height: 1;
	}
	
	.wu-status-label {
		font-size: 32px;
		font-weight: 700;
		margin-bottom: 4px;
	}
	
	.wu-status-desc {
		font-size: 14px;
		color: #6b7280;
	}
	
	.wu-status-info {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
		gap: 20px;
		margin-bottom: 20px;
	}
	
	.wu-info-item {
		display: flex;
		align-items: flex-start;
		gap: 12px;
	}
	
	.wu-info-icon {
		font-size: 28px;
	}
	
	.wu-info-label {
		font-size: 13px;
		color: #6b7280;
		margin-bottom: 4px;
	}
	
	.wu-info-value {
		font-size: 16px;
		font-weight: 600;
		color: #111827;
	}
	
	.wu-progress {
		width: 100%;
		height: 6px;
		background: #e5e7eb;
		border-radius: 3px;
		margin-top: 8px;
		overflow: hidden;
	}
	
	.wu-progress-bar {
		height: 100%;
		border-radius: 3px;
		transition: width 0.3s;
	}
	
	.wu-status-note {
		padding: 16px;
		background: rgba(255, 255, 255, 0.8);
		border-radius: 8px;
		color: #374151;
		font-size: 14px;
		line-height: 1.6;
	}
	
	.wu-card {
		background: #fff;
		border: 1px solid #e5e7eb;
		border-radius: 12px;
		margin-bottom: 24px;
		overflow: hidden;
	}
	
	.wu-card-header {
		padding: 20px 24px;
		border-bottom: 1px solid #e5e7eb;
		display: flex;
		align-items: center;
		gap: 10px;
	}
	
	.wu-card-icon {
		font-size: 24px;
	}
	
	.wu-card-title {
		font-size: 18px;
		font-weight: 600;
		color: #111827;
		flex: 1;
	}
	
	.wu-card-badge {
		font-size: 12px;
		padding: 4px 12px;
		background: #3b82f6;
		color: #fff;
		border-radius: 20px;
		font-weight: 500;
	}
	
	.wu-card-body {
		padding: 24px;
	}
	
	.wu-specs {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
		gap: 20px;
	}
	
	.wu-spec {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 16px;
		background: #f9fafb;
		border-radius: 8px;
	}
	
	.wu-spec-label {
		font-size: 14px;
		color: #6b7280;
	}
	
	.wu-spec-value {
		font-size: 16px;
		font-weight: 600;
		color: #111827;
	}
	
	.wu-spec-badge {
		font-size: 13px;
		padding: 4px 12px;
		background: #d1fae5;
		color: #065f46;
		border-radius: 20px;
		font-weight: 600;
	}
	
	.wu-services {
		display: grid;
		gap: 12px;
	}
	
	.wu-service {
		display: flex;
		align-items: center;
		gap: 12px;
		padding: 16px;
		background: #f9fafb;
		border-radius: 8px;
	}
	
	.wu-service-check {
		color: #10b981;
		font-size: 20px;
		font-weight: 700;
	}
	
	.wu-service-name {
		font-size: 15px;
		color: #111827;
	}
	
	.wu-timeline {
		display: grid;
		gap: 20px;
	}
	
	.wu-timeline-item {
		display: grid;
		grid-template-columns: 100px 1fr;
		gap: 20px;
		padding-bottom: 20px;
		border-bottom: 1px solid #e5e7eb;
	}
	
	.wu-timeline-item:last-child {
		border-bottom: none;
		padding-bottom: 0;
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
		margin-bottom: 6px;
	}
	
	.wu-timeline-note {
		font-size: 14px;
		color: #6b7280;
		line-height: 1.6;
	}
	
	.wu-table-wrap {
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
	
	.wu-contact {
		background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
		border-radius: 12px;
		padding: 40px;
		text-align: center;
		color: #fff;
	}
	
	.wu-contact-title {
		font-size: 28px;
		font-weight: 700;
		margin-bottom: 8px;
	}
	
	.wu-contact-sub {
		font-size: 15px;
		color: rgba(255, 255, 255, 0.9);
		margin-bottom: 20px;
	}
	
	.wu-contact-info {
		font-size: 15px;
		color: rgba(255, 255, 255, 0.95);
		line-height: 1.8;
		margin-bottom: 20px;
	}
	
	.wu-contact-footer {
		font-size: 14px;
		color: rgba(255, 255, 255, 0.85);
	}
	
	.wu-empty {
		text-align: center;
		padding: 40px;
		color: #9ca3af;
		font-size: 14px;
	}
	
	@media (max-width: 768px) {
		.wu-status-main {
			flex-direction: column;
			text-align: center;
		}
		
		.wu-status-info {
			grid-template-columns: 1fr;
		}
		
		.wu-timeline-item {
			grid-template-columns: 80px 1fr;
		}
	}
	</style>
	<?php
}
