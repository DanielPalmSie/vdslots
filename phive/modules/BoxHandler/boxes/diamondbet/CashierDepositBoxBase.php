<?php

use Videoslots\User\PaymentProviders\QuickDeposits;
use Laraphive\Domain\Payment\Services\PspConfigServiceInterface;

require_once 'CashierBoxBase.php';
require_once __DIR__ . '/../../../Cashier/Decorator/FallbackDepositDecorator.php';

/**
 * This is the base deposit class for the new cashier.
 *
 * This class contains the functionality that is common to:
 *
 * 1. Desktop deposit.
 * 2. Mobile withdraw.
 *
 */
class CashierDepositBoxBase extends CashierBoxBase{

    /**
     * A PCI compliant on / off flag, controls CC routing.
     * @var bool
     */
    public $pci_compliant = null;

    /**
     * Member variable that keeps track of the CC PSP to route via. This logic is complex enough
     * that it can not be easily configured.
     * @var string
     */
    public $cc_supplier = '';

    /**
     * Contains display related overrides
     * @var array
     */
    public $overrides = [];

    /**
     * Contains display related groups, ie the BANKs grouping.
     * @var array
     */
    public $groups = [];

    /**
     * Potential cards whose deposits can be repeated, AKA oneclick.
     * @var array
     */
    public $repeat_cards = [];

    /**
     * All forms of repeats as returned by the MTS, grouped in sub arrays per PSP
     * @var array
     */
    public $repeats = [];

    /**
     * The prior deposit, used in order to preselect the previously used PSP.
     * @var array
     */
    public $prior_deposit = [];

    /**
     * The prior deposit info, used in order to preselect the previously used PSP.
     * @var array
     */
    public $prior_deposit_info = [];

    /**
     * The CC network / provider config that is applicable to the player.
     * @var array
     */
    public $cc_psp_configs = [];

    /**
     * Quick deposits list for the bank section(including sub-banks) from the deposits table.
     * @var array
     */
    private $bank_repeats = [];

    /**
     * Array of psp methodes that cannot be used for webview with using `provider` query string
     * @var string[]
     */
    public $not_allow_psp_webview = [
        'applepay'
    ];

    /**
     * The init box function that runs after construction but before HTML output.
     *
     * Here we setup various data / info that we will subsequently need:
     * * 3DS return logic.
     * * Repeat cards.
     * * Prior deposit info used to preselect the previously used PSP.
     *
     * @param DBUser $u_obj Optional user object in case of CLI testing.
     *
     * @return void
     */
    public function init($u_obj = null){
        $this->action  = 'deposit';

        parent::init($u_obj);

        $this->cc_supplier       = $this->mts->getCcSupplier();
        $_SESSION['cc_supplier'] = $this->cc_supplier;
        $this->groups            = $this->c->getSetting('psp_groups')[$this->action][$this->channel];
        $this->overrides         = $this->getOverrides();
        $this->cc_psp_configs    = $this->mts->getValidCcSupplierConfigs();
        $pci_compliant           = $this->cc_psp_configs[$this->cc_supplier]['pci_compliant'];

        if(is_array($pci_compliant)){
            // Config looks like this: 'pci_compliant' => ['NO' => false, 'ROW' => true],
            // Reason being that we want to route to for instance WC on certain BINs for certain countries.
            $this->pci_compliant = $pci_compliant[$this->user->getCountry()] ?? $pci_compliant['ROW'];
        } else {
            $this->pci_compliant = $pci_compliant;
        }


        // We unset the 3D result as it is only needed to preserve result data between the check card and deposit calls which happen in succession.
        $this->mts->unsetSess('3d_result');

        // Resets failover and 3DS status info that the MTS is using to determine which CC supplier to use.
        foreach($this->cc_psp_configs as $cc_psp => $conf){
            if(!empty($_REQUEST["{$cc_psp}_end"]) || $_GET['supplier'] == $cc_psp){
                $this->mts->resetOnReturn($this->user);
                $this->mts->resetOnSuccess($this->user);
                //TODO do selective dumps /Ricardo
                //phive()->dumpTbl('deposit-ccard-end', phive()->getCommonLogVariables(), $this->user);
                break;
            }
        }

        if(phive('WireCard')->getSetting('no_sms') === true){
            $_SESSION['3d_card'] = true;
            $_SESSION['sms_ok'] = true;
        }

        $quickDeposits = new QuickDeposits($this->user);
        $this->repeats = $quickDeposits->getRecurringDeposits();
        $this->repeat_cards = $quickDeposits->getRepeatCreditCards($this->repeats);

        $this->prior_deposit = $this->c->getLatestDeposit($this->user);

        list($this->prior_deposit_psp, $this->prior_deposit_sub_psp) = $this->getPriorDepositPsp();

        $this->prior_deposit_info = ['psp' => $this->prior_deposit_psp, 'sub_psp' => $this->prior_deposit_sub_psp];
    }

