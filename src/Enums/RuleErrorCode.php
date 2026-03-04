<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Enums;

/**
 * Canonical machine-readable rule engine error codes.
 *
 * @author Brian Faust <brian@cline.sh>
 */
enum RuleErrorCode: string
{
    case CompileInvalidCombinator = 'compile.invalid_combinator';
    case CompileInvalidRuleStructure = 'compile.invalid_rule_structure';
    case CompileInvalidNotArity = 'compile.invalid_not_arity';
    case CompileCacheKeyGenerationFailed = 'compile.cache_key_generation_failed';
    case CompileUnknownOperator = 'compile.unknown_operator';
    case RuntimeEvaluationFailed = 'runtime.evaluation_failed';
}
