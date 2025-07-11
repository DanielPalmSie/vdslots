<?php
require_once __DIR__.'/../../../../../diamondbet/boxes/DiamondBox.php';
require_once __DIR__.'/../../../DBUserHandler/html/registration_new.php';

class NormalRegBoxBase extends DiamondBox{

    function init(){
        $this->handlePost(array('step', 'url'));
        $this->full_url = llink( $this->url );
        $_GET['step1'] 	= $this->step;
        if(!empty($_POST['reg_submit']) && $this->step != 3){
            require_once __DIR__.'/../../../Micro/registration_new.php';
            if($GLOBALS['reg_result']['status'] != 'err')
                phive('Redirect')->to($this->full_url);
        }
    }

    function printHTML(){
        drawFancyJs();
        loadJs("/phive/js/jQuery-UI/".$this->getJQueryUIVersion()."jquery-ui.min.js");
        loadJs("/phive/js/jquery.validate.min.js");
        loadJs("/phive/js/jquery.validate.password.js");
        loadJs("/phive/modules/DBUserHandler/html/registration_new.js");
        loadCss("/diamondbet/css/" . brandedCss() . "new-registration.css");
//        require_once __DIR__.'/../../../../../diamondbet/html/chat-support.php';
        ?>

        <script>
         jQuery(document).ready(function(){
           <?php if($this->step < 3): ?>
             setupRegistration();
           <?php endif ?>
           <?php Registration::printOnlyJava($this->step) ?>
         });
        </script>

        <?php
        // Step 1 and 2 have different dimensions then step 3 and 4,
        // so we set different id's and class depending on the step
        if($this->step == 1 || $this->step == 2) {
            $id_wrapper = 'registration-wrapper';
            $class_header_center = 'registration-header-center';
        } else {
            $id_wrapper = 'registration-wrapper-step3';
            $class_header_center = 'registration-header-center-step3';
        }
        ?>

        <div id="<?php echo $id_wrapper; ?>">

            <div class="registration-header">
                <div class="registration-header-right">
                    <!--icon to close this box -->
                    <div id="close-registration-box" onclick="parent.$.multibox('close', 'registration-box')">X</div>
                </div>
                <div class="registration-header-left">
                    <!-- link to chat -->
                    <div id="chat-registration" onclick="<?php echo 'window.parent.'.phive('Localizer')->getChatUrl() ?>"></div>

                </div>
                <div class="<?php echo $class_header_center; ?>">
                    <?php
                    if(1 == $this->step) {
                        et('register.step1.top');
                    }
                    if(2 == $this->step) {
                        et('register.step2.top');
                    }
                    if(3 == $this->step) {
                        et('register.step3.top');
                    }
                    if(4 == $this->step) {
                        et('register.step4.top');
                    }
                    ?>
                </div>
            </div>

            <div class="registration-container">
                <form id="validation_step<?php echo $this->step ?>" method="post" enctype="multipart/form-data">
                <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">

                    <?php
                    if(1 == $this->step):
                        Registration::stepOne();
                    elseif(2 == $this->step):
                    ?>
                        <script>
                            <?php foreach($_SESSION['rstep1'] as $key => $val): ?>
                               regPrePops['<?php echo $key ?>'] = '<?php echo $val ?>';
                            <?php endforeach ?>
                        </script>
                    <?php
                        Registration::stepTwo();
                    elseif(3 == $this->step):
                        Registration::stepThree();
                    elseif(4 == $this->step):
                        Registration::stepFour();
                    endif;
                    ?>

                    <input type="hidden" name="step<?php echo $this->step ?>" value="<?php echo $this->step ?>"/>
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