    /**
     * Main logic filtering PSPs so we only have configs for PSPs we want to display.
     *
     * @return array The array of PSPs we want to display.
     */
    public function getPspsForDisplay(){
        $tmp_psps = [];
        $psps     = [];
        $ordering = $this->c->getSetting('psp_group_ordering')[$this->country] ?? ['ccard', 'hosted_ccard', 'bank', 'ewallet', 'pcard', 'mobile'];
        $filtered_ordering = lic('filterPaymentMethod', [$ordering], $this->user);
        $ordering = (!empty($filtered_ordering)) ? $filtered_ordering : $ordering;

        // We need this before doing the sort to keep the same order we have in the config file.
        $sub_ordering = [];
        foreach($this->full_config as $psp => $config) {
            $sub_ordering[$config['type']][] = $psp;
        }

        $this->reorderPspsInGroups($sub_ordering);

        uasort($this->full_config, function($a, $b) use ($ordering){
            $pos_a = array_search($a['type'], $ordering);
            $pos_b = array_search($b['type'], $ordering);
            return $pos_a - $pos_b;
        });

        foreach($this->full_config as $psp => $config){
            if($this->c->withdrawDepositAllowed($this->user, $psp, $this->action, $this->channel)){
                $this->populatePsp($config, $psp);
                $config[$this->action]['min_amount'] = mc($config[$this->action]['min_amount'], $this->user);
                $config[$this->action]['max_amount'] = mc($config[$this->action]['max_amount'], $this->user);
                $tmp_psps[$psp] = $config;
            }
        }

        // We need to use the sub ordering to keep the right position, but we need to use ordering to keep the right order of macro method
        foreach ($ordering as $type) {
            $sub_psps = $sub_ordering[$type];
            foreach($sub_psps as $psp) {
                if(isset($tmp_psps[$psp])) {
                    $psps[$psp] = $tmp_psps[$psp];
                }
            }
        }

        return $psps;
    }

    private function reorderPspsInGroups(array &$groups): void
    {
        foreach ($groups as $group => $psps) {
            $group_config = phiveApp(PspConfigServiceInterface::class)->getPspSetting($group);
            $psp_display_order = $group_config['deposit']['psp_display_ordering'][$this->country] ?? null;
            if ($psp_display_order) {
                $groups[$group] = $this->reorderArray($psps, $psp_display_order);
            }
        }
    }

    private function reorderArray(array $array, array $order): array
    {
        $intersect = array_intersect($order, $array);
        $diff = array_diff($array, $intersect);
        return array_merge($intersect, $diff);
    }

    /**
     * Interface type default declaration.
     */
    public function channelPriorDepositPsp($prior_psp, $prior_scheme)
    {

    }

    /**
     * Return the preselected default for a given country if any
     *
     * @return mixed
     */
    public function getPreselectDefault($first_only = true)
    {
        $res = $this->c->getSetting('preselect_defaults')[$this->country];

        if($first_only === true && is_array($res)) {
            return reset($res);
        }

        return $res;
    }

    public function getFirstActivePspName(): ?array
    {
        $firstActivePspName = array_key_first($this->psps);

        switch($firstActivePspName) {
            case 'bank':
                if (count($this->psps) > 1) {
                    return [$this->psps[$firstActivePspName]['type'], array_keys($this->psps)[1]];
                }

                return null;
            default:
                return [$firstActivePspName];
        }
    }

    /**
     * Responsible for picking the PSP to display on first load.
     *
     * Will try and use the previously used PSP but if this is the first time deposit we use various configs and default values.
     *
     * @return array An array with the prior PSP and prior scheme / sub PSP, used with list() in the caller.
     */
    public function getPriorDepositPsp(){
        $default_psp       = 'ccard';
        $prior_psp         = !empty($this->prior_deposit['card_hash']) ? $default_psp : $this->prior_deposit['dep_type'];
        $prior_scheme      = $this->prior_deposit['scheme'];

        if(empty($prior_psp)){
            $preselect_default = $this->getPreselectDefault(false);
            if (empty($preselect_default)) {
                $firstActivePspName = $this->getFirstActivePspName();

                if ($firstActivePspName !== null) {
                    return $firstActivePspName;
                }

                return [$default_psp, 'visa'];
            }

            if ($this->channel !== 'desktop' && is_array($preselect_default) && count($preselect_default) > 1) {
                $preselect_default = $preselect_default[1];
            }
            return is_array($preselect_default) ? $preselect_default : [$preselect_default, $preselect_default];
        }

        if (in_array($prior_psp, ['emp', 'wirecard'])) {
            $prior_psp = $default_psp;
        }

        list($prior_psp, $prior_scheme) = $this->channelPriorDepositPsp($prior_psp, $prior_scheme);

        if ($prior_psp === 'ccard' && $prior_scheme === 'googlepay') {
            return ['googlepay', $prior_scheme];
        }

        if(!$this->showAlt($prior_psp)){
            foreach($this->c->getSetting('preselect_overrides')[$prior_psp] as $override){
                if($this->showAlt($override)){
                    $prior_psp = $override;
                    break;
                }
            }
        }

        if(empty($prior_psp)){
            // For some reason we still didn't manage to get a prior PSP to select so we just default to ccard to avoid
            // the black screen.
            return ['ccard', 'visa'];
        }

        return [$prior_psp, $prior_scheme];
    }

