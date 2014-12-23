<?php

namespace Supra\Package\Cms\Pages\Request;

use Doctrine\ORM\Query;
use Symfony\Component\HttpFoundation\Request;
use Supra\Package\Cms\Pages\Layout\Processor\ProcessorInterface;
use Supra\Package\Cms\Entity\Abstraction\AbstractPage;
use Supra\Package\Cms\Entity\Abstraction\Entity;
use Supra\Package\Cms\Entity\Template;
use Supra\Package\Cms\Entity\TemplateLayout;
use Supra\Package\Cms\Entity\TemplateLocalization;
use Supra\Package\Cms\Entity\Abstraction\Localization;
use Supra\Package\Cms\Entity\PageLocalization;
use Supra\Package\Cms\Entity\Abstraction\PlaceHolder;
use Supra\Package\Cms\Entity\Abstraction\Block;
use Supra\Package\Cms\Entity\BlockProperty;
use Supra\Package\Cms\Entity\PageBlock;
use Supra\Package\Cms\Entity\TemplateBlock;
use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Package\Cms\Pages\Exception\RuntimeException;
use Supra\Package\Cms\Pages\Layout\Theme\ThemeInterface;
use Supra\Package\Cms\Pages\Layout\Theme\ThemeLayoutInterface;
use Supra\Package\Cms\Pages\Set;

/**
 * PageController request class.
 */
