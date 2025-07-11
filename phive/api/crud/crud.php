<?php

use Videoslots\HistoryMessages\Exceptions\InvalidMessageDataException;
use Videoslots\HistoryMessages\GameUpdateHistoryMessage;

require_once __DIR__ . '/../../phive.php';
class Crud{

  static function table($table, $push = false, $prior_insert = false){
    $me 		= new Crud();
    $me->table	 	= $table;
    $me->sql 		= phive('SQL');
    $me->push		= $push;
    $me->prior_insert = $prior_insert;
    return $me;
  }

  function hideControls(){
    $this->hide_controls = true;
    return $this;
  }

    function delete($idval, $key = 'id'){
        $this->sql->delete($this->table, "$key = '$idval'", $_GET['user_id']);
    }

  /*
   * array('affe_id' => array('table' => 'users', 'idfield' => 'user_id', 'dfield' => 'username'))
   */
  function getForInsert($field, $value){
    if(!$this->checkMap($field))
      return $value;

    $map = $this->map[$field];

    //echo "SELECT {$map['idfield']} FROM {$map['table']} WHERE {$map['dfield']} = '$value'";
    //exit;

    return phive('SQL')->getValue("SELECT {$map['idfield']} FROM {$map['table']} WHERE {$map['dfield']} = '$value'");
  }

  function filterPost(){
    $rarr = array();

    if(!empty($this->multi)){
      foreach($this->multi as $mkey){
        foreach($_POST[$mkey] as $mval){
          $tmp = array();
          foreach($this->getCols() as $col){
            if($col != $mkey)
              $tmp[$col] = trim($_POST[$col]);
            else
              $tmp[$mkey] = $mval;
          }
          $rarr[] = $tmp;
        }
      }
    }else{
      foreach($this->getCols() as $col)
        $rarr[$col] = trim($_POST[$col]);
    }

    return $rarr;
  }

  function update($idval, $key = 'id'){
    $inserts = $this->updated_arr = $this->filterPost();
    $where = $this->updated_where = array($key => $idval);
    phive('SQL')->sh($inserts, 'user_id', $this->table)->updateArray($this->table, $inserts, $where);
    $this->logChange($idval, $inserts);
  }

  function insert($key){
    if($this->prior_insert == true)
      return;
    $inserts 		= $this->filterPost();
    if(is_array($inserts[0])){
      foreach($inserts as $insert){
          $id = phive('SQL')->insertArray($this->table, $insert);
          $this->logChange($id, $insert);
      }
    }else{
        $id = phive('SQL')->insertArray($this->table, $inserts);
        $this->logChange($id, $inserts);
    }
  }

  /**
   * Logs a change in an entity
   *
   * @param int $id
   * @param array $data
   *
   * @return void
   */
  function logChange($id, $data)
  {
      if ($this->table === 'micro_games') {
          $this->logGameChange($id, $data);
      }
  }

  /**
   * Logs game change
   *
   * @param int $id
   * @param array $data
   * @return void
   */
  function logGameChange($id, $data)
  {
      try {
          $topic = 'game_update';
          $historyData = [
              'id'                 => (int)$id,
              'is_new'             => true,
              'mobile_id'          => (int)($data['mobile_id'] ?? 0),
              'tag'                => $data['tag'],
              'game_name'          => $data['game_name'],
              'game_id'            => $data['game_id'],
              'operator'           => $data['operator'],
              'provider_game_id'   => $data['ext_game_name'],
              'device_type_number' => (int)$data['device_type_num'],
              'enabled'            => (int)$data['enabled'],
              'active'             => (int)$data['active'],
              'event_timestamp'    => time(),
          ];
          phive('Licensed')->addRecordToHistory(
              $topic,
              new GameUpdateHistoryMessage($historyData)
          );
          phive('Logger')
            ->getLogger('history_message')
            ->warning('Game updated using old admin CRUD.', [
                'id' => $id
            ]);
      } catch (InvalidMessageDataException $e) {
          phive('Logger')
              ->getLogger('history_message')
              ->error(
                  $e->getMessage(),
                  [
                      'topic'             => $topic,
                      'validation_errors' => $e->getErrors(),
                      'trace'             => $e->getTrace(),
                      'data'              => $historyData
                  ]
              );
      }
  }

  function insertUpdateDelete($key = 'id'){

    if($_GET['action'] == 'delete'){
      $this->delete($_GET[$key], $key);
      return;
    }

    if($_GET['action'] == 'update' && empty($_REQUEST['submit_new']))
      $this->update($_POST[$key], $key);
    else if($_GET['action'] == 'insert' || !empty($_REQUEST['submit_new']))
      $this->insert($key);
  }

