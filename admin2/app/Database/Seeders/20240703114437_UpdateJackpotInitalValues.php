<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class UpdateJackpotInitalValues extends Seeder
{

	private Connection $connection;
	private string $table;
	private string $brand;
	private int $multiplier;

    public function init()
    {
		$this->table = 'jackpots';
		$this->connection = DB::getMasterConnection();
		$this->brand = phive('BrandedConfig')->getBrand();
		$this->multiplier = 100;
    }

    public function up()
    {
		$this->init();
		
		if ($this->brand != 'megariches') {
			return;
		}
	
		$jackpots = $this->connection->table($this->table)->get();
		if (empty($jackpots)) {
			return;
		}
		
		foreach ($jackpots as $jackpot) {
			$this->connection
				->table($this->table)
				->where('id', $jackpot->id)
				->update([
					'amount' => $jackpot->amount * $this->multiplier,
					'amount_minimum' => $jackpot->amount_minimum * $this->multiplier
				]);
		}
    }
	
	public function down()
	{
		$this->init();
		
		if ($this->brand != 'megariches') {
			return;
		}

		$jackpots = $this->connection->table($this->table)->get();
		if (empty($jackpots)) {
			return;
		}

		foreach ($jackpots as $jackpot) {
			$this->connection
				->table($this->table)
				->where('id', $jackpot->id)
				->update([
					'amount' => $jackpot->amount / $this->multiplier,
					'amount_minimum' => $jackpot->amount_minimum / $this->multiplier
				]);
		}
	}
	
}