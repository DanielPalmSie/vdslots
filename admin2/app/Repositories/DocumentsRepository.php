<?php

namespace App\Repositories;

use App\Classes\Mts;
use App\Models\User;
use App\Models\UserSetting;
use Carbon\Carbon;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Valitron\Validator;
use App\Models\IpLog;
use App\Models\Config;
use Videoslots\FraudDetection\FraudFlags\SourceOfFundsRequestedFlag;
use Videoslots\FraudDetection\RevokeEvent;


class DocumentsRepository
{
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Gets all documents with a certain status.
     * Used on page document-list, which lists all processing documents.
     *
     * @param string $status
     * @return array
     */
    public function getDocumentsByStatus($status)
    {
        $documents = phive('Dmapi')->getDocumentsByStatus($status);

        $document_list = [];
        foreach ($documents as $document) {
            $document_tag = str_replace('-pic', '', ucfirst($document['tag']));
            $document_tag = str_replace('pic', '', $document_tag);

            $document_list[$document_tag][$document['user_id']]['username'] = $document['user_id'];

            // add timestamp of the last uploaded file, use attribute updated_at for this
            if (!empty($document['files'])) {
                $timestamp = $this->getTimestampOfLastUploadedFile($document['files']);
                $document_list[$document_tag][$document['user_id']]['timestamp'] = $timestamp;
            } elseif ($document['tag'] == 'sourceoffundspic') {
                // get the timestamp from source_of_funds_data
                $document_list[$document_tag][$document['user_id']]['timestamp'] = $document['source_of_funds_data']['created_at'];
            } else {
                $document_list[$document_tag][$document['user_id']]['timestamp'] = 'unknown';
            }
        }

        // Sort each list on timestamp
        foreach ($document_list as $document_type => &$user_list) {
            usort($user_list, function ($a, $b) {
                return strtotime($a['timestamp']) - strtotime($b['timestamp']);
            });
        }

        return $document_list;
    }

