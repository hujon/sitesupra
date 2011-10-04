<?php

namespace Supra\Controller\Pages\Configuration;

use Supra\Controller\Pages\BlockControllerCollection;
use Supra\Loader\Loader;
use Supra\Configuration\ConfigurationInterface;

/**
 * Block configuration class
 * @author Dmitry Polovka <dmitry.polovka@videinfra.com>
 */
class BlockControllerConfiguration implements ConfigurationInterface
{
	/**
	 * Autogenerated from block classname if isn't set manually
	 * @var string
	 */
	public $id;
	
	/**
	 * @var string
	 */
	public $title;
	
	/**
	 * @var string
	 */
	public $description;
	
	/**
	 * Local icon path
	 * @var string
	 */
	public $icon = 'icon.png';
	
	/**
	 * Full icon web path, autogenerated if empty
	 * @var string
	 */
	public $iconWebPath;
	
	/**
	 * CMS classname for the block
	 * @var string
	 */
	public $cmsClassname = 'Editable';
	
	/**
	 * Block controller class name
	 * @var string
	 */
	public $controllerClass;
	
	/**
	 * Adds block configuration to block controller collection
	 */
	public function configure()
	{
		if (empty($this->id)) {
			$id = str_replace('\\', '_', $this->controllerClass);
			$this->id = $id;
		}
		
		if (empty($this->iconWebPath)) {
			$this->iconWebPath = $this->getIconWebPath();
		}
		
		BlockControllerCollection::getInstance()
				->addConfiguration($this);
	}
	
	/**
	 * Return icon webpath
	 * @return string
	 */
	private function getIconWebPath()
	{
		$file = Loader::getInstance()->findClassPath($this->controllerClass);
		$dir = dirname($file);
		$iconPath = $dir . '/' . $this->icon;

		if (strpos($iconPath, SUPRA_WEBROOT_PATH) === 0) {
			$iconPath = substr($iconPath, strlen(SUPRA_WEBROOT_PATH) - 1);
		} else {
			$iconPath = null;
		}
		
		return $iconPath;
	}
}