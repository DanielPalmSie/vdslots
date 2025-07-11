<?php
namespace IT\Services;

use CodiceFiscale\Calculator;
use CodiceFiscale\InverseCalculator;
use CodiceFiscale\Subject;
use IT\Pacg\Types\TaxCodeType;

/**
 * Class TaxCodeService
 * @package IT\Services
 */
class TaxCodeService
{
    const ERROR_CODE = 400;
    const SUCCESS_CODE = 200;
    /**
     * @var array
     */
    private $data;

    /**
     * @var string
     */
    private $tax_code;

    /**
     * @param TaxCodeType $tax_code_type
     * @return Subject
     * @throws \Exception
     */
    private function getSubject(TaxCodeType $tax_code_type): Subject
    {
        try {
            $tax_code_type_value = $tax_code_type->toArray();
            if (isset($tax_code_type_value['registryCode'])) {
                $tax_code_type_value['belfioreCode'] = $tax_code_type_value['registryCode'];
            }

            return new Subject($tax_code_type_value);
        } catch (\Exception $exception) {
            throw $exception;
        }
    }

    /**
     * @return ErrorFormatterService
     */
    private function getErrorFormatter(): ErrorFormatterService
    {
        return new ErrorFormatterService();
    }

    /**
     * @param array $data
     * @param bool $throw_exception
     * @return TaxCodeType
     * @throws \Exception
     */
    private function getTaxCodeType(array $data, bool $throw_exception = true): TaxCodeType
    {
        $this->data = $data;
        $tax_code = new TaxCodeType();
        $tax_code->fill($data);
        $tax_code->validate($data);

        if (! empty($tax_code->errors) && $throw_exception) {
            throw new \Exception($this->getErrorFormatter()->format($tax_code->errors));
        }

        return $tax_code;
    }

    /**
     * @param Subject $subject
     * @return string
     */
    private function calculate(Subject $subject): string
    {
        $calculator = new Calculator($subject);
        $this->tax_code = $calculator->calculate();
        return $this->tax_code;
    }

    /**
     * @param string $tax_code
     * @return Subject
     * @throws \Exception
     */
    private function extractDataFromTaxCode(string $tax_code): Subject
    {
        $inverseCalculator = new InverseCalculator($tax_code);
        return $inverseCalculator->getSubject();
    }

    /**
     * @param Subject $subject
     * @return TaxCodeType
     * @throws \Exception
     */
    private function mountResponse(Subject $subject): TaxCodeType
    {
        $data = [
            "name" => $subject->getName() ?? 'XXX',
            "surname" => $subject->getSurname() ?? 'XXX',
            "birthDate" => $subject->getBirthDate()->format('Y-m-d'),
            "gender" => $subject->getGender(),
            "registryCode" => $subject->getBelfioreCode()
        ];

        return $this->getTaxCodeType($data);
    }

    /**
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function generate(array $data): array
    {
        $tax_code_type = $this->getTaxCodeType($data, false);

        if ($tax_code_type->errors) {
            return [
                'code' => self::ERROR_CODE,
                'error' => $tax_code_type->errors
            ];
        }

        try {
            $subject = $this->getSubject($tax_code_type);
            $tax_code = $this->calculate($subject);
            return [
                'code' => self::SUCCESS_CODE,
                'tax_code' => $tax_code
            ];
        } catch (\Exception $exception) {
            return [
                'code' => self::ERROR_CODE,
                'error' => $exception->getMessage()
            ];
        }
    }

    /**
     * @return ResidenceService
     */
    private function getResidence(): ResidenceService
    {
        return new ResidenceService();
    }

    /**
     * @param string $tax_code
     * @return array
     * @throws \Exception
     */
    public function extract(string $tax_code): array
    {
        $this->tax_code = $tax_code;
        $subject = $this->extractDataFromTaxCode($tax_code);
        $tax_code_type = $this->mountResponse($subject);

        if ($tax_code_type->errors) {
            return [
                'code' => self::ERROR_CODE,
                'tax_code' => $tax_code_type->errors,
                'error_message' => t('invalid_tax_code')
            ];
        }

        $tax_code_data = $tax_code_type->toArray();
        if ($tax_code_data['registryCode'] ?? false) {
            $tax_code_data += $this->getResidence()->getMunicipalityDetail($tax_code_data['registryCode']);
        }

        return [
            'code' => self::SUCCESS_CODE,
            'tax_code' => $tax_code_data
        ];
    }
}