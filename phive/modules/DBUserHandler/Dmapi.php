<?php

/**
 * This class is for calling the Dmapi (document_service) to send newly uploaded documents, but also to upload public files and images.
 */

use IdScan\lib\FittedImage;
use Laraphive\Domain\Payment\Services\PspConfigServiceInterface;

require_once __DIR__ . '/../../api/PhModule.php';

// TODO refactor the calling code, no need to build the data and explicitly use Phive::post() in X places, do it like it is done in Mts.
class Dmapi extends PhModule
{
    public $settings;

    public $headers;

    public $document_service_base_url;

    // TODO, move this to the psp config array
    public $map = [
        'addresspic'           => 'addresspic',
        'bank'                 => 'bankpic',
        'bankpic'              => 'bankpic',
        'citadel'              => 'citadelpic',
        'creditcard'           => 'creditcardpic',
        'ccard'                => 'creditcardpic',
        'applepay'             => 'creditcardpic',
        'ecopayz'              => 'ecopayzpic',
        'emp'                  => 'creditcardpic',
        'idcard-pic'           => 'idcard-pic',
        'instadebit'           => 'instadebitpic',
        'mb'                   => 'skrillpic',
        'neteller'             => 'netellerpic',
        'skrill'               => 'skrillpic',
        'trustly'              => 'bankaccountpic',
        'swish'                => 'bankaccountpic',
        'paypal'               => 'paypalpic',
        'wirecard'             => 'creditcardpic',
        'worldpay'             => 'creditcardpic',
        'hexopay'              => 'creditcardpic',
        'sourceoffunds'        => 'sourceoffundspic',
        'entercash'            => 'bankaccountpic',
        'internaldocument'     => 'internaldocumentpic',
        'proofofwealth'        => 'proofofwealthpic',
        'proofofsourceoffunds' => 'proofofsourceoffundspic',
        'venuspoint'           => 'venuspointpic',
        'interac'              => 'interacpic',
        'muchbetter'           => 'muchbetterpic',
        'zimplerbank'          => 'bankaccountpic',
        'sepa'                 => 'bankaccountpic',
        'wpsepa'               => 'bankaccountpic',
        'mifinity'             => 'mifinitypic',
        'astropay'             => 'astropaypic',
        'astropaywallet'       => 'astropaypic',
        'credorax'             => 'creditcardpic',
        'sourceofincome'       => 'sourceofincomepic',
        'vega'                 => 'vegapic',
        'googlepay'            => 'creditcardpic',
    ];

    protected $tags_to_settings_map = [
        'idcard-pic' => 'poi_approved',
        'addresspic' => 'poa_approved',
    ];

    public const STATUS_APPROVED = 'approved';
    public const STATUS_REQUESTED = 'requested';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_APPROVED_ENUM = 2;
    public const STATUS_REQUESTED_ENUM = 0;

    const SAVE_DOCUMENT_RETRIES = 5;

    public function __construct()
    {
        $this->uh                        = phive('DBUserHandler');
        $this->document_service_base_url = $this->uh->getSetting('document_service_base_url');
        $this->settings['key']           = $this->uh->getSetting('videoslots_api_key');
        $this->headers                   = "X-API-KEY: " . $this->settings['key'] . "\r\nAccept: application/vnd.api+json";
        $this->timeout                   = $this->uh->getSetting('document_service_request_timeout');
        $this->upload_timeout            = $this->uh->getSetting('document_service_upload_request_timeout');
    }

    // filename or closure as argument
    function deletePubFile($arg)
    {
        if (phive('UserHandler')->getSetting('send_public_files_to_dmapi')) {
            $this->deletePublicFile('file_uploads', $arg);
        } else {
            // do normal delete here
            $arg();
        }
    }

    //args: $temp_file_location, $filename, $subfolder
    //or: $func
    function uploadPubFile($temp_file_location, $filename, $subfolder, $func = '')
    {
        $args = func_get_args();
        if (phive('UserHandler')->getSetting('send_public_files_to_dmapi')) {
            $this->uploadPublicFile($temp_file_location, 'file_uploads', $filename, $subfolder);
        } else {
            // do normal upload here
            $func();
        }
    }

    /**
     * Generates a unique reference for the current user
     *
     * @return string
     */
    public function generateMerchantScanReference($username)
    {
        $merchantIdScanReference = md5($username);

        return $merchantIdScanReference;
    }


    /**
     * Handle the posted data of the Proof of Identity
     * This is being used for the new backoffice document page
     *
     * @return array $errors
     */
    public function handlePostedIDV2($user_id)
    {

        // verify post data
        $errors = $this->validateUploadedID($_POST, $_FILES);

        if (!empty($errors)) {
            $result = ['errors' => $errors];

            return $result;
        }

        // move uploaded files to tmp location
        $filer = new Filer();
        $uploadpath = $filer->getSetting("UPLOAD_PATH");
        $uploaddir = $uploadpath . '/user-files/tmp/';
        $this->checkUploadPath($uploaddir);

        $files = [];

        // store files in temp local folder
        if (isset($_FILES['image-front']['name'])) {
            $image_front_filename = $uploaddir . $user_id . '_' . $_POST['idtype'] . '_front';
            if (!move_uploaded_file($_FILES['image-front']['tmp_name'], $image_front_filename)) {
                $errors[] = 'front.image.not.valid';
            } else {
                $files[] = array(
                    'original_name' => $_FILES['image-front']['name'],
                    'uploaded_name' => $_FILES['image-front']['name'],
                    'mime_type' => mime_content_type($image_front_filename),
                    'tag' => 'idcard-front',
                    'encoded_data' => base64_encode(file_get_contents($image_front_filename)),
                );
            }
        }

        $image_back_filename = '';
        if (isset($_FILES['image-back']['name'])) {
            $image_back_filename = $uploaddir . $user_id . '_' . $_POST['idtype'] . '_back';
            if (!move_uploaded_file($_FILES['image-back']['tmp_name'], $image_back_filename)) {
                $errors[] = 'back.image.not.valid';
            } else {
                $files[] = array(
                    'original_name' => $_FILES['image-back']['name'],
                    'uploaded_name' => $_FILES['image-back']['name'],
                    'mime_type' => mime_content_type($image_back_filename),
                    'tag' => 'idcard-back',
                    'encoded_data' => base64_encode(file_get_contents($image_back_filename)),
                );
            }
        }

        if (empty($errors)) {
            $result = ['files' => $files];
        } else {
            $result = ['errors' => $errors];
        }

        // remove files from tmp
        unlink($image_front_filename);
        if (!empty($image_back_filename)) {
            unlink($image_back_filename);
        }

        return $result;
    }


    public function handlePostedDocument($user_id)
    {
        // move uploaded files to tmp location
        $filer = new Filer();
        $uploadpath = $filer->getSetting("UPLOAD_PATH");
        $uploaddir = $uploadpath . '/user-files/tmp/';
        $this->checkUploadPath($uploaddir);

        $files = [];
        $errors = [];
        $filenames = [];
        foreach ($_FILES as $key => $file) {
            $image_filename = $uploaddir . $user_id . '_' . uniqid();
            $filenames[] = $image_filename;

            if (!empty($file['name']) && $file['size'] > 0) {
                if (!move_uploaded_file($file['tmp_name'], $image_filename)) {
                    $errors['image.not.valid'] = $file['name'];
                } else {
                    $files[] = array(
                        'original_name' => $file['name'],
                        'uploaded_name' => $image_filename,
                        'mime_type' => mime_content_type($image_filename),
                        'tag' => $_POST['document_type'],
                        'subtag' => isset($_POST['subtag']) ? $_POST['subtag'] : '',
                        'encoded_data' => base64_encode(file_get_contents($image_filename)),
                    );
                }
            }
        }

        if (empty($errors)) {
            $result = ['files' => $files];
        } else {
            $result = ['errors' => $errors];
        }

        // remove files from tmp
        foreach ($filenames as $filename) {
            unlink($filename);
        }

        return $result;
    }

    public function debugKey($key)
    {
        return phive('UserHandler')->getSetting('dmapi_debug') === true ? $key : '';
    }

