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

namespace Supra\Package\Cms\Pages\Block\Config;

use Supra\Package\Cms\Entity\BlockProperty;

class PropertyListConfig extends AbstractPropertyConfig implements PropertyCollectionConfig
{
	/**
	 * @var AbstractPropertyConfig
	 */
	protected $item;

	/**
	 * @var string
	 */
	protected $label;

	/**
	 * @param string $label
	 */
	public function setLabel($label)
	{
		$this->label = $label;
	}

	/**
	 * @return string
	 */
	public function getLabel()
	{
		return $this->label;
	}

	/**
	 * @param AbstractPropertyConfig $item
	 */
	public function setListItem(AbstractPropertyConfig $item)
	{
		$item->setParent($this);
		$this->item = $item;
	}

	/**
	 * @return AbstractPropertyConfig
	 */
	public function getListItem()
	{
		return $this->item;
	}

	/**
	 * {@inheritDoc}
	 */
	public function isMatchingProperty(BlockProperty $property)
	{
		return $property->getHierarchicalName() == $this->name;
	}

	/**
	 * {@inheritDoc}
	 */
	public function createProperty($name)
	{
		return new BlockProperty($name);
	}

	/**
	 * {@inheritDoc}
	 * @throws \LogicException
	 */
	public function getEditable()
	{
		throw new \LogicException('Collections have no editables.');
	}
}