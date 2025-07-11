<?php
namespace IT\Pacg\Requests;

use IT\Pacg\Responses\QuerySelfExcludedSubjectHistoryResponse;

/**
 * Class QuerySelfExcludedSubjectHistoryRequest
 * @package IT\Pacg\Requests
 */
class QuerySelfExcludedSubjectHistoryRequest extends PacgRequest
{
    protected $key = 'interrogazioneStoriaSoggettoAutoesclusoIn';
    protected $message_name = 'interrogazioneStoriaSoggettoAutoescluso';
    protected $set_account_information = false;

    /**
     * @inheritDoc
     */
    public function response(): string
    {
        return QuerySelfExcludedSubjectHistoryResponse::class;
    }
}