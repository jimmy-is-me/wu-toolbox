<?php
if (!defined('ABSPATH')) exit;

/*
 * WumetaxToolkit - Dashboard Status Overview
 * Version: 1.0
 * 
 * PURPOSE:
 * - Client-friendly status dashboard (not technical monitoring)
 * - Show maintenance transparency
 * - Build trust with clear communication
 * 
 * PRINCIPLES:
 * - No technical scores/metrics
 * - Only show status + results (not raw data)
 * - Hide blocks when nothing to report
 */

// ===== Menu Registration =====

add_action('admin_menu', function() {
	add_submenu_page(
		'wumetax-toolkit',
		'儀表板顯示清單',
		'儀表板顯示清單',
		'read', // 所有登入者可見
		'wu-dashboard-overview',
		'wu_dashboard_overview_page',
		1 // 置頂
	);
});

// ===== Options Initialization =====

add_action('admin_init', function() {
	// 1. 網站狀態
	add_option('wu_dashboard_site_status', 'normal'); // normal / watching / handling
	add_option('wu_dashboard_site_status_note', '');
	
	// 2. 最近維運紀錄
	add_option('wu_dashboard_last_maintenance_date', '');
	add_option('wu_dashboard_last_maintenance_note', '');
	
	// 3. 管理項目（固定勾選項）
	add_option('wu_dashboard_management_note', '');
	
	// 4. 異常提醒
	add_option('wu_dashboard_alert_enabled', 0);
	add_option('wu_dashboard_alert_message', '');
	
	// 5. 受管制項目
	add_option('wu_dashboard_restricted_items', '系統核心設定、外掛更新管理、主機安全防護');
	add_option('wu_dashboard_contact_note', '如需調整請聯絡管理方');
	
	// 6. 網站基本資訊
	add_option('wu_dashboard_site_note', '');
	
	// 7. 管理單位資訊
	add_option('wu_dashboard_manager_name', '');
	add_option('wu_dashboard_manager_contact', '');
	
	// 8. 服務內容摘要
	add_option('wu_dashboard_service_items', '系統維護、安全監控、功能更新');
	add_option('wu_dashboard_service_note', '');
	
	// 9. 系統提示
	add_option('wu_dashboard_system_message', '');
});

// ===== Dashboard Display Page =====

