<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Variables;

use Cline\Ruler\Variables\Concerns\ResolvesPropertyValue;
use Override;

/**
 * Property accessor for nested variable data.
 *
 * VariableProperty implements VariableOperand to access properties, methods, or array offsets
 * of a parent variable's resolved value. During evaluation, it resolves the parent
 * variable first, then extracts the named property from the result using a prioritized
 * lookup strategy.
 *
 * This enables accessing nested data structures in rule conditions without complex
 * value extraction logic, supporting object properties, methods, ArrayAccess offsets,
 * and array keys through a unified interface.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class VariableProperty implements VariableOperand
{
    use ResolvesPropertyValue;

    /**
     * Create a new VariableProperty instance.
     *
     * @param VariableOperand $parent the parent variable whose resolved value will
     *                                be accessed to extract this property. During evaluation,
     *                                this parent is resolved first, then the specified property
     *                                is extracted from the resolved value using the name.
     * @param null|string     $name   the property name to access on the parent's resolved value.
     *                                Used for method calls, property access, or array key lookup.
     *                                The name determines which extraction strategy is used.
     * @param mixed           $value  default value to return if the property cannot be resolved
     *                                from the parent variable's value. Used as a fallback when
     *                                none of the property extraction strategies succeed.
     */
    public function __construct(
        private readonly VariableOperand $parent,
        private readonly ?string $name = null,
        private mixed $value = null,
    ) {}

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
     * Get the parent variable whose value will be accessed.
     */
    #[Override()]
    protected function getParent(): VariableOperand
    {
        return $this->parent;
    }
}
