<?php
require_once __DIR__.'/../../phive/modules/BoxHandler/boxes/diamondbet/MobileStartBoxBase.php';
class MobileStartBox extends MobileStartBoxBase{

  function flexJs(){ ?>
    <script>
      $(window).on("load",function() {
        $('.mobile-start-box .flexslider').each(function() {
          $(this).flexslider({
            <?php if(isIpad()): ?>
              minItems: 2,
              maxItems: 2,
              itemWidth: 240,
            <?php endif ?>
            animation: "slide",
            controlNav: $(this).hasClass('mobile-news-slide') ? false : true,
            slideshow: false,
            directionNav: false
          });
        });

        $('.mobile-start-box .big-flexslider').each(function() {
          $(this).flexslider({
            <?php if(isIpad()): ?>
              minItems: 2,
              maxItems: 2,
              itemWidth: 240,
            <?php endif ?>
            animation: "slide",
            controlNav: $(this).hasClass('mobile-news-slide') ? false : true,
            slideshow: <?php echo $this->rotate_top == 'yes' ? 'true' : 'false'  ?>,
            directionNav: false
          });
        });

      });
    </script>
  <?php }
}
