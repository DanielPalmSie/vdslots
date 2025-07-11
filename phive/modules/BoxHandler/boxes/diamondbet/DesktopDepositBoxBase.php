<?php

use Laraphive\Domain\Payment\Services\PspConfigServiceInterface;

require_once 'CashierDepositBoxBase.php';

/**
 * This class contains the functionality that is common to desktop deposits.
 *
 */
class DesktopDepositBoxBase extends CashierDepositBoxBase{

    /**
     * The default predefined amounts.
     * @var array
     */
    public $default_predef_amounts = [];

    /**
     * Array of cashier related configs.
     * @var array
     */
    public $cashierConfigs = [];

    /**
     * The init method.
     *
     * Here we set the default predefined amounts that can be picked.
     *
     * NOTE the initialization of a separate box to get its content, this content should be put
     * in a separate place and used in both boxes instead.
     *
     * @param DBUser $u_obj Optional user object, currently logged in user will be used if missing.
     *
     * @param bool $only_init True if we only want to initialize and bypass loading the help box for instance, false otherwise.
     * @return void
     */
    public function init($u_obj = null, $only_init = false){

        $this->channel = 'desktop';
        parent::init($u_obj);

        if($only_init){
            return;
        }

        $this->hb = phive("BoxHandler")->getBoxById(902);
        $this->hb->init();

        $userCurrency = $this->user->getAttr('currency');
        $this->initializeCashierWithUserCurrency($userCurrency);
    }

    function initializeCashierWithUserCurrency(string $userCurrency): array
    {
        $cashierConfigTag = 'cashier';
        $this->cashierConfigs = phive('Config')->getByTags($cashierConfigTag, true)[$cashierConfigTag];

        $overrideAmounts = $this->overridePredefinedAmounts($userCurrency);

        return $this->default_predef_amounts = !empty($overrideAmounts)
            ? $overrideAmounts
            : $this->calculateDefaultPredefinedAmounts($userCurrency);
    }

    public function calculateDefaultPredefinedAmounts(string $userCurrency): array
    {
        $configName = 'default-amounts';
        $defaultAmountsConfig = phive('Cashier')->filterNonZeroNonEmptyArray($this->cashierConfigs[$configName] ?? []);

        $defaultAmounts = !empty($defaultAmountsConfig)
            ? $defaultAmountsConfig
            : phive('Config')->getValue(
                'cashier',
                $configName,
                [10, 20, 30, 50, 100, 150],
                ['type' => 'text', 'delimiter' =>',']
            );

        $mod = phive("Currencer")->getCurrency($userCurrency)['mod'] ?? 1;
        $display_amounts = array_map(fn($amount) => (int)round($this->filterAmount($amount) * $mod), $this->sortAmountsArray($defaultAmounts));
        return array_combine($display_amounts, $display_amounts);
    }

    public function overridePredefinedAmounts(string $userCurrency): array
    {
        $customAmounts = [];

        $overrideAmounts = $this->cashierConfigs['override-amounts'];
        if (empty($overrideAmounts)) {
            return $customAmounts;
        }

        $overrideAmountsArray = $this->formatOverriddenAmountsArray($overrideAmounts);
        foreach ($overrideAmountsArray as $configCurrency => $configAmount) {
            if ($userCurrency === strtoupper($configCurrency)) {
                $amounts = $this->getCashierAmountsByConfig(
                    phive('Cashier')->filterNonZeroNonEmptyArray($configAmount)
                );
                if ($amounts) {
                    $customAmounts = $amounts;
                    break;
                }
            }
        }

        return $customAmounts;
    }

