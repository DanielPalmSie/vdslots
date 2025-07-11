<?php

use Laraphive\Domain\Payment\Services\PspConfigServiceInterface;

//require_once __DIR__.'/../../../../../diamondbet/boxes/DiamondBox.php';
require_once __DIR__.'/../../../../../phive/modules/Cashier/Mts.php';


/**
 * This is the base class for the new cashier.
 *
 * This class contains the functionality that is common to:
 *
 * 1. Desktop deposit.
 * 2. Desktop withdraw.
 * 3. Mobile deposit.
 * 4. Mobile withdraw.
 *
 */
class CashierBoxBase{

    /**
     * @var CasinoCashier An instance of CasinoCashier so we don't have to do the singleton invocation everywhere.
     */
    public $c = null;

    /**
     * This is the full CasinoCashier config for all PSPs that we will use instead of calling CasinoCashier::getSetting()
     * in various places.
     * @var array
     */
    public $full_config = [];

    /**
     * The currently logged in user (or passed in on the CLI).
     * @var DBUser
     */
    public $user = null;

    /**
     * The ISO2 code of the current user's country, used instead of calling DBUser::getCountry() everywhere.
     * @var string
     */
    public $country = '';

    /**
     * The ISO3 code of the current user's currency, used instead of calling DBUser::getCurrency() everywhere.
     * @var string
     */
    public $currency = '';

    /**
     * An instance of the Mts.
     * @var Mts
     */
    public $mts = null;

    /**
     * A filtered version of $full_config only containing the PSP configurations for the currently applicable
     * PSPs that we want to display.
     * @var array
     */
    public $psps = [];


    /**
     * This variable will contain an error message for display in case the user is blocked in some way from making
     * deposits or withdrawals.
     * @var string
     */
    public $block_msg = '';


    /**
     * This variable will contain an error action for display in case the user is blocked in some way from making
     * deposits or withdrawals.
     * @var string
     */
    public $block_action = '';

    /**
     * This one is in the correct place, controls prepopulated values for deposits and whether or not
     * certain PSPs can be withdrawn with / to.
     * @var array $prior_info
     */
    public $prior_info = [];

    /**
     * The init function.
     *
     * Here we initiate everything we need for the various combinations of channel / device and transaction type (deposit / withdraw).
     *
     * @param DBUser $u_obj An optional user object which can be used to test on the CLI. If we're in a web server context
     * the user will be the currently logged in user.
     *
     * @return void
     */
    public function init($u_obj = null){
        $this->c                 = phive('Cashier');
        $this->full_config       = phiveApp(PspConfigServiceInterface::class)->getPspSetting();


        $this->user              = $u_obj ?? cuPl();

        if(empty($this->user)){
            $this->user = null;
            /**
             * The new strict browser cookie policy to prevent webtracking removes the session when we are coming back from a payment's supplier
             * As of Sep 2021 only affects iOS devices, but this feature will become standard soon
             * This workaround requires that we reload the page coming from our domain so the session is started on this request and the cookie is correctly set on the next request
             * see. ch129598
             */
            if (empty($_REQUEST['redirected']) && $this->action === 'deposit' && $_GET['end'] === 'true') {
                phive('Redirect')->to('/diamondbet/html/cashier-redirection.php?' . http_build_query(array_merge($_GET, ['redirected' => true, 'to' => $_REQUEST['dir']])));
            }
            phive()->dumpTbl('deposit-start-logout', phive()->getCommonLogVariables());
            phive('Redirect')->to(llink('/'));
        }

        $this->country           = $this->user->getCountry();
        $this->currency          = $this->user->getCurrency();
        $this->mts               = new Mts('', $this->user);
        $this->psps              = $this->getPspsForDisplay();
        $this->block_msg         = $this->getBlockMsg();
        $this->prior_info        = $this->c->getDepositInfo($this->user);

        if($this->action === 'deposit' && $_GET['end'] === 'true'
            && ($_GET['status'] === 'failed' || $_GET['action'] === 'fail') ) {
            $this->c->fireOnFailedDeposit($this->user, $this->action);
        }
    }

    public function baseInit(){}
    public function is404(){ return false; }

    public function getInfoAlias($psp, $country = null){
        $alias = "{$this->action}.start.$psp";

        if ($country) {
            $alias .= ".$country";
        }

        $alias .= ".html";
        return $alias;
    }

