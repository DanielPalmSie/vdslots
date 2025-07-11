<?php
namespace IT\Pacg\Requests;

use IT\Pacg\Responses\SummaryOfServiceOperationsResponse;

/**
 * Class SummaryOfServiceOperationsRequest
 * @package IT\Pacg\Requests
 */
class SummaryOfServiceOperationsRequest extends PacgRequest
{
    protected $key = 'riepilogoOperazioniServizioIn';
    protected $message_name = 'riepilogoOperazioniServizio';

    /**
     * @inheritDoc
     */
    public function response(): string
    {
        return SummaryOfServiceOperationsResponse::class;
    }
}