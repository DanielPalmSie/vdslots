<?php


trait DocumentStatusTrait
{

    /**
     * @param $user
     * @param $documentTypes
     * @return bool|void
     */
    public function documentsUploadedOrApproved($user, $documentTypes)
    {
        $user = cu($user);

        if (empty($user)) {
            return;
        }

        $documents = phive('Dmapi')->getUserDocumentsV2($user->getId());

        if (empty($documents) || $documents === 'service not available') {
            return;
        }
        $filtered_docs_by_type = array_filter($documents, function ($document) use ($documentTypes) {
            return in_array($document['tag'], $documentTypes);
        });

        $result = true;

        if (count($filtered_docs_by_type) !== count($documentTypes)) {
            $result = false;
        } else {
            foreach ($filtered_docs_by_type as $file) {
                if (!in_array($file['status'], ['processing', 'approved'])) {
                    $result = false;
                    break;
                }
            }
        }

        return $result;
    }

    /**
     *  Check user's document status
     * @param $user
     * @param array $documentTypes
     * @return void
     */
    private function documentUploadUserVerification($user, array $documentTypes)
    {
        $user = cu($user);
        $documents_uploaded_or_approved = $this->documentsUploadedOrApproved($user, $documentTypes);


        if ($documents_uploaded_or_approved) {
            $user->deleteSetting('play_block');
        } else {
            if (!$user->isPlayBlocked()) {
                $user->playBlock();
            }
            if ($user->isVerified()) {
                $user->unVerify();
            }
        }
    }

    /**
     * Block user If one of required documents is deleted
     * @param $user
     * @param string $documentType
     * @param array $documentTypes
     * @return void
     */
    private function documentDeleteUserVerification($user, string $documentType, array $documentTypes)
    {
        if (!in_array($documentType, $documentTypes)) {
            return;
        }

        $user_obj = cu($user);

        if (empty($user_obj)) {
            return;
        }

        $user_obj->unVerify();
        $user_obj->playBlock();
    }
}