<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Tests\Exceptions;

use InvalidArgumentException;

use function sprintf;

/**
 * @author Brian Faust <brian@cline.sh>
 */
final class UnknownFactDefinitionException extends InvalidArgumentException
{
    public static function forDefinition(string $definition): self
    {
        return new self(sprintf('Unknown fact definition "%s".', $definition));
    }
}
