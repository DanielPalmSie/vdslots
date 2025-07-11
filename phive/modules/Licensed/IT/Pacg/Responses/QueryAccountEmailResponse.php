<?php
namespace IT\Pacg\Responses;

/**
 * Class QueryAccountEmailResponse
 * @package IT\Pacg\Responses
 */
class QueryAccountEmailResponse extends PacgResponse
{
    /**
     * @var string
     */
    public $email;

    /**
     * @param $response_array
     */
    public function fillableResponse($response_array)
    {
        $this->email = $response_array['responseElements']['postaElettronica'];
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $values = [
            'email' => $this->email
        ];

        return parent::toArray($values);
    }
}