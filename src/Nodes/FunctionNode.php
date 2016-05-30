<?php
namespace SamIT\ExpressionManager\Nodes;


class FunctionNode extends UnaryNode
{
    private $name;
    private $operand;

    /**
     * BinaryNode constructor.
     * @param $operator
     * @param Node $operand1
     */
    public function __construct(FunctionNameNode $name, ListNode $operand)
    {
        $this->name = $name->getName();
        $this->operand = $operand;
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * @return ListNode
     */
    public function getOperand()
    {
        return $this->operand;
    }

}