<?php
namespace IT\Pgda\Services;

/**
 * Session and reported anomalies request message (565)
 * Class SessionReportedAnomaliesEntity
 * @package IT\Pgda\Services
 */
class SessionReportedAnomaliesEntity extends PgdaEntity
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
    public $request_id;

    /**
     * @var string
     */
    protected $format = "J";

    /**
     * @var array
     */
    protected $fillable = [
        'game_code',
        'game_type',
        'request_id'
    ];

    /**
     * @inheritDoc
     */
    public function toArray(array $array = []): array
    {
        return parent::toArray([$this->request_id]);
    }
}