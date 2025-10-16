<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Fixtures;

use ArrayAccess;
use Override;

use function array_key_exists;

/**
 * Test fixture: Object implementing ArrayAccess for testing VariableProperty ArrayAccess support.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class ArrayAccessObject implements ArrayAccess
{
    public function __construct(
        private array $data = [],
    ) {}

    #[Override()]
    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->data);
    }

    #[Override()]
    public function offsetGet(mixed $offset): mixed
    {
        return $this->data[$offset] ?? null;
    }

    #[Override()]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    #[Override()]
    public function offsetUnset(mixed $offset): void
    {
        unset($this->data[$offset]);
    }
}
