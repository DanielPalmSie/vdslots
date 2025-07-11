<?php
require_once __DIR__.'/../../../../../diamondbet/boxes/DiamondBox.php';
class GamesLeftBoxBase extends DiamondBox{

  public function init(){


  }

  public function printHTML(){?>
<script>
  $(document).ready(function(){
    $("#games-left").find("li").hover(
      function(){ $(this).animate({"margin-left": "85px"}, 200, 'jswing'); },
      function(){ $(this).animate({"margin-left": "5px"}, 200, 'jswing'); }
    );

    $("#games-left-btn").on('click', function() {
      if ($(this).hasClass('toggled')) {
        $("#games-left").animate({"left": "-180px"}, 200, 'jswing');
        $(this).removeClass('toggled');
      } else {
        $("#games-left").animate({"left": "-80px"}, 200, 'jswing');
        $(this).addClass('toggled');
      }
    });
 });
</script>
<div id="games-left-btn" class="side-btn games-left-btn">
  <div class="left-tower-txt">
    <?php $this->towerTxt(t('last'), 'tower-side') ?>
  </div>
  <div class="left-tower-txt">
    <?php $this->towerTxt(t('played'), 'tower-side-small') ?>
  </div>
</div>
<div id="games-left" class="games-left">
  <ul class="clean-list">
    <?php foreach(phive('MicroGames')->getPopular(4) as $g): ?>
      <li>
	<img <?php jsOnClick(phive('MicroGames')->getUrl('', $g, false)) ?> src="<?php echo phive('MicroGames')->carouselPic($g) ?>" style="width: 175px; height: 103px;" />
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
