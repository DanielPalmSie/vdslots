<?php
require_once __DIR__.'/../../../../../diamondbet/boxes/DiamondBox.php';

class ProofOfWealthPopupBoxBase extends DiamondBox {

    function init()
    {
        $this->handlePost(array('step', 'url'));
        $this->full_url = llink( $this->url );
        $_GET['step1'] 	= $this->step;
    }

    function printHTML()
    {
        drawFancyJs();
        loadJs("/phive/js/jQuery-UI/".$this->getJQueryUIVersion()."jquery-ui.min.js");
        loadJs("/phive/js/jquery.validate.min.js");
        loadJs("/phive/js/jquery.validate.password.js");
        loadJs("/phive/js/sourceoffunds.js");
        loadJs("/phive/js/utility.js");
        loadJs("/phive/modules/DBUserHandler/html/registration_new.js");
        loadCss("/diamondbet/css/" . brandedCss() . "sourceoffunds.css");
        loadCss("/diamondbet/css/" . brandedCss() . "new-registration.css");
        loadCss("/diamondbet/fonts/icons.css");
        ?>

        <div id="<?php echo phive()->isMobile() ? 'proofofwealth-popup-wrapper-mobile' : 'proofofwealth-popup-wrapper' ?>">

            <div class="proofofwealth-popup-header">
                <div class="proofofwealth-popup-header-right">
                    <!--icon to close this box -->
                    <?php if(phive()->isMobile()): ?>
                        <div id="close-proofofwealth-popup" onclick="closeProofOfWealthBoxMobile()"><span class="icon icon-vs-close"></span></div>
                    <?php else: ?>
                        <div id="close-proofofwealth-popup" onclick="closeProofOfWealthBox()"><span class="icon icon-vs-close"></span></div>
                    <?php endif;?>
                </div>
                <div class="proofofwealth-popup-header-left">
                    <!-- link to chat -->
                    <div id="chat-registration" onclick="<?php echo 'window.parent.'.phive('Localizer')->getChatUrl() ?>"></div>

                </div>
                <div class="proofofwealth-popup-header-center">

                </div>
            </div>

            <div class="registration-container">
                <?php et('proofofwealth.popup') ?>
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




