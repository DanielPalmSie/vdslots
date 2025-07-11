<?php
require_once __DIR__ . "/../../api/PhModule.php";

class Permission extends PhModule{

  public function getAvailableTags($searchString = '%'){
    $searchString = phive("SQL")->escape($searchString);
    phive("SQL")->query("SELECT * FROM {$this->getSetting('TAGS_TABLE')} WHERE tag LIKE $searchString");
    return phive("SQL")->fetchArray();
  }

  public function tagExists($tag){
    phive("SQL")->query("SELECT count(*) FROM `{$this->getSetting('TAGS_TABLE')}` WHERE tag = " .phive("SQL")->escape($tag));
    return phive("SQL")->result();
  }

  function getUsersGroups($uid, $only_ids = false){
    $ud  = ud($uid);
    $sql = "SELECT * FROM groups";
    if($ud['username'] != 'admin')
      $sql .= " WHERE group_id IN(SELECT group_id FROM groups_members WHERE user_id = $uid)";
    $res = phive('SQL')->loadArray($sql);
    if($only_ids)
      return phive()->arrCol($res, 'group_id');
    return $res;
  }

  public function getAllPermissions($userOrGroup){
    if($userOrGroup instanceof Group){
      $table = $this->getSetting("GROUP_TABLE");
      $id_type = 'group_id';
    }else if(is_object($userOrGroup)){
      $table = $this->getSetting("USER_TABLE");
      $id_type = 'user_id';
    }else{
      trigger_error("Parameter must be either User or Group", E_USER_ERROR);
      return false;
    }
    return phive("SQL")->loadArray("SELECT * FROM $table WHERE $id_type = {$userOrGroup->getId()}");
  }

  public function permissionCount($userOrGroup){
    if($userOrGroup instanceof Group){
      $table = $this->getSetting("GROUP_TABLE");
      $id_type = 'group_id';
    }else if(is_object($userOrGroup)){
      $table = $this->getSetting("USER_TABLE");
      $id_type = 'user_id';
    }else{
      trigger_error("Parameter must be either User or Group", E_USER_ERROR);
      return false;
    }
      $str = "SELECT count(*) FROM $table WHERE $id_type = {$userOrGroup->getId()}";
    phive("SQL")->query($str);
    return phive("SQL")->result();
  }

    public function addTag($tag, $mod_desc = ""){
        if($mod_desc != '(automatically added)')
            $this->permitOrDie('edit.permissions');
        $insert = array("tag" => $tag, "mod_desc" => $mod_desc);
        $pt     = $this->getSetting("TAGS_TABLE");
        if (!$this->tagExists($tag)){
            return phive("SQL")->insertArray($pt, $insert);
        }else
            return false;
    }

    public function removeTag($tag){
        $this->permitOrDie('edit.permissions');
        $table = $this->getSetting("TAGS_TABLE");
        $str = "DELETE FROM $table WHERE tag = ".phive('SQL')->escape($tag);
        return phive("SQL")->query($str);
    }

    public function grant($userOrGroup, $tag, $modifier = ""){
        if(!$this->tagExists($tag, $modifier))
            $this->addTag($tag, "(Added because someone was granted)");
        return $this->setPermission($userOrGroup, $tag, $modifier, 'grant');
    }

  public function deny($userOrGroup, $tag, $modifier = ""){
    if (!$this->tagExists($tag, $modifier))
      $this->addTag($tag, "(Added because someone was denied)");
    return $this->setPermission($userOrGroup, $tag, $modifier, 'deny');
  }

    public function setPermission($userOrGroup, $tag, $modifier, $permission){
        $this->permitOrDie('edit.permissions');
        if($userOrGroup instanceof Group){
            $table = $this->getSetting("GROUP_TABLE");
            $id_type = 'group_id';
        }else if(is_object($userOrGroup)){
            $table = $this->getSetting("USER_TABLE");
            $id_type = 'user_id';
        }else{
            trigger_error("Parameter must be either User or Group", E_USER_ERROR);
            return false;
        }
        $entry = array($id_type => $userOrGroup->getId(), "tag" => $tag, "mod_value" => $modifier, 'permission' => $permission);
        return phive("SQL")->insertArray($table, $entry, null, true);
    }

    public function deletePermission($userOrGroup, $tag, $modifier = ""){
        $this->permitOrDie('edit.permissions');
        if($userOrGroup instanceof Group){
            $table = $this->getSetting("GROUP_TABLE");
            $id_type = 'group_id';
        }else if(is_object($userOrGroup)){
            $id_type = 'user_id';
            $table = $this->getSetting("USER_TABLE");
        }else{
            trigger_error("Parameter must be either User or Group", E_USER_ERROR);
            return false;
        }
        $str = "DELETE FROM $table WHERE $id_type = {$userOrGroup->getId()} AND tag = '$tag' AND mod_value = '$modifier'";
        return phive("SQL")->query($str);
    }

  public function getUsersWithPermission($tag, $modifier = '', $permission = 'grant'){
    $ut = $this->getSetting("USER_TABLE");
    $sql = phive("SQL");
    $sql->query("SELECT `user_id` FROM `$ut` WHERE `tag`= {$sql->escape($tag)} AND `mod_value`= {$sql->escape($modifier)} AND `permission`=" . $sql->escape($permission));
    $r = $sql->fetchArray('NUM');
    $users = array();
    foreach ($r as $row)
      $users[] = cu($row[0]);
    return $users;
  }