    /**
     * Returns predefined cashier amounts in the format [[displayAmount => debitAmount], ...].
     * These amounts are retrieved from the database configuration and can be in one of two possible formats:
     *
     * - Simplified Format: e.g., ['SEK' => [100, 200, 300, ...]]
     *
     * - Full Data Format: e.g.,
     *  ['SEK' => [100 => 100, 200 => 200, 300 => 300, ...]]
     *  OR
     *  ['SEK' => [70 => 100, 150 => 200, 250 => 300, ...]]
     *
     * The full data format allows for the configuration to override display and debit values separately.
     */
    public function getCashierAmountsByConfig(array $configAmount): array
    {
        return array_keys($configAmount) !== range(0, count($configAmount) - 1)
            ? $configAmount
            : array_combine($configAmount, $configAmount);
    }

    /**
     * This method is used to parses a config value array containing currency and amounts information, fetched from the config table
     * and returns an array with the extracted data.
     *
     * The input array can have the following format (arrays):
     * "{CURRENCY} => {VALUE_1, VALUE_2, ...}".
     * OR
     * "{CURRENCY} => {CREDIT:DEBIT, CREDIT:DEBIT, ...}".
     *
     * @param array $overriddenAmounts An array containing overridden amounts.
     * @return array An associative array where keys are currency codes and values are arrays of amounts.
     *               $resultArray = [
     *               "EUR" => [25, 50, 100, 250, 500, 1000],
     *               "SEK" => [250 => 300, 500 => 300, 1000 => 300, 2500 => 300, 5000 => 300, 10000 => 300],
     *               ]
     */
    public function formatOverriddenAmountsArray(array $overriddenAmounts): array {
        $result = [];

        foreach ($overriddenAmounts as $currency => $denominationStr) {
            $currency = strtoupper($currency);
            $denominationPairs = explode(',', $denominationStr);
            $denominations = array_reduce($denominationPairs, function ($acc, $pair) {
                list($credit, $debit) = explode(':', $pair);
                $credit = $this->filterAmount($credit);
                $debit = $this->filterAmount($debit);

                $arrKey = $debit === 0 ? $credit : ($debit ? $credit : count($acc));
                $arrValue = $debit === 0 ? $debit : ($debit ?? $credit);
                $acc[$arrKey] = $arrValue;

                return $acc;
            }, []);

            $result[$currency] = $this->sortAmountsArray($denominations);
        }

        return $result;
    }

    /**
     * Retrieves the overridden fast deposit prior amounts for the user's currency.
     *
     * @return int[]|null The overridden fast deposit prior amounts for the user's currency
     */
    public function getOverriddenFastDepositPriorAmount(): ?int {
        $userCurrency = $this->user->getAttr('currency');
        $overrideAmounts  = $this->cashierConfigs['override-fast-deposit-prior-amounts'];
        $overrideAmounts = array_change_key_case($overrideAmounts, CASE_UPPER);

        if (!empty($overrideAmounts) && isset($overrideAmounts[$userCurrency]) && !empty($overrideAmounts[$userCurrency])) {
            return $this->filterAmount(explode(',', $overrideAmounts[$userCurrency])[0]);
        }

        return null;
    }

    /**
     * Get the default fast deposit prior amount.
     *
     * @param array $predefAmounts An array containing predefined amounts.
     * @return mixed The prior amount from the predefined amounts or a calculated value.
     */
    public function getDefaultFastDepositPriorAmount(array $predefAmounts) {
        $configName = 'fast-deposit-prior-eur-amount';

        $initialEuroValue = $this->cashierConfigs[$configName]
            ?? phive('Config')->getValue('cashier', $configName, 30);
        $priorAmount = mc($this->filterAmount($initialEuroValue), $this->user);

        return array_key_exists((int)$priorAmount, $predefAmounts)
            ? $priorAmount
            : phive()->getLvl($priorAmount, $predefAmounts, $priorAmount);
    }

    /**
     * Filter and sanitize a value to extract a numeric amount.
     *
     * @param mixed $value The value to be filtered.
     *
     * @return int|null Returns the sanitized numeric amount, or null if the value is not numeric.
     */
    public function filterAmount($value) {
        $filteredValue = preg_replace('/[^0-9.]/', '', $value);

        if (!is_numeric($filteredValue)) {
            return null;
        }

        return (int)$filteredValue;
    }

