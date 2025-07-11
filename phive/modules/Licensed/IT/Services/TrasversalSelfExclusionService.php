<?php
namespace IT\Services;

/**
 * Class TrasversalSelfExclusionService
 * @package IT\Services
 */
class TrasversalSelfExclusionService implements PayloadInterface
{
    /**
     * Self-exclusion management
     */
    const MANAGEMENT_SELF_EXCLUSION = 1;
    const MANAGEMENT_REACTIVATION = 2;

    /**
     * Self-exclusion type
     */
    const SELF_EXCLUSION_REACTIVATION = 0;

    const SELF_EXCLUSION_PERMANENT = 1;
    const SELF_EXCLUSION_30_DAYS = 2;
    const SELF_EXCLUSION_60_DAYS = 3;
    const SELF_EXCLUSION_90_DAYS = 4;

    /**
     * @var object
     */
    protected $user;

    /**
     * @var int
     */
    private $self_exclusion_time;

    /**
     * @var int
     */
    private $management_type;

    /**
     * TrasversalSelfExclusionService constructor.
     * @param $user
     * @param int $management_type
     * @param int $self_exclusion_time
     */
    public function __construct($user, int $management_type, int $self_exclusion_time)
    {
        $this->user = $user;
        $this->self_exclusion_time = $self_exclusion_time;
        $this->management_type = $management_type;
    }

    /**
     * @param int $time
     * @return int
     */
    protected function getSelfExclusionType(int $time): int
    {
        switch ($time) {
            case 30:
                return self::SELF_EXCLUSION_30_DAYS;
            case 60:
                return self::SELF_EXCLUSION_60_DAYS;
            case 90:
                return self::SELF_EXCLUSION_90_DAYS;
            case 1:
                return self::SELF_EXCLUSION_PERMANENT;
            default:
                return self::SELF_EXCLUSION_REACTIVATION;
        }
    }

    /**
     * @return array
     */
    public function getPayload(): array
    {
        return [
            'tax_code' =>  $this->user->getSetting('fiscal_code'),
            'self_exclusion_management' =>  $this->management_type,
            'self_exclusion_type' =>  $this->getSelfExclusionType($this->self_exclusion_time)
        ];
    }
}
