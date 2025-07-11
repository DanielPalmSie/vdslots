<?php
namespace IT\Pacg\Requests;

use IT\Pacg\Responses\QueryAccountStatusResponse;

/**
 * Class QueryAccountStatusRequest
 * @package IT\Pacg\Requests
 */
class QueryAccountStatusRequest extends PacgRequest
{
    protected $key = 'interrogazioneStatoContoIn';
    protected $message_name = 'interrogazioneStatoConto';

    /**
     * @inheritDoc
     */
    public function response(): string
    {
        return QueryAccountStatusResponse::class;
    }
}