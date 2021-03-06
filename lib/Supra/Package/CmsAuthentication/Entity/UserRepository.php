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

namespace Supra\Package\CmsAuthentication\Entity;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserRepository extends EntityRepository implements UserProviderInterface
{
	/**
	 * Default suffix domain
	 *
	 * @var string
	 */
	protected $defaultDomain;

	/**
	 * @param string $defaultDomain
	 */
	public function setDefaultDomain($defaultDomain)
	{
		$this->defaultDomain = $defaultDomain;
	}

	/**
	 * Loads the user for the given username.
	 *
	 * This method must throw UsernameNotFoundException if the user is not
	 * found.
	 *
	 * @param string $username The username
	 *
	 * @throws \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
	 * @throws \Exception
	 * @return UserInterface
	 *
	 * @see UsernameNotFoundException
	 *
	 */
	public function loadUserByUsername($username)
	{
		if (strpos($username, '@') === false) {
			$username = $username . '@' . $this->defaultDomain;
		}

		$users = $this->findByLogin($username);

		if (count($users) > 1) {
			throw new \Exception('Error: duplicate usernames');
		}

		if (count($users) == 0) {
			throw new UsernameNotFoundException(sprintf('User with username "%s" was not found', $username));
		}

		return $users[0];
	}

	/**
	 * Refreshes the user for the account interface.
	 *
	 * It is up to the implementation to decide if the user data should be
	 * totally reloaded (e.g. from the database), or if the UserInterface
	 * object can just be merged into some internal array of users / identity
	 * map.
	 * @param UserInterface $user
	 *
	 * @return UserInterface
	 *
	 * @throws UnsupportedUserException if the account is not supported
	 */
	public function refreshUser(UserInterface $user)
	{
		throw new \Exception(__NAMESPACE__.__METHOD__.' is not yet implemented');
	}

	/**
	 * Whether this provider supports the given user class
	 *
	 * @param string $class
	 *
	 * @return Boolean
	 */
	public function supportsClass($class)
	{
		throw new \Exception(__NAMESPACE__.__METHOD__.' is not yet implemented');
	}

}
