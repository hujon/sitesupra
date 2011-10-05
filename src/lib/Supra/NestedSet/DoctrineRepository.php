<?php

namespace Supra\NestedSet;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\QueryBuilder;
use Supra\Controller\Pages\Entity\Abstraction\Entity;
use Node\NodeInterface;

/**
 * 
 */
class DoctrineRepository extends RepositoryAbstraction
{
	/**
	 * Loaded object repository
	 * @var DoctrineRepositoryArrayHelper
	 */
	protected $arrayHelper;

	/**
	 * @var EntityManager
	 */
	protected $entityManager;

	/**
	 * @var Mapping\ClassMetadata
	 */
	protected $classMetadata;

	/**
	 * @var string
	 */
	protected $className;

	/**
	 * @var string
	 */
	protected $tableName;

	/**
	 * @var int
	 */
	protected $max = 0;
	
	/**
	 * Additional condition for all queries
	 * @var string
	 */
	private $additionalCondition;

	/**
	 * Constructor
	 * @param EntityManager $em
	 * @param Mapping\ClassMetadata $class
	 */
	public function __construct(EntityManager $em, Mapping\ClassMetadata $class)
	{
		$this->entityManager = $em;
		$this->classMetadata = $class;
		$this->className = $class->name;
		$this->arrayHelper = new DoctrineRepositoryArrayHelper();
		$platform = $em->getConnection()->getDatabasePlatform();
		$this->tableName = $class->getQuotedTableName($platform);
	}

	/**
	 * @return EntityManager
	 */
	public function getEntityManager()
	{
		return $this->entityManager;
	}

	/**
	 * Get class name of managed Doctrine entity
	 * @return string
	 */
	public function getClassName()
	{
		return $this->className;
	}
	
	/**
	 * Overrides the class name
	 * @param string $className
	 */
	public function setClassName($className)
	{
		$this->className = $className;
	}
	
	/**
	 * Get maximal interval value used by nodes
	 * @return int
	 */
	protected function getMax()
	{
		$dql = "SELECT MAX(e.right) FROM {$this->className} e";
		$dql .= $this->getAdditionalCondition('WHERE');
		$query = $this->entityManager
				->createQuery($dql);
		$max = (int) $query->getSingleScalarResult();

		// Maybe array helper stores even bigger value.
		// In reality it won't happen because items are flushed on DQL run.
		$maxArrayHelper = $this->arrayHelper->getCurrentMax();
		$max = max($max, $maxArrayHelper);

		return $max;
	}

//	public function extend($offset, $size)
//	{
//		$size = (int)$size;
//		$offset = (int)$offset;
//
//		foreach (array('left', 'right') as $field) {
//			$dql = "UPDATE {$this->className} e
//					SET e.{$field} = e.{$field} + ?2
//					WHERE e.{$field} >= ?1";
//			$dql .= $this->getAdditionalCondition('AND');
//			$query = $this->entityManager->createQuery($dql);
//			$query->execute(array(1 => $offset, 2 => $size));
//		}
//
//		$this->arrayHelper->extend($offset, $size);
//	}

	/**
	 * Remove unused space in the nested set intervals
	 * @param int $offset
	 * @param int $size
	 */
	public function truncate($offset, $size)
	{
		$size = (int)$size;
		$offset = (int)$offset;

		foreach (array('left', 'right') as $field) {
			$dql = "UPDATE {$this->className} e
					SET e.{$field} = e.{$field} - {$size}
					WHERE e.{$field} >= {$offset}";
			
			$dql .= $this->getAdditionalCondition('AND');

			$query = $this->entityManager->createQuery($dql);
			$query->execute();
		}

		$this->arrayHelper->truncate($offset, $size);
	}

	/**
	 * Move the node to the new position and change level by {$levelDiff}
	 * @param Node\DoctrineNode $node
	 * @param int $pos
	 * @param int $levelDiff
	 */
	public function move(Node\NodeInterface $node, $pos, $levelDiff)
	{
		$className = $this->className;
		$arrayHelper = $this->arrayHelper;
		$self = $this;
		
		// Transactional because need to rollback in case of trigger failure
		$this->entityManager->transactional(function($entityManager) use ($node, $pos, $levelDiff, $className, $arrayHelper, $self) {
			
			if ( ! $node instanceof Node\DoctrineNode) {
				throw new Exception\WrongInstance($node, 'Node\DoctrineNode');
			}

			// Decision was to remove the fush operation
			// because it's the only place where lvl, lft, rgt are being changed.
			//
			// flush before update
//			$entityManager->flush();

			$left = $node->getLeftValue();
			$right = $node->getRightValue();
			$spaceUsed = $right - $left + 1;
			$moveA = null;
			$moveB = null;
			$a = null;
			$b = null;
			$min = null;
			$max = null;

			if ($pos > $left) {
				$a = $right + 1;
				$b = $pos - 1;
				$moveA = $pos - $left - $spaceUsed;
				$moveB = - $spaceUsed;
				$min = $left;
				$max = $pos - 1;
			} else {
				$a = $pos;
				$b = $left - 1;
				$moveA = $pos - $left;
				$moveB = $spaceUsed;
				$min = $pos;
				$max = $right;
			}

			// Using SQL because DQL does not support such format
			// Will fail with SQL server implementation without function IF(cond, yes, no)
			// NB! It's important to set "lvl" as first for MySQL
			$dql = "UPDATE {$className} e
					SET e.level = e.level + IF(e.left BETWEEN {$left} AND {$right}, {$levelDiff}, 0),
						e.left = e.left + IF(e.left BETWEEN {$left} AND {$right}, {$moveA}, IF(e.left BETWEEN {$a} AND {$b}, {$moveB}, 0)),
						e.right = e.right + IF(e.right BETWEEN {$left} AND {$right}, {$moveA}, IF(e.right BETWEEN {$a} AND {$b}, {$moveB}, 0))
					WHERE (e.left BETWEEN {$min} AND {$max}
						OR e.right BETWEEN {$min} AND {$max})";
			
			$dql .= $self->getAdditionalCondition('AND');
			
			$query = $entityManager->createQuery($dql);
			$result = $query->execute();

//			$connection = $entityManager->getConnection();
//			$statement = $connection->prepare($sql);
//			$result = $statement->execute();
//
//			// Throw the exception if the exceptions are not thrown by the statement
//			if ( ! $result) {
//				$errorInfo = $statement->errorInfo();
//				$errorString = $errorInfo[2];
//				throw new \PDOException($errorString);
//			}

			$arrayHelper->move($node, $pos, $levelDiff);
		});
	}

//	public function oldMove(Node\DoctrineNode $node, $pos, $levelDiff = 0)
//	{
//		$pos = (int)$pos;
//		$levelDiff = (int)$levelDiff;
//
//		$left = $node->getLeftValue();
//		$right = $node->getRightValue();
//		$diff = $pos - $left;
//
//		$dql = "UPDATE {$this->className} e
//				SET e.left = e.left + {$diff},
//					e.right = e.right + {$diff},
//					e.level = e.level + {$levelDiff}
//				WHERE e.left >= {$left} AND e.right <= {$right}";
//		
//		$dql .= $this->getAdditionalCondition('AND');
//
//		$query = $this->entityManager->createQuery($dql);
//		$query->execute();
//
//		$this->arrayHelper->move($node, $pos, $levelDiff);
//	}

