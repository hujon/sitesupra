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

use MediaEmbed\MediaEmbed;
use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Package\Cms\Editable\Filter\FilterInterface;
use Supra\Package\Cms\Entity\BlockProperty;
use Supra\Package\Cms\Entity\ReferencedElement\MediaReferencedElement;
use Supra\Package\Cms\Entity\ReferencedElement\ReferencedElementUtils;
use Supra\Package\Cms\Entity\ReferencedElement\LinkReferencedElement;
use Supra\Package\Cms\Entity\ReferencedElement\ImageReferencedElement;
use Supra\Package\Cms\Html\HtmlTagStart;
use Supra\Package\Cms\Html\HtmlTagEnd;
use Supra\Package\Cms\Html\HtmlTag;
use Supra\Package\Cms\Pages\Markup;
use Supra\Package\Cms\Pages\Editable\BlockPropertyAware;

/**
 * Parses supra markup tags inside the HTML content.
 */
class HtmlFilter implements FilterInterface, BlockPropertyAware, ContainerAware
{
	/**
	 * @var BlockProperty
	 */
	protected $blockProperty;

	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * @param string $content
	 * @param array $options
	 * @return string
	 */
	public function filter($content, array $options = array())
	{
		$elements = array();
		
		foreach ($this->blockProperty->getMetadata() as $key => $item) {
			$elements[$key] = $item->getReferencedElement();
		}

		return $this->parseSupraMarkup($content, $elements);
	}

	/**
	 * Replace image/link supra tags with real elements.
	 *
	 * @param string $value
	 * @param array $metadataElements
	 * @return string
	 */
	protected function parseSupraMarkup($value, array $metadataElements = array())
	{
		$tokenizer = new Markup\DefaultTokenizer($value);

		$tokenizer->tokenize();

		$result = array();

		foreach ($tokenizer->getElements() as $element) {

			if ($element instanceof Markup\HtmlElement) {
				$result[] = $element->getContent();

			} elseif ($element instanceof Markup\SupraMarkupImage) {

				if (isset($metadataElements[$element->getId()])) {
					$result[] = (string) $this->parseSupraImage($metadataElements[$element->getId()]);
				}
//
			} elseif ($element instanceof Markup\SupraMarkupVideo) {

				if (isset($metadataElements[$element->getId()])) {
					$result[] = (string) $this->parseSupraMedia($metadataElements[$element->getId()]);
				}

			} elseif ($element instanceof Markup\SupraMarkupLinkStart) {
				
				if (isset($metadataElements[$element->getId()])) {
					$result[] = (string) $this->parseSupraLinkStart($metadataElements[$element->getId()]);
				}

			} elseif ($element instanceof Markup\SupraMarkupLinkEnd) {
				$result[] = (string) $this->parseSupraLinkEnd();
			}
		}

		return join('', $result);
	}

	/**
	 * Parse supra.link, return beginning part of referenced link element.
	 * 
	 * @param LinkReferencedElement $link
	 * @return HtmlTagStart
	 */
	protected function parseSupraLinkStart(LinkReferencedElement $link)
	{
		$tag = new HtmlTagStart('a');

		$title = ReferencedElementUtils::getLinkReferencedElementTitle(
				$link,
				$this->container->getDoctrine()->getManager(),
				$this->container->getLocaleManager()->getCurrentLocale()
		);

		// @TODO: what if we failed to obtain the URL?
		$url = ReferencedElementUtils::getLinkReferencedElementUrl(
				$link,
				$this->container->getDoctrine()->getManager(),
				$this->container->getLocaleManager()->getCurrentLocale()
		);

		$tag->setAttribute('target', $link->getTarget())
				->setAttribute('title', $title)
				->setAttribute('href', $url)
				->setAttribute('class', $link->getClassName())
		;

		switch ($link->getResource()) {
			case LinkReferencedElement::RESOURCE_FILE:
				$tag->setAttribute('target', '_blank');
				break;
		}

		return $tag;
	}

	/**
	 * Returns closing tag for referenced link element.
	 * 
	 * @return HtmlTagEnd
	 */
	protected function parseSupraLinkEnd()
	{
		return new HtmlTagEnd('a');
	}

	/**
	 * Parse supra.image
	 * 
	 * @param ImageReferencedElement $imageData
	 * @return null|HtmlTag
	 */
	protected function parseSupraImage(ImageReferencedElement $imageData)
	{
		$imageId = $imageData->getImageId();

		$fileStorage = $this->container['cms.file_storage'];
		/* @var $fileStorage \Supra\Package\Cms\FileStorage\FileStorage */

		$image = $fileStorage->findImage($imageId);

		if ($image === null) {
			return null;
		}

		$sizeName = $imageData->getSizeName();

		$size = $image->findImageSize($sizeName);

		if ($size === null) {
			$this->container->getLogger()->warn("Image [{$imageId}] size [{$sizeName}] not found.");
			return null;
		}

		$tag = new HtmlTag('img');

		if ($size->isCropped()) {
			$width = $size->getCropWidth();
			$height = $size->getCropHeight();
		} else {
			$width = $size->getWidth();
			$height = $size->getHeight();
		}

		$src = $fileStorage->getWebPath($image, $size);

		$tag->setAttribute('src', $src);

		$align = $imageData->getAlign();
		if (!empty($align)) {
			if ($align === 'left') {
				$tag->addClass('pull-left');
			} else if ($align === 'right') {
				$tag->addClass('pull-right');
			} else if ($align === 'center') {
				$tag->addClass('center-block');
				$tag->setAttribute('style', "width: {$width}px;");
			}
		}

		if (!empty($width)) {
			$tag->setAttribute('width', $width);
		}

		if (!empty($height)) {
			$tag->setAttribute('height', $height);
		}

		$title = trim($imageData->getTitle());
		if (!empty($title)) {
			$tag->setAttribute('title', $title);
		}

		$tag->setAttribute('alt', trim($imageData->getAlternateText()));

		return $tag;
	}

	/**
	 * @param MediaReferencedElement $mediaElement
	 * @return null|string
	 */
	protected function parseSupraMedia(MediaReferencedElement $mediaElement)
	{
		$mediaEmbed = $this->container['cms.media_embed'];
		/* @var $mediaEmbed MediaEmbed */

		$mediaObject = $mediaEmbed->parseUrl($mediaElement->getUrl());

		if ($mediaObject === null) {
			return null;
		}

		$mediaObject->setWidth($mediaElement->getWidth());
		$mediaObject->setHeight($mediaElement->getHeight());

		return $mediaObject->getEmbedCode();
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
