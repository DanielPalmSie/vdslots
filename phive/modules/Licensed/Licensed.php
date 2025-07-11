<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 07/09/18
 * Time: 16:41
 */

use Carbon\Carbon;
use DBUserHandler\DBUserRestriction;
use Laraphive\Contracts\EventPublisher\EventPublisherInterface;
use longlang\phpkafka\Exception\ConnectionException;
use longlang\phpkafka\Exception\KafkaErrorException;
use longlang\phpkafka\Exception\SocketException;
use RgEvaluation\Factory\DynamicVariablesSupplierResolver;
use Videoslots\HistoryMessages\HistoryMessageInterface;
use Videoslots\HistoryMessages\UserStatusUpdateHistoryMessage;
use Videoslots\HistoryMessages\UserUpdateHistoryMessage;
use Videoslots\HistoryMessages\GameVersionUpdateHistoryMessage;
use Videoslots\HistoryMessages\Exceptions\InvalidMessageDataException;
use Videoslots\Menu\Boxes\MobileMenu\Element\MobileBalanceTableData;
use Videoslots\RgLimits\Builders\Locks\DefaultLock;
use Videoslots\RgLimits\Builders\Locks\LockInterface;
use Carbon\Exceptions\InvalidFormatException;

require_once __DIR__ .'/Traits/TaxTrait.php';
require_once __DIR__ . '/Traits/DocumentStatusTrait.php';
require_once __DIR__ . '/Traits/IntendedGamblingTrait.php';
require_once __DIR__ . '/../../traits/HasSitePublisherTrait.php';

class Licensed extends PhModule
{
    use TaxTrait,
        DocumentStatusTrait,
        IntendedGamblingTrait,
        HasSitePublisherTrait;

    public const FORCED_LANGUAGE = '';
    public const FORCED_PROVINCE = '';
    public const SELF_EXCLUSION_POSITIVE = 'Y';
    public const SELF_EXCLUSION_NEGATIVE = 'N';
    public const SELF_EXCLUSION_ERROR = 'ERROR';
    public const EXCLUDE_FIELDS_USERS_CHANGES_STATS = ['id' => true, 'user_id' => true];
    public const PREVENT_MULTIPLE_GAME_SESSIONS_HANDLER = 'prevent_multiple_game_sessions_handler';
    public const REGISTER_BUTTON_TYPE_EXTRA = 'extra_register';

    public const OVER_AGE_IMG = "18+W.png";

    protected const SELF_LOCK_COOL_OFF_DAYS = 7;

    /**
     * @var string
     */
    public const INTERMEDIARY_STEP_ACTION_NID_VERIFICATION = "NID_VERIFICATION";

    /**
     * @var string
     */
    public const RESPONSIBLE_GAMBLINE_ROUTE = "responsible-gaming";

    private ?RgLimits $rg = null;

    /** @var DBUserHandler|UserHandler $uh */
    public $uh;
    /** @var string $forced_country */
    public $forced_country = "";
    public string $countryIso = "";
    public string $provinceIso = "";

    public $personal_number_length;
    public string $ext_exclusion_name = '';

    /**
     * Used only for edge case scenarios in external self exclusion check
     *
     * @var array $prevent_self_exclusion_flow
     */
    public $prevent_self_exclusion_flow = [];

    /**
     * TODO get rid of the comment asap /Ricardo
     * TODO see if we can make this dynamic with a diff between registrationStep2Fields on licensed
     *  and the ISO.php class, check if we need to create a registrationStep1Fields too, considering that
     *  there are some extra fields for IT there too, so we use a more similar structure.
     *  After we do that need to rework showRegistrationExtraFields and extraRegistrationValidations to use new logic
     *  /Paolo
     */
    protected $extra_registration_fields = [
        'step1' => [],
        'step2' => [],
    ];

    /**
     * List of "extra fields" provided during the registration that we need to store for the user
     * The fields will be saved on users_settings. (Ex. see IT or DE)
     *
     * @var array
     */
    protected array $fields_to_save_into_users_settings = [];
    protected string $logger_name = 'licensed';
    protected Logger $logger;

    private static Licensed $settings_instance;

    /**
     * @var false|mixed|string|null
     */
    private $lic_country_cache;

    private $lic_country_province_cache;
    private bool $skip_domain_iso_override = false;

    protected array $user_jurisdiction_cache = [];
    protected array $iso_license_cache = [];

    protected bool $default_net_deposit_in_client_currency = false;

    public function __construct(){
        $this->uh   = phive('UserHandler');
        // To prevent endless recursion when falling back.
        $this->extv = false;
        $this->extv_fallback = false;
        $this->logger     = phive('Logger')->getLogger($this->logger_name);
        $this->logger->addIntrospectionProcessor();

    }

    /**
     * Returns the timeout on the session when idle, if not configured, by default is 7200 seconds.
     * If configured on the country setting that value will override the generic one.
     *
     * Config value has to be int and it is considered seconds.
     *
     * @return mixed
     */
    public function getSessionTimeout()
    {
        $timeout = (int)$this->getLicSetting('session_timeout');

        return (!empty($timeout) ? $timeout : (int)phive()->getSetting('sess_timeout', 7200)) * 2;
    }

    /**
     * Trigger action on freespin finished
     * Shows a popup to the user that will reload the page
     *
     * @param $user
     * @param $participation
     * @param $tab_id
     */
    public function onSessionFreespinsFinished($user, $participation, $tab_id)
    {
        if ($user instanceof DBUser) {
            phive('Localizer')->setLanguage($user->getLang());
        }

        $msg['popup'] = 'balance_session_freespins_finished';
        $msg['msg'] = t('game-session-balance.freespins-finished');

        toWs($msg, 'extgamesess' . $tab_id, $user->getId());
    }

    /**
     * Customers can't cancel withdrawals, reverse and they pass directly to the "lock" status that is flush internally
     *
     * @return bool
     */
    public function noReverseWithdrawals()
    {
        return $this->getLicSetting('no_reverse_withdrawals') === true;
    }

    /**
     * Show login redirects
     *
     * @param $lang
     * @return bool
     */
    public function loginRedirects($lang){
        if (!isLogged()) {
            return false;
        }

        $rg_configs = $this->getLicSetting('rg_info');

        if(! empty($rg_configs['popup_active']) && empty($_SESSION['lic_login_redirects'])){
            $_SESSION['lic_login_redirects'] = true;
            $url = phive()->isMobile() ? 'rg-activity' : '?rg_login_info=true';

            phive('Redirect')->to($url, $lang, true, "302 Found");
        }

        return true;
    }

    /**
     * When dark launch is enabled for a jurisdiction only test accounts can login.
     *
     * @param DBUser $user
     * @return bool
     */
    public function loginBlocked($user): bool
    {
        if (empty($this->getLicSetting('dark_launch')) && empty($this->getLicSetting('login_block'))) {
            return false;
        }

        if ($user->isTestAccount() === true) {
            return false;
        }

        return true;
    }

    /**
     * Run a set of common checks before allowing the player to play, if a check fail the user is prompted with
     * a popup (desktop) or redirected to a page (mobile) where he's requested to fill in the missing mandatory data.
     *
     * The below checks are toggleable features mostly based on:
     * - licSettings (Ex. deposit, login)
     * - OR users settings (Ex. occupation).
     *
     * @param DBUser $u_obj - user object
     * @param string $type - "flash"|"mobile"
     * @param array|null $game - Array of game data
     * @return string
     */
    public function beforePlay($u_obj, $type = 'flash', $game = null)
    {
        $url = '';
        $skip = false;

        $u_obj = cu($u_obj);
        if (empty($u_obj)) {
            return $url;
        }

        if (
            ((!empty($this->getLicSetting('gamebreak_24')) || !empty($this->getLicSetting('gamebreak_indefinite'))) || !empty($u_obj->getRgLockedGames()))
            && !empty($game)
        ) {
            if ($u_obj->isGameLocked($game['tag'])) {
                $_SESSION['locked_game_popup'] = $game['tag'];
                $url = $this->goToUrlBeforePlay($u_obj, $type, '/', $url);
            }
        }

        if (empty($_GET['rg_login_info'])) {
            $url = $this->handleRgLimitPopupRedirection($u_obj, $type, 'deposit');

            if (empty($url)) {
                $url = $this->handleRgLimitPopupRedirection($u_obj, $type, 'login');
            }

            if (empty($url)) {
                $url = $this->handleRgLimitPopupRedirection($u_obj, $type, 'occupation');
            }
        }

        return $url;
    }

    /**
     * @param DBUser $u_obj
     * @param string $device_type
     * @param string $limit_type - limit to check, for "deposit|login" if not empty, for "change-deposit-before-play
     * @return string
     */
    protected function handleRgLimitPopupRedirection($u_obj, $device_type = 'flash', $limit_type)
    {
        if(empty($u_obj)) {
            return '';
        }
        switch ($limit_type) {
            case 'occupation':
                if ($this->hasViewedResponsibleGaming($u_obj)) {
                    return '';
                }
                $desktop_url = '?show_occupation=true';
                $mobile_url = 'rg-occupation';
                break;
            /**
             * Handle common logic for "forced limit" (licSetting) in case of user without a limit set (database)
             * for all standard limit types (Ex. deposit, login)
             */
            case $this->rgLimits()->checkType($limit_type):
                // current Jurisdiction doesn't require mandatory limit
                if (empty($this->getLicSetting("{$limit_type}_limit")['popup_active'])) {
                    return '';
                }
                // Limit already exist, so no need for redirection.
                if (!empty($this->rgLimits()->getByTypeUser($u_obj, $limit_type))) {
                    return '';
                }
                $desktop_url = '?rg_login_info=' . $limit_type;
                $mobile_url = 'rg-' . $limit_type;
                break;
            // Custom logic for a special scenario (SE only)
            case 'change-deposit-before-play':
                $country_deposit_limit = $this->rgLimits()->getLicLimit($u_obj, 'deposit');
                // current Jurisdiction doesn't require mandatory limit
                if (empty($country_deposit_limit['allow_global_limit_override'])) {
                    return '';
                }
                $user_limit = $this->rgLimits()->getLimit($u_obj, 'deposit', 'week');
                // limit already below threshold.
                if ($user_limit['cur_lim'] <= $country_deposit_limit['limit']) {
                    return '';
                }
                $desktop_url = '?rg_login_info=' . $limit_type;
                $mobile_url = 'rg-' . $limit_type;
                break;
            case 'activity':
                $rg_info = $this->getLicSetting('rg_info');

                if (empty($rg_info['popup_rg_activity']) || empty($rg_info['popup_rg_activity_period'])) {
                    return '';
                }

                $tag = 'rg-activity-last-accepted';
                $edge_date = phive()->hisMod($rg_info['popup_rg_activity_period']);

                $rg_activity = phive('SQL')
                    ->sh($u_obj->getId())
                    ->loadArray("SELECT * FROM actions WHERE target = {$u_obj->getId()} AND tag = '{$tag}' AND created_at > '{$edge_date}' ORDER BY created_at DESC LIMIT 1");

                // If user accepted rg popup in period of time => do nothing
                if (! empty($rg_activity)) {
                    return '';
                }

                // redirect and show rg popup to user
                $desktop_url = '?rg_login_info=' . $limit_type;
                $mobile_url = 'rg-' . $limit_type;

                break;
            case 'gbg_verification':
                $desktop_url = $this->getVerificationModalUrl();
                $mobile_url = $this->getVerificationModalUrl(true);
                break;
            default:
                return '';
        }

        // setting current page as "return page after rg popup"
        $http = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
        $_SESSION['rg_login_info_callback'] = "$http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

        $lang = phive('Localizer')->getSiteLanguage($u_obj);

        if ($device_type == 'mobile') {
            $url = phive('Redirect')->getToUrl("/mobile/$mobile_url", $lang);
        } else {
            $url = 'stop';
            // We can't stay on the same page as the RC popup interferes with the popup so we just go to /
            phive('Redirect')->jsRedirect($desktop_url, $lang, false, [], true);
        }

        return $url;
    }

    /**
     * Provide the correct link to be redirected to after RG popup redirection.
     * @see Licensed::handleRgLimitPopupRedirection() - value is set here
     *
     * Ex.
     * - User doesn't have deposit limit
     * - click on GameA
     * - gets redirected to: (both popup and mobile page have same content)
     *   - desktop => homepage + popup
     *   - mobile => /mobile/rg-deposit
     * - after he set his limits is redirected back to GameA.
     *
     * Main logic to retrieve the correct link relies on "$_SESSION['rg_login_info_callback']"
     * this can be overridden via "$_GET['redirect_after_action']" or "$_GET['newsite_url_after_rg']" (Ex. going to mobile BoS without a limit set)
     * or ignored via "$_POST['noRedirect']" and the popup will simply close without a redirection (Ex. showDepositLimitPrompt)
     *
     * @return mixed|string
     */
    public function getRedirectBackToLinkAfterRgPopup()
    {
        $redirect_url = '';
        if (empty($_POST['noRedirect'])) {
            $redirect_url = $_GET['newsite_url_after_rg'] ?? $_GET['redirect_after_action'] ?? $_SESSION['rg_login_info_callback'];
            if (!empty($_COOKIE['redirect_after_login'])) {
                $redirect_url = $_COOKIE['redirect_after_login'];
            } elseif (empty($redirect_url)) {
                $redirect_url = phive('Casino')->getBasePath();
            }
        }
        return $redirect_url;
    }

    /**
     * @param DBUser $u_obj
     * @param $type
     * @param $base_url
     * @param $url
     * @return string
     */
    public function goToUrlBeforePlay($u_obj, $type, $base_url, &$url) {
        if($type == 'mobile'){
            $url = phive('Redirect')->getToUrl($base_url, cLang());
        } else {
            $url = 'stop';
            // We can't stay on the same page as the RC popup interferes with the popup so we just go to /
            phive('Redirect')->jsRedirect($base_url, cLang(), false, [], true);
        }
        return $url;
    }

    /**
     * Check if a specific RG section need to be showed/hidden to the player.
     * By default if the setting "rg_sections" is missing we display all sections.
     * If the $section is not set in the setting we return true.
     *
     * TODO currently works only for "lock", logic need to be applied on all sections
     *
     * @param $section
     * @return bool|mixed
     */
    public function hasRgSection($section)
    {
        $rg_sections = $this->getLicSetting('rg_sections');
        if(empty($rg_sections)) {
            return true;
        }
        return $rg_sections[$section] ?? true;
    }

    /**
     * @param DBUser $user
     * @param $section
     *
     * @deprecated
     */
    public function printExtraRgLimits($user, $section)
    {
        if (empty($section) || is_bool($section)) {
            return;
        }

        switch ($section) {
            case 'gamebreak_24':
                $this->printGamebreak24($user);
                break;
            case 'undo_withdrawals':
                $this->printUndoWithdrawals($user);
                break;
            case 'gamebreak_indefinite':
                $this->printGamebreakIndefinite($user);
                break;
            default:
        }
    }

    /**
     *
     * @return array
     */
    public function getSelfExclusionTimeOptions()
    {
        return [183, 365, 730, 1095, 1825];
    }

    /**
     * If set to true all the categories on "gamebreak 24" will be checked by default
     *
     * @return bool
     */
    public function preselectGamebreak24()
    {
        return false;
    }

    /**
     * @param DBUser $user
     *
     * @deprecated
     */
    public function printGamebreak24($user) {
        if (empty($this->getLicSetting('gamebreak_24'))) {
            return;
        }
        if (empty($user)) {
            return;
        }

        $available_categories = $this->getGamebreak24Categories();
        $locked_categories = $user->getRgLockedGames();
        ?>
        <div class="simple-box pad-stuff-ten" id="gamebreak_24">
            <div class="account-headline"><? et('spelpaus.24.headline') ?></div>
            <p><?php et('spelpaus.24.info.html') ?> </p>
            <? foreach ($available_categories as $category):
                $checked = in_array($category['alias'], $locked_categories);
                ?>
                <div style="float: left;">
                    <input type="checkbox" class="hours-24-lock-games" id="lockgamescat_<?=$category['alias']?>"
                           value="<?= $checked ? '' : $category['alias'] ?>"
                        <?= $checked || lic('preselectGamebreak24') ? 'checked' : '' ?>
                        <?= $checked ? "disabled" : "" ?>
                    >
                    <label for="lockgamescat_<?=$category['alias']?>"><?= phive('Localizer')->getPotentialString($category['name']) ?></label>
                </div>
            <? endforeach; ?>
            <div class="right">
                <br clear="all"/>
                <br clear="all"/>
                <button class="btn btn-l btn-default-l w-100" data-games="true" onclick="lockGames24Hours(1)">
                    <?php et('game-category.locked.lock') ?>
                </button>
            </div>
            <br clear="all"/>
        </div>
        <script>
            // select/unselect all the tags when clicking on "all_categories"
            $('#gamebreak_24').on('click', 'input[type="checkbox"]', function(event){
                if(event.target.value == 'all_categories') {
                    $.each($('#gamebreak_24 input[type="checkbox"]'), function(index, el){
                        if(event.target.checked) {
                            el.checked = true;
                        } else {
                            if(!el.disabled) {
                                el.checked = false;
                            }
                        }
                    });
                } else {
                    // when unselecting a single category we uncheck "all_categories"
                    if(!event.target.checked) {
                        $('#lockgamescat_all_categories')[0].checked = false;
                    }
                }
            });
        </script>
        <?
    }

    /**
     * Return the list of the available categories from the home page side menu, plus a special value "all_categories"
     * that can be used to block any game play on all categories (similar to play_block).
     *
     * @return array
     */
    public function getGamebreak24Categories()
    {
        $query = "SELECT alias, name FROM menus WHERE parent_id = 80";
        $user = cu();
        if (!empty($user)) {
            $country = $user->getCountry();
            $query = $query . " AND excluded_countries NOT LIKE '%$country%'";
        }
        $menu_categories = phive('SQL')->loadArray($query);
        $all_category = ['alias' => "all_categories", 'name' => "#gamebreak24.all.categories"];

        $game_break_categories = [$all_category, ...$menu_categories];

        if($this->isSportsbookEnabled() && !$this->isSportsbookOnMaintenance()) {
            $game_break_categories[] = ['alias' => "sportsbook", 'name' => "#menu.secondary.sportsbook"];
        }

        if ($this->isPoolxEnabled() && !$this->isPoolxOnMaintenance()) {
            $game_break_categories[] = ['alias' => "superstip", 'name' => "#menu.secondary.poolx"];
        }

        return $game_break_categories;
    }

    /**
     * Print the menu to block games categories for Indefinite period of time, and unblock with cooloff period of time
     *
     * @param DBUser $user
     *
     * @deprecated
     */
    public function printGamebreakIndefinite($user) {
        if (empty($this->getLicSetting('gamebreak_indefinite')) || empty($user)) {
            return;
        }

        /* Games categories as the same like for game block for 24 hours */
        $available_categories  = $this->getGamebreak24Categories();
        /* Get locked categories and their time period of blocking from DB */
        $locked_categories_and_period = $user->getRgLockedGamesAndPeriod();
        /* List of categories what will be unblocked after cooloff period */
        $unblocked_list = [];
        /* Names of categories what blocked for Indefinite period of time */
        $locked_categories_names = [];
        foreach ($locked_categories_and_period as $category => $period) {
            if ($period != 0) {
                /* Removing some of categories what not needed for visibility on UI */
                if (in_array($category, $user->getExtractedCategoriesFromVisibleMenu())) {
                    continue;
                }
                $unblocked_list[$category] = $period;
            } else {
                $locked_categories_names[] = $category;
            }
        }
        ?>

        <div class="simple-box pad-stuff-ten" id="gamebreak_indefinite">
            <div class="account-headline"><?php et('game-category-block-indefinite.title') ?></div>
            <p><?php et('game-category-block-indefinite.description') ?></p>
            <? foreach ($available_categories as $category):
                $checked = in_array($category['alias'], $locked_categories_names);
                ?>
                <div style="float: left;">
                    <input type="checkbox" class="indefinite-lock-games" id="lockgamescat_<?=$category['alias']?>"
                           value="<?= $category['alias'] ?>"
                        <?= $checked ? 'checked' : '' ?>
                    >
                    <label for="lockgamescat_<?=$category['alias']?>"><?= phive('Localizer')->getPotentialString($category['name']) ?></label>
                </div>
            <? endforeach; ?>
            <br clear="all"/>
            <br clear="all"/>
            <? foreach ($unblocked_list as $category => $period): ?>
                <span class="vip-color"><i><b><?php et('game-category-block-indefinite.unblock') ?>:</b></i></span>&nbsp;
                <span class="rg-limits-actions__extra-text"><i>
                        <?php echo phive('Localizer')->getPotentialString($this->getLicSetting('blockable_game_categories')[$category]) ?>&nbsp;
                        <?php echo phive()->lcDate($period, '%x %R') ?></i></span>
                <br clear="all"/>
                <br clear="all"/>
            <? endforeach; ?>
            <div class="right">
                <button class="btn btn-l btn-default-l w-100" data-games="true" onclick="lockUnlockGamesIndefinite()">
                    <?php et('save') ?>
                </button>
            </div>
            <br clear="all"/>
        </div>
        <script>
            // select/unselect all the tags when clicking on "all_categories"
            $('#gamebreak_indefinite').on('click', 'input[type="checkbox"]', function(event){
                if(event.target.value === 'all_categories') {
                    $.each($('#gamebreak_indefinite input[type="checkbox"]'), function(index, el){
                        if(event.target.checked) {
                            el.checked = true;
                        } else {
                            if(!el.disabled) {
                                el.checked = false;
                            }
                        }
                    });
                } else {
                    // when unselecting a single category we uncheck "all_categories"
                    if(!event.target.checked) {
                        $('#lockgamescat_all_categories')[0].checked = false;
                    }
                }
            });
        </script>
        <?
    }