  function setWhere($where){
    $this->where_str = $where;
    return $this;
  }

  function deleteOff(){
    $this->delete = false;
    return $this;
  }

  function noAutoList(){
    $this->noAutoList = true;
    return $this;
  }

    function setSqlStr($str = ''){
        $this->sql_str = empty($str) ? $_POST['sql_str'] : $str;
        if(empty($this->sql_str)){
            $this->sql_str = $_SESSION['crud_sql_str'];
        }else{
            if(strpos($this->sql_str, "LIMIT") === false)
                $this->sql_str .= " LIMIT 0,30"; //No limit so we set default to 30 to avoid crash
        }
        $_SESSION['crud_sql_str'] = $this->sql_str;
        return $this;
    }

    function showSearchArea(){
        $this->show_search = true;
        return $this;
    }

    function getList($limit = ''){
        if(!empty($this->result))
            return $this->result;

        if(!empty($this->sql_str) && p('execute.sql')){
            //Currently only used with a sharded database, if not sharded use PHPMyAdmin or something
            $str = $this->sql_str;
            $this->result = phive('SQL')->shs()->loadArray($str);
        }else{
            $limit = empty($limit) ? '' : " LIMIT 0,$limit ";
            $where = ' WHERE 1 ';

            if(!empty($this->filter_by))
                $where .= empty($_GET['filterby']) ? '' : phive('SQL')->makeWhere($_GET['filterby'], false, 'AND');

            if(!empty($this->where_str))
                $where .= " AND ".$this->where_str;

            $str = "SELECT * FROM {$this->table} $where $limit";
            $this->result = phive('SQL')->loadArray($str);
        }


        return $this;
    }

  function getTypes(){
    $colinfo = phive('SQL')->loadArray("SHOW COLUMNS FROM {$this->table}");
    $rarr = array();
    foreach($colinfo as $info)
      $rarr[$info['Field']] = $info['Type'];
    return $rarr;
  }

  function getCols(){
    $colinfo = phive('SQL')->loadArray("SHOW COLUMNS FROM {$this->table}");
    $rarr = array();
    foreach($colinfo as $info){
      if($info['Extra'] != 'auto_increment' && $info['Default'] != 'CURRENT_TIMESTAMP')
        $rarr[] = $info['Field'];
    }
    return $rarr;
  }

  function asHeadline($str){
    return ucfirst(str_replace('_', ' ', $str));
  }

    function getEl($idval, $key){
        $str = "SELECT * FROM {$this->table} WHERE $key = '$idval'";
        $el = phive('SQL')->sh($_GET['user_id'], '', $this->table)->loadAssoc($str);
        return $el;
    }

  function getDisplayVal($el, $dfield){
    if(is_array($dfield)){
      $delim = ' - ';
      $value = '';
      foreach ($dfield as $col)
        $value .= $el[$col].$delim;
      return phive()->trimStr($delim, $value);
    }else
    return $el[$dfield];
  }

  function getSelection($col, $to_update, $map = array(), $name = ''){
    $map = empty($map) ? $this->map[$col] : $map;
    $str = "SELECT * FROM {$map['table']} WHERE 1 {$map['where']} ORDER BY {$map['dfield']}";
    $sel_arr = phive('SQL')->loadArray($str);
    $sel_name = empty($name) ? $col : $name;
    if(in_array($sel_name, $this->multi))
      $multi = true;
  ?>
  <select name="<?php echo $sel_name . ($multi ? '[]' : '') ?>" <?php if($multi) echo 'multiple="true" size="10"' ?>>
    <?php if(!empty($map['defval'])): ?>
      <option value="<?php echo $map['defkey'] ?>">
        <?php echo $map['defval'] ?>
      </option>
    <?php endif ?>
    <?php foreach($sel_arr as $el): ?>
      <option value="<?php echo $el[ $map['idfield'] ] ?>" <?php if($to_update[ $col ] == $el[ $map['idfield'] ]) echo 'selected="selected"' ?>>
        <?php echo $this->getDisplayVal($el, empty($map['dfields']) ? $map['dfield'] : $map['dfields']) ?>
      </option>
    <?php endforeach ?>
  </select>
  <?php
  }

  function filterBy($map, $show = true){
    $this->filter_by = $map;
    if($show):
  ?>
  <h3>List and filter by:</h3>
  <form action="<?php echo $this->getUrl() ?>" method="get">
    <table>
      <?php foreach($map as $col => $config): ?>
        <tr>
          <td><?php echo $this->asHeadline($col) ?>:</td>
          <td> <?php $this->getSelection($col, array(), $config, "filterby[$col]") ?> </td>
        </tr>
      <?php endforeach ?>
    </table>
    <input type="submit" value="Submit" />
  </form>
  <br>
  <?php
  endif;
  return $this;
  }

