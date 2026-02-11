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

use function sprintf;

final class InvalidCombinatorException extends RuleEvaluatorException
{
    /**
     * @param array<int, int|string> $path
     */
    public static function forCombinator(string $combinator, array $path = ['combinator']): self
    {
        return new self(
            sprintf('Invalid combinator: %s', $combinator),
            RuleErrorCode::CompileInvalidCombinator,
            RuleErrorPhase::Compile,
            $path,
            ['combinator' => $combinator],
        );
    }
}
