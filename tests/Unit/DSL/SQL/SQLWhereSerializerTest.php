<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Builder\RuleBuilder;
use Cline\Ruler\DSL\SQL\SQLWhereParser;
use Cline\Ruler\DSL\SQL\SQLWhereSerializer;
use Cline\Ruler\Operators\Comparison\Between;
use Cline\Ruler\Variables\Variable;

describe('SQLWhereSerializer', function (): void {
    describe('Happy Paths', function (): void {
        test('serialize simple comparison expression', function (): void {
            $parser = new SQLWhereParser();
            $serializer = new SQLWhereSerializer();

            $rule = $parser->parse('age > 18');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('age > 18');
        });

        test('serialize equality comparison', function (): void {
            $parser = new SQLWhereParser();
            $serializer = new SQLWhereSerializer();

            $rule = $parser->parse("status = 'active'");
            $result = $serializer->serialize($rule);

            expect($result)->toBe("status = 'active'");
        });

        test('serialize inequality comparison', function (): void {
            $parser = new SQLWhereParser();
            $serializer = new SQLWhereSerializer();

            $rule = $parser->parse("status != 'inactive'");
            $result = $serializer->serialize($rule);

            expect($result)->toBe("status != 'inactive'");
        });

        test('serialize AND expression', function (): void {
            $parser = new SQLWhereParser();
            $serializer = new SQLWhereSerializer();

            $rule = $parser->parse("age >= 18 AND country = 'US'");
            $result = $serializer->serialize($rule);

            expect($result)->toBe("age >= 18 AND country = 'US'");
        });

        test('serialize OR expression', function (): void {
            $parser = new SQLWhereParser();
            $serializer = new SQLWhereSerializer();

            $rule = $parser->parse("age >= 21 OR country = 'CA'");
            $result = $serializer->serialize($rule);

            expect($result)->toBe("age >= 21 OR country = 'CA'");
        });

        test('serialize NOT expression', function (): void {
            $parser = new SQLWhereParser();
            $serializer = new SQLWhereSerializer();

            $rule = $parser->parse('NOT (age < 18)');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('NOT (age < 18)');
        });

        test('serialize IN operator', function (): void {
            $parser = new SQLWhereParser();
            $serializer = new SQLWhereSerializer();

            $rule = $parser->parse("country IN ('US', 'CA', 'UK')");
            $result = $serializer->serialize($rule);

            expect($result)->toBe("country IN ('US', 'CA', 'UK')");
        });

        test('serialize NOT IN operator', function (): void {
            $parser = new SQLWhereParser();
            $serializer = new SQLWhereSerializer();

            $rule = $parser->parse("country NOT IN ('FR', 'DE')");
            $result = $serializer->serialize($rule);

            expect($result)->toBe("country NOT IN ('FR', 'DE')");
        });

        test('serialize BETWEEN operator', function (): void {
            $parser = new SQLWhereParser();
            $serializer = new SQLWhereSerializer();

            $rule = $parser->parse('age BETWEEN 18 AND 65');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('age BETWEEN 18 AND 65');
        });

        test('serialize LIKE operator', function (): void {
            $parser = new SQLWhereParser();
            $serializer = new SQLWhereSerializer();

            $rule = $parser->parse("email LIKE '%@example.com'");
            $result = $serializer->serialize($rule);

            expect($result)->toBe("email LIKE '%@example.com'");
        });

        test('serialize NOT LIKE operator', function (): void {
            $parser = new SQLWhereParser();
            $serializer = new SQLWhereSerializer();

            $rule = $parser->parse("email NOT LIKE '%@spam.com'");
            $result = $serializer->serialize($rule);

            expect($result)->toBe("email NOT LIKE '%@spam.com'");
        });

        test('serialize IS NULL operator', function (): void {
            $parser = new SQLWhereParser();
            $serializer = new SQLWhereSerializer();

            $rule = $parser->parse('middle_name IS NULL');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('middle_name IS NULL');
        });

        test('serialize IS NOT NULL operator', function (): void {
            $parser = new SQLWhereParser();
            $serializer = new SQLWhereSerializer();

            $rule = $parser->parse('email IS NOT NULL');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('email IS NOT NULL');
        });

        test('serialize expression with parentheses', function (): void {
            $parser = new SQLWhereParser();
            $serializer = new SQLWhereSerializer();

            $rule = $parser->parse("(age >= 18 AND country = 'US') OR age >= 21");
            $result = $serializer->serialize($rule);

            expect($result)->toBe("(age >= 18 AND country = 'US') OR age >= 21");
        });

        test('serialize complex nested expression', function (): void {
            $parser = new SQLWhereParser();
            $serializer = new SQLWhereSerializer();

            $rule = $parser->parse(
                "(age >= 18 AND country = 'US') OR (age >= 21 AND country IN ('CA', 'UK'))",
            );
            $result = $serializer->serialize($rule);

            expect($result)->toBe(
                "(age >= 18 AND country = 'US') OR (age >= 21 AND country IN ('CA', 'UK'))",
            );
        });

        test('serialize less than or equal operator', function (): void {
            $parser = new SQLWhereParser();
            $serializer = new SQLWhereSerializer();

            $rule = $parser->parse('age <= 65');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('age <= 65');
        });

        test('serialize greater than or equal operator', function (): void {
            $parser = new SQLWhereParser();
            $serializer = new SQLWhereSerializer();

            $rule = $parser->parse('age >= 18');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('age >= 18');
        });

        test('serialize string with quotes', function (): void {
            $parser = new SQLWhereParser();
            $serializer = new SQLWhereSerializer();

            $rule = $parser->parse("name = 'O''Reilly'");
            $result = $serializer->serialize($rule);

            expect($result)->toBe("name = 'O''Reilly'");
        });

        test('serialize numeric values', function (): void {
            $parser = new SQLWhereParser();
            $serializer = new SQLWhereSerializer();

            $rule = $parser->parse('price = 99.99');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('price = 99.99');
        });

        test('serialize boolean TRUE', function (): void {
            $parser = new SQLWhereParser();
            $serializer = new SQLWhereSerializer();

            $rule = $parser->parse('active = TRUE');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('active = TRUE');
        });

        test('serialize boolean FALSE', function (): void {
            $parser = new SQLWhereParser();
            $serializer = new SQLWhereSerializer();

            $rule = $parser->parse('active = FALSE');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('active = FALSE');
        });

        test('serialize LIKE with underscore wildcard', function (): void {
            $parser = new SQLWhereParser();
            $serializer = new SQLWhereSerializer();

            $rule = $parser->parse("code LIKE 'A_B'");
            $result = $serializer->serialize($rule);

            expect($result)->toBe("code LIKE 'A_B'");
        });
    });

    describe('Round-Trip Tests', function (): void {
        test('round-trip simple comparison', function (): void {
            $parser = new SQLWhereParser();
            $serializer = new SQLWhereSerializer();

            $original = 'age > 18';
            $rule = $parser->parse($original);
            $serialized = $serializer->serialize($rule);

            expect($serialized)->toBe($original);
        });

        test('round-trip AND expression', function (): void {
            $parser = new SQLWhereParser();
            $serializer = new SQLWhereSerializer();

            $original = "age >= 18 AND country = 'US'";
            $rule = $parser->parse($original);
            $serialized = $serializer->serialize($rule);

            expect($serialized)->toBe($original);
        });

        test('round-trip OR expression', function (): void {
            $parser = new SQLWhereParser();
            $serializer = new SQLWhereSerializer();

            $original = "age >= 21 OR status = 'verified'";
            $rule = $parser->parse($original);
            $serialized = $serializer->serialize($rule);

            expect($serialized)->toBe($original);
        });

        test('round-trip IN operator', function (): void {
            $parser = new SQLWhereParser();
            $serializer = new SQLWhereSerializer();

            $original = "country IN ('US', 'CA', 'UK')";
            $rule = $parser->parse($original);
            $serialized = $serializer->serialize($rule);

            expect($serialized)->toBe($original);
        });

        test('round-trip BETWEEN operator', function (): void {
            $parser = new SQLWhereParser();
            $serializer = new SQLWhereSerializer();

            $original = 'age BETWEEN 18 AND 65';
            $rule = $parser->parse($original);
            $serialized = $serializer->serialize($rule);

            expect($serialized)->toBe($original);
        });

        test('round-trip LIKE operator', function (): void {
            $parser = new SQLWhereParser();
            $serializer = new SQLWhereSerializer();

            $original = "email LIKE '%@example.com'";
            $rule = $parser->parse($original);
            $serialized = $serializer->serialize($rule);

            expect($serialized)->toBe($original);
        });

        test('round-trip IS NULL', function (): void {
            $parser = new SQLWhereParser();
            $serializer = new SQLWhereSerializer();

            $original = 'middle_name IS NULL';
            $rule = $parser->parse($original);
            $serialized = $serializer->serialize($rule);

            expect($serialized)->toBe($original);
        });

        test('round-trip IS NOT NULL', function (): void {
            $parser = new SQLWhereParser();
            $serializer = new SQLWhereSerializer();

            $original = 'email IS NOT NULL';
            $rule = $parser->parse($original);
            $serialized = $serializer->serialize($rule);

            expect($serialized)->toBe($original);
        });

        test('round-trip complex nested expression', function (): void {
            $parser = new SQLWhereParser();
            $serializer = new SQLWhereSerializer();

            $original = "(age >= 18 AND country = 'US') OR age >= 21";
            $rule = $parser->parse($original);
            $serialized = $serializer->serialize($rule);

            expect($serialized)->toBe($original);
        });

        test('round-trip NOT expression', function (): void {
            $parser = new SQLWhereParser();
            $serializer = new SQLWhereSerializer();

            $original = 'NOT (age < 18)';
            $rule = $parser->parse($original);
            $serialized = $serializer->serialize($rule);

            expect($serialized)->toBe($original);
        });

        test('serialize nested proposition in operand', function (): void {
            $parser = new SQLWhereParser();
            $serializer = new SQLWhereSerializer();

            $rule = $parser->parse("(age >= 18 AND country = 'US') OR age >= 21");
            $result = $serializer->serialize($rule);

            expect($result)->toContain('(');
        });

        test('serialize array values', function (): void {
            $parser = new SQLWhereParser();
            $serializer = new SQLWhereSerializer();

            $rule = $parser->parse("country IN ('US', 'CA')");
            $result = $serializer->serialize($rule);

            expect($result)->toContain('IN');
        });

        test('serialize BETWEEN operator', function (): void {
            $builder = new RuleBuilder();
            $serializer = new SQLWhereSerializer();

            $variable = new Variable('age');
            $min = new Variable(null, 18);
            $max = new Variable(null, 65);
            $operator = new Between($variable, $min, $max);
            $rule = $builder->create($operator);

            $result = $serializer->serialize($rule);

            expect($result)->toContain('BETWEEN');
        });
    });
});
