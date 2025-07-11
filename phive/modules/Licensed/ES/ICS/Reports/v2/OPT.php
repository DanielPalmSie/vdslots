<?php

namespace ES\ICS\Reports\v2;

use Carbon\Carbon;
use ES\ICS\Constants\ICSConstants;
use Exception;
use ES\ICS\Reports\BaseReport;

class OPT extends BaseReport
{
    public const TYPE = 'OP';
    public const SUBTYPE = 'OPT';
    public const NAME = 'RegistroOPT';
    protected static int $internal_version = 4;

    /**
     * Return operator data grouped by game type
     *
     * @return array
     * @throws Exception
     */
    public function getGroupedRecords(): array
    {
        $bets = $this->listToAssoc($this->getBets());
        $wins = $this->listToAssoc($this->getWins());
        $rollback_bets = $this->listToAssoc($this->getRollbackBets());
        $result = [];

        foreach ($this->getTypes() as $type) {
            if (empty($bets[$type]) && empty($wins[$type]) && empty($rollback_bets[$type])) {
                $result[$type] = [
                    []
                ];

                continue;
            }

            $result[$type] = [
                [
                    'bets' => $bets[$type],
                    'wins' => $wins[$type],
                    'rollback_bets' => $rollback_bets[$type],
                ]
            ];
        }

        return $result;
    }

    /**
     * Map operator data to required Registro structure
     *
     * @param array $records
     * @param string $group CJ* - batch number, Other - game type
     * @return array
     * @throws Exception
     */
    public function setupRecords(array $records, string $group = ''): array
    {
        $total = count($records);
        return array_map(function($record, $index) use ($group, $total) {
            return [
                '_attributes' => ['xsi:type' => self::NAME],
                'Cabecera' => $this->getRecordHeader($index + 1, $total),
                'Mes' => phive()->fDate($this->getPeriodEnd(), ICSConstants::MONTH_FORMAT),

                'TipoJuego' => $group,
                'Participacion' => $this->formatNumberToBreakdown((int) $record['bets'] * -1),
                'ParticipacionDevolucion' => $this->formatNumberToBreakdown($record['rollback_bets']),
                'Premios' => $this->formatNumberToBreakdown($record['wins']),
                'PremiosEspecie' => $this->formatBreakdownByOperator([]),
                'Botes' => $this->formatBreakdownByOperator([]),
                'AjustesRed' => $this->formatBreakdownByOperator([]),
                'Otros' => $this->formatBreakdownByOperatorDescription([]),
                'Comision' => $this->formatBreakdownByOperator([]),
                'GGR' => $this->formatAmount((int)$record['rollback_bets'] + (int)$record['wins'] - (int)$record['bets']),
            ];
        }, $records, array_keys($records));
    }

    /**
     * @param int $subregister_index
     * @param int $total_subregisters
     *
     * @return array
     */
    public function getRecordHeader(int $subregister_index = 1, int $total_subregisters = 1): array
    {
        // We need to have unique report id per each game type
        $this->setReportId();

        return parent::getRecordHeader($subregister_index, $total_subregisters);
    }

    /**
     * Convert amount to breakdown by operator
     *
     * @param $amount
     * @return array
     */
    protected function formatNumberToBreakdown($amount): array
    {
        return $this->formatBreakdownByOperator([
            [
                'amount' => $amount ?? 0
            ]
        ]);
    }

    /**
     * Return the file name
     *
     * @return string
     */
    protected function getFileName(): string
    {
        return implode("_", [
            $this->getOperatorId(),
            $this->getStorageId(),
            self::TYPE,
            self::SUBTYPE,
            $this->type,
            ICSConstants::FREQUENCY_FILE_NAME_VALUES[$this->getFrequency()],
            $this->getDate()->format(ICSConstants::MONTH_FORMAT),
            $this->getBatchId()
        ]);
    }