    /**
     * Creates an empty document with status 'requested'
     * This function is to be used whenever a user makes a deposit
     * for the first time with a new bank, or when a user registers on the website.
     *
     * @param int       $user_id
     * @param string    $type
     * @param string    $scheme
     * @param string    $subtag         This can be used later to store the bank account number
     * @param int       $external_id
     * @param int       $actor_id
     * @param array     $extra          Extra info such as values that can be used to display for instance more information on a document.
     * @param string    $status         Initial status, defaults to requested.
     * @return void
     */
    public function createEmptyDocument($user_id, $type, $scheme = '', $subtag = '', $external_id = '', $actor_id = 0, $extra = [], $status = 'requested'): void
    {
        $document_type = $this->getDocumentTypeFromMap($type, $scheme);
        if (empty($document_type)) {
            return;
        }


        $scheme         = $scheme == 'none' ? '' : $scheme;
        $subtag         = $subtag == 'none' ? '' : $subtag;
        $external_id    = $external_id == 'none' ? '' : $external_id;

        $url = "{$this->document_service_base_url}documents/create-empty-document";

        $main_attributes = [
            'user_id'     => $user_id,
            'actor_id'    => $actor_id,
            'tag'         => $document_type,
            'subtag'      => $subtag,
            'external_id' => $external_id,
            'status'      => $status,
            'extra'       => $extra
        ];

        if (!empty($extra['supplier'])) {
            $main_attributes['supplier'] = $extra['supplier'];
        }

        if (!empty($extra['sub_supplier'])) {
            $main_attributes['sub_supplier'] = $extra['sub_supplier'];
        }

        if (!empty($extra['account_ext_id'])) {
            $main_attributes['account_ext_id'] = $extra['account_ext_id'];
        }

        $data = [
            'type' => 'document',
            'attributes' => $main_attributes,
            'relationships' => [],
        ];

        $body = [
            'data' => $data
        ];

        $json = json_encode($body);

        phive()->fire(
            'dmapi',
            'DmapiCreateEmptyDocument',
            [$user_id, $url, $json],
            0,
            function () use ($user_id, $url, $json) {
                $this->createEmptyDocumentAsync($user_id, $url, $json);
            }
        );
    }

    /**
     * @param int $user_id
     * @param string $url
     * @param string $request
     * @param int $retry
     * @return void
     */
    public function createEmptyDocumentAsync(int $user_id, string $url, string $request, int $retry = self::SAVE_DOCUMENT_RETRIES)
    {
        $requestData = json_decode($request, true);
        $type = $requestData['data']['attributes']['tag'] ?? '';
        $retry--;
        $retryNumber = self::SAVE_DOCUMENT_RETRIES - $retry;

        $response = phive()->post($url, $request, 'application/json', $this->headers, $this->debugKey('dmapi_create_empty_document'), 'POST', $this->timeout);
        $result           = json_decode($response, true);
        $response_headers = phive()->res_headers;

        // Check the status code. Apache is sending 'HTTP/1.0 201 Created', while Nginx is sending 'HTTP/1.1 201 Created'
        if (!empty($response_headers[0]) && $response_headers[0] != 'HTTP/1.1 201 Created' && $response_headers[0] != 'HTTP/1.0 201 Created') {
            $result = '';
        }

        if ($retry < 0) {
            phive('Logger')->getLogger('dmapi')->error("createEmptyDocument reached the maximum number of attempts", [$user_id, $type]);
            return;
        }

        // put request in queue if there is no response, or if the response is different then 201 Created
        if (empty($response_headers) || $result == '') {
            $error_message = "DMAPI createEmptyDocument ($type) - error. Try $retryNumber";
            phive('UserHandler')->logAction($user_id, $error_message, 'creating_documents');
            phive('Logger')->getLogger('dmapi')->debug($error_message, [$response_headers, json_decode($response, true)]);

            phive()->fire(
                'dmapi',
                'DmapiCreateEmptyDocumentError',
                [$user_id, $url, $request, $retry],
                $this->delay($retry),
                function () use ($user_id, $url, $request, $retry) {
                    $this->createEmptyDocumentAsync($user_id, $url, $request, $retry);
                }
            );
        } else {
            phive('UserHandler')->logAction($user_id, "DMAPI createEmptyDocument ($type) - success", 'creating_documents');
            $this->flagDocsForRefresh($user_id);
        }
    }

    public function createAndReturnEmptyDocument($user_id, $type, $actor_id = 0)
    {
        $document_type = $this->getDocumentTypeFromMap($type, '');
        if (empty($document_type)) {
            return;
        }

        $url = "{$this->document_service_base_url}documents/create-empty-document";

        $main_attributes = [
            'user_id'     => $user_id,
            'actor_id'    => $actor_id,
            'tag'         => $document_type,
            'subtag'      => '',
            'external_id' => '',
            'status'      => 'requested',
            'extra'       => []
        ];

        $data = [
            'type' => 'document',
            'attributes' => $main_attributes,
            'relationships' => [],
        ];

        $body = [
            'data' => $data
        ];

        $json             = json_encode($body);
        $response         = phive()->post($url, $json, 'application/json', $this->headers, $this->debugKey('dmapi_create_empty_document'), 'POST', $this->timeout);
        $result           = json_decode($response, true);
        $response_headers = phive()->res_headers;

        // Check the status code. Apache is sending 'HTTP/1.0 201 Created', while Nginx is sending 'HTTP/1.1 201 Created'
        if (!empty($response_headers[0]) && $response_headers[0] != 'HTTP/1.1 201 Created' && $response_headers[0] != 'HTTP/1.0 201 Created') {
            $result = '';
        }

        // put request in queue if there is no response, or if the response is different then 201 Created
        if (empty($response_headers) || $result == '') {
            $this->putRequestInQueue($url, 'POST', $json);
        }

        $this->flagDocsForRefresh($user_id);

        if (!empty($result)) {
            // convert into a format that we can pass to convertDocumentsAndRelationshipsToArray
            $document_data = [];
            $document_data['data'][0]  = $result['data'];
            $document_data['included'] = $result['included'];

            $result = $this->convertDocumentsAndRelationshipsToArray($document_data);
        }

        return $result;
    }

    public function createInitialEmptyDocuments($user_id)
    {
        if (empty($user_id)) {
            return false;
        }

        phive('UserHandler')->logAction($user_id, 'createInitialEmptyDocuments', 'creating_documents');

        $this->createEmptyDocument($user_id, 'idcard-pic');
        $this->createEmptyDocument($user_id, 'addresspic');
        $this->createEmptyDocument($user_id, 'bankpic');

        return true;
    }



    public function changeUserId($old_user_id, $new_user_id)
    {
        $url = "{$this->document_service_base_url}change-user-id";

        $body = [
            'data' => [
                'old_user_id' => $old_user_id,
                'new_user_id' => $new_user_id,
            ]
        ];

        $json = json_encode($body);

        $response = phive()->post($url, $json, 'application/json', $this->headers, $this->debugKey('dmapi_change_user_id'), 'POST', $this->timeout);

        $result = json_decode($response, true);

        $response_headers = phive()->res_headers;

        // Check the status code. Apache is sending 'HTTP/1.0 201 Created', while Nginx is sending 'HTTP/1.1 201 Created'
        if (!empty($response_headers[0]) && $response_headers[0] == 'HTTP/1.1 200 OK' || $response_headers[0] == 'HTTP/1.0 200 OK') {
            return true;
        }

        return false;
    }

    /**
     * Gets the user's documents from the DMAPI, compatible with multiple accounts per bank
     * Note: this is used in the new backoffice
     * Todo: also update interface for frontend users so they can verify multiple accounts per bank.
     *
     * @param int|string $user_id
     */
    public function getUserDocumentsV2($user_id)
    {
        $url = "{$this->document_service_base_url}{$user_id}/documents";

        list($response, $http_code, $http_headers) = phive()->post(
            $url,
            '',
            'application/json',
            $this->headers,
            $this->debugKey('dmapi_get_user_documents_V2'),
            'GET',
            $this->timeout,
            [],
            'UTF-8',
            true
        );

        if (empty($response) || ($http_code != 200)) {
            return 'service not available';
        }

        $result = json_decode($response, true);

        if (empty($result['errors'])) {
            $documents = $this->convertDocumentsAndRelationshipsToArray($result);
            $documents = $this->orderDocuments($documents);

            return $documents;
        } else {
            if ($result['errors'][0]['status'] == '404') {
                // If we get a 404 response from the document service,
                // it means this user has no documents uploaded yet
                return [];
            }
        }
    }

