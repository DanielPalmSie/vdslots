<?php
namespace IT\Pacg\Responses;

use IT\Pacg\Types\ResponseAccountListType;

/**
 * Class ListAccountsWithoutSubRegistrationResponse
 * @package IT\Pacg\Responses
 */
class ListAccountsWithoutSubRegistrationResponse extends PacgResponse
{
    /**
     * @var array
     */
    public $accounts_without_subregistration;

    /**
     * @param $response_array
     */
    public function fillableResponse($response_array)
    {
        $this->accounts_without_subregistration = (new ResponseAccountListType())->fill($response_array['responseElements']);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $values = [
            'accounts_without_subregistration' => $this->accounts_without_subregistration->toArray()
        ];

        return parent::toArray($values);
    }
}