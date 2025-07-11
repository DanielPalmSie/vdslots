<?php
die('not used anymore');

require_once __DIR__.'/../../../../../diamondbet/boxes/DiamondBox.php';
class WinnersJpsBoxBase extends DiamondBox{
  
  function init(){
    $this->winners = phive('MicroGames')->getCurWinners(3);
  }
  
  function printHTML(){ ?>
<div class="jp-winners">
  <div class="red-white-headline">
    <span> <?php et('latest.winners') ?> </span>
  </div>	
  <div class="content">
    <table>
      <?php foreach($this->winners as $w): ?>
	<tr>
	  <td class="orange"><?php echo $w['firstname'] ?></td>
	  <td class="yellow"><?php et($w['tag']) ?></td>
	  <td class="orange"><?php efEuro($w['wins']) ?></td>
	</tr>
      <?php endforeach ?>
    </table>
  </div>
  <div class="red-white-headline">
    <span> <?php et('emu.jackpots') ?> </span>
  </div>	
  <div class="content">
    <?php foreach(phive('MicroGames')->getAllJps("jp_img != ''", 3) as $jp): ?>
      <div class="jp">
	<img src="<?php fupUri($jp['jp_img']) ?>" />
	<br/>
	<span class="yellow">
	  <?php efEuro($jp['jp_value']) ?>
	</span>
      </div>
    <?php endforeach ?>
  </div>
</div>

<?php }
}
