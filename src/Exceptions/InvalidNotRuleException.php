<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Exceptions;

use Cline\Ruler\Enums\RuleErrorCode;
use Cline\Ruler\Enums\RuleErrorPhase;

/**
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidNotRuleException extends RuleEvaluatorException
{
    /**
     * @param array<int, int|string> $path
     */
    public static function create(array $path = ['value']): self
    {
        return new self(
            'Logical NOT must have exactly one argument',
            RuleErrorCode::CompileInvalidNotArity,
            RuleErrorPhase::Compile,
            $path,
        );
    }
}
