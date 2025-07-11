<?php

namespace App\Commands;

use App\Models\Export;
use Carbon\Carbon;
use Ivoba\Silex\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Reschedule all exports that stuck in progress more than 1 hour
 */
class RescheduleStuckExportCommand extends Command
{
    const OUTDATED_PERIOD_IN_HOURS = 1;
    private $app;

    protected function configure()
    {
        $this->setName("export:reschedule")
            ->setDescription("Reschedule all exports that stuck in progress more than 1 hour.");
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->app = $this->getSilexApplication();
        $this->app['monolog']->addInfo('Ran: ' . json_encode($input->getArguments()));
        Export::query()
            ->where('status', Export::STATUS_PROGRESS)
            ->where('attempts', '<', Export::MAX_ATTEMPTS)
            ->where('schedule_time', '<', Carbon::now()->subHours(static::OUTDATED_PERIOD_IN_HOURS)->toDateTimeString())
            ->get()
            ->map(function ($export) {
                $this->processRescheduleItem($export);
            });

        return 0;
    }



    /**
     * @param Export $export
     */
    private function processRescheduleItem($export)
    {
        $export->status = Export::STATUS_SCHEDULED;
        $export->schedule_time = Carbon::now();
        $export->save();
        $this->app['monolog']->addInfo("Export ID:{$export->id} type:{$export->type} was rescheduled because
        processing stuck more than " . static::OUTDATED_PERIOD_IN_HOURS . " hours");
    }
}