  function checkGetFilter($col){
    if(!empty($_GET["filterby"]))
      return $_GET["filterby"][$col];
    return false;
  }

  function setUrl($url){
    $this->url = $url;
    return $this;
  }

  function urlAdd($key, $value){
    $this->url[$key] = $value;
    return $this;
  }

  function getUrl(){
    $rstr = '?';
    foreach($this->url as $key => $value)
      $rstr .= "$key=$value&";
    return trim($rstr, '&');
  }

  function renderForm($key = 'id', $el){
    if(!empty($el[$key])){
      $this->url['action'] 	= "update";
      $this->url[$key] 		= $_GET[$key];
    }else if(empty($this->url))
      $this->url['action'] = $action = 'insert';

    $type_info = $this->getTypes();
    $slave_tables = phive("SQL")->getSetting('slave_tables');
  ?>
  <table border="0" cellspacing="5" cellpadding="5">
    <tr>
      <td>
        <div class="crud-form">
          <h3> <?php echo strpos($action, 'insert') !== false ? 'Insert:' : 'Update:' ?> </h3>
          <form method="post" action="<?php echo $this->urlAdd('table', $this->table)->getUrl() ?>">
            <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
            <input type="hidden" value="<?php echo empty($el) ? '' : $el[$key] ?>" name="<?php echo $key ?>" />
            <table>
              <?php foreach($this->getCols() as $col): ?>
                <tr>
                  <td>
                    <?php echo $this->asHeadline($col) ?>:
                  </td>
                  <td>
                    <?php if(!$this->checkMap($col)): ?>

                      <?php if($type_info[ $col ] == 'text'): ?>
                        <textarea cols="50" rows="20" name="<?php echo $col ?>" <?php echo $this->shouldFieldBeReadonly($col) ? "readonly" : "" ?> ><?php echo empty($el) ? $_POST[$col] : $this->getForDisplay($col, $el[$col]) ?></textarea>
                      <?php else: ?>
                        <input type="text" value="<?php echo empty($el) ? $_POST[$col] : $this->getForDisplay($col, $el[$col]) ?>" name="<?php echo $col ?>" <?php echo $this->shouldFieldBeReadonly($col) ? "readonly" : "" ?>/>
                      <?php endif ?>

                    <?php elseif($this->checkGetFilter($col)): ?>
                      <input type="text" value="<?php echo $this->checkGetFilter($col) ?>" name="<?php echo $col ?>" <?php echo $this->shouldDisableField($col) ? "readonly" : "" ?>/>
                    <?php else: ?>
                      <?php $this->getSelection($col, $el) ?>
                    <?php endif ?>
                  </td>
                </tr>
              <?php endforeach ?>
              <?php if(!empty($this->show_search) && p('execute.sql')): ?>
                  <tr>
                      <td>Search SQL:</td>
                      <td>
                          <textarea cols="50" rows="20" name="<?php echo 'sql_str' ?>"><?php echo $_POST['sql_str'] ?></textarea>
                      </td>
                  </tr>
              <?php endif ?>
            </table>
            <br />
            <input type="submit" value="Save" />
            <?php if(!empty($el)): ?>
              <input style="margin-left: 250px;" type="submit" name="submit_new" value="Save as new" />
            <?php endif ?>
          </form>
        </div>
      </td>
      <td>
        <script>
         $(document).ready(function(){
           $(".push").click(function(){
             var obj = {
               table: "<?php echo $this->table ?>",
               slave: getSuffix($(this).attr('id')),
               field: '<?php echo $key ?>',
               value: '<?php echo $el[$key] ?>'
             };
             $.post('/diamondbet/html/sync-slaves.php', obj, function(res){
               $("#msg").html(empty(res) ? 'The row could not be pushed, probably because it exists in the remote already.' : 'Row pushed successfully.');
             }, 'json');
           });
         });
        </script>
        <?php if(!empty($slave_tables[$this->table]) && $this->url['action'] == 'update'): ?>
          <?php foreach(phive("SQL")->getSetting('slaves') as $slave): ?>
            <button class="push" id="push-<?php echo $slave ?>">
              Push to <?php echo $slave ?>
            </button>
          <?php endforeach ?>
          <div id="msg">

          </div>
        <?php endif ?>
      </td>
    </tr>
  </table>
  <br>
  <br>
  <?php }

