<?php
if (!defined('ABSPATH')) exit;

/*
 * WumetaxToolkit - Premium Client Dashboard
 * Version: 3.0 - Client Satisfaction Dashboard
 * 
 * PURPOSE:
 * - Make clients happy with positive information
 * - Show hosting specs, service records, payment history
 * - Full-screen dashboard or optional widget mode
 * - Zero external HTTP requests (performance optimized)
 */

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
	// 啟用控制
	add_option('wu_dashboard_enabled', 1);
	add_option('wu_dashboard_mode', 'fullpage'); // fullpage / widget
	
	// 網站狀態
	add_option('wu_dashboard_site_status', 'normal');
	add_option('wu_dashboard_status_note', '');
	
	// 最近處理紀錄
	add_option('wu_dashboard_recent_work', array());
	
	// 服務項目 (可自訂細節)
	add_option('wu_dashboard_services', array(
		array('name' => '定期備份', 'detail' => '每日備份,僅保留3天', 'enabled' => true),
		array('name' => '系統安全監控', 'detail' => '24/7 自動監控', 'enabled' => true),
		array('name' => '功能更新維護', 'detail' => '每月檢查更新', 'enabled' => true),
		array('name' => '效能優化', 'detail' => '持續監控優化', 'enabled' => true),
		array('name' => '技術支援', 'detail' => '工作日回應', 'enabled' => true),
	));
	
	// 主機規格
	add_option('wu_dashboard_hosting_plan', 'image'); // onepage / image / ecommerce
	add_option('wu_dashboard_hosting_cpu', '2 Core');
	add_option('wu_dashboard_hosting_ram', '4 GB');
	add_option('wu_dashboard_hosting_rating', '優良');
	
	// 款項紀錄
	add_option('wu_dashboard_payments', array());
});

// ===== Full Page Dashboard =====

