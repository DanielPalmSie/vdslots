<?php

use Carbon\Carbon;
use Laraphive\Contracts\EventPublisher\EventPublisherInterface;

require_once __DIR__ .'/../../traits/HasSitePublisherTrait.php';

/**
 * Handles all logic related to customer account linking between brands.
 *
 * At this point it only supports 2 brands, N-brands support is not support to speed up development.
 */
class Linker
{
    use HasSitePublisherTrait;

    private const TIME_OUT_REQUEST = 3;

    /**
     * All the linker configuration
     *
     * @var array
     */
    protected $config;

    /**
     * @var Distributed
     */
    protected $distributed;

    /**
     * @var SQL
     */
    protected $db;

    /**
     *
     * Linker constructor.
     */
    public function __construct()
    {
        $this->distributed = phive('Distributed');
        $this->db = phive('SQL');
        $this->config = $this->distributed->getSetting('brand_linker');
    }

    /**
     * Config getter
     *
     * @param $name
     * @param false $default
     * @return false|mixed
     */
    public function getSetting($name, $default = null)
    {
        return $this->config[$name] ?? $default;
    }

    /**
     * Get the remote id of a linked brand
     *
     * @param DBUser|int $user
     * @param bool $get_all When false we only return the primary, otherwise we return all
     * @return int|bool
     */
    public function getUserRemoteId($user, $get_all = false)
    {
        $user = cu($user);
        if (empty($user)) {
            return false;
        }

        $remoteBrand = getRemote();
        $remoteUserId = $user->getSetting(distKey($remoteBrand));

        if (isRemoteBrandSCV()) {
            $result = $remoteUserId;
        } else {
            if ($get_all === true) {
                $idList = $user->getSetting('c'. distId($remoteBrand). '_matches');

                $result = empty($idList)
                    ? $remoteUserId
                    : array_keys(json_decode($idList, true) ?? $remoteUserId);
            } else {
                $result = $remoteUserId;
            }
        }

        return empty($result) ? false : $result;
    }

