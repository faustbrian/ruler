<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Rule;
use Cline\Ruler\DSL\GraphQL\GraphQLFilterParser;

describe('GraphQLFilterParser', function (): void {
    describe('Happy Paths', function (): void {
        test('parse simple comparison expression', function (): void {
            $parser = new GraphQLFilterParser();
            $rule = $parser->parse(['age' => ['gt' => 18]]);

            $context = new Context(['age' => 25]);

            expect($rule)->toBeInstanceOf(Rule::class)
                ->and($rule->evaluate($context))->toBeTrue();
        });

        test('parse comparison with field that fails', function (): void {
            $parser = new GraphQLFilterParser();
            $rule = $parser->parse(['age' => ['gt' => 18]]);

            $context = new Context(['age' => 15]);

            expect($rule->evaluate($context))->toBeFalse();
        });

        test('parse equality operator', function (): void {
            $parser = new GraphQLFilterParser();
            $rule = $parser->parse(['status' => ['eq' => 'active']]);

            $trueContext = new Context(['status' => 'active']);
            $falseContext = new Context(['status' => 'inactive']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse implicit equality', function (): void {
            $parser = new GraphQLFilterParser();
            $rule = $parser->parse(['status' => 'active']);

            $trueContext = new Context(['status' => 'active']);
            $falseContext = new Context(['status' => 'inactive']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse logical AND expression', function (): void {
            $parser = new GraphQLFilterParser();
            $rule = $parser->parse([
                'AND' => [
                    ['age' => ['gte' => 18]],
                    ['country' => 'US'],
                ],
            ]);

            $trueContext = new Context(['age' => 25, 'country' => 'US']);
            $falseContext1 = new Context(['age' => 15, 'country' => 'US']);
            $falseContext2 = new Context(['age' => 25, 'country' => 'CA']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext1))->toBeFalse()
                ->and($rule->evaluate($falseContext2))->toBeFalse();
        });

        test('parse implicit AND with multiple fields', function (): void {
            $parser = new GraphQLFilterParser();
            $rule = $parser->parse([
                'age' => ['gte' => 18],
                'country' => 'US',
            ]);

            $trueContext = new Context(['age' => 25, 'country' => 'US']);
            $falseContext1 = new Context(['age' => 15, 'country' => 'US']);
            $falseContext2 = new Context(['age' => 25, 'country' => 'CA']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext1))->toBeFalse()
                ->and($rule->evaluate($falseContext2))->toBeFalse();
        });

        test('parse logical OR expression', function (): void {
            $parser = new GraphQLFilterParser();
            $rule = $parser->parse([
                'OR' => [
                    ['age' => ['gte' => 21]],
                    ['country' => 'US'],
                ],
            ]);

            $trueContext1 = new Context(['age' => 25, 'country' => 'CA']);
            $trueContext2 = new Context(['age' => 18, 'country' => 'US']);
            $falseContext = new Context(['age' => 18, 'country' => 'CA']);

            expect($rule->evaluate($trueContext1))->toBeTrue()
                ->and($rule->evaluate($trueContext2))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse NOT expression', function (): void {
            $parser = new GraphQLFilterParser();
            $rule = $parser->parse(['NOT' => ['age' => ['lt' => 18]]]);

            $trueContext = new Context(['age' => 25]);
            $falseContext = new Context(['age' => 15]);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parseWithAction executes callback when true', function (): void {
            $parser = new GraphQLFilterParser();
            $executed = false;
            $rule = $parser->parseWithAction(['age' => ['gte' => 18]], function () use (&$executed): void {
                $executed = true;
            });

            $context = new Context(['age' => 25]);
            $rule->execute($context);

            expect($executed)->toBeTrue();
        });

        test('parseWithAction does not execute callback when false', function (): void {
            $parser = new GraphQLFilterParser();
            $executed = false;
            $rule = $parser->parseWithAction(['age' => ['gte' => 18]], function () use (&$executed): void {
                $executed = true;
            });

            $context = new Context(['age' => 15]);
            $rule->execute($context);

            expect($executed)->toBeFalse();
        });

        test('parse in operator with array', function (): void {
            $parser = new GraphQLFilterParser();
            $rule = $parser->parse(['country' => ['in' => ['US', 'CA', 'UK']]]);

            $trueContext = new Context(['country' => 'US']);
            $falseContext = new Context(['country' => 'FR']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse notIn operator', function (): void {
            $parser = new GraphQLFilterParser();
            $rule = $parser->parse(['role' => ['notIn' => ['banned', 'suspended']]]);

            $trueContext = new Context(['role' => 'active']);
            $falseContext = new Context(['role' => 'banned']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse contains operator', function (): void {
            $parser = new GraphQLFilterParser();
            $rule = $parser->parse(['email' => ['contains' => '@example.com']]);

            $trueContext = new Context(['email' => 'user@example.com']);
            $falseContext = new Context(['email' => 'user@test.com']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse startsWith operator', function (): void {
            $parser = new GraphQLFilterParser();
            $rule = $parser->parse(['name' => ['startsWith' => 'John']]);

            $trueContext = new Context(['name' => 'John Doe']);
            $falseContext = new Context(['name' => 'Jane Smith']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse endsWith operator', function (): void {
            $parser = new GraphQLFilterParser();
            $rule = $parser->parse(['filename' => ['endsWith' => '.pdf']]);

            $trueContext = new Context(['filename' => 'document.pdf']);
            $falseContext = new Context(['filename' => 'document.docx']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse match operator with regex', function (): void {
            $parser = new GraphQLFilterParser();
            $rule = $parser->parse(['phone' => ['match' => '^\\d{3}-\\d{4}$']]);

            $trueContext = new Context(['phone' => '123-4567']);
            $falseContext = new Context(['phone' => '12-34567']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse inequality operator', function (): void {
            $parser = new GraphQLFilterParser();
            $rule = $parser->parse(['status' => ['ne' => 'inactive']]);

            $trueContext = new Context(['status' => 'active']);
            $falseContext = new Context(['status' => 'inactive']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse less than or equal operator', function (): void {
            $parser = new GraphQLFilterParser();
            $rule = $parser->parse(['age' => ['lte' => 65]]);

            $trueContext = new Context(['age' => 30]);
            $falseContext = new Context(['age' => 70]);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse range query with multiple operators', function (): void {
            $parser = new GraphQLFilterParser();
            $rule = $parser->parse(['age' => ['gte' => 18, 'lte' => 65]]);

            $trueContext = new Context(['age' => 30]);
            $falseContext1 = new Context(['age' => 15]);
            $falseContext2 = new Context(['age' => 70]);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext1))->toBeFalse()
                ->and($rule->evaluate($falseContext2))->toBeFalse();
        });

        test('parse expression with nested logic', function (): void {
            $parser = new GraphQLFilterParser();
            $rule = $parser->parse([
                'OR' => [
                    [
                        'AND' => [
                            ['age' => ['gte' => 18]],
                            ['country' => 'US'],
                        ],
                    ],
                    ['age' => ['gte' => 21]],
                ],
            ]);

            $trueContext1 = new Context(['age' => 20, 'country' => 'US']);
            $trueContext2 = new Context(['age' => 25, 'country' => 'CA']);
            $falseContext = new Context(['age' => 18, 'country' => 'CA']);

            expect($rule->evaluate($trueContext1))->toBeTrue()
                ->and($rule->evaluate($trueContext2))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse JSON string input', function (): void {
            $parser = new GraphQLFilterParser();
            $json = '{"age": {"gte": 18}, "country": "US"}';
            $rule = $parser->parse($json);

            $trueContext = new Context(['age' => 25, 'country' => 'US']);
            $falseContext = new Context(['age' => 15, 'country' => 'US']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });
    });
});
