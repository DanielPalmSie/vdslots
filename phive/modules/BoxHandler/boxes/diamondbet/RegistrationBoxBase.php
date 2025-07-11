<?php

require_once __DIR__ . '/../../../../../diamondbet/boxes/DiamondBox.php';
require_once __DIR__ . '/../../../../../phive/modules/DBUserHandler/Registration/RegistrationHtml.php';

/**
 * Class RegistrationBoxBase
 */
class RegistrationBoxBase extends DiamondBox
{
    /** @var string $full_url */
    public $full_url;
    /** @var integer $step */
    public $step;

    /**
     * @var bool
     */
    private $migration = false;

    /**
     * Ported old code
     */
    public function init()
    {
        $this->handlePost(array('step', 'url'));
        RegistrationHtml::skipDefaultProvince();
        $this->full_url = llink($this->url);
        $this->migration = $_SESSION['rstep2']['migration'];
    }

    /**
     * Ported old code
     */
    public function printHTML()
    {
        drawFancyJs();
        loadJs("/phive/js/jQuery-UI/".$this->getJQueryUIVersion()."jquery-ui.min.js");
        loadJs("/phive/js/jquery.validate.min.js");
        loadJs("/phive/js/jquery.validate.password.js");
        loadJs("/phive/js/jquery.validate.iban.js");
        loadJs("/phive/modules/DBUserHandler/html/registration_new.js");
        loadCss("/diamondbet/css/" . brandedCss() . "new-registration.css");
        loadCss("/diamondbet/fonts/icons.css");
        if (phive()->isMobile()) {
            loadCss("/diamondbet/css/" . brandedCss() . "new-registration-mobile.css");
            ?>
                <script>
                    var mobileStep = '<?php echo $this->full_url ?>';
                    $(document).ready(function () {
                        $("#close-registration-box").click(function(){ goTo('/?signout=true'); });
                    })
                </script>

                <style>
                    #cookie-banner {
                        display: none !important;
                    }
                </style>
            <?php
        }
        ?>

        <script>
            jQuery(document).ready(function () {
                <?php if($this->step < 3): ?>
                setupRegistration();
                <?php endif ?>
                <?php RegistrationHtml::printOnlyJava($this->step) ?>
            });
        </script>

        <?php
        // Step 1 and 2 have different dimensions then step 3 and 4,
        // so we set different id's and class depending on the step
        $class_wrapper = '';
        if ($this->step == 1 || $this->step == 2) {
            $id_wrapper = 'registration-wrapper';
            $class_header_center = 'registration-header-center';
            if($this->step == 2) {
                $class_wrapper = 'registration-step2';
            }
        } else {
            $id_wrapper = 'registration-wrapper-step3';
            $class_header_center = 'registration-header-center-step3';
        }
        $show_footer = lic('getLicSetting', ['show_rg_info_in_registration']) && (1 == $this->step || 2 == $this->step);
        $target_attr = phive()->isMobile() ? '' : 'target="_blank" rel="noopener noreferrer"'
        ?>

        <div id="<?php echo $id_wrapper; ?>" class="<?php echo $class_wrapper; ?> <?php echo $show_footer ? 'show-registration-footer' :'' ?>">

            <div class="registration-header">
                <div class="registration-header-right">
                    <!--icon to close this box -->
                    <div id="close-registration-box" onclick="parent.$.multibox('close', 'registration-box')"><span class="icon icon-vs-close"></span></div>
                </div>
                <div class="registration-header-left">
                    <!-- link to chat -->
                    <div id="chat-registration" onclick="<?php echo 'window.parent.' . phive('Localizer')->getChatUrl() ?>"></div>

                </div>
                <div class="<?php echo $class_header_center; ?>">
                    <?php
                    if($this->migration){
                        et("msg.ontario.popup.header");
                    } else {
                        et("register.step{$this->step}.top");
                    }

                    ?>
                </div>
            </div>

            <div class="registration-container">
                <form id="validation_step<?php echo $this->step ?>" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="token" value="<? echo $_SESSION['token']; ?>">

                    <?php
                    if (1 == $this->step):
                        RegistrationHtml::stepOne();
                    elseif (2 == $this->step):
                        ?>
                        <script>
                            <?php foreach($_SESSION['rstep1'] as $key => $val): ?>
                            regPrePops['<?php echo $key ?>'] = '<?php echo $val ?>';
                            <?php endforeach ?>
                        </script>
                        <?php
                        RegistrationHtml::stepTwo($this->migration);
                    elseif (3 == $this->step):
                        RegistrationHtml::stepThree();
                    elseif (4 == $this->step):
                        RegistrationHtml::stepFour();
                    endif;
                    ?>

                    <input type="hidden" name="step<?php echo $this->step ?>" value="<?php echo $this->step ?>"/>
                </form>
            </div>
            <?php
            if ($show_footer):
            ?>
            <div class="registration-footer">
                <div class="responsible-gambling-desc">
                    <div><?php et('registration.page.rg.info') ?></div>
                </div>
                <div class="responsible-gambling-logo">
                    <div>
                        <a href="https://www.connexontario.ca/en-ca/" <?php echo $target_attr ?> >
                            <span class="bold"><?php et('help.and.contact') ?></span>
                        </a>
                        <span class="vertical-bar">|</span>
                        <a href="/responsible-gambling/" <?php echo $target_attr ?> >
                            <span class="bold"><?php et('responsible.gaming') ?></span>
                        </a>
                        <span class="vertical-bar">|</span>
                    </div>
                    <a href="/responsible-gambling/#preventing-underage-gambling" <?php echo $target_attr ?> >
                        <div class="age_icon bold">19+</div>
                    </a>
                </div>
            </div>
            <?php
            endif;
            ?>
        </div>
        <?php
    }

    /**
     * Ported old code
     */
    public function printExtra()
    {
        ?>
        <p>
            Step #:
            <input type="text" name="step" value="<?php echo $this->step ?>"/>
        </p>
        <p>
            Next step URL:
            <input type="text" name="url" value="<?php echo $this->url ?>"/>
        </p>
        <?php
    }

}
