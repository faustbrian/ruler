<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\DSL\MongoDB\MongoQueryParser;
use Cline\Ruler\DSL\MongoDB\MongoQuerySerializer;

use const JSON_THROW_ON_ERROR;

describe('MongoQuerySerializer', function (): void {
    describe('Happy Paths', function (): void {
        test('serialize simple comparison', function (): void {
            $parser = new MongoQueryParser();
            $serializer = new MongoQuerySerializer();

            $rule = $parser->parse(['age' => ['$gt' => 18]]);
            $result = $serializer->serialize($rule);

            expect($result)->toBe('{"age":{"$gt":18}}');
        });

        test('serialize implicit equality', function (): void {
            $parser = new MongoQueryParser();
            $serializer = new MongoQuerySerializer();

            $rule = $parser->parse(['status' => 'active']);
            $result = $serializer->serialize($rule);

            expect($result)->toBe('{"status":"active"}');
        });

        test('serialize explicit equality', function (): void {
            $parser = new MongoQueryParser();
            $serializer = new MongoQuerySerializer();

            $rule = $parser->parse(['status' => ['$eq' => 'active']]);
            $result = $serializer->serialize($rule);

            expect($result)->toBe('{"status":"active"}');
        });

        test('serialize logical and', function (): void {
            $parser = new MongoQueryParser();
            $serializer = new MongoQuerySerializer();

            $rule = $parser->parse([
                '$and' => [
                    ['age' => ['$gte' => 18]],
                    ['country' => 'US'],
                ],
            ]);
            $result = $serializer->serialize($rule);

            $decoded = json_decode($result, true, 512, JSON_THROW_ON_ERROR);

            expect($decoded)->toHaveKey('$and')
                ->and($decoded['$and'])->toBeArray()
                ->and($decoded['$and'])->toHaveCount(2);
        });

        test('serialize implicit and with multiple fields', function (): void {
            $parser = new MongoQueryParser();
            $serializer = new MongoQuerySerializer();

            $rule = $parser->parse([
                'age' => ['$gte' => 18],
                'country' => 'US',
            ]);
            $result = $serializer->serialize($rule);

            $decoded = json_decode($result, true, 512, JSON_THROW_ON_ERROR);

            expect($decoded)->toHaveKey('$and')
                ->and($decoded['$and'])->toBeArray();
        });

        test('serialize logical or', function (): void {
            $parser = new MongoQueryParser();
            $serializer = new MongoQuerySerializer();

            $rule = $parser->parse([
                '$or' => [
                    ['age' => ['$gte' => 21]],
                    ['country' => 'US'],
                ],
            ]);
            $result = $serializer->serialize($rule);

            $decoded = json_decode($result, true, 512, JSON_THROW_ON_ERROR);

            expect($decoded)->toHaveKey('$or')
                ->and($decoded['$or'])->toBeArray()
                ->and($decoded['$or'])->toHaveCount(2);
        });

        test('serialize not operator', function (): void {
            $parser = new MongoQueryParser();
            $serializer = new MongoQuerySerializer();

            $rule = $parser->parse([
                '$not' => ['age' => ['$lt' => 18]],
            ]);
            $result = $serializer->serialize($rule);

            $decoded = json_decode($result, true, 512, JSON_THROW_ON_ERROR);

            expect($decoded)->toHaveKey('$not');
        });

        test('serialize in operator with array', function (): void {
            $parser = new MongoQueryParser();
            $serializer = new MongoQuerySerializer();

            $rule = $parser->parse(['country' => ['$in' => ['US', 'CA', 'UK']]]);
            $result = $serializer->serialize($rule);

            expect($result)->toBe('{"country":{"$in":["US","CA","UK"]}}');
        });

        test('serialize nin operator', function (): void {
            $parser = new MongoQueryParser();
            $serializer = new MongoQuerySerializer();

            $rule = $parser->parse(['role' => ['$nin' => ['banned', 'suspended']]]);
            $result = $serializer->serialize($rule);

            expect($result)->toBe('{"role":{"$nin":["banned","suspended"]}}');
        });

        test('serialize regex operator', function (): void {
            $parser = new MongoQueryParser();
            $serializer = new MongoQuerySerializer();

            $rule = $parser->parse(['phone' => ['$regex' => '^\\d{3}-\\d{4}$']]);
            $result = $serializer->serialize($rule);

            $decoded = json_decode($result, true, 512, JSON_THROW_ON_ERROR);

            expect($decoded)->toHaveKey('phone')
                ->and($decoded['phone'])->toHaveKey('$regex');
        });

        test('serialize regex with options', function (): void {
            $parser = new MongoQueryParser();
            $serializer = new MongoQuerySerializer();

            $rule = $parser->parse(['name' => ['$regex' => '^john', '$options' => 'i']]);
            $result = $serializer->serialize($rule);

            $decoded = json_decode($result, true, 512, JSON_THROW_ON_ERROR);

            expect($decoded)->toHaveKey('name')
                ->and($decoded['name'])->toHaveKey('$regex')
                ->and($decoded['name'])->toHaveKey('$options')
                ->and($decoded['name']['$options'])->toBe('i');
        });

        test('serialize exists operator', function (): void {
            $parser = new MongoQueryParser();
            $serializer = new MongoQuerySerializer();

            $rule = $parser->parse(['email' => ['$exists' => true]]);
            $result = $serializer->serialize($rule);

            $decoded = json_decode($result, true, 512, JSON_THROW_ON_ERROR);

            expect($decoded)->toHaveKey('email')
                ->and($decoded['email'])->toHaveKey('$exists');
        });

        test('serialize type operator', function (): void {
            $parser = new MongoQueryParser();
            $serializer = new MongoQuerySerializer();

            $rule = $parser->parse(['age' => ['$type' => 'number']]);
            $result = $serializer->serialize($rule);

            expect($result)->toBe('{"age":{"$type":"number"}}');
        });

        test('serialize between operator', function (): void {
            $parser = new MongoQueryParser();
            $serializer = new MongoQuerySerializer();

            $rule = $parser->parse(['age' => ['$between' => [18, 65]]]);
            $result = $serializer->serialize($rule);

            expect($result)->toBe('{"age":{"$between":[18,65]}}');
        });

        test('serialize inequality operator', function (): void {
            $parser = new MongoQueryParser();
            $serializer = new MongoQuerySerializer();

            $rule = $parser->parse(['status' => ['$ne' => 'inactive']]);
            $result = $serializer->serialize($rule);

            expect($result)->toBe('{"status":{"$ne":"inactive"}}');
        });

        test('serialize less than or equal', function (): void {
            $parser = new MongoQueryParser();
            $serializer = new MongoQuerySerializer();

            $rule = $parser->parse(['age' => ['$lte' => 65]]);
            $result = $serializer->serialize($rule);

            expect($result)->toBe('{"age":{"$lte":65}}');
        });

        test('serialize greater than or equal', function (): void {
            $parser = new MongoQueryParser();
            $serializer = new MongoQuerySerializer();

            $rule = $parser->parse(['age' => ['$gte' => 18]]);
            $result = $serializer->serialize($rule);

            expect($result)->toBe('{"age":{"$gte":18}}');
        });

        test('serialize less than', function (): void {
            $parser = new MongoQueryParser();
            $serializer = new MongoQuerySerializer();

            $rule = $parser->parse(['quantity' => ['$lt' => 10]]);
            $result = $serializer->serialize($rule);

            expect($result)->toBe('{"quantity":{"$lt":10}}');
        });

        test('serialize strict equality', function (): void {
            $parser = new MongoQueryParser();
            $serializer = new MongoQuerySerializer();

            $rule = $parser->parse(['age' => ['$same' => 18]]);
            $result = $serializer->serialize($rule);

            expect($result)->toBe('{"age":{"$same":18}}');
        });

        test('serialize strict inequality', function (): void {
            $parser = new MongoQueryParser();
            $serializer = new MongoQuerySerializer();

            $rule = $parser->parse(['verified' => ['$nsame' => false]]);
            $result = $serializer->serialize($rule);

            expect($result)->toBe('{"verified":{"$nsame":false}}');
        });

        test('serialize boolean true value', function (): void {
            $parser = new MongoQueryParser();
            $serializer = new MongoQuerySerializer();

            $rule = $parser->parse(['verified' => true]);
            $result = $serializer->serialize($rule);

            expect($result)->toBe('{"verified":true}');
        });

        test('serialize boolean false value', function (): void {
            $parser = new MongoQueryParser();
            $serializer = new MongoQuerySerializer();

            $rule = $parser->parse(['deleted' => false]);
            $result = $serializer->serialize($rule);

            expect($result)->toBe('{"deleted":false}');
        });

        test('serialize null value', function (): void {
            $parser = new MongoQueryParser();
            $serializer = new MongoQuerySerializer();

            $rule = $parser->parse(['deleted_at' => null]);
            $result = $serializer->serialize($rule);

            // MongoDB semantics: {field: null} is equivalent to {field: {$exists: false}}
            expect($result)->toBe('{"deleted_at":{"$exists":false}}');
        });

        test('serialize numeric values', function (): void {
            $parser = new MongoQueryParser();
            $serializer = new MongoQuerySerializer();

            $rule = $parser->parse(['price' => ['$gt' => 99.99]]);
            $result = $serializer->serialize($rule);

            expect($result)->toBe('{"price":{"$gt":99.99}}');
        });

        test('serialize string operators', function (): void {
            $parser = new MongoQueryParser();
            $serializer = new MongoQuerySerializer();

            $rule = $parser->parse(['name' => ['$startsWith' => 'John']]);
            $result = $serializer->serialize($rule);

            expect($result)->toBe('{"name":{"$startsWith":"John"}}');
        });

        test('serializeToArray returns array', function (): void {
            $parser = new MongoQueryParser();
            $serializer = new MongoQuerySerializer();

            $rule = $parser->parse(['age' => ['$gte' => 18]]);
            $result = $serializer->serializeToArray($rule);

            expect($result)->toBeArray()
                ->and($result)->toHaveKey('age')
                ->and($result['age'])->toHaveKey('$gte')
                ->and($result['age']['$gte'])->toBe(18);
        });
    });

    describe('Round-Trip Tests', function (): void {
        test('round trip simple comparison', function (): void {
            $parser = new MongoQueryParser();
            $serializer = new MongoQuerySerializer();

            $original = ['age' => ['$gt' => 18]];
            $rule = $parser->parse($original);
            $serialized = $serializer->serializeToArray($rule);
            $reparsed = $parser->parse($serialized);

            expect($serialized)->toEqual($original);
        });

        test('round trip implicit equality', function (): void {
            $parser = new MongoQueryParser();
            $serializer = new MongoQuerySerializer();

            $original = ['status' => 'active'];
            $rule = $parser->parse($original);
            $serialized = $serializer->serializeToArray($rule);

            expect($serialized)->toEqual($original);
        });

        test('round trip logical operators', function (): void {
            $parser = new MongoQueryParser();
            $serializer = new MongoQuerySerializer();

            $original = [
                '$and' => [
                    ['age' => ['$gte' => 18]],
                    ['country' => 'US'],
                ],
            ];
            $rule = $parser->parse($original);
            $serialized = $serializer->serializeToArray($rule);

            expect($serialized)->toEqual($original);
        });

        test('round trip with arrays', function (): void {
            $parser = new MongoQueryParser();
            $serializer = new MongoQuerySerializer();

            $original = ['country' => ['$in' => ['US', 'CA', 'UK']]];
            $rule = $parser->parse($original);
            $serialized = $serializer->serializeToArray($rule);

            expect($serialized)->toEqual($original);
        });

        test('round trip between operator', function (): void {
            $parser = new MongoQueryParser();
            $serializer = new MongoQuerySerializer();

            $original = ['age' => ['$between' => [18, 65]]];
            $rule = $parser->parse($original);
            $serialized = $serializer->serializeToArray($rule);

            expect($serialized)->toEqual($original);
        });
    });

    describe('Edge Cases', function (): void {
        test('serialize complex nested query', function (): void {
            $parser = new MongoQueryParser();
            $serializer = new MongoQuerySerializer();

            $query = [
                '$or' => [
                    [
                        '$and' => [
                            ['age' => ['$gte' => 18]],
                            ['country' => 'US'],
                        ],
                    ],
                    ['age' => ['$gte' => 21]],
                ],
            ];

            $rule = $parser->parse($query);
            $result = $serializer->serializeToArray($rule);

            expect($result)->toHaveKey('$or')
                ->and($result['$or'])->toBeArray();
        });
    });
});
