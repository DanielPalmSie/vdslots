<?php

namespace ES\ICS\Reports\v3;

use ES\ICS\Constants\ICSConstants;

class RUD extends \ES\ICS\Reports\v2\RUD
{
    protected const USER_LIMIT_AMOUNT_MAX_LENGTH = 15;
    protected const LIMIT_TYPES = ['Diario', 'Semanal', 'Mensual'];
    protected static int $internal_version = 1;

    public function getRecordHeader(int $subregister_index = 1, int $total_subregisters = 1): array
    {
        $header = parent::getRecordHeader($subregister_index, $total_subregisters);

        unset($header['OperadorId']);
        unset($header['AlmacenId']);
        return $header;
    }

    protected function formatUserData(array $user): array
    {
        $status_changes = $user['status_changes'];

        if ($this->getFrequency() === ICSConstants::MONTHLY_FREQUENCY) {
            $status_changes = $this->mergeStatusChanges($user['status_changes'], $user['id']);
        }

        $actions = $this->mergeDepositLimitChanges($user['actions'], $user['id']);

        $user_data = array_merge(
            [
                'JugadorId' => $this->limitLength($user['id'], 50),
                'FechaActivacion' => $this->getUserActivationDate($user['registration_end_date']),
                'CambiosEnDatos' => $this->getUserDataChanged($user['id']),
                'RegionFiscal' => $user['fiscal_region'],
            ],
            $this->formatResidence($user),
            [
                'FechaNacimiento' => phive()->fDate($user['dob'], ICSConstants::DAY_FORMAT),
                'Login' => $this->limitLength($user['username'], 100),
                'Nombre' => $this->limitLength($user['firstname'], 100),
                'Apellido1' => $this->limitLength($user['lastname'], 100),
                'Apellido2' => $this->limitLength($user['lastname_second'] ?? '', 100),
                'Email' => $this->limitLength($user['email'], 100),
                'EmailVerificado' => $user['email_code_verified'] === 'yes' ? 'S' : 'N',
                'Sexo' => ICSConstants::GENDER_VALUES[$user['sex']],
                'Domicilio' => [
                    'Direccion' => $this->limitLength($user['address'], 100),
                    'Ciudad' => $this->limitLength($user['city'], 100),
                    'CP' => $this->limitLength($user['zipcode'], 10),
                    'Pais' => $user['residence_country'],
                ],
                'Telefono' => $user['mobile'],
                'TelefonoVerificado' => $user['sms_code_verified'] === 'yes' ? 'S' : 'N',
                'LimitesJugador' => $this->getUserLimits($actions),
                'Exclusion' => $this->getUserExclusion($actions ?? []),
                'PerfilEspecial' => $this->getUserProfile($actions ?? []),
                'Estado' => $this->getUserStatus($status_changes),
            ],
            $this->getSVDIVerified($user),
            $this->getFirstVerifiedDocument($user),
            $this->getDeviceOfNewUser($user)
        );

        if (empty($user_data['Apellido2'])) {
            unset($user_data['Apellido2']);
        }

        if (empty($user_data['Exclusion'])) {
            unset($user_data['Exclusion']);
        }

        if (empty($user_data['PerfilEspecial'])) {
            unset($user_data['PerfilEspecial']);
        }

        return $user_data;
    }

