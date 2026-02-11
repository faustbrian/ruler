<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Exceptions;

use function sprintf;

/**
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidOperandCardinalityException extends OperatorException
{
    public static function forOperator(string $class, string $expected, int $actual): self
    {
        return new self(sprintf('%s expects %s operands, got %d', $class, $expected, $actual));
    }
}
