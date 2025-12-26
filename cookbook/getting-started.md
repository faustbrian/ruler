# Getting Started with Ruler

Ruler provides an intuitive DSL through the RuleBuilder.

## Basic Example with RuleBuilder

```php
$rb = new RuleBuilder;
$rule = $rb->create(
    $rb->logicalAnd(
        $rb['minNumPeople']->lessThanOrEqualTo($rb['actualNumPeople']),
        $rb['maxNumPeople']->greaterThanOrEqualTo($rb['actualNumPeople'])
    ),
    function() {
        echo 'YAY!';
    }
);

$context = new Context([
    'minNumPeople' => 5,
    'maxNumPeople' => 25,
    'actualNumPeople' => fn() => 6,
]);

$rule->execute($context); // "Yay!"
```

## Without RuleBuilder

Alternatively, you can construct rules directly without RuleBuilder:

```php
$actualNumPeople = new Variable('actualNumPeople');
$rule = new Rule(
    new Operator\LogicalAnd([
        new Operator\LessThanOrEqualTo(new Variable('minNumPeople'), $actualNumPeople),
        new Operator\GreaterThanOrEqualTo(new Variable('maxNumPeople'), $actualNumPeople)
    ]),
    function() {
        echo 'YAY!';
    }
);

$context = new Context([
    'minNumPeople' => 5,
    'maxNumPeople' => 25,
    'actualNumPeople' => fn() => 6,
]);

$rule->execute($context); // "Yay!"
```
