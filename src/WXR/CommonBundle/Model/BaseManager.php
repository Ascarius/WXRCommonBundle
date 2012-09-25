<?php

namespace WXR\CommonBundle\Model;

abstract class BaseManager implements BaseManagerInterface
{
    /**
     * FQCN
     *
     * @var string
     */
    protected $class;

    public function __construct($class)
    {
        $this->class = $class;
    }

    /**
     * {@inheritdoc}
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * {@inheritdoc}
     */
    public function create()
    {
        $class = $this->class;

        return new $class;
    }

}
