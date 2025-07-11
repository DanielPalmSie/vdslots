<?php

use DBUserHandler\DBUserRestriction;
use Laraphive\Domain\Payment\DataTransferObjects\ListWithdrawalProvidersData;
use Laraphive\Domain\Payment\DataTransferObjects\Responses\GetWithdrawalProvidersResponseData;
use Laraphive\Domain\Payment\DataTransferObjects\VerifiedCardsData;
use Laraphive\Domain\Payment\Factories\WithdrawalProvidersFactory;
use Laraphive\Domain\Payment\Mappers\WithdrawalProvidersMapper;
use Laraphive\Domain\Payment\Services\PspConfigServiceInterface;

require_once 'CashierBoxBase.php';

/**
 * This is the base withdraw class for the new cashier.
 *
 * This class contains the functionality that is common to:
 *
 * 1. Desktop withdraw.
 * 2. Mobile withdraw.
 *
 * Since these two scenarios are very similar between mobile and desktop this class contains the vast majority of all logic
 * pertaining to withdrawals.
 *
 */
class CashierWithdrawBoxBase extends CashierBoxBase{


    /**
     * The base array of source data fetched from the MTS.
     * @var array
     */
    public $sources = [];

    /**
     * The base array of card data fetched from the MTS.
     * @var array
     */
    public $verified_sources = [];

    /**
     * Used to generate a drop down of bank accounts that can be used to withdraw to as well as controlling various other
     * elements whose display is affected by the presence or absence of bank accounts.
     * @var array
     */
    public $bank_accounts = [];

    /**
     * Current FIFO PSP
     *
     * @var mixed
     */
    public $fifo_psp;

    public const CLOSED_LOOP = 'closed_loop';

    public const FIFO = 'fifo';

    /**
     * Current unverified FIFO PSP, for instance when cards needs to be verified
     *
     * @var mixed
     */
    public $unverified_fifo_psp;

    public $display_sources = [];

    public $anti_fraud_scheme;

    public $closed_loop_applies = false;

    public $unverified_sources = [];

    public $deposit_info = null;


    /**
     * Array of psp methods that cannot be used for webview with using `provider` query string
     *
     * @var string[]
     */
    public $not_allow_psp_webview = [
        'applepay'
    ];

    /**
     * The box init method.
     *
     * Here we:
     * * Get the bank accounts that can be used to withdraw to.
     * * Setup the bank accounts drop down data.
     * * Get the cards that can be used to withdraw to.
     * * Set the KYC status of the ccard option / "PSP" (it's a display override)
     *
     * @param DBUser $u_obj A potential user object.
     *
     * @return void
     */
    function init($u_obj = null){
        $this->action = 'withdraw';

        parent::init($u_obj);

        $this->bank_accounts = $this->c->getBankAccountsFromDocuments($this->user);

        $bank_doc = array_filter(phive('Dmapi')->getDocuments($this->user->getId()), function($doc){
            return $doc['tag'] == 'bankpic';
        });

        if(empty($bank_doc)){
            $res = phive('Dmapi')->createEmptyBankDocument($this->user, [], 'bank');
        }

        // Get all credit cards from the MTS, and use the Dmapi to check if the cards are verified.
        // This way we are dealing with a single source of truth,
        // and are using the same data that can be seen through the backoffice.
        $bin_codes = $this->c->getSetting('prepaid_bin_codes')[$this->user->getCountry()];

        $cards = [];
        if($bin_codes === false)
            $cards = []; // Country is set to false in the config so withdrawals with card has been turned off.
        else if(!empty($bin_codes))
            $cards = $this->mts->getCardsByBincodes($this->user, $bin_codes);
        else{
            $cards = $this->mts->rpc('query', 'recurring', 'getAllCardsForWithdraw', ['user_id' => $this->user->getId()]);
            // $cards = $this->mts->getCards(0, $this->mts->getOutCcSuppliers());
        }

        $this->sources['ccard'] = $cards;

        foreach ($cards as $card) {
            if ($card['sub_supplier'] === 'googlepay') {
                continue;
            }

            $card_type = phiveApp(PspConfigServiceInterface::class)->getPspSetting($card['sub_supplier'])['type'];
            if(empty($card_type)){
                // We're looking at a card sub supplier here, ie bambora via piq, so we use the card scheme, ie visa, mc etc.
                $card_type = 'ccard';
            } else {
                // mc, visa etc will have type ccard so we treat them the same, otherwise we're looking at a card wallet like
                // Apple Pay with its own DPAN scheme.
                $card_type = $card_type == 'ccard' ? 'ccard' : $card['sub_supplier'];
            }

            $can_withdraw_result = $this->c->canWithdraw($this->user, $card_type, '', '', $card['id']);

            // If card is KYC approved we add it to the drop down
            if($can_withdraw_result['success'] === true) {
                $this->addVerifiedSource('ccard', $card_type, $card);
            } else {
                $this->addUnverifiedSource('ccard', $card_type, $card);
            }
        }

        foreach ($this->bank_accounts as $bank_account) {
            $can_withdraw_bank_account = $this->c->canWithdraw($this->user, $bank_account['supplier'], '', $bank_account['account_ref']);

            if($can_withdraw_bank_account['success'] === true) {
                $this->addVerifiedSource('bank', $bank_account['supplier'], $bank_account);
            } else {
                $this->addUnverifiedSource('bank', $bank_account['supplier'], $bank_account);
            }
        }

        $this->handleFraudProtection();
    }

    public function addVerifiedSource($type, $sub_type, $data){
        $this->verified_sources[$type][$sub_type][] = $data;
    }

     public function addUnverifiedSource($type, $sub_type, $data){
        $this->unverified_sources[$type][$sub_type][] = $data;
    }

    public function setMultipleDisplaySources($psps){
        foreach($psps as $psp => $conf){
            $this->setDisplaySources($psp, null, $conf);
        }
    }

    public function setDisplaySources($type, $data = null, $config = [])
    {
        $config['closed_loop_cents'] = $config['closed_loop_cents'] ?? 0;
        if(empty($data)){
            if(empty($this->deposit_info)){
                $this->deposit_info = $this->c->getDepositInfo($this->user);
            }
            $source = $this->deposit_info[$config['option_of'] ?? $type];
            if(!empty($source) && $config['closed_loop_cents'] !== -1){
                // A standard PSP like Skrill with a Skrill email as source.
                $data = [$source];
            }
        } else {
            // A more complex option like a bunch of credit / debit cards.
            $data = array_filter($data, function($row){
                return $row['closed_loop_cents'] !== -1;
            });
        }
        if(!empty($data)){
            $this->display_sources[$type] = $data;
        }
    }

    public function getVerifiedSource($type, $sub_type = null){
        $ret = $this->verified_sources[$type];
        return empty($sub_type) ? phive()->flatten($ret, true) : $ret[$sub_type];
    }

