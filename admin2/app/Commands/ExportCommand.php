<?php
/**
 * Created by PhpStorm.
 * User: iondum
 * Date: 24/01/18
 * Time: 12:34
 */

namespace App\Commands;

use App\Classes\Dmapi;
use App\Classes\Filter\FilterClass;
use App\Classes\Filter\FilterData;
use App\Classes\Mts;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\ReplicaFManager as ReplicaDB;
use App\Helpers\DataFormatHelper;
use App\Models\BonusTypeTemplate;
use App\Models\Config;
use App\Models\EmailQueue;
use App\Models\Export;
use App\Models\NamedSearch;
use App\Models\OfflineCampaigns;
use App\Models\User;
use App\Models\UserFlag;
use App\Models\VoucherTemplate;
use App\Repositories\ActionRepository;
use App\Repositories\ContactsFilterRepository;
use App\Repositories\MessagingRepository;
use Carbon\Carbon;
use Ivoba\Silex\Command\Command;
use PHPExcel;
use PHPExcel_IOFactory;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;
use ZipArchive;

class ExportCommand extends Command
{
    const TYPE_SCHEDULE = 3;
    const TYPE_DIRECT = 2;
    const TYPE_CRON = 1;

    private $export_type;
    private $app;

    protected function configure()
    {
        $types = implode(',', array_keys(Export::EXPORT_MAP));

        $this->setName("export")
            ->setDescription("This will do the export to odt files.")
            ->addArgument('type', InputArgument::OPTIONAL, "Type of the export. In [{$types}]")
            ->addArgument('id', InputArgument::OPTIONAL, "Id of the processed element.")
            ->addArgument('schedule_time', InputArgument::OPTIONAL, "When should the item be processed.");
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
        // setup $this->export_type
        $this->setupExportType($input);

        if ($this->export_type == self::TYPE_CRON) {
            $this->app['monolog']->addInfo(" execute type:TYPE_CRON");
            Export::query()
                ->whereIn('status', [Export::STATUS_SCHEDULED, Export::STATUS_FAILED])
                ->where('attempts', '<', Export::MAX_ATTEMPTS)
                ->where('schedule_time', '<', Carbon::now()->toDateTimeString())
                ->get()
                ->map(function ($export) {
                    $this->processExportItem($export);
                });

            return 0;
        }

        // prevent duplicate schedules
        if ($this->export_type == self::TYPE_SCHEDULE) {
            $this->app['monolog']->addInfo(" execute type:TYPE_SCHEDULE");
            $export = Export::query()
                ->where('type', $input->getArgument('type'))
                ->where('target_id', $input->getArgument('id'))
                ->where('schedule_time', $input->getArgument('schedule_time'))
                ->where('status', Export::STATUS_SCHEDULED)
                ->first();

            if ($export) {
                $this->app['monolog']->addError('Export exists already. Exit.' . json_encode($export));
                return 0;
            }
        }

        // Prevent duplicate process when it's running and has status STATUS_PROGRESS
        $export = Export::query()
            ->where('type', $input->getArgument('type'))
            ->where('target_id', $input->getArgument('id'))
            ->latest()
            ->first();

        if (!is_null($export) && $export->status == Export::STATUS_PROGRESS) {
            $this->app['monolog']->addError('Export is processing already. Exit.' . json_encode($export));
            return 0;
        }

        try {
            $scheduleTime = Carbon::parse($input->getArgument('schedule_time'));
        } catch (\Exception $e) {
            $scheduleTime = Carbon::now();
        }

        $export = new Export;
        $export->type = $input->getArgument('type');
        $export->target_id = $input->getArgument('id');
        $export->schedule_time = $scheduleTime;
        $export->status = $this->export_type == self::TYPE_SCHEDULE
            ? Export::STATUS_SCHEDULED
            : Export::STATUS_PROGRESS;
        $export->file = '';
        $export->data = '';
        $export->save();

        $this->app['monolog']->addInfo('Created Export: ' . json_encode($export));

        $this->processExportItem($export);

        return 0;
    }

