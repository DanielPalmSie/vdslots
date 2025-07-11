<?php

trait CancelSessionFromXML
{

    private $tamper_token;
    private $tmp_token_folder;
    private $start_date;
    private $end_date;
    private $game_sessions;
    private $spilHjemmeside_cancel;
    private $cancel_all_sessions;

    /**
     * This function will copy to a tmp folder all the unique "tamper token folder" from the original export path
     * Then it extract all the XML from the ZIP and process all the existing files reported on the DB table for the requested date
     * Finally read all the sessions from the XML and generate a cancel sessions XML for each file.
     *
     * @param $date
     */
    private function cancelReportByDate($date)
    {
        $token_filename_prefixes = array_unique(array_column($this->tamper_token, 'filename_prefix'));
        foreach ($token_filename_prefixes as $key => $zip_file) {
            $this->copyTotmpFolder($this->tamper_token[$key]);
            $files[] = $this->searchInsubFolders("$zip_file.zip");
        }
        $this->openZip($files);
        foreach ($this->tamper_token as $sessions_in_file) {
            $xml_file = $this->searchFile($sessions_in_file);
            if (empty($xml_file)) {
                echo "ERROR - Couldn't find XML for: " . json_encode($sessions_in_file) . chr(10);
                continue;
            }
            if (!$this->setDate($sessions_in_file, $date)) {
                continue;
            }
            if ($this->parseXml($xml_file, $date)) {
                $this->saveXmlCancel();
            }
        }
    }

    /**
     * Copy the folder to temporal_folder (from export_data to /tmp)
     *
     * @param $file_data
     */
    private function copyTotmpFolder($file_data)
    {
        $pos = strpos($file_data['file_path'], $file_data['filename_prefix']);
        if ($pos === false) {
            echo "ERROR - Filename '{$file_data['filename_prefix']}' not found in path '{$file_data['file_path']}' " . chr(10);
        }
        $folder = substr($file_data['file_path'], 0, ($pos - 1));
        shell_exec("cp -r $folder $this->tmp_token_folder");
        $folder_name = basename($folder);
        if (!is_dir("$this->tmp_token_folder/$folder_name")) {
            echo " ERROR - The folder $folder was not copied" . chr(10);
        }
    }


    /**
     * Saving the report in db
     *
     * @param $output_file_name
     */
    public function saveReport($output_file_name, $reportType = SafeConstants::KASINO_SPIL_CANCEL)
    {
        $filename_prefix = $this->SpilCertifikatIdentifikation . '-' . $this->safe_params->getTokenId();
        DataService::insertReport(
            $this->safe_params,
            $reportType,
            $filename_prefix,
            $output_file_name,
            $this->start_date,
            $this->end_date,
            $this->game_sessions
        );
    }

    /**
     * Add xml file to the zip where all the report for a token are.
     */
    public function saveXmlCancel($reportType = SafeConstants::KASINO_SPIL_CANCEL)
    {
        $output_file_path = $this->reportsFolderPath(SafeConstants::KASINO_SPIL);
        $this->makeDirectory($output_file_path);
        $this->updatePreviousFile();
        $output_file_name = $output_file_path . $this->generateLatestXmlFilename();
        $this->saveOutput($output_file_name);
        $this->saveReport($output_file_path, $reportType);
        $this->updateMAC($output_file_name);
        $this->addFileIntoZip(SafeConstants::KASINO_SPIL, $output_file_name, $this->generateLatestXmlFilename());
    }

    /**
     * Unzip the files to looking for the files
     *
     * @param $files
     * @return mixed
     */
    private function openZip($files)
    {
        foreach ($files as $file) {
            $zip = new ZipArchive();
            $is_zip_open = $zip->open($file);
            if ($is_zip_open === true) {
                $zip->extractTo($this->tmp_token_folder);
                $zip->close();
            } else {
                echo "ERROR - Zip $file could not be opened" . chr(10);
            };
        }
    }

    /**
     * Search for the XML file in the subfolders
     *
     * @param $xml_file - path to the file
     * @return mixed
     */
    private function searchFile($xml_file)
    {
        return $this->searchInsubFolders("{$xml_file['filename_prefix']}-{$xml_file['sequence']}.xml") ?? $this->searchInsubFolders("{$xml_file['filename_prefix']}-E.xml");
    }

    /**
     * Search for a file in subfolders, return first occurrence
     *
     * @param $filename
     * @return mixed
     */
    public function searchInsubFolders($filename)
    {
        return glob("$this->tmp_token_folder/{,*/,*/*/,*/*/*/}$filename", GLOB_BRACE)[0];
    }

