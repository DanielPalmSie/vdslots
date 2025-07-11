<?php
require_once __DIR__.'/../../../../../diamondbet/boxes/DiamondBox.php';
class BannerRotatorBoxBase extends DiamondBox{
    function init(){
      $this->mg = phive("MicroGames");
      $this->handlePost(array('sub_tags', 'main_tags', 'banner_link', 'banner_in_link', 'show_banner', 'banner_links', 'banner_images', 'banner_links_logged', 'banner_images_logged', 'rotate_top'), array('show_banner' => 'yes'));
      $logged = isLogged();
      $sLogged = (($logged) ? '_logged' : '');
      $this->banner_images_arr = array_combine(explode(',', $this->{'banner_images' . $sLogged}), explode(',', $this->{'banner_links' . $sLogged}));
    }

    
  function printGameSliders($games){ ?>
    <?php foreach($games as $salias => $sgames):
      if(empty($sgames))
        continue;
      ?>
      <div class="flexslider-item">
        <div class="flexslider-headline"><?php et($salias)  ?></div>
        <div class="flexslider-container">
          <div class="flexslider">
              <ul class="slides">
                <?php foreach($sgames as $sg): ?>
                <li>
                  <img onclick="playMobileGame('<?php echo $sg['ext_game_name'] ?>');" src="<?php fupUri("backgrounds/".$sg['bkg_pic']) ?>" />
                  </li>
              <?php endforeach ?>
              </ul>
          </div>
        </div>
      </div>
    <?php endforeach ?>
  <?php }

  function printDynamic(){
    if(phive()->isEmpty($this->banner_images_arr) || $this->show_banner == 'yes')
      return;
    ?>
    <div class="flexslider-item">
      <div class="big-flexslider-container">
        <div class="big-flexslider" <?php if(isset($_GET['editstrings'])) echo "style='overflow: auto;'" ?>>
            <div class="spinner">
              <div class="rect1"></div>
              <div class="rect2"></div>
              <div class="rect3"></div>
              <div class="rect4"></div>
              <div class="rect5"></div>
            </div>            
            <ul class="slides">
            <?php foreach($this->banner_images_arr as $img_alias => $link): ?>
              <li onclick="goTo('<?php echo llink($link) ?>')">
                <?php img($img_alias, 665, 274); ?>
                <a class="btn btn-xl gradient-default" href="<?php echo llink($link) ?>"><?php et('play.now') ?></a>

                </li>
            <?php endforeach ?>
            </ul>
        </div>
      </div>
    </div>
  <?php }

  function flexJs(){ ?>
    <script>
     $(window).on("load",function() { 
       var aBannerClasses = ['one','two','three','four','five','six','seven','eight','nine','ten','eleven','twelve','thirteen','fourteen','fifteen','sixteen','seventeen','eighteen','nineteen','twenty'];
       $('.startbox').css('visibility', 'visible');  
       $('.big-flexslider').flexslider({
         animation: "slide",
         prevText: "&nbsp;",          
         nextText: "&nbsp;", 
         controlNav: false,
         after: function(slider){
		   $('.flex-direction-nav > li:first-child').removeAttr('class').addClass(aBannerClasses[slider.currentSlide]);
         },
         slideshow: <?php echo $this->rotate_top == 'yes' ? 'true' : 'false'  ?>,
         directionNav: true
       });

     });
    </script>
  <?php } 
  
    function printHTML(){ ?>
      <script type="text/javascript" src="/phive/js/jquery.flexslider-min.js"></script>
      <script type="text/javascript" charset="utf-8">

      var cols = 1;

      function rearrangeSliders(){
        var new_cols = parseInt($(window).width() / 400); ///
        if(new_cols != cols){
          cols = new_cols;

          //$("#viewport").attr('content', 'width = ' + 400 * cols);

          //$(".flexslider-item").width(390 / cols);
        }
      }     
      </script>
      <?php $this->flexJs() ?>
      <div class="startbox">
        <?php $this->printDynamic() ?>
        <?php //$this->printGameSliders($this->sgames) ?>
        <?php //$this->printGameSliders($this->mgames) ?>
      </div>
    <?php }

    public function printExtra(){ ?>
        <p>
            Show custom custom sub tags (alias1,alias2):
            <?php dbInput('sub_tags', $this->sub_tags) ?>
        </p>
        <p>
            Show main tags (videoslots,videopoker):
            <?php dbInput('main_tags', $this->main_tags) ?>
        </p>
        <p>
            Banner links to (ex: /mobile/cashier/deposit/):
            <?php dbInput('banner_link', $this->banner_link) ?>
        </p>
        <p>
            Banner when logged in links to (ex: /mobile/cashier/deposit/):
            <?php dbInput('banner_in_link', $this->banner_in_link) ?>
        </p>
        <p>
            Show top static banner (yes/no), if yes will hide the dynamic top banner:
            <?php dbInput('show_banner', $this->show_banner) ?>
        </p>
        <p>
          Dynamic top banner logged out links (/link1/,/link2/):
            <?php dbInput('banner_links', $this->banner_links) ?>
        </p>
        <p>
          Dynamic top banner logged out images (image.alias1,image.alias2):
            <?php dbInput('banner_images', $this->banner_images) ?>
        </p>
        <p>
          Dynamic top banner logged in links (/link1/,/link2/):
            <?php dbInput('banner_links_logged', $this->banner_links_logged) ?>
        </p>
        <p>
          Dynamic top banner logged in images (image.alias1,image.alias2):
            <?php dbInput('banner_images_logged', $this->banner_images_logged) ?>
        </p>
        <p>
          Rotate (slideshow) top banner (yes/no):
            <?php dbInput('rotate_top', $this->rotate_top) ?>
        </p>
    <?php }
}