    /**
     * Check if response is service unavailable
     *
     * @param $response
     * @return bool
     */
    public function isServiceAvailable($response): bool
    {
        return $response !== 'service not available';
    }

    // TODO Edwin fix this to actually have a real endpoint to get docs by tag and status(es) for a specific user
    public function getUserDocumentsByTag($user_id, $tag, $status = ['approved'])
    {
        $documents = $this->getUserDocumentsV2($user_id);
        $candidate_docs = [];
        foreach ($documents as $document) {
            if ($document['tag'] == $tag && in_array($document['status'], $status)) {
                $candidate_docs[] = $document;
            }
        }
        return $candidate_docs;
    }

    /**
     * Get user documents status
     *
     * @param int|string $user_id
     * @return array
     */
    public function getUserDocumentsGroupedByTagStatus($user_id): array
    {
        $documents = $this->getUserDocumentsV2($user_id);

        if (!$this->isServiceAvailable($documents)) {
            $documents = [];
        }

        if (empty($documents)) {
            return [];
        }

        return array_reduce($documents, static function ($carry, $item) {
            $carry[$item['tag']] = $item['status'];
            return $carry;
        }, []);
    }

    /**
     * Check if all provided documents have specific status
     *
     * @param array $document_status
     * @param array $required_documents
     * @param string $status
     * @param string $check
     * @return bool
     */
    public function documentsHaveStatus($document_status, $required_documents, $status, $check = 'all'): bool
    {
        $status_count = 0;
        foreach ($required_documents as $document) {
            if ($document_status[$document] === $status) {
                $status_count++;
            }
        }

        if ($check === 'some') {
            return $status_count > 0;
        }

        // all
        return $status_count === count($required_documents);
    }

    /**
     * Orders a user's documents in the same order as it was in the old system:
     *
     * - Proof of Identity
     * - Proof of Address
     * - Source of Wealth
     * - Proof of Wealth
     * - Internal Document
     * - All Credit cards
     * - Neteller
     * - Skrill
     * - Trustly
     * - iDeal
     * - Instadebit
     * - Ecopayz
     * - Verify bank account
     * - Citadel (Instant bank)
     * - Any remaining documents
     *
     * @param array $documents
     * @return array
     */
    private function orderDocuments($documents)
    {
        $main_documents      = [];
        $internal_documents  = [];
        $creditcarddocs      = [];
        $bankaccountdocs     = [];
        $deposit_method_docs = [];
        $other_docs          = [];

        foreach ($documents as $key => $document) {
            switch ($document['tag']) {
                case 'idcard-pic':
                    $main_documents[0] = $document;
                    unset($documents[$key]);
                    break;

                case 'addresspic':
                    $main_documents[1] = $document;
                    unset($documents[$key]);
                    break;

                case 'sourceoffundspic':
                    $main_documents[2] = $document;
                    unset($documents[$key]);
                    break;

                case 'proofofwealthpic':
                    $main_documents[3] = $document;
                    unset($documents[$key]);
                    break;

                case 'proofofsourceoffundspic':
                    $main_documents[4] = $document;
                    unset($documents[$key]);
                    break;

                case 'internaldocumentpic':
                    $internal_documents[] = $document;
                    unset($documents[$key]);
                    break;

                case 'creditcardpic':
                    $creditcarddocs[] = $document;
                    unset($documents[$key]);
                    break;

                case 'bankaccountpic':
                    $bankaccountdocs[] = $document;
                    unset($documents[$key]);
                    break;

                case 'bankpic':
                    $other_docs[0] = $document;
                    unset($documents[$key]);
                    break;

                case 'citadelpic':
                    $other_docs[1] = $document;
                    unset($documents[$key]);
                    break;

                case 'netellerpic':
                    $deposit_method_docs[0] = $document;
                    unset($documents[$key]);
                    break;

                case 'skrillpic':
                    $deposit_method_docs[1] = $document;
                    unset($documents[$key]);
                    break;

                case 'trustlypic':
                    $deposit_method_docs[2] = $document;
                    unset($documents[$key]);
                    break;

                case 'idealpic':
                    // We skip iDeal for now until we're up and running in NL properly.
                    // $deposit_method_docs[3] = $document;
                    unset($documents[$key]);
                    break;

                case 'instadebitpic':
                    $deposit_method_docs[4] = $document;
                    unset($documents[$key]);
                    break;

                case 'ecopayzpic':
                    $deposit_method_docs[5] = $document;
                    unset($documents[$key]);
                    break;

                default:
                    break;
            }
        }

        // sort the arrays
        ksort($main_documents);
        ksort($deposit_method_docs);
        ksort($other_docs);

        // merge the arrays
        $ordered_documents = array_merge($main_documents, $internal_documents, $creditcarddocs, $bankaccountdocs, $deposit_method_docs, $other_docs, $documents);

        return $ordered_documents;
    }

    function createEmptyBankDocument($user, $args, $psp)
    {
        $sort_code     = phive()->rmNonAlphaNums($args['bank_clearnr']);
        $post_acc_nr   = phive()->rmNonAlphaNums($args['bank_account_number']);
        $acc_nr        = $sort_code . $post_acc_nr;
        $iban          = phive()->rmNonAlphaNums($args['iban']);
        $insert_acc_nr = $clear_nr = '';
        $err           = [];
        // We don't do the clearnr in a separate field anymore
        //$clear_nr = str_replace([' ', '-'], '', $_POST['clearnr']);

        // TODO, Don't use the bank_clearnr in pending_withdrawals, store the whole
        // number in bank_account_number instead.
        if ($user->getCountry() == 'SE') {
            if (!is_numeric($post_acc_nr[0])) {
                $err['acc_iban'] = 'err.wrong.number';
            }

            if (strlen($acc_nr) < 10) {
                $err['acc_iban'] = 'err.empty';
            }

            $clear_nr_length = 4;
            //if(strpos($bank, 'swedbank') !== false || strpos($bank, 'sparbank') !== false){
            // We might not need to do anything more than just check the first number, if it is 8 or not
            if ($acc_nr[0] == '8') {
                $clear_nr_length = 5;
            }
            //}

            $clear_nr      = substr($acc_nr, 0, $clear_nr_length);
            $insert_acc_nr = substr($acc_nr, $clear_nr_length);
        }

        if (empty($iban) && empty($acc_nr)) {
            $err['acc_iban'] = 'err.empty';
        }

        // This is off as per Alex's verbal instructions on 2017-12-05.
        //if(!empty($iban) && strtoupper(substr($iban, 0, 2)) != strtoupper($user->getCountry()))
        //$err['acc_iban'] = 'err.country';

        // Send async request to Dmapi to create a new document with the account number
        $this->createEmptyDocument($user->getId(), $psp, 'bankaccount', empty($acc_nr) ? $iban : $acc_nr);

        return ['errors' => $err, 'data' => ['bank_account_number' => $insert_acc_nr, 'iban' => $iban, 'bank_clearnr' => $clear_nr]];
    }


    /**
     * Gets all the files of a document.
     * This is used in the backoffice when a document is deleted, in order to
     * add entries into the actions table with links to the deleted files.
     *
     * @param int $document_id
     * @return string
     */
    public function getFilesOfDocument($document_id)
    {
        $document_id = intval($document_id);
        $url = "{$this->document_service_base_url}documents/{$document_id}/files";

        $response = phive()->post($url, '', 'application/json', $this->headers, $this->debugKey('dmapi_get_files_of_document'), 'GET', $this->upload_timeout);

        if (empty($response)) {
            return 'service not available';
        }

        $result = json_decode($response, true);

        return $result['data'];
    }


    public function addFileToDocument($document_id, $images, $user_id)
    {
        $document_id = intval($document_id);
        $url = "{$this->document_service_base_url}documents/{$document_id}/files";

        $data =  ['data' =>
                        [
                            'type' => 'file',
                            'attributes' => $images['files'][0]
                        ]
                    ];

        $json = json_encode($data);

        $response = phive()->post($url, $json, 'application/json', $this->headers, $this->debugKey('dmapi_add_file_to_document'), 'POST', $this->upload_timeout);

        $result = json_decode($response, true);

        $this->flagDocsForRefresh($user_id);

        if (empty($response)) {
            $result = ['errors' => ['Document service not available, please try again later']];
            return $result;
        }

        if (!empty($result['errors'])) {
            $result['errors'][0] = 'Unable to add file to document';
        }

        $user = cu($user_id);
        lic('onDocumentStatusUpdated', [$user, null], $user);

        return $result;
    }


