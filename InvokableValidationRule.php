<?php

namespace WPWhales\Validation;

use WPWhales\Contracts\Validation\DataAwareRule;
use WPWhales\Contracts\Validation\ImplicitRule;
use WPWhales\Contracts\Validation\InvokableRule;
use WPWhales\Contracts\Validation\Rule;
use WPWhales\Contracts\Validation\ValidationRule;
use WPWhales\Contracts\Validation\ValidatorAwareRule;
use WPWhales\Translation\CreatesPotentiallyTranslatedStrings;

class InvokableValidationRule implements Rule, ValidatorAwareRule
{
    use CreatesPotentiallyTranslatedStrings;

    /**
     * The invokable that validates the attribute.
     *
     * @var \WPWhales\Contracts\Validation\ValidationRule|\WPWhales\Contracts\Validation\InvokableRule
     */
    protected $invokable;

    /**
     * Indicates if the validation invokable failed.
     *
     * @var bool
     */
    protected $failed = false;

    /**
     * The validation error messages.
     *
     * @var array
     */
    protected $messages = [];

    /**
     * The current validator.
     *
     * @var \WPWhales\Validation\Validator
     */
    protected $validator;

    /**
     * The data under validation.
     *
     * @var array
     */
    protected $data = [];

    /**
     * Create a new explicit Invokable validation rule.
     *
     * @param  \WPWhales\Contracts\Validation\ValidationRule|\WPWhales\Contracts\Validation\InvokableRule  $invokable
     * @return void
     */
    protected function __construct(ValidationRule|InvokableRule $invokable)
    {
        $this->invokable = $invokable;
    }

    /**
     * Create a new implicit or explicit Invokable validation rule.
     *
     * @param  \WPWhales\Contracts\Validation\ValidationRule|\WPWhales\Contracts\Validation\InvokableRule  $invokable
     * @return \WPWhales\Contracts\Validation\Rule
     */
    public static function make($invokable)
    {
        if ($invokable->implicit ?? false) {
            return new class($invokable) extends InvokableValidationRule implements ImplicitRule {
            };
        }

        return new InvokableValidationRule($invokable);
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $this->failed = false;

        if ($this->invokable instanceof DataAwareRule) {
            $this->invokable->setData($this->validator->getData());
        }

        if ($this->invokable instanceof ValidatorAwareRule) {
            $this->invokable->setValidator($this->validator);
        }

        $method = $this->invokable instanceof ValidationRule
                        ? 'validate'
                        : '__invoke';

        $this->invokable->{$method}($attribute, $value, function ($attribute, $message = null) {
            $this->failed = true;

            return $this->pendingPotentiallyTranslatedString($attribute, $message);
        });

        return ! $this->failed;
    }

    /**
     * Get the underlying invokable rule.
     *
     * @return \WPWhales\Contracts\Validation\ValidationRule|\WPWhales\Contracts\Validation\InvokableRule
     */
    public function invokable()
    {
        return $this->invokable;
    }

    /**
     * Get the validation error messages.
     *
     * @return array
     */
    public function message()
    {
        return $this->messages;
    }

    /**
     * Set the data under validation.
     *
     * @param  array  $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Set the current validator.
     *
     * @param  \WPWhales\Validation\Validator  $validator
     * @return $this
     */
    public function setValidator($validator)
    {
        $this->validator = $validator;

        return $this;
    }
}
