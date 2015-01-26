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

namespace Supra\Package\CmsAuthentication;

use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Event\KernelEvent;
use Supra\Core\Package\AbstractSupraPackage;
use Supra\Package\CmsAuthentication\Application\CmsAuthenticationApplication;
use Supra\Package\CmsAuthentication\Command\GroupsAddCommand;
use Supra\Package\CmsAuthentication\Command\GroupsListCommand;
use Supra\Package\CmsAuthentication\Command\GroupsRemoveCommand;
use Supra\Package\CmsAuthentication\Command\GroupsUpdateCommand;
use Supra\Package\CmsAuthentication\Command\UsersAddCommand;
use Supra\Package\CmsAuthentication\Command\UsersListCommand;
use Supra\Package\CmsAuthentication\Command\UsersRemoveCommand;
use Supra\Package\CmsAuthentication\Command\UsersUpdateCommand;
use Supra\Package\CmsAuthentication\Event\Listener\CmsAuthenticationRequestListener;
use Supra\Package\CmsAuthentication\Event\Listener\CmsAuthenticationResponseListener;
use Symfony\Component\Security\Core\Authentication\AuthenticationProviderManager;
use Symfony\Component\Security\Core\Authentication\Provider\AnonymousAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Provider\DaoAuthenticationProvider;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Core\User\ChainUserProvider;
use Symfony\Component\Security\Core\User\UserChecker;

class SupraPackageCmsAuthentication extends AbstractSupraPackage
{
	public function inject(ContainerInterface $container)
	{
		$configuration = $this->loadConfiguration($container);

		//register commands
		$container->getConsole()->add(new UsersListCommand());
		$container->getConsole()->add(new UsersUpdateCommand());
		$container->getConsole()->add(new UsersAddCommand());
		$container->getConsole()->add(new UsersRemoveCommand());
		$container->getConsole()->add(new GroupsListCommand());
		$container->getConsole()->add(new GroupsAddCommand());
		$container->getConsole()->add(new GroupsRemoveCommand());
		$container->getConsole()->add(new GroupsUpdateCommand());

		$container[$this->name.'.request_listener'] = function () {
			return new CmsAuthenticationRequestListener();
		};

		$container[$this->name.'.response_listener'] = function () {
			return new CmsAuthenticationResponseListener();
		};

		$container->getEventDispatcher()
			->addListener(KernelEvent::REQUEST, array($container[$this->name.'.request_listener'], 'listen'));
		$container->getEventDispatcher()
			->addListener(KernelEvent::RESPONSE, array($container[$this->name.'.response_listener'], 'listen'));

		//routing
		$container->getRouter()->loadConfiguration(
			$container->getApplication()->locateConfigFile($this, 'routes.yml')
		);

		//applications
		$container->getApplicationManager()->registerApplication(new CmsAuthenticationApplication());

		//we need to inject shared users em to doctrine if provided
		if ($configuration['users']['shared_connection']) {
			$doctrineConfig = $container->getApplication()->getConfigurationSection('framework');

			$doctrineConfig['doctrine']['entity_managers']['shared'] = array(
				'connection' => 'shared',
				'event_manager' => 'public',
				'configuration' => 'default'
			);

			$doctrineConfig['doctrine']['connections']['shared'] = $configuration['users']['shared_connection'];

			$container->getApplication()->setConfigurationSection('framework', $doctrineConfig);
		}
	}

	public function finish(ContainerInterface $container)
	{
		$container->setParameter('cms_authentication.provider_key', 'cms_authentication');

		$container['cms_authentication.users.voters'] = function (ContainerInterface $container) {
			$voters = array();

			foreach ($container->getParameter('cms_authentication.users.voters') as $id) {
				$voters[] = $container[$id];
			}

			return $voters;
		};

		$container['cms_authentication.users.access_decision_manager'] = function (ContainerInterface $container) {
			return new AccessDecisionManager($container['cms_authentication.users.voters']);
		};

		$container['cms_authentication.encoder_factory'] = function (ContainerInterface $container) {
			$encoders = array();

			foreach ($container->getParameter('cms_authentication.users.password_encoders') as $user => $encoderClass) {
				$encoders[$user] = new $encoderClass();
			}

			return new EncoderFactory($encoders);
		};

		$container['cms_authentication.users.authentication_manager'] = function (ContainerInterface $container) {
			$providers = array();

			foreach ($container->getParameter('cms_authentication.users.user_providers') as $type => $providersDefinition) {
				if ($type != 'doctrine') {
					throw new \Exception('Only "doctrine" user providers are allowed now');
				}

				foreach ($providersDefinition as $name => $providerDefinition) {
					$provider = $container->getDoctrine()->getManager($providerDefinition['em'])
						->getRepository($providerDefinition['entity']);
					$provider->setDefaultDomain($container->getParameter('cms_authentication.users.default_domain'));

					$providers[] = $provider;
				}

				$chainProvider = new ChainUserProvider($providers);

				$realProviders = array(
					new AnonymousAuthenticationProvider(uniqid()),
					new DaoAuthenticationProvider(
						$chainProvider,
						new UserChecker(),
						$container->getParameter('cms_authentication.provider_key'),
						$container['cms_authentication.encoder_factory']
					)
				);

				return new AuthenticationProviderManager($realProviders);
			}

			return new AuthenticationProviderManager($providers);
		};

		$container['security.context'] = function (ContainerInterface $container) {
			return new SecurityContext(
				$container['cms_authentication.users.authentication_manager'],
				$container['cms_authentication.users.access_decision_manager']
			);
		};
	}

}
