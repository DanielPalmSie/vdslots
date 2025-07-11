<?php
class PhiveValidator{
  function __construct($str){
    $this->str = $str;
    $this->error = false;
  }

  static function captchaImg(string $requested_captcha = '', bool $enable_reset = false){
    require_once __DIR__ . '/captcha/simple-php-captcha.php';
    $_SESSION['captcha'] = captcha(array(), $requested_captcha ?? '', $enable_reset);
    return $_SESSION['captcha']['image_src'];
  }

  static function captchaCode(bool $enable_reset = false, string $requested_captcha = ''){
    require_once __DIR__ . '/captcha/simple-php-captcha.php';

    if ($enable_reset) {
        $key = !empty($requested_captcha) ? "_CAPTCHA_$requested_captcha" : "_CAPTCHA";
        $captcha_session = $_SESSION[getLimitAttemptKey($key)];

        if (!empty($captcha_session)) {
            return $captcha_session['config']['code'];
        }
    } else {
        if (!empty($requested_captcha)) {
            return $_SESSION["_CAPTCHA_$requested_captcha"]['config']['code'];
        }
    }

    return $_SESSION['captcha']['code'];
  }

  static function removeCaptchaSessionData()
  {
	  unset($_SESSION['captcha']);
  }

  static function start($str = ''){
    $v = new PhiveValidator($str);
    return $v;
  }

  function reset($str){
    $this->str = $str;
    $this->error = false;
  }

  //
  function validateArr($arr){
    $errors = array();
    foreach($arr as $item){
      $this->reset($item['value']);
      $err = call_user_func_array(array($this, $item['func']), empty($item['params']) ? array() : $item['params']);
      if(!empty($this->error))
        $errors[ $item['field'] ] = $this->error;
    }
    return $errors;
  }

  function setErr($err){
    if(!$this->error)
      $this->error = $err;
  }

    public function iban(){
        $iban = strtolower($this->str);

        $countries = ['al'=>28,'ad'=>24,'at'=>20,'az'=>28,'bh'=>22,'be'=>16,'ba'=>20,'br'=>29,'bg'=>22,'cr'=>21,'hr'=>21,'cy'=>28,'cz'=>24,'dk'=>18,'do'=>28,'ee'=>20,'fo'=>18,'fi'=>18,'fr'=>27,'ge'=>22,'de'=>22,'gi'=>23,'gr'=>27,'gl'=>18,'gt'=>28,'hu'=>28,'is'=>26,'ie'=>22,'il'=>23,'it'=>27,'jo'=>30,'kz'=>20,'kw'=>30,'lv'=>21,'lb'=>28,'li'=>21,'lt'=>20,'lu'=>20,'mk'=>19,'mt'=>31,'mr'=>27,'mu'=>30,'mc'=>27,'md'=>24,'me'=>22,'nl'=>18,'no'=>15,'pk'=>24,'ps'=>29,'pl'=>28,'pt'=>25,'qa'=>29,'ro'=>24,'sm'=>27,'sa'=>24,'rs'=>22,'sk'=>24,'si'=>19,'es'=>24,'se'=>24,'ch'=>21,'tn'=>24,'tr'=>26,'ae'=>23,'gb'=>22,'vg'=>24];

        $chars = ['a'=>10,'b'=>11,'c'=>12,'d'=>13,'e'=>14,'f'=>15,'g'=>16,'h'=>17,'i'=>18,'j'=>19,'k'=>20,'l'=>21,'m'=>22,'n'=>23,'o'=>24,'p'=>25,'q'=>26,'r'=>27,'s'=>28,'t'=>29,'u'=>30,'v'=>31,'w'=>32,'x'=>33,'y'=>34,'z'=>35];

        if(strlen($iban) == $countries[substr($iban, 0, 2)]){

            $moved_char = substr($iban, 4).substr($iban, 0, 4);
            $moved_arr  = str_split($moved_char);
            $str        = "";

            foreach($moved_arr as $key => $value){
                if(!is_numeric($moved_arr[$key])){
                    $moved_arr[$key] = $chars[$moved_arr[$key]];
                }
                $str .= $moved_arr[$key];
            }

            if(bcmod($str, '97') == 1){
                return $this;
            }
        }

        $this->setErr('ibanIncorrect');

        return $this;
    }

  function length($count){
    if(strlen($this->str) < $count)
      $this->setErr('short');
    return $this;
  }

