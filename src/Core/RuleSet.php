<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Core;

use Cline\Ruler\Enums\ConflictResolutionStrategy;
use Cline\Ruler\Exceptions\DuplicateRuleIdException;
use Cline\Ruler\Exceptions\ForwardChainingLoopException;
use Cline\Ruler\Exceptions\RuleNotInSetException;

use function array_filter;
use function array_values;
use function spl_object_hash;
use function throw_if;
use function throw_unless;
use function uasort;

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
     * Runtime-disabled rules keyed by object hash.
     *
     * @var array<string, true>
     */
    private array $disabledRules = [];

    /**
     * Rule identifiers mapped to object hashes for uniqueness checks.
     *
     * @var array<string, string>
     */
    private array $ruleIds = [];

    /**
     * Create a new RuleSet with optional initial rules.
     *
     * @param array<int, Rule> $rules Optional array of Rule instances to add to the set
     *                                during construction. Duplicate rules are automatically
     *                                deduplicated using object hash.
     */
    public function __construct(
        array $rules = [],
        /**
         * Conflict resolution strategy used for execution ordering.
         */
        private ConflictResolutionStrategy $conflictResolutionStrategy = ConflictResolutionStrategy::InsertionOrder,
    ) {
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
        $id = $rule->getId();

        if (isset($this->ruleIds[$id]) && $this->ruleIds[$id] !== $hash) {
            throw DuplicateRuleIdException::forId($id);
        }

        $this->rules[$hash] = $rule;
        $this->ruleIds[$id] = $hash;

        if (isset($this->ruleOrder[$hash])) {
            return;
        }

        $this->ruleOrder[$hash] = $this->nextOrder++;
    }

    /**
     * Remove a rule from the set.
     */
    public function removeRule(Rule $rule): void
    {
        $hash = spl_object_hash($rule);
        $id = $rule->getId();

        unset($this->rules[$hash], $this->ruleOrder[$hash], $this->disabledRules[$hash]);

        if (!isset($this->ruleIds[$id]) || $this->ruleIds[$id] !== $hash) {
            return;
        }

        unset($this->ruleIds[$id]);
    }

    /**
     * Replace an existing rule instance while preserving insertion order.
     *
     * @throws RuleNotInSetException When the rule to replace is not in the set
     */
    public function replaceRule(Rule $existing, Rule $replacement): void
    {
        $existingHash = spl_object_hash($existing);
        throw_unless(isset($this->rules[$existingHash]), RuleNotInSetException::create());

        $replacementId = $replacement->getId();

        if (isset($this->ruleIds[$replacementId]) && $this->ruleIds[$replacementId] !== $existingHash) {
            throw DuplicateRuleIdException::forId($replacementId);
        }

        $existingId = $existing->getId();
        $order = $this->ruleOrder[$existingHash];
        $wasDisabled = isset($this->disabledRules[$existingHash]);

        unset($this->rules[$existingHash], $this->ruleOrder[$existingHash], $this->disabledRules[$existingHash]);

        $replacementHash = spl_object_hash($replacement);
        $this->rules[$replacementHash] = $replacement;
        $this->ruleOrder[$replacementHash] = $order;
        $this->ruleIds[$replacementId] = $replacementHash;

        if ($existingId !== $replacementId) {
            unset($this->ruleIds[$existingId]);
        }

        if (!$wasDisabled) {
            return;
        }

        $this->disabledRules[$replacementHash] = true;
    }

    /**
     * Clear all rules and lifecycle state.
     */
    public function clearRules(): void
    {
        $this->rules = [];
        $this->ruleOrder = [];
        $this->disabledRules = [];
        $this->ruleIds = [];
        $this->nextOrder = 0;
    }

    /**
     * Disable a rule at RuleSet level by instance or rule id.
     */
    public function disableRule(Rule|RuleId $rule): void
    {
        $hash = $this->resolveRuleHash($rule);

        if ($hash === null) {
            return;
        }

        $this->disabledRules[$hash] = true;
    }

    /**
     * Enable a previously disabled rule by instance or rule id.
     */
    public function enableRule(Rule|RuleId $rule): void
    {
        $hash = $this->resolveRuleHash($rule);

        if ($hash === null) {
            return;
        }

        unset($this->disabledRules[$hash]);
    }

    /**
     * Check if a rule is currently enabled in this RuleSet.
     */
    public function isRuleEnabled(Rule|RuleId $rule): bool
    {
        $hash = $this->resolveRuleHash($rule);

        return $hash !== null && !isset($this->disabledRules[$hash]);
    }

    /**
     * Get rules in current execution order.
     *
     * @return array<int, Rule>
     */
    public function getRules(): array
    {
        return $this->getOrderedRules();
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
     * @param  Context                $context the context containing variable values and facts
     *                                         used to evaluate and execute each rule
     * @return RuleSetExecutionReport Structured per-rule execution report for the pass
     */
    public function executeRules(Context $context): RuleSetExecutionReport
    {
        $results = [];

        foreach ($this->getOrderedRules() as $rule) {
            $results[] = $rule->execute($context);
        }

        return new RuleSetExecutionReport($results);
    }

    /**
     * Execute rules using forward chaining until no more rules fire.
     *
     * Re-evaluates the ordered rule set across multiple cycles. This allows
     * actions that mutate Context facts to activate additional rules in later
     * cycles.
     *
     * @param Context $context             Evaluation context (may be mutated by actions)
     * @param int     $maxCycles           Hard upper bound to prevent infinite loops
     * @param bool    $allowRepeatedFiring When false, each rule may fire at most once
     *
     * @throws ForwardChainingLoopException When the cycle limit is reached while rules still fire
     *
     * @return RuleSetExecutionReport Structured report for all forward-chaining cycles
     */
    public function executeForwardChaining(
        Context $context,
        int $maxCycles = 100,
        bool $allowRepeatedFiring = false,
    ): RuleSetExecutionReport {
        $firedRules = [];
        $results = [];
        $cycle = 0;

        do {
            $firedThisCycle = 0;

            foreach ($this->getOrderedRules() as $rule) {
                $hash = spl_object_hash($rule);

                if (!$allowRepeatedFiring && isset($firedRules[$hash])) {
                    continue;
                }

                $result = $rule->execute($context);
                $results[] = $result;

                if (!$result->actionExecuted) {
                    continue;
                }

                $firedRules[$hash] = true;
                ++$firedThisCycle;
            }

            ++$cycle;
        } while ($firedThisCycle > 0 && $cycle < $maxCycles);

        throw_if($firedThisCycle > 0, ForwardChainingLoopException::exceededMaxCycles());

        return new RuleSetExecutionReport($results, $cycle);
    }

    /**
     * Return rules in execution order after conflict resolution.
     *
     * @return array<int, Rule>
     */
    private function getOrderedRules(): array
    {
        if ($this->conflictResolutionStrategy === ConflictResolutionStrategy::InsertionOrder) {
            return array_values(
                array_filter(
                    $this->rules,
                    fn (Rule $rule): bool => !isset($this->disabledRules[spl_object_hash($rule)]),
                ),
            );
        }

        $rules = array_filter(
            $this->rules,
            fn (Rule $rule): bool => !isset($this->disabledRules[spl_object_hash($rule)]),
        );

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

    /**
     * Resolve rule object hash from a Rule instance or rule identifier.
     */
    private function resolveRuleHash(Rule|RuleId $rule): ?string
    {
        if ($rule instanceof Rule) {
            $hash = spl_object_hash($rule);

            return isset($this->rules[$hash]) ? $hash : null;
        }

        return $this->ruleIds[$rule->toString()] ?? null;
    }
}
