<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Core;

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
    protected $rules = [];

    /**
     * Create a new RuleSet with optional initial rules.
     *
     * @param array<int, Rule> $rules Optional array of Rule instances to add to the set
     *                                during construction. Duplicate rules are automatically
     *                                deduplicated using object hash.
     */
    public function __construct(array $rules = [])
    {
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
        $this->rules[spl_object_hash($rule)] = $rule;
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
        foreach ($this->rules as $rule) {
            $rule->execute($context);
        }
    }
}
