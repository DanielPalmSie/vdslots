<?php
include_once('topcommon.php');

$mg 	= phive('QuickFire');
$loc 	= phive('Localizer');
$pager 	= phive('Pager');

$hide_top    = $pager->fetchSetting('hide_top');
$only_logo   = $pager->fetchSetting('only_logo');
$is_sportsbook   = $pager->fetchSetting('is_sportsbook');
$menuer      = phive('Menuer');
$top_menu    = $menuer->forRender('top', '', true, $_SESSION['user_id']);
$aff_section = phive('Pager')->fetchSetting('affiliate');
$subtop_menu = $menuer->forRender('new-sub-top-menu', '', true, $_SESSION['mg_username']);
$secondary_menu = $menuer->forRender($menuer->getSecondaryMenuId());
$top_logos   = lic('topLogos', ['black']);
$fast_psp    = phive('Cashier')->getFastPsp();
$page        = $pager->getPage($pager->page_id);
// 45: admin_log - 12: admin
$removeTopMargin = ($page['page_id'] == 45 || $page['page_id'] == 12 || $page['parent_id'] == 12) ? true : false;

$calculateTopLogoClasses = function ($is_sportsbook, $inside_top_bar) {
    $extra_classes = [];
    if ($is_sportsbook) {
        $extra_classes[] = 'logo_sports';
    }

    if (!empty($inside_top_bar)) {
        $extra_classes[] = $inside_top_bar;
    }

    return implode(" ", $extra_classes);
};
$top_logo_classes = $calculateTopLogoClasses($is_sportsbook ?? null, lic('insideTopbar', [$pager->getRawPathNoTrailing()]));
?>

<?php if($hide_top != 1): ?>
  <script type="text/javascript" src="/phive/js/selectbox/js/jquery.selectbox-0.1.3.min.js"></script>
  <div id="header" style="<?php echo $only_logo == 1 ? 'margin-bottom: 130px;' : '' ?>">
    <div class="header-holder">
      <div class="logo <?= $top_logo_classes ?>" onclick="goTo('<?php echo ($is_sportsbook) ? llink($pager->path)  : llink('/') ?>')"></div>

      <?php if($only_logo != 1): ?>
        <script>
          $(document).ready(function(){
            $("#currency_top").selectbox({
              classHolder: 'sbTopCurrencyHolder',
              classOptions: 'sbTopCurrencyOptions',
              onChange: function(val, inst) {
                goTo('?site_currency='+val);
              }
            });

            if($("#currency_top > option").length === 1) {
                var sb = $('#currency_top').attr('sb');
                $('#sbSelector_'+sb).off('click').on('click', function (e){
                    e.preventDefault();
                });
                $('#sbToggle_'+sb).off('click').on('click', function (e){
                    e.preventDefault();
                });
            }
          });
        </script>

        <?php if(!empty($top_logos)): ?>
          <div class="gradient-normal rg-top-<?php echo lic('getIso') ?>" id="rg-top-bar">
            <div class="rg-top__container">
                <?= lic('rgOverAge', ['logged-in-time', 'over-age-desktop']); ?>
                <?= lic('rgLoginTime', ['rg-top__item logged-in-time']); ?>
              <?= $top_logos ?>
            </div>
          </div>
        <?php endif ?>



        <div class="lang-holder" style="<?php echo isLogged() ? 'margin-right: 0px;' : '' ?>">
          <div class="cur-lang version2">
            <?php if(phive("Currencer")->getSetting('multi_currency') == true && !isLogged()): ?>
              <?php cisosSelect(false, ciso(), 'currency_top', '', array(), false, false, false) ?>
              <div class="icon icon-vs-chevron-left"></div>
            <?php endif ?>
          </div>
          <ul class="lang">
            <li>
              <ul class="topmost-menu">
                  <?php foreach(phive('Menuer')->forRender('topmost-menu') as $item): ?>
                  <li>
                    <img src="<?php fupUri("topmost-menu-{$item['alias']}.png") ?>" />
                    <a <?php echo $item['params']?>>
                      <?php echo $item['txt']?>
                    </a>
                  </li>
                <?php endforeach ?>
                <li class="pointer" onclick="<?php echo phive('Localizer')->getChatUrl() ?>">
                  <?php if (phive()->getSetting('chat_support_disabled')): ?>
                      <img src="/diamondbet/images/<?= brandedCss() ?>topmost-menu-chat-disabled.png" alt="Chat disabled" />
                      <?php et('chat.offline') ?>
                  <?php else: ?>
                    <img src="<?php fupUri('topmost-menu-chat.png') ?>" />
                    <?php et('chat') ?>
                  <?php endif ?>
                </li>
                <li class="pointer" onclick="getPhoneUsForm();">
                  <img src="<?php fupUri("topmost-menu-phone.png") ?>" />
                  <?php et('phone') ?>
                </li>
                <?php if(!empty($fast_psp)): ?>
                    <li id="logout" onclick="goTo('<?php echo llink("?signout=true") ?>')" style="cursor: pointer;">
                        <span>
                            <?php echo t('logout') ?>
                        </span>
                  <span class="logout-btn icon-vs-login"></span>
              </li>
                <?php endif ?>
              </ul>
            </li>
          </ul>
        </div>

        <div class="sponsor-logos">
          <?php if (!lic('getSponsorshipLogos')): ?>
            <?php if((t('partnership1.html') != '(partnership1.html)')): ?>
              <div class="sponsor-logo sponsor-logo-1">
                <img class="sponsor-logo-image" src="<?php et('partnership1.html') ?>" />
              </div>
            <?php endif ?>

            <?php if((t('partnership2.html') != '(partnership2.html)')): ?>
              <div class="sponsor-logo sponsor-logo-2">
                <img class="sponsor-logo-image" src="<?php et('partnership2.html') ?>" />
              </div>
            <?php endif ?>
          <?php endif ?>
        </div>

        <div class="section">
          <div class="form-holder">
            <?php isPNP() ?  include_once('top-login-start-playing.php') : include_once('top-login-register.php')?>
          </div>
        </div>
      <?php endif ?>
    </div>
  </div>
