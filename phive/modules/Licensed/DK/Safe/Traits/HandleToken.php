<?php

trait HandleToken
{
    /**
     * Will close the tamper token. If there is a report running it will wait until the report is finished to close the
     * token.
     *
     * @param bool $generate_new_token
     * @return bool
     */
    public function closeTamperToken(bool $generate_new_token = true)
    {
        if ($this->checkRunningReport(self::TAMPER_TOKEN_RUNNING_REPORT)) {
            $this->finishTimeoutRequest();

            if (! $this->checkAndWait()) {
                $this->safe_log->log("INFO :: Tamper token could not be closed because of running report.");
                return false;
            }
        }

        // temp files
        phive()->dumpTbl('safe_tamper-token_closed', 'closing the tamper token ' . $this->safe_params->exportJson());
        try {
            $this->makeCloseTamperTokenRequest($generate_new_token);
            $this->removeReportRunning();
        } catch (Exception $exception) {
            $this->safe_log->log("ERROR :: Tamper token could not be closed. " . $exception->getMessage());
            $this->removeReportRunning();
        }

        return true;
    }

    /**
     * Finish the TOKEN request that got stuck.
     * @param bool $generate_new_token
     */
    public function finishTimeoutRequest($generate_new_token = false)
    {
        $timeout_request = phive()->getMiscCache(self::SAFE_SENDING_TOKEN_REQUEST_KEY);
        if ($timeout_request) {
            $timeout_request = json_decode($timeout_request);
            $this->safe_log->log("INFO :: Re-requesting timeout token request on type . " . $timeout_request->type);

            if ($timeout_request->type === SafeConstants::CLOSE_TOKEN_KEY) {
                try {
                    $this->requestToCloseToken($timeout_request->xml, $timeout_request->old_tokens_path);
                } catch (Exception $exception) {
                    $this->safe_log->log("ERROR :: Tamper token could not be closed. " . $exception->getMessage());
                }
            } elseif ($timeout_request->type === SafeConstants::NEW_TOKEN_KEY && $generate_new_token) {
                try {
                    $this->makeCloseTamperTokenRequest($generate_new_token);
                } catch (Exception $exception) {
                    $this->safe_log->log("ERROR :: Tamper token could not be closed. " . $exception->getMessage());
                }
            } elseif ($timeout_request->type === SafeConstants::UNUSED_TOKEN_KEY) {
                $this->closedUnusedToken($timeout_request->xml);
            }

            if ($timeout_request->type !== SafeConstants::UNUSED_TOKEN_KEY) {
                $this->removeReportRunning();
            }
        }
    }

    /**
     * Make the request to close the tamper token.
     *
     * @param bool $generate_new_token
     */
    public function makeCloseTamperTokenRequest(bool $generate_new_token = true)
    {
        if ($this->safe_params->getSequence() == 1) {
            $this->safe_params->setTokenStartMac('empty');
        }

        $xml_content = SafeXmlGenerator::generateCloseTokenXML(
            $this->safe_params->getTransaktionsId(),
            $this->safe_params->getTokenId(),
            $this->safe_params->getTokenStartMac(),
            $this->SpilCertifikatIdentifikation
        );

        $old_tokens_path = $this->pathToMainTokensFolder();

        if ($generate_new_token) {
            // open an  new token before close the old;
            $this->safe_log->log("TOKEN-INFO :: Requesting to open a new token.");
            $new_token_created = $this->getTamperToken();
        } else {
            $new_token_created = true;
        }

        if ($new_token_created) {
            $this->requestToCloseToken($xml_content, $old_tokens_path);
        }
    }

    /**
     * @param $xml_content
     * @param $old_tokens_path
     */
    private function requestToCloseToken($xml_content, $old_tokens_path)
    {
        $this->safe_log->log("TOKEN-INFO :: Requesting to close the token.");

        $tokenId = $this->getTokenFromFolderPath($old_tokens_path);

        if ($this->isTestTamperToken($tokenId)) {
            $this->removeTokenFolders($old_tokens_path);
            return;
        }

        $xml_obj = $this->sendToExternalService(SafeConstants::TOKEN_REQUEST_URL_CONFIG_NAME,
            $xml_content, SafeConstants::CLOSE_TOKEN_KEY, $old_tokens_path);
        $response = get_object_vars($xml_obj->S_Body->TamperTokenAnvend_O->Kontekst
            ->HovedOplysningerSvar->SvarReaktion);
        // remove the xml folder
        $this->removeTokenFolders($old_tokens_path);

        $this->closeTokenResponse($response);
    }

