<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Fixtures;

use function sprintf;

/**
 * Test fixture: Object with callable methods for testing VariableProperty method access.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class ObjectWithMethods
{
    public function __construct(
        private string $firstName = 'John',
        private string $lastName = 'Doe',
    ) {}

    public function getFullName(): string
    {
        return sprintf('%s %s', $this->firstName, $this->lastName);
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function isActive(): bool
    {
        return true;
    }

    public function getCount(): int
    {
        return 42;
    }
}
