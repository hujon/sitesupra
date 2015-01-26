<?php

/*
 * Copyright (C) SiteSupra SIA, Riga, Latvia, 2015
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */

namespace Supra\Core\HttpFoundation;

use Symfony\Component\HttpFoundation\JsonResponse;

class SupraJsonResponse extends JsonResponse
{
	protected $jsonData = null;
	protected $jsonStatus = 1;
	protected $jsonErrorMessage = null;
	protected $jsonWarningMessage = null;
	protected $jsonPermissions = null;
	protected $jsonParts = array();

	public function __construct($data = true, $status = 200, $headers = array())
	{
		$this->jsonData = $data;

		parent::__construct($data, $status, $headers);
	}

	public function addPart($name, $value)
	{
		$this->jsonParts[$name] = $value;

		return parent::setData($this->compactJson());
	}

	public function setData($data = array())
	{
		$this->jsonData = $data;

		return parent::setData($this->compactJson());
	}

	/**
	 * @param $errorMessage
	 * @return JsonResponse
	 */
	public function setErrorMessage($errorMessage)
	{
		$this->jsonErrorMessage = $errorMessage;

		return parent::setData($this->compactJson());
	}

	/**
	 * @return null
	 */
	public function getErrorMessage()
	{
		return $this->jsonErrorMessage;
	}

	/**
	 * @param null $permissions
	 * @return JsonResponse
	 */
	public function setPermissions($permissions)
	{
		$this->jsonPermissions = $permissions;

		return parent::setData($this->compactJson());
	}

	/**
	 * @return null
	 */
	public function getPermissions()
	{
		return $this->jsonPermissions;
	}

	/**
	 * @param int $status
	 * @return JsonResponse
	 */
	public function setStatus($status)
	{
		$this->jsonStatus = $status;

		return parent::setData($this->compactJson());
	}

	/**
	 * @return int
	 */
	public function getStatus()
	{
		return $this->jsonStatus;
	}

	/**
	 * @param null $warningMessage
	 * @return JsonResponse
	 */
	public function setWarningMessage($warningMessage)
	{
		$this->jsonWarningMessage = $warningMessage;

		return parent::setData($this->compactJson());
	}

	/**
	 * @return null
	 */
	public function getWarningMessage()
	{
		return $this->jsonWarningMessage;
	}

	protected function compactJson()
	{
		return array_merge(array(
			'status' => $this->jsonStatus,
			'data' => $this->jsonData,
			'error_message' => $this->jsonErrorMessage,
			'warning_message' => $this->jsonWarningMessage,
			'permissions' => $this->jsonPermissions
		), $this->jsonParts);
	}

}