    public function getInfoTextOverride($psp){
        return null;
    }

    /**
     * Here we populate with various info that we don't want or can have in the config.
     *
     * We also popupate with default values so we don't have to do a lot of unnecessary conditionals
     * everywhere, for instance default max and min deposit / withdraw amounts.
     *
     * @param array &$arr The config array passed in as a reference.
     * @param string $psp The PSP we're working with / populating.
     *
     * @return void
     */
    public function populatePsp(&$arr, $psp){
        $info_text_override  = $this->getInfoTextOverride($psp);
        $arr['info_text']    = empty($info_text_override) ? t($this->getInfoAlias($psp)) : $info_text_override();
        $arr['display_name'] = phive('Localizer')->getPotentialStringOrAlias($arr['display_name']);
        $arr['display_psp']  = $psp;
        $arr['network']      = $this->c->getPspRoute($this->user, $psp);

        $in_out = $this->action == 'deposit' ? 'in' : 'out';
        $arr[$this->action]['min_amount'] = phiveApp(PspConfigServiceInterface::class)->getLowerLimit($psp, $in_out, $this->user);
        $arr[$this->action]['max_amount'] = phiveApp(PspConfigServiceInterface::class)->getUpperLimit($psp, $in_out, $this->user);

        // Atm used for mobile deposit logo overrides of the big logo but might be handy in other situations too.
        $logo_override_base_name = $this->getLogoOverrideConfig($psp, $arr)['logo'];
        if(!empty($logo_override_base_name)){
            $arr['logo_override_base_name'] = $logo_override_base_name;
        }

        $countries_for_ui_adjustment = $this->c->getSetting('deposit_cashier_middle_ui_adjustments')['included_countries'];
        if (isset($countries_for_ui_adjustment) && in_array($this->user->getCountry(), $countries_for_ui_adjustment)) {
            $arr['ui_adjustments_needed'] = true;

            if ($psp === 'bank') {
                $info_text = t($this->getInfoAlias($psp, $this->user->getCountry()));
                if ($info_text) {
                    $arr['info_text'] = $info_text;
                }
            }
        }
    }

    /**
     * Checks if a PSP IS a configured override.
     *
     * @param string $psp The PSP we want to check.
     *
     * @return bool True if the PSP **is** an override, false otherwise.
     */
    public function isOverride($psp){
        return in_array($psp, $this->c->getSetting('psp_overrides')[$this->action][$this->channel]);
    }

    /**
     * Gets a potential override from the psp_overrides config.
     *
     * NOTE that the override logic is handled differently on deposit and on withdraw:
     *
     * * **Deposit:** here we use the override to show the unique extra fields of the override, when
     * the user for instance selects MasterCard we instead display the ccard fields / HTML.
     *
     * * **Withdraw:** here we use the override to just override certain small things like the display name,
     * content and logo. This is because of the withdraw interface which already displays things grouped,
     * eg the ccard option / logo. Then we have the various bank alternatives which need their own unique fields
     * but at the same time override logo, content and display name (ie they're all BANK).
     *
     * @param string $psp The PSP to work with.
     *
     * @return string The ovrride PSP.
     */
    public function getOverride($psp){
        return $this->c->getSetting('psp_overrides')[$this->action][$this->channel][$psp];
    }

    /**
     * Gets all the overrides and populates them.
     *
     * At the moment this logic is used in the JS logic for deposits in order to override configs, eg VISA -> ccard.
     *
     * @see CashierBoxBase::getOverride()
     *
     * @return array The populated override configs.
     */
    public function getOverrides(){
        $ret       = [];
        $overrides = $this->c->getSetting('psp_overrides')[$this->action][$this->channel];
        foreach($overrides as $from => $to){
            $to_psp = $this->full_config[$to];
            $this->populatePsp($to_psp, $to);
            $ret[$from] = $to_psp;
        }
        return $ret;
    }

    /**
     * Populates the overrides with their config values and returns the result.
     *
     * Uses the current action (deposit / withdraw) and the current channel (mobile / desktop) in order
     * to get a 1d array of from => to overrides which are then used to populate the overrides' configs.
     *
     * @return array The full config values of all overrides.
     */
    public function getOverrideConfigs(){
        $ret       = [];
        $overrides = $this->c->getSetting('psp_overrides')[$this->action][$this->channel];
        foreach($this->psps as $psp => $config){
            $override = $overrides[$psp];
            if(empty($override)){
                continue;
            }
            $ret[$override] = $this->full_config[$override];
        }
        return $ret;
    }

