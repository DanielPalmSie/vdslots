<?php

namespace ES\ICS\Reports;

use DateTime;
use DOMDocument;
use ES;
use ES\ICS\Constants\ICSConstants;
use ES\ICS\Type\Transaction;
use XML\XAdES;
use Exception;
use Localizer;
use MicroGames;
use PhpZip\Constants\ZipCompressionMethod;
use PhpZip\Constants\ZipEncryptionMethod;
use Spatie\ArrayToXml\ArrayToXml;
use SQL;

abstract class BaseReport
{
    public const GENERATE_SIGNED = 'GENERATE_SIGNED';
    public const GENERATE_PLAIN = 'GENERATE_PLAIN';
    public const GENERATE_ZIP = 'GENERATE_ZIP';

    protected const GROUP_DEPOSITS = 'deposits';
    protected const GROUP_WITHDRAWALS = 'withdrawals';

    protected static int $internal_version;

    public const TYPE = 'abstract';
    public const SUBTYPE = 'abstract';
    public const NAME = 'abstract';
    public const CSV_RELATIVE_PATH = '/../../Csv/statuses.csv';
    public const CSV_DEPOSIT_LIMITS_RELATIVE_PATH = '/../../Csv/deposit_limits.csv';
    private static array $bonus_names = [];
    private static Localizer $localizer;

    /** @var string $type Game type OR Array index */
    protected $type;
    protected $iso;
    protected $operator_id;
    protected $storage_id;
    protected $batchId = '';
    protected $frequency = '';
    /**
     * @var DateTime
     */
    protected $date;
    protected $lic_settings;
    /** @var self[] $data */
    protected $data;
    protected $period_end;
    protected $period_start;
    protected $report_id;
    protected $generation_date;
    /** @var SQL $db */
    protected $db;
    /** @var MicroGames $mg */
    protected $mg;
    protected $game_tags;
    /** @var array $rectification - Host the report rectification configuration */
    public array $rectification = [];
    protected ?ES $license = null;
    /** @var array $game_tags_cache cache for loadGameTags */
    private static array $game_tags_cache = [];
    protected static array $game_type_cache = [];
    protected array $game_types = [];

    protected array $csv_statuses = [];
    protected array $csv_deposit_limits = [];

    /**
     * BaseReport constructor.
     *
     * @param $iso
     * @param array $lic_settings
     * @param array $report_settings
     * @throws Exception
     */
    public function __construct($iso, $lic_settings = [], $report_settings = [])
    {
        $this->db = phive('SQL')->readOnly();
        $this->mg = phive('MicroGames');
        $this->iso = $iso;
        $this->lic_settings = $lic_settings;
        $this->operator_id = $lic_settings['operatorId'];
        $this->storage_id = $lic_settings['storageId'];

        $this->setReportId()
            ->setFrequency($report_settings['frequency'])
            ->setPeriodStart($report_settings['period_start'])
            ->setPeriodEnd($report_settings['period_end'])
            ->setGenerationDate($report_settings['generation_date'])
            ->setCsvStatuses()
            ->setCsvDepositLimits();

        if (!empty($report_settings['game_types'])) {
            $this->setGameTypes($report_settings['game_types']);
        }

        $this->loadGameTags();
    }

    /**
     * @return int[]
     */
    public function getBonusTypes(): array
    {
        // TODO: remove 82 cash transaction type
        $casino = phive('CasinoCashier');
        $bonus_types = array_intersect_key(
            $casino->getColsForDailyStats(),
            array_flip(array_merge($casino->getCashTransactionsBonusTypes(), lic('getAdditionalReportingBonusTypes', [], null, null, 'ES')))
        );
        //this value is added to rewards column in daily_stats,
        //but it's not shown as part of the user account in the frontend, so it shouldn't be added here
        //I'm not even sure it's a monetary value
        unset($bonus_types['frb_cost']);
        //also should not be reported as it doesnt affect the cash balance
        unset($bonus_types['tournament_ticket_shift']);
        //cash rewards are moved into Otros
        unset($bonus_types['trophy_top_up_shift']);
        //We're reporting this as a Otros entry
        unset($bonus_types['tournament_joker_prize']);

        return $bonus_types;
    }

    /**
     * @return string
     */
    public function getOthersTypes(): string
    {
        //9 => 'Chargeback'
        //13 => 'Normal refund'
        //15 => 'Failed bonus'
        //34 => 'Casino tournament buy in'
        //38 => 'Tournament cash win'
        //43 => 'Inactivity fee'
        //50 => 'Withdrawal deduction'
        //52 => 'Casino tournament house fee'
        //54 => 'Casino tournament rebuy'
        //61 => 'Cancel / Unreg of casino tournament buy in'
        //63 => 'Cancel / Unreg of casino tournament house fee'
        //77 => 'Trophy top up shift'
        //82 => 'TODO:'
        //85 => 'Tournament cash win'
        //91 => 'Liability adjustment'
        return '9,13,15,34,38,43,50,52,54,61,63,77,82,85,91';
    }