	/**
	 * Deletes the nested set part under the node including the node
	 * @param Node\NodeInterface $node
	 */
	public function delete(Node\NodeInterface $node)
	{
		if ( ! $node instanceof Node\DoctrineNode) {
			throw new Exception\WrongInstance($node, 'Node\DoctrineNode');
		}
		
		$left = $node->getLeftValue();
		$right = $node->getRightValue();

		$dql = "DELETE FROM {$this->className} e
				WHERE e.left >= {$left} AND e.right <= {$right}";

		$dql .= $this->getAdditionalCondition('AND');
		
		$query = $this->entityManager->createQuery($dql);
		$query->execute();
		
		$this->arrayHelper->delete($node);
	}

	/**
	 * Perform the search in the database
	 * @param SearchCondition\SearchConditionInterface $filter
	 * @param SelectOrder\SelectOrderInterface $order
	 * @return array
	 */
	public function search(SearchCondition\SearchConditionInterface $filter, SelectOrder\SelectOrderInterface $order = null)
	{
		$em = $this->getEntityManager();
		$className = $this->className;
		$alias = 'e';
		
		$qb = $em->createQueryBuilder();
		$qb->select($alias)
				->from($className, $alias);

		if ( ! ($filter instanceof SearchCondition\DoctrineSearchCondition)) {
			throw new Exception\WrongInstance($filter, 'SearchCondition\DoctrineSearchCondition');
		}
		$qb = $filter->getSearchDQL($qb);

		if ( ! is_null($order)) {
			if ( ! ($order instanceof SelectOrder\DoctrineSelectOrder)) {
				throw new Exception\WrongInstance($order, 'SelectOrder\DoctrineSelectOrder');
			}
			$qb = $order->getOrderDQL($qb);
		}

		$result = $qb->getQuery()
				->getResult();
		
		return $result;
	}

	/**
	 * Create search condition object
	 * @return SearchCondition\DoctrineSearchCondition
	 */
	public function createSearchCondition()
	{
		$searchCondition = new SearchCondition\DoctrineSearchCondition();
		$searchCondition->setAdditionalCondition($this->getAdditionalCondition());
		
		return $searchCondition;
	}

	/**
	 * Create order rule object
	 * @return SelectOrder\DoctrineSelectOrder
	 */
	public function createSelectOrderRule()
	{
		$SelectOrder = new SelectOrder\DoctrineSelectOrder();
		return $SelectOrder;
	}

	/**
	 * Register the node
	 * @param Node\NodeInterface $node
	 */
	public function register(Node\NodeInterface $node)
	{
		$this->arrayHelper->register($node);
	}

	/**
	 * Free the node
	 * @param Node\NodeInterface $node
	 */
	public function free(Node\NodeInterface $node = null)
	{
		if (is_null($node)) {
			$this->arrayHelper->free();
		} else {
			$this->arrayHelper->free($node);
		}
	}

	/**
	 * Prepare object for garbage collector
	 */
	public function destroy()
	{
		$this->arrayHelper->destroy();
		$this->arrayHelper = null;
		$this->classMetadata = null;
		$this->entityManager = null;
	}
	
	/**
	 * Return additional condition with prefix if not empty
	 * @param string $prefix
	 * @return string
	 */
	public function getAdditionalCondition($prefix = '')
	{
		$condition = $this->additionalCondition;
		
		if ( ! empty($condition)) {
			$condition = ' ' . $prefix . ' ' . $condition;
		}
		
		return $condition;
	}

	/**
	 * Sets additional condition, puts in braces
	 * @param string $additionalCondition
	 */
	public function setAdditionalCondition($additionalCondition)
	{
		if ( ! empty($additionalCondition)) {
			$additionalCondition = '(' . $additionalCondition . ')';
		}
		$this->additionalCondition = $additionalCondition;
	}

}