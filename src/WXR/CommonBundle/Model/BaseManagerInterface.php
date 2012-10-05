<?php

namespace WXR\CommonBundle\Model;

interface BaseManagerInterface
{
    /**
     * Get FQCN of model object
     *
     * @return string
     */
    public function getClass();

    /**
     * Create new model object
     *
     * @return object
     */
    public function create();

    /**
     * Persist model object(s)
     *
     * @param object|object[] $object
     */
    public function persist($object);

    /**
     * Remove model object(s)
     *
     * @param object|object[] $object
     */
    public function remove($object);

    /**
     * Refresh model object
     *
     * @param object $object
     */
    public function refresh($object);

    /**
     * Find model object by id
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
