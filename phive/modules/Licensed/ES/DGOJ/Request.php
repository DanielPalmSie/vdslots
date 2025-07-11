<?php


class Request
{
    private const FIELD_ALIASES = [
        'dni' => 'personal_number',
        'nombre' => 'first_name',
        'apellido1' => 'last_name',
        'apellido2' => 'second_last_name',
        'fechaNacimiento' => 'birth_date',
        'numSoporte' => 'support_number', // we do not have such field
    ];

    // fields are required only for ES users (with NIF)
    private const FIELDS_REQUIRED_WITH_NIF = [
        'apellido2'
    ];

    /** @var string $invalid_name_characters Replace these characters as requested in page 27 */
    private string $invalid_name_characters = '1,2,3,4,5,6,7,8,9,0,$,&,¿,?,¡,!,|,(,),@,#,¬,+,*,{,},<,>,%,\/,\\';

    /** @var array|string[] $name_keys Remove invalid name characters on this list of keys */
    private array $name_keys = ['nombre', 'apellido1', 'apellido2'];

    /** @var array|string[] $special_characters Replacers based on page 27 */
    private array $special_characters = [
        'á,à,ä,â,Á,À,Ä,Â' => 'A',
        'é,è,ë,ê,É,È,Ë,Ê' => 'e',
        'í,ì,ï,î,Í,Ì,Ï,Î' => 'i',
        'ó,ò,ö,ô,Ó,Ò,Ö,Ô' => 'o',
        'ú,ù,û,Ú,Ù,Û' => 'U',
        'Ü,ü' => 'Ü',
    ];

    /** @var array $data Contains the cleaned data */
    private array $data = [];

    /** @var array|string[] $required_fields List of all required fields */
    public static array $required_fields = [
        'dni',
        'nombre',
        'apellido1', // `apellido2` is required only for ES users with NIF
        'fechaNacimiento',
    ];

    /**
     * Request constructor.
     * @param array $users
     */
    public function __construct(array $users)
    {
        if (!is_array($users[0])) {
            $users = [$users];
        }

        foreach ($users as $user) {
            $this->data[] = $this->cleanRequestObject($user);
        }
    }

    /**
     * Apply replacers required from the docs
     * @param array $user
     *
     * @return array
     */
    public function cleanRequestObject(array $user): array
    {
        $result = [];
        foreach (self::FIELD_ALIASES as $key => $readable_key) {
            if (empty($user[$key])) {
                continue;
            }
            $result[$key] = $this->cleanValue($user[$key], in_array($key, $this->name_keys, true));
        }
        return $result;
    }

    /**
     * Validate request object
     * @param array $data
     * @return array
     */
    public static function validateRequestObject(array $data): array
    {
        $errors = [];
        $error_alias = 'errors.field.required_cant_be_empty';
        $fields = self::$required_fields;

        if (lic('isValidNif', [$data['dni']])) {
            $fields = array_merge($fields, self::FIELDS_REQUIRED_WITH_NIF);
        }

        foreach ($fields as $field) {
            if (empty($data[$field])) {
                $errors[] = tAssoc($error_alias, ['field' => self::FIELD_ALIASES[$field] ?? $field]);
            }
        }

        return $errors;
    }

    /**
     * Return the cleaned data
     * @return array
     */
    final public function getCleanData(): array
    {
        return $this->data;
    }

    /**
     * Clean value based on rules from page 27
     *
     * @param string $value
     * @param bool|null $is_name
     * @return string
     */
    private function cleanValue(string $value, ?bool $is_name = false): string
    {
        if (empty($value)) {
            return $value;
        }

        $value = trim($value, ' -');

        $value = preg_replace('/\s+/', ' ', $value);

        $value = strtolower($value);

        foreach ($this->special_characters as $list => $replace) {
            $value = str_replace(explode(',', $list), $replace, $value);
        }

        if ($is_name) {
            $value = str_replace(explode(',', $this->invalid_name_characters), '', $value);
        }

        return $value;
    }


}
