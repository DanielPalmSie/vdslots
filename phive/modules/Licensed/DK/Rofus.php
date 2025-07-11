<?php
/**
 * All Rofus related stuff will go there
 */

trait Rofus
{
    public function mockRofusCheck()
    {
        return 'is self excluded';
    }

    /**
     * Check if user is underage
     *
     * @param DBUser $u_obj
     * @return string - value in ['N', 'E', 'Y']
     */
    public function checkAgeStatus(DBUser $u_obj): string
    {
        try {
            $doc = $this->singleGamstopRequest($u_obj, 'GamblerCSRPValidationRequest');

            /** In case of errors/service down, we are stopping the process to continue. Thus mocking as self excluded */
            if (is_string($doc) || !$doc->getElementsByTagName('GamblerCPRStatus')[0] || !$doc->getElementsByTagName('GamblerAgeStatus')[0]) {
                return 'Y';
            }

            $gambler_status = $doc->getElementsByTagName('GamblerCPRStatus')[0]->nodeValue;
            $age_status = $doc->getElementsByTagName('GamblerAgeStatus')[0]->nodeValue;

            if($gambler_status === 'CPRIsNotRegistered') {
                return 'N';
            }

            return $age_status === 'AgeIs18OrAbove' ? 'N' : 'Y';
        } catch (\Exception $exception) {
            phive('Logger')->error(
                "Rofus. checkAgeStatus. E",
                ['user_data' => $u_obj, 'exception' => $exception->getMessage()]
            );
        }
    }

    /**
     * Check if user is self excluded
     *
     * @param DBUser $u_obj
     * @return string - value in ['N', 'E', 'Y']
     */
    public function checkExclusionStatus(DBUser $u_obj): string
    {
        if ($this->getLicSetting('gamstop')['disable_calls'] === true) {
            return 'D';
        }
        try {
            $doc = $this->singleGamstopRequest($u_obj, 'GamblerCheckRequest');

            if (is_string($doc) || !$doc->getElementsByTagName('GamblerExclusionStatus')[0]) {
                return 'E';
            }

            $status = trim($doc->getElementsByTagName('GamblerExclusionStatus')[0]->nodeValue);

            if (empty($status) || $status == 'E') {
                return 'E';
            }

            return $status == 'PersonIsNotRegistered' ? 'N' : 'Y';

        } catch (\Exception $exception) {
            phive('Logger')->error(
                "Rofus. checkAgeStatus. E",
                ['user_data' => $u_obj, 'exception' => $exception->getMessage()]
            );
        }
    }

    /**
     * Check if user is excluded
     *
     * @param DBUser $u_obj
     * @return string - value in ['N', 'E', 'Y']
     */
    public function checkGamStop(DBUser $u_obj): string
    {
        if ($u_obj->hasSetting('registration_in_progress')) {
            $result = $this->checkAgeStatus($u_obj);

            if($result === 'Y') {
                return $result;
            }
        }

        return $this->checkExclusionStatus($u_obj);
    }

    /**
     * Check user exclusion status for a single user
     *
     * GamblerExclusionStatus
     *  Can be either of the below but we're really only interested in it if is PersonIsNotRegistered or not atm.
     *  PersonIsNotRegistered
     *  PersonIsRegisteredIndefinitely
     *  PersonIsRegisteredTemporarily
     *
     * @param DBUser $u_obj
     * @param string $action
     * @return string
     */
    public function singleGamstopRequest(DBUser $u_obj, string $action)
    {
        /** @var DOMDocument $doc */
        $nid = $u_obj->getNid();
        $user_id = $u_obj->getId();

        $data = "
            <PersonInformation>
                <PersonCPRNumber>$nid</PersonCPRNumber>
                <SpillerInformationIdentifikation>$user_id</SpillerInformationIdentifikation>
            </PersonInformation>
        ";

        return $this->makeGamstopRequest($data, $action, false, $u_obj->getId());
    }

    /**
     * Check user exclusion status for a list of cpr numbers
     *
     * Implementation is based on the WSDL found in:
     *  https://rofusdemo.spillemyndigheden.dk/GamblerReklameProject/GamblerReklameService
     *
     * @param array $items - list of nid, eg. [1211800050, 1211800107]
     * @return array|string - on error returns string, on success returns list of excluded cpr numbers
     */
    public function bulkGamstopRequest($items)
    {
        /** @var DOMDocument $doc */
        $items = implode("\n", array_map(function ($cpr) {
            return "<PersonCPRNummer>$cpr</PersonCPRNummer>";
        }, $items));

        $data = "<SpillerListe>$items</SpillerListe>";
        $doc = $this->makeGamstopRequest($data, 'GamblerMultiReklameCheckRequest', true);

        if (is_string($doc)) {
            return $doc;
        }

        if ($doc->getElementsByTagName('SpillerListeReklameFravalgt')->length === 0) {
            return 'E';
        }

        $blocked = [];
        foreach ($doc->getElementsByTagName('PersonCPRNummer') as $block) {
            $blocked[] = $block->nodeValue;
        }

        return $blocked;
    }

