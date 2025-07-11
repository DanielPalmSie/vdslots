<?php
/**
 * All SAFE related stuff will go there
 */
require 'SafeParams.php';
require 'DataService.php';
require 'ZipHandler.php';
require 'Constants/SafeConstants.php';
require 'Helpers/SafeXmlGenerator.php';
require 'Helpers/SafeLog.php';
require 'Traits/RegenerateDataTrait.php';
require 'Traits/CancelPreviousSession.php';
require 'Traits/HandleToken.php';
require 'Traits/CancelSessionFromXML.php';

class SAFE
{
    use RegenerateDataTrait,
        CancelPreviousSession,
        HandleToken,
        CancelSessionFromXML;

    const SAFE_LATEST_FILE_SUFFIX = "-E";

    /**
     * The name of the key in misc_cache table when we have a running report.
     */
    const TAMPER_TOKEN_RUNNING_REPORT = 'tamperToken';

    /**
     * The name of the key in misc_cache table when we have a running report.
     */
    const CANCEL_RUNNING_REPORT = 'cancelReport';

    /**
     * The key on misc_cache to make sure the token request is done.
     */
    const SAFE_SENDING_TOKEN_REQUEST_KEY = 'SAFE_SENDING_TOKEN_REQUEST';

    /**
     * Max number of retries to connect to the SAFE in case of:
     * - another process is running
     * - the folder still contains TMP files that need to be processed by the CRON in the SAFE
     * to avoid failing when a little extra wait can solve the issue.
     */
    private $retry_safe = 3;
    private $retry_interval = 10; // Seconds

    protected $iso;

    /**
     * The frequency of the execution of the CRON in the SAFE box.
     *
     * We use this variable to wait for the CRON execution in case we need
     * to enforce a regeneration of data before closing the tamper token.
     *
     * In seconds
     *
     * @var int
     */
    private $cron_interval = 60;

    /**
     * Final output filename
     */
    protected $outputFilename;

    /**
     * Final output
     */
    protected $export_path;

    /**
     * Namespace (with the version of the XML provided ??)
     */
    protected $xmlNamespace;

    /**
     * Licensee (Company) unique identifier provided by DGA
     */
    protected $SpilCertifikatIdentifikation;


    /**
     * DOM object to send To SAFE
     */
    private $output;


    /**
     * @var  string id sent to SAFE ,
     * it will  be saved if the file is sent correctly
     */
    protected $cursor;

    /**
     * Cursor in the second brands
     *
     * @var array
     */
    protected $cursor_secondary =[];


    /*
     * @var uuid  id for the EndOftheDay Report ,
     * used to replacement of the data
     */
    protected $uuid;

    /**
     * Lic settings.
     *
     * @var array
     */
    protected $lic_settings = [];

    /**
     * Regenerate or cancel according with the brand
     *
     * @var string
     */
     public $brand_SpilHjemmeside = 'all';

    /**
     * keep track of the last generated tamper token and the sequence number for the next report to be generated
     */
    const SAFE_PARAM_KEY = 'SAFE_params';

    /**
     * Prevent 2 report to run at the same time to avoid breaking MAC / tamper token generation
     */
    const RUNNING_REPORT_KEY = 'SAFE_report_running';

    /**
     * Keep track of info for report cancellation, needed if we need to void some data.
     */
    const PENDING_CANCEL_KEY = 'SAFE_pending_cancel';

    /**
     * SAFE regeneration script currently running
     */
    const REGENERATION_RUNNING = 'SAFE_regenerate_running';

    const DAILY_LOG_KEY = 'SAFE_daily_report_log';


    const SAFE_CURSOR_SECONDARY = "secondary_cursor";

    /**
     * @var string Current report date - only for EndOfDay report
     */
    protected $report_date;

    /**
     * This object will hold the Safe Params
     *
     * @var SafeParams
     */
    protected $safe_params;

    /**
     * @var SafeLog
     */
    protected $safe_log;

    /**
     * @var string
     */
    protected $currency;

    /**
     * This instance tells if we calculate rollbacks when we get game session data for the reports on KasinoSpil.
     *
     * @var bool
     */
    protected $should_calculate_rollback_kasinospil = true;

    /**
     * This instance tells if we calculate rollbacks when we get game session data for the reports on EndOfDay.
     *
     * @var bool
     */
    protected $should_calculate_rollback_endofday = true;

    /**
     * Keeping track on which token are we regenerating. Gets saved on
     *
     * @var string
     */
    protected $regenerating_from_token = '';

    /**
     * Used to put a static game session in case when the data is coming from outside our database.
     * If this is set we don't query our database to get game session.
     *
     * @var array
     */
    public $custom_report_data = [];