    /**
     * Sends a request to replace an uploaded file, used in the Back Office
     *
     * @param  array     $images
     * @param  int       $file_id
     * @param  int       $user_id
     * @return  array
     */
    public function replaceUploadedFile($images, $file_id, $user_id)
    {
        $url = "{$this->document_service_base_url}files/replacefile";

        $data = [
            'data' => [
                'file' => $images['files'][0]
            ]
        ];

        $data['data']['file']['actor_id'] = cuAttr('id');
        $data['data']['file']['user_id'] = $user_id;
        $data['data']['file']['file_id'] = $file_id;

        $json = json_encode($data);

        $response = phive()->post($url, $json, 'application/json', $this->headers, $this->debugKey('dmapi_replace_file'), 'POST', $this->timeout);

        $this->flagDocsForRefresh($user_id);

        if (empty($response)) {
            $result = ['errors' => ['Document service not available, please try again later']];
            return $result;
        }

        $result = json_decode($response, true);

        if (!empty($result['errors'])) {
            $result['errors'][0] = 'Unable to replace file';
        }

        return $result;
    }


    /**
     * @param $document_id
     * @param $images
     * @param $subtag
     * @param $country
     * @param $user_id
     * @return array|string[][]
     * @throws JsonException
     */
    public function addMultipleFilesToDocument($document_id, $images, $subtag, $country, $user_id): array
    {
        $user = cu($user_id);
        $document_id = intval($document_id);
        $url = "{$this->document_service_base_url}documents/{$document_id}/addfiles";

        $current_document = phive('Dmapi')->getDocumentById($document_id, $user_id);

        $files = [];
        foreach ($images as $image) {
            $files[] = [
                'type'       => 'file',
                'attributes' => $image
            ];
        }

        $data =  [
            'data' => [
                'files' => $files
            ]
        ];

        // For ID docs, we need to add the ID type and Country to the request
        if (!empty($subtag)) {
            $data['data']['attributes'] = [
                    'subtag'  => $subtag,
                    'country' => $country
                ];
        }

        // add actor ID and user ID
        $data['data']['attributes']['actor_id'] = cuAttr('id');
        $data['data']['attributes']['user_id']  = $user_id;

        $json = json_encode($data, JSON_THROW_ON_ERROR);

        $response = phive()->post($url, $json, 'application/json', $this->headers, $this->debugKey('dmapi_add_multiple_files_to_document'), 'POST', $this->timeout);

        $response_decoded = json_decode($response, true);

        $this->flagDocsForRefresh($user_id);

        if (empty($response)) {
            $result = ['errors' => ['Document service not available, please try again later']];

            return $result;
        }

        if (!empty($response_decoded['errors'])) {
            $result = [];
            $result['errors'][0] = 'Unable to add files to document';
            return $result;
        }

        if (!empty($response_decoded['data'])) {
            // convert into a format that we can pass to convertDocumentsAndRelationshipsToArray
            $document_data = [];
            $document_data['data'][0]  = $response_decoded['data'];
            $document_data['included'] = $response_decoded['included'];

            $result = $this->convertDocumentsAndRelationshipsToArray($document_data);
            lic('onDocumentProcessing', [$user, $response_decoded['data']], $user);
        }

        phive('DBUserHandler')->onMissingDocumentUpload($current_document, $user);
        lic('onDocumentStatusUpdated', [$user, $current_document], $user);

        return $result;
    }


    public function deleteUploadedDocument($document_id, $user_id)
    {
        $document_id = intval($document_id);
        $url = "{$this->document_service_base_url}documents/{$document_id}/delete";

        $response = phive()->post($url, '', 'application/json', $this->headers, $this->debugKey('dmapi_delete_document'), 'POST', $this->timeout);

        $response_headers = phive()->res_headers;

        $this->flagDocsForRefresh($user_id);

        // Check the status code. Apache is sending 'HTTP/1.1 204 No Content', while Nginx is sending 'HTTP/1.0 204 No Content'
        if (!empty($response_headers[0]) && $response_headers[0] != 'HTTP/1.1 204 No Content' && $response_headers[0] != 'HTTP/1.0 204 No Content') {
            // something went wrong, we should have errors
            $result = ['errors' => ['Unable to delete file']];
            return $result;
        }

        if (empty($response_headers)) {
            $result = ['errors' => ['Document service not available, please try again later']];
            return $result;
        }

        $result = json_decode($response, true);

        $user = cu($user_id);
        lic('onDocumentStatusUpdated', [$user, null], $user);

        return $result;
    }

    public function deleteUploadedFile($file_id, $user_id)
    {
        $url = "{$this->document_service_base_url}files/{$file_id}/delete";

        $response = phive()->post($url, '', 'application/json', $this->headers, $this->debugKey('dmapi_delete_file'), 'POST', $this->timeout);

        $response_headers = phive()->res_headers;

        $result = json_decode($response, true);

        $this->flagDocsForRefresh($user_id);

        // Check the status code. Apache is sending 'HTTP/1.1 204 No Content', while Nginx is sending 'HTTP/1.0 204 No Content'
        if ($result[0] != 'File deleted') {
            // something went wrong, we should have errors
            $result = ['errors' => ['Unable to delete file']];
            return $result;
        }

        if (empty($response_headers)) {
            $result = ['errors' => ['Document service not available, please try again later']];
            return $result;
        }

        $user = cu($user_id);
        lic('onDocumentStatusUpdated', [$user, null], $user);

        return $result;
    }

    public function rejectAllFilesFromDocument($user_id, $tag)
    {
        $url = "{$this->document_service_base_url}documents/reject-all-files-from-document";

        $body = [
            'data' => [
                'user_id' => $user_id,
                'tag' => $tag
            ]
        ];

        $json = json_encode($body);

        $response = phive()->post($url, $json, 'application/json', $this->headers, $this->debugKey('dmapi_reject_all_files_from_document'), 'POST', $this->timeout);

        $response_headers = phive()->res_headers;

        $this->flagDocsForRefresh($user_id);

        // Check the status code. Apache is sending 'HTTP/1.1 204 No Content', while Nginx is sending 'HTTP/1.0 204 No Content'
        if (!empty($response_headers[0]) && $response_headers[0] != 'HTTP/1.1 204 No Content' && $response_headers[0] != 'HTTP/1.0 204 No Content') {
            // something went wrong, we should have errors
            $result = ['errors' => ['Unable to delete the files for this document']];

            return $result;
        }

        if (empty($response_headers)) {
            $this->putRequestInQueue($url, 'POST', $json);
        }

        $result = json_decode($response, true);

        return $result;
    }

    public function flagDocsForRefresh($user_id)
    {
        phMsetShard('dmapi-docs-updated', 1, $user_id);
    }

    /**
     * Updates the document status
     *
     * @param int $actor_id
     * @param int $document_id
     * @param string $status
     * @param int $user_id
     * @return array   Returns the document on success, or an error on failure
     */
    public function updateDocumentStatus($actor_id, $document_id, $status, $user_id)
    {
        $document_id = intval($document_id);
        $url = "{$this->document_service_base_url}documents/{$document_id}";

        $body = [
            'data' => [
                'type' => 'document',
                'id' => (string)$document_id,
                'attributes' => [
                    'actor_id' => $actor_id,
                    'user_id'  => $user_id,
                    'status'   => $status
                ]
            ]
        ];

        $json = json_encode($body);

        $response = phive()->post($url, $json, 'application/json', $this->headers, $this->debugKey('dmapi_update_document_status'), 'POST', $this->timeout);

        $this->flagDocsForRefresh($user_id);

        if (empty($response)) {
            $result = ['errors' => ['Document service not available, please try again later']];
            return $result;
        }

        $response_decoded = json_decode($response, true);

        if (!empty($response_decoded['errors'])) {
            if (is_string($response_decoded['errors'])) {
                $result['errors'][0] = $response_decoded['errors'];
            } else {
                $result['errors'] = [];
                $result['errors'][0] = 'Unable to update document status';
            }
        }

        if (!empty($response_decoded['data'])) {
            // convert into a format that we can pass to convertDocumentsAndRelationshipsToArray
            $document_data = [];
            $document_data['data'][0]  = $response_decoded['data'];
            $document_data['included'] = $response_decoded['included'];

            $result = $this->convertDocumentsAndRelationshipsToArray($document_data);
        }

        $user = cu($user_id);
        lic('onDocumentStatusUpdated', [$user, $response_decoded['data'] ?? null], $user);

        return $result;
    }

