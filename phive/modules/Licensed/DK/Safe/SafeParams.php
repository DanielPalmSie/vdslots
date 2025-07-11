<?php


class SafeParams
{
    protected $transaktions_id;
    protected $transaktions_t_id;
    protected $service_id;
    protected $token_id;
    protected $token_start_mac;
    protected $token_start_datetime;
    protected $token_end_datetime;
    protected $sequence;
    protected $cursor;
    protected $uuid;
    protected $xml;

    protected $is_rollback = false;
    protected $rollback_amount;
    protected $rollback_type;
    protected $user_game_session_id;
    protected $user_id;

    protected $regenerated_from_token;

    private $extra_info = [];

    /**
     * SafeParams constructor.
     * @param array $params
     */
    public function __construct(array $params = [])
    {
        $this->transaktions_id = $params['TransaktionsID'] ?: null;
        $this->transaktions_t_id = $params['TransaktionsTid'] ?: null;
        $this->service_id = $params['ServiceID'] ?: null;
        $this->token_id = $params['TamperTokenID'] ?: null;
        $this->token_start_mac = $params['TamperTokenStartMAC'] ?: null;
        $this->token_start_datetime = $params['TamperTokenUdstedelseDatoTid'] ?: null;
        $this->token_end_datetime = $params['TamperTokenPlanlagtLukketDatoTid'] ?: null;
        $this->sequence = $params['sequence'] ?: null;
        $this->cursor = $params['cursor'] ?: null;
        $this->uuid = $params['uuid'] ?: null;
        $this->xml = $params['xml'] ?: null;
    }

    /**
     * @return string|null
     */
    public function getTransaktionsId()
    {
        return $this->transaktions_id;
    }

    /**
     * @param string|null $transaktions_id
     */
    public function setTransaktionsId($transaktions_id)
    {
        $this->transaktions_id = $transaktions_id;
    }

    /**
     * @return string|null
     */
    public function getTransaktionsTId()
    {
        return $this->transaktions_t_id;
    }

    /**
     * @param string|null $transaktions_t_id
     */
    public function setTransaktionsTId($transaktions_t_id)
    {
        $this->transaktions_t_id = $transaktions_t_id;
    }

    /**
     * @return string|null
     */
    public function getServiceId()
    {
        return $this->service_id;
    }

    /**
     * @param string|null $service_id
     */
    public function setServiceId($service_id)
    {
        $this->service_id = $service_id;
    }

    /**
     * @return int|null
     */
    public function getTokenId()
    {
        return $this->token_id;
    }

    /**
     * @param int|null $token_id
     */
    public function setTokenId($token_id)
    {
        $this->token_id = $token_id;
    }

    /**
     * @return string|null
     */
    public function getTokenStartMac()
    {
        return $this->token_start_mac;
    }

    /**
     * @param string|null $token_start_mac
     */
    public function setTokenStartMac($token_start_mac)
    {
        $this->token_start_mac = $token_start_mac;
    }

    /**
     * @return string|null
     */
    public function getTokenStartDatetime()
    {
        return $this->token_start_datetime;
    }

    /**
     * @param string|null $token_start_datetime
     */
    public function setTokenStartDatetime($token_start_datetime)
    {
        $this->token_start_datetime = $token_start_datetime;
    }

    /**
     * @return string|null
     */
    public function getTokenEndDatetime()
    {
        return $this->token_end_datetime;
    }

    /**
     * @param string|null $token_end_datetime
     */
    public function setTokenEndDatetime($token_end_datetime)
    {
        $this->token_end_datetime = $token_end_datetime;
    }

    /**
     * @return int|null
     */
    public function getSequence()
    {
        return $this->sequence;
    }

    /**
     * @param int|null $sequence
     */
    public function setSequence($sequence)
    {
        $this->sequence = $sequence;
    }

    /**
     * @return string|null
     */
    public function getCursor()
    {
        return $this->cursor;
    }

    /**
     * @param string|null $cursor
     */
    public function setCursor($cursor)
    {
        $this->cursor = $cursor;
    }

