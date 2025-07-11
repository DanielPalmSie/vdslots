<?php

namespace DBUserHandler\Libraries\GoogleAnalytics\Types;

use DBUserHandler\Libraries\GoogleAnalytics\Interfaces\GoogleEventInterface;

abstract class GoogleAnalytics extends GoogleEvent implements GoogleEventInterface
{
    /**
     * @return string
     */
    abstract public function getAction(): string;

    /**
     * @return string
     */
    abstract public function getCategory(): string;

    /**
     * @return string
     */
    abstract public function getName(): string;

    /**
     * This kind of google events fall into the "ECommerce" section on google analytics
     * and it's used to keep track of the conversion (with amount) for each product (Ex. First Deposit)
     * (conversions -> ecommerce -> transactions)
     *
     * @return array
     */
    public function getAnalyticsObject(): array
    {
        // event exist on redis
        if (empty($this->redis_event) || $this->redis_event['triggered'] != 'yes' || $this->redis_event['analytics']) {
            return [];
        }

        // we override via the redis event the base category (Ex. deposit we track them by type)
        $category = $this->redis_event['type'] ?? $this->getCategory();
        $marketName = $this->redis_event['market_name'] ?? $this->user->getMarketName();
        $paymentMethod = $this->redis_event['payment_method'] ?? '';
        $transactionId = $this->redis_event['id'] ?? '';
        $bonusCode = !phive()->isEmpty(phive('Bonuses')->getCurReload($this->user)) ? phive('Bonuses')->getCurReload($this->user) : 'no-bonus-code';

        $this->redis_event['analytics'] = true;
        $gti = $this->redis_event['group_transaction_id'] ?? '';

        $defaultReturn = [
            'key' => $this->key,
            'event' => $this->getName(),
            'category' => $category, // Current not used, but kept it as is; could be needed in future.
        ];

        if (phive('RavenTrack')->isEnabled()) {
            $lookupData = phive('RavenTrack')->getClickLookup();
            if (phive()->getSetting('enable_logs_in_google_events')) {
                phive('Logger')->getLogger('google-analytics')->info('btag_raventrack', ['data'=> json_encode($lookupData), 'user_id'=>$this->user->getId()]);
            }
            if ($lookupData && $lookupData['tracking_tag_information']['btag']) {
                $defaultReturn['click_id'] = $lookupData['click_id'] ?? '';
                $defaultReturn['gclid'] = $lookupData['gclid'] ?? '';
                $defaultReturn['msclkid'] = $lookupData['msclkid'] ?? '';
                $defaultReturn['campaign_id'] = $lookupData['campaign_id'] ?? '';
                $defaultReturn['click_date'] = $lookupData['click_date'] ?? '';
                $defaultReturn['btag'] = $lookupData['tracking_tag_information']['btag'];
                $this->redis_event['btag'] = $lookupData['tracking_tag_information']['btag'];
            }
        }
        // Added btag also in tracking event
        $this->user->setTrackingEvent($this->key, $this->redis_event);

        switch ($this->key) {
            case 'started-first-deposit':
            case 'made-first-deposit':
            case 'enod':
            case 'started-subsequent-deposit':
                $return = [
                    'userId' => $this->user->getId(),
                    'clientId' => $this->user->getGaId(),
                    'marketName' => $marketName,
                    'paymentMethod' => $paymentMethod,
                    'bonusCode' => $bonusCode,
                    'transactionId' => $transactionId,
                    'groupTransactionId' => $gti
                ];

                $defaultReturn = array_merge($defaultReturn, $return);
                break;
            case 'logged-in':
            case 'partially-registered':
            case 'registered':
                $return = [
                    'userId' => $this->user->getId(),
                    'clientId' => $this->user->getGaId(),
                    'marketName' => $marketName
                ];
                $defaultReturn = array_merge($defaultReturn, $return);
                break;
            default:
                //
                break;
        }

        return $defaultReturn;
    }
}