    protected function formatResidence(array $user): array
    {
        $document = $this->limitLength($user['nid'], 50);

        if ($user['residence_country'] === $this->getCountry()) {

            return [
                'Residente' => [
                    'Nacionalidad' => $user['nationality'],
                    'Documento' => $document,
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
                'Documento' => $document,
            ]
        ];
    }

    protected function getUserStatus(array $status_changes): array
    {
        $status_changes = array_map(
            function ($status_change) {
                $formatted = $this->getLicense()->formatUserStatusChangeAction($status_change);

                $this->processDeprecatedStatuses($formatted['external_status_from']);
                $this->processDeprecatedStatuses($formatted['external_status_to']);

                return [
                    // External status see 3.5.7.2 Player's Status
                    'EstadoCNJ' => $formatted['external_status_to'],
                    // Internal status need to map to a player status
                    'EstadoOperador' => mb_substr($formatted['status_to'], 0, 100),
                    'Desde' => phive()->fDate($status_change['created_at'], ICSConstants::DATETIME_TO_GMT_FORMAT)
                ];
            },
            $status_changes
        );

        $last_change = end($status_changes);

        $result = [
            'EstadoCNJ' => $last_change['EstadoCNJ'], // Current external status see 3.5.7.2 Player's Status
            'EstadoOperador' => $last_change['EstadoOperador'], // Current internal status
            'Historico' => $status_changes
        ];

        if (in_array($last_change['EstadoCNJ'], ['S', 'C'])) {
            $result = array_merge($result, [
                'Motivo' => [
                    'MotivoSC' => 'Otros',
                    'DescripcionSC' => ''
                ]
            ]);
        }

        return $result;
    }

    public function getDeviceId(string $uagent): string
    {
        return mb_substr(parent::getDeviceId($uagent), 0, 100);
    }

    /**
     * ICSConstants::PRECAUTIONARY_SUSPENSION and ICSConstants::CONTRACT_CANCELLATION
     * are no longer supported in this version
     */
    protected function processDeprecatedStatuses(string &$status): void
    {
        $status = str_replace(
            [
                ICSConstants::PRECAUTIONARY_SUSPENSION,
                ICSConstants::CONTRACT_CANCELLATION
            ],
            ICSConstants::OTHERS,
            $status
        );
    }

    protected function getFirstVerifiedDocument(array $user): array
    {
        $document_verification['VDocumental'] = $user['first_verification_date'] ? ICSConstants::VERIFIED: ICSConstants::NOT_VERIFIED;

        if ($user['first_verification_date']) {
            $document_verification['TipoVDocumental'] = [
                'Tipo' => 'DOC',
                'FVDocumental' => phive()->fDate($user['first_verification_date'], ICSConstants::DATETIME_TO_GMT_FORMAT)
            ];
        }

        return $document_verification;
    }

    protected function getQueryUsersActions(string $users_ids_in): string
    {
        return "
            SELECT
                target as user_id,
                tag,
                descr,
                created_at AS created_at
            FROM
                actions
            WHERE target IN ({$users_ids_in})
                AND (
                    tag IN (
                        'user_status_changed',
                        'excluded-date',
                        'unexclude-date',
                        'deposit-rgl-applied',
                        'deposit-rgl-current',
                        'deposit-rgl-remove'
                    ) OR (
                        descr LIKE '%_SELF_EXCLUDED'
                        OR descr = 'set-flag|mixed - Triggered flag RG65'
                        OR descr LIKE '% set excluded-date to %'
                        OR descr LIKE '% set unexcluded-date to %'
                    )
                )
                AND created_at <= '{$this->getPeriodEnd()}'
        ";
    }

    protected function getQueryUsersDepositLimits(string $users_ids_in): string
    {
        return "";
    }


    protected function getUserProfile(array $user_actions): array
    {
        $result = [];

        foreach ($user_actions as $user_action) {
            $userProfile = $this->mapUserActionToUserProfile($user_action['descr']);

            if (!$userProfile) continue;

            return [
                'PerfilJugador' => $userProfile,
                'FechaInicio' => phive()->fDate($user_action['created_at'], ICSConstants::DATETIME_TO_GMT_FORMAT),
            ];
        }

        return $result;
    }

    protected function mapUserActionToUserProfile(string $description): ?string
    {
        $mapping = [
            'set-flag|mixed - Triggered flag RG65' => 'JugadorIntensivo'
        ];

        if (!isset($mapping[$description])) return null;

        return $mapping[$description];
    }

    protected function getUserExclusion(array $user_actions): array
    {
        $result = [];

        $user_actions = array_filter($user_actions, function ($user_action) {
            return ($user_action['tag'] === 'excluded-date' && strpos($user_action['descr'], 'set excluded-date to') !== false)
                || ($user_action['tag'] === 'unexclude-date' && strpos($user_action['descr'], 'set unexcluded-date to') !== false);
        });

        $user_actions_processed = [];
        foreach ($user_actions as $user_action) {
            if (!preg_match('/-date to (\d{4}-\d{2}-\d{2})/', $user_action['descr'], $matches)) continue;

            $date = $matches[1];

            $user_actions_processed[] = [
                'date' => $date,
                'action' => $user_action['tag'],
            ];
        }

        usort($user_actions_processed, function ($a, $b) {
            return $a['date'] <=> $b['date'];
        });

        $excludeDate = null;

        foreach ($user_actions_processed as $user_action) {
            if ($user_action['action'] === 'excluded-date') {
                $excludeDate = $user_action['date'];
            } elseif ($user_action['action'] === 'unexclude-date') {
                $months = $this->calculateMonthsBetween($excludeDate, $user_action['date']);
                $formattedDate = phive()->fDate($excludeDate, ICSConstants::DATETIME_TO_GMT_FORMAT);

                $result[] = [
                    'Cantidad' => $months,
                    'Unidad' => 'MES',
                    'FechaActivacionExclusion' => $formattedDate,
                    'Autocontinuacion' => 'S',
                    'FechaSolicitudCambioExclusion' => $formattedDate
                ];
            }
        }

        return $result;
    }

    protected function calculateMonthsBetween(string $start, string $end): int
    {
        $startDate = new \DateTime($start);
        $endDate = new \DateTime($end);
        $diff = $startDate->diff($endDate);
        return $diff->y * 12 + $diff->m;
    }

    protected function getUserLimits(array $user_actions): array
    {
        $result = $currentLimitsArray = [];

        foreach ($user_actions as $user_action) {

            $tag = $user_action['tag'];

            if ($tag === 'deposit-rgl-current') {
                $currentLimitsArray[] = $user_action;
                continue;
            }

            if ($tag === 'deposit-rgl-remove') {
                $this->addRemovedDepositLimitToResult($user_action, $result);
                continue;
            }

            if ($tag === 'deposit-rgl-applied') {
                $this->addDepositLimitToResult($user_action, $result);
            }
        }

        // if we did not find any limit changes, we check the initial limits
        if (empty($result) && !empty($currentLimitsArray)) {

            usort($currentLimitsArray, function ($a, $b) {
                return strtotime($a['created_at']) <=> strtotime($b['created_at']);
            });

            $user_action = $currentLimitsArray[0];

            $this->addDepositLimitToResult($user_action, $result);
        }

        usort($result, function (array $a, array $b){
            return $a['FechaActivacionLimite'] <=> $b['FechaActivacionLimite'];
        });

        return $result;
    }

    protected function isValidLimitType(string $type): bool
    {
        return in_array($type, self::LIMIT_TYPES);
    }

    protected function addDepositLimitToResult(array $user_action, array &$result): void
    {
        $formattedDepositLimits = $this->getFormattedDepositLimits(
            $user_action['descr'],
            $user_action['created_at']
        );

        foreach ($formattedDepositLimits as $key => $formattedLimit) {
            if (!$this->isValidLimitType($key)
                || !$this->isValidLength($formattedLimit, self::USER_LIMIT_AMOUNT_MAX_LENGTH)) {
                continue;
            }

            $result[] = [
                'TipoLimite' => 'Deposito',
                'PeriodoLimite' => $key,
                'Cantidad' => $formattedLimit,
                'UnidadLimite' => 'EUR',
                'FechaActivacionLimite' => $formattedDepositLimits['LimitesDesde'],
                'FechaSolicitudCambioLimite' => $formattedDepositLimits['LimitesDesde'],
            ];
        }
    }

    protected function addRemovedDepositLimitToResult(array $user_action, array &$result): void
    {
        $formattedDate = phive()->fDate($user_action['created_at'], ICSConstants::DATETIME_TO_GMT_FORMAT);

        foreach (self::LIMIT_TYPES as $type) {
            $result[] = [
                'TipoLimite' => 'Deposito',
                'PeriodoLimite' => $type,
                'Cantidad' => '-1',
                'UnidadLimite' => 'EUR',
                'FechaActivacionLimite' => $formattedDate,
                'FechaSolicitudCambioLimite' => $formattedDate,
            ];
        }
    }
}
