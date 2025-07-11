<?php
namespace IT\Pacg\Responses;

use IT\Pacg\Types\ResponseAccountListType;

/**
 * Class ListDormantAccountsResponse
 * @package IT\Pacg\Responses
 */
class ListDormantAccountsResponse extends PacgResponse
{
    /**
     * @var array
     */
    public $dormant_accounts;

    /**
     * @param $response_array
     */
    public function fillableResponse($response_array)
    {
        $this->dormant_accounts = (new ResponseAccountListType())->fill($response_array['responseElements']);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $values = [
            'dormant_accounts' => $this->dormant_accounts->toArray()
        ];

        return parent::toArray($values);
    }
}