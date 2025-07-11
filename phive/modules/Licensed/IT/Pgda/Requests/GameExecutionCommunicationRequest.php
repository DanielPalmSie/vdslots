<?php
namespace IT\Pgda\Requests;

/**
 * Class GameExecutionCommunicationRequest
 * @package IT\Pgda\Requests
 */
class GameExecutionCommunicationRequest extends PgdaRequest
{
    const ENDPOINT_MASK = 'ServletFactory%s_V213_%s';
    /**
     * @var string
     */
    protected $game_type = '580';

    /**
     * @var string
     */
    protected $request_code = '580';

    protected function getEndPoint(array $settings = [], $request_code = 580): string
    {
        return parent::getEndPoint($settings, $this->request_code);
    }
}
