<?php

use Videoslots\User\ThirdPartyVerificationFields\DataTransferObject\TopPartData;
use Laraphive\Domain\Content\DataTransferObjects\ContactUs\FormData;

class MboxCommon {

    /**
     * Handle the common logic for the top bar on the popups.
     *
     * @param \Videoslots\User\ThirdPartyVerificationFields\DataTransferObject\TopPartData $data
     */
    public function topPart(TopPartData $data){
        $hide_close = $data->isHideClose();
        $redirect_on_mobile = $data->isRedirectOnMobile();
        $close_mobile_game_overlay = $data->isCloseMobileGameOverlay();
        $top_left_icon = $data->isTopLeftIcon();
        $box_headline_alias = $data->getBoxHeadlineAlias();
        $target = $data->getTarget();
        $box_id = $data->getBoxId();
        $redirect_link = $data->getCloseButton()->getRedirectTo();
?>
    <div class="lic-mbox-header relative">
        <?php if($hide_close === false):
            $params = " '$box_id', " . phive()->getJsBool($redirect_on_mobile). ", " . phive()->getJsBool($close_mobile_game_overlay);
            ?>
            <div class="lic-mbox-close-box" onclick="closePopup(<?=$params;?>)"><span class="icon icon-vs-close"></span></div>
        <?php endif; ?>
        <?php if($top_left_icon === true):?>
            <div class="lic-mbox-icon"></div>
        <?php endif; ?>
        <div class="lic-mbox-title">
            <?php et($box_headline_alias) ?>
        </div>
    </div>
    <script>
        function closePopup(box_id, redirectOnMobile, closeMobileGameOverlay) {
            redirectOnMobile = typeof redirectOnMobile === "boolean" ? redirectOnMobile : true;
            closeMobileGameOverlay = closeMobileGameOverlay || false;

            if(parent.$('#vs-popup-overlay__iframe').length && closeMobileGameOverlay) {
                parent.$('.vs-popup-overlay__header-closing-button').click();
            }

            // we go to a new page on mobile because all the popups should be in a new page
            return isMobile() && redirectOnMobile
                ? window.location.href = "<?php echo $redirect_link ?>"
                : <?=$target?>.$.multibox('close', box_id);
        }
    </script>
    <?php
    }

    /**
     * Return current player, if logged out reload the page and go to the lobby.
     *
     * @return DBUser
     */
    public function getUserOrDie()
    {
        $u_obj  = cuPl();
        if(empty($u_obj)) {
            die(jsRedirect(phive('Casino')->getBasePath()));
        } else {
            return $u_obj;
        }
    }
}

function fastDepositIcon($type = 'mini', $return = false, string $fast_psp_option = null) {
    if (is_null($fast_psp_option)) {
        $fast_psp_option = getPaymentServiceProvider();
    }

    if($return){
        ob_start();
    }
    if(!empty($fast_psp_option)):
    ?>
        <div class="fast-deposit__btn-<?php echo $type?>" onclick="showFastDepositPopup('<?php echo $fast_psp_option ?>')">
            <img src="<?php echo getFastDepositIconSrc($fast_psp_option, $type); ?>" class="<?php echo $type ?>-psp-icon">
        </div>
    <?php endif;
    if($return){
        return ob_get_clean();
    }
}

function getFastDepositIconSrc($fast_psp_option, $type = 'mini'): string
{
    $localizedIconUri = fupUri($fast_psp_option, true)."-{$type}-".strtolower(getCountry()).".png";
    $localizedIconPath = $fast_psp_option."-{$type}-".strtolower(getCountry()).".png";
    if (fileOrImageExists('/file_uploads', $localizedIconPath)) {
        return $localizedIconUri;
    }

    return fupUri($fast_psp_option, true)."-{$type}.png";
}

/**
 * @return string|null
 */
function getPaymentServiceProvider(): ?string
{
    return phive('Cashier')->getFastPsp();
}

function getLoginHeaderFromContext($context)
{
    return $context === 'login' ? 'login' : 'verify.with.nid.'.phive('Licensed')->getLicCountry();
}

function displayGameRibbonImage($g, $pics = [], $return = false, $is_tall_version = false)
{
    $boosterPic = isset($pics['weekend_booster']) && !empty($pics['weekend_booster']) ? $pics['weekend_booster'] : 'booster';

    $alt = '';
    $img_url = '';

    // Weekend booster
    if(!empty($g['payout_extra_percent']) && $g['payout_extra_percent'] != 0 && fileOrImageExists('/file_uploads', "ribbons/$boosterPic.png")) {
        $img_url = fupUri("ribbons/$boosterPic.png", true);
        $alt = t('weekend.booster.ribbon');
    }
    // Live casino
    elseif(!empty($g['ribbon_pic']) && strpos($g['ribbon_pic'], 'live-casino') !== false) {
        $img_url = fupUri("ribbons/{$g['ribbon_pic']}.png", true);
        $alt = t('live.casino');
    }
    // Others
    elseif (!empty($g['ribbon_pic'])) {
        $postfix = $is_tall_version ? '_bigthumbnail' : '';

        $img_url = fupUri("ribbons/{$g['ribbon_pic']}_".cLang().$postfix.".png", true);
    }

    if(!empty($img_url)) {
        if($return) {
            return $img_url;
        } else {
            ?>
            <img src="<?= $img_url ?>" class="ribbon-pic" alt="<?= $alt ?>"/>
            <?php
        }
    }
}

/***
 * @param array $game
 * This function accepts a game and compare the values of game tag and game operator
 * with the BoxHandler config settings tag and operator to call the displayGameRibbonImage function
 * to display the ribbon pic on banner image.
 ***/
function displayBannerRibbonImage($game) {
    $operators = phive('BoxHandler')->getSetting('banner_ribbon_allowed_operators');
    $tags = phive('BoxHandler')->getSetting('banner_ribbon_allowed_tags');
    if($game['ribbon_pic'] !== '' && in_array($game['operator'], $operators) && in_array($game['tag'], $tags)) {
        displayGameRibbonImage($game);
    }
}

function getUserBoLink($user_id) {
    return phive('DBUserHandler')->getBOAccountUrl($user_id);
}

function jsSanitize($p_sValue,$p_aOptions){
  if(empty($p_aOptions)) {
    $p_aOptions = array(
      "'" => '&apos;'
    );
  }
  return strtr(trim($p_sValue), $p_aOptions);
}

function wsUpdateBalance(){
  if(!empty($GLOBALS['ws_update_balance']))
    return;
  $GLOBALS['ws_update_balance'] = true;
  if(isLogged() && hasWs()){
?>
  <script>
   $(document).ready(function(){
     if(hasWs() && ("#top-balance").length > 0){
       doWs('<?php echo phive('UserHandler')->wsUrl('balance') ?>', function(e) {
         var res = JSON.parse(e.data);
         if(typeof res['bonus'] !== 'undefined') {
           $("#top-bonus-balance").html(nfCents(res.bonus));
         }
         $("#top-balance").html(nfCents(res.cash));
         if(typeof res['mp_balance'] !== 'undefined') {
           $("#mp-top-balance", $("#mbox-iframe-mp-box").contents()).html(res.mp_balance);
         }
       });
     }
   });
  </script>
  <?php
  }
}

function oddEven($i, $odd = 'odd', $even = 'even'){
  echo $i % 2 === 0 ? $even : $odd;
}

function okCenterBtn($on_click){ ?><br/><br/><center><button onclick="<?php echo $on_click ?>" class="btn btn-l btn-default-l w-125 neg-margin-top-25 margin-ten-bottom">OK</button></center><?php }

function limitEdate($start_date, $end_date, $format = 'date'){
  if(p('no.date.limit'))
    return $end_date;
  $format = $format == 'date' ? 'Y-m-d' : 'Y-m-d H:i:s';
  $info = prettyTimeInterval(strtotime($end_date) - strtotime($start_date));
  if($info['days'] > 32)
    $end_date = phive()->hisMod('+31 day', $start_date, $format);
  return $end_date;
}

