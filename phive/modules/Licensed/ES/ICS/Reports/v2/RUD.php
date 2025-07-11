<?php

namespace ES\ICS\Reports\v2;

use ES\ICS\Constants\ICSConstants;
use ES\ICS\Validation\Traits\DeviceTrait;
use Exception;
use ES\ICS\Reports\BaseReport;

class RUD extends BaseReport
{
    use DeviceTrait;

    public const USER_DATA_NEW = 'A';
    public const USER_DATA_MODIFIED = 'S';
    public const USER_DATA_NON_CHANGED = 'N';
    protected const TYPE_DOCUMENT_ID = 'ID';
    protected const CHUNK_SIZE = 200;

    public const TYPE = 'RU';
    public const SUBTYPE = 'RUD';
    public const NAME = 'RegistroRUD';
    protected static int $internal_version = 9;

    protected const REQUIRED_FIELDS = [
        'username',
        'firstname',
        'lastname',
        'email',
        'sex',
        'address',
        'city',
        'zipcode',
        'mobile',
        'fiscal_region',
        'dob',
        'country',
        'nationality',
        'residence_country',
        'nid',
        'registration_end_date',
        'reg_ip',
    ];

    protected const REQUIRED_FIELDS_WITH_NIF = [
        'lastname_second', // it is required only if user has NIF (citizen of Spain)
    ];

    // Newly registered users
    protected ?array $new_users = null;
    // Users modified in the reporting period
    protected ?array $modified_users = null;
    protected ?array $default_deposit_limit = null;
    protected string $setting_name_dni_verification = '';
    protected string $setting_name_dni_verification_date = '';

    /**
     * Return list of users grouped according to report and sub record limit rules
     *
     * @return array
     * @throws Exception
     */
    protected function getGroupedRecords(): array
    {
        $sub_records = array_chunk($this->getUsersToReport(), ICSConstants::ITEMS_PER_SUBRECORD);

        return array_chunk($sub_records, ICSConstants::RECORD_PER_BATCH);
    }

    /**
     * Map list of users to required Registro structure
     *
     * @param array $records
     * @param string $group RU* - batch number, Other - game type
     *
     * @return array
     * @throws Exception
     */
    protected function setupRecords(array $records, string $group = ''): array
    {
        $countRecords = count($records);

        return array_map(function ($userIds, $index) use ($countRecords) {
            $users = $this->collectUsersWithData($userIds);

            $xml_data = [
                '_attributes' => ['xsi:type' => self::NAME],
                'Cabecera' => $this->getRecordHeader($index + 1, $countRecords),
                'Periodicidad' => ICSConstants::FREQUENCY_VALUES[$this->getFrequency()],
                'Periodo' => $this->getPeriod($this->getFrequency()),
                'Jugador' => array_map(function ($user) {
                    return $this->formatUserData($user);
                }, $users)
            ];

            if (empty($xml_data['Jugador'])) {
                unset($xml_data['Jugador']);
            }

            return $xml_data;
        }, $records, array_keys($records));
    }

    /**
     * Get data about first verification (internal) of user's document
     * @param array $user
     *
     * @return array
     */
    protected function getFirstVerifiedDocument(array $user): array
    {
        $document_verification['VDocumental'] = $user['first_verification_date'] ? ICSConstants::VERIFIED: ICSConstants::NOT_VERIFIED;

        if ($user['first_verification_date']) {
            $document_verification['FVDocumental'] = phive()->fDate($user['first_verification_date'], ICSConstants::DATETIME_TO_GMT_FORMAT);
        }

        return $document_verification;
    }

    /**
     * @param array $users_ids
     *
     * @return array
     * @throws Exception
     */
    protected function collectUsersWithData(array $users_ids): array
    {
        if (empty($users_ids)) {
            return [];
        }

        $users_data = $this->getUsersAdditionalData($users_ids);

        $users = $this->mergeUsersData($users_ids, $users_data);

        return $this->getValidUsers($users);
    }