    /**
     * Separate function as we need it in forked context in some scenarios
     *
     * @param DBUser|int $user
     * @param string $check_self_exclusion - "yes/no" instead of true/false as we are using this via CLI from pexec, so we cannot pass real boolean.
     *            if "yes" we force an extra call to determine if the user is self-excluded on other brands, and lock on the customer on the current one too.
     *
     * @return mixed
     */
    public function brandLink($user, string $check_self_exclusion = 'no', int $retry_attempt = 0)
    {
        $user_temp_store = $user;
        $ret = false;
        $user = cu($user);

        if (empty($user)) {
            throw new RuntimeException("User not found with value ".json_encode($user_temp_store));
        }

        $account_linking_retry_attempts = phive('Distributed')->getSetting('account_linking_retry_attempts', 20);

        if ($retry_attempt > $account_linking_retry_attempts) {
            phive('Logger')->getLogger('brand_link')
                ->warning("The max amount of retrying attempts for account linking has been exceeded.
                Attempts #{$retry_attempt}. User ID {$user->getId()}");
            $user->refreshSetting('brand_link_max_retry', 1, true);
            return false;
        }

        $account_linking_retry_delay_in_microseconds = phive('Distributed')->getSetting('account_linking_retry_delay');

        try {
            if (in_array($user->getCountry(), $this->getSetting('registration_link_blocked_countries', []))) {
                $user->deleteSetting('cross_brand_check_block');
                return $ret;
            }

            $remote = $this->getSetting('registration_link_remote', false);
            if (!empty($remote)) {
                if ($this->isAllowedToLinkAccounts($user) === false) {
                    $this->deleteCrossBrandSettings($user);
                    return $ret;
                }

                $ping_remote = toRemote($remote, 'getLocalBrandId', [], self::TIME_OUT_REQUEST);
                if (is_int($ping_remote)) {
                    if(!empty(licSetting('cross_brand', $user)['block_deposits_while_linking'])) {
                        //Prevent deposits until the cross brand check is done
                        $user->setSetting('cross_brand_check_block', 1);
                    }

                    $res = lic('matchInBrand', [$user, $remote], $user);
                    if (!empty($res['success'])) {
                        $user->deleteSetting('brand_check_in_progress');


                        if (!empty($res['result']['user_id'])) {
                            $link_result = $this->distributed->linkCustomer(
                                $user->getId(),
                                $res['result']['brand_id'],
                                $res['result']['user_id'],
                                $res['result']['matches']
                            );

                            if ($check_self_exclusion === 'yes') {
                                list($block, $result) = lic('hasInternalSelfExclusion', [$user], $user);
                                if ($block !== false) {
                                    phive('DBUserHandler')->logAction($user, "Standard login: internal self-exclusion check failed", 'failed_login_blocked');

                                    phiveApp(EventPublisherInterface::class)
                                        ->fire('authentication', 'AuthenticationLoginWhenSelfExcludedEvent', [$user->getId()], 0);

                                    $_SESSION['skip_websocket_logout'] = false;
                                    phive('DBUserHandler')->logoutUser($user->getId(), 'locked');
                                }
                            }

                            if (lic('getLicSetting', ['cross_brand'], $user)['check_self_lock']) {
                                list($block, $result) = lic('hasInternalSelfLock', [$user], $user);
                                if ($block !== false) {
                                    phive('DBUserHandler')->logAction(
                                        $user,
                                        "Standard login: internal self-lock check failed",
                                        'failed_login_blocked'
                                    );

                                    phiveApp(EventPublisherInterface::class)
                                        ->fire(
                                            'authentication',
                                            'AuthenticationLoginWhenSelfLockedEvent',
                                            [$user->getId()],
                                            0
                                        );

                                    $_SESSION['skip_websocket_logout'] = false;
                                    phive('DBUserHandler')->logoutUser($user->getId(), 'locked');
                                }
                            }

                            $this->syncRemoteSettingsAfterRegistration($user);
                            $this->syncRgLimitsOnRegistration($user);
                            $this->setDocumentStatusSetting($user);

                            if (rgLimits()->shouldApplyFixedCycleTimestamp($user)) {
                                rgLimits()->getOrSetFixedCycleResetsAtSourceTimestamp($user);
                            }

                            phive('Cashier/Aml')->performThresholdChecks($user);

                            $ret = $link_result;
                        }

                        $user->deleteSetting('cross_brand_check_block');
                        $user->deleteSetting('brand_link_max_retry');
                    } else {
                        $message = "User {$user->getId()} failed while matching remote brand account.";
                        if (!empty($res['result']['error']) && $res['result']['error'] === "processing") {
                            if (!$user->hasCompletedRegistration()) {
                                $this->deleteCrossBrandSettings($user);
                                return false;
                            }

                            if ($account_linking_retry_delay_in_microseconds != 0 && $retry_attempt >= 1) {
                                usleep($account_linking_retry_delay_in_microseconds); // delay for SCV microservice
                            }

                            $message .= " Retrying attempt #{$retry_attempt}";
                            $this->getSitePublisher()
                                ->fire(
                                    'distributed-retry',
                                    'Site/Linker',
                                    'brandLink',
                                    [$user->getId(), $check_self_exclusion, ++$retry_attempt]
                                );
                        }

                        phive('Logger')->getLogger('brand_link')->warning(
                            $message,
                            ['response' => $res]
                        );
                    }
                } else {
                    throw new RuntimeException("Remote brand is not responding {$ping_remote['result']}");
                }
            } else {
                //no cross-brand active
                $this->deleteCrossBrandSettings($user);

                if (rgLimits()->shouldApplyFixedCycleTimestamp($user)) {
                    rgLimits()->getOrSetFixedCycleResetsAtSourceTimestamp($user);
                }
            }
        } catch (\Exception $e) {
            phive('Logger')->getLogger('brand_link')->error(
                "Error brandLink. Retrying attempt #{$retry_attempt}",
                [
                    'check_self_exclusion' => $check_self_exclusion,
                    'user'                 => json_encode($user_temp_store),
                    'error'                => $e->getMessage(),
                ]
            );

            if (!$user->hasCompletedRegistration()) {
                $this->deleteCrossBrandSettings($user);
                return false;
            }

            if ($account_linking_retry_delay_in_microseconds != 0 && $retry_attempt >= 1) {
                usleep($account_linking_retry_delay_in_microseconds); // delay for SCV microservice
            }
            $this->getSitePublisher()
                ->fire('distributed-retry', 'Site/Linker', 'brandLink', [uid($user_temp_store), $check_self_exclusion, ++$retry_attempt]);
        }

        return $ret;
    }

    /**
     *
     * @param DBUser $user
     * @return void
     */
    public function deleteCrossBrandSettings($user): void
    {
        $user->deleteSetting('brand_check_in_progress');
        $user->deleteSetting('cross_brand_check_block');
        $user->deleteSetting('brand_link_max_retry');
    }

    /**
     * Check whether the jurisdiction of the user allows brand-linking
     * @param DBUser $user
     * @return bool
     */
    public function isAllowedToLinkAccounts($user): bool
    {
        $cross_brand = licSetting('cross_brand', $user);
        if (empty($cross_brand)) {
            return false;
        }
        if (empty($cross_brand['do_brand_link'])) {
            return false;
        }

        return true;
    }

    /**
     * @param $day_start
     * @param $day_end
     * @param int $verbosity
     * @param int $usleep
     */
    public function bulkBrandLink($day_start, $day_end = null, $verbosity = 0, $usleep = 200000)
    {
        $remote = $this->getSetting('registration_link_remote', false);
        if (empty($remote)) {
            return;
        }

        if (empty($day_end)) {
            $day_end = $day_start;
        }

        $extra_where = '';
        if (!empty($countries = $this->getSetting('registration_link_blocked_countries', []))) {
            $extra_where = " AND u.country NOT IN (". $this->db->makeIn($countries) .")";
        }

        $query = "SELECT u.id, u.email, u.country, u.register_date, us.setting, us.value, u.nid
                    FROM users AS u
                     LEFT JOIN users_settings us on u.id = us.user_id AND us.setting = '". distKey($remote) ."'
                    WHERE u.register_date BETWEEN '{$day_start}' AND '{$day_end}'
                        AND u.firstname != '' AND u.lastname != '' {$extra_where}
                    GROUP BY u.id;";

        foreach (phive('SQL')->shs()->loadArray($query) as $elem) {
            $user = cu($elem['id']);

            //We ignore accounts for bulk if they are test accounts or they need nid but nid is empty
            if ($user->isTestAccount() || (lic('needsNid', [$user], $user) && empty($elem['nid']))) {
                continue;
            }

            if ($this->isAllowedToLinkAccounts($user) === false) {
                continue;
            }

            if (!empty($elem['value'])) {

                if (!empty($remote)) {
                    toRemote($remote, 'confirmMovedPlayer', [$elem['value'], distId(), $elem['id']]);
                }

                if ($verbosity == 2) {
                    echo "Existing link: " . json_encode($elem) . "\n";
                }

            } else {
                $this->brandLink(
                    $user,
                    empty(licSetting('cross_brand', $user)['check_self_exclusion']) ? 'no' : 'yes'
                );

                if ($verbosity == 2) {
                    echo "Full link: " . json_encode($elem) . "\n";
                }
            }

            if ($verbosity == 1) {
                echo "+";
            }

            usleep($usleep);
        }
    }


    /**
     * We match by a single user attribute.
     *
     * @param array $data User data and the attribute key
     * @return array
     */
    public function matchByAttribute($data)
    {
        $country_where = !empty($data['user_data']['country']) ? " AND country = '{$data['user_data']['country']}'" : '';
        $zipcode = !empty($data['user_data']['zipcode']) ? "AND zipcode = '{$data['user_data']['zipcode']}'" : '';

        $query = "SELECT id, email, nid, zipcode
                    FROM users
                  WHERE {$data['attribute']} = '{$data['user_data'][$data['attribute']]}'{$country_where}
                  {$zipcode}";

        $match_result = $this->matchReturn(phive('SQL')->shs()->loadArray($query));
        if (empty($match_result['matches']) && phive('SQL')->disabledNodeIsActive()) {
            $match_result = $this->matchReturn(phive('SQL')->onlyMaster()->loadArray($query));
        }
        return $match_result;
    }

    /**
     * We match following Gamstop matching criteria. We need to compare firstname, lastname, dob, email, postcode, mobile
     * Any 4, 5 or 6 way match will be considering a positive match.
     *
     * @param array $data
     * @return array|false[]
     */
    public function matchFullDetails($data)
    {
        $user_data =  (array)$data['user_data'];

        $user_data['zipcode'] = phive()->rmWhiteSpace($user_data['zipcode']);

        /**
         * Check if there is already 0 after country code.
         * If there is 0 after country code. Remove 0 to build alternative number. This will allow matching between numbers 44xxxx [Videoslots] and 440xxxx [MrVegas]
         * If there is no 0 after country code. Add 0 to build alternative number. This will allow matching between numbers like 440xxxx [Videoslots] and 44xxxx [MrVegas]
         *
         * Mobile match will be 1, if either original or modified mobile number is matched in DB.
         */
        $alternate_mobile = (strpos($user_data['mobile'], '0') === 2)
            ? substr_replace($user_data['mobile'], '', 2, 1)
            : substr_replace($user_data['mobile'], 0, 2, 0);

        $query = "SELECT (
                        (firstname = '{$user_data['firstname']}') +
                        (lastname = '{$user_data['lastname']}') +
                        (dob = '{$user_data['dob']}') +
                        (REPLACE(zipcode , ' ', '') = '{$user_data['zipcode']}') +
                        (email = '{$user_data['email']}') +
                        (mobile = '{$user_data['mobile']}' OR mobile = '{$alternate_mobile}')
                    ) as matches, sub.*
                    FROM (
                        SELECT id, firstname, lastname, dob, zipcode, email, mobile, country
                        FROM users u
                        WHERE u.country = '{$user_data['country']}'
                        AND (
                            u.firstname = '{$user_data['firstname']}' OR
                            u.lastname = '{$user_data['lastname']}' OR
                            u.dob = '{$user_data['dob']}' OR
                            REPLACE(u.zipcode , ' ','') = '{$user_data['zipcode']}' OR
                            u.email = '{$user_data['email']}' OR
                            u.mobile = '{$user_data['mobile']}' OR u.mobile = '{$alternate_mobile}'
                        )
                    ) AS sub
                    HAVING matches >= 4;";

        $match_result = $this->matchReturn(phive('SQL')->shs()->loadArray($query));
        if (empty($match_result['matches']) && phive('SQL')->disabledNodeIsActive()) {
            $match_result = $this->matchReturn(phive('SQL')->onlyMaster()->loadArray($query));
        }
        return $match_result;
    }

