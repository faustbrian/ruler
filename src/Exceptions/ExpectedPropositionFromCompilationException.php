<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Exceptions;

use function sprintf;

final class ExpectedPropositionFromCompilationException extends CompilerException
{
    public static function forExpression(string $expression): self
    {
        return new self(sprintf('%s must compile to a Proposition', $expression));
    }
}
