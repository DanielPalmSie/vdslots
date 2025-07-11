<?php
namespace IT\Pacg\Requests;

use IT\Pacg\Responses\QueryAccountPseudonymResponse;

/**
 * Class QueryAccountPseudonymRequest
 * @package IT\Pacg\Requests
 */
class QueryAccountPseudonymRequest extends PacgRequest
{
    protected $key = 'interrogazionePseudonimoContoIn';
    protected $message_name = 'interrogazionePseudonimoConto';

    /**
     * @inheritDoc
     */
    public function response(): string
    {
        return QueryAccountPseudonymResponse::class;
    }
}