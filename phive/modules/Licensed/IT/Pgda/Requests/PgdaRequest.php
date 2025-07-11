<?php
namespace IT\Pgda\Requests;

use IT\Abstractions\AbstractRequest;
use IT\Pgda\Responses\PgdaResponse;

class PgdaRequest extends AbstractRequest
{
    const SIGNATURE = 'Firma';

    const ENDPOINT_MASK = 'ServletFactory%s_V213_%s';

    /**
     * @var string
     */
    protected $request_code = '';

    /**
     * @var string
     */
    protected $game_type = 'QF';

    /**
     * @inheritDoc
     */
    public function responseName(): string
    {
        return PgdaResponse::class;
    }

    /**
     * @param array $settings
     * @return string
     */
    protected function getEndPoint(array $settings = [], $request_code): string
    {
        $end_point = sprintf(
            $settings['pgda']['endpoint_mask'] ?? static::ENDPOINT_MASK,
            $settings['pgda']['sign_message'] ? static::SIGNATURE : '',
            $settings['pgda']['game_type'] ?? $this->game_type
        );
        switch ($request_code) {
            case in_array($request_code, $settings['pgda']['messages_gaming']):{
                $base_url = $settings['pgda']['base_url_gaming'];
                break;
            }
            case in_array($request_code, $settings['pgda']['messages_archiving']):{
                $base_url = $settings['pgda']['base_url_archiving'];
                break;
            }
        }
        return rtrim($base_url , '/') . '/' .  $end_point;
    }

    /**
     * @inheritDoc
     */
    protected function setSetting(array $settings = [], $request_code = 0)
    {
        $this->client->setUrl($this->getEndPoint($settings, $request_code));
    }

    /**
     * @inheritDoc
     */
    protected function setCommonAttributes(array $data): array
    {
        $data['header']['Cod_tipo_messaggio'] = $this->request_code;
        return $data;
    }

    /**
     * Request package name
     * @return string
     */
    public function requestName(): string
    {
        return 'Pgda';
    }
}