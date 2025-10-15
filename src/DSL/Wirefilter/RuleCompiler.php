<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\Wirefilter;

use Cline\Ruler\Builder\Variable;
use Cline\Ruler\Core\Proposition;
use Cline\Ruler\Variables\Variable as BaseVariable;
use LogicException;
use Symfony\Component\ExpressionLanguage\Node\ArgumentsNode;
use Symfony\Component\ExpressionLanguage\Node\ArrayNode;
use Symfony\Component\ExpressionLanguage\Node\BinaryNode;
use Symfony\Component\ExpressionLanguage\Node\ConstantNode;
use Symfony\Component\ExpressionLanguage\Node\FunctionNode;
use Symfony\Component\ExpressionLanguage\Node\GetAttrNode;
use Symfony\Component\ExpressionLanguage\Node\NameNode;
use Symfony\Component\ExpressionLanguage\Node\Node;
use Symfony\Component\ExpressionLanguage\Node\UnaryNode;
use Symfony\Component\ExpressionLanguage\ParsedExpression;

use function array_map;
use function assert;
use function in_array;
use function is_string;
use function iterator_to_array;
use function sprintf;
use function throw_unless;

/**
 * Compiles symfony/expression-language AST to Ruler Operator trees.
 *
 * Walks the ParsedExpression AST and converts each node type into the
 * appropriate Ruler Operator or Variable. Handles field resolution,
 * operator mapping, and nested expression trees.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class RuleCompiler
{
    /**
     * Create a new RuleCompiler instance.
     *
     * @param FieldResolver    $fieldResolver    Resolves dot-notation field paths to Variables
     *                                           and VariableProperty chains. Maintains cache for
     *                                           consistent Variable instance reuse.
     * @param OperatorRegistry $operatorRegistry maps DSL operator names to their corresponding
     *                                           Ruler Operator class implementations
     */
    public function __construct(
        private FieldResolver $fieldResolver,
        private OperatorRegistry $operatorRegistry,
    ) {}

    /**
     * Compile a ParsedExpression into a Proposition.
     *
     * Walks the expression AST and converts each node type into the appropriate
     * Ruler component (Operators, Variables, VariableProperties). The result is
     * a Proposition tree ready for evaluation.
     *
     * @param ParsedExpression $expression The parsed DSL expression AST from ExpressionParser
     *
     * @throws LogicException When expression doesn't compile to a valid Proposition
     *
     * @return Proposition The compiled Proposition tree
     */
    public function compile(ParsedExpression $expression): Proposition
    {
        $result = $this->compileNode($expression->getNodes());

        throw_unless($result instanceof Proposition, LogicException::class, 'Expression must compile to a Proposition');

        return $result;
    }

    /**
     * Compile a constant value node.
     *
     * Wraps literal values (strings, numbers, booleans, null) in a Variable
     * for use in operator expressions.
     *
     * @param  ConstantNode $node The constant node containing the literal value
     * @return BaseVariable A Variable wrapping the constant value
     */
    private static function compileConstant(ConstantNode $node): BaseVariable
    {
        return new BaseVariable(null, $node->attributes['value']);
    }

    /**
     * @codeCoverageIgnore
     */
    private static function throwUnsupportedNodeType(): never
    {
        throw new LogicException('Unsupported object type in GetAttrNode');
    }

    /**
     * @codeCoverageIgnore
     */
    private static function throwUnsupportedUnaryOperator(string $operator): never
    {
        throw new LogicException(sprintf('Unsupported unary operator: %s', $operator));
    }

    /**
     * Compile a single AST node into a Ruler component.
     *
     * Routes each node type to its specific compilation method. Handles constants,
     * variables, property access, binary/unary operators, function calls, and arrays.
     *
     * @param Node $node The symfony/expression-language AST node to compile
     *
     * @throws LogicException When encountering an unsupported node type
     *
     * @return BaseVariable|Proposition|Variable The compiled Ruler component
     */
    private function compileNode(Node $node): mixed
    {
        return match (true) {
            $node instanceof ConstantNode => self::compileConstant($node),
            $node instanceof NameNode => $this->compileName($node),
            $node instanceof GetAttrNode => $this->compileGetAttr($node),
            $node instanceof BinaryNode => $this->compileBinary($node),
            $node instanceof UnaryNode => $this->compileUnary($node),
            $node instanceof FunctionNode => $this->compileFunction($node),
            $node instanceof ArrayNode => $this->compileArray($node),
            default => throw new LogicException(sprintf('Unsupported node type: %s', $node::class)),
        };
    }

    /**
     * Compile a name/variable reference node.
     *
     * Resolves simple variable references (e.g., "age", "status") to their
     * corresponding Variable instances using the FieldResolver.
     *
     * @param  NameNode              $node The name node containing the variable identifier
     * @return BaseVariable|Variable The resolved Variable
     */
    private function compileName(NameNode $node): Variable|BaseVariable
    {
        $name = $node->attributes['name'];
        assert(is_string($name), 'NameNode attribute "name" must be a string');

        return $this->fieldResolver->resolve($name);
    }

    /**
     * Compile a property access node (e.g., user.age).
     *
     * Handles nested property access by building a dot-notation path string
     * and resolving it to a VariableProperty chain.
     *
     * @param  GetAttrNode           $node The property access node
     * @return BaseVariable|Variable The resolved VariableProperty chain
     */
    private function compileGetAttr(GetAttrNode $node): Variable|BaseVariable
    {
        $object = $node->nodes['node'];
        $attribute = $node->nodes['attribute'];

        assert($object instanceof Node, 'GetAttrNode "node" must be a Node instance');
        assert($attribute instanceof Node, 'GetAttrNode "attribute" must be a Node instance');

        // Build dot-notation path
        $path = $this->buildFieldPath($object, $attribute);

        return $this->fieldResolver->resolve($path);
    }

    /**
     * Build a dot-notation field path from nested GetAttrNode structure.
     *
     * Recursively traverses nested property access nodes to construct the
     * complete dot-notation path (e.g., "http.request.uri.path").
     *
     * @param  Node   $object    The object being accessed
     * @param  Node   $attribute The attribute/property being accessed
     * @return string The complete dot-notation field path
     */
    private function buildFieldPath(Node $object, Node $attribute): string
    {
        // Get the attribute name
        $attributeValue = null;

        if ($attribute instanceof ConstantNode) {
            $attributeValue = $attribute->attributes['value'];
            assert(is_string($attributeValue), 'Attribute value must be a string');
        }

        assert($attributeValue !== null, 'Attribute value must be extracted');

        // Recursively build path from nested GetAttrNode
        if ($object instanceof GetAttrNode) {
            $nestedObject = $object->nodes['node'];
            $nestedAttribute = $object->nodes['attribute'];

            assert($nestedObject instanceof Node, 'Nested GetAttrNode "node" must be a Node instance');
            assert($nestedAttribute instanceof Node, 'Nested GetAttrNode "attribute" must be a Node instance');

            return $this->buildFieldPath($nestedObject, $nestedAttribute).'.'.$attributeValue;
        }

        // Base case: NameNode
        if ($object instanceof NameNode) {
            $name = $object->attributes['name'];
            assert(is_string($name), 'NameNode attribute "name" must be a string');

            return $name.'.'.$attributeValue;
        }

        self::throwUnsupportedNodeType(); // @codeCoverageIgnore
    }

    /**
     * Compile a binary operator node.
     *
     * Handles PHP binary operators (==, !=, >, <, +, -, *, /, etc.) and maps
     * them to DSL operators. Mathematical operators are wrapped in Variables
     * for chaining, while comparison/logical operators return Propositions.
     *
     * @param BinaryNode $node The binary operator node
     *
     * @throws LogicException When encountering an unsupported binary operator
     *
     * @return BaseVariable|Proposition|Variable The compiled operator
     */
    private function compileBinary(BinaryNode $node): Proposition|Variable|BaseVariable
    {
        $leftNode = $node->nodes['left'];
        $rightNode = $node->nodes['right'];

        assert($leftNode instanceof Node, 'BinaryNode "left" must be a Node instance');
        assert($rightNode instanceof Node, 'BinaryNode "right" must be a Node instance');

        $left = $this->compileNode($leftNode);
        $right = $this->compileNode($rightNode);
        $operator = $node->attributes['operator'];

        assert(is_string($operator), 'BinaryNode "operator" must be a string');

        // Map PHP operators to DSL operators
        $operatorName = match ($operator) {
            '>' => 'gt',
            '>=' => 'gte',
            '<' => 'lt',
            '<=' => 'lte',
            '==' => 'eq',
            '!=' => 'ne',
            '===' => 'is',
            '!==' => 'isNot',
            '+' => 'add',
            '-' => 'subtract',
            '*' => 'multiply',
            '/' => 'divide',
            '%' => 'modulo',
            '**' => 'exponentiate',
            'and' => 'and',
            'or' => 'or',
            'xor' => 'xor',
            'in' => 'in',
            'not in' => 'notIn',
            'matches' => 'matches',
            default => throw new LogicException(sprintf('Unsupported binary operator: %s', $operator)),
        };

        // Mathematical operators return VariableOperands, not Propositions
        $mathematicalOperators = ['add', 'subtract', 'multiply', 'divide', 'modulo', 'exponentiate'];

        if (in_array($operatorName, $mathematicalOperators, true)) {
            $operatorClass = $this->operatorRegistry->get($operatorName);
            $mathOperator = new $operatorClass($left, $right);

            // Wrap in a Variable for chaining
            return new Variable($this->fieldResolver->getRuleBuilder(), null, $mathOperator);
        }

        return $this->createOperator($operatorName, [$left, $right]);
    }

    /**
     * Compile a unary operator node.
     *
     * Handles unary operators (not, -). The 'not' operator returns a Proposition,
     * while negation (-) is a mathematical operator wrapped in a Variable.
     *
     * @param UnaryNode $node The unary operator node
     *
     * @throws LogicException When encountering an unsupported unary operator
     *
     * @return BaseVariable|Proposition|Variable The compiled operator
     */
    private function compileUnary(UnaryNode $node): Proposition|Variable|BaseVariable
    {
        $operandNode = $node->nodes['node'];
        assert($operandNode instanceof Node, 'UnaryNode "node" must be a Node instance');

        $operand = $this->compileNode($operandNode);
        $operator = $node->attributes['operator'];

        assert(is_string($operator), 'UnaryNode "operator" must be a string');

        $operatorName = match ($operator) {
            'not' => 'not',
            '-' => 'negate',
            default => self::throwUnsupportedUnaryOperator($operator), // @codeCoverageIgnore
        };

        // Negate is a mathematical operator that returns VariableOperand, not Proposition
        if ($operatorName === 'negate') {
            $operatorClass = $this->operatorRegistry->get($operatorName);
            $mathOperator = new $operatorClass($operand);

            // Wrap in a Variable for chaining
            return new Variable($this->fieldResolver->getRuleBuilder(), null, $mathOperator);
        }

        return $this->createOperator($operatorName, [$operand]);
    }

    /**
     * Compile a function call node.
     *
     * Handles DSL function calls like "eq(age, 18)" or "contains(name, 'John')".
     * Compiles arguments and creates the appropriate Operator instance.
     *
     * @param  FunctionNode $node The function call node
     * @return Proposition  The compiled Operator as a Proposition
     *
     * @codeCoverageIgnore
     */
    private function compileFunction(FunctionNode $node): Proposition
    {
        $functionName = $node->attributes['name'];
        assert(is_string($functionName), 'FunctionNode "name" must be a string');

        $argumentsNode = $node->nodes['arguments'];
        assert($argumentsNode instanceof ArgumentsNode, 'FunctionNode "arguments" must be an ArgumentsNode instance');

        $arguments = $this->compileArguments($argumentsNode);

        return $this->createOperator($functionName, $arguments);
    }

    /**
     * Compile function arguments.
     *
     * Recursively compiles each argument node in a function call to its
     * corresponding Ruler component (Variables, Propositions, or literals).
     *
     * @param  ArgumentsNode     $argumentsNode The arguments node containing argument list
     * @return array<int, mixed> Array of compiled argument values
     *
     * @codeCoverageIgnore
     */
    private function compileArguments(ArgumentsNode $argumentsNode): array
    {
        /** @var array<int, Node> $nodes */
        $nodes = iterator_to_array($argumentsNode->nodes);

        return array_map(
            fn (Node $arg): mixed => $this->compileNode($arg),
            $nodes,
        );
    }

    /**
     * Compile an array literal node.
     *
     * Compiles array literals like ["US", "CA", "UK"] used in operators
     * such as 'in' or set operations. Extracts raw values from Variables.
     *
     * @param  ArrayNode    $node The array node containing array elements
     * @return BaseVariable A Variable wrapping the compiled array
     */
    private function compileArray(ArrayNode $node): BaseVariable
    {
        $elements = [];

        foreach ($node->nodes as $elementNode) {
            assert($elementNode instanceof Node, 'ArrayNode elements must be Node instances');

            $compiled = $this->compileNode($elementNode);

            // Extract raw values from Variables
            $elements[] = $compiled instanceof BaseVariable ? $compiled->getValue() : $compiled;
        }

        return new BaseVariable(null, $elements);
    }

    /**
     * Create an Operator instance from DSL operator name and operands.
     *
     * Looks up the Operator class from the registry, instantiates it with
     * the provided operands, and handles special cases for logical operators
     * which expect an array of propositions as a single argument.
     *
     * @param string            $operatorName The DSL operator name (e.g., "eq", "and", "contains")
     * @param array<int, mixed> $operands     The compiled operand values for the operator
     *
     * @throws LogicException When operator doesn't implement Proposition interface
     *
     * @return Proposition The instantiated Operator
     */
    private function createOperator(string $operatorName, array $operands): Proposition
    {
        $operatorClass = $this->operatorRegistry->get($operatorName);

        // Logical operators expect an array of propositions as a single argument
        $logicalOperators = ['and', 'or', 'not', 'xor', 'nand', 'nor'];

        if (in_array($operatorName, $logicalOperators, true)) {
            $operator = new $operatorClass($operands);
        } else {
            $operator = new $operatorClass(...$operands);
        }

        throw_unless($operator instanceof Proposition, LogicException::class, sprintf('Operator %s must implement Proposition', $operatorClass));

        return $operator;
    }
}