    /**
     *
     * @param User $user
     * @param Request $request
     * @return string  Returns the JSON that will be returned by the controller action
     */
    public function updateDocumentStatus($app, $user, $request)
    {
        if(!$this->validateUpdateRequest($request)) {
            return json_encode(['success' => false, 'message' => 'Something went wrong, please try again']);
        }

        $status        = $request->get('status');
        $actor_id      = UserRepository::getCurrentId();
        $document_id   = $request->get('document_id');
        $document_type = $request->get('document_type');
        $reject_reason = $request->get('reject_reason');

        if(!$this->hasUpdatePermission($status)) {
            return json_encode(['success' => false, 'message' => "You do not have permission for this action"]);
        }

        $response = phive('Dmapi')->updateDocumentStatus($actor_id, $document_id, $status, $user->id);

        if (empty($response)
            || (!empty($response['errors']))
        ) {
            return json_encode(['success' => false, 'message' => $response['errors'][0]]);
        }

        if($document_type == 'sourceofincomepic') {
            $user_repo = new UserRepository($user);
            $user_repo->setSetting('source_of_income_status', $status);
        }

        if($document_type == 'sourceoffundspic') {
            $user_repo = new UserRepository($user);
            $u_obj = cu($user->id);
            $user_repo->deleteSetting('source_of_funds_self_approval');
            $user_repo->deleteSetting('source_of_funds_admin_approval');

            if($status == 'approved') {
                $user_repo->deleteSetting('source_of_funds_waiting_since');
                $user_repo->deleteSetting('source_of_funds_status');
                SourceOfFundsRequestedFlag::create()->revoke($u_obj, RevokeEvent::ON_DOC_APPROVED_BO);
                $user_repo->deleteSetting('sowd-enforce-verification');
                $u_obj->unRestrict();
                $restriction_reason = phive('DBUserHandler')->doCheckRestrict($u_obj);

                if ($restriction_reason) {
                    $u_obj->restrict($restriction_reason);
                }

//          } elseif($status == 'archived') {
//               $user_repo->setSetting('source_of_funds_status', 'requested');  // redundant
            } else {
                $user_repo->setSetting('source_of_funds_status', $status);
                if($status == 'rejected') {
                    $first_visit_on_withdraw = $user_repo->getSetting('source_of_funds_waiting_since');
                    // if is empty it mean it was first approved then rejected, so we reset the timer and user has 30 days before being fully restricted
                    if(empty($first_visit_on_withdraw)) {
                        $first_visit_on_withdraw = Carbon::now()->toDateTimeString();
                        $user_repo->setSetting('source_of_funds_waiting_since', $first_visit_on_withdraw);
                    }
                    // if more than 30 days has passed we enforce SOWD verification
                    // (Ex. user visit withdraw, fill the SOWD after 10 days, is rejected, he still has 20 days before being restricted)
                    if(Carbon::parse($first_visit_on_withdraw)->lte(Carbon::now()->subDays(30))) {
                        $user_repo->setSetting('sowd-enforce-verification', 1);
                    }
                }
            }
        }

        // Do not send emails for internaldocumentpic, these are for internal use only, and the player does not see the document
        if($document_type != 'internaldocumentpic') {
            $this->sendEmailToUser($user, $status, $document_type, $reject_reason);
        }

        // Log this action
        $description = "Document status updated to {$status} for {$document_type} (document_id = {$document_id})";
        $subtag  = $response[$document_id]['subtag'];
        if(!empty($subtag)) {
            $description .= " with subtag = {$subtag}";
        }

        // Set the tag, example document-approved, document-rejected, document-deactivated, document-activated
        $status_tag = $this->getDocumentStatusTag($status, 'document');

        ActionRepository::logAction($user->id, $description, $status_tag, false, $actor_id);
        IpLog::logIp($actor_id, $user->id, IpLog::TAG_DOCUMENTS, $description);

        $app['flash']->add('success', $this->getSuccessMessage($status));

        $u_obj = cu($user->id);
        if($status =='rejected'){
            $restriction_reason = phive('DBUserHandler')->doCheckRestrict($u_obj);

            if ($restriction_reason) {
                $u_obj->restrict($restriction_reason);
                $this->app['flash']->add('info', 'User was restricted by this action');
            }
        } else{
            phive('DBUserHandler')->doCheckUnrestrict($u_obj, $document_type);
        }

        lic('onDocumentStatusChange', [$user->getKey(), $document_type, $status], $user->getKey());

        lic('onUploadDocument', [$user->getKey()], $user->getKey());

        if(
            $document_type === 'bankaccountpic'
            && $request->get('supplier') === 'trustly'
            && $request->get('status') === 'approved'
        ) {
            $mts = new Mts($app);

            if ($request->get('account_ext_id')) {
                $mts->approveAccount($request->get('account_id'));

                return json_encode(['success' => true, 'message' => $this->getSuccessMessage($status), 'document' => $response]);
            }

//            TODO: Register account disabled https://videoslots.atlassian.net/browse/BAN-11430
//            $accountRequestData = $request->get('add_bank_account_request_data');
//            $data = [
//                "user_id" => $user->id,
//                "first_name" => $user->first_name,
//                "last_name" => $user->last_name,
//
//                "country" => $user->country,
//                "zip_code" => $user->zipcode,
//                "city" => $user->city,
//                "address" => $user->address,
//
//                "mobile" => $user->mobile,
//                "email" => $user->email,
//                "dob" => $user->dob,
//
//                "clearing_house" => $accountRequestData['clearing_house'],
//                "account_number" => $accountRequestData['account_number']
//            ];
//
//            if ($user->getNid()) {
//                $data['nid'] = $user->getNid();
//            }
//
//            if (!empty($accountRequestData['bank_number'])) {
//                $data['bank_number'] = $accountRequestData['bank_number'];
//            }
//
//            $registerAccountResponse = $mts->registerAccount($request->get('supplier'), $data);
//
//            if ($registerAccountResponse['success']) {
//                $accountResponseData = $registerAccountResponse['data']['account'] ?? [];
//
//                phive('Dmapi')->updateDocumentColumns(
//                    [
//                        'external_id' => $accountResponseData['id'],
//                        'subtag' => $accountResponseData['partial_source_id'],
//                        'sub_supplier' => $accountResponseData['sub_supplier'],
//                        'account_ext_id' => $accountResponseData['external_id'],
//                    ],
//                    $document_id,
//                    $user->id
//                );
//            }
        }

        return json_encode(['success' => true, 'message' => $this->getSuccessMessage($status), 'document' => $response]);
    }

    /**
     *
     * @param Request $request
     * @return boolean
     */
    public function validateUpdateRequest($request)
    {
        if ($request->isMethod('post')) {
            $data = $request->request->all();
        }
        if ($request->isMethod('get')) {
            $data = $request->query->all();
        }
        $validator = new Validator($data);
        $validator->rule('required', ['status', 'document_id', 'document_type']);
        $validator->rule('integer', ['document_id']);
        $validator->rule('slug', ['status', 'document_type']);
        if($validator->validate()) {
            return true;
        }
        return false;
    }

