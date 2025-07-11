<?php
namespace App\Classes\FormBuilder;

class Validate implements ValidateInterface {
  private $_m_aErrors = array();
  private $_m_aPatterns = array(
    'email' => '/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,4}$/',
    'dob' => '/^(19|20)[0-9]{2}[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$/',
    'phone' => '/[^\d()+]/'
  );
  
  public function required($p_sValue){
    $this->_addError('required', $p_sValue !== '');
  }
  
  public function number($p_sValue){
    $this->_addError('number', ctype_digit($p_sValue));
  }
  
  public function alnum($p_sValue){
    $this->_addError('alnum', ctype_alnum($p_sValue));
  }
  
  public function alpha($p_sValue){
    $this->_addError('alpha', ctype_alpha($p_sValue));
  }
  
  public function email($p_sValue){
    $this->_addError('email', $this->_pattern($p_sValue, 'email'));
  }
  
  public function max($p_sValue, $p_iLength){
    $this->_addError('max', (strlen($p_sValue) > $p_iLength));
  }
  
  public function min($p_sValue, $p_iLength){
    $this->_addError('min', (strlen($p_sValue) < $p_iLength));
  }

  public function dob($p_sValue){
  
  }

  public function phone($p_sValue){
    $this->_addError('phone', $this->_pattern($p_sValue, 'phone'));
  }

  public function getErrors(){
    return $this->_m_aErrors;
  }
  
  private function _pattern($p_sValue, $p_sPattern){
    if (!preg_match($this->_m_aPatterns[$p_sPattern], $p_sValue)) {
      return false;
    }
    return true;
  }
  
  private function _addError($p_sMethod, $p_bResult){
    if($p_bResult === false){
      $this->_m_aErrors[$p_sMethod] = false;
    }
  }
}