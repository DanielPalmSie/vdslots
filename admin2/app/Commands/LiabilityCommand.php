<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 4/20/16
 * Time: 12:28 PM
 */

namespace App\Commands;

use App\Classes\PR;
use App\Extensions\Database\Connection\Connection;
use App\Models\UserMonthlyLiability;
use App\Repositories\LiabilityRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Ivoba\Silex\Command\Command;
use Silex\Application;
use Symfony\Component\Console\Output\OutputInterface;
use App\Extensions\Database\FManager as DB;

class LiabilityCommand extends Command
{

    /** @var  OutputInterface $output */
    protected $output;

    /** @var array $data Stores data in the class to save data over the commands */
    protected $data = [];

    protected function generateData($year, $month, $no_bets_or_wins = false, $day = null)
    {
        $repo = new LiabilityRepository($year, $month, null, null, $day);

        $this->output->writeln("{$this->now()} Starting liability data generation for year $year and month $month. ");

        //todo check how to do it as it is not manual and something happen a lot of wd will be flagged during the night
        //$this->liabilityProgressStatus($date->copy()->format('Y-m'), 'start');

        $callback = function (Connection $connection) use ($repo, $no_bets_or_wins) {

            $this->output->writeln("{$this->now()} Processing: VS {$connection->getName()}");

            $connection->setFetchMode(\PDO::FETCH_ASSOC);

            $this->data['connection'] = $connection;
            $repo->setConnection($connection);

            if (empty($no_bets_or_wins)) {
                $this->insertData($repo->getBetsAndWins($repo::CAT_BETS));
                $this->insertData($repo->getBetsAndWins($repo::CAT_WINS));
                $this->insertData($repo->getBetsAndWins($repo::CAT_FRB_WINS));

                //Sportsbook stats
                $this->insertData($repo->getSportsbookData('wins'));
                $this->insertData($repo->getSportsbookData('bets'));
                $this->insertData($repo->getSportsbookData('void'));
            }
            $this->insertData($repo->getDeposits());
            $this->insertData($repo->getDeposits(true));
            $this->insertData($repo->getMismatchedDeposits());
            $this->insertData($repo->getWithdrawals());
            $this->insertData($repo->getWithdrawals(true));

            $this->insertData($repo->getTournament($repo::CAT_BOS_BUYIN_34));
            $this->insertData($repo->getTournament($repo::CAT_BOS_PRIZES_38));
            $this->insertData($repo->getTournament($repo::CAT_BOS_HOUSE_RAKE_52));
            $this->insertData($repo->getTournament($repo::CAT_BOS_REBUY_54));
            $this->insertData($repo->getCancelledTournament($repo::CAT_BOS_CANCEL_BUYIN_61));
            $this->insertData($repo->getCancelledTournament($repo::CAT_BOS_CANCEL_HOUSE_FEE_63));
            $this->insertData($repo->getCancelledTournament($repo::CAT_BOS_CANCEL_REBUY_64));
            $this->insertData($repo->getCancelledTournament($repo::CAT_BOS_CANCEL_PAYBACK_65));
            $this->insertData($repo->getRewards());
            $this->insertData($repo->getBonuses());
            $this->insertData($repo->getFailedBonus());
            $this->insertData($repo->getBoosterVaultTransfer());
            $this->insertData($repo->getMisc());
            $this->insertData($repo->getChargebackSettlement());
            $this->insertData($repo->getTaxDeductions());
            $this->insertData($repo->getUndoneWithdrawals(null, 'zimpler'));
            $this->insertData($repo->getRollbacks($repo::CAT_BET_REFUND_7));
            $this->insertData($repo->getRollbacks($repo::CAT_WIN_ROLLBACK_7));
            $this->insertData($repo->getSportsAgentFee(106));
            $this->insertData($repo->getSportsAgentFee(107));

            if ($repo->getDate()->lessThanOrEqualTo(Carbon::create(2016, 11))) {
                $this->insertData($repo->getAffiliatePayouts());
            }
        };

        if (DB::isSharded((new UserMonthlyLiability())->getTable())) {
            DB::loopNodes($callback); //table will be global and sharded
        } else {
            $callback(DB::getMasterConnection());
        }

        if ($repo->getDate()->greaterThan(Carbon::create(2016, 11))) {
            $this->generatePartnerroomData($repo);
        }

        //todo check how to do it as it is not manual and something happen a lot of wd will be flagged during the night
        //$this->liabilityProgressStatus($date->copy()->format('Y-m'), 'end');

        // We update the misc_cache value to track until which year and month the PLR was generated
        phive()->miscCache('reports-last-users_monthly_liability', "$year-$month", true);

        $this->output->writeln("{$this->now()} Finished.");
    }

