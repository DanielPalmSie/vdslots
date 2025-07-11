<?php
namespace IT\Pgda\Responses;

use IT\Traits\BinaryTrait;

class SessionReportedAnomaliesResponse extends PgdaResponse
{
    /**
     * | HEADER 42 * 2 | CODE 2 bytes | SESSION_TOTAL 2 bytes | BODY string
     * @var string
     */
    protected $format_success = 'H84header/ncode/nsession_total';

    /**
     * @var array
     */
    private $sub_formats = [
        'identifier' => 'A16session_id/nnumber_of_anomalies',
        'amount_name' => 'number_of_anomalies',
        'sub_format' => [
            'identifier' => 'ncode',
        ]
    ];

    /**
     * @inheritDoc
     */
    protected function extractResponse($response)
    {
        parent::extractResponse($response);
        if ($this->isSuccess()) {
            $this->extractOutCome(
                $response,
                $this->format_success,
                $this->sub_formats,
                'session_total'
            );
        }
    }

    /**
     * @param $response
     * @param string $format
     * @param array $sub_formats
     * @param string $amount_name
     * @return string
     */
    private function extractOutCome($response, string &$format, array $sub_formats, string $amount_name): string
    {
        for ($i = 0; $i < $this->response[$amount_name]; $i++) {
            $format .= '/'.$sub_formats['identifier'];
            if ($sub_formats['sub_format'] ?? false) {
                $this->response = BinaryTrait::deconvert($response, $format);
                return $this->extractOutCome($response, $format, $sub_formats['sub_format'], $sub_formats['amount_name']);
            }
        }
        $this->response = BinaryTrait::deconvert($response, $format);
        return $format;
    }
}