    public function updateOrCreateDocument(
        int $userId,
        int $clientId,
        string $tag,
        string $subTag,
        array $data,
        ?string $status = null
    ): ?array {
        $document = $this->getDocumentBasedOnUniqueConstraint($userId, $clientId, $tag, $subTag);

        if (empty($document)) {
            $this->createEmptyDocument(
                $userId,
                $data['supplier'],
                $data['type'],
                $subTag,
                $data['account_id'],
                0,
                $data,
                $status ?? 'requested'
            );

            return null;
        }

        $docDataToUpdate = [
            'actor_id' => cu(DBUserHandler::SYSTEM_USER)->getId(),
            'external_id' => $data['account_id'],
        ];

        if ($status) {
            $statusesMap = [
                self::STATUS_APPROVED => self::STATUS_APPROVED_ENUM,
                self::STATUS_REQUESTED => self::STATUS_REQUESTED_ENUM,
            ];

            $docDataToUpdate['status'] = $statusesMap[$status];
        }

        $docAttrs = $document['attributes'];

        if (!$docAttrs['supplier']) {
            $docDataToUpdate = array_merge($docDataToUpdate, [
                'supplier' => $data['supplier'],
            ]);
        }

        if (!$docAttrs['sub_supplier']) {
            $docDataToUpdate = array_merge($docDataToUpdate, [
                'sub_supplier' => $data['sub_supplier'],
            ]);
        }

        if (!$docAttrs['account_ext_id']) {
            $docDataToUpdate = array_merge($docDataToUpdate, [
                'account_ext_id' => $data['account_ext_id']
            ]);
        }

        return $this->updateDocumentColumns($docDataToUpdate, $document['id'], $userId);
    }

    public function changeSourceOfFundsDataDocumentID($user_id, $sowd_id, $document_id)
    {
        $url = "{$this->document_service_base_url}{$sowd_id}/{$document_id}/change-sowd-document-id";

        $response = phive()->post($url, '', 'application/json', $this->headers, $this->debugKey('dmapi_get_user_documents_V2'), 'POST', $this->timeout);
        $this->flagDocsForRefresh($user_id);

        if (empty($response)) {
            $result = ['errors' => ['Document service not available, please try again later']];
            return $result;
        }

        $response_decoded = json_decode($response, true);

        if (!empty($response_decoded['errors'])) {
            if (is_string($response_decoded['errors'])) {
                $result['errors'][0] = $response_decoded['errors'];
            } else {
                $result['errors'] = [];
                $result['errors'][0] = 'Unable to update document status';
            }
        }

        return $response_decoded;
    }


    /**
     * Updates the document status for credit cards only.
     * In this case we pass the card_id instead of the document id.
     * This is used when the MTS notifies VS that a card needs to be deactivated.
     * @see /phive/modules/Cashier/html/mts_notify.php
     *
     * @param int $actor_id
     * @param int $user_id
     * @param int $card_id
     * @param string $status
     * @return boolean
     */
    public function updateDocumentStatusForCreditCard($actor_id, $user_id, $card_id, $status)
    {
        $card_id = (int)$card_id;
        $url = "{$this->document_service_base_url}documents/update-card-status";

        $body = [
            'data' => [
                'type' => 'document',
                'attributes' => [
                    'user_id'     => $user_id,
                    'actor_id'    => $actor_id,
                    'external_id' => $card_id,
                    'status'      => $status
                ]
            ]
        ];

        $json = json_encode($body);

        $response = phive()->post($url, $json, 'application/json', $this->headers, $this->debugKey('dmapi_update_card_status'), 'POST', $this->timeout);
        $response_decoded = json_decode($response, true);

        $this->flagDocsForRefresh($user_id);

        if (empty($response) || !empty($response_decoded['errors'])) {
            return false;
        }

        return $response_decoded['success'];
    }

    /**
     *
     * @param array $data
     * @return array
     */
    public function createSourceOfFundsData($user_id, $data)
    {
        $url = "{$this->document_service_base_url}documents/{$data['document_id']}/source-of-funds/create-source-of-funds-data";

        $data['actor_id'] = $user_id;

        $body = $this->setSourceOfFundsDataBody($data);
        $json = json_encode($body);

        $response = phive()->post($url, $json, 'application/json', $this->headers, $this->debugKey('dmapi_create_source_of_funds_data'), 'POST', $this->timeout);

        $this->flagDocsForRefresh($user_id);

        if (empty($response)) {
            $result = ['errors' => ['Document service not available, please try again later']];
            return $result;
        }

        $response_decoded = json_decode($response, true);
        return $response_decoded;
    }

    public function updateSourceOfFundsData($user_id, $data)
    {
        $url = "{$this->document_service_base_url}documents/{$data['document_id']}/source-of-funds/update-source-of-funds-data";

        $data['actor_id'] = cuAttr('id');

        $body = $this->setSourceOfFundsDataBody($data);
        $json = json_encode($body);

        $response = phive()->post($url, $json, 'application/json', $this->headers, $this->debugKey('dmapi_update_source_of_funds_data'), 'POST', $this->timeout);

        $this->flagDocsForRefresh($user_id);

        if (empty($response)) {
            $result = ['errors' => ['Document service not available, please try again later']];
            return $result;
        }

        $response_decoded = json_decode($response, true);
        return $response_decoded;
    }


    public function setSourceOfFundsDataBody($data)
    {
        $body = [
            'data' => [
                'type'       => 'document',
                'id'         => (string) $data['document_id'],
                'attributes' => [
                    'actor_id'               => $data['actor_id'],
                    'user_id'                => $data['user_id'],
                    'name_of_account_holder' => $data['name_of_account_holder'],
                    'name'                   => $data['name'],
                    'address'                => $data['address'],
                    'funding_methods'        => $data['funding_methods'],
                    'other_funding_methods'  => $data['other_funding_methods'],
                    'industry'               => $data['industry'],
                    'occupation'             => $data['occupation'],
                    'annual_income'          => $data['annual_income'],
                    'currency'               => $data['currency'],
                    'no_income_explanation'  => $data['no_income_explanation'],
                    'your_savings'           => $data['your_savings'],
                    'savings_explanation'    => $data['savings_explanation'],
                    'date_of_submission'     => $data['date_of_submission'],
                    'form_version'           => $data['form_version'],
                ]
            ]
        ];

        return $body;
    }

    /**
     * Updates the status of a single file, which may lead to the document itself
     * being approved or rejected automatically. That's why we are returning
     * the document and not only the file.
     *
     * @param int     $actor_id
     * @param int     $file_id
     * @param string  $status
     * @param int     $user_id
     * @return string  Returns the document on success, or an error on failure
     */
    public function updateFileStatus($actor_id, $file_id, $status, $user_id)
    {
        $url = "{$this->document_service_base_url}files/{$file_id}";

        $body = [
            'data' => [
                'type' => 'file',
                'id' => (string)$file_id,
                'attributes' => [
                    'actor_id' => $actor_id,
                    'status' => $status
                ]
            ]
        ];

        $json = json_encode($body);

        $response = phive()->post($url, $json, 'application/json', $this->headers, $this->debugKey('dmapi_update_file_status'), 'POST', $this->timeout);

        $this->flagDocsForRefresh($user_id);

        if (empty($response)) {
            $result = ['errors' => ['Document service not available, please try again later']];
            return $result;
        }

        $response_decoded = json_decode($response, true);

        if (!empty($response_decoded['errors'])) {
            $result['errors'][0] = 'Unable to update file status';
        }

        if (!empty($response_decoded['data'])) {
            // convert into a format that we can pass to convertDocumentsAndRelationshipsToArray
            $document_data = [];
            $document_data['data'][0]  = $response_decoded['data'];
            $document_data['included'] = $response_decoded['included'];
            $result = $this->convertDocumentsAndRelationshipsToArray($document_data);
        }

        $user = cu($user_id);
        lic('onDocumentStatusUpdated', [$user, $response_decoded['data'] ?? null], $user);

        return $result;
    }