    /**
     * Helper function to process different type of matching functions.
     *
     * @param array $result Results from the query
     * @return array
     */
    private function matchReturn($result)
    {
        usort($result, function ($item1, $item2) {
            return $item2['matches'] <=> $item1['matches'];
        });

        $matches = [];
        foreach ($result as $elem) {
            $matches[$elem['id']] = $elem['matches'];
        }

        return ['main_match_id' => $result[0]['id'], 'matches' => $matches];
    }

    /**
     * Check if remote user has deposit or play blocks
     * @param $user
     */
    public function syncRemoteSettingsAfterRegistration($user): void
    {
        $settings = lic('getLicSetting', ['cross_brand'], $user->getId())['sync_settings_with_remote_brand_after_registration'];
        $remote_brand = getRemote();
        foreach ($settings as $setting) {
            $setting_exists_remote = toRemote(
                $remote_brand,
                'getRemoteSetting',
                [$user->getRemoteId(), $setting]
            );

            if ($setting_exists_remote['success']) {
                $user->setSetting($setting, $setting_exists_remote['result']);
                phive('DBUserHandler')->logAction(
                    $user->getId(),
                    "Synchronized {$setting} with {$remote_brand}",
                    'set-setting'
                );
            }
        }
    }


    /**
     * Set document status settings
     *
     * @param DBUser $user
     */
    public function setDocumentStatusSetting(DBUser $user)
    {
        $documents_to_sync = lic('getDocumentsToSync', [], $user);
        $remote_brand = getRemote();
        $remote_user_id = $user->getRemoteId();
        $user_id = $user->getId();

        $local_documents = Phive('Dmapi')->getUserDocumentsV2($user_id);
        if (empty($local_documents)) {
            usleep(0.5 * 1000 * 1000);

            $local_documents = Phive('Dmapi')->getUserDocumentsV2($user_id);
        }

        $remote_doc_response = toRemote(
            $remote_brand,
            'getRemoteDocuments',
            [$remote_user_id]
        );
        if (empty($remote_doc_response['success'])) {
            usleep(0.5 * 1000 * 1000);

            $remote_doc_response = toRemote(
                $remote_brand,
                'getRemoteDocuments',
                [$remote_user_id]
            );
        }

        if ($remote_doc_response['success'] === false) {
            // its a single brand account, so return
            return;
        }

        foreach ($documents_to_sync as $tag) {
            $doc_type_setting_name = phive('Dmapi')->getSettingNameForDocumentType($tag);

            $local_latest = $this->getLatestLocalDocumentFile($local_documents, $tag);
            $local_latest_file = $local_latest['file'];
            $local_document_status = $local_latest['status'];
            $local_doc_brand = $local_latest['brand'];

            $remote_latest = ['file' => [], 'status' => 'requested'];
            if ($remote_doc_response['success'] === true) {
                $remote_latest = $this->getLatestRemoteDocumentFile($remote_doc_response['result'], $tag);
            }
            $remote_latest_file = $remote_latest['file'];
            $remote_document_status = $remote_latest['status'];
            $remote_doc_brand = $remote_latest['brand'];

            if (empty($local_latest_file) && empty($remote_latest_file)) {
                $this->updateDocumentSetting($user, $doc_type_setting_name, $local_document_status, $local_doc_brand);
                continue;
            }

            if (empty($local_latest_file)) {
                $this->updateDocumentSetting($user, $doc_type_setting_name, $remote_document_status, $remote_doc_brand);
                continue;
            }

            if (empty($remote_latest_file)) {
                $this->updateDocumentSetting($user, $doc_type_setting_name, $local_document_status, $local_doc_brand);
                continue;
            }

            // Check and parse with default fallback to avoid exceptions
            $local_file_updated_at = !empty($local_latest_file['updated_at'])
                ? Carbon::parse($local_latest_file['updated_at'])
                : Carbon::minValue();

            $remote_file_updated_at = !empty($remote_latest_file['updated_at'])
                ? Carbon::parse($remote_latest_file['updated_at'])
                : Carbon::minValue();

            if ($local_file_updated_at->greaterThanOrEqualTo($remote_file_updated_at)) {
                $this->updateDocumentSetting($user, $doc_type_setting_name, $local_document_status, $local_doc_brand);
                continue;
            }

            if ($local_file_updated_at->lessThan($remote_file_updated_at)) {
                $this->updateDocumentSetting($user, $doc_type_setting_name, $remote_document_status, $remote_doc_brand);
            }
        }

        $user->updateCDDFlagOnDocumentStatusChange();
    }