    /**
     * Sorts an array of amounts.
     *
     * @param array $array The input array to be sorted.
     * @return array The sorted array.
     */
    function sortAmountsArray(array $array): array {
        $isAssociative = array_keys($array) !== range(0, count($array) - 1);
        $isAssociative ? ksort($array) : sort($array);

        return $array;
    }

    /**
     * Used to set the predefined amounts.
     *
     * If no configured predefined amounts are present then the defaults will be used.
     *
     * @param string $psp The PSP to work with.
     *
     * @return array The amounts as an associative array with the credit amount as the key and the debit amount as the value.
     */
    public function getPredefAmounts($psp){
        return $this->psps[$psp][$this->action]['amounts'][$this->currency] ?? $this->default_predef_amounts;
    }

    public function hasPredefAmounts($psp){
        return !empty($this->psps[$psp][$this->action]['amounts'][$this->currency]);
    }

    /**
     * Used to group the PSP logos to the left.
     *
     * @return array The 3D array with the grouped PSPs.
     */
    public function getGroupedPsps(){
        $groupedPsps = $this->c->getGroupedPsps('type', $this->psps);

        $filterDepositGroupedPsps = lic('filterDepositGroupedPsps', [$groupedPsps, $this->user], $this->user);
        if (!empty($filterDepositGroupedPsps)) {
            $groupedPsps = $filterDepositGroupedPsps;
        }

        return $groupedPsps;
    }

    /**
     * Controls display to the left.
     *
     * Here we use the group info, if a PSP part of a grouping (eg SEB under BANK) this method prevents
     * direct display of the small logo to the left.
     *
     * @param string $psp The PSP to check.
     * @param array|null $psp_conf The PSP's config settings.
     *
     * @return bool True if we display, false otherwise.
     */
    public function doDisplayLeft($psp, array $psp_conf = null){
        if(in_array($psp, $this->groups)){
            // PSP is the group so we have to display.
            return true;
        }
        if ($psp_conf && ($psp_conf['display_under_methods'] ?? false)) {
            return true;
        }

        if(in_array($this->psps[$psp]['type'], $this->groups)){
            // The PSP's type is in a group so we hide.
            return false;
        }

        return true;
    }

    public function overridePriorDepositPsp($prior_scheme = null){
        // TODO refactor to make this configurable when there is time, it's ugly /Henrik
        // Some non-card PSPs are using the card_hash column, we hijack to preselect them properly here.
        $map = [
            'adyen' => function(){
                if($this->prior_deposit['scheme'] == 'trustly'){
                    // Trustly so we try and connect via the display name.
                    list($bank_psp, $config) = $this->c->getPspSettingFromDisplayName($this->prior_deposit['display_name']);
                    if(!empty($config)){
                        return ['bank', $bank_psp];
                    }
                }
                // A card so we do nothing, ie return the same.
                return null;
            },
            'trustly' => ['bank', empty($prior_scheme) ? 'trustly' : $prior_scheme],
            'zimpler' => function(){
                $map = [
                    'bank' => ['bank', 'zimplerbank'],
                    'bill' => ['zimpler', '']
                ];

                $return = $map[ $this->prior_deposit['scheme'] ];
                // Scheme is seb or some other bank so we just return it as is.
                return $return ?? ['bank', $this->prior_deposit['scheme']];
            },
            'skrill' => function(){
                if(!empty($this->prior_deposit['scheme']) && $this->prior_deposit['scheme'] != 'skrill'){
                    // We assume bank atm as we're looking at sofort, rapid or giropay.
                    return ['bank', $this->prior_deposit['scheme']];
                } else {
                    return ['skrill', 'skrill'];
                }
            }
        ];

        //TODO super ugly panic fix, this means if we go back to Trustly prior logic will break /Ricardo
        /*
           if ($this->prior_deposit['dep_type'] == 'trustly' && empty($this->c->getPspNetwork($this->user, $this->prior_deposit['dep_type']))) {
           $prior_logic = ['bank', 'zimplerbank'];
           } else {
           $prior_logic = $map[$this->prior_deposit['dep_type']];
           }
         */

        $prior_logic = $map[$this->prior_deposit['dep_type']];

        $override_psp = null;

        if(!empty($prior_logic)){
            $override_psp = is_callable($prior_logic) ? $prior_logic() : $prior_logic;
        }

        return $override_psp;
    }

