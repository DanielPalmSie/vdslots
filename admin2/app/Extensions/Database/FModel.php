<?php
/**
 * Due to the impossibility to have a Laravel Service Provider, this class must be extended by a model instead
 * extending the Eloquent Model base class.
 *
 * Main functionalities:
 *  - Validate before inserting or updating a model instance.
 *  - Helper to get the table columns.
 *  - Added sharding support based on the user id @see sh()
 *
 * User: ricardo
 * Date: 21/07/16
 * Time: 14:00
 */

namespace App\Extensions\Database;

use App\Extensions\Database\Eloquent\Model;
use Valitron\Validator;
use Symfony\Component\HttpFoundation\Request;

class FModel extends Model
{
    const SCENARIO_DEFAULT = 'default';

    private $globally_guarded = ['token'];

    private $is_validated = true;

    private $errors = [];

    private $scenario = self::SCENARIO_DEFAULT;

    private $added_rules = [];

    /**
     * @param string $key
     * @return bool
     */
    public function isGuarded($key)
    {
        return parent::isGuarded($key) || in_array($key, $this->globally_guarded);
    }

    /**
     * Returns the validation rules for attributes.
     *
     * Validations are based on vlucas/valitron standalone validator package @see https://github.com/vlucas/valitron
     * to be able to know the rules format.
     *
     * Only allowed these scenarios:
     *  - default: rules applied to all events.
     *  - create: rules only on insert.
     *  - update: rules on updating.
     *
     * Example format:
     *
     *  [
     *      'default' => [
     *          'required' => [['attribute1'],['attribute2']]
     *      ],
     *      'update' => [
     *          'length' => [['attribute3, 2]]
     *      ],
     *      'create' => [
     *          'integer' => [['attribute4]]
     *      ]
     *  ];
     *
     *
     * @return array
     */
    protected function rules()
    {
        return [];
    }

    /**
     * Returns customized labels for attributes validation messages.
     *
     * Example format:
     *
     *  [
     *      'name' => 'Name',
     *      'email' => 'Email address'
     *  ]
     *
     * @return array
     */
    protected function labels()
    {
        return [];
    }

    /**
     *  Listeners added to the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function (FModel $model) {
            $model->setScenario('create');
            return $model->validate();
        });

        static::updating(function (FModel $model) {
            $model->setScenario('update');
            return $model->validate();
        });
    }

    /**
     * Retrieves and merge the rules related to the default and another scenarios
     *
     * @return array
     */
    private function getRules()
    {
        if (!in_array($this->scenario, [self::SCENARIO_DEFAULT, 'create', 'update'])) {
            throw new \InvalidArgumentException("Scenario not supported");
        }

        if ($this->scenario == self::SCENARIO_DEFAULT) {
            return $this->rules()[self::SCENARIO_DEFAULT];
        } else {
            $scenario_rules = $this->rules()[$this->scenario];
            if (count($scenario_rules) > 0) {
                foreach ($this->rules()[self::SCENARIO_DEFAULT] as $key => $value) {
                    if (isset($scenario_rules[$key])) {
                        $scenario_rules[$key] = array_merge($scenario_rules[$key], $value);
                    } else {
                        $scenario_rules[$key] = $value;
                    }
                }
                return $scenario_rules;
            } else {
                return $this->rules()[self::SCENARIO_DEFAULT];
            }

        }
    }

    /**
     * Add rules directly into the model without setting it up in the rules array.
     *
     * @param array $rule Only Valitron array format rules accepted @see rules method
     * @return self
     */
    public function addRules($rule): self
    {
        $this->added_rules[] = $rule;
        return $this;
    }

    /**
     * Returns the result of the validation.
     *
     * @return bool
     */
    public function validate()
    {
        if (!$this->is_validated || empty($this->getRules())) {
            return true;
        }

        try {
            $validator = new Validator($this->getAttributes());
            $validator->rules($this->getRules());
            $validator->labels($this->labels());

            if (!empty($this->added_rules)) {
                $validator->rules($this->added_rules);
            }

            if (!$validator->validate()) {
                $this->errors = $validator->errors();
                return false;
            }
        } catch (\Exception $e) {
            $this->errors = ['error' => [0 => 'Internal validation error.']];
            return false;
        }

        return true;
    }

    /**
     *  Deactivate the validation.
     * @return self
     */
    public function noValidate(): self
    {
        $this->is_validated = false;
        return $this;
    }

    /**
     * To manually set the scenario.
     *
     * @param string $scenario
     * @return self
     */
    public function setScenario($scenario): self
    {
        $this->scenario = $scenario;
        return $this;
    }

    /**
     * Retrieves the current scenario.
     *
     * @return string
     */
    public function getScenario()
    {
        return $this->scenario;
    }

    /**
     * Get the error message bag
     *
     * @param $errors
     * @return self
     */
    public function appendErrors($errors): self
    {
        $this->errors = array_merge($this->errors, $errors);
        return $this;
    }

    /**
     * Get the error message bag
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Get the first error in the message bag.
     *
     * @return array
     */
    public function getFirstError()
    {
        return reset($this->errors);
    }

    /**
     * Get the current error in the message bag.
     *
     * @return array
     */
    public function getCurrentError()
    {
        return current($this->errors);
    }
    /**
     * Get the last error in the message bag.
     *
     * @return array
     */
    public function getLastError()
    {
        return end($this->errors);
    }

    /**
     * Returns if the model has an error.
     *
     * @return bool
     */
    public function hasErrors()
    {
        return !empty($this->errors);
    }

    public function overrideErrors($errors): self
    {
        $this->errors = $errors;
        return $this;
    }

    public function getDistinct($column)
    {
        $distinct_data = $this->select($column)->orderBy($column, "ASC")->groupBy($column)->distinct()->whereRaw("LENGTH(?)>0", [$column])->get();
        $distinct = [];
        foreach ($distinct_data as $row) {
            $distinct[] = $row[$column];
        }

        return $distinct;
    }

    /**
     * @param Request $request
     * @param string $key
     * @return mixed
     */
    public static function findOrNewFromRequest($request, $key = 'id') {
        return empty($id = (int)$request->get($key, 0))
            ? new static()
            : self::query()->find($id);
    }
}
