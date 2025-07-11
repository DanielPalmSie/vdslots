<?php
namespace IT\Pgda\Services;

/**
 * Signature certificate addition message (840)
 * Class AdditionSignatureCertificateEntity
 * @package IT\Pgda\Services
 */
class AdditionSignatureCertificateEntity extends PgdaEntity
{
    /**
     * @var int
     */
    public $game_code;

    /**
     * @var int
     */
    public $game_type;

    /**
     * @var int
     */
    public $certificate_serial_number;

    /**
     * @var string
     */
    public $certificate;

    /**
     * @var string
     */
    protected $format = "NnA*";

    /**
     * @var array
     */
    protected $fillable = [
        'game_code',
        'game_type',
        'certificate_serial_number',
        'certificate',
    ];

    /**
     * @inheritDoc
     */
    public function toArray(array $array = []): array
    {
        return parent::toArray(
            [
                $this->certificate_serial_number,
                mb_strlen($this->certificate, '8bit'),
                $this->certificate
            ]
        );
    }
}