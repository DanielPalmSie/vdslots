<?php

declare(strict_types=1);

namespace Videoslots\RgLimits;

final class Settings
{
    /**
     * @var string
     */
    public const LIMIT_WAGER = "wager";

    /**
     * @var string
     */
    public const LIMIT_LOSS = "loss";

    /**
     * @var string
     */
    public const LIMIT_DEPOSIT = "deposit";

    /**
     * @var string
     */
    public const LIMIT_LOGIN = "login";

    /**
     * @var string
     */
    public const LIMIT_TIMEOUT = "timeout";

    /**
     * @var string
     */
    public const LIMIT_BETMAX = "betmax";

    /**
     * @var string
     */
    public const LIMIT_RC = "rc";

    /**
     * @var string
     */
    public const LIMIT_BALANCE = 'balance';

    /**
     * @var string
     */
    public const LIMIT_NET_DEPOSIT = 'net_deposit';

    /**
     * @param string $type
     *
     * @return bool
     */
    public static function canRemove(string $type): bool
    {
        return in_array($type, [
            self::LIMIT_WAGER,
            self::LIMIT_LOSS,
            self::LIMIT_DEPOSIT,
            self::LIMIT_LOGIN,
            self::LIMIT_TIMEOUT,
            self::LIMIT_BETMAX,
            self::LIMIT_RC,
        ]);
    }
}
