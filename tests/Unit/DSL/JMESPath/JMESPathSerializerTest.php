<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\DSL\JMESPath\JMESPathParser;
use Cline\Ruler\DSL\JMESPath\JMESPathSerializer;

describe('JMESPathSerializer', function (): void {
    describe('Happy Paths', function (): void {
        test('serialize simple comparison', function (): void {
            $parser = new JMESPathParser();
            $serializer = new JMESPathSerializer();

            $rule = $parser->parse('age > `18`');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('age > `18`');
        });

        test('serialize equality comparison', function (): void {
            $parser = new JMESPathParser();
            $serializer = new JMESPathSerializer();

            $rule = $parser->parse('status == `"active"`');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('status == `"active"`');
        });

        test('serialize logical and', function (): void {
            $parser = new JMESPathParser();
            $serializer = new JMESPathSerializer();

            $rule = $parser->parse('age >= `18` && country == `"US"`');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('age >= `18` && country == `"US"`');
        });

        test('serialize logical or', function (): void {
            $parser = new JMESPathParser();
            $serializer = new JMESPathSerializer();

            $rule = $parser->parse('age >= `21` || country == `"US"`');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('age >= `21` || country == `"US"`');
        });

        test('serialize nested property access', function (): void {
            $parser = new JMESPathParser();
            $serializer = new JMESPathSerializer();

            $rule = $parser->parse('user.age >= `18`');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('user.age >= `18`');
        });

        test('serialize not equal operator', function (): void {
            $parser = new JMESPathParser();
            $serializer = new JMESPathSerializer();

            $rule = $parser->parse('status != `"inactive"`');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('status != `"inactive"`');
        });

        test('serialize less than operator', function (): void {
            $parser = new JMESPathParser();
            $serializer = new JMESPathSerializer();

            $rule = $parser->parse('age < `18`');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('age < `18`');
        });

        test('serialize less than or equal operator', function (): void {
            $parser = new JMESPathParser();
            $serializer = new JMESPathSerializer();

            $rule = $parser->parse('age <= `18`');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('age <= `18`');
        });

        test('serialize greater than or equal operator', function (): void {
            $parser = new JMESPathParser();
            $serializer = new JMESPathSerializer();

            $rule = $parser->parse('age >= `18`');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('age >= `18`');
        });

        test('serialize with contains function', function (): void {
            $parser = new JMESPathParser();
            $serializer = new JMESPathSerializer();

            $rule = $parser->parse('contains(tags, `"php"`)');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('contains(tags, `"php"`)');
        });

        test('serialize with starts_with function', function (): void {
            $parser = new JMESPathParser();
            $serializer = new JMESPathSerializer();

            $rule = $parser->parse('starts_with(email, `"admin"`)');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('starts_with(email, `"admin"`)');
        });

        test('serialize complex nested expression', function (): void {
            $parser = new JMESPathParser();
            $serializer = new JMESPathSerializer();

            $rule = $parser->parse('(age >= `18` && country == `"US"`) || age >= `21`');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('(age >= `18` && country == `"US"`) || age >= `21`');
        });

        test('serialize with array filter', function (): void {
            $parser = new JMESPathParser();
            $serializer = new JMESPathSerializer();

            $rule = $parser->parse('users[?age > `18`]');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('users[?age > `18`]');
        });

        test('serialize with projection', function (): void {
            $parser = new JMESPathParser();
            $serializer = new JMESPathSerializer();

            $rule = $parser->parse('users[*].age');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('users[*].age');
        });

        test('serialize with pipe expression', function (): void {
            $parser = new JMESPathParser();
            $serializer = new JMESPathSerializer();

            $rule = $parser->parse('users | length(@) > `5`');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('users | length(@) > `5`');
        });
    });

    describe('Round-Trip Tests', function (): void {
        test('round trip simple comparison', function (): void {
            $parser = new JMESPathParser();
            $serializer = new JMESPathSerializer();

            $original = 'age > `18`';
            $rule = $parser->parse($original);
            $serialized = $serializer->serialize($rule);
            $reparsed = $parser->parse($serialized);

            expect($serialized)->toBe($original);
        });

        test('round trip complex expression', function (): void {
            $parser = new JMESPathParser();
            $serializer = new JMESPathSerializer();

            $original = 'age >= `18` && country == `"US"`';
            $rule = $parser->parse($original);
            $serialized = $serializer->serialize($rule);

            expect($serialized)->toBe($original);
        });

        test('round trip nested property', function (): void {
            $parser = new JMESPathParser();
            $serializer = new JMESPathSerializer();

            $original = 'user.profile.age >= `18`';
            $rule = $parser->parse($original);
            $serialized = $serializer->serialize($rule);

            expect($serialized)->toBe($original);
        });

        test('round trip with function', function (): void {
            $parser = new JMESPathParser();
            $serializer = new JMESPathSerializer();

            $original = 'contains(tags, `"php"`)';
            $rule = $parser->parse($original);
            $serialized = $serializer->serialize($rule);

            expect($serialized)->toBe($original);
        });
    });
});
