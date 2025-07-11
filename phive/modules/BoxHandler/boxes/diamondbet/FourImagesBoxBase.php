<?php
require_once __DIR__.'/../../../../../diamondbet/boxes/DiamondBox.php';
class FourImagesBoxBase extends DiamondBox{
  
  function printHtml(){
    ?>
    <?php foreach (range(1, 4) as $num): ?>
    <?php if($num > 1) { 
    	echo '</div><div class="four-images' . (($num == 4) ? ' last' : '' ) . '">';
    } else {
    	echo '<div class="four-images' . (($num == 4) ? ' last' : '' ) . '">';
    }
    ?>
      <?php img('img.'.phive('Localizer')->getCurNonSubLang().$num.'.box'.$this->getId(), 234, 108) ?>
        <a class="btn btn-l gradient-normal" href="<?php echo llink('/' . $this->{'four_image_' . $num} . '/') ?>"><?php et('read.more') ?></a>
    <?php endforeach ?>
    </div>
    <?php
  }  
  
  function init(){
    $this->handlePost(array('four_image_1', 'four_image_2', 'four_image_3', 'four_image_4'));
  }
   
  function printExtra(){ ?>
  <?php foreach (range(1, 4) as $num): ?>
    <p>
      <label>Image URL <?php echo $num; ?>: </label>
      <input type="text" name="four_image_<?php echo $num; ?>" value="<?php echo $this->{'four_image_' . $num} ?>" />
    </p>
    <?php endforeach ?>
    <?php
    } 
}