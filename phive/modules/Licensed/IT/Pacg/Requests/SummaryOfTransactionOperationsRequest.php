<?php
namespace IT\Pacg\Requests;

use IT\Pacg\Responses\SummaryOfTransactionOperationsResponse;

/**
 * Class SummaryOfTransactionOperationsRequest
 * @package IT\Pacg\Requests
 */
class SummaryOfTransactionOperationsRequest extends PacgRequest
{
    protected $key = 'riepilogoOperazioniMovimentazioneIn';
    protected $message_name = 'riepilogoOperazioniMovimentazione';

    /**
     * @inheritDoc
     */
    public function response(): string
    {
        return SummaryOfTransactionOperationsResponse::class;
    }
}