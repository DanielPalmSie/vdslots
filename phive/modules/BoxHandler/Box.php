<?php

class Box{
  public $attributes, $box_id;
  public $sql, $bh;

  function __construct($box_id = null){
    $this->box_id = $box_id;
    $this->sql = phive("SQL");
    $this->bh = phive("BoxHandler");
    $this->populateMe();
  }

  function populateMe(){
    if (is_null($this->box_id)) return false;
    phive('SQL')->query("SELECT attribute_name, attribute_value FROM {$this->bh->getSetting("DB_BOXATTRIBUTES")} WHERE box_id = {$this->getId()} ORDER BY attribute_name ASC");
    $rows = phive('SQL')->fetchArray();
    foreach($rows as $row)
      $this->attributes[$row["attribute_name"]] = $row["attribute_value"];
  }

  public function getId() {
    return $this->box_id;
  }

  public function setId($box_id = null) {
    $this->box_id = $box_id;
    $this->populateMe();
  }

  public function getType() {
    return get_class($this);
  }

  function getAttribute($name) {
    return isset($this->attributes[$name]) ? $this->attributes[$name] : '';
  }

  function attributeIsSet($name) {
    return isset($this->attributes[$name]);
  }

  function getAttributes() {
    return $this->attributes;
  }

  function setAttribute($name, $value){
    $prev_value = $this->getAttribute($name);

    $entry = array("box_id" => $this->getId(), "attribute_name" => $name, "attribute_value" => $value);

    if(phive('SQL')->insertArray($this->bh->getSetting("DB_BOXATTRIBUTES"), $entry, null, true) !== false) {
      if ($value !== $prev_value) {
        $this->sendAttributeChangeMail($name, $prev_value, $value);
      }

      $this->attributes[$name] = $value;
      return true;
    }else
      return false;
  }

  function deleteAttribute($name){
    unset($this->attributes[$name]);
    $name = phive('SQL')->escape($name);
    return phive('SQL')->query("DELETE FROM {$this->bh->getSetting("DB_BOXATTRIBUTES")} WHERE box_id = {$this->getId()} AND attribute_name = $name");
  }

  function printCSS(){}

  public function is404($args){
    if(count($args) > 0)
      return true;
    return false;
  }

  function printInstanceJS(){	}

  public function init(){}
  public function onAjax(){}

  function boxHtml($box_name){
    require_once __DIR__ . '/../../../'.$this->getSetting('site_folder')."/boxes/$box_name.php";
    $box = new $box_name();
    $box->init();
    ob_start();
    $box->printHTML();
    $content = ob_get_contents();
    ob_clean();
    return $content;
  }

  function callModule($m_name, $m_func){
    return phive($m_name)->$func();
  }

  function getParsedContent(){
    $rstr = '';
    foreach(explode('{}', $this->getContent()) as $piece){
      if(preg_match('|^box:|', $piece)){
	$tmp 	= explode(':', $piece);
	$rstr 	.= $this->boxHtml($tmp[1]);
      }else if(preg_match('|^module:|', $piece)){
	$tmp 	= explode(':', $piece);
	$rstr 	.= $this->boxHtml($tmp[1], $tmp[2]);
      }else
      $rstr 	.= $piece;
    }
    return $rstr;
  }

  public function printHTML(){}

  function getCompatibleContainers()
  {
    return ALL_CONTAINERS;
  }

  function redir($url){
    header("HTTP/1.1 302");
    header("Location: ".$url);
    header("Connection: close");
    exit;
  }

  private function sendAttributeChangeMail($field, $prev_value, $current_value): void
  {
    $email = phive('MailHandler2')->getSetting('CONFIG_MAIL');
    $replacers = [
      '__TIMESTAMP__' => date('Y-m-d H:i:s'),
      '__MADE-BY__' => cu()->getUsername(),
      '__NAME__' => $field,
      '__BOX-NAME__' =>  static::class . ' (Id: ' . $this->getId() . ')',
      '__OLD-VALUE__' => $prev_value,
      '__NEW-VALUE__' => $current_value,
    ];

    phive('MailHandler2')->sendMailToEmail('box-attribute.change', $email, $replacers);
  }
}


class BOX_ERROR{
  private $box_id;
  public function __construct($box_type, $box_id, $err = ""){
    $this->box_id = $box_id;
  }
  public function getType(){return get_class($this);}
  public function getId(){return $this->box_id;}
  public function __call($name, $args){}
  public function __get($name){}
  public function __set($name, $value){}
}
