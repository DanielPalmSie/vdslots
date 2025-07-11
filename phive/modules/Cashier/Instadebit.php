<?php

/**
 * @link https://docs.paramountcommerce.com/v1/INSTADEBIT.htm?tocpath=INSTADEBIT%7C_____0 Instadebit documentation
 */
class Instadebit
{
    /**
     * @param string $setting
     * @param DBUser|string $user
     * @return string
     */
    private function getSetting(string $setting, $user): string
    {
        $cashier = phive('Cashier');
        if (!($user instanceof DBUser)) {
            return $cashier->getSetting($setting);
        }

        $settingName = $setting;
        if ($user->getCountry() == 'CA' && $user->getMainProvince() == 'ON') {
            $settingName = $setting . '_ca_on';
        }

        if ($settingName && null !== $cashier->getSetting($settingName)) {
            $setting = $settingName;
        }

        return $cashier->getSetting($setting);
    }

  function post($p, $url = '', $uid = ''){
    phive()->dumpTbl("instadebit_nudge_verify_post", $p, $uid);
      $url = empty($url) ? phive("Cashier")->getSetting('instadebit_vurl') : $url;
      // TODO refactor to remove this, use phive()->post instead.
    return phive("Cashier")->postData($url, $p);
  }

  function verify(){
    phive()->dumpTbl("instadebit_nudge_doc", file_get_contents("php://input"));
    $res = $this->post($_POST);
    phive()->dumpTbl("instadebit_nudge_verification_res", $res);
    $res 	= phive()->decUrl($res);
    if($res['verification_code'] !== '0')
      die("ok");
  }

  public function payout($pw){

    if(phive('Cashier')->getSetting('no_out') === true)
      return true;
    $user = cu($pw['user_id']);
    list($site_name, $site_type) = explode(".", phive()->getSetting('domain'));

    $arr = [
        'merchant_id' 		=> $this->getSetting('instadebit_mid', $user),
        'merchant_pass' 	=> $this->getSetting('instadebit_pwd', $user),
        'user_id' 		    => $user->getSetting('instadebit_user_id'),
        'merchant_user_id' 	=> $user->getId(),
        'txn_type'		    => 'F',
        'merchant_txn_num'	=> $site_name.$pw['id'],
        'txn_amount'		=> $pw['amount'] / 100,
        'txn_currency'		=> $pw['currency']
    ];

    phive()->dumpTbl("instadebit_payout_post", $arr);
    $res = $this->post($arr, phive("Cashier")->getSetting('instadebit_payurl'), $user);
    phive()->dumpTbl("instadebit_payout_result", $res, $user);
    $res = phive()->decUrl($res);
    if($res['txn_status'] == 'S'){
      $pw['ext_id'] = $res['txn_num'];
      phive("SQL")->sh($pw, 'user_id', 'pending_withdrawals')->save('pending_withdrawals', $pw);
      return true;
    }
    return $res['error_code'];
  }

  public function getFormFields(DBUser $user): array
  {
      $hph = $user->getAttr('mobile');
      list($year, $month, $day) = explode('-', $user->getAttr('dob'));

      return [
          'merchant_id'      => $this->getSetting('instadebit_mid', $user),
          'merchant_user_id' => $user->getId(),
          'txn_currency'     => $user->getCurrency(),
          'merchant_sub_id'  => $this->getSetting('instadebit_subid', $user),
          'first_name'       => $user->getAttr('firstname'),
          'last_name'        => $user->getAttr('lastname'),
          'addr_1'           => $user->getAttr('address'),
          'city'             => $user->getAttr('city'),
          'state'            => $user->getMainProvince(),
          'zip'              => $user->getAttr('zipcode'),
          'country'          => $user->getCountry(),
          'hph_area_code'    => substr($hph, 0, 3),
          'hph_local_number' => substr($hph, 3),
          'dob_day'          => $day,
          'dob_month'        => $month,
          'dob_year'         => $year,
      ];
  }
}