    /**
     *
     * @param Request $request
     * @return boolean
     */
    public function validateUpdateFileStatusRequest($request)
    {
        if ($request->isMethod('post')) {
            $data = $request->request->all();
        }
        if ($request->isMethod('get')) {
            $data = $request->query->all();
        }
        $validator = new Validator($data);
        $validator->rule('required', ['status', 'file_id', 'document_type']);
        $validator->rule('integer', ['file_id']);
        $validator->rule('slug', ['status', 'document_type']);
        if($validator->validate()) {
            return true;
        }
        return false;
    }


    /**
     * Checks if a user has permission for a specific update action
     *
     * @return boolean
     */
    public function hasUpdatePermission($status)
    {
        switch ($status) {
            case 'rejected':
                if ((p('user.reject.pic'))) {
                    return true;
                }
                break;

            case 'approved':
                if ((p('user.verify'))) {
                    return true;
                }
                break;

            case 'deactivated':     // only used for credit card documents
                if ((p('ccard.deactivate'))) {
                    return true;
                }
                break;

            case 'active':          // only used for credit card documents
                if ((p('ccard.deactivate'))) {
                    return true;
                }
                break;

            case 'archived':        // only used when re-requesting Source of Wealth documents
                if ((p('documents.create.sourceoffunds'))) {
                    return true;
                }
                break;

            default:
                return false;
        }
    }

    /**
     *
     * @param string $status
     * @return string
     */
    public function getSuccessMessage($status)
    {
        switch ($status) {
            case 'deactivated':     // only used for credit card documents
                $success_message = 'Credit card deactivated, and document status updated';
                break;

            case 'active':          // only used for credit card documents
                $success_message = 'Credit card activated, and document status updated';
                break;

            default:
                $success_message = "Document status updated.";
        }

        return $success_message;
    }

    /**
     * Sends an email to the user to inform him of a status change
     *
     * @param User      $user
     * @param string    $status
     * @param string    $document_type
     * @return boolean
     */
    public function sendEmailToUser($user, $status, $document_type, $reject_reason = '')
    {
        $replacer_repository = new ReplacerRepository();

        $phive_user = phive('UserHandler')->getUser($user->id);

        switch ($status) {
            case 'rejected':
                $mail_alias = $document_type . '.picrejected';

                // idpic and addresspic are different
                if($document_type == 'addresspic') {
                    $mail_alias = 'address.picrejected';
                } elseif($document_type == 'idcard-pic') {
                    $mail_alias = 'id.picrejected';
                } elseif($document_type == 'skrillpic') {
                    $mail_alias = 'mbpic.picrejected';
                } elseif($document_type == 'creditcardpic') {
                    $mail_alias = 'ccard.picrejected';
                    // temp do not send email when credit card is rejected
                    break;
                }

                $reject_reason = '';

                if(phive()->moduleExists("MailHandler2")){
                    $replacers = $replacer_repository->getDefaultReplacers($user);

                    $emailresult = phive("MailHandler2")->sendMail($mail_alias, $phive_user, $replacers);
                }

                break;
            case 'approved':
                $mail_alias = $document_type . '.verified';

                // idpic, addresspic, netellerpic, and skrillpic are different
                if($document_type == 'addresspic') {
                    $mail_alias = 'address.verified';
                } elseif($document_type == 'idcard-pic') {
                    $mail_alias = 'account.verified';
                } elseif($document_type == 'skrillpic') {
                    $mail_alias = 'mbpic.verified';
                } elseif($document_type == 'creditcardpic') {
                    $mail_alias = 'ccard.verified';
                }

                if(phive()->moduleExists("MailHandler2") && !empty($mail_alias)) {
                    $replacers = $replacer_repository->getDefaultReplacers($user);

                    $emailresult = phive("MailHandler2")->sendMail($mail_alias, $phive_user, $replacers);
                }
                break;
            default:
                break;
        }

        return $emailresult;
    }

    /**
     * Tries to get the timestamp of the last uploaded file by looking at the updated_at attribute of the files
     *
     * @param array $files
     * @return string
     */
    private function getTimestampOfLastUploadedFile($files)
    {
        $timestamp = '';
        foreach ($files as $file) {
            if($file['updated_at'] > $timestamp) {
                $timestamp = $file['updated_at'];
            }
        }

        return $timestamp;
    }