    public function getUnverifiedSource($type, $sub_type = null){
        $ret = $this->unverified_sources[$type];
        return empty($sub_type) ? phive()->flatten($ret, true) : $ret[$sub_type];
    }

    public function getDisplaySources($type): array
    {
        return $this->display_sources[$type] ?? [];
    }

    public function getDisplaySourcesBySubKey($type, $subKey){
        return $this->display_sources[$type][$subKey];
    }

    public function hasAchievedClosedLoop(){
        return $this->closed_loop_applies === false;
    }

    public function handleFraudProtection(){

        $this->anti_fraud_scheme = $this->c->getAntiFraudScheme($this->user);
        $cards                   = $this->getVerifiedSource('ccard');
        $unverified_cards        = $this->getUnverifiedSource('ccard');

        $banks = $this->getVerifiedSource('bank');
        $unverified_banks = $this->getUnverifiedSource('bank');

        switch($this->anti_fraud_scheme){
            case self::FIFO:

                $fifo_data = $this->c->getFifo($this->user, $this->psps, $cards);
                $this->fifo_psp = $fifo_data[0];

                // We get rid of all non FIFO cards.
                foreach($cards as $c){
                    if($fifo_data[1]['card_hash'] == $c['card_num']){
                        $this->setDisplaySources($this->fifo_psp, [$c]);
                        break;
                    }
                }

                if (!empty($unverified_cards)) {
                    $this->unverified_fifo_psp = $this->c->getFifo($this->user, $this->psps, $unverified_cards)[0];
                }

                $ret_banks = [];
                foreach($banks as $bank){
                    $ret_banks['banks'][$bank['supplier']][] = $bank;
                }
                $this->setDisplaySources('banks', $ret_banks['banks']);

                phive('Logger')->getLogger('payments')->debug('FIFO PSP and Unverified_FIFO_psp: CashierWDBoxBase',
                    [
                        'user' => $this->user->userId,
                        'FIFO_Data' => $fifo_data[0],
                        'Unverified_FIFO_psp' => $this->unverified_fifo_psp ?? "",
                    ]);

                $this->c->setCurrentFifo($fifo_data);
                break;

            case self::CLOSED_LOOP:
                $closedLoopFacade = $this->c->getClosedLoopFacade();
                $filteredOptions = $closedLoopFacade->prepareWithdrawOptionsForDisplay($this->user, $cards, $banks, $this->psps);
                [$display_cards, $display_banks, $this->psps, $this->closed_loop_applies] = $filteredOptions;

                $this->setMultipleDisplaySources($this->psps);
                $this->setDisplaySources('ccard', $display_cards['ccard']);
                $this->setDisplaySources('applepay', $display_cards['applepay']);

                $this->handleSelectBankAccountsInClosedLoop($banks, $display_banks);
                $this->setDisplaySources('banks', $display_banks['banks']);
                break;

            default:
                $this->setMultipleDisplaySources($this->psps);
                $ret_cards = [];
                foreach($cards as $card){
                    if($card['sub_supplier'] == 'applepay') {
                        $ret_cards['applepay'][] = $card;
                    } else {
                        $ret_cards['ccard'][] = $card;
                    }
                }

                $this->setDisplaySources('ccard', $ret_cards['ccard']);
                $this->setDisplaySources('applepay', $ret_cards['applepay']);

                $ret_banks = [];
                foreach($banks as $account){
                    $ret_banks['banks'][$account['supplier']][] = $account;
                }
                $this->setDisplaySources('banks', $ret_banks['banks']);
                break;
        }
    }

    private function handleSelectBankAccountsInClosedLoop(array $allBankAccounts, array &$display_Banks): void
    {
        foreach ($allBankAccounts as $bank_account) {
            $supplier = $bank_account['supplier'];

            $displayBanks = &$display_Banks['banks'][$supplier];

            $found = array_filter($displayBanks, function ($bank) use ($bank_account) {
                return $bank['account_ref'] === $bank_account['account_ref'];
            });

            if (empty($found)) {
                $bank_account['encrypted_account_ext_id'] = uniqid('account_', true);
                $displayBanks[] = $bank_account;
            }
        }
    }

    /**
     * This gets the potential block message that will abort further DOM rendering.
     *
     * @return string The message in case there is one, empty string if not.
     */
    function getBlockMsg(){
        if($this->user->hasUnpaidInvoices())
            return 'unpaid.invoices.html';
        return '';
    }

    /**
     * Main DOM / HTML rendering entrypoint.
     *
     * Here we immediately display an error message if the user can not withdraw (not verified etc), and abort further DOM output.
     *
     * @uses CasinoCashier::canWithdraw()
     *
     * @return bool True if we should continue DOM output in the child, false otherwise.
     */
    public function printHTML()
    {
        $res = parent::printHTML();

        if (!$res) {
            return false;
        }
        $user = cuPl();

        if (!$user) {
            return false;
        }

        ?>
        <script type="text/javascript" src="/phive/js/jquery.validate.iban.js"></script>
        <?php if ($user->getSetting('current_status') !== UserStatus::STATUS_ACTIVE) { ?>
            <?php if (lic('showAccountVerificationReminder', [$user, true], $user)) { ?>
            <script>lic('showVerifyIdentityReminder', []);</script>
            <?php } ?>
            <?php
            $restriction = $this->user->getDocumentRestrictionType();

            if (!empty($restriction) && $this->user->getSetting('restriction_reason') !== DBUserRestriction::SOWD) {
                $msg_title = 'restrict.msg.expired.documents.title';

                if ($restriction == 'restrict.msg.processing.documents') {
                    $msg_title = 'restrict.msg.processing.documents.title';
                }

                ?>
                <script>
                    extBoxAjax('restricted-popup', 'restricted-popup', {msg_title: '<?= $msg_title ?>'}, {});
                </script>
                <?php
                //do not show warning message on a document's page
                $_SESSION['restricted_msg_shown'] = true;
            }
            ?>
            <?php if (lic('showAccountVerificationOvertime', [$user], $user)) { ?>
                <script>lic('showAccountVerificationOvertime', []);</script>
          <?php }
        }

        $can_withdraw_result = $this->c->canWithdraw($this->user);

        return $this->handleCanWithdrawResult($can_withdraw_result);
    }

