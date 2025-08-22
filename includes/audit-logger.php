<?php
if (!defined('ABSPATH')) exit;

class WU_Audit_Logger {
	private $options;
	private $option_name = 'wu_audit_logger_options';
	private $table_name;
	private $cron_hook = 'wu_audit_logger_purge_event';

	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'wu_audit_logs';

		add_action('admin_menu', array($this, 'add_submenu_page'));
		add_action('admin_init', array($this, 'init_settings'));

		// Lazy install and schedule when options are loaded
		$this->options = get_option($this->option_name, array(
			'enabled' => false,
			'keep_days' => 7,
		));

		add_action('init', array($this, 'maybe_setup'));
		add_action($this->cron_hook, array($this, 'purge_old_logs'));

		// Register log hooks only if enabled
		if (!empty($this->options['enabled'])) {
			$this->register_hooks();
		}
	}

	public function maybe_setup() {
		if (!empty($this->options['enabled'])) {
			$this->maybe_create_table();
			$this->maybe_schedule_cron();
		}
	}

	private function maybe_create_table() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			log_time DATETIME NOT NULL,
			user_id BIGINT UNSIGNED NULL,
			action VARCHAR(80) NOT NULL,
			object_type VARCHAR(50) NULL,
			object_id BIGINT UNSIGNED NULL,
			object_name VARCHAR(191) NULL,
			ip_address VARCHAR(64) NULL,
			meta LONGTEXT NULL,
			PRIMARY KEY  (id),
			KEY log_time_idx (log_time),
			KEY action_idx (action),
			KEY object_type_idx (object_type),
			KEY user_idx (user_id)
		) $charset_collate;";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	private function maybe_schedule_cron() {
		if (!wp_next_scheduled($this->cron_hook)) {
			wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', $this->cron_hook);
		}
	}

	public function purge_old_logs() {
		$days = isset($this->options['keep_days']) ? intval($this->options['keep_days']) : 7;
		$days = in_array($days, array(3,7,14,31), true) ? $days : 7;
		global $wpdb;
		$threshold = gmdate('Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS);
		$wpdb->query($wpdb->prepare("DELETE FROM {$this->table_name} WHERE log_time < %s", $threshold));
	}

	public function add_submenu_page() {
		add_submenu_page(
			'wu-toolbox',
			'變更紀錄追蹤',
			'變更紀錄追蹤',
			'manage_options',
			'wu-audit-logger',
			array($this, 'settings_page')
		);
	}

	public function init_settings() {
		register_setting('wu_audit_logger_group', $this->option_name, array($this, 'sanitize_options'));

		add_settings_section('wu_audit_logger_section', '設定', '__return_false', 'wu-audit-logger');

		add_settings_field('enabled', '啟用功能', array($this, 'enabled_field'), 'wu-audit-logger', 'wu_audit_logger_section');
		add_settings_field('keep_days', '保留天數', array($this, 'keep_days_field'), 'wu-audit-logger', 'wu_audit_logger_section');
	}

	public function sanitize_options($input) {
		$sanitized = array();
		$sanitized['enabled'] = !empty($input['enabled']);
		$days = isset($input['keep_days']) ? intval($input['keep_days']) : 7;
		$sanitized['keep_days'] = in_array($days, array(3,7,14,31), true) ? $days : 7;
		return $sanitized;
	}

	public function enabled_field() {
		$enabled = !empty($this->options['enabled']);
		echo '<label><input type="checkbox" name="' . esc_attr($this->option_name) . '[enabled]" value="1" ' . checked($enabled, true, false) . '> 啟用追蹤</label>';
	}

	public function keep_days_field() {
		$current = isset($this->options['keep_days']) ? intval($this->options['keep_days']) : 7;
		$options = array(3,7,14,31);
		echo '<select name="' . esc_attr($this->option_name) . '[keep_days]">';
		foreach ($options as $d) {
			echo '<option value="' . intval($d) . '" ' . selected($current, $d, false) . '>' . intval($d) . ' 天</option>';
		}
		echo '</select>';
	}

	private function get_user_id() {
		$user_id = get_current_user_id();
		return $user_id ? intval($user_id) : null;
	}

	private function get_ip_address() {
		$keys = array('HTTP_X_FORWARDED_FOR','HTTP_CLIENT_IP','REMOTE_ADDR');
		foreach ($keys as $k) {
			if (!empty($_SERVER[$k])) {
				$raw = sanitize_text_field(wp_unslash($_SERVER[$k]));
				$parts = explode(',', $raw);
				return trim($parts[0]);
			}
		}
		return null;
	}

	private function insert_log($action, $object_type = null, $object_id = null, $object_name = null, $meta = null, $user_id = null) {
		global $wpdb;
		$data = array(
			'log_time' => current_time('mysql', true),
			'user_id' => is_null($user_id) ? $this->get_user_id() : $user_id,
			'action' => sanitize_text_field($action),
			'object_type' => is_null($object_type) ? null : sanitize_text_field($object_type),
			'object_id' => is_null($object_id) ? null : intval($object_id),
			'object_name' => is_null($object_name) ? null : (is_string($object_name) ? mb_substr($object_name, 0, 191) : null),
			'ip_address' => $this->get_ip_address(),
			'meta' => is_null($meta) ? null : wp_json_encode($meta),
		);
		$format = array('%s','%d','%s','%s','%d','%s','%s','%s');
		$wpdb->insert($this->table_name, $data, $format);
	}

	private function register_hooks() {
		// Posts & Pages
		add_action('save_post', function($post_id, $post, $update){
			if (wp_is_post_revision($post_id)) return;
			$this->insert_log($update ? 'post_updated' : 'post_created', $post->post_type, $post_id, $post->post_title);
		}, 10, 3);
		add_action('before_delete_post', function($post_id){
			$post = get_post($post_id);
			if ($post) $this->insert_log('post_deleted', $post->post_type, $post_id, $post->post_title);
		});

		// Media
		add_action('add_attachment', function($post_id){
			$post = get_post($post_id);
			$this->insert_log('media_uploaded', 'attachment', $post_id, $post ? $post->post_title : null);
		});
		add_action('delete_attachment', function($post_id){
			$post = get_post($post_id);
			$this->insert_log('media_deleted', 'attachment', $post_id, $post ? $post->post_title : null);
		});

		// Taxonomy terms
		add_action('created_term', function($term_id, $tt_id, $taxonomy){
			$term = get_term($term_id, $taxonomy);
			$this->insert_log('term_created', $taxonomy, $term_id, $term ? $term->name : null);
		}, 10, 3);
		add_action('edited_term', function($term_id, $tt_id, $taxonomy){
			$term = get_term($term_id, $taxonomy);
			$this->insert_log('term_edited', $taxonomy, $term_id, $term ? $term->name : null);
		}, 10, 3);
		add_action('delete_term', function($term_id, $tt_id, $taxonomy){
			$this->insert_log('term_deleted', $taxonomy, $term_id, null);
		}, 10, 3);

		// Comments
		add_action('wp_insert_comment', function($comment_id, $comment){
			$this->insert_log('comment_created', 'comment', $comment_id, wp_trim_words($comment->comment_content, 8), array('post_id' => $comment->comment_post_ID, 'status' => $comment->comment_approved));
		}, 10, 2);
		add_action('transition_comment_status', function($new_status, $old_status, $comment){
			$this->insert_log('comment_status', 'comment', $comment->comment_ID, wp_trim_words($comment->comment_content, 8), array('from' => $old_status, 'to' => $new_status));
		}, 10, 3);
		add_action('delete_comment', function($comment_id){
			$this->insert_log('comment_deleted', 'comment', $comment_id, null);
		});

		// Widgets & Menus & Options
		add_action('updated_option', function($option, $old_value, $value){
			if ($option === $this->option_name) return; // ignore self
			if (strpos($option, 'widget_') === 0) {
				$this->insert_log('widget_updated', 'widget', null, null, array('option' => $option));
			}
			if ($option === 'nav_menu_options') {
				$this->insert_log('menu_updated', 'menu', null, null);
			}
			if ($option === 'wp_page_for_privacy_policy') {
				$this->insert_log('privacy_page_set', 'privacy', intval($value), get_the_title(intval($value)));
			}
		}, 10, 3);
		add_action('wp_update_nav_menu', function($menu_id){
			$term = wp_get_nav_menu_object($menu_id);
			$this->insert_log('menu_updated', 'menu', $menu_id, $term ? $term->name : null);
		});

		// Plugins
		add_action('activated_plugin', function($plugin){
			$this->insert_log('plugin_activated', 'plugin', null, $plugin);
		});
		add_action('deactivated_plugin', function($plugin){
			$this->insert_log('plugin_deactivated', 'plugin', null, $plugin);
		});
		add_action('deleted_plugin', function($plugin){
			$this->insert_log('plugin_deleted', 'plugin', null, $plugin);
		});

		// Users
		add_action('user_register', function($user_id){
			$user = get_user_by('id', $user_id);
			$this->insert_log('user_created', 'user', $user_id, $user ? $user->user_login : null);
		});
		add_action('profile_update', function($user_id){
			$user = get_user_by('id', $user_id);
			$this->insert_log('user_updated', 'user', $user_id, $user ? $user->user_login : null);
		});
		add_action('delete_user', function($user_id){
			$this->insert_log('user_deleted', 'user', $user_id, null);
		});

		// Logins
		add_action('wp_login', function($user_login, $user){
			$this->insert_log('user_login', 'user', $user->ID, $user_login, null, $user->ID);
		}, 10, 2);
		add_action('wp_login_failed', function($username){
			$this->insert_log('user_login_failed', 'user', null, $username);
		});

		// Privacy export/erase
		add_action('user_request_action_confirmed', function($request_id, $action){
			$this->insert_log('privacy_request_confirmed', 'privacy', $request_id, $action);
		}, 10, 2);
		add_action('wp_privacy_personal_data_erased', function($request_id){
			$this->insert_log('privacy_data_erased', 'privacy', $request_id, null);
		});
		add_action('wp_privacy_personal_data_exported', function($request_id){
			$this->insert_log('privacy_data_exported', 'privacy', $request_id, null);
		});

		// Access denied to admin pages
		add_action('admin_page_access_denied', function(){
			$this->insert_log('admin_access_denied', 'admin', null, null, array('screen' => function_exists('get_current_screen') && get_current_screen() ? get_current_screen()->id : null));
		});
	}

	public function settings_page() {
		if (isset($_POST['submit']) && check_admin_referer('wu_audit_logger_save','wu_audit_logger_nonce')) {
			$raw = isset($_POST[$this->option_name]) ? (array) $_POST[$this->option_name] : array();
			$save = $this->sanitize_options($raw);
			update_option($this->option_name, $save);
			$this->options = $save;
			if (!empty($save['enabled'])) {
				$this->maybe_create_table();
				$this->maybe_schedule_cron();
			} else {
				// disable cron if turning off
				$timestamp = wp_next_scheduled($this->cron_hook);
				if ($timestamp) wp_unschedule_event($timestamp, $this->cron_hook);
			}
			echo '<div class="updated"><p>設定已儲存 ✅</p></div>';
		}

		$current = $this->options;
		?>
		<div class="wrap">
			<h1>變更紀錄追蹤</h1>
			<form method="post" action="">
				<?php wp_nonce_field('wu_audit_logger_save','wu_audit_logger_nonce'); ?>
				<?php settings_fields('wu_audit_logger_group'); ?>
				<?php do_settings_sections('wu-audit-logger'); ?>
				<?php submit_button('儲存設定'); ?>
			</form>

			<h2 style="margin-top:30px;">最近紀錄</h2>
			<?php $this->render_logs_table(); ?>
		</div>
		<?php
	}

	private function render_logs_table() {
		global $wpdb;
		$limit = 50;
		$rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->table_name} ORDER BY id DESC LIMIT %d", $limit));
		if (empty($rows)) {
			echo '<p>目前沒有紀錄。</p>';
			return;
		}
		echo '<table class="widefat striped"><thead><tr>';
		echo '<th>時間</th><th>使用者</th><th>動作</th><th>類型</th><th>目標</th><th>IP</th><th>備註</th>';
		echo '</tr></thead><tbody>';
		foreach ($rows as $r) {
			$user_display = $r->user_id ? esc_html(get_the_author_meta('user_login', $r->user_id)) : '-';
			$meta = $r->meta ? esc_html(wp_strip_all_tags(wp_json_encode(json_decode($r->meta, true), JSON_UNESCAPED_UNICODE))) : '';
			echo '<tr>';
			echo '<td>' . esc_html(get_date_from_gmt($r->log_time, 'Y-m-d H:i:s')) . '</td>';
			echo '<td>' . $user_display . '</td>';
			echo '<td>' . esc_html($r->action) . '</td>';
			echo '<td>' . esc_html($r->object_type) . '</td>';
			echo '<td>' . esc_html(trim(($r->object_name ? $r->object_name . ' ' : '') . ($r->object_id ? "(#{$r->object_id})" : ''))) . '</td>';
			echo '<td>' . esc_html($r->ip_address) . '</td>';
			echo '<td>' . $meta . '</td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
	}
}

new WU_Audit_Logger();