function wu_dashboard_overview_page() {
	$can_edit = current_user_can('manage_options');
	
	// 儲存設定
	if ($can_edit && isset($_POST['wu_dashboard_submit'])) {
		check_admin_referer('wu_dashboard_settings');
		
		update_option('wu_dashboard_site_status', sanitize_text_field($_POST['site_status'] ?? 'normal'));
		update_option('wu_dashboard_site_status_note', sanitize_textarea_field($_POST['site_status_note'] ?? ''));
		
		update_option('wu_dashboard_last_maintenance_date', sanitize_text_field($_POST['maintenance_date'] ?? ''));
		update_option('wu_dashboard_last_maintenance_note', sanitize_textarea_field($_POST['maintenance_note'] ?? ''));
		
		update_option('wu_dashboard_management_note', sanitize_textarea_field($_POST['management_note'] ?? ''));
		
		update_option('wu_dashboard_alert_enabled', isset($_POST['alert_enabled']) ? 1 : 0);
		update_option('wu_dashboard_alert_message', sanitize_textarea_field($_POST['alert_message'] ?? ''));
		
		update_option('wu_dashboard_restricted_items', sanitize_textarea_field($_POST['restricted_items'] ?? ''));
		update_option('wu_dashboard_contact_note', sanitize_textarea_field($_POST['contact_note'] ?? ''));
		
		update_option('wu_dashboard_site_note', sanitize_textarea_field($_POST['site_note'] ?? ''));
		
		update_option('wu_dashboard_manager_name', sanitize_text_field($_POST['manager_name'] ?? ''));
		update_option('wu_dashboard_manager_contact', sanitize_textarea_field($_POST['manager_contact'] ?? ''));
		
		update_option('wu_dashboard_service_items', sanitize_textarea_field($_POST['service_items'] ?? ''));
		update_option('wu_dashboard_service_note', sanitize_textarea_field($_POST['service_note'] ?? ''));
		
		update_option('wu_dashboard_system_message', sanitize_textarea_field($_POST['system_message'] ?? ''));
		
		echo '<div class="notice notice-success is-dismissible"><p><strong>✅ 設定已儲存</strong></p></div>';
	}
	
	// 載入選項
	$site_status = get_option('wu_dashboard_site_status', 'normal');
	$site_status_note = get_option('wu_dashboard_site_status_note', '');
	
	$maintenance_date = get_option('wu_dashboard_last_maintenance_date', '');
	$maintenance_note = get_option('wu_dashboard_last_maintenance_note', '');
	
	$management_note = get_option('wu_dashboard_management_note', '');
	
	$alert_enabled = get_option('wu_dashboard_alert_enabled', 0);
	$alert_message = get_option('wu_dashboard_alert_message', '');
	
	$restricted_items = get_option('wu_dashboard_restricted_items', '');
	$contact_note = get_option('wu_dashboard_contact_note', '');
	
	$site_note = get_option('wu_dashboard_site_note', '');
	
	$manager_name = get_option('wu_dashboard_manager_name', '');
	$manager_contact = get_option('wu_dashboard_manager_contact', '');
	
	$service_items = get_option('wu_dashboard_service_items', '');
	$service_note = get_option('wu_dashboard_service_note', '');
	
	$system_message = get_option('wu_dashboard_system_message', '');
	
	// 狀態標籤
	$status_labels = array(
		'normal' => array('label' => '正常運作中', 'color' => '#46b450', 'icon' => '✓'),
		'watching' => array('label' => '觀察中', 'color' => '#ffb900', 'icon' => '👁'),
		'handling' => array('label' => '處理中', 'color' => '#00a0d2', 'icon' => '🔧')
	);
	
	$current_status = $status_labels[$site_status] ?? $status_labels['normal'];
	
	?>
	<div class="wrap">
		<h1>📊 儀表板顯示清單</h1>
		
		<?php if ($can_edit): ?>
		<div class="notice notice-info" style="padding:15px;">
			<p style="margin:0;"><strong>💡 設計原則</strong></p>
			<ul style="margin:8px 0 0 20px;line-height:1.8;">
				<li>儀表板是給客戶「安心看」,不是給工程師「監控用」</li>
				<li>只顯示狀態與結果,不顯示技術細節與數字</li>
				<li>沒狀況 = 不顯示區塊,避免製造焦慮</li>
			</ul>
		</div>
		<?php endif; ?>
		
		<!-- 客戶檢視模式 -->
		<div style="background:#fff;padding:30px;border:1px solid #ddd;border-radius:8px;margin-top:20px;">
			
			<!-- 1. 網站整體狀態 -->
			<div style="margin-bottom:40px;">
				<div style="display:flex;align-items:center;margin-bottom:15px;">
					<span style="font-size:32px;margin-right:12px;"><?php echo $current_status['icon']; ?></span>
					<div>
						<h2 style="margin:0;font-size:24px;color:<?php echo $current_status['color']; ?>;">
							<?php echo esc_html($current_status['label']); ?>
						</h2>
						<p style="margin:5px 0 0;color:#666;font-size:14px;">網站整體狀態</p>
					</div>
				</div>
				<?php if (!empty($site_status_note)): ?>
				<div style="background:#f9f9f9;padding:12px 16px;border-left:4px solid <?php echo $current_status['color']; ?>;border-radius:4px;">
					<p style="margin:0;color:#555;"><?php echo nl2br(esc_html($site_status_note)); ?></p>
				</div>
				<?php endif; ?>
			</div>
			
			<!-- 2. 最近維運紀錄 -->
			<?php if (!empty($maintenance_date) || !empty($maintenance_note)): ?>
			<div style="margin-bottom:40px;padding-bottom:30px;border-bottom:1px solid #eee;">
				<h3 style="margin:0 0 15px;font-size:18px;color:#333;">🔄 最近維運紀錄</h3>
				<?php if (!empty($maintenance_date)): ?>
				<p style="margin:0 0 8px;color:#666;">
					<strong>日期:</strong> <?php echo esc_html($maintenance_date); ?>
				</p>
				<?php endif; ?>
				<?php if (!empty($maintenance_note)): ?>
				<p style="margin:0;color:#555;line-height:1.6;">
					<?php echo nl2br(esc_html($maintenance_note)); ?>
				</p>
				<?php endif; ?>
			</div>
			<?php endif; ?>
			
			<!-- 3. 管理項目透明化 -->
			<div style="margin-bottom:40px;padding-bottom:30px;border-bottom:1px solid #eee;">
				<h3 style="margin:0 0 15px;font-size:18px;color:#333;">🛡️ 持續管理中的項目</h3>
				<ul style="margin:0;padding-left:20px;line-height:2;color:#555;">
					<li>✓ 系統更新管理</li>
					<li>✓ 基本資安防護</li>
					<li>✓ 核心功能監控</li>
				</ul>
				<?php if (!empty($management_note)): ?>
				<div style="background:#f0f7ff;padding:12px 16px;margin-top:12px;border-radius:4px;">
					<p style="margin:0;color:#555;font-size:14px;"><?php echo nl2br(esc_html($management_note)); ?></p>
				</div>
				<?php endif; ?>
			</div>
			
			<!-- 4. 異常或提醒事項 -->
			<?php if ($alert_enabled && !empty($alert_message)): ?>
			<div style="margin-bottom:40px;padding-bottom:30px;border-bottom:1px solid #eee;">
				<div style="background:#fff3cd;border:1px solid #ffc107;padding:15px 20px;border-radius:6px;">
					<h3 style="margin:0 0 10px;font-size:18px;color:#856404;">⚠️ 提醒事項</h3>
					<p style="margin:0;color:#856404;line-height:1.6;">
						<?php echo nl2br(esc_html($alert_message)); ?>
					</p>
				</div>
			</div>
			<?php endif; ?>
			
			<!-- 5. 受管制項目 -->
			<?php if (!empty($restricted_items)): ?>
			<div style="margin-bottom:40px;padding-bottom:30px;border-bottom:1px solid #eee;">
				<h3 style="margin:0 0 15px;font-size:18px;color:#333;">🔒 受管制項目</h3>
				<p style="margin:0 0 10px;color:#555;line-height:1.8;">
					<?php echo nl2br(esc_html($restricted_items)); ?>
				</p>
				<?php if (!empty($contact_note)): ?>
				<p style="margin:10px 0 0;color:#666;font-size:14px;font-style:italic;">
					<?php echo esc_html($contact_note); ?>
				</p>
				<?php endif; ?>
			</div>
			<?php endif; ?>
			
			<!-- 6. 網站基本資訊 -->
			<div style="margin-bottom:40px;padding-bottom:30px;border-bottom:1px solid #eee;">
				<h3 style="margin:0 0 15px;font-size:18px;color:#333;">🌐 網站基本資訊</h3>
				<p style="margin:0 0 8px;color:#666;">
					<strong>網址:</strong> <a href="<?php echo esc_url(home_url()); ?>" target="_blank" style="color:#0073aa;"><?php echo esc_html(home_url()); ?></a>
				</p>
				<p style="margin:0 0 8px;color:#666;">
					<strong>上線狀態:</strong> <span style="color:#46b450;">●</span> 運作中
				</p>
				<?php if (!empty($site_note)): ?>
				<p style="margin:10px 0 0;color:#555;font-size:14px;">
					<?php echo nl2br(esc_html($site_note)); ?>
				</p>
				<?php endif; ?>
			</div>
			
			<!-- 7. 管理單位資訊 -->
			<?php if (!empty($manager_name) || !empty($manager_contact)): ?>
			<div style="margin-bottom:40px;padding-bottom:30px;border-bottom:1px solid #eee;">
				<h3 style="margin:0 0 15px;font-size:18px;color:#333;">👤 管理單位資訊</h3>
				<?php if (!empty($manager_name)): ?>
				<p style="margin:0 0 8px;color:#666;">
					<strong>單位名稱:</strong> <?php echo esc_html($manager_name); ?>
				</p>
				<?php endif; ?>
				<?php if (!empty($manager_contact)): ?>
				<div style="background:#f9f9f9;padding:12px 16px;border-radius:4px;margin-top:10px;">
					<p style="margin:0;color:#555;font-size:14px;white-space:pre-line;"><?php echo esc_html($manager_contact); ?></p>
				</div>
				<?php endif; ?>
			</div>
			<?php endif; ?>
			
			<!-- 8. 服務內容摘要 -->
			<?php if (!empty($service_items)): ?>
			<div style="margin-bottom:40px;padding-bottom:30px;border-bottom:1px solid #eee;">
				<h3 style="margin:0 0 15px;font-size:18px;color:#333;">📋 服務內容摘要</h3>
				<p style="margin:0 0 10px;color:#555;line-height:1.8;">
					<?php echo nl2br(esc_html($service_items)); ?>
				</p>
				<?php if (!empty($service_note)): ?>
				<p style="margin:10px 0 0;color:#666;font-size:13px;font-style:italic;">
					<?php echo esc_html($service_note); ?>
				</p>
				<?php endif; ?>
			</div>
			<?php endif; ?>
			
			<!-- 9. 系統提示區 -->
			<?php if (!empty($system_message)): ?>
			<div style="background:#f0f7ff;padding:15px 20px;border-left:4px solid #0073aa;border-radius:4px;">
				<p style="margin:0;color:#555;line-height:1.6;">
					💬 <?php echo nl2br(esc_html($system_message)); ?>
				</p>
			</div>
			<?php endif; ?>
			
		</div>
		
		<!-- 管理員編輯區 -->
		<?php if ($can_edit): ?>
		<div style="margin-top:40px;padding-top:30px;border-top:2px solid #ddd;">
			<h2>⚙️ 管理員設定區</h2>
			<p style="color:#666;margin-bottom:20px;">以下設定僅管理員可見,客戶看不到此區域</p>
			
			<form method="post" style="background:#fff;padding:25px;border:1px solid #ddd;border-radius:5px;">
				<?php wp_nonce_field('wu_dashboard_settings'); ?>
				
				<table class="form-table">
					<!-- 1. 網站狀態 -->
					<tr>
						<th scope="row">
							<label>網站整體狀態</label>
						</th>
						<td>
							<select name="site_status" style="min-width:200px;">
								<option value="normal" <?php selected($site_status, 'normal'); ?>>✓ 正常運作中</option>
								<option value="watching" <?php selected($site_status, 'watching'); ?>>👁 觀察中</option>
								<option value="handling" <?php selected($site_status, 'handling'); ?>>🔧 處理中</option>
							</select>
							<p class="description">選擇目前網站運作狀態</p>
							
							<textarea name="site_status_note" rows="2" class="large-text" style="margin-top:10px;" placeholder="選填:狀態說明(只在異常時填寫)"><?php echo esc_textarea($site_status_note); ?></textarea>
							<p class="description">例如:「系統更新後觀察中」</p>
						</td>
					</tr>
					
					<!-- 2. 最近維運紀錄 -->
					<tr>
						<th scope="row">
							<label>最近維運紀錄</label>
						</th>
						<td>
							<input type="text" name="maintenance_date" value="<?php echo esc_attr($maintenance_date); ?>" class="regular-text" placeholder="例如:2026-01-02">
							<p class="description">維運日期</p>
							
							<textarea name="maintenance_note" rows="3" class="large-text" style="margin-top:10px;" placeholder="維運內容摘要(一到兩句話)"><?php echo esc_textarea($maintenance_note); ?></textarea>
							<p class="description">例如:「已完成外掛更新與安全性檢查」</p>
						</td>
					</tr>
					
					<!-- 3. 管理項目補充說明 -->
					<tr>
						<th scope="row">
							<label>管理項目補充說明</label>
						</th>
						<td>
							<textarea name="management_note" rows="2" class="large-text" placeholder="選填:補充說明"><?php echo esc_textarea($management_note); ?></textarea>
							<p class="description">例如:「每週自動備份一次」</p>
						</td>
					</tr>
					
					<!-- 4. 異常提醒 -->
					<tr>
						<th scope="row">
							<label>異常提醒</label>
						</th>
						<td>
							<label>
								<input type="checkbox" name="alert_enabled" value="1" <?php checked(1, $alert_enabled); ?>>
								<strong>顯示提醒區塊</strong>
							</label>
							<p class="description">勾選後才會在儀表板顯示提醒訊息</p>
							
							<textarea name="alert_message" rows="3" class="large-text" style="margin-top:10px;" placeholder="提醒內容"><?php echo esc_textarea($alert_message); ?></textarea>
							<p class="description">例如:「近期流量異常,已在觀察」</p>
						</td>
					</tr>
					
					<!-- 5. 受管制項目 -->
					<tr>
						<th scope="row">
							<label>受管制項目</label>
						</th>
						<td>
							<textarea name="restricted_items" rows="3" class="large-text"><?php echo esc_textarea($restricted_items); ?></textarea>
							<p class="description">客戶不能自己動的項目清單</p>
							
							<input type="text" name="contact_note" value="<?php echo esc_attr($contact_note); ?>" class="large-text" style="margin-top:10px;" placeholder="聯絡說明">
							<p class="description">例如:「如需調整請聯絡管理方」</p>
						</td>
					</tr>
					
					<!-- 6. 網站備註 -->
					<tr>
						<th scope="row">
							<label>網站備註</label>
						</th>
						<td>
							<textarea name="site_note" rows="2" class="large-text" placeholder="選填:補充說明"><?php echo esc_textarea($site_note); ?></textarea>
							<p class="description">補充網站相關說明</p>
						</td>
					</tr>
					
					<!-- 7. 管理單位 -->
					<tr>
						<th scope="row">
							<label>管理單位資訊</label>
						</th>
						<td>
							<input type="text" name="manager_name" value="<?php echo esc_attr($manager_name); ?>" class="regular-text" placeholder="單位名稱">
							<p class="description">管理單位或公司名稱</p>
							
							<textarea name="manager_contact" rows="3" class="large-text" style="margin-top:10px;" placeholder="聯絡方式"><?php echo esc_textarea($manager_contact); ?></textarea>
							<p class="description">例如:電話、Email、Line ID 等</p>
						</td>
					</tr>
					
					<!-- 8. 服務內容 -->
					<tr>
						<th scope="row">
							<label>服務內容摘要</label>
						</th>
						<td>
							<textarea name="service_items" rows="3" class="large-text"><?php echo esc_textarea($service_items); ?></textarea>
							<p class="description">已包含的服務項目清單</p>
							
							<input type="text" name="service_note" value="<?php echo esc_attr($service_note); ?>" class="large-text" style="margin-top:10px;" placeholder="補充說明">
							<p class="description">例如:「不含內容修改服務」</p>
						</td>
					</tr>
					
					<!-- 9. 系統提示 -->
					<tr>
						<th scope="row">
							<label>系統提示訊息</label>
						</th>
						<td>
							<textarea name="system_message" rows="2" class="large-text" placeholder="選填:正面或中性的提示訊息"><?php echo esc_textarea($system_message); ?></textarea>
							<p class="description">例如:「系統運作正常,如有問題請隨時聯絡」</p>
						</td>
					</tr>
				</table>
				
				<?php submit_button('儲存設定', 'primary large', 'wu_dashboard_submit'); ?>
			</form>
		</div>
		<?php endif; ?>
		
		<!-- 設計原則說明 -->
		<?php if ($can_edit): ?>
		<div style="background:#fff;padding:20px;border:1px solid #ddd;border-radius:5px;margin-top:30px;">
			<h3>📐 儀表板設計原則</h3>
			<ul style="line-height:2;color:#555;">
				<li><strong>不顯示技術數據</strong>:沒有效能分數、錯誤數量、資源用量等</li>
				<li><strong>不顯示紅字警告</strong>:避免製造不必要的焦慮</li>
				<li><strong>沒狀況就不顯示</strong>:區塊只在有內容時才出現</li>
				<li><strong>文字簡短清楚</strong>:一句話說完,不要寫報告</li>
				<li><strong>預設正常狀態</strong>:不需要特別說明「一切正常」</li>
			</ul>
			
			<h4 style="margin-top:20px;">使用時機範例</h4>
			<ul style="line-height:2;color:#555;">
				<li><strong>正常時</strong>:狀態選「正常運作中」,其他欄位留空或簡單填寫</li>
				<li><strong>更新後</strong>:狀態改「觀察中」,填寫「系統更新後觀察中」</li>
				<li><strong>有異常</strong>:啟用「異常提醒」,填寫具體狀況與處理方式</li>
				<li><strong>維運完成</strong>:更新「最近維運紀錄」,一句話說明做了什麼</li>
			</ul>
		</div>
		<?php endif; ?>
	</div>
	
	<style>
	.wrap h2, .wrap h3 {
		font-weight: 600;
	}
	.form-table th {
		width: 200px;
		font-weight: 600;
	}
	.form-table td {
		padding: 20px 10px;
	}
	</style>
	<?php
}

