<?php


namespace SamIT\ExpressionManager\Nodes;


class NameNode extends Node
{
    /**
     * @var
     */
    private $name;

    public function __construct($name)
    {
        if (!is_string($name)) {
            throw new \InvalidArgumentException("Name must be string");
        }
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

}