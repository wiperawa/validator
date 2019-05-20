<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://avatars0.githubusercontent.com/u/993323" height="100px">
    </a>
    <h1 align="center">Yii Validator</h1>
    <br>
</p>

The package provides data validation capabilities.

[![Latest Stable Version](https://poser.pugx.org/yiisoft/validator/v/stable.png)](https://packagist.org/packages/yiisoft/validator)
[![Total Downloads](https://poser.pugx.org/yiisoft/validator/downloads.png)](https://packagist.org/packages/yiisoft/validator)
[![Build Status](https://travis-ci.org/yiisoft/validator.svg?branch=master)](https://travis-ci.org/yiisoft/validator)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/yiisoft/validator/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/validator/?branch=master)

## Features

- Could be used with any object. 

## General usage

Library could be used in two ways: validating a single value and validating a set of data.

### Validating a single value

```php
<?php
$rules = new Rules([
    new Required(),
    (new Number())->min(10),
    function ($value): Result {
        $result = new Result();
        if ($value !== 42) {
            $result->addError('Value should be 42!');
        }
        return $result;
    }
]);

$result = $rules->validate(41);
if ($result->isValid() === false) {
    foreach ($result->getErrors() as $error) {
        // ...
    }
}
```

### Validating a set of data

```php
<?php
class MoneyTransfer implements \Yiisoft\Validator\DataSet
{
    private $amount;
    
    public function __construct($amount) {
        $this->amount = $amount;
    }
    
    public function getValue(string $key){
        if (!isset($this->$key)) {
            throw new \InvalidArgumentException("There is no \"$key\" in MoneyTransfer.");
        }
        
        return $this->$key;
    }
}

$moneyTransfer = new MoneyTransfer();

$validator = new Validator([    
    'amount' => [
        (new Number())->integer(),
        (new Number())->integer()->max(100),
        function ($value): Result {
            $result = new Result();
            if ($value === 13) {
                $result->addError('Value should not be 13!');
            }
            return $result;
        }
    ],
]);

$results = $validator->validate($moneyTransfer);
foreach ($results as $attribute => $result) {
    if ($result->isValid() === false) {
        foreach ($result->getErrors() as $error) {
            // ...
        }
    }
}
```

### Creating your own valdation rules

In order to create your own valdation rule you should extend `Rule` class:

```php
<?php
namespace MyVendor\Rules;

use Yiisoft\Validator\Result;
use Yiisoft\Validator\Rule;

class Pi extends Rule
{
    public function validateValue($value): Result
    {
        $result = new Result();
        if ($value != M_PI) {
            $result->addError('Value is not PI!');
        }
        return $result;
    }
}
```

 