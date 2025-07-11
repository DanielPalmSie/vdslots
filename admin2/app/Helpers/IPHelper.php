<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 17/03/16
 * Time: 12:21
 */

namespace App\Helpers;

class IPHelper
{
    public static function remIp()
    {
        if (isset($_SERVER["HTTP_PANDA_ORIGINAL_IP"])) {
            return $_SERVER["HTTP_PANDA_ORIGINAL_IP"];
        } elseif (!isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
            return $_SERVER['REMOTE_ADDR'];
        } else {
            $cf_ip_ranges = ['204.93.240.0/24','204.93.177.0/24','199.27.128.0/21','173.245.48.0/20','103.21.244.0/22',
                '103.22.200.0/22','103.31.4.0/22','141.101.64.0/18','108.162.192.0/18','190.93.240.0/20','188.114.96.0/20',
                '197.234.240.0/22','198.41.128.0/17','162.158.0.0/15'];
            foreach ($cf_ip_ranges as $range) {
                if (self::ipInRange($_SERVER['REMOTE_ADDR'], $range)) {
                    return $_SERVER['HTTP_CF_CONNECTING_IP'];
                }
            }
            return $_SERVER['REMOTE_ADDR'];
        }
    }

    private static function ipInRange($ip, $range)
    {
        if (strpos($range, '/') == false) {
            $range .= '/32';
        }
        list($range, $netmask) = explode('/', $range, 2);
        $range_decimal = ip2long($range);
        $ip_decimal = ip2long($ip);
        $wildcard_decimal = pow(2, (32 - $netmask)) - 1;
        $netmask_decimal = ~ $wildcard_decimal;
        return (($ip_decimal & $netmask_decimal) == ($range_decimal & $netmask_decimal));
    }
}
