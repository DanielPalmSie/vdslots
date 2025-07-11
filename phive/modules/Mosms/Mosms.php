<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../Raker/Parser.php';

use Mosms\SmsSenderInterface;
use Mosms\ZignsecSoap\ZignsecSoapSmsSender;
use Mosms\ZignsecV5\ZignsecV5SmsSender;

class Mosms extends PhModule
{
    private SmsSenderInterface $sms_sender;

    function phInstall()
    {
        $active_sms_sender = $this->getSetting('active_sms_sender', 'zignsec_v5');

        switch ($active_sms_sender) {
            case 'zignsec_v5':
                $settings = $this->getSetting('sms_senders')['zignsec_v5'];

                /**
                 * @var ZignSecV5 $zignsec_v5
                 */
                $zignsec_v5 = phive('DBUserHandler/ZignSecV5');

                $this->sms_sender = new ZignsecV5SmsSender($settings, $zignsec_v5);
                break;
            case 'zignsec_soap':
                $settings = $this->getSetting('sms_senders')['zignsec_soap'];

                $this->sms_sender = new ZignsecSoapSmsSender($settings);
                break;
            default:
                throw new Exception("Unknown active sms sender: $active_sms_sender");
        }
    }

    /**
     * Check if the "calling_code" for the country is the same between the number stored in the User and the new one.
     * Match if both numbers starts the same way.
     *
     * @param $user
     * @param $new_number
     * @return bool
     */
    public function sameCountry($user, $new_number)
    {
        $new_clean = $this->cleanUpNumber($new_number);
        $arr = $this->splitNumberIntoParts($user);
        return strpos($new_clean, $arr[0]) === 0;
    }

    /**
     * Return the number information split in an array with 3 items:
     * - [0] - country_calling_code (Ex. for a GB customer "44")
     * - [1] - mobile_without_ccode (Ex. 12345678)
     * - [2] - mobile_full (Ex. 4412345678)
     *
     * If no number is provider the number will be extracted from the User.
     *
     * @param $user
     * @param string $number
     * @return array
     */
    public function splitNumberIntoParts($user, $number = '')
    {
        $mobile_full = $this->cleanUpNumber(empty($number) ? $user : $number);
        $calling_code = $user->getSetting('calling_code');

        if (empty($calling_code)) {
            $country_iso = $user->getAttribute('country');
            $country = phive('Localizer')->getBankCountryByIso($country_iso);
            $calling_code = $country['calling_code'];
        }

        $path = "|^{$calling_code}0|";
        if (preg_match($path, $mobile_full)) {
            $mobile_full = preg_replace($path, $calling_code, $mobile_full);
        }

        $path = "|^{$calling_code}|";
        $mobile_without_ccode = preg_replace($path, '', $mobile_full);
        return array($calling_code, $mobile_without_ccode, $mobile_full);
    }

    /**
     * Add to cash_transactions the PRICE for the sms we send to the user.
     *
     * @param $user
     */
    private function transactSms($user)
    {
        if (is_object($user)) {
            $bc = phive("UserHandler")->userBankCountry($user);
            phive("Cashier")->insertTransaction($user->getId(), ceil($bc['sms_price']), 33, "SMS Fee");
        }
    }

    /**
     * Process the Queue with the messages, by $limit amount each time.
     * TODO we know that with current implementation if something goes wrong some MSG may be removed before being sent.
     *  This will be fixed when we switch to a proper Queue. /Paolo + Ricardo
     *
     * @param $limit
     * @param int $priority
     */
    public function runQ($limit, $priority = 0)
    {
        $where_extra = empty($priority) ? '' : "AND scheduled_at <= NOW()";
        $list = phive('SQL')->loadArray("SELECT * FROM sms_queue WHERE priority = {$priority} {$where_extra} LIMIT 0,$limit");

        foreach ($list as $msg) {
            phive('SQL')->query("DELETE FROM sms_queue WHERE id = {$msg['id']}");
        }

        foreach ($list as $msg) {
            $user = cu($msg['user_id']);
            if (is_object($user)) {
                if ($user->isBlocked()) {
                    continue;
                }

                $this->sendSms($user, $msg['msg']);
                sleep(1);
            }
        }
    }

