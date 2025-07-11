<?php
require_once __DIR__ . '/../../api/PhModule.php';

class Hierarchy extends PhModule{
  private $keys = array();
  public function __construct(){ $this->setKeys();}
  
  public function setKeys($id_key="", $parent_key="", $alias_key="", $name_key="", $priority_key=""){
    $this->keys['id'] 		= $id_key;
    $this->keys['parent'] 	= $parent_key;
    // optional
    $this->keys['alias'] 	= $alias_key;
    $this->keys['name'] 	= $name_key;
    $this->keys['priority'] = $priority_key;
  }
  
  public function getKey($key) { return $this->keys[$key]; }
  public function getKeys() { return $this->keys; }
  
  public function requireKeys(){
    $args = func_get_args();
    foreach ($args as $arg){
      if (!$this->keys[$arg]){
	return false;
      }
    }
    return true;
  }

  function getParentID($id){}
  function getList(){}
  public function getHierarchy(){
    $all = $this->getList();
    $result = array();
    $this->buildHierarchy($result, $all);
    return $result;
  }

  protected function buildHierarchy(&$result, $entries, $parent=0, $depth=0){
    $id_key = $this->getKey('id');
    $parent_key = $this->getKey('parent');

    if ($id_key == "" && $parent_id == ""){
      trigger_error("Can't use Hierarchy::buildHierarchy without calling setKeys().", E_USER_ERROR);
      return;
    }
    
    foreach ($entries as $entry){
      if ($entry[$parent_key] == $parent){
	$entry['depth'] = $depth;
	$result[] = $entry;
	$this->buildHierarchy($result, $entries, $entry[$id_key], 1+$depth);
      }
    }
  }

  public function getListboxData(){
    $all = $this->getList();
    $result = array();
    $result[0] = "/ (root)";
    $this->buildListbox($result, $all);
    return $result;
  }
  
  private function buildListbox(&$result, $entries, $parent=0, $depth=0){
    $keys = $this->getKeys();
    if ($keys['id'] == "" || $keys['parent'] == "" || $keys['alias'] == ""){
      trigger_error("Can't use Hierarchy::buildListbox without calling setKeys().", E_USER_ERROR);
      return;
    }
    
    foreach ($entries as $entry){
      if ($entry[$keys['parent']] == $parent){
	$indent = "";
	for($i=0; $i<$depth+1; ++$i)
	  $indent .= "&nbsp;&nbsp;";
	$result[$entry[$keys['id']]] = $indent . $entry[$keys['alias']];
	$this->buildListbox($result, $entries, $entry[$keys['id']], 1+$depth);
      }
    }
  }
  
  public function isValidParent($id, $parent_id){
    if ($id == $parent_id)
      return false;
    
    if ($parent_id == 0)
      return true;
    
    $ids = array($id, $parent_id);
    $last_id = $parent_id;
    $i=0;
    while (true){
      $id = $this->getParentID($last_id);

      if ($id == 0)
	return true;
      
      if (in_array($id, $ids))
	return false;
      
      array_push($ids, $id);
      $last_id = $id;
      
      if ($i++>100){
	trigger_error("Exceeded loop maximum", E_USER_ERROR);
	break;
      }
    }
    
    return true;
  }
  
  function isEmpty($tree){
    $empty = true;
    foreach($tree as $val){
      $empty = is_array($val) ? $this->isEmpty($val) : empty($val);
      if(!$empty)
	return false;
    }
    return $empty;
  }
  
  function isTwig($tree){
    foreach($tree as $val){
      if(is_array($val))
	return false;
    }
    return true;
  }
  
  function twigIsEmpty($twig){
    foreach($tree as $val){
      if(!empty($val))
	return false;
    }
    return true;
  }
  
  function pruneTree($tree){
    $rtree = array();
    foreach($tree as $key => $val){
      if(is_array($val)){
	$tmp = $this->pruneTree($val);
	if(!$this->isEmpty($tmp))
	  $rtree[$key] = $tmp;
      }else if(!empty($val))
      $rtree[$key] = $val;
    }
    return $this->isEmpty($rtree) ? array() : $rtree;
  }
  
}