    /**
     * Enable RG section for the player to opt in/out from the undo withdrawals feature.
     *
     * @param DBUser $user
     *
     * @deprecated
     */
    public function printUndoWithdrawals($user)
    {
        if (empty($this->getLicSetting('undo_withdrawals'))) {
            return;
        }
        $opted_in = empty($user->getSetting('undo_withdrawals_optout'));
        ?>
        <div class="simple-box pad-stuff-ten" id="undo_widthdrawals">
            <div class="account-headline"><? et('rg.undo.withdrawals.headline') ?></div>
            <p><? et('rg.undo.withdrawals.info.html') ?></p>
            <div style="text-align: center">
                <input type="radio" name="undo_withdrawals" id="undo_withdrawals-yes"
                       value="1" <?= $opted_in ? 'checked' : '' ?>>
                <label for="undo_withdrawals-yes"><?= t('rg.undo.withdrawals.opt.in') ?></label>
                <input type="radio" name="undo_withdrawals" id="undo_withdrawals-no"
                       value="0" <?= !$opted_in ? 'checked' : '' ?>>
                <label for="undo_withdrawals-no"><?= t('rg.undo.withdrawals.opt.out') ?></label>
            </div>
            <div class="right">
                <br clear="all"/>
                <br clear="all"/>
                <button class="btn btn-l btn-default-l w-100" onclick="undoWithdrawalsOptInOut()">
                    <?php et('save') ?>
                </button>
            </div>
            <br clear="all"/>
        </div>
        <?
    }

    /**
     * Filters the event feed.
     *
     * @param array $events The events.
     *
     * @return array The filtered array.
     */
    public function filterEvents($events){
        if($this->getLicSetting('filter_events') !== true){
            return $events;
        }
        return array_filter($events, function($event){
            return $event->country == $this->getIso();
        });
    }

    /**
     * For DK player we need to give them their money back when they self exclude.
     * We send an Email to payment to take care of that.
     *
     * TODO this needs to be reworked into AML51 (no email should be send to payment according to our policy).
     *  check instead if we need to send an email to the USER to tell him that we are going to pay him in XX days, or if the popup message is enough.
     */
    public function selfExcludeEmail($u_obj){
        if(empty($u_obj->getBalance())){
            return false;
        }

        if($this->getLicSetting('send_email_on_self_exclude') === true){
            $content = "A DK user Self Excluded and needs to be refunded whitin 5 days.<br> uid: {$u_obj->getId()}, balance: {$u_obj->getBalance()}.";
            phive('MailHandler2')->mailLocal($content, $content, 'payments');
            return true;
        }

        return false;
    }

    /**
     * Empty by default, or whatever in the config since cases like DE are still MGA for example for a while.
     *
     * @param string $game_network
     * @return string
     */
    public function getGameJurisdictionString($game_network)
    {
        if ($this->getLicSetting('has_game_jurisdiction_string')) {
            $jur_string = '.' . strtolower($this->getIso());
        } else {
            $jur_string = '';
        }
        return $game_network . $jur_string . '.jurisdiction';
    }

    public function getSelfExclusionConfirmMessage(bool $translate = true)
    {
        $alias = 'exclude.generic.end.info.html';

        return $translate ? t($alias) : $alias;
    }

    public function getSelfExclusionRecommendation() {
        return '';
    }

    public function handleGameVersionCommon($session_id, $ins)
    {
        try {
            $game_version = phive('SQL')->getValue("SELECT game_version FROM micro_games mg LEFT JOIN game_country_versions gcv ON gcv.game_id = mg.id WHERE gcv.country = '{$this->getIso()}' AND ext_game_name = '{$ins['game_ref']}' AND device_type_num = {$ins['device_type']};");
            if (!empty($game_version)) {
                $insert = [
                    'game_session_id' => $session_id,
                    'country' => $this->getIso(),
                    'game_version' => $game_version
                ];
                phive('SQL')->sh($ins['user_id'])->insertArray('users_game_sessions_stats', $insert);
            }
        } catch (Exception $e) {
            error_log("game-version-error", $e->getMessage());
        }
    }

    public function rgOnChangeCron($rgl)
    {
        $user = cu($rgl['user_id']);
        if (empty($user)) {
            return false;
        }
        if (!empty($user) && $user->checkCountryConfig('rg-onchange-confirm', 'countries')) {
            $rgl['old_lim'] = $rgl['cur_lim'];
            $user->setSetting('has_old_limits', 1);
        }
        return $rgl;
    }

    /**
     * @return RgLimits
     */
    public function rgLimits(): RgLimits
    {
        if (empty($this->rg)) {
            $this->rg = rgLimits();
        }

        return $this->rg;
    }

    /**
     * Will return the default settings from Licensed config based on user jurisdiction if it's specified otherwise empty array.
     * Settings in the config can be both a single value or an array, if single value that will be applied on all the "time_span".
     *
     * If popup_default_values_currency is set then it will convert values to the currency
     *
     * Ex.
     * 'deposit_limit' => [
     *     'popup_active' => true,
     *     'popup_default_values' => 999999
     * ]
     * will return  ['day' => 999999, 'week' => 999999, 'month' => 999999]
     *
     * 'login_limit' => [
     *     'popup_active' => true,
     *     'popup_default_values' => ['day' => 24, 'week' => 168, 'month' => 720]
     * ],
     * will return the array as it is ['day' => 24, 'week' => 168, 'month' => 720]
     *
     * @param DBUser $u_obj
     * @param string $type Limit name can be fex login or deposit
     * @return array|mixed
     */
    public function getDefaultLimitsByType($u_obj, $type)
    {
        $lic_defaults = [];
        $lic_default_settings = $this->getLicSetting("{$type}_limit");

        if (!empty($lic_default_settings) && !empty($lic_default_settings['popup_default_values'])) {

            $getValue = function ($default_value) use ($lic_default_settings, $u_obj) {
                $currency = $lic_default_settings['popup_default_values_currency'];
                if (!empty($currency)) {
                    return round(chg($currency, cu($u_obj), $default_value, 1));
                } else {
                    return $default_value;
                }
            };

            if (is_array($lic_default_settings['popup_default_values'])) {
                $lic_defaults = array_map($getValue, $lic_default_settings['popup_default_values']);

            } else {
                foreach ($this->rgLimits()->time_spans as $time_span) {
                    $lic_defaults[$time_span] = $getValue($lic_default_settings['popup_default_values']);
                }
            }
            foreach ($lic_defaults as &$limit) {
                $limit = $this->rgLimits()->cleanInput($type, $limit);
            }
        }
        return $lic_defaults;
    }

    /**
     * Custom jurisdictional logic that overrides the actual user input when setting up the limit
     *
     * @param DBUser $u_obj
     * @param string $type
     * @param string $time_span
     * @param mixed $requested_limit
     * @param array $lic_defaults
     * @param array $previous_limits
     * @return array Second param
     */
    public function overrideRgLimit($u_obj, $type, $time_span, $requested_limit, $lic_defaults, $previous_limits)
    {
        return [$requested_limit, false];
    }

    /**
     * Get the wager result for the rg info popup
     *
     * @param null|DBUser $u_obj
     * @return int
     */
    public function getWagerResult($u_obj = null)
    {
        $u_obj = cu($u_obj);
        $rg_info_settings = $this->getLicSetting('rg_info');
        if (empty($u_obj) || empty($rg_info_settings['popup_active'])) {
            return 0;
        }

        if (empty($rg_info_settings['active'])) {
            $start_stamp = phive()->hisMod($rg_info_settings['period']);
        } else {
            $start_stamp = max(strtotime(phive()->hisMod($rg_info_settings['period']), strtotime($rg_info_settings['cutoff_date'])));
            $start_stamp = phive()->hisNow($start_stamp);
        }

        $end_stamp = phive()->hisNow();
        $res = phive('MicroGames')->sumColsFromGameSessions($u_obj, ['result_amount'], [$start_stamp, $end_stamp]);
        return (int)$res['result_amount'];
    }

    /**
     * Return the standard cool off period when making and RG limit more liberal
     * if a $modifier is specified "day|week|month" return timestamp will be "+1 $modifier"
     * else if DEFAULT_COOLOFF is passed the cooloff period depends on the config defined on the DB per jurisdiction.
     *
     * !!! if "force_cooloff_period" licSetting exist it will override whatever is set on the DB !!!
     *
     * - ALL - getRgChangeStamp('day') -> +1 day
     * - ALL - getRgChangeStamp('week') -> +1 week
     * - ALL - getRgChangeStamp('month') -> +1 month
     * - XX - getRgChangeStamp(DEFAULT_COOLOFF) -> +configValue("license-xx", "cooloff-period") days // specific config for Jurisdiction (if exist), else ...
     * - MT - getRgChangeStamp(DEFAULT_COOLOFF) -> +configValue("rg-limits", "cooloff-period") days // ... fallback to default config
     * - IT - getRgChangeStamp(DEFAULT_COOLOFF) && getLicSetting('force_cooloff_period') -> +getLicSetting('force_cooloff_period') days // forced by setting
     *
     * @param $modifier - DEFAULT_COOLOFF|day|week|month
     * @param null $type - not used for standard scenarios (SE only for now)
     * @param string $iso - needed for not logged in context (Ex. Admin2 or CRON)
     * @return mixed
     */
    public function getRgChangeStamp($modifier, $type = null, $iso = null)
    {
        if($modifier !== RgLimits::DEFAULT_COOLOFF) {
            return phive()->hisMod("+1 $modifier");
        }

        $num_of_days = $this->getCooloffPeriod($iso);

        return phive()->hisMod("+{$num_of_days} days");
    }

    /**
     * Return number of days for cooloff period set in the DB.
     * This will return in order of priority:
     * - force_cooloff_period (licSetting)
     * - cooloff-period by country (config tag "license-xx")
     * - cooloff-period global (config tag "rg-limits")
     *
     * @param $iso
     * @return false|mixed
     */
    public function getCooloffPeriod($iso)
    {
        $num_of_days = $this->getLicSetting('force_cooloff_period');

        if(empty($num_of_days)) {
            $num_of_days = $this->getConfigByJurisdiction('rg-limits', 'cooloff-period', $iso, 3);
        }

        return $num_of_days;
    }

    public function imgUri($fname)
    {
        $dir = "/diamondbet/images/" . brandedCss();

        $licImage = "{$dir}license/{$this->getLicIso()}/$fname";
        $default = "{$dir}LicDefault/$fname";
        return !file_exists(__DIR__ . "/../../..$licImage") ? $default : $licImage;
    }

    public function showCountryFieldOnRegistration(): bool
    {
        return (bool) $this->getLicSetting('show_country_field_on_registration');
    }

    public function shouldOptOutMarketingOnTermsRejection(): bool
    {
        return (bool) $this->getLicSetting('opt_out_marketing_on_terms_rejection');
    }

    /**
     * Determine if we need to show licensing strip on current site (phive/diamondbet):
     * everywhere on both desktop and mobile
     *
     * @return bool
     */
    public function showTopLogos()
    {
        return !p('admin_top') && $this->getLicSetting('rg-buttons');
    }

    /**
     * -- Applies to BOS only --
     * Show licensing strip in lobby
     *
     * @return bool
     */
    public function showBosLicensingStripInLobby()
    {
        return $this->showTopLogos();
    }

    /**
     * -- Applies to BOS only --
     * Show licensing strip in game mode
     *
     * @return bool
     */
    public function showBosLicensingStripInGame()
    {
        return $this->showTopLogos();
    }

    public function doDisplayLoggedTime(){
        return isLogged() && $this->getLicSetting('rg-buttons') && $this->getLicSetting('rg-timer');
    }

    /**
     * This function is overridden in the child classes, and will contain, for the logged in players, the number of seconds from:
     *  - the login for SE, DK players
     *  - the start of the game for GB player
     *  - ROW false
     * for logged out players instead it will return:
     *  - the current time for DK player, only on mobile.
     *  - ROW false
     *
     * When false is returned nothing will be triggered, otherwise we display a clock in the licensing strip.
     *
     * @param DBUser $user
     * @return bool
     */
    public function rcElapsedTime($user = null)
    {
        return false;
    }

    /**
     * Return current session elapsed time in seconds
     * Used for licensing strip clocks & RC
     *
     * @param DBUser $user
     * @return int
     */
    protected function getSessionLength($user)
    {
        return (int)$user->getSessionLength('s', 2);
    }

    /**
     * Check the mobile length(Used for GB players,gamstop doesn't allow more the 14 numbers)
     *
     * @param $prefix
     * @param $mobile
     * @return bool
     */
    public function isMobileLengthCorrect($mobile, $prefix)
    {
        return true;
    }

    /**
     * Return elapsed seconds (start time) for RG clock
     * if false is disabled
     *
     * @return bool|int
     */
    public function rgClockTime($user)
    {
        return $this->rcElapsedTime($user);
    }

    /**
     * This function will setup the timer for the licensing strips
     */
    public function rgFullClock()
    {
        $user = cu();
        $seconds = $this->rgClockTime($user);
        if($seconds === false) {
            return;
        }

        if($seconds === 'simple_clock') {
            digitalFullClock();
            ?>
            <script>
                setupFullClock('client_date');
            </script>
            <?php
        } else {
            $hours = floor($seconds / 3600);
            $mins = floor($seconds / 60 % 60);
            $secs = floor($seconds % 60);

            $hours = ($hours >= 10 ? ''.$hours : '0'.$hours).":" ;
            $mins = ($mins >= 10 ? ''.$mins : '0'.$mins).":" ;
            $secs = $secs >= 10 ? ''.$secs: '0'.$secs ;

            digitalFullClock("",$hours,$mins,$secs);
            ?>
            <script>
                setupFullClock();
            </script>
            <?php
        }
    }

    public function rgLoginTime($class)
    {
        if (!$this->doDisplayLoggedTime()) {
            return;
        }
        ?>
        <div class="<?= $class ?>" >
            <div class="logged-in-time__icon">
                <span class="icon icon-vs-clock-closed"></span>
            </div>
            <div class="logged-in-time__time">
                <?= $this->rgFullClock()?>
            </div>
        </div>
        <?php
    }

    public function isUnderAge($u_obj, $nid = null){
        if(!empty($nid) && method_exists($this, 'nidToDob')) {
            $dob = $this->nidToDob($nid);
        } else {
            $dob = $u_obj->getAttr('dob');
        }
        $age_thold = phive('SQL')->getValue('', 'reg_age', 'bank_countries', ['iso' => $this->getIso()]);
        return $age_thold > phive()->subtractTimes(phive()->hisNow(), $dob.' 00:00:00', 'y', false);
    }

    /**
     * Handle all logic to be executed when registration has finished.
     * This can be overridden by specific jurisdiction (Ex. DK, SE)
     *
     * @param DBUser $u_obj
     */
    public function onRegistrationEnd(DBUser $u_obj)
    {
        phive('UserHandler')->logAction($u_obj, 'createEmptyDocuments on onRegistrationEnd', 'creating_documents');
        $this->createEmptyDocuments($u_obj);
        $this->setIntendedGamblingEligibility($u_obj);
    }

    /**
     * Generate all the standard documents for a customer, the docs will be in "requested" status.
     * If $to_create is passed, we can generate just a subset of the docs.
     *
     * @param DBUser $u_obj
     * @param string[] $to_create - which document we need to create
     */
    public function createEmptyDocuments(DBUser $u_obj, $to_create = ['idcard-pic', 'addresspic', 'bankpic'])
    {
        // Create empty documents in dmapi for ID, Address and Bank
        if (phive('UserHandler')->getSetting('create_requested_documents')) {
            $userId = $u_obj->getId();

            if (in_array('idcard-pic', $to_create)) {
                // Override for "idcard-pic" document for jurisdiction with special cases (Ex. IT)
                $created_documents = lic('createEmptyDocument', [$u_obj], $u_obj);
                if (empty($created_documents)) {
                    phive('UserHandler')->logAction($userId, 'DMAPI createEmptyDocument - idcard-pic', 'creating_documents');
                    phive('Dmapi')->createEmptyDocument($userId, 'idcard-pic');
                }
            }
            if (in_array('addresspic', $to_create)) {
                phive('UserHandler')->logAction($userId, 'DMAPI createEmptyDocument - addresspic', 'creating_documents');
                phive('Dmapi')->createEmptyDocument($userId, 'addresspic');
            }
            if (in_array('bankpic', $to_create)) {
                phive('UserHandler')->logAction($userId, 'DMAPI createEmptyDocument - bankpic', 'creating_documents');
                phive('Dmapi')->createEmptyDocument($userId, 'bankpic');
            }
            $source_of_income_required_countries = array_filter(explode(" ", phive('Config')->getValue('documents', 'source-of-income-countries', '')));
            if (in_array($u_obj->getCountry(), $source_of_income_required_countries)) {
                phive('UserHandler')->logAction($userId, 'DMAPI createEmptyDocument - sourceofincome', 'creating_documents');
                phive('Dmapi')->createEmptyDocument($userId, 'sourceofincome');
            }
        }
    }

    /**
     * Create POI & POA "approved" documents (+ empty bank) and mark the customer as verified.
     * Even if no file is uploaded we can mark the docs as approved as it's the result of an external verification.
     *
     * @param DBUser $u_obj
     * @param bool $verify
     * @param bool $empty_docs
     */
    public function createApprovedDocumentsAndVerify(DBUser $u_obj, $verify = true, $empty_docs = true)
    {
        if ($verify) {
            $u_obj->verify();
        }

        phive('Dmapi')->createEmptyDocument($u_obj->getId(), 'idcard-pic', '', '', '', 0, [], 'approved');
        phive('Dmapi')->createEmptyDocument($u_obj->getId(), 'addresspic', '', '', '', 0, [], 'approved');
        if ($empty_docs) {
            $this->createEmptyDocuments($u_obj, ['bankpic']);
        }
    }

    /**
     * Do similarity matches with external register and returns true if all is good
     *
     * @param DBUser $user
     * @param bool $strict If false we try with levenshtein distance
     * @return bool True if all is good
     */
    public function checkSimilarityWithExternalData($user, $strict = true)
    {
        $res = [];
        $ldist   = phive('Config')->getValue('ext_verification', 'name_ldistance', 3);

        $raw_nid_data = json_decode($user->getSetting('nid_data'),true);
        $person_data = $raw_nid_data['Person'];
        if (empty($person_data) || $person_data['PersonStatus'] != 'Active') {
            $status = !empty($person_data) ? " Status: {$person_data['PersonStatus']}" : '';
            phive('UserHandler')->logAction($user, "External provider data missing.{$status}", 'personal-data-mismatch');
            return false;
        }

        $nid_data = $this->getPersonLookupHandler()->mapLookupData($raw_nid_data);

        foreach (['address', 'zipcode', 'city', 'firstname', 'lastname', 'dob'] as $to_check) {
            if (empty($to_check) || mb_strtolower($nid_data[$to_check]) != mb_strtolower($user->getAttr($to_check))) {
                if ($strict === false && $to_check !== 'dob') {
                    if (levenshtein($nid_data[$to_check], $user->getAttr($to_check)) > $ldist){
                        $res[] = $to_check;
                    }
                } else {
                    $res[] = $to_check;
                }
            }
        }
        if (!empty($res)) {
            phive('UserHandler')->logAction($user, "External provider data mismatch on: ". implode(',', $res), 'personal-data-mismatch');
            return false;
        } else {
            return true;
        }
    }

    public function needsNid($user): bool
    {
        return false;
    }

    public function isNeedRequiredForRegistration(): bool
    {
        return $this->getLicSetting('is_nid_required_for_registration') === true;
    }

    public function validateRegFields(){
        return [];
    }

    public function isTest(){
        return $this->getLicSetting('test') === true;
    }

    // TODO this almost identical to stuff in ZignSec, perhaps we can use a mixin? /Henrik
    public function success($result) {
        return ['success' => true, 'result' => $result];
    }

    public function fail($result) {
        return ['success' => false, 'result' => $result];
    }

    public function jsonFail($result) {
        return json_encode(['success' => false, 'result' => $result]);
    }

    public function canGetReloadBonus($b, $u_obj){
        return true;
    }

    /**
     * Controls who can see or not multiview play
     *
     * @return bool
     */
    public function hasMultiViewPlay()
    {
        if (!empty($this->getLicSetting('disable_multiview_play'))) {
            return false;
        }
        return phive('BoxHandler')->getSetting('multiview');
    }

    /**
     * Prevent access to gameplay if the user has not set his occupation and accepted responsible gaming informative.
     * Managed by config `show_employment_popup`
     *
     * @param DBUser|null $user
     * @return bool
     */
    public function hasViewedResponsibleGaming($user): bool
    {
        if (in_array($user->data['country'], $this->getLicSetting('employment_popup_excluded_countries'))) {
            return true;
        }

        if (empty($this->getLicSetting('show_employment_popup'))) {
            return true;
        }

        if($user->getSetting('viewed-resp-gaming') == 1 && rgLimits()->hasLimits($user, 'loss')) {
            return true;
        }

        if(in_array($user->data['country'], ['GB', 'ES'])) {
            return false;
        }

        return $this->hasGameSessions($user);
    }


    public function hasViewedOccupationPopup($user): bool
    {
        return true;
    }

    /**
     * @return \Videoslots\RgLimits\Builders\Locks\LockInterface
     */
    public function createLockBuilder(): LockInterface
    {
        return new DefaultLock();
    }

