<?php
require_once __DIR__.'/MgPlayBoxBase.php';
class MgJpListBoxBase extends MgPlayBoxBase{

  function init(){ }

  function getCurSym($jp){
    if($jp['network'] == 'bsg')
      return phive("Currencer")->getSetting("cur_sym");
    else
      return cs();
  }

  function is404($args){
    return false;
  } 
  
  public function printHTML(){
    $ciso = ciso();
    /** @var MicroGames $gh */
    $gh = phive('MicroGames');
    $jps = $gh->getAllJpsGames("jps.currency = '$ciso'", "gms.device_type = 'flash'", "GROUP BY jps.jp_id");
    $chunks	= array_chunk($jps, $gh->getSetting('jackpot_game_rows', 4));
    ?>
    <div class="boxes2">
      <div class="boxes-container2">
        <div class="boxes-holder">
          <?php foreach($chunks as $chunk): ?>
            <div class="boxes-frame2">
              <div class="boxes-inner">
                <?php for($i = 0; $i < $gh->getSetting('jackpot_game_rows', 4); $i++):
                  $jp = $chunk[$i];
                if($i > 0){
                  echo '</div><div class="box2">';
                } else {
                  echo '<div class="box2">';
                }
                ?>
                    <?php if(!empty($jp)): ?>
                      <p> <strong style="font-size: 16px;"> <?php echo $jp['game_name'] ?> </strong></p>
                      <h3><?php echo cs().' '.number_format(max(0, (int)$jp['jp_value']) / 100) ?> </h3>
                      <div style="float:left; padding-bottom: 10px;">
                        <a href="<?php echo $gh->getUrl('', $jp) ?>">
                          <img class="jp_image" src="<?php echo $gh->carouselPic($jp) ?>" title="<?php echo $jp['game_name'] ?>" alt="<?php echo $jp['game_name'] ?>" />
                        </a>
                      </div>
                    <?php endif ?>                 
                <?php endfor ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php

  }

  function printExtra(){?>
  <?php }
}
