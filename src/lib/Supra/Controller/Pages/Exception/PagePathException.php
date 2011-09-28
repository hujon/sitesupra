<?php

namespace Supra\Controller\Pages\Exception;

use Supra\Controller\Pages\Entity\PageLocalization;

/**
 * Thrown on invalid page path
 */
class PagePathException extends RuntimeException
{
	/**
	 * @var PageLocalization
	 */
	protected $pageLocalization;
	
	/**
	 * @param string $message
	 * @param PageLocalization $pageLocalization
	 */
	public function __construct($message, PageLocalization $pageLocalization)
	{
		$this->pageLocalization = $pageLocalization;
	}

	/**
	 * @return PageLocalization
	 */
	public function getPageLocalization()
	{
		return $this->pageLocalization;
	}

}