    /**
     * Format user limits from action log deposit limit description
     * If deposit limits are removed - '-1' value should be set
     *
     * @param string $deposit_limits
     * @param string $deposit_created_at
     * @return string[]
     */
    protected function getFormattedDepositLimits(string $deposit_limits, string $deposit_created_at): array
    {
        $currency = ICSConstants::CURRENCY;

        if ($deposit_limits) {
            preg_match(
                "/Limits: (?<day>(.*?)) $currency,(?<week>(.*?)) $currency,(?<month>(.*?)) $currency/",
                $deposit_limits,
                $matches
            );

            if (empty($matches)){
                preg_match(
                    '/Limits: (?P<day>-?[\d]+),(?P<week>-?[\d]+),(?P<month>-?[\d]+)/',
                    $deposit_limits,
                    $matches
                );
            }

            if (empty($matches)){
                phive('Licensed/ES/ES')->reportLog("ERROR :: Deposit limits used in RUD must match the regex($deposit_limits)!");
            }
        }else{
            phive('Licensed/ES/ES')->reportLog("ERROR :: Deposit limits used in RUD must be present!");
        }

        // just in case we have no value in DB (it shouldn't happen) => use default limit
        $default_deposit_limits = $this->getDefaultDepositLimit();

        $formatted_deposit_limits = [
            'Diario' => $this->format2Decimal(!empty($matches['day']) ? preg_replace('/[,.]/i', '', $matches['day']) : $default_deposit_limits['day']),
            'Semanal' => $this->format2Decimal(!empty($matches['week']) ? preg_replace('/[,.]/i', '', $matches['week']) : $default_deposit_limits['week']),
            'Mensual' => $this->format2Decimal(!empty($matches['month']) ? preg_replace('/[,.]/i', '', $matches['month']) : $default_deposit_limits['month']),
        ];

        if ($deposit_created_at) {
            $formatted_deposit_limits['LimitesDesde'] = phive()->fDate($deposit_created_at, ICSConstants::DATETIME_TO_GMT_FORMAT);
        }

        return $formatted_deposit_limits;
    }

    /**
     * @param array $user
     *
     * @return array
     * @throws Exception
     */
    protected function formatUserData(array $user): array
    {
        $status_changes = $user['status_changes'];

        if ($this->getFrequency() === ICSConstants::MONTHLY_FREQUENCY) {
            $status_changes = $this->mergeStatusChanges($user['status_changes'], $user['id']);
        }

        $user_data = array_merge(
            [
                'ID' => [
                    'OperadorId' => $this->getOperatorId(),
                    'JugadorId' => $user['id'],
                ],
                'FechaActivacion' => $this->getUserActivationDate($user['registration_end_date']),
                'CambiosEnDatos' => $this->getUserDataChanged($user['id']),
                'RegionFiscal' => $user['fiscal_region'],
            ],
            $this->formatResidence($user),
            [
                'FechaNacimiento' => phive()->fDate($user['dob'], ICSConstants::DAY_FORMAT),
                'Login' => $user['username'],
                'Nombre' => $user['firstname'],
                'Apellido1' => $user['lastname'],
                'Apellido2' => $user['lastname_second'] ?? '',
                'email' => $user['email'],
                'Sexo' => ICSConstants::GENDER_VALUES[$user['sex']],
                'Domicilio' => [
                    'Direccion' => $user['address'],
                    'Ciudad' => $user['city'],
                    'CP' => $user['zipcode'],
                    'Pais' => $user['residence_country'],
                ],
                'Telefono' => $user['mobile'],
                'LimitesDeposito' => $this->getFormattedDepositLimits($user['deposit_limits'] ?? '', $user['deposit_created_at'] ?? ''),
                'Estado' => $this->getUserStatus($status_changes),
            ],
            $this->getSVDIVerified($user),
            $this->getFirstVerifiedDocument($user),
            $this->getDeviceOfNewUser($user)
        );

        if (empty($user_data['Apellido2'])) {
            unset($user_data['Apellido2']);
        }

        return $user_data;
    }

    /**
     * @param array $status_changes
     * @return array
     */
    protected function getUserStatus(array $status_changes): array
    {
        $status_changes = array_map(
            function ($status_change) {
                $formatted = $this->getLicense()->formatUserStatusChangeAction($status_change);

                return [
                    // External status see 3.5.7.2 Player's Status
                    'EstadoCNJ' => $formatted["external_status_to"],
                    // Internal status need to map to a player status
                    'EstadoOperador' => $formatted["status_to"],
                    // Date of the status change
                    'Desde' => phive()->fDate($formatted['created_at'], ICSConstants::DATETIME_TO_GMT_FORMAT),
                ];
            },
            $status_changes
        );

        $last_change = end($status_changes);

        return [
            'EstadoCNJ' => $last_change["EstadoCNJ"], // Current external status see 3.5.7.2 Player's Status
            'EstadoOperador' => $last_change["EstadoOperador"], // Current internal status
            'Historico' => $status_changes
        ];
    }