    public function getRejectReasons($document_type = '')
    {
        // TODO: where to keep the reject reasons? In config table , or a new table ?

        // Problem = we have a different localized string for each document type

        // TODO: only show relevant reasons, POA only for POA, Wrong Card Info only for credit cards


        $reject_reasons = [
            'rejected_not_clear'              => 'Rejected Document Not Clear',
            'rejected_duplicate_document'     => 'Rejected Duplicate Document',
            'rejected_document_cropped'       => 'Rejected Document is Cropped',
            'rejected_wrong_card_info'        => 'Rejected Wrong Card Info',
            'rejected_not_needed'             => 'Rejected as Doc Not Needed',
            'rejected_doc_type_not_supported' => 'Rejected as Doc Type Not Supported for Verification',
            'rejected_black_and_white'        => 'Rejected as Document is B/W',
            'rejected_poa_too_old'            => 'Rejected as POA is older than 3 months',
            'rejected_no_name_visible'        => 'Rejected as no Name visible on document',
            'rejected_document_expired'       => 'Rejected Document is Expired',
            'rejected_details_are_3rd_party'  => 'Rejected as Doc details are 3rd Party',
        ];

        return $reject_reasons;
    }

    /**
     * Validate an uploaded file
     *
     * @return string
     */
    public function validateUploadedFiles()
    {
        // make sure at least 1 file is uploaded
        $filecount = 0;

        // only allow files from certain extensions to be uploaded
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf', 'bmp', 'tiff'];
        $allowed_mime_types = ['image/jpeg', 'image/png', 'application/pdf', 'image/bmp', 'image/tiff', 'image/x-ms-bmp'];
        $max_size           =  10 * 1024 * 1024; // 10485760 | 10 mb
        $max_size_mb        = 10;

        $error = '';
        foreach ($_FILES as $file) {
            if(!empty($file['error'])) {
                $error = $file['error'];
                return $this->errorcodeToMessage($error, $max_size_mb);
            }
            if(!empty($file['tmp_name'])) {
                $filecount++;

                if($file['size'] == 0) {
                    $error = 'File is empty (0 KB)';
                } elseif($file['size'] > $max_size) {
                    $error = "File size too big, max is {$max_size_mb}MB";
                } else {
                    $info      = pathinfo($file['name']);
                    $extension = strtolower($info['extension']);
                    $mime_type = mime_content_type($file['tmp_name']);

                    if(!in_array($mime_type, $allowed_mime_types)) {
                        $error = 'File type not allowed, allowed types jpg, jpeg, png, pdf, bmp, tiff (mime type not correct)';
                    }
                    if(!in_array(strtolower($extension), $allowed_extensions)) {
                        $error = 'File type not allowed, allowed types jpg, jpeg, png, pdf, bmp, tiff';
                    }
                }
            }
        }

        if($filecount == 0) {
            $error = 'No files uploaded';
        }
        return $error;
    }

    /**
     *
     * @param int $code
     * @param int $max_size_mb
     * @return string
     */
    private function errorcodeToMessage($code, $max_size_mb)
    {
        $error_messages = [
            0 => 'There is no error, the file uploaded with success',
            1 => "File size too big, max is {$max_size_mb}MB",
            2 => "File size too big, max is {$max_size_mb}MB",
            3 => 'The uploaded file was only partially uploaded',
            4 => 'No file was uploaded',
            6 => 'Missing a temporary folder',
            7 => 'Failed to write file to disk.',
            8 => 'A PHP extension stopped the file upload.',
        ];

        if(!isset($error_messages[$code])) {
            return 'Unknown upload error';
        }

        return $error_messages[$code];
    }

    /**
     * Checks if a user has a Wealth of Funds document by looping through his documents.
     *
     * @param array $documents
     * @return boolean
     */
    public function hasSourceOfFundsDocument($documents)
    {
        foreach ($documents as $document) {
            if($document['tag'] == 'sourceoffundspic') {
                return true;
            }
        }

        return false;
    }


    public function getSourceOfFundsDocumentId($documents)
    {
        foreach ($documents as $document) {
            if($document['tag'] == 'sourceoffundspic') {
                return $document['id'];
            }
        }

        return false;
    }

