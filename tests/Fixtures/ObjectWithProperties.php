<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Fixtures;

/**
 * Test fixture: Object with public properties for testing VariableProperty property access.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class ObjectWithProperties
{
    public function __construct(
        public string $name = 'Test User',
        public string $email = 'test@example.com',
        public int $age = 30,
        public bool $active = true,
    ) {}
}
