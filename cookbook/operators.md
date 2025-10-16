# Operators

## Comparison Operators

Define variables for rule evaluation:

```php
$a = $rb['a'];
$b = $rb['b'];
```

Build propositions using comparison operators:

```php
$a->greaterThan($b);                      // true if $a > $b
$a->greaterThanOrEqualTo($b);             // true if $a >= $b
$a->lessThan($b);                         // true if $a < $b
$a->lessThanOrEqualTo($b);                // true if $a <= $b
$a->equalTo($b);                          // true if $a == $b
$a->notEqualTo($b);                       // true if $a != $b
$a->stringContains($b);                   // true if strpos($b, $a) !== false
$a->stringDoesNotContain($b);             // true if strpos($b, $a) === false
$a->stringContainsInsensitive($b);        // true if stripos($b, $a) !== false
$a->stringDoesNotContainInsensitive($b);  // true if stripos($b, $a) === false
$a->startsWith($b);                       // true if strpos($b, $a) === 0
$a->startsWithInsensitive($b);            // true if stripos($b, $a) === 0
$a->endsWith($b);                         // true if strpos($b, $a) === len($a) - len($b)
$a->endsWithInsensitive($b);              // true if stripos($b, $a) === len($a) - len($b)
$a->sameAs($b);                           // true if $a === $b
$a->notSameAs($b);                        // true if $a !== $b
```

## Mathematical Operators

```php
$c = $rb['c'];
$d = $rb['d'];
```

Mathematical operators return values rather than boolean results, so they must be combined with comparison operators:

```php
$rb['price']
  ->add($rb['shipping'])
  ->greaterThanOrEqualTo(50)
```

Of course, there are more.

```php
$c->add($d);          // $c + $d
$c->subtract($d);     // $c - $d
$c->multiply($d);     // $c * $d
$c->divide($d);       // $c / $d
$c->modulo($d);       // $c % $d
$c->exponentiate($d); // $c ** $d
$c->negate();         // -$c
$c->ceil();           // ceil($c)
$c->floor();          // floor($c)
```

## Set Operators

```php
$e = $rb['e']; // These should both be arrays
$f = $rb['f'];
```

Manipulate sets with set operators:

```php
$e->union($f);
$e->intersect($f);
$e->complement($f);
$e->symmetricDifference($f);
$e->min();
$e->max();
```

And use set Propositions to include them in Rules:

```php
$e->containsSubset($f);
$e->doesNotContainSubset($f);
$e->setContains($a);
$e->setDoesNotContain($a);
```