function doOb($func){
  ob_start();
  $func();
  return ob_get_clean();
}

function btn($class, $txt, $link, $onclick, $width, $icon = null){
    if (!empty($icon)) {
        loadCss("/diamondbet/fonts/icons.css");
    }
    $link = !empty($link) ? "goTo('$link')" : $onclick;
    $style = !empty($width) ? 'style="width: '.$width.'px;"' : '';
  ?>
  <button class="<?php echo $class ?>" onclick="<?php echo $link ?>" <?php echo $style ?>>
      <?php if (!empty($icon)) { ?>
        <div class="icon icon-<?php echo $icon ?> vs-icon"></div>
      <?php } ?>
      <span><?php echo $txt ?></span>
  </button>
<?php }

function atag($contents, $url, $class=""){
  if(empty($url) || empty($contents))
    return $contents;
  $class = empty($class) ? '' : ' class="'.$class.'" ';
  $url = ' href="'.$url.'"';
  echo "<a$class$url>$contents</a>";
}

function drawStartEndJs(){ ?>
  <?php
  $new_version_jquery_ui = phive('BoxHandler')->getSetting('new_version_jquery_ui') ?? '';

  loadJs("/phive/js/jQuery-UI/".$new_version_jquery_ui."jquery-ui.min.js");
  loadCss("/phive/js/jQuery-UI/".$new_version_jquery_ui."jquery-ui.min.css"); ?>
  <script>
    jQuery(function() {
      //var dpOpts = {dateFormat: 'yy-mm-dd'};
      var dpOpts = {
        dateFormat  : 'yy-mm-dd',
        changeMonth : true,
        changeYear  : true,
        yearRange   : "-100:+0"
      };
      jQuery("#sdate").datepicker(dpOpts);
      jQuery("#edate").datepicker(dpOpts);
    });
  </script>
<?php
}

function retJsonOrDie($field, $errstr, $ajax = false){
  $translate = t('register.'.$field) . ': ' . t($errstr)."<br>";
  if($ajax)
    die(json_encode(array("error" => $translate)));
  else
    return json_encode(array("error" => $translate));
}

function imgTag($src){
    return '<img src="'.$src.'"/>';
}

function countryFlag($iso = ''){
  $iso = empty($iso) ? $_SESSION['local_usr']['country'] : $iso;

  $flag_override = phive()->getSetting('flag_override');
  if (!empty($flag_override) && isset($flag_override[$iso])) {
      $iso = $flag_override[strtoupper($iso)];
  }

  $file = phive()->getSetting('site_loc').'phive/images/small_flags/'.strtolower($iso).'.png';
  if(!is_file($file))
      $iso = 'www';
    if(in_array($iso, ['AU', 'NZ'])){
        return false;
    }

?>
  <img class="top-profile-image" src="/phive/images/small_flags/<?php echo strtolower($iso) ?>.png"/>
<?php }

function drawStartEndHtml(){ ?>
  <tr>
    <td>Start date:</td>
    <td>
      <?php dbInput('sdate', $_REQUEST['sdate']) ?>
    </td>
  </tr>
  <tr>
    <td>End date:</td>
    <td>
      <?php dbInput('edate', $_REQUEST['edate']) ?>
    </td>
  </tr>
<?php }

function drawFancyJs(){
  if(empty($GLOBAL['fancy_ok'])){
  ?>
<!--  <?php /*loadJs("/phive/js/fancybox/fancybox/jquery.fancybox-1.3.4.js") */?>
  --><?php /*loadJs("/phive/js/fancybox/fancybox/jquery.easing-1.3.pack.js") */?>
  <?php loadJs("/phive/js/multibox.js") ?>
  <?php /*loadCss("/phive/js/fancybox/fancybox/jquery.fancybox-1.3.4.css") */?>
  <?php loadCss("/diamondbet/css/" . brandedCss() . "fancybox.css") ?>
<?php
  }
  $GLOBAL['fancy_ok'] = true;
}

function jsInclude($script){  ?>
  <script type="text/javascript" src="<?php echo $script ?>"></script>
<?php }

function jsTag($code){  ?>
  <script type="text/javascript"><?php echo $code ?></script>
<?php }

function mboxMsg($alias){
    $content = addslashes(t($alias));
    // we cannot echo a content between quotes on multiline or JS will parse it as an error
    $content = str_replace("\n", "", $content);
    $code = "mboxMsg('$content', true)";
    jsTag($code);
}


function drawTblHead($fields){ ?>
  <tr class="stats_header">
    <?php foreach($fields as $field): ?>
      <td><?php echo $field ?></td>
    <?php endforeach; ?>
  </tr>
<?php }

function drawTblBody($rows, $draw = array(), $actions){
  $draw 	= empty($draw) ? array_keys($rows[0]) : $draw;
  $i 		= 0;
   foreach($rows as $row):
   ?>
     <tr class="<?php echo ($i % 2 == 0) ? "fill-odd" : "fill-even" ?>">
      <?php foreach($draw as $field): ?>
        <?php if ($field == 'target') : ?>
          <td><?php profileLink( $row[$field] , $row[$field]) ?></td>
        <?php else: ?>
          <td><?php echo $row[$field] ?></td>
        <?php endif ?>
      <?php endforeach ?>
      <?php foreach($actions as $a => $id_label):
      [$id_key, $label] = $id_label;
      ?>
        <td>
          <a href="<?php echo ($a[0] == '/' ? '' : '?').$a ?>=<?php echo $row[$id_key] ?>">
            <?php echo $label ?>
          </a>
        </td>
      <?php endforeach ?>
     </tr>
   <?php $i++; endforeach; ?>
<?php }

function drawTable($rows, $draw = array(), $actions = array()){ ?>
  <table class="stats_table">
  <?php
    $draw 	= empty($draw) ? array_keys($rows[0]) : $draw;
    drawTblHead($draw);
    drawTblBody($rows, $draw, $actions);
  ?>
  </table>
<?php }

$button_id = 0;
function dbButton($text,$link,$align = "left",$extra = null, $extra_attr = ''){
  global $button_id;
  $button_id++;
  if($text):
  if($align == "center")
    $style = "margin-left:auto;margin-right:auto;";

  else if($align == "right")
    $style = "float:right;";
  ?>
   <div>
     <a href="<?=$link?>" <?php echo $extra_attr ?>><?php echo $text; ?></a>
   </div>
  <?php
  endif;
}

  function dbSubmit($label, $name = 'submit'){ ?>
  <input type="submit" name="<?php echo $name ?>" value="<?php echo $label ?>" />
<?php }

function dbBinary($value, $name, $labels = array('Yes', 'No'), $values = array(0, 1)){
  //$value = empty($value) ? $values[0] : $value;
  ?>
  <?php echo $labels[0] ?><input type="radio" name="<?php echo $name ?>" value="<?php echo $values[1] ?>" <?php if($value === $values[1]) echo 'checked=""'?> />
  <?php echo $labels[1] ?><input type="radio" name="<?php echo $name ?>" value="<?php echo $values[0] ?>" <?php if($value === $values[0]) echo 'checked=""'?> />
  <?php
}

function dbCheck($name, $value = ''){ ?>
  <input type="checkbox" name="<?php echo $name ?>" id="<?php echo $name ?>" <?php echo empty($value) ? '' : "checked=\"checked\"" ?>/>
<?php }

function dbRadio($name, $id, $value = ''){ ?>
  <input type="radio" name="<?php echo $name ?>" id="<?php echo $id ?>" <?php echo empty($value) ? '' : "checked=\"checked\"" ?>/>
<?php }

/**
 * Display the checkbox:
 *
 * @param mixed $user User identifying element.
 * @param string $setting name and id attribute.
 * @param string $default checked or not.
 * @param string $hidden is used to insert hiddle input type just before the checkbox to retain value of unchecked checkbox.
 *
 * @return @void.
 */
