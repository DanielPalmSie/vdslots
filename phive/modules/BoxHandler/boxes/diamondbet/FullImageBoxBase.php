<?php
require_once __DIR__.'/../../../../../diamondbet/boxes/DiamondBox.php';

class FullImageBoxBase extends DiamondBox{
  public function init(){
      $this->handlePost(array('link'));
  }

    function printHTML()
    {
        $page_id = phive('Pager')->getId();

        $mobile_class = phive()->isMobile() ? 'fullimagebox-image-container-mobile' : '';
        ?>
        <div class="left fullimagebox-image-container <?php echo $mobile_class; ?>" style="margin-bottom: -3px;">
            <?php if(!empty($this->link)): ?>
                <a href="<?php echo $this->link; ?>">
            <?php endif; ?>

                <?php
                if($page_id == 324 && $this->getId() == 946) {

                    // check if we have a bonus code, and if so get the image aliases
                    $freespins_all_image_alias = '';
                    $freespins_exceptions_image_alias = '';
                    if(!empty(phive('Bonuses')->getBonusCode())) {
                        $freespins_all_image_alias          = phive('ImageHandler')->getImageAliasForBonusCode('banner.freespins.freespins.all.');
                        $freespins_exceptions_image_alias   = phive('ImageHandler')->getImageAliasForBonusCode('banner.freespins.freespins.exceptions.');
                    }

                    if($this->canNetent()) {
                        $default_alias = "banner.freespins.freespins.all.default";
                        $bonus_alias = $freespins_all_image_alias;
                    } else {
                        $default_alias = "banner.freespins.freespins.exceptions.default";
                        $bonus_alias = $freespins_exceptions_image_alias;
                    }
                    // check if this default alias exists, if not use the old alias
                    if(!phive('ImageHandler')->getID($default_alias)) {
                        $default_alias = "fullimagebox.".$this->getId();
                    }
                    img($bonus_alias, 961, empty($this->height) ? null: $this->height, $default_alias, false, null, '', '', true, true);

                } elseif($page_id == 298 && $this->getId() == 886) {

                    $default_alias = 'banner.welcomebonus.welcomebonus.default';

                    $bonus_alias = phive('ImageHandler')->getImageAliasForBonusCode('banner.welcomebonus.welcomebonus.');

                    // check if this default alias exists, if not use the old alias
                    if(!phive('ImageHandler')->getID($default_alias)) {
                        $default_alias = "fullimagebox.".$this->getId();
                    }
                    img($bonus_alias, 961, empty($this->height) ? null: $this->height, $default_alias, false, null, '', '', true, true);

                } else {
                    $alias = "fullimagebox.".$this->getId();
                    $defaultAlias = $alias;
                    img($alias, 961, empty($this->height) ? null: $this->height, $defaultAlias, false, null, '', '', true, true);
                }
                ?>

            <?php if(!empty($this->link)): ?>
                </a>
            <?php endif; ?>
        </div>
        <?php
    }

  function printExtra(){?>
      <p>
        <label for="link">Image link (if empty there will be no link):</label>
        <input type="text" name="link" value="<?php echo $this->link; ?>" id="link"/>
        Box id: <?=$this->getId()?>
      </p>
      <?php
  }

}
