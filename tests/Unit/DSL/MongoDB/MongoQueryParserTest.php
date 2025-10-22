<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Core\Context;
use Cline\Ruler\Core\Rule;
use Cline\Ruler\DSL\MongoDB\MongoQueryParser;

describe('MongoQueryParser', function (): void {
    describe('Happy Paths', function (): void {
        test('parse simple comparison expression', function (): void {
            $parser = new MongoQueryParser();
            $rule = $parser->parse(['age' => ['$gt' => 18]]);

            $context = new Context(['age' => 25]);

            expect($rule)->toBeInstanceOf(Rule::class)
                ->and($rule->evaluate($context))->toBeTrue();
        });

        test('parse comparison with field that fails', function (): void {
            $parser = new MongoQueryParser();
            $rule = $parser->parse(['age' => ['$gt' => 18]]);

            $context = new Context(['age' => 15]);

            expect($rule->evaluate($context))->toBeFalse();
        });

        test('parse implicit equality operator', function (): void {
            $parser = new MongoQueryParser();
            $rule = $parser->parse(['status' => 'active']);

            $trueContext = new Context(['status' => 'active']);
            $falseContext = new Context(['status' => 'inactive']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse explicit equality operator', function (): void {
            $parser = new MongoQueryParser();
            $rule = $parser->parse(['status' => ['$eq' => 'active']]);

            $trueContext = new Context(['status' => 'active']);
            $falseContext = new Context(['status' => 'inactive']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse logical and expression', function (): void {
            $parser = new MongoQueryParser();
            $rule = $parser->parse([
                '$and' => [
                    ['age' => ['$gte' => 18]],
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

        test('parse implicit and with multiple fields', function (): void {
            $parser = new MongoQueryParser();
            $rule = $parser->parse([
                'age' => ['$gte' => 18],
                'country' => 'US',
            ]);

            $trueContext = new Context(['age' => 25, 'country' => 'US']);
            $falseContext = new Context(['age' => 15, 'country' => 'US']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse logical or expression', function (): void {
            $parser = new MongoQueryParser();
            $rule = $parser->parse([
                '$or' => [
                    ['age' => ['$gte' => 21]],
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

        test('parse not expression', function (): void {
            $parser = new MongoQueryParser();
            $rule = $parser->parse([
                '$not' => ['age' => ['$lt' => 18]],
            ]);

            $trueContext = new Context(['age' => 25]);
            $falseContext = new Context(['age' => 15]);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse in operator with array', function (): void {
            $parser = new MongoQueryParser();
            $rule = $parser->parse(['country' => ['$in' => ['US', 'CA', 'UK']]]);

            $trueContext = new Context(['country' => 'US']);
            $falseContext = new Context(['country' => 'FR']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse nin operator', function (): void {
            $parser = new MongoQueryParser();
            $rule = $parser->parse(['role' => ['$nin' => ['banned', 'suspended']]]);

            $trueContext = new Context(['role' => 'member']);
            $falseContext = new Context(['role' => 'banned']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse regex operator', function (): void {
            $parser = new MongoQueryParser();
            $rule = $parser->parse(['phone' => ['$regex' => '^\\d{3}-\\d{4}$']]);

            $trueContext = new Context(['phone' => '123-4567']);
            $falseContext = new Context(['phone' => '12-34567']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse regex with options', function (): void {
            $parser = new MongoQueryParser();
            $rule = $parser->parse(['name' => ['$regex' => '^john', '$options' => 'i']]);

            $trueContext = new Context(['name' => 'JOHN DOE']);
            $falseContext = new Context(['name' => 'Jane Doe']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse exists operator true', function (): void {
            $parser = new MongoQueryParser();
            $rule = $parser->parse(['email' => ['$exists' => true]]);

            $trueContext = new Context(['email' => 'test@example.com']);
            $falseContext = new Context(['email' => null]);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse exists operator false', function (): void {
            $parser = new MongoQueryParser();
            $rule = $parser->parse(['deleted_at' => ['$exists' => false]]);

            $trueContext = new Context(['deleted_at' => null]);
            $falseContext = new Context(['deleted_at' => '2025-01-01']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse type operator', function (): void {
            $parser = new MongoQueryParser();
            $rule = $parser->parse(['age' => ['$type' => 'number']]);

            $trueContext = new Context(['age' => 25]);
            $falseContext = new Context(['age' => 'not-a-number']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse between operator', function (): void {
            $parser = new MongoQueryParser();
            $rule = $parser->parse(['age' => ['$between' => [18, 65]]]);

            $trueContext = new Context(['age' => 30]);
            $falseContext = new Context(['age' => 70]);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parseWithAction executes callback when true', function (): void {
            $parser = new MongoQueryParser();
            $executed = false;
            $rule = $parser->parseWithAction(
                ['age' => ['$gte' => 18]],
                function () use (&$executed): void {
                    $executed = true;
                },
            );

            $context = new Context(['age' => 25]);
            $rule->execute($context);

            expect($executed)->toBeTrue();
        });

        test('parseWithAction does not execute callback when false', function (): void {
            $parser = new MongoQueryParser();
            $executed = false;
            $rule = $parser->parseWithAction(
                ['age' => ['$gte' => 18]],
                function () use (&$executed): void {
                    $executed = true;
                },
            );

            $context = new Context(['age' => 15]);
            $rule->execute($context);

            expect($executed)->toBeFalse();
        });

        test('parseJson from JSON string', function (): void {
            $parser = new MongoQueryParser();
            $rule = $parser->parseJson('{"age": {"$gte": 18}}');

            $context = new Context(['age' => 25]);

            expect($rule->evaluate($context))->toBeTrue();
        });

        test('parseJsonWithAction executes callback when true', function (): void {
            $parser = new MongoQueryParser();
            $executed = false;
            $rule = $parser->parseJsonWithAction(
                '{"age": {"$gte": 18}}',
                function () use (&$executed): void {
                    $executed = true;
                },
            );

            $context = new Context(['age' => 25]);
            $rule->execute($context);

            expect($executed)->toBeTrue();
        });

        test('parse strict equality operator', function (): void {
            $parser = new MongoQueryParser();
            $rule = $parser->parse(['age' => ['$same' => 18]]);

            $trueContext = new Context(['age' => 18]);
            $falseContext = new Context(['age' => '18']);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse strict inequality operator', function (): void {
            $parser = new MongoQueryParser();
            $rule = $parser->parse(['verified' => ['$nsame' => false]]);

            $trueContext = new Context(['verified' => 0]);
            $falseContext = new Context(['verified' => false]);

            expect($rule->evaluate($trueContext))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });

        test('parse string operators', function (): void {
            $parser = new MongoQueryParser();

            $startsWithRule = $parser->parse(['name' => ['$startsWith' => 'John']]);
            $endsWithRule = $parser->parse(['name' => ['$endsWith' => 'Doe']]);
            $containsRule = $parser->parse(['name' => ['$contains' => 'Middle']]);

            $context = new Context(['name' => 'John Middle Doe']);

            expect($startsWithRule->evaluate($context))->toBeTrue()
                ->and($endsWithRule->evaluate($context))->toBeTrue()
                ->and($containsRule->evaluate($context))->toBeTrue();
        });
    });

    describe('Edge Cases', function (): void {
        test('parse empty query matches all', function (): void {
            $parser = new MongoQueryParser();
            $rule = $parser->parse([]);

            $context = new Context(['age' => 25]);

            expect($rule->evaluate($context))->toBeTrue();
        });

        test('parse complex nested query', function (): void {
            $parser = new MongoQueryParser();
            $rule = $parser->parse([
                '$or' => [
                    [
                        '$and' => [
                            ['age' => ['$gte' => 18]],
                            ['country' => 'US'],
                        ],
                    ],
                    ['age' => ['$gte' => 21]],
                ],
            ]);

            $trueContext1 = new Context(['age' => 20, 'country' => 'US']);
            $trueContext2 = new Context(['age' => 25, 'country' => 'CA']);
            $falseContext = new Context(['age' => 18, 'country' => 'CA']);

            expect($rule->evaluate($trueContext1))->toBeTrue()
                ->and($rule->evaluate($trueContext2))->toBeTrue()
                ->and($rule->evaluate($falseContext))->toBeFalse();
        });
    });
});
