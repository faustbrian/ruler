<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Exceptions;

use function sprintf;

final class InvalidFilterException extends ParserException
{
    public static function expectedArray(string $actualType): self
    {
        return new self(sprintf('Invalid filter: expected array, got %s', $actualType));
    }
}
