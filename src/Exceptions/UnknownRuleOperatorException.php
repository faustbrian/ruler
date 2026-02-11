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
use Throwable;

use function sprintf;

/**
 * @author Brian Faust <brian@cline.sh>
 */
final class UnknownRuleOperatorException extends RuleEvaluatorException
{
    /**
     * @param array<int, int|string> $path
     */
    public static function forOperator(string $operator, string $field, array $path = ['operator'], ?Throwable $previous = null): self
    {
        return new self(
            sprintf('Unknown operator: "%s"', $operator),
            RuleErrorCode::CompileUnknownOperator,
            RuleErrorPhase::Compile,
            $path,
            ['operator' => $operator, 'field' => $field],
            $previous,
        );
    }
}
