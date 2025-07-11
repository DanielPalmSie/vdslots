<?php

// TODO henrik move this to the Mts class and remove this file, don't forget the settings, move them to casino cashier config.

require_once __DIR__ . '/../../api/PhModule.php';
class WireCard extends PhModule{


    function luhnCheck($number) {
        // Strip any non-digits (useful for credit card numbers with spaces and hyphens)
        $number=preg_replace('/\D/', '', $number);

        // Set the string length and parity
        $number_length=strlen($number);
        $parity=$number_length % 2;

        // Loop through each digit and do the maths
        $total=0;
        for ($i=0; $i<$number_length; $i++) {
            $digit=$number[$i];
            // Multiply alternate digits by two
            if ($i % 2 == $parity) {
                $digit*=2;
                // If the sum is two digits, add them together (in effect)
                if ($digit > 9) {
                    $digit-=9;
                }
            }
            // Total up the digits
            $total+=$digit;
        }

        // If the total mod 10 equals 0, the number is valid
        return ($total % 10 == 0) ? TRUE : FALSE;
    }

    function cleanCardNumber($number){
        return preg_replace('/\D/', '', $number);
    }
    
  function altString(&$user){
    $alts = array(
      'trustly' => array('SE', 'FI'),
      'instadebit' => array('CA'),
      'ecopayz' => array('BR', 'MX', 'NO')
    );
    $alt = 'other';
    $country = $user->getAttr('country');
    foreach($alts as $c => $countries){
      if(in_array($country, $countries)){
        $alt = $c;
        break;
      }
    }
    return $alt;
  }

    function getSixFourAsterisk($num){
        if(empty($num))
            return '';
        $num = $this->cleanCardNumber($num);
        $ffour  = substr($num, 0, 4);
        $fftwo = substr($num, 4, 2);
        $lfour = substr($num, -4);
        //4263 87** **** 1454
        return "$ffour $fftwo".'** **** '.$lfour;
    }

    function getCardType($cnum){
        if(empty($cnum))
            return '';
        switch ($cnum) {
            case(preg_match('/^4/', $cnum) >= 1):
                return 'visa';
            case(preg_match('/^5[1-5]/', $cnum) >= 1):
                return 'mc';
            case(preg_match('/^3[47]/', $cnum) >= 1):
                return 'amex';
            case(preg_match('/^3(?:0[0-5]|[68])/', $cnum) >= 1):
                return 'diners';
            case(preg_match('/^6(?:011|5)/', $cnum) >= 1):
                return 'discover';
            case(preg_match('/^(?:2131|1800|35)/', $cnum) >= 1):
                return 'jcb';
        }

        return 'card';
    }

  function resetOnSuccess(&$user){
    $_SESSION['failed_deposits']   = 0;
    $_SESSION['sms_tries']         = 0;
    $_SESSION['sms_ok']            = true;
    $user->setSetting('n-quick-deposits', 0);
  }

    
  function getDepositGuwid($user, $pci_cnum, $whole_row = false){
    $uid = $user->getId();
    $launch_date = $this->getSetting('launch_date') == '' ? '2010-01-01 00:00:00' : $this->getSetting('launch_date');
    $str = "SELECT * FROM deposits
      WHERE user_id = $uid
      AND `timestamp` > '$launch_date'
      AND card_hash = '$pci_cnum'
      LIMIT 0,1";
    $row = phive('SQL')->loadAssoc($str);
    return $whole_row ? $row : $row['ext_id'];
  }

    function loadEmp(){
      if(phive('Config')->getValue('emp', 'active') == 'yes'){
        return true;
      }
      return false;
    }



}
