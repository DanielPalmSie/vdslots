<?php

namespace App\Commands\SCV;

use Carbon\Carbon;
use Ivoba\Silex\Command\Command;
use Exception;
use InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Videoslots\HistoryMessages\UserSnapshotHistoryMessage;
use DBUser;
use ParseCsv;

class ExportUsersToSCV extends Command
{
    /** @var  OutputInterface */
    protected OutputInterface $output;

    public Carbon $start_time;
    public Carbon $end_time;
    public string $jurisdiction;

    public const HISTORY_TOPIC_NAME = 'user_import';

    public const LOCAL_BRAND_TO_REMOTE_MAP = [
        '100' => 'mrvegas',
        '101' => 'videoslots',
    ];

    public const LINK_SETTING = [
        '100' => 'c101_id',
        '101' => 'c100_id',
    ];

    public array $users_to_exclude = [];

    protected function configure()
    {
        $this->setName("scv:export-users")
            ->setDescription("Creates history messages to export users to SCV")
            ->addArgument(
                "start_time",
                InputArgument::REQUIRED,
                "Date from users to be exported registered. Format: Y-m-d H:i:s"
            )
            ->addArgument(
                "end_time",
                InputArgument::REQUIRED,
                "End date until users to be exported registered. Format: Y-m-d H:i:s"
            )
            ->addArgument(
                "jurisdiction",
                InputArgument::REQUIRED,
                "The jurisdiction users needed to be moved from. i.e. MGA, SGA, DGA, UKGC etc..",
            )->addArgument(
                "csv_path",
                InputArgument::OPTIONAL,
                "Full path to the csv file for users to be excluded from the exports"
            );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $start_time = $input->getArgument("start_time");
        $end_time = $input->getArgument("end_time");
        $this->jurisdiction = $input->getArgument("jurisdiction");
        $csv_path = $input->getArgument("csv_path");

        $format = 'Y-m-d H:i:s';

        if (!$this->validateDateTime($start_time, $format)) {
            $output->writeln("Start date is not valid.");
            return 1;
        }
        $this->start_time = Carbon::createFromFormat($format, $start_time);

        if (!$this->validateDateTime($end_time, $format)) {
            $output->writeln("End date is not valid.");
            return 1;
        }
        $this->end_time = Carbon::createFromFormat($format, $end_time);

        if (!$this->validateJurisdiction($this->jurisdiction)) {
            $output->writeln("Jurisdiction is not valid.");
            return 1;
        }

        if (!empty($csv_path)) {
            if (!$this->validateAndSetUsersToExcludeFromExports($csv_path)) {
                return 1;
            }
        }

        $start_time = Carbon::now()->toDateTimeString();
        $output->writeln("Fetching users to export {$start_time}");
        $users_to_export = $this->getUsersToExport();

        $count_users_to_export = count($users_to_export);
        $output->writeln("Processing users to export. Nr of users: {$count_users_to_export} "
            . Carbon::now()->toFormattedDateString());
        foreach ($users_to_export as $key => $user_details) {
            $output->write($key + 1 . "/{$count_users_to_export}|");
            $this->exportUserToScv($user_details);
            usleep(100);
        }

        $output->writeln("DONE");
        $output->writeln("Started at: {$start_time}");
        $output->writeln("Finished " . Carbon::now());

        return 0;
    }

    /**
     * Validates and sets date
     *
     * @param string $datetime
     * @param string $format
     * @return bool
     */
    public function validateDateTime(string $datetime, string $format = 'Y-m-d H:i:s'): bool
    {
        try {
            $carbon = Carbon::createFromFormat($format, $datetime);
        } catch (InvalidArgumentException  $exception) {
            $this->output->writeln("Exception: " . $exception->getMessage());
            return false;
        }

        if ($carbon->copy()->format($format) !== $datetime) {
            return false;
        }

        return true;
    }