    /**
     * Covered cases:
     *      - export data to file right away
     *          -> requires: type, id
     *      - schedule an export
     *          -> requires: type, id, schedule_time
     *      - cron job task
     *          -> no argument should me provided
     *
     * @param InputInterface $input
     *
     * @return int
     * @throws \Exception
     */
    private function setupExportType(InputInterface $input)
    {
        $type = !empty($input->getArgument('type'));
        $target_id = !empty($input->getArgument('id'));
        $schedule_time = !empty($input->getArgument('schedule_time'));

        if ($type and $target_id and $schedule_time) {
            return $this->export_type = self::TYPE_SCHEDULE;
        }

        if ($type and $target_id) {
            return $this->export_type = self::TYPE_DIRECT;
        }

        if (!$type and !$target_id and !$schedule_time) {
            return $this->export_type = self::TYPE_CRON;
        }

        throw new \Exception('Undefined state.');
    }

    /**
     * @param Export $export
     */
    private function processExportItem($export)
    {
        $this->app['monolog']->addInfo(" processExportItem shouldProcess:{$export->shouldProcess()} ");
        if (!$export->shouldProcess()) {
            return;
        }

        $export->increment('attempts');
        $export->save();

        try {
            $this->app['monolog']->addInfo("Offline-campaigns: start");
            $export->status = Export::STATUS_PROGRESS;
            $export->save();
            $this->app['monolog']->addInfo(" processExportItem handlerName:{$export->getHandlerName()} getTargetFolderPath:{$export->getTargetFolderPath()}");
            $this->{$export->getHandlerName()}($export->target_id, $export->getTargetFolderPath(), $export);
        } catch (\Exception $e) {
            $export->status = Export::STATUS_FAILED;

            if($export->hasReachedMaxAttempts()){
                $export->data = "Export is locked. Retry attempts on fail exceeded: {$export->attempts}. The reason: {$e->getMessage()}";
            } else {
                $export->data = $e->getMessage();
            }
            $export->save();
            $this->app['monolog']->addError($e->getMessage());
        }
        if ($export->type == 'all_user_data') {
            $this->app['monolog']->addInfo(" processExportItem all_user_data");
            $this->sendNotification($export);
        }
        $this->app['monolog']->addInfo("Offline-campaigns: end");
    }

    /**
     * Send mail notification to configured email addresses.
     *
     * @param Export $export
     */
    private function sendNotification($export)
    {
        $status = $export->failed() ? 'failed' : 'finished';
        $subject = "All data export {$status} for {$export->target_id}";
        $body = "<div> <p>Export overview</p><ul>";
        $body .= "<li><b>Internal export ID: </b> {$export->id}</li>";
        $body .= "<li><b>Created:</b>{$export->created_at}</li>";
        $body .= "<li><b>Schedule for:</b>{$export->schedule_time}</li>";
        $body .= "<li><b>Status:</b> {$export->getStatus()}</li>";
        $body .= $export->failed()
            ? "<li><b>Fail reason:</b> {$export->data}</li>"
            : "<li><b>Download url: </b> <a href='{$export->getFile()}'>{$export->file}</a></li>";
        $body .= "</ul></div>";

        try {
            $this->app['monolog']->addInfo("  sendNotification subject: {$subject} body: {$body}");
            EmailQueue::sendInternalNotification($subject, $body,
                Config::getValue('export', 'notifications', null, false, true)
            );
        } catch (\Exception $e) {
            $this->app['monolog']->addError('sendNotification:' . $e->getMessage());
        }
    }

    /**
     * @param        $named_search_id
     * @param        $file_path
     * @param Export $export
     *
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    private function contactsList($named_search_id, $file_path, $export)
    {
        $repo = new MessagingRepository();

        /** @var NamedSearch $named_search */
        $named_search = NamedSearch::find($named_search_id);
        $keys = json_decode($named_search->output_fields);

        $contacts_filter = (new FilterClass(
            'users',
            new Request(),
            new FilterData(),
            json_decode($named_search->getAttribute('form_params')),
            $keys,
            false,
            $named_search->getAttribute('language')
        ))->setup();

        $contacts = DB::shsSelect('users', $contacts_filter->getSql());
        $contacts = $repo->basicAllowSendToUsers(collect($contacts)->pluck('id')->toArray())->toArray();

        $repo = new ContactsFilterRepository();
        $contacts = $repo->beautifyResult($contacts_filter, $contacts);
        $contacts = $repo->censorSensibleFields($contacts, true);

