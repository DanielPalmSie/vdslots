<?php
require_once 'CashierDepositBoxBase.php';

/**
 * This class contains the functionality that is common to mobile deposits.
 *
 */
class MobileDepositBoxBase extends CashierDepositBoxBase
{
    /**
     * If we pass the `provider` name to query string in deposit request for showing only this provider.
     * @var array
     */
    public $selected_psp = [];

    /**
     * The init method.
     *
     * @param DBUser $u_obj Optional user object, currently logged in user will be used if missing.
     *
     * @return void
     */
    public function init($u_obj = null){
        $this->channel = 'mobile';
        parent::init($u_obj);
        if ($this->block_action == 'show-net-deposit-limit-message') {
            phive('Redirect')->to('/mobile/rg-net-deposit-info/', cLang());
        }

        if ($this->redirectToVerificationModal($this->user)) {
            phive('Redirect')->to(lic('getVerificationModalUrl', [true, true]), cLang());
        }
        if (isset($_GET['provider'])) {
            $this->selected_psp = [
                'psp' => $_GET['provider'],
                'sub_psp' => ''
            ];
        }
        $this->bh = phive("BoxHandler")->getBoxById(902);
    }

    /**
     *
     *
     * @param string $prior_psp The previously used PSP, will default to configured PSP if no previous deposit exists.
     * @param string $prior_scheme The previously used sub PSP / scheme.
     *
     * @return array An array with the prior PSP as the first element and the prior scheme / sub PSP as the second element.
     */
    public function channelPriorDepositPsp($prior_type, $prior_scheme){
        $presel = $this->getPreselectDefault();

        // Something like ['bank', 'trustly'] was returned but we don't support Trustly.
        if(!empty($presel) && !$this->showAlt($prior_type)){
            return [$presel, $presel];
        }

        // TODO refactor to make this configurable in case more PSPs need to be added.
        // Some non-card PSPs are using the card_hash column, we hijack to preselect them properly here.
        switch($this->prior_deposit['dep_type']){
            case 'trustly':
                //TODO super ugly panic fix, this means if we go back to Trustly prior logic will break /Ricardo
                /*
                if (empty($this->c->getPspNetwork($this->user, $this->prior_deposit['dep_type']))) {
                    return ['zimplerbank', 'zimplerbank'];
                } else {
                    return [$this->prior_deposit['dep_type'], $this->prior_deposit['dep_type']];
                }
                */

                if(!empty($prior_scheme)){
                    return [$prior_scheme, $prior_scheme];
                }
                return [$this->prior_deposit['dep_type'], $this->prior_deposit['dep_type']];
            case 'zimpler':
                $map = [
                    'bank' => ['zimplerbank', 'zimplerbank'],
                    'bill' => ['zimpler', 'zimpler']
                ];
                return $map[ $prior_scheme ] ?? [$prior_scheme, $prior_scheme];
            case 'skrill':
                if(!empty($this->prior_deposit['scheme']) && $this->prior_deposit['scheme'] != 'skrill'){
                    // We assume bank atm as we're looking at sofort, rapid or giropay.
                    return [$this->prior_deposit['scheme'], $this->prior_deposit['scheme']];
                } else {
                    return ['skrill', 'skrill'];
                }
                break;
        }

        // We're on the mobile page and we are looking at a non card sub supplier
        // then we preselect that sub supplier unless the supplier is NOT bank OR in an array of select schemes, eg Apple Pay.
        if(!empty($prior_scheme) && !in_array($prior_scheme, ['mc', 'visa', 'maestro', 'jcb']) && ($this->psps[$prior_type]['type'] == 'bank' || in_array($prior_scheme, ['applepay']))){
            //if(!empty($prior_scheme) && !in_array($prior_scheme, ['mc', 'visa', 'maestro', 'jcb'])){
            $prior_type = $prior_scheme;
        }

        if(empty($prior_type)){
            // For some reason we still didn't manage to get a prior PSP to select so we just default to ccard to avoid
            // the black screen.
            return ['ccard', 'ccard'];
        }

        return [$prior_type, $prior_scheme];
    }

