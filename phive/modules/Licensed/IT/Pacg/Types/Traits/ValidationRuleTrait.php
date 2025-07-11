<?php
namespace IT\Pacg\Types\Traits;

/**
 * Trait ValidationRuleTrait
 * @package IT\Pacg\Types\Traits
 */
trait ValidationRuleTrait
{

    /**
     * @param $value
     * @return bool
     * @throws \Exception
     */
    public function isGenericString($value): bool
    {
        $this->minLength($value, 1);
        $this->maxLength($value, 100);
        return true;
    }


    /**
     * @param $value
     * @param int $length
     * @return bool
     * @throws \Exception
     */
    public function minLength($value, int $length): bool
    {
        if (strlen($value) <= $length) {
            throw new \Exception($this->getErrorDescription(2, ["%1" => $length]));
        }

        return true;
    }


    /**
     * @param $value
     * @param int $length
     * @return bool
     * @throws \Exception
     */
    public function maxLength($value, int $length): bool
    {
        if (strlen($value) >= $length) {
            throw new \Exception($this->getErrorDescription(3, ["%1" => $length]));
        }

        return true;
    }

    /**
     * @param $value
     * @param int $length
     * @return bool
     * @throws \Exception
     */
    public function exactLength($value, int $length): bool
    {
        if (strlen($value) !== $length) {
            throw new \Exception($this->getErrorDescription(4, ["%1" => $length]));
        }

        return true;
    }

    /**
     * @param $value
     * @return bool
     * @throws \Exception
     */
    public function isInteger($value): bool
    {
        if (!is_numeric($value)) {
            throw new \Exception($this->getErrorDescription(5));
        }

        return true;
    }

    /**
     * @param $value
     * @return bool
     * @throws \Exception
     */
    public function isEmail($value): bool
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new Exception($this->getErrorDescription(8));
        }

        $this->minLength($value, 1);
        $this->maxLength($value, 100);

        return true;
    }

    /**
     * @param $value
     * @return bool
     * @throws \Exception
     */
    public function isGenderValid($value)
    {
        $allowedValues = ["F", "M"];

        if (!in_array($value, $allowedValues)) {
            throw new \Exception($this->getErrorDescription(7));
        }

        return true;
    }

}