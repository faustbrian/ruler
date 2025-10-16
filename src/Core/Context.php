<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Ruler\Core;

use ArrayAccess;
use InvalidArgumentException;
use ReturnTypeWillChange;
use RuntimeException;
use SplObjectStorage;

use function array_key_exists;
use function array_keys;
use function is_callable;
use function is_object;
use function sprintf;
use function throw_if;
use function throw_unless;

/**
 * Stores and manages evaluation facts for rule processing with lazy evaluation support.
 *
 * Context acts as a dependency injection container for rule evaluation, storing facts
 * that can be either static values or lazy-evaluated callables. Supports shared values
 * (singleton pattern), protected callables (stored as literals), and frozen facts (immutable
 * after first access).
 *
 * Derived from Pimple, by Fabien Potencier:
 *
 * https://github.com/fabpot/Pimple
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @implements ArrayAccess<string, mixed>
 */
final class Context implements ArrayAccess
{
    /** @var array<string, true> Tracks all defined fact names for quick existence checks */
    private array $keys = [];

    /** @var array<string, mixed> Stores the current resolved or raw values for all facts */
    private array $values = [];

    /** @var array<string, true> Tracks facts that have been evaluated and frozen to prevent re-evaluation */
    private array $frozen = [];

    /** @var array<string, mixed> Stores original callable definitions for frozen shared facts */
    private array $raw = [];

    /** @var SplObjectStorage<object, mixed> Storage for callables marked as shared (singleton pattern) */
    private readonly mixed $shared;

    /** @var SplObjectStorage<object, mixed> Storage for callables marked as protected (literal values) */
    private readonly mixed $protected;

    /**
     * Create a new context instance.
     *
     * Optionally, bootstrap the context by passing an array of fact names and
     * values. Callable values will be lazily evaluated when accessed unless
     * explicitly marked as protected.
     *
     * @param array<string, mixed> $values Initial facts to populate the context with (default: empty array)
     */
    public function __construct(array $values = [])
    {
        $this->shared = new SplObjectStorage();
        $this->protected = new SplObjectStorage();

        foreach ($values as $key => $value) {
            $this->offsetSet($key, $value);
        }
    }

    /**
     * Check if a fact is defined.
     *
     * @param  string $name The unique name for the fact
     * @return bool   True if the fact exists, false otherwise
     */
    public function offsetExists($name): bool
    {
        return array_key_exists($name, $this->keys);
    }

    /**
     * Get the value of a fact with lazy evaluation support.
     *
     * For callable facts, this method evaluates the callable with the current
     * context. Shared callables are evaluated once and frozen. Protected callables
     * return the callable itself without evaluation.
     *
     * @param string $name The unique name for the fact
     *
     * @throws InvalidArgumentException If the fact name is not defined
     *
     * @return mixed The resolved value of the fact or the result of evaluating the callable
     */
    #[ReturnTypeWillChange()]
    public function offsetGet($name)
    {
        throw_unless($this->offsetExists($name), InvalidArgumentException::class, sprintf('Fact "%s" is not defined.', $name));

        $value = $this->values[$name];

        // If the value is already frozen, or if it's not callable, or if it's protected, return the raw value
        if (array_key_exists($name, $this->frozen) || !is_object($value) || $this->protected->contains($value) || !self::isCallable($value)) {
            return $value;
        }

        // If this is a shared value, resolve, freeze, and return the result
        if ($this->shared->contains($value)) {
            $this->frozen[$name] = true;
            $this->raw[$name] = $value;

            /** @phpstan-ignore callable.nonCallable */
            return $this->values[$name] = ($value)($this);
        }

        // Otherwise, resolve and return the result
        /** @phpstan-ignore callable.nonCallable */
        return ($value)($this);
    }

    /**
     * Set a fact name and value.
     *
     * A fact will be lazily evaluated if it is a Closure or invokable object.
     * To define a fact as a literal callable, use Context::protect.
     *
     * @param string $name  The unique name for the fact
     * @param mixed  $value The value or a closure to lazily define the value
     *
     * @throws RuntimeException If attempting to override a frozen fact
     */
    public function offsetSet($name, $value): void
    {
        throw_if(array_key_exists($name, $this->frozen), RuntimeException::class, sprintf('Cannot override frozen fact "%s".', $name));

        $this->keys[$name] = true;
        $this->values[$name] = $value;
    }

    /**
     * Unset a fact and clean up associated metadata.
     *
     * Removes the fact from all internal storage arrays and detaches any
     * associated callable from the shared and protected storages.
     *
     * @param string $name The unique name for the fact
     */
    public function offsetUnset($name): void
    {
        if ($this->offsetExists($name)) {
            $value = $this->values[$name];

            if (is_object($value)) {
                $this->shared->detach($value);
                $this->protected->detach($value);
            }

            unset($this->keys[$name], $this->values[$name], $this->frozen[$name], $this->raw[$name]);
        }
    }

    /**
     * Mark a callable fact as shared (singleton pattern).
     *
     * Shared facts are lazily evaluated once on first access, then the result
     * is frozen and cached for subsequent accesses within this Context instance.
     *
     * @param callable $callable A fact callable to share
     *
     * @throws InvalidArgumentException If the value is not a Closure or invokable object
     *
     * @return callable The passed callable for chaining
     *
     * @phpstan-return object
     */
    public function share(mixed $callable): mixed
    {
        throw_unless(self::isCallable($callable), InvalidArgumentException::class, 'Value is not a Closure or invokable object.');

        /** @var callable&object $callable */
        $this->shared->attach($callable);

        return $callable;
    }

    /**
     * Protect a callable from being interpreted as a lazy fact definition.
     *
     * This is useful when you want to store a callable as the literal value of
     * a fact rather than having it automatically evaluated when accessed.
     *
     * @param callable $callable A callable to protect from being evaluated
     *
     * @throws InvalidArgumentException If the value is not a Closure or invokable object
     *
     * @return callable The passed callable for chaining
     *
     * @phpstan-return object
     */
    public function protect(mixed $callable): mixed
    {
        throw_unless(self::isCallable($callable), InvalidArgumentException::class, 'Callable is not a Closure or invokable object.');

        /** @var callable&object $callable */
        $this->protected->attach($callable);

        return $callable;
    }

    /**
     * Get the raw value of a fact without evaluation.
     *
     * For frozen shared facts, returns the original callable definition.
     * For other facts, returns the current stored value without triggering
     * lazy evaluation.
     *
     * @param string $name The unique name for the fact
     *
     * @throws InvalidArgumentException If the fact name is not defined
     *
     * @return mixed The raw value or original callable definition
     */
    public function raw(string $name)
    {
        throw_unless($this->offsetExists($name), InvalidArgumentException::class, sprintf('Fact "%s" is not defined.', $name));

        if (array_key_exists($name, $this->frozen)) {
            return $this->raw[$name];
        }

        return $this->values[$name];
    }

    /**
     * Get all defined fact names.
     *
     * @return array<int, string> Array of all fact names currently defined in the context
     */
    public function keys(): array
    {
        return array_keys($this->keys);
    }

    /**
     * Check whether a value is a Closure or invokable object.
     *
     * @param  mixed $callable The value to check for callable nature
     * @return bool  True if the value is an object and callable, false otherwise
     */
    private static function isCallable($callable): bool
    {
        return is_object($callable) && is_callable($callable);
    }
}