    /**
     * Calls the document service to get all documents with a certain status
     *
     * @param type $status
     */
    public function getDocumentsByStatus($status)
    {
        $url = "{$this->document_service_base_url}documents/status/{$status}";

        $response = phive()->post($url, '', 'application/json', $this->headers, $this->debugKey('dmapi_get_documents_by_status_' . $status), 'GET', $this->timeout);

        if (empty($response)) {
            $result = ['errors' => ['Document service not available, please try again later']];

            return $result;
        }

        $result = json_decode($response, true);

        $documents = $this->convertDocumentsAndRelationshipsToArray($result);

        return $documents;
    }

    /**
     * Calls the document service to get all unverified Source Of Funds documents
     *
     * @return array
     */
    public function getUnverifiedSourceOfFundsDocuments()
    {
        $url = "{$this->document_service_base_url}source-of-funds-pending/";

        $response = phive()->post($url, '', 'application/json', $this->headers, $this->debugKey('dmapi_get_sourceoffunds_documents'), 'GET', $this->timeout);

        if (empty($response)) {
            $result = ['errors' => ['Document service not available, please try again later']];

            return $result;
        }

        $result = json_decode($response, true);

        $documents = $this->convertDocumentsAndRelationshipsToArray($result);

        return $documents;
    }


    public function providerAsBank($tag, $user_id)
    {
        $type = str_replace('pic', '', $tag);
        $user = cu($user_id);
        if (phive('Cashier')->providerAsBank($user, $type)) {
            return true;
        }
        return false;
    }


    public function expireDocument($doc, $user_id)
    {
        $user = cu($user_id);
        lic('onDocumentExpired', [$user, $doc], $user_id);
        return $this->updateDocumentColumns(['expired' => 1], $doc['id'], $user_id);
    }

    public function updateDocumentColumns($update_arr, $document_id, $user_id)
    {
        $document_id = (int)$document_id;
        $url = "{$this->document_service_base_url}documents/{$document_id}/update-values";

        $body = [
            'data' => [
                'type'       => 'document',
                'id'         => (string)$document_id,
                'attributes' => $update_arr
            ]
        ];

        $json = json_encode($body);

        $response = phive()->post($url, $json, 'application/json', $this->headers, $this->debugKey('dmapi_update_document_columns'), 'POST', $this->timeout);

        $this->flagDocsForRefresh($user_id);

        if (empty($response)) {
            $result = ['errors' => ['Document service not available, please try again later']];
            return $result;
        }

        $result = json_decode($response, true);

        if (!empty($result['errors'])) {
            $result['errors'][0] = 'Unable to update columns';
        }

        return $result;
    }

    public function updateDocumentExpiryDate($actor_id, $document_id, $expiry_date)
    {
        $document_id = intval($document_id);
        $url = "{$this->document_service_base_url}documents/{$document_id}/update-expiry-date";

        $body = [
            'data' => [
                'type' => 'document',
                'id' => (string)$document_id,
                'attributes' => [
                    'actor_id' => $actor_id,
                    'expiry_date' => $expiry_date
                ]
            ]
        ];

        $json = json_encode($body);

        $response = phive()->post($url, $json, 'application/json', $this->headers, $this->debugKey('dmapi_update_expiry_date'), 'POST', $this->timeout);

        $this->flagDocsForRefresh($user_id);

        if (empty($response)) {
            $result = ['errors' => ['Document service not available, please try again later']];

            return $result;
        }

        $result = json_decode($response, true);

        if (!empty($result['errors'])) {
            $result['errors'][0] = 'Unable to update expiry date';
        }

        return $result;
    }


    /**
     * Validate the form
     *
     * @param array $data The posted data
     * @param array $files The uploaded files
     *
     * @return array $errors
     */
    public function validateUploadedID($data, $files)
    {
        $errors = array();
        if (empty($data['idtype'])) {
            $errors[] = 'select.id.type.error';
        }
        if (empty($files['image-front']['name'])) {
            $errors[] = 'please.upload.front';
        }
        if (($data['idtype'] == 'ID_CARD' || $data['idtype'] == 'DRIVING_LICENSE') && empty($files['image-back']['name'])) {
            $errors[] = 'please.upload.back';
        }
        return $errors;
    }

    /**
     * This is used by the document page
     *
     * @param string $error_key
     * @return array
     */
    public function validateUploadedFiles($error_key = '')
    {
        return phive('Filer')->validateUploadedFiles($error_key);
    }


    /**
     * Uploads a public file to the Dmapi
     *
     * @param string    $temp_file_location
     * @param string    $foldername     ie: image_uploads or file_uploads
     * @param string    $filename
     * @param string    $subfolder      can also contain subsubfolders, ie: events/grey
     * @return array
     */
    public function uploadPublicFile($temp_file_location, $foldername, $filename, $subfolder = '')
    {
        $url = "{$this->document_service_base_url}files/upload-public-file";

        $data =  [
            'data' => [
                'filename'      => $filename,
                'folder'        => $foldername,
                'encoded_data'  => base64_encode(file_get_contents($temp_file_location))
            ]
        ];

        if (!empty($subfolder)) {
            $data['data']['subfolder'] = $subfolder;
        }

        $json       = json_encode($data);
        $response   = phive()->post(
            $url,
            $json,
            'application/json',
            $this->headers,
            $this->debugKey('dmapi_upload_public_file'),
            'POST',
            $this->timeout
        );

        if (empty($response)) {
            $result = ['errors' => ['Media service not available, please try again later']];
            $this->putRequestInQueue($url, 'POST', $json);
            return $result;
        }

        $result = json_decode($response, true);

        return $result;
    }

    /**
     * Deletes a public file at the Dmapi
     *
     * @param string  $foldername    ie: image_uploads or file_uploads
     * @param string  $filename
     * @param string  $subfolder    Can contain sub-subfolders
     * @return array
     */
    public function deletePublicFile($foldername, $filename, $subfolder = '')
    {
        $url = "{$this->document_service_base_url}files/delete-public-file/?folder={$foldername}&filename={$filename}";

        if (!empty($subfolder)) {
            $subfolder = urlencode($subfolder);
            $url .= "&subfolder={$subfolder}";
        }

        $response   = phive()->post(
            $url,
            '',
            'application/json',
            $this->headers,
            $this->debugKey('dmapi_delete_public_file'),
            'POST',
            $this->timeout
        );

        if (empty($response)) {
            $result = ['errors' => ['Media service not available, please try again later']];
            $this->putRequestInQueue($url, 'POST', '');
            return $result;
        }

        $result = json_decode($response, true);

        return $result;
    }

    /**
     * Get a list of files from a specific public folder
     *
     * @param string $folder
     * @param string $subfolder    Can contain subsubfolder: events/grey
     * @return array
     */
    public function getListOfPublicFiles($folder, $subfolder = '')
    {
        $url = "{$this->document_service_base_url}get-list-of-public-files/?folder={$folder}";

        if (!empty($subfolder)) {
            $url .= "&subfolder={$subfolder}";
        }

        $response   = phive()->post(
            $url,
            '',
            'application/json',
            $this->headers,
            '',
            'GET',
            $this->timeout
        );

        if (empty($response)) {
            $result = ['errors' => ['Media service not available, please try again later']];
            return $result;
        }

        $result = json_decode($response, true);

        return $result;
    }

    /**
     * Get a list of subfolders from a specific public folder
     *
     * @param string $folder
     * @return array
     */
    public function getPublicSubfolders($folder)
    {
        $url = "{$this->document_service_base_url}get-list-of-public-subfolders/?folder={$folder}";

        $response   = phive()->post(
            $url,
            '',
            'application/json',
            $this->headers,
            '',
            'GET',
            $this->timeout
        );

        if (empty($response)) {
            $result = ['errors' => ['Media service not available, please try again later']];
            return $result;
        }

        $result = json_decode($response, true);

        return $result;
    }

