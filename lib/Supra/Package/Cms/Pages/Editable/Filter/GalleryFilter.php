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

namespace Supra\Package\Cms\Pages\Editable\Filter;

use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Package\Cms\Editable\Filter\FilterInterface;
use Supra\Package\Cms\Entity\BlockProperty;
use Supra\Package\Cms\Entity\ReferencedElement\ImageReferencedElement;
use Supra\Package\Cms\Pages\Editable\BlockPropertyAware;

class GalleryFilter implements FilterInterface, BlockPropertyAware, ContainerAware
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * @var BlockProperty
	 */
	protected $blockProperty;

	/**
	 * {@inheritDoc}
	 * @return string
	 */
	public function filter($content, array $options = array())
	{
		$itemTemplate = ! empty($options['itemTemplate']) ? (string) $options['itemTemplate'] : '';
		$wrapperTemplate = ! empty($options['wrapperTemplate']) ? (string) $options['wrapperTemplate'] : '';

		$output = '';

		$fileStorage = $this->container['cms.file_storage'];
		/* @var $fileStorage \Supra\Package\Cms\FileStorage\FileStorage */

		foreach ($this->blockProperty->getMetadata() as $metadata) {
			/* @var $metadata \Supra\Package\Cms\Entity\BlockPropertyMetadata */

			$element = $metadata->getReferencedElement();

			if (! $element instanceof ImageReferencedElement
					|| $element->getSizeName() === null) {

				continue;
			}

			$image = $fileStorage->findImage($element->getImageId());

			if ($image === null) {
				continue;
			}

			$imageWebPath = $fileStorage->getWebPath($image, $element->getSizeName());

			// @FIXME:
			$fullSizeWebPath = null;

			$itemData = array(
				'image' 		=> '<img src="' . $imageWebPath . '" alt="' . $element->getAlternateText() . '" />',
				'imageUrl' 		=> $imageWebPath,
				'imageFullSizeUrl' => $fullSizeWebPath,
				'title' 		=> $element->getTitle(),
				'description' 	=> $element->getDescription(),
			);

			$output .= preg_replace_callback(
				'/{{\s*(image|title|description)\s*}}/',
				function ($matches) use ($itemData) {
					return $itemData[$matches[1]];
				},
				$itemTemplate
			);
		}

		return preg_replace('/{{\s*items\s*}}/', $output, $wrapperTemplate);
	}

	/**
	 * {@inheritDoc}
	 */
	public function setBlockProperty(BlockProperty $blockProperty)
	{
		$this->blockProperty = $blockProperty;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}
}