<?php endif ?>

<div id="main" class="<?= $removeTopMargin ? 'admin-main' : '' ?>">

  <?php if($hide_top != 1 && $only_logo != 1): ?>

  <div class="frame">

    <div class="frame-t">&nbsp;</div>

    <div class="frame-inner">
      <div class="sub-nav">

        <div class="sub-nav-holder">
          <div class="sub-nav-l">&nbsp;</div>
          <ul>
            <?php foreach($top_menu as $item): ?>
              <li><a <?php echo $item['params']?>><?php echo $item['txt']?></a></li>
            <?php endforeach ?>
          </ul>
          <div class="sub-nav-r">&nbsp;</div>
        </div>
      </div>
      <div id="nav">
        <ul>
          <?php foreach($subtop_menu as $item): ?>
            <li <?php echo $item['current'] ? 'class="active"' : '' ?>>
              <a <?php echo $item['params']?>><?php echo $item['txt']?></a>
              <span><strong><em>&nbsp;</em></strong></span>
            </li>
          <?php endforeach ?>
        </ul>
      </div>
      <?php if(phive('Menuer')->getSetting('secondary_nav', false) && $secondary_menu): ?>
        <div class="secondary-nav-container" data-menu-id="<?= phive('Menuer')->getSecondaryMenuHtmlId() ?>">
          <button class="nav-arrow nav-arrow--left" aria-label="Scroll left">
            <span class="icon icon-vs-chevron-right"></span>
          </button>
          <div id="<?= phive('Menuer')->getSecondaryMenuHtmlId() ?>" class="<?= lic('isSportsbookEnabled') ? 'sportsbook-live-menu' : '' ?>">
            <ul>
              <?php foreach($secondary_menu as $item): ?>
                <li <?php echo $item['current'] ? 'class="active"' : '' ?> id=<?php echo 'sec-menu--' . ($item['alias'] != 'sportsbook'? $item['alias'] : 'sportsbook-prematch') ?>  onclick="secondaryMenuClickHandler('<?php echo $item['alias'] ?>')">
                  <a <?php echo $item['params']?>>
                    <span class="icon <?=$item['icon']?>"></span>
                    <?php echo $item['txt']?>
                  </a>
                  <span><strong><em>&nbsp;</em></strong></span>
                </li>
              <?php endforeach ?>
            </ul>
          </div>
          <button class="nav-arrow nav-arrow--right" aria-label="Scroll right">
            <span class="icon icon-vs-chevron-right"></span>
          </button>
        </div>
        <script src="/diamondbet/js/secondary-nav.js"></script>
      <?php endif ?>
    </div>
    <script>
      function secondaryMenuClickHandler(alias) {
          if (alias.includes('sportsbook') && typeof window.vueGoToSportsbook !== 'undefined') {
              event.preventDefault();
              window.vueGoToSportsbook(alias);
          }
      }
    </script>
  </div>
  <?php endif ?>
  <?php if(hasMp() && !phive('MicroGames')->blockMisc()): ?>
    <div class="right-fixed rot-90" onclick="showMpBox('/tournament/')"><?php et('mps') ?></div>
  <?php endif ?>
