<?php

namespace App\Factory\RiskProfileRating;

interface GrsTrigger
{
    public function score(): int;
    public function triggerName():? string;
    public function within():? int;
    public function period():? string;
    public function riskGroup():? string;
    public function exists():bool;
}