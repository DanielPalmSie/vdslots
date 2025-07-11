<?php
/**
 * Created by PhpStorm.
 * User: iondum
 * Date: 21/11/18
 * Time: 17:04
 */

namespace App\Classes;


use Exception;
use Illuminate\Support\Collection;

class PaymentsHelper
{
    CONST OPTIONS = [
        'ecopayz' => [
            'title' => 'EcoPayz',
            'score' => 5,
            'deposits' => "dep_type='ecopayz'",
            'pending_withdrawals' => "payment_method='ecopayz'"
        ],
        'VISA' => [
            'title' => 'VISA',
            'score' => 3,
            'deposits' => "(scheme = 'VISA' or card_hash REGEXP '^[vV]' or card_hash REGEXP '^4')",
            'pending_withdrawals' => "(scheme REGEXP '^[vV]' or scheme REGEXP '^4')",
        ],
        'Mastercard' => [
            'title' => 'Mastercard',
            'score' => 3,
            'deposits' => "(scheme='Mastercard' or card_hash REGEXP '^[mM]' or card_hash REGEXP '^5[1-5]')",
            'pending_withdrawals' => "(scheme REGEXP '^[mM]' or scheme REGEXP '^5[1-5]')"
        ],
        /**
         * smsvoucher == payground
         */
        'prepaid_card' => [
            'title' => 'Prepaid Card',
            'score' => 10,
            'deposits' => "(dep_type in ('paysafe', 'astro', 'neosurf', 'flexepin', 'smsvoucher', 'ecovoucher', 'entropay'))"
        ],
        'flexepin' => [
            'title' => 'Flexepin',
            'score' => 10,
            'deposits' => "dep_type='flexepin'",
            'pending_withdrawals' => "payment_method='flexepin'"
        ],
        'klama' => [
            'title' => 'Klama',
            'score' => 2,
            'deposits' => "(scheme='directEbanking' AND dep_type='adyen')",
            'pending_withdrawals' => "payment_method='klama'"
        ],
        'skrill' => [
            'title' => 'Skrill',
            'score' => 5,
            'deposits' => "dep_type='skrill'",
            'pending_withdrawals' => "payment_method='skrill'"
        ],
        'trustly' => [
            'title' => 'Trustly',
            'score' => 2,
            'deposits' => "(dep_type='trustly' AND scheme!='ideal')",
            'pending_withdrawals' => "payment_method='trustly'"
        ],
        'zimplerbank' => [
            'title' => 'Zimpler Bank',
            'score' => 2,
            'deposits' => "(dep_type = 'zimpler' AND scheme != 'bill')",
            'pending_withdrawals' => "payment_method='zimpler'"
        ],
        'puggle' => [
            'title' => 'Zimpler Mobile',
            'score' => 5,
            'deposits' => "dep_type='puggle'",
            'pending_withdrawals' => "payment_method='puggle'"
        ],
        'siru' => [
            'title' => 'Siru Mobile',
            'score' => 5,
            'deposits' => "dep_type='siru'",
            'pending_withdrawals' => "payment_method='siru'"
        ],
        'neosurf' => [
            'title' => 'Neosurf',
            'score' => 10,
            'deposits' => "dep_type='neosurf'",
            'pending_withdrawals' => "payment_method='neosurf'"
        ],
        'instadebit' => [
            'title' => 'Instadebit',
            'score' => 2,
            'deposits' => "dep_type='instadebit'",
            'pending_withdrawals' => "payment_method='instadebit'"
        ],
        'paysafe' => [
            'title' => 'Paysafecard',
            'score' => 10,
            'deposits' => "dep_type='paysafe'",
            'pending_withdrawals' => "payment_method='paysafe'"
        ],
        'payground' => [
            'title' => 'SMSVoucher',
            'score' => 5,
            'deposits' => "dep_type='payground'",
            'pending_withdrawals' => "payment_method='payground'"
        ],
        'neteller' => [
            'title' => 'Neteller',
            'score' => 5,
            'deposits' => "dep_type='neteller'",
            'pending_withdrawals' => "payment_method='neteller'"
        ],
        'citadel' => [
            'title' => 'Citadel',
            'score' => 2,
            'deposits' => "dep_type='citadel'",
            'pending_withdrawals' => "payment_method='citadel'"
        ],
        'interac' => [
            'title' => 'Interac',
            'score' => 2,
            'deposits' => "scheme='interac'",
            'pending_withdrawals' => "payment_method='interac'"
        ],
        'paypal' => [
            'title' => 'Paypal',
            'score' => 5,
            'deposits' => "dep_type='paypal'",
            'pending_withdrawals' => "payment_method='paypal'"
        ],
        'ideal' => [
            'title' => 'iDeal',
            'score' => 2,
            'deposits' => "scheme='ideal'",
            'pending_withdrawals' => "payment_method='ideal'"
        ],
        'entercash' => [
            'title' => 'Entercash',
            'score' => 2,
            'deposits' => "(dep_type='entercash' AND scheme!='ideal')",
            'pending_withdrawals' => "payment_method='entercash'"
        ],
        'mobilepay' => [
            'title' => 'MobilePay',
            'score' => 5,
            'deposits' => "(dep_type='paymentiq' AND scheme='mobilepay')",
        ],
        //TODO: BAN-11013 removed until the confirmation on how we proceed Bambora cards
//        'bambora' => [
//            'title' => 'Bambora',
//            'score' => 5,
//            'deposits' => "dep_type='bambora'",
//            'pending_withdrawals' => "payment_method='bambora'"
//        ],
        'astropay' => [
            'title' => 'AstroPay',
            'score' => 5,
            'deposits' => "dep_type='astropaywallet'",
            'pending_withdrawals' => "payment_method='astropaywallet'"
        ],
        'cashtocode' => [
            'title' => 'CashtoCode',
            'score' => 5,
            'deposits' => "dep_type='cashtocode'",
        ],
        'euteller' => [
            'title' => 'Euteller',
            'score' => 5,
            'deposits' => "dep_type='euteller'",
        ],
        'flykk' => [
            'title' => 'Flykk',
            'score' => 5,
            'deposits' => "dep_type='flykk'",
        ],
        'mifinity' => [
            'title' => 'MiFinity',
            'score' => 5,
            'deposits' => "dep_type='mifinity'",
            'pending_withdrawals' => "payment_method='mifinity' AND scheme!='payanybank'"
        ],
        'payretailers' => [
            'title' => 'PayRetailers',
            'score' => 5,
            'deposits' => "dep_type='payretailers'",
            'pending_withdrawals' => "payment_method='payretailers'"
        ],
        'muchbetter' => [
            'title' => 'MuchBetter',
            'score' => 5,
            'deposits' => "dep_type='muchbetter'",
            'pending_withdrawals' => "payment_method='muchbetter'"
        ],
    ];

