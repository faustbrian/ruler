<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Tests\Fixtures\Fact;
use Tests\Fixtures\Invokable;

describe('Context', function (): void {
    describe('Happy Paths', function (): void {
        test('constructs with facts array containing closures', function (): void {
            $facts = [
                'name' => 'Mint Chip',
                'type' => 'Ice Cream',
                'delicious' => fn (): true => true,
            ];

            $context = new Context($facts);

            expect($context->offsetExists('name'))->toBeTrue();
            expect($context['name'])->toEqual('Mint Chip');

            expect($context->offsetExists('type'))->toBeTrue();
            expect($context['type'])->toEqual('Ice Cream');

            expect($context->offsetExists('delicious'))->toBeTrue();
            expect($context['delicious'])->toBeTrue();
        });

        test('stores and retrieves string values', function (): void {
            $context = new Context();
            $context['param'] = 'value';

            expect($context['param'])->toEqual('value');
        });

        test('creates new instance from closure factory', function (): void {
            $context = new Context();
            $context['fact'] = fn (): Fact => new Fact();

            expect($context['fact'])->toBeInstanceOf(Fact::class);
        });

        test('creates different instances on each access', function (): void {
            $context = new Context();
            $context['fact'] = fn (): Fact => new Fact();

            $factOne = $context['fact'];
            expect($factOne)->toBeInstanceOf(Fact::class);

            $factTwo = $context['fact'];
            expect($factTwo)->toBeInstanceOf(Fact::class);

            $this->assertNotSame($factOne, $factTwo);
        });

        test('passes context as parameter to factory closure', function (): void {
            $context = new Context();
            $context['fact'] = fn (): Fact => new Fact();
            $context['context'] = fn ($context) => $context;

            $this->assertNotSame($context, $context['fact']);
            expect($context['context'])->toBe($context);
        });

        test('checks if keys are set with isset', function (): void {
            $context = new Context();
            $context['param'] = 'value';
            $context['fact'] = fn (): Fact => new Fact();

            $context['null'] = null;

            expect($context->offsetExists('param'))->toBeTrue();
            expect($context->offsetExists('fact'))->toBeTrue();
            expect($context->offsetExists('null'))->toBeTrue();
            expect($context->offsetExists('non_existent'))->toBeFalse();
        });

        test('injects parameters via constructor', function (): void {
            $params = ['param' => 'value'];
            $context = new Context($params);

            expect($context['param'])->toBe($params['param']);
        });

        test('retrieves null values correctly', function (): void {
            $context = new Context();
            $context['foo'] = null;
            expect($context['foo'])->toBeNull();
        });

        test('unsets keys correctly', function (): void {
            $context = new Context();
            $context['param'] = 'value';
            $context['fact'] = fn (): Fact => new Fact();

            unset($context['param'], $context['fact']);
            expect($context->offsetExists('param'))->toBeFalse();
            expect($context->offsetExists('fact'))->toBeFalse();
        });

        test('shares factory to return same instance', function ($definition): void {
            $context = new Context();
            $fact = resolve_fact_definition($definition);

            $context['shared_fact'] = $context->share($fact);

            $factOne = $context['shared_fact'];
            expect($factOne)->toBeInstanceOf(Fact::class);

            $factTwo = $context['shared_fact'];
            expect($factTwo)->toBeInstanceOf(Fact::class);

            expect($factTwo)->toBe($factOne);
        })->with('factDefinitionProvider');

        test('protects closure from being invoked', function ($definition): void {
            $context = new Context();
            $fact = resolve_fact_definition($definition);

            $context['protected'] = $context->protect($fact);

            expect($context['protected'])->toBe($fact);
        })->with('factDefinitionProvider');

        test('stores global function name as string value', function (): void {
            $context = new Context();
            $context['global_function'] = 'strlen';
            expect($context['global_function'])->toBe('strlen');
        });

        test('returns raw closure definition', function (): void {
            $context = new Context();
            $context['fact'] = $definition = fn (): string => 'foo';
            expect($context->raw('fact'))->toBe($definition);
        });

        test('returns null from raw when value is null', function (): void {
            $context = new Context();
            $context['foo'] = null;
            expect($context->raw('foo'))->toBeNull();
        });

        test('returns original callable from raw for frozen shared fact', function (): void {
            // Arrange
            $context = new Context();
            $callable = fn (Context $c): Fact => new Fact($c);
            $context['shared_fact'] = $context->share($callable);

            // Act - Access the shared fact to trigger freezing
            $result = $context['shared_fact'];

            // Assert - raw() should return the original callable for frozen facts
            expect($result)->toBeInstanceOf(Fact::class);
            expect($context->raw('shared_fact'))->toBe($callable);
        });

        test('retrieves all keys', function (): void {
            $context = new Context();
            $context['foo'] = 123;
            $context['bar'] = 123;

            expect($context->keys())->toEqual(['foo', 'bar']);
        });

        test('treats invokable object as factory', function (): void {
            $context = new Context();
            $context['invokable'] = new Invokable();

            expect($context['invokable'])->toBeInstanceOf(Fact::class);
        });

        test('treats non invokable object as parameter', function (): void {
            $context = new Context();
            $context['non_invokable'] = new Fact();

            expect($context['non_invokable'])->toBeInstanceOf(Fact::class);
        });
    });

    describe('Sad Paths', function (): void {
        test('throws exception when accessing undefined key', function (): void {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Fact "foo" is not defined.');
            $context = new Context();
            echo $context['foo'];
        });

        test('throws exception when raw accesses undefined key', function (): void {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Fact "foo" is not defined.');
            $context = new Context();
            $context->raw('foo');
        });

        test('throws exception when share receives invalid fact definition', function ($fact): void {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Value is not a Closure or invokable object.');
            $context = new Context();
            $context->share($fact);
        })->with('badFactDefinitionProvider');

        test('throws exception when protect receives invalid fact definition', function ($fact): void {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Callable is not a Closure or invokable object.');
            $context = new Context();
            $context->protect($fact);
        })->with('badFactDefinitionProvider');

        test('throws exception when attempting to override frozen fact', function (): void {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Cannot override frozen fact "shared_fact".');

            $context = new Context();
            $context['shared_fact'] = $context->share(fn (): Fact => new Fact());

            $context['shared_fact'];

            $context['shared_fact'] = 'new value';
        });

        test('unsets shared object correctly', function (): void {
            $context = new Context();
            $callable = fn (): Fact => new Fact();
            $context['shared'] = $context->share($callable);

            unset($context['shared']);

            expect($context->offsetExists('shared'))->toBeFalse();
        });

        test('unsets protected object correctly', function (): void {
            $context = new Context();
            $callable = fn (): Fact => new Fact();
            $context['protected'] = $context->protect($callable);

            unset($context['protected']);

            expect($context->offsetExists('protected'))->toBeFalse();
        });
    });
});

/**
 * Provider for invalid fact definitions.
 */
dataset('badFactDefinitionProvider', fn (): array => [
    [123],
    [new Fact()],
]);

/**
 * Provider for fact definitions.
 */
dataset('factDefinitionProvider', fn (): array => [
    ['closure'],
    ['invokable'],
]);

function resolve_fact_definition(string $definition): callable
{
    return match ($definition) {
        'closure' => static fn (Context $context): Fact => new Fact($context),
        'invokable' => new Invokable(),
        default => throw new InvalidArgumentException(sprintf('Unknown fact definition "%s".', $definition)),
    };
}
