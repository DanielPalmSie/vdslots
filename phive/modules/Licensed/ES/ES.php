<?php

require_once __DIR__ . '/../Traits/RealityCheckTrait.php';
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ .'/DGOJ/DGOJ.php';
require_once __DIR__ .'/../Helpers/ReportLog.php';
require_once __DIR__ . '/../../Micro/traits/ExternalGameSessionTrait.php';


use Carbon\Carbon;
use ES\ICS\Constants\ICSConstants;
use ES\ICS\Reports\BaseProxy;
use ES\ICS\Reports\BaseReport;
use ES\ICS\Reports\Info;
use ES\ICS\Reports\JUD;
use ES\Services\SessionService;

class ES extends Licensed
{
    use RealityCheckTrait;
    use ExternalGameSessionTrait;

    public const FORCED_LANGUAGE = 'es';
    static $exceeds_limit;
    protected array $fields_to_save_into_users_settings = [
        'fiscal_region', 'nationality', 'residence_country', 'lastname', 'lastname_second'
    ];

    protected string $logger_name = 'es_licensed';

    private $ext_session_service;
    private DGOJ $dgoj;

    public string $ext_exclusion_name = 'rgiaj';
    public string $ext_verification_system = 'dgoj';
    private ?ReportLog $report_log = null;

    public function __construct()
    {
        parent::__construct();

        $this->dgoj = new DGOJ($this->getLicSetting('DGOJ'));
    }

    /**
     * We hide BOS
     *
     * TODO When ch97941 is deployed, this function can be removed and keep the same functionality adding
     * Licensed.config.php hide-bos setting on ES block
     *
     * @return bool
     */
    public function hideBattles()
    {
        return true;
    }

    /**
     * Gets the open users_game_session related to current participation
     *
     * @param array $participation
     *
     * @return array[]
     */
    public function getGameSessionByParticipation(array $participation, bool $current_session = true)
    {
        return $this->getExternalSessionService()->getGameSessionByParticipation($participation, $current_session);
    }

    /**
     * Gets the open sessions information to display on the header
     *
     * @param $user
     *
     * @return array[]
     */
    public function getOpenParticipation($user)
    {
        return $this->getExternalSessionService()->getOpenParticipation($user);
    }

    /**
     * This function is needed to be able to handle the error results from initGameSessionWithBalance
     *
     * @param bool $is_bos
     * @return bool
     */
    public function hasGameplayWithSessionBalance(bool $is_bos = false): bool
    {
        return !$is_bos;
    }

    /**
     * When customer is logged in and haven't set a session stake we show the popup.
     *
     * @param $user
     * @param $game
     * @return bool
     */
    public function showSessionBalancePopup($user, $game)
    {
        $this->logger->debug([
            'type' => 'popup',
            'user' => $user,
            'game' => $game,
        ]);
        $desktop_game = phive('MicroGames')->getDesktopGame($game) ?? $game;

        $active_free_spins = phive('Bonuses')->getBonusEntryByGameIdAndFreeSpinsRemaining(uid($user), $desktop_game['game_id']);
        if (!empty($_GET['eid']) || !empty($active_free_spins)) {
            phMsetShard('ext-game-session-stake', ['real_stake' => 0,  'token' => phive()->uuid(), 'game_ref' => $game['ext_game_name']], $user->getId(), 30);
            return false;
        }
        return true;
    }

    /**
     * Prevent showing the existing popups by sending a non-configured value
     *
     * @param $user
     * @param $game
     *
     * @return string|null
     */
    public function skipPopupDueToFreeSpins($user, $game)
    {
        $user = cu($user);
        $this->logger->debug([
            'type' => 'popup',
            'user' => $user,
        ]);
        if (empty($user) || empty($game)) {
            return false;
        }

        if (!empty($game['game_id']) && is_array($game['game_id'])) {
            $game = $game['game_id'];
        }

        $game = phive('Bonuses')->getBonusGameForMobileDevice($game);

        return !empty(phive('Bonuses')->getBonusEntryByGameIdAndFreeSpinsRemaining(uid($user), $game['game_id']));
    }

    /**
     * It is important that the session is created on game launch and balance set to 0 until the form is filled.
     * When the form is filled then need to transfer in transactions.
     *
     * @param DBUser $user
     * @param string $token_id
     * @param array $game
     * @return int|false
     * @throws Exception
     */
    public function initGameSessionWithBalance($user, string $token_id, array $game)
    {
        $user = cu($user);

        $data = json_decode(phMgetShard('ext-game-session-stake', $user), true);

        if (empty($data) || $data['game_ref'] !== $game['ext_game_name']) {
            $this->logger->error(__METHOD__, [
                'data' => $data ?? '',
                'user' => $user->getId() ?? '',
                'game' => $game['ext_game_name'] ?? ''
            ]);
            return false; // the user didn't set the session balance yet or bad request
        }

        phive('Casino')->finishGameSession($user->getId());

        $open_participation = $this->getOpenParticipation($user);

        if (!empty($open_participation)) {
            $this->getExternalSessionService()->endPlayerSession($user, $open_participation['id'], phive()->hisNow());
            $this->purgeMetaGameSession($user);
        }

        if(!empty($this->hasGameSessionRestrictions($user))) {
            $this->logger->debug([
                'type' => 'popup',
                'user' => $user,
            ]);
            toWs(['popup' => 'game_session_temporary_restriction'], 'extgamesess', $user->getId());
            return false;
        }

        $session_id = $this->getExternalSessionService()->createNewSession($user, $token_id, $data, $game);
        $this->createExternalSession($user, $session_id, $token_id);

        if (empty($session_id)) {
            $error_message = t('game-session-balance.init-error');
            phive('Logger')->error('error-ext-session-start', [$user->getId(), $game['ext_game_name']]);

            $this->logger->debug(__METHOD__, [
                'user' => $user->getId(),
                'token_id' => $token_id,
                'ext_game_name' =>  $game['ext_game_name'],
            ]);
        }
        phive('Logger')->getLogger('game_providers')->debug(__METHOD__, [
            'user_id' => $user->getId(),
            'game' =>  $game,
            'data' => $data,
            'token_id' => $token_id,
            'participation_id' => $session_id,
            'error_message' => $error_message ?? NULL,
            'open_participation_id' => $open_participation['id'] ?? NULL,
        ]);

        if (!empty($error_message)) {
            $this->logger->debug([
                'type' => 'popup',
                'user' => $user,
            ]);
            toWs(['popup' => 'error_starting_session', 'msg' => $error_message], 'extgamesess', $user->getId());

            phMdelShard('ext-game-session-stake', $user);
            return false;
        }

        return $session_id;
    }


