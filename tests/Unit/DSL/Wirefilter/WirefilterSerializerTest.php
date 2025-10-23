<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\Builder\RuleBuilder;
use Cline\Ruler\Core\Proposition;
use Cline\Ruler\DSL\Wirefilter\WirefilterParser;
use Cline\Ruler\DSL\Wirefilter\WirefilterSerializer;
use Cline\Ruler\Operators\Comparison\Between;
use Cline\Ruler\Operators\Comparison\EqualTo;
use Cline\Ruler\Operators\Comparison\GreaterThan;
use Cline\Ruler\Operators\Comparison\GreaterThanOrEqualTo;
use Cline\Ruler\Operators\Comparison\LessThan;
use Cline\Ruler\Operators\Logical\LogicalAnd;
use Cline\Ruler\Operators\Logical\LogicalNand;
use Cline\Ruler\Operators\Logical\LogicalNor;
use Cline\Ruler\Operators\Logical\LogicalNot;
use Cline\Ruler\Operators\Logical\LogicalOr;
use Cline\Ruler\Operators\Logical\LogicalXor;
use Cline\Ruler\Operators\String\StartsWith;
use Cline\Ruler\Operators\String\StringContains;
use Cline\Ruler\Variables\Variable;

describe('WirefilterSerializer', function (): void {
    describe('Happy Paths', function (): void {
        test('serialize simple comparison', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('age > 18');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('age > 18');
        });

        test('serialize equality comparison', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('status == "active"');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('status == "active"');
        });

        test('serialize logical and', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('age >= 18 and country == "US"');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('age >= 18 and country == "US"');
        });

        test('serialize logical or', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('age >= 21 or country == "US"');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('age >= 21 or country == "US"');
        });

        test('serialize not operator', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('not (age < 18)');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('not (age < 18)');
        });

        test('serialize mathematical addition', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('price + shipping > 100');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('price + shipping > 100');
        });

        test('serialize mathematical subtraction', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('total - discount < 100');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('total - discount < 100');
        });

        test('serialize mathematical multiplication', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('quantity * price > 1000');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('quantity * price > 1000');
        });

        test('serialize mathematical division', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('total / count == 10');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('total / count == 10');
        });

        test('serialize modulo operator', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('value % 2 == 0');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('value % 2 == 0');
        });

        test('serialize exponentiation operator', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('base ** power > 100');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('base ** power > 100');
        });

        test('serialize negation operator', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('-value < 0');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('-value < 0');
        });

        test('serialize in operator with array', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('country in ["US", "CA", "UK"]');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('country in ["US", "CA", "UK"]');
        });

        test('serialize not in operator', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('role not in ["banned", "suspended"]');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('role not in ["banned", "suspended"]');
        });

        test('serialize matches operator', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('phone matches "/^\\\\d{3}-\\\\d{4}$/"');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('phone matches "/^\\\\d{3}-\\\\d{4}$/"');
        });

        test('serialize strict equality', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('age === 18');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('age === 18');
        });

        test('serialize strict inequality', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('verified !== false');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('verified !== false');
        });

        test('serialize inequality operator', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('status != "inactive"');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('status != "inactive"');
        });

        test('serialize less than or equal', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('age <= 65');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('age <= 65');
        });

        test('serialize greater than or equal', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('age >= 18');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('age >= 18');
        });

        test('serialize less than', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('quantity < 10');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('quantity < 10');
        });

        test('serialize complex nested expression', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('(age >= 18 and country == "US") or age >= 21');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('(age >= 18 and country == "US") or age >= 21');
        });

        test('serialize boolean true value', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('verified == true');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('verified == true');
        });

        test('serialize boolean false value', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('deleted == false');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('deleted == false');
        });

        test('serialize null value', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('deleted_at == null');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('deleted_at == null');
        });

        test('serialize numeric values', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('price > 99.99');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('price > 99.99');
        });

        test('serialize nested proposition in parentheses', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('(age >= 18 and country == "US") or age >= 21');
            $result = $serializer->serialize($rule);

            // Should preserve parentheses for AND inside OR
            expect($result)->toContain('(');
        });

        test('serialize same as operator', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('value === 1');
            $result = $serializer->serialize($rule);

            expect($result)->toContain('===');
        });

        test('serialize not same as operator', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('value !== 1');
            $result = $serializer->serialize($rule);

            expect($result)->toContain('!==');
        });

        test('serialize generic operator via registry lookup', function (): void {
            // Test serializeGenericOperator path by using an operator not in explicit match
            $builder = new RuleBuilder();
            $serializer = new WirefilterSerializer();

            // Create a rule using StartsWith which isn't in the explicit match cases
            $variable = new Variable('name');
            $value = new Variable(null, 'John');
            $operator = new StartsWith($variable, $value);
            $rule = $builder->create($operator);

            $result = $serializer->serialize($rule);

            expect($result)->toContain('startsWith');
        });

        test('serialize operator with getOperands method fallback', function (): void {
            // Test getOperands fallback path with an operator that might use method instead of property
            $builder = new RuleBuilder();
            $serializer = new WirefilterSerializer();

            $variable = new Variable('text');
            $value = new Variable(null, 'test');
            $operator = new StringContains($variable, $value);
            $rule = $builder->create($operator);

            $result = $serializer->serialize($rule);

            // StringContains maps to contains() in wirefilter DSL
            expect($result)->toContain('contains');
        });

        test('serialize between function', function (): void {
            $builder = new RuleBuilder();
            $serializer = new WirefilterSerializer();

            // Create Between operator manually
            $age = new Variable('age');
            $min = new Variable(null, 18);
            $max = new Variable(null, 65);
            $between = new Between($age, $min, $max);
            $rule = $builder->create($between);

            $result = $serializer->serialize($rule);

            expect($result)->toContain('between');
            expect($result)->toContain('age');
        });

        test('serialize logical xor', function (): void {
            $builder = new RuleBuilder();
            $serializer = new WirefilterSerializer();

            $age = new Variable('age');
            $verified = new Variable('verified');
            $ageCheck = new GreaterThanOrEqualTo($age, new Variable(null, 18));
            $verifiedCheck = new EqualTo($verified, new Variable(null, true));
            $xor = new LogicalXor([$ageCheck, $verifiedCheck]);
            $rule = $builder->create($xor);

            $result = $serializer->serialize($rule);

            expect($result)->toContain('xor');
        });

        test('serialize logical nand', function (): void {
            $builder = new RuleBuilder();
            $serializer = new WirefilterSerializer();

            $age = new Variable('age');
            $country = new Variable('country');
            $ageCheck = new GreaterThanOrEqualTo($age, new Variable(null, 18));
            $countryCheck = new EqualTo($country, new Variable(null, 'US'));
            $nand = new LogicalNand([$ageCheck, $countryCheck]);
            $rule = $builder->create($nand);

            $result = $serializer->serialize($rule);

            expect($result)->toContain('nand');
        });

        test('serialize logical nor', function (): void {
            $builder = new RuleBuilder();
            $serializer = new WirefilterSerializer();

            $age = new Variable('age');
            $banned = new Variable('banned');
            $ageCheck = new LessThan($age, new Variable(null, 18));
            $bannedCheck = new EqualTo($banned, new Variable(null, true));
            $nor = new LogicalNor([$ageCheck, $bannedCheck]);
            $rule = $builder->create($nor);

            $result = $serializer->serialize($rule);

            expect($result)->toContain('nor');
        });

        test('serialize escaped string value', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('message == "Hello \\"World\\""');
            $result = $serializer->serialize($rule);

            expect($result)->toContain('Hello');
        });

        test('serialize nested array values', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $rule = $parser->parse('tags in [1, 2, 3]');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('tags in [1, 2, 3]');
        });
    });

    describe('Sad Paths', function (): void {
        test('throw exception for unsupported value type', function (): void {
            $serializer = new WirefilterSerializer();
            $builder = new RuleBuilder();

            // Create a variable with an object value that cannot be serialized
            $variable = new Variable('test');
            $objectValue = new Variable(null, new stdClass());
            $operator = new EqualTo($variable, $objectValue);
            $rule = $builder->create($operator);

            expect(fn (): string => $serializer->serialize($rule))
                ->toThrow(LogicException::class, 'Cannot cast value to string: object');
        });

        test('throw exception for unknown operator', function (): void {
            $serializer = new WirefilterSerializer();
            $builder = new RuleBuilder();

            // Create a custom operator that's not in the registry
            $customOperator = new class() implements Proposition
            {
                public function evaluate(mixed $context): bool
                {
                    return true;
                }
            };

            $rule = $builder->create($customOperator);

            expect(fn (): string => $serializer->serialize($rule))
                ->toThrow(LogicException::class);
        });

        test('throw exception for binary operator with wrong operand count', function (): void {
            $serializer = new WirefilterSerializer();
            $builder = new RuleBuilder();

            // Create an operator with wrong number of operands using reflection
            $variable = new Variable('test');
            $operator = new EqualTo($variable, new Variable(null, 1));

            // Modify operands to have wrong count using reflection
            $reflection = new ReflectionClass($operator);
            $operandsProperty = $reflection->getProperty('operands');
            $operandsProperty->setValue($operator, [$variable]); // Only 1 operand instead of 2

            $rule = $builder->create($operator);

            expect(fn (): string => $serializer->serialize($rule))
                ->toThrow(LogicException::class, 'Binary operator == requires exactly 2 operands');
        });

        test('throw exception for not operator with wrong operand count', function (): void {
            $serializer = new WirefilterSerializer();
            $builder = new RuleBuilder();

            // Create a NOT operator with wrong number of operands
            $var1 = new Variable('test1');
            $var2 = new Variable('test2');
            $condition = new EqualTo($var1, new Variable(null, 1));
            $notOperator = new LogicalNot([$condition]);

            // Modify to have wrong operand count
            $reflection = new ReflectionClass($notOperator);
            $operandsProperty = $reflection->getProperty('operands');
            $operandsProperty->setValue($notOperator, [$condition, $var2]); // 2 operands instead of 1

            $rule = $builder->create($notOperator);

            expect(fn (): string => $serializer->serialize($rule))
                ->toThrow(LogicException::class, 'NOT operator requires exactly 1 operand');
        });
    });

    describe('Edge Cases', function (): void {
        test('serialize variable with null name and math operator value', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            // This tests the path where variable has null name and value is a math operator
            $rule = $parser->parse('price + 10 > 100');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('price + 10 > 100');
        });

        test('serialize not operator with non-proposition operand', function (): void {
            $serializer = new WirefilterSerializer();
            $builder = new RuleBuilder();

            // Create NOT with a proposition first
            $age = new Variable('age');
            $condition = new GreaterThan($age, new Variable(null, 18));
            $notOperator = new LogicalNot([$condition]);

            // Use reflection to inject a non-proposition operand (for coverage of line 237)
            $variable = new Variable('active');
            $reflection = new ReflectionClass($notOperator);
            $operandsProperty = $reflection->getProperty('operands');
            $operandsProperty->setValue($notOperator, [$variable]);

            $rule = $builder->create($notOperator);

            $result = $serializer->serialize($rule);

            expect($result)->toBe('not active');
        });

        test('serialize nested proposition as operand', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            // This creates nested propositions as operands
            $rule = $parser->parse('(age >= 18 and country == "US") or (age >= 21 and country == "CA")');
            $result = $serializer->serialize($rule);

            expect($result)->toContain('and');
            expect($result)->toContain('or');
        });

        test('serialize with operator precedence wrapping', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            // Test wrapping: AND inside OR needs parentheses
            $rule = $parser->parse('(age >= 18 and verified == true) or country == "US"');
            $result = $serializer->serialize($rule);

            expect($result)->toContain('(');
            expect($result)->toContain(')');
        });

        test('serialize or inside and with wrapping', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            // Test wrapping: OR inside AND needs parentheses
            $rule = $parser->parse('(age >= 18 or verified == true) and country == "US"');
            $result = $serializer->serialize($rule);

            expect($result)->toContain('(');
            expect($result)->toContain(')');
        });

        test('serialize and inside xor with wrapping', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            // Test wrapping: AND inside XOR needs parentheses
            $rule = $parser->parse('(age >= 18 and verified == true) xor country == "US"');
            $result = $serializer->serialize($rule);

            expect($result)->toContain('(');
            expect($result)->toContain('xor');
        });

        test('serialize or inside nand with wrapping', function (): void {
            $builder = new RuleBuilder();
            $serializer = new WirefilterSerializer();

            // Test wrapping: OR inside NAND needs parentheses
            $age = new Variable('age');
            $verified = new Variable('verified');
            $country = new Variable('country');
            $ageCheck = new GreaterThanOrEqualTo($age, new Variable(null, 18));
            $verifiedCheck = new EqualTo($verified, new Variable(null, true));
            $orOp = new LogicalOr([$ageCheck, $verifiedCheck]);
            $countryCheck = new EqualTo($country, new Variable(null, 'US'));
            $nand = new LogicalNand([$orOp, $countryCheck]);
            $rule = $builder->create($nand);

            $result = $serializer->serialize($rule);

            expect($result)->toContain('(');
            expect($result)->toContain('nand');
        });

        test('serialize and inside nor with wrapping', function (): void {
            $builder = new RuleBuilder();
            $serializer = new WirefilterSerializer();

            // Test wrapping: AND inside NOR needs parentheses
            $age = new Variable('age');
            $verified = new Variable('verified');
            $country = new Variable('country');
            $ageCheck = new GreaterThanOrEqualTo($age, new Variable(null, 18));
            $verifiedCheck = new EqualTo($verified, new Variable(null, true));
            $andOp = new LogicalAnd([$ageCheck, $verifiedCheck]);
            $countryCheck = new EqualTo($country, new Variable(null, 'US'));
            $nor = new LogicalNor([$andOp, $countryCheck]);
            $rule = $builder->create($nor);

            $result = $serializer->serialize($rule);

            expect($result)->toContain('(');
            expect($result)->toContain('nor');
        });

        test('serialize variable with null name and non-operator value', function (): void {
            $serializer = new WirefilterSerializer();
            $builder = new RuleBuilder();

            // Create a variable with null name and a simple value (not a math operator)
            $variable = new Variable('test');
            $value = new Variable(null, 'simple_value');
            $operator = new EqualTo($variable, $value);
            $rule = $builder->create($operator);

            $result = $serializer->serialize($rule);

            expect($result)->toBe('test == "simple_value"');
        });

        test('serialize with operands property returning non-array', function (): void {
            $serializer = new WirefilterSerializer();
            $builder = new RuleBuilder();

            // Create a custom operator with operands property that's not an array
            $customOp = new class() implements Proposition
            {
                public $operands = 'not-an-array';

                public function evaluate(mixed $context): bool
                {
                    return true;
                }
            };

            $rule = $builder->create($customOp);

            // This should use the fallback path (lines 134-135)
            expect(fn (): string => $serializer->serialize($rule))
                ->toThrow(LogicException::class);
        });

        test('serialize deeply nested proposition as operand', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            // Create a very nested structure with proposition inside math operation
            $rule = $parser->parse('(age >= 18 and country == "US") or (price + (quantity * 2) > 100)');
            $result = $serializer->serialize($rule);

            // This tests line 298 - nested propositions as operands
            expect($result)->toContain('and');
            expect($result)->toContain('or');
            expect($result)->toContain('+');
            expect($result)->toContain('*');
        });

        test('serialize variable with null name and primitive value', function (): void {
            $serializer = new WirefilterSerializer();
            $builder = new RuleBuilder();

            // Create a variable with null name and a primitive value (tests line 338)
            $variable = new Variable('test');
            $value = new Variable(null, 42);
            $operator = new EqualTo($variable, $value);
            $rule = $builder->create($operator);

            $result = $serializer->serialize($rule);

            expect($result)->toBe('test == 42');
        });
    });

    describe('Round-Trip Tests', function (): void {
        test('round trip simple comparison', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $original = 'age > 18';
            $rule = $parser->parse($original);
            $serialized = $serializer->serialize($rule);
            $reparsed = $parser->parse($serialized);

            expect($serialized)->toBe($original);
        });

        test('round trip complex expression', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $original = 'age >= 18 and country == "US"';
            $rule = $parser->parse($original);
            $serialized = $serializer->serialize($rule);

            expect($serialized)->toBe($original);
        });

        test('round trip mathematical expression', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $original = 'price + shipping > 100';
            $rule = $parser->parse($original);
            $serialized = $serializer->serialize($rule);

            expect($serialized)->toBe($original);
        });

        test('round trip with arrays', function (): void {
            $parser = new WirefilterParser();
            $serializer = new WirefilterSerializer();

            $original = 'country in ["US", "CA", "UK"]';
            $rule = $parser->parse($original);
            $serialized = $serializer->serialize($rule);

            expect($serialized)->toBe($original);
        });
    });
});