    protected function getOthersDescription($row): string
    {
        switch ($row['transactiontype']){
            //BOS cash prizes are reported with BOS and the transaction id
            case 85:
            case 38:
                return 'BOS '.$row['id'];
            //trophy redemption is reported with the transaction id
            case 77:
                return $row['id'];
            default:
                return $row['description'];
        }
    }


    /**
     * Setup filter users query to be used by subclasses
     *
     * @param string $user_id_column
     *
     * @return string
     */
    protected function filterUsers(string $user_id_column): string
    {
        $sql = "
            AND {$user_id_column} NOT IN (
                SELECT user_id FROM users_settings AS u_s WHERE u_s.setting = 'test_account' AND u_s.value = 1
            )
        ";

        return $sql;
    }

    /**
     * @param string|null $signComparing
     *
     * @return string
     * @throws Exception
     */
    protected function getBaseUserWhereCondition(?string $signComparing = null): string
    {
        $sql = "
            users.country = '{$this->getCountry()}'
            AND users.nid != ''
            {$this->filterUsers('users.id')}
            AND users.id NOT IN (
                SELECT user_id FROM users_settings AS u_s WHERE u_s.setting = 'registration_in_progress' AND u_s.value >= 1
            )
        ";

        if ($signComparing !== '<=') {
            $sql .= "
                AND users.id IN (
                    SELECT user_id FROM users_settings AS u_s WHERE u_s.setting = 'registration_end_date' AND u_s.value <= '{$this->getPeriodEnd()}'
                )
            ";
        }

        if (in_array($signComparing, ['>=', '<='])) {
            $sql .= "
                AND users.id IN (
                    SELECT user_id FROM users_settings AS u_s WHERE u_s.setting = 'registration_end_date' AND u_s.value {$signComparing} '{$this->getPeriodStart()}'
                )
            ";
        }

        return $sql;
    }

    /**
     * @param array $records
     * @param string $group
     *
     * @return array
     */
    protected function setupRecords(array $records, string $group = ''): array
    {
        return [];
    }

    /**
     * 4.5.3 Type of Gambling
     * Return full list of all gambling types mentioned in the docs
     *
     * @return string[]
     */
    public function getGamblingTypes(): array
    {
        return [
            "AHC",
            "AHM",
            "ADC",
            "ADM",
            "AOC",
            "POT",
            "POC",
            "BLJ",
            "PUN",
            "BNG",
            "RLT",
            "COC",
            "COM",
            "ADX",
            "AOX",
            "AHX",
            "AZA"
        ];
    }

    /**
     * Cache game_tags and connections because game_tags is master only
     */
    private function loadGameTags()
    {
        //save cache on a static variable for mass generation of reports
        if(empty(self::$game_tags_cache)) {
            $game_tag_con = $this->db->loadArray(
                "SELECT game_id, alias FROM game_tag_con gtc INNER JOIN game_tags gt ON gtc.tag_id=gt.id"
            );

            self::$game_tags_cache = array_reduce($game_tag_con, function ($carry, $el) {
                if (empty($carry[$el['game_id']])) {
                    $carry[$el['game_id']] = [];
                }
                $carry[$el['game_id']][] = $el['alias'];

                return $carry;
            }, []);
        }

        $this->game_tags = self::$game_tags_cache;
    }

    /**
     *
     *  Get value for the given key from license settings,
     *  allow multilevel using dot notation level1.level2
     *  if doesnt exist return default value
     *
     * @param string $key access key (dot notation)
     * @param null $default default value
     * @return array|mixed|null value
     */
    protected function getLicSetting(string $key, $default = null)
    {
        if (empty($key)) {
            return $this->lic_settings;
        }

        $settings = $this->lic_settings;
        foreach (explode('.', $key) as $segment) {
            if (!isset($settings[$segment])) {
                return $default;
            }
            $settings = $settings[$segment];
        }

        return $settings;
    }

    protected function getCountry(): string
    {
        return ICSConstants::COUNTRY;
    }

    /**
     * Transform report data to XML
     *
     * @param bool $signed
     * @return string
     */
    public function toXML($signed = false): string
    {
        $report_content = array_merge(
            [
                'Cabecera' => [
                    'OperadorId' => $this->getOperatorId(),
                    'AlmacenId' => $this->getStorageId(),
                    'LoteId' => $this->getBatchId(),
                    'Version' => $this->getXmlVersion(),
                ]
            ],
            ['Registro' => $this->getData()]
        );

        $report_content = ArrayToXml::convert($report_content, $this->getRootElement());

        if ($signed) {
            $xml = new DOMDocument('1.0');
            $xml->loadXML($report_content);

            $private_key = $this->getLicSetting('DGOJ.ssl.private_key');
            $certificate = $this->getLicSetting('DGOJ.ssl.certificate');

            XAdES::sign($xml, $private_key, $certificate);

            $report_content = $xml->saveXML();
        }

        return $report_content;
    }

