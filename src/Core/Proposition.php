<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Core;

/**
 * Represents a propositional statement that can be evaluated.
 *
 * Defines the contract for all propositional logic statements in the rule
 * evaluation system. Propositions are boolean expressions that evaluate to
 * true or false based on the provided context values.
 *
 * @author Brian Faust <brian@cline.sh>
 */
interface Proposition
{
    /**
     * Evaluates the proposition with the given context.
     *
     * Processes the propositional logic statement using values from the
     * provided context to determine whether the proposition is satisfied.
     * The evaluation result determines whether the rule condition passes.
     *
     * @param  Context $context Evaluation context containing variable values and state
     *                          used to resolve values and evaluate the proposition
     * @return bool    Returns true if the proposition is satisfied, false otherwise
     */
    public function evaluate(Context $context): bool;
}
