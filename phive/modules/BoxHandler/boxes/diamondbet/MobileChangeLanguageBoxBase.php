<?php
require_once __DIR__.'/../../../../../diamondbet/boxes/DiamondBox.php';
class MobileChangeLanguageBoxBase extends DiamondBox{
    function init(){
        $languages = phive("Localizer")
            ->filterLanguageOptions(cu())
            ->getAllLanguages(null ,'language, selectable');

        $languages = phive()->reKey($languages, 'language');
        $this->available_languages = [];
        
        foreach (phive("Localizer")->getCountries(true) as $c){
            if($languages[$c['language']]['selectable'] == "1" && $c['subdomain']){
                $link = phive("Localizer")->getNonSubLang($c['language'])."/mobile/";
                $image_url = "/phive/images/flags_iso2/".$c['subdomain'].".png";
                array_push($this->available_languages, ["link" => $link, "image_url" => $image_url]);
            }
        }
    }
    
    function printHTML(){ ?>
    <div class="frame-block">
      <div class="frame-holder">
        <div class="mobile-change-language-cont">
          <h3 class="mobile-change-language-headline"><?php echo t('change.language') ?></h3>
          <br/>
          <?php foreach ($this->available_languages as $c):?>
            <a href="<?php echo $c['link'] ?>">
              <img class="img50-cube" src="<?php echo $c['image_url'] ?>"/>	
            </a>
          <?php endforeach ?>
        </div>			
      </div>	
    </div>
    <?php }
}
