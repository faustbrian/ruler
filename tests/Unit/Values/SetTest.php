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
    });
});