    /**
     * We save the status of the users monthly liability data generation to be able to avoid issues on the withdrawals liability calculations
     *
     * @param $date
     * @param $action
     */
    protected function liabilityProgressStatus($date, $action)
    {
        $reports_info = phive('SQL')->loadKeyValues("SELECT * FROM misc_cache WHERE id_str LIKE 'reports-%-users_monthly_liability'", 'id_str', 'cache_value');

        if ($action == 'start' || $action == 'restart') {
            if (empty($reports_info['reports-processing-users_monthly_liability'])) {
                DB::statement("INSERT INTO misc_cache VALUES ('reports-processing-users_monthly_liability', '{$date}')");
            } else {
                DB::statement("UPDATE misc_cache SET cache_value = '{$date}' WHERE id_str = 'reports-processing-users_monthly_liability'");
            }
        } elseif ($action == 'end') {
            if (empty($reports_info['reports-last-users_monthly_liability'])) {
                DB::statement("INSERT INTO misc_cache VALUES ('reports-last-users_monthly_liability', '{$date}')");
            } elseif ($reports_info['reports-last-users_monthly_liability'] < $date) {
                DB::statement("UPDATE misc_cache SET cache_value = '{$date}' WHERE id_str = 'reports-last-users_monthly_liability'");
            }
            DB::statement("DELETE FROM misc_cache WHERE id_str = 'reports-processing-users_monthly_liability'");
        }
    }

    protected function insertData($data, $connection = false)
    {
        if ($connection === false) {
            UserMonthlyLiability::bulkInsert($data, null, $this->data['connection']);
            if (UserMonthlyLiability::isMasterAndSharded()) {
                UserMonthlyLiability::bulkInsert($data, null, DB::getMasterConnection());
            }
        } else {
            UserMonthlyLiability::bulkInsert($data, 'user_id', $connection);
        }
    }

    protected function generatePartnerroomData(LiabilityRepository $repo)
    {
        /** @var Application $app */
        $app = $this->getSilexApplication();
        /*
         * Process PR data
         */
        if ($app['pr.config']['liability.support']) {
            $this->output->writeln("{$this->now()} Processing: Partnerroom.");
            $pr_rpc = new PR($app);
            $this->insertData($repo->getPRWithdrawals($pr_rpc), DB::connection());
            $this->insertData($repo->getPRWithdrawals($pr_rpc, true), DB::connection());
            $this->insertData($repo->getPRTransactions($pr_rpc), DB::connection());
        } else {
            $this->output->writeln('Partnerroom support disabled');
        }
    }

    /**
     * @param $year
     * @param $month
     * @param array $in_categories
     * @param array $not_in_categories
     * @return mixed
     */
    protected function deleteData($year, $month, $in_categories = [], $not_in_categories = [])
    {
        /** @var Builder $partial_query */
        $partial_query = UserMonthlyLiability::where(['year' => $year, 'month' => $month]);

        if (!empty($not_in_categories)) {
            $partial_query->whereNotIn('main_cat', $not_in_categories);
        }

        if (!empty($in_categories)) {
            $partial_query->whereIn('main_cat', $in_categories);
        }

        return $partial_query->delete();
    }


    protected function now()
    {
        return '[' . Carbon::now()->format('Y-m-d H:i:s') . ']';
    }

}
