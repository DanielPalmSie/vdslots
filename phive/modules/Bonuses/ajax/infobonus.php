<?php
require_once __DIR__ . '/../../../phive.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';
phive('Localizer')->setFromReq();

function depositWarningHtml(){
    $html = "<div class='info-bonus'>".t('bonus.deposit.not.fulfilled.html')."</div><div>&nbsp;</div>";
    $html .= btnNormal(t('bonus.fail'), "FailBonus();", true, 260);
    $html .= "<br clear='all'/><br clear='all'/>";
    $html .= btnNormal(t('bonus.deposit.not.fulfilled.cancel'), "location = '/account';", true, 260);
    $html .= "<br clear='all'/><br clear='all'/>";
    $html .= btnNormal(t('continue.deposit'), "fbClose();", true, 260);
    $html .= "<br clear='all'/>";
    $html .= "<div>&nbsp;</div>";
    return $html;
}

switch ($_POST['action']) {
    case 'bonus_code':
        $user = cu();
        if(empty($user))
            die( json_encode(array("status" => "fail", "error" => t("no.user") ) ) );
        // Turned off since we don't want to mess with people if they've gotten rewards from trophies. /Henrik
        //if($user->isBonusBlocked())
        //    die( json_encode(array("status" => "fail", "error" => t("bonus.block") ) ) );
        $reload = phive('Bonuses')->getReload($_POST['bonus_code'], '', true, $user);
        if(!empty($reload))
            phive('Bonuses')->setCurReload(trim($_POST['bonus_code']));
        else
            die( json_encode(array("status" => "fail", "error" => t("bonus.no.match") ) ) );
        break;
    case 'bonus_profit':
        $user = cuPl();
        if(empty($user))
            die('no user');
        
        $bonuses = array_filter(
            phive('Bonuses')->getUserBonuses($user->getId(), '', "IN('active','complete')", " IN('casino', 'casinowager', 'freespin')"),
            function($r){
                if($r['bonus_type'] != 'freespin'){
                    // All non FRB are kept.
                    return true;
                } else if(!empty($r['cost'])) {
                    // An FRB with turnover requirements.
                    return true;
                }

                // An FRB we want to ignore.
                return false;
            }
        );
        
        if($_POST['transaction_type'] == 'withdraw'){
            $html = "<div class='info-bonus'>".t(phive('Bonuses')->getBonusString('bonus.withdraw.not.fulfilled.html'))."</div><div>&nbsp;</div>";
            $html .= btnNormal(t('bonus.withdraw.not.fulfilled.continue'), "mboxClose();", true, 260);
            $html .= "<br clear='all'/><br clear='all'/>";
            $html .= btnNormal(t('bonus.withdraw.not.fulfilled.cancel'), "location = '/account';", true, 260);
            $html .= "<br clear='all'/>";
            $html .= "<div>&nbsp;</div>";
        }else{
            $html = depositWarningHtml();
        }

        if( !empty($bonuses) && empty($_SESSION['bonus_profit_noshow'])){
            $profit = phive('Cashier')->getDepBonusProfit($user->getId());
            die( json_encode(array("status" => "fail", "html" => $html, "profit" => $profit ) ) );
        }
        die( json_encode(array("status" => "ok") ) );
        break;
    case 'bonus_fail':
        phive('Bonuses')->failBonusEntries($_SESSION['mg_id'], "Failed because of withdrawal request");
        echo json_encode(array("status" => "ok"));
        break;
}
