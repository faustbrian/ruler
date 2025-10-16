<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Builder;

use ArrayAccess;
use Cline\Ruler\Builder\Variable;
use Cline\Ruler\Core\Proposition;
use Cline\Ruler\Core\Rule;
use Cline\Ruler\Operators\Logical\LogicalAnd;
use Cline\Ruler\Operators\Logical\LogicalNot;
use Cline\Ruler\Operators\Logical\LogicalOr;
use Cline\Ruler\Operators\Logical\LogicalXor;
use InvalidArgumentException;
use LogicException;

use function array_key_exists;
use function array_keys;
use function class_exists;
use function is_string;
use function sprintf;
use function throw_unless;
use function ucfirst;

/**
 * Fluent DSL for building rules and evaluating propositions.
 *
 * RuleBuilder provides a domain-specific language for constructing rules with
 * intuitive syntax. It manages variables, registers custom operators, and creates
 * complex logical conditions using a fluent interface that reads like natural language.
 *
 * The ArrayAccess implementation enables variable access using array syntax:
 * `$builder['variableName']` creates or retrieves a Variable instance.
 *
 * Custom operators can be registered via namespaces, allowing the DSL to be
 * extended with domain-specific comparison and logical operators.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @implements ArrayAccess<string, Variable>
 */
final class RuleBuilder implements ArrayAccess
{
    /**
     * Cache of Variable instances created by this RuleBuilder.
     *
     * @var array<string, Variable>
     */
    private array $variables = [];

    /**
     * Registered operator namespaces for dynamic operator resolution.
     *
     * @var array<string, bool>
     */
    private array $operatorNamespaces = [];

    /**
     * Create a new Rule with the given condition and optional action.
     *
     * @param  Proposition   $condition the propositional condition that determines when
     *                                  the rule is satisfied and the action should execute
     * @param  null|callable $action    Optional callback to execute when the condition
     *                                  evaluates to true. Receives no arguments and its
     *                                  return value is ignored.
     * @return Rule          the constructed Rule instance
     */
    public function create(Proposition $condition, $action = null): Rule
    {
        return new Rule($condition, $action);
    }

    /**
     * Register a namespace for dynamic operator resolution.
     *
     * Registered namespaces are searched when Variables invoke operators via magic
     * methods. This enables extending the DSL with custom operators without modifying
     * the core library.
     *
     * Note: Operator namespaces are case-sensitive and depend on filesystem conventions.
     *
     * @param string $namespace the fully-qualified namespace containing operator classes
     *
     * @throws InvalidArgumentException when the namespace parameter is not a string
     *
     * @return self returns this RuleBuilder for method chaining
     */
    public function registerOperatorNamespace(string $namespace): self
    {
        /** @phpstan-ignore function.alreadyNarrowedType */
        throw_unless(is_string($namespace), InvalidArgumentException::class, 'Namespace argument must be a string');

        $this->operatorNamespaces[$namespace] = true;

        return $this;
    }

    /**
     * Create a logical AND operator that requires all propositions to be true.
     *
     * @param  Proposition ...$props One or more propositions that must all evaluate
     *                               to true for the AND operation to succeed.
     * @return LogicalAnd  the logical AND operator instance
     */
    public function logicalAnd(Proposition ...$props): LogicalAnd
    {
        return new LogicalAnd($props);
    }

    /**
     * Create a logical OR operator that requires at least one proposition to be true.
     *
     * @param  Proposition ...$props One or more propositions where at least one must
     *                               evaluate to true for the OR operation to succeed.
     * @return LogicalOr   the logical OR operator instance
     */
    public function logicalOr(Proposition ...$props): LogicalOr
    {
        return new LogicalOr($props);
    }

    /**
     * Create a logical NOT operator that inverts the proposition's result.
     *
     * @param  Proposition $prop the single proposition to negate
     * @return LogicalNot  the logical NOT operator instance
     */
    public function logicalNot(Proposition $prop): LogicalNot
    {
        return new LogicalNot([$prop]);
    }

    /**
     * Create a logical XOR operator that requires exactly one proposition to be true.
     *
     * @param  Proposition ...$props One or more propositions where exactly one must
     *                               evaluate to true for the XOR operation to succeed.
     * @return LogicalXor  the logical XOR operator instance
     */
    public function logicalXor(Proposition ...$props): LogicalXor
    {
        return new LogicalXor($props);
    }

    /**
     * Check whether a Variable has been created for the given name.
     *
     * Part of the ArrayAccess interface implementation.
     *
     * @param  string $name the variable name to check
     * @return bool   true if the variable exists, false otherwise
     */
    public function offsetExists($name): bool
    {
        return array_key_exists($name, $this->variables);
    }

    /**
     * Get or create a Variable using array access syntax.
     *
     * Part of the ArrayAccess interface. Enables variable access using array syntax:
     * `$builder['variableName']`.
     *
     * @param  string   $name the variable name to retrieve or create
     * @return Variable the cached or newly created Variable instance
     */
    public function offsetGet($name): Variable
    {
        if (!array_key_exists($name, $this->variables)) {
            $this->variables[$name] = new Variable($this, $name);
        }

        return $this->variables[$name];
    }

    /**
     * Set the default value of a Variable using array access syntax.
     *
     * Part of the ArrayAccess interface. Enables setting variable default values
     * using array syntax: `$builder['variableName'] = $value`.
     *
     * @param string $name  the variable name
     * @param mixed  $value the default value for the variable
     */
    public function offsetSet($name, $value): void
    {
        $this->offsetGet($name)->setValue($value);
    }

    /**
     * Remove a Variable from the RuleBuilder's cache.
     *
     * Part of the ArrayAccess interface. Enables removing variables using array
     * syntax: `unset($builder['variableName'])`.
     *
     * @param string $name the variable name to remove
     */
    public function offsetUnset($name): void
    {
        unset($this->variables[$name]);
    }

    /**
     * Find an operator class in the registered namespaces.
     *
     * Searches all registered operator namespaces for a class matching the given
     * operator name (case-insensitive, with first letter capitalized). This enables
     * Variables to dynamically invoke operators via magic methods.
     *
     * @param string $name the operator method name to find
     *
     * @throws LogicException when no matching operator is found in any registered namespace
     *
     * @return string the fully-qualified operator class name
     */
    public function findOperator(string $name): string
    {
        $operator = ucfirst($name);

        /** @var string $namespace */
        foreach (array_keys($this->operatorNamespaces) as $namespace) {
            $class = $namespace.'\\'.$operator;

            if (class_exists($class)) {
                return $class;
            }
        }

        throw new LogicException(sprintf('Unknown operator: "%s"', $name));
    }
}