function dbCheckSetting($user, $setting, $default = 'checked', $hidden = false){
    if ($hidden) {
        ?>
        <input type="hidden" name="<?php echo $setting ?>" id="<?php echo $setting ?>" value="off"/>
        <?php
    }
    ?>
    <input type="checkbox" name="<?php echo $setting ?>" id="<?php echo $setting ?>" <?php echo ($user->getSetting($setting) == 1 || (!$user->hasSetting($setting) && $default == 'checked')) ? 'checked="checked"' : '' ?> />
<?php }

function dbCheckSettingSelected($user, $setting) : bool {
    return $user->getSetting($setting) == 1;
}

function dbCheckSubSettingSelected($user, $key, $content) : string {
    $selected = false;
    foreach ($content['options'] as $option_key => $option) {
        if ( $user->getSetting("privacy-{$key}-{$option_key}") == 1 ) {
            $selected = true;
            break;
        }
    }
    // if one of them is selected then don't select opt-out
    if ($selected) return '';
    return 'checked';
}

function printDisplayJs(){
  if($GLOBALS['display.js'] == 'loaded')
    return;
  echo '<script src="/diamondbet/html/display.js" type="text/javascript" charset="utf-8"></script>';
  $GLOBALS['display.js'] = 'loaded';
}

function dbFormButton($text, $id = ''){
  ?>
    <div class="small_button" onclick="submitForm('<?php echo $id ?>')">
      <?php echo $text; ?>
    </div>
  <?php
}

function h($str){
  return "<h1>$str</h1>";
}

function stringCutOff($str, $max=30){
  return substr(trim($str), 0, $max);
}

function printMoney($amount, $currency){
  if($currency == "USD")
    return "$ ".$amount;
  else if($currency == "EUR")
    return "€ ".$amount;
  return $amount;
}

  function imgOrPdf($url, $width, $height){ ?>
      <?php if(strpos($url, '.pdf') !== false): ?>
          <a href="<?php echo $url ?>">PDF</a>
      <?php else: ?>
          <img src="<?php echo $url ?>" style="width: <?php echo $width ?>px; height: <?php echo $height ?>px;"/>
      <?php endif ?>
  <?php }

function prettyTimeInterval($secs){
  return phive()->timeIntervalArr($secs);
}

function digitalClock($class = ''){ ?><ul class="digital-clock <?php echo $class ?>"><li class="hour">00</li><li class="min">00</li></ul><?php }

function digitalFullClock($class = '',$hours='00',$mins='00',$secs='00'){ ?><ul class="digital-full-clock <?php echo $class ?>"><li class="hour"><?= $hours ?></li><li class="min"><?= $mins ?></li><li class="sec"><?= $secs ?></li></ul><?php }

function showTac($news, $disp_bullet = true){
  if($news->getTac() != ''){ ?>
    <?php if($disp_bullet): ?>
      <p class="item">•</p>
    <?php endif ?>
    <p class="item">
      <a href="#" onClick="javascript:popUp('<?php echo '/diamondbet/html/showtac.php?id='.$news->getId() ?>', 400, 500);">
        <?php echo t("newstop.tac"); ?>
      </a>
    </p>
  <?php
  }
}

/***
 * @param string $name The name attr of the select box.
 * @param array $values The values of the form array(value => label)
 * @param string|array $select Preselect this value
 * @param array $start Optional default values that should be in the beginning
 * @param mixed $auto_complete Optional value for the autocomplete
  ***/
  function dbSelect($name, $values, $select = '', $start = array(), $class = '', $multi = false, $extra_attr = '', $id = true, $disabled = false, $auto_complete = ''){
  ?>
      <select <?php echo $extra_attr ?> name="<?php echo $name ?>" <?= $id? 'id = "'.$name.'"' : ''; ?> <?php echo $multi ? 'multiple="multiple" size="10"' : '' ?> <?php if(!empty($class)) echo 'class="'.$class.'"'  ?> <?php if($disabled) echo 'disabled=true' ?> <?php if(!empty($auto_complete)) echo 'autocomplete="'.$auto_complete.'"'  ?>>
      <?php if (!empty($start)): ?>
        <option value="<?php echo $start[0] ?>" <?php if($_POST[ $name ] == $start[0] || $select == $start[0]) echo 'selected="selected"'; ?>>
          <?php echo $start[1] ?>
        </option>
      <?php endif ?>
      <?php foreach($values as $key => $value): ?>
        <?php if(is_array($value)): ?>
          <?php if($value['type'] == 'optgroup'): ?>
            <optgroup label="<?php et($key) ?>"></optgroup>
          <?php else: ?>
          <option
            value="<?php echo $key ?>"
            <?php if ((is_array($select) && in_array($key, $select)) || ($select == $key && !empty($select))) echo 'selected="selected"'?>
            <?php if(is_array($value['data'])): ?>
              <?php foreach($value['data'] as $c_key => $c_value):?>
                <?php echo 'data-'. $c_key . '="' . $c_value . '"'?>
              <?php endforeach ?>
            <?php endif ?>
          >
            <?php echo !empty($value['name']) ? $value['name'] : '' ?>
          </option>
        <?php endif ?>
        <?php else: ?>
          <option value="<?php echo $key ?>" <?php if ((is_array($select) && in_array($key, $select)) || ($select == $key && !empty($select))) echo 'selected="selected"'; ?>>
            <?php echo $value ?>
          </option>
        <?php endif ?>
      <?php endforeach ?>
    </select>
  <?php
}


  function profileLink($username, $string = '') {?>
    <a href="<?= llink("/admin/userprofile/?username=$username") ?>"><?= $string ?></a>
<?php }
  function accLink($uid, $page = '', $string = ''){
    $page = empty($page) ? '' : "$page/";
    $string = empty($string) ? $uid : t($string); // this will end up printing the user_id instead of the username but on the only 2 place where it's used a $string is provided.
  ?>
  <a href="<?php echo phive('UserHandler')->getUserAccountUrl($page) ?>"><?php echo $string ?></a>
<?php }

function cisosSelect($start_null = false, $select = '', $name = 'currency', $class = '', $start_extra = array(), $show_symbol = true, $all = false, $legacy = true, $disabled = false){
  $tmp 	= cisos(false, $all, $legacy);
  $values = array();
  $force_currency = lic('getForcedCurrency', []);

  if(!empty($force_currency)) {
     foreach ($tmp as $r) {
        if ($r['code'] === $force_currency) {
            if (phive('Localizer')->doExtraLocalization()) {
                $printable_currency = t("currency.name.{$r['code']}");
            } else {
                $printable_currency = $r['code'];
            }
            $values[$r['code']] = ($show_symbol ? $r['symbol'] . " " : '') . $printable_currency;
            break;
        }
     }
  }
  else {
    foreach ($tmp as $r) {
      if (phive('Localizer')->doExtraLocalization()) {
          $printable_currency = t("currency.name.{$r['code']}");
      } else {
          $printable_currency = $r['code'];
      }
      $values[$r['code']] = ($show_symbol ? $r['symbol']." " : '').$printable_currency;
    }
  }

  if(!empty($start_extra))
    $values = array_merge($start_extra, $values);

  if($start_null)
    $start = array('', t('select'));

  dbSelect($name, $values, $select, $start, $class, false, '', true, $disabled);
}