    /**
     * Prints the CVV / CV2 card field.
     *
     * @return void
     */
    function printCvv(){ ?>
        <div class="cashierInputLabel"><?php et('dc.cv2'); ?></div>
        <input title="<?php et('cashier.error.required') ?>" name="cv2" class="cashierInput medium required dc_cv2" autocomplete="cc-csc" type="<?php echo $this->ifMobElse('tel', 'text') ?>"/>
        <div class="infoCvc icon_questionmark" onclick="theCashier.cvcInfo()"></div>
        <div class="info_box infoBoxCvc">
            <div class="cvs_info"><?php et("dc.cvs.info.html") ?></div>
            <div class="card_back"></div>
        </div>
    <?php
    }

    /**
     * Potential override of the info text, used to generate custom links etc.
     *
     * @param string $psp The PSP.
     *
     * @return string The info text.
     */
    public function getInfoTextOverride($psp){
        $map = [
            'muchbetter' => function() use ($psp, $config){
                $arr = phive()->mapit([
                    'currency'    => 'currency',
                    'email'       => 'email',
                    'firstname'   => 'firstname',
                    'lastname'    => 'lastname',
                    'dobYYYYMMDD' => 'dob',
                    'address1'    => 'address',
                    'zipcode'     => 'zipcode',
                    'phonenumber' => 'mobile',
                    'city'        => 'city',
                    'country'     => 'country'
                ], ud($this->user));
                $link = $this->full_config[$psp]['signup_link'].http_build_query($arr);
                return t2($this->getInfoAlias($psp), [$link]);
            }
        ];

        return $map[$psp];
    }

    public function getExtraMobileAppFields($psp, $ignore_channel = false){
        $msg = addslashes(t($psp.'.loader.msg'));
        jsTag("pspHooks.{$psp}.loaderMsg = '$msg';");
        $prior_acc_info = empty($this->prior_info[$psp]) ? $this->user->getMobile() : $this->prior_info[$psp];
        dbInput('message', phive()->getSetting('domain').' '.t('account.deposit'), 'hidden');
        if($this->channel == 'desktop' || $ignore_channel){
            $this->printLabel($psp, "accountid");
            dbInput('accountid', $prior_acc_info, 'text', 'cashierInput');
        }
    }

