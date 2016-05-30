<?php
namespace SamIT\ExpressionManager\Nodes;

class BinaryNode extends Node
{
    private $operator;
    private $operand1;
    private $operand2;

    /**
     * BinaryNode constructor.
     * @param $operator
     * @param Node $operand1
     * @param Node $operand2
     */
    public function __construct(OperatorNode $operator, Node $operand1, Node $operand2)
    {
        $this->operator = $operator->getOperator();
        $this->operand1 = $operand1;
        $this->operand2 = $operand2;
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

    /**
     * @return Node
     */
    public function getOperand2()
    {
        return $this->operand2;
    }

}