    /**
     * Create report encrypted zip file
     * @param null $file_path
     * @param null $password
     * @param bool $signed
     * @throws Exception
     */
    public function toZip($file_path = null, $password = null, $signed = true)
    {
        $content = $this->toXML($signed);
        $zipFile = new \PhpZip\ZipFile();

        $zipFile->addFromString('enveloped.xml', $content, ZipCompressionMethod::DEFLATED);

        if (!empty($password)) {
            $zipFile->setPassword($password, ZipEncryptionMethod::WINZIP_AES_256);
        }

        $zipFile
            ->saveAsFile($file_path)
            ->close();
    }

    /**
     * Get the XML root element
     *
     * @return array
     */
    private function getRootElement() :array
    {
        $xmlns = str_replace('{version}', $this->getXmlVersion(), ICSConstants::XMLNS_URL);
        $schema_location = str_replace('{version}', $this->getXmlVersion(), ICSConstants::SCHEMA_LOCATION);

        return [
            'rootElementName' => 'Lote',
            '_attributes' => [
                'xmlns' => $xmlns,
                'xsi:schemaLocation' => $xmlns . ' ' . $schema_location,
                'xmlns:xsi' => ICSConstants::XMLNS_XSI
            ]
        ];

    }

    /**
     * Setup header for rectification reports
     *
     * @param $data
     * @return $this
     * @throws Exception
     */
    public function rectifyReport($data): BaseReport
    {
        $this->rectification = [
            'Rectificacion' => [
                'RegistroId' => $data['id'],
                // if invalid date supplied we expect exception and regeneration to fail
                'RegistroFecha' => (new DateTime($data['date']))->format(ICSConstants::DATETIME_FORMAT),
            ]
        ];

        return $this;
    }

    /**
     * Get Registro Header data
     *
     * @param int $subregister_index
     * @param int $total_subregisters
     *
     * @return array
     * @throws Exception
     */
    public function getRecordHeader(int $subregister_index = 1, int $total_subregisters = 1): array
    {
        return array_merge([
            // Operator Id
            'OperadorId' => $this->getOperatorId(),
            // Storage Id
            'AlmacenId' => $this->getStorageId(),
            // Identifier for the report ,  It  must  be  unique  in  the  Storage  System and operator's system.
            'RegistroId' => $this->getReportId(),
            // Current subdivision index. If the record is not divided into sub-records = 1
            'SubregistroId' => $subregister_index,
            // Total amount of divisions if no sub-records = 1
            'SubregistroTotal' => $total_subregisters,
            'Fecha' => phive()->fDate($this->getGenerationDate(), ICSConstants::DATETIME_FORMAT),
            // The  model  allows  data  from  a  previous  record  to  be  rectified.  The  rectification  completely  cancels  the  referenced  record
            // and  it  must  submit a new record with the correct information.The  referenced  record  must  not  be  deleted  from  the  operator's  database.
            // The  rectification  can  only  refer  to  a  record  within  the  same  storage  system.The RecordID of the record to be rectified and cancelled shall be noted,
            // as well as the date/time of the referenced record.If the rectification does not refer to any record, it shall be considered a duplicate record and thus rejected
        ], $this->rectification);
    }

    /**
     * @return string
     */
    protected function getFileName(): string
    {
        return implode('_', [
            $this->getOperatorId(),
            $this->getStorageId(),
            static::TYPE,
            static::SUBTYPE,
            ICSConstants::FREQUENCY_FILE_NAME_VALUES[$this->getFrequency()],
            $this->getDate()->format($this->getFrequency() === ICSConstants::DAILY_FREQUENCY ? ICSConstants::DAY_FORMAT : ICSConstants::MONTH_FORMAT),
            $this->getBatchId(),
        ]);
    }

    /**
     * Set the report batch id
     *
     * @return self
     */
    public function setBatchId(): self
    {
        // TODO LEON review batchID generation
        $this->batchId = phive()->today('ymd') . $this::TYPE . uniqid().'-'. static::getInternalVersion();

        return $this;
    }

    /**
     * Set the frequency of the report
     *
     * @param string $frequency
     *
     * @return self
     */
    public function setFrequency(string $frequency): self
    {
        $this->frequency = $frequency;

        return $this;
    }

    /**
     * Get the report frequency
     *
     * @return string
     */
    public function getFrequency(): string
    {
        return $this->frequency;
    }

    /**
     * Get the batch id
     *
     * @return string
     */
    public function getBatchId(): string
    {
        return $this->batchId;
    }

    /**
     * Get the report operator id
     *
     * @return string
     */
    public function getOperatorId(): string
    {
        return $this->operator_id;
    }

    /**
     * Get the report storage id
     *
     * @return string
     */
    public function getStorageId(): string
    {
        return $this->storage_id;
    }

    /** @throws Exception */
    public function getXmlVersion(): string
    {
        return Info::getXmlVersion();
    }

