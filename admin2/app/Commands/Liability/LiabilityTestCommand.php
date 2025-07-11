<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 4/26/16
 * Time: 12:28 PM
 */

namespace App\Commands\Liability;

use App\Commands\LiabilityCommand;
use App\Extensions\Database\FManager as DB;
use App\Helpers\DataFormatHelper;
use App\Models\User;
use App\Models\UserDailyBalance;
use App\Repositories\LiabilityRepository;
use Carbon\Carbon;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LiabilityTestCommand extends LiabilityCommand
{

    /** @var  OutputInterface $output */
    protected $output;

    private $user;

    protected function configure()
    {
        $this->setName("liability:test")
            ->setDescription("Liability Test Job");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //$this->output = $output;
        //$this->rebuildDay(Carbon::create(2017, 8, 1));

        //$this->testExcel();

        //$this->testRecalcCategory();

        //dd("DONE");

        return 0;
    }

    private function testRecalcCategory()
    {

        $connection = DB::connection();

        $connection->setFetchMode(\PDO::FETCH_ASSOC);

        $repo = new LiabilityRepository(2017, 9);

        $dep = count($repo->getDeposits());

        $manual = count($repo->getDeposits(true));

        dd($dep, $manual);
    }

    private function testExcel()
    {
        /** @var User user */
        $this->user = User::findByUsername('devtestse');
        $day = Carbon::parse('2017-09-04');

        $queries = LiabilityRepository::getUserTransactionListQueries($this->user, $day, true);

        $data = $queries['bets']->union($queries['wins'])->union($queries['cash'])->orderBy('date', 'asc')->get();
        $total = count($data);

        $filename = "Transactions_list_{$this->user->id}_{$this->user->username}_{$day->toDateString()}b";

        $opening_balance = $this->user->repo->getBalance($day);
        $header = ['Date', 'Type', 'Transaction ID', 'Amount (cents)', 'Balance (cents)', $opening_balance, '', 'Description', 'More info'];

        $excel = new \PHPExcel();
        $excel->getProperties()->setCreator("System")->setTitle($filename);

        $excel->setActiveSheetIndex(0);
        $excel->getActiveSheet()->fromArray($header, null, 'A1');

        $i = 2;
        foreach ($data as $row) {
            $cmp_balances = strtolower($row->type) == 'win' ? "=D{$i}+E{$i}-F{$i}" : "=E{$i}-F{$i}";
            $excel->getActiveSheet()
                ->setCellValue("A{$i}", $row->date)
                ->setCellValue("B{$i}", is_numeric($row->type) ? DataFormatHelper::getCashTransactionsTypeName($row->type) : ucwords($row->type))
                ->setCellValue("C{$i}", $row->id)
                ->setCellValue("D{$i}", $row->amount)
                ->setCellValue("E{$i}", $row->balance)
                ->setCellValue("F{$i}", "=F" . ($i - 1) . "+D{$i}")
                ->setCellValue("G{$i}", $cmp_balances)
                ->setCellValue("H{$i}", $row->description)
                ->setCellValue("I{$i}", $row->more_info);
            $i++;
        }

        //Adjust columns
        $excel->getActiveSheet()->getColumnDimension('C')->setWidth(100.0);
        $excel->getActiveSheet()->getColumnDimension('C')->setAutoSize(false);
        $excel->getActiveSheet()->getColumnDimension('D')->setWidth(100.0);
        $excel->getActiveSheet()->getColumnDimension('D')->setAutoSize(false);
        $excel->getActiveSheet()->getColumnDimension('E')->setWidth(100.0);
        $excel->getActiveSheet()->getColumnDimension('E')->setAutoSize(false);

        $excel_writer = \PHPExcel_IOFactory::createWriter($excel, 'OpenDocument');

        $file = getenv('STORAGE_PATH') . "/reports/{$filename}.ods";
        $excel_writer->save($file);
    }

    private function rebuildDay(Carbon $date)
    {
        DB::connection()->setFetchMode(\PDO::FETCH_ASSOC);

        $source = 3;

        //I get the list with people that has any kind of transaction on the day to get the balance using the latest one
        $user_list = DB::select("SELECT DISTINCT sub.user_id FROM 
              (SELECT DISTINCT user_id FROM cash_transactions WHERE timestamp BETWEEN :start_date AND :end_date
                UNION
                SELECT DISTINCT user_id FROM users_daily_stats WHERE date = :uds_date AND (bets > 0 OR wins > 0 OR frb_wins > 0)) AS sub", [
            'start_date' => $date->copy()->startOfDay()->toDateTimeString(),
            'end_date' => $date->copy()->endOfDay()->toDateTimeString(),
            'uds_date' => $date->copy()->toDateString()
        ]);

        $insert = [];
        $udbs_date = $date->copy()->addDay()->toDateString();
        foreach ($user_list as $key => $sub) {
            $user_id = $sub['user_id'];
            $user = User::find($user_id);
            //check the oldest transaction that day
            $latest = $this->getDayLastTransaction($date->copy(), $user_id);

            if (empty($latest)) {
                throw new \Exception("No transaction found for user $user_id");
            }

            $insert[] = [
                'user_id' => $user_id,
                'date' => $udbs_date,
                'cash_balance' => $latest['balance'],
                'bonus_balance' => 0,
                'currency' => $user->currency,
                'country' => $user->country,
                'source' => $source
            ];
        }

        UserDailyBalance::bulkInsert($insert);

        //I get the people without transactions and with previous day balance
        $rows_to_copy = DB::select("SELECT udbs.user_id, udbs.cash_balance, udbs.bonus_balance, udbs.country, udbs.currency, :new_date AS date, :new_source AS source FROM users_daily_balance_stats udbs
                      LEFT JOIN (SELECT DISTINCT user_id FROM cash_transactions WHERE timestamp BETWEEN :start_date_cash AND :end_date_cash) AS ctu ON ctu.user_id = udbs.user_id
                      LEFT JOIN (SELECT DISTINCT user_id FROM users_daily_stats WHERE date = :date_daily_stats AND (bets > 0 OR wins > 0 OR frb_wins > 0)) AS s1 ON s1.user_id = udbs.user_id
                    WHERE udbs.source = 0 AND udbs.date = :date_balance_stats AND s1.user_id IS NULL AND ctu.user_id IS NULL  ORDER BY udbs.cash_balance DESC", [
            'start_date_cash' => $date->copy()->startOfDay()->toDateTimeString(),
            'end_date_cash' => $date->copy()->endOfDay()->toDateTimeString(),
            'date_daily_stats' => $date->copy()->toDateString(),
            'date_balance_stats' => $date->copy()->subDay()->toDateString(),
            'new_date' => $date->copy()->addDay()->toDateString(),
            'new_source' => $source
        ]);

        UserDailyBalance::bulkInsert($rows_to_copy);
    }

    private function getDayLastTransaction(Carbon $date, $user_id)
    {
        $start_date = $date->startOfDay()->toDateTimeString();
        $end_date = $date->endOfDay()->toDateTimeString();

        return DB::select("SELECT *
            FROM (SELECT
                    'cash'    AS type,
                    id,
                    timestamp AS created_at,
                    balance
                  FROM cash_transactions
                  WHERE id = (SELECT MAX(id)
                              FROM cash_transactions
                              WHERE user_id = {$user_id} AND timestamp BETWEEN '{$start_date}' AND '{$end_date}')
                  UNION
                  SELECT
                    'bet' AS type,
                    id,
                    created_at,
                    balance
                  FROM bets
                  WHERE id = (SELECT MAX(id)
                              FROM bets
                              WHERE user_id = {$user_id} AND created_at BETWEEN '{$start_date}' AND '{$end_date}')
                  UNION
                  SELECT
                    'win' AS type,
                    id,
                    created_at,
                    (balance + amount) as balance
                  FROM wins
                  WHERE id = (SELECT MAX(id)
                              FROM wins
                              WHERE
                                user_id = {$user_id} AND created_at BETWEEN '{$start_date}' AND '{$end_date}')) AS sub
            ORDER BY created_at DESC
            LIMIT 1")[0];
    }

}