    /**
     *
     * @param string $folder
     * @param string $subfolder
     * @param string $old_name
     * @param string $new_name
     * @return array
     */
    public function renamePublicFile($folder, $subfolder, $old_name, $new_name)
    {
        $url = "{$this->document_service_base_url}rename-public-file";

        $data =  [
            'data' => [
                'folder'        => $folder,
                'old_name'      => $old_name,
                'new_name'      => $new_name,
            ]
        ];

        if (!empty($subfolder)) {
            $data['data']['subfolder'] = $subfolder;
        }

        $json       = json_encode($data);

        $response   = phive()->post(
            $url,
            $json,
            'application/json',
            $this->headers,
            '',
            'POST',
            $this->timeout
        );

        if (empty($response)) {
            $result = ['errors' => ['Media service not available, please try again later']];
            return $result;
        }

        $result = json_decode($response, true);

        return $result;
    }

    /**
     * Checks if the upload path exists and is writable, if not it creates the directory and/or makes it writable
     *
     * @param string $uploaddir
     */
    private function checkUploadPath($uploaddir)
    {
        if (!file_exists($uploaddir)) {
            $oldmask = umask(0);
            mkdir($uploaddir, 0777);
            umask($oldmask);
        }
        if (!is_writable($uploaddir)) {
            $oldmask = umask(0);
            chmod($uploaddir, 0777);
            umask($oldmask);
        }
    }

    private function putRequestInQueue($url, $method, $json)
    {
        $array = [
            'service' => 'dmapi',
            'url' => $url,
            'method' => $method,
            'headers' => $this->headers,
            'json_data' => $json,
        ];
        phive('SQL')->insertArray('requests_queue', $array);
    }

    public function getDocumentTypeFromMap($type, $scheme = '')
    {
        $res = $this->map[$type];

        if (!empty($res) && empty($scheme)) {
            return $res;
        }

        switch ($type) {
            case 'adyen':
                $adyen_map = ['trustly' => 'trustlypic', 'directEbanking' => 'sofortpic'];
                if ($type == 'adyen') {
                    $res = $adyen_map[$scheme];
                    if (empty($res)) {
                        $res = 'creditcardpic';
                    }
                }
                break;
            case 'paymentiq':
                if (in_array(strtolower($scheme), ['interac', 'vega', 'interaconline'])) {
                    $res = strtolower($scheme) . "pic";
                } else {
                    $res = 'creditcardpic';
                }
                break;
            case 'skrill':
                if (in_array($scheme, ['rapid', 'sofort', 'giropay'])) {
                    $res = 'bankpic';
                }
                break;
        }

        if (empty($res)) {
            $type = phiveApp(PspConfigServiceInterface::class)->getPspSetting($type)['type'];
            $res = $this->map[$type];
        }

        return empty($res) ? '' : $res;
    }

    /**
     * Gets a document by its id.
     *
     * @param int $id The id.
     * @param int $user_id The user id, is needed in order to correctly deal with the session cache of the documents.
     *
     * @return array|null The document array if found, null otherwise.
     */
    public function getDocumentById($id, $user_id)
    {
        return phive()->search2d($this->getDocuments($user_id), 'id', $id);
    }

    /**
     * Gets a document by its tag, regardless of the status
     * @param string $tag
     * @param int $user_id
     *
     * @return array|bool The document array if found, false otherwise.
     */
    public function getDocumentByTag($tag, $user_id)
    {
        $documents = $this->getDocuments($user_id);

        $found = !is_array($documents) ? false : array_search($tag, array_column($documents, 'tag'));

        return ($found === false) ? false : $documents[$found];
    }

    /**
     * Get the users documents and stores them in the session, will be refreshed if status is updated.
     *
     * @param int $user_id
     * @return array
     */
    public function getDocuments($user_id)
    {
        // If the session cache is empty or has been refreshed we make a new original call.
        if (empty($_SESSION['dmapi_user_docs'][$user_id]) || !empty(phMgetShard('dmapi-docs-updated', $user_id))) {
            $_SESSION['dmapi_user_docs'][$user_id] = $this->getUserDocumentsv2($user_id);
            phMdelShard('dmapi-docs-updated', $user_id);
        }

        return $_SESSION['dmapi_user_docs'][$user_id];
    }


    /**
     * Checks the status for a document that matches the bank name and bank account number
     *
     * @param int    $user_id
     * @param string $tag
     * @param string $bank_account_number   If supplied we check the subtag for the correct account number
     * @param int    $card_id               Required for credit cards, if this is not supplied for credit cards we return status 'unknown'
     * @param string $card_hash             NOT RECOMMENDED: If we don't have the card_id, but only the card hash,
     *                                      we can use that for checking the card, but it can give wrong results
     *                                      when a user has with multiple cards with the same hash.
     *
     * @return string
     */
    public function checkDocumentStatus($user_id, $tag, $bank_account_number = '', $card_id = '', $card_hash = '')
    {
        $documents = $this->getDocuments($user_id);

        // Loop through all documents looking for a match
        foreach ($documents as $document) {
            if ($document['tag'] == $tag) {
                if (!empty($document['expired'])) {
                    return 'expired';
                }

                if ($tag == 'creditcardpic' && $document['external_id'] == $card_id && !empty($card_id)) {
                    return $document['status'];
                } elseif ($tag == 'creditcardpic' && $document['subtag'] == $card_hash && !empty($card_hash) && empty($card_id)) {
                    return $document['status'];
                } elseif ($tag != 'creditcardpic' && $document['subtag'] == $bank_account_number && !empty($bank_account_number)) {
                    return $document['status'];
                } elseif ($tag != 'creditcardpic') {
                    return $document['status'];
                }
            }
        }

        // Prevent fatal errors in case we cannot find the status
        return 'unknown';
    }

    public function checkDocumentStatusBySubtag($user_id, $psp, $scheme, $sub_tag)
    {
        $tag = $this->getDocumentTypeFromMap($psp, $scheme);
        foreach ($this->getDocuments($user_id) as $document) {
            if ($document['tag'] === $tag && $document['subtag'] === $sub_tag) {
                return $document['status'];
            }
        }

        return 'unknown';
    }

    public function checkProviderStatus($user_id, $provider, $scheme, $bank_account_number = '', $card_id = '', $card_hash = '')
    {
        $user_id = uid($user_id);
        $document_type = $this->getDocumentTypeFromMap($provider, $scheme);
        //print_r([$user_id, $document_type, $bank_account_number, $card_id, $card_hash]);
        return $this->checkDocumentStatus($user_id, $document_type, $bank_account_number, $card_id, $card_hash);
    }

    private function getHeadlineTag($tag)
    {
        switch ($tag) {
            case 'addresspic':
                $headline_tag = 'address';
                break;

            case 'idcard-pic':
                $headline_tag = 'id';
                break;

            default:
                $headline_tag = $tag;
                break;
        }

        return $headline_tag;
    }

    /**
     * Convert the documents and it's relationships (ie. files) to an array that can be looped through
     *
     * @param array $result
     * @return array
     */
    private function convertDocumentsAndRelationshipsToArray($result)
    {
        $nice_array = [];
        foreach ($result['data'] as $document) {        // this only works if there are multiple documents
            if ($document['attributes']['tag'] == 'idpic') {
                // this is for compatibility with old ID pics
                $tag = 'idpic_front';
            } else {
                $tag = $document['attributes']['tag'];
            }

            // TODO: find better solution for this temporary hack to override do_kyc for a specific psp based on jurisdiction.
            //  overrideDisplayDocument returns NULL if there is no override, TRUE if it MUST be shown, FALSE if it must NOT be shown.
            $override_display_document = lic('getDisplayDocumentOverride', [$document['attributes']['user_id'], $tag], $document['attributes']['user_id']);
            if ($override_display_document === false) {
                continue;
            }

            // check if this document should be left out, because for certain countries we see the payment provider as bank (ie: citadelAsBank, or EntercashAsBank)
            if (($override_display_document !== true) && $this->providerAsBank($document['attributes']['tag'], $document['attributes']['user_id'])) {
                continue;
            }

            foreach ($document['relationships']['files']['data'] as $file) {
                // search for the file in 'included'
                foreach ($result['included'] as $included) {
                    if ($included['id'] == $file['id']) {
                        // add id to attributes
                        $included['attributes']['id'] = $file['id'];
                        // add attributes to nice array
                        $nice_array[$document['id']]['files'][] = $included['attributes'];
                    }
                }
            }

            $nice_array[$document['id']]['id']  = $document['id'];
            $nice_array[$document['id']]['tag'] = $tag;

            // add permission tag of document
            $permission_tag = $tag;
            if ($tag == 'idcard-pic') {
                $permission_tag = 'idpic';
            }
            if ($tag == 'addresspic') {
                $permission_tag = 'address';
            }
            $nice_array[$document['id']]['permission_tag'] = $permission_tag;
            $nice_array[$document['id']]['headline_tag']   = $this->getHeadlineTag($tag);

            // add document attributes
            foreach ($document['attributes'] as $key => $attribute) {
                $nice_array[$document['id']][$key] = $attribute;
            }
        }

        return $nice_array;
    }