    /**
     * Preselected PSP logic.
     *
     * This method handles the pecularities of desktop deposit preselection, if for instance the previous deposit was with SEB
     * We need to first display the banks in the middle as the user had clicked the BANK logo to the left. Then we need to
     * highlight the SEB radio in the middle as if the user had clicked that logo / radio.
     *
     * @param string $prior_psp The previously used PSP, will default to configured PSP if no previous deposit exists.
     * @param string $prior_scheme The previously used sub PSP / scheme.
     *
     * @return array An array with the prior PSP as the first element and the prior scheme / sub PSP as the second element.
     */
    public function channelPriorDepositPsp($prior_psp, $prior_scheme){
        list($override_psp, $override_scheme) = $this->overridePriorDepositPsp($prior_scheme);

        if(!empty($override_psp)){
            return [$override_psp, $override_scheme];
        }

        // The type is not bank and is showing, ie swish with seb or swedbank, we return [swish, swish] immediately.
        if(!empty($this->psps[$prior_psp]) && !in_array($this->psps[$prior_psp]['type'], $this->groups)){
            return [$prior_psp, $prior_psp];
        }

        foreach($this->groups as $group){

            $group_methods = array_keys($this->getPspsByType($group));

            if(in_array($prior_psp, $group_methods) && !in_array($prior_scheme, $group_methods)){
                // If the sub supplier or the main supplier is a bank method we hijack completely here with the banks section.
                $prior_scheme = $prior_psp;
                $prior_psp   = $group;
            } else if(in_array($prior_scheme, $group_methods)) {
                // We've got a bank sub supplier so we just override the supplier
                $prior_psp   = $group;
            }

            if($prior_psp != $group && !empty($this->c->getPspNetwork($this->user, $prior_scheme))){
                // If the sub supplier is not a bank method and we can get the network / supplier we hijack with the sub supplier
                $prior_psp = $prior_scheme;
            }

        }

        $presel = $this->getPreselectDefault();


        // Something like ['bank', 'trustly'] was returned but we don't support Trustly.
        if(!empty($presel) && !$this->showAlt($prior_scheme) && !empty(phiveApp(PspConfigServiceInterface::class)->getPspSetting()[$prior_scheme])){

            $config = phiveApp(PspConfigServiceInterface::class)->getPspSetting( $presel );
            $groups = $this->c->getSetting('psp_groups')[$this->action][$this->channel];

            if(in_array($config['type'], $groups)){
                // ['bank', 'trustly'] now becomes for instance ['bank', 'sofort']
                return [$config['type'], $presel];
            }

            return [$presel, ''];
        }

        if($prior_psp == 'ccard' && !$this->showAlt($prior_scheme)){
            return [$prior_psp, ''];
        }

        return [$prior_psp, $prior_scheme];
    }

