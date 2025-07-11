<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;

class AddWebsiteBackendItalyComponentSeeder extends Seeder
{
	private string $table;
	private Connection $connection;
	private array $components;

	public function init()
	{
		$this->connection = DB::getMasterConnection();
		$this->table = 'smt_components';
		$this->components = [
			'phive' => [
				'alias' => 'Website backend module',
				'name' => 'Website backend module (license-italy)',
			], 
			'diamondbet' => [
				'alias' => 'Website Version 1 (diamondbet)',
				'name' => 'Website Version 1 (diamondbet) (license-italy)',
			]
		];
	}

    public function up()
    {
		$this->createComponent($this->components['phive']);
		$this->createComponent($this->components['diamondbet']);
	}

	public function down()
	{
		$this->removeComponent($this->components['phive']);
		$this->removeComponent($this->components['diamondbet']);
	}

	/**
	 * Create a new component based of the old one
	 * 
	 * @param array $component
	 * @return void
	 */
	private function createComponent(array $component)
	{
		$oldComponent = $this->connection
			->table($this->table)
			->where('name', $component['alias'])
			->where('version', 1)
			->orderBy('id', 'asc')
			->take(1)
			->get()
			->toArray();

		$newComponent = get_object_vars($oldComponent[0]);
		$newComponent['name'] = $component['name'];
		$newComponent['subcategory_id'] = 0;
		$newComponent['id'] = $this->connection
				->table($this->table)
				->max('id') + 1;

		$this->connection
			->table($this->table)
			->insert($newComponent);
	}

	/**
	 * Remove a component
	 *
	 * @param array $component
	 * @return void
	 */
	private function removeComponent(array $component)
	{
		$this->connection
			->table($this->table)
			->where('name', $component['name'])
			->where('version', 1)
			->delete();
	}
}