    /**
     * Get the report data
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Get the report id
     *
     * @return string
     */
    public function getReportId(): string
    {
        return $this->report_id;
    }

    /**
     * Set the report id
     *
     * @return self
     */
    public function setReportId(): self
    {
        $this->report_id = uniqid();

        return $this;
    }

    /**
     * Get the report data
     *
     * @return DateTime
     */
    public function getDate(): DateTime
    {
        return $this->date;
    }

    /**
     * Get the report period start
     * @param null|string $modify
     * @return string
     * @throws Exception
     */
    public function getPeriodStart($modify = null): string
    {
        if (!empty($modify)) {
            return (new DateTime($this->period_start))->modify($modify)->format('Y-m-d H:i:s');
        }

        return $this->period_start;
    }

    /**
     * Set the report period start
     *
     * @param null $period_start
     *
     * @return self
     * @throws Exception
     */
    public function setPeriodStart($period_start = null): self
    {
        if (empty($period_start)) {
            $period_start = $this->getFrequency() === ICSConstants::DAILY_FREQUENCY
                ? phive()->yesterday('Y-m-d 00:00:00')
                : date('Y-m-d 00:00:00', strtotime('first day of last month'));
        }

        $this->period_start = $period_start;

        return $this;
    }

    /**
     * Get the report period end
     *
     * @param null|string $modify
     * @return string
     * @throws Exception
     */
    public function getPeriodEnd($modify = null): string
    {
        if (!empty($modify)) {
            return (new DateTime($this->period_end))->modify($modify)->format('Y-m-d H:i:s');
        }

        return $this->period_end;
    }

    /**
     * Set the report period end
     *
     * @param null $period_end
     *
     * @return self
     * @throws Exception
     */
    public function setPeriodEnd($period_end = null): self
    {
        if (empty($period_end)) {
            $period_end = phive()->yesterday('Y-m-d 23:59:59');
        }

        $this->period_end = $period_end;
        $this->date = new DateTime($period_end);

        return $this;
    }

    /**
     * Get the ISO code
     *
     * @return string
     */
    public function getISO(): string
    {
        return $this->iso;
    }

    /**
     * Generate zip file
     *
     * @return string
     */
    public function generateZip()
    {
        // TODO LEON implement
    }

    /**
     * Create Period structure for XML report
     * @param $frequency
     *
     * @return array
     * @throws Exception
     */
    protected function getPeriod($frequency): array
    {
        if ($frequency === ICSConstants::DAILY_FREQUENCY) {
            return ['Dia' => phive()->fDate($this->getPeriodEnd(), ICSConstants::DAY_FORMAT)];
        }

        return ['Mes' => phive()->fDate($this->getPeriodEnd(), ICSConstants::MONTH_FORMAT)];
    }

    /**
     * Convert amount to EUR and return the value in the required format
     *
     * @param int|array $amount
     * @param string $currency
     * @return array[]
     */
    protected function formatAmount($amount, string $currency = ICSConstants::CURRENCY): array
    {
        if(!is_array($amount)){
            $amount = [$currency => $amount];
        }
        $lines = [];
        foreach($amount as $unit => $value){
            $lines[] = [
                'Cantidad' => $this->format2Decimal($value),
                'Unidad' => $unit
            ];
        }

        return [
            'Linea' => $lines
        ];

    }

    /**
     * Breakdown by operator
     *
     * @param array $operations
     * @return array
     */
    protected function formatBreakdownByOperator(array $operations): array
    {
        // We are only a single operator so we only have 1 breakdown
        // If we dont need to report anything about GP, we can do the total in the query itself

        $operations =  array_reduce(
            $operations,
            function ($carry, $operation) {
                $carry['total'] = $carry['total'] + $operation['amount'];
                $carry['breakdown'][] = [
                    'OperadorId' => $this->getOperatorId(),
                    'Importe' => $this->formatAmount($operation['amount']),
                ];
                return $carry;
            },
            [
                'total' => 0,
                'breakdown' => []
            ]
        );

        if ($operations['total'] === 0) {
            return [
                'Total' => $this->formatAmount($operations['total']),
            ];
        }

        return [
            'Total' => $this->formatAmount($operations['total']),
            'Desglose' => $operations['breakdown']
        ];
    }

