<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\DSL\Wirefilter;

use Cline\Ruler\Builder\Variable as BuilderVariable;
use Cline\Ruler\Core\Operator;
use Cline\Ruler\Core\Proposition;
use Cline\Ruler\Core\Rule;
use Cline\Ruler\Operators\Comparison\Between;
use Cline\Ruler\Operators\Comparison\EqualTo;
use Cline\Ruler\Operators\Comparison\GreaterThan;
use Cline\Ruler\Operators\Comparison\GreaterThanOrEqualTo;
use Cline\Ruler\Operators\Comparison\In;
use Cline\Ruler\Operators\Comparison\LessThan;
use Cline\Ruler\Operators\Comparison\LessThanOrEqualTo;
use Cline\Ruler\Operators\Comparison\NotEqualTo;
use Cline\Ruler\Operators\Comparison\NotIn;
use Cline\Ruler\Operators\Comparison\NotSameAs;
use Cline\Ruler\Operators\Comparison\SameAs;
use Cline\Ruler\Operators\Logical\LogicalAnd;
use Cline\Ruler\Operators\Logical\LogicalNand;
use Cline\Ruler\Operators\Logical\LogicalNor;
use Cline\Ruler\Operators\Logical\LogicalNot;
use Cline\Ruler\Operators\Logical\LogicalOr;
use Cline\Ruler\Operators\Logical\LogicalXor;
use Cline\Ruler\Operators\Mathematical\Addition;
use Cline\Ruler\Operators\Mathematical\Division;
use Cline\Ruler\Operators\Mathematical\Exponentiate;
use Cline\Ruler\Operators\Mathematical\Modulo;
use Cline\Ruler\Operators\Mathematical\Multiplication;
use Cline\Ruler\Operators\Mathematical\Negation;
use Cline\Ruler\Operators\Mathematical\Subtraction;
use Cline\Ruler\Operators\String\Matches;
use Cline\Ruler\Variables\Variable;
use Cline\Ruler\Variables\VariableOperand;
use LogicException;
use ReflectionClass;

use function addslashes;
use function array_map;
use function array_values;
use function count;
use function gettype;
use function implode;
use function in_array;
use function is_array;
use function is_bool;
use function is_numeric;
use function is_string;
use function sprintf;
use function throw_if;

/**
 * Serializes Rule objects back to Wirefilter DSL expression strings.
 *
 * Provides reverse transformation from compiled Rule/Proposition trees back
 * to human-readable Wirefilter DSL syntax. Supports all Wirefilter operators
 * including comparison, logical, mathematical, and string operations.
 *
 * Pattern: Each DSL should provide three public classes:
 * - {DSL}Parser: Parse DSL strings → Rule objects
 * - {DSL}Serializer: Serialize Rule objects → DSL strings
 * - {DSL}Validator: Validate DSL strings without full parsing
 *
 * Example usage:
 * ```php
 * $serializer = new WirefilterSerializer();
 * $parser = new WirefilterParser();
 *
 * $rule = $parser->parse('age >= 18 && country == "US"');
 * $expression = $serializer->serialize($rule);
 * // Returns: 'age >= 18 && country == "US"'
 * ```
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see WirefilterParser For parsing DSL strings into Rules
 * @see WirefilterValidator For validating DSL strings
 *
 * @psalm-immutable
 */