abstract class PageRequest extends Request implements ContainerAware
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * @var Localization
	 */
	private $localization;

	/**
	 * @var string
	 */
	private $media = TemplateLayout::MEDIA_SCREEN;

	/**
	 * @var Set\PageSet
	 */
	private $pageSet;

	/**
	 * @var ThemeLayoutInterface
	 */
	private $layout;

	/**
	 * @var Set\PlaceHolderSet
	 */
	protected $placeHolderSet;

	/**
	 * @var Set\BlockSet
	 */
	protected $blockSet;

	/**
	 * @var Set\BlockPropertySet
	 */
	protected $blockPropertySet;

	/**
	 * Block ID array to skip property loading for them.
	 * These are usually blocks with cached results.
	 * @var array
	 */
	private $skipBlockPropertyLoading = array();

	/**
	 * @TODO: remove dependency from Request object?
	 *
	 * @param Request $request source request
	 * @param string $media
	 */
	public function __construct(Request $request, $media = TemplateLayout::MEDIA_SCREEN)
	{
		parent::__construct(
				$request->query->all(),
				$request->request->all(),
				$request->attributes->all(),
				$request->cookies->all(),
				$request->files->all(),
				$request->server->all(),
				$request->content
		);

		$this->setDefaultLocale($request->getLocale());

		$this->media = $media;
	}

	/**
	 * Will return true if the resource data should be acquired from the local
	 * object not from the database. Used by history actions.
	 *
	 * @param Entity $entity
	 * @return bool
	 */
	protected function isLocalResource(Entity $entity)
	{
		return false;
	}

	/**
	 * Appends query result cache information in case of VIEW mode
	 * @param Query $query
	 */
	protected function prepareQueryResultCache(Query $query)
	{
		// Does nothing by default
	}

	/**
	 * @return Localization
	 */
	public function getLocalization()
	{
		return $this->localization;
	}

	/**
	 * @param Localization $localization
	 */
	public function setLocalization(Localization $localization)
	{
		$this->clear();
		$this->localization = $localization;
	}

	public function clear()
	{
		$this->localization = $this->blockPropertySet
				= $this->blockSet
				= $this->layout
				= $this->pageSet
				= $this->placeHolderSet
				= null;
	}

	/**
	 * @return string
	 */
	public function getMedia()
	{
		return $this->media;
	}

	/**
	 * @param string $media
	 */
	public function setMedia($media)
	{
		$this->media = $media;
	}

	/**
	 * Helper method to get requested page entity
	 * @return AbstractPage
	 */
	public function getPage()
	{
		$page = $this->getLocalization()
				->getMaster();

		if ($page === null) {
			throw new RuntimeException(
				"Missing page object for localization [{$this->getLocalization()->getId()}]."
			);
		}

		return $page;
	}

	/**
	 * @return Set\PageSet
	 */
	public function getPageSet()
	{
		if (isset($this->pageSet)) {
			return $this->pageSet;
		}
		
		$localization = $this->getLocalization();
		
		if ($localization instanceof TemplateLocalization) {
			$this->pageSet = $this->getTemplateTemplateHierarchy(
					$localization->getMaster()
			);
			
		} elseif ($localization instanceof PageLocalization) {
			$this->pageSet = $this->getPageTemplateHierarchy($localization);
			
		} else {
			throw new RuntimeException(sprintf(
					'Don\'t know how to get page set for instance of [%s].',
					get_class($localization)
			));
		}

		return $this->pageSet;
	}
	
	/**
	 * @param PageLocalization $localization
	 * @return Set\PageSet
	 */
	protected function getPageTemplateHierarchy(PageLocalization $localization)
	{
		$template = $localization->getTemplate();
		$page = $localization->getPage();

		if (empty($template)) {
			throw new RuntimeException("No template assigned to the page {$localization->getId()}");
		}

		$pageSet = $this->getTemplateTemplateHierarchy($template);
		$pageSet[] = $page;

		return $pageSet;
	}
	
	/**
	 * @param Template $template
	 * @return Set\PageSet
	 */
	protected function getTemplateTemplateHierarchy(Template $template)
	{
		/* @var $templates Template[] */
		$templates = $template->getAncestors(0, true);
		$templates = array_reverse($templates);

		$pageSet = new Set\PageSet($templates);
		
		return $pageSet;
	}

	/**
	 * @return array
	 */
	public function getPageSetIds()
	{
		return $this->getPageSet()
						->collectIds();
	}

	/**
	 * @return Template
	 */
	public function getRootTemplate()
	{
		return $this->getPageSet()
						->getRootTemplate();
	}
	
	/**
	 * @return string
	 */
	public function getBlockRequestId()
	{
		$parameters = $this->query;

		if ( ! $parameters->has('block_id')
				&& $this->isMethod('post')) {
			
			$parameters = $this->request;
		}

		return $parameters->get('block_id');
	}
	
	/**
	 * @return boolean
	 */
	public function isBlockRequest()
	{
		$blockId = $this->getBlockRequestId();
		$isBlockRequest = ! empty($blockId);
		
		return $isBlockRequest;
	}

	/**
	 * @return ThemeLayoutInterface
	 */
	public function getLayout()
	{
		if (isset($this->layout)) {
			return $this->layout;
		}

		$layoutName = $this->getPageSet()
				->getLayoutName($this->media);

		return $this->layout = $this->getTheme()
				->getLayout($layoutName);
	}

	/**
	 * @return ThemeInterface
	 */
	public function getTheme()
	{
		return $this->container['cms.pages.theme.provider']
				->getActiveTheme();
	}

	/**
	 * TODO: maybe should return null for history request?
	 * @return array
	 */
	public function getLayoutPlaceHolderNames()
	{
		$layout = $this->getLayout();

		if ($layout === null) {
			return null;
		}

		return $this->getLayoutProcessor()
				->getPlaces($layout->getFileName());
	}

	/**
	 * @return Set\PlaceHolderSet
	 */
	public function getPlaceHolderSet()
	{
		if (isset($this->placeHolderSet)) {
			return $this->placeHolderSet;
		}

		$localization = $this->getLocalization();
		$localeId = $localization->getLocale();
		$this->placeHolderSet = new Set\PlaceHolderSet($localization);
		
		$pageSetIds = $this->getPageSetIds();
		$layoutPlaceHolderNames = $this->getLayoutPlaceHolderNames();
		
//		$layoutPlaceHolders = $this->getLayoutPlaceHolders();
		
		$entityManager = $this->getEntityManager();
		
		// Skip only if empty array is received
		if (is_array($layoutPlaceHolderNames) && empty($layoutPlaceHolderNames)) {
//		if (is_array($layoutPlaceHolders) && empty($layoutPlaceHolders)) {
			return $this->placeHolderSet;
		}
		
		$currentPageIsLocalResource = false;
		
		if ($this->isLocalResource($localization)) {
			// Ignoring the current page when selecting placeholders by DQL, will acquire from the object
			array_pop($pageSetIds);
			$currentPageIsLocalResource = true;
		}

		// Nothing to search for
		if ( ! empty($pageSetIds)) {

			// Find template place holders
			$qb = $entityManager->createQueryBuilder();

			$qb->select('ph')
					->from(PlaceHolder::CN(), 'ph')
					->join('ph.localization', 'pl')
					->join('pl.master', 'p')
					->andWhere($qb->expr()->in('p.id', $pageSetIds))
					->andWhere('pl.locale = ?0')
					->setParameter(0, $localeId)
					// templates first (type: 0-templates, 1-pages)
					->orderBy('ph.type', 'ASC')
					->addOrderBy('p.level', 'ASC');
			
			if ( ! empty($layoutPlaceHolderNames)) {
				$qb->andWhere($qb->expr()->in('ph.name', $layoutPlaceHolderNames));
			}

			$query = $qb->getQuery();
			$this->prepareQueryResultCache($query);
			$placeHolderArray = $query->getResult();

			foreach ($placeHolderArray as $placeHolder) {
				/* @var $place PlaceHolder */
				$this->placeHolderSet->append($placeHolder);
			}
		}
		
		// Merge the local resource localization into the placeholder set
		if ($currentPageIsLocalResource) {
			$placeHolders = $localization->getPlaceHolders();

			foreach ($placeHolders as $placeHolder) {
				/* @var $place PlaceHolder */
				$this->placeHolderSet->append($placeHolder);
			}
		}

		return $this->placeHolderSet;
	}
	
	/**
	 * @return Set\BlockSet
	 */
	public function getBlockSet()
	{
		if (isset($this->blockSet)) {
			return $this->blockSet;
		}

		$entityManager = $this->getEntityManager();

		$this->blockSet = new Set\BlockSet();

		$localFinalPlaceHolders = array();
		
		$finalPlaceHolderIds = array();
		
		$placeHolderSet = $this->getPlaceHolderSet();

//		$allFinalPlaceHolderIds = $placeHolderSet->getFinalPlaceHolders()
//				->collectIds();

		// Filter out the locally managed placeholders (history)
		foreach ($placeHolderSet->getFinalPlaceHolders() as $placeHolder) {
			if ($this->isLocalResource($placeHolder)) {
				$localFinalPlaceHolders[] = $placeHolder;
			} else {
				$finalPlaceHolderIds[] = $placeHolder->getId();
			}
		}
		
		$parentPlaceHolderIds = $placeHolderSet->getParentPlaceHolders()
				->collectIds();

		$blocks = array();
		
		// Just return empty array if no final/parent place holders have been found
		if ( ! empty($finalPlaceHolderIds) || ! empty($parentPlaceHolderIds)) {
			
			// Here we find all 1) locked blocks from templates; 2) all blocks from final place holders
			$qb = $entityManager->createQueryBuilder();
			$qb->select('b')
					->from(Block::CN(), 'b')
					->join('b.placeHolder', 'ph')
					->andWhere('b.inactive = FALSE')
					->orderBy('b.position', 'ASC');

			$expr = $qb->expr();
			$or = $expr->orX();

			// final placeholder blocks
			if ( ! empty($finalPlaceHolderIds)) {
				$or->add($expr->in('ph.id', $finalPlaceHolderIds));
			}

			// locked block condition
			if ( ! empty($parentPlaceHolderIds)) {
				$lockedBlocksCondition = $expr->andX(
						$expr->in('ph.id', $parentPlaceHolderIds), 'b.locked = TRUE'
				);
				$or->add($lockedBlocksCondition);
			}

			$qb->where($or);

			// When specific ID is passed, limit by it
			$blockId = $this->getBlockRequestId();
			if ( ! is_null($blockId)) {
				$qb->andWhere('b.id = :blockId OR b.componentClass = :blockId')
						->setParameter('blockId', $blockId);
			}
			
			// Execute block query
			$query = $qb->getQuery();
			$this->prepareQueryResultCache($query);
			$blocks = $query->getResult();
		}
		
		// Add blocks from locally managed placeholders
		foreach ($localFinalPlaceHolders as $placeHolder) {
			/* @var $placeHolder PlaceHolder */
			$additionalBlocks = $placeHolder->getBlocks()->getValues();
			$blocks = array_merge($blocks, $additionalBlocks);
		}
		
		// Skip temporary blocks for VIEW mode
		foreach ($blocks as $blockKey => $block) {
			if ($block instanceof TemplateBlock) {
				if ($block->getTemporary()) {
					unset($blocks[$blockKey]);
				}
			}
		}
		
		foreach ($blocks as $blockKey => $block) {
			if ($block instanceof PageBlock) {
				if ($block->isInactive()) {
					unset($blocks[$blockKey]);
				}
			}
		}

		/*
		 * Collect locked blocks first, these are positioned as first blocks in
		 * the placeholder if placeholder is not locked.
		 * First locked blocs are taken from the top template.
		 */
		foreach ($placeHolderSet as $placeHolder) {
			foreach ($blocks as $key => $block) {
			/* @var $block Block */

				if ($block->getLocked() && $block->getPlaceHolder()->equals($placeHolder)) {
					if ( ! $placeHolder->getLocked()) {
						$this->blockSet[] = $block;
						unset($blocks[$key]);
					}
				}
			}
		}

		// Collect all unlocked blocks
		/* @var $block Block */
		foreach ($blocks as $block) {
			$this->blockSet[] = $block;
		}
		
		// Ordering the blocks by position in the layout
		$placeHolderNames = $this->getLayoutPlaceHolderNames();
		$this->blockSet->orderByPlaceHolderNameArray($placeHolderNames);

		return $this->blockSet;
	}

	/**
	 * Mark that properties must not be loaded for this block
	 * @param string $blockId
	 */
	public function skipBlockPropertyLoading($blockId)
	{
		$this->skipBlockPropertyLoading[] = $blockId;
	}

	/**
	 * @return Set\BlockPropertySet
	 */
	public function getBlockPropertySet()
	{
		if (isset($this->blockPropertySet)) {
			return $this->blockPropertySet;
		}

		$this->blockPropertySet = new Set\BlockPropertySet();

		$entityManager = $this->getEntityManager();
		$qb = $entityManager->createQueryBuilder();
		$expr = $qb->expr();
		$or = $expr->orX();

		$cnt = 0;

		$blockSet = $this->getBlockSet();
		
		//$sharedPropertyFinder = new SharedPropertyFinder($entityManager);
		
		$localResourceLocalizations = array();

		// Loop generates condition for property getter
		foreach ($blockSet as $block) {
			/* @var $block Block */

			$blockId = $block->getId();

			// Skip if the block response is read from the cache already
			if (in_array($blockId, $this->skipBlockPropertyLoading)) {
				continue;
			}

			$data = null;

			if ($block->getLocked()) {
				$data = $block->getPlaceHolder()
						->getMaster();
			} else {
				$data = $this->getLocalization();
			}
			
			$dataId = $data->getId();
			
			if ( ! $this->isLocalResource($data)) {

				$and = $expr->andX();
				$and->add($expr->eq('bp.block', '?' . ( ++ $cnt)));
				$qb->setParameter($cnt, $blockId);
				$and->add($expr->eq('bp.localization', '?' . ( ++ $cnt)));
				$qb->setParameter($cnt, $dataId);

				$or->add($and);
			} else {
				// In reality there can be only one local resource localization
				$localResourceLocalizations[$dataId] = $data;
			}

			//$sharedPropertyFinder->addBlock($block, $data);
		}
		
		$result = array();
		
		// Load only if any condition is added to the query
		if ($cnt != 0) {
			$qb->select('bp')
					->from(BlockProperty::CN(), 'bp')
					->where($or);
			$query = $qb->getQuery();

			$this->prepareQueryResultCache($query);
			$result = $query->getResult();
		}
		
		// Now merge local resource block properties
		foreach ($localResourceLocalizations as $localization) {
			/* @var $localization Localization */
			$localProperties = $localization->getBlockProperties()
					->getValues();
			$result = array_merge($result, $localProperties);
		}
		
		$this->blockPropertySet->exchangeArray($result);

		return $this->blockPropertySet;
	}

	public function getLocale()
	{
		if ($this->attributes->has('_locale')) {
			return $this->attributes->get('_locale');
		}

		return parent::getLocale();
	}

	/**
	 * @param ContainerInterface $container
	 */
	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}

	/**
	 * @TODO: generate PlaceHolders and response part objects on demand,
	 *		instead of processing layout file twice.
	 *
	 * @return ProcessorInterface
	 */
	protected function getLayoutProcessor()
	{
		return $this->container['cms.pages.layout_processor'];
	}

	/**
	 * @return \Doctrine\ORM\EntityManager
	 */
	protected function getEntityManager()
	{
		return $this->container->getDoctrine()->getManager();
	}
}
