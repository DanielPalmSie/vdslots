<?= "<?php ";?>

namespace App\RgEvaluation\Triggers;

use App\RgEvaluation\ActivityChecks\ActivityCheckInterface;

class <?= $triggerClassName ?> extends Trigger
{
    protected array $stateTransitionMap = [

    ];

    public function getActivityCheck(): ActivityCheckInterface
    {

    }
}
