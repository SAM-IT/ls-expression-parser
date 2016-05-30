<?php
namespace SamIT\ExpressionManager\Nodes;


class ListNode extends Node
{
    private $operands = [];

    public function append(Node $node)
    {
        $this->operands[] = $node;
    }

    public function length() {
        return count($this->operands);
    }

    public function item($i) {
        return $this->operands[$i];
    }


}