    /**
     * @param string $jurisdiction
     * @return bool
     */
    public function validateJurisdiction(string $jurisdiction): bool
    {
        return in_array(
            $jurisdiction,
            phive('Licensed')->getSetting('country_by_jurisdiction_map')
        );
    }

    /**
     * @return array
     */
    public function getUsersToExport(): array
    {
        $sql = phive('SQL');
        $failed_status = DBUser::SCV_EXPORT_STATUS_VALUE_FAILED;
        $scv_error_status = DBUser::SCV_EXPORT_STATUS_VALUE_SCV_ERROR;

        $sql_inline = $this->getCountryAndProvinceSqlForJurisdiction($this->jurisdiction);

        $local_brand_id = phive('Distributed')->getLocalBrandId();
        $brand_link_setting_name = self::LINK_SETTING[$local_brand_id];

        $query = "
            SELECT users.id                       AS user_id,
                   registration_end_date.value    AS registration_end_date,
                   brand_link_user_id.value       AS brand_link_user_id
            FROM users
                     LEFT JOIN scv_export_status scv_export_status
                               ON scv_export_status.user_id = users.id
                     LEFT JOIN users_settings users_settings
                               ON users_settings.user_id = users.id AND users_settings.setting = 'main_province'
                     LEFT JOIN users_settings registration_end_date
                               ON registration_end_date.user_id = users.id AND
                                  registration_end_date.setting = 'registration_end_date'
                     LEFT JOIN users_settings registration_in_progress
                               ON registration_in_progress.user_id = users.id AND
                                  registration_in_progress.setting = 'registration_in_progress'
                     LEFT JOIN users_settings brand_link_user_id
                               ON brand_link_user_id.user_id = users.id AND
                                  brand_link_user_id.setting = '{$brand_link_setting_name}'
            WHERE (register_date BETWEEN '{$this->start_time->format('Y-m-d')}'
                          AND '{$this->end_time->subDay()->format('Y-m-d')}'
                     OR registration_end_date.value BETWEEN '{$this->start_time->format('Y-m-d H:i:s')}'
                          AND '{$this->end_time->format('Y-m-d H:i:s')}'
                     OR registration_in_progress.created_at BETWEEN '{$this->start_time->format('Y-m-d H:i:s')}'
                          AND '{$this->end_time->format('Y-m-d H:i:s')}')
              AND (
                    scv_export_status.status IS NULL
                    OR scv_export_status.status = '{$failed_status}'
                    OR scv_export_status.status = '{$scv_error_status}'
                  )
              AND {$sql_inline}
        ";

        return $sql->shs()->loadArray($query);
    }

