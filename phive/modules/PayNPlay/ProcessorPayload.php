<?php

namespace PayNPlay;

/**
 * Payload formatter for PNP.
 * Takes request and based on strategy converts it to a valid format for ProcessorFactory
 */
class ProcessorPayload
{
    /**
     * @var array
     */
    private array $request;

    /**
     *
     */
    private const STRATEGY_TRUSTLY = 'strategy_trustly';
    /**
     *
     */
    private const STRATEGY_SWISH = 'strategy_swish';

    /**
     * @param array $request
     */
    public function __construct(array $request)
    {
        $this->request = $request;
    }

    /**
     * @return array
     */
    private function getBankIDPayload(): array
    {
        $successUrl = phive()->getSiteUrl('', true, 'phive/modules/PayNPlay/html/success_bankid.php');
        $failUrl = phive()->getSiteUrl('', true, 'phive/modules/PayNPlay/html/fail.php');

        $payload = [
            'country' => getCountry(),
            'successUrl' => $successUrl,
            'failUrl' => $failUrl,
        ];

        $payload['currency'] = ciso();

        if (isset($this->request['amount'])) {
            $payload['amount'] = $this->request['amount'] * 100;
        }

        return $payload;
    }

    /**
     * @return array
     */
    private function getSwishPayload(): array
    {
        $payload = [];
        $payload['amount'] = (int) $this->request['amount'] * 100;
        $payload['currency'] = ciso();
        $payload['country'] = getCountry();
        $payload['ip'] = remIp() ?? '0.0.0.0';

        if(isLogged()){
            $payload['userId'] = cu()->getId();
            $payload['personId'] = cu()->getNid();
        } else {
            $extData = phive('PayNPlay')->getBankIDExtData($_SESSION['bankid_request_id']);
            $payload['userId'] = $extData['userId'];
            $payload['personId'] = $extData['nid'];
        }

        return $payload;
    }

    /**
     * @return array
     */
    private function getTrustlyPayload(): array
    {
        $localizer = phive('Localizer');

        $successUrl = phive()->getSiteUrl('', true, 'phive/modules/PayNPlay/html/success_trustly.php');
        $failUrl = phive()->getSiteUrl('', true, 'phive/modules/PayNPlay/html/fail.php');

        $payload = [
            'country' => getCountry(),
            'currency' => ciso(),
            'locale' => $localizer->getLocale($localizer->getLanguage()),
            'requestKYC' => true,
            'successUrl' => $successUrl,
            'failUrl' => $failUrl,
            'ip' => remIp(),
        ];

        if (cu()) {
            /** @var DBUser $user */
            $user = cu();
            $payload['userId'] = $user->getId();
            $payload['firstName'] = $user->getAttr('firstname');
            $payload['lastName'] = $user->getAttr('lastname');
            $payload['email'] = $user->getAttr('email');
        }

        if (isset($this->request['amount'])) {
            $payload['amount'] = $this->request['amount'] * 100;
        }

        return $payload;
    }

    /**
     * @return array
     */
    public function getPayload(): array
    {
        $strategy = $this->request['strategy'];
        $step = $this->request['strategy_step'];

        if ($strategy == self::STRATEGY_SWISH && $step == 1) {
            $payload = $this->getBankIDPayload();
        } elseif ($strategy == self::STRATEGY_SWISH && $step == 2) {
            $payload = $this->getSwishPayload();
        } elseif ($strategy == self::STRATEGY_TRUSTLY) {
            $payload = $this->getTrustlyPayload();
        }

        if ($_COOKIE['referral_id']) {
            $payload['bonus_code'] = $_COOKIE['referral_id'];
            phive('PayNPlay')->setBonusDataToRedis($_COOKIE['referral_id']);
        }

        return $payload;
    }
}
