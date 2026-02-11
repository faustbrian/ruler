<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Core;

/**
 * Structured outcome for RuleEvaluator executions.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class RuleEvaluatorReport
{
    /**
     * @param array<string, mixed> $values
     */
    public function __construct(
        private bool $result,
        private RuleExecutionResult $ruleResult,
        private array $values,
    ) {}

    /**
     * Boolean outcome of rule evaluation.
     */
    public function getResult(): bool
    {
        return $this->result;
    }

    /**
     * Detailed top-level rule execution result.
     */
    public function getRuleResult(): RuleExecutionResult
    {
        return $this->ruleResult;
    }

    /**
     * Input values used for this evaluation.
     *
     * @return array<string, mixed>
     */
    public function getValues(): array
    {
        return $this->values;
    }
}
