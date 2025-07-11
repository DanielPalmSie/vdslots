<?php
require_once __DIR__.'/../../../../../diamondbet/boxes/DiamondBox.php';
class GameCarouselBoxBase extends DiamondBox{
  
  public function init(){
    $this->loggedin = true; // isLogged();

  }
  
  public function printHTML(){?>
<script type="text/javascript" src="/phive/js/jcarousel/lib/jquery.jcarousel.min.js"></script>
<link rel="stylesheet" type="text/css" href="/phive/js/jcarousel/skins/gmcarousel/skin.css" />
<script>
 $(document).ready(function(){
   jQuery('#gmcarousel').jcarousel({ });
   $(".gc-over").hover(
     function(){ $(this).animate({opacity: 1}, 100, 'linear'); },
     function(){ $(this).animate({opacity: 0}, 100, 'linear'); }
   );
 });
</script>
<div class="gm-carousel">
  <ul id="gmcarousel" class="jcarousel-skin-gmcarousel">
    <?php foreach(phive('MicroGames')->getPopular() as $g): ?>
      <li>
	<img <?php jsOnClick(phive('MicroGames')->getUrl('', $g, false)) ?> src="<?php echo phive('MicroGames')->carouselPic($g) ?>" style="width: 175px; height: 103px;" />
	<div class="gc-over">
	  <?php if(!$this->loggedin): ?>
	    <div class="gc-btn-holder">
	      <?php g29Btn(t('practice'), '', "playGameNow('{$g['game_id']}')") ?>
	    </div>
	  <?php endif ?>
	  <div class="<?php echo $this->loggedin == true ? "gc-btn-holder-in" : "gc-btn-holder" ?>">
	    <?php o29Btn(t('play'), phive('MicroGames')->getUrl('', $g, false, true)) ?>
	  </div>							
	</div>
      </li>
    <?php endforeach ?>
  </ul>
</div>
<?php }

function printExtra(){?>
<p>
  Label:
  <input type="text" name="str_name" value="<?php echo $this->str_name ?>"/>
</p>
<?php }
}
