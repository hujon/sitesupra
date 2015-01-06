<?php
namespace Supra\Package\Cms\Pages\Editable\Filter;

use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Package\Cms\Editable\Filter\FilterInterface;
use Supra\Package\Cms\Entity\BlockProperty;
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
					$image = $metadataElements[$element->getId()];
					$result[] = (string) $this->parseSupraImage($image);
				}

//			} elseif ($element instanceof Markup\SupraMarkupIcon) {
//
//				if (isset($metadataElements[$element->getId()])) {
//					$icon = $metadataElements[$element->getId()];
//					$result[] = (string) $this->parseSupraIcon($icon);
//				}
//
//			} elseif ($element instanceof Markup\SupraMarkupVideo) {
//
//				if (isset($metadataElements[$element->getId()])) {
//					$video = $metadataElements[$element->getId()];
//					$result[] = (string) $this->parseSupraVideo($video);
//				}

			} elseif ($element instanceof Markup\SupraMarkupLinkStart) {
				
				if (isset($metadataElements[$element->getId()])) {
					$link = $metadataElements[$element->getId()];
					$result[] = (string) $this->parseSupraLinkStart($link);
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
			$tag->addClass('align-' . $align);

			if ($align === 'middle') {
				$tag->setAttribute('style', "width: {$width}px;");
			}
		}

		$tag->addClass($imageData->getStyle());

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

		$alternativeText = trim($imageData->getAlternativeText());
		$tag->setAttribute('alt', (!empty($alternativeText) ? $alternativeText : ''));

		/*
		 * Temporary hardcore version
		 *
		 * @TODO:
		 * 1. use depending on 'htmlEditorPlugins' parameter in cms\configuration\config.pages.yml
		 * 1a. create parameter - 'style'?			 *
		 * 2. add attributes depending on ImageReferencedElement style type
		 * 2a. create style types (constants - lightbox,...)
		 */
		$style = $imageData->getStyle();

		if ($style == ImageReferencedElement::STYLE_LIGHTBOX) {
			$tag->setAttribute('rel', 'lightbox');
			$tag->setAttribute('data-fancybox-href', $fileStorage->getWebPath($image));
		}

		return $tag;
	}

//	/**
//	 * @FIXME
//	 *
//	 * Parse supra.video
//	 * @param VideoReferencedElement $videoElement
//	 *
//	 * @return string
//	 */
//	protected function parseSupraVideo(VideoReferencedElement $videoElement)
//	{
//		$html = null;
//
//		$resource = $videoElement->getResource();
//
//		$width = $videoElement->getWidth();
//		$height = $videoElement->getHeight();
//
//		$align = $videoElement->getAlign();
//		$alignClass = (!empty($align) ? "align-$align" : '');
//
//		if ($resource == VideoReferencedElement::RESOURCE_LINK) {
//
//			$service = $videoElement->getExternalService();
//			$videoId = $videoElement->getExternalId();
//
//			$wmodeParam = null;
//			if ($this->requestType == self::REQUEST_TYPE_EDIT) {
//				$wmodeParam = 'wmode="opaque"';
//			}
//
//			if ($service == VideoReferencedElement::SERVICE_YOUTUBE) {
//				$html = "<div class=\"video $alignClass\" data-attach=\"$.fn.resize\">
//					<iframe src=\"//www.youtube.com/embed/{$videoId}?{$wmodeParam}&rel=0\" width=\"{$width}\" height=\"{$height}\" frameborder=\"0\" allowfullscreen></iframe>
//				</div>";
//			}
//			else if ($service == VideoReferencedElement::SERVICE_VIMEO) {
//				$html = "<div class=\"video $alignClass\" data-attach=\"$.fn.resize\">
//				<iframe src=\"//player.vimeo.com/video/{$videoId}?title=0&amp;byline=0&amp;portrait=0&amp;color=0&amp;api=1&amp;player_id=player{$videoId}\" id=\"player{$videoId}\" width=\"{$width}\" height=\"{$height}\" frameborder=\"0\" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>
//				</div>";
//			}
//		}
//		else if ($resource == VideoReferencedElement::RESOURCE_SOURCE) {
//
//			$src = $videoElement->getExternalPath();
//
//			if ($videoElement->getExternalSourceType() == VideoReferencedElement::SOURCE_IFRAME) {
//				$html = "<div class=\"video $alignClass\" data-attach=\"$.fn.resize\">
//					<iframe src=\"//{$src}\" width=\"{$width}\" height=\"{$height}\" frameborder=\"0\" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>
//					</div>";
//			}
//			else if ($videoElement->getExternalSourceType() == VideoReferencedElement::SOURCE_EMBED) {
//
//				$wmodeParam = null;
//				if ($this->requestType == self::REQUEST_TYPE_EDIT) {
//					$wmodeParam = 'wmode="opaque"';
//				}
//
//				$html = "<div class=\"video $alignClass\" data-attach=\"$.fn.resize\">
//					<object width=\"{$width}\" height=\"{$height}\">
//					<param name=\"movie\" value=\"//{$src}\"></param>
//					<param name=\"allowFullScreen\" value=\"true\"></param><param name=\"allowscriptaccess\" value=\"always\"></param>
//					<embed {$wmodeParam} src=\"//{$src}\" type=\"application/x-shockwave-flash\" width=\"{$width}\" height=\"{$height}\" allowscriptaccess=\"always\" allowfullscreen=\"true\"></embed>
//				</object></div>";
//			}
//		}
//
//		return $html;
//	}

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
