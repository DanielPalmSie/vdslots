<?php
declare(strict_types=1);

namespace App\Commands;

use Carbon\Carbon;
use Ivoba\Silex\Command\Command;
use Silex\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DailyReportCommand extends Command
{
    protected function configure()
    {
        $this->setName("daily_reports")
            ->setDescription("Generate daily reports to run at 00:00");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Application $app */
        $app = $this->getSilexApplication();
        $app['monolog']->addError("Command: daily_reports - Started");

        //calculate end balance for the previous day
        $today = Carbon::yesterday()->format('Y-m-d');
        $balances_command = $this->getApplication()->find('regulations:calculate_balances');
        $params = [
            'date_from' => $today,
            'date_to' => $today,
            '--country' => 'ES'
        ];
        $balances_command->run(new ArrayInput($params), $output);
        $app['monolog']->addError('Command: daily_reports - regulations:calculate_balances for: ES');

        loadPhive();
        //generated ES daily reports (RUD,RUT,CJD,CJT)
        phive('Licensed/ES/ES')->onMidnight();
        $app['monolog']->addError('Command: daily_reports - generate daily reports for: ES - done');

        return 0;
    }
}