    /**
     * Parse the existing XML and prepare a cloned XML marking all the session as cancelled
     * "SpilAnnullering = 1"
     *
     * @param $xmlFile
     * @param $date
     * @return bool
     */
    private function parseXml($xmlFile, $date, $reportType = SafeConstants::KASINO_SPIL_CANCEL)
    {
        if (!phive()->validateDate($date)) {
            echo "ERROR - Invalid date" . chr(10);
            return false;
        }

        $SpilAnnullering = 1;
        if ($reportType === SafeConstants::KASINO_SPIL) {
            $SpilAnnullering = 0;
        }

        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->load($xmlFile);

        $rootElement = $dom->documentElement;
        $rootElement->getElementsByTagName("SpilFilIdentifikation")
            ->item(0)->nodeValue = $this->generateUniqueID();

        $xml_export = false;
        $sessionsToRemove = [];
        foreach ($rootElement->getElementsByTagName("KasinospilSession") as $session) {
            if (
                $this->spilHjemmeside_cancel !== 'all' &&
                $this->spilHjemmeside_cancel !== $session->SpilSted->SpilHjemmeside
            ) {
                $sessionsToRemove[] = $session;
                continue;
            }

            $SpilAnnulleringDatoTid = $session->getElementsByTagName("SpilFaktiskSlutDatoTid")->item(0)->nodeValue;
            if ($reportType === SafeConstants::KASINO_SPIL) {
                $SpilAnnulleringDatoTid = $session->getElementsByTagName("SpilAnnulleringDatoTid")->item(0)->nodeValue;
            }

            $spilAnnulleringElement = $session->getElementsByTagName("SpilAnnullering")->item(0);
            $endDate = date('Y-m-d', strtotime($session->getElementsByTagName("SpilFaktiskSlutDatoTid")
                ->item(0)->nodeValue));

            if ($this->cancel_all_sessions === true || $endDate === $date) {
                $xml_export = true;
                $spilAnnulleringElement->getElementsByTagName("SpilAnnullering")->item(0)->nodeValue = $SpilAnnullering;
                /**
                 * DGA require this date to be the same of the day we are cancelling,
                 * so we use session closing time.
                 */
                $spilAnnulleringElement
                    ->getElementsByTagName("SpilAnnulleringDatoTid")->item(0)->nodeValue = $SpilAnnulleringDatoTid;
                continue;
            }

            $sessionsToRemove[] = $session;
        }

        foreach ($sessionsToRemove as $session) {
            $session->parentNode->removeChild($session);
        }

        $this->getSessionforReport($rootElement->getElementsByTagName("KasinospilSession"));

        $dom->formatOutput = true;
        $this->output = $dom;
        return $xml_export;
    }

    /**
     * Sum all the sessions from the XML data to be able to properly log them in the report table
     *
     * @param $sessions
     */
    private function getSessionforReport($sessions)
    {
        $game_sessions = [];
        foreach ($sessions as $session) {
            if ($session->getElementsByTagName("SpilHjemmeside")
                    ->item(0)->nodeValue === $this->spilHjemmeside_cancel || $this->spilHjemmeside_cancel == 'all') {
                $game_sessions[] = [
                    'win_amount' => $session->getElementsByTagName('KasinospilGevinstSpil')->item(0)->nodeValue,
                    'bet_amount' => $session->getElementsByTagName('KasinospilIndskudSpil')->item(0)->nodeValue,
                    'bet_cnt' => $session->getElementsByTagName('KasinospilAntalTræk')->item(0)->nodeValue,
                ];
            }
        }

        $this->game_sessions = $this->calculateSumOfGameSession($game_sessions);
    }

    /**
     * Set the dates for saving the report in DB
     *
     * @param $sessions
     * @param $date
     * @return bool
     */
    private function setDate($sessions, $date): bool
    {
        if (!phive()->validateDate($date)) {
            echo "ERROR - Invalid date" . chr(10);
            return false;
        }
        $this->start_date = $sessions['report_data_from'];
        $this->end_date = $sessions['report_data_to'];

        if ($this->cancel_all_sessions === true) {
            return true;
        }

        $startDate = date('Y-m-d', strtotime($sessions['report_data_from']));
        $endDate = date('Y-m-d', strtotime($sessions['report_data_to']));

        if ($date !== $startDate) {
            $this->start_date = "$date 00:00:00";
        }

        if ($date !== $endDate) {
            $this->end_date = "$date 23:59:59";
        }

        return true;
    }