    /**
     * Takes the return of CasinoCashier::canWithdraw() and outputs appropriate HTML/JS.
     *
     * @param mixed $can_withdraw_result The can withdraw result.
     *
     * @return bool True if user can withdraw, false otherwise, used in child classes to determine what they want to do.
     */
    public function handleCanWithdrawResult($can_withdraw_result): bool
    {
        if ($can_withdraw_result['success'] === true) {
            return true;
        }

        if (isset($can_withdraw_result['data'][0]) && $can_withdraw_result['data'][0] == 'source_of_funds') {
            jsTag("showSourceOfFundsBox('/sourceoffunds1/?document_id='+{$can_withdraw_result['data'][1]['id']});");
        }

        $msg = $can_withdraw_result['msg'];
        ?>
        <div class="frame-block withdraw-disabled-area">
            <?php if($this->channel != 'mobile'): ?>
                <?php img('documents.header.banner', 960, 308) ?>
            <?php endif ?>
            <div class="frame-holder" id="nowithdraw_explanation">
                <?php et($msg) ?>
                <br/>
                <?php if($msg === 'verify'): ?>
                    <div class="cashierBtnOuter" style="margin: 25px;">
                        <div class="cashierBtnInner" onclick="window.location.href = '<?php echo editCardUrl($this->user->getId(), $this->channel) ?>';">
                            <h4><?php et($msg) ?></h4>
                        </div>
                    </div>
                <?php endif ?></div>
        </div>
        <?php

        return false;
    }

    /**
     * Handles whether or not an alternative should display or not if the user has not deposited.
     *
     * We first check if this requirement is disabled by a config setting, we then deal with special cases.
     * If no special cases we simply check if prior_info is empty or not.
     *
     * @param string $psp The PSP to work with.
     * @param array $config The PSP's config.
     *
     * @return bool True if the user needs to deposit first, false otherwise.
     */
    public function depositFirst(string $psp, array $config): bool
    {
        $depositFirst = (bool)($this->c->getResolvedPspConfigValue(
            $this->user,
            'deposit_first',
            true,
            $psp,
            $config)
        );

        if ($depositFirst === false) {
            return false;
        }

        if (array_key_exists('option_of', $config)) {
            $psp = $config['option_of'];
        }

        switch ($psp) {
            case 'ccard':
                // No verified cards and no unverified cards means that no deposits have been made.
                return empty($this->getVerifiedSource('ccard')) && empty($this->getUnverifiedSource('ccard'));

            default:
                $prior_info = $this->prior_info[$psp];

                if (!empty($prior_info)) {
                    return false;
                }

                return $this->c->getPspDepCount($this->user->getId(), $psp) == 0;
        }
    }

    /**
     * This method is responsible for displaying a proper message, typically on deposit return.
     *
     * @return array An associative array with the message and if it was a return or not.
     */
    function onPspReturn(){
        $is_return = false;

        if(!empty($_GET['end'])){
            $string = $_GET['action'] == 'fail' || $_GET['status'] == 'failed' ? 'withdraw.fail.body' : 'withdraw.complete.body';
            $msg    = t($string);
            $is_return = true;
        }

        return ['is_return' => $is_return, 'msg' => $msg];
    }


    /**
     * This method basically defaults to CasinoCashier::canWithdraw() apart from an extra check for CCs, if there exists any cards
     * that money can be withdrawn to.
     *
     * @return array True in the success key if the user can withdraw, false otherwiwse.
     */
    public function canWithdraw(string $psp, array $config): array
    {
        $displaySources = $this->getDisplaySources($psp);
        $hasClosedLoop = $this->hasAchievedClosedLoop();
        $unverifiedSource = !empty($this->getUnverifiedSource($psp)) || !empty($this->getUnverifiedSource($config['be_type'], $psp));

        if (empty($displaySources) && !$hasClosedLoop) {
            if ($unverifiedSource) {
                /*
                 * We have unverified sources, but an anti-fraud scheme has filtered out all display options for
                 * some reason. This state should be treated as unverified.
                */
                phive('Logger')->getLogger('payments')->debug(
                    'CASHIER BOX BASE canWithdraw: UNVERIFIED SOURCE (payment service)',
                    [
                        'user_id' => $this->user->getId(),
                        'source' => $displaySources,
                        'cl_achievement' => $hasClosedLoop
                    ]
                );
                return $this->c->retFail('verify');
            }

            /*
             * We have verified sources, but Closed Loop has filtered out all display options for some reason.
             * This state should be treated as blocked.
            */
            return $this->c->retFail('err.disabled.by.closed.loop');
        }

        $type = ($psp == 'applepay') ? 'ccard' : $psp;
        $sub_type = ($type == 'ccard') ? $psp : null;

        if (!empty($this->getVerifiedSource($type, $sub_type))) {
            // We have some verified sources for the option / psp we therefore display it.
            return $this->c->retSuccess();
        }

        return $this->c->canWithdraw($this->user, $psp);
    }

    public function getPspDisplayData(string $psp, array $config): array
    {
        $is_withdraw_fallback = $this->c->isWithdrawFallback($psp, $this->user);
        $showEditProfileBtn = false;
        $showVerifyBtn = false;
        $block_msg = '';

        $config_max = $config[$this->action]['max_amount'];
        $cl_cents   = $this->psps[$psp]['closed_loop_cents'];
        $config_min = $config[$this->action]['min_amount'];

        // Due to closed loop requirements the player might have to be allowed to withdraw less than normal and
        // at the same time be forced to withdraw less than normal max.
        $min_amount = $cl_cents > 0 && $cl_cents < $config_min ? $cl_cents : $config_min;
        $max_amount = $cl_cents > 0 && $cl_cents < $config_max ? $cl_cents : $config_max;

        $do_standard_kyc = true;

        switch($this->anti_fraud_scheme){
            case self::FIFO:
                // We check FIFO first, no need to have the player go through KYC on non FIFO method. Unless it is an unverified FIFO.
                if(!$is_withdraw_fallback && !$this->c->isCurrentFifo($psp, null, '', $this->psps, $this->user)){
                    if ($this->unverified_fifo_psp == $psp) {
                        $showVerifyBtn = true;
                    } else {
                        $block_msg = 'fifoblock.html';
                    }
                    // FIFO overrides the standard KYC handling / display.
                    $do_standard_kyc = false;
                }
                break;

            case self::CLOSED_LOOP:
                if($this->psps[$psp]['closed_loop_cents'] === -1){
                    // Disabled option because of pending withdrawals.
                    $block_msg = 'err.disabled.by.closed.loop';
                }
                break;

            default:
                break;
        }


        if($do_standard_kyc){
            $deposit_first = $this->depositFirst($psp, $config);
            if ($deposit_first) {
                $block_msg = 'depositFirst.html';
            } else {
                $canWithdraw = $this->canWithdraw($psp, $config);

                if($canWithdraw['success'] === false) {
                    if (!empty($canWithdraw['data']) && $canWithdraw['msg'] === 'wrong.fields') {
                        $block_msg = array_values($canWithdraw['data'])[0];
                        $showEditProfileBtn = true;
                    } elseif (!empty($canWithdraw['data']) && $canWithdraw['data'][0] === 'wrong_user_fields') {
                        $showEditProfileBtn = true;
                    } elseif ($canWithdraw['msg'] == 'verify') {
                        $showVerifyBtn = true;
                    } else {
                        $block_msg = $canWithdraw['msg'];
                    }

                    if (!empty($canWithdraw['data']) && $canWithdraw['data']['display_block_message'] === false) {
                        unset($block_msg);
                    }
                }
            }
        }

        $deduct = phive('Cashier')->getDisplayDeduct($psp, $this->user);

        return [
            $is_withdraw_fallback,
            $showEditProfileBtn,
            $showVerifyBtn,
            $block_msg,
            $min_amount,
            $max_amount,
            $canWithdraw ?? null,
            $deduct
        ];
    }