function dbSelectWith(
    string $name,
           $data,
    string $valueKey,
           $labelKeys,
           $selectByDefault = '',
    array  $prependingKeyValue = [],
    string $extraSelectClasses = '',
    ?array $optionsAttrKeys = null
): void
{
    $processedData = [];

    foreach ($data as $sub) {
        $nameStr = is_array($labelKeys)
            ? trim(implode(' ', array_filter(array_map(fn($key) => $sub[$key] ?? '', $labelKeys))))
            : ($sub[$labelKeys] ?? '');

        $processedData[$sub[$valueKey]] = ['name' => $nameStr];

        if ($optionsAttrKeys) {
            foreach ($optionsAttrKeys as $optionAttrKey) {
                if (isset($sub[$optionAttrKey])) {
                    $processedData[$sub[$valueKey]]['data'][$optionAttrKey] = $sub[$optionAttrKey];
                }
            }
        }
    }

    dbSelect($name, $processedData, $selectByDefault, $prependingKeyValue, $extraSelectClasses);
}

/**
 * Method returns an html input
 * @name string
 * @value string
 * @type string
 * @class string
 * @extra_attr string
 * @id bool
 * @min bool
 * @max bool
 * @placeholder string
 */
function dbInput($name, $value = '', $type = 'text', $class = '', $extra_attr = '', $id = true, $min = false, $max = false, $placeholder = ''){
  $name = htmlspecialchars($name);
  $value = htmlspecialchars($value);
  $type = htmlspecialchars($type);
  $placeholder = htmlspecialchars($placeholder);
  $attributes = [
      "name='$name'",
      'value="'.$value.'"',
      "type='$type'",
  ];
  if(!empty($id)) { $attributes[] = "id='$name'"; }
  if(!empty($class)) { $attributes[] = "class='$class'"; }
  if(is_numeric($min)) { $attributes[] = "min='$min'"; }
  if(is_numeric($max)) { $attributes[] = "max='$max'"; }
  if(!empty($extra_attr)) { $attributes[] = "$extra_attr"; }
  if(!empty($placeholder)) { $attributes[] = "placeholder='$placeholder'"; }
  ?>
    <input <?php echo implode(' ', $attributes); ?>/>
  <?php
}

/**
 * Method returns an html text area
 * @name string
 * @value string
 * @class string
 * @extra_attr string
 * @id bool
 * @placeholder string
 */
function dbInputTextArea($name, $value = '', $class = '', $extra_attr = '', $id = true, $placeholder = ''){
    $name = htmlspecialchars($name);
    $value = htmlspecialchars($value);
    $placeholder = htmlspecialchars($placeholder);
    $attributes = [
        "name='$name'",
        "value='$value'",
    ];
    if(!empty($id)) { $attributes[] = "id='$name'"; }
    if(!empty($class)) { $attributes[] = "class='$class'"; }
    if(!empty($extra_attr)) { $attributes[] = "$extra_attr"; }
    if(!empty($placeholder)) { $attributes[] = "placeholder='$placeholder'"; }
    ?>
    <textarea <?php echo implode(' ', $attributes); ?>></textarea>
    <?php
}

function jsRedirect($url, $is_iframe = false)
{
    $target_window = $is_iframe ? 'window.top' : 'window';
    ?>
    <script> <?php echo $target_window ?>.location.href = '<?php echo phive('Localizer')->langLink('', $url) ?>'; </script>
    <?php
}

function jsOnClick($url){?> onClick = "goTo('<?php echo phive('Localizer')->langLink('', $url) ?>')" <?php }
function jumpTo($url){?> onClick = "goTo('<?php echo $url ?>')" <?php }
function goToLlink($url){ return "goTo('".llink($url)."')"; }

function jsReloadBase(){?>
  <script> jsReloadBase(); </script>
<?php }

function jsReload($extra = ''){?>
  <script> window.location.href = document.URL+'<?php echo $extra ?>'; </script>
<?php }

function fieldToHeadline($str){
  return ucfirst(str_replace('_', ' ', $str));
}

function getYearMonths($year){
  $months = array();
  foreach(range(1, 12) as $num)
    $months[] = $year.'-'.str_pad($num, 2, '0', STR_PAD_LEFT);
  return $months;
}

function getLcYearMonths($year){
  $months = array();
  foreach(range(1, 12) as $num)
    $months[] = phive()->lcDate($year.'-'.str_pad($num, 2, '0', STR_PAD_LEFT), '%b %G');
  return $months;
}

function padMonth($mnum){
  return str_pad($mnum, 2, '0', STR_PAD_LEFT);
}

function efEuro($cents, $return = false, $divide_by = 100, $decimals = 2){
  $amount = cs().' '.number_format($cents / $divide_by, $decimals);
  if($return)
    return $amount;
  else
    echo $amount;
}

function efIso($cents, $return = false, $divide_by = 100, $decimals = 2){
    $amount = ciso().' '.number_format($cents / $divide_by, $decimals);
    if($return)
        return $amount;
    else
        echo $amount;
}

function cfPlain($cents, $return = false, $divide_by = 100){
  $amount = cs().' '.($cents / $divide_by);
  if($return)
    return $amount;
  else
    echo $amount;
}

function niceFileInput($filename, $btn_class, $plain = false){
  $GLOBALS['nice_file_inputs']++;
  $i = $GLOBALS['nice_file_inputs'];
?>
  <script>
    $(document).ready(function(){
      $("#real-file-input<?php echo $i ?>").change(function(){ $("#fake-file-input<?php echo $i ?>").val( $(this).val() ); });
    });
  </script>
  <div class="fileinputs">
    <input name="<?php echo $filename ?>" id="real-file-input<?php echo $i ?>" type="file" class="hidden-file" />
    <div class="fakefile">
      <table>
        <tr>
          <td> <input id="fake-file-input<?php echo $i ?>" /> </td>
          <td> <button class="<?php echo $plain?'':$btn_class ?>"><?php et('browse') ?></button> </td>
        </tr>
      </table>
    </div>
  </div>
<?php }

function handleDatesSubmit($weekly = false){
  $year 			= empty($_GET['year']) ? date('Y') : $_GET['year'];

  if(empty($_GET['sdate'])){
    if(empty($_GET['month']) && ($_GET['week']=='') ) {
      $sdate    = $year.'-01-01';
      $edate    = "$year-12-31";
      $s_month    = 1;
      $e_month    = date('n', strtotime($edate));
      $mgroup     = true;
      $type     = $weekly? 'week' : 'month';
    }
    else if (!empty($_GET['week'])) {
      $sdate = date('Y-m-d', strtotime($year."W".$_GET['week']."1"));
      $edate = date('Y-m-d', strtotime($year."W".$_GET['week']."7"));
      $days = phive()->getDateInterval($sdate, $edate);
      $s_month    = date('j', strtotime($sdate));
      $week = true;
      $mgroup     = 'day';
      $type     = 'day';
    }
    else{
      $mgroup 		= 'day';
      $month 		= $_GET['month'];
      $sdate 		= "$year-$month-01";
      $s_month    = 1;
      $e_month		= date('t', strtotime($sdate));
      $edate 		= "$year-$month-$e_month";
      $type 		= 'day';
      $total_net 	= 0;
      $stats 		= array();
    }
  }else{
    $sdate 		= $_GET['sdate'];
    $edate 		= $_GET['edate'];
    $diff               = phive()->subtractTimes($edate, $sdate, 'd');
    if($diff <= 30){
      $mgroup 		= 'day';
      $type 		= 'day';
    }else if (!$weekly) {
      $mgroup 		= true;
      $type 		= 'month';
    }
    else {
      $mgroup = true;
      $type = 'week';
    }
    $s_month    = 1;
    $total_net 		= 0;
    $stats 		= array();
  }

  $sstamp		= "$sdate 00:00:00";
  $estamp		= "$sdate 24:00:00";

  $rarr = array();
  foreach(array('year', 'days', 'sdate', 'edate', 'e_month', 's_month', 'mgroup', 'type', 'week', 'month', 'total_net', 'stats', 'sstamp', 'estamp') as $var)
    $rarr[$var] = $$var;
  return $rarr;
}

