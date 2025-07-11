<?php
class Group {
  
  private $pParent;
  private $groupId;
  private $name;
  
    function __construct($pParent, $groupIdOrArray, $name=null) {
        $this->edit_p = 'edit.groups';        
        if (is_array($groupIdOrArray)){
            $this->pParent = $pParent;
            $this->groupId = $groupIdOrArray['group_id'];
            $this->name = $groupIdOrArray['name'];
            return;
        }
        
        if (!($pParent instanceof UserHandler)){
            echo "Error trying to get Group. Invalid user handler.";
            return;
        }
        
        if ($groupIdOrArray == null && $name == null){
            echo "Must enter either group id or group name to construct a Group object.";
            return;
        }
        
        $this->pParent	= $pParent;
        $this->groupId	= $groupIdOrArray;
        $this->name = $name;
        
        $this->fetch();
    }
    
  private function fetch() {
    $sql = phive('SQL');
    if ($this->groupId)
      $where = "`group_id`=" . $sql->escape($this->groupId);
    else if ($this->name)
      $where = "`name`=" . $sql->escape($this->name);
    $q = "SELECT group_id, name FROM {$this->pParent->getSetting('db_groups')} WHERE $where LIMIT 1";
    $array = $sql->loadAssoc($q);

    if(empty($array)){
      $this->groupId = 0;
      return false;
    }else{
      $this->groupId	= $array['group_id'];
      $this->name	= $array['name'];
      return true;
    }
  }
  
  function getName() { return $this->name; }
  
  function getId() { return $this->groupId; }
  
    function editName($name){
        pOrDie('edit.groups');
        $sql   = phive('SQL');
        // TODO henrik replace with $table = 'groups';
        $table = $this->pParent->getTableName(GROUPS);
        $r     = $sql->query("UPDATE $table SET `name`= $sql->escape($name) WHERE `group_id`=" . $sql->escape($this->getId()));
        if ($r)
            $this->name = $name;
        return $r;
    }	

  function getSelSql($sel){
    $ut	= $this->pParent->getSetting("db_users");
    $mt	= $this->pParent->getSetting("db_groups_members");
    return "SELECT $sel FROM $ut, $mt WHERE $ut.id = $mt.user_id AND $mt.group_id = {$this->groupId} ORDER BY $ut.username";
  }
 
  function memberCount(){
    $ut	= $this->pParent->getSetting("db_users");
    $q = $this->getSelSql("count($ut.id)");
    return phive('SQL')->getValue($q);
  }

  function getMembers($extraSQL = null) {
    $ut	= $this->pParent->getSetting("db_users");
    $mt	= $this->pParent->getSetting("db_groups_members");
    $q = "SELECT $ut.id FROM $ut, $mt WHERE $ut.id = $mt.user_id AND $mt.group_id = {$this->groupId} ORDER BY $ut.username";
    if($extraSQL) 
      $q .= " " . $extraSQL;
    $rArray = phive('SQL')->loadArray($q);
    foreach($rArray as $r)
      $Members[] = $this->pParent->getUser($r['id']);
    return $Members;
  }
  
  function isMember($User) {
    $role = $this->getUserRole($User);
    return ($role == GROUP_ROLE_MEMBER || $role == GROUP_ROLE_ADMIN  || $role == GROUP_ROLE_FOUNDER);
  }
  
  function getUserRole($User) {
    $q = "SELECT role FROM {$this->pParent->getSetting('db_groups_members')} WHERE `group_id`= {$this->getId()} AND `user_id`= {$User->getId()} LIMIT 1";
    return phive('SQL')->getValue($q);
  }
  
  function addMember($User, $role) {}
  
  function removeMember($User) {}

    // TODO henrik remove
  function getTableName($table_id){ return $this->pParent->getTableName($table_id); }
}

