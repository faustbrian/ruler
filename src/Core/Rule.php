<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Core;

use LogicException;

use function is_callable;
use function throw_unless;

/**
 * Represents a conditional rule with an optional action.
 *
 * A Rule combines a propositional condition with an optional callback action
 * that executes when the condition evaluates to true. Rules are the core
 * building blocks for implementing business logic and conditional workflows
 * in the Ruler library.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class Rule implements Proposition
{
    /**
     * The optional action to execute when the rule condition is satisfied.
     *
     * @var null|callable
     */
    private $action;

    /**
     * Create a new Rule instance.
     *
     * @param Proposition   $condition The propositional condition that determines whether
     *                                 this rule is satisfied. The condition is evaluated
     *                                 against a Context to produce a boolean result.
     * @param null|callable $action    Optional callback to execute when the condition
     *                                 evaluates to true. The callback receives no arguments
     *                                 and its return value is ignored.
     */
    public function __construct(
        private readonly Proposition $condition,
        $action = null,
    ) {
        $this->action = $action;
    }

    /**
     * Evaluate the rule condition against the given context.
     *
     * @param  Context $context the context containing variable values and facts
     *                          used to evaluate the propositional condition
     * @return bool    true if the rule condition is satisfied, false otherwise
     */
    public function evaluate(Context $context): bool
    {
        return $this->condition->evaluate($context);
    }

    /**
     * Execute the rule by evaluating its condition and running the action if satisfied.
     *
     * The rule's condition is first evaluated against the provided context. If the
     * condition evaluates to true and an action callback is defined, the action
     * is executed. The action must be callable, otherwise an exception is thrown.
     *
     * @param Context $context the context containing variable values and facts
     *                         used to evaluate the rule
     *
     * @throws LogicException when the action is defined but not callable
     */
    public function execute(Context $context): void
    {
        if ($this->evaluate($context) && $this->action !== null) {
            throw_unless(is_callable($this->action), LogicException::class, 'Rule actions must be callable.');

            ($this->action)();
        }
    }
}
