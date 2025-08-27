<?php
if (!defined('ABSPATH')) exit;

/*
 * Lightweight post views counter
 * - Stores real views in post meta: _wu_views_real
 * - Optional admin offset meta: _wu_views_offset
 * - Read via helper that sums real + offset
 * - Auto increment on single post
 * - Shortcode [wu_views id="" show="real|total"]
 * - Admin column and quick edit for offset
 */

function wu_get_post_views($post_id = null, $mode = 'total') {
	$post_id = $post_id ? intval($post_id) : get_the_ID();
	if (!$post_id) return 0;
	$real = intval(get_post_meta($post_id, '_wu_views_real', true));
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
	$id = intval($atts['id']) ?: get_the_ID();
	$show = ($atts['show'] === 'real') ? 'real' : 'total';
	return intval(wu_get_post_views($id, $show));
});

// Auto display after content (front)
add_filter('the_content', function($content){
	if (!is_singular('post')) return $content;
	$views = wu_get_post_views(get_the_ID(), 'total');
	$html = '<div class="wu-views" style="opacity:.8;font-size:.9em;">👁️ 瀏覽：' . intval($views) . '</div>';
	return $content . $html;
});

// Admin column
add_filter('manage_post_posts_columns', function($cols){
	$cols['wu_views'] = '瀏覽量';
	return $cols;
});
add_action('manage_post_posts_custom_column', function($col, $post_id){
	if ($col !== 'wu_views') return;
	$real = intval(get_post_meta($post_id, '_wu_views_real', true));
	$offset = intval(get_post_meta($post_id, '_wu_views_offset', true));
	echo '<span title="真實: '.$real.' + 調整: '.$offset.'">' . intval($real + $offset) . '</span>';
}, 10, 2);

// Quick edit field for offset
add_action('quick_edit_custom_box', function($column_name, $post_type){
	if ($post_type !== 'post' || $column_name !== 'wu_views') return;
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