    /**
     * @return string|null
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * @param string|null $uuid
     */
    public function setUuid($uuid)
    {
        $this->uuid = $uuid;
    }

    /**
     * @return array|null
     */
    public function getXml()
    {
        return $this->xml;
    }

    /**
     * @param array|null $xml
     */
    public function setXml($xml)
    {
        $this->xml = $xml;
    }

    /**
     * @return mixed
     */
    public function getIsRollback()
    {
        return $this->is_rollback;
    }

    /**
     * @param mixed $is_rollback
     */
    public function setIsRollback($is_rollback)
    {
        $this->is_rollback = $is_rollback;
    }

    /**
     * @return mixed
     */
    public function getRollbackAmount()
    {
        return $this->rollback_amount;
    }

    /**
     * @param mixed $rollback_amount
     */
    public function setRollbackAmount($rollback_amount)
    {
        $this->rollback_amount = $rollback_amount;
    }

    /**
     * @return mixed
     */
    public function getRollbackType()
    {
        return $this->rollback_type;
    }

    /**
     * @param mixed $rollback_type
     */
    public function setRollbackType($rollback_type)
    {
        $this->rollback_type = $rollback_type;
    }

    /**
     * @return mixed
     */
    public function getUserGameSessionid()
    {
        return $this->user_game_session_id;
    }

    /**
     * @param mixed $user_game_session_id
     */
    public function setUserGameSessionid($user_game_session_id)
    {
        $this->user_game_session_id = $user_game_session_id;
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * @param mixed $user_id
     */
    public function setUserId($user_id)
    {
        $this->user_id = $user_id;
    }

    /**
     * @return mixed
     */
    public function getRegeneratedFromToken()
    {
        return $this->regenerated_from_token;
    }

    /**
     * @param mixed $regenerated_from_token
     */
    public function setRegeneratedFromToken($regenerated_from_token)
    {
        $this->regenerated_from_token = $regenerated_from_token;
    }

    /**
     * @return array
     */
    public function getExtraInfo(): array
    {
        return $this->extra_info;
    }

    /**
     * @param array $extra_info
     */
    public function setExtraInfo(array $extra_info)
    {
        $this->extra_info = $extra_info;
    }

    /**
     * @return array
     */
    public function getAll()
    {
        $safe_data = [
            'TransaktionsID' => $this->transaktions_id,
            'TransaktionsTid' => $this->transaktions_t_id,
            'ServiceID' => $this->service_id,
            'TamperTokenID' => $this->token_id,
            'TamperTokenStartMAC' => $this->token_start_mac,
            'TamperTokenUdstedelseDatoTid' => $this->token_start_datetime,
            'TamperTokenPlanlagtLukketDatoTid' => $this->token_end_datetime,
            'sequence' => $this->sequence,
            'cursor' => $this->cursor,
            'xml' => $this->xml,
            'uuid' => $this->uuid,
        ];

        if ($this->is_rollback) {
            $safe_data['is_rollback'] = true;
            $safe_data['rollback_amount'] = $this->rollback_amount;
            $safe_data['rollback_type'] = $this->rollback_type;
            $safe_data['user_game_session_id'] = $this->user_game_session_id;
            $safe_data['user_id'] = $this->user_id;
        }

        if ($this->extra_info) {
            $safe_data['extra_info'] = $this->extra_info;
        }

        if ($this->regenerated_from_token) {
            $safe_data['regenerated_from_token'] = $this->regenerated_from_token;
        }

        return $safe_data;
    }

    /**
     * Export all date as json
     *
     * @return false|string
     */
    public function exportJson()
    {
        return json_encode([
            'TransaktionsID' => $this->transaktions_id,
            'TransaktionsTid' => $this->transaktions_t_id,
            'ServiceID' => $this->service_id,
            'TamperTokenID' => $this->token_id,
            'TamperTokenStartMAC' => $this->token_start_mac,
            'TamperTokenUdstedelseDatoTid' => $this->token_start_datetime,
            'TamperTokenPlanlagtLukketDatoTid' => $this->token_end_datetime,
            'sequence' => $this->sequence,
            'cursor' => $this->cursor,
        ]);
    }
}