function wu_render_client_dashboard_page() {
	if (!get_option('wu_dashboard_enabled', 1)) {
		echo '<div class="wrap"><h1>儀表板未啟用</h1><p>請聯絡管理員啟用此功能。</p></div>';
		return;
	}
	
	$status = get_option('wu_dashboard_site_status', 'normal');
	$status_note = get_option('wu_dashboard_status_note', '');
	$recent_work = get_option('wu_dashboard_recent_work', array());
	$services = get_option('wu_dashboard_services', array());
	$hosting_plan = get_option('wu_dashboard_hosting_plan', 'image');
	$hosting_cpu = get_option('wu_dashboard_hosting_cpu', '2 Core');
	$hosting_ram = get_option('wu_dashboard_hosting_ram', '4 GB');
	$hosting_rating = get_option('wu_dashboard_hosting_rating', '優良');
	$payments = get_option('wu_dashboard_payments', array());
	
	// PHP 版本
	$php_version = PHP_VERSION;
	
	// 主機方案名稱
	$plan_names = array(
		'onepage' => '一頁式主機方案',
		'image' => '形象網站主機方案',
		'ecommerce' => '電商主機方案'
	);
	$plan_name = $plan_names[$hosting_plan] ?? '標準主機方案';
	
	// 狀態配置
	$status_config = array(
		'normal' => array('label' => '正常運作中', 'color' => '#46b450', 'icon' => '✓', 'bg' => '#f0fff4'),
		'watching' => array('label' => '觀察中', 'color' => '#ffb900', 'icon' => '👁', 'bg' => '#fff9e6'),
		'handling' => array('label' => '處理中', 'color' => '#00a0d2', 'icon' => '🔧', 'bg' => '#f0f7ff')
	);
	$current_status = $status_config[$status] ?? $status_config['normal'];
	
	?>
	<div class="wrap" style="max-width:1400px;margin:20px auto;">
		<h1 style="font-size:32px;margin-bottom:30px;">📊 網站管理儀表板</h1>
		
		<!-- 狀態總覽 -->
		<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:20px;margin-bottom:30px;">
			
			<!-- 網站狀態卡片 -->
			<div style="background:<?php echo $current_status['bg']; ?>;padding:30px;border-radius:12px;border:2px solid <?php echo $current_status['color']; ?>;box-shadow:0 2px 8px rgba(0,0,0,0.08);">
				<div style="text-align:center;">
					<div style="font-size:72px;line-height:1;margin-bottom:15px;"><?php echo $current_status['icon']; ?></div>
					<h2 style="margin:0 0 8px;color:<?php echo $current_status['color']; ?>;font-size:28px;font-weight:700;">
						<?php echo esc_html($current_status['label']); ?>
					</h2>
					<p style="margin:0;color:#666;font-size:15px;">網站整體狀態</p>
					
					<?php if (!empty($status_note)): ?>
					<div style="margin-top:20px;padding:15px;background:rgba(255,255,255,0.8);border-radius:8px;">
						<p style="margin:0;color:#555;font-size:14px;line-height:1.6;">
							<?php echo nl2br(esc_html($status_note)); ?>
						</p>
					</div>
					<?php endif; ?>
				</div>
			</div>
			
			<!-- 主機規格卡片 -->
			<div style="background:#fff;padding:30px;border-radius:12px;border:1px solid #e0e0e0;box-shadow:0 2px 8px rgba(0,0,0,0.08);">
				<h3 style="margin:0 0 20px;font-size:20px;color:#333;display:flex;align-items:center;">
					<span style="font-size:28px;margin-right:10px;">🖥️</span>
					主機規格
				</h3>
				
				<div style="margin-bottom:15px;padding:12px;background:#f0f7ff;border-radius:6px;">
					<div style="color:#0073aa;font-weight:600;margin-bottom:4px;">方案類型</div>
					<div style="color:#333;font-size:16px;"><?php echo esc_html($plan_name); ?></div>
				</div>
				
				<table style="width:100%;border-collapse:collapse;">
					<tr>
						<td style="padding:10px 0;color:#666;font-size:14px;border-bottom:1px solid #f0f0f0;">處理器</td>
						<td style="padding:10px 0;text-align:right;font-weight:600;color:#333;border-bottom:1px solid #f0f0f0;">
							<?php echo esc_html($hosting_cpu); ?>
						</td>
					</tr>
					<tr>
						<td style="padding:10px 0;color:#666;font-size:14px;border-bottom:1px solid #f0f0f0;">最大記憶體</td>
						<td style="padding:10px 0;text-align:right;font-weight:600;color:#333;border-bottom:1px solid #f0f0f0;">
							<?php echo esc_html($hosting_ram); ?>
						</td>
					</tr>
					<tr>
						<td style="padding:10px 0;color:#666;font-size:14px;border-bottom:1px solid #f0f0f0;">PHP 版本</td>
						<td style="padding:10px 0;text-align:right;font-weight:600;color:#333;border-bottom:1px solid #f0f0f0;">
							<?php echo esc_html($php_version); ?>
						</td>
					</tr>
					<tr>
						<td style="padding:10px 0;color:#666;font-size:14px;">評估等級</td>
						<td style="padding:10px 0;text-align:right;">
							<span style="background:#46b450;color:#fff;padding:4px 12px;border-radius:20px;font-size:13px;font-weight:600;">
								<?php echo esc_html($hosting_rating); ?>
							</span>
						</td>
					</tr>
				</table>
			</div>
			
			<!-- WooCommerce 訂單統計 (如有安裝) -->
			<?php if (class_exists('WooCommerce')): ?>
			<div style="background:#fff;padding:30px;border-radius:12px;border:1px solid #e0e0e0;box-shadow:0 2px 8px rgba(0,0,0,0.08);">
				<h3 style="margin:0 0 20px;font-size:20px;color:#333;display:flex;align-items:center;">
					<span style="font-size:28px;margin-right:10px;">🛒</span>
					訂單統計
				</h3>
				
				<?php
				$today_orders = wu_safe_get_orders_count_today();
				$processing = wu_safe_get_processing_orders_count();
				$month_orders = wu_safe_get_orders_count_month();
				?>
				
				<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px;margin-bottom:15px;">
					<div style="text-align:center;padding:20px;background:#f0f7ff;border-radius:8px;">
						<div style="font-size:36px;font-weight:700;color:#0073aa;margin-bottom:5px;">
							<?php echo number_format($today_orders); ?>
						</div>
						<div style="color:#666;font-size:13px;">今日訂單</div>
					</div>
					
					<div style="text-align:center;padding:20px;background:#fff3cd;border-radius:8px;">
						<div style="font-size:36px;font-weight:700;color:#856404;margin-bottom:5px;">
							<?php echo number_format($processing); ?>
						</div>
						<div style="color:#666;font-size:13px;">處理中</div>
					</div>
				</div>
				
				<div style="text-align:center;padding:15px;background:#f0fff4;border-radius:8px;">
					<div style="font-size:28px;font-weight:700;color:#46b450;margin-bottom:5px;">
						<?php echo number_format($month_orders); ?>
					</div>
					<div style="color:#666;font-size:13px;">本月訂單總數</div>
				</div>
			</div>
			<?php endif; ?>
			
		</div>
		
		<!-- 最近處理紀錄 -->
		<?php if (!empty($recent_work)): ?>
		<div style="background:#fff;padding:30px;border-radius:12px;border:1px solid #e0e0e0;box-shadow:0 2px 8px rgba(0,0,0,0.08);margin-bottom:30px;">
			<h3 style="margin:0 0 20px;font-size:22px;color:#333;display:flex;align-items:center;">
				<span style="font-size:28px;margin-right:10px;">🔄</span>
				最近處理紀錄
			</h3>
			
			<div style="display:grid;gap:15px;">
				<?php 
				usort($recent_work, function($a, $b) {
					return strtotime($b['date']) - strtotime($a['date']);
				});
				
				foreach (array_slice($recent_work, 0, 5) as $work): 
				?>
				<div style="padding:18px;background:#f9f9f9;border-left:4px solid #0073aa;border-radius:6px;">
					<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;">
						<div style="font-weight:600;color:#333;font-size:16px;">
							<?php echo esc_html($work['title']); ?>
						</div>
						<div style="color:#999;font-size:13px;white-space:nowrap;margin-left:15px;">
							<?php echo esc_html(date('Y/m/d', strtotime($work['date']))); ?>
						</div>
					</div>
					<?php if (!empty($work['note'])): ?>
					<div style="color:#666;font-size:14px;line-height:1.6;">
						<?php echo nl2br(esc_html($work['note'])); ?>
					</div>
					<?php endif; ?>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>
		
		<!-- 目前包含的服務 -->
		<div style="background:#fff;padding:30px;border-radius:12px;border:1px solid #e0e0e0;box-shadow:0 2px 8px rgba(0,0,0,0.08);margin-bottom:30px;">
			<h3 style="margin:0 0 20px;font-size:22px;color:#333;display:flex;align-items:center;">
				<span style="font-size:28px;margin-right:10px;">📋</span>
				目前包含的服務
			</h3>
			
			<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:15px;">
				<?php foreach ($services as $service): ?>
					<?php if (!empty($service['enabled'])): ?>
					<div style="padding:20px;background:#f9f9f9;border-radius:8px;border:1px solid #e8e8e8;">
						<div style="display:flex;align-items:flex-start;margin-bottom:8px;">
							<span style="color:#46b450;font-size:24px;margin-right:10px;">✓</span>
							<div style="flex:1;">
								<div style="font-weight:600;color:#333;font-size:16px;margin-bottom:4px;">
									<?php echo esc_html($service['name']); ?>
								</div>
								<div style="color:#666;font-size:14px;line-height:1.5;">
									<?php echo esc_html($service['detail']); ?>
								</div>
							</div>
						</div>
					</div>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		</div>
		
		<!-- 款項收費紀錄 -->
		<?php if (!empty($payments) && current_user_can('manage_options')): ?>
		<div style="background:#fff;padding:30px;border-radius:12px;border:1px solid #e0e0e0;box-shadow:0 2px 8px rgba(0,0,0,0.08);margin-bottom:30px;">
			<h3 style="margin:0 0 20px;font-size:22px;color:#333;display:flex;align-items:center;">
				<span style="font-size:28px;margin-right:10px;">💰</span>
				款項收費紀錄
			</h3>
			
			<table style="width:100%;border-collapse:collapse;">
				<thead>
					<tr style="background:#f5f5f5;">
						<th style="padding:12px;text-align:left;color:#666;font-size:14px;border-bottom:2px solid #ddd;">日期</th>
						<th style="padding:12px;text-align:left;color:#666;font-size:14px;border-bottom:2px solid #ddd;">項目</th>
						<th style="padding:12px;text-align:right;color:#666;font-size:14px;border-bottom:2px solid #ddd;">金額</th>
						<th style="padding:12px;text-align:center;color:#666;font-size:14px;border-bottom:2px solid #ddd;">狀態</th>
					</tr>
				</thead>
				<tbody>
					<?php 
					usort($payments, function($a, $b) {
						return strtotime($b['date']) - strtotime($a['date']);
					});
					
					foreach ($payments as $payment): 
					?>
					<tr style="border-bottom:1px solid #f0f0f0;">
						<td style="padding:12px;color:#666;font-size:14px;">
							<?php echo esc_html(date('Y/m/d', strtotime($payment['date']))); ?>
						</td>
						<td style="padding:12px;color:#333;font-size:14px;">
							<?php echo esc_html($payment['item']); ?>
						</td>
						<td style="padding:12px;text-align:right;color:#333;font-weight:600;font-size:15px;">
							NT$ <?php echo number_format($payment['amount']); ?>
						</td>
						<td style="padding:12px;text-align:center;">
							<?php if ($payment['status'] === 'paid'): ?>
								<span style="background:#46b450;color:#fff;padding:4px 12px;border-radius:20px;font-size:12px;">已付款</span>
							<?php else: ?>
								<span style="background:#ffb900;color:#fff;padding:4px 12px;border-radius:20px;font-size:12px;">待付款</span>
							<?php endif; ?>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php endif; ?>
		
		<!-- 聯絡資訊 -->
		<div style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);padding:40px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.15);text-align:center;color:#fff;">
			<h3 style="margin:0 0 10px;font-size:24px;color:#fff;font-weight:700;">
				WUMETAX 末特數位科技
			</h3>
			<p style="margin:0 0 25px;color:rgba(255,255,255,0.9);font-size:15px;">網站維運管理單位</p>
			
			<div style="display:flex;justify-content:center;gap:20px;flex-wrap:wrap;">
				<a href="https://wumetax.com/contact-us/" target="_blank" style="display:inline-flex;align-items:center;background:rgba(255,255,255,0.2);padding:12px 24px;border-radius:30px;color:#fff;text-decoration:none;font-size:15px;font-weight:600;transition:all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
					<span style="font-size:20px;margin-right:8px;">🌐</span>
					聯絡我們表單
				</a>
				
				<a href="https://line.me/R/ti/p/@081pjqol" target="_blank" style="display:inline-flex;align-items:center;background:rgba(255,255,255,0.2);padding:12px 24px;border-radius:30px;color:#fff;text-decoration:none;font-size:15px;font-weight:600;transition:all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
					<span style="font-size:20px;margin-right:8px;">💬</span>
					LINE 官方帳號
				</a>
			</div>
			
			<p style="margin:25px 0 0;color:rgba(255,255,255,0.85);font-size:14px;">
				有任何問題歡迎隨時與我們聯絡
			</p>
		</div>
	</div>
	<?php
}

