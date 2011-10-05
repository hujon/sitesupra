<?php

namespace Supra\Controller\Pages\Entity;

use Supra\Controller\Pages\Set\PageSet;

/**
 * NOT persisting group localization object
 */
class GroupLocalization extends Abstraction\Localization
{
	public function __construct($locale, GroupPage $groupPage)
	{
		parent::__construct($locale);
		$this->setTitle($groupPage->getTitle());
		$this->master = $groupPage;
	}
	
	public function getTemplateHierarchy()
	{
		return new PageSet(array($this));
//		throw new \Supra\Controller\Pages\Exception\RuntimeException("Template hierarchy cannot be called for a group page");
	}
	
	public function getPathPart()
	{
		return null;
	}
	
	public function getPath()
	{
		return null;
	}
	
	public function getParentPath()
	{
		$parent = $this->getParent();
		
		if (empty($parent)) {
			return null;
		}
		
		$path = $parent->getPath();
		
		if (is_null($path)) {
			$path = $parent->getParentPath();
		}
		
		return $path;
	}
}
