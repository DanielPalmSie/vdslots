<?php
require_once __DIR__.'/../../../../../diamondbet/boxes/DiamondBox.php';
require_once __DIR__.'/../../../DBUserHandler/html/empty_dob.php';

class EmptyDobBoxBase extends DiamondBox{

    function init(){
        $this->handlePost(array('step', 'url'));
        $this->full_url = llink( $this->url );
        $_GET['step1'] 	= $this->step;
    }

    function printHTML(){
        $uh = phive('UserHandler');
        drawFancyJs();

        $fc 		= new FormerCommon();
        $required_age   = phive('SQL')->getValue("SELECT reg_age FROM bank_countries WHERE iso = '{$_SESSION['rstep1']['country']}'");
        $day            = !empty($_SESSION['rstep2']['birthdate']) ? $_SESSION['rstep2']['birthdate'] : $_POST['birthdate'];
        $month          = !empty($_SESSION['rstep2']['birthmonth']) ? $_SESSION['rstep2']['birthmonth'] : $_POST['birthmonth'];
        $year           = !empty($_SESSION['rstep2']['birthyear']) ? $_SESSION['rstep2']['birthyear'] : $_POST['birthyear'];

        $is_zipcode       = $_GET['zipcode'] == 'true' ? true : false;
        $is_nid           = $_GET['nid'] == 'true' ? true : false;
        loadJs("/phive/js/jQuery-UI/".$this->getJQueryUIVersion()."jquery-ui.min.js");
        loadJs("/phive/js/jquery.validate.min.js");
        loadJs("/phive/js/jquery.validate.password.js");
        loadJs("/phive/js/emptydob.js");
        loadJs("/phive/modules/DBUserHandler/html/registration_new.js");
        loadCss("/diamondbet/css/" . brandedCss() . "emptydob.css");
        loadCss("/diamondbet/css/" . brandedCss() . "new-registration.css");
        ?>

        <script>
         jQuery(document).ready(function() {
             setupEmptyDobBox();
             setupPersonalNumberBox();
         });
        </script>

        <div id="dobbox-wrapper">

            <div class="dobbox-header">
                <div class="dobbox-header-right">
                    <!--icon to close this box -->
                </div>
                <div class="dobbox-header-left">
                    <!-- link to chat -->
                    <div id="chat-registration" onclick="<?php echo 'window.parent.'.phive('Localizer')->getChatUrl() ?>"></div>
                </div>
                <div class="dobbox-header-center">
                    <?php if($is_zipcode): ?>
                        <span><?php et('fill.in.zipcode.title'); ?></span>
                    <?php elseif($is_nid): ?>
                        <span><?php et('fill.in.personal_number.title'); ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="registration-container">
                <form id="dobbox_step1" action="" method="post">
                <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">

                    <div class="dobbox-content-left">

                        <?php if($is_zipcode): ?>
                            <p><?php et('fill.in.zipcode.body'); ?></p>

                            <div id="birthdate-container">
                                <label>
                                    <input id="zipcode" class="new-standard-input" style="width: auto" type="text" autocapitalize="off" autocorrect="off" placeholder="<?= t('zipcode')?>" value="">
                                </label>
                            </div>
                            <!-- submit -->
                            <div id="submit_step_1" class="register-button-emptydob" onclick="submitZipcode()">
                                <div<?php if(phive()->isMobile()) { echo ' class="register-big-btn-txt register-button-emptydob-mobile"'; } ?>>
                                    <?php et('update'); ?>
                                </div>
                            </div>
                        <?php elseif($is_nid): ?>
                            <p><?php et('fill.in.personal_number.body'); ?></p>

                            <div id="birthdate-container">
                                <label>
                                    <input id="personal_number" class="new-standard-input" style="width: auto" type="text" autocapitalize="off" autocorrect="off" placeholder="<?= t('fill.in.personal_number.placeholder')?>" value="">
                                </label>
                            </div>
                            <!-- submit -->
                            <div id="submit_step_1" class="register-button-emptydob" onclick="submitPersonalNumber()">
                                <div<?php if(phive()->isMobile()) { echo ' class="register-big-btn-txt register-button-emptydob-mobile"'; } ?>>
                                    <?php et('update'); ?>
                                </div>
                            </div>
                            
                        <?php else: ?>
                            <h3><?php et('fill.in.dob'); ?></h3>

                            <div id="birthdate-container">
                                <label>
                                <span class="styled-select" id="birthyear-cont">
                                    <?php dbSelect("birthyear", $fc->getYears($required_age), empty($year) ? 1970 : $year, array('', t('year')), 'birthdate-emptydob', false, ''); ?>
                                </span>
                                    <span class="styled-select">
                                    <?php dbSelect("birthdate", $fc->getDays(), $day, array('', t('day')), 'birthdate-emptydob', false, '') ?>
                                </span>
                                    <span class="styled-select">
                                    <?php dbSelect("birthmonth", $fc->getFullMonths(), $month, array('', t('month')), 'birthdate-emptydob', false, '') ?>
                                </span>
                                </label>
                            </div>
                            <!-- submit -->
                            <div id="submit_step_1" class="register-button-emptydob" onclick="submitDob()">
                                <div<?php if(phive()->isMobile()) { echo ' class="register-big-btn-txt register-button-emptydob-mobile"'; } ?>>
                                    <?php et('submit'); ?>
                                </div>
                            </div>
                        <?php endif; ?>

                    </div>
                </form>
            </div>

        </div>
        <?php
    }

    public function printExtra() {
        ?>
        <p>
          Step #:
          <input type="text" name="step" value="<?php echo $this->step?>"/>
        </p>
        <p>
          Next step URL:
          <input type="text" name="url" value="<?php echo $this->url ?>"/>
        </p>
        <?php
    }

}