    /**
     * @return array
     * @throws Exception
     */
    protected function getUsersToReport(): array
    {
        if ($this->getFrequency() === ICSConstants::DAILY_FREQUENCY) {
            return array_replace($this->getNewUsers(), $this->getModifiedUsers());
        } else {
            return $this->getAllUsersIds();
        }
    }

    /**
     * @return $this
     * @throws Exception
     */
    protected function setNewUsers(): self
    {
        $sql = "
            SELECT
                users.id
            FROM
                users
            WHERE
                {$this->getBaseUserWhereCondition('>=')}
        ";

        $this->new_users = $this->db->shs()->load1DArr($sql, 'id', 'id');

        return $this;
    }

    /**
     * @return array
     * @throws Exception
     */
    protected function getNewUsers(): array
    {
        if ($this->new_users === null) {
            $this->setNewUsers();
        }

        return $this->new_users;
    }

    /**
     * @throws Exception
     */
    protected function setModifiedUsers(): void
    {
        $sql = "
            SELECT
                users.id
            FROM
                users
            WHERE
                {$this->getBaseUserWhereCondition('<=')}
                AND users.id IN (
                    SELECT target
                    FROM actions
                    WHERE tag in ('deposit-rgl-applied', 'profile-update-success', 'profile-update-by-admin', 'user_status_changed')
                    AND created_at >= '{$this->getPeriodStart()}'
                    AND created_at <= '{$this->getPeriodEnd()}'
                );
        ";

        $this->modified_users = $this->db->shs()->load1DArr($sql, 'id', 'id');
    }

    /**
     * @return array
     * @throws Exception
     */
    protected function getModifiedUsers(): array
    {
        if ($this->modified_users === null) {
            $this->setModifiedUsers();
        }

        return $this->modified_users;
    }

    /**
     * @return array
     * @throws Exception
     */
    protected function getAllUsersIds(): array
    {
        $sql = "
            SELECT
                users.id
            FROM
                users
            WHERE
                {$this->getBaseUserWhereCondition()}
        ";

        return $this->db->shs()->load1DArr($sql, 'id', 'id');
    }

    /**
     * Confirmation from the Identity Verification System (SVDI)
     * `S` shall be stated where the DGOJ's Identity Verification System was used and positive verification obtained,
     * otherwise `N` shall be stated.
     *
     * @param array $user
     *
     * @return array
     */
    protected function getSVDIVerified(array $user): array
    {
        $field_name_dni_verification = $this->getSettingNameDniVerification();
        $field_name_dni_verification_date = $this->getSettingNameDniVerificationDate();

        if (empty($user[$field_name_dni_verification])) {
            return ['VSVDI' => ICSConstants::NOT_VERIFIED];
        }

        return [
            'VSVDI' => ICSConstants::VERIFIED,
            'FVSVDI' => phive()->fDate($user[$field_name_dni_verification_date], ICSConstants::DATETIME_TO_GMT_FORMAT)
        ];
    }

    /**
     * Get Resident or non Resident format from user data
     * @param array $user
     *
     * @return array[]
     */
    protected function formatResidence(array $user): array
    {
        if ($user['residence_country'] === $this->getCountry()) {
            $fieldName = $this->getLicense()->isValidNif($user['nid']) ? 'DNI' : 'NIE';

            return [
                'Residente' => [
                    'Nacionalidad' => $user['nationality'],
                    $fieldName => $user['nid'],
                ]
            ];
        }

        return [
            'NoResidente' => [
                'Nacionalidad' => $user['nationality'],
                'PaisResidencia' => $user['residence_country'],
                // We only accept non residents with NIE/NIF
                // so document type will be always type 'ID'
                'TipoDocumento' => self::TYPE_DOCUMENT_ID,
                'Documento' => $user['nid']
            ]
        ];
    }

    /**
     * Get reporting value base on user profile data changes
     *
     * @param int $user_id
     *
     * @return string 'A' New User, 'S' Modified user, 'N' No changes
     * @throws Exception
     */
    protected function getUserDataChanged(int $user_id): string
    {
        if (!empty($this->getNewUsers()[$user_id])) {
            return self::USER_DATA_NEW;
        }

        if (!empty($this->getModifiedUsers()[$user_id])) {
            return self::USER_DATA_MODIFIED;
        }

        return self::USER_DATA_NON_CHANGED;
    }