    /**
     * Cancel all xml inside a zip by dates or token
     *
     * @param $star_date
     * @param $end_date
     * @param string $folder
     * @param string $spilHjemmeside
     * @param array $force_token
     * @param array $force_files
     * @return bool
     */
    public function cancelXmlByDate($star_date, $end_date, $folder = '/tmp', $spilHjemmeside = 'all', $force_token = [], $force_files = [], $cancel_all_sessions = false)
    {
        $this->extractParams();
        $is_token_closed = $this->closeTamperToken();
        if ($is_token_closed === false) {
            echo "There is another report running.\n";
            return false;
        }
        if ($this->checkRunningReport(SAFE::CANCEL_RUNNING_REPORT)) {
            return false;
        }
        $this->tmp_token_folder = $folder;
        $this->spilHjemmeside_cancel = $spilHjemmeside;
        $this->cancel_all_sessions = $cancel_all_sessions;
        $current = strtotime($star_date);
        $last = strtotime($end_date);
        while ($current <= $last) {
            $date = date('Y-m-d', $current);
            echo "Processing $date...\n";
            echo "Token: ".$this->safe_params->getTokenId()."\n";
            $this->tamper_token = empty($force_files) ? $this->getLastTokensVersionByDate($date, $force_token) : $force_files;
            $this->cancelReportByDate($date);
            echo "$date cancelled\n";
            if (!empty($force_token)) {
                break;
            }
            $current = strtotime('+1 day', $current);
        }
        $this->removeReportRunning();
        $result = $this->closeTamperToken();
        if ($result === false) {
            echo "ERROR - token could not be closed correctly, something went wrong!\n";
        }

        echo "DONE - report between $star_date and $end_date are now cancelled.\n" .
            "You can now proceed with data regeneration for the same period\n";
    }

    /**
     * Return the last batch of tokens generated.
     * On first cancellation will return the whole sequence of files to be processed: N KasinoSpil (1 file every 5min)
     * After that, as we run the regeneration, will return: 1 KasinoSpil (1 file with all the sessions)
     *
     *
     * when the main function cancelXmlByDate with a tamperToken not empty(4th parameter),
     * The system gets the information by TamperToken(unique_id) in external_regulatory_reports_logs
     * if the tamperToken is empty , getting the information by date
     * @param $date
     * @param array $tamperToken
     * @return mixed
     */
    public function getLastTokensVersionByDate($date, $tamperToken = [])
    {
        if (!empty($tamperToken)) {
            return $this->getDataByToken($tamperToken, $date);
        }
        $tokens = $this->getTokensFromRegulatoryReportLogs('regeneration', $date);
        if (empty($tokens)) {
            $tokens = $this->getTokensFromRegulatoryReportLogs('original', $date);
        }
        return $tokens;
    }

        /**
         * Get information (location, period, etc) about the XML that we need to cancel.
         * On first cancellation/regeneration we fetch all the single XMLs (report_data_to BETWEEN 00:00:00 AND 23:59:59)
         * After that we fetch the last regeneration (report_data_from = $date 00:00:00 AND report_data_to = $date(+1 day) 00:00:00
         *
         * Beware of the subtle changes on the dates, those are mandatory to properly process the data.
         *
         * @param $report
         * @param $date
         * @return mixed
         */
        public function getTokensFromRegulatoryReportLogs($report, $date)
        {
            $report_type = SafeConstants::KASINO_SPIL;
            /**
             * We need to cover cases where the last sessions of $date are being reported on the next day
             * Ex: Kasinospil report generated on 2023-02-18 with report_data_from 2023-02-17 23:55:29
             * and report_data_to 2023-02-18 00:08:46
             */
            $nextDate = date('Y-m-d', strtotime($date .' +1 day'));
            $nextDateSQL = " OR (
                report_data_from BETWEEN '$date 00:00:00' AND '$date 23:59:59'
                AND report_data_to like '{$nextDate}%'
            )";

            $whereSQL = "(report_data_to BETWEEN '$date 00:00:00' AND '$date 23:59:59' $nextDateSQL)";

            $order_limit_sql = "";
            if ($report == 'regeneration') {
                $regeneration_from = "$date 00:00:00";
                /**
                 * Regenerated report before 2021-03-26 were including 00:00:00 of next day in the report, but we need to exclude that instead.
                 * To keep consistency on fetching info from the DB for older report, in case we end up regenerating them again
                 * we need to still look for the old way of storing data in the DB.
                 */
                $cutoff_date_for_wrong_ending_period = '2021-03-26';
                $regeneration_to = $date < $cutoff_date_for_wrong_ending_period ? phive()->hisMod("+1 day", $date, 'Y-m-d 00:00:00') : "$date 23:59:59";
                $whereSQL = "report_data_from = '$regeneration_from' AND report_data_to = '$regeneration_to'";
                $order_limit_sql = "ORDER BY id DESC LIMIT 1";
            }
            $sql = "
                SELECT filename_prefix, sequence, file_path, report_data_from, report_data_to
                FROM external_regulatory_report_logs
                WHERE report_type = '$report_type'
                  AND $whereSQL
                GROUP BY filename_prefix, sequence
                $order_limit_sql
            ";

