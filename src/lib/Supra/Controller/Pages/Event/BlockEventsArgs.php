<?php

namespace Supra\Controller\Pages\Event;

use Supra\Event\EventArgs;
use Supra\Controller\Pages\BlockController;

class BlockEventsArgs extends EventArgs
{
	/**
	 * @var float
	 */
	public $duration;
	
	/**
	 * @var \Supra\Controller\Pages\Entity\Abstraction\Block
	 */
	public $block;
	
	/**
	 * @var BlockController
	 */
	public $blockController;
	
	/**
	 * @var string
	 */
	public $actionType;
	
	/**
	 * @var boolean
	 */
	public $cached;
}