    public function __construct($iso, $settings = [])
    {
        $this->iso = $iso;

        $this->export_path = $settings['export_path'];
        $this->xmlNamespace = $settings['xml_namespace'];
        $this->SpilCertifikatIdentifikation = $settings['SpilCertifikatIdentifikation'];
        $this->lic_settings = $settings;

        $logs_enabled = isset($this->lic_settings['logs_enabled']) ? $this->lic_settings['logs_enabled'] : true;
        $this->safe_log = new SafeLog($settings['log_folder'], $logs_enabled);
    }

    /**
     * Update the token cursor.
     *
     * @param $cursor
     */
    public function setCursor($cursor)
    {
        $this->safe_params->setCursor($cursor);

        $this->saveParams();
    }

    public function getCursor(){
        return $this->safe_params->getCursor();
    }

    /**
     * Ssaving the cursor for diferents brands
     */
    public function setSecondaryCursor(){
        if(!empty($this->cursor_secondary)){
            phive()->miscCache(self::SAFE_CURSOR_SECONDARY, json_encode($this->cursor_secondary), true);
        }
    }

    /**
     * Path to main folder of SAFE reports.
     *
     * @return string
     */
    protected function pathToMainTokensFolder()
    {
        $tamper_token = $this->safe_params->getTokenId();
        $token_start_datetime = date('Y-m-d', strtotime($this->safe_params->getTokenStartDatetime()));
        return "$this->export_path/{$token_start_datetime}/{$this->SpilCertifikatIdentifikation}-{$tamper_token}/";
    }

    /**
     * Create the  path for the SAFE.
     *
     * @param $type
     * @return string - The path in the SAFE
     */
    protected function reportsFolderPath($type)
    {
        return $this->pathToMainTokensFolder() . $type . "/" . date('Y-m-d') . "/";
    }

    /**
     * Generate SAFE filenames.
     *
     * @return string
     */
    protected function generateLatestXmlFilename()
    {
        $token_id = $this->safe_params->getTokenId();

        return $this->SpilCertifikatIdentifikation . "-" . $token_id . self::SAFE_LATEST_FILE_SUFFIX . ".xml";
    }

    /**
     * Get the filename to update the previous one.
     *
     * @param $token_id
     * @param $sequence
     * @return string
     */
    protected function getLastXmlReportFilename($token_id, $sequence)
    {
        return $this->SpilCertifikatIdentifikation . "-" . $token_id . "-" . $sequence . ".xml";
    }

    /**
     * Convert Data to XML.
     *
     * @param array $game_session data fom database to create the XML
     * @param string $type type of the file , in this moment EndOfDay or KasinoSpil
     * @param bool $cancel
     * @return $this
     */
    private function toXML(array $game_session, $type = '', $cancel = false)
    {
        $this->safe_log->log("INFO :: Creating xml File ");
        $namespace = $this->xmlNamespace;

        $SpilFilIdentifikation = $this->generateUniqueID();
        $this->safe_log->log("INFO :: Creating the xml for this " . print_r($game_session, 1));

        $xml = "";
        $xml_content = [];

        if ($type == SafeConstants::END_OF_DAY) {
            // Used when we regenerate data. We report the uuid of the previous EndOfDay
            // which we are regenerating.
            $SpilFilErstatningIdentifikation = $this->uuid ?? '';

            $generated_data = SafeXmlGenerator::endOfDayToXml(
                $namespace,
                $game_session,
                $SpilFilIdentifikation,
                $SpilFilErstatningIdentifikation,
                $this->SpilCertifikatIdentifikation
            );

            $this->safe_params->setUuid($SpilFilIdentifikation);

            $xml = $generated_data['xml'];
            $xml_content = $generated_data['xml_content'];

            $this->safe_params->setXml($xml_content);

        } else if($type == SafeConstants::KASINO_SPIL) {
            $spil_hjemmeside = $this->lic_settings['SpilHjemmeside'];

            $generated_data = SafeXmlGenerator::kasinoSpilToXml(
                $namespace,
                $game_session,
                $cancel,
                $spil_hjemmeside,
                $SpilFilIdentifikation,
                $this->SpilCertifikatIdentifikation,
                $this->iso
            );

            $xml = $generated_data['xml'];
            $xml_content = $generated_data['xml_content'];
        }

        if ($xml instanceof SimpleXMLElement) {
            SafeXmlGenerator::arrayToXml($xml, $xml_content);
        }

        $dom = dom_import_simplexml($xml)->ownerDocument;
        $dom->formatOutput = true;
        $this->output = $dom;
        $this->currency = $game_session['currency'];

        return $this;
    }

    /**
     * Generating unique id. (Used on every XML)
     */
    protected function generateUniqueID(): string
    {
        $current_date_time = phive()->hisNow('', 'YmdHis');
        return phive()->uuid() . "-" . $current_date_time;
    }

    /**
     * Saving the file in local
     * @param $report_filename
     * @return object|bool
     */
    public function saveOutput($report_filename)
    {
        try {
            $this->output->save($report_filename);
            chmod($report_filename, 0777);
            $this->outputFilename = $report_filename;

            return $this;
        } catch (Exception $e) {
            phive('Logger')->error('safe_report_save-failed', $report_filename);
        }

        return false;
    }

