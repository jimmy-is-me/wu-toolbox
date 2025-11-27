<?php
if (!defined('ABSPATH')) exit;
/*
 * Lightweight post views counter
 * - Stores real views in post meta: _wu_views_real
 * - Optional admin offset meta: _wu_views_offset
 * - Read via helper that sums real + offset
 * - Auto increment on single post
 * - Shortcode [wu_views id="" show="real|total"]
 * - Auto/Manual display controlled via options:
 *   - wu_views_auto_display (1/0)
 *   - wu_views_display_mode (real/total)
 *   - wu_views_admin_display (1/0) - 控制後台顯示
 * - Admin column, quick edit and meta box for offset
 */

function wu_get_post_views($post_id = null, $mode = 'total') {
	$post_id = $post_id ? intval($post_id) : get_the_ID();
	if (!$post_id) return 0;
	$real   = intval(get_post_meta($post_id, '_wu_views_real', true));
	$offset = intval(get_post_meta($post_id, '_wu_views_offset', true));
	return ($mode === 'real') ? $real : max(0, $real + $offset);
}

function wu_increment_post_views() {
	if (!is_singular('post')) return; // 文章
	if (is_user_logged_in() && current_user_can('manage_options')) return; // 管理員瀏覽不計數
	$post_id = get_queried_object_id();
	if (!$post_id) return;

	$real = intval(get_post_meta($post_id, '_wu_views_real', true));
	$real++;
	update_post_meta($post_id, '_wu_views_real', $real);
}
add_action('template_redirect', 'wu_increment_post_views');

// Shortcode: [wu_views id="" show="real|total"]
add_shortcode('wu_views', function($atts){
	$atts = shortcode_atts(array('id'=>0,'show'=>'total'), $atts, 'wu_views');
	$id   = intval($atts['id']) ?: get_the_ID();
	$show = ($atts['show'] === 'real') ? 'real' : 'total';
	return intval(wu_get_post_views($id, $show));
});

// Settings defaults：預設全部關閉
add_action('admin_init', function(){
	add_option('wu_views_auto_display', 0);          // 預設：前台不自動顯示
	add_option('wu_views_display_mode', 'total');    // 預設模式仍為 total
	add_option('wu_views_admin_display', 0);         // 預設：後台不顯示
});

// Auto display after content (front) if enabled
add_filter('the_content', function($content){
	if (!is_singular('post')) return $content;
	if (!get_option('wu_views_auto_display', 0)) return $content; // default 改為 0
	$mode  = (get_option('wu_views_display_mode', 'total') === 'real') ? 'real' : 'total';
	$views = wu_get_post_views(get_the_ID(), $mode);
	$html  = '<div class="wu-views" style="opacity:.8;font-size:.9em;">👁️ 瀏覽：' . intval($views) . '</div>';
	return $content . $html;
});

// Admin column - 根據設定控制是否顯示
add_filter('manage_post_posts_columns', function($cols){
	if (get_option('wu_views_admin_display', 0)) {
		$cols['wu_views'] = '瀏覽量';
	}
	return $cols;
});

add_action('manage_post_posts_custom_column', function($col, $post_id){
	if ($col !== 'wu_views') return;
	if (!get_option('wu_views_admin_display', 0)) return;

	$real   = intval(get_post_meta($post_id, '_wu_views_real', true));
	$offset = intval(get_post_meta($post_id, '_wu_views_offset', true));
	$total  = $real + $offset;
	echo '<span title="真實: '.$real.' + 調整: '.$offset.'">' . intval($total) . '</span>';
}, 10, 2);

// Quick edit field for offset
add_action('quick_edit_custom_box', function($column_name, $post_type){
	if ($post_type !== 'post' || $column_name !== 'wu_views') return;
	if (!get_option('wu_views_admin_display', 0)) return;

	echo '<fieldset class="inline-edit-col-left"><div class="inline-edit-col"><label class="inline-edit-group">';
	echo '<span class="title">瀏覽量調整</span>';
	echo '<input type="number" name="wu_views_offset" value="0" class="ptitle" style="width:100px;">';
	echo '</label></div></fieldset>';
}, 10, 2);

add_action('save_post_post', function($post_id){
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
	if (!current_user_can('edit_post', $post_id)) return;
	if (isset($_POST['wu_views_offset'])) {
		update_post_meta($post_id, '_wu_views_offset', intval($_POST['wu_views_offset']));
	}
});

