<?php
namespace IT\Pacg\Responses;

/**
 * Class QueryAccountPseudonymResponse
 * @package IT\Pacg\Responses
 */
class QueryAccountPseudonymResponse extends PacgResponse
{
    /**
     * @var string
     */
    public $pseudonym;

    /**
     * @param $response_array
     */
    public function fillableResponse($response_array)
    {
        $this->pseudonym = $response_array['responseElements']['pseudonimo'];
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $values = [
            'pseudonym' => $this->pseudonym
        ];

        return parent::toArray($values);
    }
}