    /**
     * Creation of a semaphore to avoid two reports at the same time and mismatch the mac parameter.
     * If there is no SAFE_report_running row in "misc_cache" it creates it.
     *
     * @param $pid
     * @param bool $insert_running_reports
     * @return bool
     */
    public function checkRunningReport($pid, bool $insert_running_reports = true)
    {
        $result = phive()->getMiscCache(self::RUNNING_REPORT_KEY);
        if (! empty($result)) {
            return true;
        } else {
            if ($insert_running_reports) {
                // Insert SAFE_report_running
                phive()->miscCache(self::RUNNING_REPORT_KEY, $pid);
            }

            return false;
        }
    }

    /**
     * Remove the  semaphore
     */
    public function removeReportRunning()
    {
        phive('SQL')->delete('misc_cache', ['id_str' => self::RUNNING_REPORT_KEY]);
    }

    /**
     * Creating the files for the SAFE
     * @param $type
     * @param $country
     * @param string $date - end_time
     * @param string $current_cursor - start_time
     * @param array $forced_values
     * @return bool false if there is another report running
     */
    public function exportData($type, $country, string $date = '', string $current_cursor = '', $forced_values = [])
    {
        $result = false;

        try {
            if ($this->checkRunningReport($type)) {
                return false;
            }

            $this->safe_log->log("INFO :: START EXPORT DATA PROCESS");

            if (!isCli() && ! $this->custom_report_data) {
                return false;
            }

            $result = false;

            $this->extractParams();
            $this->safe_params->setRegeneratedFromToken($this->regenerating_from_token);

            $this->safe_log->log("INFO :: selecting tamper token {$this->safe_params->getTokenId()}");
            $this->safe_log->log("Exporting data for {$type}");
            switch ($type) {
                case SafeConstants::END_OF_DAY:
                    $result = $this->exportForEndOfDay($type, $country, $date, $forced_values);
                    break;
                case SafeConstants::KASINO_SPIL:
                    $result = $this->exportKasinoSpilFile($type, $country, $date, $current_cursor);
                    break;
                case SafeConstants::EVOLUTION_KASINO_SPIL:
                    $result = $this->exportKasinoSpilFileForEvolution();
                    break;
                case SafeConstants::EVOLUTION_END_OF_DAY:
                    $result = $this->exportEndOfDayForEvolution();
                    break;
            }
        } catch (Error $e) {
            $this->safe_log->log("ERROR :: " . $e->getMessage());
            $this->removeReportRunning();
        }

        $this->removeReportRunning();

        return $result;
    }

    /**
     * Export data for end of the day.
     *
     * Normal flow: ($date is empty) the process will generate data for yesterday.
     * Regenerate flow: ($date is yyyy-mm-dd) we will regenerate the provided day.
     *
     * !!! IMPORTANT !!!
     * An extra check was added to prevent generating EndOfDay report on "normal flow" in case cursor is set in the future.
     * This allow us to prevent having the CRON generate report without having first KasinoSpil data. (more info on ch118859)
     *
     * @param $type
     * @param $country
     * @param $date - ''|yyyy-mm-dd - default to yesterday if nothing is passed.
     * @param $forced_values
     * @return bool
     */
    private function exportForEndOfDay($type, $country, $date, $forced_values = [])
    {
        // Prevent EndOfDay generation (when called via CRON) if cursor is in the future.
        if(empty($date)) {
            if($this->safe_params->getCursor() > phive()->hisNow()) {
                return;
            }
        }
        $eod_brands = $this->getReportFromRemoteBrand($country, $date, SafeConstants::END_OF_DAY, '', [], $forced_values);
        if (!$eod_brands['success']) {
            $this->safe_log->log("ERROR::Error getting data from remote Brand");
            return false;
        }
        $eod_result = $this->endOfDayData($country, $date, $forced_values);
        $eods_reports = $this->joinReportsBrand($eod_result, $eod_brands['result']);
        $result = false;
        if (!empty($eods_reports)) {
            $eods_brands = $this->logInExternalRegulatoryTable($eods_reports);
            $eods_reports = $this->sumBrands($eods_reports);
            $this->toXML($eods_reports, SafeConstants::END_OF_DAY);

            //Change names for handleReport
            $eods_reports['bet_count'] = $eods_reports['EndOfDayRapportAntalSpil'];
            unset($eods_reports['EndOfDayRapportAntalSpil']);
            unset($eods_reports['date']);

            $end_of_day_date = (!empty($date)) ? $date : phive()->yesterday();
            $result = $this->handleReport($type, $end_of_day_date, $end_of_day_date, false, $this->joinReportsBrand($eods_reports, $eods_brands));
        }

        return $result;
    }

