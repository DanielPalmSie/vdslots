<?php

use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class TranslateCompetitorsForBetslip extends Migration
{
    /** @var string */
    protected $table;

    protected $connection;

    public function init()
    {
        $this->table = 'localized_strings';
        $this->pattern = "/sb.market_outcome.[0-9]+.name/";
        $this->searchForValue = [
            '1' => "1",
            '2' => "2"
        ];
        $this->setWithValue = [
            '1' => "{\$competitor1}",
            '2' => "{\$competitor2}"
        ];
        $this->connection = DB::getMasterConnection();
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $rows = $this->connection
            ->table($this->table)
            ->select('*')
            ->where('alias', 'like', 'sb.market_outcome.%.name')
            ->where('alias', 'not like', 'sb.market_outcome.sr:%.name')
            ->where('alias', 'not like', 'sb.market_outcome.lo:%.name')
            ->where(function($query) {
                $query->where('value', '=', $this->searchForValue['1'])
                    ->orWhere('value', '=', $this->searchForValue['2']);
            })
            ->get();

        foreach ($rows as $r) {
            if (preg_match($this->pattern, $r->alias, $matches) > 0) {
                if (count($matches) > 0) {
                    $alias = $matches[0];
                    foreach ($this->searchForValue as $key => $searchVal) {
                        if ($r->value === $this->searchForValue[$key]) {
                            $newValue = $this->setWithValue[$key];
                        }
                    }
                    if (!$newValue) {
                        continue;
                    }

                    $this->connection
                        ->table($this->table)
                        ->where('alias', '=', $alias)
                        ->where('language', '=', $r->language)
                        ->where('value', '=', $r->value)
                        ->update(array(
                            'value' => $newValue
                        ));
                }
            }
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {

    }
}
