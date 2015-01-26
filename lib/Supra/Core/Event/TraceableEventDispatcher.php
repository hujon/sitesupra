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

namespace Supra\Core\Event;

use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;

class TraceableEventDispatcher extends EventDispatcher implements ContainerAware
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	protected $eventTrace;

	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}

	public function getEventTrace()
	{
		return $this->eventTrace;
	}

	public function dispatch($eventName, Event $event = null)
	{
		$this->logEvent($eventName, $event);

		$eventId = 'event_'.$eventName . (is_object($event) ? spl_object_hash($event) : 'null');

		//log this into debugbar if provided
		if (isset($this->container['debug_bar.debug_bar'])) {
			$this->container['debug_bar.debug_bar']['time']
				->startMeasure($eventId, 'Event: '.$eventName);
		}

		$listeners = array();

		foreach ($this->getListeners($eventName) as $callable) {
			if ($callable instanceof \Closure) {
				$listeners[] = 'Closure';
			} elseif (is_array($callable) && count($callable) == 2) {
				$listeners[] = get_class($callable[0]);
			} else {
				$listeners[] = 'Unknown';
			}
		}

		$this->eventTrace[] = array(
			'name' => $eventName,
			'timestamp' => microtime(true),
			'listeners' => $listeners,
			'event' => $event
		);

		$result = parent::dispatch($eventName, $event);

		if (isset($this->container['debug_bar.debug_bar'])) {
			if ($this->container['debug_bar.debug_bar']['time']->hasStartedMeasure($eventId)) {
				//yes, this can happen because of stopPropagation() call
				$this->container['debug_bar.debug_bar']['time']
					->stopMeasure($eventId);
			}
		}

		return $result;
	}

	protected function logEvent($name, $event)
	{
		//log this into monolog
		$context = array();

		if ($event instanceof RequestResponseEvent) {
			$context['url'] = $event->getRequest()->getPathInfo();
		}

		$this->container->getLogger()->addDebug(sprintf('Processing event "%s"', $name), $context);
	}
}
