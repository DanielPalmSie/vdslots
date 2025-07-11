<?php

namespace App\Commands\RgEvaluation;

use App\Extensions\Database\Eloquent\Builder;
use App\Models\Config;
use App\Models\User;
use App\Models\UserRgEvaluation;
use App\RgEvaluation\States\CheckUsersGRSState;
use App\RgEvaluation\Triggers\TriggerInterface;
use Carbon\Carbon;
use Exception;
use Ivoba\Silex\Command\Command;
use ReflectionClass;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BaseRgEvaluationCommand extends Command
{
    protected ?User $actor;

    protected const RG_EVALUATION_STATE_CONFIG = 'rg-evaluation-state';

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->addArgument('interaction_started', InputArgument::OPTIONAL,
            "All interactions from this date will be processed.");
        $this->actor = User::where('username', 'system')->first();
    }

    /**
     * Checks whether the command is enabled or not in the current environment.
     *
     */
    public function isEnabled(): bool
    {
        $config = Config::where('config_name', static::RG_EVALUATION_STATE_CONFIG)->first();
        return $config->config_value === 'on';
    }

    /**
     * Processed items marked as completed (processed = 1)
     *
     * @param Carbon $rgInteractionStarted
     * @param string $step
     *
     * @return void
     * @throws \ReflectionException
     */
    protected function evaluate(Carbon $rgInteractionStarted, string $step): void
    {
        $this->fetchQueuedProcessesQuery(
            $step,
            $rgInteractionStarted
        )
            ->chunk(100, function ($data) {
                foreach ($data as $userRgEvaluation) {
                    try {
                        $triggerInstance = $this->getTriggerInstance($userRgEvaluation);
                        $triggerInstance->evaluate();
                    } catch (Exception $e) {
                        if (!is_a($e, \ReflectionException::class)) {
                            $this->getSilexApplication()['monolog']->addError(__METHOD__, [$e->getMessage()]);
                        }
                    }
                }
            });
    }

    /**
     * @param string $step
     * @param Carbon $rgInteractionStarted
     *
     * @return Builder
     */
    protected function fetchQueuedProcessesQuery(string $step, Carbon $rgInteractionStarted): Builder
    {
        return UserRgEvaluation::whereDate(
            'created_at',
            $rgInteractionStarted->toDateString()
        )
            ->with('user')
            ->byStep($step)
            ->new();
    }

    /**
     * @param string $rgInteractionStarted
     * @param int    $evaluationInterval
     *
     * @return Carbon
     */
    protected function getRgInteractionStartedAt(string $rgInteractionStarted, int $evaluationInterval): Carbon
    {
        $scheduledRgInteractionStarted = Carbon::now()->subDays($evaluationInterval);
        try {
            $rgInteractionStarted = Carbon::parse($rgInteractionStarted);

            if ($rgInteractionStarted > $scheduledRgInteractionStarted) {
                return $scheduledRgInteractionStarted;
            }
            return $rgInteractionStarted;
        } catch (Exception $e) {
            return $scheduledRgInteractionStarted;
        }
    }

    /**
     * @throws \ReflectionException
     */
    protected function getTriggerInstance($userRgEvaluation): TriggerInterface
    {
        $triggerClassName = "App\RgEvaluation\Triggers\\" . $userRgEvaluation->trigger_name;
        return (new ReflectionClass($triggerClassName))->newInstance(
            $userRgEvaluation,
            new CheckUsersGRSState($this->getSilexApplication()),
            $this->getSilexApplication()
        );
    }
}