    /**
     * Gets the local override section for the current PSP, note that this logic is separate from the psp_override logic.
     *
     * @param string $psp The PSP.
     *
     * @return arrry|null The config array or null in case none was found.
     */
    public function getLogoOverrideConfig($psp, $config = null){
        $config    = $config ?? $this->psps[$psp];
        $overrides = $config[$this->action]['logo_overrides'] ?? $config['logo_overrides'];

        foreach($overrides as $override){
            if(in_array($this->country, $override['countries'])){
                return $override;
                break;
            }
        }

        return null;
    }

    /**
     * Used to get a display name override from the local logo_overrides clause, note that this logic is separate from the psp_override logic.
     *
     * @param string $psp The PSP.
     *
     * @return string The display name, the override if found and if no override the standard display name.
     */
    public function getDisplayName($psp){
        $name_override = $this->getLogoOverrideConfig($psp);
        return $name_override['display_name'] ?? $this->psps[$psp]['display_name'];
    }

    /**
     * This handles unique logo overrides.
     *
     * This is primarily used for the EMP override logos such as MC -> Kwickgo.
     *
     * @param string $psp The PSP to work with.
     * @param string $version The logo version (small / large).
     *
     * @return string The logo PNG image name.
     */
    public function getLogo($psp, $version = 'small'){
        $logo_override = $this->getLogoOverrideConfig($psp);
        $logo_name     = $logo_override['logo'] ?? $psp;
        return $version == 'small' ? "$logo_name-small.png" : "$logo_name.png";
    }

