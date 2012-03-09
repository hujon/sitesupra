<?php

namespace Supra\Database\Configuration;

use Supra\Controller\Pages\PageController;
use Doctrine\Common\EventManager;
use Supra\Controller\Pages\Listener;

/**
 * 
 */
class PublicEntityManagerConfiguration extends EntityManagerConfiguration
{
	public function configure()
	{
		$this->name = PageController::SCHEMA_PUBLIC;
		$this->objectRepositoryBindings[] = '';
		
		parent::configure();
	}
	
	protected function configureEventManager(EventManager $eventManager)
	{
		parent::configureEventManager($eventManager);
		
		$eventManager->addEventSubscriber(new Listener\PagePathGenerator());
		$eventManager->addEventSubscriber(new Listener\PageGroupCacheDropListener());
	}

}