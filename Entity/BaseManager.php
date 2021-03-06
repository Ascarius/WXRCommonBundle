<?php

namespace WXR\CommonBundle\Entity;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class BaseManager extends \WXR\CommonBundle\Model\BaseManager
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * Base alias in queries
     *
     * @var string
     */
    protected $alias = 'a';

    /**
     * Criteria key for search handler
     *
     * @var string
     */
    protected $searchKey = '_search';

    public function __construct(EntityManager $em, $class)
    {
        parent::__construct($class);

        $this->em = $em;
    }

    /**
     * {@inheritDoc}
     */
    public function persist($entity, $andFlush = true)
    {
        if (is_array($entity)) {
            foreach ($entity as $e) {
                $this->em->persist($e);
            }
        } else {
            $this->em->persist($entity);
        }

        if ($andFlush) {
            $this->em->flush();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function remove($entity, $andFlush = true)
    {
        if (is_array($entity)) {
            foreach ($entity as $e) {
                $this->em->remove($e);
            }
        } else {
            $this->em->remove($entity);
        }

        if ($andFlush) {
            $this->em->flush();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function refresh($entity)
    {
        $this->em->refresh($entity);
    }

    /**
     * {@inheritDoc}
     */
    public function find($id)
    {
        return $this->em->getRepository($this->class)->find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function findOneBy(array $criteria)
    {
        try {
            return $this->getQueryBuilder(false, $criteria)->getQuery()->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        return $this->getQueryBuilder(false, $criteria, $orderBy, $limit, $offset)->getQuery()->getResult();
    }

    /**
     * {@inheritDoc}
     */
    public function countBy(array $criteria)
    {
        return (int) $this->getQueryBuilder(true, $criteria)->getQuery()->getSingleScalarResult();
    }

    /**
     * {@inheritDoc}
     */
    public function findAll()
    {
        return $this->getQueryBuilder(false, array())->getQuery()->getResult();
    }

    /**
     * {@inheritDoc}
     */
    public function countAll()
    {
        return (int) $this->getQueryBuilder(true, array())->getQuery()->getSingleScalarResult();
    }

    /**
     * {@inheritDoc}
     */
    public function getCriteriaHandlers()
    {
        $handlers[$this->searchKey] = array($this, 'searchHandler');

        return $handlers;
    }

    /**
     * Get search properties
     * Properties on which searchHandler will perform
     *
     * @return array
     */
    public function getSearchableProperties()
    {
        return array();
    }

    /**
     * Search handler
     *
     * @param QueryBuilder $qb
     * @param string $value
     */
    public function searchHandler(QueryBuilder $qb, $value)
    {
        $value = trim($value);
        $properties = $this->getSearchableProperties();
        $wordsConditions = array();
        $paramKey = 'search';
        $i = 0;

        if (empty($properties) || empty($value)) {
            return;
        }

        foreach (explode(' ', $value) as $word) {
            $propertiesConditions = array();

            foreach ($properties as $property) {
                // Add base alias if not set
                $column = false === strpos($property, '.') ?
                    $this->alias.'.'.$property : $property;

                $propertiesConditions[] = $column.' LIKE :'.$paramKey.$i;
                $qb->setParameter($paramKey.$i, '%'.$word.'%');
            }

            $wordsConditions[] = '(' . implode(' OR ', $propertiesConditions) . ')';
            ++$i;
        }

        $qb->andWhere(implode(' AND ', $wordsConditions));
    }

    /**
     * Get query builder
     *
     * @param boolean $count
     * @param array $criteria
     * @param array|null $orderBy
     * @param integer|null $limit
     * @param integer|null $offset
     * @return QueryBuilder
     */
    public function getQueryBuilder($count, array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        $qb = $this->em->createQueryBuilder();

        $this->buildSelectClause($qb, $count);
        $this->buildFromClause($qb, $criteria);
        $this->buildWhereClause($qb, $criteria);

        if (!$count) {

            $this->buildOrderClause($qb, $orderBy);

            if ($limit) {
                $qb->setMaxResults($limit);
            }
            if ($offset) {
                $qb->setFirstResult($offset);
            }
        }

        return $qb;
    }

    /**
     * Build SELECT clause
     *
     * @param QueryBuilder $qb
     * @param boolean $count
     */
    protected function buildSelectClause(QueryBuilder $qb, $count)
    {
        if ($count) {
            $qb->select('COUNT(DISTINCT '.$this->alias.')');
        } else {
            $qb->select('DISTINCT '.$this->alias);
        }
    }

    /**
     * Build FROM clause
     *
     * @param QueryBuilder $qb
     * @param array $criteria
     */
    protected function buildFromClause(QueryBuilder $qb, array $criteria)
    {
        $qb->from($this->class, $this->alias);
    }

    /**
     * Build WHERE clause
     *
     * @param QueryBuilder $qb
     * @param array $criteria
     */
    protected function buildWhereClause(QueryBuilder $qb, array $criteria)
    {
        $handlers = $this->getCriteriaHandlers();
        $i = 0;

        foreach ($criteria as $property => $value) {

            // Handler
            if (isset($handlers[$property])) {
                call_user_func($handlers[$property], $qb, $value);
                continue;
            }

            // Process criterium
            $this->processCriterium($qb, $property, $value, $i);

            ++$i;
        }
    }

    /**
     * Process unhandled criterium
     *
     * @param QueryBuilder $qb
     * @param string $property
     * @param mixed $value
     * @param integer $index
     */
    protected function processCriterium(QueryBuilder $qb, $property, $value, $index)
    {
        $alias = $this->alias;
        $paramKey = 'criterium';
        $param = $paramKey.$index;

        // Add base alias if not set
        $column = false === strpos($property, '.') ?
            $this->alias.'.'.$property : $property;

        // Null value
        if (is_null($value)) {

            $qb->andWhere($column.' IS NULL');

        // Operator & value
        } elseif (is_array($value) && count($value) == 2) {

            $operator = $value[0];
            $value = $value[1];

            if (is_null($value)) {

                if ($operator == '=') {
                    $operator = 'IS';
                } elseif ($operator == '!=') {
                    $operator = 'IS NOT';
                }

                $qb->andWhere($column.' '.$operator.' NULL');

            } else {

                switch ($operator) {
                    case '=':
                    case '!=':
                    case '<':
                    case '<=':
                    case '>':
                    case '>=':
                    case 'LIKE':
                        $qb->andWhere($column.' '.$operator.' :'.$param);
                        $qb->setParameter($param, $value);
                        break;

                    case 'IN':
                        $qb->andWhere($qb->expr()->in($column, $value));
                        break;

                    case 'NOT IN':
                        $qb->andWhere($qb->expr()->notIn($column, $value));
                        break;

                    default:
                        throw new \InvalidArgumentException(sprintf('Operator "%s" was not recognized', $operator));
                }
            }

        // Equal
        } else {

            $qb->andWhere($column.' = :'.$param);
            $qb->setParameter($param, $value);

        }
    }

    /**
     * Build ORDER BY clause
     *
     * @param QueryBuilder $qb
     * @param array|null $orderBy
     */
    protected function buildOrderClause(QueryBuilder $qb, array $orderBy = null)
    {
        if ($orderBy) {
            foreach ($orderBy as $property => $direction) {

                // Add base alias if not set
                $column = false === strpos($property, '.') ?
                    $this->alias.'.'.$property : $property;

                $qb->addOrderBy($column, $direction);
            }
        }
    }

}