    /**
     * Gets all PSP configs of a certain type, eg 'bank'.
     *
     * @param string $type The type.
     * @param bool $include_grouper Do we include the grouping / template psp if there is any, eg do we
     * include the bank PSP if we want all PSPs of type bank?
     *
     * @return array The filtered array.
     */
    public function getPspsByType($type, $include_grouper = false){
        return array_filter($this->psps, function($psp_config, $psp) use ($type, $include_grouper){
            if($psp_config['type'] != $type){
                return false;
            }

            if($psp == $type && !$include_grouper){
                return false;
            }

            return true;

        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * A wrapper around CasinoCashier::withdrawDepositAllowed().
     *
     * @uses CasinoCashier::withdrawDepositAllowed()
     *
     * @param string $psp The PSP to work with.
     *
     * @return bool True if we should show the PSP, false otherwise.
     */
    public function showAlt($psp){
        if(empty($psp)){
            return false;
        }
        return $this->c->withdrawDepositAllowed($this->user, $psp, $this->action);
    }

    /**
     * Gets the translation alias for the form field label.
     *
     * NOTE that some fields are overridden, like email, it makes not sense having separate translations for
     * rapid.email, skrill email etc.
     *
     * @param string $psp The PSP.
     * @param string $field The field name, underscores will be replaced by periods to comply more with the canonical
     * alias format.
     *
     * @return string The alias.
     */
    public function getLabelTranslation($psp, $field){
        $overrides = ['email', 'iban'];
        if(in_array($field, $overrides)){
            return $field;
        }
        return $psp.'.'.str_replace('_', '.', $field);
    }

    /**
     * Gets the field type depending on device / channel.
     *
     * @param string $type The type, eg number.
     *
     * @return string The type ie text or tel.
     */
    public function getFieldType($type){
        if($type == 'number'){
            return $this->ifMobElse('tel', 'text');
        }
        return $type;
    }


    public function getPrepopValue($psp, $field_config){
        if($field_config['prepopulate_prior'] === true){
            // We want to prioritize the previously used field
            $prepop_value = $this->prior_info[$psp];
        }

        if(!empty($field_config['populate_with']) && empty($prepop_value)){
            $prepop_value = $this->user->getAttrOrSetting($field_config['populate_with']);
        }

        if ($psp === 'muchbetter' && empty($prepop_value)) {
            $prepop_value = $this->user->getMobile();
        }

        if(empty($prepop_value)){
            $prepop_value = !empty($this->prior_info[$psp]) && !empty($field_config['prepopulate']) ? $this->prior_info[$psp] : '';
        }

        return $prepop_value;
    }

    /**
     * Outputs an extra / unique field.
     *
     * This method is responsible for generating the potentially extra fields for all the PSPs, and their labels.
     * It takes three main arguments and three optional override arguments.
     *
     * @uses \CashierBoxBase::getLabelTranslation()
     *
     * @param string $psp The PSP.
     * @param string $field The field name.
     * @param array $field_config The PSP's field config for this action (deposit or withdraw), they can be different.
     * @param string|null $prepop_value Optional value that will prepopulate the field, if this is null we will look in the
     * prior_info array for this PSP.
     * @param bool|null $disabled Optional value that will control whether or not the field should be disabled or not.
     * If set to null we look at the config's **prepopulate** setting, if it is **disable** or not. We also do **not**
     * disable if the $prepop_value is empty at this point.
     * @param string $loc_alias Is per default the empty string. If it is empty we use self::getLabelTranslation() to get it.
     *
     * @return void
     */
    public function generateExtraField($psp, $field, $field_config, $prepop_value = null, $disabled = null, $loc_alias = ''){

        if(!empty($field_config['countries']) && !in_array($this->user->getCountry(), $field_config['countries'])){
            // We have a countries array and the user is not in it so this extra field does not apply to the user.
            return null;
        }

        $validate     = $field_config['validate'];
        $validate_str = '';
        $validate_err = 'cashier.error.';
        if(is_array($validate)){
            foreach($validate as $attr => $val){
                $validate_str .= $attr.'="'.$val.'" ';
                $validate_err .= $attr;
            }
        } else {
            $validate_str = (string)$validate;
            $validate_err .= $validate;
        }

        // If we're looking at a drop down we display it and return to avoid running the standard text / number field logic.
        if($field_config['type'] == 'drop_down'){
        ?>
            <div class="cashierInputLabel">
                <?php echo empty($loc_alias) ? t($this->getLabelTranslation($psp, $field)) : t($loc_alias) ?>
            </div>
        <?php
            $extra_attrs = empty($validate_str) ? '' : $validate_str.' title="'.t($validate_err).'"';
            dbSelect($field, $field_config['values'], '', ['', t('select')], 'cashierInput', false, $extra_attrs);
            return true;
        }

        if($prepop_value === null){
            $prepop_value = $this->getPrepopValue($psp, $field_config);
        }

        if($disabled === null){
            $disabled = !empty($prepop_value) && $field_config['prepopulate'] == 'disable';
        }

        $loc_alias    = empty($loc_alias) ? $field_config['label'] : $loc_alias;
        $type         = $field_config['type'] ?? 'text';
    ?>
        <div class="cashierInputLabel"><?php echo empty($loc_alias) ? t($this->getLabelTranslation($psp, $field)) : t($loc_alias) ?></div>
        <input name="<?php echo $field ?>" value="<?php echo $prepop_value ?>" <?php echo $disabled ? 'disabled="disabled"' : '' ?> class="cashierInput" type="<?php echo $this->getFieldType($type) ?>" <?php echo $validate_str  ?> title="<?php echo !empty($validate_str) ? t($validate_err) : '' ?>"/>
    <?php
    }

    /**
     * Interface stub / placeholder to potentially avoid unnecessary control statements.
     *
     * @param string $psp The PSP.
     * @param array $config The config.
     *
     * @return void
     */
    public function generateExtraElements($psp, $config){}

    /**
     * A small helper that does something depending on channel.
     *
     * @param mixed $mobv The thing to return if channel is mobile.
     * @param mixed $normalv The thing to return if channel is desktop.
     *
     * @return mixed The return value.
     */
    public function ifMobElse($mobv, $normalv){
        if(!is_callable($mobv))
            return $this->channel == 'mobile' ? $mobv : $normalv;
        else
            return $this->channel == 'mobile' ? $mobv() : $normalv();
    }

    /**
     * Prints CSS and JS that is common to all channels and actions.
     *
     * @return void
     */
    public function printCSS(){
        echo '<script async type="text/javascript" src="https://pay.google.com/gp/p/js/pay.js"></script>';
        loadJs("/phive/js/privacy_dashboard.js");
        loadJs("/phive/js/googlepay.js");
        loadJs("/phive/js/cashier.js");
        loadJs("/phive/js/handlebars.js");
        loadJs("/phive/js/jquery.validate.min.js");
        loadJs('/phive/js/jquery.json.js');
        loadJs("/phive/modules/Licensed/Licensed.js");
        lic('loadJs');
    }

    /**
     * Helper method used to print labels, typically in the context of X::generateExtraElements()
     *
     * @param string $psp The PSP.
     * @param string $field The field name.
     *
     * @return void
     */
    function printLabel($psp, $field){ ?>
        <div class="cashierInputLabel <?php echo $field ?>-label"><?php et("$psp.$field") ?></div>
    <?php
    }

    /**
     * Base DOM printer entry point.
     *
     * Typically called in child classes, if it returns false the child logic will stop execution.
     *
     * @return bool True if all is good, false if we have an error and we want to stop outputting more DOM.
     */
    public function printHTML(){
        if(!empty($this->block_msg)){
            et($this->block_msg);
            return false;
        }
        return true;
    }

    /**
     * Generation of handlebars templates applicable to deposits in general.
     *
     * @link https://handlebarsjs.com/
     *
     * @return void
     */
    public function generateHandleBarsTemplates(){ ?>
        <script id="baseErrorHeadline" type="text/x-handlebars-template">
            <span style="font-size:18px; color:red;">
                <strong>
                    {{displayName}}: <?php et('mts.transaction_failed.error') ?>
                </strong>
            </span>
            <hr>
            <br>
        </script>
    <?php
    }


    /**
     * Called in every ChannelAction class in order to initiate the rudimentary and homegrown class inheritance logic there.
     *
     * @return void
     */
    public function setCashierJs(){ ?>
        <script>
         setTheCashier(cashier.<?php echo $this->action ?>.<?php echo $this->channel ?>);
        </script>
        <script type="text/javascript" src="/phive/js/jquery.validate.iban.js"></script>
    <?php
    }

    /**
     * Since the class hierarchy is based on the action this one is needed here in order to cater for all mobile scenarios regardless of action.
     *
     * **NOTE** that if more logic like this shows up we need a trait, **not** continue building like below.
     * However, at this point in time it feels like overkill to create a trait just for one method.
     *
     * @return void
     */
    public function printMobileTopLogos(){
        $sorted = array_reverse(phive()->sort2d($this->psps, 'display_weight'));
    ?>
        <div class="quick-dep-select">
            <?php foreach($sorted as $psp => $config): ?>
                <img id="logo-top-<?php echo $psp ?>" src="<?php fupUri($this->getLogo($config['display_psp'], 'small')) ?>" onclick="theCashier.logoClick('<?php echo $psp ?>')">
            <?php endforeach ?>
        </div>
    <?php
    }

    public function getUserData(){
        $userData = phive()->moveit(['country', 'currency', 'firstname', 'lastname'], ud($this->user));

        $userId = $this->user->userId;
        $sql = "SELECT * FROM triggers_log WHERE user_id = $userId AND trigger_name = 'RG65'";
        $rg65Sql = phive('SQL')->sh($userId)->loadAssoc($sql);
        $userData['hasRg65'] = !empty($rg65Sql);

        return $userData;
    }

    public function shouldShowTrustlyDepositPopup(): array
    {
        $trustly_deposit_config = phiveApp(PspConfigServiceInterface::class)->getPspSetting('trustly', 'deposit');
        $trustly_deposit_popup_config = phiveApp(PspConfigServiceInterface::class)->getPspSetting('paypal', 'trustly_deposit_popup');
        $user_country = $this->user->getCountry();

        return [
            'paypal' => $trustly_deposit_config['active']
                && in_array($user_country, $trustly_deposit_config['included_countries'])
                && $trustly_deposit_popup_config['active']
                && in_array($user_country, $trustly_deposit_popup_config['countries'])
                && !$this->user->getSetting('trustly_deposit_popup_shown')
        ];
    }

    /**
     * Outputs various data structures as JSON for the JS logic to consume.
     *
     * @return void
     */
    public function setPspJson(){ ?>
        cashier.psps                    = <?php echo json_encode($this->psps) ?>;
        cashier.returnInfo              = <?php echo json_encode($this->onPspReturn()) ?>;
        cashier.userData                = <?php echo json_encode($this->getUserData()) ?>;
        cashier.showTrustlyDepositPopup = <?php echo json_encode($this->shouldShowTrustlyDepositPopup()) ?>;
        cashier.isMobileApp = <?php echo json_encode(phive()->isMobileApp()) ?>;
    <?php
    }
}
?>
