<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Variables;

use Cline\Ruler\Core\Context;
use Cline\Ruler\Values\Value;
use Illuminate\Support\Arr;

use function str_contains;

/**
 * Deferred value reference resolved from Context at evaluation time.
 *
 * Supports RuleEvaluator value semantics where string values may refer to
 * context fields (including dot notation) or fall back to string literals.
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class ContextValueReference implements VariableOperand
{
    /**
     * Create a new deferred context value reference.
     */
    public function __construct(
        private string $reference,
    ) {}

    /**
     * Resolve the reference against runtime context with literal fallback.
     */
    public function prepareValue(Context $context): Value
    {
        if (str_contains($this->reference, '.')) {
            $values = [];

            foreach ($context->keys() as $key) {
                $values[$key] = $context[$key];
            }

            return new Value(Arr::get($values, $this->reference));
        }

        if ($context->offsetExists($this->reference)) {
            return new Value($context[$this->reference]);
        }

        return new Value($this->reference);
    }
}
