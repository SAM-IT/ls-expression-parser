<?php
namespace SamIT\ExpressionManager\Nodes;


class OperatorNode extends Node
{
    /**
     * @var
     */
    private $operator;

    public function __construct($operator)
    {
        $this->operator = $operator;
    }

    public function getOperator()
    {
        return $this->operator;
    }

}