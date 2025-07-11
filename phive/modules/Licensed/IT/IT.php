<?php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../Micro/traits/ExternalGameSessionTrait.php';

use IT\Services\AAMSSession\Participation;
use IT\Pacg\Codes\ReturnCode as PacgReturnCode;
use IT\Services\AccountLimitService;
use IT\Services\AccountTransactionService;
use IT\Services\BonusCancellationService;
use IT\Services\TrasversalSelfExclusionService;
use IT\Services\PlayerService;
use IT\Services\TaxCodeService;
use IT\Services\AAMSSession\AAMSSessionService;
use IT\Services\GameExecutionCommunicationService;
use IT\Services\GameSessionsAlignmentCommunicationService;
use IT\Pacg\PacgTrait;
use IT\Pgda\PgdaTrait;
use IT\Traits\ServicesTrait;
use Videoslots\Menu\Boxes\MobileMenu\Element\MobileBalanceTableData;
use Videoslots\Menu\Boxes\MobileMenu\Formatter\MobileBalanceTableElementFormatter;

/**
 * Class IT
 * @package IT
 */
class IT extends Licensed
{
    use ExternalGameSessionTrait;
    use PacgTrait;
    use PgdaTrait;
    use ServicesTrait;

    public const FORCED_LANGUAGE = 'it';

    public string $ext_exclusion_name = 'sogei';

    /**
     * IT specific language configuration
     *
     * @var array
     */
    private $config;

    /**
     * @var string
     */
    private static $extra_classes;

    /**
     * @var string
     */
    private static $free_play_button_id;

    /**
     * @var array
     */
    private $remove_validation = [
        'bonus_code_text', 'bonus_code', 'doc_issue_date'
    ];

    /**
     * @var PlayerService
     */
    private PlayerService $player_service;

    private $ext_session_service;

    /**
     * IT constructor.
     */
    public function __construct()
    {
        $this->loader_action = $this->getLoaderAction();
        $this->player_service = new PlayerService;

        $this->fillExtraRegistrationFields();
        parent::__construct();
    }

    /**
     * Return the configuration for the specific license
     */
    public function config()
    {
        if(!$this->config) {
            $this->config = phive('Licensed')->getSetting('IT');
        }

        return $this->config;
    }

    /**
     * Insert all allowed fields for italian license
     */
    private function fillExtraRegistrationFields()
    {
        $this->extra_registration_fields["step2"] = array_fill_keys(
            array_values($this->getAllowedFieldList()),
            1
        );

        foreach ($this->remove_validation as $remove) {
            unset($this->extra_registration_fields["step2"][$remove]);
        }
    }

    /**
     * @var array
     */
    private $field_map = [
        "main_address" => "address",
        "main_city" => "city",
    ];

    /**
     * @var array
     */
    protected array $fields_to_save_into_users_settings = [
        'main_country',
        'main_province',
        'doc_type',
        'doc_number',
        'doc_issue_date',
        'doc_issued_by',
        'doc_place',
        'doc_year',
        'doc_month',
        'doc_date',
        'fiscal_code',
        'birth_country',
        'birth_province',
        'birth_city',
        'nationality',
    ];
    /**
     * PASSPORT 9 alphanumeric characters uppercase 2 letter + 7 numbers
     * DRIVING_LICENSE min 9 max 10 alphanumeric characters uppercase
     * ID_CARD New CIE (Electronic ID): 2 letter + 5 numbers + 2 letter,
     * ID_CARD Old paper ID v1: 2 letter + 6 numbers
     * ID_CARD Old paper ID v2: 2 letter + 7 numbers
     * ID_CARD Other different: 7 numbers + 2 letters
     *
     * @param $step
     * @param $request
     *
     * @return array
     */
    public function extraRegistrationValidations($step, array $request, $user_id = null)
    {
        $doc_number = $request['doc_number'];
        $errors = parent::extraRegistrationValidations($step, $request);
        $doc = $this->config()['doc_type'][$request['doc_type']];
        $pattern = $doc['regex'] ?? null;
        if (!empty($pattern) && !preg_match($pattern, $doc_number)) {
            $errors['doc_type'] = $doc['error'];
        } elseif ($request['doc_type'] == 10 && $request['birth_country'] == 'IT') {
            $errors['doc_type'] = $doc['error'];
        }
        return $errors;
    }

    /**
     * Return a AccountLimitService instance
     * @param $user
     * @return AccountLimitService
     */
    private function getAccountLimitService($user): AccountLimitService
    {
        return new AccountLimitService($user);
    }

    /**
     * @return Participation
     */
    public function getParticipationService(): Participation
    {
        return new Participation($this);
    }

    /**
     * @return GameExecutionCommunicationService
     */
    private function getGameExecutionCommunicationService(): GameExecutionCommunicationService
    {
        return new GameExecutionCommunicationService($this);
    }

    /**
     * @return GameSessionsAlignmentCommunicationService
     */
    private function getGameSessionsAlignmentCommunicationService(): GameSessionsAlignmentCommunicationService
    {
        return new GameSessionsAlignmentCommunicationService($this);
    }

    /**
     * Return a AccountTransactionService instance
     * @return AccountTransactionService
     * @throws Exception
     */
    private function getAccountTransactionService(): AccountTransactionService
    {
        return new AccountTransactionService();
    }