    /**
     * Main PSP printing logic.
     *
     * This method is used on every PSP to be displayed, for the withdrawals we do not use Handlebars as they all should show on desktop anyway.
     * We simply hide the main part of the box and when the top expanding arrow / bar is clicked we display that region, this is just like how
     * it worked in the old box logic but with one exception, we don't do unique ids on all form fields anymore.
     *
     * @param string $psp The PSP to render.
     * @param array $config The PSP config.
     * @param bool $hide Controls whether or not we hide the whole box, atm true on mobile and false on desktop.
     *
     * @return void
     */
    public function printPsp(string $psp, array $config, bool $hide = false): void
    {
        list ($is_withdraw_fallback,
            $showEditProfileBtn,
            $showVerifyBtn,
            $block_msg,
            $min_amount,
            $max_amount,
            $canWithdraw,
            $deduct
            ) = $this->getPspDisplayData($psp, $config);

        ?>

        <div id="<?php echo $psp ?>Box" class="cashierBox" style="border-color: rgb(51, 51, 51); <?php echo $hide ? 'display: none;' : '' ?>">
            <span class="slideTrigger">
                <div class="cashierHeader">
                    <h3><?php echo $this->getDisplayName($psp) ?></h3>
                    <div class="arrow open"></div>
                </div>
                <div class="infoArea">
                    <div class="infoImage">
                        <img src="<?php echo fupUri($this->getLogo($config['display_psp'], 'big')) ?>">
                    </div>
                    <span class="infoText">
                        <?php et("{$this->action}.start.{$config['display_psp']}.html") ?>
                    </span>
                    <ul class="cashier-fee-list">
                        <li>
                            <span class="ulBig"><?php et("expenses") ?></span>
                            <span class="cashier-expense"> <?php echo $deduct ?> </span>
                        </li>
                        <li>
                            <span class="ulBig cashier-expense-min-label"><?php et("min") ?></span>
                            <span class="cashier-expense cashier-expense-min-value">
                                <?php efEuro(mc($min_amount, $this->user)) ?>
                            </span>
                        </li>
                        <li>
                            <span class="ulBig cashier-expense-min-label"><?php et("max") ?></span>
                            <span class="cashier-expense cashier-expense-max-value">
                                <?php efEuro(mc($max_amount, $this->user)) ?>
                            </span>
                        </li>
                    </ul>
                    <div style="clear: both;"></div>
                </div>
            </span>
            <div class="cashierBoxInsert" style="display: block;">
                <?php if(!empty($block_msg)): ?>
                    <div class="cashierInputLabel maxWidth">
                        <?php et($block_msg) ?>
                    </div>
                <?php endif; ?>
                <?php if ($showVerifyBtn): ?>
                    <div class="cashierInputLabel maxWidth">
                        <?php et('verifyfirst.html'); ?>
                    </div>
                    <div class="cashierBtnOuter">
                        <div class="cashierBtnInner" onclick="window.location.href = '<?php echo editCardUrl($this->user->getId(), $this->channel, false, $is_withdraw_fallback ? '?create_doc_for='.$psp : '') ?>'">
                            <h4><?php et('verify') ?></h4>
                        </div>
                    </div>
                <?php elseif ($showEditProfileBtn): ?>
                    <?php if (isset($canWithdraw['data'][1]) && is_array($canWithdraw['data'][1])): ?>
                        <div class="cashierInputLabel maxWidth">
                            <?php foreach ($canWithdraw['data'][1] as $errorAlias): ?>
                                <?php et($errorAlias) ?><br>
                            <?php endforeach; ?>
                        </div>
                    <?php endif ?>
                    <div class="cashierBtnOuter">
                        <div class="cashierBtnInner" onclick="window.location.href = '<?=phive('UserHandler')->getUserAccountUrl('update-account') ?>'">
                            <h4><?php et('edit-profile') ?></h4>
                        </div>
                    </div>
                <?php else: ?>
                    <?php
                        $validationRules = htmlspecialchars(json_encode($this->c->getFrontEndWithdrawValidationRules($psp, $this->user->getCountry())), ENT_QUOTES);
                    ?>
                    <form id="withdrawForm-<?php echo $psp ?>" <?php if(!empty($block_msg) && empty(isMobileSite())): ?>style="padding-top: 25px;" <?php endif ?>class="withdrawForm" method="post" action="" data-validation="<?=$validationRules?>">
                        <?php $this->generateExtraFields($psp, $config) ?>

                        <div class="cashierInputLabel amount-label"><?php et('dc.register.amount') ?></div>
                        <input title="<?php et('cashier.error.required') ?>" name="amount" value="" class="cashierInput required" type="<?php echo $this->ifMobElse('tel', 'number') ?>">

                        <div class="cashierBtnOuter">
                            <div class="cashierBtnInner withdrawPost">
                                <h4><?php et('goto') ?></h4>
                            </div>
                        </div>

                        <!--The hidden submit button ensures that pressing Enter in any input field triggers the form submission as expected.-->
                        <button type="submit" style="display: none;"></button>
                    </form>
                <?php endif ?>
            </div>
        </div>

    <?php
    }

    /**
     * Helper to display the CC drop down of cards that can be withdrawn to.
     *
     * @return void
     */
    public function printCardsDropDown($type = 'ccard'){ ?>
        <div class="cashierInputLabel"><?php et('choose.ccard') ?>:</div>
        <?php if(!useOldDesign()): ?><div class="cashierSelect__container"><?php endif; ?>
        <?php dbSelectWith('ccard_select', $this->getDisplaySources($type), 'id', ['card_num', 'closed_loop_formatted'], '', [], 'ccard_select cashierSelect') ?>
        <?php if(!useOldDesign()): ?></div><?php endif; ?>
    <?php
    }

