<?php

namespace DBUserHandler\Libraries\GoogleAnalytics\Actions;

use DBUser;
use DBUserHandler\Libraries\GoogleAnalytics\Types\GoogleEvent;

class PRRoomEvent extends GoogleEvent
{
    /**
     * @var string
     */
    protected string $bonus_code;

    /**
     * @var string
     */
    protected string $affiliate_post_back_id;

    /**
     * PRRoomEvent constructor.
     * @param DBUser $user
     * @param string $key
     */
    public function __construct(DBUser $user, string $key)
    {
        parent::__construct($user, $key);

        $this->bonus_code = $this->user->getAttr('bonus_code');
        $this->affiliate_post_back_id = $this->user->getSetting('affiliate_postback_id');

    }

    /**
     * @return string
     */
    public function getQueryString(): string
    {
        if (empty($this->redis_event) || $this->redis_event['triggered'] !== 'yes' || $this->redis_event['pr_room'] === true) {
            return '';
        }

        $this->redis_event['pr_room'] = true;
        $this->user->setTrackingEvent($this->key, $this->redis_event);

        if (!$this->shouldEventBeFired()) {
            return '';
        }

        $amount = $this->redis_event['amount'] * 100;
        $product = phive()->getSetting('pr_product', 'videoslots');

        return "product={$product}&event={$this->key}&amount={$amount}&bonus_code={$this->bonus_code}&uid={$this->user->getId()}&affiliate_postback_id={$this->affiliate_post_back_id}";
    }

    /**
     * @return bool
     */
    protected function shouldEventBeFired(): bool
    {
        return phive()->getSetting('tracking_pixels_s2s');
    }
}