    /**
     * Check if user has game sessions
     *
     * @param DBUser|null $user
     * @return bool
     */
    private function hasGameSessions($user): bool {
        return !empty(phive('SQL')->sh($user->userId)->getValue("SELECT count(user_id) FROM users_game_sessions WHERE user_id = {$user->userId}"));
    }

    public function extVerificationSupplier($fallback = false){
        // We check this one first in case we have already fallen back so we don't do unnecessary calls to the verifier that is down.
        if(!empty($_SESSION['external_verifier_fallback'])){
            $this->extv_fallback = true;
            return phive($_SESSION['external_verifier_fallback']);
        }

        $supplier = licSetting($fallback ? 'ext_verification_supplier_fallback' : 'ext_verification_supplier');

        if(empty($supplier)){
            return false;
        }
        if($fallback){
            $this->extv_fallback = true;
            $_SESSION['external_verifier_fallback'] = $supplier;
        }
        return phive($supplier);
    }

    public function setVerificationFallback(){
        $fallback = $this->extVerificationSupplier(true);
        if(!is_object($fallback)){
            return false;
        }
        //print_r($fallback);
        $this->extv = $fallback;
        return true;
    }

    public function ajaxKeepRgLimits(){
        rgLimits()->rejectOldLimits(cuPl());
    }

    public function ajaxRevertToOldLimits(){
        rgLimits()->revertToOldLimits(cuPl());
    }

    public function ajaxPollLoginVerification($post){
        $res = phMget($post['id'].'.result');
        $this->extv = $this->extVerificationSupplier();
        if($this->extv->force_polling){
            if (!empty($res)) {
                return $res;
            }
            $res = $this->extv->getExtvIdResult($post['id'], $this);
            if(!$res['success']){
                return $res;
            }

            // The result from getExtvIdResult() must be compatible with what the onExtVerificationCallback() method needs.
            return $this->onExtVerificationCallback($res['result']);
        }

        if(empty($res)){
            return $this->fail('waiting_for_result');
        }
        trackRegistration($res, "ajaxPollLoginVerification_dataFromRedis");

        // we received user data so we can disable step2 fields
        $_SESSION['ext_normal_user'] = true;
        return $res;
    }

    public function getCachedExtVerResult($req_id = '', $key = '', $u_obj = null){
        $req_id = empty($req_id) ? $_SESSION['cur_req_id'] : $req_id;
        $res    = [];
        if(empty($req_id)){
            $u_obj = $u_obj ?? cuPl();
            if(!empty($u_obj)){
                // We're looking at a player who is looking to  continue an aborted registration.
                $res['result']['lookup_res'] = $u_obj->getJsonSetting('reg_data');
                $_SESSION['ext_normal_user'] = true;
            }
        } else {
            $res = phMgetArr($req_id.'.result');
            $_SESSION['ext_normal_user'] = true;
        }

        if(empty($key)){
            return $res;
        }

        return $res['result']['lookup_res'][$key];
    }

    public function ajaxStartLoginVerification($post){
        $this->forceCountry($post['country']);
        $this->extv = $this->extVerificationSupplier();
        $sessionId = $post['session_id'] ?? session_id();
        $redis_key = $sessionId . '-nid-verification-uid';
        $uid       = phMget($redis_key);
        if(!empty($uid)){
            // If the phMget() call returns non-null we're looking at a player without NID that has to disclose and verify his NID (ie the 'verification' context).
            phive('DBUserHandler')->logAction($uid, "tried personal number {$post['nid']}", 'bankid', true, $uid);
        }

        $start_data = ['sid' => $sessionId, 'uid' => $uid, 'context' => $post['context']];

        if(!empty($uid)){
            phMdel($redis_key);
        }

        if($this->isTest()){
            $res = [
                'success' => true,
                'result' => [
                    'autoStartToken' => 'abc123',
                    'id'             => 'abc123',
                    'orderRef'       => 'abc123'
                ]
            ];
        } else {
            $res = $this->extv->extvIdStart($this->getIso(), $post['nid'], null, 'auth', $post['is_api']);

            if(!$res['success']){

                // We check if we already have fallen back first to prevent endless recursion.
                if(!$this->extv_fallback && $this->setVerificationFallback()){
                    return $this->ajaxStartLoginVerification($post);
                }
            }
        }

        $req_id = $res['result']['id'];
        // We make use of the returned request id by using it both as a key for Redis data and we also store it in the session.
        phMsetArr($req_id.'.start', $start_data);

        $_SESSION['cur_req_id'] = $req_id;
        $res['mobile']          = phive()->isMobile();
        $res['start_app_text']  = t('bankid.app.start');
        return $res;
    }

    public function getNid($u = null){
        $u = cu($u);
        if(empty($u)){
            return '';
        }
        return $u->getNid();
    }

    /**
     *
* @param $only_path
* @return string[]|void
     */
    public function loadJs($only_path = false)
    {
        $iso = $this->getIso();
        $province_file = "$iso/js/{$this->provinceIso}.js";
        $iso_file = "$iso/js/$iso.js";

        $files = [];

        if (file_exists(__DIR__ . '/' . $iso_file)) {
            $files[] = $iso_file;
        }
        // order is important here, we want to load the province file last
        if (!empty($this->provinceIso) && file_exists(__DIR__ . '/' . $province_file)) {
            $files[] = $province_file;
        }

        if ($only_path) {
            return array_map(function ($file) {
                return getFileWithCacheBuster("/phive/modules/Licensed/$file");
            }, $files);
        }

        foreach ($files as $file) {
            loadJs("/phive/modules/Licensed/$file");
        }
    }

    /**
     * Load country specific css
     */
    public function loadCss()
    {
        $device = phive()->isMobile() ? 'mobile' : 'desktop';
        $file = $this->getIso() . "/css/$device.css";
        if (file_exists(__DIR__ . '/' . $file)) {
            loadCss("/phive/modules/Licensed/$file");
        }
    }

    public function getStartLoginJsFunc(){
        return 'doLoginLic';
    }

    public function hasExtVerification(){
        return $this->getLicSetting('has_ext_verification') === true;
    }

    /**
     * Verifies if OSR (One-step registration) is enabled
     * @return bool
     */
    public function oneStepRegistrationEnabled():bool {
        return $this->getLicSetting('one_step_registration') === true;
    }

    /**
     * @return bool
     */
    public function isInactivityFeeEnabled(): bool
    {
        return $this->getLicSetting('is_inactivity_fee_enabled') === true;
    }

    /**
     * Check if sms/email verification is required
     *
     * @return bool
     */
    public function verifyCommunicationChannel(): bool
    {
        $noExtVerification = !$this->hasExtVerification();
        $verifyCommunicationChannel = $this->getLicSetting('verify_communication_channel') === true;
        $oneStepRegistration = $this->oneStepRegistrationEnabled();

        return $noExtVerification || $verifyCommunicationChannel || $oneStepRegistration;
    }

    public function extVerify($u, $nid = ''){
        $nid = empty($nid) ? $this->getCachedExtVerResult()['result']['lookup_res']['nid'] : $nid;
        if(empty($nid)){
            return [false];
        }
        $res = $u->setNid($nid);
        if(!$res){
            $this->uh->addBlock($u, 10);
            return [false, "nid.already.taken"];
        }

        $u->setSetting('verified-nid', 1);

        if ($this->getLicSetting('has_ext_verification_documents_approval')) {
            phive('UserHandler')->logAction($u, 'creating approved documents on extVerify', 'creating_documents');

            $this->createApprovedDocumentsAndVerify($u);
        } else {
            phive('UserHandler')->logAction($u, 'creating empty documents on extVerify', 'creating_documents');

            $this->createEmptyDocuments($u);
        }

        if($this->isUnderAge($u, $nid)){
            $this->uh->addBlock($u, 14);
            return [false];
        }

        return [true];
    }

    public function onExtVerificationCallback($req){
        $nid        = phive()->rmNonNums($req['nid']);
        $country    = $req['country'];
        $start_info = phMgetArr($req['req_id'].'.start');
        $sid        = $start_info['sid'];
        $context    = $start_info['context'];
        $this->extv = $this->extVerificationSupplier();

        phive('Logger')->info('onExtVerificationCallback-request', [$req]);
        phive('Logger')->info('onExtVerificationCallback-start-data', [$start_info]);

        // We don't need the Redis info anymore
        phMdel($req['req_id'].'.start');

        $u = null;
        if (!empty($start_info['uid'])) {
            $u       = cu($start_info['uid']);
            $context = 'verification';
        } elseif (!empty($nid)) {
            $u       = $this->uh->getUserByNid($nid, $country);
        }

        $err = '';
        if(empty($u) && $context != 'registration'){
            $err = 'no.user.found.bankid';
        } else {
            phive('Localizer')->setLanguage(cuAttr('preferred_lang', $u));

            // Context:
            // Login: NID exists already and we log the player in.
            // Registration: NID does not exist already so we connect the NID to the reg process.
            // Verification: player exists already but without NID, we connect the NID to the player.
            switch($context){

                case 'registration':
                    // We try to prepopulate with more data from external registry.
                    // If we can't find enough info the player will have to add in step2.
                    $tmp = $this->extv->getExtData($country, $nid, $req['req_id']);
                    trackRegistration($tmp, "onExtVerificationCallback_dataFromApi");
                    // We fix the names.
                    foreach(['firstname', 'lastname'] as $key){
                        $req[$key] = ucfirst(strtolower($req[$key]));
                    }
                    $lookup_res = empty($tmp) ? $req : array_merge($tmp, $req);
                    trackRegistration($lookup_res, "onExtVerificationCallback_mergedReqAndApiData");

                    if(!empty($u) && $u->hasSetting('registration_end_date')){
                        if (!empty($nid) && empty($u->data['nid'])) {
                            $u->setAttribute('nid', $nid, false, "External Verification Callback");
                        }

                        phive('Logger')->info('onExtVerificationCallback-user', [$u->data['id']]);

                        // User already exists, we might be looking at an unfinished reg process, so we log the player in.
                        $res = ['msg' => 'login.success', 'context' => 'login', 'login_token' => $this->uh->createLoginToken($u)];
                        // And we store the lookup result for prepopulating step 2.
                        $u->setJsonSetting('reg_data', $lookup_res);
                    }
                    break;

                case 'verification':
                    $ldist   = phive('Config')->getValue('ext_verification', 'name_ldistance', 3);
                    [$nid_res] = $this->extVerify($u, $nid);

                    if(!$nid_res){
                        // We've got a duplicate NID!
                        $err = 'nid.already.taken';
                    }else{
                        if(levenshtein(strtoupper($u->getFullName()), $req['fullname']) > $ldist){
                            $old_name = $u->getFullName();
                            $u->setAttr('firstname', $req['firstname']);
                            $u->setAttr('lastname', $req['lastname']);
                            $res = ['msg' => 'name.mismatch'];
                        }

                        if($u->getAttr('dob') != $req['dob']){
                            $old_dob = $u->getAttr('dob');
                            $u->setAttr('dob', $req['dob']);
                            $res = ['msg' => 'dob.mismatch'];
                        }

                        if(empty($res)){
                            $res = ['msg' => 'verification.successful'];
                        }

                        // We delete the legacy NID to prevent further prepopulation with it.
                        $u->deleteSetting('nid');

                        $res['login_token'] = $this->uh->createLoginToken($u);
                    }
                    break;

                case 'login':
                    $u->setSetting('verified-nid', 1);

                    $u->deleteSetting('failed_logins');
                    $u->deleteSetting('failed_login_otp_attempts');
                    $u->deleteSetting('failed_login_captcha_attempts');

                    $res = ['msg' => 'login.success', 'login_token' => $this->uh->createLoginToken($u)];
                    break;

                default:
                    $err = 'no.supported.context';
                    break;
            }
        }

        if(!empty($err)){
            $res = $this->fail(t($err));
        } else {
            $res['msg']        = t($res['msg']);
            $res['context']    = $res['context'] ?? $context;
            $res['lookup_res'] = $lookup_res;
            $_SESSION['ext_normal_user'] = true;
            $res               = $this->success($res);
        }

        phMsetArr($req['req_id'].'.result', $res);

        toWs($res, 'nid_verification', $sid);
        phive('Logger')->info('onExtVerificationCallback-Response', [$res]);

        return $res;
    }

    /**
     * Detect if user has external self exclusion
     *  We're checking for edge cases defined in prevent_self_exclusion_flow
     *  For example in GB the case Y to N should not be possible according to @paolo
     *   So we have prevent_self_exclusion_flow = ['Y' => 'N']
     *
     * @param $user
     * @param string $gamstop_res - value in ['', 'Y', 'N', 'P'], to be used on batch checks
     *
     * @return bool
     */
    public function hasExternalSelfExclusionCommon($user, string $gamstop_res = ''): bool
    {
        /** @var DBUser $user */
        $user = cu($user);
        if (empty($user)) { //In case session/customer does not exists we just assume the same as if GamStop failed
            return true;
        }

        if ($user->isTestAccount()) {
            return false; //Test account we don't check anything
        }

        if (empty($gamstop_res)) {
            $gamstop_res = $this->checkGamStop($user);
        }

        if ($this->getLicSetting('gamstop')['is_active'] !== true) {
            //Deactivated so we don't do anything else
            return false;
        }

        if (!in_array($gamstop_res, [self::SELF_EXCLUSION_POSITIVE, self::SELF_EXCLUSION_NEGATIVE, 'P'])) {
            if ($gamstop_res === 'D') {
                $this->uh->logAction($user, $this->ext_exclusion_name.' check is disabled.', $this->ext_exclusion_name.'-update', true);

                return false;
            }

            $this->uh->logAction($user, $this->ext_exclusion_name." failed. {$gamstop_res}", $this->ext_exclusion_name.'-update', true);

            return true;
        }

        $cur_ext_exclusion = $user->getSetting('cur-'.$this->ext_exclusion_name);

        foreach ($this->prevent_self_exclusion_flow as $from => $to) {
            if ($cur_ext_exclusion === $from && $gamstop_res === $to) {
                $response = "Invalid operation when previously user was $from and now the respone is $to";
                $this->logExternal('gamstop-invalid-operation', [], [$response], microtime(true), 200, 0, 0, $user->getId());

                return true;
            }
        }

        $user->setSetting("last-{$this->ext_exclusion_name}-check", phive()->hisNow());
        $user->setSetting('cur-'.$this->ext_exclusion_name, $gamstop_res);

        if (!empty($cur_ext_exclusion) && $cur_ext_exclusion !== $gamstop_res) {
            $user->setSetting('pre-'.$this->ext_exclusion_name, $cur_ext_exclusion);
            $this->uh->logAction($user, $this->ext_exclusion_name." check changed from {$cur_ext_exclusion} to {$gamstop_res}", $this->ext_exclusion_name."-update", true);
        }

        if ($gamstop_res === self::SELF_EXCLUSION_POSITIVE) { //User is externally self excluded
            if (!$this->uh->isSelfExcluded($user) && !$this->uh->isExternalSelfExcluded($user)) {
                //User not previously self excluded in any form in our system, we mark as externally self excluded
                $this->uh->externalSelfExclude($user);
            }

            return true;
        } elseif (in_array($gamstop_res, [self::SELF_EXCLUSION_NEGATIVE, 'P']) && $this->uh->isExternalSelfExcluded($user)) {
            //User was externally self excluded in our system and now it is not externally self excluded
            $this->uh->removeExternalSelfExclusion($user);
        }

        return false;
    }

    /**
     * Check if self excluded in another brand
     * @param DBUser|int $user
     * @param bool $check_local
     * @return array
     */
    public function hasInternalSelfExclusion($user, bool $check_local = true)
    {
        if (empty($this->getLicSetting('cross_brand')['check_self_exclusion'])) {
            return [false, false];
        }

        $user = cu($user);

        if (empty($remote_user_ids = linker()->getUserRemoteId($user, true))) {
            return [false, false];
        }

        $remote = getRemote();

        $response = toRemote($remote,
            'checkSelfExclusion',
            [$remote_user_ids],
            2
        );

        if(!empty($response['success'])) {
            if ($response['result']['status'] == 'Y') {
                $res = true;
                if ($check_local && !$this->uh->isSelfExcluded($user)) {
                    $this->uh->selfExclude($user, 1, !empty($response['result']['indefinitely-self-excluded']), $response['result']['unexclude-date']);
                }
            } else {
                $res = false;
                if ($response['result']['status'] == 'P') {
                    if (!$user->hasSetting('unexcluded-date')) {
                        $user->setSetting('unexcluded-date', $response['result']['unexcluded-date']);
                        $user->setSetting('excluded-date', $response['result']['excluded-date']);
                    }
                    if (empty($response['result']['active']) && !$user->isBlocked()) {
                        $this->uh->addBlock($user, 4);
                        $res = true;
                    }
                }
            }
            $this->uh->logAction($user, "Internal self exclusion check from {$remote} resulted in {$response['result']['status']}", "brand-self-exclusion");
            return [$res, $response['result']['status']];
        } else {
            $this->uh->logAction($user, "Internal self exclusion check failed due to no response from {$remote}", "brand-self-exclusion");
            return [false, 'E'];
        }

    }

    /**
     * Check if self locked in another brand
     *
     * @param DBUser|int $user
     * @return array
     */
    public function hasInternalSelfLock($user)
    {
        $check_self_locks = $this->getLicSetting('cross_brand')['check_self_lock'];
        $user = cu($user);
        $remote_user_ids = linker()->getUserRemoteId($user, true);
        if (empty($check_self_locks) || empty($user) || empty($remote_user_ids)) {
            return [false, false];
        }

        $remote = getRemote();
        $response = toRemote(
            $remote,
            'checkSelfLock',
            [$remote_user_ids],
            2
        );

        if(!empty($response['success'])) {
            if ($response['result']['status'] == 'Y') {
                $res = true;
                if (!$user->isSelfLocked()) {
                    $lock_hours = $response['result']['lock-hours'];
                    $lock_date = $response['result']['lock-date'];
                    $unlock_date = $response['result']['unlock-date'];

                    phive('DBUserHandler/RgLimitsActions')->setUserObject($user)->addSelfLockFromRemoteBrand(
                        $user,
                        $lock_hours,
                        $lock_date,
                        $unlock_date
                    );
                } else {
                    $this->uh->logAction(
                        $user,
                        "User is locked on {$remote} - already locked on local",
                        "brand-self-lock"
                    );
                }
            } else {
                $res = false;
            }
            $status = $response['result']['status'];
            $this->uh->logAction(
                $user,
                "Internal self lock check from {$remote} resulted in {$status}",
                "brand-self-lock");
            return [$res, $status];
        } else {
            $this->uh->logAction(
                $user,
                "Internal self lock check failed due to no response from {$remote}",
                "brand-self-lock"
            );
            return [false, 'E'];
        }
    }

    /**
     * To check if we should show the cross brand limits application checkbox
     *
     * @param string $type Limit type i.e. deposit
     * @param DBUser|mixed $user
     * @return bool True if we show it
     */
    public function showCrossBrandLimitExtra($type, $user)
    {
        if (!in_array($type, $this->getLicSetting('cross_brand')['apply_limit_to_all'])) {
            return false;
        }

        return !empty(linker()->getUserRemoteId($user));
    }

    /**
     * Will run a DOB check on all the providers sets on "['kyc_suppliers']['config']['dob_order']".
     * As soon as we get 1 verified result we exit the loop and mark the user as age verified.
     * Upon starting this verification "deposit_block" is set on the user and it will not be removed until we get a verified reply.
     * If all the provider fails then we add "experian_block" too to the user.
     *
     * This replaces the old function phive()->pexec('UserHandler', 'checkKycDob', array($user->getId()));
     * TODO logic here needs to be forked
     *
     * @param null $user
     * @param null|array $suppliers They can be selected by Jurisdictional order, so for example GB might want supplier A and B, then SE only supplier B // TODO add support for this /Paolo
     * @param bool $force - if set to TRUE it will do the the check even if the user already did it.
     *
     * @return bool
     */
    public function checkKycDobCommon($user = null, $suppliers = null, $force = false)
    {
        $user = cu($user);

        // Check to prevent calls to external API when user is blocked or already verified.
        if ($user->hasSetting('experian_block') || ($user->hasDoneAgeVerification() && !$force)) {
            return true;
        }

        $user->setSetting('tmp_deposit_block', 1); //We prevent deposits until the fork is done

        $suppliers = $suppliers ?? phive('Licensed')->getSetting('kyc_suppliers')['config']['dob_order'];

        $ext_kyc_module = $this->getExternalKyc();
        $ext_kyc_module->resetFailureCheck();

        // If no suppliers are provided we behave like it was a success
        if(empty($suppliers)) {
            phive('Logger')->getLogger('payments')->info('Track tmp_deposit_block: no suppliers are provided',
                [
                    'user_id' => $user->getId(),
                    'url' => $_SERVER['REQUEST_URI'] ?? null,
                    'file' => __METHOD__ . '::' . __LINE__,
                ]
            );

            $ext_kyc_module->preventFailLogAndBlock();
            $user->deleteSetting('tmp_deposit_block');
            $user->deleteSetting('experian_block');
        }

        foreach ($suppliers as $supplier) {
            /** @var Experian|ID3 $module */
            $module = phive("DBUserHandler/{$supplier}");
            if(empty($module) || !method_exists($module, 'checkDob')) {
                continue;
            }
            $result_code = $module->checkDob($user);
            // If not explicitly returning TRUE i'm returning an error code. (Ex. -1, 0, 1)
            if ($result_code === true) {
                phive('Logger')->getLogger('payments')->info('Track tmp_deposit_block: result_code is true',
                    [
                        'user_id' => $user->getId(),
                        'url' => $_SERVER['REQUEST_URI'] ?? null,
                        'file' => __METHOD__ . '::' . __LINE__,
                        'result_code' => $result_code
                    ]
                );

                $ext_kyc_module->preventFailLogAndBlock();
                $user->deleteSetting('tmp_deposit_block');
                $user->deleteSetting('experian_block');
                break;
            } else {
                $ext_kyc_module->addKycErrors($supplier, $result_code);
            }
        }

        $ext_kyc_module->logAndBlockUserOnFailedKyc($user, 'age');
        $ext_kyc_module->logSuccessfulAction($user, 'age');

        return $ext_kyc_module->isSuccessful();
    }