    /**
     * Checks if a user has Wealth of Funds Data by looping through his documents.
     *
     * @param array $documents
     * @return boolean
     */
    public function hasSourceOfFundsData($documents)
    {
        foreach ($documents as $document) {
            if($document['tag'] == 'sourceoffundspic' && !empty($document['source_of_funds_data'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if a user has a source of income document by looping through his documents.
     *
     * @param array $documents
     * @return boolean
     */
    public function hasSourceOfIncomeDocument($documents)
    {
        foreach ($documents as $document) {
            if($document['tag'] == 'sourceofincomepic') {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if a user has Historical Source of Wealth Data by looping through his documents.
     *
     * @param array $documents
     * @return boolean
     */
    public function hasHistoricalSourceOfFundsData($documents)
    {
        foreach ($documents as $document) {
            if($document['tag'] == 'sourceoffundspic' && !empty($document['historical_source_of_funds_data'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if a user has an Internal Document by looping through his documents.
     *
     * @param array $documents
     * @return boolean
     */
    public function hasInternalDocument($documents)
    {
        foreach ($documents as $document) {
            if($document['tag'] == 'internaldocumentpic') {
                return true;
            }
        }
    }

    /**
     * Checks if a user has an Proof Of Wealth Document by looping through his documents.
     *
     * @param array $documents
     * @return boolean
     */
    public function hasProofOfWealthDocument($documents)
    {
        foreach ($documents as $document) {
            if($document['tag'] == 'proofofwealthpic') {
                return true;
            }
        }
    }

    /**
     * Checks if a user has an Proof Of Wealth Document by looping through his documents.
     *
     * @param array $documents
     * @return boolean
     */
    public function hasProofOfSourceOfFundsDocument($documents)
    {
        foreach ($documents as $document) {
            if($document['tag'] == 'proofofsourceoffundspic') {
                return true;
            }
        }
    }

    /**
     * Check if we need to show the buttons to create Source of Wealth documents for a user,
     * based on the configured countries.
     * If no countries are configured, or the config does not exist, we show the buttons to all users.
     *
     * @param User $user
     * @return boolean
     */
    public function showSourceOfWealthButtons($user)
    {
        return $this->showButtons($user, 'source_of_wealth_countries');
    }

    /**
     * Check if we need to show the Re-request button for the Source of Wealth document
     *
     * @param array $documents
     * @return boolean
     */
    public function showReRequestSourceOfWealthButton($documents)
    {
        foreach ($documents as $document) {
            if($document['tag'] == 'sourceoffundspic' && $document['status'] == 'approved') {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if we need to show the buttons to create Proof of Wealth documents for a user,
     * based on the configured countries.
     * If no countries are configured, or the config does not exist, we show the buttons to all users.
     *
     * @param User $user
     * @return boolean
     */
    public function showProofOfSourceOfWealthButtons($user)
    {
        return $this->showButtons($user, 'proof_of_source_of_wealth_countries');
    }

    /**
     *
     * @param User $user
     * @param string $config_name
     * @return boolean
     */
    private function showButtons($user, $config_name)
    {
        // get config value
        $configured_countries = Config::where('config_name', $config_name)
                ->where('config_tag', 'documents')
                ->first();

        if(empty($configured_countries->config_value)) {
            return true;
        }

        $countries = explode(' ', $configured_countries->config_value);
        if(in_array($user->country, $countries)) {
            return true;
        }

        return false;
    }

    /**
     * Get a list of all unverified Source of Wealth and Proof of Wealth documents,
     * sorted by status and waiting time since last update.
     *
     * NOTE, for now the Dmapi returns only requested documents.
     *
     * @return array
     */
    public function getUnverifiedSourceOfFundsDocuments()
    {
        $documents = phive('Dmapi')->getUnverifiedSourceOfFundsDocuments();

        $now = time();
        $status_sortorder = [
            'requested' => 0,
            'processing'=> 1,
            'approved'  => 2,
            'rejected'  => 3,
        ];

        foreach ($documents as $key => &$document) {
            $user = User::find($document['user_id']);
            // Do not show blocked users on this list
            if ((!empty($user) && $user->active == 0) || empty($user)) {
                unset($documents[$key]);
                continue;
            }
            if (!empty($user)) {
                $document['username'] = $user->username;
                $document['country']  = $user->country;
                $document['currency'] = $user->currency;
            }

            $document['color'] = 'green';
            $document['frozen'] = false;
            // calculate wait time since the user visited the withdrawal page and was prompted with the Source of Funds declaration form
            $waiting_since = $user->getSetting('source_of_funds_waiting_since');
            if(empty($waiting_since) & $document['tag'] == 'sourceoffundspic') {
                if($document['status'] == 'requested') {
                    $document['wait_time_formatted'] = 'Pending for user to visit Withdrawal page.';
                }
                if($document['status'] == 'rejected') {
                    $document['color'] = 'red';
                    $document['frozen'] = true;
                    $document['wait_time_formatted'] = 'Document was rejected, so user is forced to fill it again.';
                }
            } else{
                if ($document['tag'] == 'sourceoffundspic'){
                    $document['wait_time'] = $now - strtotime($user->getSetting('source_of_funds_waiting_since'));
                } else {
                    $document['wait_time'] = $now - strtotime($document['updated_at']);
                }
                $seconds = $document['wait_time'];
                $now2 = new \DateTime('@0');
                $updated_at = new \DateTime("@$seconds");
                $difference = $updated_at->diff($now2);
                if ($difference->format('%a') === '0') {
                    $document['wait_time_formatted'] = $difference->format('%hh %im');
                } else {
                    $document['wait_time_formatted'] = $difference->format('%a days %hh %im');
                }

                // use color if wait time longer then 30 days
                $sourceoffunds_expiry = Config::where('config_name', 'sourceoffunds_expiry')->where('config_tag', 'documents')->first();
                if ((int)$difference->format('%a') >= $sourceoffunds_expiry->config_value) {
                    $document['color'] = 'red';
                    $document['frozen'] = true;
                }
            }

            $document['status_sort_nr'] = $status_sortorder[$document['status']];

            // Rewrite tags
            $document['tag_formatted'] = $this->rewriteTag($document['tag']);

            // create arrays of colums in order to sort by column later
            $status[$key]    = $document['status_sort_nr'];
            $wait_time[$key] = $document['wait_time'];
        }

        // sort by column
        array_multisort($status, SORT_ASC, $wait_time, SORT_DESC, $documents);

        $split_docs = [
            'frozen' => [],
            'pending' => []
        ];
        foreach($documents as $doc) {
            if($doc['frozen']) {
                $split_docs['frozen'][] = $doc;
            } else {
                $split_docs['pending'][] = $doc;
            }
        }

        return $split_docs;
    }

    /**
     *
     * @param string $tag
     * @return string
     */
    private function rewriteTag($tag)
    {
        $map = [
            'sourceoffundspic'        => 'Source of Wealth',
            'proofofwealthpic'        => 'Proof of Wealth',
            'proofofsourceoffundspic' => 'Proof of Source of Funds',
        ];

        return empty($map[$tag]) ? '' : $map[$tag];
    }

    /**
     * Get the status tag for use in the actions table
     *
     * @param string $status
     * @param string $file_or_document  'file' or 'document'
     * @return string
     */
    public function getDocumentStatusTag($status, $file_or_document)
    {
        if($status == 'active') {
            $status_tag = $file_or_document . '-activated';
        } else {
            $status_tag = $file_or_document . '-' . $status;
        }

        return $status_tag;
    }

    /**
     * Filters out the cross-brand documents for the list of documents page
     *
     * @param array $documents_list
     * @return array
     */
    public function filterOutCrossBrandDocuments(array $documents_list): array
    {
        foreach ($documents_list as $headline => $list_to_filter) {
            $document_tag = $this->getDocumentTagFromHeadline($headline);
            $setting_name = phive('Dmapi')->getSettingNameForDocumentType($document_tag);


            $crossbranded_user_ids = UserSetting::shs()
                ->select('user_id')
                ->where('setting', '=', $setting_name)
                ->pluck('user_id')
                ->all();

            if (empty($crossbranded_user_ids)) {
                continue;
            }

            $documents_list[$headline] = array_filter($list_to_filter, static function ($document) use ($crossbranded_user_ids) {
                return !in_array($document['username'], $crossbranded_user_ids);
            });
        }

        return $documents_list;
    }

    /**
     * Converts user readable document headlines to the actual document tag name
     *
     * @param string $headline
     * @return string
     */
    public function getDocumentTagFromHeadline(string $headline): string
    {
        $headlines_to_document_tag_name_map_uniques = [
            'Idcard' => 'idcard-pic',

        ];

        return $headlines_to_document_tag_name_map_uniques[$headline] ?? strtolower($headline) . 'pic';
    }
}