    /**
     * Handles the printing of the left area with the groups of logos.
     *
     * @return void
     */
    function printLeft(){ ?>
        <div class="cashier-left-wrap">
            <div class="cashierBox">
                <div class="cashier-main-headline cashierHeader">
                    <h3>
                        <?php et('deposit.method') ?>
                    </h3>
                </div>
                <div class="cashier-left-content-wrap">
                    <?php foreach($this->getGroupedPsps() as $headline => $psps): ?>
                        <div class="cashier-minor-headline"><?php et($headline) ?></div>
                        <div class="cashier-psp-items">
                            <?php
                            $first_sub_psp = '';
                            foreach($psps as $nm => $psp_conf):

                                // Fallback to first sub_psp in case none is pre-defined in "prior_deposit_sub_psp"
                                if($headline == 'bank' && (empty($first_sub_psp) || $this->prior_deposit_sub_psp == $nm)) {
                                    $first_sub_psp = $nm;
                                }

                                if(!$this->doDisplayLeft($nm, $psp_conf)) {
                                    continue;
                                }

                                $logo_img = $this->getLogo($nm, 'small');
                                if(empty($logo_img)) {
                                    continue;
                                }
                            ?>
                                <span id="cashier-left-alt-<?php echo $nm ?>">
                                    <img id="<?php echo "logo-left-$nm" ?>" class="cashier-left-alt-pic" onclick="theCashier.logoClick('<?php echo $nm ?>', '<?php echo $first_sub_psp ?>')" src="<?php echo fupUri($logo_img) ?>" />
                                </span>
                            <?php endforeach ?>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>
        </div>
    <?php
    }

    /**
     * Handles the printing of the middle area.
     *
     * Various handlebars generated content will be displayed in the #deposit-cashier-box div.
     *
     * @return void
     */
    public function printMiddle(){
        $validationRules = htmlspecialchars(json_encode($this->c->getFrontEndDepositValidationRules($this->user)), ENT_QUOTES);
        ?>
        <div class="cashier-middle-wrap">
            <form id="deposit-form" data-validation="<?= $validationRules ?>">
                <div id="deposit-cashier-box" class="cashierBox"></div>
            </form>
        </div>
    <?php
    }

    public function printRight(){ ?>
        <div class="cashier-right-wrap">
            <div class="cashierBox">
                <div class="cashier-main-headline cashierHeader">
                    <h3>
                        <?php et('choose.amount') ?>
                    </h3>
                </div>
                <div class="cashier-right-content-wrap">
                    <div id="deposit-predefined-amounts-container"></div>

                    <span class="expense-info">
                        <span class="cashier-expense-min-label"><?php et("min") ?></span>
                        <span class="cashier-expense-min-value" id="cashier-min-amount"></span>
                    </span>
                    <span class="expense-info">
                        <span class="cashier-expense-max-label"><?php et("max") ?></span>
                        <span class="cashier-expense-max-value" id="cashier-max-amount"></span>
                    </span>
                    <ul class="cashier-fee-list-right">
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
                    </ul>

                    <div class="cashierBtnInner deposit-finished" onclick="theCashier.postDeposit('<?php echo $this->action ?>')">
                        <h4><?php et('deposit') ?></h4>
                    </div>

                    <div class="show-bonus-code-txt" onclick="cashier.showBonusCode()">
                        <?php et('got.a.bonus.code') ?>
                    </div>

                </div>
            </div>
        </div>
    <?php
    }

    /**
     * Includes the necessary CSS and JS to power the desktop deposit logic and interface.
     *
     * @return void
     */
    public function printCSS(){
        parent::printCSS();
        loadCss("/diamondbet/css/" . brandedCss(). "cashier2.css");
    }


    /**
     * Render site information
     * @return void
     */
    public function siteInfoHtml()
    {
        et('site.info.html');
    }