function getUsersDailyLink($col, $val){
  return "/admin/casino-stats/?year={$_REQUEST['year']}&user_comp=%253D&month={$_REQUEST['month']}&sdate={$_REQUEST['sdate']}&edate={$_REQUEST['edate']}&day={$_REQUEST['day']}&limit={$_REQUEST['limit']}&username={$_REQUEST['username']}&currency={$_REQUEST['currency']}&user_col=$col&user_val=$val&submit=Submit";
}

/**
 * Get the page background
 * @return string
 */
function getBg(){

  // check if we have a landing page bg defined
  $sBg = phive('Pager')->fetchLandingPage();

  if(empty($sBg) && !empty($_COOKIE['bkg'])){
    // No need to check if the image actually does exist
    if(ctype_digit($_COOKIE['bkg'])){
      $sBg = 'user_backgrounds/' . $_COOKIE['bkg'] . '.jpg';
    }
  }

  // still empty than get one of the predefined bg
  if(empty($sBg)){
    $sBg = phive('Config')->getRand('bkg', true);
  }

  if(!empty($sBg)){
    return "background-image: url(".fupUri($sBg, true).");";
  }

  // sorry no bg images where found
  return '';
}

function getUserDailyLinkPeriod($sdate, $edate, $username){
  return "/admin/casino-stats/?sdate=$sdate&edate=$edate&username=$username&submit=Submit";
}

function getUsersFromAffiliateLink($sdate, $edate, $affe_id, $currency, $bonus_code = ''){
  if(!empty($bonus_code))
    $extra = "&user_col=bonus_code&user_comp=%253D&user_val=$bonus_code";
  return "/admin/casino-stats/?sdate=$sdate&edate=$edate&stats_col=affe_id&stats_comp=%253D&stats_val=$affe_id&currency=$currency&order_by=bets&desc_asc=desc$extra";
}

function printChart($data, $keys, $num = 0, $cur = true){
  if(empty($keys))
    return;

  $mons = array(1 => "Jan", 2 => "Feb", 3 => "Mar", 4 => "Apr", 5 => "May", 6 => "Jun", 7 => "Jul", 8 => "Aug", 9 => "Sep", 10 => "Oct", 11 => "Nov", 12 => "Dec");
  $categories = "";
  if (count($data) == 4){
    foreach ($data as $i => $v) {
      $categories .= "'{$v['date']}',";
    }
  }
  else {
    foreach ($data as $i => $v) {
      $arr = explode("-", $v['date']);
      $categories .= "'{$arr[0]}-{$arr[1]}',";
    }
  }
  $categories = trim($categories, ",");

  $label = implode(', ', array_map('ucfirst', $keys));

  $hdata = array();

  $c = end($data);
  $currency = $c['currency'];

  foreach($keys as $key){
    $obj = new stdClass();
    $tmp = array();

    foreach($data as $row){
      if ($cur)
        $tmp[] = (int)$row[$key] / 100;
      else $tmp[] = (int)$row[$key];
    }
    $obj->name = ucfirst($key);
    $obj->data = $tmp;
    $hdata[] = $obj;
  }
  ?>
  <div id="highchart<?php echo $num ?>" style="width: 100%; height: 250px;"></div>
  <script src="/phive/js/highcharts/js/highcharts4.js" type="text/javascript"></script>
  <script>
    var chart<?php echo $num ?>; // globally available
    $(document).ready(function() {
          chart<?php echo $num ?> = new Highcharts.Chart({
             chart: {
                renderTo: 'highchart<?php echo $num ?>',
                type: 'column'
             },
            plotOptions: {
                column: {
                    pointPadding: 0.2,
                    borderWidth: 0
                }
            },
             title: {
                text: '<?php echo ucfirst($label) ?>'
             },
             xAxis: {
                  categories: [ <?php echo $categories ?> ]
             },
             yAxis: {
                title: {
                   text: '<?php echo ucfirst($label) ?>'
                }
             },
             tooltip: {
                  formatter: function() {
                          return '<b>'+ this.series.name +'</b><br/>'+
                          this.x +': '+ this.y +' <?php echo $cur? $currency: "" ?>';
                  }
               },
             series: <?php echo json_encode($hdata) ?>
          });
       });
  </script>
<?php }

function highChart($data, $keys, $date_lbl = 'date'){

  if(empty($keys))
    return;

  $label = implode(', ', array_map('ucfirst', $keys));

  $hdata = array();

  $data = phive()->sort2d($data, $date_lbl);

  foreach($keys as $key){
    $obj = new stdClass();
    $tmp = array();

    foreach($data as $row)
      $tmp[] = array((strtotime($row[$date_lbl]) * 1000) + 43200000, (int)$row[$key] / 100);

    $obj->name = ucfirst($key);
    $obj->data = $tmp;
    $hdata[] = $obj;
  }
  ?>
  <div id="highchart" style="width: 100%; height: 400px;"></div>
  <script src="/phive/js/highcharts/js/highcharts.js" type="text/javascript"></script>
  <script>
    var chart1; // globally available
    $(document).ready(function() {
          chart1 = new Highcharts.Chart({
             chart: {
                renderTo: 'highchart',
                type: 'spline'
             },
             title: {
                text: '<?php echo ucfirst($label) ?>'
             },
             xAxis: {
                type: 'datetime',
                  dateTimeLabelFormats: { month: '%e. %b' }
             },
             yAxis: {
                title: {
                   text: '<?php echo ucfirst($label) ?>'
                }
             },
             tooltip: {
                  formatter: function() {
                          return '<b>'+ this.series.name +'</b><br/>'+
                          Highcharts.dateFormat('%e. %b', this.x) +': '+ this.y +' <?php echo ciso() ?>';
                  }
               },
             series: <?php echo json_encode($hdata) ?>
          });
       });
  </script>
<?php }


function wDayCls($default, $day, $override = '', $cancel = false){

  if($cancel)
    return $default;

  if((!empty($_REQUEST['year']) && !empty($_REQUEST['month'])) || !empty($override)){
    $wday = date('N', strtotime(!empty($override) ? $override : "{$_REQUEST['year']}-{$_REQUEST['month']}-".padMonth($day)));
    if($wday == 6)
      return 'fill_saturday';
    if($wday == 7)
      return 'fill_sunday';
  }
  return $default;
}

function tableSorter($id, $map = array(), $widget = 'zebra'){
  $str = '';
  foreach($map as $n => $s)
    $str .=  "$n: { sorter: \"$s\" },";
  $str = trim($str, ", ");
  ?>
  <script>
    function fixStripes() {
      $('table tr').removeClass('odd even fill-even fill-odd').odd().addClass('fill-even').end().even().addClass('fill-odd');
    }
  </script>
<?php }

//TODO remove?
function currencyMenu(){
  if(phive("Currencer")->getSetting('multi_currency') !== true)
    return;
  if(isLogged())
    return;
  ?>
  <div class="currency-menu">
    <ul>
      <?php foreach(cisos(true) as $cur): ?>
        <li onclick="goTo('?site_currency=<?php echo $cur ?>')">
          <img src="/diamondbet/images/<?= brandedCss() ?>currencies/<?php echo $cur . ($cur == ciso() ? '_chosen' : '') ?>.png" />
        </li>
        <li class="crmenu-separator">&nbsp;</li>
      <?php endforeach ?>
    </ul>
  </div>
<?php }

function searchUserCol(){ ?>
  <tr>
    <td>User column (eg: country, province, sex, city, bonus_code, verified_phone):</td>
    <td>
      <?php dbInput('user_col', $_REQUEST['user_col']) ?>
    </td>
  </tr>
  </tr>
    <tr>
    <td>User comparator, &gt; (greater than) , &lt; (lower than), = (equal) or != (not equal):</td>
    <td>
      <?php dbSelect('user_comp', array(urlencode('=') => '=', urlencode('>') => '>', urlencode('<') => '<', urlencode('!=') => '!='), $_REQUEST['user_comp']) ?>
    </td>
  </tr>
  <tr>
    <td>User value (eg: se, Male/Female, stockholm, code, 0/1):</td>
    <td>
      <?php dbInput('user_val', $_REQUEST['user_val']) ?>
    </td>
  </tr>
<?php }