  function exactLength($count){
    if(strlen($this->str) !== $count)
      $this->setErr('exact_length');
    return $this;
  }

  function nothing(){
    if (empty($this->str)) {
        $this->setErr('empty');
    } else if ((filter_var($this->str, FILTER_SANITIZE_STRING)) != $this->str){
        $this->setErr('invalid-chars');
    }
    return $this;
  }

  function nullStr(){
    if($this->str === null || $this->str === '')
      $this->setErr('empty');
    return $this;
  }

  function white(){
    if(preg_match('/\s/', $this->str))
      $this->setErr('white');
    return $this;
  }

  function twoCharGroups(){
      $result = preg_replace(array('/^[a-zA-Z]+$/', '/^\d+$/', '/^\W+$/'), '', phive()->rmWhiteSpace($this->str));
    if($result == '')
      $this->setErr('simple');
    return $this;
  }

  function specials(){
    if(!preg_match('/^[a-zA-Z0-9]+$/', $this->str))
      $this->setErr('specials');
    return $this;
  }

  function nonAlphaNumHyphenUnderscore(){
    if(!preg_match('/^[a-zA-Z0-9-_]+$/', $this->str))
      $this->setErr('nonhyphenunderscore-specials');
    return $this;
  }

  function reqBirthDate() {
    $this->nothing()->birthDate();
    return $this->error;
  }

  function birthDate(){
    if(!preg_match('/^(19|20)[0-9]{2}[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$/', $this->str))
      $this->setErr('birthdate');
    return $this;
  }

  function noFutureDate() {
    $date = str_replace('-', '', $this->str);
    if($date > str_replace('-', '', date('yy-mm-dd')))
      $this->setErr('futureDate');
    return $this;
  }

  function reqEmail() {
    $this->nothing()->email();
    return $this->error;
  }

  function email()
  {
    $localPart = strstr($this->str, '@', true);
    if (strlen($localPart) > 64) {
        $this->setErr('email.local.length.exceeded');
        return $this;
    }

    if (!filter_var($this->str, FILTER_VALIDATE_EMAIL)) {
      $this->setErr('email.invalid.format');
      return $this;
    }

    $maxLength = licSetting('email_length_restrictions') ?? 254;
    if (strlen($this->str) > $maxLength) {
      $this->setErr(tAssoc('email.length.exceeded', ['maxLength' => $maxLength]));
    }

     return $this;
  }

  function reqTelephone(){
    $this->nothing()->telephone();
    return $this->error;
  }

  function telephone(){
    if(preg_match('/[^\d()+]/', $this->str))
      $this->setErr('telephone');
    return $this;
  }

  function reqStrictPassword($length){
    $this->nothing()->strictPassword($length);
    return $this->error;
  }

    function hasUpper(){
        if(!preg_match('/[A-Z]/', $this->str))
            $this->setErr('simple');
        return $this;
    }

    public function hasLower(): PhiveValidator
    {
        if (!preg_match('/[a-z]/', $this->str)) {
            $this->setErr('simple');
        }
        return $this;
    }

    function hasXnumbers($x){
        $before_cnt = strlen($this->str);
        $result     = preg_replace('/\d/', '', $this->str);
        $after_cnt  = strlen($result);
        if($before_cnt - $after_cnt < $x)
            $this->setErr("needs.$x.numbers");
        return $this;
    }

    function reqBirthYear($length)
    {
        $this->length($length)->hasXnumbers($length);;
        return $this->error;
    }
    function reqBirthMonth($length)
    {
        $this->length($length)->hasXnumbers($length);
        return $this->error;
    }

  function strictPassword($length){
      $this->length($length)->noPasswordWhitespace()->hasUpper()->hasLower()->hasXnumbers(2);
      return $this->error;
  }

  function noPasswordWhitespace()
  {
    if (preg_match('/\s/', $this->str))
      $this->setErr('password.no.whitespace');
    return $this;
  }

  function reqStrictUsername($length){
    $this->nothing()->strictUsername($length);
    return $this->error;
  }

  function strictUsername($length){
    $this->length($length)->white()->specials();
    return $this->error;
  }

  function mediumUsername($length){
    $this->length($length)->nonAlphaNumHyphenUnderscore();
    return $this->error;
  }

  function looseUsername($length){
    $this->length($length)->white();
    return $this->error;
  }

  function passwordConfirm($password){

    if($password != $this->str)
      $this->setErr('mismatch');

    return $this;
  }

