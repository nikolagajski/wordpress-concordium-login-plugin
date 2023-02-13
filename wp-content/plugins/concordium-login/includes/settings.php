<?php

add_action('admin_init', function () {
	register_setting(
		'concordium_login_plugin_options',
		'concordium_login_plugin_options',
		function ($value) {
			$valid = true;
			$input = (array) $value;

			if (empty($input['hostname']))
			{
				$valid = false;
				add_settings_error('concordium_login_plugin_options', 'hostname', __('Hostname empty.', 'concordium-login'));
			}
			elseif (filter_var($input['hostname'], FILTER_VALIDATE_URL) === FALSE)
			{
				$valid = false;
				add_settings_error('concordium_login_plugin_options', 'hostname', __('Invalid hostname format.', 'concordium-login'));
			}

			if (empty($input['nonce_expired']))
			{
				$valid = false;
				add_settings_error('concordium_login_plugin_options', 'hostname', __('Nonce Expiring interval is empty.', 'concordium-login'));
			}
			else
			{
				try
				{
					new DateInterval($input['nonce_expired']);
				}
				catch (Throwable $e)
				{
					$valid = false;
					add_settings_error('concordium_login_plugin_options', 'hostname', __('Nonce Expiring interval: Unknown format', 'concordium-login'));
				}
			}

			// Ignore the user's changes and use the old database value.
			if (!$valid)
			{
				$value = get_option('concordium_login_plugin_options');
			}

			return $value;
		});
	add_settings_section('concordium_settings', 'Concordium', function () {
		echo '<p>' . __('Here you can set all the options for using the Concordium log-in', 'concordium-login') . '</p>';
	}, 'concordium_login_plugin');

	add_settings_field('concordium_login_hostname', __('JSON-RPC Concordium Client hostname <i>(Use next format: example.com:10000)</i>', 'concordium-login'), function () {
		$options = get_option('concordium_login_plugin_options', []);
		echo "<input id='concordium_login_hostname' name='concordium_login_plugin_options[hostname]' type='text' value='" . esc_attr($options['hostname'] ?? 'http://example.com:9095') . "' />";
	}, 'concordium_login_plugin', 'concordium_settings');
	add_settings_field('concordium_login_nonce_expired', __('Nonce Expiring interval <i>(Available format can be prepared with parameters from <a href="https://www.php.net/manual/en/dateinterval.format.php" target="_blank" rel="noopener noreferrer">PHP DateInterval::format</a>)</i>', 'concordium-login'), function () {
		$options = get_option('concordium_login_plugin_options', []);
		echo "<input id='concordium_login_nonce_expired' name='concordium_login_plugin_options[nonce_expired]' type='text' value='" . esc_attr($options['nonce_expired'] ?? 'PT10M') . "' />";
	}, 'concordium_login_plugin', 'concordium_settings');
});

add_action('admin_menu', function () {
	add_options_page(
		__('Concordium', 'concordium-login'),
		__('Concordium', 'concordium-login'),
		'manage_options',
		'concordium-login-plugin',
		function () {
			?>
			<form action="options.php" method="post">
				<?php
				settings_fields('concordium_login_plugin_options');
				do_settings_sections('concordium_login_plugin'); ?>
				<input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e('Save'); ?>"/>
			</form>
			<?php
		}
	);
});