// ===== Dashboard Widget (Optional) =====

add_action('wp_dashboard_setup', function() {
	// 只在主儀表板顯示快速狀態
	$site_status = get_option('wu_dashboard_site_status', 'normal');
	
	wp_add_dashboard_widget(
		'wu_status_widget',
		'網站狀態',
		function() use ($site_status) {
			$status_labels = array(
				'normal' => array('label' => '正常運作中', 'color' => '#46b450', 'icon' => '✓'),
				'watching' => array('label' => '觀察中', 'color' => '#ffb900', 'icon' => '👁'),
				'handling' => array('label' => '處理中', 'color' => '#00a0d2', 'icon' => '🔧')
			);
			
			$current = $status_labels[$site_status] ?? $status_labels['normal'];
			
			?>
			<div style="text-align:center;padding:20px 0;">
				<div style="font-size:48px;margin-bottom:10px;"><?php echo $current['icon']; ?></div>
				<h3 style="margin:0;color:<?php echo $current['color']; ?>;font-size:20px;"><?php echo esc_html($current['label']); ?></h3>
				<p style="margin:15px 0 0;font-size:13px;">
					<a href="<?php echo admin_url('admin.php?page=wu-dashboard-overview'); ?>">查看完整儀表板 →</a>
				</p>
			</div>
			<?php
		}
	);
});