    /**
     * Get alerts from a supplier (when a player become PEP or is sanctioned)
     *
     * @param null $suppliers
     */
    public function checkPepAlertsCommon($suppliers = null)
    {
        $suppliers = $this->getPepSuppliers($suppliers);
        foreach ($suppliers as $supplier) {
            if($supplier && !$this->checkSupplierPepActiveAlert($supplier)) {
                break;
            }
            $module = phive("DBUserHandler/{$supplier}");
            if (empty($module) || !method_exists($module, 'getPepAlerts')) {
                continue;
            }
            $result = $module->getPepAlerts();
            if (!empty($result)) {
                $this->handleAlerts($result, $supplier);
            }
        }
    }



    /**
     * Handle the alerts created for a supplier
     *
     * @params array $alerts, all alerts in the system
     * @param $supplier
     */
    public function handleAlerts($alerts, $supplier)
    {
        foreach ($alerts as $alert) {
            $user = cu($alert['uniqueId']);
            if(empty($user)) {
                // hotfix to prevent fatal error on "getJsonSetting" when user is null.
                // done to prevent fatal error breaking everyday.php CRON execution. /Paolo
                phive()->dumpTbl('pep-monitoring-error', ["Searching for user with ID {$alert['uniqueId']}, but not found", $alert]);
                continue;
            }
            $low_supplier = strtolower($supplier);
            $match_date = max(array_column($alert['matches'], 'matchDate'));
            $old_alert = $user->getJsonSetting("$low_supplier-alert");
            $old_match_date = max(array_column($old_alert['matches'], 'matchDate'));
            if ($old_match_date == $match_date) {
                continue;
            }
            $user->setJsonSetting("$low_supplier-alert", $alert);
            $email[] = phive("DBUserHandler/{$supplier}")->getPEPAlertsEmailBody($user, $alert);
        }
        if (!empty($email)) {
            phive("DBUserHandler/ExternalKyc")->sendPepAlert($email, $supplier);
        }
    }

    /**
     * Will run a PEP/SL check on all the providers sets on "['kyc_suppliers']['config']['pep_order']".
     * As soon as we get 1 verified result we exit the loop and mark the user as PEP/SL verified.
     * If all the provider fails then we "block()" the user with reason 12 (PEP/SL) add "experian_block" too.
     * In case of recurrent check, if a user is not a PEP anymore, and was previously blocked with reason 12 we unblock the player.
     *
     * @param $user
     * @param bool $recurrent
     * @param null $suppliers
     * @return bool
     */
    public function checkPEPSanctionsCommon($user, $recurrent = false, $suppliers = null)
    {
        if (empty(phive('Licensed')->getSetting('kyc_suppliers')['config']['pep_order'])) {
            return true;
        }
        $user = cu($user);

        $suppliers = $this->getPepSuppliers($suppliers);


        /** @var ExternalKyc $ext_kyc_module */
        $ext_kyc_module = phive("DBUserHandler/ExternalKyc");
        $ext_kyc_module->resetFailureCheck();

        // If no suppliers are provided we behave like it was a success
        if(empty($suppliers)) {
            $ext_kyc_module->preventFailLogAndBlock();
        }

        foreach ($suppliers as $supplier) {
            /** @var Acuris|ID3 $module */

            $module = phive("DBUserHandler/{$supplier}");
            if(empty($module) || !method_exists($module, 'checkPEPSanctions')) {
                continue;
            }
            $result_code = $module->checkPEPSanctions($user);

            // If not explicitly returning TRUE i'm returning an error code. (Ex. ERROR, ALERT, '')
            if ($result_code === true) {

                $ext_kyc_module->preventFailLogAndBlock();
                // In case of a successful result on "recurrent check" if the user is blocked (but not super blocked)
                // we re-enable it if the blocked reason was "Failed PEP/SL check" (12)
                if($recurrent && $user->isBlocked() && !$user->isSuperBlocked() && phive('UserHandler')->getBlockReason($user->getId()) == 12){
                    phive('UserHandler')->removeBlock($user);
                    $user->deleteSetting('experian_block');
                }

                break;
            } else {
                $ext_kyc_module->addKycErrors($supplier, $result_code);

                if ($this->checkSupplierPepActiveAlert($supplier)){
                    $ext_kyc_module->setAlert(true);
                }
            }
        }

        $ext_kyc_module->logAndBlockUserOnFailedKyc($user, 'pep', $recurrent);
        $ext_kyc_module->logSuccessfulAction($user, 'pep');

        return $ext_kyc_module->isSuccessful();
    }

    private function checkSupplierPepActiveAlert($supplier):bool {
        return (bool) phive('Licensed')->getSetting('kyc_suppliers')[strtolower($supplier)]['pep_active_alerts'];
    }

    /**
     * Get all suppliers from config
     *
     * @param $suppliers
     * @return mixed
     */
    public function getPepSuppliers($suppliers){
        return  $suppliers ?? phive('Licensed')->getSetting('kyc_suppliers')['config']['pep_order'];
    }

    /**
     * Monitoring the users
     *
     * @param $user
     * @param $edit
     * @param null $suppliers
     */
    public function handleUsersOnExternalKycMonitoring($user, $edit, $suppliers = null)
    {
        $suppliers = $this->getPepSuppliers($suppliers);
        foreach ($suppliers as $supplier) {
            $module = phive("DBUserHandler/{$supplier}");
            if (empty($module) || !method_exists($module, 'addToMonitoring')) {
                continue;
            }

            if (phive('Licensed')->getSetting('kyc_suppliers')[strtolower($supplier)]['monitoring_disabled']) {
                $user->setSetting("pending_{$supplier}_monitoring", (int)$edit);
                return;
            }

            $module->addToMonitoring($user, $edit);
        }
    }

    /**
     * Wrapper function for "checkKycDobCommon" and "checkPEPSanctionsCommon" to prevent creating 2 forks
     *
     * @param int $uid
     * @param bool $recurrent
     */
    public function checkKycGeneral($uid, $recurrent = false)
    {
        $this->checkKycDobCommon($uid);
        $this->checkPEPSanctionsCommon($uid, $recurrent);
    }

    /**
     * This will trigger all the KYC check for a user, for now only PEP/SL check during registration / login quarterly.
     *
     * When the check is recurrent the process is running on a fork so we just run the function directly, instead
     * when the process is not recurrent we want to run the PEP/SL check on a fork.
     * This is done to avoid having the player hanging with a loader for a long time when finalizing the registration process.
     *
     * @param $user
     * @param bool $recurrent True when it is ongoing monitoring
     */
    public function checkKyc($user, bool $recurrent = false)
    {
        if($user->hasSetting('test_account')){
            return;
        }

        if(empty($recurrent)) {
            phiveApp(EventPublisherInterface::class)
                ->fire('authentication', 'AuthenticationCheckPEPSanctionsCommonEvent', [uid($user), $recurrent], 0);
        } else {
            $this->checkPEPSanctionsCommon($user, $recurrent);
        }
    }

    public function customLoginJs($context){
        echo 'licFuncs.onLoginReady.call();';
        if ( $context === 'registration' ) {
            echo 'licFuncs.showCustomLogin.call();';
        }
    }

    /**
     * Sample'test_url' => 'https://videoslots.gamtest.se/sv-SE?puid='
     *
     * @param DBUser|mixed $u_obj
     * @param string $lang
     * @return string
     */
    public function getGamTestUrl($u_obj = null, $lang = null)
    {
        if (empty($lang)) {
            // NOTE that cLang() won't work on the CLI
            $lang = empty($u_obj) ? cLang() : $u_obj->getLang();
        }

        $settings = phive('Licensed')->getSetting('self_test_suppliers')[$this->getLicSetting('self_test')];

        $lang = in_array($lang, $settings['languages']) ? $lang : 'en';

        $base_url = $settings['url'] .'/'. phive('Localizer')->getLocale($lang, 'langtag');

        if (empty($u_obj)) {
            return $base_url;
        }

        return $base_url . '?puid=' . $u_obj->getId();
    }

    /**
     * @param DBUser|null $u_obj
     * @param string $lang
     * @return mixed
     */
    public function getRespGamingUrl($u_obj = null, $lang = null)
    {
        if(empty($u_obj)) {
            return phive('Casino')->getBasePath($lang, null, true) . self::RESPONSIBLE_GAMBLINE_ROUTE . '/';
        }

        return phive('UserHandler')->getUserAccountUrl('responsible-gambling', $lang);
    }

    /**
     * @param DBUser|null $u_obj
     * @param string $lang
     * @return mixed
     */
    public function getDocumentsUrl($u_obj = null, $lang = null)
    {
        if(empty($u_obj)) {
            return phive('Casino')->getBasePath($lang, null, true).'documents/';
        }
        return phive('UserHandler')->getUserAccountUrl('documents', $lang);
    }

    public function ajaxRgDepLimPromptSetShown(){
        $u_obj = cuPl();
        if(!empty($u_obj)){
            $u_obj->setSetting('reg-dep-lim-prompt', 1);
            echo 'ok';
        } else {
            echo 'nok';
        }
    }

    /**
     * If > 0 it will set the max number of days a player can lock his account.
     * It can be overridden on an child ISO class (Ex. see GB.php)
     *
     * @return int
     */
    public function getLockAccountMaxDays() {
        return 0;
    }

    /**
     * Alias used for the RG Lock Account section
     * - Default (no $iso)
     *
     * @return array
     */
    public function getLockAccountMessages(): array
    {
        return [
            'headline' => "lock.account.days",
            'description' => "lock.account.info.html",
            'submenu' => "lock.account.x.days",
        ];
    }

    /**
     * Log to the external audit log table. Just a helper in case we want to improve the logic in the future.
     *
     * @param $tag
     * @param $request
     * @param $response
     * @param $time
     * @param $code
     * @param int $request_id
     * @param int $response_id
     * @param int $uid
     */
    public function logExternal($tag, $request, $response, $time, $code, $request_id = 0, $response_id = 0, $uid = 0)
    {
        phive()->externalAuditTbl($tag, $request, $response, $time, $code, $request_id, $response_id, $uid);

    }

    /**
     * Return the current version of terms and condition for the user jurisdiction
     * If nothing is specified it fallback to the default one.
     * Product is using for detect for which terms and condition (general, sports)
     * If nothing specified by default will be general.
     *
     * @param string $product
     * @return mixed
     */
    public function getTermsAndConditionVersion($product = '')
    {
        if (!empty($product)) {
            return $this->getConfigsByJurisdiction('terms-and-conditions',
                ['tc-version-' . $product, 'tc-page-' . $product])['tc-version-' . $product];
        }

        return $this->getConfigsByJurisdiction('terms-and-conditions', ['tc-version', 'tc-page'])['tc-version'];
    }

    /**
     * Return the current version of Bonus Terms and Condition for the user jurisdiction
     *
     * @return string
     */
    public function getBonusTermsAndConditionVersion(): string
    {
        $btc_config = $this->getConfigsByJurisdiction('terms-and-conditions', ['bonus-tc-version']);

        return (string) ($btc_config['bonus-tc-version'] ?? '');
    }

    /**
     * Return the current version of Privacy Policy for the user jurisdiction
     *
     * @return string
     */
    public function getPrivacyPolicyVersion(): string
    {
        $privacy_policy_config = $this->getConfigsByJurisdiction('users', ['pp-version']);

        return (string) ($privacy_policy_config['pp-version'] ?? '');
    }

    /**
     * Return the page containing privacy policy for the user jurisdiction
     *
     * @return string
     */
    public function getPrivacyPolicyPage(): string
    {
        $btc_config = $this->getConfigsByJurisdiction('users', ['pp-page']);

        return (string) ($btc_config['pp-page'] ?? '');
    }

    /**
     * Return the page containing the terms and condition for the user jurisdiction
     *
     * @return string
     */
    public function getBonusTermsAndConditionPage(): string
    {
        $btc_config = $this->getConfigsByJurisdiction('terms-and-conditions', ['bonus-tc-page']);

        return (string) ($btc_config['bonus-tc-page'] ?? '');
    }

    /**
     * Return the page containing the terms and condition for the user jurisdiction
     * If nothing is specified it fallback to the default one.
     *
     * Product is using for detect for which terms and condition (general, sports)
     * If nothing specified by default will be general.
     *
     * @param string $product
     * @return mixed
     */
    public function getTermsAndConditionPage($product = '')
    {
        if (!empty($product)) {
            return $this->getConfigsByJurisdiction('terms-and-conditions',
                ['tc-version-' . $product, 'tc-page-' . $product])['tc-page-' . $product];
        }

        return $this->getConfigsByJurisdiction('terms-and-conditions', ['tc-version', 'tc-page'])['tc-page'];
    }

    /**
     * This function will be used if we have a jurisdiction specific config that override the standard one.
     * We check if a $name config exist under the "license-$iso" tag, otherwise return just the $name from the requested $tag (default scenario)
     *
     * Ex. $tag = 'terms-and-condition', $name = 'tc-version'
     * JURISDICTION, TAG, NAME, VALUE
     * 1) SE, license-SE, tc-version, 1.1    // License setting found
     * 2a) FI, license-FI, tc-version, NULL   // No license-FI config exist then 2b)
     * 2b) DEFAULT, terms-and-condition, tc-version, 2.6 // Fallback to default config
     *
     * @param $tag
     * @param $name
     * @param $iso - empty mean use the current ISO
     * @param null $default - if specified will set the default value on DB
     * @return mixed
     */
    protected function getConfigByJurisdiction($tag, $name, $iso = null, $default = null)
    {
        $result = false;
        $iso = empty($iso) ? $this->getIso() : $iso;
        // We only run the "lic query" if called by an ISO2 context, otherwise on Licensed $iso is empty
        if(!empty($iso)) {
            $result = phive('Config')->getValue('license-' . strtolower($iso), $name);
        }
        if (empty($result)) {
            $result = phive('Config')->getValue($tag, $name, $default);
        }
        return $result;
    }

    /**
     * This function will be used when we need to retrieve multiple configs for a jurisdiction and all of them must have a value.
     * We check if all the requested $names exist for a defined ISO and return them,
     * otherwise if just 1 is missing it will return the default config.
     *
     * @param $tag
     * @param $names
     * @param $iso - empty mean use the current ISO
     *
     * @return array
     */
    protected function getConfigsByJurisdiction($tag, $names = [], $iso = null)
    {
        $configs = [];
        $iso = empty($iso) ? $this->getIso(true) : $iso;
        // We only run the "lic query" if called by an ISO2 context, otherwise on Licensed $iso is empty
        if(!empty($iso)) {
            $configs = phive('Config')->getByTagValues('license-' . strtolower($iso));
        }
        // check if we have found a config, f.e. redirected from admin2 returns this as empty
        if (empty($configs)) {
            $request_uri = explode('/', $_SERVER['REQUEST_URI']);
            if ($request_uri[1] === 'account' && count($request_uri) >= 3) {
                $uid = $request_uri[2];
            }
            else {
                $uid = cu();
            }
            $current_user = cuPl($uid);
            $iso = licJur($current_user);
            $configs = phive('Config')->getByTagValues('license-' . strtolower($iso));
        }

        $allRequestedConfigsExist = true;
        $requestedConfigs = [];
        foreach($configs as $configName=>$configValue) {
            if(in_array($configName, $names)) {
                if(empty($configValue)) {
                    $allRequestedConfigsExist = false;
                    break;
                }
                $requestedConfigs[$configName] = $configValue;
            }
        }
        // If any of the requested config is missing (different count) or empty ($allRequestedConfigsExist) we will use the default ones
        if(count($names) != count($requestedConfigs) || !$allRequestedConfigsExist) {
            return phive('Config')->getByTagValues($tag);
        }

        return $requestedConfigs;
    }

    /**
     * Track status changes on the user.
     * Status will change on specific actions from the user or CS (via BO)
     * Ex. verify, restrict, block, self-exclude, etc...
     *
     * If status is the same, or an invalid one is provided, status is not changed.
     *
     * This can be enabled per jurisdiction via "enable_user_status_tracking" lic setting.
     *
     * Ex.
     * In Spain (ES) we are mapping our internal status to "EstadoCNJ" to filter player in ICS reports
     *
     * @see UserStatus::STATUS_ACTIVE - or all other STATUS_XXX variables
     * @param int $user_id - using user_id as it can be called from admin2 (that uses a different User object)
     * @param string $status - status user will be changed to from UserStatus::STATUS_XXX constants
     */
    public function trackUserStatusChanges($user_id, string $status)
    {
        $user = cu($user_id);
        if (empty($user) || empty($status) || !$this->getLicSetting('enable_user_status_tracking')) {
            return;
        }

        if (!in_array($status, UserStatus::getStatuses())) {
            phive()->dumpTbl('user_status_invalid', [$status], $user);
            return;
        }

        if ($status === UserStatus::STATUS_SELF_EXCLUDED) {
            $settings = $user->getSettingsIn(['unexclude-date', 'indefinitely-self-excluded', 'unlock-date'], true);
            if (
                !isset($settings['unexclude-date'], $settings['indefinitely-self-excluded']) &&
                isset($settings['unlock-date']) && $settings['unlock-date']['value'] > date('Y-m-d H:i:s')
            ) {
                //addBlock uses SELF_EXCLUDED for both "Lock Account" and "Self Exclude", it differs only on the settings
                $status = UserStatus::STATUS_SELF_LOCKED;
            }
        }

        $current_status = $user->getSetting('current_status') ?: UserStatus::STATUS_NA;
        if ($status === $current_status) {
            return;
        }

        if (!UserStatus::isAllowedStatusChange($user, $current_status, $status)) {
            return;
        }

        $user->refreshSetting('current_status', $status);
        phive('UserHandler')->logAction($user, "[$current_status-$status] Status changed from $current_status to $status", 'user_status_changed');
        $this->addRecordToHistory(
            'user_status_updated',
            new UserStatusUpdateHistoryMessage([
                'user_id'           => (int) $user->getId(),
                'old_status'        => $current_status,
                'new_status'        => $status,
                'event_timestamp'   => time(),
            ])
        );
    }

    /**
     * Get the current user status
     *
     * @param DBUser $user
     * @return mixed
     */
    public function getUserStatus(DBUser $user)
    {
        return $user->getSetting('current_status');
    }

    /**
     * Get all user_status changes for a customer.
     * If custom mapping exist for a specific jurisdiction it will be returned on "external_status_xxx"
     *
     * @param DBUser $user
     * @return mixed
     */
    public function getUserStatusHistory(DBUser $user)
    {
        return array_map(
            function($action) { return $this->formatUserStatusChangeAction($action); },
            phive('DBUserHandler')->getUserActions($user, 'user_status_changed')
        );
    }

    /**
     * Format user_status_changed description action.
     * Adding status to and from, and specific jurisdiction mapping if it exist.
     *
     * @param array $action
     * @return array
     */
    public function formatUserStatusChangeAction(array $action): array
    {
        preg_match("/\[(?P<from>.+)-(?P<to>.+)\]/", $action['descr'], $matches);

        $from = $matches['from'];
        $to = $matches['to'];

        $action['status_from'] = $from;
        $action['status_to'] = $to;
        $action['external_status_from'] = $this->getExternalUserStatusMapping($from);
        $action['external_status_to'] = $this->getExternalUserStatusMapping($to);

        return $action;
    }

    /**
     * Can be used to override internal mapping to something else for a specific License. (Ex. ES)
     * By default is using list of STATUS_XXX defined in DBUser on both "internal" and "external_status"
     *
     * @return array
     */
    protected function userStatusMapping(): array
    {
        return UserStatus::getStatuses();
    }

    /**
     * Return the mapped user status in case a jurisdictional override is present.
     * If a status is not mapped in the override we append "(missing-mapping)"
     *
     * @param $status
     * @return mixed|string
     */
    public function getExternalUserStatusMapping($status): string
    {
        $original_mapping = UserStatus::getStatuses();
        $mapping = $this->userStatusMapping();
        $missing = $original_mapping === $mapping ? '' : '(missing-mapping)';
        return $mapping[$status] ?? $status.$missing;
    }

    /**
     * Check if SB is enabled on both Global and Jurisdictional level.
     * For TestAccount is always true.
     *
     * @param string $tag
     * @return bool
     */
    public function isSportsbookEnabled(string $tag = "", $user_id = ""): bool
    {
        // Global - completely enable/disable SB.
        if (empty(phive()->getSetting('sportsbook', false))) {
            return false;
        }
        $user = cu($user_id);

        if (phive('Micro/Sportsbook')->isBetaTest()) {
            if (empty($user)) {
                return false;
            }
            if (!phive('Micro/Sportsbook')->hasSportsbookPermission($tag, $user)) {
                return false;
            }
            return true;
        }

        // Jurisdiction - enable/disable SB on a single jurisidction.
        return !empty($this->getLicSetting('sportsbook'));
    }

    public function isSportsbookOnMaintenance(): bool
    {
        // Global - completely set SB on maintenance.
        if (phive()->getSetting('sportsbook_maintenance', false)) {
            return true;
        }

        // Jurisdiction - set SB maintenance on a single jurisidction
        return !empty($this->getLicSetting('sportsbook_maintenance'));
    }

