<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class AddHelpDomainPage extends Migration
{
    protected string $tablePages;
    private string $tablePageSetting;
    protected string $tableBoxes;
    protected string $tableBoxAttributes;
    protected string $tableLocalizedStrings;
    private string $brand;

    const MOBILE_PREFIX = "/mobile";

    private Connection $connection;
    /**
     * @var array[]
     */
    private array $pageData;
    /**
     * @var array[]
     */
    private array $pageSettingsData = [];

    /**
     * @var array[]
     */
    private array $localizedStringAlias = [];

    /**
     * @var array[]
     */
    private array $boxData;


    public function init()
    {

        $this->brand = phive('BrandedConfig')->getBrand();

        $this->tablePages = 'pages';
        $this->tablePageSetting = 'page_settings';
        $this->tableBoxes = 'boxes';
        $this->tableBoxAttributes = 'boxes_attributes';
        $this->tableLocalizedStrings = 'localized_strings';

        $this->connection = DB::getMasterConnection();

        $this->pageData = [
            [
                'parent_cached_path' => '/',
                'alias' => 'help',
                'cached_path' => '/help'
            ],
            [
                'parent_cached_path' => '/help',
                'alias' => 'privacy-policy',
                'cached_path' => '/help/privacy-policy'
            ],
            [
                'parent_cached_path' => '/help',
                'alias' => 'cookie-policy',
                'cached_path' => '/help/cookie-policy'
            ],
            [
                'parent_cached_path' => '/help',
                'alias' => 'terms-and-conditions',
                'cached_path' => '/help/terms-and-conditions'
            ],
            [
                'parent_cached_path' => '/help/terms-and-conditions',
                'alias' => 'sga-svenska-regler-och-villkor',
                'cached_path' => '/help/terms-and-conditions/sga-svenska-regler-och-villkor'
            ],
            [
                'parent_cached_path' => '/help/terms-and-conditions',
                'alias' => 'mga-games-specific',
                'cached_path' => '/help/terms-and-conditions/mga-games-specific'
            ],
        ];

        $bgImage = $this->brand === 'mrvegas' ? 'MV-BG.jpg' : 'VideoSlots-Background3.jpg';

        $this->pageSettingsData = [
            '/help' => [
                [
                    'page_id' => 0,
                    'name' => 'landing_bkg',
                    'value' => $bgImage,
                ]
            ],
            '/mobile/help' => [
                [
                    'page_id' => 0,
                    'name' => 'landing_bkg',
                    'value' => $bgImage,
                ]
            ],
            '/help/privacy-policy' => [
                [
                    'page_id' => 0,
                    'name' => 'landing_bkg',
                    'value' => $bgImage,
                ]
            ],
            '/mobile/help/privacy-policy' => [
                [
                    'page_id' => 0,
                    'name' => 'landing_bkg',
                    'value' => $bgImage,
                ]
            ],
            '/help/cookie-policy' => [
                [
                    'page_id' => 0,
                    'name' => 'landing_bkg',
                    'value' => $bgImage,
                ]
            ],
            '/mobile/help/cookie-policy' => [
                [
                    'page_id' => 0,
                    'name' => 'landing_bkg',
                    'value' => $bgImage,
                ]
            ],
            '/help/terms-and-conditions/sga-svenska-regler-och-villkor' => [
                [
                    'page_id' => 0,
                    'name' => 'landing_bkg',
                    'value' => $bgImage,
                ]
            ],
            '/help/terms-and-conditions/mga-games-specific' => [
                [
                    'page_id' => 0,
                    'name' => 'landing_bkg',
                    'value' => $bgImage,
                ]
            ],
        ];


        $this->localizedStringAlias = [
            '/help' =>  [
                    'language' => 'en',
                    'alias' => '',
                    'value' => '<div class="section-cards">
                                    <div class="section-card">
                                        <div class="section-card__logo term-and-conditions"></div>
                                        <div class="section-card__name">
                                            Terms & Conditions
                                        </div>
                                        <div class="section-card__link">
                                            <a href="/help/terms-and-conditions">Read More</a>
                                        </div>
                                    </div>

                                    <div class="section-card">
                                        <div class="section-card__logo privacy_policy"></div>
                                        <div class="section-card__name">
                                            Privacy Policy
                                        </div>
                                        <div class="section-card__link">
                                            <a href="/help/privacy-policy">Read More</a>
                                        </div>
                                    </div>

                                    <div class="section-card">
                                        <div class="section-card__logo cookies_policy"></div>
                                        <div class="section-card__name">
                                            Cookies Policy
                                        </div>
                                        <div class="section-card__link">
                                            <a href="/help/cookie-policy">Read More</a>
                                        </div>
                                    </div>

                                </div>',
                ],
            '/mobile/help' =>  [
                    'language' => 'en',
                    'alias' => '',
                    'value' => '<div class="section-cards">
            <div class="section-card">
                <div class="section-card__logo term-and-conditions"></div>
                <div class="section-card__name">
                    Terms & Conditions
                </div>
                <div class="section-card__link">
                    <a href="/mobile/help/terms-and-conditions">Read More</a>
                </div>
            </div>

            <div class="section-card">
                <div class="section-card__logo privacy_policy"></div>
                <div class="section-card__name">
                    Privacy Policy
                </div>
                <div class="section-card__link">
                    <a href="/mobile/help/privacy-policy">Read More</a>
                </div>
            </div>

            <div class="section-card">
                <div class="section-card__logo cookies_policy"></div>
                <div class="section-card__name">
                    Cookies Policy
                </div>
                <div class="section-card__link">
                    <a href="/mobile/help/cookie-policy">Read More</a>
                </div>
            </div>

        </div>',
                ],
            '/help/privacy-policy' => [
                    'language' => 'en',
                    'alias' => '',
                    'value' => null,
                ],
            '/mobile/help/privacy-policy' =>[
                    'language' => 'en',
                    'alias' => '',
                    'value' => null,
                ],
            '/help/cookie-policy' => [
                    'language' => 'en',
                    'alias' => '',
                    'value' => null,
                ],
            '/mobile/help/cookie-policy' => [
                    'language' => 'en',
                    'alias' => '',
                    'value' => null,
                ],
            '/help/terms-and-conditions' => [
                'language' => 'en',
                'alias' => '',
                'value' => null,
            ],
            '/help/terms-and-conditions/sga-svenska-regler-och-villkor' => [
                    'language' => 'en',
                    'alias' => '',
                    'value' => null,
                ],
            '/help/terms-and-conditions/mga-games-specific' => [
                    'language' => 'en',
                    'alias' => '',
                    'value' => null,
                ],
        ];

    }

    /**
     * Do the migration
     */
    public function up()
    {

        /*
        |--------------------------------------------------------------------------
        | Add Page record
        |--------------------------------------------------------------------------
        */

        if ( ! ($this->brand === 'mrvegas' || $this->brand === 'videoslots') ) {
            return;
        }

        foreach ($this->pageData as $pageObj) {

            $pageList = [];

            $pageList[] = [
                'parent_id' => $pageObj['parent_cached_path'] === "/" ? 0 : $this->getPageID($pageObj['parent_cached_path']),
                'alias' => $pageObj['alias'],
                'filename' => 'diamondbet/help.php',
                'cached_path' => $pageObj['cached_path'],
            ];

            $pageList[] = [
                'parent_id' => $pageObj['parent_cached_path'] === "/" ? $this->getMobilePageParentID() : $this->getPageID(self::MOBILE_PREFIX . $pageObj['parent_cached_path']),
                'alias' => $pageObj['alias'],
                'filename' => 'diamondbet/help.php',
                'cached_path' => self::MOBILE_PREFIX . $pageObj['cached_path'],
            ];


            foreach ($pageList as $data) {

                /*
                 |--------------------------------------------------------------------------
                 | Create or Update Pages
                 |--------------------------------------------------------------------------
                */

                $isPageExists = $this->connection
                    ->table($this->tablePages)
                    ->where('parent_id', '=', $data['parent_id'])
                    ->where('alias', '=', $data['alias'])
                    ->where('filename', '=', $data['filename'])
                    ->where('cached_path', '=', $data['cached_path'])
                    ->exists();

                if (!$isPageExists) {
                    $this->connection->table($this->tablePages)->insert($data);
                }


                /*
                |--------------------------------------------------------------------------
                | Update page_settings for Page record
                | first create page then update page_settings
                |--------------------------------------------------------------------------
                */
                $page = $this->connection->table($this->tablePages)
                    ->where('parent_id', '=', $data['parent_id'])
                    ->where('alias', '=', $data['alias'])
                    ->where('cached_path', '=', $data['cached_path'])
                    ->first();

                if (!empty($page)) {
                    $pageSettings = $this->pageSettingsData[$data['cached_path']];
                    foreach ($pageSettings as $setting) {
                        $setting['page_id'] = $page->page_id;

                        $this->connection->table($this->tablePageSetting)
                            ->insert($setting);
                    }
                }


                /*
               |--------------------------------------------------------------------------
               | Update Boxes for PageId
               |--------------------------------------------------------------------------
               */

                $this->boxData = [
                    [
                        'container' => 'full',
                        'box_class' => 'SimpleExpandableBox',
                        'page_id' => $page->page_id,
                    ],
                ];

                foreach ($this->boxData as $box) {
                    $isBoxExists = $this->connection
                        ->table($this->tableBoxes)
                        ->where('container', '=', $box['container'])
                        ->where('box_class', '=', $box['box_class'])
                        ->where('page_id', '=', $box['page_id'])
                        ->exists();

                    if (!$isBoxExists) {
                        $this->connection->table($this->tableBoxes)->insert(array_merge($box, ['priority' => 0]));
                    }


                    $box_row = $this->connection
                        ->table($this->tableBoxes)
                        ->where('container', '=', $box['container'])
                        ->where('box_class', '=', $box['box_class'])
                        ->where('page_id', '=', $box['page_id'])
                        ->first();

                    if (!empty($box_row)) {
                        $localizedStringObj = $this->localizedStringAlias[$data['cached_path']];

                        if(empty($localizedStringObj['alias'])) {
                            $localizedStringObj['alias'] = 'simple.' . $box_row->box_id . '.html';
                        }

                        if(empty($localizedStringObj['value'])) {

                            $cachedPath = explode("help", $data['cached_path'])[1];

                            $existingPageBoxId = $this->getBoxID("SimpleExpandableBox", $cachedPath);

                            /* Update box_attributes settings */
                            $boxAttributesForProdPage = [];

                            $boxAttributesForHelpPage = [
                                [
                                    'box_id' => $box_row->box_id,
                                    'attribute_name' => 'string_name',
                                    'attribute_value' => 'simple.'. $existingPageBoxId .'.html'
                                ]
                            ];

                            if($data['cached_path'] === '/help/terms-and-conditions') {
                               $boxAttributesForProdPage[] =  [
                                   'box_id'   => $existingPageBoxId,
                                   'attribute_name' => 'replacers',
                                   'attribute_value' => 'help:',
                               ];

                               $boxAttributesForHelpPage[] =  [
                                   'box_id'   => $box_row->box_id,
                                   'attribute_name' => 'replacers',
                                   'attribute_value' => 'help:/help',
                               ];
                            }

                            $this->updateBoxAttribute($boxAttributesForProdPage);
                            $this->updateBoxAttribute($boxAttributesForHelpPage);


                        } else {
                            $this->connection->table($this->tableLocalizedStrings)
                                ->insert($localizedStringObj);
                        }

                    }

                }
            }
        }


        /*
         |--------------------------------------------------------------------------
         | Update Help page info from start_go table
         |--------------------------------------------------------------------------
         */

        foreach ($this->localizedStringAlias as $path => $value) {
            if(str_starts_with($path, "/mobile")) {
                continue;
            }
            $from = $path.'/';
            $to = '/mobile'.$path.'/';

            $helpPageExists = $this->checkStartGo($from, $to);
            if (empty($helpPageExists)) {
                $this->connection
                    ->table('start_go')
                    ->insert([
                        'from' => $from,
                        'to' => $to
                    ]);
            }
        }
    }

    /**
     * Undo the migration
     */

    public function down()
    {

        if ( ! ($this->brand === 'mrvegas' || $this->brand === 'videoslots') ) {
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Delete Page records
        |--------------------------------------------------------------------------
        */

       $pageList = $this->createPageObject();

       foreach ($pageList as $data) {

                /*
               |--------------------------------------------------------------------------
               | Delete page_settings for Page record
               | first delete page_settings & then delete pages
               |--------------------------------------------------------------------------
               */
                $page = $this->connection->table($this->tablePages)
                    ->where('alias', '=', $data['alias'])
                    ->where('parent_id', '=', $data['parent_id'])
                    ->where('cached_path', '=', $data['cached_path'])
                    ->first();

                if (!empty($page)) {
                    $pageSettings = $this->pageSettingsData[$data['cached_path']];
                    foreach ($pageSettings as $setting) {
                        $this->connection->table($this->tablePageSetting)
                            ->where('name', '=', $setting['name'])
                            ->where('page_id', '=', $page->page_id)
                            ->delete();
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Delete Boxes for PageId
                |--------------------------------------------------------------------------
                */

                $this->boxData = [
                    [
                        'container' => 'full',
                        'box_class' => 'SimpleExpandableBox',
                        'page_id' => $page->page_id,
                    ],
                ];

                foreach ($this->boxData as $box) {

                    $box_row = $this->connection
                        ->table($this->tableBoxes)
                        ->where('container', '=', $box['container'])
                        ->where('box_class', '=', $box['box_class'])
                        ->where('page_id', '=', $box['page_id'])
                        ->first();

                    if(!empty($box_row)) {
                        $this->connection
                            ->table($this->tableLocalizedStrings)
                            ->where('alias', '=', 'simple.'.$box_row->box_id.'.html')
                            ->delete();
                    }

                    $cachedPath = explode("help", $data['cached_path'])[1];

                    $existingPageBoxId = $this->getBoxID("SimpleExpandableBox", $cachedPath);

                    foreach ([$box_row->box_id, $existingPageBoxId] as $pagBoxId) {
                        $this->connection
                            ->table($this->tableBoxAttributes)
                            ->where('box_id', $pagBoxId)
                            ->where('attribute_name', 'string_name')
                            ->delete();

                        $this->connection
                            ->table($this->tableBoxAttributes)
                            ->where('box_id', $pagBoxId)
                            ->where('attribute_name', 'replacers')
                            ->delete();
                    }

                    $this->connection
                        ->table($this->tableBoxes)
                        ->where('container', '=', $box['container'])
                        ->where('box_class', '=', $box['box_class'])
                        ->where('page_id', '=', $box['page_id'])
                        ->delete();
                }

               /*
               |--------------------------------------------------------------------------
               | Delete Pages for pages table
               |--------------------------------------------------------------------------
               */

                 $this->connection
                       ->table($this->tablePages)
                       ->where('parent_id', '=', $data['parent_id'])
                       ->where('alias', '=', $data['alias'])
                       ->where('filename', '=', $data['filename'])
                       ->where('cached_path', '=', $data['cached_path'])
                       ->delete();
       }


        /*
         |--------------------------------------------------------------------------
         | Delete Help page info from start_go table
         |--------------------------------------------------------------------------
         */

        foreach ($this->localizedStringAlias as $path => $value) {

            if(str_starts_with($path, "/mobile")) {
                continue;
            }
            $from = $path.'/';
            $to = '/mobile'.$path.'/';

            $helpPageExists = $this->checkStartGo($from, $to);
            if (!empty($helpPageExists)) {
                $this->connection
                    ->table('start_go')
                    ->where('from', '=', $from)
                    ->where('to', '=', $to)
                    ->delete();
            }
        }

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

    private function getPageID(string $cache_path)
    {
        $page = $this->connection
            ->table($this->tablePages)
            ->where('cached_path', '=', $cache_path)
            ->first();

        return (int)$page->page_id;
    }

    private function getBoxID($box_class, $cached_path): int
    {
        $box = $this->connection
            ->table($this->tablePages)
            ->join($this->tableBoxes, 'pages.page_id', '=', 'boxes.page_id')
            ->where('boxes.box_class', '=', $box_class)
            ->where('cached_path', '=', $cached_path)
            ->first();

        return (int)$box->box_id;
    }

    private function checkStartGo($from, $to) {
        return $this->connection
            ->table('start_go')
            ->where('from', '=', $from)
            ->where('to', '=', $to)
            ->exists();
    }


    private function createPageObject(): array
    {

        $pageList = [];

        foreach ($this->pageData as $pageObj) {
            $pageList[] = [
                'parent_id' => $pageObj['parent_cached_path'] === "/" ? 0 : $this->getPageID($pageObj['parent_cached_path']),
                'alias' => $pageObj['alias'],
                'filename' => 'diamondbet/help.php',
                'cached_path' => $pageObj['cached_path'],
            ];

            $pageList[] =   [
                'parent_id' => $pageObj['parent_cached_path'] === "/" ? $this->getMobilePageParentID() : $this->getPageID(self::MOBILE_PREFIX . $pageObj['parent_cached_path']),
                'alias' => $pageObj['alias'],
                'filename' => 'diamondbet/help.php',
                'cached_path' => self::MOBILE_PREFIX . $pageObj['cached_path'],
            ];
        }

        return $pageList;
    }

    private function updateBoxAttribute($data) {
        foreach ($data as $boxAttribute) {
            $exists_string_name = $this->connection
                ->table($this->tableBoxAttributes)
                ->where('box_id', '=', $boxAttribute['box_id'])
                ->where('attribute_name', '=', $boxAttribute['attribute_name'])
                ->first();
            if (!empty($exists_string_name)) {
                $this->connection
                    ->table($this->tableBoxAttributes)
                    ->where('box_id', '=', $boxAttribute['box_id'])
                    ->where('attribute_name', '=', $boxAttribute['attribute_name'])
                    ->update(['attribute_value' => $boxAttribute['attribute_value']]);

            } else {
                $this->connection->table($this->tableBoxAttributes)->insert($boxAttribute);
            }
        }
    }
}

