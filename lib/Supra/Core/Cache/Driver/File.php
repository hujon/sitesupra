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

namespace Supra\Core\Cache\Driver;

class File implements DriverInterface
{
	/**
	 * Filesystem root folder
	 *
	 * @var string
	 */
	protected $prefix;

	public function __construct($prefix)
	{
		if (!is_dir($prefix) && !mkdir($prefix, 0777, true)) {
			throw new \Exception(sprintf('Directory "%s" does not exist and can not be created', $prefix));
		}

		if (!is_writable($prefix)) {
			throw new \Exception(sprintf('Directory "%s" is not writable', $prefix));
		}

		$this->prefix = $prefix;
	}

	/**
	 * {@inheritdoc}
	 */
	public function set($prefix, $key, $value, $timestamp = 0, $ttl = 0)
	{
		$data = serialize(array(
			'timestamp' => $timestamp,
			'ttl' => $ttl == 0 ? 0 : time() + $ttl,
			'data' => $value
		));

		$file = $this->getFilename($prefix, $key);

		$dir = dirname($file);

		if (!is_dir($dir)) {
			mkdir($dir, 0777, true);
		}

		file_put_contents($file, $data);
	}

	/**
	 * {@inheritdoc}
	 */
	public function get($prefix, $key, $timestamp = 0)
	{
		$file = $this->getFilename($prefix, $key);

		if (!is_readable($file)) {
			return false;
		}

		$data = unserialize(file_get_contents($file));

		if ($data['ttl'] != 0 && $data['ttl'] < time()) {
			return false;
		}

		if ($timestamp != 0 && $timestamp > $data['timestamp']) {
			return false;
		}

		return $data['data'];
	}

	/**
	 * {@inheritdoc}
	 */
	public function has($prefix, $key, $timestamp = 0)
	{
		return (bool)$this->get($prefix, $key, $timestamp);
	}

	/**
	 * {@inheritdoc}
	 */
	public function delete($prefix, $key)
	{
		$file = $this->getFilename($prefix, $key);

		if (is_file($file)) {
			unlink($file);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function clear($prefix = null)
	{
		if ($prefix) {
			if (is_dir($this->prefix.DIRECTORY_SEPARATOR . $prefix)) {
				$this->removeDirectory($this->prefix.DIRECTORY_SEPARATOR . $prefix);
			}
		} else {
			foreach (glob($this->prefix.DIRECTORY_SEPARATOR.'*') as $directory) {
				if (is_dir($directory)) {
					$this->removeDirectory($directory);
				}
			}
		}
	}

	protected function removeDirectory($dir)
	{
		foreach (glob($dir.'/*') as $obj) {
			if (is_dir($obj)) {
				$this->removeDirectory($obj);
			} else {
				unlink($obj);
			}
		}

		rmdir($dir);
	}

	protected function getFilename($prefix, $key)
	{
		if (!is_scalar($key)) {
			$key = serialize($key);
		}

		$key = md5($key);

		$folder = substr($key, 0, 2);

		$name = substr($key, 2);

		return implode(DIRECTORY_SEPARATOR,
			array(
				$this->prefix,
				$prefix,
				$folder,
				$name
			)
		) . $this->getExtension();
	}

	protected function getExtension()
	{
		return '.cache';
	}
}