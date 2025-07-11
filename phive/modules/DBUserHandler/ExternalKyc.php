<?php

use Videoslots\HistoryMessages\InterventionHistoryMessage;

/**
 * Class ExternalKyc
 *
 * TODO this is a temporal class to avoid merge conflicts with the BankID SEB branch as this is implemented there as
 *  ExternalVerifier.php, I'm just implementing the method so we can move easily in the future
 *
 * TODO add centralized logic here for common shared "ext_kyc_xxx" setting /Paolo
 * TODO move add/removal of settings from the classes and do it here on a "final" action with the new shared setting /Paolo
 */


class ExternalKyc
{
    /**
     * Constant used on mapping for checkDob results to standardize the result on DB. (based on Experian data)
     * This value is set on the "users_setting" and used on "logAndBlockUserOnFailedKyc" for the error message.
     * - Experian & ID3
     */
    public const DOB_RESULTS = [
        'ALERT' => -1, // unsuccessful / failing search - Ex. Experian: not found; ID3: found and mismatching info
        'REFER' => 0, // uncertain of user identity - Ex. success and underage OR not sure about the user data (partial match)
        'PASS' => 1, // complete success (18+)
        'ERROR' => 2, // error (Ex. network error)
        'NO MATCH' => 3, // no match found (ID3 only)
        'PASS DUAL' => 4, // complete success (ID3 only)
        'PASS CREDIT' => 5, // complete success (ID3 only)
    ];

    /**
     * Constant used on mapping for checkPEPSanctions results to standardize the result on DB. (based on ID3 data)
     * This value is set on the "users_setting" and used on "logAndBlockUserOnFailedKyc" for the error message.
     * - Acuris & ID3
     */
    public const PEP_RESULTS = [
        'ALERT' => 'ALERT', // User is PEP or SL and we are sure the user match the provided data
        'REFER' => 'REFER', // User is PEP or SL BUT we are NOT 100% about the provided user data (partial match on info)
        'NO MATCH' => 'NO MATCH', // User not found (this is still a PASS)
        'PASS' => 'PASS', // User is found but is not PEP or SL
        'ERROR' => 'ERROR', // error (Ex. network error)
        'NOT_MAPPED' => 'REFER', // returned message is not mapped, we should do a manual check on the user.
    ];

    /**
     * Default timeout for API calls, fallback in case no setting is present.
     */
    public const DEFAULT_API_TIMEOUT = 10;

    /**
     * Global settings for KYC related services
     *
     * @var array
     */
    public $config_settings = [];

    /**
     * External Provider settings, populated in the child constructor
     *
     * @var array
     */
    public $kyc_settings = [];

    /**
     * DBUserHandler instance
     *
     * @var DBUserHandler $duh
     */
    public $duh;

    /**
     * If at least 1 provider give us a valid response we set this to false and do not fire logAndBlockUserOnFailedKyc.
     *
     * @var bool
     */
    private $all_check_failed = true;

    /**
     * Will contain all the failing providers responses
     *
     * @var array
     */
    private $kyc_errors = [];

    /**
     * Flag for PEP alert notifications
     *
     * @var bool
     */
    private $alert = false;

    /**
     * Flag for Age alert notifications
     *
     * @var bool
     */
    private $alertAge = true;


    public function __construct()
    {
        $this->config_settings = phive('Licensed')->getSetting('kyc_suppliers')['config'];

        $this->duh = phive('DBUserHandler');
    }

    /**
     * Having to deal with Phive singleton pattern we need to reset "all_check_failed" to his initial value & clear the errors
     * if this class is instantiated more than once (Ex. GB -> checkKyc)
     */
    public function resetFailureCheck()
    {
        $this->all_check_failed = true;
        $this->kyc_errors = [];
    }

    public function preventFailLogAndBlock()
    {
        $this->all_check_failed = false;
    }

    /**
     * Return TRUE if at least one of the provider has returned a positive value.
     *
     * @return bool
     */
    public function isSuccessful()
    {
        return !$this->all_check_failed;
    }


