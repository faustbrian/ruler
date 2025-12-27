---
title: Operators
description: Complete reference for Ruler's 50+ comparison, mathematical, set, and logical operators.
---

Ruler provides over 50 operators for building expressive rule conditions.

## Comparison Operators

Define variables for rule evaluation:

```php
$a = $rb['a'];
$b = $rb['b'];
```

### Equality

```php
$a->equalTo($b);                          // true if $a == $b
$a->notEqualTo($b);                       // true if $a != $b
$a->sameAs($b);                           // true if $a === $b
$a->notSameAs($b);                        // true if $a !== $b
```

### Numeric Comparison

```php
$a->greaterThan($b);                      // true if $a > $b
$a->greaterThanOrEqualTo($b);             // true if $a >= $b
$a->lessThan($b);                         // true if $a < $b
$a->lessThanOrEqualTo($b);                // true if $a <= $b
```

### String Comparison

```php
$a->stringContains($b);                   // true if strpos($b, $a) !== false
$a->stringDoesNotContain($b);             // true if strpos($b, $a) === false
$a->stringContainsInsensitive($b);        // true if stripos($b, $a) !== false
$a->stringDoesNotContainInsensitive($b);  // true if stripos($b, $a) === false
$a->startsWith($b);                       // true if strpos($b, $a) === 0
$a->startsWithInsensitive($b);            // true if stripos($b, $a) === 0
$a->endsWith($b);                         // true if ends with substring
$a->endsWithInsensitive($b);              // case-insensitive ends with
```

## Mathematical Operators

Mathematical operators return values rather than boolean results, so they must be combined with comparison operators:

```php
$rb['price']
    ->add($rb['shipping'])
    ->greaterThanOrEqualTo(50);
```

### Arithmetic

```php
$c = $rb['c'];
$d = $rb['d'];

$c->add($d);          // $c + $d
$c->subtract($d);     // $c - $d
$c->multiply($d);     // $c * $d
$c->divide($d);       // $c / $d
$c->modulo($d);       // $c % $d
$c->exponentiate($d); // $c ** $d
```

### Unary Math

```php
$c->negate();         // -$c
$c->ceil();           // ceil($c)
$c->floor();          // floor($c)
```

## Set Operators

For working with arrays:

```php
$e = $rb['e'];  // Array
$f = $rb['f'];  // Array
```

### Set Manipulation

```php
$e->union($f);               // Elements in either set
$e->intersect($f);           // Elements in both sets
$e->complement($f);          // Elements in $e but not $f
$e->symmetricDifference($f); // Elements in one set but not both
$e->min();                   // Minimum value
$e->max();                   // Maximum value
```

### Set Propositions

```php
$e->containsSubset($f);       // true if $f ⊆ $e
$e->doesNotContainSubset($f); // true if $f ⊄ $e
$e->setContains($a);          // true if $a ∈ $e
$e->setDoesNotContain($a);    // true if $a ∉ $e
```

## Logical Operators

Combine propositions:

```php
$rb->logicalAnd($propA, $propB);    // True if both are true
$rb->logicalOr($propA, $propB);     // True if either is true
$rb->logicalXor($propA, $propB);    // True if exactly one is true
$rb->logicalNot($propA);            // Inverts the result
```

## Custom Operators

Extend Ruler with domain-specific operators:

```php
namespace My\Ruler\Operators;

use Ruler\Context;
use Ruler\Operator\VariableOperator;
use Ruler\Proposition;
use Ruler\Value;

class ALotGreaterThan extends VariableOperator implements Proposition
{
    public function evaluate(Context $context): bool
    {
        list($left, $right) = $this->getOperands();
        $value = $right->prepareValue($context)->getValue() * 10;

        return $left->prepareValue($context)->greaterThan(new Value($value));
    }

    protected function getOperandCardinality()
    {
        return static::BINARY;
    }
}
```

Register and use:

```php
$rb->registerOperatorNamespace('My\\Ruler\\Operators');
$rb->create($rb['a']->aLotGreaterThan(10));
```