    /**
     * @param string $registration_end_date
     *
     * @return string
     */
    protected function getUserActivationDate(string $registration_end_date): string
    {
        return phive()->fDate($registration_end_date, ICSConstants::DATETIME_FORMAT);
    }

    /**
     * @param string $users_ids_in
     *
     * @return string
     * @throws Exception
     */
    protected function getQueryUsersSettings(string $users_ids_in): string
    {
        $field_name_dni_verification = $this->getSettingNameDniVerification();
        $field_name_dni_verification_date = $this->getSettingNameDniVerificationDate();

        return "
            SELECT
                user_id,
                setting,
                value,
                created_at AS setting_at
            FROM
                users_settings
            WHERE user_id IN ({$users_ids_in})
                AND setting IN (
                    'lastname', 'lastname_second', 'fiscal_region', 'residence_country', 'nationality', 'registration_end_date',
                    'email_code_verified', 'sms_code_verified',
                    '{$field_name_dni_verification}', '{$field_name_dni_verification_date}', 'first_verification_date'
                )
                AND created_at <= '{$this->getPeriodEnd()}'
        ";
    }

    protected function getQueryUsersActions(string $users_ids_in): string
    {
        return '';
    }

    /**
     * We need a complete history of statuses by the end of the date
     *
     * @param string $users_ids_in
     *
     * @return string
     * @throws Exception
     */
    protected function getQueryUsersStatuses(string $users_ids_in): string
    {
        // id on order by is not reliable, but it gives us a stable sorting with RUT
        // when we have multiple status changes on the same second (BLOCK=>SELF_EXCLUDED)
        return "
            SELECT
                target AS user_id,
                descr,
                created_at
            FROM
                actions
            WHERE target IN ({$users_ids_in})
                AND tag = 'user_status_changed'
                AND created_at <= '{$this->getPeriodEnd()}'
            ORDER BY actions.created_at, actions.id
        ";
    }

    /**
     * The daily, weekly and monthly deposit limits that are valid at the end of the period under consideration.
     * 3.4.1 Detailed User Record (RUD)
     *
     * @param string $users_ids_in
     * I can't use here range limit like `AND created_at >= $this->getPeriodStart()`
     * Because I need the last deposit limit in the end of period (even if it wasn't set in current range)
     *
     * @return string
     * @throws Exception
     */
    protected function getQueryUsersDepositLimits(string $users_ids_in): string
    {
        return "
            SELECT
                target AS user_id,
                descr AS deposit_limits,
                created_at AS deposit_created_at
            FROM actions as a1
            WHERE a1.target IN ({$users_ids_in})
            AND a1.id = (
                SELECT a2.id
                FROM actions as a2
                WHERE a2.tag = 'deposit-rgl-current'
                    AND a2.target = a1.target
                    AND a2.target IN ({$users_ids_in})
                    AND a2.created_at <= '{$this->getPeriodEnd()}'
                ORDER BY a2.created_at DESC, a2.id DESC LIMIT 1
            )
        ";
    }

    /**
     * @param array $users_ids
     *
     * @return array[]
     * @throws Exception
     */
    protected function getUsersAdditionalData(array $users_ids): array
    {
        $users_ids_in = $this->db->makeIn($users_ids);

        $users_settings = [];
        $users_statuses = [];
        $users_actions = [];
        $users_deposit_limits = [];
        $users_changes_stats = [];

        if ($users_ids_in) {
            $sql_settings = $this->getQueryUsersSettings($users_ids_in);
            $sql_statuses = $this->getQueryUsersStatuses($users_ids_in);
            $sql_actions = $this->getQueryUsersActions($users_ids_in);
            $sql_deposit_limits = $this->getQueryUsersDepositLimits($users_ids_in);
            $sql_users_changes_stats = $this->getQueryUsersChangeStats($users_ids_in);

            $users_settings = $this->db->shs()->loadArray($sql_settings);
            $users_actions = empty($sql_actions) ? [] : $this->db->shs()->loadArray($sql_actions);
            $users_statuses = $this->db->shs()->loadArray($sql_statuses);
            $this->db->prependFromArchives(
                $users_statuses,
                '', //we need all history, so we pass empty date to get it
                $sql_statuses,
                'actions'
            );
            $users_deposit_limits = $this->db->shs()->loadArray($sql_deposit_limits);

            $user_ids_present = [];
            foreach ($users_deposit_limits as $r) {
                $user_ids_present[] = $r['user_id'];
            }

            $missing_users = array_diff($users_ids, $user_ids_present);
            $sql_archive_deposit_limits = $this->getQueryUsersDepositLimits($this->db->makeIn($missing_users));

            $this->db->prependFromArchives(
                $users_deposit_limits,
                '',
                $sql_archive_deposit_limits,
                'actions'
            );

            $this->db->prependFromArchives(
                $users_actions,
                '',
                $sql_actions,
                'actions'
            );
            
            $users_changes_stats = $this->db->shs()->loadArray($sql_users_changes_stats);
        }

        return [
            'users_settings' => $users_settings,
            'users_statuses' => $users_statuses,
            'users_actions' => $users_actions,
            'users_deposit_limits' => $users_deposit_limits,
            'users_change_stats' => $users_changes_stats,
        ];
    }

