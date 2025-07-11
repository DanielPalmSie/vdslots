<?php

class UserDocuments
{
    /**
     * @var string
     */
    private const TAG_BANK_ACCOUNT_PIC = 'bankaccountpic';

    /**
     * @var string
     */
    private const TAG_INTERNAL_DOCUMENT_PIC = 'internaldocumentpic';

    /**
     * @var string
     */
    private const TAG_CREDIT_CARD_PIC = 'creditcardpic';

    /**
     * @var string
     */
    public const TAG_ID_CARD_PIC = 'idcard-pic';

    /**
     * @var string
     */
    public const TAG_SOURCE_OF_INCOME_PIC = 'sourceofincomepic';

    /**
     * @var string
     */
    private const TAG_SOURCE_OF_FUNDS_PIC = 'sourceoffundspic';

    /**
     * @var string
     */
    public const STATUS_APPROVED = 'approved';

    /**
     * @var string
     */
    public const STATUS_REQUESTED = 'requested';

    /**
     * @var string
     */
    public const STATUS_REJECTED = 'rejected';

    /**
     * @var string
     */
    public const STATUS_PROCESSING = 'processing';

    /**
     * @var string
     */
    private const FILE_STATUS_EXPIRED = 'expired';

    /**
     * @var string
     */
    private const FORM_DEFAULT_UPLOAD = 'displayUploadForm';

    /**
     * @var string
     */
    private const FORM_SOURCE_OF_INCOME = 'displayUploadFormSourceOfIncome';

    /**
     * @var string
     */
    private const FORM_SOURCE_OF_FUNDS = 'displaySourceoffundsForm';

    /**
     * @var string
     */
    private const FORM_ID_CARD = 'displayUploadFormIDcard';

    /**
     * @var array
     */
    private array $raw_documents;

    /**
     * @var array
     */
    public const INCOME_TYPES = [
        'payslip',
        'pension',
        'inheritance',
        'gifts',
        'tax_declaration',
        'dividends',
        'interest',
        'business_activities',
        'divorce_settlements',
        'gambling_wins',
        'sales_of_property',
        'rental_income',
        'capital_gains',
        'royalty_or_licensing_income',
        'other',
    ];

    /**
     * @var DBUser
     */
    private DBUser $user;

    /**
     * @param array $raw_documents
     * @param DBUser $user
     */
    public function __construct(array $raw_documents, DBUser $user)
    {
        $this->user = $user;
        $this->raw_documents = $raw_documents;
        $this->setLanguage();
    }

    /**
     * @return array
     */
    public function formatDocuments(): array
    {
        $result = [];

        foreach ($this->raw_documents as $document) {
            if ($this->documentHasProperTags($document)) {
                $result[] = $this->formatDocument($document);
            }
        }

        return $result;
    }

    /**
     * @param array $document
     *
     * @return array
     */
    private function formatDocument(array $document): array
    {
        $result = [];

        if(!empty($document['expired'])){
            $document['status'] = self::STATUS_REQUESTED;
        }

        $headline_raw = $document['headline_tag'];
        $doc_headline_text_raw = $this->getDocHeadlineRawText($headline_raw);
        $result['name'] = $headline_raw;
        $result['headline'] = $this->formatHeadline($document['headline_tag']);
        $result['headline_raw'] = $headline_raw;
        $result['doc_headline_text'] = t($doc_headline_text_raw);
        $result['doc_headline_text_raw'] = $doc_headline_text_raw;
        $result['original_names'] = $this->formatOriginalNames($document);
        $result['doc_status'] = $this->formatStatus($document);
        $result = array_merge($result, $this->formatTags($document));

        if ($result['doc_status'] != self::STATUS_APPROVED) {
            switch ($document['tag']){
                case self::TAG_ID_CARD_PIC:
                    $result = array_merge($result, $this->formatIdCardPicUploadForm($document));
                    break;
                case self::TAG_SOURCE_OF_FUNDS_PIC:
                    $result = array_merge($result, $this->formatSourceOfFundsUploadForm($document));
                    break;
                case self::TAG_SOURCE_OF_INCOME_PIC:
                    $result = array_merge($result, $this->formatSourceOfIncomeUploadForm($document));
                    break;
                default:
                    $result = array_merge($result, $this->formatDefaultUploadForm($document));

            }
        } else {
            $result['upload_form_type'] = 'NA';
        }

        return $result;
    }

    /**
     * @return array
     */
    private function getIdCardDropdown(): array
    {
        $result = [];

        $dropdown_options = [
            'PASSPORT' => t('Passportidentity.card'),
            'ID_CARD' => t('identity.card'),
            'DRIVING_LICENSE' => t('driving.license')
        ];

        if (lic('hasDocumentTypeRestriction')) {
            $dropdown_options = lic('getDocumentTypeAllowed');
        }

        foreach ($dropdown_options as $name => $value) {
            $result[] = compact('name', 'value');
        }

        return $result;
    }

