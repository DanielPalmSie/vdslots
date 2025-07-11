<?php
require_once __DIR__.'/../../../../../diamondbet/boxes/DiamondBox.php';

class SourceOfFundsBoxBase extends DiamondBox {

    /** @var SourceOfFunds $sof_class */
    public $sof_class;

    function init()
    {
        $this->sof_class = phive('DBUserHandler/SourceOfFunds');
        $this->handlePost(array('step', 'url'));
        $this->full_url = llink( $this->url );
        $_GET['step1'] 	= $this->step;
    }

    function printHTML()
    {
        drawFancyJs();

        $fc 		= new FormerCommon();
        $user = cu();
        loadJs("/phive/js/jQuery-UI/".$this->getJQueryUIVersion()."jquery-ui.min.js");
        loadJs("/phive/js/jquery.validate.min.js");
        loadJs("/phive/js/jquery.validate.password.js");
        loadJs("/phive/js/sourceoffunds.js");
        loadJs("/phive/js/utility.js");
        loadJs("/phive/modules/DBUserHandler/html/registration_new.js");
        loadCss("/diamondbet/css/" . brandedCss() . "sourceoffunds.css");
        loadCss("/diamondbet/css/" . brandedCss() . "new-registration.css");
        loadCss("/diamondbet/fonts/icons.css");

        // Set up initial values for the form
        $full_name = $user->data['firstname'] . ' ' . $user->data['lastname'];
        $address   = $user->data['address'];
        $day       = date('d');
        $month     = date('m');
        $year      = date('Y');

        $funding_methods       = [];
        $others                = '';
        $occupation            = '';
        $annual_income         = '';
        $no_income_explanation = '';
        $your_savings          = '';
        $savings_explanation   = '';
        $name                  =  $full_name;
        $password              =  '';

        $document_id = $_GET['document_id'];

        $annual_income_options = $this->sof_class->getIncomeOptions($user->getCurrency());
        $your_savings_options = $this->sof_class->getSavingOptions($user->getCurrency());

        // If we ever change the form, make a new version and keep the old version.
        // In the BO we link to the correct version automaticly.
        // The Dmapi keeps track of which version had been filled in by which user.
        $current_file = __DIR__."/../../../DBUserHandler/html/source_of_funds_form_version_1.1.php";
        $current_filepath = $current_file;

        $submit_function_name = 'submitSourceOfFunds';

        $can_submit   = true;
        $can_edit_form = true;

        $user_id      = cuAttr('id');
        ?>

        <script>
         jQuery(document).ready(function() {
             parent.$.multibox('resize', 'cashier-box', 969, 950);
             parent.parent.$.multibox('resize', 'mp-box', 969, 950);
             setupSourceOfFundsBox();

             if (isAndroid()) {
                 $('#sourceoffundsbox-wrapper').addClass('sow-android');
             }
         });

        </script>

        <div id="sourceoffundsbox-wrapper" class=<?= phive()->isMobile() ? "sourceoffundsbox-wrapper-mobile" : "sourceoffundsbox-wrapper" ?> >

            <div class="sourceoffundsbox-header">
                <div class="sourceoffundsbox-header-right">
                    <!--icon to close this box -->
                    <?php if(phive()->isMobile()): ?>
                        <div id="close-sourceoffundsbox" onclick="closeSourceOfFundsBoxMobile()"><span class="icon icon-vs-close"></span></div>
                    <?php else: ?>
                        <div id="close-sourceoffundsbox" onclick="closeSourceOfFundsBox(false)"><span class="icon icon-vs-close"></span></div>
                    <?php endif;?>
                </div>
                    <div class="sourceoffundsbox-header-left">
                        <?php if(!isPNP()): ?>
                        <!-- link to chat -->
                        <div id="chat-registration" onclick="<?php echo 'window.parent.'.phive('Localizer')->getChatUrl() ?>"></div>
                        <?php endif;?>
                    </div>
                <div class=<?= phive()->isMobile() ? "sourceoffundsbox-header-center-mobile" : "sourceoffundsbox-header-center" ?> >
                    <?php et('source.of.funds.form.title'); ?>
                </div>
            </div>

            <?php require_once $current_filepath; ?>

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


