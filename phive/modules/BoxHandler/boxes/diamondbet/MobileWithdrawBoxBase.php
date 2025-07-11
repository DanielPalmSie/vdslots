<?php
require_once 'CashierWithdrawBoxBase.php';

/**
 * This class contains the functionality that is common to mobile withdrawals.
 *
 */
class MobileWithdrawBoxBase extends CashierWithdrawBoxBase{

    public function init($u_obj = null){
        $this->channel = 'mobile';
        parent::init($u_obj);
    }
    

    public function printCSS(){
        parent::printCSS();
    }

    /**
     * The top logos differ from the desktop version, as well as the fact that we only display one psp
     * at a time.
     *
     * @return void
     */    
    public function printHTML()
    {
        $selected_psp = $_GET['provider'] ?? '';
        $is_existed_psp = false;

        if($selected_psp !== '') {
            $is_existed_psp = array_key_exists($selected_psp, $this->psps);

            if (!$is_existed_psp || in_array($selected_psp, $this->not_allow_psp_webview)) {
                return box404();
            }
        }

        $res = parent::printHTML();

        if($res){
            parent::printHtmlCommon(function() use ($selected_psp){
                if ($selected_psp == '') {
                    $this->printMobileTopLogos();
                }

            }, $is_existed_psp);


            if ($is_existed_psp) {
                ?>
                <script>
                    $(document).ready(function(){
                        theCashier.logoClick('<?= $selected_psp ?>');
                    });

                </script>
                <?php
            }
        }
    }
}
