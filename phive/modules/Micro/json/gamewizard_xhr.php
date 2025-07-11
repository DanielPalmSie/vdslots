<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../api/m/Form.php';

use MVC\Form as F;
use Videoslots\HistoryMessages\GameUpdateHistoryMessage;

function imginfo($img) {
  $dir = phive('Filer')->getSetting("UPLOAD_PATH") . "/";
  if (!file_exists($dir . $img))
    return sprintf("%s doesn't exist", $img);
  else
    return sprintf('%s exists', $img);
}

function Selected($game, $var, $else = null) {
  $return = (isset($game) && is_object($game)) ? $game->$var : "";
  return ($return == "" && $else != null) ? $else : $return;
}

function SelectedAdditional($aAdditionalValues, $var, $else = null) {
  $return = (isset($aAdditionalValues[$var]) && $aAdditionalValues[$var]) ? $aAdditionalValues[$var] : "";
  return ($return == "" && $else != null) ? $else : $return;
}
function slugify($text){
  // replace non letter or digits by -
  $text = preg_replace('~[^\\pL\d]+~u', '-', $text);
  $text = trim($text, '-');
  $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
  $text = strtolower($text);
  $text = preg_replace('~[^-\w]+~', '', $text);
  return $text;
}

if ($_POST['action_type'] === "move_to_brand") {
    if (!p('settings.games.section.copy_games_to_brand')) {
        die(json_encode(['success' => false, 'message' => "You don't have permission to do this."]));
    }

    if(empty($_POST["id"])) {
        die(json_encode(['success' => false, 'message' => 'Game without ID']));
    }

    $game_id = (int)$_POST["id"];
    $game = phive('MicroGames')->getById($game_id);
    unset($game['id']);
    $desktop_game = [];
    $mobile_game = [];
    // if we are fetching a mobile game directly (Ex. game is mobile only)
    if($game['device_type'] !== 'flash') {
        $desktop_game = [];
        $mobile_game = $game;
    } else {
        $desktop_game = $game;
        if(!empty($game['mobile_id'])) {
            $mobile_game = phive('MicroGames')->getMobileGame($game);
        }
    }

    $game_tags = phive('SQL')->loadArray("SELECT * FROM game_tag_con WHERE game_id = " . $game_id);
    $game_tags_shared = [];
    foreach($game_tags as $game_tag) {
        $game_tags_shared[] = $game_tag['tag_id'];
    }

    $brandId = phive('Distributed')->getLocalBrandId() ?? 100;
    /**
     * fetch data from bonus_type - SELECT * FROM bonus_types WHERE game_id = $game[game_id]
     * create an array to be passed and then inserted without the ID (on moveGameToBrand)
     */
    $bonus_types = phive('SQL')->loadArray("
        SELECT * FROM bonus_types WHERE game_id = '{$game['ext_game_name']}' AND brand_id = {$brandId}
    ");

    /**
     * fetch data from trophies - SELECT * FROM trophies WHERE game_ref = $game[ext_game_name]
     * ADD a left join on trophy_awards to get the alias of the assigned trophy.
     * create an array to be passed and then inserted without the ID (on moveGameToBrand)
     * Then match the correct award_id via alias on the remote brand
     * if the alias of the award doesn't exist return an error message like "this action could not be automated, need to be fixed manually"
     */
    $trophies = phive('SQL')->loadArray("
        SELECT * FROM trophies WHERE game_ref = '{$game['ext_game_name']}'
    ");

    /**
     * fetch data from trophy_awards - SELECT * FROM trophy_awards WHERE bonus_id IN (IDs from bonus_type)
     * create an array to be passed and then inserted without the ID (on moveGameToBrand)
     * + a mapping between bonus_type and trophy_awards so we keep the same relationship.
     */
    $trophy_awards = [];
    foreach ($trophies as $trophy) {
        $award_id = (int)$trophy['award_id'];
        if ($award_id > 0) {
            $trophy_awards[] = $award_id;
        }
    }
    $trophy_awards = implode(",", array_filter($trophy_awards));
    $trophy_awards = phive('SQL')->loadArray("
        SELECT * FROM trophy_awards WHERE id IN ($trophy_awards)
    ");

    $brands_to_sync = phive('Distributed')->getSetting('brand_games_sync_to_machines', []);
    if (empty($brands_to_sync)) {
        die(json_encode(['success' => false, 'message' => 'No machine selected']));
    }

    $source = phive('Filer')->getFileUri('thumbs/' . $game['game_id'] . '_c.jpg');
    $images = [
        'thumbs' => $source,
        'thumbs_dmapi' => [$source, 'file_uploads', $game['game_id'] . '_c.jpg', 'thumbs'],
    ];
    $data = [$game, $mobile_game, $game_tags_shared, $bonus_types, $trophies, $trophy_awards, $images];
    $game_created_on_brand = [];
    foreach ($brands_to_sync as $machine) {
        $game_created_on_brand[$machine] = toRemote($machine, 'moveGameToBrand', [$data]);
    }
    die(json_encode($game_created_on_brand));
}

if (!empty($_POST['save'])) {
  $bkgpic                  = (empty($_FILES['file']['name']['img_bg'])) ? $_POST['bkg_pic'] : $_FILES['file']['name']['img_bg'];
  list($width,$height)     = explode("x", $_POST['resolutions']);
  $p                       = phive()->remEmpty($_POST);
  $gcols                   = phive('SQL')->getColumns('micro_games', true);
  $insert                  = array_intersect_key($p, $gcols);
  $insert["game_id"]       = $_POST['gameid'];
  $insert["bkg_pic"]       = $bkgpic;
  $insert["device_type"]   = $_POST['device_type_text'];
  $insert["operator"]      = $_POST['operator_text'];
  $insert["width"]         = $width;
  $insert["height"]        = $height;
  foreach(array('active', 'enabled', 'multi_channel', 'stretch_bkg', 'ribbon_pic', 'included_countries') as $field)
    $insert[$field]        = $_POST[$field];

  if(p('settings.games.section.payout_extra_percent')) {
    $insert["payout_extra_percent"] = $_POST['payout_extra_percent'];
  }

  /*
    $insert["active"]        = $_POST['active'];
    $insert["enabled"]       = $_POST['enabled'];
    $insert["multi_channel"] = $_POST['multi_channel'];
    $insert["stretch_bkg"]   = $_POST['stretch_bkg'];
    $insert['ribbon_pic']    = $_POST['ribbon_pic'];
   */

  $insert["languages"]     = trim(implode(',', $_POST['languages']), ',');
  $insert['blocked_provinces'] = $insert['blocked_provinces'] ?? '';

  if(!empty($_POST['id']))
    $insert['id'] = $_POST['id'];

  array_walk($insert, 'trim');

  $_FILES['file']['name']['img_ss'] = $insert['game_id'] . '_big'.  substr($_FILES['file']['name']['img_ss'],-4);
  $_FILES['file']['name']['img_tn'] = $insert['game_id'] . '_c'.  substr($_FILES['file']['name']['img_tn'],-4);
  $_FILES['file']['name']['img_tnh'] = $insert['game_id'] . '_c2'.  substr($_FILES['file']['name']['img_tnh'],-4);
  $_FILES['file']['name']['img_mb'] = $insert['game_id'] . '_mb'.  substr($_FILES['file']['name']['img_mb'],-4);
  $_FILES['file']['name']['img_db'] = $insert['game_id'] . '_db'.  substr($_FILES['file']['name']['img_db'],-4);
  $_FILES['file']['name']['img_gh'] = $insert['game_id'] . '_gh'.  substr($_FILES['file']['name']['img_gh'],-4);

  //dynamic sidebar images - gamereviewboxbase
  $_FILES['file']['name']['img_sr_1'] = $insert['game_id'] . '_sr1'.  substr($_FILES['file']['name']['img_sr_1'],-4);
  $_FILES['file']['name']['img_sr_2'] = $insert['game_id'] . '_sr2'.  substr($_FILES['file']['name']['img_sr_2'],-4);
  $_FILES['file']['name']['img_sr_3'] = $insert['game_id'] . '_sr3'.  substr($_FILES['file']['name']['img_sr_3'],-4);
  $_FILES['file']['name']['img_sr_4'] = $insert['game_id'] . '_sr4'.  substr($_FILES['file']['name']['img_sr_4'],-4);

    phive('SQL')->save("micro_games", $insert);

  if(!empty($_FILES))
    phive('Filer')->multipleUpload();

  $is_new = !($_POST['id'] > 0 && !empty($_POST['id']));

  if (!$is_new) {
      // editing
      $micro_games_id = $_POST['id'];
      $insert['ext_game_name'] = phive('MicroGames')->getById($micro_games_id)['ext_game_name']; //disabled field on editor
      echo "2147483648";
  }else{
      // adding
      $micro_games_id = phive('SQL')->insertId();
      echo $micro_games_id;
  }

  if (!empty($micro_games_id)) {
      /* This is called directly on Licensed, because it's not a user action, so it has no jurisdiction */
      phive('Licensed')->addRecordToHistory(
          'game_update',
          new GameUpdateHistoryMessage([
              'id'                  => (int) $micro_games_id,
              'is_new'              => $is_new,
              'mobile_id'           => (int) ($insert['mobile_id'] ?? 0),
              'tag'                 => $insert['tag'],
              'game_name'           => $insert['game_name'],
              'game_id'             => $insert['game_id'],
              'operator'            => $insert['operator'],
              'provider_game_id'    => $insert['ext_game_name'],
              'device_type_number'  => (int) $insert['device_type_num'],
              'enabled'             => (int) $insert['enabled'],
              'active'              => (int) $insert['active'],
              'event_timestamp'     => time(),
          ])
      );
  }

  die();
}
/**
 * Different form for additional game attributes
 */
if (!empty($_POST['save_game_attr'])) {
  $message_err = array("message" => "Its not possible to save the record.", "status" => "error");
  $message_ok = array("message" => "Record saved.", "status" => "ok");

  $aFields = array(
    "label"
    , "default_value"
    , "possible_values"
    , "html_type"
    , "enable"
    , "visible_front_end"
    , "tab_front_end"
    , "priority"
  );
  /**
   * Is it a new field? Different behaviour, insert not update
   */
  if (!empty($_POST['is_new'])) {
    $new_field = true;
  }
  foreach ($aFields as $field) {
    foreach ($_POST[$field] as $key => $value) {
      switch($field){
        case 'possible_values':
          $value = json_encode(explode(",", trim($value, ',')));
          break;
        case 'label': // generate alias from label
          if ($new_field) {
            $insert['alias'] = slugify($value);
          }else {
            $insert[$key]['alias'] =  slugify($value);
          }
          break;
      }
      if ($new_field) {
        $insert[$field] = $value;
      } else {
        $insert[$key][$field] = $value;
      }
    }
  }
  if ($new_field) {
    if (!phive('SQL')->save("game_attributes", $insert)) {
      echo json_encode($message_err);
      exit;
    }
  } else {
    foreach ($insert as $key => $values) {
      if (!phive('SQL')->save("game_attributes", $values, array('id' => $key))) {
        echo json_encode($message_err);
        exit;
      }
    }
  }
  echo json_encode($message_ok);
}
if (!empty($_POST['delete_game_attr'])) {
  $attr_id = $_POST['attrId'];
  $message_err = array("message" => "Its not possible to remove the record.", "status" => "error");
  $message_ok = array("message" => "Record removed.", "status" => "ok");
  $status = 'error';
  if (!$attr_id) {
    echo json_encode($message_err);
    exit;
  }
  $where = array("id" => $attr_id);
  if (!phive('SQL')->delete("game_attributes", $where)) {
    $message = array("message" => "Its not possible to remove the record.", "status" => "error");
    echo json_encode($message_err);
    exit;
  }
  $where = array("parent_id" => $attr_id);
  if (!phive('SQL')->delete("game_attributes", $where)) {
    $message = array("message" => "Its not possible to remove the record.", "status" => "error");
    echo json_encode($message_err);
    exit;
  }
  echo json_encode($message_ok);
  exit;
}

if (!empty($_POST['get_game_generic_attr'])) { // default attributes for all games

  $mg = phive('MicroGames');
  $game_additional_options_generic = $mg->getGameAdditionalAttributes(false,false,true); //used for all games

  echo '<h3>Game Attributes Generic (For every game)</h3>';
  echo '<div id="newField"><label></label></div>';
  echo '<table style="width:100%">';
  echo '<thead>';
  echo '<tr>';
  echo '<th style="width: 150px;">Field</th>';
  echo '<th style="width: 150px;">Default Value</th>';
  echo '<th style="width: 150px;">Alias</th>';
  echo '<th style="width: 150px;">Possible Values (comma separated)</th>';
  echo '<th style="width: 40px;">Input</th>';
  echo '<th style="width: 40px;">Visible Front End</th>';
  echo '<th style="width: 40px;">Tab Front End</th>';
  echo '<th style="width: 40px;">Remove</th>';
  echo '<th style="width: 40px;">Priority</th>';
  echo '</tr>';
  echo '</thead>';
  echo '<tbody>';

  $rc = 0;
  foreach ($game_additional_options_generic as $add_attr) {
    $rc++;
    $background_color = ($rc % 2) == 0 ? "#eee" : "#dadada";
    $add_attr_value = $add_attr['val'] ? $add_attr['val'] : $add_attr['default_value'];
    $add_attr_id = $add_attr['id'];
    $add_attr_alias = $add_attr['alias'];
    $add_attr_label = $add_attr['label'];
    $add_attr_priority_val = $add_attr['priority'];
    $add_attr_visible_front_end = $add_attr['visible_front_end'];
    $add_attr_possible_values = $add_attr['possible_values'] ? implode(",", json_decode($add_attr['possible_values'])) : '';
    $add_attr_visible_front_end_checked = $add_attr['visible_front_end'] == 1 ? 'selected=selected' : '';
    $add_attr_html_type = $add_attr['html_type'];
    $add_attr_text = F::input(array('size' => '15', 'value' => "{$add_attr_value}", "id" => "{$add_attr_name}", 'name' => 'default_value[' . $add_attr_id . ']'));
    $add_attr_possible_value = F::input(array('size' => '15', 'value' => "{$add_attr_possible_values}", "id" => "possible_values[{$add_attr_id}]", 'name' => 'possible_values[' . $add_attr_id . ']'));
    $add_attr_priority_value = F::input(array('size' => '15', 'value' => "{$add_attr_priority_val}", "id" => "priority[{$add_attr_id}]", 'name' => 'priority[' . $add_attr_id . ']'));
    $is_sidebar_img_field = false;
    if(stripos($add_attr_label,'additional_game_attr_link_img_sr_') !== false){
      $is_sidebar_img_field = true;
    }
    $is_enable_game_review_page = false;
    if(stripos($add_attr_label,'ENABLE GAME REVIEW PAGE') !== false){
      $is_enable_game_review_page = true;
    }
    $is_reg_game_bonus_id = false;
    if(stripos($add_attr_label,'reg-game-bonus-id') !== false){
      $is_reg_game_bonus_id = true;
    }


      //TODO Alberto: use PHP templating below instead of echoing everything

    echo '<tr style="background-color:' . $background_color . '">';
    if($is_sidebar_img_field || $is_enable_game_review_page || $is_reg_game_bonus_id){
      echo "<td><textarea name='label[{$add_attr_id}]'style='width:90%' disabled>{$add_attr_label}</textarea></td>";
      echo "<td style='text-align: center;'>{$add_attr_text}</td>";
      echo "<td>&nbsp;</td>";
      echo "<td>&nbsp;</td>";
      echo "<td>&nbsp;</td>";
      echo "<td>&nbsp;</td>";
      echo "<td>&nbsp;</td>";
      echo "<td>&nbsp;</td>";
      echo "<td>&nbsp;</td>";
    }else{
      echo "<td><textarea name='label[{$add_attr_id}]'style='width:90%'>{$add_attr_label}</textarea></td>";
      echo "<td style='text-align: center;'>{$add_attr_text}</td>";
      echo "<td><textarea name='alias[{$add_attr_id}]'style='width:90%' disabled>{$add_attr_alias}</textarea></td>";
      echo "<td style='text-align: center;'>{$add_attr_possible_value}</td>";
      echo '<td style="text-align: center;">';
      echo "<select name='html_type[{$add_attr_id}]'>";
      echo "<option " . ( $add_attr['html_type'] == 'text' ? 'selected=selected' : '') . " value='text'>text</option>";
      echo "<option " . ( $add_attr['html_type'] == 'radio' ? 'selected=selected' : '') . " value='radio'>radio</option>";
      echo "<option " . ( $add_attr['html_type'] == 'select' ? 'selected=selected' : '') . " value='select'>select</option>";
      echo "</select>";
      echo '</td>';
      echo '<td style="text-align: center;">';
      echo "<select name='visible_front_end[{$add_attr_id}]'>";
      echo "<option " . ( $add_attr['visible_front_end'] == 1 ? 'selected=selected' : '') . " value='1'>Yes</option>";
      echo "<option " . ( $add_attr['visible_front_end'] == 0 ? 'selected=selected' : '') . " value='0'>No</option>";
      echo "</select>";
      echo '</td>';
      echo '<td style="text-align: center;">';
      echo "<select name='tab_front_end[{$add_attr_id}]'>";
      echo "<option value='' selected></option>";
      echo "<option " . ( $add_attr['tab_front_end'] == 'Overview' ? 'selected=selected' : '') . " value='Overview'>Overview</option>";
      echo "<option " . ( $add_attr['tab_front_end'] == 'Features' ? 'selected=selected' : '') . " value='Features'>Features</option>";
      echo "<option " . ( $add_attr['tab_front_end'] == 'More Info' ? 'selected=selected' : '') . " value='More Info'>More Info</option>";
      echo "</select>";
      echo '</td>';
      echo "<td><input type='button' name='delete_game_attr' onclick='delete_game_attributes({$add_attr_id})' value='Remove'></td>";
      echo "<td style='text-align: center;'>{$add_attr_priority_value}</td>";
    }

    echo '</tr>';
  }
  echo '</tbody>';
  echo '</table>';
  echo '<div style="width:100%; text-align: center; margin-top: 20px"><input type="button" onclick="submit_game_attributes(\'game_attributes_section\')" value="Submit Attributes"></div>';
}
if (!empty($_POST['get_game_specific_attr'])) {
  $mg = phive('MicroGames');
  $fspins = phive('Config')->getByTagValues('freespins');
  $gameid = intval($_POST['gameid']);
  $game_additional_options_specific = $mg->getGameAdditionalAttributes($gameid,false,true); //used for all games
  echo '<h3>Additional attributes</h3>';
  echo "<input type='hidden' name='gameid' value='{$gameid}'>";
  echo '<table style="width:100%">';
  echo '<thead>';
  echo '<tr>';
  echo '<th>Field</th>';
  echo '<th>Value</th>';
  echo '</tr>';
  echo '</thead>';
  echo '<tbody>';
  $rc = 0;
  foreach ($game_additional_options_specific as $add_attr) {
    $rc++;
    $background_color = ($rc % 2) == 0 ? "#eee" : "#dadada";
    $add_attr_id = $add_attr['id'];
    $add_attr_name = $add_attr['name'];
    $add_attr_label = $add_attr['label'];
    $add_attr_value = $add_attr['val'] ? $add_attr['val'] : $add_attr['default_value'];
    $add_attr_text = "";
    $options = "";
    switch ($add_attr['html_type']) {
      case 'radio':
        $add_attr_possible_values = ($add_attr['possible_values']) ? json_decode($add_attr['possible_values']) : '';
        foreach ($add_attr_possible_values as $possible_value) {
          $checked = (strtolower($add_attr_value) === strtolower($possible_value)) ? 'checked=checked' : '';
          $add_attr_text .= $possible_value . ' ' . "<input type='radio' name='add_attr_value[{$add_attr_id}]' value='{$possible_value}' {$checked}>";
        }
        break;
      case 'text':
        $add_attr_text = F::input(array('size' => '64', 'value' => "{$add_attr_value}", "name" => "add_attr_value[{$add_attr_id}]", "id" => "add_attr_value[{$add_attr_id}]"));
        break;
      case 'select':
        $options .= "<option selected></option>";
        $add_attr_possible_values = ($add_attr['possible_values']) ? json_decode($add_attr['possible_values']) : '';
        foreach ($add_attr_possible_values as $possible_value) {
          $checked = (strtolower($add_attr_value) === strtolower($possible_value)) ? 'selected' : '';
          $options .= "<option value='{$possible_value}' {$checked}>{$possible_value}</option>";
        }
        $add_attr_text = "<select name='add_attr_value[{$add_attr_id}]'>{$options}</select>";
        break;
    }
    echo '<tr style="background-color:' . $background_color . '">';
    echo "<td>{$add_attr_label}</td>";
    echo "<td>{$add_attr_text}</td>";
    echo '</tr>';
  }
  echo '</tbody>';
  echo '</table>';
  echo '<hr>';
  echo '<div style="width:100%; text-align: center; margin-top: 20px"><input type="button" onclick="submit_game_attributes(\'game_attributes_specific_section\')" value="Submit Attributes"></div>';
}

if (!empty($_POST['save_specific_game_attributes'])) { //save specific game attributes
  $message_err = array("message" => "Its not possible to save the record.", "status" => "error");
  $message_ok = array("message" => "Record saved.", "status" => "ok");
  $gameid = intval($_POST['gameid']);
  $errors = false;
  if(!$gameid){
    $message_err = array("message" => "Its not possible to save the record.", "status" => "error");
    echo json_encode($message_err);
    exit;
  }
  $insertAddAttr = array();
  foreach($_POST['add_attr_value'] as $key => $val){
    $insertAddAttr['value'] = $val;
    $insertAddAttr['game_id'] = $gameid;
    $insertAddAttr['parent_id'] = $key;
    phive('SQL')->save("game_attributes", $insertAddAttr,array('game_id' =>$gameid, 'parent_id' => $key));
  }
  if($errors){
    echo json_encode($message_err);
    exit;
  }
  echo json_encode($message_ok);
}