    /**
     * Get the latest document files of $document_tag from local
     *
     * @param $local_documents array The list of local documents
     * @param $document_tag string The document tag to filter for
     * @return array
     */
    public function getLatestLocalDocumentFile($local_documents, $document_tag)
    {
        $doc_index = array_search($document_tag, array_column($local_documents, 'tag'));
        $local_document = $doc_index !== false ? $local_documents[$doc_index] : null;
        $local_latest['file'] = ($local_document && isset($local_document['files'])) ? array_pop($local_document['files']) : [];
        $local_latest['status'] = $local_document['status'] ?? 'requested';
        $local_latest['brand'] = getLocalBrand();

        return $local_latest;
    }

    /**
     * Get the latest document files of $document_tag from remote
     *
     * @param $remote_documents array The list of remote documents
     * @param $document_tag string The document tag to filter for
     * @return array
     */
    protected function getLatestRemoteDocumentFile($remote_documents, $document_tag)
    {
        $remote_latest_docs = [];
        foreach ($remote_documents as $brand => $documents) {
            $filtered_doc = array_filter($documents, function ($doc) use ($document_tag) {
                return $doc['tag'] === $document_tag && isset($doc['files']);
            });

            if (empty($filtered_doc)) {
                continue;
            }

            $filtered_doc = array_pop($filtered_doc);
            $remote_files = $filtered_doc['files'];
            $remote_files['remote_doc_status'] = $filtered_doc['status'];
            $remote_files['brand'] = $brand;

            $remote_latest_docs[] = $remote_files;
        }

        // get the latest remote file
        usort($remote_latest_docs, function ($a, $b){
            return Carbon::parse($a['updated_at'])->greaterThan(Carbon::parse($b['updated_at'])) ? -1 : 1;
        });

        $latest_file = $remote_latest_docs[0];
        $status = $latest_file['remote_doc_status'];
        $brand = $latest_file['brand'];
        unset($latest_file['remote_doc_status']);
        unset($latest_file['brand']);

        $remote_latest['file'] = $latest_file;
        $remote_latest['status'] = $status;
        $remote_latest['brand'] = $brand;

        return $remote_latest;
    }

