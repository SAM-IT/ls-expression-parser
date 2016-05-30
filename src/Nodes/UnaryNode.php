<?php
namespace SamIT\ExpressionManager\Nodes;


class UnaryNode extends Node
{
    private $operator;
    private $operand;

    /**
     * BinaryNode constructor.
     * @param $operator
     * @param Node $operand1
     */
    public function __construct(OperatorNode $operator, Node $operand)
    {
        $this->operator = $operator->getOperator();
        $this->operand1 = $operand;
    }

    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * @return Node
     */
    public function getOperand1()
    {
        return $this->operand1;
    }

}