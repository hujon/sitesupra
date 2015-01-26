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

namespace Supra\Package\Cms\Pages\Twig;

use Supra\Package\Cms\Entity\Abstraction\Localization;
use Supra\Package\Cms\Entity\PageLocalization;
use Supra\Package\Cms\Pages\Block\BlockExecutionContext;
use Supra\Package\Cms\Pages\PageExecutionContext;
use Supra\Package\Cms\Pages\Request\PageRequestEdit;
use Supra\Package\Cms\Html\HtmlTag;
use Supra\Package\Cms\Uri\Path;

class PageExtension extends \Twig_Extension
{
	/**
	 * @var BlockExecutionContext
	 */
	private $blockExecutionContext;

	/**
	 * @var PageExecutionContext;
	 */
	private $pageExecutionContext;

	/**
	 * {@inheritDoc}
	 */
	public function getName()
	{
		return 'supraPage';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getFunctions()
	{
		return array(
			new \Twig_SimpleFunction('collection', null, array(
						'node_class' => 'Supra\Package\Cms\Pages\Twig\BlockPropertyListNode',
						'is_safe' => array('html')
				)
			),
			new \Twig_SimpleFunction('list', null, array(
						'node_class' => 'Supra\Package\Cms\Pages\Twig\BlockPropertyListNode',
						'is_safe' => array('html')
				)
			),
			new \Twig_SimpleFunction('set', null, array(
					'node_class' => 'Supra\Package\Cms\Pages\Twig\BlockPropertySetNode',
					'is_safe' => array('html')
				)
			),
			new \Twig_SimpleFunction('property', null, array('node_class' => 'Supra\Package\Cms\Pages\Twig\BlockPropertyNode', 'is_safe' => array('html'))),
			new \Twig_SimpleFunction('isPropertyEmpty', null, array('node_class' => 'Supra\Package\Cms\Pages\Twig\BlockPropertyValueTestNode', 'is_safe' => array('html'))),
			new \Twig_SimpleFunction('placeHolder', null, array('node_class' => 'Supra\Package\Cms\Pages\Twig\PlaceHolderNode', 'is_safe' => array('html'))),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getFilters()
	{
		return array(
			new \Twig_SimpleFilter('decorate', array($this, 'decorateHtmlTag'), array('is_safe' => array('html'))),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getGlobals()
	{
		return array(
			'supraPage'	=> $this
		);
	}

	/**
	 * Whether the passed link is actual - is some descendant opened currently
	 * @param string $path
	 * @param boolean $strict
	 * @return boolean
	 */
	public function isActive($path, $strict = false)
	{
		// Check if path is relative
		$pathData = parse_url($path);
		if ( ! empty($pathData['scheme'])
			|| ! empty($pathData['host'])
			|| ! empty($pathData['port'])
			|| ! empty($pathData['user'])
			|| ! empty($pathData['pass'])
		) {
			return false;
		}

		$path = $pathData['path'];

		$localization = $this->getPage();

		if ( ! $localization instanceof PageLocalization) {
			return false;
		}

		// Remove locale prefix
		$path = preg_replace(
			sprintf('#^(/?)%s(/|$)#', preg_quote($localization->getLocaleId())),
			'$1',
			$path
		);

		$checkPath = new Path($path);
		$currentPath = $localization->getPath();

		if ($currentPath === null) {
			return false;
		}

		return $strict
			? $checkPath->equals($currentPath)
			: $currentPath->startsWith($checkPath);
	}

	/**
	 * @param string $name
	 * @param array $options
	 * @return mixed
	 */
	public function getPropertyValue($name, array $options = array())
	{
		return $this->getBlockExecutionContext()
				->controller->getPropertyViewValue($name, $options);
	}

	/**
	 * Gets if specified property value is empty.
	 * 
	 * You cannot test the value directly in twig, since in CMS view mode,
	 * properties with inline editable always will have additional wrappers.
	 *
	 * @param string $name
	 * @return bool
	 */
	public function isPropertyValueEmpty($name)
	{
		$value = $this->getBlockExecutionContext()
				->controller->getProperty($name)->getValue();

		return empty($value);
	}

	/**
	 * @return bool
	 */
	public function isCmsRequest()
	{
		if ($this->blockExecutionContext) {
			return $this->blockExecutionContext
					->request instanceof PageRequestEdit;
		}
		
		return $this->getPageExecutionContext()
				->request instanceof PageRequestEdit;
	}

	/**
	 * @param BlockExecutionContext $context
	 */
	public function setBlockExecutionContext(BlockExecutionContext $context)
	{
		$this->blockExecutionContext = $context;
	}

	/**
	 * @param PageExecutionContext $context
	 */
	public function setPageExecutionContext(PageExecutionContext $context)
	{
		$this->pageExecutionContext = $context;
	}

	/**
	 * @param HtmlTag $tag
	 * @param array $attributes
	 *
	 * @return null|HtmlTag
	 */
	public function decorateHtmlTag($tag, array $attributes)
	{
		if (! $tag instanceof HtmlTag) {
			return null;
		}

		foreach ($attributes as $name => $value) {
			$tag->setAttribute($name, $value);
		}

		return $tag;
	}

	/**
	 * @return Localization
	 */
	public function getPage()
	{
		return $this->getPageExecutionContext()->request->getLocalization();
	}

	/**
	 * @return PageExecutionContext
	 * @throws \LogicException
	 */
	private function getPageExecutionContext()
	{
		if ($this->pageExecutionContext === null) {
			throw new \LogicException('Not in page controller execution context.');
		}
		return $this->pageExecutionContext;
	}

	/**
	 * @return BlockExecutionContext
	 * @throws \LogicException
	 */
	private function getBlockExecutionContext()
	{
		if ($this->blockExecutionContext === null) {
			throw new \LogicException('Not in block controller execution context.');
		}
		return $this->blockExecutionContext;
	}
}
