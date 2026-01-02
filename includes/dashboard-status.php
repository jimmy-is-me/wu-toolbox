<?php
if (!defined('ABSPATH')) exit;

/*
 * WumetaxToolkit - Client Dashboard Overview
 * Version: 2.0 - Data-Driven Client Dashboard
 * 
 * PURPOSE:
 * - Show business-understandable metrics (not technical monitoring)
 * - Display service status & contact info
 * - SEO basic data (display only, no settings)
 * - Performance optimized (no heavy queries)
 * 
 * PRINCIPLES:
 * - Dashboard = Read-only display layer
 * - Settings page = Management interface
 * - No technical jargon, no red warnings
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
	add_option('wu_dashboard_site_status', 'normal');
	add_option('wu_dashboard_last_maintenance', '');
	add_option('wu_dashboard_service_list', "網站定期備份\n系統安全監控\n功能更新維護\n效能優化調整\n技術支援諮詢");
	add_option('wu_dashboard_show_traffic', 1);
	add_option('wu_dashboard_show_woo', 1);
	add_option('wu_dashboard_show_seo', 1);
});

// ===== Dashboard Widgets =====

add_action('wp_dashboard_setup', function() {
	// 1. 網站狀態卡片
	wp_add_dashboard_widget(
		'wu_status_card',
		'網站狀態',
		'wu_render_status_widget'
	);
	
	// 2. 流量概覽
	if (get_option('wu_dashboard_show_traffic', 1)) {
		wp_add_dashboard_widget(
			'wu_traffic_overview',
			'流量概覽',
			'wu_render_traffic_widget'
		);
	}
	
	// 3. WooCommerce 訂單概覽
	if (get_option('wu_dashboard_show_woo', 1) && class_exists('WooCommerce')) {
		wp_add_dashboard_widget(
			'wu_woo_overview',
			'訂單概覽',
			'wu_render_woo_widget'
		);
	}
	
	// 4. SEO 基本資料
	if (get_option('wu_dashboard_show_seo', 1)) {
		wp_add_dashboard_widget(
			'wu_seo_overview',
			'SEO 基本資料',
			'wu_render_seo_widget'
		);
	}
	
	// 5. 服務內容
	wp_add_dashboard_widget(
		'wu_service_list',
		'目前包含的服務',
		'wu_render_service_widget'
	);
	
	// 6. 聯絡資訊
	wp_add_dashboard_widget(
		'wu_contact_info',
		'聯絡我們',
		'wu_render_contact_widget'
	);
});

// ===== Widget: 網站狀態 =====

function wu_render_status_widget() {
	$status = get_option('wu_dashboard_site_status', 'normal');
	$maintenance = get_option('wu_dashboard_last_maintenance', '');
	
	$status_config = array(
		'normal' => array('label' => '正常運作中', 'color' => '#46b450', 'icon' => '✓'),
		'watching' => array('label' => '觀察中', 'color' => '#ffb900', 'icon' => '👁'),
		'handling' => array('label' => '處理中', 'color' => '#00a0d2', 'icon' => '🔧')
	);
	
	$current = $status_config[$status] ?? $status_config['normal'];
	
	?>
	<div style="text-align:center;padding:30px 20px;">
		<div style="font-size:64px;line-height:1;margin-bottom:15px;"><?php echo $current['icon']; ?></div>
		<h2 style="margin:0 0 8px;color:<?php echo $current['color']; ?>;font-size:28px;font-weight:600;">
			<?php echo esc_html($current['label']); ?>
		</h2>
		<p style="margin:0;color:#666;font-size:14px;">網站整體狀態</p>
		
		<?php if (!empty($maintenance)): ?>
		<div style="margin-top:20px;padding:12px;background:#f9f9f9;border-radius:4px;">
			<p style="margin:0;color:#555;font-size:13px;">
				<strong>最近維運:</strong> <?php echo esc_html($maintenance); ?>
			</p>
		</div>
		<?php endif; ?>
	</div>
	<?php
}

// ===== Widget: 流量概覽 =====

function wu_render_traffic_widget() {
	// 使用 WordPress 內建統計 (輕量)
	$today_views = wu_get_post_views_today();
	$week_views = wu_get_post_views_week();
	$month_views = wu_get_post_views_month();
	
	?>
	<div style="padding:20px;">
		<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:15px;">
			
			<div style="text-align:center;padding:20px;background:#f0f7ff;border-radius:8px;">
				<div style="font-size:32px;font-weight:700;color:#0073aa;margin-bottom:5px;">
					<?php echo number_format($today_views); ?>
				</div>
				<div style="color:#666;font-size:13px;">今日瀏覽</div>
			</div>
			
			<div style="text-align:center;padding:20px;background:#f0fff4;border-radius:8px;">
				<div style="font-size:32px;font-weight:700;color:#46b450;margin-bottom:5px;">
					<?php echo number_format($week_views); ?>
				</div>
				<div style="color:#666;font-size:13px;">近 7 天</div>
			</div>
			
			<div style="text-align:center;padding:20px;background:#fff9e6;border-radius:8px;">
				<div style="font-size:32px;font-weight:700;color:#f0b849;margin-bottom:5px;">
					<?php echo number_format($month_views); ?>
				</div>
				<div style="color:#666;font-size:13px;">近 30 天</div>
			</div>
			
		</div>
		
		<div style="margin-top:15px;padding:12px;background:#f9f9f9;border-radius:4px;text-align:center;">
			<p style="margin:0;color:#666;font-size:12px;">
				💡 數據基於網站內建統計
			</p>
		</div>
	</div>
	<?php
}

// ===== Widget: WooCommerce 訂單概覽 =====

function wu_render_woo_widget() {
	if (!class_exists('WooCommerce')) {
		echo '<p style="padding:20px;text-align:center;color:#999;">未安裝 WooCommerce</p>';
		return;
	}
	
	// 輕量查詢 (無效能影響)
	$today_orders = wu_get_orders_count_today();
	$week_orders = wu_get_orders_count_week();
	$month_orders = wu_get_orders_count_month();
	$processing = wu_get_processing_orders_count();
	
	?>
	<div style="padding:20px;">
		<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:15px;margin-bottom:15px;">
			
			<div style="text-align:center;padding:20px;background:#f0f7ff;border-radius:8px;">
				<div style="font-size:32px;font-weight:700;color:#0073aa;margin-bottom:5px;">
					<?php echo number_format($today_orders); ?>
				</div>
				<div style="color:#666;font-size:13px;">今日訂單</div>
			</div>
			
			<div style="text-align:center;padding:20px;background:#fff3cd;border-radius:8px;">
				<div style="font-size:32px;font-weight:700;color:#856404;margin-bottom:5px;">
					<?php echo number_format($processing); ?>
				</div>
				<div style="color:#666;font-size:13px;">處理中</div>
			</div>
			
		</div>
		
		<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:15px;">
			
			<div style="padding:15px;background:#f9f9f9;border-radius:4px;text-align:center;">
				<div style="font-size:20px;font-weight:600;color:#333;margin-bottom:3px;">
					<?php echo number_format($week_orders); ?>
				</div>
				<div style="color:#666;font-size:12px;">近 7 天訂單</div>
			</div>
			
			<div style="padding:15px;background:#f9f9f9;border-radius:4px;text-align:center;">
				<div style="font-size:20px;font-weight:600;color:#333;margin-bottom:3px;">
					<?php echo number_format($month_orders); ?>
				</div>
				<div style="color:#666;font-size:12px;">近 30 天訂單</div>
			</div>
			
		</div>
	</div>
	<?php
}

// ===== Widget: SEO 基本資料 =====

function wu_render_seo_widget() {
	$total_posts = wp_count_posts('post')->publish;
	$total_pages = wp_count_posts('page')->publish;
	$site_url = home_url();
	
	// 檢查是否有 sitemap
	$has_sitemap = false;
	$sitemap_url = home_url('/sitemap.xml');
	$response = wp_remote_head($sitemap_url, array('timeout' => 3));
	if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) == 200) {
		$has_sitemap = true;
	}
	
	// 檢查 robots.txt
	$has_robots = file_exists(ABSPATH . 'robots.txt');
	
	?>
	<div style="padding:20px;">
		<div style="margin-bottom:15px;">
			<table style="width:100%;border-collapse:collapse;">
				<tr>
					<td style="padding:10px 0;color:#666;font-size:14px;">已發布文章</td>
					<td style="padding:10px 0;text-align:right;font-weight:600;color:#333;">
						<?php echo number_format($total_posts); ?> 篇
					</td>
				</tr>
				<tr style="border-top:1px solid #eee;">
					<td style="padding:10px 0;color:#666;font-size:14px;">已發布頁面</td>
					<td style="padding:10px 0;text-align:right;font-weight:600;color:#333;">
						<?php echo number_format($total_pages); ?> 頁
					</td>
				</tr>
				<tr style="border-top:1px solid #eee;">
					<td style="padding:10px 0;color:#666;font-size:14px;">Sitemap 狀態</td>
					<td style="padding:10px 0;text-align:right;">
						<?php if ($has_sitemap): ?>
							<span style="color:#46b450;font-weight:600;">✓ 已設定</span>
						<?php else: ?>
							<span style="color:#999;">未偵測到</span>
						<?php endif; ?>
					</td>
				</tr>
				<tr style="border-top:1px solid #eee;">
					<td style="padding:10px 0;color:#666;font-size:14px;">Robots.txt</td>
					<td style="padding:10px 0;text-align:right;">
						<?php if ($has_robots): ?>
							<span style="color:#46b450;font-weight:600;">✓ 已設定</span>
						<?php else: ?>
							<span style="color:#999;">未偵測到</span>
						<?php endif; ?>
					</td>
				</tr>
			</table>
		</div>
		
		<div style="padding:12px;background:#f0f7ff;border-radius:4px;text-align:center;">
			<p style="margin:0;color:#555;font-size:12px;">
				💡 SEO 設定由管理方維護
			</p>
		</div>
	</div>
	<?php
}

// ===== Widget: 服務內容 =====

function wu_render_service_widget() {
	$service_list = get_option('wu_dashboard_service_list', '');
	$items = array_filter(explode("\n", $service_list));
	
	?>
	<div style="padding:20px;">
		<?php if (!empty($items)): ?>
		<ul style="margin:0;padding:0;list-style:none;">
			<?php foreach ($items as $item): ?>
			<li style="padding:10px 0;border-bottom:1px solid #f0f0f0;color:#555;font-size:14px;">
				<span style="color:#46b450;margin-right:8px;">✓</span>
				<?php echo esc_html(trim($item)); ?>
			</li>
			<?php endforeach; ?>
		</ul>
		<?php else: ?>
		<p style="margin:0;padding:20px;text-align:center;color:#999;">尚未設定服務項目</p>
		<?php endif; ?>
	</div>
	<?php
}

// ===== Widget: 聯絡資訊 =====

function wu_render_contact_widget() {
	?>
	<div style="padding:20px;">
		<div style="margin-bottom:20px;text-align:center;">
			<h3 style="margin:0 0 5px;font-size:18px;color:#333;font-weight:600;">
				WUMETAX 末特數位科技
			</h3>
			<p style="margin:0;color:#666;font-size:13px;">網站維運管理單位</p>
		</div>
		
		<div style="background:#f9f9f9;padding:15px;border-radius:6px;margin-bottom:12px;">
			<div style="display:flex;align-items:center;margin-bottom:10px;">
				<span style="color:#0073aa;font-size:18px;margin-right:10px;">🌐</span>
				<a href="https://wumetax.com/contact-us/" target="_blank" style="color:#0073aa;text-decoration:none;font-size:14px;">
					聯絡我們表單
				</a>
			</div>
			<div style="display:flex;align-items:center;">
				<span style="color:#46b450;font-size:18px;margin-right:10px;">💬</span>
				<a href="https://line.me/R/ti/p/@081pjqol" target="_blank" style="color:#46b450;text-decoration:none;font-size:14px;">
					LINE 官方帳號
				</a>
			</div>
		</div>
		
		<div style="text-align:center;padding:10px;background:#fff3cd;border-radius:4px;">
			<p style="margin:0;color:#856404;font-size:12px;">
				有問題隨時與我們聯絡
			</p>
		</div>
	</div>
	<?php
}

// ===== Helper Functions (Performance Optimized) =====

// 今日瀏覽 (使用 transient 快取)
function wu_get_post_views_today() {
	$cache_key = 'wu_views_today_' . date('Ymd');
	$cached = get_transient($cache_key);
	
	if ($cached !== false) {
		return $cached;
	}
	
	global $wpdb;
	$today_start = strtotime('today');
	
	// 簡化查詢 (僅計數)
	$count = $wpdb->get_var($wpdb->prepare("
		SELECT COUNT(DISTINCT user_ip) 
		FROM {$wpdb->prefix}statistics_visits 
		WHERE last_visit >= %d
	", $today_start));
	
	$count = $count ?: 0;
	set_transient($cache_key, $count, HOUR_IN_SECONDS);
	
	return $count;
}

// 近 7 天瀏覽
function wu_get_post_views_week() {
	$cache_key = 'wu_views_week_' . date('W');
	$cached = get_transient($cache_key);
	
	if ($cached !== false) {
		return $cached;
	}
	
	global $wpdb;
	$week_start = strtotime('-7 days');
	
	$count = $wpdb->get_var($wpdb->prepare("
		SELECT COUNT(DISTINCT user_ip) 
		FROM {$wpdb->prefix}statistics_visits 
		WHERE last_visit >= %d
	", $week_start));
	
	$count = $count ?: 0;
	set_transient($cache_key, $count, HOUR_IN_SECONDS * 6);
	
	return $count;
}

// 近 30 天瀏覽
function wu_get_post_views_month() {
	$cache_key = 'wu_views_month_' . date('Ym');
	$cached = get_transient($cache_key);
	
	if ($cached !== false) {
		return $cached;
	}
	
	global $wpdb;
	$month_start = strtotime('-30 days');
	
	$count = $wpdb->get_var($wpdb->prepare("
		SELECT COUNT(DISTINCT user_ip) 
		FROM {$wpdb->prefix}statistics_visits 
		WHERE last_visit >= %d
	", $month_start));
	
	$count = $count ?: 0;
	set_transient($cache_key, $count, HOUR_IN_SECONDS * 12);
	
	return $count;
}

// 今日訂單數 (WooCommerce)
function wu_get_orders_count_today() {
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

// 近 7 天訂單數
function wu_get_orders_count_week() {
	$cache_key = 'wu_orders_week_' . date('W');
	$cached = get_transient($cache_key);
	
	if ($cached !== false) {
		return $cached;
	}
	
	$args = array(
		'limit' => -1,
		'date_created' => '>=' . strtotime('-7 days'),
		'return' => 'ids'
	);
	
	$orders = wc_get_orders($args);
	$count = count($orders);
	
	set_transient($cache_key, $count, HOUR_IN_SECONDS * 6);
	
	return $count;
}

// 近 30 天訂單數
function wu_get_orders_count_month() {
	$cache_key = 'wu_orders_month_' . date('Ym');
	$cached = get_transient($cache_key);
	
	if ($cached !== false) {
		return $cached;
	}
	
	$args = array(
		'limit' => -1,
		'date_created' => '>=' . strtotime('-30 days'),
		'return' => 'ids'
	);
	
	$orders = wc_get_orders($args);
	$count = count($orders);
	
	set_transient($cache_key, $count, HOUR_IN_SECONDS * 12);
	
	return $count;
}

// 處理中訂單數
function wu_get_processing_orders_count() {
	if (function_exists('wc_processing_order_count')) {
		return wc_processing_order_count();
	}
	
	global $wpdb;
	return $wpdb->get_var("
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
		
		update_option('wu_dashboard_site_status', sanitize_text_field($_POST['site_status'] ?? 'normal'));
		update_option('wu_dashboard_last_maintenance', sanitize_text_field($_POST['last_maintenance'] ?? ''));
		update_option('wu_dashboard_service_list', sanitize_textarea_field($_POST['service_list'] ?? ''));
		update_option('wu_dashboard_show_traffic', isset($_POST['show_traffic']) ? 1 : 0);
		update_option('wu_dashboard_show_woo', isset($_POST['show_woo']) ? 1 : 0);
		update_option('wu_dashboard_show_seo', isset($_POST['show_seo']) ? 1 : 0);
		
		// 清除快取
		delete_transient('wu_views_today_' . date('Ymd'));
		delete_transient('wu_views_week_' . date('W'));
		delete_transient('wu_views_month_' . date('Ym'));
		delete_transient('wu_orders_today_' . date('Ymd'));
		delete_transient('wu_orders_week_' . date('W'));
		delete_transient('wu_orders_month_' . date('Ym'));
		
		echo '<div class="notice notice-success is-dismissible"><p><strong>✅ 設定已儲存</strong></p></div>';
	}
	
	$site_status = get_option('wu_dashboard_site_status', 'normal');
	$last_maintenance = get_option('wu_dashboard_last_maintenance', '');
	$service_list = get_option('wu_dashboard_service_list', '');
	$show_traffic = get_option('wu_dashboard_show_traffic', 1);
	$show_woo = get_option('wu_dashboard_show_woo', 1);
	$show_seo = get_option('wu_dashboard_show_seo', 1);
	
	?>
	<div class="wrap">
		<h1>⚙️ 儀表板設定</h1>
		
		<div class="notice notice-info" style="padding:15px;">
			<p style="margin:0;"><strong>💡 說明</strong></p>
			<ul style="margin:8px 0 0 20px;line-height:1.8;">
				<li>此頁面為管理設定介面,客戶看不到</li>
				<li>儀表板會直接顯示在 WordPress 管理後台首頁</li>
				<li>所有數據使用快取機制,不影響網站效能</li>
			</ul>
		</div>
		
		<form method="post" style="background:#fff;padding:25px;border:1px solid #ddd;border-radius:5px;margin-top:20px;">
			<?php wp_nonce_field('wu_dashboard_settings'); ?>
			
			<table class="form-table">
				
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
						<p class="description">客戶會在儀表板看到此狀態</p>
					</td>
				</tr>
				
				<!-- 最近維運 -->
				<tr>
					<th scope="row">
						<label>最近維運時間</label>
					</th>
					<td>
						<input type="text" name="last_maintenance" value="<?php echo esc_attr($last_maintenance); ?>" class="regular-text" placeholder="例如: 2026/01/02 完成系統更新">
						<p class="description">顯示在狀態卡片下方</p>
					</td>
				</tr>
				
				<!-- 服務清單 -->
				<tr>
					<th scope="row">
						<label>服務內容清單</label>
					</th>
					<td>
						<textarea name="service_list" rows="8" class="large-text" placeholder="每行一項服務"><?php echo esc_textarea($service_list); ?></textarea>
						<p class="description">每行一項服務,會顯示打勾清單</p>
					</td>
				</tr>
				
				<!-- 顯示控制 -->
				<tr>
					<th scope="row">
						<label>顯示區塊</label>
					</th>
					<td>
						<fieldset>
							<label style="display:block;margin-bottom:8px;">
								<input type="checkbox" name="show_traffic" value="1" <?php checked(1, $show_traffic); ?>>
								<strong>流量概覽</strong>
							</label>
							
							<label style="display:block;margin-bottom:8px;">
								<input type="checkbox" name="show_woo" value="1" <?php checked(1, $show_woo); ?>>
								<strong>訂單概覽</strong> (需安裝 WooCommerce)
							</label>
							
							<label style="display:block;">
								<input type="checkbox" name="show_seo" value="1" <?php checked(1, $show_seo); ?>>
								<strong>SEO 基本資料</strong>
							</label>
						</fieldset>
						<p class="description">控制要在儀表板顯示哪些區塊</p>
					</td>
				</tr>
				
			</table>
			
			<?php submit_button('儲存設定', 'primary large', 'wu_dashboard_save'); ?>
		</form>
		
		<!-- 效能說明 -->
		<div style="background:#fff;padding:20px;border:1px solid #ddd;border-radius:5px;margin-top:30px;">
			<h3>⚡ 效能優化機制</h3>
			<ul style="line-height:2;color:#555;">
				<li><strong>查詢快取</strong>: 今日數據快取 1 小時,週/月數據快取 6-12 小時</li>
				<li><strong>輕量查詢</strong>: 僅查詢必要欄位,不載入完整物件</li>
				<li><strong>條件載入</strong>: 未勾選的區塊不會執行查詢</li>
				<li><strong>資料庫索引</strong>: 使用 WordPress 與 WooCommerce 原生索引</li>
			</ul>
			
			<p style="margin:15px 0 0;color:#666;font-size:14px;">
				💡 如需立即更新數據,儲存設定後會自動清除快取
			</p>
		</div>
	</div>
	<?php
}

// ===== CSS Styles =====

add_action('admin_head', function() {
	?>
	<style>
	#wu_status_card .inside,
	#wu_traffic_overview .inside,
	#wu_woo_overview .inside,
	#wu_seo_overview .inside,
	#wu_service_list .inside,
	#wu_contact_info .inside {
		padding: 0 !important;
		margin: 0 !important;
	}
	
	#wu_status_card h2,
	#wu_traffic_overview h2,
	#wu_woo_overview h2,
	#wu_seo_overview h2,
	#wu_service_list h2,
	#wu_contact_info h2 {
		padding: 12px !important;
		margin: 0 !important;
		border-bottom: 1px solid #f0f0f0 !important;
	}
	</style>
	<?php
});