    /**
     * Sum all reports EndOfDay to send to the SAFE.
     *
     * @param $report
     * @return array
     */
    private function sumBrands($report)
    {
        $report = [
            "EndOfDayRapportAntalSpil" => array_sum(array_column($report, 'EndOfDayRapportAntalSpil')),
            "win_amount" => array_sum(array_column($report, 'win_amount')),
            "bet_amount" => array_sum(array_column($report, 'bet_amount')),
            'date' => $report[0]['date']
        ];
        return $report;
    }

    /**
     * @param $reports
     * @return mixed
     */
    private function logInExternalRegulatoryTable($reports)
    {
        $brands = [];
        foreach ($reports as $report) {
            if (!empty($report)) {
                $brands[$report['SpilHjemmeside']] = $report;
            }
        }
        return $brands;
    }



    /**
     * Get game session data for end of day.
     *
     * @param $country
     * @param string $date
     * @param array $forced_values
     * @return array
     */
    public function endOfDayData($country, $date = '', $forced_values = [])
    {
        if (empty($date)) {
            $date = phive()->yesterday();
        }

        $this->report_date = $date;

        $excluded_providers = isset($this->lic_settings['excluded_providers']) ? $this->lic_settings['excluded_providers'] : [];

        $result = DataService::endOfDayDailyGameStats($date, $country, $excluded_providers, $this->should_calculate_rollback_endofday, $this->lic_settings['SpilHjemmeside']);

        if ($forced_values) {
            $result['EndOfDayRapportAntalSpil'] = $forced_values['bet_count'] ?? $result['EndOfDayRapportAntalSpil'];
            $result['bet_amount'] = $forced_values['bet_amount'] ?? $result['bet_amount'];
            $result['win_amount'] = $forced_values['win_amount'] ?? $result['win_amount'];
        }

        if (!empty($result)) {
            $this->toXML($result, SafeConstants::END_OF_DAY);
        }

        if (empty($result)) {
            $this->safe_log->log("INFO :: No data on users_daily_game_stats to put on EndOfDay");
        }
        if (phive('Distributed')->getSetting('safe_main_brand') !== true) {
            $this->safe_log->log("INFO :: Returning End ofThe day to the Main brand");
            return ['sessions' => [$result]];
        }
        return [$result];
    }

    /**
     * Exports XML file for KasinoSpil.
     *
     * @param string $type
     * @param string $country
     * @param string $date - end_time (standard flow: empty -> 5 sec in the past, regenerate flow: a full date is provided)
     * @param string $current_cursor - start_time
     * @return bool $result
     */
    private function exportKasinoSpilFile(string $type, string $country, string $date, string $current_cursor = '')
    {
        $sessions_brand = $this->getReportFromRemoteBrand($country, $date, SafeConstants::KASINO_SPIL, $current_cursor, $this->getSecondaryCursor());
        if (!$sessions_brand['success']) {
            $this->safe_log->log("ERROR!!!! :: Error getting data from remote Brand");
            return false;
        }
        $game_sessions = $this->kasinoSpilData($country, $date, $current_cursor);
        $result = false;
        $sessions_merge = $this->joinReportsBrand($game_sessions, $sessions_brand['result']);
        if (!empty($sessions_merge)) {
            $this->toXML($sessions_merge, SafeConstants::KASINO_SPIL);
            $game_session_result = $this->calculateSumOfGameSession($sessions_merge);
            if (!empty($current_cursor) && !empty($date)) {
                // put report_data_from and report_data_to dates on $current_cursor and $data on case of regenerating old data
                $result = $this->handleReport($type, $current_cursor, $date, true, $game_session_result);
            } else {
                $min = min(array_column($sessions_merge, 'end_time'));
                $max = max(array_column($sessions_merge, 'end_time'));
                // if in normal process (not on regenerate)
                $result = $this->handleReport($type, $min, $max, true, $game_session_result);
            }
        }

        return $result;
    }

    /**
     * Join all sessions from differents brand in the same array to send to SAFE
     *
     *
     * @param $session_primary
     * @param $sessions_from_brands
     * @return array
     */
    private function joinReportsBrand($session_primary, $sessions_from_brands)
    {
        if (!empty($session_primary) && !empty($sessions_from_brands)) {
            return array_merge($session_primary, $sessions_from_brands);
        }
        if (!empty($session_primary)) {
            return $session_primary;
        }
        return $sessions_from_brands;

    }

