<?php

namespace App\Commands\Regulations\ICS;

use ES;
use ES\ICS\Reports\BaseReport;
use Exception;
use RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Ivoba\Silex\Command\Command;

class RectifyReport extends Command
{
    private const REPORT_TYPE_OPT = 'OPT';

    private string $report_uid;
    private array $game_types = [];
    private ?string $report_data_from;
    private ?string $report_type;
    private ?string $report_data_to;

    private OutputInterface $output;
    private InputInterface $input;
    private ES $license;

// Example
// ics:rectify "2022-06-26" "2022-06-26" RUD
// ics:rectify 62dfebd151064

    protected function configure()
    {
        $this->setName('ics:rectify')
            ->setDescription('Rectify ICS report')
            ->addUsage('624430ee9ecec # where `624430ee9ecec` is the column `unique_id` of the table `external_regulatory_report_logs` ||  
            "2022-06-26" "2022-06-26" RUD # where dates are `report_data_from` and `report_data_to` of the table `external_regulatory_report_logs`, and RUD - type od report')
            ->addArgument('argument', InputArgument::REQUIRED, 'Set report uid or date of report')
            ->addArgument('report_data_to', InputArgument::OPTIONAL, 'Set end date  of the report')
            ->addArgument('report_type', InputArgument::OPTIONAL, 'Set type of the report');
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
        $this->rectifyReport();

        return 0;
    }

    /**
     * @throws Exception
     */
    private function initProperties(): void
    {
        $this->license = phive('Licensed/ES/ES');
        $this->report_uid = $this->input->getArgument('argument');
        $this->report_data_from = $this->input->getArgument('argument');
        $this->report_data_to = $this->input->getArgument('report_data_to');
        $this->report_type = $this->input->getArgument('report_type');
    }

    /**
     * @throws Exception
     */
    private function validateProperties(): void
    {
        $this->report_uid = strtotime($this->report_uid) ? $this->getIdReportByDate() : $this->report_uid;

        $sql = "
            SELECT unique_id, id, report_type, filename_prefix FROM external_regulatory_report_logs 
            WHERE regulation = '{$this->license->getLicSetting('regulation')}' 
                AND unique_id = '{$this->report_uid}'
        ";
        $report_log = phive('SQL')->readOnly()->loadAssoc($sql);

        if (empty($report_log)) {
            throw new Exception("There is no such report_uid in the DB: `{$this->report_uid}`");
        }

        if ($report_log['report_type'] === self::REPORT_TYPE_OPT) {
            $this->game_types = $this->getGameTypeFromFilename($report_log['filename_prefix']);
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    private function rectifyReport(): void
    {
        $this->license->rectifyReport($this->report_uid, [BaseReport::GENERATE_ZIP], $this->game_types);

        $this->output->writeln("Finish Rectifying Reports UID: `{$this->report_uid}`");
    }

    /**
     * For rectifying OPT report we must pass game type as well
     *
     * @param string $filename_prefix
     *
     * @return array
     * @throws Exception
     */
    private function getGameTypeFromFilename(string $filename_prefix): array
    {
        $game_type = explode('_', $filename_prefix)[4] ?? '';

        if (!in_array($game_type, $this->getOptGameTypes())) {
            throw new Exception("Incorrect game_type: `{$game_type}`");
        }

        return [$game_type];
    }

    /**
     * @return string[]
     */
    private function getOptGameTypes(): array
    {
        $ics_settings = (array) $this->license->getLicSetting('ICS');

        return (array) ($ics_settings['licensed_external_game_types'] ?? []);
    }

    /**
     * @return string|null
     */
    private function getIdReportByDate(): ?string
    {
        $report_data_from = $this->report_data_from = explode(' ', $this->report_data_from)[0];
        $report_data_to = $this->report_data_to = explode(' ', $this->report_data_to)[0];
        $sql= "
            SELECT unique_id FROM external_regulatory_report_logs 
            WHERE regulation = '{$this->license->getLicSetting('regulation')}' 
                AND report_data_from = '{$report_data_from} 00:00:00'
                AND report_data_to = '{$report_data_to} 23:59:59'
                AND report_type = '{$this->report_type}'
           ORDER BY created_at DESC
           LIMIT 1
        ";
       return phive('SQL')->readOnly()->getValue($sql);
    }

}