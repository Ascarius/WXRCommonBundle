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

    public function findOneBy(array $criteria);

    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null);

    public function countBy(array $criteria);

    public function findAll();

    public function countAll();
}