    /**
     * @param array $users_ids
     * @param array $users_data
     *
     * @return array
     * @throws Exception
     */
    protected function mergeUsersData(array $users_ids, array $users_data): array
    {
        $field_name_dni_verification = $this->getSettingNameDniVerification();
        $field_name_dni_verification_date = $this->getSettingNameDniVerificationDate();

        $users = array_reduce(
            $users_ids,
            function ($carry, $user_id) use ($field_name_dni_verification, $field_name_dni_verification_date) {
                if (!isset($carry[$user_id])) {
                    $carry[$user_id] = [
                        'id' => $user_id,
                        'username' => '',
                        'firstname' => '',
                        'dob' => '',
                        'nid' => '',
                        'address' => '',
                        'city' => '',
                        'zipcode' => '',
                        'country' => '',
                        'email' => '',
                        'sex' => '',
                        'mobile' => '',
                        'registration_end_date' => '',
                        'status_changes' => [],
                        'deposit_limits' => '',
                        'first_verification_date' => '',
                        $field_name_dni_verification => '',
                        $field_name_dni_verification_date => '',
                        'reg_ip' => '',
                        'device' => '',
                        'device_identifier' => ''
                    ];
                }

                return $carry;
            }
        );

        foreach ($users_data['users_settings'] as $settings) {
            $user_id = $settings['user_id'];

            if (isset($users[$user_id])) {
                $users[$user_id][$settings['setting']] = $settings['value'];
                $users[$user_id]["{$settings['setting']}_set_at"] = $settings['setting_at'];
            }
        }

        foreach ($users_data['users_statuses'] as $action) {
            $user_id = $action['user_id'];

            if (isset($users[$user_id])) {
                $users[$user_id]['status_changes'][] = [
                    'created_at' => $action['created_at'],
                    'descr' => $action['descr'],
                    'tag' => $action['tag']
                ];
            }
        }

        foreach ($users_data['users_deposit_limits'] as $deposit_limits) {
            $user_id = $deposit_limits['user_id'];

            if (isset($users[$user_id])) {
                $users[$user_id]['deposit_limits'] = $deposit_limits['deposit_limits'];
                $users[$user_id]['deposit_created_at'] = $deposit_limits['deposit_created_at'];
            }
        }

        foreach ($users_data['users_change_stats'] as $users_stat) {
            $user_id = $users_stat['user_id'];

            if (isset($users[$user_id])) {
                $users[$user_id][$users_stat['type']] = $users_stat['post_value'];
            }
        }

        foreach ($users_data['users_actions'] as $users_action) {
            $user_id = $users_action['user_id'];

            if (isset($users[$user_id])) {
                $users[$user_id]['actions'][] = $users_action;
            }
        }

        return $users;
    }

    /**
     * @param string $message
     * @param array $context
     * @throws Exception
     */
    protected function errorLog(string $message, array $context = []): void
    {
        $context = array_merge($context, [
            'frequency' => $this->getFrequency(),
            'period_start' => $this->getPeriodStart(),
            'period_end' => $this->getPeriodEnd(),
            'file_name' => $this->getFileName(),
        ]);

        phive('Licensed/ES/ES')->reportLog("{$message}. Additional data: " . json_encode($context));
    }