            return phive('SQL')->loadArray($sql);
        }

        /**
         * Get all information by token,
         * in the function getTokensFromRegulatoryReportLogs,we get the information by date,
         * and in this function we can get the information by token and by date
         *
         * @param array $tamperToken
         * @param string $date
         * @return mixed
         */
        public function getDataByToken($tamperToken, $date)
        {
            $tokens = implode(',', $tamperToken);
            $report_type = SafeConstants::KASINO_SPIL;

            /**
             * We need to cover cases where the last sessions of $date are being reported on the next day
             * Ex: Kasinospil report generated on 2023-02-18 with report_data_from 2023-02-17 23:55:29
             * and report_data_to 2023-02-18 00:08:46
             */
            $nextDate = date('Y-m-d', strtotime($date .' +1 day'));
            $nextDateSQL = "(report_data_from BETWEEN '$date 00:00:00' AND '$date 23:59:59'
                AND report_data_to like '{$nextDate}%')";

            $sql = "
            SELECT filename_prefix, sequence, file_path, report_data_from, report_data_to
            FROM external_regulatory_report_logs
            WHERE report_type = '{$report_type}'
              AND unique_id IN ({$tokens})
              AND (
                      report_data_to BETWEEN '$date 00:00:00' AND '$date 23:59:59'
                      OR $nextDateSQL
                  )
            GROUP BY filename_prefix, sequence";

            return phive('SQL')->loadArray($sql);
        }

        /**
         * Cancel duplicate KasinoSpil_Cancel files
         * The related KasinoSpil files are regenerated here to cancel the duplicate KasinoSpil_Cancel files
         *
         * @param string $token The token of the related KasinoSpil report
         * @param int $sequence The sequence of the related KasinoSpil report
         * @param string $date The date (report_data_to) of the related KasinoSpil report
         * @param int $duplicateCount The number of duplicates that should be cancelled
         * @return void
         */
        public function cancelDuplicateKasinoSpilCancelReport($token, $sequence, $date, $duplicateCount): void
        {
            $report = Phive('SQL')->loadArray("
                    SELECT filename_prefix, sequence, file_path, report_data_from, report_data_to
                    FROM external_regulatory_report_logs
                    WHERE unique_id = {$token}
                    AND sequence = {$sequence}
                    LIMIT 1
                ");

            if (!$report) {
                echo "ERROR - No file found with token {$token} and sequence {$sequence} \n";
                return;
            }

            $this->extractParams();
            $is_token_closed = $this->closeTamperToken();
            if ($is_token_closed === false) {
                echo "There is another report running.\n";
                return;
            }
            if ($this->checkRunningReport(SAFE::CANCEL_RUNNING_REPORT)) {
                return;
            }

            $kasinoSpilReport = $report[0];
            $this->tmp_token_folder = '/tmp';
            $this->spilHjemmeside_cancel = 'all';
            $this->cancel_all_sessions = true;
            echo "Processing $date...\n";
            echo "Token: ".$this->safe_params->getTokenId()."\n";

            $this->copyTotmpFolder($kasinoSpilReport);
            $files[] = $this->searchInsubFolders("{$kasinoSpilReport['filename_prefix']}.zip");
            $this->openZip($files);
            $xmlFile = $this->searchFile($kasinoSpilReport);
            if (empty($xmlFile)) {
                echo "ERROR - Couldn't find XML for: " . json_encode($kasinoSpilReport) . chr(10);
                return;
            }
            $this->setDate($kasinoSpilReport, $date);

            $reportType = SafeConstants::KASINO_SPIL;
            foreach(range(1, $duplicateCount) as $number) {
                if ($this->parseXml($xmlFile, $date, $reportType)) {
                    $this->saveXmlCancel($reportType);
                    echo "Generated duplicate KasinoSpil #{$number} \n";
                }
            }

            $this->removeReportRunning();
            $result = $this->closeTamperToken();
            if ($result === false) {
                echo "ERROR - token could not be closed correctly, something went wrong!\n";
            }

            echo "DONE \n";
        }
    }