    /**
     * Check if the given token is a test tamper token
     * @param $tokenId
     */
    private function isTestTamperToken($tokenId)
    {
        $sql = "
                SELECT log_info
                FROM
                    external_regulatory_report_logs
                WHERE
                    unique_id = '{$tokenId}'
                    AND report_type = '". SafeConstants::NEW_TOKEN."'
                LIMIT 1
                ";

        $tamperToken = phive('SQL')->loadAssoc($sql);
        $info = json_decode($tamperToken['log_info']);

        return $info->ServiceID === SafeConstants::TEST_TAMPER_TOKEN_SERVICE;
    }

    /**
     * Extract token from the token folder path string
     * @param $token_path
     */
    private function getTokenFromFolderPath($token_path)
    {
        return explode('-', array_reverse(array_filter(explode('/', $token_path)))[0])[1];
    }

    public function doesTokenHaveReports($tokenId)
    {
        $where_in = phive('SQL')->makeIn(SafeConstants::NO_FILE_REPORTS);

        $sql = "
                SELECT COUNT(id) as report_count
                FROM
                    external_regulatory_report_logs
                WHERE
                    unique_id = '{$tokenId}'
                    AND report_type NOT IN ({$where_in})
                ";

        $reportCount = phive('SQL')->loadAssoc($sql);

        return (int) $reportCount['report_count'] > 0;
    }

    /**
     * Handle the close token response.
     * @param $response
     */
    public function closeTokenResponse($response)
    {
        if (isset($response['Fejl'])) {
            phive()->dumpTbl('safe_tamper-token_res-error', json_encode($response));
            $this->safe_log->log("Token could not be closed: " . json_encode($response));
        } else {
            if (isset($response['Advis'])) {
                // save the correct result
                phive()->dumpTbl('safe_tamper-token_res-correct', json_encode($response));
                echo "Token is closed successfully.\n";
                return;
            } else {
                $this->safe_log->log("ERROR::There is something wrong with the XML response.");
            }
        }

        echo "Token failed to be closed.";
    }

    /**
     * Retrieve tamper token from Webservice
     */
    public function getTamperToken()
    {
        if ($this->lic_settings['use_random_test_tampertoken'] === true) {
            return $this->createTestTamperToken();
        }
        $uuid = $this->generateUniqueID();
        $xml_content = SafeXmlGenerator::generateOpenTokenXML($uuid, $this->SpilCertifikatIdentifikation);

        $xml_response = $this->sendToExternalService(SafeConstants::TOKEN_REQUEST_URL_CONFIG_NAME, $xml_content, SafeConstants::NEW_TOKEN_KEY);
        $mac = get_object_vars($xml_response->S_Body->TamperTokenAnvend_O->TamperTokenHent_O);
        phive()->dumpTbl('safe_mac_original', json_encode($mac));
        $transaction = get_object_vars($xml_response->S_Body->TamperTokenAnvend_O->Kontekst->HovedOplysningerSvar);

        if (! empty($transaction)) {
            // reset the sequence to 1 for the new token.
            $mac['sequence'] = 1;
            // saving the cursor
            $mac['cursor'] = $this->safe_params->getCursor();
            $params = array_merge($mac, $transaction);

            $this->safe_params = new SafeParams($params);
            $this->saveParams();

            DataService::insertReport(
                $this->safe_params,
                SafeConstants::NEW_TOKEN,
                '',
                '',
                $this->safe_params->getTokenStartDatetime(),
                $this->safe_params->getTokenEndDatetime()
            );

            return true;
        }

        return false;
    }

