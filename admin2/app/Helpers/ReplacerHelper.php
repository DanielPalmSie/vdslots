<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 17/03/16
 * Time: 12:21
 */

namespace App\Helpers;

use Silex\Application;

class ReplacerHelper
{
    private static $aReplacers = [
        '__USERNAME__' => 'Username',
        '__FULLNAME__' => 'Fullname',
        '__USERID__' => 'User ID',
        '__CURRENCY__' => 'User Currency',
        '__EMAIL__' => 'User email',
        '__COUNTRY__' => 'Country',
        '__FIRSTNAME__' => 'First name',
        '__LASTNAME__' => 'Last name',
        '__MOBILE__' => 'Mobile Phone',
        '__ADDRESS__' => 'Address',
        '__CITY__' => 'City',
        '__ALIAS__' => 'Alias',
        '__CURSYM__' =>  'Currency modifier',
        '__FREESPINS[]__' =>  'Number of freespins according to players bonus. Insert default value',
        '__WELCOMEBONUS[]__' =>  'Welcome bonus according to players bonus. Insert default value',
        '__B_BONUSCODE__' => 'Bonus Code',
        '__B_RELOADCODE__' => 'Reload Code',
        '__B_BONUSNAME__' => 'Bonus Name',
        '__B_REWARD__' =>  'Bonus Reward',
        '__B_EXPIRETIME__' =>  'Bonus Expire Time',
        '__B_NUMDAYS__' =>  'Bonus Num Days',
        '__B_GAME__' =>  'Bonus Game Name',
        '__B_WAGERREQ__' =>  'Wager Requirements',
        '__B_AMOUNT__' =>  'Bonus Amount',
        '__B_EXTRAAMOUNT__' =>  'Bonus Extra Amount',
        '__B_EXTRA__' =>  'Bonus Extra',
        '__V_VOUCHERNAME__' => 'Voucher name',
        '__V_VOUCHERCODE__' => 'Voucher code',
        '__V_AMOUNT__' => 'If bonus empty -> voucher amount otherwise Valid Days',
        '__V_DAYS__' => 'Valid Days',
        '__V_COUNT__' => 'Voucher Count',
        '__V_GAME__' => 'Voucher Game Name',
        '__V_SPINS__' =>  'Number of spins'
    ];
    public static function getDescription($replacer)
    {
        return self::$aReplacers[$replacer] ? $replacer . ' (' . self::$aReplacers[$replacer] . ')' : $replacer;
    }
}
