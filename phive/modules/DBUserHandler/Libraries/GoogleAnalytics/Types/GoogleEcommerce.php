<?php

namespace DBUserHandler\Libraries\GoogleAnalytics\Types;

use DBUser;
use DBUserHandler\Libraries\GoogleAnalytics\Interfaces\GoogleEventInterface;
use DBUserHandler\Libraries\GoogleAnalytics\Constants;

abstract class GoogleEcommerce extends GoogleAnalytics
{
    /**
     * @var string
     */
    protected string $currency = 'EUR';

    /**
     * @var string
     */
    protected string $affiliation = 'Videoslots';

    /**
     * @var string
     */
    protected string $bonus_code;

    /**
     * @var DBUser
     */
    protected DBUser $user;

    /**
     * @return string
     */
    abstract public function getName(): string;

    /**
     * @return string
     */
    public function getAction(): string
    {
        return 'deposit';
    }

    /**
     * @return string
     */
    public function getCategory(): string
    {
        return 'bank';
    }

    /**
     * This kind of google events fall into the "ECommerce" section on google analytics
     * and it's used to keep track of the conversion (with amount) for each product (Ex. First Deposit)
     * (conversions -> ecommerce -> transactions)
     *
     * @return array
     */
    public function getAnalyticsObject(): array
    {
        $this->redis_event = $this->getEvent();
        // event exist on redis
        if (empty($this->redis_event) || $this->redis_event['triggered'] != 'yes' || $this->redis_event['analytics'] == true) {
            return [];
        }

        $this->bonus_code = !phive()->isEmpty($this->user->getAttr('bonus_code')) ? $this->user->getAttr('bonus_code') : 'no-bonus-code';

        return array_merge(
            parent::getAnalyticsObject(),
            [
                'transactionId' => $this->redis_event['id'], // deposit id
                'transactionTotal' => $this->redis_event['amount'], // deposit amount
                'transactionTotalInt' => floor($this->redis_event['amount']),
                'ecommerce' => $this->getEcommerceObject(),
                'ga4_ecommerce' => $this->ga4Event()
            ]
        );
    }

    /**
     * @return array
     */
    private function getEcommerceObject(): array
    {
        $this->redis_event = $this->getEvent();

        if (empty($this->redis_event)) {
            return [];
        }

        return [
            'purchase' => [
                'actionField' => [
                    'id' => $this->redis_event['id'],
                    'affiliation' => $this->affiliation,
                    'coupon' => $this->bonus_code,
                    'revenue' => $this->redis_event['amount'],
                    'tax' => 0.00,
                    'shipping' => 0.00,
                ],
                'products' => [
                    [
                        'name' => $this->getName(),
                        'id' => $this->key,
                        'price' => $this->redis_event['amount'],
                        'brand' => $this->affiliation,
                        'variant' => $this->redis_event['deposit_data']['display_name'],
                        'category' => 'Deposit',
                        'quantity' => 1,
                        'coupon' => $this->bonus_code,
                    ]
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    private function ga4Event(): array
    {
        return [
            'transaction_id' => $this->redis_event['id'],
            'affiliation' => $this->affiliation,
            'value' => $this->redis_event['amount'],
            'tax' => 0.00,
            'shipping' => 0.00,
            'currency' => $this->currency,
            'coupon' => $this->bonus_code,
            'products' => [
                array_filter([
                    'item_id' => $this->key,
                    'item_name' => $this->getName(),
                    'item_variant' => $this->redis_event['deposit_data']['display_name'],
                    'affiliation' => $this->affiliation,
                    'currency' => $this->currency,
                    'discount' => 0.00,
                    'price' => $this->redis_event['amount'],
                    'quantity' => 1,
                    'coupon' => $this->bonus_code,
                    'item_category' => 'Deposit'
                ])
            ]
        ];
    }
}