    /**
     * Main HTML / DOM entrypoint.
     *
     * @return bool True if nothing blocks display (such as deposit limits etc), false otherwise.
     */
    public function printHTML(){
        if ($this->redirectToVerificationModal($this->user)) {
            lic('handleRgLimitPopupRedirection', [$this->user, 'flash', 'gbg_verification']);
            return false;
        }

        if ($this->user->isDepositBlocked() && $this->user->hasSetting('id_scan_failed')) {
            lic('redirectToDocumentsPage', [$this->user], $this->user);
            return false;
        }

        $res = parent::printHTML();
        if(!$res){
            return false;
        }
        $this->setCashierJs();
    ?>
        <div class="cashier-wrapper">
            <table class="cashier2-layout-table">
                <tr>
                    <td>
                        <?php $this->printLeft() ?>
                    </td>
                    <td class="cashier2-layout-table-middle">
                        <?php $this->printMiddle() ?>
                    </td>
                    <td class="cashier2-layout-table-right">
                        <?php $this->printRight() ?>
                    </td>
                </tr>
            </table>
            <table class="cashier-help-table help-row-table">
                <tr>
                    <?php
                    $this->hb->privacyAdjustment('privacy.adjustment', 'showPrivacySettings()', 'privacy-adjustment');
                    ?>
                     <?php
                        lic('getRgLink', [ 'help_start_box' => $this->hb]);
                        $this->hb->liveChat('parent.'.phive('Localizer')->getChatUrl());
                        $this->hb->sendEmail('goToBlank');
                        $this->hb->talkWithUs();
                        $this->hb->readFaq('goToBlank');
                    ?>
                    <td class="faq-right-text company-details">
                        <?php et('site.info.html') ?>
                    </td>
                </tr>
            </table>
        </div>
        <?php
        $this->generateHandleBarsTemplates();
        $this->setPspJson();
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

        if($this->isReturn()): ?>
        <script>
           $(document).ready(function(){
               if(isIframe() && window.parent.document.getElementById('mbox-iframe-fast-deposit-box')){
                   // We're in a fast deposit frame on return so we need to break out and show the real deposit.
                   window.parent.mboxDeposit('<?php echo $_SERVER['REQUEST_URI'] ?>');
               }
           });
        </script>
        <?php endif;

        return true;
    }

    /**
     * Generates and sets up Handlebars templates and data that is particular to desktop deposits.
     *
     * @return void
     */
    public function generateHandleBarsTemplates(){
            parent::generateHandleBarsTemplates();
        ?>
        <script id="basicDepositHb" type="text/x-handlebars-template">
            <div class="cashierHeader">
                <h3>{{pspName}}</h3>
            </div>
            <span class="infoText">
                <p>{{{pspInfo}}}</p>
            </span>
            <div class="cashierBoxInsert">
                {{{radios}}}
                <div id="extra-fields"></div>
                {{{repeats}}}
            </div>
        </script>

        <script id="subRadioHb" type="text/x-handlebars-template">
            <label class="b-selector">
                <input type="radio" name="subs" id="{{psp}}-subradio" value="{{psp}}" onclick="theCashier.selectSubPsp('{{psp}}')">
                <span style="background-image:url(/file_uploads/{{img}}-small.png);"></span>
            </label>
        </script>

        <script id="subRadioContainerHb" type="text/x-handlebars-template">
            <div class="banks-selector">
                {{{radios}}}
            </div>
            <br/>
        </script>

        <script id="predefAmountsHb" type="text/x-handlebars-template">
            <table>
                <tbody>
                    {{#each pairs as |pair|}}
                    <tr>
                        {{{pair}}}
                    </tr>
                    {{/each}}
                    <tr>
                        <td>
                            <div id="predef-amount-other"
                                 class="cashier-predefined-amount cashier-predefined-amount-other"
                                 onclick="theCashier.clickOther()">
                            <?php et('other') ?>
                            </div>
                        </td>
                        <td>
                            <input id="deposit-amount" value="" class="amountInput" type="<?php echo $this->ifMobElse('tel', 'number') ?>">
                        </td>
                    </tr>
                </tbody>
            </table>
        </script>


        <script>
         cashier.tpls = [
             'basicDepositHb',
             'subRadioHb',
             'subRadioContainerHb',
             'predefAmountsHb',
             'predefAmountHb',
             'predefAmountChunkHb',
             'cvcOneclickPopupHb',
             'showPaymentMsg',
             'repeatsHb',
             'baseErrorHeadline'
         ];
         theCashier.defaultPredefAmounts = <?php echo json_encode($this->default_predef_amounts) ?>;
        </script>

    <?php
    }


}
