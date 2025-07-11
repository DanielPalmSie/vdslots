<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Repositories\ActionRepository;

class DecreaseDepositLimitForDeUsers extends Seeder
{

    const DE_DEPOSIT_LIMIT = 100000;
    const COUNTRY = 'DE';
    const RG_LIMIT_TYPE = 'deposit';

    private Connection $connection;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        $limits = $this->connection
            ->table('rg_limits')->shs()
            ->select('rg_limits.id', 'user_id', 'cur_lim', 'new_lim', 'time_span')
            ->join('users', 'users.id', '=', 'rg_limits.user_id')
            ->where('users.country', '=', self::COUNTRY)
            ->where('type', '=', self::RG_LIMIT_TYPE)
            ->where(function ($q) {
                return $q->where('cur_lim', '>', self::DE_DEPOSIT_LIMIT)
                    ->orWhere('new_lim', '>', self::DE_DEPOSIT_LIMIT);
            })->get()->toArray();

        $this->saveToCsv($limits);

        $this->connection
            ->table('rg_limits')
            ->join('users', 'users.id', '=', 'rg_limits.user_id')
            ->where('users.country', '=', self::COUNTRY)
            ->where('type', '=', self::RG_LIMIT_TYPE)
            ->where('cur_lim', '>', self::DE_DEPOSIT_LIMIT)
            ->update(['cur_lim' => self::DE_DEPOSIT_LIMIT]);

        $this->connection
            ->table('rg_limits')
            ->join('users', 'users.id', '=', 'rg_limits.user_id')
            ->where('users.country', '=', self::COUNTRY)
            ->where('type', '=', self::RG_LIMIT_TYPE)
            ->where('new_lim', '>', self::DE_DEPOSIT_LIMIT)
            ->update(['new_lim' => self::DE_DEPOSIT_LIMIT]);

        $this->logAction($limits);
    }

    private function saveToCsv(array $data)
    {
        $filename = 'deposit_limits_de_' . time() . '.csv';
        $f = fopen($filename, 'w');
        
        if ($f === false) {
            die('Error opening the file ' . $filename);
        }

        fputcsv($f, ['id', 'user_id', 'cur_lim', 'new_lim', 'time_span']);
        foreach ($data as $limit) {
            fputcsv($f, (array) $limit);
        }

        fclose($f);
    }

    private function logAction(array $data)
    {
        foreach ($data as $limit) {
            $cur_lim_desc = $limit->cur_lim > self::DE_DEPOSIT_LIMIT ? "cur limit changed from {$limit->cur_lim} to " . self::DE_DEPOSIT_LIMIT : "";
            $new_lim_desc = $limit->new_lim > self::DE_DEPOSIT_LIMIT ? "new limit changed from {$limit->new_lim} to " . self::DE_DEPOSIT_LIMIT : "";
            $description = "{$limit->time_span} limit updated: {$cur_lim_desc} {$new_lim_desc}";
            ActionRepository::logAction($limit->user_id, $description, 'deposit', false, null, true);
        }
    }
}