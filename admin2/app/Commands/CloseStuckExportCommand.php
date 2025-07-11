<?php

namespace App\Commands;

use App\Models\Export;
use Ivoba\Silex\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Closes all exports that stuck in progress after all retry attempts.
 */
class CloseStuckExportCommand extends Command
{
    private $app;

    protected function configure()
    {
        $this->setName("export:close")
            ->setDescription("Closes all exports that stuck in progress after all retry attempts.");
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
            ->where('attempts', Export::MAX_ATTEMPTS)
            ->get()
            ->map(function ($export) {
                $this->processCloseItem($export);
            });

        return 0;
    }



    /**
     * @param Export $export
     */
    private function processCloseItem($export)
    {
        $export->status = Export::STATUS_FAILED;
        $export->data = "Export processing is stuck for some reason and can't be handled. Retrying attempts: {$export->attempts}";
        $export->save();
        $this->app['monolog']->addInfo("Export ID:{$export->id} type:{$export->type} was set as FAILED after spending all retry attempts.");
    }
}