    /**
     * Creates the required history message to be processed by SCV to export the user there.
     *
     * @param $user_id
     * @return void
     */
    public function exportUserToScv(array $user_details): void
    {
        $user_id = $user_details['user_id'];
        $user = cu($user_id);
        $user_data = $user->getData();
        unset($user_data['id']);
        $status_failed = DBUser::SCV_EXPORT_STATUS_VALUE_FAILED;
        $status_initiated = DBUser::SCV_EXPORT_STATUS_VALUE_INITIATED;
        $status_incorrect_link = DBUser::SCV_EXPORT_STATUS_VALUE_INCORRECT_LINK;

        try {
            if ($this->shouldExcludeFromExport($user_id)){
                $status = $user->setOrUpdateSCVExportStatus($status_incorrect_link);
                if (!$status) {
                    throw new Exception("Failed to set or save SCV export status. Status: {$status_incorrect_link}");
                }
                return;
            }

            $status = $user->setOrUpdateSCVExportStatus($status_initiated);

            if (!$status) {
                throw new Exception("Failed to set or save SCV export status. Status: {$status_initiated}");
            }

            $local_brand_id = phive('Distributed')->getLocalBrandId();
            $args = [
                'user_id' => (int)$user_id,
                'user' => $user_data,
                'users_settings' => [
                    'remote_brand_tag' => self::LOCAL_BRAND_TO_REMOTE_MAP[$local_brand_id],
                    'remote_brand_user_id' => $user_details['brand_link_user_id'],
                    'registration_end_date' => $user_details['registration_end_date'],
                ],
                'rg_limits' => [],
                'event_timestamp' => time(),
            ];
            /** @uses Licensed::addRecordToHistory() */
            $add_record_to_history_success = lic('addRecordToHistory', [
                self::HISTORY_TOPIC_NAME,
                new UserSnapshotHistoryMessage($args)
            ], $user_id);

            if (!$add_record_to_history_success) {
                throw new Exception("Failed to send message to queue {$user_id}");
            }

            $status = $user->setOrUpdateSCVExportStatus(DBUser::SCV_EXPORT_STATUS_VALUE_HISTORY_MESSAGE_CREATED);
            if (!$status) {
                throw new Exception("Failed to set or save SCV export status. Status: {$status_initiated}");
            }
        } catch (InvalidMessageDataException $exception) {
            $status = $user->setOrUpdateSCVExportStatus($status_failed);

            phive('Logger')
                ->getLogger('history_message')
                ->error("Invalid message data exception on export user to SCV",
                    [
                        'report_type' => self::HISTORY_TOPIC_NAME,
                        'args' => $args,
                        'validation_errors' => $exception->getErrors(),
                        'user_id' => $user_id,
                    ]);
            if (!$status) {
                phive('Logger')
                    ->getLogger('history_message')
                    ->error("Failed to set or save SCV export status. Status: {$status_failed}", $user_id);
            }
        } catch (Exception $exception) {
            $status = $user->setOrUpdateSCVExportStatus($status_failed);

            phive('Logger')
                ->getLogger('history_message')
                ->error($exception->getMessage(), $user_id);

            if (!$status) {
                phive('Logger')
                    ->getLogger('history_message')
                    ->error("Failed to set or save SCV export status. Status: {$status_failed}", $user_id);
            }
        }
    }

    /**
     * @param string $jurisdiction
     * @return string
     */
    public function getCountryAndProvinceSqlForJurisdiction(string $jurisdiction): string
    {
        $map = [
            'UKGC' => "country = 'GB'",
            'SGA' => "country = 'SE'",
            'DGA' => "country = 'DK'",
            'DGOJ' => "country = 'ES'",
            'ADM' =>  "country = 'IT'",
            'KSA' => "country = 'NL'",
            'AGCO' => "country = 'CA' AND users_settings.value = 'ON'",
            'MGA' => "(country NOT IN ('GB', 'SE', 'DK', 'IT', 'NL')
                OR (country = 'CA' and users_settings.value != 'ON'))"
        ];

        return $map[$jurisdiction];
    }

    /**
     * Validates and sets the list of users to be excluded from the export
     *
     * @param string $csv_path
     * @return bool
     */
    private function validateAndSetUsersToExcludeFromExports(string $csv_path): bool
    {
        $this->users_to_exclude = [];

        if (!file_exists($csv_path)) {
            $this->output->writeln("File doesn't exist: {$csv_path}");
            return false;
        }

        $csv = new ParseCsv\Csv($csv_path);
        $users_to_exclude_data = $csv->data;

        if (
            !array_key_exists('user_id', $users_to_exclude_data[0])
        ) {
            $this->output->writeln("File is not in the correct format. Columns user_id and customer_id are required.");
            return false;
        }

        $count = count($users_to_exclude_data);
        if ($count < 1) {
            $this->output->writeln("File is empty: {$csv_path}");
            return false;
        }

        foreach ($users_to_exclude_data as $user) {
            $this->users_to_exclude[] = $user['user_id'];
        }

        return true;
    }

    /**
     * Checks if the user should be excluded from the export based on the input csv
     *
     * @param $user_id
     * @return void
     */
    public function shouldExcludeFromExport($user_id): bool
    {
        return in_array($user_id, $this->users_to_exclude);
    }

}
