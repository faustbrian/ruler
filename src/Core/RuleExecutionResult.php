<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Core;

/**
 * Structured execution result for a single rule evaluation attempt.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final readonly class RuleExecutionResult
{
    /**
     * Create a new rule execution result.
     */
    public function __construct(
        public ?string $ruleId,
        public ?string $ruleName,
        public int $priority,
        public bool $enabled,
        public bool $matched,
        public bool $actionExecuted,
    ) {}
}
