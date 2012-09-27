<?php

namespace WXR\CommonBundle\Model;

interface BaseManagerInterface
{
    /**
     * Get FQCN of entity object
     *
     * @return string
     */
    public function getClass();

    /**
     * Create new entity
     *
     * @return object
     */
    public function create();

    /**
     * Persist entity/ies
     *
     * @param object|object[] $entity
     */
    public function persist($entity);

    /**
     * Remove entity object(s)
     *
     * @param object|object[] $entity
     */
    public function remove($entity);

    /**
     * Find entity by id
     *
     * @param mixed $id
     * @return object|null
     */
    public function find($id);

    /**
     * Find one by
     *
     * @param array $criteria
     * @return object|null
     */
    public function findOneBy(array $criteria);

    /**
     * Find by
     *
     * @param array $criteria
     * @param array|null $orderBy
     * @param integer|null $limit
     * @param integer|null $offset
     * @return object[]
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null);

    /**
     * Count by
     *
     * @param array $criteria
     * @return integer
     */
    public function countBy(array $criteria);

    /**
     * Find all
     *
     * @return object[]
     */
    public function findAll();

    /**
     * Count all
     *
     * @return integer
     */
    public function countAll();
}
