<?php
declare(strict_types=1);

namespace App\Commands\Regulations\ICS;

use ES\ICS\Constants\ICSConstants;
use ES\ICS\Reports\BaseReport;
use ES\ICS\Reports\Info;
use Ivoba\Silex\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateDraft extends Command
{
    protected function configure()
    {
        $this->setName('regulations:ics:generate_draft')
            ->setAliases(['ics:generate_draft', 'ics:gendraft'])
            ->setDescription('Generate an ICS draft report for the selected date(s). Selection of daily/monthly is made based on end_date')
            ->addArgument('report_type', InputArgument::REQUIRED, 'The report to generate')
            ->addArgument('start_date', InputArgument::REQUIRED, 'Set start date in Y-m-d. Example: 2021-11-23')
            ->addArgument('end_date', InputArgument::OPTIONAL, 'Set end date. If provided and different than start_date, it will trigger a monthly report. Example: 2021-11-30')
            ->addOption('target_dir', '', InputOption::VALUE_OPTIONAL, 'Optional directory to use instead of the on in settings. It will generate the full ICS structure inside as needed')
            ->addOption('game_types', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, "Set report's game_types. It is used only with OPT report. Example: `--game_types='AZA' --game_types='RLT'`", []);
    }


    /** @noinspection PhpUndefinedClassInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $type = $input->getArgument('report_type');
        $date_from = $input->getArgument('start_date');
        $date_to = $input->getArgument('end_date') ?? $date_from;
        $target_dir = $input->getOption('target_dir');
        $game_types = array_unique((array) $input->getOption('game_types'));

        if ($date_to === $date_from) {
            $frequency = ICSConstants::DAILY_FREQUENCY;
            $validReports = Info::getDailyReportClasses();
        } else {
            $frequency = ICSConstants::MONTHLY_FREQUENCY;
            $validReports = Info::getMonthlyReportClasses();
        }

        $this->ensureReportIsValid($type, $validReports);

        $report = '\\ES\\ICS\\Reports\\' . $type;
        $license = phive('Licensed/ES/ES');
        $settings = $license->getAllLicSettings();

        if ($target_dir) {
            $settings['ICS']['export_folder'] = $target_dir;
        }

        // we don't want to actually give the option to disable DB reporting on the base class
        // so we just disable it on a dynamic class for this only
        class_alias($report, 'DraftParent');

        /** @var BaseReport $report */
        $report = new class(
            'ES',
            $settings,
            [
                'period_start' => $date_from . ' 00:00:00',
                'period_end'   => $date_to . ' 23:59:59',
                'frequency'    => $frequency,
                'game_types'   => $game_types,
            ]

        ) extends \DraftParent {
            public function storeReport()
            {
                return true;
            }
        };

        foreach ($report->getFiles() as $file) {
            $file_path = $file->saveFile(BaseReport::GENERATE_PLAIN);
            $output->writeln("File generated on {$file_path}");
        }

        return 0;
    }

    private function ensureReportIsValid(string $type, array $validReports): void
    {
        $classNames = array_map(function ($classname){
            return basename(str_replace('\\', '/', $classname));
        }, $validReports);

        if (!in_array($type, $classNames)) {
            throw new InvalidArgumentException(sprintf('Selected report type "%s" is not valid.', $type));
        }
    }
}
