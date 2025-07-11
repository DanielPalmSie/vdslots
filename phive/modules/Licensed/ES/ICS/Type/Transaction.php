<?php

namespace ES\ICS\Type;

use ES\ICS\Constants\ICSConstants;

class Transaction
{
    public const NO_DEVICE = '__NO_DEVICE__';

    private $amount = '0';
    private $timestamp = '';
    private $scheme = '';
    private $display_name = '';
    private $type = '';
    private $card_hash = '';
    private $ip = '';
    private $equipment = '';
    private $uagent = '';
    private $card_type = '';
    private $status = '';
    private $mts_id = '';
    private $user_id = '';
    private $approved_by = '';
    private $lic_settings;

    public function __construct($details, $lic_settings)
    {
        $this->lic_settings = $lic_settings;

        foreach ($details as $key => $value) {
            $this->{$key} = $value !== null ? trim($value) : '';
        }

        $this->type = strtolower($this->type); //config keys to compare are lowercase

    }

    /**
     * Amount
     *
     * @return float
     */
    public function getAmount(): float
    {
        return (float)rnfCents($this->amount, '.', '');
    }

    /**
     * Timestamp
     *
     * @param string $format
     *
     * @return string
     */
    public function getTimestamp(string $format = ICSConstants::DATETIME_FORMAT): string
    {
        return phive()->fDate($this->timestamp, $format);
    }


    /**
     * Credit card last 4 digits.
     * It might return an empty string
     * @return string
     */
    public function getLastFourDigitsCard (): string
    {
        return substr($this->card_hash, -4);
    }

    /**
     * Payment method
     *
     * @param bool $with_card_hash
     * @return string
     */
    public function getPaymentMethod(): string
    {
        $psp_settings = null;

        if (!empty($this->type)) {
            $psp_settings = phive('CasinoCashier')->getPspSettingDeprecated($this->type);
        }

        if (empty($psp_settings) && !empty($this->scheme)) {
            $psp_settings = phive('CasinoCashier')->getPspSettingDeprecated($this->scheme);
        }

        if (empty($psp_settings) && !empty($this->display_name)) {
            $psp_settings = phive('CasinoCashier')->getPspSettingDeprecated(strtolower($this->display_name));
        }

        if (empty($psp_settings)) {
            return $this->type;
        }

        return $psp_settings['type'];
    }

    /**
     * Payment method type
     * - get the payment method type
     * - found in 3.5.7.4 of Guia_calidad_reporte_datos_SCI_v3_2_EN.docx
     *
     * @return string
     */
    public function getPaymentMethodType(): string
    {
        $types = $this->lic_settings['ICS']['payment_method'];

        foreach ($types as $type => $options) {
            if (in_array($this->type, $options)) {
                return $type;
            }
        }
        return '99';
    }

    /**
     * Other payment method
     * SPECIFY TYPE WHEN PAYMENT TYPE IS OTHER
     * Only need it when payment type is other (99)
     *
     * For now we don't needed it, all payments have the mapping in place.
     *
     *
     * @return string
     */
//    public function getPaymentMethodOther(): string
//    {
//        return '';
//    }

    /**
     * IP used to do the Operation
     *
     * @return string
     */
    public function getIp(): string
    {
        return $this->ip;
    }

    /**
     * Device type
     * 'MO' => everything , 'PC' => PC+macintosh, 'TB' => ipad
     *
     * to match deposit entry with users_sessions:
     * the more recent users_sessions where started_at is less than deposit timestamp
     *
     * @return string
     */
    public function getDeviceType(): string
    {
        if($this->equipment === static::NO_DEVICE){
            return 'OT';
        }
        return $this->lic_settings['ICS']['device_type'][strtolower($this->equipment)] ?? 'MO';
    }

    /**
     * Device Identifier
     * Most specific as possible (MAC,IMEI, phone model, so, browser...)
     * the more recent actions where tag = "%uagent%" and started_at is less than deposit timestamp
     *
     * @return string
     */
    public function getDeviceId(): string
    {
        $device = explode('set uagent to', $this->uagent);

        return array_pop($device);
    }

    /**
     * @return string
     */
    public function getDisplayName(): string
    {
        return (string) $this->display_name;
    }

    /**
     * @return string
     */
    public function getCardType(): string
    {
        return (string) $this->card_type;
    }

    public function getMtsId(): string
    {
        return (string) $this->mts_id;
    }

    public function getStatus(): string
    {
        return (string) $this->status;
    }

    public function getUserId(): string
    {
        return (string) $this->user_id;
    }

    public function getApprovedBy(): string
    {
        return (string) $this->approved_by;
    }
}
