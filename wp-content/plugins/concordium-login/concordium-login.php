<?php
/**
 * Plugin Name: concordium-login
 * Description: Let users be auth via Concordium.
 * Version: 0.1
 * Author: aesirx.io
 * Author URI: https://aesirx.io/
 * Domain Path: /languages
 **/

require_once 'vendor/autoload.php';
require_once 'includes/settings.php';
require_once 'includes/ajax.php';

function concordium_start_session(): void
{
	if (session_status() === PHP_SESSION_NONE)
	{
		session_start();
	}
}

add_action('login_form', function () {
	$rand = sprintf("%06d", rand(0, 999999));

	$svg = file_get_contents(ABSPATH . '/wp-content/plugins/concordium-login/assets/images/concordium_black.svg');

	wp_register_script(
		'concordium',
		plugins_url('assets/js/login.js', __FILE__),
		['wp-i18n']
	);
	wp_set_script_translations('concordium', 'concordium-login');
	wp_register_style('concordium', '/wp-content/plugins/concordium-login/assets/css/login.css');
	wp_enqueue_style('concordium');
	wp_enqueue_script('concordium');
	wp_localize_script(
		'concordium',
		'CONCORDIUM_VAL',
		[
			'siteurl' => get_option('siteurl'),
			'ajaxurl' => admin_url('admin-ajax.php'),
		]
	);

	?>
	<div class="user-pass-wrap">
		<button type="button" name="concordium_submit" id="concordium_submit_<?php echo $rand ?>"
				class="button button-default concordium_submit">
			<?php echo $svg ?> Concordium
		</button>
	</div>
	<?php
});

function concordium_set_user_to_nonce(int $userId): void
{
	concordium_start_session();

	if (empty($_SESSION['concordium_login_account_address']))
	{
		return;
	}

	$accountAddress = $_SESSION['concordium_login_account_address'];

	global $wpdb;

	$table = $wpdb->prefix . 'concordium_nonce';

	$record = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM $table WHERE account_address = %s",
			$accountAddress
		)
	);

	if (!$record
		|| $record->user_id)
	{
		return;
	}

	$wpdb->update(
		$table,
		[
			'user_id' => $userId,
		],
		[
			'account_address' => $accountAddress,
		]
	);

	$_SESSION['concordium_login_user_id'] = $userId;

	add_action('admin_notices', function (): void {
		?>
		<div class="update notice">
			<p><?php _e('Concordium wallet was successfully linked to the account!', 'concordium-login'); ?></p>
		</div>
		<?php
	});
}

add_filter('authenticate', function ($user) {
	concordium_start_session();

	if ($user instanceof WP_User
		|| empty($_SESSION['concordium_login_account_address'])
		|| empty($_SESSION['concordium_login_user_id']))
	{
		return $user;
	}

	global $wpdb;

	$table = $wpdb->prefix . 'concordium_nonce';
	$record = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM $table WHERE account_address = %s AND user_id = %s",
			$_SESSION['concordium_login_account_address'],
			$_SESSION['concordium_login_user_id']
		)
	);

	if (!$record)
	{
		return $user;
	}

	return new WP_User($_SESSION['concordium_login_user_id']);
});

add_action('register_new_user', 'concordium_set_user_to_nonce');

add_action('wp_login', function ($user_login, $user): void {
	concordium_set_user_to_nonce($user->ID);
}, 10, 2);

add_action('wp_logout', function (int $userId): void {
	concordium_start_session();

	$_SESSION['concordium_login_account_address'] = null;
	$_SESSION['concordium_login_user_id'] = null;
});

register_activation_hook(__FILE__, function () {
	global $wpdb;

	$table_name = $wpdb->prefix . "concordium_nonce";
	$users = $wpdb->prefix . "users";

	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name
(
    user_id         bigint(20) unsigned NULL DEFAULT NULL,
    nonce           varchar(255) NOT NULL,
    account_address varchar(255) NOT NULL,
    created_at      datetime     NOT NULL,
    UNIQUE KEY  idx_account_address (account_address),
    UNIQUE KEY  idx_user_id (user_id) USING BTREE,
    FOREIGN KEY  (user_id) REFERENCES $users(id) ON DELETE CASCADE ON UPDATE SET NULL
) ENGINE = InnoDB
  $charset_collate";

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
});
