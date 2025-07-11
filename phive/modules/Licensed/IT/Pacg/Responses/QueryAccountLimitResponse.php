<?php
namespace IT\Pacg\Responses;

use IT\Pacg\Types\ResponseLimitListType;

/**
 * Class QueryAccountLimitResponse
 * @package IT\Pacg\Responses
 */
class QueryAccountLimitResponse extends PacgResponse
{
    /**
     * @var array
     */
    public $limits;

    /**
     * @param $response_array
     */
    public function fillableResponse($response_array)
    {
        if($response_array['responseElements']['numeroLimiti'] == 1) {
            $this->limits = (new ResponseLimitListType())->fill(['limite' => [$response_array['responseElements']['limite']]]);
        } else {
            $this->limits = (new ResponseLimitListType())->fill(['limite' => $response_array['responseElements']['limite']]);
        }
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $values = [
            'limits' => $this->limits->toArray()
        ];

        return parent::toArray($values);
    }
}