<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Fixtures;

/**
 * @author Brian Faust <brian@cline.sh>
 */
final class Fact
{
    public $value;

    /**
     * @param mixed $value
     */
    public function __construct($value = null)
    {
        if ($value !== null) {
            $this->value = $value;
        }
    }
}
