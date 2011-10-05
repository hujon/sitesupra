<?php

namespace Supra\Controller\Pages\Repository;

use Doctrine\ORM\EntityRepository;
use Supra\NestedSet\DoctrineRepository;
use Supra\NestedSet\RepositoryInterface;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\EntityManager;
use BadMethodCallException;
use Supra\Controller\Pages\Entity\Page;

/**
 * Abstract page repository
 */
abstract class PageAbstractRepository extends EntityRepository implements RepositoryInterface
{
	/**
	 * @var DoctrineRepository
	 */
	protected $nestedSetRepository;

	/**
	 * @param EntityManager $em
	 * @param Mapping\ClassMetadata $class
	 */
	public function __construct(EntityManager $em, Mapping\ClassMetadata $class)
	{
		parent::__construct($em, $class);
		$this->nestedSetRepository = new DoctrineRepository($em, $class);
	}

	/**
	 * @return DoctrineRepository
	 */
	public function getNestedSetRepository()
	{
		return $this->nestedSetRepository;
	}

	/**
	 * Output the dump of the whole node tree
	 * @return string
	 */
	public function drawTree()
	{
		$output = $this->nestedSetRepository->drawTree();
		return $output;
	}

	/**
	 * Free the node
	 * @param Node\NodeInterface $node
	 */
	public function free(Node\NodeInterface $node = null)
	{
		$this->nestedSetRepository->free($node);
	}

	/**
	 * Prepares the object to be available to garbage collector.
	 * The further work with the repository will raise errors.
	 */
	public function destroy()
	{
		$this->__call('destroy', array());
		$this->nestedSetRepository = null;
	}
	
	/**
	 * Get root nodes
	 * @return array
	 */
	public function getRootNodes()
	{
		$rootNodes = $this->findByLevel(0);
		
		return $rootNodes;
	}
}