    /**
     * Return a BonusCancellationService instance
     * @return BonusCancellationService
     * @throws Exception
     */
    private function getBonusCancellationService($user, $bonus_entry): BonusCancellationService
    {
        if (empty($this->bonus_cancelation_service)) {
            $this->bonus_cancelation_service = new BonusCancellationService($user, $bonus_entry);
        }
        return $this->bonus_cancelation_service;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getBirthCountryList(): array
    {
        $countries_service = $this->getCountriesService();
        $countries = $countries_service->getCountries();
        return phive('Cashier')->displayBankCountries($countries);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getProvinces(): array
    {
        $residence_service = $this->getResidenceService();
        return $residence_service->getProvinceList();
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getAllProvinces(): array
    {
        $provinces_service = $this->getProvincesService();
        return $provinces_service->getProvinceList(true);
    }

    /**
     * @param null $lang
     * @return bool|string
     */
    public function topMobileLogos($lang = null)
    {
        if ($this->getLicSetting('rg-buttons')) {
            return phive()->ob(function () use ($lang) {
                $this->rgLogo('black', 'rg-mobile-top-it', $lang);
            });
        }
        return false;
    }

    /**
     * @param string $type
     * @param string $extra_classes
     * @return bool|string
     */
    public function topLogos($type = 'white', $extra_classes = '')
    {
        if ($this->showTopLogos()) {
            return phive()->ob(function () use ($type, $extra_classes) {
                $this->rgLogo('white', 'margin-five-top ' . $extra_classes);
            });
        }
        return false;
    }

    /**
     * @return string
     */
    public static function getExtraClasses(): string
    {
        return self::$extra_classes;
    }

    /**
     * @param string $type
     * @param string $extra_classes
     * @param null $lang
     * @return void
     */
    public function rgLogo($type = 'white', $extra_classes = '', $lang = null)
    {
        $pager = phive('Pager');
        $page = $pager->getPage($pager->page_id);

        // hotfix sc-197697
        // parent is homepage layout for desktop or mobile, not to display on game page
        if (!in_array($page['parent_id'], [0, 115, 130, 268, 275]) || in_array($pager->page_id, [332, 765])) {
            return;
        }

        self::$extra_classes = $extra_classes;
        $rg_log_name = 'rg_logo/';
        $rg_log_name .= ($type === 'white') ? 'desktop' : 'mobile';

        licHtml($rg_log_name);
    }

    /**
     * @param $help_start_box
     * @return void
     */
    public function getRgLink($help_start_box)
    {
        $help_start_box->rgLink('responsible-gambling');
    }

    /**
     * When inside homepage, return a class that will add a top margin in some elements for Italian jurisdiction,
     * so they leave a space for the Italian top bar
     * Pass the $path string to detect if we're in homepage
     * @param string $path
     * @return string
     */
    public function insideTopbar($path = '')
    {
        return 'topmargin';
    }

    /**
     * Prints HTML for Free Play button
     * Prints nothing if user is blocked
     *
     * @return string
     */
    public function freePlayButton():void
    {
        if (!self::noDemo()) {
            licHtml('free_play_button');
        }
    }

    /**
     * Prints HTML for Free Play button
     * Prints nothing if user is blocked
     *
     * @param DBUser|null $user
     * @return mixed
     */
    public function freePlayButtonMobile(DBUser $user = null)
    {
        if (!self::noDemo()) {
            return licHtml('free_play_button_mobile', $user, true);
        }
    }

    /**
     * This function is used to determine if the game is a live casino type
     * @param array $game
     * @return bool
     */
    public function isLiveCasino(array $game)
    {
        $game_tags = phive('MicroGames')->getGameTags($game);
        $filtered = array_filter($game_tags, function ($tag) {
            return in_array($tag['tag_id'], phive('Licensed/IT/IT')->getLicSetting('hide_popup_for_live_casino'));
        });
        return count($filtered) > 0;
    }

    /**
     * Returns true if the current game (demo or real) must be blocked. See phive/modules/Micro/MicroGames.php::onPlay > "if (lic('noDemo'))"
     * If we are explicitly loading the game in demo mode then check settings to prevent for example a logged-in MT player from hacking the URL and illegally launching demo mode.
     * If the player is logged in then the game can proceed.
     * If the player is not logged in then it depends on the setting for no-demo countries.
     *
     * @param bool $show_demo Flag indicating if the game is being loaded in demo mode.
     * @return bool true if game should be blocked otherwise false.
     */
    public function noDemo(bool $show_demo = false, array $game = []):bool
    {
        if (isLogged() && !$show_demo) {
            return false;
        }

        // Logged in users are allowed to play in demo mode
        $no_demo_countries = phive('Config')->valAsArray('countries', 'no-demo-play');
        $country = cuCountry('', false);
        $no_demo = in_array($country, $no_demo_countries);
        if (isLogged()) {
            // If player hasn't submitted his documents in 60 days we void his demo play capability
            $demo_play_blocked = !$this->getPlayerService()->hasPermission(cu(), 'play4fun');
            $no_demo |= $demo_play_blocked;
        }elseif (!$no_demo && !empty($game) && $this->isLiveCasino($game)) {
            return true;
        }
        // If player is not logged in and country is in the no demo countries we disallow demo play in order to protect the children.
        //@todo: Check if there is a case when ALL could be at the offset 0 of $no_demo_countries
        $cur = current($no_demo_countries);
        return (bool) ($cur == 'ALL' ?: $no_demo);
    }

    /**
     * We hide Jackpots menu in this jurisdiction for the time being
     *
     * TODO move this to Licensed as a config
     *
     * @return bool
     */
    public function hideJackpots()
    {
        return true;
    }

    /**
     * Registration step 2 setup
     *
     * @return string[][]
     */
    public function registrationStep2Fields()
    {
        $field_list = [
            'left' => ['firstname', 'lastname', 'fiscal_code', 'birthdate', 'birth_country', 'birth_province', 'birth_city', 'nationality', 'sex'],
            'middle' => ['main_address', 'main_country', 'main_province', 'main_city', 'zipcode', 'doc_type', 'doc_number', 'doc_issue_date', 'doc_issued_by', 'doc_place', 'preferred_lang'],
            'right' => ['currency', 'email_code', 'bonus_code_text', 'bonus_code']
        ];

        if(!empty($_REQUEST['birth_country']) && $_REQUEST['birth_country'] != 'IT') {
            $excluded_fields = ['birth_province', 'birth_city'];
            $field_list['left'] = array_filter($field_list['left'], function($key) use ($excluded_fields) {
                return in_array($key, $excluded_fields);
            }, ARRAY_FILTER_USE_KEY);
        }

        if(!empty($_REQUEST['main_country']) && $_REQUEST['main_country'] != 'IT') {
            $excluded_fields = ['main_province', 'main_city'];
            $field_list['middle'] = array_filter($field_list['middle'], function($key) use ($excluded_fields) {
                return in_array($key, $excluded_fields);
            }, ARRAY_FILTER_USE_KEY);
        }

        return $field_list;
    }

    /**
     * @param array $request
     * @return array
     */
    public function mappingRequestFields(array $request): array
    {
        $request_return = [];
        foreach ($request as $input_name => $value) {
            if (array_key_exists($input_name, $this->field_map)) {
                $request_return[$this->field_map[$input_name]] = $value;
                continue;
            }
            $request_return[$input_name] = $value;
        }

        return $request_return;
    }

    /**
     * @param DBUser|null $user
     * @return bool
     */
    public function isMissingOpenAccountNaturalPerson($user = null): bool
    {
        $user = cu($user);
        if ($user) {
            $opened_account_natural_person = $user->getSetting('open_account_natural_person');
            return $opened_account_natural_person != 1;
        }

        return false;
    }

    /**
     * @param DBUser|null $user
     * @param bool $logout
     * @return array
     * @throws Exception
     */
    public function openAccountNaturalPerson($user = null, $logout = true): array
    {
        $user = cu($user);
        $registration_service = $this->getRegistrationService($user);

        if (empty($user->getSetting('registration_progress_limits'))) {
            return ['generic' => t('registration.step2.missing-deposit-limits')];
        }

        $payload = $registration_service->getPayload();

        // when testing could be helpful stop sending message to adm to avoid to waste testing users by adm
        if(!$this->config()['pacg']['is_disabled']) {
            $open_account_response = $registration_service->saveOpenAccountNaturalPersonResponse($this->onOpenAccountNaturalPerson($payload));

            if (!$open_account_response['success']) {
                $this->logPacgResponse($user, $open_account_response);

                $input_name = $registration_service->getInputNameByErrorCode($open_account_response['code']);
                if (!empty($input_name)) {
                    return [$input_name => $open_account_response['message']];
                }
                return ['generic' => $open_account_response['message']];
            }
        }

        // we forget to set the limit in the proper table before to delete
        $user->deleteSetting('registration_progress_limits');

        return [];
    }

    /**
     * Return data used on step2
     * @param DBUser|null $user
     * @return array
     */
    public function getStep2Data(DBUser $user = null): array
    {
        $data = [];
        if (empty($user)) {
            // should never get here, only if player loses his session during registration
            return $data;
        }
        if (!$user->hasSetting('registration_progress_limits')) {
            list($method, $params) = $this->initIntermediaryStepAfter();
            // TODO: find better solution
            $data = array_merge(["success" => true, "action" => compact('method', 'params')]);
        }

        $registration_service = $this->getRegistrationService($user);

        $data['step2Data'] = $registration_service->getStep2Data();
        return $data;
    }

    /**
     * @param string $tax_code
     * @return array
     * @throws Exception
     */
    public function extractTaxCode(string $tax_code): array
    {
        try {
            $tax_code_service = $this->getTaxCodeService();
            return $tax_code_service->extract($tax_code);
        } catch (Exception $exception) {
            return [
                'code' => TaxCodeService::ERROR_CODE,
                'error_message' => t('invalid_tax_code')
            ];
        }

    }

    /**
     * Display a <tr> with an amount (for mobile balance table)
     *
     * @param string $labelAlias Alias of label to display (from localized_strings table)
     * @param string $id CSS id of the amount element
     * @param float $amount Amount to display
     *
     */
    private function mobileBalanceAmount($labelAlias, $id, $amount)
    {
        if ($amount > 0) {
            // Display always 2 decimals
            $amount = number_format($amount, 2, '.', '');

            // Print row
            ?>
            <tr>
                <td>
                    <span class="small-bold"><?php et($labelAlias) ?></span>
                </td>
                <td class="right">
                    <span class="small-bold header-3">
                        <?= cs() ?>
                        <span id="<?= $id ?>"><?= $amount ?></span>
                    </span>
                </td>
            </tr>
            <?php
        }
    }


    /**
     * Display a <table> with account balances in mobile main menu
     *
     * @param string $tableClass CSS class of table
     */
    public function mobileBalanceTable($tableClass = 'txt-table')
    {
        // Fetch the amounts from account
        $cash_balance = phive("QuickFire")->parentBalance();
        $bonus_balance = empty($this->bonus_balance) ? 0 : $this->bonus_balance / 100;
        $casino_wager = empty($this->casino_wager) ? 0 : $this->casino_wager / 100;

        // Calculate the amounts to display
        $main = $cash_balance + $bonus_balance + $casino_wager;
        $bonus = $bonus_balance + $casino_wager;
        $balance = $casino_wager;
        $withdrawable = $cash_balance;

        // Display the amounts
        echo "<table class='{$tableClass}'>";
        $this->mobileBalanceAmount('casino.balances.main', 'mobile-left-menu-main', $main);
        $this->mobileBalanceAmount('casino.balances.bonus', 'mobile-left-menu-bonus-balance', $bonus);
        $this->mobileBalanceAmount('casino.balances.balance', 'mobile-left-menu-balance', $balance);
        $this->mobileBalanceAmount('casino.balances.withdrawable', 'mobile-left-menu-withdrawable', $withdrawable);
        echo "</table>";
    }

    /**
     * @param string|null $province
     * @return array
     * @throws Exception
     */
    public function getMunicipalityByProvinceList(string $province = null): array
    {
        $residence_service = $this->getResidenceService();
        $municipality_by_province_list = $residence_service->getMunicipalityByProvinceList();
        if ($province) {
            $municipality_by_province_list = $municipality_by_province_list[$province];
        }

        return $municipality_by_province_list;
    }

    /**
     * @param string|null $province
     * @return array
     * @throws Exception
     */
    public function getAllMunicipalityByProvinceList(string $province = null): array
    {
        $residence_service = $this->getProvincesService();
        $municipality_by_province_list = $residence_service->getAllMunicipalityByProvinceList();

        if ($province) {
            $municipality_by_province_list = $municipality_by_province_list[$province];
        }

        return $municipality_by_province_list;
    }

    /**
     * @param DBUser $user
     * @return bool
     */
    public function hasDocumentTypeRestriction($user = null): bool
    {
        $user = empty($user) ? cu() : $user;
        $document_list = $this->getDocumentService()->getDocuments($user);
        return !empty($document_list);
    }

    /**
     * @param DBUser $user
     * @return array
     */
    public function getDocumentTypeAllowed($user = null): array
    {
        $user = empty($user) ? cu() : $user;
        $document = $this->getDocumentService()->filterDocuments($user);
        return empty($document) ? [] : $document;
    }

    /**
     * Creates an empty document with status 'requested'
     *
     * @param DBUser $user
     * @return bool
     */
    public function createEmptyDocument($user): bool
    {
        return $this->getDocumentService()->createEmptyDocument($user);
    }

    /**
     * @param int $doc_type_id
     * @return array
     */
    public function getIssuingAuthorityList(int $doc_type_id): array
    {
        return $this->getDocumentService()->getIssuingAuthorityList($doc_type_id);
    }

    /**
     * CSS and JS helper code to make registration step 2 look properly
     */
    public function registrationStep2Misc()
    {
        ?>
        <style>
            #registration-wrapper,
            .step2 {
                width: 1320px;
                overflow-x: hidden;
            }
        </style>
        <script>
            top.$("#registration-box").css("width", "1320px")
            top.$("#registration-box").resize('registration-box', '1320px', '500px', 'registration-box', false)
        </script>
        <?php
    }


    /**
     * @return array
     */
    public function getDocumentTypeList(): array
    {
        $document_list = phive('Config')->valAsArray('license-it', 'registration-document-list', ' ', ':');
        if (empty($document_list)) {
            return [];
        }
        foreach ($document_list as $key => &$document_name) {
            $document_name = t($document_name);
        }
        return $document_list;
    }

    /**
     * Get document issuer list
     *
     * @return array|null
     */
    public function getDocumentIssuedByList(): ?array
    {
        return !empty($this->getDocumentTypeList()) ? [] : null;
    }

    /**
     * Return the content of the Register button in Registration step 1
     *
     * @param bool $translate
     *
     * @return string
     */
    public function getRegistrationMessage(bool $translate = true)
    {
        $alias = 'continue';

        return $translate ? t($alias) : $alias;
    }

    /**
     * Check if we need to display deposit limit popup when opening cashier.
     *
     * @param null|DBUser $user
     * @return bool
     */
    public function hasDepositLimitOnCashier($user = null)
    {
        $user = cu($user);

        if (empty($user) || empty($this->getLicSetting('deposit_limit')['popup_active'])) {
            return false;
        }

        if (!empty($this->rgLimits()->getByTypeUser($user, 'deposit'))) {
            return false;
        }

        return true;
    }

    /**
     * @param object $user
     * @param string $gamstop_res
     * @return bool
     */
    public function hasExternalSelfExclusion($user = null, $gamstop_res = ''): bool
    {
        return $this->hasExternalSelfExclusionCommon($user, $gamstop_res);
    }

    /**
     * @param object $user
     * @return string
     * @throws Exception
     */
    public function checkGamStop($user = null)
    {
        $user = cu($user);
        $subregistration_service = $this->getSubregistrationService($user);
        $response = $this->subregistration($subregistration_service->getPayload());

        if (empty($response['code']) || $response['code'] == 500) {
            throw new Exception('PACG API not available');
        }

        $is_success = $response['code'] === PacgReturnCode::SUCCESS_CODE;
        if ($is_success) {
            return self::SELF_EXCLUSION_NEGATIVE;
        }

        $this->logPacgResponse($user, $response);

        $is_excluded = $response['code'] === PacgReturnCode::SUBJECT_IS_SELF_EXCLUDED;
        if ($is_excluded) {
            return self::SELF_EXCLUSION_POSITIVE;
        }

        throw new Exception('Uncommon PACG subregistration response: ' . $response['code']);
    }

    /**
     * @param object $user
     * @param array $response
     * @return void
     */
    private function logPacgResponse($user, $response)
    {
        $info = [
            'user_id' => $user->getId(),
            'code' => $response['code'],
            'description' => $this->errorCode($response['code'])
        ];
        phive('Logger')->getLogger('pacg_adm')->info($info, ['tag' => 'pacg-adm-external-block', 'user_id' => $user->getId()]);
        phive('UserHandler')->logAction($user, $info['description']. ' - ' . $info['code'], 'pacg-adm-external-block', false, $user);
    }

    /**
     * @param $user
     * @return bool
     * @throws Exception
     */
    public function userIsMarketingBlocked($user)
    {
        $user = cu($user);

        if (empty($user)) {
            return true;
        }

        $res = $this->checkGamStop($user);

        return $res == 'Y';
    }

    /**
     * @return array
     */
    public function getSelfExclusionTimeOptions()
    {
        $option = [];
        if ($this->permanentSelfExclusion(true)) {
            $option = ['permanent'];
        }
        return array_merge([30, 60, 90], $option);
    }

    /**
     * @param object $user
     * @return array
     * @throws Exception
     */
    public function selfExclusionPermanent($user = null): array
    {
        return $this->selfExclusionAction(
            $user,
            TrasversalSelfExclusionService::MANAGEMENT_SELF_EXCLUSION,
            TrasversalSelfExclusionService::SELF_EXCLUSION_PERMANENT
        );
    }

    /**
     * @param DBUser $user
     * @param int $time
     * @return array
     * @throws Exception
     */
    public function selfExclusionTemporary($user, int $time): array
    {
        return $this->selfExclusionAction(
            $user,
            TrasversalSelfExclusionService::MANAGEMENT_SELF_EXCLUSION,
            $time
        );
    }

    /**
     * @param object $user
     * @return array
     * @throws Exception
     */
    public function selfExclusionRecover($user = null): array
    {
        return $this->selfExclusionAction(
            $user,
            TrasversalSelfExclusionService::MANAGEMENT_REACTIVATION
        );
    }

    /**
     * @param object $user
     * @param int $management_type
     * @param int $time
     * @return array
     * @throws Exception
     */
    private function selfExclusionAction($user, int $management_type, int $time = 0): array
    {
        $user = cu($user);
        $trasversal_self_exclusion_service = $this->getTrasversalSelfExclusionService(
            $user,
            $management_type,
            $time
        );

        return $this->trasversalSelfExclusionManagement($trasversal_self_exclusion_service->getPayload());
    }

    /**
     * @param bool $return_permanent_self_exclusion
     * @return bool
     */
    public function permanentSelfExclusion(bool $return_permanent_self_exclusion = false): bool
    {
        if ($return_permanent_self_exclusion) {
            return !empty($this->getLicSetting('permanent_self_exclusion'));
        }

        return false;
    }

    /**
     * @param string $action
     * @param array $request
     * @return string
     */
    public function overrideRgAction($action, $request): string
    {
        switch ($action) {
            case 'exclude':
                if (isset($request['rg_duration']) && $request['rg_duration'] == 'permanent') {
                    $action = 'exclude-permanent';
                }
        }

        return $action;
    }

    /**
     * @param $duration
     * @return DateTime
     * @throws Exception
     */
    public function calculateSelfExclusionDurationDate($duration): DateTime
    {
        $datetime = new DateTime();
        $datetime->modify("+{$duration} day");
        return $datetime;
    }

    /**
     * @param string $name
     * @return string
     */
    public function getRegistrationFilePath(string $name): string
    {
        $config = $this->getAllLicSettings();
        return $config['registration_file'][$name] ?? '';
    }

    /**
     * This method is used to override the existing logic for checking depositBlocked or not. It impacts on the deposit
     * popup view.
     *
     * @param int|object $u_obj user_id or user_object from DB
     * @return string
     */
    public function isDepositViewBlocked($u_obj)
    {
        return $this->isPlayerDepositBlocked($u_obj) ? 'deposit.blocked.html' : '';
    }

    /**
     * This method returns true if the player blocked to deposit with all PSPs methods
     *
     * @param int|object $u_obj user_id or user_object from DB
     * @return bool
     */
    public function isPlayerDepositBlocked($u_obj)
    {
        return (!$this->player_service->hasPermission($u_obj, 'deposit') && !$this->player_service->hasPermission($u_obj, 'prepaid_deposit'));
    }

    /**
     * @param DBUser $u_obj
     * @param bool $is_api
     * @return bool|string
     */
    public function onLogin(DBUser $u_obj, bool $is_api = false)
    {
        parent::onLogin($u_obj);
        $this->cleanExternalGameSession($u_obj);
        $player_service = $this->getPlayerService();
        if ($player_service->isVerified($u_obj)) {
            $this->stopShowingReminderPopup();
            return true;
        }

        if(!$is_api) {
            if (!isset($_SESSION['account_verification_reminder'])) {
                $_SESSION['account_verification_reminder'] = true;
            }
        }
        if ($this->isPlayBlocked($u_obj)) {
            $u_obj->playBlock();
        }
        if (!$player_service->hasPermission($u_obj, 'withdraw') && !$u_obj->hasSetting('withdrawal_block')) {
            $u_obj->setSetting('withdrawal_block', 1);
        }
        if ($this->isPlayerDepositBlocked($u_obj)) {
            $u_obj->depositBlock();
        }
        if (!$player_service->hasPermission($u_obj, 'login')) {
            return 'it_account_closed';
        }

        return true;
    }

    /**
     * @param DBUser|string|int|null $user
     *
     * @return bool
     */
    public function isPlayBlocked($user): bool
    {
        return parent::isPlayBlocked($user) && !($_REQUEST['show_demo'] && ($this->getPlayerService())->hasPermission($user, 'play4fun')) && $this->userHasPlayableStatus($user);
    }

    /**
     * Handle all logic to be executed when registration has finished.
     * This can be overridden by specific jurisdiction (Ex. DK, SE)
     *
     * @param DBUser $u_obj
     */
    public function onRegistrationEnd(DBUser $u_obj)
    {
        $this->createEmptyDocuments($u_obj);
        $u_obj->setSetting('temporal_account', 1);
        $u_obj->setSetting('withdrawal_block', 1);
        $u_obj->playBlock();
        phive('Cashier/Fr')->nameOrSurnameInEmail($u_obj);
    }

    /**
     * on Verify
     *
     * @param DBUser $user
     * @return void
     */
    public function onVerify(DBUser $user): void
    {
        $user->resetPlayBlock();
        $user->resetDepositBlock();
        $user->deleteSetting('withdrawal_block');
    }


    /**
     * On documents status change update the user settings and activity status.
     *
     * @param $user
     * @param $document_type
     * @param $status
     * @return void
     */
    public function onDocumentStatusChange($user, $document_type, $status)
    {
        $user = cu($user);

        if ($this->player_service->isVerified($user)) {
            $user->setSetting('poi_approved', 1);
            $user->deleteSetting('current_status');
            $user->deleteSetting('deposit_block');
            $user->deleteSetting('play_block');
            $user->deleteSetting('withdrawal_block');
            $user->deleteSetting('temporal_account');
        } else {
            $user->deleteSetting('poi_approved');
            $user->setSetting('deposit_block', 1);
            $user->setSetting('play_block', 1);
            $user->setSetting('withdrawal_block', 1);
            $user->setSetting('temporal_account', 1);
        }
    }


    /**
     * This method is used to filter(add/remove/override) the PSPs in the deposit popup view (Desktop and Mobile)
     *
     * @param array $groupedPsps psps array could be associative or sequential
     * @param int|object $u_obj user_id or user_object from DB
     * @return array
     */
    public function filterDepositGroupedPsps($groupedPsps, $u_obj = '')
    {
        if (!$this->player_service->hasPermission($u_obj, 'prepaid_deposit')){
            if (!$this->isArraySequential($groupedPsps)) {
                unset($groupedPsps['pcard']);
            } else {
                $pos = array_search('pcard', $groupedPsps);
                unset($groupedPsps[$pos]);
            }
        }
        return $groupedPsps;
    }

    /**
     * This method is used to check the array is sequential or not
     *
     * @param array $arr array could be associative or sequential
     * @return bool
     */
    private function isArraySequential($arr): bool
    {
        return array_keys($arr) === range(0, count($arr) - 1);
    }

    /**
     * Invoked when the user's 'verified' status changes.
     *
     * @param $user_id
     * @param string $status
     */
    public function trackUserStatusChanges($user_id, string $status)
    {
        parent::trackUserStatusChanges($user_id, $status);

        if (empty($user = cu($user_id))) {
            return;
        }

        if ($status == UserStatus::STATUS_ACTIVE) {
            $user->deleteSetting('deposit_block');
        } else {
            if ($this->isPlayerDepositBlocked($user_id)) {
                $user->setSetting('deposit_block', 1);
            }
        }
    }

    /**
     * Returns verification data
     *
     * @param $user
     * @return array
     */
    public function accountVerificationData($user): array
    {
        $user = cu($user);
        $player_service = $this->getPlayerService();
        $status = $player_service->getPlayerStatus($user);
        $verified_description = $player_service::VERIFIED_DESCRIPTION;
        $data = $verified_description[$status];

        if (!empty($data) && $data['status'] == $player_service::NO_VERIFIED_LONGER) {
            $data['days_over'] = $player_service->getDaysOverLimit($user);
        }

        return $data;
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
            $player_service = $this->getPlayerService();
            $player_status = $player_service->getPlayerStatus($user);
            $verified_description = $player_service::VERIFIED_DESCRIPTION;
            $days = $verified_description[$player_status]['days_left'];

            return [
                'days'    => $days - 1 - $date_diff['days'],
                'hours'   => 24 - $date_diff['hours'],
                'minutes' => 60 - $date_diff['mins']
            ];
        }

        return null;
    }

    /**
     * Block a player account
     * @throws Exception
     */
    private function externalBlockUserAccount(): void
    {
        $this->getPlayerService()->blockUserAccount($this);
    }

    /**
     * Close a player account
     */
    public function closeUserAccountCron()
    {
        $this->getPlayerService()->closeUserAccount();
    }

    /**
     * @param $payment_method
     * @return array
     */
    public function filterPaymentMethod($payment_method): array
    {
        $return_payment_method = [];
        foreach ($payment_method as $payment_type => $data) {
            if ($this->getPlayerService()->isAllowedPaymentMethod($data['type'] ?? '')) {
                $return_payment_method[$payment_type] = $data;
            }
        }
       return $this->filterDepositGroupedPsps($return_payment_method) ?? $return_payment_method;
    }


    //---------------- EXTERNAL GAME SESSION

    /**
     * This function is needed to be able to handle the error results from initGameSessionWithBalance
     *
     * @param bool $is_bos If is battle of slots or not
     * @return bool
     */
    public function hasGameplayWithSessionBalance(bool $is_bos = false): bool
    {
        return !$is_bos;
    }

    /**
     * It is important that the session is created on game launch and balance set to 0 until the form is filled.
     * When the form is filled then need to transfer in transactions.
     *
     * @param $user
     * @param string $token_id
     * @param array $game
     * @return int
     * @throws Exception
     */
    public function initGameSessionWithBalance($user, string $token_id, array $game)
    {
        $stake = json_decode(phMgetShard('ext-game-session-stake', $user), true);

        if (empty($stake) || $stake['game_ref'] !== $game['ext_game_name']) {
            return false; // the user didn't set the session balance yet or bad request
        }

        $session_id = $this->createNewExternalSession($user, $game, $token_id, $stake['real_stake'], 0);
        if (!empty($session_id)){
            $this->createExternalSession($user, $session_id, $stake['token'], intval($stake['tab_id']));
            $this->onStartedExternalSession($user, $session_id, $stake);
        } else {
            toWs(['popup' => 'error_starting_session', 'msg' => t('game-session-balance.init-error')], 'extgamesess' . $stake['tab_id'], $user->getId());
            phive('Logger')->error('ADM_error', ['error_starting_session']);

            phMdelShard('ext-game-session-stake', $user);
            return false;
        }
        return $session_id;
    }

    /**
     * @throws Exception
     */
    public function createNewExternalSession($user, $game, $token, $amount, $bonus_stake = 0)
    {
        $session_id = $this->getExternalSessionService()->createNewSession($user, $game, $token, $amount, $bonus_stake);
        $this->setExternalSessionByToken($user, $token);
        $tab_id = phMgetShard('ext-game-session-tab', $user);
        $ext_session_id = phive('SQL')->getValue(null, 'ext_session_id', 'ext_game_sessions', ['id' => $this->session_entry['external_game_session_id']]);
        toWs(['ingame_new_session' => true, 'ext_session_id' => $ext_session_id, 'participation_id' => $this->session_entry['participation_id'] ], 'extgamesess' . $tab_id, $user->getId());
        return $session_id;
    }


    /**
     * Send to the frontend the session information to be displayed on the topbar
     *
     * @param DBUser $user
     * @param $session_id
     * @param $stake
     */
    public function onStartedExternalSession(DBUser $user, $session_id, $stake)
    {
        $participation = $this->getExternalSessionService()->getByParticipationId($user, $session_id);
        $ext_session_id = phive('SQL')->getValue(null, 'ext_session_id', 'ext_game_sessions', ['id' => $participation['external_game_session_id']]);
        toWs(['ext_session_id' => $ext_session_id, 'participation_id' => $participation['participation_id'], 'token' => $stake['token'] ], 'extgamesess' . $stake['tab_id'], $user->getId());
    }

    /**
     * Returns the service that gives access to Session functionality
     * @return AAMSSessionService
     */
    public function getExternalSessionService()
    {
        if (empty($this->ext_session_service)) {
            $this->ext_session_service = AAMSSessionService::factory($this);
        }

        return $this->ext_session_service;
    }

    /**
     * Gets the open sessions information to display on the header
     *
     * @param $user
     * @return array[]
     */
    public function getSessionBalances($user): array
    {
        return $this->getExternalSessionService()->getSessionBalances($user);
    }

    /**
     * Gets a particular participation information
     *
     * @param $user
     * @param $participation_id
     * @return array[]
     */
    public function getParticipationInformation($user, $participation_id): array
    {
        return $this->getExternalSessionService()->getByParticipationId($user, $participation_id);
    }

    /**
     * @param string $ext_session_id
     * @param string $end_time
     * @param int|null $attempt
     * @return bool
     */
    public function admEndSession(string $ext_session_id, string $end_time, ?int $attempt): bool
    {
        return $this->getExternalSessionService()->admEndSession($ext_session_id, $end_time, $attempt);
    }

    /**
     * Return the external game session details (open, close and increments) of a user for a given game session
     *
     * @param integer|string $user_id The user id
     * @param integer|string $session_id The ext_game_participations.id
     *
     * @return array An array of increments ([[session_id, participation_id, session_status, game_name, created_at, balance, amount, win]])
     */
    public function getExternalGameSessionDetails($user_id, $session_id): array
    {
        return $this->getExternalSessionService()->getExternalGameSessionDetailsBySessionId($user_id, $session_id);
    }

    /**
     * @param mixed $user_id The user id or the (already initialized) user object
     * @return bool
     */
    public function isUserAccountClose($user): bool
    {
        $user = cu($user);
        return $user->getSetting('closed_account') == 1;
    }


    /**
     * Report to ADM all game version changes
     * @throws Exception
     */
    public function reportSoftwareVersionCron()
    {
        $this->getSoftwareVersionCommunicationService($this)->reportGameChanges();
    }

    /**
     *  Specifies the lifetime of the cookie in seconds which is sent to the browser. The value 0 means "until the browser is closed."
     *  The user will need to login again after closing the browser.
     *
     * @return int
     */
    public function cookieLifetime()
    {
        return 0;
    }

    /**
     * @param $user
     * @param string $email
     * @return void
     * @throws Exception
     */
    public function updateEmail($user, string $email)
    {
        $user = cu($user);
        if ($user->getData('email') != $email) {
            $payload = $this->getEmailAccountService($user)->getPayload($email);
            $this->updateEmailAccount($payload);
        }
    }

    /**
     * Return the external game session details (open, close and increments) of a user for a given period
     *
     * @param integer|string $user_id The user id
     * @param string $start_date The starting datetime, in SQL accepted format ('yyyy-mm-dd hh:mm:ss')
     * @param string $end_date The ending datetime, in SQL accepted format ('yyyy-mm-dd hh:mm:ss')
     *
     * @return array An array of increments ([[session_id, participation_id, session_status, game_name, created_at, balance, amount, win]])
     */
    public function getGameSessionBalancesByUserId($user_id, $start_date, $end_date): array
    {
        $ext_session_service = new AAMSSessionService($this);
        return $ext_session_service->getGameSessionBalancesByUserId($user_id, $start_date, $end_date);
    }

    /**
     * Used by "user-service-laravel" and "new game mode"
     * return all the data needed to display the strip - NO HTML
     * @param DBUser $user
     * @return array|false
     */
    public function getBaseGameParams($user = null)
    {
        if($_REQUEST['show_demo'] ?? false) {
            return false;
        }

        if (!empty($user)) {
            return [
                'localized' => [
                    'participation_id' => t('session.balance.participation.id'),
                    'ext_session_id' => t('session.balance.aams.session.id'),
                ]
            ];
        }

        return false;
    }

    /**
     * Gets information about the session that will be displayed to the player on the top bar while playing
     *
     * @return array
     */
    public function ajaxGetGameSessionBalance(): array
    {
        $user = cu();
        return $this->getSessionBalances($user);
    }

    /**
     * Return handle response success or error from PACG on update user province
     * @param $user
     * @param string $city_name
     * @return array
     * @throws Exception
     */
    public function updateUserProvince($user, string $city_name): array
    {
        $user = cu($user);
        $provinceService = $this->getProvincesService();
        $new_province_data = $provinceService->getProvinceByCityName($city_name);
        $residential_province_acronym = $user->getSetting('main_province');
        if (!empty($new_province_data) && $residential_province_acronym != $new_province_data['automotive_code']) {
            $payload = $provinceService->getPayloadToChangeProvince($new_province_data['automotive_code']);
            $response = $this->changeAccountProvinceOfResidence($payload);
            if (PacgReturnCode::SUCCESS_CODE == $response['code']) {
                $user->setSetting('main_province', $new_province_data['automotive_code']);
                return [
                    'found' => true,
                    'update' => true
                ];
            }
            return ['found' => false, 'error' => $this->errorCode($response['code'])];
        }
        return ['found' => !empty($new_province_data)];
    }

    /**
     * Mapping return codes from PACG response
     * @param $code
     * @return string
     */
    private function errorCode($code)
    {
        return (new PacgReturnCode())->getCodeDescription($code);
    }

    /**
     * This method returns success if the call to ADM was successful or if the call to ADM was not made.
     * In other words it only returns failure if the user account exists and the call to ADM failed.
     *
     * @param $user
     * @param int $value
     * @param array $limit
     * @return array. Example:
     *  ['success' => true,  'code' => 1024, 'message' => '']
     *  ['success' => false, 'code' => 1202, 'message' => 'Conto non esistente']
     * @throws Exception
     */
    public function changeAccountLimit($user, int $value, array $limit): array
    {
        $return = ['success' => true];
        $user = cu($user);
        if (!$this->isMissingOpenAccountNaturalPerson($user)) {
            $payload = $this->getAccountLimitService($user)->getPayload($limit, $value);
            if (!empty($payload)) {
                $response = $this->updateAccountLimit($payload);
                $return['success'] = (PacgReturnCode::SUCCESS_CODE == ($response['code'] ?? null));
                $return = array_merge($return, $response);
            }
        }
        return $return;
    }

    /**
     * Report a game session stages to adm message 580
     *
     * @param int $ext_game_session_id
     * @param array $users
     * @return bool
     */
    public function sendGameSessionCommunication(int $ext_game_session_id, array $users = []): bool
    {
        return $this->getGameExecutionCommunicationService()
            ->sendGameSessionStages($ext_game_session_id, $users);
    }

    /**
     * Report transaction to adm.
     * N.B. $user_id must be type int cause object will be serialized as array when using pexec
     *
     * @param string $action
     * @param int $value
     * @param string $supplier
     * @param int $user_id
     * @throws Exception
     */
    public function dispatchReportTransactionJob(string $action, string $supplier, int $value, int $user_id)
    {
        return $this->getAccountTransactionService()
            ->dispatchReportTransactionJob($action, $supplier, $value, $user_id);
    }

    /**
     * Report transaction to adm.
     * N.B. $user_id must be type int cause object will be serialized as array when using pexec
     *
     * @param string $action
     * @param int $value
     * @param string $supplier
     * @param int $user_id
     * @throws Exception
     */
    public function reportTransaction(string $action, string $supplier, int $value, int $user_id, int $attempt = 0)
    {
        return $this->getAccountTransactionService()
            ->reportTransaction($action, $supplier, $value, $user_id, $attempt);
    }

    /**
     * Report bonus cancellation to ADM
     *
     * @param DBUser|int $user User object or id of the user
     * @param array $bonus_entry The Bonus entry that belongs to the player
     * @param int $attempt Number of times this action has been retried
     * @return array|null
     * @throws Exception
     */
    public function cancelBonus($user, array $bonus_entry, int $attempt = 0): ?array
    {
        return $this->getBonusCancellationService($user, $bonus_entry)->reportTransaction($attempt);
    }

    /**
     * Report a game session alignement to adm message 590
     *
     * @param int $ext_game_session_id
     * @param array $users
     * @return bool
     */
    public function sendGameSessionAlignmentCommunication(int $ext_game_session_id, array $users = []): bool
    {
        return $this->getGameSessionsAlignmentCommunicationService()
            ->sendGameSessionAlignment($ext_game_session_id, $users);
    }

    /**
     * Cron job action to end finishing the game session
     * excluding sessions which no reason to retry. E.g. code 1070
     */
    public function gameSessionsAlignmentCommunicationCron()
    {
        $where = '';
        $settings = $this->config();
        $ignore_codes = $settings['pgda']['retry_request_options']['do_not_retry_on_response_code'];
        $ignore_codes = implode(',', $ignore_codes);

        if ($ignore_codes) {
            $where = "AND (status_reason IS NULL OR status_reason = '' OR JSON_EXTRACT(status_reason, '$.code') NOT IN ({$ignore_codes}))";
        }
        $db = phive('SQL');
        $date = date('Y-m-d H:i:s', strtotime('-24 hours'));
        $status_code = GameExecutionCommunicationService::STATUS_CODE_SENT;
        $ext_game_sessions = $db->loadArray("SELECT * FROM ext_game_sessions
         WHERE (status_code = {$status_code} AND ended_at < '{$date}') {$where}
         ORDER BY id DESC;");

        foreach($ext_game_sessions as $ext_game_session) {
            // here we need to push job to queue
            phive('Site/Publisher')->single(
                'pgda',
                'Licensed',
                'doLicense',
                ['IT', 'sendGameSessionAlignmentCommunication', [$ext_game_session['id']]]
            );
        }
    }

    /**
     * Get the balance that the user is allowed to withdraw without any further requirement
     * If he hasn't completed deposit wagering, we allow him to withdraw without showing error but the user will be later check by Risk & Fraud team
     * If he hasn't completed bonus payout wagering, we don't allow him even to start the withdraw
     *
     * @param DBUser $user
     * @return int
     *
     *@see CasinoCashier::processWithdrawal
     *
     */
    public function getBalanceAvailableForWithdrawal(DBUser $user): int
    {
        $not_wagered = $this->getBalanceNotWagered($user);

        if (array_sum(array_values($not_wagered)) > 0 ) {
            $available_withdrawal = 0;
        } else {
            $available_withdrawal = $user->getBalance();
        }

        return $available_withdrawal;
    }

    /**
     * Get the not wagered balance from deposit and bonus payouts
     * @param DBUser $user
     * @return array
     */
    public function getBalanceNotWagered(DBUser $user): array
    {
        $last_deposit = phive('CasinoCashier')->getLatestDeposit($user);

        if (!empty($last_deposit)) {
            $sess_sums = phive('UserHandler')->sumGameSessions($user->getId(), $last_deposit['timestamp']);
            $bonus_payouts_status = $this->getBonusPayoutWageringStatus($user, $last_deposit['timestamp']);
            $deposits_not_wagered = $last_deposit['amount'] - ($sess_sums['bet_amount'] - $bonus_payouts_status['payouts_wagered']);
            $payouts_not_wagered = $bonus_payouts_status['payouts_not_wagered'];
        }

        return [
            'deposits_not_wagered' => $deposits_not_wagered ?? 0,
            'payouts_not_wagered' => $payouts_not_wagered ?? 0
        ];
    }

    /**
     * Return the amount that the player is allowed to submit on the withdraw form to continue.     *
     * On Italy we wont allow the player continue withdrawal if he tries to withdraw funds coming from bonus payouts that have not been wagered
     *
     * @param DBUser $user
     * @return int
     */
    public function getWithdrawStartAllowedBalance(DBUser $user): int
    {
        $balance_not_wagered = $this->getBonusPayoutWageringStatus($user);
        return $user->getBalance() - $balance_not_wagered['payouts_not_wagered'];
    }

    /**
     * Calculates the amount of the bonus payouts that have been wagered
     * Since to unblock one payout you need to have wager the previous one we just fetch the last bonus payout transaction
     *
     * @param DBUser $user
     * @return int[]
     */
    public function getBonusPayoutWageringStatus(DBUser $user, $from_date = NULL): array
    {
        $user_id = $user->getId();
        if (empty($from_date)) {
            $last_bonus_payout = phive('SQL')->sh($user_id)->loadAssoc("SELECT * FROM cash_transactions WHERE user_id = $user_id AND transactiontype = 69 ORDER BY id desc LIMIT 1");
            $from_date = date('Y-m-d H:i:s', strtotime($last_bonus_payout['timestamp']) + 1);
        }

        if (!empty($last_bonus_payout['amount'])) {
            $wagered_amount = (int)phive('Casino')->getBetsOrWinSumForUser('bets', $user_id, $from_date, '');
            $bonus_payouts_not_wagered = max($last_bonus_payout['amount'] - $wagered_amount, 0);
            $bonus_payouts_wagered = $last_bonus_payout['amount'] - $bonus_payouts_not_wagered;
        }

        return [
            'payouts_not_wagered' => $bonus_payouts_not_wagered ?? 0,
            'payouts_wagered' => $bonus_payouts_wagered ?? 0
        ];
    }

    /**
     * Get the balance that the user can't withdraw because has not completed wagering requirements
     * Total balance: bonus + real balance
     *
     * @param DBUser $user
     * @return int
     */
    public function getBalanceNonWithdrawable(DBUser $user): int
    {
        $total_balance = (int)phive('Bonuses')->getBalanceByUser($user->getId()) + (int)$user->getBalance();
        return $total_balance - (int)$this->getBalanceAvailableForWithdrawal($user);
    }

    /**
     * Returns required data for intermediary step
     *
     * @param string $context value in ['registration', 'login', 'verification']
     * @return array
     */
    public function initIntermediaryStepAfter($context = null)
    {
        $method = 'extBoxAjax';
        $params = $this->getIntermediaryStepParameters();

        return [$method, $params];
    }


    /**
     * Returns the BE dependencies in order to start deposit limits popup
     * @param string $sessionId
     * @param bool $isApi
     *
     * @return array
     */
    public function getIntermediaryStepParameters(string $sessionId = "", bool $isApi = false)
    {
        // here i can store in session security question just for italy

        return ['get_html_popup', 'rg-login-box', [
            "module" => 'Licensed',
            "file" => 'deposit_limits_popup',
            "boxtitle" => 'rg.info.limits.set.title',
            "closebtn" => "no",
            "close_selector" => '.positive-action-btn',
            "is_registration" => true,
            "on_submit" => 'licFuncs.rgSubmitDepositLimitsDuringRegistration()'
        ], [
            'baseZIndex' => 20000,
        ]];
    }

    /**
     * @return bool
     */
    public function hasIntermediaryStepAfter()
    {
        return true;
    }

    /**
     * Temporary store the user limit on users_settings table
     *
     * @param $limits
     */
    public function ajaxSaveRegistrationDepositLimits($post)
    {
        $user = cuRegistration();
        $limits = $post['deposit'];
        $user->setSetting('registration_progress_limits', json_encode($limits));
        $rg = rgLimits();
        foreach ($limits as $rgl) {
            $local_clean_limit = $rg->cleanInput($rgl['type'], $rgl['limit']);
            $rg->addLimit($user, 'deposit', $rgl['time_span'], $local_clean_limit);
        }
        $rg->logAction($user, $limits[0]['type'], $limits, 'add', 'Added during registration');
        return ["success" => true];
    }

    /**
     * Set extra registration parameters on session
     * @param string $rstep
     * @param array $post
     */
    public function rehydrateRegistrationSessionParameters($user)
    {
        $_SESSION['rstep1'] = array_merge($_SESSION['rstep1'], [
            'security_question' => $user->getSetting('security_question'),
            'security_answer' => $user->getSetting('security_answer'),
            'age_check' => 1,
            'gambling_check' => 1,
        ]);
    }

    /**
     * Allows to play on multiple tabs
     * Requires Tab handling implementation on initGameSessionWithBalance and IT.js
     *
     * @return bool
     */
    public function hasExternalGameSessionTab()
    {
        return true;
    }

    /**
     * We will avoid to show the session balance to users if they have already a freespin active for the current game
     * or is battle of slots
     *
     * @param $user
     * @param $game
     * @return bool
     */
    public function showSessionBalancePopup($user, $game)
    {
        $cash_balance = $user->getBalance();
        if(empty($cash_balance)){
            // We need to play with the bonus balances because the real balance is gone.
            $bonus_balance = phive('Bonuses')->getBalanceByRef($game['ext_game_name'], $user->getId());
            if(!empty($bonus_balance)){
                phMsetShard('ext-game-session-stake', ['real_stake' => $bonus_balance, 'token' => phive()->uuid(), 'game_ref' => $game['ext_game_name']], $user->getId(), 30);
                return false;
            }
        } else {
            $show_demo = filter_var($_POST['show_demo'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $desktop_game = phive('MicroGames')->getDesktopGame($game) ?? $game;
            $shown_in_game = (phive('MicroGames')->getNetworkModule($desktop_game))->getLicSetting('hide_balance_popup', $user) ?? false;

            if (!empty($_GET['eid']) || $shown_in_game || $show_demo || count(phive('Bonuses')->getBonusEntryByGameIdAndFreeSpinsRemaining(uid($user), $desktop_game['game_id']))) {
                phMsetShard('ext-game-session-stake', ['real_stake' => 1, 'token' => phive()->uuid(), 'ingame_popup' => true, 'game_ref' => $game['ext_game_name']], $user->getId(), 30);
                return false;
            }
            return true;
        }
    }


    /**
     * Trigger action on freespin finished
     * Shows a popup to the user that will reload the page
     *
     * @param $user
     * @param $game
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
     * Triggered by cron command
     *
     * @return void
     * @throws Exception
     */
    public function onEveryHour(): void
    {
        $this->externalBlockUserAccount();
    }

    /**
     * @see Licensed::showBosLicensingStripInLobby
     *
     * @return bool
     */
    public function showBosLicensingStripInLobby()
    {
        return false;
    }


    /**
    * Gets the current game type (skill game or chance game) based on PGDA game codes.
    * @param $current_game the ext_game_id of the current game being played.
    * @return string
    */
    public function getCurrentGameType($current_game)
    {
        $game_types = $this->getLicSetting('PGDA_game_codes');
        $code = AAMSSessionService::getGameRegulatoryType($current_game);

        if($code !== false) {
            foreach ($game_types as $game_type => $val) {
                if(in_array($code, $val)) {
                    return $game_type;
                }
            }
        }
        return '';
    }

    /**
     * Check if user is active, has status ACTIVE and DNI & documents were verified
     *
     * @param DBUser|string|int|null $user
     *
     * @return bool
     */
    public function userHasPlayableStatus($user): bool
    {
        $user = cu($user);

        if (empty($user)) {
            return false;
        }

        $user_verified = $user->getSetting('verified');
        $has_playable_status = false;

        if ($user_verified === '1') {
            $has_playable_status = true;
        } elseif (empty($user_verified)) {
            $date_diff = $this->getTimeSinceRegistration($user);
            $expired_time = $date_diff['days'] < $this->getDaysToProvideDocuments();

            $required_documents_types = $this->getLicSetting('required_documents_types');
            $documents_uploaded_or_approved = $this->documentsUploadedOrApproved($user, $required_documents_types);

            $has_playable_status = !$documents_uploaded_or_approved && $expired_time || $documents_uploaded_or_approved;
        }

        return $has_playable_status;
    }

    /**
    * returns the base url to the responsible gaming page
    * @return string
    */
    public function get18PlusLink(): string
    {
        $device = !phive()->isMobile() ? 'desktop' : 'mobile';

        return phive('Casino')->getBasePath(null, $device) .'/'. licSetting('rglink_url');
    }

    /**
     * Renders mobile balance table.
     *
     * Took content from `modules/Licensed/IT/html/mobile_balance_table.php` to be able to separate rendering from
     * getting data
     *
     * @param \Videoslots\Menu\Boxes\MobileMenu\Element\MobileBalanceTableData $data
     *
     * @return void
     */
    public function formatMobileBalanceTableToHtml(MobileBalanceTableData $data): void
    {
        ?>

        <table class="txt-table">
            <tr>
                <td>
                    <span class="medium-bold">
                        <?php et($data->getAlias()) ?>
                    </span>
                </td>
                <td class="right">
                    <span class="medium-bold header-3">
                        <?= $data->getCurrency() ?>
                        <span id="<?= $data->getId() ?>">
                            <?= $data->getAmount() / 100 ?>
                        </span>
                    </span>
                </td>
            </tr>
        </table>

        <?php
    }

    /**
     * @param  \Videoslots\Menu\Boxes\MobileMenu\Element\MobileBalanceTableData  $data
     *
     * @return array
     */
    public function formatMobileBalanceTableToJson(MobileBalanceTableData $data): array
    {
        return [
            'element-type' => MobileBalanceTableElementFormatter::ELEMENT_TYPE,
            'alias'        => $data->getAlias(),
            'currency'     => $data->getCurrency(),
            'id'           => $data->getId(),
            'amount'       => (string)($data->getAmount() / 100),
        ];
    }

    /**
     * @return array
     */
    public function getMobileBalanceTable(): array
    {
        $user = cu();

        return [
            'amount' => lic('getBalanceAvailableForWithdrawal', [$user], $user),
            'label_alias' => 'casino.tooltip.withdrawable',
            'id' => 'mobile-left-menu-withdrawable',
            'currency' => cs(),
        ];
    }

    /**
     * @return bool
     */
    public function canFormatMobileBalanceTable(): bool
    {
        return true;
    }

    /**
    Finish any open game sessions for IT users.
    If the game session has been open for more than the specified age (default is 172800 seconds or 48 hours),
    then it will be closed. Only users from the IT jurisdiction will be affected.
    @param int $age The number of seconds after which a game session should be considered for closing.
    @return void
     */
    public function finishITOrphanGameSession($age = 172800)
    {
        $start_time = phive()->hisNow("-{$age} seconds");
        $zero_time = phive()->getZeroDate();
        $jur = "IT";
        $sql = phive('SQL');
        $str = "SELECT users_game_sessions.* FROM users_game_sessions left join users on users.id = users_game_sessions.user_id
                WHERE end_time='{$zero_time}' AND start_time<='{$start_time}' AND users.country='{$jur}'";

        $open = $sql->shs('merge', '', null, 'users_game_sessions')->loadArray($str);
        foreach ($open as $ugs) {
            $user = cu($ugs["user_id"]);
            $end_time = phive()->hisNow();
            $session_service = lic('getExternalSessionService', [], $user);
            $participation = $session_service->getParticipationByUGSId($user, $ugs['id']);
            $session_closed = $session_service->endPlayerSession($user, $participation['id'], $end_time);
            if (!$session_closed) {
                $success = $sql->sh($ugs['user_id'])->updateArray(
                    "users_game_sessions",
                    [
                        'end_time' => $end_time,
                    ],
                    ['id' => $ugs['id']]
                );
                if ($success) {
                    // here we need to dispatch query job passing ext_game_session_id and user_id when is not multiplayer
                    phive('Site/Publisher')->single(
                        'it-game-session',
                        'Licensed',
                        'doLicense',
                        ['IT', 'sendGameSessionCommunication', [$ugs['id'], [$ugs['user_id']]]]
                    );
                }
            }
        }
    }

    /**
     * Makes possible to disable prefilling of a Nationality Value
     * @return string
     */
    public function getNationalityValue(): string
    {
        return '';
    }


    /**
     * Main province field should be validated only if user has chosen Italy as main country
     *
     * @param string|null $main_country
     * @return bool
     */
    public function shouldValidateMainProvince(?string $main_country): bool
    {
        return $main_country === 'IT';
    }
}
