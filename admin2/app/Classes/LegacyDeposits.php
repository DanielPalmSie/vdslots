<?php
/**
 * IMPORTANT NOTICE: This class contains a lot of legacy code so no time wasted porting code that is in the phive project
 * and can be updated. So then this will avoid less modifications to this project related code. This should be ported
 * in a proper way, i.e. using an API or something like that.
 *
 * Created by PhpStorm.
 * User: ricardo
 * Date: 2/3/17
 * Time: 10:57 AM
 */

namespace App\Classes;

use App\Models\User;

class LegacyDeposits
{

    /**
     * @param User $user
     * @param null|bool $split_sub To return sub categorized methods in a separate way
     * @return array
     */
    public static function getUserDepositMethods(User $user, $split_sub = null)
    {
        $sub_banks = [];        
        
        /*
        if (phive('Cashier')->showAlt('entercash')) {
            $sub_banks = array_merge($sub_banks, []);
            //$sub_banks = array_merge($sub_banks, $deposit_box_class->getEcashOptions());
        }
        */
        
        $cashier = phive('Cashier');
        
        foreach ($cashier->getSetting('bank_methods') as $bank) {
            if ($cashier->WithdrawDepositAllowed(cu($user->id), $bank, 'deposit')) {
                $sub_banks[] = $bank;
            }
        }

      
      /*
        $deposit_box_class = new \WithdrawDepositBoxBase2();

        $deposit_box_class->user = phive('UserHandler')->getUser($user->getKey());

        $deposit_box_class = self::legacyBoxInit($deposit_box_class);

        $sub_banks = [];
        if ($deposit_box_class->showAlt('entercash')) {
            $sub_banks = array_merge($sub_banks, []);
            //$sub_banks = array_merge($sub_banks, $deposit_box_class->getEcashOptions());
        }
        foreach ($deposit_box_class->deposit_banks as $bank => $show) {
            if ($show && $deposit_box_class->showAlt($bank)) {
                $sub_banks[] = $bank;
            }
        }
      */

        $methods = collect($deposit_box_class->pcats)->flatten(1)->reject(function ($value, $key) {
            return $value === false;
        })->values()->merge($sub_banks)->merge($user->settings_repo->getDisabledDepositsMethods())->unique()->all();

        if (is_null($split_sub)) {
            return $methods;
        } elseif (empty($sub_banks)) {
            return $methods;
        } else {
            $res = [];
            foreach ($methods as $method) {
                if (in_array($method, $sub_banks)) {
                    $res['banks'] = $sub_banks;
                } elseif ($method != 'banks') {
                    $res[] = $method;
                }
            }
            return $res;
        }
    }

    public static function legacyBoxInit($deposit_box_class)
    {
        $ccard_arr = array('ccards' => array('visa', 'mc', 'maestro'));

        if ($deposit_box_class->user->data['country'] == 'NO') {
            $bank_arr = ['bankdeposit' => []];
        } else {
            $bank_arr = ['bankdeposit' => ['banks']];
        }

        $the_rest = array('invoice' => array('puggle', 'siru', 'smsbill', 'paylevo'),
            'pcards' => array('paysafe', 'astro', 'neosurf', 'flexepin', 'smsvoucher', 'ecovoucher'),
            'ewallet' => array('neteller', 'skrill', 'ecopayz'));

        if (phive('Cashier')->getSetting('banks_show')) {
            $bank_arr = ['bankdeposit' => $bank_arr['bankdeposit']];
        } else {
            $bank_arr = ['bankdeposit' => array_merge([], array_keys($deposit_box_class->deposit_banks))];
        }
        //$bank_arr = array('bankdeposit' => array('banks', 'euteller', 'poli', 'instadebit', 'ums', 'sofort', 'ideal'));

        if ($deposit_box_class->user->data['country'] == 'FI') {
            $deposit_box_class->pcats = array_merge($bank_arr, $ccard_arr, $the_rest);
        } else {
            $deposit_box_class->pcats = array_merge($ccard_arr, $bank_arr, $the_rest);
        }

        //$deposit_box_class->handlePost(array('pamounts', 'help_id'), array('pamounts' => '150,100,50,30,20,10'));


        $tmp = array();
        foreach ($deposit_box_class->pcats as $headline => $nms) {
            foreach ($nms as $nm)
                $tmp[$headline][$nm] = $nm;
        }

        $deposit_box_class->pcats = array_filter($tmp, function ($el) {
            return !phive()->isEmpty($el);
        });

        return $deposit_box_class;
    }

}
