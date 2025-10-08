<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Fixtures;

use Stringable;

final class toStringable implements Stringable
{
    private $thingy;

    /**
     * @param mixed $foo
     */
    public function __construct($foo = null)
    {
        $this->thingy = $foo;
    }

    public function __toString(): string
    {
        return (string) $this->thingy;
    }
}
