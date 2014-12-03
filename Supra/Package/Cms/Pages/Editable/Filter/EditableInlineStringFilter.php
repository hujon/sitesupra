<?php

namespace Supra\Package\Cms\Pages\Editable\Filter;

use Supra\Package\Cms\Entity\BlockProperty;
use Supra\Package\Cms\Editable\Filter\FilterInterface;
use Supra\Package\Cms\Pages\Editable\BlockPropertyAware;

class EditableInlineStringFilter implements FilterInterface, BlockPropertyAware
{
	/**
	 * @var BlockProperty
	 */
	protected $blockProperty;

	/**
	 * @param string $content
	 * @return \Twig_Markup
	 */
	public function filter($content, array $options = array())
	{
		$wrap = '<div id="content_%s_%s" class="yui3-content-inline yui3-input-string-inline">%s</div>';

		return new \Twig_Markup(
				sprintf(
					$wrap,
					$this->blockProperty->getBlock()->getId(),
					$this->blockProperty->getName(),
					htmlspecialchars($content, ENT_QUOTES, 'UTF-8')
				),
				'UTF-8'
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function setBlockProperty(BlockProperty $blockProperty)
	{
		$this->blockProperty = $blockProperty;
	}
}