    /**
     * @return array
     */
    private function getSourceOfIncome(): array
    {
        $result = [];

        foreach (self::INCOME_TYPES as $name) {
            $result[] = [
                'name' => $name,
                'value' => t('select.income.types.' . $name)
            ];
        }

        return $result;
    }

    /**
     * @param array $document
     *
     * @return bool
     */
    private function documentHasProperTags(array $document): bool
    {
        return !in_array($document['tag'], [self::TAG_BANK_ACCOUNT_PIC, self::TAG_INTERNAL_DOCUMENT_PIC]);
    }

    /**
     * @param array $document
     *
     * @return array
     */
    private function formatOriginalNames(array $document): array
    {
        $result = [];

        if($document['status'] != self::STATUS_APPROVED){
            foreach ($document['files'] as $file) {
                if($file['deleted_at'] != '' && $file['status'] != self::FILE_STATUS_EXPIRED) {
                    continue;
                }
                $result[] = [
                    'name' => empty($file['original_name']) ? 'file.jpg' : $file['original_name'],
                    'status' => $file['status']
                ];
            }
        }

        return $result;
    }

    /**
     * @param array $document
     *
     * @return array
     */
    private function formatTags(array $document): array
    {
        $result = [];

        $tag = $document['tag'];
        if ($tag == self::TAG_CREDIT_CARD_PIC) {
            $tag .= '_' . $document['id'];
        }

        $result['doc_tag_main'] = $tag;
        if (!empty($document['subtag'])
            && $document['tag'] != self::TAG_ID_CARD_PIC
            && $document['tag'] != self::TAG_SOURCE_OF_INCOME_PIC) {
            $result['doc_tag'] = $document['tag'];
            $result['doc_sub_tag'] = $document['subtag'];
        }

        return $result;
    }


    /**
     * @param array $document
     *
     * @return array
     */
    private function formatIdCardPicUploadForm(array $document): array
    {
        if ($document['status'] == self::STATUS_REQUESTED || $document['status'] == self::STATUS_REJECTED) {
            return [
                'upload_form_type' => self::FORM_ID_CARD,
                'upload_form_fields' => [
                    'document_type' => $document['tag'],
                    'document_id' => $document['id'],
                    'image-front' => '',
                    'image-back' => ''
                ],
                'upload_form_dropdown' => $this->getIdCardDropdown(),
            ];
        }

        return [];
    }

    /**
     * @param array $document
     *
     * @return array
     */
    private function formatSourceOfFundsUploadForm(array $document): array
    {
        return [
            'upload_form_type' => self::FORM_SOURCE_OF_FUNDS,
            'upload_form_fields' => [
                'document_id' => $document['id'],
                'link' => 'show_declaration_form'
            ],
            'upload_form_dropdown' => 'NA',
        ];
    }

    /**
     * @param array $document
     *
     * @return array
     */
    private function formatSourceOfIncomeUploadForm(array $document): array
    {
        if (in_array($document['status'], [self::STATUS_REQUESTED, self::STATUS_REJECTED, self::STATUS_PROCESSING])) {
            return [
                'upload_form_type' => self::FORM_SOURCE_OF_INCOME,
                'upload_form_fields' => [
                    'document_type' => $document['tag'],
                    'document_id' => $document['id'],
                    'file' => ''
                ],
                'upload_form_dropdown' => $this->getSourceOfIncome(),
            ];
        }

        return [];
    }

    /**
     * @param array $document
     *
     * @return array
     */
    private function formatDefaultUploadForm(array $document): array
    {
        return [
            'upload_form_type' => self::FORM_DEFAULT_UPLOAD,
            'upload_form_fields' => [
                'document_type' => $document['tag'],
                'document_id' => $document['id'],
                'file' => ''
            ],
            'upload_form_dropdown' => 'NA',
        ];
    }

    /**
     * @param array $document
     *
     * @return string
     */
    private function formatStatus(array $document): string
    {
        return !empty($document['expired']) ? self::STATUS_REQUESTED : $document['status'];
    }

    /**
     * @param string $headline_tag
     *
     * @return string
     */
    private function formatHeadline(string $headline_tag): string
    {
        return t($headline_tag . ".section.headline");
    }

    /**
     * @param string $headline_tag
     *
     * @return string
     */
    private function getDocHeadlineRawText(string $headline_tag): string
    {
        return $headline_tag . '.section.confirm.info';
    }

    /**
     * @return void
     */
    private function setLanguage()
    {
        $forcedLanguage = lic('getForcedLanguage');
        $language = ! empty($forcedLanguage) ? $forcedLanguage : $this->user->getAttr('preferred_lang');
        phive('Localizer')->setLanguage($language);
    }
}