    public function isPoolxEnabled(): bool
    {
        // Global - completely enable/disable poolx.
        if (empty(phive()->getSetting('poolx_enabled', false))) {
            return false;
        }

        // Jurisdiction - enable/disable poolx on a single jurisidction.
        return !empty($this->getLicSetting('poolx_enabled'));
    }

    public function isPoolxOnMaintenance(): bool
    {
        // Global - completely set PoolX on maintenance.
        if (phive()->getSetting('poolx_maintenance', false)) {
            return true;
        }

        // Jurisdiction - set PoolX maintenance on a single jurisidction
        return !empty($this->getLicSetting('poolx_maintenance'));
    }

    /**
     * Check if Jurisdiction has Bonus T&C
     *
     * @return bool
     */
    public function hasBonusTermsConditions(): bool
    {
        return (bool) $this->getLicSetting('has_bonus_terms_conditions');
    }

    /**
     *  All RC code here, loads outside the game box
     *  -  RC for normal videoslots popup
     *  -  RC for redirection popup
     *
     * @param array|null $game
     * @param boolean $check_elapsed_time
     */
    public function printRealityCheck($game = null, $check_elapsed_time = false)
    {
        // legacy for playtech - remove when refactored to the new setting
        $in_game = !empty($game);
        $network_disabled = false;
        $network = '';
        if ($in_game) {
            $rc_popup_network_off = phive('Config')->valAsArray('game-network', 'reality-check-popup-off');
            $network = $game['network'];
            $network_disabled = in_array($network, $rc_popup_network_off);
        }

        if (!empty($game['ext_game_name']) && phive()->getSetting('ukgc_lga_reality') === true && !$network_disabled && !privileged()) {
            $reality_check_interval = phive('Casino')->startAndGetRealityInterval(null, $game['ext_game_name']);
            $elapsedTime = 0;
            if (!empty($reality_check_interval)) {
                $elapsedTime = lic('rcElapsedTime') ?: 0;
                loadJs("/phive/js/reality_checks.js");
            }
        }

        if ($check_elapsed_time && empty($elapsedTime)) {
            return;
        }
        if($in_game) {
        ?>
        <script type="text/javascript">
            // Init RC on desktop game page
            var g = <?php echo json_encode($game) ?>;
            $(document).ready(function () {
                var rcInterval = <?= $reality_check_interval ?: 0 ?>;
                if (rcInterval > 0 && typeof reality_checks_js !== 'undefined') {
                    rc_params.rc_current_interval = rcInterval;
                    reality_checks_js.elapsedTime = <?= $elapsedTime ?>;
                    reality_checks_js.network = '<?= $network ?>';
                    reality_checks_js.lang = '<?= phive('Localizer')->getLanguage() ?>';
                    reality_checks_js.skipTimer = false;
                    reality_checks_js.doAfter = function () {}; // prevent continuous reload on mobile
                    reality_checks_js.in_game = true;
                    reality_checks_js.gref  = g.ext_game_name;
                    reality_checks_js.rc_createDialog();
                }
            });
        </script>
        <?php
        }
    }

    /*  #######################################################################################
     *  ## Starting specific module functions. Do not modify without Ricardo's authorization ##
     */ #######################################################################################

    /**
     * @param $iso
     * @return mixed
     */
    public function isActive($iso)
    {
        return in_array($iso, $this->getSetting('licensed_countries'));
    }

    /**
     * @param $iso
     * @return mixed
     */
    public function setCountryIso(string $iso): void
    {
        $this->countryIso = $iso;
    }

    /**
     * @param $iso
     * @return mixed
     */
    public function setProvinceIso($province_iso): void
    {
        if (!empty($province_iso)) {
            $this->provinceIso = $province_iso;
        }
    }

    /**
     * @param $iso
     * @return bool
     */
    public function getLicense($iso)
    {
        if ($this->iso_license_cache[$iso] !== null) {
            return $this->iso_license_cache[$iso];
        }

        [$country, $province] = explode('-', $iso);

        if ($province){
            $iso = str_replace('-', '', $iso);
            $file = __DIR__ . "/$country/$iso.php";

            //if no province file then fallback to a country
            if (!file_exists($file)) {
                $iso = $country;
                $file = __DIR__ . "/$country/$country.php";
            }

        } else {
            $file = __DIR__ . "/$iso/$iso.php";
        }

        if (file_exists($file)) {
            require_once $file;
            $license = new $iso();
            //necessary for js/css/html license files
            $license->setCountryIso($country);
            $license->setProvinceIso($province);
            $this->iso_license_cache[$iso] = $license;

            return $license;
        }

        return false;
    }

    /**
     * The lic() routing.
     *
     * Takes an iso2 and tries to find the corresponding sub class to instantiate and call with the $params.
     *
     * @param string $iso The iso2, ex: GB
     * @param string $func The function to call on the GB instance.
     * @param array $params The arguments to the function to call on the GB instance.
     * @param object $default_obj If no sub class can be found and this object is not null then this object will be
     * used instead of an instantiated sub class.
     *
     * @return mixed false in case no object could be found to call a method on. Otherwise the return from that call.
     */
    public function doLicense($iso, $func, $params, $default_obj = null)
    {
        if (empty($params)) {
            $params = [];
        }
        $obj = $this->getLicense($iso);

        if (empty($obj)) {
            if(empty($default_obj)){
                return false;
            } else {
                $obj = $default_obj;
            }
        }

        if (method_exists($obj, $func)) {
            return call_user_func_array([$obj, $func], $params);
        } else if(!empty($default_obj) && method_exists($default_obj, $func)) {
            return call_user_func_array([$default_obj, $func], $params);
        }

        return false;
    }


    //return country iso
    protected function getIso(bool $include_province = false)
    {
        if (!empty($this->forced_country)) {
            return $this->forced_country;
        }

        if ($this->countryIso){
            if ($include_province && $this->provinceIso) {
                return $this->countryIso . '-' . $this->provinceIso;
            }

            return $this->countryIso;
        }

        return get_called_class() === get_class() ? false : get_called_class();
    }

    protected function getLicIso(): ?string
    {
        return get_called_class() === get_class()
            ? null
            : get_called_class();
    }


    /**
     * Get a setting from a License, not from the module
     *
     * @param $setting
     * @param string|null $country
     *
     * @return mixed
     *
     * @api
     */
    public function getLicSetting($setting, ?string $country = null)
    {
        $iso = $country ?? $this->getLicIso();

        if ($iso !== null) {
            if (empty(self::$settings_instance)) {
                self::$settings_instance = new self();
            }

            return self::$settings_instance->getSetting($iso)[$setting];
        }

        return $this->getSetting($setting);
    }

    /**
     * Get all settings from a License, not from the module
     *
     * @return mixed|null
     */
    public function getAllLicSettings()
    {
        if (!empty($this->getIso())) {
            if(empty(self::$settings_instance)){
                self::$settings_instance = new self();
            }

            return self::$settings_instance->getSetting($this->getIso());
        } else {
            return $this->allSettings();
        }
    }


    /**
     * Get the country for a license for logged in/out contexts
     * Is used for JS/CSS/Modules loading
     * @param DBUser|null $user
     * @return mixed country
     */
    public function getLicCountry($user = null)
    {
        if (!empty($this->forced_country)) {
            return $this->forced_country;
        }

        $cache_key = !empty($user) ? $user->getId() : 'DEFAULT';
        if (!empty($this->lic_country_cache[$cache_key])){
            return $this->lic_country_cache[$cache_key];
        }

        if (!empty($user)) {
            $country = $user->getCountry();
        }

        $iso_overwrite = $this->skip_domain_iso_override ? false : phive('Localizer')->getDomainSetting('domain_iso_overwrite');

        if (empty($country) && $iso_overwrite){
            [$countryIso, $provinceIso] = explode("-", $iso_overwrite);
            $country = $countryIso;
        }

        if (empty($country)) {
            $country = $this->getSetting('licensed_languages')[phive('Localizer')->getLanguage()];
        }

        if (empty($country)) {
            $country = phive('IpBlock')->getCountry();
        }

        // When user is logged out and url is videoslots.com/es - the website's content should not be restricted by DGOJ
        // A content will be the same as it would be videoslots.com (menu & BOS) but in spanish language
        if (!empty($country) && in_array($country, phive('Localizer')->getDomainSetting('excluded_countries', []))) {
            $country = $this->getBaseJurisdiction();
        }

        $this->lic_country_cache[$cache_key] = $country;

        return $country;
    }


    /**
     * Get the province/country for a license for logged in/out contexts
     * Is used for Province/Country configuration loading
     * @param DBUser|null $user
     * @return mixed country
     */
    public function getLicCountryProvince($user = null, $ip = null, $setting = null)
    {
        $cache_key = !empty($user) ? $user->getId() : 'DEFAULT';
        if (!empty($this->lic_country_province_cache[$cache_key])) {
            return $this->lic_country_province_cache[$cache_key];
        }

        // If we have a user, we get the license country-province from the user
        if (!empty($user)) {
            $provinceIso = $user->getProvince();

            if ($this->isActive($provinceIso)) {
                $country = $provinceIso;
            } else {
                $country = $user->getCountry();
            }
        }

        // If we don't have a user, the country-province is first determined by the domain_iso_overwrite setting
        $iso_overwrite = $this->skip_domain_iso_override ? false : phive('Localizer')->getDomainSetting('domain_iso_overwrite');
        if (empty($country) && $iso_overwrite) {
            $country = $iso_overwrite;
        }

        // If we don't have a user and the domain_iso_overwrite setting is not set, the country-province is determined by the IP of the user
        if (empty($country) && !empty($province = phive('IpBlock')->getProvinceFromIp(null, $ip, 'province_regulated_gameplay'))) {
            $provinceIso = getCountry().'-'.$province;
            if ($this->isActive($provinceIso)) {
                $country = $provinceIso;
            }
        }

        // If all province and country checks fail, we get the country from the getLicCountry method
        if (empty($country)) {
            $country = $this->getLicCountry($user);
        }

        $this->lic_country_province_cache[$cache_key] = $country;

        return $country;
    }

    public function getExcludedRegistrationLanguages()
    {
        return [];
    }

    /**
     * Simple wrapper for the default jurisdiction setting to be used in the future if we want to categorize countries
     * between our base license or set them as ROW and out of the license.
     *
     * @return mixed|null
     */
    public function getBaseJurisdiction()
    {
        return $this->getSetting('default_jurisdiction', 'MT');
    }

    /**
     * Simple wrapper for the default jurisdiction setting to be used in the future if we want to categorize countries
     * between our base license or set them as ROW and out of the license.
     *
     * @return array|null|bool
     */
    public function juristdictionalNotice() {
        $user_country = getCountry();
        $config = phive('Config')->getValue('countries', 'show-jurisdiction-message');

        if(strpos($config, $user_country) === false){
            return false;
        }
        ?>
        <script>
            var countryInJurisdiction = true;
            if ($.cookie('jurisdiction_popup') === null) {
                addToPopupsQueue(function () {
                    lic('showJurisdictionalNotice', []);
                });
            }
        </script>
        <?php
    }

    /**
     * Checks if the user is in a country list of a global setting in Licensed.config.php, ie not in a 'ISO2' => ... sub setting.
     *
     * @param DBUser $u_obj The user object.
     *
     * @return bool True if yes, false otherwise.
     */
    public function inCountrySetting($setting, $u_obj = null){
        $u_obj = cu($u_obj);
        if(empty($u_obj)){
            return false;
        }
        return in_array($u_obj->getCountry(), $this->getSetting($setting));
    }

    /**
     * @param $u_obj
     * @param int|null $limit
     * @return int
     * @throws Exception
     */
    public function getNetDepositMonthLimit($u_obj, ?int $limit = null): int
    {
        $default_limit = (int)$this->getLicSetting('net_deposit_limit')['month'];

        if (empty($limit)) {

            if ($this->default_net_deposit_in_client_currency) {
                $default_limit = mc($default_limit, $u_obj->getCurrency());
            }
        } else {
            $default_limit = $limit;
        }

        $limits_by_age = $this->getLicSetting('net_deposit_limit_modifier_by_age') ?? [];
        $dob = $u_obj->getAttribute('dob');
        $dob = new DateTime($dob);
        $today = new DateTime('today');
        $age = $dob->diff($today)->y;
        $modified_limit = null;
        foreach ($limits_by_age as $age_interval => $option) {
            $age_interval = explode("-", $age_interval);
            $min_age = $age_interval[0];
            $max_age = $age_interval[1];
            $isAgeMatch = $age >= $min_age && $age <= $max_age;

            if (! $isAgeMatch) {
                continue;
            }

            if (is_int($option)) {
                $modified_limit = $option;
            }

            if (is_array($option) && ! empty($option['operator']) && ! empty($option['operand'])) {
                switch ($option['operator']) {
                    case 'percentage':
                    default:
                    $modified_limit = (int) round(($option['operand'] / 100) * $default_limit);
                }
            }
        }

        if (! is_null($modified_limit)) {
            return $modified_limit;
        }

        return $default_limit;
    }

    /**
     * @return mixed
     */
    public function getPersonalNumberLength()
    {
        return $this->personal_number_length;
    }

    /**
     * @param $nid
     * @return bool
     */
    public function validateNid($nid)
    {
        return strlen($nid) == $this->getPersonalNumberLength();
    }

    public function getPersonLookupHandler()
    {
        if ($this->getLicSetting('person_lookup_handler_version') === 'v5') {
            return phive('DBUserHandler')->zs5;
        }

        return phive('DBUserHandler')->zs;
    }

    /**
     * @param $nid
     * @param $country
     * @param DBUser|null $user
     * @return mixed
     */
    public function getDataFromNationalId($country = null, $nid = null, $user = null)
    {
        // check if we can get data from national id
        if (empty($country) && empty($nid) && empty($user)) {
            return true;
        }
        /** @var DBUserHandler $uh */
        $uh = phive('DBUserHandler');
        if ($uh->getSetting('zignsec_v2')['status'] !== true) {
            if (!empty($user)) {
                return $user->setNid($nid);
            } else {
                return [];
            }
        }

        /** @var false|ZignSecLookupPersonData $lookup_res */
        $lookup_res = $this->getPersonLookupHandler()->getLookupPersonByNid($country, $nid);

        if (!$lookup_res) {
            return false;
        }

        if (!$lookup_res->wasFound()) {
            phive()->dumpTbl('zignsec-not-found', $lookup_res->getResponseData(), $user);
            if ($user) {
                $user->addComment("Customer personal number was not found by Zignsec // system");
            }
            return false;
        }

        if (!empty($user)) {
            $user->setNid($nid);
            $user->setSetting('nid_data', json_encode($lookup_res->getResponseData()));
        } elseif (lic('hasPrepopulatedStep2')) {
            $_SESSION['tmp_rstep2'] = $this->getPersonLookupHandler()->mapLookupData($lookup_res->getResponseData());
            $_SESSION['rstep2_disabled'] = $_SESSION['tmp_rstep2'];
        }

        return $lookup_res;
    }

    /**
     * Disable input if we received the value from external verification
     *
     * @param string|null $key
     * @return bool
     */
    public function shouldDisableInput(?string $key): bool
    {
        // normal user && we received value for $key
        return $_SESSION['ext_normal_user'] && !empty($_SESSION['rstep2_disabled'][$key]);
    }

    /**
     * @param string|null $key
     *
     * @return bool
     */
    public function shouldDisableLabel(?string $key): bool
    {
        return false;
    }


    /**
     * return default as empty string
     *
     * @param string|null $key
     * @return string
     */
    public function shouldGetInputName(?string $key): string
    {
        /*
         * map value will be the name attr value of input field
         * and for the same we have a validation in registration_new.js
         */
        $field_name_mapping = [
            'firstname'  => 'firstname',
            'lastname'   => 'lastname',
            'lastname_second' => 'lastname',
            'address'    => 'address',
            'building'   => 'building',
            'zipcode'    => 'zipcode',
            'city'       => 'city',
            'industry'   => 'industry',
            'occupation' => 'occupation',
            'place_of_birth' => 'city'
        ];
        return $field_name_mapping[$key] ?? '';
    }

    /**
     * Hide input if config is there & field is not there in config
     *
     * @param string|null $key
     * @return bool
     */
    public function shouldHideInput(?string $key): bool
    {
        $editable_user_profile_fields  = lic('getLicSetting', ['editable_user_profile_fields'], $this->uh->currentUser);
        return !empty($editable_user_profile_fields) && !in_array($key, $editable_user_profile_fields);
    }

    /**
     *
     */
    public function hasPrepopulatedStep2() {
        return empty($this->getLicSetting('disable_prepopulated_step2'));
    }

    /**
     * Detect if permanent self exclusion is enabled for country
     *
     * @return bool
     */
    public function permanentSelfExclusion() {
        return !empty($this->getLicSetting('permanent_self_exclusion'));
    }

    /**
     * Detect if indefinite self exclusion is enabled for country
     *
     * @return bool
     */
    public function indefiniteSelfExclusion() {
        return !empty($this->getLicSetting('indefinite_self_exclusion'));
    }

    /**
     * Return the permanent self exclusion message
     *
     * @param bool $translate
     *
     * @return mixed
     */
    public function getPermanentSelfExclusionConfirmMessage(bool $translate = true)
    {
        $alias = 'exclude.generic.end.info.html';

        return $translate ? t($alias) : $alias;
    }

    /**
     * Detect if the permanent self exclusion has expired on account holding $nid
     *
     * @param $nid
     * @return mixed
     */
    public function expiredPermanentExclusionAccount($nid) {
        return phive('UserHandler')->getUserByNid('closed_' . $nid, licJurOrCountry(), false);
    }

    /**
     * Return the name of an extra class (or an empty string) when a top bar is going to be displayed
     * This extra class will add a top margin in some elements, so that top bar will not cover them
     *
     * @param string $path The current path, acquired from phive('Pager')->getRawPathNoTrailing(). Needed to detect for example that we are in homepage
     * @return string The extra class name or an empty string
     */
    public function insideTopbar($path = '')
    {
        return '';
    }
    /**
     * Used to overwrite the country on registration
     *
     * @param $country
     */
    public function forceCountry($country)
    {
        $this->resetCaches();
        $this->forced_country = $country;
        $this->extv = $this->extVerificationSupplier();
    }

    /**
     * Used to overwrite the domain_iso_override on registration
     *
     */
    public function skipDomainIsoOverride()
    {
        $this->skip_domain_iso_override = true;
        $this->lic_country_province_cache = null;
        $this->lic_country_cache = null;
    }

    public function resetCaches(){
        $this->lic_country_province_cache = null;
        $this->lic_country_cache = null;
    }

    /**
     * @param bool $translate
     *
     * @return mixed
     */
    public function getRegistrationMessage(bool $translate = true)
    {
        $alias = 'register';

        return $translate ? t($alias) : $alias;
    }

    /**
     * FE will generate the registration button based on the information returned here
     *
     * @return array
     * @deprecated
     */
    public function registrationActionButton()
    {
        return [
            "click" => $this->hasExtVerification() ? "showLoginBox('registration')" : "submitStep1()",
            "message" => $this->getRegistrationMessage()
        ];
    }

    /**
     * Display a <table> with balances for account in mobile main menu
     * @param string $tableClass  The class to add in this table
     *
     */
    public function mobileBalanceTable( $tableClass = "txt-table")
    {
        ?>
        <table class="<?= $tableClass ?>">

            <tr>
                <td>
                    <span class="medium-bold"><?php et('casino.balance.upc') ?></span>
                </td>
                <td class="right">
                <span class="medium-bold header-3">
                    <?php echo cs() ?>
                    <span id="mobile-left-menu-balance">
                    <?php echo phive("QuickFire")->parentBalance() ?>
                    </span>
                </span>
                </td>
            </tr>

            <?php if(!empty($this->bonus_balance)): ?>
                <tr>
                    <td>
                        <span class="medium-bold"><?php et('casino.bonus.balance.upc') ?></span>
                    </td>
                    <td class="right">
                    <span class="medium-bold header-3">
                    <?php echo cs() ?>
                    <span id="mobile-left-menu-bonus-balance">
                        <?php echo $this->bonus_balance / 100 ?>
                    </span>
                    </span>
                    </td>
                </tr>
            <?php endif ?>

        </table>
        <?php
    }

    /**
     * Returns required data for intermediary step
     *
     * @param string $context value in ['registration', 'registration_mitID' 'login', 'verification']
     * @param string $sessionId
     * @param bool $isApi
     *
     * @return array
     */
    public function initIntermediaryStep($context, $sessionId = "", $isApi = false, $country = null)
    {
        $country = $country ?? $this->getIso();
        $licParams = lic('getIntermediaryStepParameters', [$sessionId, $isApi], null, null, $country);


        if ($isApi) {
            return [
                'redirect_to_page' => self::INTERMEDIARY_STEP_ACTION_NID_VERIFICATION,
                'params' => [
                    'context' => $context,
                    'country' => $country,
                ],
            ];
        }

        $method = lic('getItermediaryStepMethod', [$context], null, null, $country) ?: 'showLoginBox';
        $params = [$context, true, true, $country, $licParams];

        return [$method, $params];
    }

    /**
     * Generic function to determine if a field need to be displayed or not on "non refactored" registration
     * (For refactored registration see Step1/2FieldsTrait)
     *
     * @param string $step
     * @param null $field
     * @return false|mixed
     */
    public function showRegistrationExtraFields($step = 'step1', $field = null) {
        if(empty($field) || !in_array($step, ['step1', 'step2'])) {
            return false;
        }
        return $this->extra_registration_fields[$step][$field] ?? false;
    }