    /**
     * Main HTML / DOM printer entry.
     *
     * @return bool True if nothing blocks display (such as deposit limits etc), false otherwise.
     */
    public function printHTML(){
        loadCss("/diamondbet/css/" . brandedCss() . "cashier.css");
        loadCss("/diamondbet/css/" . brandedCss() . "cashier2mobile.css");

        if ($this->user->isDepositBlocked() && $this->user->hasSetting('id_scan_failed')) {
            lic('redirectToDocumentsPage', [$this->user], $this->user);
            return false;
        }

        $is_existed_psp = array_key_exists($this->selected_psp['psp'], $this->psps);

        if (($this->selected_psp) &&
            (!$is_existed_psp ||
                in_array($this->selected_psp['psp'], $this->not_allow_psp_webview))
        ) {
            return box404();
        }

        $res = parent::printHTML();
        if(!$res){
            return false;
        }
        $this->setCashierJs();
        if (!$this->selected_psp) {
            $this->printMobileTopLogos();
        }

        ?>
        <div id="deposit-cashier-box">

        </div>
        <?php
        $this->generateHandleBarsTemplates();
        parent::generateHandleBarsTemplates();
        $this->setPspJson();
        if ($is_existed_psp) {
            ?>
            <script>
                theCashier.preSelected = <?php echo json_encode($this->selected_psp) ?>;
            </script>
            <?php
        }

        $this->generateExtraFields();

        if ($this->user instanceof DBUser && count($this->user->getBonusesToForfeitBeforeDeposit())) {
            echo <<<JS
                <script type='text/javascript'>
                    extBoxAjax(
                        'get_html_popup', 'forfeit-bonus-to-deposit',
                        {file:'forfeit_bonus_to_deposit',closebtn:'yes',boxid:'deposit-forfeit-box',boxtitle:'Message',module:'Micro'},
                        {width:'450px',containerClass:'flex-in-wrapper-popup'}
                    );
                </script>
            JS;
        }
        ?>
        <div class="privacy-confirmation-container">
            <div class="privacy-confirmation-content">
                <table class="cashier-help-table">
                    <?php $this->bh->privacyAdjustment('privacy.adjustment', 'showPrivacySettings()', 'privacy-adjustment-mobile'); ?>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * Includes cashier2mobile.css which is needed.
     *
     * @return void
     */
    public function printCSS(){
        parent::printCSS();
        loadCss("/diamondbet/css/cashier2mobile.css");
    }

    /**
     * Main Handlebars output for mobile deposits.
     *
     * @return void
     */
    public function generateHandleBarsTemplates(){
        $validationRules = htmlspecialchars(json_encode($this->c->getFrontEndDepositValidationRules($this->user)), ENT_QUOTES);
        $deposit_limit_warning = phive('DBUserHandler/RgLimits')->getDepositLimitWarning();
        if ($deposit_limit_warning) {
            ?>
            <script>
                addToPopupsQueue(function() {
                    depositLimitMessage("<?php echo $deposit_limit_warning; ?>")
                });
            </script>
            <?php
        }
        ?>
        <script id="pspBoxHb" type="text/x-handlebars-template">
            <div class="cashierBox" style="display: block;">
                <span class="slideTrigger">
                    <div class="cashierHeader">
                        <h3>{{pspName}}</h3>
                    </div>
                    <div class="infoArea">
                        <div class="infoImage">
                            <img src="/file_uploads/{{logo}}.png">
                        </div>

                        <span class="infoText">
                            {{{pspInfo}}}
                        </span>
                    </div>
                </span>

                <div style="clear: both;"></div>

                <ul class="cashier-fee-list">
                    {{#if forcedAmounts}}
                        <li>
                            <span class="ulBig"><?php et("expenses") ?></span>
                            <span id="fee-percentage" class="cashier-expense cashier-fee-number"></span>
                        </li>
                        <li>
                            <span class="ulBig"><?php et("credit.amount") ?></span>
                            <span id="credit-amount" class="cashier-expense"></span>
                        </li>
                        <li>
                            <span class="ulBig"><?php et("debit.amount") ?></span>
                            <span id="debit-amount" class="cashier-expense"></span>
                        </li>
                    {{else}}
                        <li>
                            <span class="ulBig"><?php et("expenses") ?></span>
                            <span class="cashier-expense cashier-fee-number">0%</span>
                        </li>
                        <li>
                            <span class="ulBig cashier-expense-min-label"><?php et('min') ?></span>
                            <span class="cashier-expense cashier-expense-min-value"><?php echo cs() ?> {{min}}</span>
                        </li>
                        <li>
                            <span class="ulBig cashier-expense-min-label"><?php et('max') ?></span>
                            <span class="cashier-expense cashier-expense-max-value"><?php echo cs() ?> {{max}}</span>
                        </li>
                    {{/if}}
                </ul>

                <div class="cashierBoxInsert" style="display: block;">

                    {{{repeats}}}

                    <form id="deposit-form" data-validation="<?= $validationRules ?>"></form>

                    {{#if forcedAmounts}}
                        {{{forcedAmounts}}}
                        <input id="deposit-amount" name="amount" value="" class="cashierInput" type="hidden">
                    {{else}}
                        <div id="deposit-amount-label" class="cashierInputLabel amount-label"><?php et('register.amount') ?></div>
                        <input id="deposit-amount" name="amount" value="" class="cashierInput" type="<?php echo $this->ifMobElse('tel', 'number') ?>">
                    {{/if}}

                    <div style="clear: both;"></div>

                    <div class="cashierBtnOuter">
                        <div class="cashierBtnInner deposit-finished" onclick="theCashier.postDeposit('<?php echo $this->action ?>')">
                            <h4><?php et('deposit') ?></h4>
                        </div>
                    </div>

                    <div class="show-bonus-code-txt-mobile" onclick="cashier.showBonusCode()">
                        <?php et('got.a.bonus.code') ?>
                    </div>


                </div>
            </div>
        </script>

        <script id="predefAmountsHb" type="text/x-handlebars-template">
            <table class="forced-amounts-table">
                <tbody>
                    {{#each pairs as |pair|}}
                    <tr>
                        {{{pair}}}
                    </tr>
                    {{/each}}
                </tbody>
            </table>
        </script>

        <script>
         cashier.tpls = [
             'pspBoxHb',
             'predefAmountChunkHb',
             'predefAmountsHb',
             'predefAmountHb',
             'cvcOneclickPopupHb',
             'repeatsHb',
             'baseErrorHeadline'
         ];
        </script>
    <?php
    }
}