    /**
     * Helper to display the bank accounts drop down of banks that can be withdrawn to.
     *
     * @return void
     */
    public function printBankAccountsDropDown($psp){
        ?>
        <div class="cashierInputLabel"><?php et('choose.bank.account') ?>:</div>
        <?php if(!useOldDesign()): ?><div class="cashierSelect__container"><?php endif; ?>
        <?php dbSelectWith(
            'account_select',
            $this->getDisplaySourcesBySubKey('banks', $psp) ?? [],
            'encrypted_account_ext_id',
            ['display_name', 'closed_loop_formatted'],
            '',
            [],
            'bank_account_select cashierSelect required',
            ['display_name', 'closed_loop_formatted', 'user_currency', 'closed_loop_cents']
        ) ?>
        <?php if(!useOldDesign()): ?></div><?php endif; ?>
        <?php
    }

    public function printSwishAccountsDropDown() {
        ?>
        <div class="cashierInputLabel"><?php et('choose.swish.account') ?>:</div>
        <?php if(!useOldDesign()): ?><div class="cashierSelect__container"><?php endif; ?>
        <?php dbSelectWith('swish_mobile', $this->getDisplaySourcesBySubKey('banks', 'swish'), 'account_ext_id', ['account_ext_id', 'closed_loop_formatted'], '', [], 'swish_mobile_select cashierSelect required') ?>
        <?php if(!useOldDesign()): ?></div><?php endif; ?>
        <?php
    }

    public function printHtmlCommon($hook = null, bool $selected_psp = false){
        $this->setCashierJs();
        if(!empty($hook)){
            $hook();
        }
    ?>

        <div id="cashierDepositWithdraw">
            <?php if (!$selected_psp): ?>
                <?php if ($this->channel === 'mobile'): ?>
                    <h1><?php et("withdraw.{$this->channel}.start.headline"); ?></h1>
                    <p>
                        <?php et("withdraw.{$this->channel}.start.html"); ?>
                    </p>
                <?php else: ?>
                    <div class="withdrawal-content">
                        <div class="withdrawal-text">
                            <h1><?php et("withdraw.{$this->channel}.start.headline"); ?></h1>
                            <div>
                                <?php et("withdraw.{$this->channel}.start.html"); ?>
                            </div>
                        </div>
                        <div class="withdrawal-images">
                            <img src="<?php echo getMediaServiceUrl(); ?>/file_uploads/img_tech-support.png" alt="tech-support-image"/>
                        </div>
                    </div>
                <?php endif; ?>
                <br>
            <?php endif; ?>

            <?php
            if (parent::printHTML()) {
                foreach ($this->psps as $psp => $config) {
                    $this->printPsp($psp, $config, $selected_psp);
                }
            }
            ?>
        </div>
        <div id="withdraw_complete" style="display: none;">
            <?php if (!useOldDesign()): ?>
                <img src="/diamondbet/images/<?= brandedCss() ?>withdrawal-success.svg" alt="withdrawal-success-image">
            <?php endif; ?>
            <h3><?php et('withdraw.complete.headline') ?></h3>
            <p><?php et('withdraw.complete.body') ?></p>
        </div>
        <?php
        $this->setPspJson();
    }


    public function printWithdrawBankAccountsSection($psp)
    {
        ?>
        <?php if(!empty($this->bank_accounts)):  ?>
        <button type="button" class="btn btn-l btn-default-l bank-other-account-btn"
                onclick="theCashier.otherBankAccount('<?php echo $psp ?>')"><?php et('other.bank.account') ?>
        </button>
        <?php endif ?>

        <?php if (in_array($this->user->getCountry(), ['SE', 'CA'])): ?>
            <div class="<?php echo empty($this->bank_accounts) ? '' : 'cashier-init-hidden' ?>">
                <div class="cashierInputLabel"><?php et('bank.sortcode') ?></div>
                <?php dbInput("clearnr", '', 'text', 'cashierInput', sprintf('required="" title="%s"', t('cashier.error.required')), false) ?>
            </div>
            <?php $this->printWithdrawBankAccountInput('bank.accnumber', 'accnumber', $this->bank_accounts) ?>
        <?php else: ?>
            <?php $this->printWithdrawBankAccountInput('bank.iban', 'iban', $this->bank_accounts) ?>
            <script>
                $('input[name="iban"]').on('blur keydown', function () {
                    $(this).val($(this).val().toUpperCase().replace(/\s/g, ''));
                });
            </script>
        <?php endif ?>
        <?php
    }

    public function printWithdrawBankAccountInput($label, $name, $accounts, $fieldId = false)
    {
        ?>
        <div class="cashierInputLabel"><?php et($label) ?></div>
        <?php if(empty($accounts)): ?>
            <?php dbInput($name, '', 'text', "cashierInput $name", sprintf('required="" title="%s"', t('cashier.error.required')), $fieldId) ?>
        <?php else: ?>
            <?php dbSelect($name, $accounts ?? $this->bank_accounts, '', [], "cashierInput large $name", false, sprintf('required="" title="%s"', t('cashier.error.required')), $fieldId) ?>
        <?php endif ?>
        <?php
    }