    /**
     * @param array $user
     *
     * @return array
     * @throws Exception
     */
    protected function getUserNotValidFields(array $user): array
    {
        $not_valid_fields = [];

        foreach (self::REQUIRED_FIELDS as $field) {
            if (empty($user[$field])) {
                $not_valid_fields[] = $field;
            }
        }

        $is_valid_nif = $this->getLicense()->isValidNif($user['nid']);

        // Some fields are required if user has NIF (citizen of Spain)
        if ($is_valid_nif) {
            foreach (self::REQUIRED_FIELDS_WITH_NIF as $field) {
                if (empty($user[$field])) {
                    $not_valid_fields[] = $field;
                }
            }
        }

        return $not_valid_fields;
    }

    /**
     * @param array $users
     *
     * @return array
     * @throws Exception
     */
    protected function getValidUsers(array $users): array
    {
        $valid_users = [];
        $invalid_users_data = [];

        foreach ($users as $user) {
            $not_valid_fields = $this->getUserNotValidFields($user);

            if (empty($not_valid_fields)) {
                $valid_users[] = $user;
            } else {
                $invalid_users_data[] = [
                    'user_id' => $user['id'],
                    'not_valid_fields' => $not_valid_fields,
                ];
            }
        }

        foreach (array_chunk($invalid_users_data, self::CHUNK_SIZE) as $chunked_users) {
            $this->errorLog("RUD Report. Not valid data", $chunked_users);

            phive()->dumpTbl('report_rud', $chunked_users);
        }

        return $valid_users;
    }

    /**
     * @return string
     */
    protected function getSettingNameDniVerification(): string
    {
        if (empty($this->setting_name_dni_verification)) {
            $this->setting_name_dni_verification = $this->getLicense()->getSettingNameDniVerification();
        }

        return $this->setting_name_dni_verification;
    }

    /**
     * @return string
     */
    protected function getSettingNameDniVerificationDate(): string
    {
        if (empty($this->setting_name_dni_verification_date)) {
            $this->setting_name_dni_verification_date = $this->getLicense()->getSettingNameDniVerificationDate();
        }

        return $this->setting_name_dni_verification_date;
    }

    /**
     * @return array
     */
    protected function getDefaultDepositLimit(): array
    {
        if ($this->default_deposit_limit === null) {
            $this->default_deposit_limit = $this->getLicSetting('deposit_limit')['highest_allowed_limit'];
        }

        return $this->default_deposit_limit;
    }

    /**
     * Get user's fields from "log" table
     * In case of user changed his information we are able to report proper data
     *
     * @param string $users_ids_in
     *
     * @return string
     * @throws Exception
     */
    protected function getQueryUsersChangeStats(string $users_ids_in): string
    {
        return "
            SELECT user_id, type, post_value
            FROM users_changes_stats
            WHERE id IN (
                    SELECT max(id) FROM users_changes_stats
                    WHERE user_id IN ({$users_ids_in})
                        AND type IN ('username', 'firstname', 'dob', 'nid', 'address', 'city', 'zipcode', 'country', 'email', 'sex', 'mobile', 'reg_ip')
                        AND created_at <= '{$this->getPeriodEnd()}'
                    GROUP BY type, user_id
                )
        ";
    }

    /**
     * Ip, Dispositivo and IdDispositivo should be reported if the record is about a new player
     * @param array $user
     * @return array
     * @throws Exception
     */
    protected function getDeviceOfNewUser(array $user): array
    {
        $sql_users_devices = [];
        $sql_users_equipments = [];

        $is_new_user = $this->getUserDataChanged($user['id']) === self::USER_DATA_NEW;

        if ($is_new_user) {
            $sql_users_devices = $this->getUsersDevices($user['id']);
            $sql_users_equipments = $this->getUsersEquipments($user['id']);

            $device = $this->getDeviceType(implode('', $sql_users_equipments));
            $id_device = $this->getDeviceId(implode('', $sql_users_devices));

            return[
                'IP' => $user['reg_ip'],
                'Dispositivo' => $device,
                'IdDispositivo' => $id_device,
            ];

        }

        return [];
    }

    /**
     * @param  int  $user_id
     * @return array
     * @throws Exception
     */
    protected function getUsersEquipments(int $user_id): array
    {
        $sql = "
            SELECT
                user_id,
                equipment
            FROM users_sessions
            WHERE id IN (
                    SELECT MIN(id)
                    FROM users_sessions
                    WHERE user_id = {$user_id}
                        AND created_at >= '{$this->getPeriodStart()}'
                        AND created_at <= '{$this->getPeriodEnd()}'
                    GROUP BY user_id
                )
        ";

        return $this->db->sh($user_id)->load1DArr($sql, 'equipment');

    }

