<?php

namespace WXR\CommonBundle\Entity;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class BaseManager extends \WXR\CommonBundle\Model\BaseManager
{
    protected $em;

    /**
     * Base alias in queries
     *
     * @var string
     */
    protected $alias = 'a';

    public function __construct(EntityManager $em, $class)
    {
        parent::__construct($class);

        $this->em = $em;
    }

    /**
     * {@inheritdoc}
     */
    public function persist($entity)
    {
        if (is_array($entity)) {
            foreach ($entity as $e) {
                $this->em->persist($e);
            }
        } else {
            $this->em->persist($entity);
        }

        $this->em->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function remove($entity)
    {
        if (is_array($entity)) {
            foreach ($entity as $e) {
                $this->em->remove($e);
            }
        } else {
            $this->em->remove($entity);
        }

        $this->em->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function find($id)
    {
        return $this->em->getRepository($this->class)->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function findOneBy(array $criteria)
    {
        return $this->buildQuery(false, $criteria)->getSingleResult();
    }

    /**
     * {@inheritdoc}
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        return $this->buildQuery(false, $criteria, $orderBy, $limit, $offset)->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function countBy(array $criteria)
    {
        return $this->buildQuery(true, $criteria)->getSingleScalarResult();
    }

    /**
     * {@inheritdoc}
     */
    public function findAll()
    {
        return $this->buildQuery(false, array())->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function countAll()
    {
        return $this->buildQuery(true, array())->getSingleScalarResult();
    }

    /**
     * {@inheritdoc}
     */
    public function getCriteriaHandlers()
    {
        $handlers['_search'] = array($this, 'searchHandler');

        return $handlers;
    }

    public function getSearchableProperties()
    {
        return array();
    }

    public function searchHandler(QueryBuilder $qb, $value)
    {
        $value = trim($value);
        $properties = $this->getSearchableProperties();
        $wordsConditions = array();
        $paramKey = '_s';
        $i = 0;

        if (!empty($value)) {
            return;
        }

        foreach (explode(' ', $value) as $word) {

            $propertiesConditions = array();
            foreach ($properties as $property) {
                $propertiesConditions[] = sprintf('%s.%s LIKE :%s%d', $this->alias, $property, $paramKey, $i);
                $qb->setParameter($paramKey.$i, '%'.$word.'%');
            }

            $wordsConditions[] = '(' . implode(' OR ', $propertiesConditions) . ')';
            ++$i;
        }

        $qb->addWhere(implode(' AND ', $wordsConditions));
    }

    public function buildQuery($count, array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        $qb = $this->em->createQueryBuilder();

        $this->buildSelectClause($qb, $count);
        $this->buildFromClause($qb);
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

        return $qb->getQuery();
    }

    protected function buildSelectClause(QueryBuilder $qb, $count)
    {
        if ($count) {
            $qb->select('COUNT('.$this->alias.')');
        } else {
            $qb->select($this->alias);
        }
    }

    protected function buildFromClause(QueryBuilder $qb)
    {
        $qb->from($this->class, $this->alias);
    }

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

    protected function processCriterium(QueryBuilder $qb, $property, $value, $index)
    {
        $alias = $this->alias;
        $paramKey = '_c';

        // Null value
        if (is_null($value)) {

            $qb->addWhere($alias.'.'.$property.' IS NULL');

        // Operator & value
        } elseif (is_array($value) && count($value) == 2) {

            switch ($value[0]) {
                case '=':
                case '!=':
                case '<':
                case '<=':
                case '>':
                case '>=':
                case 'LIKE':
                    $qb->addWhere($alias.'.'.$property.' '.$value[0].' :'.$paramKey.$index);
                    $qb->setParameter($paramKey.$index, $value[1]);
                    break;

                // TODO: IN

                default:
                    throw new \InvalidArgumentException(sprintf('Operator "%s" was not recognized', $value[0]));
            }

        // Equal
        } else {

            $qb->addWhere($alias.'.'.$property.' = :'.$paramKey.$index);
            $qb->setParameter($paramKey.$index, $value);

        }
    }

    protected function buildOrderClause(QueryBuilder $qb, array $orderBy = null)
    {
        if ($orderBy) {
            foreach ($orderBy as $property => $direction) {
                $qb->addOrderBy($this->alias.'.'.$property, $direction);
            }
        }
    }

}
