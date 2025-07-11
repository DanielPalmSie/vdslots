<?php

use DBUserHandler\DBUserRestriction;
use Videoslots\FraudDetection\FraudFlags\SourceOfFundsRequestedFlag;
use Videoslots\FraudDetection\RevokeEvent;

require_once __DIR__ . '/../../phive.php';

if(!phive('Permission')->hasPermission('documents.edit.sourceoffunds')) {
    $language = cuAttr('preferred_lang');
} else {
    $language = 'en';
}
phive('Localizer')->setLanguage($language);
phive('Localizer')->setNonSubLang($language);

/** @var $user_handler DBUserHandler */
$user_handler = phive('UserHandler');
/** @var $dmapi Dmapi */
$dmapi = phive('Dmapi');
$result       = ['status' => 'error'];
$user_id      = $_POST['user_id']; // TODO @Paolo check if manually editing this on the form enable the user to submit for a different user, not sure if other checks are done behind the scene...
$check_restrict = false;

if (isset($_POST['name_of_account_holder'])) {

    // Validation
    $errors = $user_handler->validateSourceOfFundsForm($user_id, $_POST);

    if (empty($errors)) {
        $data = $user_handler->prepareSourceOfFundsData($user_id, $_POST);
        $user = cu($user_id);
        // Send the form data to the Dmapi.
        // Sends an update request, if the form is submitted by an admin
        if(!phive('Permission')->hasPermission('documents.edit.sourceoffunds')) {
            $dmapi_result = $dmapi->createSourceOfFundsData($user_id, $data);
            $check_restrict = true;
        } else {
            $dmapi_result = $dmapi->updateSourceOfFundsData($user_id, $data);
        }

        if (empty($dmapi_result['errors'])) {   // also empty with error 409 This document has already been submitted.

            /**
             * The user successfully submitted the data, so we can remove the settings,
             * to avoid popup being trigger on any page.
             * Also, we remove the reasons that cause 'sowd' restriction
             */
            $user->deleteSettings(['sowd-enforce-verification', 'source_of_funds_waiting_since']);

            /**
             * We should immediately "unRestrict()" the user if the initial reason was 'sowd'
             */
            if ($user->getSetting('restriction_reason') === DBUserRestriction::SOWD) {
                $user->unRestrict();
            }

            if ($user->hasSetting('source_of_funds_self_approval') && !$user->hasSetting('source_of_funds_admin_approval')) {
                $dmapi->updateDocumentStatus($user_id, $data['document_id'], 'approved', $user_id);
                $user->deleteSetting('source_of_funds_status');
                $user->deleteSetting('source_of_funds_activated');
                SourceOfFundsRequestedFlag::create()->revoke($user, RevokeEvent::ON_DOC_REQUESTED_DATA_PROVISION_BO);
                $user->deleteSetting('source_of_funds_self_approval');
            } else {
                $user->setSetting('source_of_funds_status', 'processing');

                /**
                 * re-trigger doCheckRestrict() to check if user has to be restricted for other reason
                 */
                if (!empty($check_restrict)) {
                    $restriction_reason = phive('DBUserHandler')->doCheckRestrict($user);

                    if ($restriction_reason) {
                        $user->restrict($restriction_reason);
                    }
                }
            }

            $result['status'] = 'ok';
            $result['mobile'] = phive()->isMobile() == true ? '1' : '0';
            phive('UserHandler')->logAction($user_id, cuAttr('username')." submitted source of funds form", 'source_of_funds_submitted');

            $user->addComment('An RG interaction with customer was made to check customers affordability, to establish source of funds and source of wealth. The document is now available in the documents section under “Source of Wealth Declaration"', 0, 'rg-action');

            lic('updateNDLBasedOnSOWd', [$user, $data['annual_income']], $user);
        } else {
            $result['errors'] = ['Error saving form data'];
        }

    } else {

        $result['errors'] = $errors;
        $result['info']   = $user_handler->errorZone2($errors, true);
    }

}

echo json_encode($result);

