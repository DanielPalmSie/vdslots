<?php
require_once __DIR__.'/MgPlayBoxBase.php';
class MgGameListBoxBase extends MgPlayBoxBase{
  
  
  function init(){
    if(isset($_POST['save_settings']) && $_POST['box_id'] == $this->getId()){
      $this->setAttribute("tag", $_POST['tag']);
    }
    
    $this->str_tag = $this->getAttribute("tag");
    $this->tag = explode(',', $this->str_tag);
  }
  
  public function sortGames($sort,$tag)
  {
    switch($sort)
    {
      case 'popular':
	return phive('MicroGames')->getGamesOrderLimit('popularity', 10);
	break;
      case 'recent':
	return phive('MicroGames')->getRecentPlayed();
	break;
    }
    
    return phive('MicroGames')->getTaggedBy($tag);
  }
  
  public function printHTML(){
    if(!empty($_GET['DeploymentUrl'])){
      $this->printPlayArea();
    }else{
      $gh 		= phive('MicroGames');
      $games 		= $this->sortGames($_POST['search'],$this->tag);
      $games 		= array_chunk($games, 4);
      //$xml_path = phive('Localizer')->getCurNonSubLang().'_'.$this->tag.'_data.xml';
      
      if(empty($_POST['search']))
	$xml_path	= "/diamondbet/getgames.php?search={$this->str_tag}";
      else
	$xml_path	= "/diamondbet/getgames.php?search={$_POST['search']}&tag={$this->str_tag}";
      
      $flash_path = "/diamondbet/swf/Main.swf";
      
      if(phive()->ieversion() != -1){
	$uid = uniqid();
	//$xml_path .= "&asdf={$uid}";
	$flash_path .= "?{$uid}";
      }
?>

<script type="text/javascript">

 var listFlashvars = {};
 //listFlashvars.xmlPath = "/diamondbet/xml/<?php echo $xml_path ?>";
 listFlashvars.xmlPath = "<?php echo $xml_path; ?>";
 listFlashvars.backgroundColor = "0x000000";
 listFlashvars.labelColor = "0xFFFFFF";
 
 var listParams = {};
 listParams.loop = "true";
 listParams.menu = "true";
 listParams.quality = "best";
 listParams.scale = "scale";
 listParams.salign = "tl";
 listParams.wmode = "window";
 var attributes = {};
 swfobject.embedSWF("<?php echo $flash_path ?>", "exclusive-swf", "919", "309", "10.0.0", "/diamondbet/swf/expressInstall.swf", listFlashvars, listParams, attributes);

</script>
<div class="game-list-bg">
  <div style='position:relative;text-align: center;width: 100%;height:10px;top:-10px;'>
    <form method="post">
      <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
      <div class="button-fiexd-dark">
	<div style="float:left;width:5px;">&nbsp;</div>
	<input style='float:left;position:relative;top:3px' type='radio' onclick="this.form.submit();" name='search' value='popular'/><div class="a-big" style='float:left;position:relative;top:5px'>Most Popular</div>
	<input style='float:left;position:relative;top:3px' type='radio' onclick="this.form.submit();" name='search' value='recent'/><div class="a-big" style='float:left;position:relative;top:5px;'>My Recent</div>
	<input style='float:left;position:relative;top:3px' type='radio' onclick="this.form.submit();" name='search' value='<?php echo $this->tag ?>'/><div class="a-big" style='float:left;position:relative;top:5px;'>All</div>
	<div style="float:left;width:10px;">&nbsp;</div>
      </div>
    </form>
  </div>
  <div class="top-flash-holder">
    <div id="exclusive-swf"></div>
  </div>
</div>
<?php
}
}

function printExtra(){?>
<p>
  Game tag(s), ex videoslots,blackjack or just videoslots:
  <input type="text" name="tag" value="<?php echo $this->str_tag ?>"/>
</p>
<?php }
}
