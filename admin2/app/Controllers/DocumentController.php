<?php

namespace App\Controllers;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Models\User;
use App\Repositories\DocumentsRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Valitron\Validator;
use App\Repositories\ActionRepository;
use App\Repositories\UserRepository;
use App\Repositories\ReplacerRepository;
use App\Models\IpLog;
use Videoslots\FraudDetection\AssignEvent;
use Videoslots\FraudDetection\FraudFlags\SourceOfFundsRequestedFlag;
use Videoslots\FraudDetection\RevokeEvent;

class DocumentController implements ControllerProviderInterface
{
    /**
     * @var string
     */
    private const STATUS_REQUESTED = 'requested';

    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        return $factory;
    }

    public function viewUserDocuments(Application $app, User $user)
    {
        // get all documents from Dmapi
        $documents = phive('Dmapi')->getUserDocumentsV2($user->id);

        $cross_brand_documents = phive('Dmapi')->getCrossBrandDocuments(cu($user->id));

        if (!empty($cross_brand_documents)) {
            //filter out documents with tags that match the cross-brand document tags
            $cb_document_tags = array_keys($cross_brand_documents);
            $filtered_cross_brand_documents = array_filter($documents, static function ($elem) use ($cb_document_tags) {
                return in_array($elem['tag'], $cb_document_tags);
            });
            foreach ($filtered_cross_brand_documents as &$filtered_doc) {
                $filtered_doc['status'] = $cross_brand_documents[$filtered_doc['tag']]['status'];
            }
            $cross_brand_documents = $filtered_cross_brand_documents;
            $documents = array_filter($documents, static function ($elem) use ($cb_document_tags) {
                //if document expired - change status to requested
                if ($elem['expired'] === 1) {
                    $elem['status'] = self::STATUS_REQUESTED;
                }
                return !in_array($elem['tag'], $cb_document_tags);
            });
        }

        $document_repository = new DocumentsRepository($app);
        $reject_reasons      = $document_repository->getRejectReasons();

        $has_source_of_income_document = $document_repository->hasSourceOfIncomeDocument($documents);

        // check if the user has a Source of Wealth document
        $has_source_of_funds_document = $document_repository->hasSourceOfFundsDocument($documents);
        $has_source_of_funds_data     = $document_repository->hasSourceOfFundsData($documents);

        $has_historical_source_of_funds_data  = $document_repository->hasHistoricalSourceOfFundsData($documents);

        $has_internal_document        = $document_repository->hasInternalDocument($documents);
        $has_proofofwealth_document   = $document_repository->hasProofOfWealthDocument($documents);

        $has_proofofsourceoffunds_document   = $document_repository->hasProofOfSourceOfFundsDocument($documents);

        $show_re_request_sow_button          = $document_repository->showReRequestSourceOfWealthButton($documents);
        $sourceoffunds_document_id           = $document_repository->getSourceOfFundsDocumentId($documents);

        $show_source_of_wealth_buttons = $document_repository->showSourceOfWealthButtons($user);
        $show_proof_of_source_of_wealth_buttons = $document_repository->showProofOfSourceOfWealthButtons($user);

        return $app['blade']->view()->make('admin.user.documents.index',
                compact(
                        'app',
                        'user',
                        'documents',
                        'reject_reasons',
                        'cross_brand_documents',
                        'has_source_of_funds_document',
                        'has_source_of_funds_data',
                        'has_historical_source_of_funds_data',
                        'has_internal_document',
                        'has_proofofwealth_document',
                        'has_proofofsourceoffunds_document',
                        'show_re_request_sow_button',
                        'sourceoffunds_document_id',
                        'show_source_of_wealth_buttons',
                        'show_proof_of_source_of_wealth_buttons',
                        'has_source_of_income_document',
                        'has_source_of_income_data',
                        'show_source_of_income_buttons'
                        )
                )->render();
    }

    /**
     * Updates the status of a document
     *
     * @param Application   $app
     * @param User          $user
     * @param Request       $request
     *
     * @return string
     */
    public function updateDocumentStatus(Application $app, User $user, Request $request)
    {
        $document_repository = new DocumentsRepository($app);

        $json = $document_repository->updateDocumentStatus($app, $user, $request);

        return $json;
    }

    /**
     * Updates the expiry date for ID cards
     *
     * @param Application       $app
     * @param User              $user
     * @param Request           $request
     *
     * @return RedirectResponse
     */
    public function updateExpiryDate(Application $app, User $user, Request $request)
    {
        $actor_id       = UserRepository::getCurrentId();
        $document_id    = $request->get('document_id');
        $expiry_date    = $request->get('expiry_date');
        $document_type  = $request->get('document_type');
        $subtag         = $request->get('subtag');

        $validator = new Validator($request->request->all());
        $validator->rule('required', ['expiry_date', 'document_id']);
        $validator->rule('date', 'expiry_date');
        $validator->rule('integer', ['document_id']);
        if(!$validator->validate()) {
            $app['flash']->add('danger', 'This is not a valid date');
            return new RedirectResponse($app['url_generator']->generate('admin.user-documents', ['user' => $user->id]));
        }

        $response = phive('Dmapi')->updateDocumentExpiryDate($actor_id, $document_id, $expiry_date);

        if(empty($response['errors'])) {
            $app['flash']->add('success', 'The expiry date has been updated');
            if ($user->getSetting('idscan_expiry_status')){
                $user_repo = new UserRepository($user);
                $user_repo->deleteSetting('idscan_expiry_status');
            }
            // Log this action
            $description = "Expiry date updated to {$expiry_date} for {$document_type} (document_id = {$document_id})";
            if(!empty($subtag)) {
                $description .= " with subtag = {$subtag}";
            }
            ActionRepository::logAction($user->id, $description, 'id_expiry_date_updated', false, $actor_id);
            IpLog::logIp($actor_id, $user->id, IpLog::TAG_DOCUMENTS, $description);
        } else {
            $app['flash']->add('danger', 'Unable to update expiry date');
        }

        // redirect to viewUserDocuments
        return new RedirectResponse($app['url_generator']->generate('admin.user-documents', ['user' => $user->id]));
    }

    /**
     *
     * @param Application $app
     * @param User $user
     * @param Request $request
     * @return string
     */
    public function updateFileStatus(Application $app, User $user, Request $request)
    {
        $document_repository = new DocumentsRepository($app);

        if(!$document_repository->validateUpdateFileStatusRequest($request)) {
            $app['flash']->add('danger', 'Something went wrong, please try again');
            return json_encode(['success' => false, 'message' => 'Something went wrong, please try again']);
        }

        $actor_id      = UserRepository::getCurrentId();
        $file_id       = $request->get('file_id');
        $status        = $request->get('status');
        $document_type = $request->get('document_type');

        $response = phive('Dmapi')->updateFileStatus($actor_id, $file_id, $status, $user->id);

        if(empty($response['errors'])) {

            // send an email if a single file is rejected
            if($status == 'rejected' && $document_type != 'internaldocumentpic') {
                $document_repository->sendEmailToUser($user, $status, $document_type);
            }

            // Log this action
            $document_id = key($response);
            $description = "File status updated to {$status} for {$document_type} (document_id = {$document_id}, file_id = {$file_id})";
            $subtag  = $response[$document_id]['subtag'];
            if(!empty($subtag)) {
                $description .= " with subtag = {$subtag}";
            }

            // Set the tag, example file-approved, file-rejected
            $status_tag = $document_repository->getDocumentStatusTag($status, 'file');

            ActionRepository::logAction($user->id, $description, $status_tag, false, $actor_id);
            IpLog::logIp($actor_id, $user->id, IpLog::TAG_DOCUMENTS, $description);

            // Send email to user if the document is automaticly approved.
            $key = key($response);
            if($response[$key]['document_automatic_approved']) {

                if($document_type == 'sourceofincomepic') {
                    $user_repo = new UserRepository($user);
                    $user_repo->setSetting('source_of_income_status', 'approved');
                }

                if($document_type != 'internaldocumentpic') {
                    $document_repository->sendEmailToUser($user, $status, $document_type);
                }

                // Log this action too
                $description = "Document status automaticly updated to approved when the last file was approved for {$document_type} (document_id = {$document_id}, file_id = {$file_id})";
                $subtag  = $response[$document_id]['subtag'];
                if(!empty($subtag)) {
                    $description .= " with subtag = {$subtag}";
                }
                ActionRepository::logAction($user->id, $description, 'document_approved_automaticly', false, $actor_id);
                IpLog::logIp($actor_id, $user->id, IpLog::TAG_DOCUMENTS, $description);
                phive('DBUserHandler')->doCheckUnrestrict(cu($user->id), $document_type);
            }

            // Log action when document is automaticly rejected
            if($response[$key]['document_automatic_rejected']) {
                $description = "Document status automaticly updated to rejected when the last file was rejected for {$document_type} (document_id = {$document_id}, file_id = {$file_id})";
                $subtag  = $response[$document_id]['subtag'];
                if(!empty($subtag)) {
                    $description .= " with subtag = {$subtag}";
                }
                ActionRepository::logAction($user->id, $description, 'document_rejected_automaticly', false, $actor_id);
                IpLog::logIp($actor_id, $user->id, IpLog::TAG_DOCUMENTS, $description);
                $u_obj = cu($user->id);
                $restriction_reason = phive('DBUserHandler')->doCheckRestrict($u_obj);

                if ($restriction_reason) {
                    return $u_obj->restrict($restriction_reason);
                }
            }

            $app['flash']->add('success', 'File status updated');

            lic('onDocumentStatusChange', [$user->getKey(), $document_type, $status], $user->getKey());

            lic('onUploadDocument', [$user->getKey()], $user->getKey());
            return json_encode(['success' => true, 'message' => "File status updated.", 'document' => $response]);
        } else {
            return json_encode(['success' => false, 'message' => $response['errors'][0]]);
        }
    }

    public function deleteDocument(Application $app, User $user, Request $request)
    {
        $validator = new Validator($request->query->all());
        $validator->rule('required', ['document_id', 'document_type']);
        $validator->rule('integer', ['document_id']);
        $validator->rule('slug', ['document_type', 'status']);
        if(!$validator->validate()) {
            return json_encode(['success' => false, 'message' => 'Something went wrong, please try again']);
        }

        $document_id   = $request->get('document_id');
        $actor_id      = UserRepository::getCurrentId();
        $status        = $request->get('status');
        $document_type = $request->get('document_type');
        $subtag        = $request->get('subtag');

        $files_of_document = phive('Dmapi')->getFilesOfDocument($document_id);

        $response = phive('Dmapi')->deleteUploadedDocument($document_id, $user->id);

        if(empty($response['errors'])) {

            if($document_type == 'sourceofincomepic') {
                $user_repo = new UserRepository($user);
                $user_repo->deleteSetting('source_of_income_status');
                $user_repo->deleteSetting('source_of_income_activated');
            }

            if($document_type == 'sourceoffundspic') {
                $user_repo = new UserRepository($user);
                $user_repo->deleteSetting('source_of_funds_waiting_since');
                $user_repo->deleteSetting('source_of_funds_activated');
                $user_repo->deleteSetting('source_of_funds_status');
                SourceOfFundsRequestedFlag::create()->revoke(cu($user->id), RevokeEvent::ON_DOC_DELETION_BO);
            }

            $tagname     = t("{$document_type}.section.headline");
            $description = "deleted document {$tagname} with status {$status}";
            ActionRepository::logAction($user->id, $description, 'document_deleted', true, $actor_id);

            foreach ($files_of_document as $file) {
                // Log link to deleted file in actions, and include the status that the file had when it was deleted.
                $url         = phive('UserHandler')->getSetting('document_service_base_url') . 'viewdeletedfile/' . $file['attributes']['uploaded_name'];
                $link        = "<a href=\"{$url}\">view file</a>";
                $description = "deleted file from {$tagname} with status {$file['attributes']['status']}: {$link}";

                ActionRepository::logAction($user->id, $description, 'file_deleted_automaticly', false, $actor_id);
            }

            // Log this action
            $description_2 = "Document {$document_type} deleted (document_id = {$document_id})";
            if(!empty($subtag)) {
                $description_2 .= " with subtag = {$subtag}";
            }
            IpLog::logIp($actor_id, $user->id, IpLog::TAG_DOCUMENTS, $description_2);

            lic('onDeleteDocument', [$user->getKey(), $document_type], $user->getKey());
            return json_encode(['success' => true, 'message' => "Document deleted."]);
        } else {
            return json_encode(['success' => false, 'message' => $response['errors'][0]]);
        }
    }

    public function deleteFile(Application $app, User $user, Request $request)
    {
        $validator = new Validator($request->query->all());
        $validator->rule('required', ['file_id', 'status', 'document_type', 'uploaded_name']);
        $validator->rule('integer', ['file_id']);
        $validator->rule('slug', ['status', 'document_type']);
        $validator->rule('regex', 'uploaded_name', '/^[a-zA-Z0-9-_.]+$/');
        if(!$validator->validate()) {
            return json_encode(['success' => false, 'message' => 'Something went wrong, please try again']);
        }

        $file_id       = $request->get('file_id');
        $actor_id      = UserRepository::getCurrentId();
        $status        = $request->get('status');
        $document_type = $request->get('document_type');
        $uploaded_name = $request->get('uploaded_name');
        $subtag        = $request->get('subtag');

        $response = phive('Dmapi')->deleteUploadedFile($file_id, $user->id);

        if(empty($response['errors'])) {

            // Log link to deleted file in actions, and include the status that the file had when it was deleted.
            $tagname     = t("{$document_type}.section.headline");
            $url         = phive('UserHandler')->getSetting('document_service_base_url') . 'viewdeletedfile/' . $uploaded_name;
            $link        = "<a href=\"{$url}\">view file</a>";
            $description = "deleted file from {$tagname} with status {$status}: {$link}";

            ActionRepository::logAction($user->id, $description, 'file_deleted', true, $actor_id);

            $app['flash']->add('success', 'File deleted');

            // Log this action
            $description_2 = "File deleted from {$document_type} (file_id = {$file_id})";
            if(!empty($subtag)) {
                $description_2 .= " with subtag = {$subtag}";
            }
            IpLog::logIp($actor_id, $user->id, IpLog::TAG_DOCUMENTS, $description_2);

            lic('onUploadDocument', [$user->getKey()], $user->getKey());
            return json_encode(['success' => true, 'message' => "File deleted."]);
        } else {
            return json_encode(['success' => false, 'message' => $response['errors'][0]]);
        }
    }

    public function addMultipleFilesToDocument(Application $app, User $user, Request $request)
    {
        $validator = new Validator($request->request->all());
        $validator->rule('required', ['document_id', 'document_type']);
        $validator->rule('integer', 'document_id');
        $validator->rule('slug', 'document_type');
        if(!$validator->validate()) {
            // show errors
            $app['flash']->add('danger', 'Something went wrong, please try again');
            return json_encode(['success' => false]);
        }

        $actor_id      = UserRepository::getCurrentId();
        $document_id   = $request->get('document_id');
        $document_type = $request->get('document_type');

        // validate file uploads
        $document_repository = new DocumentsRepository($app);
        $error = $document_repository->validateUploadedFiles('upload');
        if(!empty($error)) {
            // show errors
            $app['flash']->add('danger', $error);

            return json_encode(['success' => false]);
        }

        // check permissions
        if(!p('upload.idpic')) {
            $app->abort(401);
        }

        $subtag = '';
        $country = '';
        if($document_type == 'idcard-pic') {
            $data = phive('Dmapi')->handlePostedIDV2($user->id);
            $subtag = $request->get('idtype');

            $country_iso2 = $user->country;

            // convert country code to iso3
            $country = phive('Cashier')->getIso3FromIso2($country_iso2);

        } else if($document_type == 'sourceofincomepic') {
            $subtag = json_encode(['types' => $request->get('income_types')]);
            $data = phive('Dmapi')->handlePostedDocument($user->id);

            foreach ($data['files'] as $key => $file) {
                $data['files'][$key]['tag'] = $request->get('income_types')[$key];
            }

            if (count(array_filter($_POST['income_types'])) !== count($_FILES)) {
                $data['errors'] = 'error.income.type.not.selected';
            }
        }
        else {
            $data = phive('Dmapi')->handlePostedDocument($user->id);
        }

        if(!empty($data['errors'])) {
            $errors = is_array($data['errors'])? implode(', ', $data['errors']) : $data['errors'];

            $app['flash']->add('danger', $errors);
        } else {
            $checkedImage =  phive('Dmapi')->checkImage($data['files']);

            $result = phive('Dmapi')->addMultipleFilesToDocument($document_id,  $checkedImage, $subtag, $country, $user->id);
            phive('Dmapi')->updateDocumentStatus($user->id, $document_id, 'processing', $user->id);

            if(!empty($result['errors']))  {
                $app['flash']->add('danger', $result['errors']);
            } else {
                $app['flash']->add('success', 'The file(s) were succesfully added to the document');

                // Log this action
                $description = "New file(s) uploaded for {$document_type} (document_id = {$document_id})";
                if(!empty($subtag)) {
                    $description .= " subtag = {$subtag}";
                }
                ActionRepository::logAction($user->id, $description, 'file_uploaded', false, $actor_id);
                IpLog::logIp($actor_id, $user->id, IpLog::TAG_DOCUMENTS, $description);
                phive('DBUserHandler')->doCheckUnrestrict(cu($user->id), $document_type);
            }
        }

        lic('onUploadDocument', [$user->getKey()], $user->getKey());

        // Return the document as json
        return json_encode(['success' => true, 'document' => $result]);
    }

    /**
     * Replace an existing file with another file
     * This will only be used for credit card documents
     */
    public function replaceFile(Application $app, User $user, Request $request)
    {
        $resizeImage = phive('BoxHandler/boxes/diamondbet/GmsSignupUpdateBoxBase');
        // validate file uploads
        $errors = phive('Dmapi')->validateUploadedFiles('upload');
        if(!empty($errors)) {

            // show errors
            $app['flash']->add('danger', t($errors['upload']));

            // redirect to viewUserDocuments
            return new RedirectResponse($app['url_generator']->generate('admin.user-documents', ['user' => $user->id]));
        }

        $file_id       = $request->get('file_id');
        $actor_id      = UserRepository::getCurrentId();
        $document_id   = $request->get('document_id');
        $document_type = $request->get('document_type');
        $subtag        = $request->get('subtag');

        $data = phive('Dmapi')->handlePostedDocument($user->id);

        if(!empty($data['errors'])) {

            $app['flash']->add('danger', $data['errors']);
        } else {
            $checkedImage['files'] =  phive('Dmapi')->checkImage($data['files']);
            $result = phive('Dmapi')->replaceUploadedFile($checkedImage, $file_id, $user->id);

            if(!empty($result['errors']))  {
                $app['flash']->add('danger', $result['errors']);
            } else {
                // Log this action
                $description = "File replaced for {$document_type} (document_id = {$document_id}, file_id = $file_id)";
                if(!empty($subtag)) {
                    $description .= " with subtag = {$subtag}";
                }
                ActionRepository::logAction($user->id, $description, 'file_replaced', false, $actor_id);
                IpLog::logIp($actor_id, $user->id, IpLog::TAG_DOCUMENTS, $description);

                $app['flash']->add('success', 'The file was succesfully replaced');
                lic('onUploadDocument', [$user->getKey()], $user->getKey());
            }
        }

        // redirect to viewUserDocuments
        return new RedirectResponse($app['url_generator']->generate('admin.user-documents', ['user' => $user->id]));
    }

    /**
     * Lists all documents with status 'processing'
     */
    public function listDocuments(Application $app)
    {
        $document_repository = new DocumentsRepository($app);

        $documents_list = $document_repository->getDocumentsByStatus('processing');

        $documents_list = $document_repository->filterOutCrossBrandDocuments($documents_list);

        return $app['blade']->view()->make('admin.user.listdocuments', compact('app', 'documents_list'))->render();
    }


    /**
     * Lists all non-verified Source of Wealth documents
     */
    public function listSourceOfFunds(Application $app)
    {
        $document_repository = new DocumentsRepository($app);

        $documents = $document_repository->getUnverifiedSourceOfFundsDocuments();

        return $app['blade']->view()->make('admin.user.listsourceoffunds', compact('app', 'documents'))->render();
    }


    /**
     * Rerenders a document so we can update the page with ajax
     *
     * @param  Application $app
     * @param  User        $user
     * @param  Request     $request
     * @return string
     */
    public function rerenderDocument(Application $app, User $user, Request $request)
    {
        $data       = $request->request->all();
        $key        = key($data['document']);
        $document   = $data['document'][$key];

        $has_source_of_funds_data            = empty($document['source_of_funds_data']) ? false : true;
        $has_historical_source_of_funds_data = empty($document['historical_source_of_funds_data']) ? false : true;

//        ISSUE:
//        after clicking Re-request, the historical forms are not regenerated properly.
//
//        The last form in the list (first one submitted) is not clickable.
//        The first form in the list (The one that was archived by the re-request button) shows the form that was submitted before that.

//        if($document['tag'] == 'sourceoffundspic') {
////            return new RedirectResponse($request->headers->get('referer'));
//        }

        return $app['blade']->view()
            ->make('admin.user.documents.partials.document',
                    compact(
                        'app',
                        'document',
                        'user',
                        'has_source_of_funds_data',
                        'has_historical_source_of_funds_data'
                    ))
            ->render();
    }

    /**
     * Rerenders the partial that shows the verify account button and the account's status
     *
     * @param  Application $app
     * @param  User        $user
     * @return string
     */
    public function rerenderVerifyAccountPartial(Application $app, User $user)
    {
        return $app['blade']->view()
            ->make('admin.user.documents.partials.verify_user_account_button', compact('app', 'user'))
            ->render();
    }

    /**
     * Creates a new document for Source of Wealth. The tag will be 'sourceoffundspic'.
     *
     * @param Application $app
     * @param User        $user
     * @param Request     $request
     */
    public function createSourceOfFunds(Application $app, User $user, Request $request)
    {
        $actor_id = UserRepository::getCurrentId();

        // Implement the Re-request logic here for approved sourceoffundspics
        // We need to update the status of the source_of_funds_data to 'archived',
        // and update the status of the document itself to 'requested'

        if($request->get('status') == 'archived') {
            $re_request = true;
            $document_repository = new DocumentsRepository($app);
            $json = $document_repository->updateDocumentStatus($app, $user, $request);
        } else {
            $result   = phive('Dmapi')->createAndReturnEmptyDocument($user->id, 'sourceoffunds', $actor_id);
        }

        $user_repo = new UserRepository($user);
        $user_repo->setSetting('source_of_funds_status', 'requested');
        $user_repo->setSetting('source_of_funds_activated', '1');
        SourceOfFundsRequestedFlag::create()->assign(cu($user->id), AssignEvent::ON_DOC_CREATION_BO);
        $user_repo->deleteSetting('source_of_funds_self_approval');
        $user_repo->setSetting('source_of_funds_admin_approval', 1);

        if (false) {  // TODO: return false if we are unable to create the document
            return json_encode(['success' => false]);
        }

        // Return the document as json
        if($re_request) {
            return $json;
        }
        return json_encode(['success' => true, 'document' => $result]);
    }

    /**
     * Creates a new document for Source of Income. The tag will be 'sourceofincomepic'.
     *
     * @param Application $app
     * @param User        $user
     * @param Request     $request
     */
    public function createSourceOfIncome(Application $app, User $user, Request $request)
    {
        $actor_id = UserRepository::getCurrentId();
        $result   = phive('Dmapi')->createAndReturnEmptyDocument($user->id, 'sourceofincome', $actor_id);

        $user_repo = new UserRepository($user);

        $user_repo->setSetting('source_of_income_status', 'requested');
        $user_repo->setSetting('source_of_income_activated', '1');

        return json_encode(['success' => true, 'document' => $result]);
    }

    /**
     * Creates a new 'internal document'. The tag will be 'internaldocumentpic'.
     *
     * @param Application $app
     * @param User        $user
     * @param Request     $request
     */
    public function createInternalDocument(Application $app, User $user, Request $request)
    {
        $actor_id = UserRepository::getCurrentId();
        $result = phive('Dmapi')->createAndReturnEmptyDocument($user->id, 'internaldocument', $actor_id);

        if (false) {  // TODO: return false if we are unable to create the document
            return json_encode(['success' => false]);
        }

        // Return the document as json
        return json_encode(['success' => true, 'document' => $result]);
    }

    /**
     * Creates a new Proof of Wealth document. The tag will be 'proofofwealthpic'.
     *
     * @param Application $app
     * @param User        $user
     * @param Request     $request
     */
    public function createProofOfWealthDocument(Application $app, User $user, Request $request)
    {
        $actor_id = UserRepository::getCurrentId();
        $result   = phive('Dmapi')->createAndReturnEmptyDocument($user->id, 'proofofwealth', $actor_id);

        $user_repo = new UserRepository($user);
        $user_repo->setSetting('proof_of_wealth_activated', '1');

        if (false) {  // TODO: return false if we are unable to create the document
            return json_encode(['success' => false]);
        }

        // Send email to the user, to let him know he need to upload a new document before he can make a withdrawal.
        if (phive()->moduleExists("MailHandler2")) {
            $phive_user          = phive('UserHandler')->getUser($user->id);
            $replacer_repository = new ReplacerRepository();
            $replacers           = $replacer_repository->getDefaultReplacers($user);

            phive("MailHandler2")->sendMail('proofofwealth.requested', $phive_user, $replacers);
        }

        // Return the document as json
        return json_encode(['success' => true, 'document' => $result]);
    }

    /**
     * Creates a new Proof of Wealth document. The tag will be 'proofofwealthpic'.
     *
     * @param Application $app
     * @param User        $user
     * @param Request     $request
     */
    public function createProofOfSourceOfFundsDocument(Application $app, User $user, Request $request)
    {
        $actor_id = UserRepository::getCurrentId();
        $result   = phive('Dmapi')->createAndReturnEmptyDocument($user->id, 'proofofsourceoffunds', $actor_id);

        $user_repo = new UserRepository($user);
        $user_repo->setSetting('proof_of_source_of_funds_activated', '1');

        if (false) {  // TODO: return false if we are unable to create the document
            return json_encode(['success' => false]);
        }

        // Send email to the user, to let him know he need to upload a new document before he can make a withdrawal.
        if (phive()->moduleExists("MailHandler2")) {
            $phive_user          = phive('UserHandler')->getUser($user->id);
            $replacer_repository = new ReplacerRepository();
            $replacers           = $replacer_repository->getDefaultReplacers($user);

            phive("MailHandler2")->sendMail('proofofsourceoffunds.requested', $phive_user, $replacers);
        }

        // Return the document as json
        return json_encode(['success' => true, 'document' => $result]);
    }


//    public function updateSourceOfFundsForm()
//    {
////        if(!empty($data['errors'])) {
////
////            $app['flash']->add('danger', $data['errors']);
////        } else {
////            $result = phive('Dmapi')->replaceUploadedFile($data, $file_id, $user->id);
////
////            if(!empty($result['errors']))  {
////                $app['flash']->add('danger', $result['errors']);
////            } else {
////                // Log this action
////                $description = "File replaced for {$document_type} (document_id = {$document_id}, file_id = $file_id)";
////                if(!empty($subtag)) {
////                    $description .= " with subtag = {$subtag}";
////                }
////                ActionRepository::logAction($user->id, $description, 'file_replaced', false, $actor_id);
////                IpLog::logIp($actor_id, $user->id, IpLog::TAG_DOCUMENTS, $description);
////
////                $app['flash']->add('success', 'The file was succesfully replaced');
////            }
////        }
//
//        // redirect to viewUserDocuments
//        return new RedirectResponse($app['url_generator']->generate('admin.user-documents', ['user' => $user->id]));
//    }

}