    /**
     * Basic validation on extra fields for specific jurisdiction.
     * For now we check only if the field is not empty.
     *
     * @param $step
     * @param array $request
     * @param null $user_id
     *
     * @return array
     */
    public function extraRegistrationValidations($step, array $request, $user_id = null)
    {
        $errors = [];
        foreach($this->extra_registration_fields[$step] as $field => $value) {
            if(empty($request[$field])) {
                $errors[$field] = 'empty';
            }
        }
        return $errors;
    }

    /**
     * Show Logo
     *
     * @param string $extra_classes
     * @param string $img_class
     * @return bool
     */
    public function rgOverAge($extra_classes = '', $img_class = '')
    {
        if (!$this->getLicSetting('rg-over_age')) {
            return false;
        }
        ?>
        <div class="rg-top__item over-age <?php echo $extra_classes ?> ">
            <img src="<?= $this->imgUri(static::OVER_AGE_IMG) ?>" class="vs-sticky-bar__image <?php echo $img_class ?>">
        </div>
        <?php
    }

    /**
     * Returns true if the current game (demo or real) must be blocked. See phive/modules/Micro/MicroGames.php::onPlay > "if (lic('noDemo'))"
     * If we are explicitly loading the game in demo mode then check settings to prevent for example a logged-in MT player from hacking the URL and illegally launching demo mode.
     * If the player is logged in then the game can proceed.
     * If the player is not logged in then it depends on the setting for no-demo countries.
     * If player is not logged in and country is in the no demo countries we disallow demo play in order to protect the children.
     *
     * @param bool $show_demo Flag indicating if the game is being loaded in demo mode.
     * @return bool true if game should be blocked otherwise false.
     */
    public function noDemo(bool $show_demo = false): bool
    {
        // @todo: Why do we need $show_demo? Search yield no case where this parameter is used.
        if (isLogged() && !$show_demo) {
            return false;
        }

        $no_demo_countries = phive('Config')->valAsArray('countries', 'no-demo-play');

        return current($no_demo_countries) == 'ALL' ? true : in_array(cuCountry('', false), $no_demo_countries);
    }

    /**
     * Default registration step 2 setup
     *
     * @return string[][]
     */
    public function registrationStep2Fields()
    {
        if(isPNP()){
            return [
                'fields' => ['firstname', 'lastname', 'address', 'zipcode', 'city', 'birth_country', 'person_id', 'place_of_birth']
            ];
        }

        if (phive()->isMobile()) {
            return [
                'left' => ['firstname', 'lastname', 'address', 'zipcode', 'city', 'bonus_code', 'currency', 'preferred_lang'],
                'right' => ['birthdate', 'sex', 'email_code', 'eighteen']
            ];
        }
        return [
            'left' => ['firstname', 'lastname', 'address', 'zipcode', 'city', 'preferred_lang', 'bonus_code'],
            'right' => ['birthdate', 'currency', 'sex', 'email_code', 'eighteen']
        ];
    }

    /**
     * Store extra fields during registration into users_settings.
     *
     * @param DBUser $user
     * @param array $fields
     */
    public function saveExtraInformation(DBUser $user, array $fields): void
    {
        $filter = $this->getUserSettingsFields();
        $to_save = array_filter(
            $fields,
            function($field_name) use ($filter) {
                return in_array($field_name, $filter);
            },
            ARRAY_FILTER_USE_KEY
        );
        $user->setSettings($to_save);
    }

    /**
     * @param string $iso
     * @return string
     */
    private function getLicenseConfigByIso2(string $iso): string
    {
        return "SELECT * FROM license_config WHERE LOWER(license) = '{$iso}' ";
    }

    /**
     * Get the name of a province given its ISO code.
     *
     * @param string $iso The ISO code of the province.
     *
     * @return string The name of the province, or an empty string if the province is not found.
     */
    public function getProvinceNameByIso(string $iso): string
    {
        if (empty($iso)) {
            return '';
        }

        $iso_like_query = '%"iso_code":"' . $iso . '"%';
        $province_data = phive('SQL')->getValue("SELECT config_value FROM license_config WHERE config_tag = 'provinces' AND config_value like '{$iso_like_query}' LIMIT 1");

        return isset($province_data)
            ? json_decode($province_data, true)['province'] ?? ''
            : '';
    }

    /**
     * @param  \Videoslots\Menu\Boxes\MobileMenu\Element\MobileBalanceTableData  $data
     *
     * @return void
     */
    public function formatMobileBalanceTableToHtml(MobileBalanceTableData $data): void
    {
    }

    /**
     * @param  \Videoslots\Menu\Boxes\MobileMenu\Element\MobileBalanceTableData  $data
     *
     * @return array
     */
    public function formatMobileBalanceTableToJson(MobileBalanceTableData $data): array
    {
        return [];
    }

    /**
     * @return bool
     */
    public function canFormatMobileBalanceTable(): bool
    {
        return false;
    }

    /**
     * @return array
     */
    public function getMobileBalanceTable(): array
    {
        return [];
    }

    /**
     * @param string $tag
     * @param string|null $iso
     * @return array
     */
    private function loadTagFromDB(string $tag, string $iso = ''): array
    {
        $iso = empty($iso) ? strtolower($this->getIso()) : strtolower($iso);
        return phive('SQL')->loadArray($this->getLicenseConfigByIso2($iso) . "AND config_tag = '$tag'");
    }

    /**
     * @param string $tag
     * @param string|null $configName
     * @param string|null $iso
     * @return array
     */
    private function loadTagConfigFromDB(string $tag, string $configName, string $iso = ''): array
    {
        $iso = empty($iso) ? strtolower($this->getIso()) : strtolower($iso);
        return phive('SQL')->loadArray($this->getLicenseConfigByIso2($iso) . "AND config_tag = '$tag' AND config_name = '$configName' ");
    }

    /**
     * @param string $tag
     * @param bool $only_values
     * @param string $iso
     * @return array
     */
    public function getByTags(string $tag, bool $only_values = false, string $iso = ''): array
    {
        $result = [];
        foreach($this->loadTagFromDB($tag, $iso) as $config) {
            if (! isset($result[$config['config_tag']])) {
                $result[$config['config_tag']] = [];
            }
            $config_value = $only_values ? phive('Config')->getValueFromTemplate($config) : $config;
            $result[$config['config_tag']][$config['config_name']] = $config_value;
        }

        return $result;
    }

    /**
     * @param string $tag
     * @param string $configName
     * @param bool $only_values
     * @param string $iso
     * @return array
     */
    public function getByTagsAndConfigName(string $tag, string $configName, bool $only_values = false, string $iso = ''): array
    {
        $result = [];
        foreach($this->loadTagConfigFromDB($tag, $configName, $iso) as $config) {
            if (! isset($result[$config['config_tag']])) {
                $result[$config['config_tag']] = [];
            }
            $config_value = $only_values ? phive('Config')->getValueFromTemplate($config) : $config;
            $result[$config['config_tag']][$config['config_name']] = $config_value;
        }

        return $result;
    }

    /**
     * Save configs for licenses
     * @param $tag
     * @param $name
     * @param $value
     * @param string $delimiter
     * @param array $type
     */
    public function setConfigValue($tag, $name, $value, $delimiter = ',', $type = ['type' => 'json']){
        if (is_array($value)) {
            $value = implode($delimiter, $value);
        }
        $iso = empty($iso) ? strtolower($this->getIso()) : strtolower($iso);
        phive('SQL')->insertArray('license_config', [
            'config_tag' => $tag,
            'config_name' => $name,
            'config_value' => $value,
            'config_type' => json_encode($type),
            'license' => $iso
        ]);
    }

    /**
     * Return all of the countries to populate the country of birth input
     * @return array
     */
    public function getBirthCountryList()
    {
        return array_diff_key(
            phive('Cashier')->displayBankCountries(phive('Cashier')->getBankCountries('', true), [], !phive()->isMobile()),
            phive('Config')->valAsArray('countries', 'block')
        );
    }

    /**
     * @return array
     */
    public function getNationalities(): array
    {
        $countries = phive('SQL')->readOnly()->loadArray("SELECT iso FROM bank_countries");
        $nationalities = [];

        $topNationalities = $this->getLicSetting('top_nationalities');
        foreach ($countries as $country) {
            $iso = $country['iso'];
            $nationalities[$iso] = t("country.name.{$iso}");
        }

        $topItem = [];
        if (!empty($topNationalities)) {
            // Extract keys in the correct order
            foreach ($topNationalities as $key) {
                if (isset($nationalities[$key])) {
                    $topItem[$key] = $nationalities[$key];
                    unset($nationalities[$key]);
                }
            }
        }

        asort($nationalities);

        if (!empty($topItem)) {
            return array_merge(
                ['common.countries' => ['type' => 'optgroup']],
                $topItem,
                ['uncommon.countries' => ['type' => 'optgroup']],
                $nationalities);
        }

        return $topItem + $nationalities;
    }

    /**
     * Stop showing provided reminder popup
     *
     * @param string $popup
     */
    public function stopShowingReminderPopup($popup = 'account_verification_reminder'): void
    {
        unset($_SESSION[$popup]);
    }

    /**
     *  Specifies the lifetime of the cookie in seconds which is sent to the browser. The value 0 means "until the browser is closed."
     *
     * @return int
     */
    public function cookieLifetime()
    {
        return 36000;
    }

    /**
     * Common single matching functionality for most of countries, where we just check if the same email exists.
     *
     * @param DBUser $user
     * @param string $remote
     *
     * @return
     */
    public function matchInBrand($user, $remote)
    {
        return toRemote($remote, 'matchUser', [
            $user->getId(),
            'ByAttribute',
            ['attribute' => 'email', 'user_data' => ud($user), 'jurisdiction' => $user->getJurisdiction()]
        ], 3);
    }

    /**
     * Notify net deposit limit reset by email
     *
     * @param $rgl
     * @return bool|void
     * @throws Exception
     */
    public function notifyNetDepositLimitReset($rgl)
    {
        $today = new DateTime('now');
        $user = cu($rgl['user_id']);

        if ($user->hasSetting('net-deposit-monthly-reset-email-sent')) {
            $user_notified_at = new DateTime($user->getSetting('net-deposit-monthly-reset-email-sent'));
            $should_notify_user = $user_notified_at->diff($today)->m !== 0;
            if (!$should_notify_user) {
                return false;
            }
        }

        $is_email_sent = rgLimits()->notifyNetDepositLimitReset($rgl);

        if ($rgl['time_span'] === 'month' && $is_email_sent) {
            $user->setSetting('net-deposit-monthly-reset-email-sent', $today->format('Y-m-d H:i:s'));
        }

        $description = $is_email_sent ? "Notify net deposit limit email sent successfully" : "Notify net deposit limit email sending failed";
        phive('UserHandler')->logAction($rgl['user_id'], $description, $rgl['type']);

        return $is_email_sent;
    }

    /**
     * Validate personal number
     *
     * @param $nid
     * @return null
     */
    public function validatePersonalNumber($nid) {
        return null;
    }

    /**
     * Provides personal number already taken message
     * @param bool $translate
     * @return array|string|string[]|null
     */
    public function personalNumberTakenMessage($translate = true)
    {
        $alias = 'nid.already.taken';

        return $translate ? t($alias) : $alias;
    }
    /**
     * Provides personal number is empty message
     * @param bool $translate
     * @return array|string|string[]|null
     */
    public function personalNumberEmptyMessage($translate = true)
    {
        $alias = 'empty';

        return $translate ? t($alias) : $alias;
    }

    /**
     * Used to prevent nid verification popup on registration step 1 form submit
     * @param array $data
     * @return false
     */
    public function passedExtVerification($data = [])
    {
        return false;
    }

    /**
     * Clean the nid value
     *
     * @param $nid
     * @return mixed
     */
    public function sanitizeNid($nid) {
        return phive()->rmNonNums($nid);
    }

    /**
     * If not empty returns an array with the game categories that we need to display in
     * account game history as an extra filter.
     *
     * ATM used only for ES - we can only display games of the "licenses" (game type on DGOJ) that we possess
     *
     * @return array
     */
    public function getAccountGameTypeFilters(){
        $game_type_filters = $this->getLicSetting('enabled_gambling_licenses');
        if(!empty($game_type_filters)) {
            $game_categories = [];
            foreach ($game_type_filters as $category) {
                $game_categories[$category] = t($category);
            }
            return $game_categories;
        }
        return [];
    }

    /**
     * Return true if the jurisdiction support filtering by expanded game categories.
     * - account history: add filter by gambling license (Ex. roulette, blackjack, slots)
     *
     * @return bool
     */
    public function useExpandedGameCategoriesFiltering()
    {
        return !empty($this->getLicSetting('enabled_gambling_licenses'));
    }

    /**
     * Detect if lic method exists
     *
     * @param $method
     * @return bool
     */
    public function methodExists($method): bool
    {
        return method_exists($this, $method);
    }

    /**
     * Get forced currency for registration
     *
     * @return string
     */
    public function getForcedCurrency(): string
    {
        return $this->getLicSetting('forced_currency') ?? '';
    }

    /**
     * Get forced language for registration
     *
     * @return string
     */
    public function getForcedLanguage(): string
    {
        return static::FORCED_LANGUAGE;
    }

    public function getForcedProvince(): string
    {
        return static::FORCED_PROVINCE;
    }

    /**
     * Detect if we should notify(send email) when cool off ended and new limit is applied
     *
     * @return bool
     */
    public function shouldNotifyOnRgLimitCoolOffEnd(): bool
    {
        return !empty($this->getLicSetting('notify_cool_off_end'));
    }

    /**
     * Get rg info popup: total wages, wins and losses in period from config
     *
     * @param DBUser|null $u_obj
     * @return array
     */
    public function getWagersWinsLosses(DBUser $u_obj = null, $setting='rg_info'): array
    {
        $u_obj = cu($u_obj);

        $rg_info_settings = $this->getLicSetting($setting);

        if (empty($u_obj) || empty($rg_info_settings['popup_rg_activity']) || empty($rg_info_settings['period'])) {
            return [];
        }

        $start_stamp = phive()->hisMod($rg_info_settings['period']);
        $end_stamp = phive()->hisNow();

        $result = phive('MicroGames')->sumColsFromGameSessions($u_obj, ['bet_amount', 'win_amount', 'result_amount'], [$start_stamp, $end_stamp]);

        return [
            'wagers' => $result['bet_amount'] ?? 0.00,
            'wins' => $result['win_amount'] ?? 0.00,
            'losses' => $result['result_amount'] ?? 0.00,
        ];
    }

    /**
     * Store action: user clicked at the button "Accept" on rg popup with activity info
     *
     * @return bool[]
     */
    public function ajaxRgActivityAccepted($data): array
    {
        $user = cu();
        $result = false;
        $action_tag = 'rg-activity-last-accepted';

        if (empty($user) || empty($data['config_name'])) {
            return ['success' => false];
        }

        $user_id = $user->getId();

        if ($data['config_name'] === 'rg_info' && !empty($this->getLicSetting($data['config_name'])['popup_rg_activity'])) {
            $result = phive('DBUserHandler')->logAction($user, "User {$user_id} accepted RG activity popup", $action_tag, false, $user);
        }

        if ($data['config_name'] === 'rg_65_info' && !empty($this->getLicSetting($data['config_name'])['popup_rg_activity'])) {
            $user->setSetting('intensive_gambler_warning_accepted', phive()->hisNow());
            $result = phive('DBUserHandler')->logAction($user, "User {$user_id} accepted the Intensive Gambler Check", $action_tag, false, $user);
        }

        return ['success' => (bool) $result];
    }
    /**
     * Return all the configs needed on the setup popup for Reality Check
     *
     * @return array
     */
    public function getRcConfigs(): array
    {
        // The licenced settings `reality_check` take precedence
        $rc_settings = lic('getLicSetting', ['reality_check']);
        $configs = phive('Config')->getByTagValues('lga');

        $rc_default_interval = empty($rc_settings['interval_default_in_minutes'])
            ? ($configs['reality-check-period'] ?? 60)
            : $rc_settings['interval_default_in_minutes'];
        $rc_max = empty($rc_settings['interval_max_in_minutes'])
            ? ($configs['reality-check.max.interval'] ?? 120)
            : $rc_settings['interval_max_in_minutes'];

        // default fallback values in case those are not set on config table
        return [
            'rc_steps' => $configs['reality-check.steps'] ?? 15,
            'rc_min_interval' => $configs['reality-check.min.interval'] ?? 15,
            'rc_max_interval' => $rc_max,
            'rc_default_interval' => $rc_default_interval,
        ];
    }

    /**
     * Retrieves the time interval since the player was registered in the following way
     * [
     *  'days' => 30,
     *  'hours' => 2,
     *  'minutes' => 26
     * ]
     *
     * @param DBUser|string|int $user
     * @return array
     */
    public function getTimeSinceRegistration($user): array
    {
        $register_date = phive()->hisNow();

        if (!$user instanceof DBUser) {
            $user = cu($user);
        }

        if ($user) {
            $register_date = $user->getSetting('registration_end_date');

            if (empty($register_date)) {
                $register_date = $user->data['register_date'];
            }
        }

        return phive()->timeIntervalArr(null, $register_date, phive()->hisNow());
    }

    /**
     * Retrieve the amount of days given to the player to provide his/her documents in order to be verified
     * This is the base method and it needs to be overridden in the specific License class
     *
     * @return int
     */
    public function getDaysToProvideDocuments()
    {
        return $this->getLicSetting('days_to_provide_documents');
    }

    /**
     * Retrieves the time left for the user to upload his documents before he gets blocked
     * E.g.
     * [
     *  'days' => 30,
     *  'hours' => 2,
     *  'minutes' => 26
     * ]
     *
     * @param DBUser $user
     * @return array|null
     */
    public function getTimeLeftToUploadDocuments(DBUser $user)
    {
        $days_to_provide_documents = $this->getDaysToProvideDocuments();

        if ($days_to_provide_documents) {
            $date_diff = $this->getTimeSinceRegistration($user);
            return [
                'days' => $days_to_provide_documents - 1 - $date_diff['days'],
                'hours' => 24 - $date_diff['hours'],
                'minutes' => 60 - $date_diff['mins']
            ];
        }

        return null;
    }

    /**
     * Get needed data to display details on user profile page
     *
     * @param DBUser $user
     *
     * @return array
     */
    public function getPrintDetailsData(DBUser $user): array
    {
        $value = $user->data;
        foreach ($value as $key => $data) {
            $sanitizedValue[$key] = html_entity_decode($data, ENT_QUOTES|ENT_XHTML); //decode profile fields
        }
        return $sanitizedValue;
    }

    /**
     * Get list of fields to display on user profile page
     *
     * @return string[]
     */
    public function getPrintDetailsFields(): array
    {
        return ['firstname', 'lastname', 'address', 'zipcode', 'city', 'country', 'dob', 'mobile', 'email', 'last_login', 'register_date'];
    }

    /**
     * @param string|null $zipcode
     * @return string|null
     */
    public function formatZipcode(string $zipcode = null)
    {
        return trim($zipcode);
    }

    /**
     * Save new or changed user's data into `users_changes_stats`
     *
     * @param int $user_id
     * @param array $user_new_values
     * @param array $user_old_values
     */
    public function onUserCreatedOrUpdated(int $user_id, array $user_new_values , array $user_old_values = []): void
    {
        if (empty($user_id) || empty($user_new_values)) {
            $user = cu($user_id);
            phive()->dumpTbl(
                'error_empty_param',
                ['method' => 'onUserCreatedOrUpdated', 'params' => compact('user_id', 'user_new_values', 'user_old_values')],
                $user ? $user->getId() : $user_id
            );

            return;
        }
        $changes = [];

        foreach ($user_new_values as $field_name => $post_value) {
            // values shouldn't be null (can't save null into the table `users_changes_stats`)
            $pre_value = (string) ($user_old_values[$field_name] ?? '');
            $post_value = (string) $post_value;

            if (empty($field_name) || !is_string($field_name)) {
                phive()->dumpTbl(
                    'error_wrong_param',
                    ['method' => 'onUserCreatedOrUpdated', 'params' => compact('user_id', 'user_new_values', 'user_old_values', 'field_name')],
                    cu()->getId()
                );

                continue;
            }

            // Store only changed values. Do not store empty values for new users
            if ($pre_value === $post_value || isset(static::EXCLUDE_FIELDS_USERS_CHANGES_STATS[$field_name])) {
                continue;
            }

            $changes[$field_name] = ['from' => $pre_value, 'to' => $post_value];

            $to_insert = [
                'user_id' => $user_id,
                'type' => $field_name,
                'pre_value' => $pre_value,
                'post_value' => $post_value,
            ];

            phive("SQL")->sh($user_id)->insertArray('users_changes_stats', $to_insert);
        }

        if ($changes) {
            $this->addRecordToHistory(
                'user_updated',
                new UserUpdateHistoryMessage([
                    'user_id'           => (int) $user_id,
                    'changes'           => $changes,
                    'event_timestamp'   => time(),
                ])
            );
        }
    }

    /**
     * Get available banner jurisdictions
     *
     * @return array
     */
    public function getBannerJurisdictions(): array
    {
        $jurisdictions = phive('Config')->valAsArray('jurisdictions', 'banner_jurisdictions');
        if (empty($jurisdictions)) {
            $jurisdictions = [];
        }
        return $jurisdictions;
    }

    /**
     * Control the language to which to translate for specific Jurisdiction
     * For example: ES users must see DGOJ language content when available and fallback to ES otherwise
     *
     * @return mixed
     */
    public function getDomainLanguageOverwrite()
    {
        return $this->getLicSetting('domain_language_overwrite');
    }

