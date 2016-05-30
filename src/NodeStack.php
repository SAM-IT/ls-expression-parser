<?php
namespace SamIT\ExpressionManager;
use SamIT\ExpressionManager\Nodes\Node;

/**
 * Class NodeStack
 * @package SamIT\ExpressionManager
 * @method Node pop
 */
class NodeStack extends Stack
{

    public function push(Node $item)
    {
        return parent::push($item);
    }
    

}