// Meta box on post edit for views display and offset
add_action('add_meta_boxes', function(){
	if (!get_option('wu_views_admin_display', 0)) return;

	add_meta_box('wu_views_box', '瀏覽量', function($post){
		$real   = intval(get_post_meta($post->ID, '_wu_views_real', true));
		$offset = intval(get_post_meta($post->ID, '_wu_views_offset', true));
		$total  = $real + $offset;

		echo '<p>真實瀏覽量：<strong>' . intval($real) . '</strong></p>';
		echo '<p>總瀏覽量：<strong>' . intval($total) . '</strong></p>';
		echo '<p><label>瀏覽量調整（顯示加總）<br><input type="number" name="wu_views_offset" value="' . esc_attr($offset) . '" style="width:120px;"></label></p>';
		echo '<p class="description">瀏覽量為累計數據，不會重置。調整值可為正數或負數。</p>';
		wp_nonce_field('wu_views_meta_box','wu_views_meta_box_nonce');
	}, 'post', 'side', 'default');
});

add_action('save_post_post', function($post_id){
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
	if (!current_user_can('edit_post', $post_id)) return;
	if (!isset($_POST['wu_views_meta_box_nonce']) || !wp_verify_nonce($_POST['wu_views_meta_box_nonce'], 'wu_views_meta_box')) return;
	if (isset($_POST['wu_views_offset'])) {
		update_post_meta($post_id, '_wu_views_offset', intval($_POST['wu_views_offset']));
	}
});

// Simple settings UI under parent menu if exists
add_action('admin_menu', function(){
	add_submenu_page(
		'wumetax-toolkit',
		'文章瀏覽量設定',
		'文章瀏覽量',
		'manage_options',
		'wu-post-views-settings',
		function(){
			if (isset($_POST['submit'])) {
				check_admin_referer('wu_post_views_settings');
				update_option('wu_views_auto_display', isset($_POST['wu_views_auto_display']) ? 1 : 0);
				update_option('wu_views_admin_display', isset($_POST['wu_views_admin_display']) ? 1 : 0);
				$mode = (isset($_POST['wu_views_display_mode']) && $_POST['wu_views_display_mode'] === 'real') ? 'real' : 'total';
				update_option('wu_views_display_mode', $mode);
				echo '<div class="notice notice-success"><p>設定已儲存。</p></div>';
			}

			echo '<div class="wrap"><h1>文章瀏覽量設定</h1>';
			echo '<form method="post">';
			wp_nonce_field('wu_post_views_settings');
			echo '<table class="form-table">';

			// 前台自動顯示設定
			echo '<tr><th scope="row">前台自動顯示</th><td><label><input type="checkbox" name="wu_views_auto_display" value="1" ' . checked(1, get_option('wu_views_auto_display',0), false) . '> 在文章內容後自動顯示瀏覽量</label><p class="description">若關閉，請使用短代碼 [wu_views] 自行放置。</p></td></tr>';

			// 後台顯示設定
			echo '<tr><th scope="row">後台顯示設定</th><td><label><input type="checkbox" name="wu_views_admin_display" value="1" ' . checked(1, get_option('wu_views_admin_display',0), false) . '> 在文章列表和編輯頁面顯示瀏覽量</label><p class="description">控制是否在後台顯示瀏覽量欄位和設定。</p></td></tr>';

			// 顯示模式設定
			echo '<tr><th scope="row">顯示模式</th><td>';
			echo '<label><input type="radio" name="wu_views_display_mode" value="total" ' . checked('total', get_option('wu_views_display_mode','total'), false) . '> 真實 + 管理員調整</label><br>';
			echo '<label><input type="radio" name="wu_views_display_mode" value="real" ' . checked('real', get_option('wu_views_display_mode','total'), false) . '> 只顯示真實瀏覽量</label>';
			echo '<p class="description">控制前台和短代碼的預設顯示方式。</p>';
			echo '</td></tr>';

			echo '</table>';
			submit_button();
			echo '</form>';

			echo '<h2>使用說明</h2>';
			echo '<ul>';
			echo '<li><strong>短代碼：</strong><code>[wu_views id=\"\" show=\"real|total\"]</code></li>';
			echo '<li><strong>累計瀏覽量：</strong>所有瀏覽量都是累計計算，不會每日重置</li>';
			echo '<li><strong>管理員瀏覽：</strong>管理員的瀏覽不會計入瀏覽量統計</li>';
			echo '<li><strong>調整功能：</strong>可在文章編輯頁面調整顯示的瀏覽量數字</li>';
			echo '</ul>';
			echo '</div>';
		}
	);
});

// 讓欄位可排序（可選功能）
add_filter('manage_edit-post_sortable_columns', function($columns) {
	if (get_option('wu_views_admin_display', 0)) {
		$columns['wu_views'] = 'wu_views';
	}
	return $columns;
});

add_action('pre_get_posts', function($query) {
	if (!is_admin() || !$query->is_main_query()) return;
	if ($query->get('orderby') === 'wu_views') {
		$query->set('meta_key', '_wu_views_real');
		$query->set('orderby', 'meta_value_num');
	}
});
?>
