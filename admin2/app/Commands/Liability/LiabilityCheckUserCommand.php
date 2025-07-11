<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 4/26/16
 * Time: 12:28 PM
 */

namespace App\Commands\Liability;

use App\Commands\LiabilityCommand;
use App\Helpers\DataFormatHelper;
use App\Models\User;
use App\Repositories\LiabilityRepository;
use Carbon\Carbon;
use PHPExcel;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\ReplicaFManager as ReplicaDB;
use PHPExcel_IOFactory;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class LiabilityCheckUserCommand extends LiabilityCommand
{

    /** @var  OutputInterface $output */
    protected $output;

    /** @var  InputInterface $input */
    protected $input;

    /** @var array $data */
    protected $data = [];

    /** @var  LiabilityRepository $repo */
    private $repo;

    protected function configure()
    {
        $this->setName("liability:check")
            ->setDescription("Liability Job: check liability for a given customer.")
            ->addArgument('user', InputArgument::REQUIRED, 'User id or username')
            ->addArgument('database', InputArgument::OPTIONAL, 'Database to be used, either reader or main', 'reader')
            ->addArgument('force', InputArgument::OPTIONAL, 'Force a download of a whole month');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
      $this->output = $output;

      $argument = $input->getArgument('user');
      if (is_numeric($argument)) {
        $user = ReplicaDB::shSelect($argument, 'users', "SELECT * FROM users WHERE id = {$argument}", [], true);
      } else {
        $user = ReplicaDB::shsSelect('users', "SELECT * FROM users WHERE username = '{$argument}' ", [], [], null);
      }

      if (empty($user)) {
        $output->writeln("User not found");
        return 1;
      }

      /** @var User $user */
      $user = User::sh($user[0]->id)->find($user[0]->id);

      $this->repo = new LiabilityRepository(Carbon::now()->year, Carbon::now()->month);

      $res_totals = $this->repo->getLastTotalLastPeriod($user);

      $this->output->writeln('User: ' . json_encode(['user_id' => $user->id, 'username' => $user->username]));
      $this->output->writeln('Previous: ' . json_encode($res_totals['previous']));
      $this->output->writeln('Current: ' . json_encode($res_totals['current']));

      if (empty($res_totals['previous']) && empty($res_totals['current']) && empty($input->getArgument('force'))) {
        return 1;
      }

      $helper = $this->getHelper('question');

      $question = new ChoiceQuestion(
        "Please select the month to check:",
        ['Exit', Carbon::now()->subMonth()->format('F'), Carbon::now()->format('F')],
        '0'
      );

      $month = $helper->ask($input, $output, $question);

      if ($month == 'Exit') {
        return 1;
      }

      $date = Carbon::createFromFormat('F', $month);
      if ($date->month == 12 && Carbon::now()->month == 1) {
        $date->year(Carbon::now()->subYear()->year);
      } else {
        $date->year(Carbon::now()->year);
      }

      $tmp_repo = new LiabilityRepository($date->year, $date->month);

      $unallocated_list = $tmp_repo->getUnallocatedAmount($user);

      if (empty($unallocated_list)) {
        $this->output->writeln("Nothing found on $month");
        return 1;
      }

      $table = new Table($output);
      $table->setHeaders(['Date', 'Opening', 'Liability', 'Closing', 'Unallocated', '# of rollbacks'])
        ->setRows($unallocated_list);
      $table->render();

      $question_unallocated = new ChoiceQuestion(
        "Please select the day to get the breakdown:",
        array_merge(['Month', 'All'], array_keys($unallocated_list)),
        '0'
      );

      $day = $helper->ask($input, $output, $question_unallocated);

      if (in_array(strtolower($day), ['all', 'month'])) {
        $date_to_export = [
          'start' => $date->copy()->startOfMonth(),
          'end' => $date->copy()->endOfMonth()
        ];
      } else {
        $date_to_export = Carbon::parse($day);
      }

      $filter = strtolower($day) != 'all';

      $queries = LiabilityRepository::getUserTransactionListQueries($user, $date_to_export, $filter);

      $records[] = ['Date', 'Type', 'Transaction ID', 'Amount (cents)', 'Balance (cents)', 'Description', 'More info'];

      $data = $queries['bets']
        ->union($queries['wins'])
        ->union($queries['cash'])
        ->union($queries['sports'])
        ->orderBy('date', 'asc')
        ->orderBy('weight', 'asc')
        ->orderBy('id', 'asc')
        ->get();

      $this->exportTo($data, $date_to_export, $user);

      return 0;
    }

    /**
     * @param $data
     * @param Carbon|Carbon[] $date
     * @param User $user
     * @param string $type
     */
    private function exportTo($data, $date, User $user, $type = 'OpenDocument')
    {
        if (is_array($date)) {
            $date_string = $date['start']->toDateString() . '_to_' . $date['end']->toDateString();
            $opening_balance = $user->repo->getBalance($date['start']);
        } else {
            $date_string = $date->toDateString();
            $opening_balance = $user->repo->getBalance($date);
        }

        $filename = "Transactions_list_{$user->id}_{$user->username}_{$date_string}";

        $header = ['Date', 'Type', 'Transaction ID', 'Amount (cents)', 'Balance (cents)', $opening_balance, '', 'Description', 'More info'];

        $excel = new PHPExcel();
        $excel->getProperties()->setCreator("System")->setTitle($filename);

        $excel->setActiveSheetIndex(0);
        $excel->getActiveSheet()->fromArray($header, null, 'A1');

        $i = 2;
        foreach ($data as $row) {
            $cmp_balances = strtolower($row->type) == 'win' || strtolower($row->type) == 'void' ? "=D{$i}+E{$i}-F{$i}" : "=E{$i}-F{$i}";
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

        $excel_writer = PHPExcel_IOFactory::createWriter($excel, $type);

        $file = getenv('STORAGE_PATH') . "/reports/{$filename}.ods";
        $filepath = getenv('LIABILITY_CHECK_REPORT_REMOTE_PATH');
        $excel_writer->save($file);

        $this->output->writeln("Done.");
        if ($this->getSilexApplication()['env'] == 'prod') {
            $this->output->writeln("scp melita1:$file $filepath/.");
        } elseif ($this->getSilexApplication()['env'] == 'dev') {
            exec("libreoffice -o {$file}");
        }
    }

}
