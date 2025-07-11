<?php
namespace IT\Pacg\Requests;

use IT\Pacg\Responses\QuerySelfExcludedSubjectResponse;

/**
 * Class QuerySelfExcludedSubjectRequest
 * @package IT\Pacg\Requests
 */
class QuerySelfExcludedSubjectRequest extends PacgRequest
{
    protected $key = 'interrogazioneSoggettoAutoesclusoIn';
    protected $message_name = 'interrogazioneSoggettoAutoescluso';
    protected $set_account_information = false;

    /**
     * @inheritDoc
     */
    public function response(): string
    {
        return QuerySelfExcludedSubjectResponse::class;
    }
}