    public function sendSms(DBUser $user, string $message): bool
    {
        if ($this->getSetting('test') === true) {
            return true;
        }

        [$country_code, $mobile, $mobile_full] = $this->splitNumberIntoParts($user);

        if ($this->isSmsBlocked($country_code)) {
            phive('Logger')->getLogger('mosms')->error(
                'Sms is not sent because the country is blocked',
                ['user' => $user->getId(), 'mobile' => $mobile_full]
            );

            return false;
        }

        $result = $this->sms_sender->sendSms($country_code, $mobile, $mobile_full, $message);

        if ($result->isSuccess()) {
            $this->transactSms($user);
        }

        return $result->isSuccess();
    }

    private function isSmsBlocked($country_code): bool
    {
        $number_country = phive('Localizer')->getBankCountryByCallCode($country_code)['iso'];
        $countries = phive('Config')->valAsArray('sms', 'countries');

        return !in_array($number_country, $countries, true);
    }

    /**
     * Send sms, usually used to send verification messages
     *
     * @param $user
     * @param string $number
     * @param null $code
     * @return int|mixed|Parser
     */
    public function zSsendValidation($user, $number = '', $code = null)
    {
        $user = cu($user);
        if (is_null($code)) {
            $code = rand(100000, 999999);
        }

        list($ccode, $mobile, $complete_number) = $this->splitNumberIntoParts($user, $number);

        $old = $this->getByNumber($complete_number);

        if ($this->hasFailed($old)) {
            return $old['result'];
        } else if (!empty($old)) {
            $old['validated'] = 0;
            $old['code'] = $code;
        }

        $prefix = t('mosms.verification.msg');

        if ($this->getSetting('test') === true) {
            $result = 0;
        } else {
            $result = $this->sendSms($user, "$prefix $code");
            $result = $result ? 0 : 7;
        }

        if (empty($old)) {
            $this->insertValidation($user, $complete_number, $code, $result);
        } else {
            phive('SQL')->save('mosms_check', $old);
        }

        return $result;
    }

    /**
     * Return the number stripped of any non numeric char and removing the leading "00 | 0"s
     * Accept an instance of the User object or a string with the number.
     *
     * @param $userOrMobile DBUser|string
     * @return string|string[]|null
     */
    public function cleanUpNumber($userOrMobile)
    {
        $number = is_object($userOrMobile) ? $userOrMobile->getAttribute('mobile') : $userOrMobile;
        $number = preg_replace('|[^\d]|', '', $number);
        $number = preg_replace('/^00|^0/', '', $number);
        return $number;
    }

    /**
     * Wrapper on splitNumberIntoParts to get back the full number with ccode.
     *
     * @param $user DBUser
     * @return mixed
     */
    private function fixNumber($user)
    {
        list($ccode, $mobile, $complete_number) = $this->splitNumberIntoParts($user);
        return $complete_number;
    }

    /**
     * Insert row on mosms_check table with the info required to check for the validation code sent via sms.
     *
     * @param $user DBUser
     * @param $mobile - full phone number
     * @param $code - validation code
     * @param $result - 0 default OK result | 3 service problem | 4 ?? | 7 wrong number | 99 service problem
     * @return mixed
     */
    private function insertValidation($user, $mobile, $code, $result)
    {
        $insert = [
            'user_id' => $user->getId(),
            'code' => $code,
            'mobile' => $mobile,
            'result' => $result
        ];
        return phive('SQL')->insertArray('mosms_check', $insert);
    }

