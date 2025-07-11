<?php
namespace IT\Services;

use DBUser;
use IT\Pacg\Codes\ReturnCode;
use IT\Pacg\Tables\PersonalDataOriginType;

/**
 * Class RegistrationService
 * @package IT\Services
 */
class RegistrationService implements PayloadInterface
{
    /**
     * PersonalDataOriginType
     */
    const DEFAULT_DATA_ORIGIN_TYPE = 1; // manual input of data
    /**
     * @var object
     */
    private $user;

    /**
     * Gender
     */
    const FEMALE = 'F';
    const MALE = 'M';

    /**
     * Limit types
     */
    const LIMIT_TYPES = [
        1 => 'day',
        2 => 'week',
        3 => 'month'
    ];

    const ERROR_CODE_BY_INPUT_NAME = [
        1201 => 'fiscal_code',
        1232 => 'main_province',
        1300 => 'fiscal_code',
        1301 => 'fiscal_code',
        1302 => 'birthyear',
        1303 => 'fiscal_code',
        1304 => 'fiscal_code'
    ];

    /**
     * RegistrationService constructor.
     * @param DBUser $user | null
     */
    public function __construct($user = null)
    {
        $this->user = $user;
    }

    /**
     * @return array
     */
    public function getPayload(): array
    {
        return [
            'account_code'   => $this->user->data['id'],
            'account_holder' => $this->getAccountHolderData(),
            'limits' => $this->getLimitsData(),
            'personal_data_origin_type' => self::DEFAULT_DATA_ORIGIN_TYPE
        ];
    }

    /**
     * @return array
     */
    private function getBirthData(): array
    {
        $city = $_POST['birth_city'];
        $province = $_POST['birth_province'];
        return [
            'date_of_birth' => [
                'day' => date('d', strtotime($_POST['dob'])),
                'month' => date('m', strtotime($_POST['dob'])),
                'year' => date('Y', strtotime($_POST['dob'])),
            ],
            'birthplace'=> empty($city) ? 'none' : $city,
            'birthplace_province_acronym' => empty($province) ? 'NN' : $province,
            'country'=> $_POST['birth_country']
        ];
    }

    /**
     * @return array
     */
    private function getResidenceData(): array
    {
        $province = $_POST['main_province'];
        return [
            'residential_address' => $_POST['main_address'],
            'municipality_of_residence' => $_POST['main_city'],
            'residential_province_acronym' => empty($province) ? 'NN' : $province,
            'residential_post_code' => $_POST['cap'],
            'country' => $_POST['main_country'],
        ];
    }

    /**
     * @return array
     */
    private function getDocumentData(): array
    {
        return [
            'document_type' => $_POST['doc_type'],
            'date_of_issue' => [
                'day' => $_POST['doc_date'],
                'month' => $_POST['doc_month'],
                'year' => $_POST['doc_year'],
            ],
            'document_number' => $_POST['doc_number'],
            'issuing_authority' => $_POST['doc_issued_by'],
            'where_issued' => $_POST['doc_place'],
        ];
    }

    /**
     * @return string
     */
    private function getGender(): string
    {
        return strtolower($_POST['sex']) == 'male' ? self::MALE : self::FEMALE;
    }

    /**
     * @return array
     */
    private function getAccountHolderData(): array
    {
        return [
            'tax_code' => $_POST['fiscal_code'],
            'surname' => $this->charsetDecodeUtf($_POST['lastname']),
            'name' => $this->charsetDecodeUtf($_POST['firstname']),
            'gender' => $this->getGender(),
            'email' => $this->user->data['email'],
            'pseudonym' => $this->user->data['username'],
            'birth_data' => $this->getBirthData(),
            'residence' => $this->getResidenceData(),
            'document' => $this->getDocumentData(),
        ];
    }

    /**
     * @return array
     */
    public function getLimits(): array
    {
        return json_decode($this->user->getSetting('registration_progress_limits'), true);
    }

    /**
     * @return array
     */
    private function getLimitsData(): array
    {
        $limit_type_list = array_flip(self::LIMIT_TYPES);

        $limit_list = [];
        $limits = $this->getLimits();
        foreach ($limits as $limit) {
            $limit_list[] = [
                'limit_type' => $limit_type_list[$limit['time_span']],
                'amount' => $limit['limit'],
            ];
        }

        return $limit_list;
    }

    /**
     * Return step2 registration data
     * @return array
     */
    public function getStep2Data(): array
    {
        $data = json_decode($this->user->getSetting('step2_data'), true);
        $settings = $this->getStep2Settings();

        $data['main_address'] = $data['address'];
        $data['cap'] = $data['zipcode'];
        $data['fiscal_code'] = $settings['fiscal_code']['value'] ?? null;
        $data['doc_type'] = $settings['doc_type']['value'] ?? null;
        $data['doc_number'] = $settings['doc_number']['value'] ?? null;
        $data['doc_date'] = $settings['doc_date']['value'] ?? null;
        $data['doc_month'] = $settings['doc_month']['value'] ?? null;
        $data['doc_year'] = $settings['doc_year']['value'] ?? null;
        $data['doc_issued_by'] = $settings['doc_issued_by']['value'] ?? null;
        $data['doc_place'] = $settings['doc_place']['value'] ?? null;
        $data['main_country'] = $settings['main_country']['value'] ?? null;
        $data['main_province'] = $settings['main_province']['value'] ?? null;
        $data['main_city'] = $data['city'];
        $data['birth_country'] = $settings['birth_country']['value'] ?? null;
        $data['birth_province'] = $settings['birth_province']['value'] ?? null;
        $data['birth_city'] = $settings['birth_city']['value'] ?? null;

        return empty($data) ? [] : $data;
    }

    /**
     * Return a list of registration data from the db
     * @return array
     */
    protected function getStep2Settings(): array
    {
        return $this->user->getSettingsIn(
            [
                'fiscal_code',
                'doc_type',
                'doc_number',
                'doc_date',
                'doc_month',
                'doc_year',
                'doc_issued_by',
                'doc_place',
                'main_country',
                'main_province',
                'birth_country',
                'birth_province',
                'birth_city',
            ],
            true
        );
    }

    /**
     * Return the input name by error code or an empty string
     * @param int $error_code
     * @return string
     */
    public function getInputNameByErrorCode(int $error_code): string
    {
        return self::ERROR_CODE_BY_INPUT_NAME[$error_code] ?? '';
    }

    /**
     * @param array $response
     * @return array
     */
    public function saveOpenAccountNaturalPersonResponse(array $response): array
    {
        $response['success'] = ($response['code'] == ReturnCode::SUCCESS_CODE) ? 1 : 0;

        $this->user->setSetting('open_account_natural_person', $response['success']);

        return $response;
    }

    /**
     * @param string $string
     * @return string
     */
    protected function charsetDecodeUtf(string $string): string
    {
        return html_entity_decode($string, ENT_QUOTES, 'utf8');
    }
}
