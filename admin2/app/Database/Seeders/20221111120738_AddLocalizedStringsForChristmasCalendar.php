<?php

use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\SeederTranslation;
use App\Extensions\Database\Connection\Connection;

class AddLocalizedStringsForChristmasCalendar extends SeederTranslation
{
    /**@varstring */
    protected $boxesAttributes;

    private string $table = 'localized_strings';
    private Connection $connection;

    protected array $data;

    public function init()
    {
        parent::init();
        $this->boxesAttributes = 'boxes_attributes';
        $this->connection = DB::getMasterConnection();

        $this->brand = phive('BrandedConfig')->getBrand();

        $this->data['en']['christmas.calendar.2022.html'] = file_get_contents(__DIR__ . '/../data/christmas.calendar.vs.txt');

        if ($this->brand == 'mrvegas') {
            $this->data['en']['christmas.calendar.2022.html'] = file_get_contents(__DIR__ . '/../data/christmas.calendar.mv.txt');
        }
    }

    public function up()
    {
        foreach ($this->data as $language => $translation) {
            foreach ($translation as $alias => $value) {
                $exists = $this->connection
                    ->table($this->table)
                    ->where('alias', $alias)
                    ->where('language', $language)
                    ->first();

                if (!empty($exists)) {
                    $this->connection
                        ->table($this->table)
                        ->where('alias', $alias)
                        ->where('language', $language)
                        ->update(['value' => $value]);
                } else {
                    $this->connection
                        ->table($this->table)
                        ->insert([
                            [
                                'alias' => $alias,
                                'language' => $language,
                                'value' => $value,
                            ]
                        ]);
                }

            }
        }


        if ($this->brand == 'videoslots') {
            // for 1614 - desktop
            $exists_box_class = $this->connection
                ->table($this->boxesAttributes)
                ->where('box_id', '1614')
                ->where('attribute_name', 'box_class')
                ->first();
            if (!empty($exists_box_class)) {
                $this->connection
                    ->table($this->boxesAttributes)
                    ->where('box_id', '1614')
                    ->where('attribute_name', 'box_class')
                    ->update(['attribute_value' => 'frame-block fb-background']);
            } else {
                $this->connection->table($this->boxesAttributes)->insert([
                    'box_id' => '1614',
                    'attribute_name' => 'box_class',
                    'attribute_value' => 'frame-block fb-background',
                ]);
            }


            // 1614 - mobile check_perm
            $exists_check_perm = $this->connection
                ->table($this->boxesAttributes)
                ->where('box_id', '1614')
                ->where('attribute_name', 'check_perm')
                ->first();
            if (!empty($exists_check_perm)) {
                $this->connection
                    ->table($this->boxesAttributes)
                    ->where('box_id', '1614')
                    ->where('attribute_name', 'check_perm')
                    ->update(['attribute_value' => 0]);
            } else {
                $this->connection->table($this->boxesAttributes)->insert([
                    'box_id' => '1614',
                    'attribute_name' => 'check_perm',
                    'attribute_value' => 0,
                ]);
            }

            // 1614 string name
            $exists_string_name = $this->connection
                ->table($this->boxesAttributes)
                ->where('box_id', '1614')
                ->where('attribute_name', 'string_name')
                ->first();
            if (!empty($exists_string_name)) {
                $this->connection
                    ->table($this->boxesAttributes)
                    ->where('box_id', '1614')
                    ->where('attribute_name', 'string_name')
                    ->update(['attribute_value' => 'christmas.calendar.2022.html']);
            } else {
                $this->connection->table($this->boxesAttributes)->insert([
                    'box_id' => '1614',
                    'attribute_name' => 'string_name',
                    'attribute_value' => 'christmas.calendar.2022.html',
                ]);
            }

            // 1614 sub box
            $exists_sub_box = $this->connection
                ->table($this->boxesAttributes)
                ->where('box_id', '1614')
                ->where('attribute_name', 'sub_box')
                ->first();
            if (!empty($exists_sub_box)) {
                $this->connection
                    ->table($this->boxesAttributes)
                    ->where('box_id', '1614')
                    ->where('attribute_name', 'sub_box')
                    ->update(['attribute_value' => 0]);
            } else {
                $this->connection->table($this->boxesAttributes)->insert([
                    'box_id' => '1614',
                    'attribute_name' => 'sub_box',
                    'attribute_value' => 0,
                ]);
            }


            // 1616 - mobile box_clas
            $exists_box_class = $this->connection
                ->table($this->boxesAttributes)
                ->where('box_id', '1616')
                ->where('attribute_name', 'box_class')
                ->first();
            if (!empty($exists_box_class)) {
                $this->connection
                    ->table($this->boxesAttributes)
                    ->where('box_id', '1616')
                    ->where('attribute_name', 'box_class')
                    ->update(['attribute_value' => 'frame-block fb-background']);
            } else {
                $this->connection->table($this->boxesAttributes)->insert([
                    'box_id' => '1616',
                    'attribute_name' => 'box_class',
                    'attribute_value' => 'frame-block fb-background',
                ]);
            }

            // 1616 - mobile check_perm
            $exists_check_perm = $this->connection
                ->table($this->boxesAttributes)
                ->where('box_id', '1616')
                ->where('attribute_name', 'check_perm')
                ->first();
            if (!empty($exists_check_perm)) {
                $this->connection
                    ->table($this->boxesAttributes)
                    ->where('box_id', '1616')
                    ->where('attribute_name', 'check_perm')
                    ->update(['attribute_value' => 0]);
            } else {
                $this->connection->table($this->boxesAttributes)->insert([
                    'box_id' => '1616',
                    'attribute_name' => 'check_perm',
                    'attribute_value' => 0,
                ]);
            }

            // 1616 string name
            $exists_string_name = $this->connection
                ->table($this->boxesAttributes)
                ->where('box_id', '1616')
                ->where('attribute_name', 'string_name')
                ->first();
            if (!empty($exists_string_name)) {
                $this->connection
                    ->table($this->boxesAttributes)
                    ->where('box_id', '1616')
                    ->where('attribute_name', 'string_name')
                    ->update(['attribute_value' => 'christmas.calendar.2022.html']);
            } else {
                $this->connection->table($this->boxesAttributes)->insert([
                    'box_id' => '1616',
                    'attribute_name' => 'string_name',
                    'attribute_value' => 'christmas.calendar.2022.html',
                ]);
            }

            // 1616 sub box
            $exists_sub_box = $this->connection
                ->table($this->boxesAttributes)
                ->where('box_id', '1616')
                ->where('attribute_name', 'sub_box')
                ->first();
            if (!empty($exists_sub_box)) {
                $this->connection
                    ->table($this->boxesAttributes)
                    ->where('box_id', '1616')
                    ->where('attribute_name', 'sub_box')
                    ->update(['attribute_value' => 0]);
            } else {
                $this->connection->table($this->boxesAttributes)->insert([
                    'box_id' => '1616',
                    'attribute_name' => 'sub_box',
                    'attribute_value' => 0,
                ]);
            }


        }



        if($this->brand == 'mrvegas'){
                // for 1510 - desktop
                $exists_box_class = $this->connection
                    ->table($this->boxesAttributes)
                    ->where('box_id', '1510')
                    ->where('attribute_name', 'box_class')
                    ->first();
                if (!empty($exists_box_class)) {
                    $this->connection
                        ->table($this->boxesAttributes)
                        ->where('box_id', '1510')
                        ->where('attribute_name', 'box_class')
                        ->update(['attribute_value' => 'frame-block fb-background']);
                } else {
                    $this->connection->table($this->boxesAttributes)->insert([
                        'box_id' => '1510',
                        'attribute_name' => 'box_class',
                        'attribute_value' => 'frame-block fb-background',
                    ]);
                }


                // 1510 - mobile check_perm
                $exists_check_perm = $this->connection
                    ->table($this->boxesAttributes)
                    ->where('box_id', '1510')
                    ->where('attribute_name', 'check_perm')
                    ->first();
                if (!empty($exists_check_perm)) {
                    $this->connection
                        ->table($this->boxesAttributes)
                        ->where('box_id', '1510')
                        ->where('attribute_name', 'check_perm')
                        ->update(['attribute_value' => 0]);
                } else {
                    $this->connection->table($this->boxesAttributes)->insert([
                        'box_id' => '1510',
                        'attribute_name' => 'check_perm',
                        'attribute_value' => 0,
                    ]);
                }

                // 1510 string name
                $exists_string_name = $this->connection
                    ->table($this->boxesAttributes)
                    ->where('box_id', '1510')
                    ->where('attribute_name', 'string_name')
                    ->first();
                if (!empty($exists_string_name)) {
                    $this->connection
                        ->table($this->boxesAttributes)
                        ->where('box_id', '1510')
                        ->where('attribute_name', 'string_name')
                        ->update(['attribute_value' => 'christmas.calendar.2022.html']);
                } else {
                    $this->connection->table($this->boxesAttributes)->insert([
                        'box_id' => '1510',
                        'attribute_name' => 'string_name',
                        'attribute_value' => 'christmas.calendar.2022.html',
                    ]);
                }

                // 1510 sub box
                $exists_sub_box = $this->connection
                    ->table($this->boxesAttributes)
                    ->where('box_id', '1510')
                    ->where('attribute_name', 'sub_box')
                    ->first();
                if (!empty($exists_sub_box)) {
                    $this->connection
                        ->table($this->boxesAttributes)
                        ->where('box_id', '1510')
                        ->where('attribute_name', 'sub_box')
                        ->update(['attribute_value' => 0]);
                } else {
                    $this->connection->table($this->boxesAttributes)->insert([
                        'box_id' => '1510',
                        'attribute_name' => 'sub_box',
                        'attribute_value' => 0,
                    ]);
                }

                // mrvegas mobile
                // for 1512 - desktop
                $exists_box_class = $this->connection
                    ->table($this->boxesAttributes)
                    ->where('box_id', '1512')
                    ->where('attribute_name', 'box_class')
                    ->first();
                if (!empty($exists_box_class)) {
                    $this->connection
                        ->table($this->boxesAttributes)
                        ->where('box_id', '1512')
                        ->where('attribute_name', 'box_class')
                        ->update(['attribute_value' => 'frame-block fb-background']);
                } else {
                    $this->connection->table($this->boxesAttributes)->insert([
                        'box_id' => '1512',
                        'attribute_name' => 'box_class',
                        'attribute_value' => 'frame-block fb-background',
                    ]);
                }


                // 1512 - mobile check_perm
                $exists_check_perm = $this->connection
                    ->table($this->boxesAttributes)
                    ->where('box_id', '1512')
                    ->where('attribute_name', 'check_perm')
                    ->first();
                if (!empty($exists_check_perm)) {
                    $this->connection
                        ->table($this->boxesAttributes)
                        ->where('box_id', '1512')
                        ->where('attribute_name', 'check_perm')
                        ->update(['attribute_value' => 0]);
                } else {
                    $this->connection->table($this->boxesAttributes)->insert([
                        'box_id' => '1512',
                        'attribute_name' => 'check_perm',
                        'attribute_value' => 0,
                    ]);
                }

                // 1512 string name
                $exists_string_name = $this->connection
                    ->table($this->boxesAttributes)
                    ->where('box_id', '1512')
                    ->where('attribute_name', 'string_name')
                    ->first();
                if (!empty($exists_string_name)) {
                    $this->connection
                        ->table($this->boxesAttributes)
                        ->where('box_id', '1512')
                        ->where('attribute_name', 'string_name')
                        ->update(['attribute_value' => 'christmas.calendar.2022.html']);
                } else {
                    $this->connection->table($this->boxesAttributes)->insert([
                        'box_id' => '1512',
                        'attribute_name' => 'string_name',
                        'attribute_value' => 'christmas.calendar.2022.html',
                    ]);
                }

                // 1512 sub box
                $exists_sub_box = $this->connection
                    ->table($this->boxesAttributes)
                    ->where('box_id', '1512')
                    ->where('attribute_name', 'sub_box')
                    ->first();
                if (!empty($exists_sub_box)) {
                    $this->connection
                        ->table($this->boxesAttributes)
                        ->where('box_id', '1512')
                        ->where('attribute_name', 'sub_box')
                        ->update(['attribute_value' => 0]);
                } else {
                    $this->connection->table($this->boxesAttributes)->insert([
                        'box_id' => '1512',
                        'attribute_name' => 'sub_box',
                        'attribute_value' => 0,
                    ]);
                }


            }


    }
        public function down()
        {
            foreach ($this->data as $language => $translation) {
                foreach ($translation as $alias => $value) {
                    $this->connection
                        ->table($this->table)
                        ->where('alias', $alias)
                        ->where('language', $language)
                        ->delete();
                }
            }

            $this->connection
                ->table($this->boxesAttributes)
                ->where('box_id', '1614')
                ->delete();

            $this->connection
                ->table($this->boxesAttributes)
                ->where('box_id', '1616')
                ->delete();

        }
    }