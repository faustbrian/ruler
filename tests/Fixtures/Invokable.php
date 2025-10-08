<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Fixtures;

final class Invokable
{
    /**
     * @param mixed $value
     */
    public function __invoke($value = null)
    {
        return new Fact($value);
    }
}
