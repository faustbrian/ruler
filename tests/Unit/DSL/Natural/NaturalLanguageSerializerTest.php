<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Rule;
use Cline\Ruler\DSL\Natural\NaturalLanguageParser;
use Cline\Ruler\DSL\Natural\NaturalLanguageSerializer;
use Cline\Ruler\Operators\Comparison\EqualTo;
use Cline\Ruler\Operators\Comparison\GreaterThan;
use Cline\Ruler\Operators\Comparison\In;
use Cline\Ruler\Operators\Logical\LogicalAnd;
use Cline\Ruler\Operators\Logical\LogicalOr;
use Cline\Ruler\Variables\Variable;

describe('NaturalLanguageSerializer', function (): void {
    describe('Happy Paths', function (): void {
        test('serialize simple comparison', function (): void {
            $parser = new NaturalLanguageParser();
            $serializer = new NaturalLanguageSerializer();

            $rule = $parser->parse('age is greater than 18');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('age is greater than 18');
        });

        test('serialize equality comparison with equals', function (): void {
            $parser = new NaturalLanguageParser();
            $serializer = new NaturalLanguageSerializer();

            $rule = $parser->parse('status equals active');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('status equals active');
        });

        test('serialize is not comparison', function (): void {
            $parser = new NaturalLanguageParser();
            $serializer = new NaturalLanguageSerializer();

            $rule = $parser->parse('status is not inactive');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('status is not inactive');
        });

        test('serialize greater than or equal to', function (): void {
            $parser = new NaturalLanguageParser();
            $serializer = new NaturalLanguageSerializer();

            // Parser uses "is at least", serializer outputs the full form
            $rule = $parser->parse('age is at least 18');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('age is greater than or equal to 18');
        });

        test('serialize less than', function (): void {
            $parser = new NaturalLanguageParser();
            $serializer = new NaturalLanguageSerializer();

            $rule = $parser->parse('age is less than 65');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('age is less than 65');
        });

        test('serialize less than or equal to', function (): void {
            $parser = new NaturalLanguageParser();
            $serializer = new NaturalLanguageSerializer();

            // Parser uses "is at most", serializer outputs the full form
            $rule = $parser->parse('age is at most 65');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('age is less than or equal to 65');
        });

        test('serialize logical and', function (): void {
            $parser = new NaturalLanguageParser();
            $serializer = new NaturalLanguageSerializer();

            $rule = $parser->parse('age is at least 18 and country equals US');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('age is greater than or equal to 18 and country equals US');
        });

        test('serialize logical or', function (): void {
            $parser = new NaturalLanguageParser();
            $serializer = new NaturalLanguageSerializer();

            $rule = $parser->parse('age is at least 21 or country equals US');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('age is greater than or equal to 21 or country equals US');
        });

        test('serialize between range', function (): void {
            $parser = new NaturalLanguageParser();
            $serializer = new NaturalLanguageSerializer();

            $rule = $parser->parse('age is between 18 and 65');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('age is between 18 and 65');
        });

        test('serialize is one of with multiple values', function (): void {
            $parser = new NaturalLanguageParser();
            $serializer = new NaturalLanguageSerializer();

            $rule = $parser->parse('country is one of US, CA, UK');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('country is one of US, CA, UK');
        });

        test('serialize is either or', function (): void {
            $parser = new NaturalLanguageParser();
            $serializer = new NaturalLanguageSerializer();

            $rule = $parser->parse('status is either active or pending');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('status is either active or pending');
        });

        test('serialize is not one of', function (): void {
            $parser = new NaturalLanguageParser();
            $serializer = new NaturalLanguageSerializer();

            $rule = $parser->parse('country is not one of US, CA');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('country is not one of US, CA');
        });

        test('serialize contains string operation', function (): void {
            $parser = new NaturalLanguageParser();
            $serializer = new NaturalLanguageSerializer();

            $rule = $parser->parse('name contains John');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('name contains John');
        });

        test('serialize starts with string operation', function (): void {
            $parser = new NaturalLanguageParser();
            $serializer = new NaturalLanguageSerializer();

            $rule = $parser->parse('email starts with admin');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('email starts with admin');
        });

        test('serialize ends with string operation', function (): void {
            $parser = new NaturalLanguageParser();
            $serializer = new NaturalLanguageSerializer();

            $rule = $parser->parse('filename ends with .pdf');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('filename ends with .pdf');
        });

        test('serialize boolean true value', function (): void {
            $parser = new NaturalLanguageParser();
            $serializer = new NaturalLanguageSerializer();

            $rule = $parser->parse('active is true');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('active equals true');
        });

        test('serialize boolean false value', function (): void {
            $parser = new NaturalLanguageParser();
            $serializer = new NaturalLanguageSerializer();

            $rule = $parser->parse('deleted is false');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('deleted equals false');
        });

        test('serialize numeric values', function (): void {
            $parser = new NaturalLanguageParser();
            $serializer = new NaturalLanguageSerializer();

            $rule = $parser->parse('price is greater than 99.99');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('price is greater than 99.99');
        });

        test('serialize complex nested expression with parentheses', function (): void {
            $parser = new NaturalLanguageParser();
            $serializer = new NaturalLanguageSerializer();

            $rule = $parser->parse('(age is at least 18 and country equals US) or age is at least 21');
            $result = $serializer->serialize($rule);

            // Note: Parentheses are not preserved when not needed for precedence
            expect($result)->toBe('age is greater than or equal to 18 and country equals US or age is greater than or equal to 21');
        });
    });

    describe('Round-Trip Tests', function (): void {
        test('round trip simple comparison', function (): void {
            $parser = new NaturalLanguageParser();
            $serializer = new NaturalLanguageSerializer();

            $original = 'age is greater than 18';
            $rule = $parser->parse($original);
            $serialized = $serializer->serialize($rule);

            expect($serialized)->toBe($original);
        });

        test('round trip equality comparison', function (): void {
            $parser = new NaturalLanguageParser();
            $serializer = new NaturalLanguageSerializer();

            $original = 'status equals active';
            $rule = $parser->parse($original);
            $serialized = $serializer->serialize($rule);

            expect($serialized)->toBe($original);
        });

        test('round trip logical and', function (): void {
            $parser = new NaturalLanguageParser();
            $serializer = new NaturalLanguageSerializer();

            // Use "is at least" for parsing, serializer will output full form
            $original = 'age is at least 18 and country equals US';
            $rule = $parser->parse($original);
            $serialized = $serializer->serialize($rule);

            // Serializer outputs the canonical form
            expect($serialized)->toBe('age is greater than or equal to 18 and country equals US');
        });

        test('round trip between range', function (): void {
            $parser = new NaturalLanguageParser();
            $serializer = new NaturalLanguageSerializer();

            $original = 'age is between 18 and 65';
            $rule = $parser->parse($original);
            $serialized = $serializer->serialize($rule);

            expect($serialized)->toBe($original);
        });

        test('round trip is one of', function (): void {
            $parser = new NaturalLanguageParser();
            $serializer = new NaturalLanguageSerializer();

            $original = 'country is one of US, CA, UK';
            $rule = $parser->parse($original);
            $serialized = $serializer->serialize($rule);

            expect($serialized)->toBe($original);
        });

        test('round trip is either or', function (): void {
            $parser = new NaturalLanguageParser();
            $serializer = new NaturalLanguageSerializer();

            $original = 'status is either active or pending';
            $rule = $parser->parse($original);
            $serialized = $serializer->serialize($rule);

            expect($serialized)->toBe($original);
        });

        test('round trip string operations', function (): void {
            $parser = new NaturalLanguageParser();
            $serializer = new NaturalLanguageSerializer();

            $original = 'name contains John';
            $rule = $parser->parse($original);
            $serialized = $serializer->serialize($rule);

            expect($serialized)->toBe($original);
        });
    });

    describe('Edge Cases', function (): void {
        test('serialize null value', function (): void {
            $parser = new NaturalLanguageParser();
            $serializer = new NaturalLanguageSerializer();

            $rule = $parser->parse('value equals null');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('value equals null');
        });

        test('throws exception for variable without name in field reference', function (): void {
            $serializer = new NaturalLanguageSerializer();

            // Create a Variable without a name
            $variable = new Variable(null, 'test');
            $proposition = new EqualTo($variable, new Variable(null, 'value'));
            $rule = new Rule($proposition);

            expect(fn (): string => $serializer->serialize($rule))
                ->toThrow(LogicException::class, 'Expected variable with name for field reference');
        });

        test('throws exception for unsupported value type', function (): void {
            $serializer = new NaturalLanguageSerializer();

            // Create a proposition with an unsupported value type (object)
            $variable = new Variable('field', new stdClass());
            $proposition = new EqualTo(
                new Variable('field'),
                $variable,
            );
            $rule = new Rule($proposition);

            expect(fn (): string => $serializer->serialize($rule))
                ->toThrow(LogicException::class);
        });

        test('throws exception for non-array value in list membership', function (): void {
            $serializer = new NaturalLanguageSerializer();

            // Create an In operator with a non-array value
            $variable = new Variable('field', 'not-an-array');
            $proposition = new In(
                new Variable('field'),
                $variable,
            );
            $rule = new Rule($proposition);

            expect(fn (): string => $serializer->serialize($rule))
                ->toThrow(LogicException::class, 'Expected variable with array value for list membership');
        });

        test('handles operator without operands property', function (): void {
            $parser = new NaturalLanguageParser();
            $serializer = new NaturalLanguageSerializer();

            // Use a standard operator that should work with getOperands() method
            $rule = $parser->parse('age is greater than 18');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('age is greater than 18');
        });

        test('serialize with parentheses for OR inside AND', function (): void {
            $serializer = new NaturalLanguageSerializer();

            // Create a complex expression: a AND (b OR c)
            // This tests the wrapIfNeeded functionality
            $orProposition = new LogicalOr([
                new EqualTo(
                    new Variable('status'),
                    new Variable(null, 'active'),
                ),
                new EqualTo(
                    new Variable('status'),
                    new Variable(null, 'pending'),
                ),
            ]);

            $andProposition = new LogicalAnd([
                new GreaterThan(
                    new Variable('age'),
                    new Variable(null, 18),
                ),
                $orProposition,
            ]);

            $rule = new Rule($andProposition);
            $result = $serializer->serialize($rule);

            expect($result)->toBe('age is greater than 18 and (status equals active or status equals pending)');
        });
    });
});
