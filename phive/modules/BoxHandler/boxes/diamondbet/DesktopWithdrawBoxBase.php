<?php
require_once 'CashierWithdrawBoxBase.php';

/**
 * This class contains the functionality that is common to desktop withdrawals.
 *
 */
class DesktopWithdrawBoxBase extends CashierWithdrawBoxBase{

    public function init($u_obj = null){
        $this->channel = 'desktop';
        parent::init($u_obj);
    }
    
    /**
     * The expand / collapse logic is unique to desktop withdrawals.
     *
     * @return void
     */
    public function printCSS(){
        parent::printCSS();
        loadJs("/phive/modules/Cashier/html/cashier-exp-coll.js");
    }

    /**
     * The much bigger top content is unique to desktop withdrawals.
     *
     * @return void
     */    
    public function printHTML(){
        $res = parent::printHTML();
        if($res){
            parent::printHtmlCommon();
        }
    }
}
