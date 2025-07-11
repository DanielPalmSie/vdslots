<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class UpdateTermsAndConditionsComplaintsLink extends Seeder
{
    private Connection $connection;
    private string $tablePageRoutes;
    private string $brand;

    public function init()
    {
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->connection = DB::getMasterConnection();
        $this->tablePageRoutes = 'page_routes';
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if ($this->brand !== 'megariches') {
            return;
        }

        $page = $this->connection
            ->table('pages')
            ->where('alias', '=', 'terms-and-conditions-complaints')
            ->where('filename', '=', 'diamondbet/generic.php')
            ->first();
        
        if (!$page) {
            return;
        }

        $page_id = (int)$page->page_id;

        $this->connection
            ->table($this->tablePageRoutes)
            ->insert([
                'page_id' => $page_id,
                'country' => 'SE',
                'route' => '/terms-and-conditions/sga-svenska-regler-och-villkor/#klagomal'
            ]);

        $mobilePage = $this->connection
            ->table('pages')
            ->where('alias', '=', 'terms-and-conditions-complaints')
            ->where('filename', '=', 'diamondbet/mobile.php')
            ->first();
        
        if ($mobilePage) {
            $mobilePageId = (int)$mobilePage->page_id;
            
            $this->connection
                ->table($this->tablePageRoutes)
                ->insert([
                    'page_id' => $mobilePageId,
                    'country' => 'SE',
                    'route' => '/mobile/terms-and-conditions/sga-svenska-regler-och-villkor/#klagomal'
                ]);
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->brand !== 'megariches') {
            return;
        }

        $page = $this->connection
            ->table('pages')
            ->where('alias', '=', 'terms-and-conditions-complaints')
            ->where('filename', '=', 'diamondbet/generic.php')
            ->first();
        
        if (!$page) {
            return;
        }

        $page_id = (int)$page->page_id;

        $this->connection
            ->table($this->tablePageRoutes)
            ->where('page_id', '=', $page_id)
            ->where('country', '=', 'SE')
            ->delete();
            
        $mobilePage = $this->connection
            ->table('pages')
            ->where('alias', '=', 'terms-and-conditions-complaints')
            ->where('filename', '=', 'diamondbet/mobile.php')
            ->first();
        
        if ($mobilePage) {
            $mobilePageId = (int)$mobilePage->page_id;
            
            $this->connection
                ->table($this->tablePageRoutes)
                ->where('page_id', '=', $mobilePageId)
                ->where('country', '=', 'SE')
                ->delete();
        }
    }
}