    /**
     * Here we render form fields / elements whose display is to complicated to control via configuration.
     *
     * @param string $psp The PSP to work with.
     * @param array $psp_config The PSP config.
     * @param DBUser $u_obj Optional user object, currently logged in user will be used in case it is omitted.
     * @param bool $return_fields True if we should return the fields, false if we want to render HTML instead.
     *
     * @return mixed Array if we want the fields to be returned, null or bool otherwise.
     */
     public function generateExtraElements($psp, $psp_config, $u_obj = null, $return_fields = false){

         $u_obj = $u_obj ?? $this->user;

         $fields = [];

         switch($psp){
             case 'applepay':
                 $ds = $this->getDisplaySources('applepay');
                 if ($this->anti_fraud_scheme == self::FIFO) {
                     ?>
                     <p>
                         <?php et2("{$psp}.withdraw.info", [$ds[0]['card_num']]) ?>
                     </p>
                     <br/>
                     <input id="ccard_select" name="ccard_select" value="<?php echo $ds[0]['id'] ?>" type="hidden">
                     <?php
                 } else {
                     if (!$return_fields) {
                         $this->printCardsDropDown('applepay');
                     }
                 }
                 return true;
             case 'ecopayz':
                 $accounts       = Mts::getInstance('', $u_obj)->getAccounts(Supplier::EcoPayz);
                 $account        = end($accounts);
                 $previous_value = !empty($account['value']) ? $account['value'] : '';
                 $fields[] = [$psp, 'accnumber', ['validate' => 'required'], $previous_value, !empty($previous_value)];
                 break;
             case 'instadebit':
                // We just make sure that we don't display the default bank fields by returning.
                return true;
         }

        switch($psp_config['type']){
            case 'bank':
            case 'ebank':
            case 'interaccat':
            case 'mobile':
                $via_network = $psp_config['via']['network'] ?? $this->c->getNetworkRoute($this->user, $psp_config);
                $old = phive('Cashier')->getLastPending($via_network, $u_obj->getId());
                $loc_map = [
                    'bank_clearnr'        => 'bank.branch.code',
                    'bank_account_number' => 'bank.accnumber',
                    'iban'                => 'bank.iban',
                    'swift_bic'           => 'bank.bic',
                    'bank_code'           => 'bank.code',
                    'bank_city'           => 'register.state',
                    'nid'                 => 'register.nid',
                    'bank_name'           => 'bank.name'
                ];

                $country = $u_obj->getCountry();

                if ($psp === 'inpay'){
                    $loc_map['bank_clearnr'] = $country == 'NZ'
                        ? 'bank.bank_clearing_bsb' : 'bank.bank_clearing_system_id';
                    $loc_map['bank_account_number'] = 'bank.bank_account_number';
                }

                switch($psp){
                    case 'citadel':
                        $is_eu = phive('UserHandler')->userIsEu($u_obj);
                        if($country == 'CA'){
                            $fields[] = [$psp, 'bank_name', [], $old['bank_name'], false, 'bank.name'];
                        }

                        $fields = $is_eu ? ['iban', 'swift_bic'] : ['bank_code', 'bank_clearnr', 'bank_account_number'];

                        foreach($fields as $field){
                            $fields[] = [$psp, $field, [], $old[$field], false, $loc_map[$field]];
                        }
                        break;

                    case 'interac':
                    case 'ecashout':
                        $loc_map['bank_code'] = 'bank.financial.institution.number';
                        $loc_map['bank_clearnr'] = 'bank.transit.number';
                        $loc_map['bank_account_number'] = 'bank.interac.account.number';

                        $validation_rules = [
                            'bank_code' => ['validate' => ['minlength' => 3, 'maxlength' => 3]],
                            'bank_clearnr' => ['validate' => ['minlength' => 5, 'maxlength' => 5]],
                            'bank_account_number' => ['validate' => ['minlength' => 7, 'maxlength' => 12]]
                        ];
                        foreach ($validation_rules as $field => $validation) {
                            $fields[] = [$psp, $field, $validation, $old[$field], false, $loc_map[$field]];
                        }
                        break;

                    case 'swish':
                        $this->printSwishAccountsDropDown();
                        break;

                    case 'trustly':
                        if(!$return_fields){
                            $this->printBankAccountsDropDown('trustly');

                            include __DIR__ . '/../../../Cashier/AddAccountService.php';
                            (new AddAccountService())->handleTrustlyBankAdditionalFields('trustly');
                        }
                        break;

                    case 'interacetransfer':
                        break;

                    case 'zimplerbank':
                        // Nothing apart from amount field at shis point.
                        $nid_field_config = $psp_config[$this->action]['custom_fields']['nid'];
                        // Don't have SE in the countries list in a casino with SGA license, they have a verified NID already.
                        if(!empty($nid_field_config) && in_array($this->country, $nid_field_config['countries'])){
                            if($this->user->hasNid()){
                                // We disable in case we have a verified NID.
                                $nid      = $this->user->getNid();
                                $disabled = true;
                            } else {
                                $nid      = $this->user->getSetting('unverified_nid');
                                $disabled = null;
                            }
                            // 11 for FI, 12 for SE so min 11 and max 12 should cover both countries, what we want is for
                            // Swedes to use 12 and not the 10 digit version in any case which we accomplish here.
                            $fields[] = [$psp, 'nid', ['validate' => ['minlength' => 11, 'maxlength' => 12]], $nid, $disabled];
                        }

                        if(!empty($this->c->getIbanOrAcc($old))):
                            if (!lic('hideAccountField', [], $this->user)):
                        ?>
                            <div class="cashierInputLabel"><?php et('transfer.to.account') ?>:</div>
                            <?php dbSelect('destination_account', [$this->c->getIbanOrAcc($old) => $this->c->getIbanOrAcc($old), '' => t('new.account')], '', [], 'cashierSelect') ?>
                        <?php
                            endif;
                        endif;

                        break;

                    case 'siirto':
                    case 'sepa':
                    case 'wpsepa':
                        // We filter out all broken IBANs as SEPA uses them for more than just display.
                        $this->bank_accounts = array_filter($this->bank_accounts, function ($iban) {
                            return empty(PhiveValidator::start($iban)->iban()->error);
                        });

                        $this->printWithdrawBankAccountsSection($psp);

                        if ($psp == 'wpsepa') {
                            $this->generateExtraField($psp, 'bank_name', ['validate' => 'required'], $old['bank_name'], false, 'bank.name');
                        }
                        break;

                    case 'payanybank':
                        $bicConfiguredCountries = $this->full_config['payanybank']['withdraw']['extra_fields']['swift_bic']['countries'];

                        if ($psp === 'payanybank'
                            && $this->user->getCurrency() === 'EUR'
                            && !in_array($this->user->getCountry(), $bicConfiguredCountries)
                        ) {
                            $this->generateExtraField(
                                $psp,
                                'swift_bic',
                                ['validate' => 'required'],
                                null,
                                true,
                                'register.swift_bic'
                            );
                        }

                        // We return empty array in order to prevent the default Inpay logic to execute.
                        return [];
                        break;

                    default:

                        $extra_fields = [];

                        if(in_array($psp, ['payretailers', 'pix'])){
                            $fields = ['bank_account_number', 'nid'];
                            if(in_array($u_obj->getCountry(), ['BR'])){
                                $fields[] = 'bank_clearnr';
                            }

                            // Extra fields.
                            /*
                               Do NOT remove this just yet, we might have to use this if the LATAM situation gets out of hand
                               with all the banks, then it might be better to have them in the banks table instead of configured. /Henrik
                            $bank_data = $this->c->getUserBanks($u_obj);
                            if(!empty($bank_data)){
                                $data = [];
                                foreach($bank_data as $row){
                                    $data[$row['start_clnr']] = $row['bank_name'];
                                }
                                $extra_fields[] = [$psp, 'bank_code', ['type' => 'drop_down', 'values' => $data]];
                            }
                             */

                            $drop_downs = $psp_config['withdraw']['extra_dropdowns'][$u_obj->getCountry()] ?? [];

                            foreach($drop_downs as $field => $data){
                                $extra_fields[] = [$psp, $field, ['type' => 'drop_down', 'values' => $data, 'validate' => 'required']];
                            }

                        } elseif($psp === 'inpay') {
                            // Define the field sets for European and non-European users
                            $europeanFields = ['bank_name', 'iban', 'swift_bic'];
                            $nonEuropeanFields = ['bank_name', 'bank_account_number', 'swift_bic'];
                            $fieldsByCounty = [
                                [
                                    'countries' => ['AU', 'IN', 'JP'],
                                    'fields' => ['bank_name', 'bank_account_number', 'bank_clearnr'],
                                ],
                                [
                                    'countries' => ['NZ', 'CA'],
                                    'fields' => ['bank_name', 'bank_account_number', 'bank_clearnr', 'swift_bic'],
                                ],
                                [
                                    'countries' => ['ZA', 'TH', 'ID'],
                                    'fields' => ['bank_name', 'bank_account_number', 'swift_bic'],
                                ],
                                [
                                    'countries' => ['PL', 'DK', 'IM', 'MK', 'MD', 'GB'],
                                    'fields' => ['bank_name', 'iban'],
                                ],
                                [
                                    'countries' => ['BR'],
                                    'fields' => ['bank_name', 'bank_account_number', 'nid'],
                                ],
                                [
                                    'countries' => ['HU'],
                                    'fields' => ['bank_name', 'iban', 'bank_account_number'],
                                ]
                            ];

                            $userCountry = $u_obj->getCountry();
                            $fields = [];
                            foreach ($fieldsByCounty as $item) {
                                if (in_array($userCountry, $item['countries'])) {
                                    $fields = $item['fields'];
                                    break;
                                }
                            }
                            if (empty($fields)) {
                                $fields = (phive('UserHandler')->userIsEu($u_obj) ? $europeanFields : $nonEuropeanFields);
                            }
                        }

                        foreach($fields as $field){
                            $cur_val  = null;
                            $disabled = null;
                            if($field == 'nid'){
                                $cur_val = $u_obj->getNid();
                                if(!empty($cur_val)){
                                    $disabled = true;
                                }
                            }
                            $cur_val = $cur_val ?? $old[$field];
                            $fields[] = [$psp, $field, ['validate' => 'required'], $cur_val, $disabled, $loc_map[$field]];
                        }

                        $fields = array_merge($fields, $extra_fields);

                        break;
                }
                break;

            case 'ccard':
                if(!$return_fields){
                    $this->printCardsDropDown('ccard');
                }
                break;
        }

        if($return_fields){
            return $fields;
        }

        foreach($fields as $field){
            call_user_func_array([$this, 'generateExtraField'], $field);
        }

        return false;
    }

