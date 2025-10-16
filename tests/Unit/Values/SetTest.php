<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Values\Set;
use Cline\Ruler\Values\Value;
use Tests\Fixtures\toStringable;

describe('Set', function (): void {
    describe('Happy Paths', function (): void {
        test('non stringable object', function (): void {
            $setExpected = [
                new stdClass(),
                new stdClass(),
            ];
            $set = new Set($setExpected);
            expect(count($set))->toEqual(2);
        });

        test('object uniqueness', function (): void {
            $objectA = new stdClass();
            $objectA->something = 'else';

            $objectB = new stdClass();
            $objectB->foo = 'bar';

            $set = new Set([
                $objectA,
                $objectB,
            ]);

            expect(count($set))->toEqual(2);
            expect($set->setContains(
                new Value($objectA),
            ))->toBeTrue();
            expect($set->setContains(
                new Value($objectB),
            ))->toBeTrue();
            expect($set->setContains(
                new Value(false),
            ))->toBeFalse();
        });

        test('stringable', function (): void {
            $set = new Set([
                $one = new toStringable(1),
                $two = new toStringable(2),
                $too = new toStringable(2),
            ]);

            expect(count($set))->toEqual(2);
            expect($set->setContains(
                new Value($one),
            ))->toBeTrue();
            expect($set->setContains(
                new Value($two),
            ))->toBeTrue();
            expect($set->setContains(
                new Value($too),
            ))->toBeFalse();
            expect($set->setContains(
                new Value(2),
            ))->toBeFalse();
        });

        test('min returns minimum numeric value', function (): void {
            $set = new Set([5, 2, 8, 1, 9]);
            expect($set->min())->toBe(1);
        });

        test('min returns null for empty set', function (): void {
            $set = new Set([]);
            expect($set->min())->toBeNull();
        });

        test('max returns maximum numeric value', function (): void {
            $set = new Set([5, 2, 8, 1, 9]);
            expect($set->max())->toBe(9);
        });

        test('max returns null for empty set', function (): void {
            $set = new Set([]);
            expect($set->max())->toBeNull();
        });

        test('union combines sets', function (): void {
            $set1 = new Set([1, 2, 3]);
            $set2 = new Value([3, 4, 5]);
            $result = $set1->union($set2);
            expect(count($result))->toBe(5);
        });

        test('intersect finds common elements', function (): void {
            $set1 = new Set([1, 2, 3, 4]);
            $set2 = new Value([3, 4, 5, 6]);
            $result = $set1->intersect($set2);
            expect(count($result))->toBe(2);
        });

        test('complement finds difference', function (): void {
            $set1 = new Set([1, 2, 3, 4]);
            $set2 = new Value([3, 4]);
            $result = $set1->complement($set2);
            expect(count($result))->toBe(2);
        });

        test('symmetricDifference finds exclusive elements', function (): void {
            $set1 = new Set([1, 2, 3]);
            $set2 = new Value([2, 3, 4]);
            $result = $set1->symmetricDifference($set2);
            expect(count($result))->toBe(2);
        });

        test('containsSubset returns true for valid subset', function (): void {
            $set1 = new Set([1, 2, 3, 4, 5]);
            $set2 = new Set([2, 3]);
            expect($set1->containsSubset($set2))->toBeTrue();
        });

        test('containsSubset returns false when not a subset', function (): void {
            $set1 = new Set([1, 2, 3]);
            $set2 = new Set([3, 4, 5]);
            expect($set1->containsSubset($set2))->toBeFalse();
        });

        test('containsSubset returns false when candidate is larger', function (): void {
            $set1 = new Set([1, 2]);
            $set2 = new Set([1, 2, 3, 4]);
            expect($set1->containsSubset($set2))->toBeFalse();
        });

        test('setContains works with nested arrays', function (): void {
            $set = new Set([[1, 2], [3, 4]]);
            expect($set->setContains(
                new Value([1, 2])
            ))->toBeTrue();
            expect($set->setContains(
                new Value([5, 6])
            ))->toBeFalse();
        });

        test('constructs from null creates empty set', function (): void {
            $set = new Set(null);
            expect(count($set))->toBe(0);
        });

        test('constructs from scalar wraps in array', function (): void {
            $set = new Set(42);
            expect(count($set))->toBe(1);
        });

        test('toString concatenates elements', function (): void {
            $set = new Set([1, 2, 3]);
            expect((string) $set)->toBe('123');
        });
    });

    describe('Sad Paths', function (): void {
        test('min throws exception for non-numeric values', function (): void {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('min: all values must be numeric');

            $set = new Set([1, 'string', 3]);
            $set->min();
        });

        test('max throws exception for non-numeric values', function (): void {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('max: all values must be numeric');

            $set = new Set([1, 'string', 3]);
            $set->max();
        });
    });
});
