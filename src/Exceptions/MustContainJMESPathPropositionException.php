<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Exceptions;

final class MustContainJMESPathPropositionException extends SerializerException
{
    public static function create(): self
    {
        return new self('Rule must contain a JMESPathProposition to serialize to JMESPath');
    }
}
