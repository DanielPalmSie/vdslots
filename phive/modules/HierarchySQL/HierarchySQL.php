<?php
require_once __DIR__ . '/../../modules/Hierarchy/Hierarchy.php';

class HierarchySQL extends Hierarchy{

  function getByIds($id_arr){
    $ids = phive('SQL')->makeIn($id_arr);
    return phive('SQL')->loadArray("SELECT * FROM {$this->getSetting('table_entries')} WHERE id IN($ids)");
  }
  
  public function getParentID($id){
    return (float)phive('SQL')->getValue("SELECT `{$this->getKey('parent')}` FROM `{$this->getSetting('table_entries')}` WHERE `{$this->getKey('id')}` = '$id' LIMIT 1");
  }
  
  public function getList(){
    $prio = $this->getKey('priority');
    $p_str = (($prio)?" ORDER BY `$prio`":"");
    $table = $this->getSetting('table_entries');
    phive('SQL')->query("SELECT * FROM `$table`" . $p_str);
    return phive('SQL')->fetchArray('ASSOC');		
  }	
  
  public function entryExists($alias, $parent){
    $alias_key = $this->getKey('alias');
    if ($alias_key == "")
      return false;
    
    $sql = phive('SQL');
    $table = $this->getSetting('table_entries');
    $query = "SELECT `{$this->getKey('id')}` FROM `$table` WHERE `$alias_key` LIKE {$sql->escape($alias)} AND `{$this->getKey('parent')}` = {$sql->escape($parent)} LIMIT 1";
    $sql->query($query);
    if (phive('SQL')->numRows())
      return phive('SQL')->result();
    else
      return false;
  }
  
  public function updateEntry(array $entry){
    $sql = phive('SQL');
    $id = 0;
    if(!$this->requireKeys('id'))
      return false;
    
    if($entry[$this->getKey('id')] == 0){
      unset($entry[$this->getKey('id')]);
      $where = null;
    }else
      $where = "`{$this->getKey('id')}` = " . $sql->escape($entry[$this->getKey('id')]);
    $table = $this->getSetting('table_entries');

    $id = $sql->insertArray($table, $entry, $where);
    if ($this->requireKeys('priority') && $entry[$this->getKey('id')] == 0)
      $sql->query("UPDATE `$table` SET `{$this->getKey('priority')}`= {$sql->escape($id)} WHERE `{$this->getKey('id')}` = '$id'");
    return $id == 0 ? true : $id; 
  }
  
  public function deleteEntry($entry_id){
    if (!$this->requireKeys('id'))
      return false;
    
    if ($entry_id === null)
      return new PhMessage(PHM_ERROR, "Entry ID is null.");
    
    $table = $this->getSetting('table_entries');
    phive('SQL')->query("SELECT COUNT(*) FROM $table WHERE `{$this->getKey('parent')}` = '$entry_id'");
    if (($cnt = phive('SQL')->result()) > 0)
      return new PhMessage(PHM_ERROR, "Entry is parent for $cnt entry(s) and can't be removed. $sql");

    $ret = phive('SQL')->query("DELETE FROM $table WHERE `{$this->getKey('id')}` = '$entry_id'");

    if($ret)
      return new PhMessage(PHM_OK);
    else
      return new PhMessage(PHM_ERROR, "Could not delete entry, check logs.");
  }
  
  public function getEntry($entry_id){
    if (!$this->requireKeys('id'))
      return false;
    
    $table = $this->getSetting('table_entries');
    phive('SQL')->query("SELECT * FROM `$table` WHERE `{$this->getKey('id')}` = '$entry_id'");
    return phive('SQL')->fetch('ASSOC');
  }
  
  public function getChildren($entry_id_or_alias=0){
    if (!$this->requireKeys('id', 'parent'))
      return false;
    
    if ($entry_id_or_alias === null)
      $entry_id_or_alias = 0;
    
    $table = $this->getSetting('table_entries');

    if (is_int($entry_id_or_alias)){
      $prio = $this->getKey('priority');
      $p_str = (($prio)?" ORDER BY `$prio`":"");

      $id = $entry_id_or_alias;
      phive('SQL')->query("SELECT * FROM `$table` WHERE `{$this->getKey('parent')}` = '$id'" . $p_str);
      return phive('SQL')->fetchArray('ASSOC');
    }else if (($alias_key = $this->getKey('alias')) != ""){
      $prio = $this->getKey('priority');
      $p_str = (($prio)?" ORDER BY b.`$prio`":"");
      $alias = $entry_id_or_alias;
      phive('SQL')->query("SELECT * FROM `$table` a, `$table` b WHERE a.{$this->getKey('id')} = b.{$this->getKey('parent')} AND a.`$alias_key` = '$alias'" . $p_str);
      return phive('SQL')->fetchArray('ASSOC');			
    }else
    trigger_error("Could not produce result with HierarchySQL::getChildren()", E_USER_WARNING);
  }

  public function numChildren($entry_id_or_alias=0){
    if (!$this->requireKeys('id', 'parent'))
      return false;
    
    if ($entry_id_or_alias === null)
      $entry_id_or_alias = 0;
    
    $table = $this->getSetting('table_entries');

    if (is_int($entry_id_or_alias)){
      $id = $entry_id_or_alias;
      phive('SQL')->query("SELECT count(*) FROM `$table` WHERE `{$this->getKey('parent')}` = '$id'");
      return phive('SQL')->result();
    }else if (($alias_key=$this->getKey('alias'))!=""){
      $alias = $entry_id_or_alias;
      phive('SQL')->query("SELECT count(*) FROM `$table` a, `$table` b WHERE a.{$this->getKey('id')} = b.{$this->getKey('parent')} AND a.`$alias_key` = '$alias'");
      return phive('SQL')->result();			
    }else
      trigger_error("Could not produce result with HierarchySQL::numChildren()", E_USER_WARNING);
  }
  
  public function move($dir='up', $entry_id){
    if (!$this->requireKeys('id', 'priority', 'parent'))
      return false;

    $entry = $this->getEntry($entry_id);
    $table = $this->getSetting('table_entries');
    
    $k = $this->getKeys();
    $ordering = $dir == 'up' ? 'DESC' : '';
    $priority_op = $dir == 'up' ? '<' : '>';
    phive('SQL')->query("SELECT `{$k['id']}` FROM `$table` WHERE `{$k['parent']}` = '{$entry[$k['parent']]}' AND `{$k['priority']}` $priority_op '{$entry[$k['priority']]}' ORDER BY `{$k['priority']}` $ordering LIMIT 1");    
    $id = phive('SQL')->result();
    return $this->swapPriority($entry_id, $id);
  }
  
  function getOneByAlias($alias){
    return phive('SQL')->loadObject("SELECT * FROM {$this->getSetting('table_entries')} WHERE alias = '$alias'");
  }
  
  public function swapPriority($entry_id1, $entry_id2){
    if (!$this->requireKeys('priority', 'parent'))
      return false;
    
    $entry1 = $this->getEntry($entry_id1);
    $entry2 = $this->getEntry($entry_id2);

    if ($entry1[$this->getKey('parent')] != $entry2[$this->getKey('parent')])
      return false;
    
    $a = $entry1[$this->getKey('priority')];
    $entry1[$this->getKey('priority')] = $entry2[$this->getKey('priority')];
    $entry2[$this->getKey('priority')] = $a;
    
    $this->updateEntry($entry1);
    $this->updateEntry($entry2);
    return true;
  }
}
