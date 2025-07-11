<?php

namespace DBUserHandler\Libraries\GoogleAnalytics\Types;

use DBUser;

class GoogleEvent
{
    /**
     * @var DBUser
     */
    protected DBUser $user;

    /**
     * @var string [Key stored in redis queue]
     */
    protected string $key;

    /**
     * @var array
     */
    protected array $redis_event;

    /**
     * GoogleEcommerce constructor.
     * @param string $key
     */
    public function __construct(DBUser $user, string $key)
    {
        $this->key = $key;
        $this->user = $user;
        $this->redis_event = $this->getEvent();
    }

    /**
     * @return array
     */
    public function getEvent(): array
    {
        return $this->user->getTrackingEvent($this->key);
    }
}