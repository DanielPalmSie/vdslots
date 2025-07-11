<?php
class Article{
  public $article_id = null;
  public $country = null;
  public $user_id = null;
  public $headline = null;
  public $subheading = null;
  public $content = null;
  public $time_created = null;
  public $time_edited = null;
  public $visits = null;
  public $pParent = null;
  public $exists = false;
  public $image_path = null;
  public $status = null;
  public $abstract = null;
  public $category_alias = null;

  public function __construct($pParent, $data=null,$user_id=null){
    $this->pParent = $pParent;
    $keys = array(
      'article_id',
      'country',
      'time_edited',
      'time_created',
      'content',
      'headline',
      'thread_id',
      'user_id',
      'subheading',
      'visits',
      'status',
      'image_path',
      'abstract',
      'category_alias',
      'url_name',
      'meta_title',
      'meta_keywords',
      'meta_description',
      'article_alias',
      'game_ref'
    );
    if ($data === null){
      $iarr = array("time_created" => phive()->hisNow(), "user_id" => $user_id);
      $tbl = $this->pParent->getSetting("DB_ARTICLES");
      if(phive("SQL")->insertArray($tbl, $iarr)){
        $this->article_id = phive("SQL")->insertBigId();
        $this->exists = true;
      }else
        $this->exists = false;
      
    }else if (!is_array($data)){
      $this->article_id = $data;
      phive("SQL")->query("SELECT * FROM ".$this->pParent->getSetting("DB_ARTICLES")." WHERE article_id = ".phive('SQL')->escape($this->article_id));
      $this->contents = phive("SQL")->fetch('ASSOC');
      if ($this->contents !== false){
        $this->populateMe($this->contents, $keys);
        $this->exists = true;
      }else{
        $this->exists = false;
      }
    }else{
      $this->populateMe($data, $keys);
      $this->exists = true;
    }
  }

  function populateMe($data, $keys){

      if(phive()->moduleExists('Currencer') && phive("Currencer")->getSetting('multi_currency') == true)
          $map = array('headline', 'subheading', 'abstract', 'content');
      
      foreach($keys as $key){
          //if(in_array($key, $map))
          //    $this->$key = phive("Localizer")->handleReplacements($data[$key]);
          //else
              $this->$key = $data[$key];
      }
  }

  function setValue($field, $val, $sanitize = true){
    if($sanitize)
      $this->$field = filter_var($val, FILTER_SANITIZE_STRING);
    $tbl = $this->pParent->getSetting("DB_ARTICLES");
    $arr = array($field => $val);
    $where = "article_id = ".phive('SQL')->escape($this->article_id, false);
      phive("SQL")->insertArray($tbl, $arr, $where);
  }

  function setUser($user){
    $this->user_id = $user->getId();
    $this->setValue('user_id', $this->user_id, false);
  }
  
  function setHeadline($headline){ $this->setValue('headline', $headline); }
  function setSubheading($subheading){ $this->setValue('subheading', $subheading); }
  function setContent($content){ $this->setValue('content', $content); }
  function setTimeEdited($time_edited){ $this->setValue('time_edited', $time_edited); }
  function setCategoryAlias($category_alias){ $this->setValue('category_alias', $category_alias); }
  function setCountry($country){ $this->setValue('country', $country); }
  function setURLName($url_name){ $this->setValue('url_name', $url_name); }
  function setMetaTitle($meta_title){ $this->setValue('meta_title', $meta_title); }
  function setMetaKeywords($meta_keywords){ $this->setValue('meta_keywords', $meta_keywords); }
  function setMetaDescription($meta_description){ $this->setValue('meta_description', $meta_description); }