    /**
     * Method used to send requests to gamstop
     * Docs: https://www.spillemyndigheden.dk/uploads/2018-11/Technical%20requirements%20-%20online%20casino%20and%20betting%20v2.1.pdf
     *
     * @param $data
     * @param string $action
     * @param bool $bulk
     * @param null $user_id
     * @return DOMDocument|string
     */
    private function makeGamstopRequest($data, $action = 'GamblerCheckRequest', $bulk = false, $user_id = null)
    {
        if ($this->getLicSetting('gamstop')['disable_calls'] === true) {
            return 'D';
        }

        $req_id = phive()->uuid();
        $stamp = date(self::FULL_SO_DATE_FORMAT);

        $xml = phive()->ob(function () use ($data, $req_id, $stamp, $action) { ?>
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
                              xmlns:ser="http://services.lur.skat.dk">
                <soapenv:Header/>
                <soapenv:Body>
                    <ser:<?php echo $action ?>>
                        <Kontekst>
                            <ns1:HovedOplysninger
                                xmlns:ns1="http://skat.dk/begrebsmodel/xml/schemas/kontekst/2007/05/31/">
                                <ns1:TransaktionsTid><?php echo $stamp ?></ns1:TransaktionsTid>
                                <ns1:TransaktionsID><?php echo $req_id ?></ns1:TransaktionsID>
                            </ns1:HovedOplysninger>
                        </Kontekst>
                        <?php echo $data ?>
                    </ser:<?php echo $action ?>>
                </soapenv:Body>
            </soapenv:Envelope>
            <?php
        });

        $start_time = microtime(true);
        $ss = $this->getLicSetting('gamstop');
        if (empty($bulk)) {
            $base_url = $ss['url'];
            $tag = 'rofus';
            $timeout = '10-10';
        } else {
            $base_url = $ss['bulk'];
            $tag = 'rofus-bulk';
            $timeout = '10-30';
        }

        $extra = [
            CURLOPT_USERPWD => $ss['username'] . ':' . $ss['password']
        ];

        if (empty($ss['distributed'])) {
            $res = phive()->post($base_url, $xml, 'text/xml', '', 'rofus', 'POST', $timeout, $extra);
        } else {
            $res = toRemote('rofus', 'rofusRequest', [$base_url, $xml, $timeout, $extra]);
        }

        $doc = new DOMDocument;
        $doc->loadxml($res);

        $response_id = $doc->getElementsByTagName('TransaktionsID')[0]->nodeValue;

        $this->logExternal($tag, $xml, $res, microtime(true) - $start_time, empty($res) ? 500 : 200, $req_id, $response_id, $user_id);

        if (empty($res)) {
            return 'E';
        }

        return $doc;
    }

    public function userIsMarketingBlocked($user)
    {
        $user = cu($user);

        if (empty($user) || empty($user->getNid())) {
            return true;
        }

        $res = $this->checkGamStop($user);

        // error OR user was found in Gamstop
        return (($res === 'E') || ($res === 'Y'));
    }


    /**
     * @param array $users
     * @return mixed
     */
    public function getMarketingBlockedUsers($users)
    {
        $missing_nid = [];

        foreach ($users as $index => $user) {
            if ($user['country'] !== $this->getIso()) {
                unset($users[$index]);
                continue;
            }
            if (empty($user['nid']) || strlen($user['nid']) > 10) {
                $missing_nid[] = $user;
                unset($users[$index]);
            }
        }

        $blocked = [$missing_nid];

        $count_missing_nid = count($missing_nid);
        $this->dumpLog('empty-nid-rofus',"$count_missing_nid users had missing nid or wrong length in marketing blocked check");

        foreach (array_chunk($users, $docs_max_number_of_allowed_items = 1000) as $users_chunk) {
            $res = $this->bulkGamstopRequest(array_column($users_chunk,'nid'));

            // Deactivated so no user is blocked
            if ($res === 'D') {
                continue;
            }

            // Error
            if ($res === 'E') {
                $blocked[] = $users_chunk;
                continue;
            }

            // push only blocked users
            $blocked[] = array_filter($users_chunk, function ($user) use ($res) {
                return in_array($user['nid'], $res);
            });
        }

        // convert array of arrays to array of users
        $blocked = phive()->flatten($blocked, true);

        // return array of user ids
        return array_map(function ($user) {
            return $user['id'];
        }, $blocked);
    }

}
