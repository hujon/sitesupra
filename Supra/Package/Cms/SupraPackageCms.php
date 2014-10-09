<?php

namespace Supra\Package\Cms;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\Driver\PDOMySql;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Package\AbstractSupraPackage;
use Supra\Core\Locale\LocaleManager;
use Supra\Core\Locale\Detector\ParameterDetector;
use Supra\Package\Cms\Application\CmsDashboardApplication;
use Supra\Package\Cms\Application\CmsPagesApplication;
use Supra\Package\Cms\Pages\Application\PageApplicationManager;
use Supra\Package\Cms\Pages\Application\BlogPageApplication;
use Supra\Package\Cms\Pages\Application\GlossaryPageApplication;
use Supra\Package\Cms\Pages\Layout\Theme\DefaultThemeProvider;
use Supra\Package\Cms\Pages\Listener\VersionedEntityRevisionSetterListener;
use Supra\Package\Cms\Pages\Listener\VersionedEntitySchemaListener;
use Supra\Package\Cms\Controller\PageController;
use Supra\Package\Cms\Pages\Request\PageRequestView;
use Supra\Package\Cms\Pages\Request\PageRequestEdit;
use Supra\Package\Cms\Pages\Block\BlockCollection;
use Supra\Package\Cms\Pages\Layout\Processor\TwigProcessor;
use Supra\Package\Cms\Pages\Block\BlockGroupConfiguration;
use Supra\Package\Cms\Doctrine\Subscriber\TimestampableListener;

class SupraPackageCms extends AbstractSupraPackage
{
	public function inject(ContainerInterface $container)
	{
		$this->loadConfiguration($container);

		//routing
		$container->getRouter()->loadConfiguration(
				$container->getApplication()->locateConfigFile($this, 'routes.yml')
			);

		$container->getApplicationManager()->registerApplication(new CmsDashboardApplication());
		$container->getApplicationManager()->registerApplication(new CmsPagesApplication());

		$this->injectDraftEntityManager($container);

		// Page Apps Manager
		$container[$this->name . '.pages.page_application_manager'] = function () {

			$manager = new PageApplicationManager();
			
			$manager->registerApplication(new BlogPageApplication());
			$manager->registerApplication(new GlossaryPageApplication());

			return $manager;
		};

		$frameworkConfiguration = $container->getApplication()->getConfigurationSection('framework');

		$frameworkConfiguration['doctrine']['event_managers']['public']['subscribers'][] = 'supra.cms.doctrine.event_subscriber.timestampable';

		// Theme Provider
		$container[$this->name . '.pages.theme.provider'] = function () {
			return new DefaultThemeProvider();
		};

		// PageController specific request object
		$container[$this->name . '.pages.request.view'] = function ($container) {

			// @TODO: remove dependency from Request object.
			$request = $container->getRequest();
			return new PageRequestView($request);
		};

		// PageController specific request object
		$container[$this->name . '.pages.request.edit'] = function ($container) {

			// @TODO: remove dependency from Request object.
			$request = $container->getRequest();
			return new PageRequestEdit($request);
		};

		// PageController for backend purposes
		$container[$this->name . '.pages.controller'] = function () {
			return new PageController();
		};

		// Block collection
		$container[$this->name . '.pages.blocks.collection'] = function () {

			return new BlockCollection(array(
						new BlockGroupConfiguration('features', 'Features', true),
						new BlockGroupConfiguration('system', 'System'),
			));
		};

		// Layout processor
		$container[$this->name . '.pages.layout_processor'] = function () {
			return new TwigProcessor();
		};
	}

	public function finish(ContainerInterface $container)
	{
		// Extended Locale Manager
		$container->extend('locale.manager', function (LocaleManager $localeManager, ContainerInterface $container) {

			$localeManager->processInactiveLocales();

			$localeManager->addDetector(new ParameterDetector());

			return $localeManager;
		});
	}

	/**
	 * @param ContainerInterface $container
	 */
	private function injectDraftEntityManager(ContainerInterface $container)
	{
		// separate EventManager
		$container['doctrine.event_managers.cms'] = function (ContainerInterface $container) {
			
			$eventManager = clone $container['doctrine.event_managers.public'];
			/* @var $eventManager \Doctrine\Common\EventManager */

			$eventManager->addEventSubscriber(new VersionedEntitySchemaListener());
			$eventManager->addEventSubscriber(new VersionedEntityRevisionSetterListener());

			// @TODO: quite rudimental stuff.
			// might be easily replaced with @HasLifecycleCallbacks + @prePersist + @preUpdate
			$eventManager->addEventSubscriber(new TimestampableListener());

			return $eventManager;
		};

		// separate connection. unfortunately.
		$container['doctrine.connections.cms'] = function (ContainerInterface $container) {

			// @TODO: clone somehow default connection?
			$connection = new Connection(
				array(
					'host' => 'localhost',
					'user' => 'root',
					'password' => 'root',
					'dbname' => 'supra9'
				),
				new PDOMySql\Driver(),
				$container['doctrine.configuration'],
				$container['doctrine.event_managers.cms']
			);

			return $connection;
		};

		// entity manager
		$container['doctrine.entity_managers.cms'] = function (ContainerInterface $container) {
			return EntityManager::create(
				$container['doctrine.connections.cms'],
				$container['doctrine.configuration'],
				$container['doctrine.event_managers.cms']
			);
		};
	}
}