    /**
     * The extra fields generation for withdrawals.
     *
     * Note how this version is much simpler than the deposit version which corresponds to the simpler nature of the withdraw interface.
     *
     * @param string $psp The PSP.
     * @param array $config The PSP config.
     *
     * @return void
     */
    public function generateExtraFields(string $psp, array $config): void
    {
        $fields = $config[$this->action]['extra_fields'] ?? [];

        foreach ($fields as $field => $field_config) {
            $this->generateExtraField($psp, $field, $field_config);
        }

        $this->generateExtraElements($psp, $config);

        $trailingFields = $config[$this->action]['trailing_extra_fields'] ?? [];

        foreach ($trailingFields as $field => $field_config) {
            $this->generateExtraField($psp, $field, $field_config);
        }
    }

    /**
     * The PSP generation logic for withdrawals.
     *
     * This one works differently than the deposit version as we're looking at a different override logic.
     * The deposit logic overrides **completely**, ie it is ccard you see when clicking on the MC logo.
     * Withdraw works differently, here we're instead looking at disparate PSPs that are all to display as BANK but
     * with their own unique form fields etc.
     *
     * Therefore it is simpler to just override various cosmetic elements like the display name, content and logo and
     * let the rest be original, therefore the overrides work more as templates here and do not display in any form
     * or way. But their content still needs to be translated etc as it's used by the real PSPs.
     *
     * @return array The array of PSPs that are to be displayed.
     */
    public function getPspsForDisplay(): array
    {
        $unordered = $psps = [];

        $all_psps = $this->c->getAllAllowedPsps($this->user, $this->action, $this->channel, $this->full_config);

        foreach ($all_psps as $psp => &$config) {
            if (array_key_exists('options', $config)) {
                foreach ($config['options'] as $option => &$option_config) {
                    $this->populatePsp($option_config, $option);
                }
            } else {
                $this->populatePsp($config, $psp);
            }

            $override = $this->getOverride($psp);
            if (!empty($override)) {
                $override_config = $this->full_config[$override];
                $this->populatePsp($override_config, $psp);

                $config = phive()->moveit(['display_name'], $override_config, $config);
                $config['display_psp'] = $override;
            }

            // The override logic for withdrawals do not allow overrides to at all display
            // as a standalone alternative, they have to be powered by real PSP configs.
            if (!$this->isOverride($psp)) {
                if (array_key_exists('options', $config)) {
                    foreach ($config['options'] as $key => $option) {
                        $unordered[$key] = $option;
                    }
                } else {
                    $unordered[$psp] = $config;
                }
            }
        }

        $ordering = $this->c->getSetting('psp_ordering')[$this->country] ?? ['ccard'];

        foreach ($ordering as $psp) {
            $psps[$psp] = $unordered[$psp];
        }

        $psps = array_filter(array_merge($psps, array_diff_key($unordered, $psps)));

        return $psps;
    }

    /**
     * Outputs various data structures as JSON for the JS logic to consume.
     *
     * @return void
     */
    public function setPspJson(){ ?>
        <script>
         <?php parent::setPspJson() ?>
        </script>
    <?php
    }

    /**
     * Common HTML goes here.
     *
     * To call this Handlebars templates is a misnomer as we don't use handlebars to render it but simply Jquery.
     *
     * @return xxx
     */
    public function generateHandleBarsTemplates(){ ?>
        <div id="withdraw_complete" <?php if(!$show) echo 'style="display: none;"' ?>>
            <h3><?php et('withdraw.complete.headline') ?></h3>
            <p><?php et('withdraw.complete.body') ?></p>
        </div>
    <?php
    }

    /**
     * @param DBUser $user
     * @param \Laraphive\Domain\Payment\DataTransferObjects\ListWithdrawalProvidersData $data
     *
     * @return string
     */
    private function generatePspWithdrawalUrl(DBUser $user, ListWithdrawalProvidersData $data): string
    {
        $base_url = phive('UserHandler')->getSiteUrl($user->getCountry());
        $page = llink('/mobile/cashier/withdraw/', $user->getAttr('preferred_lang'));
        $web_view_extra = $data->isWebView() ? '&display_mode=' . $data->getDisplayMode() . '&auth_token=' . $data->getAuthToken() : '';

        return $base_url . $page . '?provider=%s' . $web_view_extra;
    }

