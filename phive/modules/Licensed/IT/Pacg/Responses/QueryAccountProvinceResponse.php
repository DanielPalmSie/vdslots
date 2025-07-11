<?php
namespace IT\Pacg\Responses;

/**
 * Class QueryAccountProvinceResponse
 * @package IT\Pacg\Responses
 */
class QueryAccountProvinceResponse extends PacgResponse
{
    /**
     * @var string
     */
    public $province;

    /**
     * @param $response_array
     */
    public function fillableResponse($response_array)
    {
        $this->province = $response_array['responseElements']['provincia'];
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $values = [
            'province' => $this->province
        ];

        return parent::toArray($values);
    }
}