    /**
     * Breakdown by operator description
     *
     * @param array $operations
     * @return array|int[]
     */
    protected function formatBreakdownByOperatorDescription(array $operations)
    {
        // We are only a single operator so we only have 1 breakdown
        // If we dont need to report anything about GP, we can do the total in the query itself

        $operations =  array_reduce(
            $operations,
            static function ($carry, $operation) {
                $currency = $operation['currency'] ?? ICSConstants::CURRENCY;
                $bonus_reason = $operation['description'] ?? '';
                $carry['total'][$currency] = ($carry['total'][$currency] ?? 0) + $operation['amount'];
                $carry['breakdown'][$bonus_reason][$currency] = $operation['amount'] + ($carry['breakdown'][$bonus_reason][$currency] ?? 0);

                return $carry;
            },
            [
                'total'     => [ICSConstants::CURRENCY => 0],
                'breakdown' => []
            ]
        );

        if (empty($operations['breakdown'])) {
            return [
                'Total' => $this->formatAmount(0),
            ];
        }

        $desglose = [];

        foreach ($operations['breakdown'] as $bonus_reason => $amount) {
            $desglose[] = [
                'OperadorId' => $this->getOperatorId(),
                // The concept 'Concepto' under bonus should contain 'LIBERACION' for redeemed bonus, 'CANCELACION' for the cancellation of bonus, 'CONCESION' for granted bonus.
                'Concepto' => $bonus_reason,
                'Importe' => $this->formatAmount($amount),
            ];
        }

        return [
            'Total' => $this->formatAmount($operations['total']),
            'Desglose' => $desglose
        ];
    }

    /**
     * Check if we can get the full list of game_ref to do a single DB query with all of them
     *
     * 4.5.3 Type of Gambling
     * ● AHC: Bets with a Bookmaker on Horse-racing Events // NO NEED ATM SPORTSBOOK
     * ● AHM: Parimutuel Bets on Horse-racing Events // NO NEED ATM SPORTSBOOK
     * ● ADC: Bets with a Bookmaker on Sporting Events // NO NEED ATM SPORTSBOOK
     * ● ADM: Parimutuel Bets on Sporting Events // NO NEED ATM SPORTSBOOK
     * ● AOC: Other Bets with a Bookmaker // NO NEED ATM SPORTSBOOK
     * ● AZA: Online slot-machines
     * ● POT: Tournament Poker
     * ● POC: Cash Poker
     * ● BLJ: Blackjack
     * ● PUN: Punto Banco
     * ● BNG: Bingo
     * ● RLT: Roulette
     * ● COC: Competitions
     * ● COM: Additional Games
     * ● ADX: Exchange Bets on Sporting Events //NO NEED ATM SPORTSBOOK
     * ● AOX: Other Exchange Bets //NO NEED ATM SPORTSBOOK
     * ● AHX: Exchange Bets on Horse-racing Events //NO NEED ATM SPORTSBOOK
     *
     * @param $game_tag
     * @param null|string $game_id
     * @return string
     */
    public function getGameType($game_tag, $game_id = null): string
    {
        if (!isset(static::$game_type_cache[$game_tag][$game_id])) {
            $game_sub_tags = $this->getGameSubTags($game_id);
            $gambling_license = $this->mg->getExpandedGameCategoryByTagAndSubtag($game_tag ?? "", $game_sub_tags);

            if (!isset(static::$game_type_cache[$game_tag])) {
                static::$game_type_cache[$game_tag] = [];
            }

            static::$game_type_cache[$game_tag][$game_id] = $this->lic_settings['ICS']['game_type'][$gambling_license] ?? 'AZA';
        }

        return static::$game_type_cache[$game_tag][$game_id];
    }

    /**
     * Return game variant for the requested game, if the game type require a variant.
     * This is extracted from the game_tags associated to the game.
     *
     * @param $game_tag - mg.tag
     * @param $game_id - mg.id
     * @return string|false - if game_type require variant => string from tag (or default), else => false
     */
    public function getGameVariant($game_tag, $game_id): string
    {
        $game_sub_tags = $this->getGameSubTags($game_id);
        $game_type = $this->getGameType($game_tag, $game_id);
        $mapping = [
            'RLT' => [
                'default' => 'Francesa',
                '_roulette-french.cgames' => 'Francesa',
                '_roulette-american.cgames' => 'Americana'
            ],
            'BLJ' => [
                'default' => 'CL',
                '_blackjack-classic.cgames' => 'CL',
                '_blackjack-american.cgames' => 'AM',
                '_blackjack-ponton.cgames' => 'PO',
                '_blackjack-surrender.cgames' => 'SU',
                '_blackjack-super21.cgames' => '21'
            ]
        ];
        // Current game type doesn't support variant
        if(!isset($mapping[$game_type])) {
            return false;
        }
        foreach($game_sub_tags as $game_sub_tag) {
            if(array_key_exists($game_sub_tag, $mapping[$game_type])) {
                return $mapping[$game_type][$game_sub_tag];
            }
        }
        // Return default value to prevent generating XML not compliant with XSD schema
        return $mapping[$game_type]['default'];
    }

    /**
     * Return list of game sub tags
     *
     * @param $game_id
     * @return array
     */
    public function getGameSubTags($game_id): array
    {
        return $this->game_tags[$game_id] ?? [];
    }

