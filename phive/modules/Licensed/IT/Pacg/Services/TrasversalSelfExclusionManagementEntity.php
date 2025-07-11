<?php
namespace IT\Pacg\Services;

/**
 * Class TrasversalSelfExclusionManagementEntity
 * @package IT\Pacg\Services
 */
class TrasversalSelfExclusionManagementEntity extends PacgService
{
    public $tax_code;
    public $self_exclusion_management;
    public $self_exclusion_type;

    protected $fillable = [
        'tax_code',
        'self_exclusion_management',
        'self_exclusion_type'
    ];

    protected $rules = [
        'tax_code' => 'required|alpha_num|min:16|max:16',
        'self_exclusion_management' => 'required|trasversal_self_exclusion_management',
        'self_exclusion_type' => 'required|trasversal_self_exclusion_type',
    ];

    /**
     * @param array $array
     * @return array
     */
    public function toArray(array $array = []): array
    {
        $values = [
            'codiceFiscale' => $this->tax_code,
            'gestioneAutoesclusione' => $this->self_exclusion_management,
            'tipoAutoesclusione' => $this->self_exclusion_type
        ];

        return parent::toArray($values);
    }
}