    /**
     * Get a Mosms line by way of mobile number
     *
     * Can be used like this to validate an OTP code: phive('Mosms')->getByNumber($user)['code'] == $code
     *
     * @param string|DBUser $mobile A mobile number or a user object.
     *
     * @return array The mosms line.
     */
    private function getByNumber($mobile)
    {
        if (is_object($mobile))
            $mobile = $this->fixNumber($mobile);
        return phive('SQL')->loadAssoc('', 'mosms_check', "mobile = '$mobile'");
    }

    /**
     * Check if the row is in a failed status
     *
     * @param $row
     * @return bool
     */
    private function hasFailed($row)
    {
        if (empty($row)) {
            return false;
        }

        return in_array($row['result'], array(7, 99));
    }

    /**
     * Update the mosms_check row and mark it as validated (1)
     *
     * @param $user DBUser
     * @param $code
     * @return bool
     */
    private function validate($user, $code)
    {
        $mosms1 = $this->getByNumber($user);
        $mosms2 = $this->getByNumber($_SESSION['toupdate']['mobile']);
        foreach (array($mosms1, $mosms2) as $mosms) {
            if (empty($mosms)) {
                continue;
            }
            if ($mosms['code'] == $code) {
                $mosms['validated'] = 1;
                phive('SQL')->save('mosms_check', $mosms);
                return true;
            }
        }
        return false;
    }

    /**
     * Return the text based on the result status
     *
     * @param null $res
     * @return array|mixed|null
     */
    function getMsg($res = null)
    {
        $msgs = [
            0 => t('mosms.success'),
            3 => t('mosms.service.problem'),
            99 => t('mosms.service.problem'),
            7 => t('mosms.wrong.number')
        ];

        if ($res === null) {
            return $msgs;
        }

        // We're already looking at some kind of error message.
        if (!is_numeric($res)) {
            return $res;
        }

        return $msgs[$res];
    }

    /**
     * Send SMS and do some validation based on country / number of retries for the validation code.
     *
     * @param bool $ajax
     * @param string $user
     * @return bool|int|mixed|Parser
     */
    function validateAndSendSms($ajax = true, $user = '')
    {
        $user = empty($user) ? cu() : $user;

        if (!is_object($user)) {
            return false;
        }

        $bcountry = phive('UserHandler')->userBankCountry($user);

        if (empty($bcountry) && !empty($_REQUEST['country'])) {
            $user->setAttribute('country', $_REQUEST['country']);
        } else if (empty($bcountry)) {
            return $this->retOrDie($ajax, 'register.mobile.wrong.country');
        }

        if ($_SESSION['sms_tries'] >= 3) {
            $user->setSetting('comment-' . time(), "3 or more SMS were sent without verification at " . date('Y-m-d H:i:s') . " GMT.");
            phive('UserHandler')->addBlock($_SESSION['mg_username'], 1, true);
            jsRedirect("?signout=true");
            exit;
        }

        if (!empty($_REQUEST['mobile']) && $user->getAttribute('verified_phone') == 0) {
            $other = phive('UserHandler')->getUserByAttr('mobile', $_REQUEST['mobile']);
            $cleaned_number = phive('Mosms')->cleanUpNumber($_REQUEST['mobile']);

            if (!is_object($other)) {
                $other = phive('UserHandler')->getUserByAttr('mobile', $cleaned_number);
            }

            if (is_object($other)) {
                if ($other->getId() != $user->getId()) {
                    return $this->retOrDie($ajax, 'register.mobile.taken');
                }
            }

            $user->setAttribute('mobile', $cleaned_number);
        }

        return $this->zSsendValidation($user);
    }

    /**
     * When validating SMS we mark the customer as "verified_phone = 1"
     * + validate current session "otp = 1"
     *
     * @return bool
     */
    function validateSms()
    {
        $user = cu();

        if (!is_object($user))
            return false;

        if ($this->validate($user, $_REQUEST['code'])) {
            $user->setAttribute('verified_phone', 1);
            $user->updateSession(['otp' => 1]);
            return true;
        }

        return false;
    }