  function isnumeric(){

    if (!empty($this->str)) {

      if(!is_numeric($this->str))
        $this->setErr('notNumeric');
    }

    return $this;
  }

    function notlessthan( $min ) {

        $this->isnumeric();

        if ( intval($this->str) < $min )
            $this->setErr('notlessthan');

        return $this;
    }

    function ipCheckReq() {

        $this->nothing();

        if(filter_var($this->str, FILTER_VALIDATE_IP) === false)
            $this->setErr('ipCheckReq');

        return $this;
    }

    function reqNumber() {

        $this->nothing()->isnumeric();
        return $this->error;
    }

    function nid($u_obj){
        switch($u_obj->getCountry()){
            case 'SE':
                if(strlen($this->str) != 12){
                    $this->setErr('nidWrongLength');
                }

                $n         = null;
                $check_sum = 0;
                $ms        = [2, 1, 2, 1, 2, 1, 2, 1, 2];
                $to_check  = array_slice(str_split($this->str), 2, 11);

                for ($i = 0; $i < 9; $i++) {
                    $res = $ms[$i] * (int)$to_check[$i];
                    if($res > 9){
                        $arr = str_split((string)$res);
                        $res = (int)$arr[0] + (int)$arr[1];
                    }

                    $check_sum += $res;
                }

                $last_digit = 10 - (int)substr((string)$check_sum, -1);
                $last_digit = $last_digit == 10 ? 0 : $last_digit;

                if($last_digit != (int)substr($this->str, -1)){
                    $this->setErr('wrongChecksum');
                }

                break;
            case 'FI':
                $this->str = strtoupper($this->str);

                if(strlen($this->str) != 11){
                    $this->setErr('nidWrongLength');
                }

                list($year, $month, $day) = explode('-', $u_obj->getAttr('dob'));
                $century                  = substr($year, 0, 2);
                $year                     = substr($year, -2);
                $bdate                    = $day.$month.$year;

                if(substr($this->str, 0, 6) != $bdate){
                    $this->setErr('nidDobMismatch');
                }

                $map = [
                    '18' => '+',
                    '19' => '-',
                    '20' => 'A'
                ];

                if($map[$century] != $this->str[6]){
                    $this->setErr('wrongCenturySign');
                }

                $id = substr($this->str, 7, 3);

                if((int)$id % 2 == 0 && $u_obj->getAttr('sex') != 'Female'){
                    $this->setErr('wrongSex');
                }

                $checksum_str  = "0123456789ABCDEFHJKLMNPRSTUVWXY";
                $for_checksum  = $bdate.$id;
                $n             = $for_checksum % 31;
                if($checksum_str[$n] != $this->str[10]){
                    $this->setErr('wrongChecksum');
                }

                break;
        }
        return $this;
    }

    function amount() {
        if(!preg_match("/^\d+(\.\d{1,2})?$/", $this->str))
            $this->setErr('cashier.error.amount');
        return $this;
    }

    function bank_name() {
        if(!preg_match('/^[A-Za-z0-9\s.\'-]+$/', $this->str))
            $this->setErr('invalid.bank.name');
        return $this;
    }

    function swift_bic() {

        $swift_bic = $this->str;

        // Validate the length of the BIC
        if (strlen($swift_bic) != 8 && strlen($swift_bic) != 11) {
            $this->setErr('invalid.swift.bic.length'); // BIC should be either 8 or 11 characters long
        }

        // Check if the BIC has a valid format
        if (!preg_match('/^[A-Z]{6}[A-Z0-9]{2}([A-Z0-9]{3})?$/', $swift_bic)) {
            $this->setErr('invalid.swift.bic.format'); // BIC should follow the specified pattern: 6 uppercase letters followed by 2 alphanumeric characters (optional 3 alphanumeric characters)
        }

        return $this;
    }

    function validateTextField($regex, $minLength = 1, $maxLength = 50, $errorMessage) {

        $decodedStr = html_entity_decode($this->str, ENT_QUOTES, 'UTF-8');

        $length = mb_strlen($decodedStr);

        // Length validation
        if ($length < $minLength || $length > $maxLength) {
            $this->setErr($errorMessage);
            return $this;
        }

        // Regex validation
        if (!preg_match($regex, $decodedStr)) {
            $this->setErr($errorMessage);
            return $this;
        }

        return $this;
    }
}
