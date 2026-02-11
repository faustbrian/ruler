<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Core;

use function array_filter;
use function count;

/**
 * Structured report for a RuleSet execution pass.
 *
 * @author Brian Faust <brian@cline.sh>
 * @psalm-immutable
 */
final readonly class RuleSetExecutionReport
{
    /**
     * @param array<int, RuleExecutionResult> $results
     */
    public function __construct(
        private array $results,
        private int $cycles = 1,
    ) {}

    /**
     * @return array<int, RuleExecutionResult>
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * Number of matched rules (regardless of action callback presence).
     */
    public function getMatchedCount(): int
    {
        return count(
            array_filter(
                $this->results,
                static fn (RuleExecutionResult $result): bool => $result->matched,
            ),
        );
    }

    /**
     * Number of actions executed during the pass.
     */
    public function getActionExecutionCount(): int
    {
        return count(
            array_filter(
                $this->results,
                static fn (RuleExecutionResult $result): bool => $result->actionExecuted,
            ),
        );
    }

    /**
     * Number of cycles executed to produce this report.
     */
    public function getCycleCount(): int
    {
        return $this->cycles;
    }
}