    /**
     * Sets or deletes document status setting on local and remote brands
     * The document status setting shouldn't be present on the brand in which the document was uploaded.
     * It should be present on all other brands.
     *
     * @param $user DBUser user object
     * @param $doc_setting string document status setting to be updated
     * @param $value string value of the document status setting
     * @param $original_doc_brand string brand in which the latest document was uploaded
     * @return void
     */
    function updateDocumentSetting($user, $doc_setting, $value, $original_doc_brand)
    {
        $local_brand = getLocalBrand();
        $remote_brand = getRemote();
        $remote_user_id = $user->getRemoteId();

        if (!in_array($doc_setting, lic('getDocumentsSettingToSetOnlyOnRemote', [], $user))) {
            return;
        }

        if ($local_brand === $original_doc_brand) {
            $user->deleteSetting($doc_setting);
            toRemote(
                $remote_brand,
                'updateDocumentStatusSettingOnRemoteBrand',
                [$remote_user_id, $doc_setting, $value, $original_doc_brand]
            );
            phive('DBUserHandler')->logAction(
                $user,
                "Set document status to {$value} on {$remote_brand} on brandlink",
                $doc_setting
            );
            return;
        }

        $user->refreshSetting($doc_setting, $value);
        toRemote(
            $remote_brand,
            'updateDocumentStatusSettingOnRemoteBrand',
            [$remote_user_id, $doc_setting, $value, $original_doc_brand]
        );
        phive('DBUserHandler')->logAction(
            $user,
            "Set document status to {$value} on brandlink",
            $doc_setting
        );
    }

    public function syncRgLimitsOnRegistration(DBUser $user): void
    {
        $limits = lic('getLicSetting', ['cross_brand'], $user->getId())['rg_limits_to_sync_on_registration'];
        $remote_brand = getRemote();
        $remote_user_id = $user->getRemoteId();

        foreach ($limits as $type) {
            if (!lic('shouldSyncLimitOnRegistration', [$type], $user)) {
                return;
            }

            $remote_user_limit = toRemote(
                $remote_brand,
                'getRemoteUserLimit', [
                $remote_user_id,
                $type,
                'month'
            ]);

            if (empty($remote_user_limit['success']) || empty($remote_user_limit['result']['id'])) {
                return;
            }
            $limit = $remote_user_limit['result'];
            rgLimits()->addLimit(
                $user,
                $type,
                'month',
                $limit['cur_lim'],
                null,
                false,
                $limit['progress']
            );
        }
    }
}