final readonly class WirefilterSerializer
{
    /**
     * Serialize a Rule to a Wirefilter DSL expression string.
     *
     * Walks the Rule's Proposition tree and reconstructs the original
     * Wirefilter DSL syntax. Supports operator precedence and automatic
     * parenthesization for complex expressions.
     *
     * @param Rule $rule The Rule to serialize
     *
     * @throws LogicException When encountering unsupported operators or structures
     *
     * @return string The Wirefilter DSL expression
     */
    public function serialize(Rule $rule): string
    {
        $reflection = new ReflectionClass($rule);
        $conditionProperty = $reflection->getProperty('condition');

        /** @var Proposition $condition */
        $condition = $conditionProperty->getValue($rule);

        return $this->serializeProposition($condition);
    }

    /**
     * Get operands from an operator using reflection.
     *
     * @param  Operator|Proposition|VariableOperand $operator The operator object
     * @return array<int, mixed>                    The operands
     */
    private function getOperands(Proposition|Operator|VariableOperand $operator): array
    {
        $reflection = new ReflectionClass($operator);

        // Try to get operands property
        if ($reflection->hasProperty('operands')) {
            $operandsProperty = $reflection->getProperty('operands');
            $value = $operandsProperty->getValue($operator);

            if (is_array($value)) {
                return array_values($value);
            }

            // @codeCoverageIgnoreStart
            return [];
            // @codeCoverageIgnoreEnd
        }

        // Fallback: call getOperands() method if it's an Operator
        // @codeCoverageIgnoreStart
        if ($operator instanceof Operator) {
            return array_values($operator->getOperands());
        }

        return [];
        // @codeCoverageIgnoreEnd
    }

    /**
     * Serialize a Proposition to DSL syntax.
     *
     * @param  Proposition $proposition The proposition to serialize
     * @return string      The serialized expression
     */
    private function serializeProposition(Proposition $proposition): string
    {
        return match (true) {
            // Comparison operators
            $proposition instanceof EqualTo => $this->serializeBinary($proposition, '=='),
            $proposition instanceof NotEqualTo => $this->serializeBinary($proposition, '!='),
            $proposition instanceof SameAs => $this->serializeBinary($proposition, '==='),
            $proposition instanceof NotSameAs => $this->serializeBinary($proposition, '!=='),
            $proposition instanceof GreaterThan => $this->serializeBinary($proposition, '>'),
            $proposition instanceof GreaterThanOrEqualTo => $this->serializeBinary($proposition, '>='),
            $proposition instanceof LessThan => $this->serializeBinary($proposition, '<'),
            $proposition instanceof LessThanOrEqualTo => $this->serializeBinary($proposition, '<='),
            $proposition instanceof In => $this->serializeBinary($proposition, 'in'),
            $proposition instanceof NotIn => $this->serializeBinary($proposition, 'not in'),
            $proposition instanceof Between => $this->serializeFunction($proposition, 'between'),
            // Logical operators
            $proposition instanceof LogicalAnd => $this->serializeLogical($proposition, 'and'),
            $proposition instanceof LogicalOr => $this->serializeLogical($proposition, 'or'),
            $proposition instanceof LogicalXor => $this->serializeLogical($proposition, 'xor'),
            $proposition instanceof LogicalNand => $this->serializeLogical($proposition, 'nand'),
            $proposition instanceof LogicalNor => $this->serializeLogical($proposition, 'nor'),
            $proposition instanceof LogicalNot => $this->serializeNot($proposition),
            // String operators
            $proposition instanceof Matches => $this->serializeBinary($proposition, 'matches'),
            default => $this->serializeGenericOperator($proposition),
        };
    }

    /**
     * Serialize a binary operator.
     *
     * @param  Proposition $proposition The proposition containing two operands
     * @param  string      $operator    The operator symbol or keyword
     * @return string      The serialized binary expression
     */
    private function serializeBinary(Proposition $proposition, string $operator): string
    {
        $operands = $this->getOperands($proposition);

        throw_if(count($operands) !== 2, LogicException::class, sprintf('Binary operator %s requires exactly 2 operands', $operator));

        $left = $this->serializeOperand($operands[0]);
        $right = $this->serializeOperand($operands[1]);

        return sprintf('%s %s %s', $left, $operator, $right);
    }

    /**
     * Serialize a logical operator (and, or, xor, etc.).
     *
     * @param  Proposition $proposition The logical proposition
     * @param  string      $operator    The logical operator keyword
     * @return string      The serialized logical expression
     */
    private function serializeLogical(Proposition $proposition, string $operator): string
    {
        $operands = $this->getOperands($proposition);

        $serialized = array_map(
            fn (mixed $operand): string => $operand instanceof Proposition
                ? $this->wrapIfNeeded($operand, $operator)
                : $this->serializeOperand($operand),
            $operands,
        );

        return implode(' '.$operator.' ', $serialized);
    }

    /**
     * Serialize a NOT operator.
     *
     * @param  LogicalNot $not The NOT proposition
     * @return string     The serialized NOT expression
     */
    private function serializeNot(LogicalNot $not): string
    {
        $operands = $this->getOperands($not);

        throw_if(count($operands) !== 1, LogicException::class, 'NOT operator requires exactly 1 operand');

        $operand = $operands[0];

        if ($operand instanceof Proposition) {
            return sprintf('not (%s)', $this->serializeProposition($operand));
        }

        return sprintf('not %s', $this->serializeOperand($operand));
    }

    /**
     * Serialize a function-style operator.
     *
     * @param  Proposition $proposition The proposition
     * @param  string      $function    The function name
     * @return string      The serialized function call
     */
    private function serializeFunction(Proposition $proposition, string $function): string
    {
        $operands = $this->getOperands($proposition);

        $args = array_map(
            $this->serializeOperand(...),
            $operands,
        );

        return sprintf('%s(%s)', $function, implode(', ', $args));
    }

    /**
     * Serialize a generic operator by looking up its DSL name.
     *
     * @param  Proposition $proposition The proposition to serialize
     * @return string      The serialized expression
     */
    private function serializeGenericOperator(Proposition $proposition): string
    {
        // Lookup operator name from OperatorRegistry
        $registry = new OperatorRegistry();
        $operatorClass = $proposition::class;

        // Find the DSL name for this operator class
        foreach ($registry->all() as $dslName) {
            if ($registry->get($dslName) === $operatorClass) {
                $operands = $this->getOperands($proposition);

                $args = array_map(
                    $this->serializeOperand(...),
                    $operands,
                );

                return sprintf('%s(%s)', $dslName, implode(', ', $args));
            }
        }

        throw new LogicException(sprintf('Unknown operator: %s', $operatorClass));
    }

    /**
     * Serialize an operand (Variable, VariableOperand, or value).
     *
     * @param  mixed  $operand The operand to serialize
     * @return string The serialized operand
     */
    private function serializeOperand(mixed $operand): string
    {
        // Handle Propositions recursively
        // @codeCoverageIgnoreStart
        if ($operand instanceof Proposition) {
            return '('.$this->serializeProposition($operand).')';
        }

        // @codeCoverageIgnoreEnd

        // Handle mathematical operators
        if ($operand instanceof Addition) {
            return $this->serializeMath($operand, '+');
        }

        if ($operand instanceof Subtraction) {
            return $this->serializeMath($operand, '-');
        }

        if ($operand instanceof Multiplication) {
            return $this->serializeMath($operand, '*');
        }

        if ($operand instanceof Division) {
            return $this->serializeMath($operand, '/');
        }

        if ($operand instanceof Modulo) {
            return $this->serializeMath($operand, '%');
        }

        if ($operand instanceof Exponentiate) {
            return $this->serializeMath($operand, '**');
        }

        if ($operand instanceof Negation) {
            $innerOperands = $this->getOperands($operand);

            return sprintf('-%s', $this->serializeOperand($innerOperands[0]));
        }

        // Handle Variables
        if ($operand instanceof Variable || $operand instanceof BuilderVariable) {
            return $this->serializeVariable($operand);
        }

        // Handle raw values
        // @codeCoverageIgnoreStart
        return $this->serializeValue($operand);
        // @codeCoverageIgnoreEnd
    }

    /**
     * Serialize a mathematical operator.
     *
     * @param  VariableOperand $math     The mathematical operator
     * @param  string          $operator The operator symbol
     * @return string          The serialized mathematical expression
     */
    private function serializeMath(VariableOperand $math, string $operator): string
    {
        $operands = $this->getOperands($math);

        $serialized = array_map(
            $this->serializeOperand(...),
            $operands,
        );

        return implode(' '.$operator.' ', $serialized);
    }

    /**
     * Serialize a Variable.
     *
     * @param  BuilderVariable|Variable $variable The variable to serialize
     * @return string                   The serialized variable name
     */
    private function serializeVariable(Variable|BuilderVariable $variable): string
    {
        $name = $variable->getName();

        // If variable has no name, check if its value is an operator
        if ($name === null) {
            $value = $variable->getValue();

            // If the value is a math operator, serialize it as an operand
            if ($value instanceof Addition
                || $value instanceof Subtraction
                || $value instanceof Multiplication
                || $value instanceof Division
                || $value instanceof Modulo
                || $value instanceof Exponentiate
                || $value instanceof Negation
            ) {
                return $this->serializeOperand($value);
            }

            return $this->serializeValue($value);
        }

        return $name;
    }

    /**
     * Serialize a raw value (string, number, boolean, null, array).
     *
     * @param  mixed  $value The value to serialize
     * @return string The serialized value
     */
    private function serializeValue(mixed $value): string
    {
        if (is_string($value)) {
            return sprintf('"%s"', addslashes($value));
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (null === $value) {
            return 'null';
        }

        if (is_array($value)) {
            $elements = array_map(
                $this->serializeValue(...),
                array_values($value), // Re-index to remove keys
            );

            return sprintf('[%s]', implode(', ', $elements));
        }

        // Fallback for objects or other types
        if (is_numeric($value)) {
            return (string) $value;
        }

        throw new LogicException(sprintf('Cannot cast value to string: %s', gettype($value)));
    }

    /**
     * Wrap expression in parentheses if needed based on operator precedence.
     *
     * @param  Proposition $proposition The proposition to potentially wrap
     * @param  string      $parentOp    The parent operator
     * @return string      The wrapped or unwrapped expression
     */
    private function wrapIfNeeded(Proposition $proposition, string $parentOp): string
    {
        $needsWrap = match (true) {
            // Wrap AND inside OR: (a and b) or c
            $proposition instanceof LogicalAnd && $parentOp === 'or' => true,
            // Wrap OR inside AND: (a or b) and c
            $proposition instanceof LogicalOr && $parentOp === 'and' => true,
            // Wrap AND/OR inside XOR/NAND/NOR
            ($proposition instanceof LogicalAnd || $proposition instanceof LogicalOr)
                && in_array($parentOp, ['xor', 'nand', 'nor'], true) => true,
            default => false,
        };

        $serialized = $this->serializeProposition($proposition);

        return $needsWrap ? sprintf('(%s)', $serialized) : $serialized;
    }
}
