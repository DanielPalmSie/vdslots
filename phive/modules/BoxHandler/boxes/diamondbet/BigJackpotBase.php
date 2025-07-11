<?php
require_once __DIR__.'/DualBoxBase.php';
class BigJackpotBase extends DualBoxBase{

  function init(){
    
    $config = phive('Config')->getByTagValues('jackpotbox');
    
    $this->inc_with = $config['inc-with'];
    $this->shave_with = $config['shave-with'];
  }
  
  function printHtml(){
    $this->printJs(908, 103, "jp_total_big");
?>
<div class="frame-block">
  <div class="frame-holder">
    <div id="jp-swf"></div>
  </div>
</div>

<?php }
}
