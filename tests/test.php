<?php


include "../vendor/autoload.php";


$parser = new \SamIT\ExpressionManager\Parser(new \SamIT\ExpressionManager\Tokenizer());

$result = $parser->parse('myFunc(5, "test", false, a * b + 5 * test(a + 2))');
dumpNode($result);
echo "--------------------------\n";
echo "--------------------------\n";
echo "--------------------------\n";
var_dump($result);

function out($string, $indent) {
    echo str_repeat(' ', $indent) . $string;
}
function dumpNode(\SamIT\ExpressionManager\Nodes\Node $n, $indent = 0) {
    switch (get_class($n)) {
        case \SamIT\ExpressionManager\Nodes\FunctionNode::class:
            out($n->getName() . "\n", $indent);
            dumpNode($n->getOperand(), $indent + 4);
            break;
        case \SamIT\ExpressionManager\Nodes\ListNode::class:
            out("List: [\n", $indent);
            for($i = 0; $i < $n->length(); $i++) {
                dumpNode($n->item($i), $indent + 4);
            }
            out("]\n", $indent);
            break;
        case \SamIT\ExpressionManager\Nodes\BinaryNode::class:
            out($n->getOperator() . "\n", $indent);
            out("Left:\n", $indent);
            dumpNode($n->getOperand1(), $indent + 4);
            out("Right:\n", $indent);
            dumpNode($n->getOperand2(), $indent + 4);

            break;
        case \SamIT\ExpressionManager\Nodes\UnaryNode::class:
            out($n->getOperator() . "\n", $indent);
            out("Operand:\n", $indent);
            break;
        case \SamIT\ExpressionManager\Nodes\VariableNode::class:
            out("Variable `{$n->getName()}`\n", $indent);
            break;
        case \SamIT\ExpressionManager\Nodes\ValueNode::class:
            if (is_int($n->getValue())) {
                out("(int) {$n->getValue()}\n", $indent);
            } elseif (is_float($n->getValue())) {
                out("(float) {$n->getValue()}\n", $indent);
            } elseif (is_string($n->getValue())) {
                out("(string) {$n->getValue()}\n", $indent);
            }
            break;
        case \SamIT\ExpressionManager\Nodes\Token::class:
            out($n . "\n", $indent);
            break;
        default:
            var_dump($n);


    }
}