  function renderList($key = 'id', $list = array()){
    if($this->noAutoList !== true)
      $list = empty($list) ? $this->getList()->result : $list;
    if(empty($list))
      return;
    $headline_cols 	= array_keys(current($list));
    $row 			= 0;
  ?>
  <table class="list_table">
    <tr class="list_header">
      <?php if($this->hide_controls !== true): ?>
        <td>&nbsp;</td>
      <?php endif ?>
      <?php foreach($headline_cols as $hcol): ?>
        <td>
          <?php echo $this->asHeadline($hcol) ?>
        </td>
      <?php endforeach ?>
      <?php if($this->delete !== false && $this->hide_controls !== true): ?>
        <td>&nbsp;</td>
      <?php endif ?>
      <?php if($this->show_cancel === true): ?>
        <td>&nbsp;</td>
      <?php endif ?>
    </tr>
    <?php foreach($list as $el): ?>
      <tr class="<?php echo ($row % 2 == 0) ? "fill-odd" : "fill-even" ?>">
        <?php if($this->hide_controls !== true): ?>
          <td>
              <a href="<?php echo $this->urlAdd('action', 'updateform')->urlAdd($key, $el[$key])->urlAdd('table', $this->table)->urlAdd('user_id', $el['user_id'])->getUrl() ?>">Update</a>
          </td>
        <?php endif ?>
        <?php foreach($el as $field => $val): ?>
          <td>
            <?php echo $this->getForDisplay($field, $val, true) ?>
          </td>
        <?php endforeach ?>
        <?php if($this->delete !== false && $this->hide_controls !== true): ?>
          <td>
              <a href="<?php echo $this->urlAdd('action', 'delete')->urlAdd($key, $el[$key])->urlAdd('table', $this->table)->urlAdd('user_id', $el['user_id'])->getUrl() ?>">Delete</a>
          </td>
        <?php endif ?>
        <?php if($this->show_cancel === true): ?>
          <td>
            <a href="<?php echo $this->urlAdd('action', 'cancel')->urlAdd($key, $el[$key])->getUrl() ?>">Cancel</a>
          </td>
        <?php endif ?>
      </tr>
      <?php
      $row++;
      endforeach;
      ?>
  </table>
  <br>
  <br>
  <?php
  }


  function checkMap($field){
    if(empty($this->map))
      return false;

    if(empty($this->map[$field]))
      return false;

    return true;
  }

  /*
   * array('affe_id' => array('table' => 'users', 'idfield' => 'user_id', 'dfield' => 'username'))
   */
  function getForDisplay($field, $value, $list = false){
    if(!$this->checkMap($field))
      $ret = $value;
    else{
      $map = $this->map[$field];
      $str = "SELECT {$map['dfield']} FROM {$map['table']} WHERE {$map['idfield']} = '$value'";
      $ret = phive('SQL')->getValue($str);
    }
    return $list ? phive()->chop($ret, 30) : $ret;
  }

  function setMulti($arr){
    $this->multi = $arr;
    return $this;
  }

  function renderInterface($key = 'id', $map = array(), $show_form = true, $ini = null, $list = array(), $insert_update = true, $upd = array(), $readonly_fields = array()){
    loadCss('/phive/api/crud/crud.css');
    loadJs('/phive/js/jquery.js');
    loadJs('/phive/js/utility.js');
    $this->map = $map;
    $this->readonly_fields = $readonly_fields;
    if(empty($this->url))
      $this->url = array();
    if($insert_update)
      $this->insertUpdateDelete($key);
    if($show_form){
      if($_GET['action'] == 'updateform'){
        $this->renderForm($key, !empty($upd) ? $upd : $this->getEl($_GET[$key], $key));
      }else if(empty($_GET['action']))
        $this->renderForm($key, $ini);
      else
        echo '<br><a href="'.$this->urlAdd('action', '')->urlAdd('table', $this->table)->getUrl().'">Insert New</a><br><br>';
    }
    $this->renderList($key, $list);
  }

  function shouldFieldBeReadonly($field) {
    return $this->url['action'] === "update" && in_array($field, $this->readonly_fields);
  }
  /*
  function handleExternals($id_field){
    $m = phive('Casino');
    $res = true;
    foreach($m->getSetting('ext_networks') as $network){
      if(!empty($this->inserted_id))
        $res = $m->pushById($this->table, $id_field, $this->inserted_id, '', $network);
      else if(!empty($this->updated_arr))
        $res = $m->pushChange($this->table, $this->updated_arr, $this->updated_where, '', $network);
    }
    if($res === false)
      echo "<script>alert('The connection to the external casino is broken, the update was not pushed!');</script>";
  }
  */

}
