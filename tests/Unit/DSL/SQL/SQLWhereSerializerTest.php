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
use Cline\Ruler\Operators\Comparison\EqualTo;
use Cline\Ruler\Operators\Comparison\In;
use Cline\Ruler\Operators\Logical\LogicalNot;
use Cline\Ruler\Operators\Set\SetContains;
use Cline\Ruler\Operators\String\Matches;
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

    describe('Edge Cases', function (): void {
        test('serialize LIKE pattern with escaped percent', function (): void {
            $parser = new SQLWhereParser();
            $serializer = new SQLWhereSerializer();

            $rule = $parser->parse("name LIKE 'test\\%value'");
            $result = $serializer->serialize($rule);

            // The serializer converts escaped patterns back - line 142 coverage
            expect($result)->toBe("name LIKE 'test%value'");
        });

        test('serialize LIKE pattern with escaped underscore', function (): void {
            $parser = new SQLWhereParser();
            $serializer = new SQLWhereSerializer();

            $rule = $parser->parse("code LIKE 'A\\_C'");
            $result = $serializer->serialize($rule);

            // The serializer converts escaped patterns back - line 142 coverage
            expect($result)->toBe("code LIKE 'A_C'");
        });

        test('serialize unsupported operator throws exception', function (): void {
            $builder = new RuleBuilder();
            $serializer = new SQLWhereSerializer();

            // Create a custom unsupported operator using an existing class that's not handled
            $variable = new Variable('test');
            $value = new Variable(null, [1, 2, 3]);

            // Use a Set operator which is not supported by SQL serializer
            $operator = new SetContains($variable, $value);
            $rule = $builder->create($operator);

            expect(fn (): string => $serializer->serialize($rule))
                ->toThrow(LogicException::class, 'Unsupported operator');
        });

        test('serialize binary operator with wrong operand count throws exception', function (): void {
            $builder = new RuleBuilder();
            $serializer = new SQLWhereSerializer();

            // Create a malformed EqualTo with only one operand using reflection
            $variable = new Variable('test');
            $equalTo = new EqualTo($variable, new Variable(null, 1));

            // Manually break it to have wrong operand count
            $reflection = new ReflectionClass($equalTo);
            $operandsProperty = $reflection->getProperty('operands');
            $operandsProperty->setValue($equalTo, [$variable]); // Only 1 operand instead of 2

            $rule = $builder->create($equalTo);

            expect(fn (): string => $serializer->serialize($rule))
                ->toThrow(LogicException::class, 'Binary operator = requires exactly 2 operands');
        });

        test('serialize NOT operator with wrong operand count throws exception', function (): void {
            $builder = new RuleBuilder();
            $serializer = new SQLWhereSerializer();

            // Create a malformed LogicalNot with no operands
            $variable = new Variable('test');
            $comparison = new EqualTo($variable, new Variable(null, 1));
            $not = new LogicalNot([$comparison]);

            $reflection = new ReflectionClass($not);
            $operandsProperty = $reflection->getProperty('operands');
            $operandsProperty->setValue($not, []); // No operands

            $rule = $builder->create($not);

            expect(fn (): string => $serializer->serialize($rule))
                ->toThrow(LogicException::class, 'NOT operator requires exactly 1 operand');
        });

        test('serialize IN operator with wrong operand count throws exception', function (): void {
            $builder = new RuleBuilder();
            $serializer = new SQLWhereSerializer();

            $variable = new Variable('test');
            $values = new Variable(null, [1, 2, 3]);
            $in = new In($variable, $values);

            $reflection = new ReflectionClass($in);
            $operandsProperty = $reflection->getProperty('operands');
            $operandsProperty->setValue($in, [$variable]); // Only 1 operand

            $rule = $builder->create($in);

            expect(fn (): string => $serializer->serialize($rule))
                ->toThrow(LogicException::class, 'IN operator requires exactly 2 operands');
        });

        test('serialize IN operator with non-array value throws exception', function (): void {
            $builder = new RuleBuilder();
            $serializer = new SQLWhereSerializer();

            $variable = new Variable('test');
            $notArray = new Variable(null, 'not-an-array');
            $in = new In($variable, $notArray);

            $rule = $builder->create($in);

            expect(fn (): string => $serializer->serialize($rule))
                ->toThrow(LogicException::class, 'IN operator requires array of values');
        });

        test('serialize BETWEEN operator with wrong operand count throws exception', function (): void {
            $builder = new RuleBuilder();
            $serializer = new SQLWhereSerializer();

            $variable = new Variable('test');
            $min = new Variable(null, 1);
            $max = new Variable(null, 10);
            $between = new Between($variable, $min, $max);

            $reflection = new ReflectionClass($between);
            $operandsProperty = $reflection->getProperty('operands');
            $operandsProperty->setValue($between, [$variable, $min]); // Only 2 operands instead of 3

            $rule = $builder->create($between);

            expect(fn (): string => $serializer->serialize($rule))
                ->toThrow(LogicException::class, 'BETWEEN operator requires exactly 3 operands');
        });

        test('serialize LIKE operator with wrong operand count throws exception', function (): void {
            $builder = new RuleBuilder();
            $serializer = new SQLWhereSerializer();

            $variable = new Variable('test');
            $pattern = new Variable(null, '/^.*@example\\.com$/');
            $matches = new Matches($variable, $pattern);

            $reflection = new ReflectionClass($matches);
            $operandsProperty = $reflection->getProperty('operands');
            $operandsProperty->setValue($matches, [$variable]); // Only 1 operand

            $rule = $builder->create($matches);

            expect(fn (): string => $serializer->serialize($rule))
                ->toThrow(LogicException::class, 'LIKE operator requires exactly 2 operands');
        });

        test('serialize LIKE operator with non-string pattern throws exception', function (): void {
            $builder = new RuleBuilder();
            $serializer = new SQLWhereSerializer();

            $variable = new Variable('test');
            $notString = new Variable(null, 123); // Not a string
            $matches = new Matches($variable, $notString);

            $rule = $builder->create($matches);

            expect(fn (): string => $serializer->serialize($rule))
                ->toThrow(LogicException::class, 'LIKE pattern must be a string');
        });

        test('serialize value with unsupported type throws exception', function (): void {
            $builder = new RuleBuilder();
            $serializer = new SQLWhereSerializer();

            // Create a comparison with an object value (unsupported type)
            $variable = new Variable('test');
            $objectValue = new Variable(null, new stdClass());
            $equalTo = new EqualTo($variable, $objectValue);

            $rule = $builder->create($equalTo);

            expect(fn (): string => $serializer->serialize($rule))
                ->toThrow(LogicException::class, 'Cannot cast value to string: object');
        });

        test('serialize regexToLikePattern handles invalid regex format', function (): void {
            $parser = new SQLWhereParser();
            $serializer = new SQLWhereSerializer();

            // Create a LIKE expression then modify it to have an invalid regex pattern
            $variable = new Variable('email');
            $invalidRegex = new Variable(null, 'not-a-regex-pattern'); // Missing /^...$/
            $matches = new Matches($variable, $invalidRegex);

            $builder = new RuleBuilder();
            $rule = $builder->create($matches);

            $result = $serializer->serialize($rule);

            // Should return the original pattern since it doesn't match regex format
            expect($result)->toBe("email LIKE 'not-a-regex-pattern'");
        });

        test('serialize IN operator with numeric array values', function (): void {
            $parser = new SQLWhereParser();
            $serializer = new SQLWhereSerializer();

            $rule = $parser->parse('age IN (18, 21, 25)');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('age IN (18, 21, 25)');
        });

        test('serialize IN operator with mixed type array values', function (): void {
            $parser = new SQLWhereParser();
            $serializer = new SQLWhereSerializer();

            $rule = $parser->parse("value IN (1, 'two', TRUE, NULL)");
            $result = $serializer->serialize($rule);

            expect($result)->toBe("value IN (1, 'two', TRUE, NULL)");
        });

        test('serialize variable without name returns its value', function (): void {
            $builder = new RuleBuilder();
            $serializer = new SQLWhereSerializer();

            // Create a comparison where right operand is a Variable with no name (just a value)
            $variable = new Variable('field');
            $valueVar = new Variable(null, 42); // No name, just value
            $equalTo = new EqualTo($variable, $valueVar);

            $rule = $builder->create($equalTo);
            $result = $serializer->serialize($rule);

            expect($result)->toBe('field = 42');
        });

        test('serialize negative number value', function (): void {
            $parser = new SQLWhereParser();
            $serializer = new SQLWhereSerializer();

            $rule = $parser->parse('temperature < -10');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('temperature < -10');
        });

        test('serialize decimal number value', function (): void {
            $parser = new SQLWhereParser();
            $serializer = new SQLWhereSerializer();

            $rule = $parser->parse('price = 99.99');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('price = 99.99');
        });

        test('serialize zero value', function (): void {
            $parser = new SQLWhereParser();
            $serializer = new SQLWhereSerializer();

            $rule = $parser->parse('count = 0');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('count = 0');
        });

        test('serialize operand that is a raw value not Variable', function (): void {
            $builder = new RuleBuilder();
            $serializer = new SQLWhereSerializer();

            // Create an EqualTo with a raw integer (not wrapped in Variable)
            $variable = new Variable('test');
            $equalTo = new EqualTo($variable, new Variable(null, 42));

            // Replace second operand with raw integer using reflection
            $reflection = new ReflectionClass($equalTo);
            $operandsProperty = $reflection->getProperty('operands');
            $operands = $operandsProperty->getValue($equalTo);
            $operands[1] = 42; // Raw integer, not Variable
            $operandsProperty->setValue($equalTo, $operands);

            $rule = $builder->create($equalTo);
            $result = $serializer->serialize($rule);

            // Line 398: serializeOperand returns serializeValue for raw values
            expect($result)->toBe('test = 42');
        });

        test('serialize comparison with array value', function (): void {
            $builder = new RuleBuilder();
            $serializer = new SQLWhereSerializer();

            // Create comparison with array value directly (not via IN operator)
            $variable = new Variable('test');
            $arrayValue = [1, 2, 3];
            $equalTo = new EqualTo($variable, new Variable(null, $arrayValue));

            $rule = $builder->create($equalTo);
            $result = $serializer->serialize($rule);

            // Lines 444-449: serializeValue handles array
            expect($result)->toBe('test = (1, 2, 3)');
        });

        test('serialize operator with BuilderVariable', function (): void {
            $builder = new RuleBuilder();
            $serializer = new SQLWhereSerializer();

            // Create comparison with BuilderVariable instead of Variable
            // Use reflection to replace one operand with BuilderVariable
            $variable = new Variable('test');
            $value = new Variable(null, 42);
            $equalTo = new EqualTo($variable, $value);

            $reflection = new ReflectionClass($equalTo);
            $operandsProperty = $reflection->getProperty('operands');
            $operands = $operandsProperty->getValue($equalTo);
            // BuilderVariable requires a RuleBuilder in constructor
            $operands[0] = new Cline\Ruler\Builder\Variable($builder, 'field');
            $operandsProperty->setValue($equalTo, $operands);

            $rule = $builder->create($equalTo);
            $result = $serializer->serialize($rule);

            expect($result)->toBe('field = 42');
        });

        test('serialize LIKE with backslash followed by regular character', function (): void {
            $builder = new RuleBuilder();
            $serializer = new SQLWhereSerializer();

            // Create LIKE with pattern that has backslash followed by regular character (not % or _)
            $variable = new Variable('text');
            // This regex has \t (backslash-t) which should convert to just 't' in SQL
            $pattern = new Variable(null, '/^hello\\tworld$/');
            $matches = new Matches($variable, $pattern);

            $rule = $builder->create($matches);
            $result = $serializer->serialize($rule);

            // Line 144: Handle escaped characters that aren't % or _
            expect($result)->toBe("text LIKE 'hellotworld'");
        });

        test('serialize nested array values', function (): void {
            $builder = new RuleBuilder();
            $serializer = new SQLWhereSerializer();

            // Create comparison with nested array
            $variable = new Variable('test');
            $nestedArray = [1, [2, 3], 4];
            $equalTo = new EqualTo($variable, new Variable(null, $nestedArray));

            $rule = $builder->create($equalTo);
            $result = $serializer->serialize($rule);

            // Lines 444-449: serializeValue recursively handles arrays
            expect($result)->toBe('test = (1, (2, 3), 4)');
        });

        test('serialize empty array value', function (): void {
            $builder = new RuleBuilder();
            $serializer = new SQLWhereSerializer();

            // Create comparison with empty array
            $variable = new Variable('test');
            $emptyArray = [];
            $equalTo = new EqualTo($variable, new Variable(null, $emptyArray));

            $rule = $builder->create($equalTo);
            $result = $serializer->serialize($rule);

            // Lines 444-449: serializeValue handles empty array
            expect($result)->toBe('test = ()');
        });
    });
});
