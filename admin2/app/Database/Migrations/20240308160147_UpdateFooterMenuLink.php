<?php
use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class UpdateFooterMenuLink extends Migration
{
    private Connection $connection;
    private string $tableMenus;
    private string $tablePages;
    private string $tablePageRoutes;

    private array $pageDataList;
    private array $menuData;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->tableMenus = 'menus';
        $brand = phive('BrandedConfig')->getBrand();

        $this->tablePages ='pages';
        $this->tablePageRoutes = 'page_routes';

        $this->pageDataList = [
           [
               'parent_id' => 0,
               'alias' => 'terms-and-conditions-complaints',
               'filename' => 'diamondbet/generic.php',
               'cached_path' => '',
           ],
           [
               'parent_id' =>$this->getMobilePageParentID(),
               'alias' => 'terms-and-conditions-complaints',
               'filename' => 'diamondbet/mobile.php',
               'cached_path' => '/mobile',
           ],
        ];

        if ($brand === 'kungaslottet') {
            $this->menuData =  [
                [
                    'country' => 'MT',
                    'route' => '/terms-and-conditions/mga-games-specific/#complaints',
                    'page_id' => 0
                ],
                [
                    'country' => 'SE',
                    'route' => '/terms-and-conditions/sga-svenska-regler-och-villkor/#9',
                    'page_id' => 0
                ]
            ];
        }

        if ($brand === 'mrvegas') {
            $this->menuData = [
                [
                    'country' => 'MT',
                    'route' => '/terms-and-conditions/mga-games-specific/#complaints',
                    'page_id' => 0
                ],
                [
                    'country' => 'ES',
                    'route' => '/terms-and-conditions/mga-games-specific/#complaints',
                    'page_id' => 0
                ],
                [
                    'country' => 'PE',
                    'route' => '/terms-and-conditions/mga-games-specific/#complaints',
                    'page_id' => 0
                ],
                [
                    'country' => 'CL',
                    'route' => '/terms-and-conditions/mga-games-specific/#complaints',
                    'page_id' => 0
                ],
                [
                    'country' => 'BR',
                    'route' => '/terms-and-conditions/mga-games-specific/#complaints',
                    'page_id' => 0
                ],
                [
                    'country' => 'FI',
                    'route' => '/terms-and-conditions/mga-games-specific/#complaints',
                    'page_id' => 0
                ],
                [
                    'country' => 'NO',
                    'route' => '/terms-and-conditions/mga-games-specific/#complaints',
                    'page_id' => 0
                ],
                [
                    'country' => 'DK',
                    'route' => '/terms-and-conditions/dga-dansk-vilkar-og-betingelser/#klager',
                    'page_id' => 0
                ],
                [
                    'country' => 'CA',
                    'route' => '/terms-and-conditions/agco-ontario-terms-and-condition/#disputes',
                    'page_id' => 0
                ],
                [
                    'country' => 'SE',
                    'route' => '/terms-and-conditions/sga-svenska-regler-och-villkor/#klagomal',
                    'page_id' => 0
                ],
            ];
        }

        if ($brand === 'videoslots') {
            $this->menuData =  [
                [
                    'country' => 'MT',
                    'route' => '/terms-and-conditions/mga-games-specific/#complaints',
                    'page_id' => 0
                ],
                [
                    'country' => 'ES',
                    'route' => '/terms-and-conditions/terminos-y-condiciones-de-espana/#reclamaciones',
                    'page_id' => 0
                ],
                [
                    'country' => 'PE',
                    'route' => '/terms-and-conditions/terminos-y-condiciones-de-espana/#reclamaciones',
                    'page_id' => 0
                ],
                [
                    'country' => 'CL',
                    'route' => '/terms-and-conditions/terminos-y-condiciones-de-espana/#reclamaciones',
                    'page_id' => 0
                ],
                [
                    'country' => 'BR',
                    'route' => '/terms-and-conditions/mga-games-specific/#complaints',
                    'page_id' => 0
                ],
                [
                    'country' => 'FI',
                    'route' => '/terms-and-conditions/mga-games-specific/#complaints',
                    'page_id' => 0
                ],
                [
                    'country' => 'NO',
                    'route' => '/terms-and-conditions/mga-games-specific/#complaints',
                    'page_id' => 0
                ],
                [
                    'country' => 'DE',
                    'route' => '/terms-and-conditions/de-games-specific/#beschwerden',
                    'page_id' => 0
                ],
                [
                    'country' => 'DK',
                    'route' => '/terms-and-conditions/dga-dansk-vilkar-og-betingelser/#klager',
                    'page_id' => 0
                ],
                [
                    'country' => 'CA',
                    'route' => '/terms-and-conditions/agco-ontario-terms-and-condition/#disputes',
                    'page_id' => 0
                ],
                [
                    'country' => 'IT',
                    'route' => '/terms-and-conditions/ADM-termini-e-condizioni/#reclami',
                    'page_id' => 0
                ],
                [
                    'country' => 'SE',
                    'route' => '/terms-and-conditions/sga-svenska-regler-och-villkor/#klagomal',
                    'page_id' => 0
                ],
                [
                    'country' => 'GB',
                    'route' => '/terms-and-conditions/ukgc-terms-and-conditions/#complaints',
                    'page_id' => 0
                ],
            ];
        }
    }

    /**
     * Do the migration
     */
    public function up()
    {
        // update link for complaints-footer menu
        $this->connection
            ->table($this->tableMenus)
            ->where('alias', '=', 'complaints-footer')
            ->update([
                'link' => '/terms-and-conditions/#complaints'
            ]);

        $menu = $this->getMenu('complaints-footer');

        /*
       |--------------------------------------------------------------------------
       | Add Page record
       |--------------------------------------------------------------------------
       */

        foreach ($this->pageDataList as $pageData) {
            $prefix = $pageData['cached_path'];
            $pageData['cached_path'] = $pageData['cached_path'] . $menu->link;
            $isPageExists = $this->connection
                ->table($this->tablePages)
                ->where('parent_id', '=', $pageData['parent_id'])
                ->where('alias', '=', $pageData['alias'])
                ->where('filename', '=', $pageData['filename'])
                ->where('cached_path', '=', $pageData['cached_path'])
                ->exists();

            if (!$isPageExists) {
                $this->connection->table($this->tablePages)->insert($pageData);
            }

            /*
           |--------------------------------------------------------------------------
           | Add Page Routes record
           |--------------------------------------------------------------------------
           */

            $page_id = $this->getPageID($pageData['cached_path']);

            foreach ($this->menuData as $menuItem) {

                $menuItem['page_id'] = $page_id;
                $menuItem['route'] = $prefix . $menuItem['route'];
                $isPageExists = $this->connection
                    ->table($this->tablePageRoutes)
                    ->where('page_id', '=', $menuItem['page_id'])
                    ->where('route', '=', $menuItem['route'])
                    ->where('country', '=', $menuItem['country'])
                    ->exists();

                if (!$isPageExists) {
                    $this->connection->table($this->tablePageRoutes)->insert($menuItem);
                }
            }

            // update existing `terms-and-conditions` page alias & remove `/#complaints` from cached_path.
            $this->connection
                ->table($this->tablePages)
                ->where('parent_id', '=', $pageData['parent_id'])
                ->where('alias', '=', 'terms-and-conditions')
                ->where('filename', '=', $pageData['filename'])
                ->where('cached_path', '=', $pageData['cached_path'])
                ->update([
                    'cached_path' => $prefix . '/terms-and-conditions'
                ]);
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $menu = $this->getMenu('complaints-footer');

        foreach ($this->pageDataList as $pageData) {
            $prefix = $pageData['cached_path'];
            $pageData['cached_path'] = $pageData['cached_path'].$menu->link;

            $isPageExists = $this->connection
                ->table($this->tablePages)
                ->where('parent_id', '=', $pageData['parent_id'])
                ->where('alias', '=', $pageData['alias'])
                ->where('filename', '=', $pageData['filename'])
                ->where('cached_path', '=', $pageData['cached_path'])
                ->exists();

            if ($isPageExists) {
                /*
                |--------------------------------------------------------------------------
                | Delete Page Routes record
                |--------------------------------------------------------------------------
                */
                $page_id = $this->getPageID($pageData['cached_path']);
                foreach ($this->menuData as $menuItem) {
                    $menuItem['page_id'] = $page_id;
                    $menuItem['route'] = $prefix . $menuItem['route'];

                    $isPageRouteExists = $this->connection
                        ->table($this->tablePageRoutes)
                        ->where('page_id', '=', $menuItem['page_id'])
                        ->where('route', '=', $menuItem['route'])
                        ->where('country', '=', $menuItem['country'])
                        ->exists();

                    if ($isPageRouteExists) {
                        $this->connection->table($this->tablePageRoutes)
                            ->where('page_id', '=', $menuItem['page_id'])
                            ->where('route', '=', $menuItem['route'])
                            ->where('country', '=', $menuItem['country'])
                            ->delete();
                    }
                }

                /*
                 |--------------------------------------------------------------------------
                 | Delete Page record
                 |--------------------------------------------------------------------------
                 */

                $this->connection
                    ->table($this->tablePages)
                    ->where('parent_id', '=', $pageData['parent_id'])
                    ->where('alias', '=', $pageData['alias'])
                    ->where('filename', '=', $pageData['filename'])
                    ->where('cached_path', '=', $pageData['cached_path'])
                    ->delete();
            }
        }
    }

    private function getMenu(string $alias)
    {
        return $this->connection
            ->table($this->tableMenus)
            ->where('alias', '=', $alias)
            ->first();
    }

    private function getPageID(string $cache_path)
    {
        $page = $this->connection
            ->table($this->tablePages)
            ->where('cached_path', '=', $cache_path)
            ->first();

        return (int)$page->page_id;
    }

    private function getMobilePageParentID(): int
    {
        $page = $this->connection
            ->table($this->tablePages)
            ->where('alias', '=', 'mobile')
            ->where('filename', '=', 'diamondbet/mobile.php')
            ->where('cached_path', '=', '/mobile')
            ->first();

        return (int)$page->page_id;
    }
}
