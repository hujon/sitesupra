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

namespace Supra\Core\Doctrine\Subscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\Common\Annotations\SimpleAnnotationReader;
use Supra\Core\Doctrine\Annotation\DetachedDiscriminators;
use Supra\Core\Doctrine\Annotation\DetachedDiscriminatorValue;

class DetachedDiscriminatorHandler implements EventSubscriber
{
	/**
	 * @var SimpleAnnotationReader
	 */
	protected $annotationReader;

	/**
	 * @var bool
	 */
	protected $ignoreDetachedDiscriminators = false;
	
	/**
	 * @var array
	 */
	protected $discriminatorMaps = array();

	/**
	 * {@inheritDoc}
	 */
	public function getSubscribedEvents()
	{
		return array(Events::loadClassMetadata);
	}

	/**
	 * * @param LoadClassMetadataEventArgs $eventArgs
	 */
	public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
	{
		$reflection = $eventArgs->getClassMetadata()
				->getReflectionClass();
		
		if (empty($reflection)) {
			return;
		}

		$reader = $this->getAnnotationReader();
		
		$annotation = $reader->getClassAnnotation($reflection, DetachedDiscriminatorValue::CN());

		if ($annotation instanceof DetachedDiscriminatorValue) {
			$this->handleDetachedDisciminatorValue($eventArgs, $annotation);
		} 

		$annotation = $reader->getClassAnnotation($reflection, DetachedDiscriminators::CN());

		if ($annotation instanceof DetachedDiscriminators) {
			$this->handleDetachedDiscriminators($eventArgs);
		} 
	}

	/**
	 * @param LoadClassMetadataEventArgs $eventArgs
	 * @param DetachedDiscriminatorValue $annotation
	 */
	protected function handleDetachedDisciminatorValue(LoadClassMetadataEventArgs $eventArgs, DetachedDiscriminatorValue $annotation)
	{
		$classMetadata = $eventArgs->getClassMetadata();
		/* @var $classMetadata ClassMetadataInfo */

		$discriminatorValue = $annotation->value;

		$classMetadata->addDiscriminatorMapClass($discriminatorValue, $classMetadata->name);

		$em = $eventArgs->getEntityManager();

		$factory = $em->getMetadataFactory();

		foreach ($classMetadata->parentClasses as $parentClass) {

			$parentMetadata = $em->getClassMetadata($parentClass);
			/* @var $parentMetadata ClassMetadataInfo */

			$parentMetadata->addDiscriminatorMapClass($discriminatorValue, $classMetadata->name);
			
			$this->discriminatorMaps[$parentMetadata->name] = $parentMetadata->discriminatorMap;

			$factory->getCacheDriver()->save($parentClass . '$CLASSMETADATA', $parentMetadata);
		}
	}

	/**
	 * @param LoadClassMetadataEventArgs $eventArgs 
	 */
	protected function handleDetachedDiscriminators(LoadClassMetadataEventArgs $eventArgs)
	{
		if ($this->ignoreDetachedDiscriminators) {
			return;
		}

		$this->ignoreDetachedDiscriminators = true;

		$em = $eventArgs->getEntityManager();

		$metadataFactory = $em->getMetadataFactory();

		$metadataFactory->getAllMetadata();

		$this->ignoreDetachedDiscriminators = false;
		
		$classMetadata = $eventArgs->getClassMetadata();
		/* @var $classMetadata ClassMetadataInfo */
		
		if (isset($this->discriminatorMaps[$classMetadata->name])) {	
			$localDiscriminatorMap = $this->discriminatorMaps[$classMetadata->name];
			$classMetadata->setDiscriminatorMap($localDiscriminatorMap);
		}
	}

	/**
	 * @return SimpleAnnotationReader
	 */
	protected function getAnnotationReader()
	{
		if ($this->annotationReader === null) {
			$this->annotationReader = new SimpleAnnotationReader();
			$this->annotationReader->addNamespace('Supra\Core\Doctrine\Annotation');
		}

		return $this->annotationReader;
	}
}