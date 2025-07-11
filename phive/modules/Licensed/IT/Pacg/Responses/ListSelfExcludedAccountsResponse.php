<?php
namespace IT\Pacg\Responses;

use IT\Pacg\Types\ResponseAccountListType;

/**
 * Class ListSelfExcludedAccountsResponse
 * @package IT\Pacg\Responses
 */
class ListSelfExcludedAccountsResponse extends PacgResponse
{
    /**
     * @var array
     */
    public $self_excluded_accounts;

    /**
     * @param $response_array
     * @throws \Exception
     */
    public function fillableResponse($response_array)
    {
        $this->self_excluded_accounts = (new ResponseAccountListType())->fill($response_array['responseElements']);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $values = [
            'self_excluded_accounts' => $this->self_excluded_accounts->toArray()
        ];

        return parent::toArray($values);
    }
}