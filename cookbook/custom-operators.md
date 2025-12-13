# Custom Operators

Extend Ruler with custom operators to implement domain-specific logic:

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

Then you can use them with RuleBuilder like this:

```php
$rb->registerOperatorNamespace('My\\Ruler\\Operators');
$rb->create(
    $rb['a']->aLotGreaterThan(10);
);
```
