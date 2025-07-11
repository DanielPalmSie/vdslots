<?php 
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringsForRegistrationStep2Ontario extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'register.industry.nostar' => 'Industry',
            'register.occupation.nostar' => 'Occupation',
            'register.aml.nostar' => 'I confirm I will not use my account to engage in criminal activity, to only gamble using my own personal account & that I have input truthfully my information',
            'legal.age.nostar' => 'I confirm that I am fit to play and that I am of legal age to gamble.',
            'pep.check.nostar' => 'I confirm that I am not a PEP, head of international organization or a family member or close associate of such persons.',
            ]
        ];
}