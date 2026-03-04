<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Builder;

use ArrayAccess;
use Cline\Ruler\Builder\Concerns\FluentVariableOperators;
use Cline\Ruler\Core\Context;
use Cline\Ruler\Values\Value;
use Cline\Ruler\Variables\Variable as BaseVariable;
use Cline\Ruler\Variables\VariableOperand;

use function array_key_exists;

/**
 * Enhanced Variable with fluent interface for building rule conditions.
 *
 * Variables are placeholders in propositions and comparison operators that get
 * resolved to concrete values during evaluation. This RuleBuilder-enhanced version
 * uses composition to wrap a base Variable with a fluent interface for creating
 * variable properties, operators, and rules without verbose object instantiation.
 *
 * The ArrayAccess implementation enables array-style property access:
 * `$variable['propertyName']` creates or retrieves a VariableProperty.
 *
 * Magic method __call() allows dynamic operator registration and invocation,
 * enabling custom operators to be called as methods on Variable instances.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @implements ArrayAccess<string, mixed>
 */
final class Variable implements ArrayAccess, VariableOperand
{
    use FluentVariableOperators;

    /**
     * Cache of VariableProperty instances created for this variable.
     *
     * @var array<string, VariableProperty>
     */
    private array $properties = [];

    /**
     * The underlying variable that holds name and value.
     */
    private readonly BaseVariable $variable;

    /**
     * Create a new RuleBuilder Variable instance.
     *
     * @param RuleBuilder $ruleBuilder Reference to the RuleBuilder instance that manages this
     *                                 variable and provides access to registered operators.
     *                                 Used to resolve dynamic operator calls via __call() magic
     *                                 method and to enable nested variable property access.
     * @param null|string $name        The variable name used for context lookups. When null,
     *                                 the variable can only resolve from its default value.
     * @param mixed       $value       default value returned when the variable name is not
     *                                 found in the evaluation context
     */
    public function __construct(
        private readonly RuleBuilder $ruleBuilder,
        ?string $name = null,
        mixed $value = null,
    ) {
        $this->variable = new BaseVariable($name, $value);
    }

    /**
     * Retrieve the RuleBuilder instance that manages this variable.
     *
     * @return RuleBuilder the RuleBuilder instance
     */
    public function getRuleBuilder(): RuleBuilder
    {
        return $this->ruleBuilder;
    }

    /**
     * Get the variable name used for context lookups.
     *
     * @return null|string the variable name
     */
    public function getName(): ?string
    {
        return $this->variable->getName();
    }

    /**
     * Set the default value for this variable.
     *
     * @param mixed $value the default value to use when the variable name is not
     *                     found in the evaluation context
     */
    public function setValue(mixed $value): void
    {
        $this->variable->setValue($value);
    }

    /**
     * Get the default value for this variable.
     *
     * @return mixed the default value
     */
    public function getValue(): mixed
    {
        return $this->variable->getValue();
    }

    /**
     * Resolve this variable to a concrete Value using the current context.
     *
     * Delegates to the underlying Variable for value resolution.
     *
     * @param  Context $context the evaluation context containing variable values
     * @return Value   the resolved value wrapped in a Value instance
     */
    public function prepareValue(Context $context): Value
    {
        return $this->variable->prepareValue($context);
    }

    /**
     * Get or create a VariableProperty for accessing nested data.
     *
     * VariableProperties enable accessing methods, array indexes, and object properties
     * of this variable's resolved value. Properties are cached to ensure the same
     * VariableProperty instance is returned for repeated accesses.
     *
     * @param  string           $name  the property name to access on the variable's value
     * @param  mixed            $value optional default value if the property doesn't exist
     * @return VariableProperty the cached or newly created VariableProperty instance
     */
    public function getProperty(string $name, mixed $value = null): VariableProperty
    {
        if (!array_key_exists($name, $this->properties)) {
            $this->properties[$name] = new VariableProperty($this, $name, $value);
        }

        return $this->properties[$name];
    }

    /**
     * Check whether a VariableProperty has been defined for this variable.
     *
     * Part of the ArrayAccess interface implementation for fluent property access.
     *
     * @param  string $name the property name to check
     * @return bool   true if the property has been created, false otherwise
     */
    public function offsetExists(mixed $name): bool
    {
        return array_key_exists($name, $this->properties);
    }

    /**
     * Get or create a VariableProperty using array access syntax.
     *
     * Part of the ArrayAccess interface implementation. Enables accessing properties
     * using array syntax: `$variable['propertyName']`.
     *
     * @see getProperty
     * @param  string           $name the property name to retrieve
     * @return VariableProperty the cached or newly created VariableProperty instance
     */
    public function offsetGet(mixed $name): VariableProperty
    {
        return $this->getProperty($name);
    }

    /**
     * Set the default value of a VariableProperty using array access syntax.
     *
     * Part of the ArrayAccess interface implementation. Enables setting property
     * default values using array syntax: `$variable['propertyName'] = $value`.
     *
     * @see setValue
     * @param string $name  the property name to set
     * @param mixed  $value the default value for the property
     */
    public function offsetSet(mixed $name, mixed $value): void
    {
        $this->getProperty($name)->setValue($value);
    }

    /**
     * Remove a VariableProperty reference from the cache.
     *
     * Part of the ArrayAccess interface implementation. Enables removing properties
     * using array syntax: `unset($variable['propertyName'])`.
     *
     * @param string $name the property name to remove
     */
    public function offsetUnset(mixed $name): void
    {
        unset($this->properties[$name]);
    }
}