    /**
     * Set alert flat to PEP notifications
     *
     * @return void
     */
    public function setAlert(bool $alert): void
    {
        $this->alert = $alert;
    }


    /**
     * Set alert flat to Age notifications
     *
     * @return void
     */

    public function setAgeAlert(bool $alert): void
    {
        $this->alertAge = $alert;
    }

    /**
     * We use a separate instance of the parent class to keep track of the errors inside the checkCommonXXX methods
     *
     * @param $supplier - The supplier we are using (Ex. Experian, ID3, Acuris)
     * @param $result_code - The error code from the provider (Ex. any number "!= 1" has a specific meaning, 1 is the only success state, so it will never be reported)
     */
    public function addKycErrors($supplier, $result_code)
    {
        $this->kyc_errors[$supplier] = $result_code;
    }

    /**
     * Will return an array with the errors, if any.
     * If $format is TRUE we will return a string with all the failing provider concatenated.
     * If $reverse_dob_mapping is TRUE we user the DOB_RESULT key instead of the value, to display a more human readable message.
     *
     * @param bool $format
     * @param bool $reverse_dob_mapping
     * @return array|string
     */
    public function getKycErrors($format = false, $reverse_dob_mapping = false)
    {
        if ($format) {
            $error_message = [];
            foreach ($this->kyc_errors as $supplier => $result_code) {
                if ($reverse_dob_mapping) {
                    // handle error scenarios where we are not setting a numeric value on DOB user setting
                    $result_code = is_numeric($result_code) ? array_search($result_code, self::DOB_RESULTS) : $result_code;
                }
                $error_message[] = "supplier: $supplier, result: $result_code";
            }
            return implode(' & ', $error_message);
        }
        return $this->kyc_errors;
    }

    public function jsonSuccess($result)
    {
        return json_encode($this->success($result));
    }

    public function success($result)
    {
        return ['success' => true, 'result' => $result];
    }

    // TODO err_no is not used?
    public function fail($result, $err_no = 0)
    {
        return ['success' => false, 'result' => $result];
    }

    public function jsonFail($result, $err_no = 0)
    {
        return json_encode($this->fail($result, $err_no));
    }

    /**
     * This function will log and take actions on failing result from external identify verification services based on $type
     * If all check passes we do nothing
     * (PEP only) If 1 (or more) check fail(s) but the final result is a pass we send a warning email
     * If the final result is a fail we send a block email with the list of all the failing supplier
     *
     * Specific:
     * - age - see logAndBlockDob
     * - pep - see logAndBlockPEP
     * Common:
     * - triggers onKycCheck to set AML/RG flags
     *
     * @param $user DBUser
     * @param $type - age | pep
     * @param bool $recurrent
     */
    public function logAndBlockUserOnFailedKyc($user, $type, $recurrent = false)
    {
        if (empty($this->all_check_failed)) {
            if ($type === 'pep' && in_array(self::PEP_RESULTS['ALERT'], $this->getKycErrors()) && $this->alert) {
                $this->sendPepFailedEmail($user, false);
            }
            return;
        }

        if ($type == 'age') {
            $this->logAndBlockDob($user);
        }
        if ($type == 'pep') {
            $this->logAndBlockPEP($user, $recurrent);
            $user->sendPEPFailureBlockToRemoteBrand();
        }

        if ($this->alertAge) {
            // Fire the RG/AML triggers.
            foreach ($this->getKycErrors() as $supplier=>$result_code) {
                phive('Cashier/Aml')->onKycCheck($user->getId(), $type, $supplier, $result_code);
                // Firing only once to avoid spamming RG/AML triggers (this will report only the first failing supplier)
                break;
            }
        }
    }

