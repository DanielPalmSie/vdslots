<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;

class RemoveDublicateEmailEnteriesOfWbaPromoton extends Seeder
{
    protected string $tableMailsPromoContact;
    private Connection $connection;
    private $brand;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->tableMailsPromoContact = 'mails_promo_contact';
    }

    public function up()
    {
        $this->init();

        if ($this->brand !== 'mrvegas') {
            return;
        }
        try {
            // Count and log the number of duplicate rows before deletion
            $duplicateCount = $this->connection->table($this->tableMailsPromoContact)
                ->selectRaw('COUNT(*) - COUNT(DISTINCT mail) as duplicate_count')
                ->value('duplicate_count');

            if ($duplicateCount > 0) {
                echo "Number of duplicate rows before deletion: {$duplicateCount}\n";

                // Delete duplicate rows based on mail column, keeping the one with the lowest ID
                $deletedRows = $this->connection->table($this->tableMailsPromoContact . ' as t1')
                    ->join($this->tableMailsPromoContact . ' as t2', 't1.mail', '=', 't2.mail')
                    ->whereRaw('t1.id > t2.id')
                    ->delete();

                echo "Number of duplicate rows deleted: {$deletedRows}\n";
            } else {
                echo "No duplicate rows found. No action taken.\n";
            }
        } catch (\Exception $e) {
            // Log any errors that occur during the deletion process
            echo "Error occurred while deleting duplicate rows: {$e->getMessage()}";
        }
    }
}