    /**
     * Get count of days for self-lock cool-off period
     *
     * @return int
     */
    public function getSelfLockCoolOffDays(): int
    {
        return static::SELF_LOCK_COOL_OFF_DAYS;
    }

    /**
     * Optionally returns a jurisdiction override indicating whether this document tag should be displayed or not.
     * This is a temporary hack to override 'do_kyc' for a specific psp based on jurisdiction. At some point we need a better solution.
     *
     * @param $user
     * @param string|null $document_tag
     * @return bool|null NULL if there is no override, TRUE if it MUST be shown, FALSE if it must NOT be shown.
     */
    public function getDisplayDocumentOverride($user, ?string $document_tag): ?bool
    {
        return null;
    }

    /**
     * Set user monthly Net Deposit Limit
     *
     * @param DBUser $user
     * @param int $net_deposit_limit_month
     */
    public function setNetDepositMonthLimit(DBUser $user, int $net_deposit_limit_month)
    {
        $limits = rgLimits()->getByTypeUser($user, 'net_deposit');
        $default_net_deposit_limit_month = licSetting('net_deposit_limit', $user)['month'] ?? 0;

        if (!rgLimits()->hasLimits($user, 'net_deposit')) {
            rgLimits()->addLimit($user, 'net_deposit', 'month', $net_deposit_limit_month);
        } else {
            foreach ($limits as $limit) {
                if($limit['time_span'] == 'month') {
                    rgLimits()->changeLimit($user, 'net_deposit', $net_deposit_limit_month, $limit['time_span']);
                }
            }
        }

        if ($net_deposit_limit_month != $default_net_deposit_limit_month) {
            phive("UserHandler")->logAction(
                $user,
                "Applied modified Net Deposit Limit {$net_deposit_limit_month}",
                "modified-net-deposit-limit-applied"
            );
        }
    }

    /**
     * Jurisdiction specific validations before a deposit.
     *
     * @param DBUser $user The user making the deposit.
     * @param string $deposit_type e.g. 'bank', 'ccard' etc.
     * @param array $request The deposit request parameters.
     * @return string|null The localized error message if validation failed, or null if the validation was successful.
     */
    public function validateDeposit(DBUser $user, string $deposit_type, array $request): ?string
    {
        return null;
    }

    /**
     * Getting the balance on login for given user.
     * In case that user is not passed as a parameter method will take the current user from session.
     * @param $user
     * @return mixed|null
     */
    public function lastLoginBalance($user = null)
    {
        $user = empty($user) ? cu($_SESSION['mg_id']) : $user;
        if($user === false){
            return null;
        }
        $uid = $user->getId();
        $last_login_balance = $user->getSetting('last-login-balance');

        return $last_login_balance;
    }

    /**
     * Handle common on login logic for all jurisdictions.
     *
     * @param DBUser $u_obj
     * @return void
     */
    public function onLogin(DBUser $u_obj)
    {
        if (licSetting('show_last_login_balance', $u_obj)) {
            $u_obj->setSetting('last-login-balance', $u_obj->getBalance());
        }
        $this->addNationalitySetting($u_obj);
        $this->showOccupationalPopup($u_obj);
    }

    /**
     * @return ExternalKyc
     */
    protected function getExternalKyc(): ExternalKyc
    {
        return phive("DBUserHandler/ExternalKyc");
    }
    /**
     * Get verification modal url
     *
     * @param bool $mobile
     * @param bool $with_mobile_prefix
     *
     * @return string
     */
    public function getVerificationModalUrl(bool $mobile = false, bool $with_mobile_prefix = false): string
    {
        return $mobile ? ($with_mobile_prefix ? '/mobile/rg-verify' : 'rg-verify')
            : '?rg_login_info=verify';
    }

    /**
     * We hide loss limit
     *
     * @param $type
     * @return bool
     */
    public function hideRgRemoveLimit($type)
    {
        return $type == '';
    }

    /**
     * Get first allowed status that can be set to user
     *
     * @param DBUser|string|int|null $user
     * @param array $ignore_user_settings
     *
     * @return string
     */
    public function getAllowedUserStatus($user, array $ignore_user_settings = []): string
    {
        $user = cu($user);

        if (empty($user) || !$this->isEnabledStatusTracking()) {
            return UserStatus::STATUS_DORMANT;
        }

        $setting_names = [
            'super-blocked', 'unexclude-date', 'unlock-date', 'restrict', 'manual-fraud-flag', 'similar_fraud', 'external-excluded', 'deceased'
        ];

        if (!empty($ignore_user_settings)) {
            $setting_names = array_diff($setting_names, $ignore_user_settings);
        }

        $settings = $user->getSettingsIn($setting_names, true);

        if (!empty($settings['deceased'])) {
            // if user was deceased
            $status = UserStatus::STATUS_DECEASED;
            if (!empty($settings['manual-fraud-flag'])) {
                // if user got also manual fraud flag
                $status = UserStatus::STATUS_UNDER_INVESTIGATION;
            }
        } elseif (!empty($settings['unexclude-date']) && $settings['unexclude-date']['value'] > phive()->hisNow()) {
            // if user was excluded
            $status = UserStatus::STATUS_SELF_EXCLUDED;
        } elseif (!empty($settings['external-excluded'])) {
            // if user is external-excluded
            $status = UserStatus::STATUS_EXTERNALLY_SELF_EXCLUDED;
        } elseif (!empty($settings['super-blocked'])) {
            // if user was super blocked
            $status = UserStatus::STATUS_SUPERBLOCKED;
        } elseif (!empty($settings['unlock-date']) && $settings['unlock-date']['value'] > phive()->hisNow()) {
            // if user was blocked
            $status = UserStatus::STATUS_SELF_LOCKED;
        } elseif (!empty($settings['restrict'])) {
            // if user was restricted
            $status = UserStatus::STATUS_RESTRICTED;
        } elseif (!empty($settings['manual-fraud-flag']) || !empty($settings['similar_fraud'])) {
            // if user got manual fraud flag or similar account fraud
            $status = UserStatus::STATUS_UNDER_INVESTIGATION;
        } elseif ($this->getLicSetting('enabled_user_verifying') && empty($user->getSetting('first_verification_date'))) {
            // if user wasn't verified
            $status = UserStatus::STATUS_PENDING_VERIFICATION;
        } else {
            $status = UserStatus::STATUS_ACTIVE;
        }

        return $status;
    }

    /**
     * @param DBUser|string|int|null $user
     *
     * @return bool
     */
    public function isPlayBlocked($user): bool
    {
        $user = cu($user);

        if (empty($user)) {
            return true;
        }

        return !$user->getData('active') || !empty($user->getSettingsIn(['play_block', 'restrict'], true));
    }

    /**
     * Check user's document status
     *
     * @param $user
     *
     * @return void
     */
    public function onUploadDocument($user): void
    {
        $required_documents_types = $this->getLicSetting('required_documents_types');
        if (!empty($required_documents_types)){
            $this->documentUploadUserVerification($user, $required_documents_types);
        }

    }

    /**
     * Block user If one of required documents is deleted
     *
     * @param        $user
     * @param string $document_type
     *
     * @return void
     */
    public function onDeleteDocument($user, string $document_type): void
    {
        $required_documents_types = $this->getLicSetting('required_documents_types');
        if (!empty($required_documents_types)){
            $this->documentDeleteUserVerification($user, $document_type, $required_documents_types );
        }
    }

    /**
     * @param string $user_status
     *
     * @return bool
     */
    public function isActiveStatus(string $user_status): bool
    {
        return $user_status === UserStatus::STATUS_ACTIVE;
    }

    /**
     * @return bool
     */
    public function isEnabledStatusTracking(): bool
    {
        return (bool) $this->getLicSetting('enable_user_status_tracking');
    }

    /**
     * Send e-mail to the address listed in settings.
     */
    public function onCRUKSServiceDown()
    {
        $bluem_settings = $this->getLicSetting('bluem');

        if (empty($bluem_settings)) {
            return;
        }

        $on_service_down = $bluem_settings['notify_on_service_down'];

        if (!empty($on_service_down['send_email'])) {
            $email_receivers = $on_service_down['email_receiver_lists'];

            $subject = 'Technical error on CRUKS';
            $content = 'Technical error on CRUKS, please escalate to incidents P1 channel on Slack.';

            foreach ($email_receivers as $email_receiver) {
                phive("MailHandler2")->sendMailToEmail(
                    array('content' => $content, 'subject' => $subject),
                    trim($email_receiver)
                );
            }
        }
    }

    /**
     * Currently nothing to do on a general level so we just return true.
     *
     * @param Dbuser $u_obj The user object.
     * @param array $award The award.
     * @param bool $translate.
     *
     * @return string|bool String if failure, true if all good.
     */
    public function handleUseAward($u_obj, $award, bool $translate = true){
        return true;
    }

    /**
     * For now we just return 0 here as there is nothing to do in a general context.
     *
     * @param Dbuser $u_obj The user object.
     * @param array $award The award.
     *
     * @return string|bool String if failure, true if all good.
     */
    public function getAwardExpiryExtension($u_obj, $award){
        return 0;
    }

    /**
     * @param string $topic
     * @param HistoryMessageInterface $data
     * @param string|null $key
     * @param array|null $context
     *
     * @return bool
     */
    public function addRecordToHistory(string $topic, HistoryMessageInterface $data, ?string $key = null, ?array $context = null): bool
    {
        $history = phive('History');
        // Avoid processing $context if module is disabled
        if (!$history->isEnabled()) {
            return true;
        }

        $history_class = get_class($data);

        if(is_null($context)){
            $context = [];
        }

        $data_check = $data->toArray();

        if (isset($data_check['user_id'])) {

            if (!$this->getLicSetting('history_enabled')) {
                return true;
            }

            if (empty($this->user_jurisdiction_cache[$data_check['user_id']])) {
                $licensed_instance = phive('Licensed');
                $jurisdiction_map = $licensed_instance->getSetting('country_by_jurisdiction_map');
                $this->user_jurisdiction_cache[$data_check['user_id']] = $jurisdiction_map[$licensed_instance->getLicCountryProvince(
                        cu($data_check['user_id'])
                    )] ?? $jurisdiction_map['default'];
            }
            $context['_jurisdiction'] = $this->user_jurisdiction_cache[$data_check['user_id']];
        } else {
            $context['_jurisdiction'] = 'all';
        }

        $context['_domain'] = phive()->getSetting('full_domain');
        $context['_brand']  = phive('BrandedConfig')->getBrand();

        $counter = 0;
        $numberOfTrials = 5;

        do {
            try {
                phive()->fire(
                    'history',
                    'HistoryAddRecordToHistory',
                    [$topic, $data_check, $history_class, $key, $context],
                    0,
                    function () use ($topic, $data, $key, $context) {
                        phive('History')->addRecord($topic, $data, $key, $context);
                    }
                );
                return true;

            } catch (SocketException | ConnectionException | KafkaErrorException $ce) {
                $counter++;
                if ($counter === $numberOfTrials) {
                    $dataArr = $data->toArray();
                    $this->logger->error(
                        __METHOD__ . ' ' . $ce->getMessage(),
                        [
                            'topic' => $topic,
                            'data' => $dataArr,
                            'key' => $key,
                            'context' => $context,
                            'exception' => get_class($ce),
                        ]
                    );
                    $history->storeFailedMessage($topic, $dataArr, $context, $history_class);

                    return false;
                }

                sleep($counter);
            } catch (\Exception $e) {
                $this->logger->error(
                    __METHOD__ . ' ' . $e->getMessage(),
                    [
                        'topic' => $topic,
                        'data' => $data->toArray(),
                        'key' => $key,
                        'context' => $context,
                        'exception' => get_class($e),
                    ]
                );

                return false;
            }
        } while ($counter < $numberOfTrials);

        return true;
    }

    /**
     * @return int
     */
    public function getJurisdictionAge(): int
    {
        return $this->getLicSetting('jurisdiction-age');
    }

    /**
     * @return string
     */
    public function getJurisdictionAgePlaceholder(): string
    {
        return $this->getLicSetting('jurisdiction-age-placeholder');
    }

    /**
     * Push to websocket Session Net Winnings,
     * are defined as the total of all winnings sum minus all losses sum since session started.
     * If value is negative should be shown with the negative value.
     *
     * @param $uid
     * @param $ins
     */
    public function gamePlaySession($uid, $ins)
    {
        if ($this->getLicSetting('game_play_session')) {
            // insert_if_missing is false because we don't want to create new users_game_sessions
            // with bet_amount and win_amount = 0 during BOS tournaments.
            $session = phive('Casino')->getGsess($ins, cu($uid), false);
            $net_winnings = nfCents($session['win_amount'] - $session['bet_amount'], true);
            $data = ['net_winnings' => $net_winnings, 'start_time' => $session['start_time']];
            toWs(['session' => $data], 'game-play-session', $session['user_id']);

        }
    }

    /**
     * List of "extra fields" provided during the registration that we need to store for the user
     * The fields will be saved on users_settings. (Ex. see IT or DE)
     * @return array
     */
    public function getUserSettingsFields(): array
    {
        return $this->fields_to_save_into_users_settings;
    }

    /**
     * @return bool
     */
    public function supportIndefiniteLock(): bool
    {
       return !empty($this->getLicSetting('rg_indefinite_support'));
    }


    /**
     * Add a flag in user's setting to force the user to add nationality and place of birth if these data
     * has not been set yet
     * @param $user
     */
    protected function addNationalitySetting($user): void
    {
        $nationalityRequired = $this->getLicSetting('nationality_required');
        $userMissingNationality = $user->hasSetting('nationality_update_required') || !$user->hasSetting('nationality') || empty($user->getSetting('nationality'));
        $depositRequired = $this->getLicSetting('deposit_required_for_nationality_popup');
        $userHasDeposited = $user->hasDeposited();
        $pnpRegistrationInProgress =  isPNP() && !$user->hasCompletedRegistration();

        if($nationalityRequired && $userMissingNationality) {
            if($depositRequired && !$userHasDeposited) {
                return;
            }
            if($pnpRegistrationInProgress) {
                return;
            }
            $user->setSetting('nationality_required', 1);
        }
    }

    /**
     * Add a flag in user's setting to force the user to add nationality and place of birth if these data
     * has not been set yet
     * @param $user
     */
    protected function addNationalityBirthCountrySetting($user): void
    {
        if ($this->getLicSetting('nationality_birth_country_required')) {
            $hasNationalityBirthCountry = count($user->getSettingsIn(['nationality', 'place_of_birth'])) == 2;

            if (!$hasNationalityBirthCountry) {
                $user->setSetting('nationality_birth_country_required', 1);
                $_SESSION['show_add_nationalityandpob_popup'] = true;
            }
        }
    }

    /**
     * Add the session for updating nationality and place of birth if the in user's setting flag has been set
     * and there is no session for that.
     * @param $user
     */
    protected function checkNationalityBirthCountrySession($user): void
    {
        $hasSession = $_SESSION['show_add_nationalityandpob_popup'] ?? null;

        if (!$hasSession && $this->getLicSetting('nationality_birth_country_required')) {
            if ($user->getSetting('nationality_birth_country_required')) {
                $_SESSION['show_add_nationalityandpob_popup'] = true;
            }
        }
    }

    /**
     * Returns the list of values from @see getColsForDailyStats() that count as bonus/rewards
     * @return string[]
     */
    public function getAdditionalReportingBonusTypes(): array
    {
        return [];
    }

    /**
     * @param DBUser $user
     *
     * @return string
     */
    public function getExceededBalanceLimitWsUrl(DBUser $user): string
    {
        return '';
    }

    /**
     * @API
     *
     * Retrieve data for additional register button on step 1 for different jurisdictions (MitID)
     *
     * @return array|null
     */
    public function getRegistrationSecondButton(): ?array
    {
        $iso = licJur();
        $file = moduleFile('Licensed', '_register_second_button_data', $iso);

        if($file === '') {
            return null;
        }

        /** @var array $data */
        $data = require_once $file;

        if(! is_array($data)) {
            return null;
        }

        return $data;
    }


    /**
     * Detect if registration is still pending
     * Then trigger user details popup
     *
     * @param $user
     * @return bool
     */

    public function pnpRegistrationInProgress(DBUser $user = null): bool {
        $user = $user ?? cu();

        if (!isLogged() || !isPNP()) {
            return false;
        }

        return $user->hasSetting('registration_in_progress');
    }

    /**
     * Check if upload form should be displayed for the cross brand document based on it's status
     *
     * @param $tag string the document tag
     * @param $status string the document status
     * @return bool
     */
    public function shouldDisplayUploadFormForCrossBrandDocument($tag, $status): bool
    {
        $docs_to_show_upload_form = $this->getLicSetting('cross_brand')['document_status_with_upload_form'];
        if (empty($docs_to_show_upload_form)) {
            return false;
        }

        if (!isset($docs_to_show_upload_form[$tag]) || !in_array($status, $docs_to_show_upload_form[$tag])) {
            return false;
        }

        return true;
    }

    /**
     * Get the list of settings for documents to set only on remote.
     *
     * @return array
     */
    public function getDocumentsSettingToSetOnlyOnRemote(): array
    {
        $setting = $this->getLicSetting('cross_brand');
        return (!is_null($setting) && $setting['documents_settings_to_set_only_on_remote']) ? $setting['documents_settings_to_set_only_on_remote'] : [];
    }

    /**
     * Get the list of documents to synchronize.
     *
     * @return array
     */
    public function getDocumentsToSync(): array
    {
        $setting = $this->getLicSetting('cross_brand');
        return (!is_null($setting) && $setting['documents_to_sync']) ? $setting['documents_to_sync'] : [];
    }

    /**
     * Get the required documents types for the user.
     *
     * @return array
     */
    public function getRequiredDocumentsTypes(): array
    {
        $setting = $this->getLicSetting('cross_brand');

        return !is_null($setting) && !empty($setting['required_documents_types_cdd'])
            ? $setting['required_documents_types_cdd']
            : ($this->getLicSetting('required_documents_types_cdd') ?? []);
    }

    /**
     * @return bool
     */
    public function isCddEnabled(): bool
    {
        $setting = $this->getLicSetting('cross_brand');
        return (!is_null($setting) && ($setting['is_cdd_enabled'] ?? false));
    }

    /**
     * Returns the maximum loss limit value from config in customer's currency.
     * The loss limit configuration value is stored in Euro and converted on the fly to the client's currency.
     * If value is eq to NDL (case-insensitive) then returns default NDL limit for the license
     *
     * @param DBUser $user
     *
     * @return int|null
     */
    public function getHighestAllowedLossLimit(DBUser $user): ?int
    {
        $config = phive('SQL')->lb()->loadAssoc(
            phive('Config')->getSelect()."config_name = 'responsible-gambling' AND config_tag = 'loss-limits'"
        );
        $config_value = phive('Config')->getValueFromTemplate($config);
        $limit = $config_value[$user->getJurisdiction()] ?? null;

        if (strcasecmp($limit, 'NDL') === 0) {
            $rgl = RgLimits()->getLimit($user, 'net_deposit', 'month');
            $limit = $rgl['cur_lim'] ?? lic('getNetDepositMonthLimit', [$user], $user);
        }

        if (empty($limit) || !is_numeric($limit)) {
            return null;
        }

        $mod = phive("Currencer")->getCurrency($user->getCurrency())['mod'] ?? 1;

        return abs($limit) * $mod;
    }

    /**
     * @return bool
     */
    public function isLegacyBoosterEnabled(): bool
    {
        return (bool) phive('Licensed')->getSetting('enable_legacy_booster');
    }

