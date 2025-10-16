<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Fixtures;

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Proposition;
use InvalidArgumentException;

use function is_callable;
use function throw_unless;

/**
 * @author Brian Faust <brian@cline.sh>
 */
final class CallbackProposition implements Proposition
{
    private $callback;

    /**
     * @param callable $callback
     */
    public function __construct($callback)
    {
        throw_unless(is_callable($callback), InvalidArgumentException::class, 'CallbackProposition expects a callable argument');

        $this->callback = $callback;
    }

    public function evaluate(Context $context): bool
    {
        return ($this->callback)($context);
    }
}
