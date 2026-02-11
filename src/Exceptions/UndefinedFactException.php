<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Exceptions;

use function sprintf;

final class UndefinedFactException extends ContextException
{
    public static function forFact(string $name): self
    {
        return new self(sprintf('Fact "%s" is not defined.', $name));
    }
}