    /**
     * Convert list of operations to summary and breakdown by game type
     *
     * @param array $operations
     * @param bool $should_report_all_games_types
     *
     * @return array|\array[][]
     */
    protected function formatBreakdownByGameType(array $operations, bool $should_report_all_games_types = false): array
    {
        $operations = array_reduce(
            $operations,
            function ($carry, $operation) {
                $carry['total'] += $operation['amount'];
                $ext_game_type = $this->getGameType($operation['game_tag'], $operation['game_id']);

                $carry['breakdown'][$ext_game_type] = $operation['amount'] + ($carry['breakdown'][$ext_game_type] ?? 0);

                return $carry;
            },
            [
                'total' => 0,
                'breakdown' => []
            ]
        );

        if ($operations['total'] === 0 && !$should_report_all_games_types) {
            return [
                'Total' => $this->formatAmount($operations['total']),
            ];
        }

        $desglose = [];

        // We should report all licensed game's types for CJT
        if ($should_report_all_games_types) {
            foreach ($this->getLicensedExternalGamblingTypes() as $ext_game_type) {
                if (empty($operations['breakdown'][$ext_game_type])) {
                    $operations['breakdown'][$ext_game_type] = 0;
                }
            }
        }

        foreach ($operations['breakdown'] as $ext_game_type => $amount) {
            $desglose[] = [
                'OperadorId' => $this->getOperatorId(),
                'TipoJuego' => $ext_game_type,
                'Importe' => $this->formatAmount($amount),
            ];
        }

        return [
            'Total' => $this->formatAmount($operations['total']),
            'Desglose' => $desglose
        ];
    }

    /**
     * Convert transactional operation to the required structure
     *
     * @param Transaction $operation
     * @param string $format
     *
     * @return array
     */
    public function mapOperationFormat(
        Transaction $operation,
        string $format = ICSConstants::DATETIME_FORMAT,
        ?string $group = null
    ): array {

        $tipo_medio_pago = $operation->getCardType() ?: $operation->getPaymentMethodType();

        $operation_formatted = [
            'Fecha' => $operation->getTimestamp($format),
            'MedioPago' => $operation->getPaymentMethod(),
            'TipoMedioPago' => $tipo_medio_pago,
            'OtroTipoEspecificar' => $tipo_medio_pago == 99 ? $operation->getDisplayName() : '',
            'DigitosMedioPago' => $operation->getLastFourDigitsCard(),
            'Importe' => $operation->getAmount(),
            'IP' => $operation->getIp(),
            'Dispositivo' => $operation->getDeviceType(),
            'IdDispositivo' => $operation->getDeviceId(),
        ];

        if (empty($operation_formatted['OtroTipoEspecificar'])) {
            unset($operation_formatted['OtroTipoEspecificar']);
        }

        if (empty($operation_formatted['DigitosMedioPago'])) {
            unset($operation_formatted['DigitosMedioPago']);
        }

        return $operation_formatted;
    }

    /**
     * Format monetary operations
     *
     * @param array $operations
     * @param string $breakdown_key
     *
     * @return array|int[]
     */
    protected function formatOperations(array $operations, string $breakdown_key = 'Desglose', ?string $group = null): array
    {
        $format = $breakdown_key === 'Operaciones' ? ICSConstants::DATETIME_TO_GMT_FORMAT : ICSConstants::DATETIME_FORMAT;

        $operations = array_reduce(
            $operations,
            function ($carry, $operation) use ($format, $group) {
                $operation = new Transaction($operation, $this->lic_settings);

                $carry['total'] = $carry['total'] + $operation->getAmount();
                $carry['operations'][] = $this->mapOperationFormat($operation, $format, $group);
                return $carry;
            },
            [
                'total' => 0,
                'operations' => []
            ]
        );

        if (empty($operations['operations'])) {
            return [
                'Total' => number_format($operations['total'], 2, '.', ''),
            ];
        }

        return [
            'Total' => number_format($operations['total'], 2, '.', ''),
            $breakdown_key => $operations['operations']
        ];
    }

    /**
     * Group operations based on transaction getter
     *
     * @param $operations
     * @param $map_function
     * @param string $merge_column
     * @return array
     */
    protected function groupTransactions($operations, $map_function, $merge_column = 'amount'): array
    {
        $operations = array_reduce($operations, function ($carry, $operation) use ($map_function, $merge_column) {
            $method = $map_function($operation);
            $operation['gambling_type'] = $method;

            if (empty($carry[$method])) {
                $carry[$method] = $operation;
            } else {
                $carry[$method][$merge_column] += $operation[$merge_column];
            }

            return $carry;
        }, []);

        return array_values($operations);
    }

    protected function setUpBatching(
        array $items = [],
        int $items_per_record = ICSConstants::ITEMS_PER_SUBRECORD,
        int $records_per_batch = ICSConstants::RECORD_PER_BATCH
    ): array {
        $records = array_chunk($items, $items_per_record);
        return array_chunk($records, $records_per_batch);
    }

    /**
     * Return either frequency or day format
     *
     * @return string
     */
    public function getFrequencyDirectory(): string
    {
        if (array_key_exists($this->getFrequency(),ICSConstants::FREQUENCY_DIRECTORY_VALUES)) {
            return ICSConstants::FREQUENCY_DIRECTORY_VALUES[$this->getFrequency()];
        }

        throw new \InvalidArgumentException(sprintf('Invalid frequency type: %s', $this->getFrequency()));
    }

