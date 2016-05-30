<?php
namespace SamIT\ExpressionManager\Nodes;


class ValueNode extends Node
{
    /**
     * @var
     */
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }

}