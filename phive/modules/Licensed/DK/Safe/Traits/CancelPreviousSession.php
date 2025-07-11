<?php


trait CancelPreviousSession
{
    /**
     * Cancel previous session. (Done after we have rollback etc).
     *
     * @param $user_game_session_id
     * @param $user_id
     * @param $table
     * @param $amount
     * @param array $session
     * @return bool
     */
    public function cancelReportSession($user_game_session_id, $user_id, $table, $amount, $session = [])
    {
        try {
            if ($this->checkRunningReport(SAFE::CANCEL_RUNNING_REPORT)) {
                $report_data = ['user_game_session_id' => $user_game_session_id, 'user_id' => $user_id, 'table' => $table, 'amount' => $amount , 'session' => $session];
                $this->addPendingReports(SAFE::PENDING_CANCEL_KEY, $report_data);
                return false;
            }
            $this->cancelSession($user_game_session_id, $user_id, $table, $amount, $session);
            sleep(1);
            $this->pendingCancellations();

        } catch (Exception $e) {
            phive()->dumpTbl('safe_report_cancel-report-failed', json_encode([$user_game_session_id, $user_id, $table, $session]));
            $this->removeReportRunning();
        }
    }

    /**
     * Cancel the report by creating 2 xml files one for the cancel and another one for the bets that are not canceled.
     *
     * @param $user_game_session_id - game session id, this is used only on "safe_main_brand === true" when $session is empty
     * @param $user_id
     * @param $table - bets/wins used to determine which info we need to amend.
     * @param $amount
     * @param $session - Full session data provided from remote brand
     */
    public function cancelSession($user_game_session_id, $user_id, $table, $amount, $session)
    {
        if (!$session) {
            $session = DataService::getCancelSession($user_id, $user_game_session_id);
        }

        if (!empty($session) && phive()->getZeroDate() !== $session['end_time']) {
            if (phive('Distributed')->getSetting('safe_main_brand') !== true) {
                $session['id'] = $this->lic_settings['SpilHjemmeside'] . "_" . $session['id'];
                //send Cancellation to the main Brand
                $machine = phive('Distributed')->getSetting('safe_primary');
                $cancel = dist($machine, 'Distributed', 'cancelSessionReportFromSecondaryBrand', [$user_game_session_id, $user_id, $table, $amount, $session, $this->iso]);
                if (!$cancel['success']) {
                    $this->safe_log->log('ERROR::Cancel request is not sent to main brand' . json_encode($session));
                }
            } else {
                // cancel the previous report by id...
                $this->submitCancelSession($session, true, $table, $amount, $user_game_session_id, $user_id);
                //waiting to generate the previous report
                sleep(2);
                $session['id'] = $session['id'] . uniqid();
                $key = strpos($table, 'bets') === false ? 'win_amount' : 'bet_amount';
                $session[$key] -= abs($amount);
                $this->submitCancelSession($session, false);
            }
        }
        $this->removeReportRunning();
    }

    /**
     * Cancel session.
     *
     * @param $session
     * @param string $table
     * @param $amount
     * @param bool $cancel
     * @param string $user_game_session_id
     * @param string $user_id
     */
    protected function submitCancelSession(
        $session,
        $cancel = true,
        $table = '',
        $amount = 0,
        $user_game_session_id = '',
        $user_id = ''
    ) {
        $this->safe_log->log('INFO :: START++++++++'.($cancel === true ? '' : ' Regenerate after').' Cancel SESSION');
        $this->extractParams();

        if ($cancel) {
            // Save rollback info on SafeParams to use them on EveryDayReports and while Regenerating Data
            $this->safe_params->setIsRollback(true);
            $this->safe_params->setRollbackAmount($amount);
            $this->safe_params->setRollbackType($table);
            $this->safe_params->setUserGameSessionid($user_game_session_id);
            $this->safe_params->setUserId($user_id);
        }

        $this->toXML([$session], SafeConstants::KASINO_SPIL, $cancel);
        $output_file_path = $this->reportsFolderPath(SafeConstants::KASINO_SPIL);

        $this->makeDirectory($output_file_path);

        $output_file_name = $output_file_path . $this->generateLatestXmlFilename();
        $this->safe_log->log('INFO :: Sending the file ' . $output_file_name);

        $this->updatePreviousFile();

        $this->saveOutput($output_file_name);

        if (file_exists($output_file_name)) {
            $type = $cancel ? SafeConstants::KASINO_SPIL_CANCEL : SafeConstants::KASINO_SPIL_AFTER_CANCEL;

            $filename_prefix = $this->SpilCertifikatIdentifikation . '-' . $this->safe_params->getTokenId();
            $report_inserted = DataService::insertReport(
                $this->safe_params,
                $type,
                $filename_prefix,
                $output_file_path,
                $session['start_time'],
                $session['end_time']
            );

            if ($report_inserted) {
                $this->updateMAC($output_file_name);
                $this->addFileIntoZip(SafeConstants::KASINO_SPIL, $output_file_name, $this->generateLatestXmlFilename());
            }
        }

        $this->safe_log->log('INFO :: END++++++++'.($cancel === true ? '' : ' Regenerate after').' Cancel SESSION');
    }

    /**
     * Finish the pending cancellation.
     *
     * @return bool
     */
    public function pendingCancellations()
    {
        $reports = phive()->getMiscCache(SAFE::PENDING_CANCEL_KEY);
        $reports = json_decode($reports, true);
        phive('SQL')->delete('misc_cache', ['id_str' => SAFE::PENDING_CANCEL_KEY]);

        if (empty($reports)) {
            return false;
        }

        foreach ($reports as $report) {
            $this->cancelReportSession($report['user_game_session_id'], $report['user_id'], $report['table'], $report['amount']);
        }

        return true;
    }

    /**
     * Closing unused tokens.
     *
     * @param $token_id
     */
    public function closedUnusedTokenByID($token_id)
    {
        $message = "Unused token {$token_id} is being closed.\n";
        echo $message;
        $this->safe_log->log($message);

        $xml_request = $this->getUnusedTokensXml($token_id);
        $this->closedUnusedToken($xml_request);
    }

    /**
     * Get xml to do the API call for closing unused token.
     *
     * @param $token_id
     * @return string
     */
    private function getUnusedTokensXml($token_id)
    {
        $this->finishTimeoutRequest();

        $uuid = $this->generateUniqueID();

        return SafeXmlGenerator::generateCloseTokenXML(
            $uuid,
            $token_id,
            'empty',
            $this->SpilCertifikatIdentifikation
        );
    }

    /**
     * Make request to close the unused token.
     *
     * @param $xml_request
     */
    public function closedUnusedToken($xml_request)
    {
        if ($this->lic_settings['use_random_test_tampertoken'] === true) {
            return;
        }
        $xml_response = $this->sendToExternalService(SafeConstants::TOKEN_REQUEST_URL_CONFIG_NAME, $xml_request, SafeConstants::UNUSED_TOKEN_KEY);
        $response = get_object_vars($xml_response->S_Body->TamperTokenAnvend_O->Kontekst->HovedOplysningerSvar->SvarReaktion);

        $this->closeTokenResponse($response);
    }
}