    /**
     * Used only by JU* reports for game type directory
     *
     * @return null
     */
    public function getExtraDirectory()
    {
        return null;
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
            $this::TYPE,
            $this->getFrequencyDirectory(),
            $this::SUBTYPE,
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

    /**
     * @return iterable
     * @throws Exception
     */
    public function getFiles(): iterable
    {
        $chunk = $this->getGroupedRecords();
        // We should generate report even if we do not have any users by conditions
        if (empty($chunk) && $this->shouldGenerateEmptyReport()) {
           foreach ($this->getTypes() as $type) {
               $chunk[$type] = [
                   []
               ];
           }
        }

        foreach ($chunk as $group => $records) {
            $self = clone $this;
            $self->setBatchId();
            $self->type = $group;
            $self->data = $self->setupRecords($records, $group);

            yield $self;
        }
    }

    /**
     * Format cents into euro with 2 decimals
     *
     * @param $cents
     * @return string
     */
    public function format2Decimal($cents): string
    {
        return rnfCents($cents, '.', '');
    }

    /**
     * Save current report in database
     *
     * @return mixed
     * @throws Exception
     */
    public function storeReport()
    {
        return phive('SQL')->insertArray("external_regulatory_report_logs", [
            'regulation' => $this->getLicSetting('regulation'),
            'report_type' => $this::SUBTYPE,
            'report_data_from' => $this->getPeriodStart(),
            'report_data_to' => $this->getPeriodEnd(),
            'unique_id' => $this->getReportId(),
            'sequence' => $this->type, // game type OR array index
            'filename_prefix' => $this->getFileName(),
            'file_path' => $this->getTargetDirectory(),
            'log_info' => json_encode([
                'frequency' => $this->getFrequency(),
                'rectification_info' => $this->rectification,
            ], JSON_THROW_ON_ERROR),
        ]);
    }

    /**
     * Save xml file to the correct location
     *
     * @param string $type
     * @return string
     * @throws Exception
     */
    public function saveFile($type = self::GENERATE_PLAIN): string
    {
        $file = $this->getTargetDirectory() . '/' . $this->getFileName();
        $zip = false;
        $signed = false;

        if ($type === self::GENERATE_ZIP) {
            $signed = $zip = true;
            $file .= '.zip';
        } elseif ($type === self::GENERATE_SIGNED) {
            $signed = true;
            $file .= '_signed.xml';
        } else {
            $file .= '.xml';
        }

        if ($zip) {
            $this->toZip($file, $this->getLicSetting('regulation_password'), $signed);
        } else {
            $xml = new DOMDocument('1.0');
            if (!$signed) { //formatting breaks the signature, but we can reformat for PLAIN debug
                $xml->preserveWhiteSpace = false;
                $xml->formatOutput = true;
            }
            $xml->loadXML($this->toXML($signed));
            if ($xml->save($file) === false) {
                throw new Exception("Could not save xml file. {$file}");
            }
        }

        if (empty($this->storeReport())) {
            sleep(5);
            //try to reconnect DB and try again
            try {
                phive('SQL')->close();
                if (empty($this->storeReport())) {
                    unlink($file); //remove the file, it should be generated again
                    throw new Exception("Could not store report. {$file}");
                }
            }catch (Exception $exception){
                unlink($file); //remove the file, it should be generated again
                throw new Exception("Could not store report. {$file}.".$exception->getMessage());

            }
        }

        return $file;
    }

    protected function getLicense(): ES
    {
        if ($this->license === null) {
            $this->license = phive('Licensed')->getLicense($this->getCountry());
        }

        return $this->license;
    }

    protected function getBonusTranslation(string $description): string{
        if(!isset(self::$localizer)) {
            self::$localizer = phive('Localizer');
            //we need to set it for handleReplacements inside getPotentialString
            self::$localizer->setLang(ES::FORCED_LANGUAGE);
        }

        if(!isset(self::$bonus_names[$description])){

            self::$bonus_names[$description] = self::$localizer->getPotentialString(
                $description,
                \ES::FORCED_LANGUAGE
            );
        }

        return self::$bonus_names[$description];
    }

    /**
     * @return bool
     */
    protected function shouldGenerateEmptyReport(): bool
    {
        return true;
    }

    /**
     * @return array
     */
    protected function getTypes(): array
    {
        return [0];
    }

    /**
     * Return list of licensed gambling types
     *
     * @return string[]
     */
    public function getLicensedExternalGamblingTypes(): array
    {
        return $this->getLicSetting('ICS.licensed_external_game_types', []);
    }

    /**
     * Include only specific users based on these conditions:
     * - Daily. Information relating to the gambling accounts showing some type of transaction during the day shall be included.
     * - Monthly. Information relating to all gambling accounts registered on the operator's platform shall be included on a monthly basis.
     *
     * @param string $user_id_column
     *
     * @return string
     * @throws Exception
     */
    protected function filterByUserId(string $user_id_column): string
    {
        if ($this->getFrequency() === ICSConstants::DAILY_FREQUENCY) {
            $sql = "
                AND {$user_id_column} IN ({$this->getSqlSelectUsersIdsDailyStats()})
            ";
        } else {
            $sql = "
                AND {$user_id_column} IN ({$this->getSqlUsersIdsFullyRegistered()})
            ";
        }

        return $sql;
    }

    /**
     * @return string
     * @throws Exception
     */
    protected function getSqlSelectUsersIdsDailyStats(): string
    {
        return "
            SELECT DISTINCT user_id
            FROM external_regulatory_user_balances
            WHERE
                balance_date BETWEEN '{$this->getPeriodStart()}' AND '{$this->getPeriodEnd()}'
                AND user_id IN ({$this->getSqlUsersIdsFullyRegistered()})
        ";
    }

    /**
     * @return string
     * @throws Exception
     */
    protected function getSqlUsersIdsFullyRegistered(): string
    {
        return "
            SELECT id
            FROM users
            WHERE {$this->getBaseUserWhereCondition()}
        ";
    }

    /**
     * @param string $bonus_type
     *
     * @return string
     */
    protected function getBonusDescription(string $bonus_type): string
    {
        return ICSConstants::BONUS_TYPE_DESCRIPTIONS[$bonus_type] ?? ICSConstants::BONUS_DESCRIPTION_CONCESSION;
    }

    /**
     * @param array $game_types
     *
     * @return $this
     */
    public function setGameTypes(array $game_types): self
    {
        if (!empty($game_types)) {
            // we should set only game types that we have in `ICS.licensed_external_game_types` config
            $lic_game_types = $this->getLicensedExternalGamblingTypes();

            $game_types = array_intersect($lic_game_types, $game_types);
        }

        $this->game_types = $game_types;

        return $this;
    }

    /**
     * @return array
     */
    public function getGameTypes(): array
    {
        return $this->game_types;
    }

    /**
     * Get the report generation date
     *
     * @param null $modify
     * @return string
     * @throws Exception
     */
    public function getGenerationDate($modify = null): string
    {
        if (!empty($modify)) {
            return (new DateTime($this->generation_date))->modify($modify)->format('Y-m-d H:i:s');
        }

        return $this->generation_date;
    }

    /**
     * Set the report generation date
     *
     * @param null $generation_date
     * @return $this
     * @throws Exception
     */
    public function setGenerationDate($generation_date = null): self
    {
        if(empty($generation_date)){
            $generation_date = phive()->today('Y-m-d H:i:s');
        }

        $this->generation_date = $generation_date;

        return $this;
    }
    /**
     * @return float
     */
    public static function getInternalVersion()
    {
        return static::$internal_version;
    }

    public static function compareAmounts($val1, $val2): bool
    {
        $val1 = (int) round($val1*100);
        $val2 = (int) round($val2*100);

        return $val1===$val2;
    }

    /**
     * Get only cards with TipoMedioPago 13 (undefined cards)
     *
     * @param array $cards
     * @param string $field The field that stores card hash
     * @return array
     * @throws Exception
     */
    public function getOnlyUndefinedCardHashes(array $cards, string $field): array
    {
        return array_column(array_filter($cards, function($card){
            return in_array($card['type'], $this->lic_settings['ICS']['payment_method'][ICSConstants::UNDEFINED_CARD_TYPE]);
        }), $field);
    }

    /**
     * Set the statuses array from the csv file. Used in RU reports
     *
     * @return self
     */
    public function setCsvStatuses(): self
    {
        return $this->setCsvStatusesArray();
    }

    /**
     * Set up the statuses array from the csv file. Overwritten in RU reports
     *
     * @return self
     */
    public function setCsvStatusesArray(): self
    {
        return $this;
    }

    /**
     * Set the deposit limits array from the csv file. Used in RU reports
     *
     * @return self
     */
    public function setCsvDepositLimits(): self
    {
        return $this->setCsvDepositLimitsArray();
    }

    /**
     * Set up the deposit limits array from the csv file. Overwritten in RU reports
     *
     * @return self
     */
    public function setCsvDepositLimitsArray(): self
    {
        return $this;
    }

    protected function removeKeyRecursive(array $array, $keyToRemove): array
    {
        foreach ($array as $key => $value) {
            if ($key === $keyToRemove) {
                unset($array[$key]);
            } elseif (is_array($value)) {
                $array[$key] = $this->removeKeyRecursive($value, $keyToRemove);
            }
        }
        return $array;
    }

    protected function isValidLength($value, int $maxLength): bool
    {
        return strlen($value) <= $maxLength;
    }

    protected function limitLength(string $string, int $max): string
    {
        return mb_substr($string, 0, $max);
    }
}