  function setAlias($forced_alias = null) {
    if ($forced_alias != null) {
      $article_alias = $forced_alias;
    } else {
      $article_alias = strtolower(preg_replace("/[^a-z0-9]/i", "-", $this->headline));
    }

    if(strlen($article_alias) == 0) {
      trigger_error("Article alias is empty, based on headline '{$this->headline}'");
      return false;
    }

    $check_alias = $article_alias;
    $count = 0;
    while ($this->pParent->getArticleByAlias($check_alias)) {
      $count++;
      $check_alias = $article_alias . "-" . $count;
    }
    $article_alias = $check_alias;

    $this->setValue('article_alias', $article_alias);
    return $this->article_alias;
  }

  function getAlias() { return $this->article_alias; }
  function getIdAlias() { return "{$this->article_id}/{$this->article_alias}"; }
  function getVisits(){ return $this->visits; }
  function getStatus(){ return $this->status; }
  function getTimeEdited(){ return $this->time_edited; }
  function getTimeCreated(){    return $this->time_created; }
  function getContent(){    return $this->content; }
  function getHeadline(){    return $this->headline; }
  function getSubheading(){    return $this->subheading; }

  function getUser(){
    if ($this->user_id != null)
      return cu($this->user_id);
    return null;
  }
  
  function getUserId(){ return $this->user_id; }
  function getId(){    return $this->article_id;  }
  function getURL(){
    $str = $this->getUrlName();
    if ($str != '') $str .= '/';
    return $this->getId().'/'.$str;
  }
  
  function getCategoryAlias(){ return $this->category_alias; }

  function remove(){
    $str = "DELETE FROM ".$this->pParent->getSetting("DB_ARTICLES")." WHERE article_id = ".phive("SQL")->escape($this->article_id,false);
    phive("SQL")->query($str);
    $this->exists = false;
  }

  function getImagePath(){ return $this->image_path; }
  
  function setImagePath($image_path){
    $this->image_path = $image_path;
    $this->setValue('image_path', $this->image_path, false);
    //phive("SQL")->insertArray($this->pParent->getSetting("DB_ARTICLES"),array("image_path" => $this->image_path),"article_id = ".phive('SQL')->escape($this->article_id,false));
  }
  
  function getAbstract(){ return $this->abstract; }
  function exists(){    return $this->exists;  }
  function getCountry(){    return $this->country;  }
  function getURLName(){    return $this->url_name;  }
  function getMetaTitle(){    return $this->meta_title;  }
  function getMetaKeywords(){    return $this->meta_keywords;  }
  function getMetaDescription(){    return $this->meta_description;  }

  function setStatus($status){
    $this->setValue('status', $status);
    //$this->status = filter_var($status, FILTER_SANITIZE_STRING);
    
    //phive("SQL")->insertArray($this->pParent->getSetting("DB_ARTICLES"),array("status" => $this->status),"article_id = ".phive('SQL')->escape($this->article_id,false));
  }

  function setAbstract($abstract){
    $this->setValue('abstract', $abstract);
    //$this->abstract = filter_var($abstract, FILTER_SANITIZE_STRING);
    //phive("SQL")->insertArray($this->pParent->getSetting("DB_ARTICLES"),array("abstract" => $this->abstract),"article_id = ".phive('SQL')->escape($this->article_id,false));
  }

  function setAttr($name, $value){
    $tbl 	= $this->pParent->getSetting("DB_ARTICLES");
    $id 	= $this->getId();
    $str        = "UPDATE $tbl SET $name = '$value' WHERE article_id = $id";
      phive('SQL')->query($str);
    $this->$name = $value;
  }

  function getAttr($name){
    if(empty($this->$name)){
      $tbl = $this->pParent->getSetting("DB_ARTICLES");
      $id  = $this->getId();
      $this->$name = phive('SQL')->queryAnd("SELECT $name FROM $tbl WHERE article_id = $id")->result();
    }

    return $this->$name;
  }

  function setDescription($description){
    $this->setValue('description', $description);
    //$this->description = $description;
    //phive("SQL")->insertArray($this->pParent->getSetting("DB_ARTICLES"),array("description" => $this->description),"article_id = ".phive('SQL')->escape($this->article_id,false));
  }
}
