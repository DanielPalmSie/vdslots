<?php

require_once __DIR__ . '/../../../modules/DBUserHandler/Registration/RegistrationHtml.php';

use Firebase\JWT\BeforeValidException;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;

trait LoginTrait
{
    /** @var int Time sleep to decode JWT data */
    public static $time_sleep = 5;

    /** @var int Tries to decode JWT data */
    public static $tries_limit = 1;

    abstract public function getLicSetting($setting);

    public function getNidPlaceholder()
    {
        return 'DDMMYYSSSS';
    }

    /**
     * Validate external service response for MitID
     *
     * @param array|null $response
     */
    public function validateLoginMitID(array $response = null)
    {
        /** @var Logger $logger */
        $logger = phive('Logger');
        /** @var DBUserHandler $uh */
        $uh = phive('UserHandler');
        $date_time = new DateTime();

        /* Redirect when request cancelled or declined on MitID side */
        if ($response && $response['errorCode']) {
            if ($response['errorCode'] == ZignSecV5::CANCELLED_RESPONSE) {
                $message = $response['errorText'] ?? 'MitId request cancelled';
                $logger->error($message, ['timestamp' => $date_time->getTimestamp()]);
                logToActionsAndReturn($message);

                $_SESSION['mitid_error'] = 'mitid.error';
                phive('Redirect')->to('/');
            }
            if ($response['errorCode'] == ZignSecV5::CANCELLED_DECLINED) {
                $message = $response['errorText'] ?? 'MitId, user CPR does not match';
                $logger->error($message, ['timestamp' => $date_time->getTimestamp()]);
                logToActionsAndReturn($message);

                $_SESSION['mitid_cpr_error'] = 'mitid.cpr.error';
                phive('Redirect')->to('/');
            }
        }

        /* Decoding receiving data from ZignSec or using mock data */
        $mit_id_status = phive('DBUserHandler')->zs5->getMitIdStatus($_SESSION['mitid_session']);
        if (!empty($mit_id_status['result']['data']['errors'])) {
            $logger->error('MitId session error',['timestamp' => $date_time->getTimestamp(), 'errors' => $mit_id_status['result']['data']['errors']]);
            logToActionsAndReturn($mit_id_status['result']['data']['errors']);

            $_SESSION['mitid_error'] = 'mitid.error';
            phive('Redirect')->to('/');
        }
        $jwks = $this->getLicSetting('mitID');
        $user_data_jwt = $mit_id_status['result']['data']['result']['signedIdentity'];
        $decoded_user_data = $this->decode($user_data_jwt, $jwks);

        /* Redirect with error when decoded data problem */
        if ($decoded_user_data === null) {
            $message = 'MitId decode data error';
            $logger->error($message, ['timestamp' => $date_time->getTimestamp()]);
            logToActionsAndReturn($message);

            $_SESSION['mitid_error'] = 'mitid.error';
            phive('Redirect')->to('/');
        }

        /* Adding 'nid' (personal number / CPR) to received user data for further actions on it */
        $user_identity = (array)$decoded_user_data['identity'];
        $user_identity['personalNumber'] = $_SESSION['rstep1']['nid'];

        $user = $uh->getUserByUsername($_SESSION['mitid_user']);

        /* User registered when DK market was unregulated (didn't require NID during registration) */
        $user_without_nid_login = isset($_SESSION['mitid_user_registration']) && isset($_SESSION['verify_username']) && $user;

        /* Login case */
        if (!isset($_SESSION['mitid_user_registration']) || $user_without_nid_login) {
            /* Redirect with error when not exist user on our side */
            if ($user == null) {
                $message = 'MitId user exist error';
                $logger->warning($message, ['user' => $_SESSION['mitid_user'], 'timestamp' => $date_time->getTimestamp()]);
                logToActionsAndReturn($message);

                $_SESSION['mitid_error'] = 'mitid.error';
                phive('Redirect')->to('/');
            }

            /* Redirect with error when not exist MitID user or session */
            if (!isset($_SESSION['mitid_user']) || !isset($_SESSION['mitid_session'])) {
                $message = 'MitId session error';
                $logger->warning($message, ['timestamp' => $date_time->getTimestamp()]);
                logToActionsAndReturn($message);

                $_SESSION['mitid_error'] = 'mitid.error';
                phive('Redirect')->to('/');
            }

            /* set nid_data when MIT Id account to existing and don't validate identity since we use getMitIdCprMatch */
            if (isset($_SESSION['mitid_user_connect_account'])) {
                unset($_SESSION['mitid_user_connect_account']);
                /* Redirect with error when the user's CPR does not match */
                if (!$user_identity['cprNumberMatch']) {
                    $message = 'MitId, user CPR does not match';
                    $logger->error($message, ['timestamp' => $date_time->getTimestamp()]);
                    logToActionsAndReturn($message);

                    $_SESSION['mitid_cpr_error'] = 'mitid.cpr.error';
                    phive('Redirect')->to('/');
                }
            } else {
                /* Redirect with error when user not validated between MitID and our side, only if not registration */
                if (!$this->isIdProviderPersonIdValid($user, $user_identity["idProviderPersonId"])) {
                    $message = 'MitId user not valid error';
                    $logger->warning($message, ['user' => $user, 'timestamp' => $date_time->getTimestamp()]);
                    logToActionsAndReturn($message);

                    $_SESSION['mitid_error'] = 'mitid.error';
                    phive('Redirect')->to('/');
                }
            }

            if ($user_without_nid_login) {
                $user->setAttr('nid', $user_identity['personalNumber']);
            }

            /* Update mit id data*/
            $user->setSetting('mit_id_data', json_encode($user_identity));

            /* Redirect with user login */
            $user->setSetting('verified-nid', 1);

            $user->deleteSetting('failed_logins');
            $user->deleteSetting('failed_login_otp_attempts');
            $user->deleteSetting('failed_login_captcha_attempts');

            $uh->markSessionAsOtpValidated();
            $uh->login($user->getUsername(), '', true, false);
            phive('UserHandler')->logAction($user, 'Login by: MIT ID', 'logged_in');
            phive('Redirect')->to('/');

        /* Registration case */
        } else if (isset($_SESSION['mitid_user_registration'])) {
            unset($_SESSION['mitid_user_registration']);

            $user_with_cpr = lic('getUserIdsByNids', [[$user_identity['personalNumber']]]);
            if (!empty($user_with_cpr)) {
                /* We already have a user with the same nid */

                $user_already_registered = phive('DBUserHandler')->checkExistsByAttr('nid', $user_identity['personalNumber'], true);

                /*
                 * Check if user with the same nid finished registration.
                 * If finished - redirect with error.
                 * If not finished - allow user to complete registration.
                */
                if ($user_already_registered) {
                    $message = 'MitId, user with CPR already exist';
                    $logger->error('MitId, user with CPR already exist',
                        ['timestamp' => $date_time->getTimestamp(), 'CPR' => $user_identity['personalNumber']]);
                    logToActionsAndReturn($message . ' CPR: ' . $user_identity['personalNumber']);

                    $_SESSION['mitid_cpr_error'] = 'mitid.cpr.error';
                    phive('Redirect')->to('/');
                } else {
                    $user_id = array_values($user_with_cpr)[0];
                    $user = cu($user_id);
                    $_SESSION['reg_uid'] = $user->getId();
                    phive('DBUserHandler')->updateUser($user, $_SESSION['rstep1'], 'step1');

                    phive('Redirect')->to('?show_reg_step_2=true');
                }
            }

            /* Redirect with error when the user's CPR does not match */
            if (!$user_identity['cprNumberMatch']) {
                $message = 'MitId, user CPR does not match';
                $logger->error($message, ['timestamp' => $date_time->getTimestamp()]);
                logToActionsAndReturn($message);

                $_SESSION['mitid_cpr_error'] = 'mitid.cpr.error';
                phive('Redirect')->to('/');
            }
            $rstep1_data['rstep1'] = $_SESSION['rstep1'] ?? [];
            $_POST = $rstep1_data['rstep1'];
            $_POST['personal_number'] = $_SESSION['rstep1']['personal_number'] = $user_identity['personalNumber'];
            $_SESSION['rstep1']['response']['relaystate'] = $user_identity['personalNumber'];
            $_SESSION['rstep1']['mitid_user'] = $_SESSION['mitid_user'];

            /* Additional call to get user address, city, zipcode. And adding these data on registration step2 and DB */
            /* If we at some point don't have $_SESSION['rstep1']['country'] adding 'DK' like default */
            $country = $rstep1_data['rstep1']['country'] ?? 'DK';

            /** @var false|ZignSecLookupPersonData $lookup_data */
            $lookup_data = lic('getDataFromNationalId', [strtolower($country), $user_identity['personalNumber']], $user);

            if ($lookup_data === false) {
                $message = 'Failed GetLookupPersonCommon Request';
                $logger->error($message, ['timestamp' => $date_time->getTimestamp(), 'CPR' => $user_identity['personalNumber']]);
                logToActionsAndReturn($message . ' CPR: ' . $user_identity['personalNumber']);

                $_SESSION['mitid_error'] = 'mitid.error';
                phive('Redirect')->to('/');
            }

            phMsetArr($_SESSION['cur_req_id'] . '-raw', $lookup_data->getResponseData());
            phMsetArr($_SESSION['cur_req_id'] . '-nid', $_SESSION['rstep1']['nid']);

            $nid_data = lic('getPersonLookupHandler')->mapLookupData($lookup_data->getResponseData());

            $_SESSION['rstep1']['address'] = $nid_data['address'];
            $_SESSION['rstep1']['zipcode'] = $nid_data['zipcode'];
            $_SESSION['rstep1']['city'] = $nid_data['city'];

            /* Using firstName and lastName from additional call, regarding not correction of response data on first call */
            $_SESSION['rstep1']['firstName'] = $nid_data['firstname'];
            $_SESSION['rstep1']['lastName'] = $nid_data['lastname'];

            list($user, $errors, $res) = RegistrationHtml::finalizeRegistrationStep1($_SESSION['rstep1']);
            if (empty($user) || !empty($errors)) {
                $response = $res ?? RegistrationHtml::failureResponse($errors, true);
                $logger->error('MitId finalize registration step1 error',['response' => $response, 'timestamp' => $date_time->getTimestamp()]);
                logToActionsAndReturn($errors);

                $_SESSION['mitid_error'] = 'mitid.error';
                phive('Redirect')->to('/');
            } else {
                $response = RegistrationHtml::successResponse();
                $logger->info('MitId finalize registration step1 success',['response' => $response, 'timestamp' => $date_time->getTimestamp()]);
                $user->setAttr('nid', $_SESSION['rstep1']['nid']);
                $user->setSetting('nid_data', json_encode($lookup_data->getResponseData()));
                $user->setSetting('mit_id_data', json_encode($user_identity));
                $user->setSetting('verified-nid', 1);
                $_SESSION['reg_uid'] = $user->getId();
                $lang = $_SESSION['mitid_user_lang'];
                phive('Redirect')->to('?show_reg_step_2=true', $lang);
            }
        }
    }

