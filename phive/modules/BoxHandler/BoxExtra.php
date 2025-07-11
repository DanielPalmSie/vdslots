<?php
require_once __DIR__.'/Box.php';
class BoxExtra extends Box{

  function printModeratorHTML($print_anyway = false){

    if($this->getAttribute('check_perm') == 1 && !p("box.".$this->getId()))
      return;

    if(!empty($this->original_id))
      $this->setId($this->original_id);

    $SETTINGSEDITOR = (isset($_GET['moderate_box']) && $_GET['moderate_box'] == $this->getId());
    if($this->sub_box != 1 || $print_anyway):
    ?>
    <div style="background: #888; border: 1px solid black; border-bottom: none; color: black; padding: 2px;">
      <a name="box_<?= $this->getId() ?>" />
      <?=$this->getType();?>
      <a style="color: green; text-decoration: none; cursor: pointer;" onclick="javascript:boxAction(<?=$this->getId()?>, 'move_down', function(){location.reload(true)});">&darr;</a>
      <a style="color: green; text-decoration: none; cursor: pointer;" onclick="javascript:boxAction(<?=$this->getId()?>, 'move_up', function(){location.reload(true)});">&uarr;</a>
      &nbsp;
      <a style="color: red; text-decoration: none; cursor: pointer;" onclick="if(confirm('Are you sure you want to delete this <?=$this->getType()?>?'))boxAction(<?=$this->getId()?>, 'delete', function(){location.reload(true)});"><span style="font-family: sans-serif;">x</span></a>
      <?php if (!$SETTINGSEDITOR): ?>
        <a style="color: blue;" href="?editboxes&amp;moderate_box=<?=$this->getId()?>#box_<?= $this->getId()?>">Edit settings for this box</a> (<?= $this->getId()?>)
      <?php else: ?>
        <span>(<?= $this->getId()?>)</span>
      <?php endif ?>
    </div>
    <?php
    endif;
    if($SETTINGSEDITOR && ($this->sub_box != 1 || $print_anyway))
      $this->printSettingsHTML();
    else if($this->sub_box != 1 || $print_anyway)
      $this->printHTML();
  }

  function handlePost($fields, $defaults = array()){
    if($this->getAttribute('check_perm') == 1 && !p("box.".$this->getId()))
      return;

    foreach($fields as $field){
        if(isset($_POST['save_settings']) && $_POST['box_id'] == $this->getId() && p('editboxes.light')){
            $this->setAttribute($field, trim($_POST[$field]));
        }
      $tmp = $this->getAttribute($field);
      $this->$field = empty($tmp) ? (isset($defaults[$field]) ? $defaults[$field] : '') : $tmp;
    }
  }

  function isSub(){ return $this->getAttribute('sub_box') == 1 ? true : false; }

  public function init(){

    if(isset($_POST['save_settings']) && $_POST['box_id'] == $this->getId()){
      $this->setAttribute("print_hr", $_POST['print_hr']);
      $this->setAttribute("sub_box", $_POST['sub_box']);
      $this->setAttribute("check_perm", $_POST['check_perm']);
    }
    $this->print_hr = ($this->attributeIsSet("print_hr"))	?	$this->getAttribute("print_hr"):1;
    $this->sub_box 	= ($this->attributeIsSet("sub_box"))	?	$this->getAttribute("sub_box"):0;
    $this->sub_box 	= ($this->attributeIsSet("check_perm"))	?	$this->getAttribute("check_perm"):0;
  }

  function createAmount($r, $rake_key){
    if($rake_key != 'rake' || $this->getType() == 'ExtSrcRaceBox')
      $amount = $r[$rake_key];
    else{
      $amount 	= empty($r[$rake_key]) ? '0' : $r[$rake_key];
      if(!empty($this->cur_multiplier) && $amount != 0)
        $amount *= $this->cur_multiplier;
      $amount = round($amount, 2);
    }

    if($this->fake_points == 'yes')
      $amount /= $this->fake_value;

    return $amount;
  }

  function handleCurrency(){
    $cur = phive('Currencer');

    if(empty($this->currency))
      $this->currency = $cur->getSetting('race_currency');

    $this->cur_multiplier = $cur->getMultiplier(strtoupper($this->currency));
  }

  function red($txt){
    return '<span class="red">'.$txt.'</span>';
  }

  function getAttrValue($attrs, $key){
    foreach($attrs as $attr){
      if($attr['attribute_name'] == $key)
        return $attr['attribute_value'];
    }
    return false;
  }

    /*
  function createLvl(&$data, &$i, $info){
    foreach(array_shift($this->data) as $player){
      $data[] = array(
        ++$i.".",
        $this->getImage($i),
        $this->getPrize($player, $info),
        $this->getDisplayName($player, $player['rake']),
        phive('Currencer')->baseRaceCurSign().' '.$player['rake']
      );
    }
  }

  public function createChaseData(){
    $head_rake = $this->partner['type_id'] == 2 ? h(t("rakechase.head.loyalty")) : h(t("rakechase.head.rake"));
    $data[] = array(h(t("rakechase.head.place")), " ", h(t("rakechase.head.prize")), h(t("rakechase.head.player")), $head_rake);
    $i = 0;
    foreach($this->race_info as $info)
      $this->createLvl($data, $i, $info);

    if(!empty($this->data))
      $this->createLvl($data, $i, array('rake_level' => 0, 'max_players' => 10, 'prize' => 0));

    return $data;
  }
    */
    
  function filterEmpty($arr){
    $rarr = array();
    foreach($arr as $el){
      $el = trim($el);
      if(!empty($el))
        $rarr[] = $el;
    }
    return $rarr;
  }

  function sumKey($arr, $key){
    $total = 0;
    foreach($arr as $sub)
      $total += $sub[$key];
    return $total;
  }

  function nullToZero($amount){
    $amount = trim($amount);
    return $amount == '' ? 0 : $amount;
   }

  function handleImage($name, $func, &$article){
    if(!empty($_FILES[$name]['name'])){
      $ih 		= phive('ImageHandler');
      $image_id 	= $ih->createImageFromUpload($name, $ih->getID("news.$name.".$article->getId()));
      $ih->createAlias("news.$name.".$article->getId(), $image_id);
      $article->$func("news.$name.".$article->getId());
    }
  }

  public function getRaceInfo($cur_month, $info = ''){
    $race_info = empty($info) ? $this->getAttribute($cur_month."race_info") : $info;
    if(empty($race_info)){
      $race_info = $this->getAttribute($this->getLastMonth()."race_info");
      $this->setAttribute($cur_month."race_info", $race_info);
    }
    $info = explode('|', $race_info);
    foreach($info as $level){
      $level = explode(':', $level);
      $rarr[] = array('rake_level' => $level[1], 'max_players' => $level[0], 'prize' => $level[2]);
    }

    return $rarr;
  }

  public function getArticleUrl($article){
    return '/'.$article->getCategoryAlias().'/'.$article->getId().'/'.$article->getUrlName();
  }

  public function getPokerArticleUrl($article){
    $promo = substr(strstr($article->getCategoryAlias(), '/'), 1);
    $temp = ($promo) ? explode('/', $article->getCategoryAlias()) : $article->getCategoryAlias();
    $url = '/';
    $url .= (is_string($temp)) ? $temp : $temp[0];
    if($url === '/news') return $url .= '/'.$article->getId().'/'.$article->getUrlName();
    $url .= '/';
    $url .= ($promo) ? $promo : 'news';
    return $url .= '/'.$article->getId().'/'.$article->getUrlName();
  }


}