function yearDateForm($affe_id = null, $show_cur = false, $show_extra = false, $show_group_by = false, $show_bonus_codes = false, $as_csv = false, $show_alt = true, $classes = false, $show_weeks = false, $show_nodes = false){ ?>
  <form action="" method="get">
    <?php if (!empty($_GET['action'])): ?>
    <input type="hidden" name="action" value="<?php echo $_GET['action'] ?>">
    <?php endif ?>
    <?php if (!empty($_GET['username'])): ?>
    <input type="hidden" name="username" value="<?php echo $_GET['username'] ?>">
    <?php endif ?>
    <table <?php if ($classes) { ?> class="simple_airy_table" <?php } ?>>
      <tr>
        <td>
          <span class="small-bold"> <?php et('year') ?>:&nbsp; </span>
        </td>
        <td>
          <?php dbSelect('year', phive('Former')->fc()->getYearsFrom(2011), '', array( '', t('select') ), $classes ? 'narrow-input' : '') ?>
        </td>
        <td>
          <span class="small-bold"> &nbsp;<?php et('month') ?>:&nbsp; </span>
        </td>
        <td>
          <?php dbSelect('month', phive('Former')->fc()->getMonths(), '', array( '', t('select') ), $classes ? 'narrow-input' : '') ?>
        </td>
        <?php if($show_weeks) : ?>
        <td>
          <span class="small-bold"> &nbsp;<?php et('week') ?>:&nbsp; </span>
        </td>
        <td>
          <?php dbSelect('week', phive('Former')->fc()->getWeeks(), '', array( '', t('select') ), $classes ? 'narrow-input' : '') ?>
        </td>
        <?php endif ?>
        <?php if($show_cur): ?>
          <td>
            <span class="small-bold"> &nbsp;Currency:&nbsp; </span>
          </td>
          <td>
            <?php cisosSelect(true) ?>
          </td>
        <?php endif ?>
        <?php if($show_bonus_codes): ?>
          <td>
            <span class="small-bold"> &nbsp;<?php et('bonus.code') ?>:&nbsp; </span>
          </td>
          <td>
            <?php dbSelect('bonus_code', phive('Affiliater')->bonusCodesSelect($affe_id, 'bonus_code'), '', array( '', t('select') ), $classes ? 'narrow-input' : '') ?>
          </td>
        <?php endif ?>
        <?php if(!empty($affe_id) && !$show_bonus_codes): ?>
          <td>
            <span class="small-bold"> &nbsp;<?php et('bonus.code') ?>:&nbsp; </span>
          </td>
          <td>
            <?php dbSelect('bonus_code', phive('Affiliater')->bonusCodesSelect($affe_id, 'bonus_code'), '', array( '', t('select') ), $classes ? 'narrow-input' : '') ?>
          </td>
          <td>
            <span class="small-bold"> &nbsp;<?php et('registered.in.period') ?>:&nbsp; </span>
          </td>
          <td>
            <?php dbSelect('only_registered', array(t('no') => t('no'), t('yes') => t('yes')), '', array(), $classes ? 'narrow-input' : '') ?>
          </td>
        <?php endif ?>
        <td>
          <input type="submit" <?php if ($classes) { ?> class="submit" <?php } ?> name="submit-stats" value="<?php et('submit') ?>" />
          <?php if($as_csv): ?>
            <?php phive("UserSearch")->csvBtn() ?>
          <?php endif ?>
        </td>
      </tr>
      <?php if($show_extra): ?>
        <tr>
          <td>User col:</td>
          <td>
            <?php dbInput('user_col', $_REQUEST['user_col']) ?>
          </td>
          <td>User comparator:</td>
          <td>
            <?php dbSelect('user_comp', array(urlencode('=') => '=', urlencode('>') => '>', urlencode('<') => '<', urlencode('!=') => '!='), $_REQUEST['user_comp']) ?>
          </td>
          <td>User value:</td>
          <td>
            <?php dbInput('user_val', $_REQUEST['user_val']) ?>
          </td>
          <td>&nbsp;</td>
        </tr>
        <tr>
          <td>Reg start date:</td>
          <td>
            <?php dbInput('reg_start_date', $_REQUEST['reg_start_date']) ?>
          </td>
          <td>Reg end date:</td>
          <td>
            <?php dbInput('reg_end_date', $_REQUEST['reg_end_date']) ?>
          </td>
          <td>Bonus code:</td>
          <td>
            <?php dbInput('bonus_code', $_REQUEST['bonus_code']) ?>
          </td>
          <td>&nbsp;</td>
        </tr>
        <tr>
          <td>Start date:</td>
          <td>
            <?php dbInput('sdate', $_REQUEST['sdate']) ?>
          </td>
          <td>End date:</td>
          <td>
            <?php dbInput('edate', $_REQUEST['edate']) ?>
          </td>
          <td></td>
          <td></td>
          <td>&nbsp;</td>
        </tr>
      <?php endif ?>
    </table>
    <?php if($show_group_by): ?>
      <table>
        <tr>
          <td>Group by user col:</td>
          <td>
            <?php dbInput('group_by', $_REQUEST['group_by']) ?>
          </td>
            <td>
              Deposit alternative / network:
            </td>
            <td>
              <?php
                $legacy_networks = ['Puggle' => 'puggle'];
                $current_networks = phive('Cashier')->getNetworkSelect();
                dbSelect('deposit_alt', array_merge($current_networks, $legacy_networks), $_REQUEST['deposit_alt'], array( '', t('select') ), 'narrow-input') ?>
            </td>
            <td>
                Source:
            </td>
            <td>
                <?php dbSelect('scheme', ['bill' => 'bill', 'bank' => 'bank', 'trustly' => 'trustly'], $_REQUEST['scheme'], array( '', t('select') ), 'narrow-input') ?>
            </td>
          <td>&nbsp;</td>
        </tr>
      </table>
    <?php endif ?>
    <?php
    if($show_nodes){
        echo 'Select Database Node: ';
        $sh_arr = array_keys(phive('SQL')->getSetting('shards'));
        $shards = array_combine($sh_arr, $sh_arr);
        dbSelect('node', $shards, $_REQUEST['node'] ?? -1, [-1, 'All Nodes']);
    }
    ?>
  </form>
<?php }

