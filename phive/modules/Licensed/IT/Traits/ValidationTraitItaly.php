<?php
namespace IT\Traits;

require_once __DIR__ . '/../../../../traits/ValidationTrait.php';

use IT\Pacg\Rules\BonusCancellationTypeRule;
use IT\Pacg\Rules\BonusOperationReasonTypeRule;
use IT\Pacg\Rules\DocumentTypeRule;
use IT\Pacg\Rules\GamingFamilyRule;
use IT\Pacg\Rules\LegalEntityAccountTypeRule;
use IT\Pacg\Rules\LimitTypeRule;
use IT\Pacg\Rules\SelfExclusionTypeRule;
use IT\Pacg\Rules\ServiceOperationReasonCodeRule;
use IT\Pacg\Rules\TransactionReasonCodeRule;
use IT\Pacg\Rules\PersonalDataOriginRule;
use IT\Pacg\Rules\TrasversalSelfExclusionManagementRule;
use IT\Pacg\Rules\TrasversalSelfExclusionTypeRule;

/**
 * Trait ValidationTraitItaly
 * @package IT\Traits
 */
trait ValidationTraitItaly
{
    use \ValidationTrait {
        \ValidationTrait::validate as parentValidate;
    }

    /**
     * @param $data
     * @throws \Rakit\Validation\RuleQuashException
     */
    public function validate($data)
    {
        $validators = [
            'document_type' => new DocumentTypeRule(),
            'gaming_family' => new GamingFamilyRule(),
            'limit_type' => new LimitTypeRule(),
            'self_exclusion_type' => new SelfExclusionTypeRule(),
            'trasversal_self_exclusion_type'=> new TrasversalSelfExclusionTypeRule(),
            'trasversal_self_exclusion_management'=> new TrasversalSelfExclusionManagementRule(),
            'service_operation_reason_code' => new ServiceOperationReasonCodeRule(),
            'transaction_reason_code' => new TransactionReasonCodeRule(),
            'personal_data_origin_type' => new PersonalDataOriginRule(),
            'bonus_cancelation_type' => new BonusCancellationTypeRule(),
            'bonus_operation_reason_type' => new BonusOperationReasonTypeRule(),
            'legal_entity_account_type' => new LegalEntityAccountTypeRule(),
        ];

        $this->parentValidate($data, $validators);
    }

    /**
     * Return the country name by the iso code
     *
     * @param $iso
     * @return string
     */
    public function getCountryName($iso)
    {
        $countries = phive('Cashier')->getBankCountries('', true);
        $mappedCountries = array_combine(array_map(fn($country) => $country['iso'], $countries),  array_map(fn($country) => $country['name'], $countries));

        if(!$mappedCountries[$iso]) {
            phive()->dumpTbl('it-error-country-selection', ['it-user-country-selected-not-exists', $iso, 'method' => __METHOD__]);
            return 'Unknown';
        }

        return $mappedCountries[$iso];
    }
}
