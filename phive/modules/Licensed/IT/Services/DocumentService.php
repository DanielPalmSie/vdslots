<?php
namespace IT\Services;

/**
 * Class DocumentService
 * @package IT\Services
 */
class DocumentService
{
    /**
     * @var array
     */
    private $doc_type = [];

    /**
     * DocumentService constructor.
     * @param array $settings
     */
    public function __construct(array $settings)
    {
        $this->doc_type = $settings['doc_type'];
    }

    /**
     * @param \DBUser $user
     * @return array
     */
    public function getDocumentType($user): array
    {
        return $this->doc_type[$user->getSetting('doc_type')] ?? [];
    }

    /**
     * @param \DBUser $user
     * @return array
     */
    public function createEmptyDocumentRequest($user): array
    {
        $document_type = $this->getDocumentType($user);
        if (empty($document_type)) {
            return [];
        }

        return [$user->getId(), $document_type['tag'], '', $document_type['subtag']];
    }

    /**
     * Creates an empty document with status 'requested'
     *
     * @param \DBUser $user
     * @return bool
     */
    public function createEmptyDocument($user): bool
    {
        $request_documents = $this->createEmptyDocumentRequest($user);
        if (empty($request_documents)) {
            return false;
        }

        $this->persistEmptyDocument($request_documents);

        return true;
    }

    /**
     * @param array $request_documents
     * @return void
     */
    protected function persistEmptyDocument(array $request_documents)
    {
        phive('Dmapi')->createEmptyDocument(... $request_documents);
    }

    /**
     * @param \DBUser $user
     * @return array
     */
    public function getDocuments($user): array
    {
        $documents = $this->getDocumentListFromDmapi($user);
        if (empty($documents)) {
            return [];
        }
        return $documents;
    }

    /**
     * @param \DBUser $user
     * @return array
     */
    public function filterDocuments($user): array
    {
        $documents = $this->getDocuments($user);
        return array_filter(
            $this->getInputDocumentList(),
            function ($key) use ($documents) {
                foreach ($documents as $document) {
                    if ($document['tag'] == 'idcard-pic' && $key == $document['subtag']) {
                        return true;
                    }
                }
                return false;
            },
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * @return array
     */
    protected function getInputDocumentList(): array
    {
        $document_type_list = $this->getEmptyDocumentType();
        $input_document_list = [];
        foreach ($document_type_list as $id_document_type =>  $name_document) {
            $input_document_list[$this->doc_type[$id_document_type]['subtag']] = $name_document;
        }
        return $input_document_list;
    }

    /**
     * @return array
     */
    public function getEmptyDocumentType(): array
    {
        $document_list = $this->getDocumentTypeListFromConfig();
        foreach ($document_list as $key => &$document_name) {
            $document_name = t($document_name);
        }

        return $document_list;
    }

    /**
     * @param \DBUser $user
     * @return array
     */
    protected function getDocumentListFromDmapi($user): array
    {
        return phive('Dmapi')->getDocuments($user->getId());
    }

    /**
     * @return array
     */
    public function getDocumentTypeListFromConfig(): array
    {
        $document_type_list = [];
        foreach ($this->doc_type as $key => $doc_type) {
            $document_type_list[$key] = $doc_type['name'];
        }

        return $document_type_list;
    }

    /**
     * @param int $doc_type_id
     * @return array
     */
    public function getIssuingAuthorityList(int $doc_type_id): array
    {
        $issuing_authority_list = [];
        foreach ($this->doc_type[$doc_type_id]['issuing_authority'] ?? [] as $option)
        {
            $issuing_authority_list[$option] = $option;
        }
        return $issuing_authority_list;
    }
}