function basicMailForm(FormData $form, $show_email = true, $onclick = '', $form_id = '', $form_class = ''){ ?>
    <script>
        function sendEmailUs(){
          let hasErrors = false;
          let errorMessage = '';

          const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

          const emailValue = $("#from").val();
          if (!emailValue) {
              errorMessage += "<?php et('email.required') ?>" + '<br>';
              hasErrors = true;
          } else if (!emailRegex.test(emailValue)) {
              errorMessage += "<?php et('email.invalid.format') ?>" + '<br>';
              hasErrors = true;
          }

          if (!$("#subject").val()) {
              errorMessage += "<?php et('subject.required') ?>" + '<br>';
              hasErrors = true;
          }

          if (!$("#message").val()) {
              errorMessage += "<?php et('message.required') ?>" + '<br>';
              hasErrors = true;
          }

          if (!$("#captcha").val()) {
              errorMessage += "<?php et('captcha.required') ?>" + '<br>';
              hasErrors = true;
          }

          if (hasErrors) {
              $("#errorZone").html(errorMessage).removeClass("email-success");
              return;
          }

            mgJson({
                action: "send-email-us",
                from: $("#from").val(),
                subject: $("#subject").val(),
                message: $("#message").val(),
                captcha: $("#captcha").val()
            },function(ret){
                if(ret.res == 'fail'){
                    $("#errorZone").html(ret.error).removeClass("email-success");
                }else{
                    $("#errorZone").html(ret.res).addClass("email-success");
                    $("#send-email-form").html();
                    $("#from").val("");
                    $("#subject").val("");
                    $("#message").val("");
                    $("#captcha").val("");
                }
            });
        }
    </script>
    <table id="send-email-form" class="registerform">
      <?php if($show_email): ?>
        <tr>
          <td><?php et($form->getEmail()->getLabel()) ?></td>
          <td>
            <input
              type="<?= $form->getEmail()->getType() ?>"
              name="from"
              id="from"
              class="send-email-form__input"
              value="<?php echo empty($_POST['from']) ? $_SESSION['local_usr']['email'] : $_POST['from'] ?>"
              <?php echo lic('getMaxLengthAttribute', ['email']); ?>
              required
            />
          </td>
        </tr>
      <?php endif ?>
      <tr>
        <td><?php et($form->getSubject()->getLabel()) ?></td>
        <td>
          <input
            type="<?= $form->getSubject()->getType() ?>"
            name="<?= $form->getSubject()->getName() ?>"
            id="subject"
            class="send-email-form__input"
            value="<?php echo $_POST['subject'] ?>"
            required
          />
        </td>
      </tr>
      <tr>
        <td><?php et($form->getMessage()->getLabel()) ?></td>
        <td>
          <textarea
            name="<?= $form->getMessage()->getName()?>"
            id="message"
            class="send-email-form__textarea"
            cols="33"
            rows="10"
            required
          ><?php echo $_POST['message'] ?></textarea>
        </td>
      </tr>
      <tr>
        <td> <img src="<?php echo PhiveValidator::captchaImg() ?>"/></td>
        <td>
          <input
            type="<?= $form->getCaptcha()->getType()?>"
            name="<?= $form->getCaptcha()->getName()?>"
            id="captcha"
            class="send-email-form__input"
            value=""
            required
          />
        </td>
      </tr>
      <tr>
        <td>&nbsp;</td>
          <td><?php btnDefaultL(t($form->getSubmitButton()->getValue()), '', 'sendEmailUs()', 150) ?></td>
      </tr>
    </table>
  </form>
<?php }

 function depGo($from_registration = false) {
    // if the site type is Paynplay go to 'showPayNPlayPopupOnLogin()'
    if (isPNP() && isLogged()) {
        return "showPayNPlayPopupOnDeposit()";
    }

     if (isPNP() && !isLogged()) {
         return "showPayNPlayPopupOnLogin()";
     }

    $deposit_link = phive("Cashier")->getSetting('deposit_link');
    if ($GLOBALS['site_type'] == 'mobile' || $_REQUEST['site_type'] == 'mobile') {
        return "goTo('" . llink('/mobile'.$deposit_link) . "')";
    } else {
        if ($from_registration) {
            return "goTo(jsGetBase()+'?show_deposit=true')";
        } else {
            return "mboxDeposit('".  llink($deposit_link) ."')";
        }
    }
}


/**
 * @return void
 */
function withdrawalGo(){
    if (isPNP()) {
        if (!phive('Cashier')->canWithdraw(cu())['success']){
            return "PayNPlay.showWithdrawalFailurePopup('withdrawal-block')";
        }
        return "PayNPlay.showWithdrawalPopup()";
    }

    $link = llink('/cashier/withdraw/');
    if ($GLOBALS['site_type'] == 'mobile' || $_REQUEST['site_type'] == 'mobile') {
        $link = llink('/mobile/cashier/withdraw/');
    }

    return "goTo('$link')";
}


function parentDepGo($from_registration = false) {
    $deposit_link = phive("Cashier")->getSetting('deposit_link');
    if ($GLOBALS['site_type'] == 'mobile' || $_REQUEST['site_type'] == 'mobile') {
        $link = llink('/mobile'.$deposit_link);
        return "goTo('$link')";
    } else {
        if ($from_registration) {
            return "parentGoTo('/?show_deposit=true')";
        } else {
            $link = llink($deposit_link);
            return "mboxDeposit('$link')";
        }
    }
}

function closeFancyAndDepGo(){
  return "closeFancyAnd('".addslashes(depGo())."')";
}

function failBonusConfirm(){ ?>
  <div id="fail-confirm" style="display:none;">
    <div class="fail-confirm"> <?php et(phive('Bonuses')->getBonusString('bonus.fail.not.fulfilled.html')) ?> </div>
    <div class="fail-continue">
        <?php btnDefaultL(t('continue'), '', 'deletebonus', 100) ?>
        <?php btnDefaultL(t('cancel'), '', 'mboxClose()', 100) ?>
    </div>
  </div>
<?php }

/**
 * Create the "popup" to inform players "Active bonus cannot be forfeit during gameplay".
 */
function cannotForfit()
{ ?>
    <div id="on-going-game-session" style="display:none;">
        <div class="fail-confirm"> <?php et('active.bonus.not.forfeit.gameplay.html') ?> </div>
        <div class="fail-continue">
            <?php btnDefaultL(t('cancel'), '', 'mboxClose()', 100) ?>
        </div>
    </div>
<?php }


function failBonusWrongGame($play_func, $bonus){ ?>
  <?php et( empty($bonus['keep_winnings']) ? 'wrong.game.bonus.fail.html' : 'wrong.game.bonus.fail.keep.winnings.html' ) ?>
  <br/>
  <center>
    <table>
      <tr>
        <td class="fail-continue-left-btn">
          <div id="play-anyway-btn">
            <?php btnDefaultL(t('play.anyway'), '', $play_func, 150) ?>
          </div>
        </td>
        <td class="fail-continue-right-btn">
          <?php btnDefaultL(t('cancel'), '', 'mboxClose()', 150) ?>
        </td>
      </tr>
    </table>
  </center>
<?php }

function depositTopPending($pending){ ?>
  <div class="deposit-top-pending-box">
    <span><?php et('dbox.balance') ?></span>
    <span id="pending-balance" class="margin-ten-left"><?php efEuro(phive('UserHandler')->userTotalCash($_SESSION['mg_id'])) ?></span>
    <div id="dbox-cancel-section" class="inline">
      <span class="margin-ten-left"><?php et('dbox.pending') ?></span>
      <span class="margin-ten-left"><?php efEuro($pending['amount']) ?></span>
      <div id="dboxpending-<?php echo $pending['id'] ?>" class="cancel-pending margin-ten-left pointer cancel-pending-dbox"><?php et('cancel') ?></div>
    </div>
  </div>
<?php }

function modalTwo($lbl1, $func1, $lbl2, $func2){ ?>
  <br/>
  <br/>
  <table>
    <tr>
      <td>
        <?php btnDefaultXs(t($lbl1), '', $func1, 150) ?>
      </td>
      <td style="width: 30px;">
      </td>
      <td>
        <?php btnDefaultXs(t($lbl2), '', $func2, 150) ?>
      </td>
    </tr>
  </table>
<?php }

function inOutErrDie($err){
  $translate = "";
  foreach($err as $field => $errstr)
    $translate .= t('register.'.$field) . ': ' . t($errstr)."<br>";

  die( json_encode(array("error" => $translate) ) );
}

function miscCache($id_str){
    return phive()->getMiscCache($id_str);
}

function jsGoTo($url){?>
  <script> goTo('<?php echo $url ?>'); </script>
<?php }

