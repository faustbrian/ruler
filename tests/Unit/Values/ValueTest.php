<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Values\Value;

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
    });
});

dataset('getRelativeValues', function () {
    return [
        [1, 2,     false, false, true],
        [2, 1,     true, false, false],
        [1, 1,     false, true, false],
        ['a', 'b', false, false, true],
        [
            new DateTime('-5 days'),
            new DateTime('+5 days'),
            false, false, true,
        ],
    ];
});
