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

namespace Supra\Package\Cms\Entity\Abstraction;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Supra\Package\Cms\Entity\BlockProperty;
use Supra\Package\Cms\Entity\PageBlock;
use Supra\Package\Cms\Entity\TemplateBlock;

/**
 * Block entity abstraction
 * 
 * @Entity
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({
 *		"template"	= "Supra\Package\Cms\Entity\TemplateBlock",
 *		"page"		= "Supra\Package\Cms\Entity\PageBlock"
 * })
 */
abstract class Block extends Entity
{
	/**
	 * @Column(type="string", name="component")
	 * 
	 * @var string
	 */
	protected $componentClass;

	/**
	 * @Column(type="integer")
	 *
	 * @var int
	 */
	protected $position;

	/**
	 * @ManyToOne(targetEntity="PlaceHolder", inversedBy="blocks")
	 * @JoinColumn(name="place_holder_id", referencedColumnName="id")
	 * 
	 * @var PlaceHolder
	 */
	protected $placeHolder;

	/**
	 * Left here just because cascade in remove.
	 *
	 * @OneToMany(
	 *		targetEntity="Supra\Package\Cms\Entity\BlockProperty",
	 *		mappedBy="block",
	 *		cascade={"persist", "remove"}
	 * )
	 * 
	 * @var Collection 
	 */
	protected $blockProperties;

	/**
	 * This property is always false for page block.
	 * 
	 * @Column(type="boolean", nullable=true)
	 * 
	 * @var boolean
	 */
	protected $locked = false;

	/**
	 * Create block properties collection
	 */
	public function __construct()
	{
		parent::__construct();
		$this->blockProperties = new ArrayCollection();
	}

	/**
	 * Get locked value, always false for page blocks.
	 * 
	 * @return boolean
	 */
	public function getLocked()
	{
		return false;
	}

	/**
	 * @return bool
	 */
	public function isLocked()
	{
		return $this->getLocked() === true;
	}

	/**
	 * Gets place holder
	 * @return PlaceHolder
	 */
	public function getPlaceHolder()
	{
		return $this->placeHolder;
	}

	/**
	 * Sets place holder
	 * @param PlaceHolder $placeHolder
	 */
	public function setPlaceHolder(PlaceHolder $placeHolder)
	{
		$this->placeHolder = $placeHolder;
		$this->placeHolder->addBlock($this);
	}

	/**
	 * @return string
	 */
	public function getComponentClass()
	{
		return $this->componentClass;
	}

	/**
	 * @param string $componentClass
	 */
	public function setComponentClass($componentClass)
	{
		$this->componentClass = trim($componentClass, '\\');
	}

	/**
	 * Get component class name safe for HTML node ID generation.
	 * 
	 * @return string
	 */
	public function getComponentName()
	{
		return $this->getComponentNameFromClassName($this->componentClass);
	}

	/**
	 * @param string $className
	 * @return string
	 */
	public function getComponentNameFromClassName($className)
	{
		return str_replace('\\', '_', $className);
	}

	/**
	 * Set normalized component name, converted to classname.
	 * 
	 * @param string $componentName
	 */
	public function setComponentName($componentName)
	{
		$this->componentClass = str_replace('_', '\\', $componentName);
	}

	/**
	 * Get order number
	 * @return int
	 */
	public function getPosition()
	{
		return $this->position;
	}

	/**
	 * Set order number
	 * @param int $position
	 */
	public function setPosition($position)
	{
		$this->position = $position;
	}

	/**
	 * @return Collection
	 */
	public function getBlockProperties()
	{
		return $this->blockProperties;
	}
	
	/**
	 * Whether the block is inside one of place holder Ids provided
	 * @param array $placeHolderIds
	 * @return boolean
	 */
	public function inPlaceHolder(array $placeHolderIds)
	{
		return in_array(
				$this->getPlaceHolder()->getId(),
				$placeHolderIds,
				true
		);
	}

	/**
	 * Creates new instance based on the discriminator of the base entity.
	 *
	 * @param Localization $base
	 * @return Block
	 */
	public static function factory(Localization $base, Block $source = null)
	{
		$block = null;

		switch ($base::DISCRIMINATOR) {
			case self::TEMPLATE_DISCR:
				$block = new TemplateBlock();
				break;
			case self::PAGE_DISCR:
			case self::APPLICATION_DISCR:
				$block = new PageBlock();
				break;
			default:
				throw new \LogicException("Not recognized discriminator value for entity [{$base}].");
		}

		if ($source !== null) {
			$block->setComponentClass($source->getComponentClass());
			$block->setPosition($source->getPosition());

			foreach ($source->getBlockProperties() as $blockProperty) {
				/* @var $blockProperty BlockProperty */

				$newBlockProperty = clone $blockProperty;

				$newBlockProperty->setLocalization($base);
				$newBlockProperty->setBlock($block);

				$block->getBlockProperties()
						->add($newBlockProperty);
			}
		}

		return $block;
	}

	/**
	 * @inheritDoc
	 */
	public function getVersionedParent()
	{
		return $this->placeHolder;
	}
	
}