    /**
     * This is making the request to the external services about TAMPER TOKEN requests.
     *
     * @param $service
     * @param $xml_content
     * @param string $request_type
     * @param string $old_tokens_path
     * @return SimpleXMLElement
     */
    public function sendToExternalService($service, $xml_content, $request_type, $old_tokens_path = '')
    {
        $headers = $this->getHeaders();
        $start_time = microtime(true);
        $url = $this->lic_settings[$service];

        $this->beforeSendingRequest($xml_content, $request_type, $old_tokens_path);
        if (empty($this->lic_settings['distributed'])) {
            $res = phive()->post($url, $xml_content, 'text/xml', $headers, 'SAFE-token-request');
        } else {
            $res = toRemote('rofus', 'rofusRequest', [$url, $xml_content, 50, [], $headers, 'SAFE-token-request']);
        }

        $this->afterReceivingTheRequest($res);

        $xml = html_entity_decode($res);
        $xml = str_replace('S:', 'S_', $xml);
        $xml = str_replace('ns8:', 'ns8_', $xml);
        $xml_obj = SimpleXML_load_String($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        phive()->externalAuditTbl('tamper_token', $xml_content, $res, (microtime(true) - $start_time), '', '', $headers, '');
        return $xml_obj;
    }

    /**
     * Remove token folder.
     *
     * @param string $path
     */
    public function removeTokenFolders(string $path = "")
    {
        if (empty($path)) {
            $path = $this->pathToMainTokensFolder();
        }

        if (is_dir($path)) {
            $it = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);
            $files = new RecursiveIteratorIterator(
                $it,
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach($files as $file) {
                if ($file->isDir()) {
                    rmdir($file->getRealPath());
                } else {
                    unlink($file->getRealPath());
                }
            }

            rmdir($path);
        }
    }

    /**
     * Close the Tamper Token if the token deadline is reached.
     *
     * @return bool
     */
    public function cronCloseTamperToken()
    {
        try {
            $this->extractParams();

            $token_end_date = $this->safe_params->getTokenEndDatetime();
            $in_six_minutes = phive()->modDate(phive()->hisNow(), '+6 min', 'Y-m-d\TH:i:s.vP');

            if ($in_six_minutes > $token_end_date) {
                return $this->closeTamperToken();
            } else {
                $this->finishTimeoutRequest(true);
            }

        } catch (exception  $e) {
            error_log($e->getMessage());
            $this->removeReportRunning();
        }

        return false;
    }

    /**
     * If there is an other process in the SAFE, waiting an retry in 15 secs.
     * @return bool
     */
    private function checkAndWait()
    {
        if ($this->retry_safe == 0) {
            return false;
        }

        sleep($this->retry_interval);
        $this->retry_safe--;

        return $this->closeTamperToken();
    }

    /**
     * Create a random tamper token for testing purposes
     *
     * @return bool
     */
    public function createTestTamperToken(): bool
    {
        $randomTamperToken = time();
        $randomStartMAC = bin2hex(random_bytes(32));

        $params['TransaktionsID'] = $this->generateUniqueID();
        $params['TransaktionsTid'] = date(DateTime::RFC3339_EXTENDED);
        $params['ServiceID'] = "RandomTamperTokenTestService";
        $params['TamperTokenID'] = $randomTamperToken;
        $params['TamperTokenStartMAC'] = $randomStartMAC;
        $params['TamperTokenUdstedelseDatoTid'] = date(DateTime::RFC3339_EXTENDED);
        $params['TamperTokenPlanlagtLukketDatoTid'] = date(DateTime::RFC3339_EXTENDED, strtotime(" +1 day"));
        $params['sequence'] = 1;
        $params['cursor'] = $this->safe_params->getCursor();

        $this->safe_params = new SafeParams($params);
        $this->saveParams();

        DataService::insertReport(
            $this->safe_params,
            SafeConstants::NEW_TOKEN,
            '',
            '',
            $this->safe_params->getTokenStartDatetime(),
            $this->safe_params->getTokenEndDatetime()
        );

        return true;
    }

    /**
     * Close tokens that already have reports
     *
     * @param $token_id
     */
    public function closeUsedTokenByID($token_id)
    {
        $message = "Token {$token_id} is being closed.\n";
        echo $message;
        $this->safe_log->log($message);

        $this->finishTimeoutRequest();

        $sql = "
                SELECT log_info
                FROM
                    external_regulatory_report_logs
                WHERE
                    unique_id = '{$token_id}'
                ORDER BY sequence DESC
                LIMIT 1
                ";

        $tamperToken = phive('SQL')->loadAssoc($sql);
        $info = json_decode($tamperToken['log_info']);

        if ($info->ServiceID === SafeConstants::TEST_TAMPER_TOKEN_SERVICE) {
            return;
        }

        $xml_request = SafeXmlGenerator::generateCloseTokenXML(
            $info->TransaktionsID,
            $token_id,
            $info->TamperTokenStartMAC,
            $this->SpilCertifikatIdentifikation
        );

        $xml_response = $this->sendToExternalService(
            SafeConstants::TOKEN_REQUEST_URL_CONFIG_NAME,
            $xml_request,
            SafeConstants::CLOSE_TOKEN_KEY
        );
        $response = get_object_vars($xml_response->S_Body->TamperTokenAnvend_O->Kontekst
            ->HovedOplysningerSvar->SvarReaktion);

        $this->closeTokenResponse($response);
    }
}