function multipleUpload($form_id){?>
  <?php loadJs("/phive/js/jquery.form.min.js") ?>
  <script type="text/javascript">
   $(document).ready(function(){
     var bar = $('.bar');
     var percent = $('.percent');
     var uploadstatus = $('#uploadstatus');

     $('<?php echo $form_id?>').ajaxForm({
       beforeSend: function() {
         uploadstatus.empty();
         var percentVal = '0%';
         bar.width(percentVal)
         percent.html(percentVal);
       },
       uploadProgress: function(event, position, total, percentComplete) {
         var percentVal = percentComplete + '%';
         bar.width(percentVal)
         percent.html(percentVal);
       },
       success: function() {
         var percentVal = '100%';
         bar.width(percentVal)
         percent.html(percentVal);
       },
       complete: function(xhr) {
         uploadstatus.html(xhr.responseText);
       }
     });
   });
  </script>
  <style>
   .progress { position:relative; width:400px; border: 1px solid #ddd; padding: 1px; border-radius: 3px; }
   .bar { background-color: #B4F5B4; width:0%; height:20px; border-radius: 3px; }
   .percent { position:absolute; display:inline-block; top:3px; left:48%; }
  </style>
  <div class="progress">
    <div class="bar"></div >
    <div class="percent">0%</div >
  </div>
  <div id="uploadstatus"></div>

<?php }

function multiUpload($options){?>
  <?php loadJs("/phive/js/upload.js") ?>
  <script>
   jQuery(document).ready(function(){
     $("#upload").multiUpload(<?php echo json_encode($options) ?>);
   });
  </script>
  <style>
   #upload-cont input{
     margin-top: 5px;
     margin-bottom: 5px;
   }

   .progress-cont {
     height: 14px;
     border: 1px solid #aaa;
     width: 200px;
     margin-top: 20px;
   }

   #progress-bar{
     background-color: #005;
     width: 0%;
     font-size: 12px;
     line-height: 14px;
     height: 14px;
   }

   #progress-text{
     position: relative;
     top: -14px;
     color: #555;
     font-size: 10px;
     line-height: 14px;
     padding-left: 2px;
   }


   #upload-link {
     cursor: pointer;
     margin-bottom: 5px;
     display: inline-block;
   }

   #clear-files{
     display: none;
     cursor: pointer;
     margin-top: 5px;
     font-weight: bold;
     font-size: 14px;
     text-decoration: underline;
   }
  </style>
  <div class="pad10">
    <div id="upload-cont">
      <input id="upload" type="file" name="files" multiple="true">
      <div class="progress-cont">
        <div id="progress-bar"></div>
        <div id="progress-text">0 %</div>
      </div>
      <div id="clear-files">Upload More (Refresh Page)</div>
    </div>
  </div>
<?php }


function noCashBox($btnf = 'btnDefaultXl'){ ?>
  <div id="nocash_play_content" style="display: none;">
    <div><?php et('nocash.play.info') ?></div>
    <br/>
    <table>
      <tr>
        <td><?php $btnf(t('yes'), '', closeFancyAndDepGo(), 195) ?></td>
        <td style="width: 10px;"></td>
        <td><?php $btnf(t('no'), '', "playGameDeposit()", 195) ?></td>
      </tr>
    </table>
  </div>
<?php }

function advancedStatsTable($stats, $group_by, $show_extra = true, $do_link = true, $date_key = 'date', $func = 'casinoStatsNumCols', $tbl = 'users_daily_stats', $headline_func = 'casinoStatsHeadlines', $extra_cols = array()){
  $uh        = phive('UserHandler');
  $num_cols  = is_array($func) ? $func : $uh->$func();
  $cols      = phive('SQL')->getColumns($tbl);

  $tsarr     = array(0 => 'date');

  if(empty($group_by))
    $tsarr[] = 'text';
  foreach ($num_cols as $value)
    $tsarr[] = 'bigcurrency';
  $headlines = array(empty($group_by) ? 'Date' : ucfirst($group_by));
  if(empty($group_by))
    $headlines[] = 'W. D.';
  $headlines = array_merge($headlines, is_array($headline_func) ? $headline_func : $uh->$headline_func());
  if(!empty($_REQUEST['as_csv']))
    $csv_cols = array_merge(array('date'), $num_cols);
  $gross = phive()->sum2d($stats, 'gross');
  $btot  = phive()->sum2d($stats, 'bets');
  tableSorter("stats-table", $tsarr);
?>
  <div style="padding: 10px;">
    Note that if this function is used on the current month affiliate profits might end up being higher than shown here due to potential future income that
    moves the affiliate in question up in his commission structure.
    <br>
    <br>
    <?php if($show_extra && p('stats.all')): ?>
      <strong>Total current cash EUR: <?php nfCents( phive('Cashier')->getTotalCash('', true) ) ?></strong><br>
      <strong>Total Booster Vault balance, EUR: <?php nfCents( phive('DBUserHandler/Booster')->getTotalVaultBalance() ) ?></strong><br>
      <strong>Total bonus balances, EUR: <?php nfCents( phive('Bonuses')->getTotalBalances(" = 'active' ", true) ) ?></strong><br/>
      <strong>Total local jackpot balances, EUR: <?php nfCents( phive('MicroGames')->getLocalJpBalance() ) ?></strong>
      <br>
      <br>
      <strong><?php echo "From: $start_date To: $end_date" ?>.</strong>
      <br>
      <br>
    <?php endif ?>
    <strong>All numbers in <?php echo empty($_REQUEST['currency']) ? "EUR (converted and summed)" : $_REQUEST['currency'] ?>.</strong>
    <?php if(!empty($_REQUEST['as_csv'])): ?>
      <br>
      <br>
      <?php phive('UserSearch')->handleCsv($stats, $csv_cols, $csv_cols) ?>
    <?php endif ?>
    <br>
    <br>
    <?php highChart($stats, $_REQUEST['graph_col']) ?>
    <table id="stats-table" class="stats_table">
      <thead>
        <tr class="stats_header">
          <?php foreach($headlines as $h): ?>
            <th><?php echo $h ?></th>
          <?php endforeach ?>
        </tr>
      </thead>
      <tbody>
        <?php $i = 0; foreach($stats as $r): ?>
        <tr class="<?php echo wDayCls($i % 2 == 0 ? 'fill-odd' : 'fill-even', $mnum, $r['date'], !empty($group_by)) ?>">
          <td>
            <?php if(empty($group_by) || !$do_link): ?>
              <?php echo $r[$date_key] ?>
            <?php else: ?>
              <a href="<?php echo  getUsersDailyLink($group_by, $r[$group_by]) ?>">
                <?php echo $r[$group_by] ?>
              </a>
            <?php endif ?>
          </td>
          <?php if(empty($group_by)): ?>
            <td><?php echo phive()->fDate($r['date'], 'D') ?></td>
          <?php endif ?>
          <?php foreach($num_cols as $col): ?>
            <td> <?php nfCents($r[$col]) ?> </td>
          <?php endforeach ?>
          <?php foreach($extra_cols as $col): ?>
            <td> <?php echo $r[$col] ?> </td>
          <?php endforeach ?>
        </tr>
        <?php $i++; endforeach ?>
      </tbody>
      <tfoot>
        <tr class="stats_header">
          <td>Total</td>
          <?php if(empty($group_by)): ?>
            <td>&nbsp;</td>
          <?php endif ?>
          <?php foreach($num_cols as $col): ?>
            <td><?php echo nfCents(phive()->sum2d($stats, $col)) ?></td>
          <?php endforeach ?>
        </tr>
        <tr class="stats_header">
          <td>% of gross</td>
          <?php if(empty($group_by)): ?>
            <td>&nbsp;</td>
          <?php endif ?>
          <?php foreach($num_cols as $col): ?>
            <td><?php echo round((phive()->sum2d($stats, $col) / $gross) * 100, 2).'%' ?></td>
          <?php endforeach ?>
        </tr>
        <tr class="stats_header">
          <td>% of w. tot.</td>
          <?php if(empty($group_by)): ?>
            <td>&nbsp;</td>
          <?php endif ?>
          <?php foreach($num_cols as $col): ?>
            <td><?php echo round((phive()->sum2d($stats, $col) / $btot) * 100, 2).'%' ?></td>
          <?php endforeach ?>
        </tr>
        <tr class="stats_header">
          <?php foreach($headlines as $h): ?>
            <th><?php echo $h ?></th>
          <?php endforeach ?>
        </tr>
      </tfoot>
    </table>
  </div>
<?php }