  public function searchPermission($tag, $modifier="", $userOrGroup=null){
    $ut = $this->getSetting("USER_TABLE");
    $gt = $this->getSetting("GROUP_TABLE");
    $sql = phive("SQL");
    if($userOrGroup === null)
      $userOrGroup = cu();

    if($userOrGroup instanceof Group){
      $sql->query("SELECT tag FROM $gt WHERE group_id = {$userOrGroup->getId()} AND tag LIKE '$tag' AND mod_value = '$modifier' AND permission='grant'");
      return $sql->fetchArray();
    }else if(is_object($userOrGroup)){
      $sql->query("SELECT tag FROM $ut WHERE user_id = {$userOrGroup->getId()} AND tag LIKE '$tag' AND mod_value = '$modifier' AND permission='grant'");

      $res = $this->getTag($sql->fetchArray());

      $groups = $userOrGroup->getGroups();
      if (!empty($groups))
      foreach ($groups as $group) {
        $sql->query("SELECT tag FROM $gt WHERE group_id = {$group->getId()} AND tag LIKE '$tag' AND mod_value = '$modifier' AND permission='grant'");
        $ret = $this->getTag($sql->fetchArray());
        $res = array_merge($res, array_diff($ret, $res));
      }
      return $res;
    }else if ($userOrGroup === null)
      return false;
    else{
      trigger_error("A non-user non-group was supplied to Permission->hasPermission", E_USER_WARNING);
      return false;
    }
  }

    function hasAnyPermission($u = ''){
        if(empty($u))
            return false;
        if($u->getUsername() == 'admin')
            return true;
        if(!empty($this->getUsersGroups($u->getId())))
            return true;
        return false;
    }

    public function hasPermission($tag, $modifier = "", $userOrGroup = null, $desc = null){
        $ut  = $this->getSetting("USER_TABLE");
        $gt  = $this->getSetting("GROUP_TABLE");
        $sql = phive("SQL");
        if($userOrGroup === null)
            $userOrGroup = cu();

        if (empty($userOrGroup)) {
            return false;
        }

        if( !($wildcard = (strpos($tag, "%") !== false)) ){
            $pt = $this->getSetting("TAGS_TABLE");
            $sql->query("SELECT count(*) FROM `$pt` WHERE `tag`=" . $sql->escape($tag));
            if ($sql->result() == 0)
                $this->addTag($tag, $desc ? $desc : '(automatically added)');
        }

        $tag_op = $wildcard ? "LIKE" : "=";

        if($userOrGroup instanceof Group){
            $sql->query("SELECT permission FROM $gt WHERE group_id = {$userOrGroup->getId()} AND tag = '$tag' AND mod_value = '$modifier' LIMIT 1");
            if($sql->result() == 'grant')
                return true;
            else
                return false;
        }else if(is_object($userOrGroup)){
            if ($userOrGroup->getUsername() === 'admin')
                return true;
            $q = "SELECT permission FROM $ut WHERE user_id = {$userOrGroup->getId()} AND tag $tag_op '$tag' AND mod_value = '$modifier' LIMIT 1";
            $sql->query($q);
            $res = $sql->result();

            if($res === 'deny')
                return false;
            else if($res === 'grant')
                return true;

            $groups = $userOrGroup->getGroups();
            if(!empty($groups)){
                foreach ($groups as $group) {
                    $sql->query("SELECT permission FROM $gt WHERE group_id = {$group->getId()} AND tag $tag_op '$tag' AND mod_value = '$modifier'");
                    if($sql->result() === 'grant')
                        return true;
                }
            }
            return false;
        }else {
            return false;
        }
    }

  function permitOrDie($tag, $modifier="", $userOrGroup = null, $desc=null){
    if(!$this->hasPermission($tag, $modifier, $userOrGroup, $desc)){
      echo "No permission.";
      exit;
    }
  }

  function willEditBoxes(){
    return isset($_GET['editboxes']) && $this->hasPermission('editboxes.light');
  }

  private function getTag($array){
    if (!$array || (is_array($array) && empty($array)))
      return array();
    else{
      $ret = array();
      foreach ($array as $t)
        $ret[] = $t['tag'];
      return $ret;
    }
  }
  
    public function isSuperAgent($user = null): bool {
        $currentUser = $user ? cu($user)->userId  : uid();
        $groups = $this->getUsersGroups($currentUser, true);
        return in_array(65, $groups) || in_array(66, $groups);
    }
}

function pOrDie($tag, $modifier = "", $userOrGroup = null, $desc = null){
    if(!isCli())
        phive('Permission')->permitOrDie($tag, $modifier, $userOrGroup, $desc);
}

function pIfExists($p, $user = null){
    // If we have an array of permissions we only want to return true
    // if all of them are allowed.
    if(is_array($p)){
        foreach($p as $cur_p){
            if(!pIfExists($cur_p))
                return false;
        }
    }
    if(phive("Permission")->tagExists($p))
        return phive('Permission')->hasPermission($p, '', $user);
    return true;
}

function p($p, $user = null){
  return phive('Permission')->hasPermission($p, '', $user);
}

function privileged($user = null){
    return p('admin_top', $user);
}

function pOrSelf($p, $u, $user = null){
    if(empty($user))
        $user = cu();
    if(empty($user) || empty($u))
        return false;
    if($u->getId() == $user->getId())
        return true;
    return p($p, $user);
}

function pCommon($arr, $prefix, $cur, $view_all){
    if(p($view_all))
        return $cur;
    if(empty($cur) || !p("$prefix.$cur")){
        $cur = '';
        foreach($arr as $iso){
            if(p("$prefix.$iso"))
                $cur = $iso;
        }
        if(empty($cur))
            die('no permission');
        return $cur;
    }
    return $cur;
}

function pCur($prefix, $cur){
    return pCommon(cisos(true, true), $prefix, $cur, 'stats.all');
}

function pCountry($prefix, $country){
    return pCommon(array_keys(phive('Localizer')->getAllBankCountries('iso')), $prefix, $country, 'stats.country.all');
}
