<?php
/**
 * @package     Aesirx\Concordium\Exception
 *
 * @copyright   Copyright (C) 2016 - 2023 Aesir. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @since       __DEPLOY_VERSION__
 */

namespace Aesirx\Concordium\Exception;

class ResponseException extends \Exception
{
	/**
	 * @var mixed
	 * @since __DEPLOY_VERSION__
	 */
	private $response;

	public function __construct($response, $message = "", $code = 0, \Throwable $previous = null)
	{
		parent::__construct($message, $code, $previous);
		$this->response = $response;
	}

	/**
	 * @return mixed
	 */
	public function getResponse()
	{
		return $this->response;
	}
}