<?php

namespace Supra\Controller\Pages\Entity\Abstraction;

use Supra\Controller\ControllerAbstraction,
		Doctrine\Common\Collections\ArrayCollection,
		Doctrine\Common\Collections\Collection,
		Supra\Controller\Pages\Exception,
		Supra\Controller\Pages\BlockController,
		Supra\Controller\Pages\Entity\BlockProperty,
		Supra\Editable\EditableAbstraction,
		Supra\Controller\Pages\Request\Request,
		Supra\Controller\Pages\Response\Block\Response;

/**
 * Block database entity abstraction
 * @Entity
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"template" = "Supra\Controller\Pages\Entity\TemplateBlock", "page" = "Supra\Controller\Pages\Entity\PageBlock"})
 * @Table(name="block")
 */
class Block extends Entity
{
	/**
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue
	 * @var integer
	 */
	protected $id;

	/**
	 * @TODO: store with backslashes replaced with underscores (?)
	 * @Column(type="string")
	 * @var string
	 */
	protected $component;

	/**
	 * @Column(type="integer")
	 * @var int
	 */
	protected $position;

	/**
	 * @ManyToOne(targetEntity="PlaceHolder", inversedBy="blocks")
	 * @JoinColumn(name="place_holder_id", referencedColumnName="id", nullable=false)
	 * @var PlaceHolder
	 */
	protected $placeHolder;

	/**
	 * @OneToMany(targetEntity="Supra\Controller\Pages\Entity\BlockProperty", mappedBy="block", cascade={"persist", "remove"})
	 * @var Collection
	 */
	protected $blockProperties;

	/**
	 * This property is always false for page block
	 * @Column(type="boolean", nullable=true)
	 * @var boolean
	 */
	protected $locked = false;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->blockProperties = new ArrayCollection();
	}

	/**
	 * Get locked value, always false for page blocks
	 * @return boolean
	 */
	public function getLocked()
	{
		return false;
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
		if ($this->writeOnce($this->placeHolder, $placeHolder)) {
			$this->placeHolder->addBlock($this);
		}
	}

	/**
	 * @return integer
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getComponent()
	{
		return $this->component;
	}

	/**
	 * @param string $component
	 */
	public function setComponent($component)
	{
		$this->component = $component;
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
	 * @param BlockProperty $blockProperty
	 */
	public function addBlockProperty(BlockProperty $blockProperty)
	{
		if ($this->lock('blockProperties')) {
			if ($this->addUnique($this->blockProperties, $blockProperty)) {
				$blockProperty->setBlock($this);
			}
			$this->unlock('blockProperties');
		}
	}

	/**
	 * Whether the block is inside one of place holder Ids provided
	 * @param array $placeHolderIds
	 * @return boolean
	 */
	public function inPlaceHolder(array $placeHolderIds)
	{
		$placeHolder = $this->getPlaceHolder();
		$placeHolderId = $placeHolder->getId();
		$in = in_array($placeHolderId, $placeHolderIds);
		
		return $in;
	}
	
	/**
	 * Factory of the block controller
	 * @return BlockController
	 */
	public function createController()
	{
		$component = $this->getComponent();
		if ( ! class_exists($component)) {
			\Log::swarn("Block component $component was not found for block $this");
			
			return null;
		}

		$blockController = new $component();
		if ( ! ($blockController instanceof BlockController)) {
			\Log::swarn("Block controller $component must be instance of BlockController in block $this");
			
			return null;
		}

		$blockController->setBlock($this);

		return $blockController;
	}
	
	/**
	 * Prepares controller
	 * @param BlockController $controller
	 * @param Request $request
	 */
	public function prepareController(BlockController $controller, Request $request)
	{
		// Set properties for controller
		$blockPropertySet = $request->getBlockPropertySet();
		$blockPropertySubset = $blockPropertySet->getBlockPropertySet($this);
		$controller->setBlockPropertySet($blockPropertySubset);
		
		// Create response
		$response = $controller->createResponse($request);
		
		// Prepare
		$controller->prepare($request, $response);
	}
	
	/**
	 * Executes the controller of the block
	 * @param BlockController $controller
	 */
	public function executeController(BlockController $controller)
	{
		// Execute
		$controller->execute();
	}

}