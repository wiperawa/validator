<?php

declare(strict_types=1);

namespace Yiisoft\Validator\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\DataSetInterface;
use Yiisoft\Validator\Result;
use Yiisoft\Validator\ValidatorFactory;

class ValidatorFactoryTest extends TestCase
{
    public function testCreate()
    {
        $validation = new ValidatorFactory();

        $attribute = 'test';
        $errorMessage = 'error message';

        $validator = $validation->create(
            [
                $attribute => [
                    static function () use ($errorMessage) {
                        $result = new Result();
                        $result->addError($errorMessage);
                        return $result;
                    }
                ]
            ]
        );

        $result = $validator->validate($this->createDataSet([$attribute => '']));

        $this->assertSame($errorMessage, $result->getResult($attribute)->getErrors()[0]);
    }

    public function testCreateWithTranslator()
    {
        $translatableMessage = 'test message';
        $validation = new ValidatorFactory($this->createTranslatorMock($translatableMessage));

        $attribute = 'test';
        $validator = $validation->create(
            [
                $attribute => [
                    static function () {
                        $result = new Result();
                        $result->addError('error');
                        return $result;
                    }
                ]
            ]
        );

        $result = $validator->validate($this->createDataSet([$attribute => '']));

        $this->assertSame($translatableMessage, $result->getResult($attribute)->getErrors()[0]);
    }


    public function testCreateWithInvalidRule()
    {
        $validation = new ValidatorFactory();

        $this->expectException(\InvalidArgumentException::class);

        $attribute = 'test';
        $validation->create(
            [
                $attribute => [
                    'invalid rule'
                ]
            ]
        );
    }

    private function createTranslatorMock(string $returnMessage = null): TranslatorInterface
    {
        $translator = $this->createMock(TranslatorInterface::class);

        if ($returnMessage) {
            $translator
                ->method('translate')
                ->willReturn($returnMessage);
        }

        return $translator;
    }

    private function createDataSet(array $attributes): DataSetInterface
    {
        return new class($attributes) implements DataSetInterface {
            private array $attributes;

            public function __construct(array $attributes)
            {
                $this->attributes = $attributes;
            }

            public function getAttributeValue(string $attribute)
            {
                return $this->hasAttribute($attribute) ? $this->attributes[$attribute] : null;
            }

            public function hasAttribute(string $attribute): bool
            {
                return array_key_exists($attribute, $this->attributes);
            }
        };
    }
}
