<?php
include_once('display.php');
$mg 	= phive('QuickFire');
$loc 	= phive('Localizer');
$pager 	= phive('Pager');
$micro  = phive('Casino');
$balances = $GLOBALS['balances'];
loadCss("/diamondbet/fonts/icons.css");
$fast_psp_option_html = fastDepositIcon('fast-desktop', true);
?>

<?php if(empty($_SESSION['mg_username'])): ?>
  <?php drawLoginReg() ?>
<?php else: ?>
  <div class="top-profile-frame">

    <?php countryFlag() ?>

      <div class="top-profile-name">
          <span class="fat"><?php echo html_entity_decode( $_SESSION['local_usr']['firstname'].' '.$_SESSION['local_usr']['lastname'], ENT_QUOTES | ENT_XHTML) ?></span>
      </div>

    <?php licHtml('top_profile_balances', null) ?>

    <div class="top-profile-buttons">
        <ul>
            <?php if(!empty($fast_psp_option_html)): ?>
                <li>
                    <?php echo $fast_psp_option_html ?>
                </li>
            <?php endif ?>
            <li>
	        <a class="small-btn" onclick="<?php echo depGo() ?>">
	            <?php echo t('deposit') ?>
	        </a>
	    </li>
            <li>
	        <a class="small-btn" href="<?php echo $loc->langLink('', '/account') ?>">
	            <?php echo t('my-profile') ?>
	        </a>
	    </li>
            <?php if(empty($fast_psp_option_html)): ?>
            <li>
                <a id="logout" class="small-btn" href="<?php echo llink("?signout=true") ?>">
                    <?php echo t('logout') ?>
                </a>
            </li>
            <?php endif ?>
        </ul>
    </div>

  </div>
<?php endif ?>
