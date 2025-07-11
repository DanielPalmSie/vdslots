<?php
require_once __DIR__ . '/../../modules/HierarchySQL/HierarchySQL.php';

class CategoryHandler extends HierarchySQL{
  
  public function __construct(){
    $this->table = $this->getSetting('table_entries');
    $this->setKeys("id", "parent_id", "alias", "name", "priority");
  }
  
  public function getCategory($id) { return $this->getEntry($id); }
  
  public function getParent($cur_id){
    return phive('SQL')->queryAnd("SELECT * FROM `{$this->table}` WHERE `parent_id` = $cur_id")->result();
  }
  
  public function getByIdOrAlias($cur_id_alias){
    if(empty($cur_id_alias)) return false;
    $query = "SELECT * FROM `{$this->table}` WHERE ";
    if(!is_numeric($cur_id_alias))
      $query .= "`alias` = '$cur_id_alias'";
    else
      $query .= "`id` = $cur_id_alias";
    return phive('SQL')->queryAnd($query)->fetch('ASSOC');
  }

  public function getPath($cur_id, $use_id = false){
    $cur_alias 		= $this->getAliasById($cur_id);
    $path 			= array();
    do{
      $path[] 	= $cur_alias;
      $cur_id 	= $this->getParentID($cur_id);
      $cur_alias 	= $use_id ? $cur_id : $this->getAliasById($cur_id);
    }while($cur_alias !== false);
    
    return implode('/', array_reverse($path));
  }

  public function getIdPath($cur_id){
    return str_replace('/', ',', $this->getPath($cur_id, true));
  }

  public function getChildrenIds($cur_id, $as_array = false){
    foreach($this->getChildren($cur_id) as $child)
      $rarr[] = $child['id'];
    if(empty($rarr)) return null;
    return $as_array ? $rarr : implode(',', $rarr);
  }

  public function getTreeIds($cur_id, $as_array = false){
    $children_ids = $this->getChildrenIds($cur_id);
    if(empty($children_ids))
      return $as_array ? array($cur_id) : $cur_id;
    
    if($as_array)
      array_unshift($children_ids, $cur_id);
    else
      $children_ids = "$cur_id,$children_ids";
    
    return $children_ids;
  }

  public function getAliasById($id){
    if($id == 0)
      return false;
    $element = $this->getEntry($id);
    return $element['alias'];
  }


  function getRootCategories(){
    return phive('SQL')->loadArray("SELECT * FROM categories WHERE parent_id = 0 ORDER BY priority ASC");
  }

  public function getChildren($cur_id_alias = null){
    if(is_array($cur_id_alias))
      $cur_cat = $cur_id_alias;
    else
      $cur_cat = $this->getByIdOrAlias($cur_id_alias);
    return empty($cur_cat) ? false : phive('SQL')->loadArray("SELECT * FROM `{$this->table}` WHERE `parent_id` = {$cur_cat['id']}");
  }

  public function getCurFromPath($path){
    $args		= explode('/', $_SERVER['REQUEST_URI']);
    $cur_alias 	= array_pop($args);
    return $this->getByIdOrAlias($cur_alias);
  }
}