    /**
     * Add messages to sms_queue.
     * Blocked users are skipped.
     *
     * @param $u DBUser
     * @param $msg
     * @param bool $check_verification
     * @param int $priority
     * @param string|null $scheduled_at
     * @return bool
     */
    public function putInQ($u, $msg, $check_verification = true, $priority = 0, $scheduled_at = null)
    {
        if ($u->isBlocked()) {
            phive('UserHandler')->logAction($u->getId(), "Did not get sms because inactive.", 'sms');
            return false;
        }

        $u = $u->getData();
        if (empty($u['mobile'])) {
            return false;
        }

        if (empty($u['verified_phone']) && $check_verification) {
            return false;
        }

        $insert = ['user_id' => $u['id'], 'msg' => $msg, 'priority' => $priority];
        if (!empty($scheduled_at)) {
            $insert['scheduled_at'] = $scheduled_at;
        }

        return phive('SQL')->insertArray('sms_queue', $insert);
    }

    /**
     * Sends a promotion on an scheduled time.
     *
     * @param string $trigger
     * @param DBUser $user
     * @param string|null $date If null uses tomorrow. The scheduled at date with time based on the fixed country schedule
     * @param mixed $replacers
     * @param mixed $lang
     * @param null|string $forced_time To run campaigns manually I enforce the day
     * @return bool
     */
    public function sendSMSPromo($trigger, $user, $date = null, $replacers = null, $lang = null, $forced_time = null)
    {
        if(empty($trigger) || !is_object($user)) {
            return false;
        }

        if (!phive('UserHandler')->canSendTo($user, null, $trigger)) {
            phive('UserHandler')->logAction($user->getId(), "Didn't get SMS with trigger $trigger because of privacy settings.", 'sms');
            return false;
        }

        $mobile = $user->getAttribute("mobile");
        if(empty($mobile)) {
            return false;
        }

        if (is_null($lang)) {
            $lang = $user->getAttribute("preferred_lang");
            if (empty($lang)) {
                $lang = phive('Localizer')->getDefaultLanguage();
            }
        }

        if (is_null($lang)) {
            $replacers = phive('MailHandler2')->getDefaultReplacers($user);
        }

        $sms_template = t('sms.message', $lang);
        if (empty($sms_template)) {
            return false;
        }

        $content = phive('MailHandler2')->replaceKeywords($sms_template, $replacers);
        if (empty($content)) {
            return false;
        }

        $schedule_list = array_flip($this->getCountrySchedule());

        if (empty($forced_time)) {
            $time = $schedule_list[$user->getCountry()] ?? $schedule_list['NA'];
        } else {
            $time = $forced_time;
        }

        $date = !empty($date) ? $date : phive()->hisMod('+1 day', '', 'Y-m-d');

        $scheduled_at = "{$date} {$time}:00";

        return $this->putInQ($user, $content, true, 1, $scheduled_at);

    }

    public function getCountrySchedule()
    {
        return phive()->fromDualStr("01:00_NZ|03:00_JP|07:00_IN|08:00_RU|10:00_FI|10:30_DK|11:00_SE|11:30_DE|11:45_AT|12:00_GB|12:30_IE|13:00_NO|13:30_NL|14:00_NA|19:00_CA", '|', '_');
    }

    /**
     * Extract country code from a mobile number
     *
     * @param string $number The mobile number to extract country code from
     * @return int|null Country code or null if not found
     */
    public function extractCountryCodeFromMobile(string $number): ?int
    {
        $number = $this->cleanUpNumber($number);
        $calling_codes = phive('SQL')->loadArray(
            "SELECT calling_code FROM bank_countries ORDER BY CHAR_LENGTH(calling_code) DESC"
        );
        $calling_codes = array_map(fn ($item) => $item['calling_code'], $calling_codes);

        foreach ($calling_codes as $calling_code) {
            if (strpos($number, $calling_code) === 0) {
                return +$calling_code;
            }
        }

        return null;
    }

}