    /**
     * @param  int  $user_id
     * @return array
     * @throws Exception
     */
    protected function getUsersDevices(int $user_id): array
    {
        $sql = "
            SELECT
                target as user_id,
                descr as device_description
            FROM actions
            WHERE target = {$user_id}
                AND id IN (
                    SELECT MIN(id)
                    FROM actions
                    WHERE tag = 'uagent'
                        AND created_at >= '{$this->getPeriodStart()}'
                        AND created_at <= '{$this->getPeriodEnd()}'
                    GROUP BY target
                )
        ";

        return $this->db->sh($user_id)->load1DArr($sql, 'device_description');
    }

    /**
     * Set up the statuses array from the csv file. Set up in a structure same as the returned statuses from DB
     *
     * @return self
     */
    public function setCsvStatusesArray(): self
    {
        $csv_file_path = dirname(__FILE__) . static::CSV_RELATIVE_PATH;

        if (($handle = fopen($csv_file_path, 'r')) !== false) {
            fgetcsv($handle);
            $previous_status = 'NA';

            while (($row = fgetcsv($handle)) !== false) {
                $user_id = $row[0];
                $status = $row[1];
                $date = $row[2];

                if (!isset($this->csv_statuses[$user_id])) {
                    $previous_status = 'NA';
                    $this->csv_statuses[$user_id] = [];
                }

                $desc = "[{$previous_status}-{$status}] Status changed from {$previous_status} to {$status}";
                $this->csv_statuses[$user_id][] = ['created_at' => $date, 'descr' => $desc];
                $previous_status = $status;
            }

            fclose($handle);
        }

        return $this;
    }

    /**
     * Set up the deposit limits array from the csv file. Set up in a structure same as the returned statuses from DB
     *
     * @return self
     */
    public function setCsvDepositLimitsArray(): self
    {
        $csv_file_path = dirname(__FILE__) . static::CSV_DEPOSIT_LIMITS_RELATIVE_PATH;

        if (($handle = fopen($csv_file_path, 'r')) !== false) {
            fgetcsv($handle);

            while (($row = fgetcsv($handle)) !== false) {
                $user_id = $row[0];
                $tag = $row[1];
                $descr = $row[2];
                $created_at = $row[3];

                $this->csv_deposit_limits[$user_id][] = ['user_id' => $user_id, 'tag' => $tag, 'descr' => $descr, 'created_at' => $created_at];
            }

            fclose($handle);
        }

        return $this;
    }

    /**
     * Merges the status changes from DB with the status changes from the CSV. Returns a trimmed array with only unique status changes for the user
     * @param array $db_status_changes
     * @param int $user_id
     * @return array
     */
    public function mergeStatusChanges(array $db_status_changes, int $user_id): array
    {
        $merged_status_changes = array_merge($db_status_changes, isset($this->csv_statuses[$user_id]) ? $this->csv_statuses[$user_id] : []);

        $status_changes = [];
        $unique_keys = [];
        foreach ($merged_status_changes as $item) {
            $unique_key = $item['created_at'] . '-' . $item['descr'];

            if (!in_array($unique_key, $unique_keys)) {
                $unique_keys[] = $unique_key;
                $status_changes[] = $item;
            }
        }

        usort($status_changes, function ($a, $b) {
            return strtotime($a['created_at']) - strtotime($b['created_at']);
        });

        return $status_changes;
    }

    /**
     * Merges the status changes from DB with the status changes from the CSV. Returns a trimmed array with only unique status changes for the user
     * @param array $db_status_changes
     * @param int $user_id
     * @return array
     */
    public function mergeDepositLimitChanges(?array $db_status_changes, int $user_id): array
    {
        if (is_null($db_status_changes)) {
            $db_status_changes = [];
        }

        $merged_deposit_limit_changes = array_merge($db_status_changes, $this->csv_deposit_limits[$user_id] ?? []);

        $deposit_limit_changes = [];
        $unique_keys = [];
        foreach ($merged_deposit_limit_changes as $item) {
            $unique_key = $item['created_at'] . '-' . $item['descr'];

            if (!in_array($unique_key, $unique_keys)) {
                $unique_keys[] = $unique_key;
                $deposit_limit_changes[] = $item;
            }
        }

        usort($deposit_limit_changes, function ($a, $b) {
            return strtotime($a['created_at']) - strtotime($b['created_at']);
        });

        return $deposit_limit_changes;
    }
}