    /**
     * Get information about queued RG popups for the specified user.
     *
     * @param DBUser $user
     *
     * @return array
     */
    public function getQueuedRGPopupInfo(DBUser $user): array
    {
        $setting = $this->getLicSetting('rg-trigger-popups') ?? [];
        $setting_string = "'" . implode("','", $setting) . "'";
        $date = Carbon::now()->subDay()->toDateString();
        $last_login_at = $user->data['last_login'];
        $latest_popup_shown_at = phive("SQL")->sh($user)
            ->loadArray("SELECT * FROM users_settings WHERE user_id = '{$user->getId()}' AND setting LIKE 'popup-shown-%' ORDER BY created_at DESC LIMIT 1;");

        if (! empty($latest_popup_shown_at[0]['value'])) {
            $popups_interval_in_minutes = (int)phive('Config')->getValue('RG', 'popupsInterval', 60);
            $triggers_after = Carbon::parse($latest_popup_shown_at[0]['value'])->addMinutes($popups_interval_in_minutes)->toDateTimeString();
        } else {
            $triggers_after = $last_login_at;
        }

        $result = phive("SQL")->sh($user)
            ->loadArray("SELECT tl.user_id, tl.created_at, us.value as ticked, tl.trigger_name FROM triggers_log tl
                LEFT JOIN users_settings us ON(us.user_id = tl.user_id AND us.setting = CONCAT('popup-shown-', tl.trigger_name))
                WHERE tl.trigger_name IN($setting_string)
                AND tl.user_id = '{$user->getId()}'
                AND tl.created_at >= '{$triggers_after}'
                AND (us.value IS NULL OR date(us.value) < '{$date}')
                ORDER BY tl.created_at LIMIT 1");

        return $result[0] ?? [];
    }

    public function isRGPopupEnabled(): bool
    {
        return $this->getLicSetting('rg-popup-enabled') ?? false;
    }

    /**
     * @return bool
     */
    public function isForgotPasswordUpgradeEnabled(): bool
    {
        return (bool) $this->getLicSetting('forgot_password_upgrade_enabled');
    }

    /**
     * Return only allowed fields
     *
     * @param array $fields
     * @return array
     */
    public function adjustField(array $fields): array
    {
        $field_list = $this->getAllowedFieldList();

        return array_filter(
            $fields,
            function ($key) use ($field_list) {
                return in_array($key, $field_list);
            },
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Insert all allowed fields
     *
     * @return array
     */
    public function getAllowedFieldList(): array
    {
        return array_merge(... array_values($this->registrationStep2Fields()));
    }


    /**
     * Checks if the user block should be removed after external self exclusion end
     * In order for the block to be removed, we check if the user was blocked prior to externally self excluding
     * by checking the `external-exclusion-block` action log and comparing it timestamp to the external self exclusion
     * @param $user
     * @return bool
     */
    public function shouldRemoveUserBlockAfterExternalSelfExclusionEnds($user): bool
    {
        $external_excluded = $user->getSettingsByRegex('external-excluded')[0];

        if (!phive('UserHandler')->getActionByTagUidCreatedAt('external-exclusion-block', $user->getId(), $external_excluded['created_at'])) {
            return true;
        }

        return false;
    }

    /**
     * @param string $trigger_name
     * @param DBUser $user
     *
     * @return void
     */
    public function triggerGrsRecalculation(string $trigger_name, DBUser $user): void
    {
        if (in_array($trigger_name, $this->getLicSetting('triggers_of_grs_recalculation'), true)) {
            $type = str_starts_with($trigger_name, 'AML') ? 'AML' : 'RG';
            $this->getSitePublisher()
                ->fire(
                        'grs',
                        'Cashier/Arf',
                        'calculateScoreAPI',
                        [$user->getId(), $type]
                );
        }
    }

    function showOccupationalPopup($u_obj) {
        if( $this->getLicSetting('occupation_popup_required_in_login')
            && $this->hasViewedResponsibleGaming($u_obj)
            && !$this->hasViewedOccupationPopup($u_obj)) {
            $u_obj->setSetting('show_occupation_popup', 1);
        }
    }

    /**
     * @param DBUser $user
     * @param string $restriction_reason
     *
     * @return void
     */
    public function onUserRestricted(DBUser $user, string $restriction_reason): void
    {
        if ($restriction_reason === DBUserRestriction::CDD_CHECK) {
            $user->setSetting('cdd_withdrawal_block', '1');
        }
    }

    /**
     * Returns the user's current monthly net deposit limit
     * or the default license's NDL
     *
     * @param DBUser $user
     * @return mixed
     */
    public function getUserMonthNetDepositLimit(DBUser $user)
    {
        $rg_ndl = rgLimits()->getLimit($user, rgLimits()::TYPE_NET_DEPOSIT, 'month');
        return !empty($rg_ndl) ? $rg_ndl['cur_lim'] : lic('getNetDepositMonthLimit', [$user], $user);
    }

    /**
     * Updates the user's monthly net deposit limit to a new value
     *
     * @param DBUser $user
     * @param float|int $new_ndl
     * @param string $tag
     * @return void
     */
    public function updateUserMonthNetDepositLimit(DBUser $user, $new_ndl, string $tag)
    {
        $net_deposit_limit = lic('getNetDepositMonthLimit', [$user, $new_ndl], $user);
        $user->refreshSetting('original_net_deposit_limit', $new_ndl);
        $whole_amount = nf2($net_deposit_limit, true, 100);
        $user_currency = $user->getCurrency();
        if (!rgLimits()->hasLimits($user, 'net_deposit')) {
            $change_limit_result = rgLimits()->addLimit($user, 'net_deposit', 'month', $net_deposit_limit);
        } else {
            $limits = rgLimits()->getByTypeUser($user, 'net_deposit');
            foreach ($limits as $limit) {
                if ($limit['cur_lim'] > $new_ndl && $limit['time_span'] == 'month') {
                    $change_limit_result = rgLimits()->changeLimit($user, 'net_deposit', $net_deposit_limit, $limit['time_span']);
                    break;
                }
            }
        }

        $action_description = ($change_limit_result ?
            "Monthly net-deposit limit updated to $whole_amount $user_currency" : "Could not update monthly net-deposit limit");
        phive("UserHandler")->logAction($user, $action_description, $tag);
    }

    /**
     * Hide registration banner links
     *
     * @return bool
     */
    public function hideRegistrationBannerLinks(): bool
    {
        return (bool) phive('Licensed')->getLicSetting('hide_registration_banner_links');
    }

    /**
     * If SOWd NDL is lower than user NDL, update the NDL to SOWd NDL
     *
     * @param DBUser $user
     * @param string $sow_annual_income The annual income declared in Source of Wealth
     * @return void
     */
    public function updateNDLBasedOnSOWd(DBUser $user, string $sow_annual_income): void
    {
        $user_id = $user->getId();
        $users_currency = $user->getCurrency();
        $action_log_tag = 'SOWd_affordability_ndl_comparison';

        $sow_ndl = phive('DBUserHandler/SourceOfFunds')->getCNDLfromSOWIncomeRange($sow_annual_income, $user);
        if ($sow_ndl === -1 || empty($sow_ndl)) {
            return;
        }

        // if the NDL was set by admin from Back office, don't do anything
        $ndl_action_log = phive('SQL')->sh($user_id)->loadAssoc("
                SELECT actor
                FROM actions
                WHERE target = '{$user_id}'
                AND tag = 'net_deposit'
                AND descr like 'month limit updated, new limit is%'
                order by id desc
                limit 1
            ");

        if (!empty($ndl_action_log) && $ndl_action_log['actor'] != $user_id && $ndl_action_log['actor'] != uid('system')) {
            phive('UserHandler')->logAction(
                $user_id,
                "NDL will not be updated since it was set by admin from backoffice",
                $action_log_tag);
            return;
        }

        $old_ndl = lic('getUserMonthNetDepositLimit', [$user], $user);
        $action_description = "user's NDL. User's NDL: {$old_ndl}.";

        $user_ndl = $old_ndl;
        if ($user_affordability_data = lic('getUserNDLFromAffordabilityCheck', [$user], $user)) {
            if ($user_affordability_data['cndl'] != -1) {
                $user_ndl = $user_affordability_data['cndl'];
                $action_description = $user_affordability_data['description'];
            }
        }

        if ($sow_ndl >= $user_ndl || $sow_ndl >= $old_ndl) {
            phive('UserHandler')->logAction(
                $user_id,
                "NDL will not be updated since SOWd NDL is same or higher",
                $action_log_tag);
            return;
        }

        $new_ndl = $sow_ndl;

        lic('updateUserMonthNetDepositLimit', [$user, $new_ndl, $action_log_tag], $user);

        $formatted_amount_with_currency_new_ndl = phive('Currencer')->formatCurrency($new_ndl, $users_currency);
        $formatted_amount_with_currency_old_ndl = phive('Currencer')->formatCurrency($old_ndl, $users_currency);

        phive('UserHandler')->logAction(
            $user_id,
            "NDL of user {$user_id} updated from {$old_ndl} to {$new_ndl} due to mismatch between SOWd limit and $action_description SOWd range:$sow_annual_income",
            $action_log_tag);

        lic('addCommentOnSowdNdlUpdate', [$user, $formatted_amount_with_currency_new_ndl, $formatted_amount_with_currency_old_ndl], $user);

        $comment = "We lowered customers loss limit to {$formatted_amount_with_currency_new_ndl} from {$formatted_amount_with_currency_old_ndl} due to the affordability information we received via the SOWd.";
        $user->addComment($comment, 0, 'rg-action');
    }

    /**
     * Send GameVersionUpdateHistoryMessage messages with games version everyday. Called once per day via cron
     *
     * @param string $country
     * @return void
     */
    public function onEveryDayGameVersion(string $country = 'all'): void
    {
        $where = ($country === 'all') ? "1 = 1" : "gcv.country = '{$country}'";
        $query = "SELECT
                        gcv.game_id,
                        gcv.game_version,
                        gcv.rng_version,
                        gcv.country
                    FROM
                        game_country_versions gcv
                    WHERE
                    {$where}
                    GROUP BY
                        gcv.game_id,
                        gcv.game_version,
                        gcv.rng_version,
                        gcv.country;";
        $dga_game_versions = phive('SQL')->loadArray($query);

        foreach ($dga_game_versions as $record) {
            $data = [
                'game_id'         => (int)$record['game_id'],
                'game_version'    => [
                    $record['country'] => $record['game_version']
                ],
                'rng_version'     => [
                    $record['country'] => $record['rng_version']
                ],
                'event_timestamp' => time(),
            ];

            try {
                $history_message = new GameVersionUpdateHistoryMessage($data);
                phive('Licensed')->addRecordToHistory(
                    'game_version_update',
                    $history_message
                );
            } catch (InvalidMessageDataException $exception) {
                phive('Logger')
                    ->getLogger('history_message')
                    ->error("Invalid message data exception on cron", [
                        'report_type' => 'game_version_update',
                        'args' => $data,
                        'validation_errors' => $exception->getErrors()
                    ]);
            }
        }
    }

    /**
     * @param string $category
     * @return bool
     */
    public function isGameCategoryExcluded(string $category): bool
    {
        $excluded_game_categories = $this->getLicSetting('excluded_game_categories') ?? [];

        return in_array($category, $excluded_game_categories, true);
    }
    /**
     * Expires documents and updates user verification status.
     *
     * This method uses a two-step approach:
     * 1. First, expire all documents that are due for expiration
     * 2. Then, get user IDs with pagination and unverify users
     *
     * Called once per day via a cron job.
     *
     * @return void
     */
    public function expireAndUnVerify(): void
    {
        $tag = 'idcard-pic'; // Default tag, can be made configurable

        // Step 1: Expire all documents that are due for expiration
        // Note: client_id will be automatically set by middleware based on API key
        $expire_result = phive('Dmapi')->expireDocuments($tag);

        if (empty($expire_result['success'])) {
            phive('Logger')->error("Failed to expire documents", ['result' => $expire_result]);
            return;
        }
        
        phive('Logger')->info("Documents expired successfully", ['result' => $expire_result['success'] , 'expired_count' => $expire_result['expired_count'] ]);

        // Step 2: Get user IDs with pagination and unverify users
        $page = 1;
        $perPage = 500; // Process more users per page for efficiency
        $processedCount = 0;

        do {
            // Get user IDs with pagination
            $result = phive('Dmapi')->getExpiredUserIds($page, $perPage);
            if (empty($result['success']) || empty($result['user_ids'])) {
                phive('Logger')->info("No more user IDs to process", [
                    'page' => $page,
                    'result' => $result
                ]);
                break;
            }
            // Process user IDs returned from DMAPI
            foreach ($result['user_ids'] as $userId) {
                $user = cu($userId);
                if (!$user) {
                    phive('Logger')->error("User not found for document expiration", ['user_id' => $userId]);
                    continue;
                }
                if ($user->isVerified()) {
                    $user->unVerify();
                    phive('Logger')->info("User unverification completed", [
                        'user_id' => $userId,
                    ]);
                }
                $processedCount++;
              
            }

            // Check pagination metadata to determine if there are more pages
            $hasMorePages = false;
            if (isset($result['pagination'])) {
                $hasMorePages = $result['pagination']['has_next_page'] ?? false;
            }

            $page++;

        } while ($hasMorePages);

        if ($processedCount > 0) {
            phive('Logger')->info("Completed processing expired documents", [
                'total_processed' => $processedCount,
                'pages_processed' => $page - 1,
                'total_documents_expired' => $expire_result['expired_count'] ?? 0
            ]);
        }
    }

    /**
     * @param $type
     * @return bool
     */
    public function shouldSyncLimit($type): bool
    {
        $cross_brand_settings = $this->getLicSetting('cross_brand');

        return $cross_brand_settings && in_array($type, $cross_brand_settings['rg_limits_to_sync'] ?? []);
    }

    /** If db config 'global-customer-net-deposit' has 'NDT' or integer value (in cents) for jurisdiction then:
     * - check 'net_deposit' month limit as a threshold
     * - if it has no 'net_deposit' then take default lic 'net_deposit_limit' 'month'
     *
     * @param array  $new_limits
     * @param DBUser $user
     *
     * @return array|null
     */
    public function checkHighestAllowedCustomerNetDepositLimit(array $new_limits, DBUser $user): ?array
    {
        $config = phive('Config')->getByNameAndTag('global-customer-net-deposit', 'RG');
        $global_customer_net_deposit_limits = phive('Config')->getValueFromTemplate($config);
        $jurisdiction = $user->getJurisdiction();
        $should_convert_currency = false;

        if (empty($global_customer_net_deposit_limits[$jurisdiction])) {
            return null;
        }
        $highest_allowed_limit = $global_customer_net_deposit_limits[$jurisdiction];
        $limit_type = rgLimits()::TYPE_CUSTOMER_NET_DEPOSIT;
        $filtered_limits = array_filter($new_limits, function ($limit) use ($limit_type) {
            return $limit['type'] === $limit_type;
        });

        if ($highest_allowed_limit === 'NDT') {
            $limits = rgLimits()->getByTypeUser($user, rgLimits()::TYPE_NET_DEPOSIT);

            if (!empty($limits['month']['cur_lim'])) {
                $threshold = (int)$limits['month']['cur_lim'];
            } else {
                $threshold = (int)$this->getLicSetting('net_deposit_limit')['month'];
                $should_convert_currency = true;
            }

            if (empty($threshold)) {
                return null;
            }

        } elseif (is_numeric($highest_allowed_limit)) {
            $threshold = (int)$highest_allowed_limit;
            $should_convert_currency = true;
        } else {
            return null;
        }

        foreach ($filtered_limits as $new_limit) {
            $customer_net_deposit_in_cents = $new_limit['limit'] * 100;

            if ($should_convert_currency) {
                $customer_net_deposit_in_cents = mc($customer_net_deposit_in_cents, $user, 'div');
            }
            if ($customer_net_deposit_in_cents > $threshold) {
                return [
                    'success' => 'nok',
                    'msg' => 'show-customer-net-deposit-limit-message'
                ];
            }
        }

        return null;
    }

    /**
     * @param $type
     * @return bool
     */
    public function shouldSyncLimitOnRegistration($type): bool
    {
        $cross_brand_settings = $this->getLicSetting('cross_brand');

        return $cross_brand_settings && in_array($type, $cross_brand_settings['rg_limits_to_sync_on_registration'] ?? []);
    }

    /**
     * Get maxlength HTML attribute for input fields
     * @param string $field Field name
     * @return string HTML attribute or empty string
     */
    public static function getMaxLengthAttribute($field) {
        if ($field === 'email') {
            $length = licSetting('email_length_restrictions');
            if (!is_null($length)) {
                return 'maxlength="' . $length . '"';
            }
        }
        return '';
    }

    /**
     * Responsibility:
     * - notify customers which ignored (has not interacted with) a RG popup within current day
     * - notification time defined in db config 'notify-customers-on-ignored-rg-popup' per jurisdiction in format H or H:i
     * - starts RG evaluation process if it's allowed by 'RG{X}-evaluation-in-jurisdictions' db config/
     *
     * Involved configs:
     * - lic global config 'jurisdiction_timezones'
     * - lic setting 'notify-on-ignored-rg-popups'
     * - db config 'notify-customers-on-ignored-rg-popup'
     * - db config 'RG{X}-evaluation-in-jurisdictions'
     *
     * @return void
     */
    public function notifyCustomersOnIgnoredRgPopup(): void
    {
        $alternative_time_format = 'H:i';
        $jurisdiction_timezones = phive('Licensed')->getSetting('jurisdiction_timezones');
        $allowed_jurisdictions = phive('Config')->valAsArray('mails', "notify-customers-on-ignored-rg-popup", ';', ':');

        foreach ($allowed_jurisdictions as $jurisdiction => $hour) {
            if (is_numeric($hour)) {
                $hour = (int)$hour;
            } else {
                if (empty($hour)) {
                    continue;
                }

                try {
                    $dt = Carbon::createFromFormat($alternative_time_format, $hour);

                    if ($dt->format($alternative_time_format) === $hour) {
                        $hour = $dt->hour;
                    }
                } catch (InvalidFormatException $e){
                    error_log(
                            $e->getMessage() .
                            ": config 'notify-customers-on-ignored-rg-popup' has wrong time format {$hour} for jurisdiction {$jurisdiction}. " .
                            "Allowed formats H or H:i"
                    );
                    continue;
                }
            }
            $jurisdiction_timezone = $jurisdiction_timezones[$jurisdiction] ?? $jurisdiction_timezones['default'];
            $jurisdiction_local_time = Carbon::now($jurisdiction_timezone);

            if ($jurisdiction_local_time->hour !== $hour) {
                continue;
            }
            $start_timestamp = Carbon::now()->subHours(24)->toDateTimeString();
            $query = "WITH ranked_triggers AS (
                    SELECT
                        a1.id,
                        a1.target as user_id,
                        a1.created_at,
                        rprl.rating,
                        rprl.rating_tag,
                        SUBSTRING_INDEX(SUBSTRING_INDEX(a1.descr, ' ', 1), '.', 1) AS trigger_name,
                        ROW_NUMBER() OVER (PARTITION BY a1.target ORDER BY rprl.rating DESC, a1.created_at ASC) AS rn
                    FROM actions a1
                    LEFT JOIN actions a2
                        ON a1.target = a2.target
                        AND a2.tag = 'automatic-flags'
                        AND DATE(a2.created_at) = CURDATE()
                        AND SUBSTRING_INDEX(SUBSTRING_INDEX(a1.descr, ' ', 1), '.', 1) =
                            SUBSTRING_INDEX(SUBSTRING_INDEX(a2.descr, ' for ', -1), ' popup.', 1)
                        AND a2.descr LIKE 'Customer clicked % for % popup%'
                        AND a2.created_at > a1.created_at
                    LEFT JOIN risk_profile_rating_log AS rprl
                        ON rprl.id = (
                            SELECT id
                            FROM risk_profile_rating_log rprl2
                            WHERE rprl2.user_id = a1.target
                            AND rprl2.rating_type = 'RG'
                            AND rprl2.created_at <= a1.created_at
                            ORDER BY rprl2.created_at DESC
                            LIMIT 1
                        )
                    JOIN users_settings AS us
                        ON us.user_id = a1.target
                        AND us.setting = 'jurisdiction'
                        AND us.value = '{$jurisdiction}'
                    WHERE a1.descr LIKE '%Popup shown to customer%'
                    AND a1.tag = 'automatic-flags'
                    AND a1.created_at > '{$start_timestamp}'
                    AND a2.id IS NULL
                )
                SELECT
                    id,
                    user_id,
                    created_at,
                    rating,
                    rating_tag,
                    trigger_name
                FROM ranked_triggers
                WHERE rn = 1
                ORDER BY rating DESC;";

            $users_to_notify = phive('SQL')->shs()->loadArray($query);
            $keep_it_fun_mail = phive('MailHandler2')->getSetting('keep_it_fun_mail');
            $mix_variables['__BRAND_NAME__'] = ucfirst(phive('BrandedConfig')->getBrand());

            foreach ($users_to_notify as $row) {
                $user = cu($row['user_id']);
                $triggers = lic('getLicSetting', ['notify-on-ignored-rg-popups'], $user);

                if (!in_array($row['trigger_name'], $triggers, true)) {
                    continue;
                }

                $mix_variables["__USERNAME__"] = $user->getFirstName();
                $dynamicVariablesSupplier = new DynamicVariablesSupplierResolver($user);
                $replacers = $dynamicVariablesSupplier->resolve($row['trigger_name'])->getRgWarningEmailVariables($mix_variables);
                $mail_handler = phive('MailHandler2');
                $mail_handler->skipConsent();

                try {
                    $mail_handler->sendMail(
                        "{$row['trigger_name']}.popup.ignored",
                        $user,
                        $replacers,
                        null,
                        $keep_it_fun_mail
                    );
                } catch (Exception $e) {
                    error_log(
                        $e->getMessage() .
                        ":  mail sending on trigger '{$row['trigger_name']}.popup.ignored' failed"
                    );
                }

                $user->addComment("The player triggered the following flag {$row['trigger_name']} with risk score of {$row['rating_tag']}. " .
                    "No popup was able to be displayed. " .
                    "We have instead sent an interaction over email with the template of flag {$row['trigger_name']}",
                    0,
                    'rg-evaluation'
                );

                phive('RgEvaluation/RgEvaluation')->startEvaluation($user, $row['trigger_name']);
            }
        }
    }

    /**
     * show sponsorship logos
     *
     * @return bool
     */
    public function getSponsorshipLogos(): bool
    {
        return false;
    }
}

/**
 * Wrapper for running ISO2/html/file.php view logic.
 *
 * NOTE that this does not support running default Licensed/html/file.php logic in case the ISO2/html folder
 * does not contain the wanted file, this might in fact be unwanted behaviour in some cases so a potential refactoring
 * needs to support both scenarios.
 *
 * @param $file string The file to load.
 * @param $u_obj DBUser the user object.
 * @param $return bool Return if true, otherwise not.
 * @param $override array Optional override that will use one jurisdiction for another, ex: ['DE' => 'GB'] to display the UKGC logo for Germans.
 *
 * @return mixed Potential HTML to be returned if that is the intention.
 */
function licHtml($file, $u_obj = null, $return = false, $override = [])
{
    $iso = licJur($u_obj);
    $iso = $override[$iso] ?? $iso;

    // If iso module does not exist, clear $iso to use the default module
    $moduleFile = moduleFile('Licensed', $file, $iso);
    $iso = empty($moduleFile) ? null : $iso;

    if ($return) {
        return moduleHtml('Licensed', $file, true, $iso);
    }

    moduleHtml('Licensed', $file, false, $iso);
}
