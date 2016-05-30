<?php
namespace SamIT\ExpressionManager;
use SamIT\ExpressionManager\Nodes\BinaryNode;
use SamIT\ExpressionManager\Nodes\VariableNode;
use SamIT\ExpressionManager\Nodes\FunctionNode;
use SamIT\ExpressionManager\Nodes\FunctionNameNode;
use SamIT\ExpressionManager\Nodes\OperatorNode;
use SamIT\ExpressionManager\Nodes\ValueNode;
use SamIT\ExpressionManager\Nodes\ListNode;
/**
 *
 * @author Sam Mousa (sammousa)
 *
 * This is a clean version of em_core_helper; dealing only with actual parsing.
 * This parser only creates an ABSTRACT SYNTAX TREE.
 * It does not:
 * - Evaluate the tree.
 * - Check if variable names are valid (ie it checks syntax not semantics).
 * - Provide detailed error analysis.
 */

class Parser{
    // Context for words.
    const CONTEXT_FUNC = 'FUNC';
    const CONTEXT_VARIABLE = 'VAR';
    const CONTEXT_LITERAL = 'LITERAL';

    /**
     * @var Tokenizer;
     */
    public $tokenizer;

    protected $error;

    public function __construct(Tokenizer $tokenizer)
    {
        $this->tokenizer = $tokenizer;
    }

    /**
     * @param $string An expression
     * @return Node The result of the expression
     * @throws \Exception
     */
    public function parse($string) {
        // First tokenize it.
        $this->error = null;
        $tokens = $this->tokenizer->tokenize($string);
        $stack = new NodeStack();
        $result = $this->parseExpression($tokens, $stack) && $tokens->end();
        if (!$result) {
            $got = isset($this->error['token']) ? $this->error['token']->type: 'NULL';
            $name = Token::getName($got);
            echo "Error in expression, expected {$this->error['expected']} got {$name}({$got})\n";
        }

        if ($result) {
            return $stack->pop();
        }
    }



    /**
     * Rule: EXPR --> LOGIC_EXPR | NAME ASSIGN_OP LOGIC_EXPR
     * @param TokenStream $tokens
     * @param Stack $stack
     * @return boolean Whether parsing succeeded.
     */
    protected function parseExpression(TokenStream $tokens, Stack $stack) {
        return $this->parseAssignExpression($tokens, $stack)
            || $this->parseLogicExpression($tokens, $stack);
    }

    /**
     * Rule: (NAME ASSIGN_OP) LOGIC_EXPR
     * @param TokenStream $tokens
     * @param Stack $stack
     * @return boolean Whether parsing succeeded.
     */
    protected function parseAssignExpression(TokenStream $tokens, Stack $stack) {

        $result = $stack->begin() && $tokens->begin()
            && $this->parseName($tokens, $stack)
            && $this->parseToken(Token::ASSIGN, $tokens, $stack)
            && $this->parseLogicExpression($tokens, $stack)
            && $stack->commit() && $tokens->commit()
            || $stack->rollback() || $tokens->rollback();
        if ($result) {
            // Combine.
            $value = $stack->pop();
            /** @var OperatorNode $operator */
            $operator = $stack->pop();
            $name = $stack->pop();

            $stack->push(new BinaryNode($operator, $name, $value));
        }
        return $result;
    }

