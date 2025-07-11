<?php

namespace App\Factory\RiskProfileRating;

use App\Factory\RiskProfileRating\GrsTrigger;

class ProfileGrsTrigger implements GrsTrigger
{
    /**
     * @var mixed
     */
    private $score;
    /**
     * @var mixed
     */
    private $trigger_name;
    /**
     * @var mixed
     */
    private $risk_group;
    /**
     * @var mixed
     */
    private $within;
    /**
     * @var mixed
     */
    private $period;

    public function __construct(
        int $score,
        ?string $trigger_name = null,
        ?string $risk_group = null,
        ?int $within = null,
        ?string $period = null
    ) {
        $this->score = $score;
        $this->trigger_name = $trigger_name;
        $this->risk_group = $risk_group;
        $this->within = $within;
        $this->period = $period;
    }

    public function score(): int
    {
        return $this->score;
    }

    public function triggerName(): ?string
    {
        return $this->trigger_name;
    }

    public function within(): ?int
    {
        return $this->within;
    }

    public function period(): ?string
    {
        return $this->period;
    }

    public function riskGroup(): ?string
    {
        return $this->risk_group;
    }

    public function exists(): bool
    {
        return !empty($this->score) &&
            !is_null($this->trigger_name) &&
            !is_null($this->risk_group) &&
            !is_null($this->within) &&
            !is_null($this->period);
    }
}