<?php

declare(strict_types=1);

namespace Videoslots\RgLimits;

final class RgLimitsValidator
{
    /**
     * @var \Videoslots\RgLimits\RgLimitsService
     */
    private RgLimitsService $rgLimitsService;

    /**
     * @var \Videoslots\RgLimits\Validators\RgLimitsValidatorInterface[]
     */
    private array $validators;

    /**
     * @param \Videoslots\RgLimits\RgLimitsService $rgLimitsService
     * @param \Videoslots\RgLimits\Validators\RgLimitsValidatorInterface[] $validators
     */
    public function __construct(RgLimitsService $rgLimitsService, array $validators)
    {
        $this->rgLimitsService = $rgLimitsService;
        $this->validators = $validators;
    }

    /**
     * @return array
     */
    public function validateData(): array
    {
        foreach ($this->validators as $validator) {
            $error = $validator->validate($this->rgLimitsService);
            if (! empty($error)) {
                return $error;
            }
        }

        return [];
    }
}