    /**
     * Validate Id Provider Person Id Valid user by comparing personal number
     *
     * @param User   $user
     * @param string $idProviderPersonId
     *
     * @return bool
     */
    private function isIdProviderPersonIdValid(User $user, string $idProviderPersonId): bool
    {
        $mit_id_data = $user->getSetting('mit_id_data');

        if (empty($mit_id_data)) {
            return false;
        }

        if (json_decode($mit_id_data, true)['idProviderPersonId'] !== $idProviderPersonId) {
            return false;
        }

        return true;
    }

    /**
     * @param $user_data_jwt
     * @param $jwks
     * @param int $attempts
     *
     * @return array|null
     */
    private function decode($user_data_jwt, $jwks, int $attempts = 1): ?array
    {
        try {
            $decoded_user_data = (array)JWT::decode($user_data_jwt, JWK::parseKeySet($jwks));
        } catch (BeforeValidException $before_valid_e) {
            phive('Logger')->error('MitID JWT configuration/library error', $before_valid_e->getMessage());
            if ($attempts > self::$tries_limit) {
                return null;
            }
            sleep(self::$time_sleep);
            return $this->decode($user_data_jwt, $jwks, $attempts++);
        } catch (Exception $e) {
            phive('Logger')->error('MitID JWT configuration/library error', $e->getMessage());
            return null;
        }

        return $decoded_user_data;
    }
}

