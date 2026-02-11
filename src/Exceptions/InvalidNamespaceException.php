<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Exceptions;

use function get_debug_type;
use function sprintf;

final class InvalidNamespaceException extends BuilderException
{
    public static function forValue(mixed $value): self
    {
        return new self(sprintf('Namespace argument must be a string, got %s', get_debug_type($value)));
    }
}