    /**
     * Prints more DOM elements that can not be easily configured.
     *
     * @param string $psp The PSP to work with.
     * @param array $psp_config The config of the PSP to work with.
     *
     * @return void
     */
    public function getExtraFieldsOverride($psp, $psp_config = []){

        $map = [
            'ccard' => function() use ($psp){

                // Will default to theCashier.postTransaction() if not changed from empty.
                $submit_func = '';

                // By default cvv length, if not getting from ccard_psp_config.
                $default_cvv_length = 3;

                if($_SESSION['sms_ok'] != true) {
                    // verifyPhone means we need to prompt for a possibly different phone number.
                    // verifyCode means we send the code and show a form where the player has to input
                    // the sms code.
                    $submit_func = (int)$this->user->getAttribute('verified_phone') === 1 ? 'verifyCode' : 'verifyPhone';
                }

                // We loop all suppliers that require external load of encryption script.
                foreach($this->mts->getExternalJsCcs() as $cc_psp => $config) {
                    foreach($config['js_urls'] as $url) {
                ?>
                    <script async src="<?php echo $url ?>"></script>
                <?php
                    }
                }

                // We loop all suppliers where we have the encryption script stored locally.
                foreach($this->mts->getLocalJsCcs() as $cc_psp => $config){
                    loadJs("/phive/js/$cc_psp.js");
                }


                // Our own encryption that we're currently not using, will be used again when / if we gain PCI.
                //loadJs("/phive/js/prng4.js");
                //loadJs("/phive/js/rng.js");
                //loadJs("/phive/js/jsbn.js");
                //loadJs("/phive/js/rsa.js");
                //loadJs("/phive/js/base64.js");

                $show_repeats = !empty($this->repeat_cards) && $this->c->canQuickDepositViaCard($this->user);
                ?>
                    <script>
                     var cuCcPsp        = '<?php echo $this->cc_supplier ?>';
                     theCashier.depositOverrides['<?php echo $psp ?>'] = '<?php echo $submit_func ?>';
                     theCashier.updateCvvInput('<?php echo $this->cc_psp_configs[$this->cc_supplier]['cvv_length'] ?? $default_cvv_length ?>');
                     $(document).ready(function(){
                         $('[name="cardnumber"]').on('blur keydown', function(){
                             $(this).val($(this).val().replace(/\D/g, ''));
                         });
                     });
                    </script>

                    <?php if($show_repeats): ?>
                        <script>

                         // We need this for for instance Credorax in order to access the ext id / token via the repeat id.
                         theCashier.repeatCards = <?php echo json_encode($this->repeat_cards) ?>;

                         theCashier.onClickHooks['ccard'] = function(){
                             $('.cashierBtnOuter, #deposit-amount, #deposit-amount-label').hide();
                         };

                         $('#deposit-amount').on('input',function(e){
                             // We display the normal deposit form in case the amount gets changed.
                             theCashier.cardSwitchToNormal();
                         });

                         $('.cashier-predefined-amount').click(function(){
                             // We display the normal deposit form in case the amount gets changed.
                             theCashier.cardSwitchToNormal();
                         });

                        </script>
                        <div id="card-repeats" class="dc-quick-deposit">
                            <div class="pad10">
                                <p><?php et('prior.deposit.info') ?></p>
                                <p><?php et('prior.deposit.confirm') ?></p>
                            </div>
                            <table class="zebra-tbl w-100-pc">
                                <tr class="zebra-header">
                                    <td><?php et('amount') ?></td>
                                    <td><?php et('card.number') ?></td>
                                    <td></td>
                                </tr>
                                <?php foreach($this->repeat_cards as $pcard):
                                    // We need to simulate as if a user is inputting the amount and they do not use cents
                                    $amount = $pcard['amount'] / 100;
                                ?>
                                    <tr class="<?php echo $i % 2 == 0 ? "even" : "odd"?>">
                                        <td><?php efEuro($pcard['amount']) ?></td>
                                        <td><?php echo $pcard['card_num'] ?></td>
                                        <td>
                                            <?php
                                            if($this->cc_psp_configs[$pcard['sub_supplier']]['repeat_type'] == 'oneclick') {
                                                $cvv_len   = $this->cc_psp_configs[$pcard['supplier']]['cvv_length'] ?? $default_cvv_length;
                                                btnDefaultL(t('deposit'), '', "theCashier.showCVCBox('{$pcard['id']}', '{$pcard['supplier']}', '{$amount}', '". t('card.cvc.title') ."', '{$cvv_len}'); return false;", 80);
                                            } else {
                                                btnDefaultL(t('deposit'), '', "theCashier.repeatCardDeposit('{$pcard['id']}', '{$pcard['supplier']}', '{$amount}'); return false;", 80);
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php $i++; endforeach; ?>
                            </table>
                            <br/>
                            <br/>
                            <div class="cashier-make-normal-wrapper">
                                <?php btnDefaultXl(t('make.normal.deposit'), '', "theCashier.cardSwitchToNormal(); return false;") ?>
                            </div>
                        </div>
                    <?php endif ?>

                    <div id="card-normal-form" class="cashierBoxInsertCol ccinfo_box" style="<?php echo $show_repeats ? 'display: none;' : '' ?>">
                        <input type="hidden" value="<?php echo date('Y-m-d\TH:i:s.000+00:00') ?>" extra-attr="adyen-generationtime"/>
                        <input type="hidden" value="<?php echo $this->user->getFullName() ?>" extra-attr="adyen-holdername"/>

                        <div class="cashierInputLabel"><?php et('dc.cardnumber'); ?></div>
                        <input title="<?php et('cashier.error.ccard') ?>" name="cardnumber" autocomplete="cc-number" class="cashierInput creditcard required dc_cardnumber" type="<?php echo $this->ifMobElse('tel', 'text') ?>"/>

                        <div class="cashierInputLabel"><?php et('dc.expirydate'); ?></div>
                        <input name="dc_exp1" title="<?php et('cashier.error.required') ?>" class="dc_exp1 cashierInput small required" maxlength="2" autocomplete="cc-exp-month" type="<?php echo $this->ifMobElse('tel', 'text') ?>"/>
                        <input name="dc_exp2" title="<?php et('cashier.error.required') ?>" class="cashierInput small required dc_exp2" maxlength="2" autocomplete="cc-exp-year" type="<?php echo $this->ifMobElse('tel', 'text') ?>"/>

                        <?php $this->printCvv() ?>

                    </div>
                <?php
                    return ['ccard', $this->repeat_cards];
            },
            'siirto' => function() use ($psp){
                $this->getExtraMobileAppFields($psp, true);
            },
            'swish' => function() use ($psp){
                $msg = addslashes(t($psp.'.loader.msg'));
                jsTag("pspHooks.{$psp}.loaderMsg = '$msg';");

                $swishRepeats = (new QuickDeposits($this->user))->getRepeatSwish();

                if ($swishRepeats) {
                ?>
                    <div id="<?php echo $psp ?>-actual-fields">
                        <?php $this->renderStandardRepeats($psp, $swishRepeats) ?>
                    </div>
                <?php
                }
            },
            'applepay' => function() use ($psp){
                ?>
                <script type="text/javascript">
                    $(document).ready(function() {
                        var hideApple = function () {
                            if(theCashier.preSelected.sub_psp == 'applepay'){
                                theCashier.logoClick(isMobileDevice() ? 'ccard' : 'visa', '');
                            }
                            $(isMobileDevice() ? '#logo-top-applepay' : '#logo-left-applepay').hide();
                        };

                        if (window.ApplePaySession) {
                            if (!ApplePaySession.canMakePayments()) {
                                hideApple.call();
                            }
                        } else {
                            hideApple.call();
                        }
                    });
                </script>
              <?php
              },
              'mifinity' => function() {
                  $mode = phive()->isTest() || phive()->isLocal() ? 'demo' : 'secure';
                  $url = "https://{$mode}.mifinity.com/widgets/sgpg.js?58190a411dc3";
                ?>
                  <script src="<?php echo $url ?>"></script>
                <?php
              }
        ];

        $map = $this->appendBankRepeatsMapping($map, $psp, $psp_config);

        $psp = $this->getOverride($psp) ?? $psp;
        $psp = $this->formDisplayOverride($psp) ?? $psp;
        return $map[$psp];
    }

    /**
     * @param array $map
     * @param string $psp The PSP to work with.
     * @param array $psp_config The config of the PSP to work with.
     *
     * @return array
     */
    private function appendBankRepeatsMapping($map, $psp, $psp_config) {
        if (!empty($psp_config) && ($psp_config['type'] == 'bank') && empty($this->bank_repeats)) {
            $map[$psp] = function () use ($psp) {
                $this->bank_repeats = (new QuickDeposits($this->user))->getRepeatBanks();

                if (empty($this->bank_repeats)) {
                    return null;
                }

                ?>
                <div id="<?php echo $psp ?>-actual-fields">
                    <?php $this->renderStandardRepeats($psp, $this->bank_repeats) ?>
                </div>
                <?php

                return [$psp, $this->bank_repeats];
            };
        }

        return $map;
    }

    public function renderStandardRepeats($psp, $deposits){
    ?>
        <div class="<?php echo "$psp-quick-deposit" ?> standard-quick-deposit">
            <table class="zebra-tbl w-100-pc">
                <tr class="zebra-header">
                    <td colspan="4"><?php et('standard.prior.deposit.info') ?></td>
                </tr>
                <?php foreach($deposits as $d):
                    $img_uri = null;
                    $map = $this->getPspKeyMap();

                    $imageFileName = $d['dep_type'] === Supplier::Swish
                        ? Supplier::Swish
                        : ($map[$d['scheme']] ?? $d['dep_type']);
                    $imageFileName .= '-small.png';

                    if(phive('Filer')->hasFile($imageFileName)){
                        $img_uri = phive('Filer')->getFileUri($imageFileName);
                    }
                    $amount = $d['amount'] / 100;
                ?>
                    <tr class="<?php echo $i % 2 == 0 ? "even" : "odd"?>">
                        <td>
                            <?php if(!empty($img_uri)): ?>
                                <img style="width: 75%; height: 75%;" src="<?php echo $img_uri ?>" />
                            <?php else: ?>
                                <?php echo $d['display_name'] ?>
                            <?php endif ?>
                        </td>
                        <?php if ($d['dep_type'] == 'trustly'): ?>
                            <td><?php echo $d['card_hash'] ?></td>
                            <td class="standard-quick-deposit-amount"><?php efEuro($d['amount']) ?></td>
                        <?php else: ?>
                            <td></td>
                            <td class="standard-quick-deposit-amount"><?php efEuro($d['amount']) ?></td>
                        <?php endif ?>
                        <td>
                            <?php

                            $dt        = $d['scheme'];
                            $action    = 'deposit';
                            $repeat_id =  '';

                            if (in_array($d['dep_type'], [Supplier::Trustly, Supplier::Swish])) {
                                if ($d['dep_type'] === Supplier::Trustly) {
                                    $dt = $dt ?: $d['dep_type'];
                                } elseif ($d['dep_type'] === Supplier::Swish) {
                                    $dt = $d['dep_type'];
                                }

                                $action = 'repeat';
                                $repeat_id = $d['mts_id'];
                            }

                            btnDefaultM(t('deposit'), '', "theCashier.postTransaction('{$action}', '{$repeat_id}', '{$amount}', '{$dt}'); return false;", 80, 'right');
                            ?>
                        </td>
                    </tr>
                <?php $i++; endforeach; ?>
            </table>
            <br/>
        </div>
        <?php
    }

    /**
     * Base method for the extra fields.
     *
     * @uses CashierDepositBoxBase::getExtraFieldsOverride()
     *
     * @param string $psp The PSP to work with.
     * @param array $psp_config The config of the PSP to work with.
     *
     * @return void
     */
    public function generateExtraElements($psp, $psp_config){
        $config = $psp_config[$this->action];

        // Some "tough" to do options like credit cards have overrides instead of the standard field generation.
        $override = $this->getExtraFieldsOverride($psp, $psp_config);
        if(!empty($override)){
            $override();
        }

        ?>
        <div class="bonus-code-field" style="display: none;">
            <div class="cashierInputLabel"><?php et('bonus.code') ?></div>
            <input name="bonus" class="cashierInput" type="text" value="" onblur="theCashier.addCheckBonusCode(this, '<?php echo $this->channel ?>')">
        </div>
    <?php
    }

    /**
     * Generation of handlebars templates applicable to deposits in general.
     *
     * @link https://handlebarsjs.com/
     *
     * @return void
     */
    public function generateHandleBarsTemplates(){
        parent::generateHandleBarsTemplates();
    ?>
        <script id="cvcOneclickPopupHb" type="text/x-handlebars-template">
            <div class="dcCVCBox">
                <div class="cashierCVCLabel"><?php et('card.cvc.label') ?></div>
                <input id="cvc" name="cv2" value="" class="cashierCVCInput medium required dc_cv2" autocomplete="cc-csc" type="text" style="top:3px;border-style: groove;">
                <div style="position: relative; margin-top: 30px;">
                    <button class="btn btn-l btn-default-l w-125 neg-margin-top-25" onclick="theCashier.repeatCardDeposit('{{repeatId}}', '{{network}}', '{{amount}}'); return false;">
                        <?php et('deposit') ?>
                    </button>
                </div>
            </div>
        </script>

        <script id="showPaymentMsg" type="text/x-handlebars-template">
            <div>
                {{{msg}}}
            </div>
            <?php btnDefaultL(t('back'), '', "theCashier.backToPspForm();", 80) ?>
        </script>

        <script id="smsSentHb" type="text/x-handlebars-template">
            <div id="verify-code" class="margin-ten mobileVer">
                <b><?php et('mobile.verify.sms.sent') ?></b>
                <br/>
                <div>
	            <?php et('mobile.verify.sms.sent.html') ?>
                </div>
                <br/>
                <input id="verification-code" name="code" class="cashierDefaultInput inline" type="<?php $this->ifMobElse('tel', 'text') ?>">
                <div class="cashierBtnOuter">
	            <div id="smsCodeSendBtn" class="cashierDefaultBtnInner" onclick="theCashier.submitSmsCode()">
	                <h4><?php et('submit') ?></h4>
	            </div>
                </div>
                <div class="errors"></div>
            </div>
        </script>

        <script id="repeatsHb" type="text/x-handlebars-template">
            <table class="zebra-tbl w-100-pc">
                <tr class="zebra-header">
                    <td><?php et('oneclick.deposits') ?></td>
                    <td></td>
                </tr>
                {{#each repeats as |repeat|}}
                    <tr class="">
                        <td> {{nfCents repeat.amount}} </td>
                        <td align="right">
                            <button class="btn btn-l btn-default-l " onclick="theCashier.postRepeat({{repeat.id}}, {{repeat.amount}}); return false;" style="width: 80px;">
                                <span>Deposit</span>
                            </button>
                        </td>
                    </tr>
                {{/each}}
            </table>
        </script>

        <script id="smsVerifyStartHb" type="text/x-handlebars-template">
            <div id="verify-start" class="margin-ten mobileVer">
                <b><?php et('mobile.verify.start') ?></b>
                <br/>
                <div>
                    <?php et('mobile.verify.start.html') ?>
                </div>
                <br/>
                <input id="mobile-verification" name="mobile" class="cashierDefaultInput" type="<?php $this->ifMobElse('tel', 'text') ?>" value="<?php echo $this->user->getAttribute('mobile') ?>">
                <br/>
                <table class="device-adjustment1">
                    <tr>
                        <td>
	                    <div class="cashierBtnOuter">
	                        <div class="cashierDefaultBtnInner" onclick="theCashier.verifyCode()">
	                            <h4><?php et('submit') ?></h4>
	                        </div>
	                    </div>
                        </td>
                    </tr>
                </table>
                <div class="errors">
                </div>
            </div>
        </script>

        <script id="predefAmountChunkHb" type="text/x-handlebars-template">
            {{#each amounts as |amount|}}
            <td>
                {{{amount}}}
            </td>
            {{/each}}
        </script>

        <script id="predefAmountHb" type="text/x-handlebars-template">
            <div id="cashier-predefined-amount-{{amount}}" class="cashier-predefined-amount cashier-predefined-amount-number" style="font-size: {{size}}px;" onclick="theCashier.setAmount('{{amount}}')">
                {{display_amount}}
            </div>
        </script>

    <?php
    }

    public function afterPrintHTML(){
        jsTag("var cashierWs = '".phive('UserHandler')->wsUrl('cashier')."';");
        phMsetShard('current-user-agent', $_SERVER['HTTP_USER_AGENT']);
        phMsetShard('current-accept-language', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
        loadCss("/diamondbet/fonts/icons.css");
    ?>
        <?php if(!phive()->isTest()): ?>
            <script type="text/javascript">
             maxmind_user_id = '<?php echo phive('IpBlock')->getSetting('maxmind_uid') ?>';
             (function() {
                 var loadDeviceJs = function() {
                     var element = document.createElement('script');
                     element.src = ('https:' == document.location.protocol ? 'https:' : 'http:')
                         + '//device.maxmind.com/js/device.js';
                     document.body.appendChild(element);
                 };
                 if (window.addEventListener) {
                     window.addEventListener('load', loadDeviceJs, false);
                 } else if (window.attachEvent) {
                     window.attachEvent('onload', loadDeviceJs);
                 }
             })();
            </script>
        <?php endif ?>


        <?php if(phive('UserHandler')->doForceDeposit($this->user)): ?>
            <script>
                <?php if(lic('shouldAskForCompanyDetails')): ?>
                    addToPopupsQueue(function () {
                        lic('showCompanyDetailsPopup', [true]);
                    });
                <?php else: ?>
                    addToPopupsQueue(function ()  {
                        mboxMsg('<?php et('no.deposit.msg') ?>', true, function(){ execNextPopup(); }, 450, ...Array(7), 'no-deposit-msg');
                    });
                <?php endif ?>
            </script>
        <?php endif ?>

        <?php if(lic('hasMessageOnCashier', [$this->user], $this->user) === true): ?>
            <script>
                addToPopupsQueue(function () {
                    lic('showMessageOnCashier', []);
                });
            </script>
        <?php endif ?>

        <?php if(lic('hasDepositLimitOnCashier', [$this->user], $this->user) === true): ?>
            <script>
                addToPopupsQueue(function () {
                    lic('showDepositLimitPrompt', []);
                });
            </script>
        <?php endif ?>

        <script>
            $(document).ready(function() {
                // for mobile we are already calling execNextPopup() in topcommon.php
                if (!isMobile()) {
                    execNextPopup();
                }
            });
        </script>

        <?php
    }

    /**
     * The main DOM printing method.
     *
     * Is invoked by the children which are to stop further DOM drawring if it returns false.
     *
     * @return bool True if all is good, false otherwise.
     */
    public function printHTML(){
        $res = parent::printHTML();
        if(!$res){
            return false;
        }
        $this->afterPrintHTML();
        return true;
    }

    /**
     * Fetches various content for display to the user.
     *
     * @return string The message or the empty string in case all is good and the user can deposit.n
     */
    function getBlockMsg(){
        if(phive('MicroGames')->blockMisc($this->user))
            return 'deposit.country.ip.restriction';

        list($res, $action) = phive("Cashier")->checkOverLimits($this->user, 0, false);
        if($res) {
            $this->block_action = $action;
            return 'deposits.over.limit.html';
        }

        if(phive('Cashier')->hasPendingIncomeDocs($this->user))
            return 'nodeposit.explanation.proofofincome.html';

        $checkDepositViewBlocked = lic('checkDepositViewBlocked', [$this->user], $this->user);
        if ($checkDepositViewBlocked === false) {
            if ($this->user->isDepositBlocked()) {
                return 'deposit.blocked.html';
            }
        } elseif (!empty($checkDepositViewBlocked)) {
            return $checkDepositViewBlocked;
        }

        return '';
    }

    function isReturn(){
        foreach($_REQUEST as $key => $val){
            if(strpos($key, 'end') !== false){
                return true;
            }
        }
        return false;
    }

    /**
     * This method is responsible for displaying a proper message, typically on deposit return.
     *
     * @return array An associative array with the message and if it was a return or not.
     */
    function onPspReturn()
    {
        $is_return = $this->isReturn();
        $psp = $_GET['supplier'];

        $action = strtolower($_REQUEST['action']);
        $action = $action == 'cancelled' ? 'cancel' : $action;
        if (!empty($_REQUEST['d_type']) && $_REQUEST['d_type'] == 'undo') {
            $return_action = 'undo';
        } else {
            $return_action = $this->action;
        }

        $failed = $action == 'fail' || in_array($_REQUEST['status'], ['failed', 'cancel']);
        if (!$failed) {
            $failed = $this->didInstadebitDepositFail();
        }

        $aborted = (!empty($_REQUEST['cancel_reason']) && $_REQUEST['cancel_reason'] == 'User') || $action == 'cancel';
        $func = 'fb' . ucfirst($return_action) . 'Complete';

        $userId = $this->user->getId();
        $depositCount = phive('Cashier')->getApprovedDepositsCount($userId);

        $msg = phive()->ob(function () use ($failed, $aborted, $func, $psp, $return_action, $depositCount) {
            if (!empty($this->errors)) {
                // Other types of return errors, typically ccard 3d authorize issues.
                et($this->errors);
            } else if ($failed || $aborted) {
                et($this->full_config[$psp][$return_action]['custom_fail_msg'] ? "$psp.{$return_action}.failed.html" : $return_action . '.failed.html');
            } elseif ($return_action == 'undo') {
                et('undo.complete.body');
            } else {
                $custom_return_str = null;
                if (!empty($psp)) {
                    $custom_return_str = phiveApp(PspConfigServiceInterface::class)->getPspSetting($psp, $return_action)['success_return_msg'];
                }
                empty($custom_return_str) ? call_user_func_array($func, [$depositCount]) : et($custom_return_str);
            }
        });

        $isFirstDeposit = !$failed
            && $depositCount === 1
            && phive('Bonuses')->shouldShowWelcomeOffer($userId);

        if ($is_return) {
            // We unset 3D enroll data regardless of if we have a failure or success.
            $this->mts->unsetSess('3d_result');
        }

        $result = [
            'isFirstDeposit' => $isFirstDeposit,
            'is_return' => $is_return,
            'isSuccess' => !($aborted || $failed),
            'msg' => $msg,
        ];

        $depositFallbacks = $this->c->getSetting('fallback_deposit', []);
        $fallbackDepositDecorator = new FallbackDepositDecorator($this->user, $depositFallbacks);

        return $fallbackDepositDecorator->decorate($result);
    }

    /**
     * Check if instadebit deposit is approved or failed.
     * Seems like instadebit has as single return_url which is triggered even if the payment is canceled.
     *
     * @return bool
     */
    private function didInstadebitDepositFail()
    {
        $is_instadebit = $_GET['instadebit_end'];
        if ($is_instadebit) {
            $token = $_GET['token'];
            $deposit = phive("Cashier")->getDepByExt($token, 'instadebit');
            if (!$deposit || $deposit['status'] === 'disapproved') {
                return true;
            }
        }

        return false;
    }

    /**
     * Base PHP to JS communication / configs / variables.
     * This one will be used per default unless overridden in the channel class.
     * It sets the following:
     * * cashier.psps: the basic display config we're using here on the PSP side too.
     * * cashier.overrides: various the override configs.
     * * cashier.groups: grouping info like BANK having to show all the grouped PSPs in the middle.
     * * cashier.returnInfo: success or error message on deposit return that we display in a JS powered popup.
     * @return void
     */
    public function setPspJson()
    {
        $show_deposit_limit = rgLimits()->getLicDepLimit($this->user)
            && !rgLimits()->skipLicDepLimitReminder($this->user)
            && empty($this->deposit_limit_skip_popup);
        ?>
        <script>
            theCashier.preSelected = <?php echo json_encode($this->prior_deposit_info) ?>;
            theCashier.active = true;
            theCashier.overrideConfig = <?php echo json_encode($this->c->getSetting('psp_overrides')[$this->action][$this->channel]) ?>;
            var pciCompliant = <?php echo phive()->getJsBool($this->pci_compliant) ?>;
            theCashier.successMsg = '<?php et('deposit.successful') ?>';
            <?php parent::setPspJson() ?>
            cashier.overrides = <?php echo empty($this->overrides) ? '{}' : json_encode($this->overrides) ?>;
            cashier.groups = <?php echo empty($this->groups) ? '{}' : json_encode($this->groups) ?>;
            cashier.preselConfig = <?php echo json_encode($this->getPreselectDefault()) ?>;
            cashier.repeats = <?php echo json_encode($this->repeats) ?>;
            cashier.ccConfigs = <?php echo json_encode($this->cc_psp_configs) ?>;
            theCashier.licDepLimitShow = <?php echo phive()->getJsBool($show_deposit_limit) ?>;
            cashier.env = <?php echo json_encode($_ENV['APP_ENVIRONMENT']) ?>;
            cashier.googlePayConfig = <?php echo json_encode($this->full_config['googlepay']) ?>
        </script>
        <?php
    }

    /**
     * The main entry point method for generating PSP specific fields and labels.
     *
     * This method does the following:
     * * 1. Merges the main display PSP array with all override configs.
     * * 2. **Ignores** any PSP that has an override to avoid displaying eg BANK **and** Trustly special fields.
     * * 3. Loops the configured fields and outputs them.
     * * 4. Finally outputs any extra DOM elements that might be applicable for the PSP / override in question for the current action.
     *
     * The reason that we're not doing this in the form of Handlebars templates is that this extra logic sometimes needs to contain script
     * tags, these script tags screw up the parsing logic so we need to do them like this instead, the old HTML copy and replacement trick.
     * The downside is that we can't use direct id selectors on these elements as the DOM would then contain duplicate ids.
     *
     * @uses CashierDepositBoxBase::generateExtraElements()
     * @uses CashierWithdrawBoxBase::generateExtraElements()
     * @uses CashierBoxBase::getOverrideConfigs()
     * @uses CashierBoxBase::generateExtraField()
     *
     * @param string $do_only Do only this PSP if passed.
     * @param bool $hide To hide or not to hide the fields, should be false for the fast deposit interface.
     *
     * @return void
     */
    public function generateExtraFields($do_only = '', $hide = true){
        foreach(array_merge($this->psps, $this->getOverrideConfigs()) as $psp => $config){

            // For the fast deposit we only want the fields for one PSP.
            if(!empty($do_only) && $do_only != $psp){
                continue;
            }

            if(!empty($this->getOverride($psp))){
                // We have an orverride, ie ccard replaces VISA.
                continue;
            }

            if($hide){
                echo '<div id="'.$psp.'-fields" style="display: none;">';
            }

            $this->generateExtraElements($psp, $config);

            // If we have a form display override we skip standard fields, eg MuchBetter for Norwegians that would show ccard fields but still
            // have its own info section.
            if(empty($this->formDisplayOverride($psp))){
                $fields = $config[$this->action]['extra_fields'];

                foreach($fields as $field => $field_config){
                    $this->generateExtraField($psp, $field, $field_config);
                }
            }

            if($hide){
                echo '</div>';
            }
        }
    }

    public function formDisplayOverride($psp){
        return phiveApp(PspConfigServiceInterface::class)->getPspSetting($psp, $this->action)['override'][$this->user->getCountry()]['display'];
    }

    protected function redirectToVerificationModal($user) {
        return $user
            && lic('shouldRedirectToVerificationModal', [$user], $user)
            && lic('isGbgFailed', [$user], $user)
            && $_GET['no_redirect'] != 1;
    }

    //TODO It is a hotfix for resolving PSP logo for Quick deposit. We should implement better solution in future.
    public function getPspKeyMap(){
        $map = [];
        $psps = $this->c->getSetting('psp_config_2');
        foreach ($psps as $key => $psp) {
            $map[$key] = $key;
        }
        return $map;
    }
}