    /**
     * @param string $sql
     * @param string $table
     * @param null|array $methods
     *
     * @return string
     */
    public static function toUnionQuery(string $sql, string $table, $methods = null): string
    {
        if (! self::arePaymentMethodsSupported($table, $methods)) {
            throw new Exception('All payment methods were filtered out');
        }

        return self::getFiltered($table, $methods)
            ->map(function ($config, $key) use ($sql, $table) {
                $sql = str_replace('replace_with_key', $key, $sql);
                $sql .= " AND " . $config[$table];
                return $sql;
            })
            ->implode("\n union \n");
    }

    public static function getOptionsCollection(): Collection
    {
        return collect(self::OPTIONS);
    }

    public static function arePaymentMethodsSupported(string $table, $methods = null): bool
    {
        return self::getFiltered($table, $methods)->isNotEmpty();
    }

    private static function getFiltered(string $table, $methods = null): Collection
    {
        return PaymentsHelper::getOptionsCollection()
            ->filter(function($paymentOption, $key) use ($table, $methods) {
                // we only work on methods which are configured for the targeted table
                if (empty($paymentOption[$table])) {
                    return false;
                }

                // we only work with the provided methods, if any
                if (is_array($methods)) {
                    return in_array($key, $methods);
                }

                return true;
            });

    }
}
