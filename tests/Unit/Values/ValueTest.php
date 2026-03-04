<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Values\Value;
use Illuminate\Support\Facades\Date;

describe('Value', function (): void {
    describe('Happy Paths', function (): void {
        test('constructor', function (): void {
            $valueString = 'technologic';
            $value = new Value($valueString);
            expect($value->getValue())->toEqual($valueString);
        });

        test('greater than equal to and less than', function ($a, $b, $gt, $eq, $lt): void {
            $valA = new Value($a);
            $valB = new Value($b);

            expect($valA->greaterThan($valB))->toEqual($gt);
            expect($valA->lessThan($valB))->toEqual($lt);
            expect($valA->equalTo($valB))->toEqual($eq);
        })->with('getRelativeValues');

        test('converts object to string using object hash', function (): void {
            $object = new stdClass();
            $value = new Value($object);
            $expectedHash = spl_object_hash($object);

            expect((string) $value)->toBe($expectedHash);
        });

        test('converts Carbon object to string using object hash', function (): void {
            $carbonObject = Date::now();
            $value = new Value($carbonObject);
            $expectedHash = spl_object_hash($carbonObject);

            expect((string) $value)->toBe($expectedHash);
        });

        test('converts non-object to string using serialization', function (): void {
            $primitiveValue = 'test string';
            $value = new Value($primitiveValue);
            $expectedSerialized = serialize($primitiveValue);

            expect((string) $value)->toBe($expectedSerialized);
        });
    });
});

dataset('getRelativeValues', fn (): array => [
    [1, 2,     false, false, true],
    [2, 1,     true, false, false],
    [1, 1,     false, true, false],
    ['a', 'b', false, false, true],
    [
        Date::now()->subDays(5),
        Date::now()->addDays(5),
        false, false, true,
    ],
]);
