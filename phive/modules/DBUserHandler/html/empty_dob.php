<?php
require_once __DIR__ . '/../../../phive.php';
require_once __DIR__.'/../../../html/display_base_diamondbet.php';

if(!empty($_GET['lang'])){
  phive('Localizer')->setLanguage($_GET['lang']);
  phive('Localizer')->setNonSubLang($_GET['lang']);
}

class EmptyDob {

    public static function stepOne()
    {

    }

}

