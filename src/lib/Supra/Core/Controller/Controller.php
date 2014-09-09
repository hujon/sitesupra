<?php

namespace Supra\Core\Controller;

use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

abstract class Controller implements ContainerAware
{
	/**
	 * DI container
	 *
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * Current package class name
	 *
	 * @var string
	 */
	protected $package;

	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}

	public function renderResponse($template, $parameters = array())
	{
		$response = new Response();

		$response->setContent($this->render($template, $parameters));

		return $response;
	}

	public function render($template, $parameters)
	{
		if (strpos($template, ':') === false) {
			//there is no package name, we should add it
			$template = $this->getPackageName() . ':' . $template;
		}

		return $this->container->getTemplating()
			->render($template, $parameters);
	}

	protected function getPackageName()
	{
		$class = get_class($this);

		$class = explode('\\', $class);

		$class = array_slice($class, -3); //we expect that namespace ends with PackageName/Controller

		return $class[0];
	}
}
