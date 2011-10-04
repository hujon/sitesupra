<?php

namespace Supra\Router\Configuration;

use Supra\Router\RouterInterface;
use Supra\Router\RouterAbstraction;
use Supra\Configuration\ConfigurationInterface;

/**
 * RouterConfiguration
 */
class RouterConfiguration implements ConfigurationInterface
{
	/**
	 * @var string
	 */
	public $class = 'Supra\Router\UriRouter';
	
	/**
	 * @var string
	 */
	public $url = '/';
	
	/**
	 * @var string
	 */
	public $controller;
	
	/**
     * Default Controller execution priority
     */

	public $priority = RouterAbstraction::PRIORITY_MEDIUM;
		
	/**
	 * @return RouterInterface
	 */
	public function configure()
	{
		$router = new $this->class($this->url);
		$router->setPriorityDiff($this->priority);
		
		return $router;
	}
}