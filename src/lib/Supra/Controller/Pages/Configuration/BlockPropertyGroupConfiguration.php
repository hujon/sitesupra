<?php

namespace Supra\Controller\Pages\Configuration;

use Supra\Configuration\ConfigurationInterface;
use Supra\Loader\Loader;
use Supra\Editable\EditableInterface;
use Supra\Controller\Pages\BlockPropertyGroupCollection;

/**
 * Block Property Group Configuration
 */
class BlockPropertyGroupConfiguration implements ConfigurationInterface
{
	const TYPE_TOP = 1,
	TYPE_SIDEBAR = 2;

	/**
	 * @var string
	 */
	public $id;

	/**
	 * @var string
	 */
	public $type;

	/**
	 * @var string
	 */
	public $label;

	/**
	 * @var string
	 */
	public $icon = null;

	public function configure()
	{
		$this->id = strtr(trim($this->id), array(' ' => '_', '\\' => '_', '/' => '_'));
		
		if(empty($this->id)) {
			\Log::warn('Property group has empty id. Skipping group. ', $this);
			return;
		}
		
		$this->label = trim($this->label);
		if(empty($this->label)) {
			\Log::warn('Property group has empty label. Skipping group. ', $this);
			return;
		}
		
		$type = mb_strtolower(trim($this->type));
		
		switch ($type) {
			case 'top':
				$this->type = self::TYPE_TOP;
				break;
			case 'sidebar':
				$this->type = self::TYPE_SIDEBAR;
				break;
			default:
				\Log::warn('Property group type is not "top" or "sidebar", will use default type "sidebar". ', $this);
				$this->type = self::TYPE_SIDEBAR;
				break;
		}
		
		return $this;
		
	}

}