    /**
     * Get Reports for different brands( using in kasinoSpil and EndofDay in DK)
     * @param $country
     * @param $date - end_date (default value)
     * @param $type
     * @param string $current_cursor - start_date on remote brand (regenerate flow: based on data provided on regenerate script)
     * @param array $secondary_cursor - start_date on remote brand (standard flow: based on cursor)
     * @param array $forced_values - values that should replace the original values
     * @return array
     */
    private function getReportFromRemoteBrand($country, $date, $type, $current_cursor = '', $secondary_cursor = [], $forced_values = [])
    {
        if (phive('Distributed')->getSetting('safe_main_brand') !== true) {
            return ['success' => false, 'result' => []];
        }
        if ($this->brand_SpilHjemmeside !== 'all' || $this->brand_SpilHjemmeside == $this->lic_settings['SpilHjemmeside']) {
            return ['success' => true, 'result' => []];
        }
        $data_for_report = [];
        $received_data_from_remote_brand = false;
        foreach (phive('Distributed')->getSetting('safe_secondary') as $machine) {
            $info = dist($machine, 'Distributed', 'getDataReportfromSecondaryBrand', [$country, $date, $type, $current_cursor, $secondary_cursor[$machine], $forced_values]);
            if ($info['success']) {
                $received_data_from_remote_brand = true;
                if (!empty($info['result']['cursor'])) {
                    array_push($this->cursor_secondary, [$machine => $info['result']['cursor']]);
                }
                if ($info['result']['sessions']) { //avoid false  value
                    $data_for_report = array_merge($data_for_report, $info['result']['sessions']);
                }
            }
        }
        if (!$received_data_from_remote_brand) {
            return ['success' => false, 'result' => []];
        }

        return ['success' => true, 'result' => $data_for_report];
    }

    /**
     * @param $game_sessions
     *
     * @return int[]
     */
    private function calculateSumOfGameSession($game_sessions)
    {
        $game_session_result = [ 'bet_count' => 0, 'win_amount' => 0, 'bet_amount' => 0];
        foreach ($game_sessions as $item) {
            $game_session_result['win_amount'] += $item['win_amount'];
            $game_session_result['bet_amount'] += (float) $item['bet_amount'];
            $game_session_result['bet_count'] += $item['bet_cnt'];
        }

        return $game_session_result;
    }

    /**
     * Get game session data for kasino spil.
     *
     * @param string $country
     * @param string $date
     * @param string $current_cursor
     * @return mixed
     */
    private function kasinoSpilData(string $country, string $date = '', string $current_cursor = '')
    {
        $cursor = $this->getCursorForKasinoSpil($current_cursor);

        if (empty($cursor)) {
            $this->safe_log->log("No cursor for KasinoSpil please provide with a proper date on DK settings 'kasino_spil_start_datetime'");
            return false;
        }

        $game_sessions = DataService::kasinoSpilGameSessionData(
            $date,
            $cursor,
            $country,
            $this->should_calculate_rollback_kasinospil,
            $this->lic_settings['excluded_providers'],
            $this->lic_settings['SpilHjemmeside']
        );

        if (!empty($game_sessions)) {
            $game_sessions = phive()->sort2d($game_sessions, 'end_time', 'desc');
            foreach ($game_sessions as $index => $game_session) {
                $game_sessions[$index]['game'] = phive('MicroGames')->getByGameRef($game_session['game_ref']);
            }

            $this->cursor = $game_sessions[0]['end_time'];
        } else {
            $this->safe_log->log("INFO :: No game session to put on KasinoSpil");
        }
        if (phive('Distributed')->getSetting('safe_main_brand') !== true) {
            $this->safe_log->log("INFO :: Returning Sessions to the Main brand");
            return ['sessions' => $game_sessions, 'cursor' => $this->cursor];
        }

        return ! empty($game_sessions) ? $game_sessions : false;
    }

    /**
     * Generates the cursor (from which date time to generate the report).
     *
     * @param string $cursor
     * @return mixed|string|null
     */
    private function getCursorForKasinoSpil(string $cursor = '')
    {
        if (empty($cursor)) {
            $cursor = $this->safe_params->getCursor();

            if (empty($cursor)) {
                // this setting is used only when we initialize the reports for the first time.
                $cursor = $this->lic_settings['kasino_spil_start_datetime'];
            }
        }

        return $cursor;
    }

    /**
     * Create KasinoSpil report for evolution.
     *
     * @return bool $result
     */
    private function exportKasinoSpilFileForEvolution()
    {
        $result = false;

        $game_session = $this->custom_report_data;
        if (! empty($game_session)) {
            $this->toXML([$game_session], SafeConstants::KASINO_SPIL);

            $this->safe_params->setExtraInfo($this->custom_report_data);

            $result = $this->handleReport(
                SafeConstants::EVOLUTION_KASINO_SPIL,
                $game_session['start_time'],
                $game_session['end_time'],
                false
            );
        } else {
            $this->safe_log->log("INFO :: No game session for Evolution KasinoSpil");
        }

        return $result;
    }

    /**
     * Insert the custom/external end of day report into the DB. It will be added on the normal End of Day report later.
     *
     * @return mixed
     */
    private function exportEndOfDayForEvolution()
    {
        $this->safe_params->setExtraInfo($this->custom_report_data);

        return DataService::insertReport(
            $this->safe_params,
            SafeConstants::EVOLUTION_END_OF_DAY,
            '',
            '',
            $this->custom_report_data['date'],
            $this->custom_report_data['date']
        );
    }