    /**
     * Bets
     *
     * @return array
     * @throws Exception
     */
    public function getBets(): array
    {
        $sql = "
            SELECT
                SUM(bets.amount) AS amount,
                mg.tag AS game_tag,
                mg.id AS game_id
            FROM bets
            INNER JOIN micro_games AS mg ON mg.ext_game_name = bets.game_ref AND mg.device_type_num = bets.device_type
            WHERE bets.created_at BETWEEN '{$this->getPeriodStart()}' AND '{$this->getPeriodEnd()}'
                {$this->filterByUserId('bets.user_id')}
            GROUP BY mg.id;
        ";

        $items = $this->db->shs()->loadArray($sql);

        $this->db->prependFromArchives($items, $this->getPeriodStart(), $sql, 'bets');

        // we add rollbacks here, because the rollback process deletes those bets, but they must be informed for SaldoFinal to be correct
        $change_to_rollbacks_v2_timestamp = phive('Licensed')->getSetting('change_to_rollbacks_v2_timestamp');
        $is_before_rollbacks_v2 = Carbon::createFromFormat('Y-m-d H:i:s', $this->getPeriodStart())
            ->isBefore($change_to_rollbacks_v2_timestamp);
        if ($is_before_rollbacks_v2) {
            $rollbacks_period_end = $this->getPeriodEnd();

            if (Carbon::createFromFormat('Y-m-d H:i:s', $change_to_rollbacks_v2_timestamp)->isBefore($this->getPeriodEnd())) {
                $rollbacks_period_end = $change_to_rollbacks_v2_timestamp;
            }

            $sql = "SELECT
                ugs.bets_rollback AS amount,
                mg.tag AS game_tag,
                mg.id AS game_id
            FROM users_game_sessions AS ugs
            LEFT JOIN micro_games AS mg ON mg.ext_game_name = ugs.game_ref AND mg.device_type_num=ugs.device_type_num
            WHERE
                ugs.bets_rollback > 0
                AND ugs.start_time BETWEEN '{$this->getPeriodStart()}' AND '$rollbacks_period_end'
                {$this->filterByUserId('ugs.user_id')}
            GROUP BY ugs.id";
            $rollbacks = $this->db->shs()->loadArray($sql);
            if ($rollbacks) {
                $items = array_merge($items, $rollbacks);
            }
        }

        return $this->groupTransactions($items, function ($operation) {
            return $this->getGameType($operation['game_tag'], $operation['game_id']);
        });
    }

    /**
     * Rollback bets
     *
     * @return array
     * @throws Exception
     */
    public function getRollbackBets(): array
    {
        $sql = "
            SELECT
                ugs.bets_rollback AS amount,
                mg.tag AS game_tag,
                mg.id AS game_id
            FROM users_game_sessions AS ugs
            LEFT JOIN micro_games AS mg ON mg.ext_game_name = ugs.game_ref
            WHERE ugs.end_time BETWEEN '{$this->getPeriodStart()}'
                AND '{$this->getPeriodEnd()}'
                AND ugs.bets_rollback > 0
                AND ugs.user_id IN (SELECT id FROM users WHERE country = '{$this->getCountry()}')
                {$this->filterUsers('ugs.user_id')}
            GROUP BY ugs.id;
        ";

        $items = $this->db->shs()->loadArray($sql);
        return $this->groupTransactions($items, function ($operation) {
            return $this->getGameType($operation['game_tag'], $operation['game_id']);
        });
    }

    /**
     * Wins
     *
     * @return array
     * @throws Exception
     */
    public function getWins(): array
    {
        $sql = "
            SELECT
                SUM(wins.amount) AS amount,
                mg.tag AS game_tag,
                mg.id AS game_id
            FROM wins
            INNER JOIN micro_games AS mg ON mg.ext_game_name = wins.game_ref AND mg.device_type_num=wins.device_type
            WHERE wins.created_at BETWEEN '{$this->getPeriodStart()}' AND '{$this->getPeriodEnd()}'
                {$this->filterByUserId('wins.user_id')}
            AND bonus_bet <> " . ICSConstants::FS_WIN_TYPE . "
            GROUP BY mg.id
        ";

        $items = $this->db->shs()->loadArray($sql);
        return $this->groupTransactions($items, function ($operation) {
            return $this->getGameType($operation['game_tag'], $operation['game_id']);
        });
    }

    /**
     * Convert list of items into assoc key => val
     *
     * @param $list
     * @param $key
     * @param $val
     * @return mixed
     */
    private function listToAssoc($list, $key = 'gambling_type', $val = 'amount'): array
    {
        return array_reduce($list, function ($carry, $el) use ($key, $val) {
            $carry[$el[$key]] = $el[$val];
            return $carry;
        }, []);
    }

    /**
     * @return array
     */
    protected function getTypes(): array
    {
        $game_types = $this->getGameTypes();

        return empty($game_types) ? $this->getLicensedExternalGamblingTypes() : $game_types;
    }

    /**
     * Get target directory and create it if doesn't exist
     *
     * @return string
     * @throws Exception
     */
    public function getTargetDirectory(): string
    {
        $dir = implode('/', array_filter([
            $this->getLicSetting('ICS.export_folder'),
            $this->getOperatorId(),
            self::TYPE,
            $this->type,
            $this->getFrequencyDirectory(),
            self::SUBTYPE,
            $this->getExtraDirectory(),
        ]));

        if (!is_dir($dir)) {
            if (!mkdir($dir, 0777, true)) {
                throw new Exception("Could not create directory: {$dir}");
            }
        } elseif (!is_writable($dir)) {
            throw new Exception("Directory is not writable: {$dir}");
        }

        return $dir;
    }
}
