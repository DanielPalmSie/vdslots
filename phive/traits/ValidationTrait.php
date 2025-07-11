<?php

use Rakit\Validation\Validator;

trait ValidationTrait
{
    /**
     *
     * @var array
     */
    protected $rules    = [];

    /**
     *
     * @var array
     */
    public $errors   = [];

    /**
     *
     * @var Validator
     */
    private $validator  = null;

    /**
     * @param $validators
     * @throws \Rakit\Validation\RuleQuashException
     */
    private function includeRules($validators)
    {
        if ($validators ?? false) {
            foreach ($validators as $ruleName => $rule) {
                $this->validator->addValidator($ruleName, $rule);
            }
        }
    }

    /**
     * Set the validator
     * @param array $validators
     * @throws \Rakit\Validation\RuleQuashException
     */
    protected function setValidator(array $validators = [])
    {
        if ($this->validator ?? true) {
            $this->validator = new Validator();
            $this->includeRules($validators);
        }
    }

    /**
     * Validate the data
     *
     * @param array $data
     * @param array $validators
     * @throws \Rakit\Validation\RuleQuashException
     */
    public function validate(array $data, array $validators = [])
    {
        $this->setValidator($validators);
        $validation = $this->validator->validate($data, $this->rules);
        if($validation->fails()) {
            // Add the errors to the object
            $this->errors = $validation->errors()->toArray();
        }
    }

}