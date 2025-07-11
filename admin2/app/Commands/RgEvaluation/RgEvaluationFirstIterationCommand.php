<?php

declare(strict_types=1);

namespace App\Commands\RgEvaluation;

use App\Models\UserRgEvaluation;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RgEvaluationFirstIterationCommand extends BaseRgEvaluationCommand
{
    protected static $defaultName = 'rg:evaluation-first-iteration';
    protected static $defaultDescription = "Evaluation of the interaction popups and force the system
            to take action automatically. Fetches all NEW processes with step 'started'";


    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->getSilexApplication();
        $app['monolog']->addInfo("Command: " . static::$defaultName . " - Started");

        if (!$this->actor) {
            $app['monolog']->addError("The 'system' user does not exist.");
            return 1;
        }

        $this->evaluate(
            $this->getRgInteractionStartedAt(
                $input->getArgument('interaction_started') ?? "",
                    UserRgEvaluation::FIRST_EVALUATION_INTERVAL_IN_DAYS
            ),
            UserRgEvaluation::STEP_STARTED
        );
        $app['monolog']->addInfo("Command: " . static::$defaultName . " - Completed");

        return 0;
    }
}