        $export->file = $this->generateFile('Contacts_list', $file_path, $keys, $contacts);
        $export->status = Export::STATUS_FINISHED;
        $export->save();
    }

    /**
     * @param $filename
     * @param $file_path
     * @param $keys
     * @param $contacts
     *
     * @return string
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    private function generateFile($filename, $file_path, $keys, $contacts)
    {
        if (!file_exists($file_path)) {
            mkdir($file_path, 0655, true);
        }

        $now = Carbon::now()->format("d_M_Y_H:i:s");
        $filename = "{$filename}_{$now}";

        $header = collect($keys)->map(function ($el) {
            return collect(explode('_', $el))->map(function ($word) {
                return ucfirst($word);
            })->implode(' ');
        });

        $excel = new PHPExcel();
        $excel->getProperties()->setCreator("System")->setTitle($filename);
        $excel->setActiveSheetIndex(0);
        $excel->getActiveSheet()->fromArray($header->toArray(), null, 'A1');

        $i = 2;
        foreach ($contacts as $contact) {
            $header->each(function ($title, $index) use ($i, $excel, $contact, $keys) {
                $contact = (array)$contact;
                $excel->getActiveSheet()->setCellValue(chr(65 + $index) . $i, $contact[$keys[$index]]);
            });
            $i++;
        }

        $excel_writer = PHPExcel_IOFactory::createWriter($excel, 'CSV');
        $excel_writer->save("{$file_path}{$filename}.csv");

        return "{$filename}.csv";
    }

    /**
     * @param OfflineCampaigns $campaign
     * @param MessagingRepository $repo
     * @param bool $persist
     * @return array
     *
     * @throws \Exception
     */
    private function offlineCampaignsGeneratePromo($campaign, $repo, $persist = true)
    {
        if (!$campaign->hasPromotion()) {
            return [null, null];
        }
        /** @var VoucherTemplate|BonusTypeTemplate $template */
        $template = $campaign->{"{$campaign->type}Template"}()->first();
        $bonus_type = null;
        $voucher = null;

        if ($campaign->type === 'bonus') {
            $bonus_type = $repo->generateBonusFromTemplate($template, $persist);
            if (is_string($bonus_type)) {
                throw (new \Exception($bonus_type));
            }
        }

        if ($campaign->type === 'voucher') {
            if (!empty($template->bonus_type_template_id) and !empty($template->trophy_award_id)) {
                throw (new \Exception("Voucher template bonus and reward id empty."));
            }

            if (!empty($template->bonus_type_template_id)) {
                $bonus_type = $repo->generateBonusFromTemplate($template->bonusTypeTemplate()->first(), $persist);
            }
            if (is_string($bonus_type)) {
                throw (new \Exception($bonus_type));
            }
            $voucher = $repo->createVoucherSeries($template, $bonus_type, false, false, $persist);

            if (is_string($voucher)) {
                throw (new \Exception($voucher));
            }
        }

        return [$bonus_type, $voucher];
    }

    /**
     * Generate bonuses/vouchers and the list of users
     *
     * @param        $campaign
     * @param        $file_path
     * @param Export $export
     *
     * @throws \Exception
     */
    private function offlineCampaigns($campaign, $file_path, $export)
    {
        $persist = true;
        $repo = new MessagingRepository();
        $contacts_repo = new ContactsFilterRepository();

        $repo->data['stats']['fail']['direct_mail_consent'] = 0;
        $repo->data['count'] = 0;

        /** @var OfflineCampaigns $campaign */
        $campaign = OfflineCampaigns::query()->find($campaign);

        list($bonus_type, $voucher) = $this->offlineCampaignsGeneratePromo($campaign, $repo, $persist);

        $named_search = NamedSearch::find($campaign->named_search);

        $contacts_filter = (new FilterClass(
            'users',
            new Request(),
            new FilterData(),
            json_decode($named_search->getAttribute('form_params')),
            $keys = ['id', 'username', 'firstname', 'lastname', 'address', 'country', 'city', 'zipcode', 'account_verified'],
            false,
            $named_search->getAttribute('language')
        ))->setup();

        $contacts = DB::shsSelect('users', $contacts_filter->getSql());
        $contacts = $repo->basicAllowSendToUsers(collect($contacts)->pluck('id')->toArray())
            ->filter(function($user) use (&$repo) {
                if (!$user->block_repo->hasConsentFor("direct", "mail", "bonus")) {
                    $repo->data['stats']['fail']['direct_mail_consent'] += 1;
                    return false;
                }
                return true;
            })
            ->map(function ($contact) use ($campaign, $voucher, $bonus_type, $persist) {
                if ($campaign->type === 'voucher' and $persist) {
                    $contact_array = (array)$contact;
                    ActionRepository::logAction(
                        $contact_array,
                        $voucher->voucher_code,
                        $campaign->type
                    );
                }

                if ($campaign->type === 'bonus' and $persist) {
                    (new UserFlag([
                        'user_id' => $contact->id,
                        'flag' => 'bonus-' . $bonus_type->id
                    ]))->save();
                }
                return $contact;
            });

        $contacts = $contacts_repo->beautifyResult($contacts_filter, $contacts->toArray());

        $export->file = $this->generateFile('Offline_campaign', $file_path, $keys, $contacts);
        $export->status = Export::STATUS_FINISHED;
        $export->data = json_encode($repo->data);
        $export->save();
    }

    /**
     * @param string $filename
     * @param string $delimiter
     * @return array|bool
     */
    private function csv_to_array($filename='', $delimiter=',')
    {
        if(!file_exists($filename) || !is_readable($filename))
            return FALSE;

        $header = NULL;
        $data = array();
        if (($handle = fopen($filename, 'r')) !== FALSE)
        {
            while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
            {
                if(!$header)
                    $header = $row;
                else
                    $data[] = array_combine($header, $row);
            }
            fclose($handle);
        }
        return $data;
    }

    /**
     * Generate bonuses/vouchers and the list of users
     *
     * @param        $campaign
     * @param        $file_path
     * @param Export $export
     *
     * @throws \Exception
     */
    private function offlineCampaignsGetExcluded($campaign, $file_path, $export) {
        /** @var Export $last_export */
        $last_export = Export::lastExport($campaign,'offline-campaigns', Export::STATUS_FINISHED);

        if (!$last_export) {
            $finished = Export::STATUS_FINISHED;
            throw new \Exception("There is no export for campaign: {$campaign} with status: {$finished}");
        }

        $data = json_decode($last_export->data);
        if (empty($data)) {
            throw new \Exception("Data for export {$last_export->id} is corrupted");
        }

        $repo = new MessagingRepository();

        $users_list = $this->csv_to_array($last_export->getTargetFolderPath() . $last_export->file);
        $users_list = array_pluck($users_list, 'Id');
        $good_users = $repo->basicAllowSendToUsers($users_list)
            ->filter(function($user) {
                return $user->block_repo->hasConsentFor("direct", "mail", "bonus");
            });

        // $users_list is the initial list of users
        // $good_users contains the list of users who still pass the verifications
        // $bad_users will be all the users who were in the initial list of users but did not pass the verifications
        $bad_users = array_diff($users_list, $good_users->pluck('id')->toArray());
        $excluded_users = array_map(function($user_id) {
            return (object)[
                'id' => $user_id
            ];
        }, $bad_users);

        $export->file = $this->generateFile('Offline_campaign_excluded_users', $file_path, ['id'], $excluded_users);
        $export->status = Export::STATUS_FINISHED;
        $export->data = json_encode($repo->data);
        $export->save();
    }


    private function getTableAlias($table)
    {
        return [
            'bets_mp' => 'bets multiplayer',
            'trophy_events' => 'trophies',
            'users_daily_balance_stats' => 'daily balance',
            'users_game_sessions' => 'game sessions',
            'users_games_favs' => 'fav games',
            'users_notifications' => 'notifications',
            'users_sessions' => 'sessions',
            'wins_mp' => 'wins multiplayer',
            'mts_transactions' => 'transactions',
            'mts_credit_cards' => 'credit cards',
        ][$table] ?? $table;
    }

    private function getUserCondition($user_id, $table)
    {
        // get all column names for $table
        $columns = DB::getSchemaBuilder()->getColumnListing($table);
        $possible_columns = ['user_id', 'actor'];
        if ($table === 'users') {
            $possible_columns[] = 'id';
        }
        return collect($possible_columns)
            ->filter(function ($column) use ($columns) {
                return in_array($column, $columns);
            })
            ->map(function ($column) use ($user_id, $table) {
                return " $table.$column = $user_id ";
            })
            ->implode(' or ');
    }

    /**
     * Get additional WHERE clause for tables
     *
     * @param string $table
     * @return string
     */
    private function getTableCondition(string $table): string
    {
        if (in_array($table, ['deposits', 'pending_withdrawals'], true)) {
            return "AND status = 'approved'";
        }

        return '';
    }

    /**
     * Map the transaction types for cash_transactions table.
     * If the transaction type is not in the list, it is removed as per requirements.
     *
     * @param $data
     * @return array
     */
    private function cashTransactionsMap($data)
    {
        $types = [3, 4, 6, 7, 8, 12, 13, 33, 34, 38, 43, 44, 45, 46, 47, 48, 50, 54, 60, 61, 64, 65, 66, 67, 82, 89, 91, 92, 94, 95, 96];
        $map = [82 => "Balance mutation"];

        foreach ($data as &$el) {
            if (!in_array($el->transactiontype, $types)) {
                $el = null;
                continue;
            }
            if (!empty($map[$el->transactiontype])) {
                $el->transactiontype = $map[$el->transactiontype];
            } else {
                $el->transactiontype = DataFormatHelper::getCashTransactionsTypeName($el->transactiontype);
            }
        }
        return array_filter($data);
    }

    /**
     * On all user data export there's a magic to_convert key which holds all the columns which have to be converted
     * After the amounts have been converted to user currency, the to_convert key will be removed to prevent misleading the users
     *
     * @param $data
     * @return array
     */
    private function convertCentsToUserCurrency($data)
    {
        if (!isset(current($data)->to_convert)) {
            return $data;
        }

        return array_map(function ($el) {
            if (!isset($el->to_convert)) {
                return $el;
            }
            foreach (explode(',', $el->to_convert) as $key) {
                $el->{$key} = DataFormatHelper::nf($el->{$key});
            }
            unset($el->to_convert);
            return $el;
        }, $data);
    }

    /**
     * @param integer $user_id
     * @param string  $file_path
     * @param Export  $export
     *
     * @throws \Exception
     */
    private function allUserData($user_id, $file_path, $export)
    {
        $sql = phive('SQL');
        $shouldReadArchive = (strtolower(getenv('ARCHIVE_EXPORT')) == "true");
        $user = User::query()->find($user_id);

        unlink($file_path_user = $file_path . $user_id);
        mkdir($file_path_user, 0777, true);

        $tables = [
            "users" => "SELECT email, mobile, country, IF(newsletter=0, 'no', 'yes') as newsletter, sex, lastname, firstname, address, city, zipcode, dob, preferred_lang, username, register_date, reg_ip, IF(verified_phone=0, 'no', 'yes') AS verified_phone, alias, currency FROM users",
            "deposits" => "SELECT amount, dep_type, timestamp, currency, ip_num, 'amount' AS to_convert FROM deposits",
            "pending_withdrawals" => "SELECT amount, payment_method, wallet, scheme, timestamp, currency, ip_num, 'amount' AS to_convert FROM pending_withdrawals",
            "failed_logins" => "SELECT ip, reg_country, login_country, username, created_at, reason_tag FROM failed_logins",
            "cash_transactions" => "SELECT amount, timestamp, transactiontype, currency, balance, 'amount,balance' AS to_convert FROM cash_transactions",
            "tournament_entries" => "SELECT cash_balance, won_amount, result_place, win_amount, status, updated_at, 'cash_balance,won_amount,win_amount' AS to_convert FROM tournament_entries",
            "users_daily_balance_stats" => "SELECT date, cash_balance, bonus_balance, currency, country, 'cash_balance' AS to_convert FROM users_daily_balance_stats",
            "users_notifications" => "SELECT tag, amount, created_at, currency, 'amount' AS to_convert FROM users_notifications",
            "users_sessions" => "SELECT created_at, updated_at, ended_at, equipment, end_reason, ip FROM users_sessions",
            "vouchers" => "SELECT voucher_code, IF(redeemed=0, 'no', 'yes') AS redeemed FROM vouchers",
            "bets" => "
                SELECT amount, micro_games.game_name, created_at, balance, currency, IF(bets.device_type=0, 'web', 'mobile') AS device_type, 'amount,balance' AS to_convert FROM bets
                LEFT JOIN micro_games ON bets.game_ref = micro_games.ext_game_name AND bets.device_type = micro_games.device_type_num
            ",
            "bets_mp" => "
                SELECT amount, micro_games.game_name, created_at, balance, currency, IF(bets_mp.device_type=0, 'web', 'mobile') AS device_type,'amount,balance' AS to_convert FROM bets_mp
                LEFT JOIN micro_games ON bets_mp.game_ref = micro_games.ext_game_name AND bets_mp.device_type = micro_games.device_type_num
            ",
            "bonus_entries" => "
                SELECT bonus_types.bonus_name, start_time, end_time,status, progress, bonus_entries.reward, bonus_entries.bonus_type FROM bonus_entries
                LEFT JOIN bonus_types ON bonus_types.id = bonus_entries.bonus_id
            ",
            "trophy_events" => "
                SELECT created_at, trophy_type, game_name FROM trophy_events
                LEFT JOIN micro_games ON trophy_events.game_ref = micro_games.ext_game_name
            ",
            "users_game_sessions" => "
                SELECT start_time, end_time, game_name, IF(device_type=0, 'web', 'mobile') AS device_type, ip, bet_amount, win_amount, result_amount, balance_start, balance_end, 'bet_amount,win_amount,result_amount,balance_start,balance_end' AS to_convert FROM users_game_sessions
                LEFT JOIN micro_games ON users_game_sessions.game_ref = micro_games.ext_game_name AND users_game_sessions.device_type_num = micro_games.device_type_num
            ",
            "users_games_favs" => "
                SELECT created_at, game_name FROM users_games_favs
                LEFT JOIN micro_games ON users_games_favs.game_id = micro_games.id
            ",
            "wins" => "
                SELECT game_name, amount, created_at, balance, currency, IF(wins.device_type=0, 'web', 'mobile') AS device_type, 'amount,balance' AS to_convert FROM wins
                LEFT JOIN micro_games ON wins.game_ref = micro_games.ext_game_name AND wins.device_type = micro_games.device_type_num
            ",
            "wins_mp" => "
                SELECT game_name, amount, created_at, balance, currency, IF(wins_mp.device_type=0, 'web', 'mobile') AS device_type, 'amount,balance' AS to_convert FROM wins_mp
                LEFT JOIN micro_games ON wins_mp.game_ref = micro_games.ext_game_name AND wins_mp.device_type = micro_games.device_type_num
            "
        ];

        // add all tables containing user info
        collect(array_keys($tables))
            ->each(function ($table) use ($user, $file_path_user, $tables, $shouldReadArchive, $sql) {
                $isArchiveTable = $sql->isArchive($table);
                $userId = $user->id;

                if (empty($user_condition = $this->getUserCondition($userId, $table))) {
                    return;
                }

                if (empty($query = $tables[$table])) {
                    return;
                }

                $table_condition = $this->getTableCondition($table);

                $modifiedQuery = "{$query} WHERE {$user_condition} {$table_condition}";
                $data = ReplicaDB::shSelect($userId, $table, $modifiedQuery);

                if (empty($data) && (!$shouldReadArchive || !$isArchiveTable)) {
                    return;
                }

                if ($shouldReadArchive && $isArchiveTable) {
                    $data = json_decode(json_encode($data), true);
                    $sql->prependFromNodeArchive($data, $userId, null, $modifiedQuery, $table);
                }

                if ($table === 'cash_transactions') {
                    $data = $this->cashTransactionsMap($data);
                }

                $data = $this->convertCentsToUserCurrency($data);

                $file_name = $table;
                if ($table === 'pending_withdrawals') {
                    $file_name = 'withdrawals';
                }

                $this->createCsvFile(
                    "{$file_path_user}/{$this->getTableAlias($file_name)}.csv",
                    array_keys((array)current($data)),
                    $data
                );
            });
        // end - add all tables containing user info

        // MTS
        $mts = new Mts($this->app);
        try {
            collect($mts->dataManagementDownload($user_id))
                ->each(function ($data, $table) use ($user, $file_path_user) {
                    if (empty($data)) {
                        return;
                    }

                    $data = $this->convertCentsToUserCurrency($data);

                    $table = "mts_{$table}";
                    $this->createCsvFile("{$file_path_user}/{$this->getTableAlias($table)}.csv", array_keys((array)current($data)), $data);
                });
        } catch (\Exception $e) {
            $this->app['monolog']->addError("Export: allUserData - code: {$e->getCode()}, message: {$e->getMessage()}");
            if ($e->getCode() == 404) {
                // ignore, we didn't find the user
                $this->app['monolog']->addError("Export: allUserData - {$user_id} not found in MTS.");
            } else {
                throw new \Exception($e->getMessage(), $e->getCode());
            }
        }
        // end - MTS

        // DMAPI
        if (!file_exists($files_path = $file_path_user . '/files')) {
            mkdir($files_path, 0777, true);
        }

        try {
            $dmapi = (new Dmapi($this->app))->dataManagementDownload($user_id);

            collect($dmapi['files'])
                ->map(function ($url) {
                    return [
                        'name' => array_last(explode('/', $url)),
                        'url' => $url
                    ];
                })
                ->each(function ($file) use ($files_path) {
                    file_put_contents("{$files_path}/{$file['name']}", fopen($file['url'], 'r'));
                });

            collect($dmapi['tables'])->each(function ($data, $table) use ($user, $file_path_user) {
                if (empty($data)) {
                    return;
                }
                $this->createCsvFile(
                    "{$file_path_user}/{$this->getTableAlias($table)}.csv",
                    array_keys((array)current($data)),
                    $data
                );
            });
        } catch (\Exception $e) {
            $this->app['monolog']->addError("Export: allUserData - code: {$e->getCode()}, message: {$e->getMessage()}");
            if ($e->getCode() == 404) {
                // ignore, we didn't find the user
                $this->app['monolog']->addError("Export: allUserData - {$user_id} not found in DMAPI.");
            } else {
                throw new \Exception($e->getMessage(), $e->getCode());
            }
        }
        // end - DMAPI

        $now = Carbon::now()->format("d_M_Y_H_i_s");
        $zip_file = "{$user->id}_{$now}.zip";
        $this->createZip($file_path_user, $file_path . $zip_file, true);
        if(file_exists($file_path_user)) {
            $this->recursiveRemoveDirectory($file_path_user);
        }

        $export->file = $zip_file;
        $export->status = Export::STATUS_FINISHED;
        $export->save();
    }

    /**
     * @param $file_full_path
     * @param $columns
     * @param $values
     */
    private function createCsvFile($file_full_path, $columns, $values)
    {
        $target_file = fopen($file_full_path, "w") or dd("Unable to open file!");

        // setup header
        fputcsv($target_file, $columns);
        foreach ($values as $element) {
            $element = json_decode(json_encode($element), true);
            fputcsv($target_file, array_values($element));
        }

        fclose($target_file);
    }

    /**
     * @param      $source_folder
     * @param      $full_path_file
     * @param bool $remove_source_folder
     */
    private function createZip($source_folder, $full_path_file, $remove_source_folder = false)
    {
        // Get real path for our folder
        $rootPath = realpath($source_folder);

        // Initialize archive object
        $zip = new ZipArchive();
        $zip->open($full_path_file, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        /** @var SplFileInfo[] $files */
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootPath),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            // Skip directories (they would be added automatically)
            if (!$file->isDir()) {
                // Get real and relative path for current file
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($rootPath) + 1);

                // Add current file to archive
                $zip->addFile($filePath, $relativePath);
            }
        }

        // Create the zip file
        if ($zip->close() and $remove_source_folder) {
            // remove files from temporary directory
            array_map('unlink', glob("$source_folder/*.*"));
            // remove temporary directory
            rmdir($source_folder);
        }
    }

    /**
     * Recursive remove directory
     *
     * @param $src
     */
    private function recursiveRemoveDirectory($src)
    {
        $dir = opendir($src);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                $full = $src . '/' . $file;
                if (is_dir($full)) {
                    $this->recursiveRemoveDirectory($full);
                } else {
                    unlink($full);
                }
            }
        }
        closedir($dir);
        rmdir($src);
    }
}