    /**
     * Actions to be taken on "age" verification fail
     * - block user via "experian_block" setting
     * - log on "actions" and "users_comments".
     * - send email to "kyc_mail"
     *
     * Regarding alias: even if the alias is "experian.fail.x" (x is a number) this string contains a generic error message for calls to checkDob() from ID3 too.
     * TODO we should rename this to a more common string like "ext.identity.check.fail.xxx" (xxx is a proper message), create a mapping function for the error messages /Paolo
     * Extract first error $result_code from the error array. (we may have more than 1 failure, but we should report only 1)
     *
     * @param DBUser $user
     */
    private function logAndBlockDob($user)
    {
        $uid = $user->getId();

        $user->setSetting('experian_block', 1);
        $user->depositBlock();
        $user->deleteSetting('tmp_deposit_block');
        lic('blockKycDob', [$user], $user);

        $error_message = "Blocked, age check failed on ".$this->getKycErrors(true, true);
        $user->addComment($error_message);
        phive('UserHandler')->logAction($uid, $error_message, 'experian_block');

        $log_id = phive('UserHandler')->logAction($uid, "profile-blocked|fraud - {$error_message}", 'intervention');
        /** @uses Licensed::addRecordToHistory() */
        lic('addRecordToHistory', [
            'intervention_done',
            new InterventionHistoryMessage([
                'id'                => (int) $log_id,
                'user_id'           => (int) $uid,
                'begin_datetime'    => phive()->hisNow(),
                'end_datetime'      => '',
                'type'              => 'profile-blocked',
                'cause'             => 'fraud',
                'event_timestamp'   => time(),
            ])
        ], $user);

        if ($this->alertAge) {
            phive('MailHandler2')->mailLocal(
                $user->getCountry()." identity fail for {$uid}",
                "$error_message, account:" . '<a href="' . $user->accUrl('', true) . '">' . $uid . '</a>',
                'kyc_mail'
            );
        }


        $result_code = array_values($this->getKycErrors())[0];
        $fraud_msg = t("experian.fail.$result_code");
        $_SESSION['experian_msg'] = $fraud_msg;
    }

    /**
     * Actions to be taken on "pep" verification fail
     * - block user via "experian_block" setting (only if non recurrent, when we do the check every 4 months)
     * - send email to "kyc_mail"
     * - block user via "addBlock($user, 12)" function if its a fail
     *
     * @param $user DBUser
     * @param $recurrent
     */
    public function logAndBlockPEP($user, $recurrent = false)
    {
        if ($recurrent === false) {
            $user->setSetting('experian_block', 1);
        }

        if ($this->alert) {
            $this->sendPepFailedEmail($user);
        }

        $this->duh->addBlock($user, 12);
    }

    public function getPremise(&$ud)
    {
        $arr = explode(' ', $ud['address']);
        foreach ($arr as $i => $k) {
            if (is_numeric($k)) {
                return $k;
            }
        }

        preg_match('|\d+|', $ud['address'], $m);
        return $m[0];
    }

    /**
     * Send email notification for PEP check failed.
     *
     * @param $user DBUser
     * @param $blocked boolean
     */
    public function sendPepFailedEmail($user, $blocked = true)
    {
        $block_text = $blocked ? "Account was blocked automatically" : "User enabled, even if 1 of the PEP check failed, a manual check is recommended";
        $title = "PEP/SL failed";
        $body = "PEP/SL check failed on the user with id <a href='{$user->accUrl('', true)}'>{$user->getId()}</a>, failure from {$this->getKycErrors(true)}. {$block_text}.";

        $this->sendToPayments($title, $body);
    }

    /**
     * New alert is created
     *
     * @param $email
     * @param $supplier
     */
    public function sendPepAlert($email, $supplier)
    {
        $title = "New alert(s) in $supplier";
        $body = implode(chr(10), $email);
        $this->sendToPayments($title, $body);
    }

    /**
     * Send mail to Payments
     *
     * @param $title
     * @param $body
     */
    public function sendToPayments($title, $body)
    {
        phive('MailHandler2')->mailLocal(
            $title,
            $body,
            'fraud_mail'
        );
    }

    /**
     * log successful PEP\SL
     *
     * @param $user DBUser
     * @param $type string
     * @return void
     */
    public function logSuccessfulAction($user, string $type): void
    {
        if ($this->isSuccessful()) {
            $message = ($type == 'age') ? 'user age verified' : 'user is PEP/SL verified';
            phive('UserHandler')->logAction($user, $message, 'KYC_PASS');
        }
    }
}
