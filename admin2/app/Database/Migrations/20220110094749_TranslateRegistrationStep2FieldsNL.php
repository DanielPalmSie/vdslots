<?php

use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class TranslateRegistrationStep2FieldsNL extends Migration
{
    private string $table = 'localized_strings';
    private array $data = [
        [
            'alias' => 'register.firstname_initials',
            'language' => 'en',
            'value' => 'Initials (First Names)'
        ],
        [
            'alias' => 'register.citizen_service_number',
            'language' => 'en',
            'value' => 'Citizen Service Number'
        ],
        [
            'alias' => 'register.birth_place',
            'language' => 'en',
            'value' => 'Place of Birth'
        ],
        [
            'alias' => 'registration.doc_type.nostar',
            'language' => 'en',
            'value' => 'Document Type'
        ],
        [
            'alias' => 'registration.doc_number.nostar',
            'language' => 'en',
            'value' => 'Document Number'
        ],
        [
            'alias' => 'registration.doc_issued_by.nostar',
            'language' => 'en',
            'value' => 'Document Issued By'
        ],
        [
            'alias' => 'registration.doc_type.input-placeholder',
            'language' => 'en',
            'value' => 'Choose Type'
        ],
        [
            'alias' => 'registration.doc_issue_date.nostar',
            'language' => 'en',
            'value' => 'Date of Issue'
        ],
        [
            'alias' => 'registration.doc_number.input-placeholder',
            'language' => 'en',
            'value' => 'Number'
        ],
        [
            'alias' => 'register.iban',
            'language' => 'en',
            'value' => 'IBAN'
        ],
        [
            'alias' => 'register.honest_player',
            'language' => 'en',
            'value' => 'I confirm I will not use my account to engage in criminal activity, to only gamble using my own personal account & that I have input truthfully my information.'
        ],
        [
            'alias' => 'register.iban.description-top',
            'language' => 'en',
            'value' => 'Your bank account and player account name must be the same.'
        ],
        [
            'alias' => 'authorities.dutch',
            'language' => 'en',
            'value' => 'Dutch authorities'
        ],
        [
            'alias' => 'authorities.foreign',
            'language' => 'en',
            'value' => 'Foreign authorities'
        ],
        [
            'alias' => 'documents.residence-permit',
            'language' => 'en',
            'value' => 'Residence Permit'
        ],
        [
            'alias' => 'documents.alien-travel',
            'language' => 'en',
            'value' => "Alien's travel document"
        ],
        [
            'alias' => 'documents.refugee-travel',
            'language' => 'en',
            'value' => 'Refugee travel document'
        ],
    ];

    public function up()
    {
        foreach ($this->data as $item) {
            $localized_string = DB::getMasterConnection()
                ->table($this->table)
                ->where('alias', '=', $item['alias'])
                ->where('language', '=', $item['language'])
                ->first();

            if (!empty($localized_string)) {
                continue;
            }

            DB::getMasterConnection()->table($this->table)->insert($item);
        }
    }

    public function down()
    {
        foreach ($this->data as $item) {
            DB::getMasterConnection()
                ->table($this->table)
                ->where('alias', '=', $item['alias'])
                ->where('language', '=', $item['language'])
                ->delete();
        }
    }
}
