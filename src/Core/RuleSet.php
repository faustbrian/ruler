<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Core;

use Cline\Ruler\Enums\ConflictResolutionStrategy;
use RuntimeException;

use function array_values;
use function uasort;
use function spl_object_hash;

/**
 * Collection for managing and executing multiple rules.
 *
 * RuleSet provides a container for organizing related rules and executing them
 * together against a shared context. Rules are stored using their object hash as
 * the key, ensuring each rule appears only once in the set even if added multiple times.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class RuleSet
{
    /**
     * Collection of rules managed by this RuleSet, keyed by object hash.
     *
     * @var array<string, Rule>
     */
    private array $rules = [];

    /**
     * Insertion order index for stable conflict resolution tie-breaking.
     *
     * @var array<string, int>
     */
    private array $ruleOrder = [];

    /**
     * Next insertion order value.
     */
    private int $nextOrder = 0;

    /**
     * Conflict resolution strategy used for execution ordering.
     */
    private ConflictResolutionStrategy $conflictResolutionStrategy;

    /**
     * Create a new RuleSet with optional initial rules.
     *
     * @param array<int, Rule> $rules Optional array of Rule instances to add to the set
     *                                during construction. Duplicate rules are automatically
     *                                deduplicated using object hash.
     */
    public function __construct(
        array $rules = [],
        ConflictResolutionStrategy $conflictResolutionStrategy = ConflictResolutionStrategy::InsertionOrder,
    )
    {
        $this->conflictResolutionStrategy = $conflictResolutionStrategy;

        foreach ($rules as $rule) {
            $this->addRule($rule);
        }
    }

    /**
     * Add a rule to the RuleSet.
     *
     * Rules are stored using their object hash as the key, ensuring each unique
     * rule instance appears only once in the set. Adding the same rule multiple
     * times has no effect beyond the first addition.
     *
     * @param Rule $rule the Rule instance to add to the set
     */
    public function addRule(Rule $rule): void
    {
        $hash = spl_object_hash($rule);
        $this->rules[$hash] = $rule;

        if (!isset($this->ruleOrder[$hash])) {
            $this->ruleOrder[$hash] = $this->nextOrder++;
        }
    }

    /**
     * Set conflict resolution strategy for subsequent executions.
     */
    public function setConflictResolutionStrategy(ConflictResolutionStrategy $strategy): self
    {
        $this->conflictResolutionStrategy = $strategy;

        return $this;
    }

    /**
     * Get current conflict resolution strategy.
     */
    public function getConflictResolutionStrategy(): ConflictResolutionStrategy
    {
        return $this->conflictResolutionStrategy;
    }

    /**
     * Execute all rules in the RuleSet against the given context.
     *
     * Iterates through all rules in the set, executing each one with the provided
     * context. Each rule evaluates its condition and executes its action if the
     * condition is satisfied.
     *
     * @param Context $context the context containing variable values and facts
     *                         used to evaluate and execute each rule
     */
    public function executeRules(Context $context): void
    {
        foreach ($this->getOrderedRules() as $rule) {
            $rule->execute($context);
        }
    }

    /**
     * Execute rules using forward chaining until no more rules fire.
     *
     * Re-evaluates the ordered rule set across multiple cycles. This allows
     * actions that mutate Context facts to activate additional rules in later
     * cycles.
     *
     * @param  Context $context             Evaluation context (may be mutated by actions)
     * @param  int     $maxCycles           Hard upper bound to prevent infinite loops
     * @param  bool    $allowRepeatedFiring When false, each rule may fire at most once
     *
     * @throws RuntimeException When the cycle limit is reached while rules still fire
     *
     * @return int Number of fired rules across all cycles
     */
    public function executeForwardChaining(
        Context $context,
        int $maxCycles = 100,
        bool $allowRepeatedFiring = false,
    ): int {
        $firedRules = [];
        $totalFired = 0;
        $cycle = 0;

        do {
            $firedThisCycle = 0;

            foreach ($this->getOrderedRules() as $rule) {
                $hash = spl_object_hash($rule);

                if (!$allowRepeatedFiring && isset($firedRules[$hash])) {
                    continue;
                }

                if (!$rule->evaluate($context)) {
                    continue;
                }

                $rule->execute($context);
                $firedRules[$hash] = true;
                ++$firedThisCycle;
                ++$totalFired;
            }

            ++$cycle;
        } while ($firedThisCycle > 0 && $cycle < $maxCycles);

        if ($firedThisCycle > 0) {
            throw new RuntimeException('Forward chaining exceeded max cycles. Potential rule loop detected.');
        }

        return $totalFired;
    }

    /**
     * Return rules in execution order after conflict resolution.
     *
     * @return array<int, Rule>
     */
    private function getOrderedRules(): array
    {
        if ($this->conflictResolutionStrategy === ConflictResolutionStrategy::InsertionOrder) {
            return array_values($this->rules);
        }

        $rules = $this->rules;

        uasort($rules, function (Rule $left, Rule $right): int {
            $leftHash = spl_object_hash($left);
            $rightHash = spl_object_hash($right);
            $leftOrder = $this->ruleOrder[$leftHash];
            $rightOrder = $this->ruleOrder[$rightHash];

            $priorityComparison = match ($this->conflictResolutionStrategy) {
                ConflictResolutionStrategy::PriorityHighFirst => $right->getPriority() <=> $left->getPriority(),
                ConflictResolutionStrategy::PriorityLowFirst => $left->getPriority() <=> $right->getPriority(),
                default => 0,
            };

            if ($priorityComparison !== 0) {
                return $priorityComparison;
            }

            return $leftOrder <=> $rightOrder;
        });

        return array_values($rules);
    }
}