    /**
     * @api
     *
     * @return array<\Laraphive\Domain\Payment\DataTransferObjects\VerifiedCardsData>
     */
    public function getVerifiedCards(): array
    {
        $results = [
            'cards' => [],
            'applepay_cards' => [],
        ];

        $this->init(cu());
        $cards = $this->getDisplaySources('ccard');
        $appleCards = $this->getDisplaySources('applepay');

        if ($this->anti_fraud_scheme == self::FIFO && !empty($appleCards)) {
            $results['applepay_cards'][] = new VerifiedCardsData($appleCards[0]['id'], $appleCards[0]['card_num']);
        } else {
            foreach ($appleCards as $appleCard) {
                $results['applepay_cards'][] = new VerifiedCardsData(
                    $appleCard['id'],
                    $appleCard['card_num'],
                    $appleCard['closed_loop_cents']
                );
            }
        }

        foreach ($cards as $card) {
            $results['cards'][] = new VerifiedCardsData($card['id'], $card['card_num']);
        }

        return $results;
    }

    public function getAvailableBankAccounts(): array
    {
        $this->init(cu());
        return $this->getDisplaySources('banks');
    }

    /**
     * @param string $psp
     * @param array $config
     * @param User $user
     * @param string $withdrawal_url
     *
     * @return array
     */
    public function makeProvider(string $psp, array $config, User $user, string $withdrawal_url): array
    {
        list (,
            $showEditProfileBtn,
            $showVerifyBtn,
            $blockMsg,
            $minAmount,
            $maxAmount,
            $canWithdraw,
            $deduct
            ) = $this->getPspDisplayData($psp, $config);

        return [
            'name' => $psp,
            'display_name' => $this->getDisplayName($psp),
            'type' => $config['type'],
            'available' => $canWithdraw['success'] ?? false,
            'small_logo' => fupUri($this->getLogo($config['display_psp']), true),
            'big_logo' => fupUri($this->getLogo($config['display_psp'], 'big'), true),
            'withdrawal_url' => !in_array($psp, $this->not_allow_psp_webview) ? sprintf($withdrawal_url, $psp) : null,
            'extra_details' => [
                'show_edit_profile_btn' => $showEditProfileBtn,
                'show_verify_btn' => $showVerifyBtn,
                'block_msg' => $blockMsg,
                'min_amount' => $minAmount,
                'max_amount' => $maxAmount,
                'deduct' => $deduct,
                'description_alias' => "withdraw.start.{$config['display_psp']}.html",
            ],
        ];
    }

    /**
     * @param string $psp
     * @param string $amountTypeLimit
     * @param array $config
     *
     * @return mixed
     */
    private function checkAmountByCurrency(string $psp, string $amountTypeLimit, array $config)
    {
        $amount = $config['withdraw'][$amountTypeLimit];

        if($psp === 'inpay') {
            $amount = $amount[$this->currency] ?? $amount['DEFAULT'];
        }

        return $amount;
    }

    /**
     * @param string $psp
     * @param array $config
     * @param \User $user
     *
     * @return array|null
     */
    private function getRequiredFields(string $psp, array $config, User $user): ?array
    {
        switch ($config['type']) {
            case 'ccard':
            case 'ewallet':
                return $this->getRequiredFieldsForCards($psp, $user);
            case 'bank':
                return $this->getRequiredFieldsForBanks($psp, $config, $user);
        }

        return null;
    }

    /**
     * @param string $psp
     * @param \User $user
     *
     * @return array|null
     */
    private function getRequiredFieldsForCards(string $psp, User $user): ?array
    {
        $cards = $this->getUserCreditCards($user);

        return empty($cards)
            ? null
            : [
                'id' => $cards[$psp]['card_id'],
                'name' => 'ccard',
                'value' => $cards[$psp]['card_num'],
            ];
    }

    /**
     * @param string $psp
     * @param array $config
     * @param \User $user
     *
     * @return array|null
     */
    private function getRequiredFieldsForBanks(string $psp, array $config, User $user): ?array
    {
        $cashier = phive('Cashier');
        $viaNetwork = $config['via']['network'] ?? $cashier->getNetworkRoute($user, $config);
        $old = $cashier->getLastPending($viaNetwork, $user->getId()) ?? [];
        $oldBankName = $old['bank_name'] ?? $psp;
        $locMap = [
            'bank_clearnr' => 'bank.branch.code',
            'bank_account_number' => 'bank.accnumber',
            'iban' => 'bank.iban',
            'swift_bic' => 'bank.bic',
            'bank_code' => 'bank.code',
            'bank_city' => 'register.state',
            'nid' => 'register.nid',
            'bank_name' => 'bank.name'
        ];

        switch ($psp) {
            case 'wpsepa':
                return $this->getRequiredFieldsForBankWsepa($oldBankName);
            case 'interac':
                return $this->getRequiredFieldsForBankInterac($locMap, $old, $psp);
        }

        return null;
    }

    /**
     * @param string $bankName
     *
     * @return array
     */
    private function getRequiredFieldsForBankWsepa(string $bankName): array
    {
        return [
            'id' => 'bank_name',
            'name' => 'bank.name',
            'value' => $bankName,
        ];
    }

    /**
     * @param array $locMap
     * @param array $old
     * @param string $psp
     *
     * @return array
     */
    private function getRequiredFieldsForBankInterac(array $locMap, array $old, string $psp): array
    {
        $requiredFields = $fields = [];
        $locMap = array_merge($locMap, [
            'bank_code' => 'bank.financial.institution.number',
            'bank_clearnr' => 'bank.transit.number',
            'bank_account_number' => 'bank.interac.account.number',
        ]);
        $validationRules = [
            'bank_code' => ['validate' => ['minlength' => 3, 'maxlength' => 3]],
            'bank_clearnr' => ['validate' => ['minlength' => 5, 'maxlength' => 5]],
            'bank_account_number' => ['validate' => ['minlength' => 7, 'maxlength' => 12]]
        ];

        foreach ($validationRules as $field => $validation) {
            $requiredFields[] = [
                'id' => $field,
                'name' => $locMap[$field],
                'value' => $old[$field] ?? $psp,
            ];
        }

        return $requiredFields;
    }

    /**
     * @param \User $user
     *
     * @return array
     */
    private function getUserCreditCards(User $user): array
    {
        $cashier = phive('Cashier');
        $mts = new Mts('', $user);
        $cards = $mts->rpc('query', 'recurring', 'getAllCardsForWithdraw', ['user_id' => $user->getId()]) ?? [];
        $cardsVerified = [];

        foreach ($cards as $card) {
            $cardType = $this->getCardType($card);

            if ($cashier->canWithdraw($user, $cardType, '', '', $card['id']) === true) {
                $cardsVerified[$cardType][] = $card;
            }
        }

        return $cardsVerified;
    }

    /**
     * @param array $card
     *
     * @return string
     */
    private function getCardType(array $card): string
    {
        $cardSupplier = phiveApp(PspConfigServiceInterface::class)->getPspSetting($card['sub_supplier']);

        return isset($cardSupplier['type']) && $cardSupplier['type'] !== 'ccard'
            ? $card['sub_supplier']
            : 'ccard';
    }
}