    /**
     * Handles all actions.
     *
     * @param string $type
     * @param string $from
     * @param string $to
     * @param bool $set_cursor
     * @param array $game_session_result
     * @return bool|string
     */
    public function handleReport(
        string $type,
        string $from,
        string $to,
        bool $set_cursor = false,
        array $game_session_result = []
    ) {
        $result = false;

        // Gets transformed in the original type (KasinoSpil, EveryDay) if it is a custom type like KasinoSpil_Cancel
        $original_type = (SafeConstants::isKasinoSpil($type)) ? SafeConstants::KASINO_SPIL : $type;

        $output_file_path = $this->reportsFolderPath($original_type);

        $this->makeDirectory($output_file_path);

        $this->updatePreviousFile();

        $output_file_name = $output_file_path . $this->generateLatestXmlFilename();
        $this->saveOutput($output_file_name);

        if (file_exists($output_file_name)) {
            $filename_prefix = $this->SpilCertifikatIdentifikation . '-' . $this->safe_params->getTokenId();
            $report_inserted = DataService::insertReport(
                $this->safe_params,
                $type,
                $filename_prefix,
                $output_file_path,
                $from,
                $to,
                $game_session_result
            );

            if ($report_inserted) {
                $this->updateMAC($output_file_name);
                $result = $this->addFileIntoZip($original_type, $output_file_name, $this->generateLatestXmlFilename());

                if ($set_cursor) {
                    if (!empty($this->cursor)) {
                        $this->setCursor($this->cursor);
                    }
                    $this->setSecondaryCursor();
                }
            }
        }

        return $result;
    }

