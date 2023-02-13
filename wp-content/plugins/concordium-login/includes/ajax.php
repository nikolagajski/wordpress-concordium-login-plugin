<?php

use Aesirx\Concordium\Exception\ResponseException;
use Aesirx\Concordium\Helper;
use Aesirx\Concordium\Request\AccountInfo\AccountInfo;
use Aesirx\Concordium\Request\AccountTransactionSignature\AccountTransactionSignature;

add_action('wp_ajax_nopriv_concordium_nonce', function () {
	concordium_prepare_response(
		function (array $result): array {
			global $wpdb;
			$post = $_POST;

			$accountAddress = $post['accountAddress'];
			$table = $wpdb->prefix . 'concordium_nonce';

			$record = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM $table WHERE account_address = %s",
					$accountAddress
				)
			);

			$options = get_option('concordium_login_plugin_options');
			$save = false;
			$now = new DateTime;

			if ($record)
			{
				$createdAt = new DateTime($record->created_at);
				$expiryDate = clone $createdAt;
				$expiryDate->add(
					new \DateInterval($options['nonce_expired'] ?? 'PT10M')
				);

				if ($now > $expiryDate)
				{
					$save = true;
				}
				else
				{
					$nonce = $record->nonce;
				}
			}
			else
			{
				$save = true;
			}

			if ($save)
			{
				$nonce = sprintf("%06d", rand(0, 999999));

				if ($record)
				{
					$wpdb->update(
						$table, [
						'nonce' => $nonce,
						'created_at' => $now->format('Y-m-d H:i:s'),
					], [
							'account_address' => $accountAddress,
						]
					);
				}
				else
				{
					$wpdb->insert(
						$table,
						[
							'nonce' => $nonce,
							'account_address' => $accountAddress,
							'created_at' => $now->format('Y-m-d H:i:s'),
						]
					);
				}
			}

			$result['data']['nonce'] = getNonceMessage($nonce);

			return $result;
		}
	);
});

function getNonceMessage(string $nonce): string
{
	return sprintf(__("Sign nonce %s", 'concordium-login'), $nonce);
}

add_action('wp_ajax_nopriv_concordium_auth', function () {
	concordium_prepare_response(
		function (array $result): array {
			global $wpdb;
			$post = $_POST;

			$options = get_option('concordium_login_plugin_options');
			$accountAddress = $post['accountAddress'];
			$signed = $post['signed'];

			$table = $wpdb->prefix . 'concordium_nonce';

			$record = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM $table WHERE account_address = %s",
					$accountAddress
				)
			);

			if (!$record)
			{
				throw new Exception(__('Concordium account not found', 'concordium-login'));
			}

			$client = new JsonRpc\Client($options['hostname'] ?? '');

			if (!$client->call('getConsensusStatus', []))
			{
				throw new ResponseException($client->error, __('Concordium is not answering.Try please later 1', 'concordium-login'));
			}

			if (!$client->call(
				'getAccountInfo', [
					'address' => $accountAddress,
					'blockHash' => $client->result->lastFinalizedBlock
				]
			))
			{
				throw new ResponseException($client->error, __('Concordium is not answering.Try please later 1', 'concordium-login'));
			}

			if (!Helper::verifyMessageSignature(
				getNonceMessage($record->nonce),
				new AccountTransactionSignature($signed),
				new AccountInfo(json_decode(json_encode($client->result), true))
			))
			{
				throw new Exception(__('Validation is failed', 'concordium-login'));
			}

			concordium_start_session();
			$_SESSION['concordium_login_account_address'] = $accountAddress;

			// @todo uninstall
			if ($record->user_id)
			{
				$_SESSION['concordium_login_user_id'] = $record->user_id;

				/** @var WP_User|WP_Error $user WP_User on success, WP_Error on failure. */
				$user = wp_signon();

				if ($user instanceof WP_Error)
				{
					throw new Exception($user->get_error_message());
				}
			}
			else
			{
				if (get_option('users_can_register'))
				{
					throw new Exception(__('The wallet does not have an account yet, you can log-in or signup and then the wallet will be linked for future logins', 'concordium-login'));
				}
				else
				{
					throw new Exception(
					__('The wallet does not have an account yet, you can log-in and then the wallet will be linked for future logins', 'concordium-login')
				);
				}
			}

			return $result;
		}
	);
});

function concordium_prepare_response(callable $callback): void
{
	$result = [
		'message' => '',
		'data' => null,
		'success' => true,
	];
	header('Content-Type: application/json; charset=utf-8');

	try
	{
		$result = $callback($result);

		ob_clean();
		echo json_encode($result);
	}
	catch (Throwable $e)
	{
		ob_clean();
		status_header(500);

		if (WP_DEBUG)
		{
			$result['trace'] = $e->getTrace();

			if ($e instanceof ResponseException)
			{
				$result['response'] = $e->getResponse();
			}
		}

		$result['message'] = $e->getMessage();
		$result['success'] = false;

		echo json_encode($result);
	}

	die();
}
