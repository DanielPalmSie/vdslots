<?php
require_once __DIR__.'/../../phive/modules/BoxHandler/boxes/diamondbet/JsBannerRotatorBoxBase.php';
class JsBannerRotatorBox extends JsBannerRotatorBoxBase{

  function printCSS(){
      loadCss("/diamondbet/css/" . brandedCss() . "bannerrotator.css");
  }

  function printExtraHTML(){ ?>
<?php if(empty($this->show)) return; ?>
  <?php $this->printCarouselJs() ?>
<?php }

}
