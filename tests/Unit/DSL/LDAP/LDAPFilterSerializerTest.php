<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ruler\DSL\LDAP\LDAPFilterParser;
use Cline\Ruler\DSL\LDAP\LDAPFilterSerializer;

describe('LDAPFilterSerializer', function (): void {
    describe('Happy Paths', function (): void {
        test('serialize simple equality', function (): void {
            $parser = new LDAPFilterParser();
            $serializer = new LDAPFilterSerializer();

            $rule = $parser->parse('(country=US)');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('(country=US)');
        });

        test('serialize greater than or equal', function (): void {
            $parser = new LDAPFilterParser();
            $serializer = new LDAPFilterSerializer();

            $rule = $parser->parse('(age>=18)');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('(age>=18)');
        });

        test('serialize less than or equal', function (): void {
            $parser = new LDAPFilterParser();
            $serializer = new LDAPFilterSerializer();

            $rule = $parser->parse('(price<=100)');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('(price<=100)');
        });

        test('serialize greater than', function (): void {
            $parser = new LDAPFilterParser();
            $serializer = new LDAPFilterSerializer();

            $rule = $parser->parse('(age>21)');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('(age>21)');
        });

        test('serialize less than', function (): void {
            $parser = new LDAPFilterParser();
            $serializer = new LDAPFilterSerializer();

            $rule = $parser->parse('(quantity<10)');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('(quantity<10)');
        });

        test('serialize AND logical operator', function (): void {
            $parser = new LDAPFilterParser();
            $serializer = new LDAPFilterSerializer();

            $rule = $parser->parse('(&(age>=18)(country=US))');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('(&(age>=18)(country=US))');
        });

        test('serialize OR logical operator', function (): void {
            $parser = new LDAPFilterParser();
            $serializer = new LDAPFilterSerializer();

            $rule = $parser->parse('(|(age>=21)(country=US))');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('(|(age>=21)(country=US))');
        });

        test('serialize NOT logical operator', function (): void {
            $parser = new LDAPFilterParser();
            $serializer = new LDAPFilterSerializer();

            $rule = $parser->parse('(!(age<18))');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('(!(age<18))');
        });

        test('serialize nested logical operators', function (): void {
            $parser = new LDAPFilterParser();
            $serializer = new LDAPFilterSerializer();

            $rule = $parser->parse('(&(|(age>=21)(country=US))(status=active))');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('(&(|(age>=21)(country=US))(status=active))');
        });

        test('serialize presence check', function (): void {
            $parser = new LDAPFilterParser();
            $serializer = new LDAPFilterSerializer();

            $rule = $parser->parse('(email=*)');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('(email=*)');
        });

        test('serialize wildcard prefix match', function (): void {
            $parser = new LDAPFilterParser();
            $serializer = new LDAPFilterSerializer();

            $rule = $parser->parse('(name=John*)');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('(name=John*)');
        });

        test('serialize wildcard suffix match', function (): void {
            $parser = new LDAPFilterParser();
            $serializer = new LDAPFilterSerializer();

            $rule = $parser->parse('(email=*@example.com)');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('(email=*@example.com)');
        });

        test('serialize wildcard contains match', function (): void {
            $parser = new LDAPFilterParser();
            $serializer = new LDAPFilterSerializer();

            $rule = $parser->parse('(description=*important*)');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('(description=*important*)');
        });

        test('serialize approximate match', function (): void {
            $parser = new LDAPFilterParser();
            $serializer = new LDAPFilterSerializer();

            $rule = $parser->parse('(name~=john)');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('(name~=john)');
        });

        test('serialize numeric value', function (): void {
            $parser = new LDAPFilterParser();
            $serializer = new LDAPFilterSerializer();

            $rule = $parser->parse('(age=25)');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('(age=25)');
        });

        test('serialize float value', function (): void {
            $parser = new LDAPFilterParser();
            $serializer = new LDAPFilterSerializer();

            $rule = $parser->parse('(price=99.99)');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('(price=99.99)');
        });

        test('serialize boolean true value', function (): void {
            $parser = new LDAPFilterParser();
            $serializer = new LDAPFilterSerializer();

            $rule = $parser->parse('(verified=true)');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('(verified=true)');
        });

        test('serialize boolean false value', function (): void {
            $parser = new LDAPFilterParser();
            $serializer = new LDAPFilterSerializer();

            $rule = $parser->parse('(active=false)');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('(active=false)');
        });

        test('serialize complex multi-condition filter', function (): void {
            $parser = new LDAPFilterParser();
            $serializer = new LDAPFilterSerializer();

            $rule = $parser->parse('(&(age>=18)(|(country=US)(country=CA))(status=active))');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('(&(age>=18)(|(country=US)(country=CA))(status=active))');
        });

        test('serialize multiple AND conditions', function (): void {
            $parser = new LDAPFilterParser();
            $serializer = new LDAPFilterSerializer();

            $rule = $parser->parse('(&(age>=18)(country=US)(status=active)(verified=true))');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('(&(age>=18)(country=US)(status=active)(verified=true))');
        });

        test('serialize multiple OR conditions', function (): void {
            $parser = new LDAPFilterParser();
            $serializer = new LDAPFilterSerializer();

            $rule = $parser->parse('(|(role=admin)(role=manager)(role=owner))');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('(|(role=admin)(role=manager)(role=owner))');
        });

        test('serialize deeply nested filter', function (): void {
            $parser = new LDAPFilterParser();
            $serializer = new LDAPFilterSerializer();

            $rule = $parser->parse('(&(|(age>=21)(country=US))(!(&(status=banned)(verified=false))))');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('(&(|(age>=21)(country=US))(!(&(status=banned)(verified=false))))');
        });
    });

    describe('Round-Trip Tests', function (): void {
        test('round trip simple comparison', function (): void {
            $parser = new LDAPFilterParser();
            $serializer = new LDAPFilterSerializer();

            $original = '(age>=18)';
            $rule = $parser->parse($original);
            $serialized = $serializer->serialize($rule);
            $reparsed = $parser->parse($serialized);

            expect($serialized)->toBe($original);
        });

        test('round trip AND operator', function (): void {
            $parser = new LDAPFilterParser();
            $serializer = new LDAPFilterSerializer();

            $original = '(&(age>=18)(country=US))';
            $rule = $parser->parse($original);
            $serialized = $serializer->serialize($rule);

            expect($serialized)->toBe($original);
        });

        test('round trip OR operator', function (): void {
            $parser = new LDAPFilterParser();
            $serializer = new LDAPFilterSerializer();

            $original = '(|(age>=21)(country=US))';
            $rule = $parser->parse($original);
            $serialized = $serializer->serialize($rule);

            expect($serialized)->toBe($original);
        });

        test('round trip NOT operator', function (): void {
            $parser = new LDAPFilterParser();
            $serializer = new LDAPFilterSerializer();

            $original = '(!(status=inactive))';
            $rule = $parser->parse($original);
            $serialized = $serializer->serialize($rule);

            expect($serialized)->toBe($original);
        });

        test('round trip presence check', function (): void {
            $parser = new LDAPFilterParser();
            $serializer = new LDAPFilterSerializer();

            $original = '(email=*)';
            $rule = $parser->parse($original);
            $serialized = $serializer->serialize($rule);

            expect($serialized)->toBe($original);
        });

        test('round trip wildcard pattern', function (): void {
            $parser = new LDAPFilterParser();
            $serializer = new LDAPFilterSerializer();

            $original = '(name=John*)';
            $rule = $parser->parse($original);
            $serialized = $serializer->serialize($rule);

            expect($serialized)->toBe($original);
        });

        test('round trip approximate match', function (): void {
            $parser = new LDAPFilterParser();
            $serializer = new LDAPFilterSerializer();

            $original = '(name~=john)';
            $rule = $parser->parse($original);
            $serialized = $serializer->serialize($rule);

            expect($serialized)->toBe($original);
        });

        test('round trip complex nested expression', function (): void {
            $parser = new LDAPFilterParser();
            $serializer = new LDAPFilterSerializer();

            $original = '(&(age>=18)(|(country=US)(country=CA))(status=active))';
            $rule = $parser->parse($original);
            $serialized = $serializer->serialize($rule);

            expect($serialized)->toBe($original);
        });

        test('round trip with multiple wildcards', function (): void {
            $parser = new LDAPFilterParser();
            $serializer = new LDAPFilterSerializer();

            $original = '(name=*o*n*)';
            $rule = $parser->parse($original);
            $serialized = $serializer->serialize($rule);

            expect($serialized)->toBe($original);
        });
    });

    describe('Edge Cases', function (): void {
        test('serialize with spaces in value', function (): void {
            $parser = new LDAPFilterParser();
            $serializer = new LDAPFilterSerializer();

            $rule = $parser->parse('(name=John Doe)');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('(name=John Doe)');
        });

        test('serialize with special characters in value', function (): void {
            $parser = new LDAPFilterParser();
            $serializer = new LDAPFilterSerializer();

            $rule = $parser->parse('(email=user@example.com)');
            $result = $serializer->serialize($rule);

            expect($result)->toBe('(email=user@example.com)');
        });
    });
});