// ===== Safe Helper Functions (No External HTTP) =====

function wu_safe_get_orders_count_today() {
	if (!class_exists('WooCommerce')) return 0;
	
	$cache_key = 'wu_orders_today_' . date('Ymd');
	$cached = get_transient($cache_key);
	
	if ($cached !== false) {
		return $cached;
	}
	
	$args = array(
		'limit' => -1,
		'date_created' => '>=' . strtotime('today'),
		'return' => 'ids'
	);
	
	$orders = wc_get_orders($args);
	$count = count($orders);
	
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
	
	$args = array(
		'limit' => -1,
		'date_created' => '>=' . strtotime('first day of this month'),
		'return' => 'ids'
	);
	
	$orders = wc_get_orders($args);
	$count = count($orders);
	
	set_transient($cache_key, $count, HOUR_IN_SECONDS * 12);
	
	return $count;
}

function wu_safe_get_processing_orders_count() {
	if (!class_exists('WooCommerce')) return 0;
	
	if (function_exists('wc_processing_order_count')) {
		return wc_processing_order_count();
	}
	
	global $wpdb;
	return (int) $wpdb->get_var("
		SELECT COUNT(ID) 
		FROM {$wpdb->prefix}posts 
		WHERE post_status = 'wc-processing' 
		AND post_type = 'shop_order'
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
		
		// 儲存處理紀錄
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
		
		// 儲存服務項目
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
		
		// 儲存主機規格
		update_option('wu_dashboard_hosting_plan', sanitize_text_field($_POST['hosting_plan'] ?? 'image'));
		update_option('wu_dashboard_hosting_cpu', sanitize_text_field($_POST['hosting_cpu'] ?? ''));
		update_option('wu_dashboard_hosting_ram', sanitize_text_field($_POST['hosting_ram'] ?? ''));
		update_option('wu_dashboard_hosting_rating', sanitize_text_field($_POST['hosting_rating'] ?? ''));
		
		// 儲存款項紀錄
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
		
		echo '<div class="notice notice-success is-dismissible"><p><strong>✅ 設定已儲存</strong></p></div>';
	}
	
	$enabled = get_option('wu_dashboard_enabled', 1);
	$site_status = get_option('wu_dashboard_site_status', 'normal');
	$status_note = get_option('wu_dashboard_status_note', '');
	$recent_work = get_option('wu_dashboard_recent_work', array());
	$services = get_option('wu_dashboard_services', array());
	$hosting_plan = get_option('wu_dashboard_hosting_plan', 'image');
	$hosting_cpu = get_option('wu_dashboard_hosting_cpu', '2 Core');
	$hosting_ram = get_option('wu_dashboard_hosting_ram', '4 GB');
	$hosting_rating = get_option('wu_dashboard_hosting_rating', '優良');
	$payments = get_option('wu_dashboard_payments', array());
	
	?>
	<div class="wrap">
		<h1>⚙️ 客戶儀表板設定</h1>
		
		<div class="notice notice-info" style="padding:15px;">
			<p style="margin:0;"><strong>💡 功能說明</strong></p>
			<ul style="margin:8px 0 0 20px;line-height:1.8;">
				<li>此頁面僅管理員可見,客戶看不到</li>
				<li>儀表板會在側邊選單顯示「網站儀表板」</li>
				<li>所有數據使用快取,不會影響網站效能</li>
				<li>無任何外部 HTTP 請求</li>
			</ul>
		</div>
		
		<form method="post" style="background:#fff;padding:25px;border:1px solid #ddd;border-radius:5px;margin-top:20px;">
			<?php wp_nonce_field('wu_dashboard_settings'); ?>
			
			<table class="form-table">
				
				<!-- 啟用控制 -->
				<tr>
					<th scope="row">
						<label>啟用儀表板</label>
					</th>
					<td>
						<label>
							<input type="checkbox" name="enabled" value="1" <?php checked(1, $enabled); ?>>
							<strong>啟用客戶儀表板功能</strong>
						</label>
						<p class="description">取消勾選後,客戶將看不到「網站儀表板」選單</p>
					</td>
				</tr>
				
				<!-- 網站狀態 -->
				<tr>
					<th scope="row">
						<label>網站狀態</label>
					</th>
					<td>
						<select name="site_status" style="min-width:200px;">
							<option value="normal" <?php selected($site_status, 'normal'); ?>>✓ 正常運作中</option>
							<option value="watching" <?php selected($site_status, 'watching'); ?>>👁 觀察中</option>
							<option value="handling" <?php selected($site_status, 'handling'); ?>>🔧 處理中</option>
						</select>
						
						<textarea name="status_note" rows="2" class="large-text" style="margin-top:10px;" placeholder="選填:狀態說明"><?php echo esc_textarea($status_note); ?></textarea>
						<p class="description">例如:「系統更新後觀察中」</p>
					</td>
				</tr>
				
				<!-- 最近處理紀錄 -->
				<tr>
					<th scope="row">
						<label>最近處理紀錄</label>
					</th>
					<td>
						<div id="work-records-container">
							<?php 
							if (empty($recent_work)) {
								$recent_work = array(array('title' => '', 'date' => '', 'note' => ''));
							}
							foreach ($recent_work as $index => $work): 
							?>
							<div class="work-record-item" style="background:#f9f9f9;padding:15px;margin-bottom:10px;border-radius:4px;">
								<input type="text" name="work_titles[]" value="<?php echo esc_attr($work['title']); ?>" placeholder="處理項目標題" class="regular-text" style="margin-bottom:8px;">
								<input type="date" name="work_dates[]" value="<?php echo esc_attr($work['date']); ?>" style="margin-bottom:8px;">
								<textarea name="work_notes[]" rows="2" class="large-text" placeholder="選填:處理說明"><?php echo esc_textarea($work['note']); ?></textarea>
							</div>
							<?php endforeach; ?>
						</div>
						<button type="button" class="button" onclick="addWorkRecord()">新增紀錄</button>
						<p class="description">最多顯示最近 5 筆</p>
					</td>
				</tr>
				
				<!-- 服務項目 -->
				<tr>
					<th scope="row">
						<label>服務項目</label>
					</th>
					<td>
						<div id="services-container">
							<?php 
							if (empty($services)) {
								$services = array(array('name' => '', 'detail' => '', 'enabled' => true));
							}
							foreach ($services as $index => $service): 
							?>
							<div class="service-item" style="background:#f9f9f9;padding:15px;margin-bottom:10px;border-radius:4px;">
								<label style="display:block;margin-bottom:8px;">
									<input type="checkbox" name="service_enabled[<?php echo $index; ?>]" value="1" <?php checked(!empty($service['enabled'])); ?>>
									<strong>啟用此服務</strong>
								</label>
								<input type="text" name="service_names[]" value="<?php echo esc_attr($service['name']); ?>" placeholder="服務名稱" class="regular-text" style="margin-bottom:8px;">
								<input type="text" name="service_details[]" value="<?php echo esc_attr($service['detail']); ?>" placeholder="服務細節 (例如:每日備份,僅保留3天)" class="large-text">
							</div>
							<?php endforeach; ?>
						</div>
						<button type="button" class="button" onclick="addService()">新增服務</button>
					</td>
				</tr>
				
				<!-- 主機規格 -->
				<tr>
					<th scope="row">
						<label>主機規格</label>
					</th>
					<td>
						<div style="background:#f9f9f9;padding:15px;border-radius:4px;">
							<label style="display:block;margin-bottom:10px;">
								<strong>主機方案</strong>
								<select name="hosting_plan" style="min-width:200px;margin-left:10px;">
									<option value="onepage" <?php selected($hosting_plan, 'onepage'); ?>>一頁式主機方案</option>
									<option value="image" <?php selected($hosting_plan, 'image'); ?>>形象網站主機方案</option>
									<option value="ecommerce" <?php selected($hosting_plan, 'ecommerce'); ?>>電商主機方案</option>
								</select>
							</label>
							
							<label style="display:block;margin-bottom:10px;">
								<strong>處理器</strong>
								<input type="text" name="hosting_cpu" value="<?php echo esc_attr($hosting_cpu); ?>" placeholder="例如: 2 Core" style="margin-left:10px;">
							</label>
							
							<label style="display:block;margin-bottom:10px;">
								<strong>最大記憶體</strong>
								<input type="text" name="hosting_ram" value="<?php echo esc_attr($hosting_ram); ?>" placeholder="例如: 4 GB" style="margin-left:10px;">
							</label>
							
							<label style="display:block;">
								<strong>評估等級</strong>
								<input type="text" name="hosting_rating" value="<?php echo esc_attr($hosting_rating); ?>" placeholder="例如: 優良" style="margin-left:10px;">
							</label>
						</div>
						<p class="description">PHP 版本會自動偵測</p>
					</td>
				</tr>
				
				<!-- 款項紀錄 -->
				<tr>
					<th scope="row">
						<label>款項收費紀錄</label>
					</th>
					<td>
						<div id="payments-container">
							<?php 
							if (empty($payments)) {
								$payments = array(array('date' => '', 'item' => '', 'amount' => '', 'status' => 'pending'));
							}
							foreach ($payments as $index => $payment): 
							?>
							<div class="payment-item" style="background:#f9f9f9;padding:15px;margin-bottom:10px;border-radius:4px;">
								<div style="display:grid;grid-template-columns:120px 1fr 120px 120px;gap:10px;">
									<input type="date" name="payment_dates[]" value="<?php echo esc_attr($payment['date']); ?>">
									<input type="text" name="payment_items[]" value="<?php echo esc_attr($payment['item']); ?>" placeholder="收費項目">
									<input type="number" name="payment_amounts[]" value="<?php echo esc_attr($payment['amount']); ?>" placeholder="金額">
									<select name="payment_statuses[]">
										<option value="pending" <?php selected($payment['status'], 'pending'); ?>>待付款</option>
										<option value="paid" <?php selected($payment['status'], 'paid'); ?>>已付款</option>
									</select>
								</div>
							</div>
							<?php endforeach; ?>
						</div>
						<button type="button" class="button" onclick="addPayment()">新增款項紀錄</button>
						<p class="description">僅管理員可見此區塊</p>
					</td>
				</tr>
				
			</table>
			
			<?php submit_button('儲存設定', 'primary large', 'wu_dashboard_save'); ?>
		</form>
	</div>
	
	<script>
	function addWorkRecord() {
		var container = document.getElementById('work-records-container');
		var html = '<div class="work-record-item" style="background:#f9f9f9;padding:15px;margin-bottom:10px;border-radius:4px;">' +
			'<input type="text" name="work_titles[]" placeholder="處理項目標題" class="regular-text" style="margin-bottom:8px;">' +
			'<input type="date" name="work_dates[]" style="margin-bottom:8px;">' +
			'<textarea name="work_notes[]" rows="2" class="large-text" placeholder="選填:處理說明"></textarea>' +
			'</div>';
		container.insertAdjacentHTML('beforeend', html);
	}
	
	function addService() {
		var container = document.getElementById('services-container');
		var index = container.querySelectorAll('.service-item').length;
		var html = '<div class="service-item" style="background:#f9f9f9;padding:15px;margin-bottom:10px;border-radius:4px;">' +
			'<label style="display:block;margin-bottom:8px;">' +
			'<input type="checkbox" name="service_enabled[' + index + ']" value="1" checked>' +
			'<strong>啟用此服務</strong>' +
			'</label>' +
			'<input type="text" name="service_names[]" placeholder="服務名稱" class="regular-text" style="margin-bottom:8px;">' +
			'<input type="text" name="service_details[]" placeholder="服務細節" class="large-text">' +
			'</div>';
		container.insertAdjacentHTML('beforeend', html);
	}
	
	function addPayment() {
		var container = document.getElementById('payments-container');
		var html = '<div class="payment-item" style="background:#f9f9f9;padding:15px;margin-bottom:10px;border-radius:4px;">' +
			'<div style="display:grid;grid-template-columns:120px 1fr 120px 120px;gap:10px;">' +
			'<input type="date" name="payment_dates[]">' +
			'<input type="text" name="payment_items[]" placeholder="收費項目">' +
			'<input type="number" name="payment_amounts[]" placeholder="金額">' +
			'<select name="payment_statuses[]">' +
			'<option value="pending">待付款</option>' +
			'<option value="paid">已付款</option>' +
			'</select>' +
			'</div>' +
			'</div>';
		container.insertAdjacentHTML('beforeend', html);
	}
	</script>
	<?php
}
