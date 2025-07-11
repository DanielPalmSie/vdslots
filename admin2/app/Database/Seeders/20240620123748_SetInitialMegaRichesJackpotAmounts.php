<?php 
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;


class SetInitialMegaRichesJackpotAmounts extends Seeder
{
	protected string $tableJackpots;
	private Connection $connection;

	private array $jackpotsData;

	private $brand;

	public function init()
	{
		$this->connection = DB::getMasterConnection();
		$this->brand = phive('BrandedConfig')->getBrand();
		$this->tableJackpots = 'jackpots';
		$this->jackpotsData = [
			[
				'id' => 1,
				'name' => 'Mega Jackpots',
				'amount' => 500
			],
			[
				'id' => 2,
				'name' => 'Major Jackpots',
				'amount' =>  250
			],
			[
				'id' => 3,
				'name' => 'Mini Jackpots',
				'amount' => 100
			]
		];
	}

	/**
	 * Do the migration
	 */
	public function up()
	{
		/*
		|--------------------------------------------------------------------------
		| Update jackpots record
		|--------------------------------------------------------------------------
	   */
		
		$this->init();

		if ($this->brand !== 'megariches') {
			return;
		}

		foreach ($this->jackpotsData as $data) {
			$jackpots = $this->connection
				->table($this->tableJackpots)
                ->where('id', '=', $data['id'])
				->where('name', '=', $data['name'])
				->get();

			if (!empty($jackpots)) {
				$amount = phive('Currencer')->changeMoney('GBP', 'EUR', $data['amount']);
				foreach ($jackpots as $jackpot) {
					$this->connection
						->table($this->tableJackpots)
						->where('id', $jackpot->id)
                        ->update([
                            'amount' => number_format($amount, 12),
                            'amount_minimum' => number_format($amount, 12)
                        ]);
				}
			}
		}
	}
}