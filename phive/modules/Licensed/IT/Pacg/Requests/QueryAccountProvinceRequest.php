<?php
namespace IT\Pacg\Requests;

use IT\Pacg\Responses\QueryAccountProvinceResponse;

/**
 * Class QueryAccountProvinceRequest
 * @package IT\Pacg\Requests
 */
class QueryAccountProvinceRequest extends PacgRequest
{
    protected $key = 'interrogazioneProvinciaResidenzaContoIn';
    protected $message_name = 'interrogazioneProvinciaResidenzaConto';

    /**
     * @inheritDoc
     */
    public function response(): string
    {
        return QueryAccountProvinceResponse::class;
    }
}

