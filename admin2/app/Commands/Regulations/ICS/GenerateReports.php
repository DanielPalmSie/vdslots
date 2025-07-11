<?php

namespace App\Commands\Regulations\ICS;

use Carbon\Carbon;
use ES;
use ES\ICS\Reports\BaseReport;
use ES\ICS\Reports\Info;
use ES\ICS\Reports\JUD;
use ES\ICS\Reports\JUT;
use ES\ICS\Reports\JUC;
use ES\ICS\Reports\OPT;
use Exception;
use Ivoba\Silex\Command\Command;
use RuntimeException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateReports extends Command
{
    private const REPORT_FREQUENCIES = ['daily', 'monthly', 'realtime'];
    private const REAL_TIME_LIMIT_MINUTES = 15;
    private const REAL_TIME_LIMIT_SECONDS = 900;
    private const COUNTRY = 'ES';
    private const FORMAT_DAY = 'Y-m-d';
    private const FORMAT_TIME = 'Y-m-d H:i:s';

    private Carbon $start_date;
    private Carbon $end_date;
    private ?string $report_frequency = null;
    private bool $archive = false;
    private bool $progress = false;
    private array $reports_classes = [];
    private array $game_types = [];

    private array $users_sessions_dates = [];
    private OutputInterface $output;
    private InputInterface $input;
    private ?ProgressBar $progress_bar = null;
    private ES $license;

    protected function configure()
    {
        $this->setName('regulations:ics:generate')
            ->setAliases(['ics:generate'])
            ->setDescription('Generate ICS reports in range of dates')
            ->addUsage('2021-09-20 2021-09-30 daily --progress # - means: generate all daily reports without archiving and use progress bar')
            ->addUsage('2021-09-01 2021-09-30 monthly --archive --progress # - means: generate all monthly reports, archive and use progress bar')
            ->addUsage('2021-11-26 2021-11-30 realtime --progress # - means: generate all realtime reports without archiving and use progress bar')
            ->addUsage("2021-09-01 2021-09-30 monthly --archive --report_classes='OPT' --report_classes='RUT' # - means: generate OPT & RUT monthly reports and archive them, without progress bar")
            ->addUsage("2021-09-01 2021-09-30 monthly --report_classes='OPT' --game_types='AZA' # - means: generate OPT AZA monthly report without archiving, without progress bar")
            ->addArgument('start_date', InputArgument::REQUIRED, 'Set start date. Example: 2021-11-23')
            ->addArgument('end_date', InputArgument::REQUIRED, 'Set end date. Example: 2021-11-30')
            ->addArgument('report_frequency', InputArgument::OPTIONAL, "Set report frequency. Example: 'monthly'")
            ->addOption(
                'archive',
                null,
                InputOption::VALUE_NONE,
                'Enabling archiving. Example: --archive',
            )
            ->addOption(
                'progress',
                null,
                InputOption::VALUE_NONE,
                'Enabling progress bar. Example: --progress',
            )
            ->addOption(
                'report_classes',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                "Set report's classes that should be generated. Example: `--report_classes='OPT' --report_classes='RUT'`",
                []
            )
            ->addOption(
                'game_types',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                "Set report's game_types. It is used only with OPT report. Example: `--game_types='AZA' --game_types='RLT'`",
                []
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $this->initProperties();
        $this->validateProperties();
        $this->generateReports();
        $this->archiveReports();

        return 0;
    }

    /**
     * @throws Exception
     */
    private function generateReports(): void
    {
        if (empty($this->report_frequency)) {
            return;
        }

        switch ($this->report_frequency) {
            case 'daily':
                $this->runDailyReports();
                break;
            case 'monthly':
                $this->runMonthlyReports();
                break;
            case 'realtime':
                $this->runRealtimeReports();
                break;
        }
    }

    public function runDailyReports(): void
    {
        $this->initProgressBar($this->end_date->diffInDays($this->start_date) + 1);

        $start_day = (clone $this->start_date);
        $is_end_of_day = $this->end_date->isEndOfDay();
        $export_type = [BaseReport::GENERATE_ZIP];
        $rectify = [];
        $reports = $this->reports_classes ?: Info::getDailyReportClasses();

        while ($start_day->lessThan($this->end_date)) {
            if (!$is_end_of_day && $start_day->isSameDay($this->end_date)) {
                // Generate daily reports only for full day
                break;
            }

            $start_day_end = (clone $start_day)->endOfDay()->format(self::FORMAT_TIME);
            $this->license->generateDailyICSReports(
                $start_day->format(self::FORMAT_TIME),
                $start_day_end,
                $export_type,
                $rectify,
                $reports,
            );
            $start_day->addDay();
            $this->advanceProgressBar();
        }

        $this->finishProgressBar();
        $this->output->writeln(PHP_EOL . 'Finish Daily Reports');
    }

    public function runMonthlyReports(): void
    {
        $export_type = [BaseReport::GENERATE_ZIP];
        $rectify = [];
        $reports = $this->reports_classes ?: Info::getMonthlyReportClasses();

        $this->license->generateMonthlyICSReports(
            $this->start_date->format(self::FORMAT_TIME),
            $this->end_date->format(self::FORMAT_TIME),
            $export_type,
            $rectify,
            $reports,
            $this->game_types,
        );

        $this->output->writeln(PHP_EOL . 'Finish Monthly Reports');
    }

    /**
     * Check real-time reports for every 15 minutes
     * Skip dates range without users_sessions
     *
     * @throws Exception
     */
    public function runRealtimeReports(): void
    {
        $start_day = (clone $this->start_date);
        $reports = $this->reports_classes ?: Info::getRealTimeReportClasses();

        while ($start_day->lessThan($this->end_date)) {
            $this->initUsersSessionsDates($start_day);

            if (empty($this->users_sessions_dates)) {
                $end_of_day = (clone $start_day)->endOfDay()->format(self::FORMAT_TIME);
                $this->output->writeln(PHP_EOL . "Skip real-time reports: `{$start_day->format(self::FORMAT_TIME)}` - `{$end_of_day}`");
                $start_day->addDay();

                continue;
            }

            $this->initProgressBar(count($this->users_sessions_dates), $start_day->format(self::FORMAT_DAY));

            foreach ($this->users_sessions_dates as $period_start => $period_end) {
                foreach ($reports as $report) {
                    $classParts = explode('\\', $report);
                    $reportClassName = end($classParts);
                    $this->license->withProgress(
                        'generate-real-time-ICS-reports-'.$reportClassName,
                        function () use ($period_end, $period_start, $report) {
                            $this->license->generateRealTimeReport($period_end, $report, $period_start);
                        }
                    );
                }

                $this->advanceProgressBar();
            }

            $start_day->addDay();
        }

        $this->finishProgressBar();

        $this->output->writeln(PHP_EOL . 'Finish Realtime Reports');
    }

    public function archiveReports(): void
    {
        if (empty($this->archive)) {
            return;
        }

        $start_day = (clone $this->start_date)->startOfDay();
        $is_end_of_day = $this->end_date->isEndOfDay();

        while ($start_day->lessThan($this->end_date)) {
            if (!$is_end_of_day && $start_day->isSameDay($this->end_date)) {
                // Archive reports only for full day
                break;
            }

            $this->license->archiveDay($start_day->format('Ymd'));
            $start_day->addDay();
        }

        $this->output->writeln('Finish Archive Reports');
    }

    /**
     * @throws Exception
     */
    private function validateProperties(): void
    {
        $now = Carbon::now();

        if ($this->end_date->greaterThan($now)) {
            $this->end_date = $now;
            $this->output->writeln('End date shouldn\'t be more than `now`. End date is set as: ' . $now->format(self::FORMAT_TIME));
        }

        if (!$this->start_date->isSameMonth($this->end_date, true)) {
            throw new Exception('Start and end dates should be in the same month');
        }

        if (!in_array($this->report_frequency, self::REPORT_FREQUENCIES)) {
            throw new Exception("Wrong report frequency: `{$this->report_frequency}`");
        }

        if (('monthly' === $this->report_frequency) && !$this->end_date->isLastOfMonth()) {
            throw new Exception('End date should be the last day of month for `monthly` report');
        }

        if ($this->start_date->greaterThan($this->end_date)) {
            throw new Exception('End date should be greater than start date');
        }

        // validate `reports_classes`
        if (!empty($this->reports_classes)) {

            if ($this->report_frequency === 'daily') {
                $dailyReportClasses = Info::getDailyReportClasses();
                if (array_diff($this->reports_classes, $dailyReportClasses)) {
                    $allowed = json_encode($this->getBaseNameFromFullyQualifiedClassNameArray($dailyReportClasses), JSON_THROW_ON_ERROR);
                    throw new Exception("Wrong report classes were set for daily reports. Only report classes {$allowed} are allowed");
                }
            }

            if ($this->report_frequency === 'monthly') {
                $monthlyReportClasses = Info::getMonthlyReportClasses();
                if (array_diff($this->reports_classes, $monthlyReportClasses)) {
                    $allowed = json_encode($this->getBaseNameFromFullyQualifiedClassNameArray($monthlyReportClasses), JSON_THROW_ON_ERROR);
                    throw new Exception("Wrong report classes were set for monthly reports. Only report classes {$allowed} are allowed");
                }
            }

            if ($this->report_frequency === 'realtime') {
                $realTimeReportClasses = Info::getRealTimeReportClasses();
                if (array_diff($this->reports_classes, $realTimeReportClasses)) {
                    $allowed = json_encode($this->getBaseNameFromFullyQualifiedClassNameArray($realTimeReportClasses), JSON_THROW_ON_ERROR);
                    throw new Exception("Wrong report classes were set for realtime reports. Only report classes {$allowed} are allowed");
                }
            }
        }

        // validate `game_types`
        if (!empty($this->game_types)) {
            $report_type_opt = OPT::class;

            if ((count($this->reports_classes) !== 1) || head($this->reports_classes) !== $report_type_opt) {
                throw new Exception("Wrong `reports_classes`. It should be set to `{$report_type_opt}` only when we use `game_types`");
            }

            $ics_settings = (array) $this->license->getLicSetting('ICS');
            $lic_game_types = (array) ($ics_settings['licensed_external_game_types'] ?? []);

            if (array_diff($this->game_types, $lic_game_types)) {
                throw new Exception('Incorrect game types. Values might be as: ' . json_encode($lic_game_types, JSON_THROW_ON_ERROR));
            }
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    private function getReportClasses(): array
    {
        $reports = [];

        foreach (array_unique((array) $this->input->getOption('report_classes')) as $report) {
            $report = strtoupper(trim($report));

            if (empty($report)) {
                continue;
            }

            $reports[] = "ES\ICS\Reports\\{$report}";
        }

        return $reports;
    }

    /**
     * @param int $steps
     * @param string|null $date
     */
    private function initProgressBar(int $steps = 0, string $date = null): void
    {
        if (!$this->progress) {
            return;
        }

        if ($date) {
            $this->output->writeln(PHP_EOL . "Day: `{$date}`");
        }

        $this->progress_bar = new ProgressBar($this->output, $steps);
    }

    private function advanceProgressBar(int $step = 1): void
    {
        if (!$this->progress || empty($this->progress_bar)) {
            return;
        }

        $this->progress_bar->advance($step);
    }

    private function finishProgressBar(): void
    {
        if (!$this->progress || empty($this->progress_bar)) {
            return;
        }

        $this->progress_bar->finish();
    }

    /**
     * @throws Exception
     */
    private function initProperties(): void
    {
        $this->license = phive('Licensed/ES/ES');
        $this->start_date = (new Carbon($this->input->getArgument('start_date')))->startOfDay();
        $this->end_date = (new Carbon($this->input->getArgument('end_date')))->endOfDay();
        $this->report_frequency = strtolower($this->input->getArgument('report_frequency'));
        $this->archive = (bool) $this->input->getOption('archive');
        $this->progress = (bool) $this->input->getOption('progress');
        $this->reports_classes = $this->getReportClasses();
        $this->game_types = array_unique((array) $this->input->getOption('game_types'));
    }

    /**
     * Make date ranges for generating reports
     *
     * @param Carbon $start_day
     */
    private function initUsersSessionsDates(Carbon $start_day): void
    {
        $this->users_sessions_dates = []; // clean up previous day
        $country = self::COUNTRY;
        $start_of_day = (clone $start_day)->startOfDay();
        $end_of_day = (clone $start_day)->endOfDay();

        $users_sessions_db = Info::getUsersSessionsDates($start_of_day, $country);

        sort($users_sessions_db);

        $users_sessions = [];

        if (empty($users_sessions_db)) {
            $this->users_sessions_dates = $users_sessions;

            return;
        }

        while ($start_of_day->lessThan($end_of_day)) {
            if (empty($users_sessions_db)) {
                break;
            }

            $first_us_date = reset($users_sessions_db);

            if ($start_of_day->lessThan($first_us_date) && $start_of_day->diffInSeconds($first_us_date) > self::REAL_TIME_LIMIT_SECONDS)  {
                $start_of_day->addMinutes(self::REAL_TIME_LIMIT_MINUTES);

                continue;
            }

            $end_date = (clone $start_of_day)->addMinutes(self::REAL_TIME_LIMIT_MINUTES);

            $users_sessions[$start_of_day->format(self::FORMAT_TIME)] = $end_date->format(self::FORMAT_TIME);

            while($end_date->greaterThan(reset($users_sessions_db))) {
                array_shift($users_sessions_db);

                if (empty($users_sessions_db)) {
                    break;
                }
            }

            $start_of_day->addMinutes(self::REAL_TIME_LIMIT_MINUTES);
        }

        $this->users_sessions_dates = $users_sessions;
    }

    private function getBaseNameFromFullyQualifiedClassNameArray(array $classNames): array
    {
        return array_map(array($this, 'getBaseNameFromFullyQualifiedClassName'), $classNames);
    }

    private function getBaseNameFromFullyQualifiedClassName(string $className): string
    {
        return basename(str_replace('\\', '/', $className));
    }
}