    /**
     * Create the directories if they dont exist.
     * @param string $output_file_path
     */
    public function makeDirectory(string $output_file_path)
    {
        if (! is_dir($output_file_path)) {
            $old_mask = umask(0);
            if (!mkdir($output_file_path, 0777, true) && !is_dir($output_file_path)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $output_file_path));
            }
            umask($old_mask);
        }
    }

    /**
     * Rename the previous file with the right sequence.
     * Removes the 'self::SAFE_LATEST_FILE_SUFFIX' on the filename and adds the sequence.
     *
     * @return bool
     */
    public function updatePreviousFile()
    {
        $token_id = $this->safe_params->getTokenId();
        $report = DataService::getLatestReportByUniqueId($token_id);

        if ($report) {
            $filepath = $report['file_path'];
            $filename = $report['filename_prefix'] . self::SAFE_LATEST_FILE_SUFFIX . ".xml";
            $new_file_name = $this->getLastXmlReportFilename($token_id, $report['sequence']);

            $full_path = $filepath . $new_file_name;

            $is_renamed = rename($filepath . $filename, $full_path);
            if ($is_renamed) {
                $type = (SafeConstants::isKasinoSpil($report['report_type'])) ? SafeConstants::KASINO_SPIL : $report['report_type'];

                $report_created_date = date('Y-m-d', strtotime($report['created_at']));
                $this->addFileIntoZip($type, $full_path, $new_file_name, true, $report_created_date);
            }

            return $is_renamed;
        }

        return false;
    }

    /**
     * Add xml file to the zip where all the report for a token are.
     *
     * @param string $type
     * @param string $file_path
     * @param string $filename
     * @param bool $delete_un_sequenced_file
     * @param string $old_report_created_at
     * @return bool
     */
    public function addFileIntoZip(string $type, string $file_path, string $filename, bool $delete_un_sequenced_file = false, string $old_report_created_at = '')
    {
        $tamper_token = $this->safe_params->getTokenId();
        $token_start_datetime = date('Y-m-d', strtotime($this->safe_params->getTokenStartDatetime()));

        $main_folder = $this->SpilCertifikatIdentifikation . "-" . $tamper_token;
        $current_datetime = date('Y-m-d');
        $file_path_in_zip = "$main_folder/$type/$current_datetime/$filename";

        $zip_file = $this->getZipFilename($token_start_datetime, $tamper_token);

        if ($delete_un_sequenced_file) {
            $old_filename = $this->generateLatestXmlFilename();
            $old_file_path = "$main_folder/$type/$old_report_created_at/$old_filename";
            ZipHandler::deleteFile($zip_file, $old_file_path);
        }

        $is_inserted = ZipHandler::insertFile($zip_file, $file_path, $file_path_in_zip);
        if (! $is_inserted) {
            $this->safe_log->log("File : {$file_path} could not be inserted into the zip file.");
        }

        return $is_inserted;
    }

    /**
     * Constructs the path and name of the zip file.
     *
     * @param $token_start_datetime
     * @param $tamper_token
     * @return string
     */
    private function getZipFilename($token_start_datetime, $tamper_token)
    {
        return "$this->export_path/{$token_start_datetime}/{$this->SpilCertifikatIdentifikation}-{$tamper_token}.zip";
    }

    /**
     * Method provides by by Danish Gambling Authority (in Java translated to PHP )
     * https://spillemyndigheden.dk/en/gambling-licensing-and-technical-requirements
     * file in  General technical configuration.zip
     *
     * @param $string
     * @return float|bool
     */
    public function parseHex($string)
    {
        try {
            $parsedInt = hexdec("0x" . $string);
            if ($parsedInt <= 127 && $parsedInt >= 0) {
            } else {
                if ($parsedInt > 127 && $parsedInt < 255) {
                    //https://en.wikipedia.org/wiki/Two%27s_complement
                    $new_value = (0xFFFFFF00 | $parsedInt);
                    $a = unpack(
                        's',
                        pack('v', $new_value)
                    );
                    $parsedInt = $a[1];
                }
            }
            return (int)$parsedInt;
        } catch (Exception $e) {
            phive('Logger')->error('safe_mac_failed', $e->getMessage());
            return false;
        }
    }


    /**
     * Method Parse String , method provides by Danish Gambling Authority (in Java, traslate to PHP )
     * https://spillemyndigheden.dk/en/gambling-licensing-and-technical-requirements
     * file in  General technical configuration.zip
     *
     * @param $key
     * @return array
     */
    public function parseString($key)
    {
        $parse = [];

        $key = str_replace("\\s", "", $key);
        $length = strlen($key) / 2;
        for ($i = 0; $i < $length; $i++) {
            $pair = substr($key, 2 * $i, 2);
            $parse[] = $this->parseHex($pair);
        }
        return $parse;
    }

    /**
     * update the params to create the mac and tamperToken
     */
    public function saveParams()
    {
        phive()->miscCache(self::SAFE_PARAM_KEY, $this->safe_params->exportJson(), true);
    }

    /**
     * Get params for the SAFE and TamperToken
     */
    public function extractParams()
    {
        $params = phive()->getMiscCache(self::SAFE_PARAM_KEY);
        $decoded_params = json_decode($params, true);
        if(is_null($decoded_params)){
            $this->safe_log->log("SAFE_PARAM_KEY :: {$params}");
            $this->safe_log->log("SAFE_PARAM_KEY :: Last json error: ".json_last_error_msg());
        }
        $this->safe_params = new SafeParams($decoded_params);
    }

    /**
     * get the secondary brand cursor
     * @return mixed
     */
    public function getSecondaryCursor(){

        $params = phive()->getMiscCache(self::SAFE_CURSOR_SECONDARY);
        return json_decode($params, true)[0];
    }



    /**
     * Update the start max to a new max
     *
     * @param $output_file_name
     */
    public function updateMAC($output_file_name)
    {
        $new_mac = $this->getMac($this->safe_params->getTokenStartMac(), $output_file_name);
        phive('Logger')->error("safe_mac_update-{$this->safe_params->getTokenId()}", "updating mac, old {$this->safe_params->getTokenStartMac()} , new mac $new_mac");
        $current_sequence = $this->safe_params->getSequence();
        $this->safe_params->setSequence($current_sequence+1);
        $this->safe_params->setTokenStartMac($new_mac);
        $this->saveParams();
    }

    /**
     * Get Mac
     * @param $key -> provide by Gambling Authorit in its webservice
     *  * https://spillemyndigheden.dk/en/gambling-licensing-and-technical-requirements
     * file in  General technical configuration.zip
     * @param $file -> file generate
     * @return bool|string
     */
    public function getMac($key, $file)
    {
        $toSrting = $this->parseString($key);
        $doubleKey = implode(array_map("chr", $toSrting));
        $mac = hash_hmac_file('sha256', $file, $doubleKey);
        return $mac;
    }

    /**
     * Log into log files and misc_cache before the request started to make sure the reports dont get stuck because of timeout.
     * @param $xml_content
     * @param $request_type
     * @param $old_tokens_path
     */
    private function beforeSendingRequest($xml_content, $request_type, $old_tokens_path)
    {
        $this->safe_log->log("TOKEN-INFO :: Sending token requested. XML request: {$xml_content}");
        phive('SQL')->delete('misc_cache', ['id_str' => self::SAFE_SENDING_TOKEN_REQUEST_KEY]);
        $value = json_encode(['xml' => $xml_content, 'type' => $request_type, 'old_tokens_path' => $old_tokens_path]);
        phive()->miscCache(self::SAFE_SENDING_TOKEN_REQUEST_KEY, $value, false);
    }

    /**
     * Remove the key in misc_cache and log to let us know we didnt have a timeout request.
     * @param string $res
     */
    private function afterReceivingTheRequest($res)
    {
        phive('SQL')->delete('misc_cache', ['id_str' => self::SAFE_SENDING_TOKEN_REQUEST_KEY]);
        $this->safe_log->log("TOKEN-INFO :: Token response received. Token result: {$res} \n\n");
    }

    /**
     * @return array
     */
    private function getHeaders()
    {
        $username = $this->lic_settings['rofus_username'];
        $password = $this->lic_settings['rofus_password'];
        return ['Content-Type:application/json', 'Authorization: Basic ' . base64_encode("$username:$password")];
    }

    /**
     * Put new reports on pending, to be processed later.
     *
     * @param string $type
     * @param array $report_data
     */
    public function addPendingReports(string $type, array $report_data)
    {
        $reports = phive()->getMiscCache($type);
        $update = false;
        if (! empty($reports)) {
            $update = true;
        }

        $reports = json_decode($reports);
        $reports[] = (object) $report_data;
        $reports = json_encode($reports);
        phive()->miscCache($type, $reports, $update);
    }

    /**
     * Finish the pending cancellation.
     *
     * @param string $type
     * @return bool
     */
    public function generatePendingReports(string $type)
    {
        $reports = phive()->getMiscCache($type);
        $reports = json_decode($reports, true);
        phive('SQL')->delete('misc_cache', ['id_str' => $type]);

        if (empty($reports)) {
            return false;
        }

        foreach ($reports as $report_data) {
            // Cancel request if type is CANCEL
            if ($type === self::PENDING_CANCEL_KEY) {
                $this->cancelReportSession($report_data['game'], $report_data['user_id'], $report_data['table'], $report_data['amount']);
            } else {
                // Add to pending if the report could not be done
                if ($this->checkRunningReport($type, false)) {
                    $report_data = ['end_of_day_data' => $report_data];
                    $this->addPendingReports($type, $report_data);
                    return true;
                }

                $this->custom_report_data = $report_data;
                return $this->exportData($type, $this->iso);
            }
        }

        return true;
    }

    /**
     * Generating the custom end of day reports.
     *
     * @param $end_of_day_data
     * @param $report_type
     * @return bool
     */
    public function customEndOfDayReports($end_of_day_data, $report_type)
    {
        if ($this->checkRunningReport($report_type, false)) {
            $report_data = ['end_of_day_data' => $end_of_day_data];
            $this->addPendingReports($report_type, $report_data);
            return true;
        }

        $this->custom_report_data = $end_of_day_data;
        $this->custom_report_data['report_type'] = $report_type;

        $result = $this->exportData($report_type, $this->iso, '');

        return ($result) ? true : false;
    }

    /**
     * Generate custom end of day reports from, requested by third parties.
     *
     * @param $game_session
     * @param $report_type
     * @return bool
     * @throws Exception
     */
    public function generateCustomKasinoSpilReport($game_session, $report_type)
    {
        $this->cronCloseTamperToken();

        $this->custom_report_data = $game_session;
        $this->custom_report_data['report_type'] = $report_type;
        $this->custom_report_data['currency'] = lic('getForcedCurrency', [], null, null, 'DK');

        if ($this->checkRunningReport($report_type, false)) {
            $this->addPendingReports($report_type, $this->custom_report_data);
            return true;
        }

        $result = $this->exportData(SafeConstants::EVOLUTION_KASINO_SPIL, $this->iso);

        return ($result) ? true : false;
    }

    /**
     * Handle if it should calculate rollbacks on .
     *
     * @param string $calculate_rollbacks none|
     */
    public function shouldCalculateRollbacks($calculate_rollbacks = 'all')
    {
        switch ($calculate_rollbacks) {
            case 'all':
                $this->should_calculate_rollback_endofday = true;
                $this->should_calculate_rollback_kasinospil = true;
                break;
            case 'none':
                $this->should_calculate_rollback_endofday = false;
                $this->should_calculate_rollback_kasinospil = false;
                break;
            case SafeConstants::END_OF_DAY:
                $this->should_calculate_rollback_endofday = true;
                break;
            case SafeConstants::KASINO_SPIL:
                $this->should_calculate_rollback_kasinospil = true;
                break;
        }
    }


    /**
     * Get the data request for the main brand
     *
     * @param $country
     * @param $date
     * @param $type
     * @param $cursor_regenerate
     * @param $secondary_cursor
     * @param $forced_values
     * @return array|mixed
     */
    public function getDataReportFromSecondaryBrand($country, $date, $type, $cursor_regenerate, $secondary_cursor, $forced_values)
    {
        $params = ['cursor' => $secondary_cursor];
        $this->safe_params = new SafeParams($params);

        switch ($type) {
            case SafeConstants::KASINO_SPIL:
                $result = $this->kasinoSpilData($country, $date, $cursor_regenerate);
                break;
            case SafeConstants::END_OF_DAY:
                $result = $this->endOfDayData($country, $date, $forced_values);
                break;
        }
        return $result;
    }
}
