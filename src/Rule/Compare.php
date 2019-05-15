<?php

namespace Yiisoft\Validator\Rule;

use Yiisoft\Validator\DataSet;
use Yiisoft\Validator\Result;
use Yiisoft\Validator\Rule;

/**
 * CompareValidator compares the specified attribute value with another value.
 *
 * The value being compared with can be another attribute value
 * (specified via [[compareAttribute]]) or a constant (specified via
 * [[compareValue]]. When both are specified, the latter takes
 * precedence. If neither is specified, the attribute will be compared
 * with another attribute whose name is by appending "_repeat" to the source
 * attribute name.
 *
 * CompareValidator supports different comparison operators, specified
 * via the [[operator]] property.
 *
 * The default comparison function is based on string values, which means the values
 * are compared byte by byte. When comparing numbers, make sure to set the [[$type]]
 * to [[TYPE_NUMBER]] to enable numeric comparison.
 */
class Compare extends Rule
{
    /**
     * Constant for specifying the comparison [[type]] by numeric values.
     * @since 2.0.11
     * @see type
     */
    const TYPE_STRING = 'string';
    /**
     * Constant for specifying the comparison [[type]] by numeric values.
     * @since 2.0.11
     * @see type
     */
    const TYPE_NUMBER = 'number';

    /**
     * @var string the name of the attribute to be compared with. When both this property
     * and [[compareValue]] are set, the latter takes precedence. If neither is set,
     * it assumes the comparison is against another attribute whose name is formed by
     * appending '_repeat' to the attribute being validated. For example, if 'password' is
     * being validated, then the attribute to be compared would be 'password_repeat'.
     * @see compareValue
     */
    public $compareAttribute;
    /**
     * @var mixed the constant value to be compared with. When both this property
     * and [[compareAttribute]] are set, this property takes precedence.
     * @see compareAttribute
     */
    private $compareValue;
    /**
     * @var string the type of the values being compared. The follow types are supported:
     *
     * - [[TYPE_STRING|string]]: the values are being compared as strings. No conversion will be done before comparison.
     * - [[TYPE_NUMBER|number]]: the values are being compared as numbers. String values will be converted into numbers before comparison.
     */
    private $type = self::TYPE_STRING;
    /**
     * @var string the operator for comparison. The following operators are supported:
     *
     * - `==`: check if two values are equal. The comparison is done is non-strict mode.
     * - `===`: check if two values are equal. The comparison is done is strict mode.
     * - `!=`: check if two values are NOT equal. The comparison is done is non-strict mode.
     * - `!==`: check if two values are NOT equal. The comparison is done is strict mode.
     * - `>`: check if value being validated is greater than the value being compared with.
     * - `>=`: check if value being validated is greater than or equal to the value being compared with.
     * - `<`: check if value being validated is less than the value being compared with.
     * - `<=`: check if value being validated is less than or equal to the value being compared with.
     *
     * When you want to compare numbers, make sure to also set [[type]] to `number`.
     */
    private $operator = '==';
    /**
     * @var string the user-defined error message. It may contain the following placeholders which
     * will be replaced accordingly by the validator:
     *
     * - `{attribute}`: the label of the attribute being validated
     * - `{value}`: the value of the attribute being validated
     * - `{compareValue}`: the value or the attribute label to be compared with
     * - `{compareAttribute}`: the label of the attribute to be compared with
     * - `{compareValueOrAttribute}`: the value or the attribute label to be compared with
     */
    private $message;

    public function __construct()
    {
        if ($this->message === null) {
            switch ($this->operator) {
                case '==':
                    $this->message = Yii::t('yii', '{attribute} must be equal to "{compareValueOrAttribute}".');
                    break;
                case '===':
                    $this->message = Yii::t('yii', '{attribute} must be equal to "{compareValueOrAttribute}".');
                    break;
                case '!=':
                    $this->message = Yii::t('yii', '{attribute} must not be equal to "{compareValueOrAttribute}".');
                    break;
                case '!==':
                    $this->message = Yii::t('yii', '{attribute} must not be equal to "{compareValueOrAttribute}".');
                    break;
                case '>':
                    $this->message = Yii::t('yii', '{attribute} must be greater than "{compareValueOrAttribute}".');
                    break;
                case '>=':
                    $this->message = Yii::t('yii', '{attribute} must be greater than or equal to "{compareValueOrAttribute}".');
                    break;
                case '<':
                    $this->message = Yii::t('yii', '{attribute} must be less than "{compareValueOrAttribute}".');
                    break;
                case '<=':
                    $this->message = Yii::t('yii', '{attribute} must be less than or equal to "{compareValueOrAttribute}".');
                    break;
                default:
                    throw new \RuntimeException("Unknown operator: {$this->operator}");
            }
        }
    }

    public function withValue($value): self
    {
        $this->compareValue = $value;
        return $this;
    }

    public function withAttribute(string $attribute): self
    {
        $this->compareAttribute = $attribute;
        return $this;
    }

    public function operator(string $operator): self
    {
        $this->operator = $operator;
        return $this;
    }

    public function message(string $message)
    {
        $this->message = $message;
    }

    public function validateAttribute(DataSet $data, string $attribute): Result
    {
        $result = new Result();

        $value = $data->getValue($attribute);

        if (is_array($value)) {
            $result->addError($this->formatMessage('{attribute} is invalid.', ['attribute' => $attribute]));
            return $result;
        }

        if ($this->compareValue !== null) {
            $compareLabel = $compareValue = $compareValueOrAttribute = $this->compareValue;
        } else {
            $compareAttribute = $this->compareAttribute ?? $attribute . '_repeat';
            $compareValue = $data->getValue($compareAttribute);

            // TODO: how should we deal with labels?
            //$compareLabel = $compareValueOrAttribute = $data->getAttributeLabel($compareAttribute);
        }

        if (!$this->compareValues($this->operator, $this->type, $value, $compareValue)) {
            $result->addError($this->formatMessage($this->message, [
                'compareAttribute' => $compareLabel,
                'compareValue' => $compareValue,
                'compareValueOrAttribute' => $compareValueOrAttribute,
            ]));
        }

        return $result;
    }

    public function validateValue($value): Result
    {
        $result = new Result();

        if ($this->compareValue === null) {
            throw new \RuntimeException('CompareValidator::compareValue must be set.');
        }
        if (!$this->compareValues($this->operator, $this->type, $value, $this->compareValue)) {
            $result->addError($this->formatMessage($this->message, [
                'compareAttribute' => $this->compareValue,
                'compareValue' => $this->compareValue,
                'compareValueOrAttribute' => $this->compareValue,
            ]));


        }

        return $result;
    }

    /**
     * Compares two values with the specified operator.
     * @param string $operator the comparison operator
     * @param string $type the type of the values being compared
     * @param mixed $value the value being compared
     * @param mixed $compareValue another value being compared
     * @return bool whether the comparison using the specified operator is true.
     */
    protected function compareValues($operator, $type, $value, $compareValue)
    {
        if ($type === self::TYPE_NUMBER) {
            $value = (float)$value;
            $compareValue = (float)$compareValue;
        } else {
            $value = (string)$value;
            $compareValue = (string)$compareValue;
        }
        switch ($operator) {
            case '==':
                return $value == $compareValue;
            case '===':
                return $value === $compareValue;
            case '!=':
                return $value != $compareValue;
            case '!==':
                return $value !== $compareValue;
            case '>':
                return $value > $compareValue;
            case '>=':
                return $value >= $compareValue;
            case '<':
                return $value < $compareValue;
            case '<=':
                return $value <= $compareValue;
            default:
                return false;
        }
    }
}