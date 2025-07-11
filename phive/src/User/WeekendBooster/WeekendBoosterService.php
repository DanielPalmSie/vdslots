<?php

declare(strict_types=1);

namespace Videoslots\User\WeekendBooster;

final class WeekendBoosterService
{

	/**
	 * Cash out booster vault of the user
	 *
	 * @param int $userId
	 * @return array
	 */
	public function cashoutBoosterVault(int $userId): array
	{
		if (!self::getBooster()->hasBoosterVault($userId)) {
			return ['msg' => 'User does not have booster vault', 'success' => false];
		}

		try {
			return self::getBooster()->addBoosterToCredit($userId);
		} catch (\Exception $e) {
			self::log('cashoutBoosterVault', ['message' => $e->getMessage(), 'user_id' => $userId]);
			return ['msg' => $e->getMessage(), 'success' => false];
		}
	}

	/**
	 * Opt out or in user from booster vault
	 *
	 * @param array $data
	 * @return array
	 */
	public function optoutFromBoosterVault(array $data): array
	{
		$opt = $data['opt'] ?? '';
		$userId = $data['user_id'] ?? 0;

		if (empty($opt) || empty($userId)) {
			self::log('optoutFromBoosterVault', ['message' => 'Request data is missing opt or user_id']);
			return ['msg' => 'Request data is missing', 'success' => false];
		}

		if (!self::getBooster()->canUseBooster($userId)) {
			return ['msg' => 'User does not have booster vault', 'success' => false];
		}

		if ($opt === 'out') {
			$success = self::getBooster()->optToVault($userId, false);
			$msg = $success ? t('my.booster.vault.opted.out') : 'User already opted out';
		} elseif ($opt === 'in') {
			$success = self::getBooster()->optToVault($userId, true);
			$msg = $success ? t('my.booster.vault.opted.in') : 'User already opted in';
		} else {
			$success = false;
			$msg = '';
		}

		return ['msg' => $msg, 'success' => $success];
	}

	/**
	 * Check if the user has a special weekend booster
	 *
	 * @param int $user_id
	 * @return bool
	 */
	public function isSpecialWeekendBooster(int $user_id): bool
	{
		return self::getBooster()->canUseBooster($user_id);
	}

	/**
	 * Retrieves the parameters required for printing cashback details.
	 *
	 * @param bool $weekly Indicates whether the cashback is for a weekly period. Default is false.
	 * @param array $apiRequestData An array containing the API request data.
	 * @return array The parameters needed for printing cashback details.
	 */
	function getPrintCashbackParams($weekly = false, array $apiRequestData = []): array
	{
		$u = !empty($this->cur_user) ? cu($this->cur_user) : cu($apiRequestData['user_id'] ?? false);
		if (empty($u)) {
			return [];
		}

		$this->updateGetParams($apiRequestData);
		extract(handleDatesSubmit());

		$show_special_booster_info = self::getBooster()->canUseBooster($u) === true;
		$show_daily_drilldown = false;
		if ($show_special_booster_info === true && phive()->validateDate($_GET['day'])) {
			$show_daily_drilldown = true;
			$page_size = 20;
			list($stats_with_all_rows, $total) = self::getBooster()->getWinsWithBoostedAmount($u->getId(), $_GET['day'], $_GET['page'], $page_size);
			phive("Paginator")->setPages($total, '', $page_size);
		} else {
			$where_extra = " AND user_id = {$u->getId()} ";
			$stats = phive("UserHandler")->getCasinoStats($sdate, $edate, $type, $where_extra, '', '', '', '', false, '', false, phive('UserHandler')->dailyTbl(), $u->getId());

			$stats_with_all_rows = [];
			// Create at least 1 entry for each month/day of the final array (1-12 month, or 1-28/31 day), so later we can compare the full array with the one from "cash_transaction" to add "transferred_to_vault".
			foreach (range(1, $e_month) as $month_or_day) {
				$iso_date = $type == 'month' ? "$year-" . padMonth($month_or_day) : "$year-" . padMonth($month) . '-' . padMonth($month_or_day);
				// we need only this key to avoid errors.
				$stats_with_all_rows[$iso_date] = ['gen_loyalty' => 0];

				// if a key exist in the stats from DB we override it with all the values.
				foreach ($stats as $daily_stat) {
					$compare_date = $type == 'month' ? substr($daily_stat['date'], 0, 7) : substr($daily_stat['date'], 0, 10);
					if ($compare_date == $iso_date) {
						$stats_with_all_rows[$iso_date] = $daily_stat;
						continue;
					}
				}
			}

			// when true it means that we could have "transferred_to_vault" data coming from the cash_transactions table that we need to add to user_daily_stats gen_loyalty.
			if ($show_special_booster_info === true) {
				$stats_from_cash_transaction = self::getBooster()->getAggreatedWinsFromCashTransactions($u->getId(), $_GET['year'], $_GET['month']);

				foreach ($stats_with_all_rows as $iso_date => $daily_stat) {
					foreach ($stats_from_cash_transaction as $cash_stat) {
						$compare_date = $type == 'month' ? $cash_stat['year'] . '-' . padMonth($cash_stat['month']) : $cash_stat['year'] . '-' . padMonth($cash_stat['month']) . '-' . padMonth($cash_stat['day']);
						if ($compare_date == $iso_date) {
							$stats_with_all_rows[$iso_date]['gen_loyalty'] += abs($cash_stat['transferred_to_vault']);
							continue;
						}
					}
				}
			}
		}

		if ($this->site_type == 'mobile') {
			$cols = $show_daily_drilldown ? [150, 100, 75, 75] : [270, 100];
		} else {
			$cols = $show_daily_drilldown ? [160, 290, 105, 105] : [560, 100];
		}

		return array(
			'e_month' => $e_month,
			'stats' => $stats_with_all_rows,
			'cols' => $cols,
			'weekly' => $weekly ? true : false,
			'type' => $type,
			'show_special_booster_info' => $show_special_booster_info,
			'show_daily_drilldown' => $show_daily_drilldown
		);
	}

	/**
	 * Updates the GET parameters for the API request.
	 *
	 * @param array $apiRequestData An associative array containing the API request data.
	 *
	 * @return void
	 */
	public function updateGetParams(array $apiRequestData): void
	{
		if (!empty($apiRequestData)) {
			foreach ($apiRequestData as $key => $value) {
				$_GET[$key] = $value;
			}
		}
	}

    /**
     * @return \Booster
     */
    private static function getBooster(): \Booster
    {
        /** @var \Booster $booster */
        $booster = phive('DBUserHandler/Booster');
        return $booster;
    }

    /**
     * @return void
     */
    private static function log(string $key, array $data)
    {
        phive('Logger')->log($key, $data);
    }
}
