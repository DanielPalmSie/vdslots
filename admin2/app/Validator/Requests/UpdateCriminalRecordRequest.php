<?php

namespace App\Validator\Requests;

use App\Validator\Rules\CriminalRecordStatus;

class UpdateCriminalRecordRequest extends BaseRequest
{
    /**
     * Properties automatically are set from request
     *
     * @var string|null
     */
    protected ?string $section;
    protected ?string $jurisdiction;
    protected ?string $status;

    public function rules(): array
    {
        \Valitron\Validator::addRule(
            'criminalRecordStatus',
            new CriminalRecordStatus($this->section, $this->jurisdiction),
            'Invalid Criminal Record {field}.'
        );

        return [
            'section' => ['required', ['in', ['AML', 'RG']]],
            'jurisdiction' => ['required'],
            'status' => ['required', 'criminalRecordStatus'],
        ];
    }
}
