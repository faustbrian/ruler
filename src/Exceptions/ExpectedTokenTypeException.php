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
final class ExpectedTokenTypeException extends ParserException
{
    public static function forType(string $expected, string $actual): self
    {
        return new self(sprintf('Expected %s, got %s', $expected, $actual));
    }
}