    /**
     * Used to check, before a bet is handled (inside lgaMobileBalance), if the player has exceeded his session time
     * limit.
     *
     * @param DBUser $user
     *
     * @return bool
     * @throws Exception
     */
    public function hasExceededTimeLimit(DBUser $user): bool
    {
        $participation = $this->getOpenParticipation($user);

        $time_limit = $participation['time_limit'];
        $created_at = date('Y-m-d H:i:s', strtotime($participation['created_at']));

        try {
            $session_started_date = new DateTime($created_at);
            $current_date = new DateTime(phive()->hisNow());
        } catch (Exception $e) {
            return false;
        }
        $session_started_date->add(new DateInterval('PT' . (int)$time_limit . 'M'));

        if ($session_started_date->format('Y-m-d H:i:s') < $current_date->format('Y-m-d H:i:s')) {
            // TODO check In which scenarios can this fail? or if this check is useless /Paolo
            if (!$this->getExternalSessionService()->endPlayerSession($user, $participation['id'], $current_date)) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Checks if the player has reached the balance limit.
     * The original function is binded to some logic for IT, so we want to disable it here.
     *
     */
    public function onExternalGameSessionLimitReached()
    {
        return false;
    }

    /**
     * Update the balance for each bet so we can send it through ws
     *
     * @param $user
     * @param $game
     */
    public function wsUpdateExtGameSessionInfo($user, $game)
    {
        $this->getExternalSessionService()->wsUpdateSessionAfterBet($user, $game);
    }

    public function onNotEnoughMoney($u_obj){
        $this->getExternalSessionService()->sendSessionDataToWs(['not_enough_money' => true], $u_obj);
    }

    /**
     * Return the popup where we inform the user that hes last session was before 60 minutes
     *
     * @param $user
     *
     * @return bool
     */
    public function showTooCloseNewGameSessionWarning($user): bool
    {
        $last_closed_session = $this->getLastSession($user);

        $db_timestamp = strtotime($last_closed_session['ended_at']);

        return (time() - $db_timestamp) < (60 * 60);
    }

    /**
     * Check if this user has a self defined restriction set up
     * Apply restriction when
     *  1. last session is open and restrict is 1
     *  2. last session is closed and not enough time passed
     *
     * @param $user
     *
     * @return bool
     */
    public function hasGameSessionRestrictions($user): bool
    {
        $last_session = $this->getLastSession($user, false);
        if (empty($last_session) || empty($last_session['restrict_future_session'])) {
            return false;
        }

        if (empty($last_session['closed'])) {
            $ugs = phive('SQL')->readOnly()->sh($user)->loadAssoc("
                SELECT * FROM users_game_sessions WHERE id = {$last_session['user_game_session_id']}
            ") ?? [];

            // if user_game_sessions was closed act as if ext_game_participations was closed too
            if (!phive()->isEmpty($ugs['end_time'])) {
                phive('SQL')->sh($user)->query("
                    UPDATE ext_game_participations SET ended_at = '{$ugs['end_time']}' WHERE id = {$last_session['id']};
                ");
                phMdelShard('ext-game-session-stake', $user);
                return true;
            }
        }

        $db_timestamp = strtotime($last_session['ended_at']);
        if (time() - $db_timestamp < (int)$last_session['limit_future_session_for'] * 60) {
            phMdelShard('ext-game-session-stake', $user);
            return true;
        }

        return false;
    }

    /**
     * End the open participation in the other tab and send a WS msg.
     *
     * @param $user
     */
    public function endOpenParticipation($user, $showRegulatoryPopup = false)
    {
        $open_session = $this->getOpenParticipation($user);
        if (!empty($open_session)) {
            $this->getExternalSessionService()->endPlayerSession($user, $open_session['id'], phive()->hisNow());
            $this->purgeMetaGameSession($user);
            // TODO see if we need to add "id OR token" to websocket message, so we only target the correct tab /Paolo
            $this->logger->debug([
                'type' => 'popup',
                'user' => $user,
            ]);

            if ($showRegulatoryPopup) {
                toWs(['popup' => 'game_session_manually_closed', 'session' => $open_session],
                    'extgamesess', $user->getId());
            } else {
                toWs(['popup' => 'closed_by_new_session', 'msg' => t('closed.by.new.session')], 'extgamesess', $user->getId());
            }
        }
    }

    /**
     * Check if this user has an open session already
     *
     * @param $user
     * @return bool|false
     */
    public function hasAnOpenSession($user): bool
    {
        $open_session = $this->getOpenParticipation($user);
        $this->logger->debug([
            $user,
            $open_session
        ]);
        if (count($open_session) > 0) {
            return true;
        }
        return false;
    }

    /**
     * add an extra text on the self exclusion for spain
     *
     * @return mixed
     */
    public function getSelfExclusionExtraInfo()
    {
        return t('exclude.rgiaj.account.info.html');
    }

    /**
     * Get the last Session that was closed by the given user
     *
     * @param $user
     * @param  bool  $closed
     *
     * @return array
     */
    public function getLastSession($user, $closed = true): array
    {
        return $this->getExternalSessionService()->getLastSession($user, $closed);
    }

    /**
     * @see Licensed::userStatusMapping()
     * @return string[]
     */
    protected function userStatusMapping(): array
    {
        return [
            UserStatus::STATUS_NA => ICSConstants::OTHERS,
            UserStatus::STATUS_ACTIVE => ICSConstants::ACTIVE,
            UserStatus::STATUS_PENDING_VERIFICATION => ICSConstants::PENDING_DOCUMENT_VERIFICATION,
            UserStatus::STATUS_SUSPENDED => ICSConstants::SUSPENDED,
            UserStatus::STATUS_CANCELED => ICSConstants::CANCELED,
            UserStatus::STATUS_DORMANT => ICSConstants::OTHERS,
            UserStatus::STATUS_UNDER_INVESTIGATION => ICSConstants::PRECAUTIONARY_SUSPENSION,
            UserStatus::STATUS_BLOCKED_FOR_FRAUD => ICSConstants::CONTRACT_CANCELLATION,
            UserStatus::STATUS_EXTERNALLY_SELF_EXCLUDED => ICSConstants::INDIVIDUAL_PROHIBITION,
            UserStatus::STATUS_SELF_LOCKED => ICSConstants::SELF_EXCLUDED,
            UserStatus::STATUS_SELF_EXCLUDED => ICSConstants::SELF_EXCLUDED,
            UserStatus::STATUS_RESTRICTED => ICSConstants::OTHERS,
            UserStatus::STATUS_BLOCKED => ICSConstants::OTHERS,
            UserStatus::STATUS_SUPERBLOCKED => ICSConstants::OTHERS,
            UserStatus::STATUS_DECEASED => ICSConstants::DECEASED,
        ];
    }

    public function personalNumberMessage($translate = true)
    {
        $alias = 'register.personal.number.description.es';

        return $translate ? t($alias) : $alias;
    }

    /**
     * Validate personal number
     *
     * @param $nid
     * @return null|string
     */
    public function validatePersonalNumber($nid): ?string
    {
        if ($this->isValidNif($nid) || $this->isValidNie($nid)) {
            return null;
        }

        return 'register.err.invalid.personal.number.es';
    }

    /**
     * Check if provided value is a valid NIF
     * Residents Fiscal Identification Number (NIF)
     *
     * @param $value
     * @return bool
     */
    public function isValidNif($value): bool
    {
        $regEx = '/^[0-9]{8}[A-Z]$/i';

        $letters = 'TRWAGMYFPDXBNJZSQVHLCKE';

        if (preg_match($regEx, $value)) {
            return $letters[(substr($value, 0, 8) % 23)] === strtoupper($value[8]);
        }

        return false;
    }

    /**
     * Check if provided value is a valid NIE
     * Foreigner's Identification Number (NIE)
     *
     * @param $value
     * @return bool
     */
    public function isValidNie($value): bool
    {
        $regEx = '/^[KLMXYZ][0-9]{7}[A-Z]$/i';
        $letters = 'TRWAGMYFPDXBNJZSQVHLCKE';

        if (preg_match($regEx, $value)) {
            $replaced = str_replace(['X', 'Y', 'Z'], [0, 1, 2], $value);

            return $letters[(substr($replaced, 0, 8) % 23)] == $value[8];
        }

        return false;
    }

    /**
     * Used to prevent nid verification popup on registration step 1 form submit
     *
     * @param array $data
     * @return false
     */
    public function passedExtVerification($data = [])
    {
        return true;
    }

    /**
     * Simulate until external verification is implemented
     *
     * @param null $data
     * @return mixed
     */
    public function validateExtVerAndGetNid($data = null)
    {
        return $data['personal_number'];
    }

    /**
     * Clean the nid value
     *
     * @param string|null $nid
     * @return mixed
     */
    public function sanitizeNid($nid)
    {
        return phive()->rmNonAlphaNums($nid);
    }

    /**
     * return all the data needed to display the strip
     * @param DBUser $user
     * @return array|false
     */
    public function getBaseGameParams($user = null)
    {
        if (!empty($user)) {
            return [
                'localized' => [
                    'show_game_session_balance' => true
                ]
            ];
        }
        return false;
    }

    /**
     * Get the identity validation and RGIAJ subscribed status
     *
     * @param array $users
     * @return DGOJResponse[]
     */
    public function verifyPlayer(array $users): array
    {
        return $this->dgoj->requestPlayerType($users, $this->dgoj::VERIFY_PLAYER);
    }

    /**
     * Get the identity validation
     *
     * @param array $users
     * @return DGOJResponse[]
     */
    public function verifyIdentity(array $users): array
    {
        return $this->dgoj->requestPlayerType($users, $this->dgoj::VERIFY_IDENTITY);
    }

    /**
     * Get the RGIAJ subscribed status
     *
     * @param array $users [['dni' => '', 'nombre' => '', 'apellido1' => '', 'apellido2' => '', 'fechaNacimiento' =>
     *                     '', 'numSoporte' => '',], [], ...] Required fields for user - Request::$required_fields
     *
     * @return array
     * @throws Exception
     */
    public function verifyRGIAJ(array $users): array
    {
        return $this->dgoj->requestPlayerType($users, $this->dgoj::VERIFY_RGIAJ);
    }

    /**
     * Get the RGIAJ Changes subscribed status
     *
     * @return array
     * @throws Exception
     */
    public function verifyRgiajChanges (): array
    {
        return $this->dgoj->requestBlankType($this->dgoj::VERIFY_RGIAJ_CHANGES);
    }

    /**
     * Get the Death Changes subscribed status
     *
     * @return array
     * @throws Exception
     */
    public function verifyDeathChanges(): array
    {
        return $this->dgoj->requestBlankType($this->dgoj::VERIFY_DEATH_CHANGES);
    }

    /**
     * Registration step 2 fields
     *
     * @return string[][]
     */
    public function registrationStep2Fields(): array
    {
        if (phive()->isMobile()) {
            return [
                'left' => ['firstname', 'lastname', 'lastname_second', 'nationality', 'personal_number', 'fiscal_region', 'residence_country', 'address', 'zipcode', 'city', 'bonus_code', 'currency'],
                'right' => ['preferred_lang', 'birthdate', 'sex', 'email_code', 'eighteen']
            ];
        }
        return [
            'left' => ['firstname', 'lastname', 'lastname_second', 'nationality', 'personal_number', 'fiscal_region', 'residence_country', 'address', 'zipcode', 'city'],
            'right' => ['preferred_lang', 'birthdate', 'currency', 'sex', 'email_code', 'eighteen']
        ];
    }

    /**
     * Code => Region value taken from the regulator documentation
     * We store in the user setting the key.
     * Source: https://www.agenciatributaria.es/static_files/Sede/Procedimiento_ayuda/GC18/763_inst.pdf
     *
     * @return array
     */
    public function getFiscalRegions(): array
    {
        return [
            '01' => 'Comunidad Autónoma de Andalucía',
            '02' => 'Comunidad Autónoma de Aragón',
            '03' => 'Comunidad Autónoma del Principado de Asturias',
            '04' => 'Comunidad Autónoma de Canarias',
            '05' => 'Comunidad Autónoma de Cantabria',
            '06' => 'Comunidad Autónoma de Castilla - La Mancha',
            '07' => 'Comunidad de Castilla y León',
            '08' => 'Comunidad Autónoma de Cataluña',
            '09' => 'Comunidad Autónoma de Extremadura',
            '10' => 'Comunidad Autónoma de Galicia',
            '11' => 'Comunidad Autónoma de las Illes Balears',
            '12' => 'Comunidad Autónoma de La Rioja',
            '13' => 'Comunidad de Madrid',
            '14' => 'Comunidad Autónoma de la Región de Murcia',
            '15' => 'Comunitat Valenciana',
            '16' => 'Comunidad Foral de Navarra',
            '17' => 'Territorio Histórico de Araba',
            '18' => 'Territorio Histórico de Gipuzkoa',
            '19' => 'Territorio Histórico de Bizkaia',
            '20' => 'Ceuta',
            '21' => 'Melilla',
            '22' => 'No residentes',
        ];
    }

    /**
     * Return list of countries for non-residents spanish users
     *
     * @return mixed
     */
    public function getResidenceCountryList()
    {
        return array_reduce(phive('Cashier')->getBankCountries('', true), function($carry, $item) {
            $carry[$item['iso']] = $item['printable_name'];
            return $carry;
        }, []);
    }

    /**
     * Add extra text on top left registration step 2 form
     */
    public function registrationExtraTopLeft () {
        ?><small><?php echo t("register.step2.info.extra"); ?></small><?php
    }

    /**
     * Save extra information on the user
     *
     * @param DBUser $user
     * @param array $fields
     */
    public function saveExtraInformation(DBUser $user, array $fields): void {
        parent::saveExtraInformation($user, $fields);
        $user->setAttr('nid', $fields['personal_number']);
    }

    /**
     * Triggers AML checks during deposit.
     *
     * @param DBUser $user
     * @param mixed $timestamp
     */
    public function onDepositAMLCheck(DBUser $user, $timestamp = ''): void
    {
        $cf = phive('Config')->getByTagValues('AML');
        phive('Cashier/Aml')->depositCheck($user, $cf['AML55-value'], 'AML55', true, false, true, $cf['AML55-timeframe'], $timestamp);
    }
    /**
     * Triggered everytime user logs
     *
     * @param DBUser $u_obj
     *
     * @return bool|string
     */
    public function onLogin(DBUser $u_obj)
    {
        if($this->getUserStatus($u_obj) !== UserStatus::STATUS_ACTIVE) {

            $date_diff = $this->getTimeSinceRegistration($u_obj);

            $_SESSION['account_verification_reminder'] = $this->showAccountVerificationReminder($u_obj);

            if (!$u_obj->isVerified() && !$_SESSION['account_verification_reminder']) {
                $_SESSION['account_verification_overtime'] = $this->showAccountVerificationOvertime($u_obj);
            }

            if (!empty($u_obj->getSetting('deceased'))) {
                $u_obj->setSetting('manual-fraud-flag', 1);
                //User should be already blocked - just in case
                phive("DBUserHandler")->addBlock($u_obj, 16);
                lic('trackUserStatusChanges', [$u_obj, UserStatus::STATUS_UNDER_INVESTIGATION], $u_obj);
                return 'external-self-excluded';
            }

            if (!$u_obj->isVerified() && in_array($this->getUserStatus($u_obj), [UserStatus::STATUS_PENDING_VERIFICATION, UserStatus::STATUS_SUSPENDED, UserStatus::STATUS_CANCELED])) {
                if ($date_diff['days'] < $this->getDaysToProvideDocuments()) {
                    return true;
                }

                if (!$u_obj->isDepositBlocked()) {
                    $u_obj->depositBlock();
                }

                if (!$u_obj->isPlayBlocked()) {
                    $u_obj->playBlock();
                }

                if ($date_diff['days'] < $this->getDaysToProvideDocumentsBeforeSuspend()) {
                    return true;
                }

                phive('UserHandler')->closeAccount($u_obj);

                if ($date_diff['days'] < $this->getDaysToProvideDocumentsBeforeCancel()) {
                    return 'es_account_suspended';
                }

                $this->trackUserStatusChanges($u_obj->getId(), UserStatus::STATUS_CANCELED);
                return 'es_account_canceled';
            }
            return true;
        }

        return true;
    }

    /**
     * Retrieve the amount of days given to the player to provide his/her documents in order to be verified before
     * suspend
     *
     * @return int
     */
    public function getDaysToProvideDocumentsBeforeSuspend(): ?int
    {
        return $this->getLicSetting('days_to_provide_documents_before_suspend');
    }

    /**
     * Retrieve the amount of days given to the player to provide his/her documents in order to be verified before
     * cancel
     *
     * @return int
     */
    public function getDaysToProvideDocumentsBeforeCancel(): ?int
    {
        return $this->getLicSetting('days_to_provide_documents_before_cancel');
    }

    /**
     * Retrieve the required documents for ES users.
     *
     * @return string
     */
    public function requiredDocumentAprove()
    {
        return implode(" ", phive('Licensed/ES/ES')->getLicSetting('required_documents_types'));
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
        $required_document_setting = $this->requiredDocumentAprove();
        if ($status === 'approved' && $document_type === $required_document_setting) {
            $this->onActive($user);
            $user->setMissingSetting('first_verification_date', phive()->hisNow());
            $user->deleteSetting('deposit_block');
            $user->deleteSetting('play_block');
            $user->deleteSetting('withdrawal_block');
            $user->triggerStatusChange();

            phive('Logger')->getLogger('payments')->info('Documents change Status: Approved',
                [
                    'user_id'=> $user->getId(),
                    'user_country'=> $user->getCountry(),
                    'is_verified' => $user->getSetting('verified'),
                    'deposit_block' => $user->getSetting('deposit_block'),
                    'play_block' => $user->getSetting('play_block'),
                    'withdrawal_block'=> $user->getSetting('withdrawal_block')
                ]);
        } elseif ($status === 'rejected' && $document_type === $required_document_setting) {
            $this->trackUserStatusChanges($user, UserStatus::STATUS_PENDING_VERIFICATION);
            $user->setSetting('play_block', 1);
            $user->setSetting('withdrawal_block', 1);

            phive('Logger')->getLogger('payments')->info('Documents change Status: Rejected',
                [
                    'user_id'=> $user->getId(),
                    'user_country'=> $user->getCountry(),
                    'is_verified' => $user->getSetting('verified'),
                    'deposit_block' => $user->getSetting('deposit_block'),
                    'play_block' => $user->getSetting('play_block'),
                    'withdrawal_block'=> $user->getSetting('withdrawal_block')
                ]);
        }
    }

    /**
     * Set user temporary status and block play
     *
     * @param DBUser $u_obj
     *
     * @see Licensed::onRegistrationEnd()
     */
    public function onRegistrationEnd(DBUser $u_obj)
    {
        parent::onRegistrationEnd($u_obj);
        $this->trackUserStatusChanges($u_obj->getId(),UserStatus::STATUS_PENDING_VERIFICATION);
        $u_obj->playBlock();
        $this->setInitialDepositLimit($u_obj);
        $u_obj->withdrawBlock();

        if (rgLimits()->shouldApplyFixedCycleTimestamp($u_obj)) {
            rgLimits()->getOrSetFixedCycleResetsAtSourceTimestamp($u_obj);
        }
    }

    /**
     * Check If user has special limit for deposit, triggered during deposit
     *
     * @param DBUser $u_obj
     * @param        $limit
     * @param        $amount
     *
     * @return bool
     */
    public function hasSpecialLimit($u_obj, $limit, $amount): bool
    {
        if ($limit == 'deposit' && $u_obj->getSetting('current_status') === UserStatus::STATUS_PENDING_VERIFICATION && !$u_obj->isVerified()) {
            $sums = phive('Cashier')->getDeposits('', '', $u_obj->getId(), '', 'total');
            if (($sums['amount_sum'] + $amount) > $this->getLicSetting('temporal_account_deposit_limit')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     *
     * @param DBUser $user
     *
     * @return array
     */
    public function getPrintDetailsData(DBUser $user): array
    {
        $user_settings = [
            'fiscal_identification_number' => $user->getNid(),
            'full_last_name' => $user->data['lastname'],
            'fiscal_region' => $user->getSetting('fiscal_region') ? $this->getFiscalRegions()[$user->getSetting('fiscal_region')] : "",
            'nationality' => $user->getSetting('nationality') ? $this->getNationalities()[$user->getSetting('nationality')] : "",
            'residence_country' => $user->getSetting('residence_country') ? $this->getResidenceCountryList()[$user->getSetting('residence_country')] : "",
        ];

        return array_merge(parent::getPrintDetailsData($user), $user_settings);
    }

    /**
     * @inheritDoc
     *
     * @return string[]
     */
    public function getPrintDetailsFields(): array
    {
        return [
            'firstname',
            'full_last_name',
            'fiscal_identification_number',
            'fiscal_region',
            'nationality',
            'residence_country',
            'address',
            'zipcode',
            'city',
            'country',
            'dob',
            'mobile',
            'email',
            'last_login',
            'register_date',
        ];
    }

    /**
     * Validate user's DNI (NIE/NIF) via DGOJ
     *
     * @param array $user_data
     *
     * @return bool
     */
    public function validateDni(array $user_data): bool
    {
        if (!$this->enabledDniExternalValidation()) {
            return true;
        }

        $results_validation_dni = $this->verifyIdentity([$user_data]);
        /** @var DGOJResponse $result_validation_dni */
        $result_validation_dni = reset($results_validation_dni);
        $error = $result_validation_dni->getCommonValidationError();

        if (!empty($error)) {
            phive('Logger')->error('validation_dni_dgoj_error', ['error' => $error, 'nid' => $user_data['dni']]);

            return false;
        }

        if($result_validation_dni->response['resultadoIdentidad']['codigo'] === IdentityResponse::RESPONSE_CODE_DECEASED && !empty($_SESSION['rstep1']['user_id'])){
           throw new Exception(IdentityResponse::RESPONSE_CODE_DECEASED);
        }

        $result = $result_validation_dni->isValidIdentity();

        if ($result === null) {
            phive('Logger')->error('validation_dni_dgoj_null', ['nid' => $user_data['dni']]);
        }

        return (bool) $result;
    }

    /**
     * Validate field `personal_number` DNI (NIE/NIF) on registration
     * Return array with validation error in case of not valid DNI
     *
     * @param array $user_data
     *
     * @return string
     */
    public function validationDniOnRegistration(array $user_data): string
    {
        $validation_error = '';

        if (!$this->enabledDniExternalValidation()) {
            return $validation_error;
        }

        try{
            $is_valid_dni = $this->validateDni($user_data);
        }catch(Exception $e){
            if($e->getMessage() === IdentityResponse::RESPONSE_CODE_DECEASED) {
                $user = cu($_SESSION['rstep1']['user_id']);
                phive("DBUserHandler")->addBlock($user, 16);
                lic('trackUserStatusChanges', [$user, UserStatus::STATUS_UNDER_INVESTIGATION], $user);
            }
            $is_valid_dni = false;
        }

        $user = cuRegistration();

        if ($is_valid_dni !== true || empty($user)) {
            $validation_error = 'errors.personal_number.dgoj.not_valid';
        }

        if (!empty($user)) {
            $user->setSetting($this->getSettingNameDniVerification(), $is_valid_dni ? 1 : 0);
            $user->setSetting($this->getSettingNameDniVerificationDate(), phive()->hisNow());
        }

        return $validation_error;
    }

    /**
     * Check if user is external self excluded
     *
     * @param DBUser|string|null $user
     * @param string $gamstop_res
     *
     * @return bool
     */
    public function hasExternalSelfExclusion($user = null, string $gamstop_res = ''): bool
    {
        $user = cu($user);

        if (empty($user)) {
            return true;
        }

        if (!$this->enabledCheckSelfExclusion()) {
            return false;
        }

        // Values of external self exclusion from DB
        $self_exclusion_value = $user->getSetting("cur-{$this->ext_exclusion_name}");

        if (empty($self_exclusion_value)) {
            $self_exclusion_value = $this->hasExternalSelfExclusionCommon($user, $gamstop_res)
                ? self::SELF_EXCLUSION_POSITIVE
                : self::SELF_EXCLUSION_NEGATIVE;
        }
        return $self_exclusion_value !== self::SELF_EXCLUSION_NEGATIVE;
    }

    /**
     * @param DBUser|bool|string|null $user
     *
     * We can return 3 option of string:
     * 'Y' - user is self excluded
     * 'N' - user is not self excluded
     *
     * @return string
     * @throws Exception
     */
    public function checkGamStop($user = null): string
    {
        $user = cu($user);

        if (empty($user)) {
            return self::SELF_EXCLUSION_POSITIVE;
        }

        $user_data = [
            'dni' => $user->getData('nid'),
            'nombre' => $user->getData('firstname'),
            'apellido1' => $user->getData('lastname'),
            'apellido2' => $user->getSetting('lastname_second'),
            'fechaNacimiento' => $user->getData('dob'),
            'numSoporte' => '',
        ];

        $results_verify_rgiaj = $this->verifyRGIAJ([$user_data]);
        /** @var DGOJResponse $result_verify_rgiaj */
        $result_verify_rgiaj = reset($results_verify_rgiaj);
        $error = $result_verify_rgiaj->getCommonValidationError();

        if (!empty($error)) {
            phive('Logger')->error('self_exclusion_rgiaj_error', ['error' => $error, 'nid' => $user_data['dni']]);

            return self::SELF_EXCLUSION_POSITIVE;
        }

        $is_self_excluded = $result_verify_rgiaj->isBlacklisted();

        if ($is_self_excluded === null) {
            phive('Logger')->error('self_exclusion_rgiaj_null', ['nid' => $user_data['dni']]);

            return self::SELF_EXCLUSION_POSITIVE;
        }

        return $is_self_excluded ? self::SELF_EXCLUSION_POSITIVE : self::SELF_EXCLUSION_NEGATIVE;
    }

    /**
     * check self exclusion by RGIAJ
     *
     * @return void
     * @throws Exception
     */
    private function externalSelfExclusion(): void
    {
        if ($this->getLicSetting('gamstop')['is_active'] !== true || !$this->enabledCheckSelfExclusionCron()) {
            return;
        }

        $verify_rgiaj_changes = $this->verifyRgiajChanges();
        $user_nids = array_keys($verify_rgiaj_changes);

        if (empty($user_nids)) {
           return;
        }

        $users = $this->getUserIdsByNids($user_nids);

        /** @var DGOJResponse $result */
        foreach ($verify_rgiaj_changes as $dni => $result) {
            $change_reason = $result->getChangeReason();

            if ($change_reason) {
                $this->hasExternalSelfExclusionCommon($users[$dni], $change_reason === RGIAJChangesResponse::CHANGE_REASON_ADDED ? self::SELF_EXCLUSION_POSITIVE : self::SELF_EXCLUSION_NEGATIVE);
            }
        }
    }

    /**
     * Checks death changes
     *
     * @return void
     * @throws Exception
     */
    private function externalVerifyDeathChanges(): void
    {

        if ($this->getLicSetting('gamstop')['is_active'] !== true || !$this->enabledCheckSelfExclusionCron()) {
            return;
        }
        $verify_death_changes = $this->verifyDeathChanges();
        $user_nids = array_keys($verify_death_changes);

        if (empty($user_nids)) {
            return;
        }

        $users = $this->getUserIdsByNids($user_nids);
        /** @var DGOJResponse $result */
        foreach ($verify_death_changes as $dni => $result) {
            $user = cu($users[$dni]);
            if (!empty($user)) {
                $date_of_death = new DateTime($result->getResponse()['fechaCambio']);
                $date_of_death = $date_of_death->format('Y-m-d H:i:s');
                !empty($user->getSetting('deceased')) ?: $user->setSetting('deceased', $date_of_death);

                if(isLogged($user->getId()) || $user->getAttr('last_login') > $date_of_death){
                    $user->setSetting('manual-fraud-flag', 1);
                    phive("DBUserHandler")->addBlock($user, 16);
                }else{
                    phive("DBUserHandler")->addBlock($user, 15);
                }
            }
        }
    }

    /**
     * Should DNI be validated by DGOJ while login & registration
     *
     * @return bool
     */
    public function enabledDniExternalValidation(): bool
    {
        return (bool) $this->getLicSetting('enabled_dni_validation');
    }

    /**
     * Should user (DNI/NIF/NIE) be checked for self exclusion by DGOJ while login & registration
     *
     * @return bool
     */
    public function enabledCheckSelfExclusion(): bool
    {
        return (bool) $this->getLicSetting('enabled_check_self_exclusion');
    }

    /**
     * Enabling cron for external self exclusion
     *
     * @return bool
     */
    public function enabledCheckSelfExclusionCron(): bool
    {
        return (bool) $this->getLicSetting('enabled_check_self_exclusion_cron');
    }

    /**
     * Make an external verification
     * For Spain: verify DNI by DGOJ
     *
     * @param array $user_data
     * @param array $errors
     *
     * @return array
     */
    public function extraValidationForStep2(array $user_data, array $errors): array
    {
        $validation_errors = [];

        if (empty($errors['personal_number']) && !empty($user_data['personal_number']) && lic('enabledDniExternalValidation')) {
            $dni_error = lic('validationDniOnRegistration', [
                [
                    'dni' => $user_data['personal_number'],
                    'nombre' => $user_data['firstname'],
                    'apellido1' => $user_data['lastname'],
                    'apellido2' => $user_data['lastname_second'] ?? '',
                    'fechaNacimiento' => $user_data['dob'],
                    'numSoporte' => '',
                ]
            ]);

            if (!empty($dni_error)) {
                $validation_errors['personal_number_general_error'] = $dni_error;
            }
        }

        return $validation_errors;
    }

    /**
     * @return string
     */
    public function getSettingNameDniVerificationDate(): string
    {
        return "last-{$this->ext_verification_system}-dni-check";
    }

    /**
     * @return string
     */
    public function getSettingNameDniVerification(): string
    {
        return 'verified-nid';
    }


    /**
     * Function set default deposit limit after registration
     *
     * @param DBUser $user
     */
    private function setDefaultDepositLimit(DBUser $user): void
    {
        $highest_allowed_limit = $this->getLicSetting('deposit_limit')['highest_allowed_limit'];
        $type = $this->rgLimits()::TYPE_DEPOSIT;

        foreach ($highest_allowed_limit as $time_span => $limit) {
            $this->rgLimits()->changeLimit($user, $type, $limit, $time_span);
        }

        foreach (array_keys($highest_allowed_limit) as $time_span) {
            $this->rgLimits()->updateChangesAtLimitToNow($user, $type, $time_span);

            if ($this->rgLimits()->shouldApplyFixedCycleTimestamp($user)) {
                $reset_at = $this->rgLimits()->getFixedCycleResetStamp($user, $time_span, new DateTime());
            } else {
                $reset_at = $this->rgLimits()->getResetStamp($time_span, $type, $user->getCountry());
            }
            $this->rgLimits()->setResetAtLimit($user, $type, $time_span, $reset_at);
        }

        /* Log limits in action table for reports */
        $this->rgLimits()->logCurrentLimit($user);
        $this->rgLimits()->logAppliedLimit($user);
    }

    /**
     * Function set initially deposit limit after registration
     *
     * @param DBUser $user
     */
    private function setInitialDepositLimit(DBUser $user): void
    {
        $limit = $this->getLicSetting('temporal_account_deposit_limit');
        $type = $this->rgLimits()::TYPE_DEPOSIT;
        $time_spans = $this->rgLimits()->getTimeSpans($type);

        foreach ($time_spans as $time_span) {
            $this->rgLimits()->addLimit($user, $type, $time_span, $limit);
            $this->rgLimits()->setResetAtLimit($user, $type, $time_span, phive()->getZeroDate());
        }

        /* Log limits in action table for reports */
        $this->rgLimits()->logCurrentLimit($user);
        $this->rgLimits()->logAppliedLimit($user);
    }

    /**
     * Validate Resettable RgLimits during change limits
     *
     * @param array $new_limits
     * @param DBUser $user
     */
    public function checkHighestAllowedDepositLimit(array $new_limits, DBUser $user)
    {
        $filtered_limits = array_filter($new_limits, function ($limit) {
            return $limit['type'] === 'deposit';
        });

        // We only want to limit deposit limits
        if (empty($filtered_limits)) {
            return null;
        }



        $highest_allowed_limit = $this->getLicSetting('deposit_limit')['highest_allowed_limit'];
        $check_for_test = false;
        $cool_off_period= true;
        $deposit_approval_date = $user->getSetting('deposit_limit_change_approval_date');
        foreach ($filtered_limits as $new_limit) {
            $timespan = $new_limit['time_span'];
            $current_limit = empty($current_limits[$timespan]) ? $this->rgLimits()->getByTypeUser($user, $new_limit['type'])[$timespan] : $current_limits[$timespan];
            if ($user->getSetting('deposit_removal_check')){
                if ($current_limit['new_lim'] == -1){
                    return [
                        'success' => 'nok',
                        'msg' =>  t2('increase.deposit.limit.locked'),
                    ];
                }
                $_SESSION['new_limit_data'] = $new_limits;
                return [
                    'success' => 'nok',
                    'msg' => [
                        'action' => 'increaseDepositLimitTest',
                    ],
                ];
            }
            if ($current_limit['new_lim'] == -1){
                return [
                    'success' => 'nok',
                    'msg' =>  t2('increase.deposit.limit.locked'),
                ];
            }
            if (empty($current_limit) || $current_limit['type'] !== $new_limit['type']) {
                continue;
            }

            $new_limit_value_in_cents = $new_limit['limit'] * 100;
            //checks if user can raise limit until 3 months have elapsed.
            $update_limit_date = Carbon::parse($deposit_approval_date)->addMonths(3);
            $result_date =  Carbon::now()->gt($update_limit_date);
            if ($new_limit_value_in_cents > $current_limit['cur_lim'] && $user->getSetting('rg_review_state')){
                return [
                    'success' => 'nok',
                    'msg' =>  t2('increase.deposit.limit.locked'),
                ];
            }
            if ($user->getSetting('rg_review_state') && $current_limit['cur_lim'] == 0){
                return [
                    'success' => 'nok',
                    'msg' =>  t2('increase.deposit.limit.locked'),
                ];
            }
            if ($deposit_approval_date &&$new_limit_value_in_cents > $current_limit['cur_lim'] && !$result_date){
                return [
                    'success' => 'nok',
                    'msg' =>  t2('increase.deposit.limit.locked'),
                ];
            }
            if ($new_limit_value_in_cents > $current_limit['cur_lim']){
                $check_for_test = true;
            }
            if ($check_for_test){
                if ($new_limit_value_in_cents > $highest_allowed_limit[$timespan] && $this->getUserStatus($user) !== UserStatus::STATUS_PENDING_VERIFICATION) {
                    $_SESSION['new_limit_data'] = $new_limits;
                    return [
                        'success' => 'nok',
                        'msg' => [
                            'action' => 'increaseDepositLimitTest',
                        ],
                    ];
                }
            }

            if ($this->isDepositIncreaseRestricted($new_limit_value_in_cents, $timespan, (int) $current_limit['new_lim'], (int) $current_limit['cur_lim'])
                && $this->getUserStatus($user) == UserStatus::STATUS_PENDING_VERIFICATION) {
                return [
                    'success' => 'nok',
                    'msg' =>  t2('deposit.limit.increase.error'),
                ];
            }
            $cool_off_period = false;
        }
        if (!$check_for_test && !$cool_off_period){
            return false;
        }
        return null;
    }

    /**
     * Validate if deposit limit is valid for Spain
     *
     * @param int    $new_limit_value_in_cents
     * @param string $timespan
     * @param int    $current_limit_new_limit
     * @param int    $current_limit_cur_lim
     *
     * @return bool
     */
    private function isDepositIncreaseRestricted(int $new_limit_value_in_cents, string $timespan, int $current_limit_new_limit, int $current_limit_cur_lim): bool
    {
        $highest_allowed_limit = $this->getLicSetting('deposit_limit')['pending_verification_highest_allowed_limit'];

        if ($new_limit_value_in_cents > $highest_allowed_limit[$timespan]){
            return true;
        }

        return false;
    }

    public function depositLimitRemovalTest($u_obj, $translate = true): array
    {
        if ($this->getUserStatus($u_obj) == UserStatus::STATUS_PENDING_VERIFICATION){
            $alias = 'deposit.limit.increase.error';

            return [
                'success' => 'nok',
                'msg' =>  $translate ? t2($alias) : $alias,
            ];
        }
        if ($u_obj->getSetting('rg_review_state')){
            $alias = 'remove.deposit.limit.locked';

            return [
                'success' => 'nok',
                'msg' =>  $translate ? t2($alias) : $alias,
            ];
        }
        $deposit_approval_date = $u_obj->getSetting('deposit_limit_change_approval_date');
        //checks if user can raise limit until 3 months have elapsed.
        $update_limit_date = Carbon::parse($deposit_approval_date)->addMonths(3);
        $result_date =  Carbon::now()->gt($update_limit_date);
        if ($deposit_approval_date && !$result_date){
            $alias = 'remove.deposit.limit.locked';

            return [
                'success' => 'nok',
                'msg' =>  $translate ? t2($alias) : $alias,
            ];
        }
        return [
            'success' => 'nok',
            'msg' => [
                'action' => 'removeDepositLimitTest',
            ],
        ];
    }

    /**
     * Return the correct value for reset stamp as specified by DGOJ
     *
     * @param string $time_span
     * @param string $type
     *
     * @return string|bool
     */
    public function getResetStamp($time_span, $type)
    {
        if ($type !== $this->rgLimits()::TYPE_DEPOSIT){
            return false;
        }

        switch ($time_span) {
            case 'day':
                return date("Y-m-d 23:59:59");
            case 'week':
                return date("Y-m-d 23:59:59", strtotime('sunday this week'));
            case 'month':
                return date("Y-m-d 23:59:59", strtotime('last day of this month'));
            default:
                return false;
        }
    }


    /**
     * Add first_verification_date to users settings the first time the user is verified
     * This data is used by ICS reports
     * @param DBUser $user
     * @param int|NULL $old_verified_value if player status was active previously or not
     * @return void
     */
    public function onVerify(DBUser $user, ?int $old_verified_value):void
    {
        $user->setMissingSetting('first_verification_date', phive()->hisNow());
        $this->setDefaultDepositLimit($user);
    }

    public function onActive($user){
        $this->setDefaultDepositLimit($user);
    }

    /**
     * Generate ICS Reports
     *
     * @param string $start
     * @param string $end
     * @param string $frequency
     * @param array $reports
     * @param array $export_type
     * @param array $rectify
     * @param array $game_types
     *
     * @return array
     * @throws JsonException
     */
    public function generateICSReports(string $start, string $end, string $frequency, array $reports = [], array $export_type = [BaseReport::GENERATE_ZIP], array $rectify = [], array $game_types = []): array
    {
        try {
            $log_message = json_encode(compact('start', 'end', 'frequency', 'reports', 'export_type', 'rectify'), JSON_THROW_ON_ERROR);

            $this->reportLog('INFO :: Start generateICSReports. ' . $log_message);

            if (!is_array($export_type)) {
                $export_type = [$export_type];
            }

            $generated_files = [];
            $log_report_message = json_encode(compact('start', 'end', 'frequency', 'rectify'), JSON_THROW_ON_ERROR);

            /** @var string $report */
            foreach ($reports as $report) {
                $this->printLog("Generating report: {$report} \n");

                /** @var BaseReport $report */
                $report = new $report(
                    $this->getIso(),
                    $this->getAllLicSettings(),
                    [
                        'period_start' => $start,
                        'period_end' => $end,
                        'frequency' => $frequency,
                        'game_types' => $game_types,
                    ]
                );

                $this->printLog("Version selected: {$report->getXmlVersion()}".PHP_EOL);

                if (!empty($rectify)) {
                    $report->rectifyReport($rectify);
                }

                foreach ($report->getFiles() as $file) {
                    /** @var BaseReport $file */
                    foreach ($export_type as $type) {
                        $file_path = $file->saveFile($type);

                        $this->printLog("File path: {$file_path} \n");

                        $this->reportLog("INFO :: New file is generated: {$file_path}. {$log_report_message}");

                        $generated_files[] = $file_path;
                    }

                    $this->printLog("New file generated \n");
                }
            }

            $this->reportLog('INFO :: Finish generateICSReports. ' . $log_message);

            return $generated_files;
        } catch (\Exception $e) {
            $this->reportLog("ERROR :: generateICSReports. Message: {$e->getMessage()}");

            throw $e;
        }

    }

    /**
     * Regenerate report for rectification purposes
     *
     * @param $report_uid
     * @param array $export_type
     * @param array $game_types
     *
     * @return array
     * @throws JsonException
     */
    public function rectifyReport($report_uid, array $export_type = [BaseReport::GENERATE_ZIP], array $game_types = []): array
    {
        $log_message = json_encode(compact('report_uid', 'export_type'), JSON_THROW_ON_ERROR);

        $this->reportLog("INFO :: Start rectifyReport. {$log_message}");

        if (empty($report_uid)) {
            throw new Exception("Report ID is missing.");
        }

        $regulation = $this->getLicSetting('regulation');
        $report = phive('SQL')->readOnly()->loadAssoc("
            SELECT * FROM external_regulatory_report_logs
            WHERE regulation = '$regulation'
                AND unique_id = '$report_uid'
            LIMIT 1
        ");
        if (empty($report)) {
            throw new Exception("Report not found for ID $report_uid.");
        }

        try {
            $frequency = json_decode($report['log_info'])->frequency;
        } catch (Exception $e) {
            throw new Exception("Unable to extract frequency. Error: {$e->getMessage()}");
        }

        $result = $this->generateICSReports(
            $start = $report['report_data_from'],
            $end = $report['report_data_to'],
            $frequency,
            $reports = ['ES\\ICS\\Reports\\' . $report['report_type']],
            $export_type,
            $rectify = [
                'id' => $report_uid,
                'date' => $report['created_at']
            ],
            $game_types
        );

        $this->reportLog("INFO :: Finish rectifyReport. {$log_message}");

        return $result;
    }

    /**
     * Generate daily ICS reports
     *
     * @param string|null $start
     * @param string|null $end
     * @param array $export_type
     * @param array $rectify
     * @param array $reports
     * @param array $game_types
     *
     * @return array
     * @throws JsonException
     */
    public function generateDailyICSReports(string $start = null, string $end = null, array $export_type = [BaseReport::GENERATE_ZIP], array $rectify = [], array $reports = [], array $game_types = []): array
    {
        $log_message = json_encode(compact('start', 'end', 'export_type', 'rectify'), JSON_THROW_ON_ERROR);

        $this->reportLog("INFO :: Start generateDailyICSReports. {$log_message}");

        if (!$start) {
            $start = phive()->yesterday('Y-m-d 00:00:00');
            $end = phive()->yesterday('Y-m-d 23:59:59');
        }

        if (empty($reports)) {
            $reports = Info::getDailyReportClasses();
        }

        $frequency = ICSConstants::DAILY_FREQUENCY;

        $result = $this->generateICSReports($start, $end, $frequency, $reports, $export_type, $rectify, $game_types);

        $this->reportLog("INFO :: Finish generateDailyICSReports. {$log_message}");

        return $result;
    }

    /**
     * Generate monthly ICS reports
     *
     * @param string|null $start
     * @param string|null $end
     * @param array $export_type
     * @param array $rectify
     * @param array $reports
     * @param array $game_types
     *
     * @return array
     * @throws JsonException
     */
    public function generateMonthlyICSReports(string $start = null, string $end = null, array $export_type = [BaseReport::GENERATE_ZIP], array $rectify = [], array $reports = [], array $game_types = []): array
    {
        $log_message = json_encode(compact('start', 'end', 'export_type', 'rectify'), JSON_THROW_ON_ERROR);

        $this->reportLog("INFO :: Start generateMonthlyICSReports. {$log_message}");

        if (!$start) {
            $start = date("Y-m-d 00:00:00", strtotime("first day of previous month"));
            $end = date("Y-m-d 23:59:59", strtotime("last day of previous month"));
        }

        if (empty($reports)) {
            $reports = Info::getMonthlyReportClasses();
        }

        $frequency = ICSConstants::MONTHLY_FREQUENCY;

        $result = $this->generateICSReports($start, $end, $frequency, $reports, $export_type, $rectify, $game_types);

        $this->reportLog("INFO :: Finish generateMonthlyICSReports. {$log_message}");

        return $result;
    }

    /**
     * Helper to quickly disable debug echo
     *
     * @param $str
     */
    private function printLog($str)
    {
        if (empty($this->getLicSetting('ICS')['enable_log'])) {
            return;
        }
        echo $str;
    }

    /**
     * Generate report if all conditions are passed
     *
     * @param $now
     * @param $report
     * @param string|null $default_start
     * @param array $export_type
     * @return array
     * @throws Exception
     */
    public function generateRealTimeReport($now, $report, $default_start = null, array $export_type = [BaseReport::GENERATE_ZIP]): array
    {
        $now = (new DateTime($now ?? 'now'))->format(ICSConstants::DATETIME_DBFORMAT);

        $this->printLog("{$now} Generating for interval {$default_start} - {$now} \n");
        // generate report
        return $this->generateICSReports(
            $start = $default_start,
            $end = $now,
            $frequency = ICSConstants::REALTIME_FREQUENCY,
            $reports = [$report],
            $export_type
        );
    }

    /**
     * Run callback only if there's no entry in progress for provided progress_key
     *
     * @param $progress_key
     * @param $callback
     */
    public function withProgress($progress_key, $callback)
    {
        // currently running process didn't finish
        if (!empty(phive()->getMiscCache($progress_key))) {
            $this->reportLog("INFO :: Skip withProgress. {$progress_key}");

            return;
        }

        phive('SQL')->insertArray('misc_cache', ['id_str' => $progress_key, 'cache_value' => '1']);
        try {
            $callback();
        } catch (Exception $e) {
            $this->printLog("There was an error in {$progress_key}\n");
        }

        phive('SQL')->delete('misc_cache', ['id_str' => $progress_key]);
    }

    /**
     * Generate each report every 15 minutes or after 500 entries since the last report
     *  - will run every 1 minute and store state
     *
     * @param string|null $period_end
     * @param string|null $period_start
     * @param array $export_type
     * @return array
     * @throws Exception
     */
    public function generateRealTimeReports(?string $period_end = null, ?string $period_start = null, array $export_type = [BaseReport::GENERATE_ZIP]): ?array
    {
        $res = [];

        if(empty($period_end)){
            $period_end = date(ICSConstants::DATETIME_DBFORMAT);
        }

        $reportClasses = Info::getRealTimeReportClasses();
        /** @var BaseProxy $firstClass */
        $firstClass = reset($reportClasses);

        $period_start = $period_start ?? $this->getLatestReportDataToByType($firstClass::SUBTYPE);

        $this->reportLog("INFO :: Start generateRealTimeReports: `{$period_start}` - `{$period_end}`");

        $should_report = false;
        if ($period_start){
            $ju_report = new $firstClass(
                $this->getIso(),
                $this->getAllLicSettings(),
                [
                    'period_start' => $period_start,
                    'period_end' => $period_end,
                    'frequency' => '',
                ]
            );
            $should_report = $ju_report->shouldReportNow(new DateTime($period_end), $period_start);
        }

        if ($period_start === null || !$should_report) {
            $this->reportLog("INFO :: Skip generateRealTimeReports: `{$period_start}` - `{$period_end}`");
            return null;
        }

        foreach ($reportClasses as $reportClass) {
            $this->withProgress('generate-real-time-ICS-reports-'.$reportClass, function () use ($period_end, $period_start, &$res, $export_type, $reportClass) {
                $res = array_merge($res, $this->generateRealTimeReport($period_end, $reportClass, $period_start, $export_type));
            });
        }

        $this->reportLog("INFO :: Finish generateRealTimeReports: `{$period_start}` - `{$period_end}`");

        return $res;
    }

    /**
     * Run every minute
     * Generate real time reports
     *
     * @throws Exception
     */
    public function onEveryMinReporting()
    {
        $this->handleRealtimeReports();
    }

    /**
     * Zip specific directory as requested in 4.3 Packaging of the gambling data
     *
     * @param string $target
     */
    public function archiveDay(string $target = ''): void
    {
        $this->reportLog("INFO :: Start archiveDay. {$target}");

        if (empty($target)) {
            $target = phive()->yesterday('Ymd');
        }

        $dir = implode('/', array_filter([
            $this->getLicSetting('ICS')['export_folder'],
            $this->getLicSetting('operatorId'),
            'JU'
        ]));

        if (!is_dir("{$dir}/{$target}")) {
            $this->reportLog("INFO :: Skip archiveDay. {$target}");

            return;
        }

        //<OperatorID>_<StorageID>_<Type>_DAILY_<Date>.<zip>
        $filename = implode(
            '_',
            [
                $this->getLicSetting('operatorId'),
                $this->getLicSetting('storageId'),
                'JU',
                'DIARIO',
                $target
            ]
        );

        $script = "
            cd $dir
            mkdir -p Anteriores
            mkdir -p $target
            zip -r -m Anteriores/$filename.zip $target
        ";

        shell_exec($script);

        $this->reportLog("INFO :: Finish archiveDay. {$target}");
    }

    /**
     * @param string $type
     *
     * @return string|null
     * @throws Exception
     */
    private function getLatestReportDataToByType(string $type): ?string
    {
        $sql = "
                SELECT max(report_data_to) as report_data_to
                FROM
                    external_regulatory_report_logs
                WHERE
                    report_type= '{$type}'
                LIMIT 1
                ";

        $report = phive('SQL')->load1DArr($sql, 'report_data_to');
        if (!empty($report[0])) {
            //start checking from the next second, to avoid including a game session in 2 reports
            $from_date = (new DateTime($report[0]))->add(new DateInterval('PT1S'))->format(ICSConstants::DATETIME_DBFORMAT);
        } else {

            $from_date = null;
            // This is a hack for the first JUC generation. Delete in the future.
            if ($type == 'JUC') {
                $from_date = $this->getLatestReportDataToByType('JUD');
            }
        }
        return $from_date;
    }

    /**
     * Returns the service that gives access to Session functionality
     *
     * @return SessionService
     */
    public function getExternalSessionService()
    {
        if (empty($this->ext_session_service)) {
            $this->ext_session_service = new SessionService();
        }
        return $this->ext_session_service;
    }

    /**
     * Return the external game session details (open, close and increments) of a user for a given period
     *
     * @param integer|string $user_id The user id
     * @param string $start_date The starting datetime, in SQL accepted format ('yyyy-mm-dd hh:mm:ss')
     * @param string $end_date The ending datetime, in SQL accepted format ('yyyy-mm-dd hh:mm:ss')
     * @param string $page current pagination page
     *
     * @return array An array of increments ([[session_id, participation_id, session_status, game_name, created_at,
     *               balance, amount, win]])
     */
    public function getGameSessionBalancesByUserId($user_id, $start_date, $end_date, $page = null)
    {
        return $this->getExternalSessionService()->getGameSessionBalancesByUserId($user_id, $start_date, $end_date, $page);
    }
    /**
     * Confirm new game session when one is loading or already open
     *
     * @param  DBUser  $user
     * @param  array  $post
     *
     * @return array
     */
    public function enforceUniqueGameSession(DBUser $user, array $post): array
    {
        if (!empty($post['has_open_session'])) {
            lic('endOpenParticipation', [$user], $user);
        } elseif (!$this->showSetSessionBalance($user) || !empty($this->getLicSessionService($user)->getAllOpenSessionsBalance($user))) {
            $this->logger->debug([
                    'type' => 'popup',
                    'user' => $user,
                    'post' => $post,
            ]);

            return [
                'success' => false,
                'popup'   => 'game_session_close_existing_and_open_new_session_prompt',
                'args'    => ['confirm_new_session' => true]
            ];
        }

        return ['success' => true];
    }

    /**
     * Triggered by cron command
     *
     * @return void
     * @throws Exception
     */
    public function onEveryHour(): void
    {
        $this->externalSelfExclusion();
        $this->suspendIntensiveGamblers();
    }

    /**
     * Triggered by cron command
     *
     * @param string $date
     *
     * @return void
     * @throws Exception
     */
    public function onEveryday($date = ''): void
    {
        $this->externalVerifyDeathChanges();
        $this->sendOneYearPasswordChangeMail();
    }

    public function sendOneYearPasswordChangeMail()
    {
        $ESPlayers = phive('SQL')->shs()
            ->loadArray("SELECT users.id, IFNULL(us.value, DATE_SUB(CURDATE(), INTERVAL 1 YEAR)) AS passresetdate
                    FROM users
         LEFT JOIN users_settings us on users.id = us.user_id AND us.setting = 'last_pwd_update'
         LEFT JOIN users_settings us2 on users.id = us2.user_id AND us2.setting = 'reset_password_email_sent'
        WHERE users.country = 'ES'
          AND users.register_date <= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
          AND us2.value IS NULL
        HAVING passresetdate <= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)");

        foreach ($ESPlayers as $ESPlayer) {
          $user = cu($ESPlayer);
          $result = phive('MailHandler2')->sendMail('password-reminder-change', $user, null, 'dgoj');
          if (!$result){
              $this->logger->info('Failed to send password reset mail for user');
              phive('DBUserHandler')->logAction($user->id, 'Failed to send password reset mail for user', 'password-reminder-change');

              continue;
          }
          $user->setSetting('reset_password_email_sent', 1);
          phive('DBUserHandler')->logAction($user->id, 'Password Email reminder sent to the user', 'password-reminder-change');

        }
    }

    /**
     * Triggered by cron command
     *
     * @param string $date
     *
     * @return void
     * @throws Exception
     */
    public function onMidnight($date = ''): void
    {
        $this->handleReportsICS();
    }

    /**
     * Get user_ids with nid keys
     *
     * @param array<string> $user_nids
     *
     * @return array<string, string>
     */
    private function getUserIdsByNids(array $user_nids): array
    {
        $in = phive('SQL')->makeIn($user_nids);
        $query = "
            SELECT
                u.id, u.nid
            FROM
                users AS u
            WHERE
                u.nid IN ($in)
        ";
        $users = phive('SQL')->readOnly()->shs()->load1DArr($query, 'id', 'nid');

        return $users === false ? [] : $users;
    }

    /**
     * @return bool
     */
    private function isReportEnabled(): bool
    {
        return !empty($this->getLicSetting('ICS')['enable_reports']);
    }

    /**
     * @throws Exception
     */
    private function handleReportsICS(): void
    {
        $this->reportLog('INFO :: Start handleReportsICS');

        if (!$this->isReportEnabled()) {
            $this->reportLog('INFO :: ICS reports are disabled. handleReportsICS');

            return;
        }

        $this->archiveDay();

        $this->generateDailyICSReports();

        if ((int)date('d') === 1) {
            $this->generateMonthlyICSReports();
        }

        $this->reportLog('INFO :: Finish handleReportsICS');
    }

    /**
     * @param string $message
     */
    public function reportLog(string $message): void
    {
        if ($this->report_log === null) {
            $lic_settings = $this->getAllLicSettings();

            if (empty($lic_settings['log_folder'])) {
                return;
            }

            $this->report_log = new ReportLog($lic_settings['log_folder'], $this->getIso(), $lic_settings['logs_enabled'] ?? true, 'log_ics_');
        }

        $this->report_log->log($message);
    }

    private function handleRealtimeReports(): void
    {
        if (!$this->isReportEnabled()) {
            $this->reportLog('INFO :: ICS reports are disabled. handleRealtimeReports');

            return;
        }

        $this->generateRealTimeReports();
    }

    /**
     * Get account verification data required in the form
     *
     * @param DBUser $user
     * @return array|string[][]
     */
    public function accountVerificationData($user): array
    {
        if ($this->showAccountVerificationOvertime($user)) {
            return [
                'paragraphs' => [
                    'acc.verification.documents.reminder.p1.overtime.es',
                    'acc.verification.documents.reminder.p2.overtime.es'
                ]
            ];
        }
        return [
            'days_left' => $this->getDaysToProvideDocuments(),
            'paragraphs' => [
                'acc.verification.documents.reminder.p1.es',
                'acc.verification.documents.reminder.p2.es'
            ]
        ];
    }

    /**
     * Check if account verification time expired
     *
     * @param $user
     * @return bool
     */
    public function showAccountVerificationOvertime($user): bool
    {
        if(empty($user)) {
            return false;
        }

        $user = cu($user);

        if (empty($user) || $user->isVerified()) {
            return false;
        }

        return $this->getTimeSinceRegistration($user)['days'] > $this->getDaysToProvideDocuments();
    }

    /**
     * Control if player should see the account verification reminder
     *
     * @param mixed $user
     * @param bool $show_processing
     * @return bool
     */
    public function showAccountVerificationReminder($user = null, $show_processing = false): bool
    {
        $user = cu($user);
        if (empty($user)) {
            return false;
        }

        $show_reminder = $user->hasDeposited()
            && !$this->showAccountVerificationOvertime($user)
            && !$user->isBlocked()
            && !$user->isVerified();

        if (!$show_reminder) {
            return false;
        }

        $documents = ['idcard-pic', 'addresspic', 'bankpic'];
        $document_status = phive('Dmapi')->getUserDocumentsGroupedByTagStatus($user->getId());

        // some documents have not been submitted
        if (phive('Dmapi')->documentsHaveStatus($document_status, $documents, 'requested', 'some')) {
            return true;
        }

        // all documents have been approved
        if (phive('Dmapi')->documentsHaveStatus($document_status, $documents, 'approved')) {
            return false;
        }

        // some documents are still in processing
        if (phive('Dmapi')->documentsHaveStatus($document_status, $documents, 'processing', 'some')) {
            return $show_processing;
        }

        return true;
    }

    /**
     * Returns true if the current game (demo or real) must be blocked. See phive/modules/Micro/MicroGames.php::onPlay
     * > "if (lic('noDemo'))" If we are explicitly loading the game in demo mode then check settings to prevent for
     * example a logged-in MT player from hacking the URL and illegally launching demo mode. If the player is logged in
     * then the game can proceed. If the player is not logged in then it depends on the setting for no-demo countries.
     * If player is not logged in and country is in the no demo countries we disallow demo play in order to protect the
     * children.
     *
     * @param bool $show_demo Flag indicating if the game is being loaded in demo mode.
     * @return bool redirect to register page if game should be blocked otherwise false.
     */
    public function noDemo(bool $show_demo = false): bool
    {
        if (isLogged() && !$show_demo) {
            return false;
        }

        $no_demo_countries = phive('Config')->valAsArray('countries', 'no-demo-play');

        $no_demo = current($no_demo_countries) == 'ALL' ? true : in_array(cuCountry('', false), $no_demo_countries);

        if ($no_demo) {
            if (phive()->isMobile()){
                phive('Redirect')->jsRedirect('/mobile/register', 'es', false, [], true);
            } else {
                phive('Redirect')->jsRedirect('/?signup=1', 'es', false, [], true);
            }
        }

        return false;
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

        return parent::isPlayBlocked($user) || !$this->userHasPlayableStatus($user) || $this->isUserBlocked($user);
    }

    /**
     * Does user have blocking, restrict, play blocked or excluding settings
     *
     * @param DBUser|string|int|null $user
     *
     * @return bool
     */
    private function isUserBlocked($user): bool
    {
        $user = cu($user);

        if (empty($user)) {
            return true;
        }

        // 'play_block' & 'restrict' are checked in the parent::isPlayBlocked($user)
        $setting_names = [
            'super-blocked', 'unexclude-date', 'unlock-date', 'manual-fraud-flag', 'similar_fraud', 'external-excluded',
        ];

        $settings = $user->getSettingsIn($setting_names, true);

        if (!empty($settings['unexclude-date']) && $settings['unexclude-date']['value'] < phive()->hisNow()) {
            unset($settings['unexclude-date']);
        }

        if (!empty($settings['unlock-date']) && $settings['unlock-date']['value'] < phive()->hisNow()) {
            unset($settings['unlock-date']);
        }

        $is_excluded = !empty($settings['unexclude-date']) || !empty($settings['external-excluded']);
        $is_fraud = !empty($settings['similar_fraud']) || !empty($settings['manual-fraud-flag']);
        $is_blocked = !empty($settings['unlock-date']) || !empty($settings['super-blocked']);

        return $is_excluded || $is_fraud || $is_blocked;
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

        $user_status = $user->getSetting('current_status');
        $has_playable_status = false;

        if ($user_status === UserStatus::STATUS_ACTIVE) {
            $has_playable_status = true;
        } elseif ($user_status === UserStatus::STATUS_PENDING_VERIFICATION) {
            $date_diff = $this->getTimeSinceRegistration($user);
            $expired_time = $date_diff['days'] < $this->getDaysToProvideDocuments();

            $required_documents_types = $this->getLicSetting('required_documents_types');
            $documents_uploaded_or_approved = $this->documentsUploadedOrApproved($user, $required_documents_types);

            $has_playable_status = !$documents_uploaded_or_approved && $expired_time || $documents_uploaded_or_approved;
        }

        return $has_playable_status;
    }


    /**
     * Determines whether or not the player can use / activate the award in question or not,
     * will return a translated error message in case the activation is refused.
     *
     * Here we first check that the award is an FRB bonus, after that we check how many days the
     * player has been registered, if less then 30 days we return the difference in an error message
     * encouraging the player to try again in such many days.
     *
     * @param Dbuser $u_obj The user object.
     * @param array $award The award.
     * @param bool $translate.
     *
     * @return string|bool String if failure, true if all good.
     */
    public function handleUseAward($u_obj, $award, bool $translate = true){
        $diff_days = $this->getAwardExpiryExtension($u_obj, $award);
        if(empty($diff_days)){
            return true;
        }

        $alias = 'can.use.award.license.refusal';
        if ($translate) {
            return t2($alias, [$diff_days]);
        }

        return $alias . "::$diff_days";
    }

    /**
     * Here we first check that the award is in a configured list of types, after that we check how many days the
     * player has been registered, if less then X days we return the difference, othwerwise 0.
     *
     * @param Dbuser $u_obj The user object.
     * @param array $award The award.
     *
     * @return int
     */
    public function getAwardExpiryExtension($u_obj, $award){
        $types = $this->getLicSetting('reg_days_before_promo_award_types');

        if(empty($types)){
            // Nothing to do.
            return 0;
        }

        if(!in_array($award['type'], $types)){
            // This type of award is not affected so nothing to check / do.
            return 0;
        }

        $reg_days_before_promo = $this->getLicSetting('reg_days_before_promo');

        if(empty($reg_days_before_promo)){
            // Nothing to do.
            return 0;
        }

        $days_since_reg = $this->getTimeSinceRegistration($u_obj)['days'];

        if($days_since_reg >= $reg_days_before_promo){
            // The player has been registered for longer than the threshold (eg 30 days) so nothing to do / check.
            return 0;
        }

        return $reg_days_before_promo - $days_since_reg;
    }

    /**
     * @param string $user_status
     *
     * @return bool
     */
    public function isActiveStatus(string $user_status): bool
    {
        return $user_status === UserStatus::STATUS_ACTIVE || $user_status === UserStatus::STATUS_PENDING_VERIFICATION || $user_status === UserStatus::STATUS_UNDER_INVESTIGATION;
    }

    /**
     * Skip unVerify for ES users as it changes the user status back to pending.
     *
     * @return bool
     */
    public function skipUnVerify(): bool
    {
        return true;
    }

    /**
     * Specialized function to log new users_games_sessions and ext_game_participations
     * sessions
     * @param string $elem
     * @param string $message
     * @param User $user
     * @param int $level
     */
    public function logGS(string $elem, string $message, User $user, int $level) : void
    {
        if($this->getLicSetting('enable_game_sessions_logging')) {
            $this->logger->log(
                $elem,
                [
                    'message' => $message,
                    'user' => $user->getId()
                ],
                $level);
        }
    }

    /**
     * Returns the game data that the user is playing (from the ext_game_participation)
     *
     * @param $post
     * @return array|false[]
     */
    public function ajaxGetCurrentGame($post): array
    {
        $user = cu();
        $ext_participation = $this->getLicSessionService($user)->getOpenParticipation($user);
        if (empty($ext_participation)) {
            return ['success' => true, 'current_game' => []];
        }

        $user_game_session = phive('SQL')->sh($user->getId())->loadAssoc(NULL, 'users_game_sessions', ['id' => $ext_participation['user_game_session_id']]);
        if (empty($user_game_session)) {
            return ['success' => true, 'current_game' => []];
        }

        $current_game = phive('MicroGames')->getByGameRef(
            $user_game_session['game_ref'],
            $user_game_session['device_type_num'],
            $user
        );

        $desktop_game = [];
        $is_mobile_game = empty($current_game['game_url']) || $current_game['device_type_num'] != 0;

        if ($is_mobile_game) {
            $desktop_game = phive('MicroGames')->getByMobileId($current_game['id']);
        }

        if ($is_mobile_game && !empty($desktop_game)) {
            $current_game['game_url'] = $desktop_game['game_url'];
        }

        return ['success' => true, 'current_game' => $current_game ?? []];
    }

    /**
     * Save Rg test response
     *
     * @param $post
     * @return array|false[]
     */
    public function ajaxSaveRgTestResponse($post): array
    {
        $user = cu();
        self::$exceeds_limit = true;
        if (!empty($user) && in_array($post['result'], ['fail', 'pass'])){
            $user->setSetting('rg_review_state', 1);
            $this->sendEmailToRGTeam($user, $post['result'], ($post['type'] == 'increase') ? 'increase' : 'remove' );
            if ($post['result'] == 'pass'){
                $new_limits =  $_SESSION['new_limit_data'];
                $filtered_limits = array_filter($new_limits, function ($limit) {
                    return $limit['type'] === 'deposit';
                });
                if ($post['type'] == 'increase'){
                    $user->deleteSetting('deposit_removal_check');
                    if (!rgLimits()->hasLimits($user, 'deposit')) {
                        foreach ($filtered_limits as $new_limit) {
                           $this->addNewLimit($user, $new_limit['type'], $new_limit['time_span'] , $new_limit['limit'] * 100 );
                        }
                    }else{
                        foreach ($filtered_limits as $new_limit) {
                            $this->rgLimits()->changeLimit($user, $new_limit['type'], $new_limit['limit'] * 100, $new_limit['time_span'] );
                        }
                    }

                }else{
                    $user->setSetting('deposit_removal_check', 1);
                    $this->rgLimits()->removeLimit($user, 'deposit');
                }

                unset($_SESSION['new_limit_data']);
            }
            phive('DBUserHandler')->logAction($user->id, $post['result'], 'rg_test');
            return ['success' => true, 'msg' => ''] ;
        }
        return ['success' => false];
    }

    /**
     * Checks if the user has performed the rg test and has answered yes to something
     * and sets a session variable that will be used to disable deposit limit change files
     *
     * @param User $user
     * @return false
     */
    public function disableDepositFieldsOptions(User $user)
    {
        $rg_data = phive('UserHandler')->getUserActions($user, ['rg_test'],
            [], ['created_at' => 'desc'], 1);
        $rg_admin_data = phive('UserHandler')->getUserActions($user, ['rg_test_confirmation'],
            [], ['created_at' => 'desc'], 1);
        $rg_admin_data_descr = json_decode($rg_admin_data[0]['descr']);
        if (!empty($rg_data)){
            if ($rg_data[0]['descr'] == 'fail'){
                return $rg_data[0]['created_at'];
            }
            if (!empty($rg_admin_data)&& $rg_data[0]['descr'] == 'pass'&&
                $rg_admin_data_descr->result == 'fail' && $rg_data[0]['id'] == $rg_admin_data_descr->id){
                return $rg_admin_data[0]['created_at'];
            }
        }

        return false;
    }

    public function sendEmailToRGTeam($user, $result, $type)
    {
        $sender_email = $this->getLicSetting('sender_email');
        $recipient_emails = $this->getLicSetting('recipient_emails');

        $subject = 'User with ID '.$user->getId().' tried to '. $type . ' deposit limit';
        $plaintext_body = 'User with ID '.$user->getId().' tried to ' . $type . ' deposit limit and the test result was '.$result;
        $html_body =  '<h1>'. $plaintext_body .'</h1>';
        foreach ($recipient_emails as $recipient_email){
            phive( 'MailHandler2' )->saveRawMail($subject, $html_body, $sender_email, $recipient_email);
        }
    }

    public function getRgChangeStamp($modifier, $type = null, $iso = null)
    {

       if (self::$exceeds_limit){
           return '';
       }
        return parent::getRgChangeStamp($modifier, $type, $iso);

    }

    /**
     * Adds a new limit to new_lim column instead of cur_limit
     *
     * @param DBUser $u_obj The DBUser object.
     * @param string $type The type such as deposit, wager or loss.
     * @param string $time_span An enum string, can be day, week or month.
     * @param int $limit The limit amount.
     *
     * @return mixed If the insert was not successful we return false, the unique id of the inserted row otherwise.
     */
    public function addNewLimit($u_obj, $type, $time_span, $limit)
    {
        $insert = [
            'user_id'    => $u_obj->getId(),
            'new_lim'    => $limit,
            'time_span'  => $time_span,
            'progress'   => 0,
            'updated_at' => phive()->hisNow(),
            'created_at' => phive()->hisNow(),
            'type'       => $type
        ];

        return  phive('SQL')->sh($insert)->insertArray('rg_limits', $insert);
    }

    /**
     * Returns the list of values from @see getColsForDailyStats() that count as bonus/rewards only for ICS
     * @return string[]
     */
    public function getAdditionalReportingBonusTypes(): array
    {
        return [
                'paid_loyalty'
        ];
    }

    /**
     * This function returns the `users_game_sessions` entry.
     *
     * If the current session is closed or for any reason it can not be found,
     * this function will try to return `users_game_sessions` entry using
     * the `ext_game_participation` entry (participation['session_id']).
     *
     * This function should be called only from handleWin, because is the only moment in which
     * we don't want to create new `users_game_sessions` entries, to satisfy the
     * 1 to 1 relationship between `users_game_sessions` and `ext_game_participations`.
     *
     *
     * In the cases in which we are processing a win from previous game sessions
     * if a new one has already started, we need to find proper game that the win belongs to.
     * This in order to avoid `users_game_sessions` entries with `win_amount != 0 and bet_amount = 0`
     *
     * @param array $ins
     * @param $user
     * @param $participation
     * @param $game
     * @param bool $isBos
     * @return object|null
     */
    public function getGsessByParticipation(array $ins, $user, $participation, $game, bool $isBos): ?array
    {
        $user = cu($user);
        if (!$this->hasGameplayWithSessionBalance($isBos) || empty($participation)) {
            return null;
        }

        $ugsByParticipation = $this->getLicSessionService($user)->getGameSessionByParticipation($participation, false);
        if (!empty($ugsByParticipation)) {
            $this->logger->debug(__METHOD__, [
                'game_tag' => $game['tag'] ?? '',
                'game' => $game['ext_game_name'] ?? '',
                'ugs_id' => $ugsByParticipation['id'] ?? '',
                'participation' => $participation['id'] ?? '',
                'user_id' => is_object($user) ? $user->getId() : $user['id'],
            ]);

            return $ugsByParticipation;
        }

        $ugs = phive('Casino')->getGsess($ins, $user, false);

        $this->logger->debug(__METHOD__, [
            'ugs_id' => null,
            'game_tag' => $game['tag'] ?? '',
            'game' => $game['ext_game_name'] ?? '',
            'participation' => $participation['id'] ?? '',
            'user_id' => is_object($user) ? $user->getId() : $user['id'],
        ]);

        return $ugs;
    }

    /**
     * To update the result_amount and balance_end when our system receives
     * win calls after users_game_sessions entry has been closed, and returns true if the operation was
     * successfully.
     *
     * It usually happens on live-casino, most commonly for roulette games where the user can place the bet
     * and close the browser, reload the tab, etc. While the ball is still spinning in the roulette.
     *
     * @param string $type "win" will increase the balance_end, and "bet" the opposite.
     * @param $ugs
     * @param array $ins
     * @param $user
     * @param $participation
     * @param $game
     * @param bool $isBos
     * @return bool
     */
    public function gSessionHandleLateCall($type = '', $ugs, array $ins, $user, $participation, $game, bool $isBos): bool {
        if (empty($ugs) || $ugs['end_time'] === '0000-00-00 00:00:00') {
            // If the session is still opened we don't need to handle this logic.
            return false;
        }

        // We need to load again the users_game_session with fresh data because it was updated on Casino::handleWin()
        $ugs = $this->getGsessByParticipation($ins, $user, $participation, $game, $isBos);
        if (empty($ugs)) {
            return false;
        }

        $result = $ugs['win_amount'] - $ugs['bet_amount'];

        if (!in_array($type, ['win', 'bet'])) {
            return false;
        }

        $balanceEnd = $type === 'bet' ? $ugs['balance_end'] - $ins['amount'] : $ugs['balance_end'] + $ins['amount'];

        return phive('SQL')->sh($ugs)->query("
            UPDATE users_game_sessions SET
               result_amount = {$result}, balance_end = {$balanceEnd}, end_time = CURRENT_TIMESTAMP()
            WHERE id = {$ugs['id']}
        ");
    }

  /**
   * Sets the session balances, logs the results for failures.
   * @param $post
   * @return array|bool[]
   */
    public function ajaxSetExternalGameSessionBalance($post)
    {
        $res = $this->ajaxUpdateExternalGameSessionBalance($post);
        $user = cu();

        if(!empty($post) && !empty($user)) {
            $message = sprintf('User started new session limit (Time limit:%s - Spend Limit:%s - Restrict Future Sessions:%s)',
                $post['gameLimit'],
                $post['balance'],
                $post['restrictFutureSessions']
            );
            phive('UserHandler')->logAction($user,$message, 'setting-session-limit');

            if(empty($res) || $res['success'] === false) {
                $res['ext_session_fail'] = [
                    'user_balance' => $user->getBalance(),
                    'meta_session_info' => $this->getMetaGameSession($user->getId()),
                    'open_participation' => $this->getOpenParticipation($user),
                    'post' => $post
                ];
            }
        }

        return $res;
    }

    /**
     * @param string|null $date now|Y-m-d
     *
     * @return void
     */
    public function onMondayMidnight(?string $date = null): void
    {
        foreach ($this->getLicSetting('intensive_gambling_signs') as $setting) {
            phive('Cashier/Rg')
                ->checkIntensiveGamblerSignsByAccumulatedNetLoss(
                    $this->getIso(),
                    $setting['age'],
                    $setting['age_operator'],
                    $setting['consecutive_weeks'],
                    $setting['net_loss_thold'],
                    $date ?? 'now'
                );
        }

        foreach ($this->getLicSetting('non_intensive_gambling_signs') as $setting) {
            phive('Cashier/Rg')
                ->revocationOfIntensiveGamblerSignsByAccumulatedNetLoss(
                    $this->getIso(),
                    $setting['age'],
                    $setting['age_operator'],
                    $setting['consecutive_weeks'],
                    $setting['net_loss_thold'],
                    $date ?? 'now'
                );
        }
        $this->resetIntensiveGamblingCheck();

    }

    /**
     * Returns the date of trigger activation otherwise false
     *
     * @param DBUser $user
     *
     * @return string|bool
     */
    public function isIntensiveGambler(DBUser $user)
    {
        $trigger = phive('SQL')->sh($user->getId())->loadObject(
            "SELECT created_at FROM triggers_log WHERE user_id = {$user->getId()} AND
                                  trigger_name = 'RG65' ORDER BY created_at DESC LIMIT 1"
        );

        if(isset($trigger->created_at)){
            return $trigger->created_at;
        }

        return false;
    }

    /**
     * Is the warning popup should be shown to inform customer about necessary to decrease gaming activity
     *
     * @return bool
     */
    public function isIntensiveGamblerPopupEnabled(): bool
    {
        return $this->getLicSetting('rg_65_info')['popup_active'] ?? false;
    }

    /**
     * For Spanish users the user block after external self exclusion ends should be removed manually
     * @param $user
     * @return bool
     */
    public function shouldRemoveUserBlockAfterExternalSelfExclusionEnds($user): bool
    {
        return false;
    }

    public function suspendIntensiveGamblers(): void
    {
        $hours = (int) phive('Config')->getValue('RG', 'RG65-suspend-account-in-hours', 72);
        $users = phive('SQL')->shs()->loadArray(
            "SELECT u.id, us_warning_shown.value as warning_shown_at FROM users as u
             JOIN users_settings as us_warning_shown ON (u.id = us_warning_shown.user_id
             AND us_warning_shown.setting = 'intensive_gambler_warning_shown' AND us_warning_shown.value <= DATE_SUB(NOW(), INTERVAL {$hours} HOUR))
             LEFT JOIN users_settings as us_status ON u.id = us_status.user_id AND us_status.setting = 'current_status'
             LEFT JOIN users_settings as us_warning_accepted ON (u.id = us_warning_accepted.user_id AND us_warning_accepted.setting = 'intensive_gambler_warning_accepted')
             WHERE us_warning_accepted.value IS NULL AND (us_status.value is NULL OR us_status.value IN ('PENDING_VERIFICATION', 'ACTIVE'));"
        );

        foreach ($users as $user_data) {
            $user = cu($user_data['id']);
            $old_user_status = $user->getSetting('current_status');
            $blocked_status = UserStatus::STATUS_BLOCKED;
            phive("DBUserHandler")->addBlock($user, 17);
            phive('DBUserHandler')->logAction(
                    $user->getId(),
                    "User's status changed from {$old_user_status} to {$blocked_status} due to ignoring intensive gambling check"
            );
        }
    }

    /**
     * To start the Intensive Gambling awareness process from scratch.
     * 1) Remove users settings related to Intensive gambling awareness:
     * - intensive_gambler_warning_shown
     * - intensive_gambler_warning_accepted
     * 2) Remove 'intensive_gambler_warning_shown' from Redis memory
     *
     * @return void
     */
    public function resetIntensiveGamblingCheck(): void
    {
        $users = phive('SQL')->shs()->loadArray(
                "SELECT user_id FROM users_settings
                 WHERE setting IN ('intensive_gambler_warning_shown', 'intensive_gambler_warning_accepted')
                 GROUP BY user_id;"
        );

        foreach ($users as $user) {
            $db_user = cu($user['user_id']);
            $db_user->deleteSetting('intensive_gambler_warning_shown');
            $db_user->deleteSetting('intensive_gambler_warning_accepted');
            phMdelShard('intensive_gambler_warning_shown', $user['user_id']);
        }
    }
}