    /**
     * Gets all documents from the other brand
     * Statuses of documents from other brand are stored as settings in users_settings table of local brand
     *
     * @param DBUser $user
     * @return array
     */
    public function getCrossBrandDocuments(DBUser $user): array
    {
        $documents = [];
        $documents_to_sync = lic('getLicSetting', ['cross_brand'], $user)['documents_to_sync'];
        foreach ($this->tags_to_settings_map as $tag => $setting_name) {
            if (!in_array($tag, $documents_to_sync, true)) {
                continue;
            }

            $user_doc_status = $user->getSetting($setting_name);
            if (!empty($user_doc_status)) {
                $documents[$tag] = [
                    'status' => $user_doc_status,
                    'headline_tag' => $this->getHeadlineTag($tag),
                ];
            }
        }

        return $documents;
    }

    /**
     * @param $document_type
     * @return string
     */
    public function getSettingNameForDocumentType($document_type): string
    {
        return $this->tags_to_settings_map[$document_type] ?? '';
    }

    /**
     * Check if all the required docs are approved
     *
     * @param DBUser $user
     * @param array $required_documents_types
     * @return bool
     * @noinspection NotOptimalIfConditionsInspection
     */
    public function areAllUserRequiredDocumentsApproved(DBUser $user, array $required_documents_types): bool
    {
        $user_id = $user->getId();
        $documents = $this->getUserDocumentsV2($user_id);

        $cross_brand_documents = $this->getCrossBrandDocuments($user);

        if (!empty($cross_brand_documents)) {
            $cb_document_tags = array_keys($cross_brand_documents);
            $documents = array_filter($documents, static function ($elem) use ($cb_document_tags) {
                return !in_array($elem['tag'], $cb_document_tags);
            });
        }

        // Count the number of approved required documents in both sets
        $approved_docs = 0;
        foreach ($cross_brand_documents as $doc => $values) {
            if (in_array($doc, $required_documents_types) && $values['status'] === self::STATUS_APPROVED) {
                $approved_docs++;
            }
        }

        foreach ($documents as $doc) {
            if (in_array($doc['tag'], $required_documents_types) && $doc['status'] === self::STATUS_APPROVED) {
                $approved_docs++;
            }
        }

        return $approved_docs >= count($required_documents_types);
    }

    /**
     * @param int $retry_num
     * @return int
     */
    private function retries(int $retry_num): int
    {
        return (self::SAVE_DOCUMENT_RETRIES - $retry_num);
    }

    /**
     *  5 ^ 3 = 125 seconds, 5 ^ 2 = 25 seconds, 5 ^ 1 = 5 seconds, 5 ^ 0 = 1 second
     *
     * @param int $retry_num
     * @return int milliseconds
     */
    private function delay(int $retry_num): int
    {
        return (int)pow(5, $this->retries($retry_num)) * 1000;
    }

    /**
     * @return array Returns the document on success, or an error on failure
     */
    private function getDocumentBasedOnUniqueConstraint(int $userId, int $clientId, string $tag, string $subTag)
    {
        $url = "{$this->document_service_base_url}documents/get-document";

        $body = [
            'data' => [
                'user_id'   => $userId,
                'client_id' => $clientId,
                'tag'       => $tag,
                'subtag'    => $subTag
            ]
        ];

        $json = json_encode($body);

        try {
            [$response, $statusCode, $headers] = phive()->post(
                $url,
                $json,
                'application/json',
                $this->headers,
                $this->debugKey('dmapi_get_document'),
                'POST',
                $this->timeout,
                [],
                'UTF-8',
                true
            );
        } catch (Exception $e) {
            return ['errors' => ['Document service not available, please try again later']];
        }

        if ($statusCode === 404) {
            return [];
        }

        $response_decoded = json_decode($response, true);
        if (!empty($response_decoded['errors'])) {
            return $response_decoded;
        }

        return $response_decoded['data'];
    }

    /**
     * Get the status of a document based on its tag for a given user.
     *
     * @param DBUser $user
     * @param string $tag The tag of the document to get the status for.
     * @return string The status of the document corresponding to the given tag, or 'unknown' if not found.
     */
    public function getDocumentStatusFromTag(DBUser $user, $tag)
    {
        $documents = $this->getUserDocumentsV2($user->getId());

        foreach ($documents as $doc) {
            if ($doc['tag'] === $tag) {
                return $doc['status'];
            }
        }

        return 'requested';
    }

    public function checkImage($images)
    {
        $max_file_size = phive('Filer')->getSetting('valid_file_upload')['file_compress_size'];

        foreach ($images as &$image) {
            foreach ($_FILES as $file) {
                if ($file['name'] == $image['original_name']) {
                    if ($this->isImage($image['mime_type']) && $file['size'] > $max_file_size) {
                        $compressedImage = $this->compressedImage($image, $max_file_size);
                        $image['original_name'] = pathinfo($image['original_name'], PATHINFO_FILENAME) . '.' . $compressedImage['extension'];
                        $image['encoded_data'] = $compressedImage['encoded_data'];
                    }
                    break;
                }
            }
        }

        return $images;
    }
    public function compressedImage($image, $max_file_size): array
    {
        try {
            $compressedImageData = (new FittedImage($max_file_size, $image['encoded_data']));
            return ['encoded_data' => $compressedImageData->encoded(), 'extension' => $compressedImageData->extension()];
        } catch (\Exception $e) {
            phive('Logger')->error("Error while compressing the image {$e->getMessage()}.");
            return ['encoded_data' => $image['encoded_data'], 'extension' => pathinfo($image['original_name'], PATHINFO_EXTENSION)];
        }
    }

    public function isImage($type): bool
    {
        $file_types = phive('Filer')->getSetting('valid_file_upload')['allowed_file_mime_types'];
        $image_mime_types = array_filter($file_types, function ($mime_type) {
            return str_starts_with($mime_type, 'image/');
        });
        return in_array($type, $image_mime_types);
    }

    /**
     * Expires documents and returns user IDs with pagination support
     *
     * @param string $tag
     * @param int $brandId
     * @return array
     */
    public function expireDocuments(string $tag = 'idcard-pic')
    {
        $url = "{$this->document_service_base_url}expire-documents";

        $queryParams = [
            'tag' => $tag
        ];
        

        $url .= '?' . http_build_query($queryParams);

        $response = phive()->post($url, '', 'application/json', $this->headers, $this->debugKey('dmapi_expire_documents'), 'POST', $this->timeout);

        if (empty($response)) {
            $result = ['errors' => ['Document service not available, please try again later']];
            return $result;
        }

        $response_decoded = json_decode($response, true);

        return $response_decoded;
    }

    /**
     * Get user IDs of expired documents with pagination support
     *
     * @param int $page
     * @param int $perPage
     * @param string $tag
     * @return array
     */
    public function getExpiredUserIds(int $page = 1, int $perPage = 100, string $tag = 'idcard-pic')
    {
        $url = "{$this->document_service_base_url}get-expired-user-ids";

        // Add pagination and tag parameters to the URL
        $queryParams = [
            'page' => $page,
            'per_page' => min($perPage, 1000),
            'tag' => $tag
        ];

        $url .= '?' . http_build_query($queryParams);

        $response = phive()->post($url, '', 'application/json', $this->headers, $this->debugKey('dmapi_get_expired_user_ids'), 'GET', $this->timeout);
        if (empty($response)) {
            $result = ['errors' => ['Document service not available, please try again later']];
            return $result;
        }

        $response_decoded = json_decode($response, true);

        return $response_decoded;
    }
}
