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
use Cline\Ruler\Variables\Concerns\ResolvesPropertyValue;
use Cline\Ruler\Variables\VariableOperand;
use LogicException;
use Override;

use function array_key_exists;

/**
 * RuleBuilder-enhanced property accessor for nested variable data.
 *
 * VariableProperty implements VariableOperand to access properties, methods, or array offsets
 * of a parent variable's resolved value. During evaluation, it resolves the parent
 * variable first, then extracts the named property from the result using a prioritized
 * lookup strategy.
 *
 * The RuleBuilder integration provides the same fluent interface as Variable, enabling
 * property access to be chained with operators and comparisons without verbose object
 * instantiation.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @implements ArrayAccess<string, mixed>
 */
final class VariableProperty implements ArrayAccess, VariableOperand
{
    use FluentVariableOperators;
    use ResolvesPropertyValue;

    /**
     * Cache of nested VariableProperty instances created for this property.
     *
     * @var array<string, self>
     */
    private array $properties = [];

    /**
     * The RuleBuilder instance that provides operator resolution.
     */
    private readonly RuleBuilder $ruleBuilder;

    /**
     * Create a new VariableProperty instance.
     *
     * @param VariableOperand $parent The parent VariableOperand instance whose resolved value will
     *                                be accessed to extract this property. During evaluation,
     *                                the parent is resolved first, then this property name is
     *                                used to access a method, property, or array key on the
     *                                parent's resolved value.
     * @param null|string     $name   The property name to access on the parent's resolved value.
     *                                Used for method calls, property access, or array key lookup.
     * @param mixed           $value  default value to return if the property cannot be resolved
     *                                from the parent variable's value
     */
    public function __construct(
        private readonly VariableOperand $parent,
        private readonly ?string $name = null,
        private mixed $value = null,
    ) {
        $this->ruleBuilder = $this->resolveRuleBuilder($parent);
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
     * Get the property name to access on the parent's resolved value.
     *
     * @return null|string the property name
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set the default value for this property.
     *
     * @param mixed $value the default value to use when the property cannot be resolved
     */
    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }

    /**
     * Get the default value for this property.
     *
     * @return mixed the default value
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Get or create a nested VariableProperty for accessing further nested data.
     *
     * @param  string $name  the property name to access on the property's value
     * @param  mixed  $value optional default value if the property doesn't exist
     * @return self   the cached or newly created VariableProperty instance
     */
    public function getProperty(string $name, mixed $value = null): self
    {
        if (!array_key_exists($name, $this->properties)) {
            $this->properties[$name] = new self($this, $name, $value);
        }

        return $this->properties[$name];
    }

    /**
     * Check whether a nested VariableProperty has been defined for this property.
     *
     * @param  string $name the property name to check
     * @return bool   true if the property has been created, false otherwise
     */
    public function offsetExists(mixed $name): bool
    {
        return array_key_exists($name, $this->properties);
    }

    /**
     * Get or create a nested VariableProperty using array access syntax.
     *
     * @param  string $name the property name to retrieve
     * @return self   the cached or newly created VariableProperty instance
     */
    public function offsetGet(mixed $name): self
    {
        return $this->getProperty($name);
    }

    /**
     * Set the default value of a nested VariableProperty using array access syntax.
     *
     * @param string $name  the property name to set
     * @param mixed  $value the default value for the property
     */
    public function offsetSet(mixed $name, mixed $value): void
    {
        $this->getProperty($name)->setValue($value);
    }

    /**
     * Remove a nested VariableProperty reference from the cache.
     *
     * @param string $name the property name to remove
     */
    public function offsetUnset(mixed $name): void
    {
        unset($this->properties[$name]);
    }

    /**
     * Get the parent variable whose value will be accessed.
     */
    #[Override()]
    protected function getParent(): VariableOperand
    {
        return $this->parent;
    }

    /**
     * Resolve the RuleBuilder from the parent variable chain.
     *
     * Traverses up the parent chain until finding a Variable that has
     * a RuleBuilder instance directly available.
     *
     * @param  VariableOperand $parent the parent to start resolving from
     * @return RuleBuilder     the RuleBuilder instance
     */
    private function resolveRuleBuilder(VariableOperand $parent): RuleBuilder
    {
        if ($parent instanceof Variable) {
            return $parent->getRuleBuilder();
        }

        if ($parent instanceof self) {
            return $parent->getRuleBuilder();
        }

        // This should never happen with proper usage, but provides a clear error
        // @codeCoverageIgnoreStart
        throw new LogicException('Cannot resolve RuleBuilder from parent: parent must be Variable or VariableProperty');
        // @codeCoverageIgnoreEnd
    }
}