    /**
     * Rule: LOGIC_EXPR --> EQ_EXPR (LOGIC_OP EQ_EXPR)*
     * @param TokenStream $tokens
     * @param Stack $stack
     * @return boolean Whether parsing succeeded.
     */
    protected function parseLogicExpression(TokenStream $tokens, Stack $stack)
    {
        $result = $this->parseEqExpression($tokens, $stack);
        if ($result) {
            while ($result) {
                $result = (
                        $tokens->begin() && $stack->begin()
                        && $this->parseToken('LOGIC_OP', $tokens, $stack)
                        && $this->parseEqExpression($tokens, $stack)
                        && $tokens->commit() && $stack->commit()
                    )
                    || $tokens->rollback() || $stack->rollback();
                if ($result) {
                    // Combine.
                    $operand2 = $stack->pop();
                    $operator = $stack->pop();
                    $operand1 = $stack->pop();
                    $stack->push(new BinaryNode($operator, $operand1, $operand2));
                }
            }
            return true;
        } else {
            return false;
        }

    }
    /**
     * Rule: EQ_EXPR --> ADD_EXPR (EQ_OP ADD_EXPR)*
     * @param TokenStream $tokens
     * @param Stack $stack
     * @return boolean Whether parsing succeeded.
     */
    protected function parseEqExpression(TokenStream $tokens, Stack $stack) {
        $result = $this->parseAddExpression($tokens, $stack);
        if ($result) {
            while ($result) {
                $result = (
                    $tokens->begin() && $stack->begin()
                    && $this->parseToken('EQ_OP', $tokens, $stack)
                    && $this->parseAddExpression($tokens, $stack)
                    && $tokens->commit() && $stack->commit()
                )
                || $tokens->rollback() || $stack->rollback();
                if ($result) {
                    // Combine.
                    $operand2 = $stack->pop();
                    $operator = $stack->pop();
                    $operand1 = $stack->pop();
                    
                    $stack->push(new BinaryNode($operator, $operand1, $operand2));
                }
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * Rule: ADD_EXPR --> MULTI_EXPR (ADD_OP MULTI_EXPR)*
     * @param TokenStream $tokens
     * @param Stack $stack
     * @return boolean Whether parsing succeeded.
     */
    protected function parseAddExpression(TokenStream $tokens, Stack $stack) {
        $result = $this->parseMultiExpression($tokens, $stack);
        if ($result) {
            while ($result) {
                $result = (
                    $tokens->begin() && $stack->begin()
                    && $this->parseToken(Token::ADD_OP, $tokens, $stack)
                    && $this->parseMultiExpression($tokens, $stack)
                    && $tokens->commit() && $stack->commit()
                )
                || $tokens->rollback() || $stack->rollback();
                if ($result) {
                    // Combine.
                    $operand2 = $stack->pop();
                    $operator = $stack->pop();
                    $operand1 = $stack->pop();
                    $stack->push(new BinaryNode($operator, $operand1, $operand2));
                }
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * Rule: MULTI_EXPR --> PRIMARY (MULTI_OP PRIMARY)*
     * @param TokenStream $tokens
     * @param Stack $stack
     * @return boolean Whether parsing succeeded.
     */
    protected function parseMultiExpression(TokenStream $tokens, Stack $stack) {
        $result = $this->parsePrimary($tokens, $stack);
        if ($result) {
            while ($result) {
                $result = (
                    $tokens->begin() && $stack->begin()
                    && $this->parseToken(Token::MULTI_OP, $tokens, $stack)
                    && $this->parsePrimary($tokens, $stack)
                    && $tokens->commit() && $stack->commit()
                )
                || $tokens->rollback() || $stack->rollback();
                if ($result) {
                    // Combine.
                    $operand2 = $stack->pop();
                    /** @var OperatorNode$operator */
                    $operator = $stack->pop();
                    $operand1 = $stack->pop();
                    $stack->push(new BinaryNode($operator, $operand1, $operand2));
                }
            }
            return true;
        } else {
            return false;
        }

    }

    /**
     * Rule: PRIMARY --> LPAREN LOGIC_EXPR RPAREN | VALUE | UN_OP LOGIC_EXPR | FUNC | NAME
     * @param TokenStream $tokens
     * @param Stack $stack
     * @return boolean Whether parsing succeeded.
     */
    protected function parsePrimary(TokenStream $tokens, Stack $stack)
    {
        return (
            $tokens->begin() && $stack->begin()
            && $this->consumeToken(Token::LP, $tokens, $stack)
            && $this->parseLogicExpression($tokens, $stack)
            && $this->consumeToken(Token::RP, $tokens, $stack)
            && $tokens->commit() && $stack->commit()
        )
        || $tokens->rollback() || $stack->rollback()
        || $this->parseValue($tokens, $stack)
        || $this->parseUnaryExpression($tokens, $stack)
        || $this->parseFunc($tokens, $stack)
        || $this->parseName($tokens, $stack)
        || $this->parseUnaryExpression($tokens, $stack, Token::ADD_OP);
    }

    /**
     * This parses an unary expression and puts the result on the stack.
     * The type argument allows using it for + and - as well (they can be used as binary and unary ops).
     * Rule: UN_OP EXPR
     *
     * @param TokenStream $tokens
     * @param Stack $stack
     * @return boolean Whether parsing succeeded.
     */
    protected function parseUnaryExpression(TokenStream $tokens, Stack $stack, $type = Token::UN_OP) {
        if (($tokens->begin() && $stack->begin()
            && $this->parseToken($type, $tokens, $stack)
            && $this->parseLogicExpression($tokens, $stack)
            && $tokens->commit() && $stack->commit()
        )
        || $tokens->rollback() || $stack->rollback()) {
            $operand = $stack->pop();
            $operator = $stack->pop();
            $stack->push(new UnaryNode($operator, $operand));
            return true;
        } else {
            return false;
        }
    }

    /**
     * Rule: FUNC --> WORD LPAREN LIST RPAREN
     * @param TokenStream $tokens
     * @param Stack $stack
     * @return boolean Whether parsing succeeded.
     */
    protected function parseFunc(TokenStream $tokens, Stack $stack)
    {
        if ((
            $tokens->begin() && $stack->begin()
            && $this->parseToken(Token::WORD, $tokens, $stack, self::CONTEXT_FUNC)
            && $this->consumeToken(Token::LP, $tokens, $stack)
            && $this->parseList($tokens, $stack)
            && $this->consumeToken(Token::RP, $tokens, $stack)
            && $tokens->commit() && $stack->commit()
        )
        || $tokens->rollback() || $stack->rollback()) {
            $operands = $stack->pop();
            $operator = $stack->pop();
            $stack->push(new FunctionNode($operator, $operands));
            return true;
        } else {
            return false;
        }

    }


    /**
     * Rule: LIST --> E | EXPR (LIST_SEPARATOR EXPR)*
     * @param TokenStream $tokens
     * @param Stack $stack
     * @return boolean Whether parsing succeeded.
     */
    protected function parseList(TokenStream $tokens, Stack $stack) {
        $list = new ListNode([]);
        // Parse first item, if any.
        $result = $this->parseLogicExpression($tokens, $stack);
        if ($result) {
            // List must be an array.
            $list->append($stack->pop());
            while ($result) {
                $result = (
                    $tokens->begin() && $stack->begin()
                    && $this->consumeToken(Token::SEPARATOR, $tokens, $stack)
                    && $this->parseLogicExpression($tokens, $stack)
                    && $tokens->commit() && $stack->commit()
                )
                || $tokens->rollback() || $stack->rollback();
                if ($result) {
                    // Combine.
                    $list->append($stack->pop());
                }
            }
        }
        // List must be an array.
        $stack->push($list);
        // Always return true, empty list is valid.
        return true;

    }

    /**
     * Parse a token from the input and put it on the stack.
     * Optionally set its context.
     * @param $type
     * @param TokenStream $tokens
     * @param Stack $stack
     * @param null $context
     * @return boolean Whether parsing succeeded.
     */
    protected function parseToken($type, TokenStream $tokens, Stack $stack, $context = null) {
        while($this->consumeToken(Token::WS, $tokens, $stack)) {}
        if (!$tokens->end() && $tokens->peek()->type == $type) {

            $token = $tokens->next();
            if (isset($context)) {
                $token->context = $context;
            }
            switch($context) {
                case self::CONTEXT_FUNC:
                    $stack->push(new FunctionNameNode($token->value));
                    break;
                case self::CONTEXT_LITERAL:
                    $stack->push(new ValueNode($token->value));
                    break;
                case self::CONTEXT_VARIABLE:
                    $stack->push(new VariableNode($token->value));
                    break;
                default:
                    $stack->push(new OperatorNode($token->value));
                    break;
            }
            return true;
        } else {
            $this->error($type, $tokens, $stack);
            return false;
        }
    }

    protected function error($type, TokenStream $tokens, Stack $stack) {
        // Stores the deepest error.
        if ($type != Token::WS && (!isset($this->error['index']) || $tokens->getIndex() > $this->error['index'])) {
            $this->error = [
                'expected' => Token::getName($type),
                'stack' => $stack,
                'token' => $tokens->end() ? null : $tokens->peek(),
                'index' => $tokens->getIndex()
            ];
        }
    }

    /**
     * @param $type
     * @param TokenStream $tokens
     * @param Stack $stack
     * @return bool
     */
    protected function consumeToken($type, TokenStream $tokens, Stack $stack) {
        // Consume white space if any.
        if ($type != Token::WS) {
            $this->consumeToken(Token::WS, $tokens, $stack);
        }
        if (!$tokens->end() && $tokens->peek()->type == $type) {
            $tokens->next();
            return true;
        } else {
            $this->error($type, $tokens, $stack);
            return false;
        }
    }

    /**
     * Rule: VALUE --> BOOL | STRING | NUMBER
     * @param TokenStream $tokens
     * @param Stack $stack
     * @return boolean
     */
    protected function parseValue(TokenStream $tokens, Stack $stack) {
        return $this->parseToken(Token::STRING, $tokens, $stack, self::CONTEXT_LITERAL)
            || $this->parseToken(Token::BOOL, $tokens, $stack, self::CONTEXT_LITERAL)
            || $this->parseToken(Token::NUMBER, $tokens, $stack, self::CONTEXT_LITERAL);
    }

    /**
     * Rule: NAME --> SGQA (APPLY WORD)? | WORD (APPLY WORD)?
     * @param TokenStream $tokens
     * @param Stack $stack
     * @return boolean
     */
    protected function parseName(TokenStream $tokens, Stack $stack) {
//        echo "<span style='background-color: blue;>Parsing name.</span>";
        return (
            $tokens->begin() && $stack->begin()
            && $this->parseToken(Token::SGQA, $tokens, $stack, self::CONTEXT_VARIABLE)
            && $this->parseApply($tokens, $stack)
            && $tokens->commit() && $stack->commit()
        )
        || $tokens->rollback() || $stack->rollback()
        || (
            $tokens->begin() && $stack->begin()
            && $this->parseToken(Token::WORD, $tokens, $stack, self::CONTEXT_VARIABLE)
            && $this->parseApply($tokens, $stack)
            && $tokens->commit() && $stack->commit()
        )
        || $tokens->rollback() || $stack->rollback();

    }

    /**
     * Parse optional apply rule.
     * Rule: (APPLY WORD)?
     * @param TokenStream $tokens
     * @param Stack $stack
     * @return bool True
     */
    protected function parseApply(TokenStream $tokens, Stack $stack)
    {
        if ((
            $tokens->begin() && $stack->begin()
            && $this->consumeToken(Token::APPLY, $tokens, $stack)
            && $this->parseToken(Token::WORD, $tokens, $stack, self::CONTEXT_FUNC)
            && $tokens->commit() && $stack->commit()
        )
        || $tokens->rollback() || $stack->rollback()
        ) {
            // Basically this is a function operator.
            $operator = $stack->pop();
            $operand = $stack->pop();

            $stack->push(new UnaryNode($operator, $operand));
        }
        // Always return true since this is an optional rule.
        return true;
    }
}