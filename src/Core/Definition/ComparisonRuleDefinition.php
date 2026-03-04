<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Core\Definition;

/**
 * Typed rule definition for single field/operator/value comparisons.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class ComparisonRuleDefinition implements RuleDefinition
{
    /**
     * @param string $field    Field path to read from context
     * @param string $operator Operator method name to invoke on the field variable
     * @param mixed  $value    Comparison operand value
     */
    public function __construct(
        public string $field,
        public string $operator,
        public mixed $value,
    ) {}
}
