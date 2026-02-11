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

/**
 * @author Brian Faust <brian@cline.sh>
 */
final class RuntimeEvaluationFailedException extends RuleEvaluatorException
{
    /**
     * @param array<int, int|string> $path
     * @param array<string, mixed>   $details
     */
    public static function forReason(string $reason, array $path = [], array $details = [], ?Throwable $previous = null): self
    {
        return new self($reason, RuleErrorCode::RuntimeEvaluationFailed, RuleErrorPhase::Runtime, $path, $details, $previous);
    }
}
