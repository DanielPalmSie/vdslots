<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class MrvegasGreentubeMissingWins extends Seeder
{
    private $data;

    public function init()
    {
        $this->data = [
            [544521, 38516566388, 15],
            [473340, 38516817178, 116.7],
            [853312, 38516856897, 3],
            [853312, 38516882904, 48],
            [853312, 38516920244, 3],
            [853312, 38516955002, 10],
            [484034, 38517060432, 10],
            [484034, 38517083202, 10],
            [325756, 38517681776, 15],
            [476511, 38517771966, 2500],
            [272496, 38518146342, 238],
            [272496, 38518234794, 262.7],
            [280755, 38518266056, 1065],
            [476511, 38518294939, 400],
            [280755, 38518437572, 29],
            [556205, 38518502846, 76.98],
            [556205, 38518726975, 3],
            [473340, 38518761430, 358],
            [556205, 38519051198, 3],
            [556205, 38519085335, 1.5],
            [556205, 38519098608, 73.3],
            [476511, 38519604367, 170],
            [402776, 38519790164, 2.25],
            [476511, 38520090805, 50],
            [476511, 38520134367, 1940],
            [544521, 38520238092, 1550],
            [582317, 38520736388, 3],
            [315045, 38520887063, 215],
            [476511, 38521358816, 75],
        ];
    }

    public function up()
    {
        foreach ($this->data as $row) {
            // Extract data from the row
            $userId = $row[0];       // User ID
            $transactionId = $row[1]; // Transaction ID
            $amount = $row[2] * 100; // Convert amount to cents

            // Prepare the SQL query
            $query = "
            INSERT INTO wins (
                trans_id,
                game_ref,
                user_id,
                amount,
                created_at,
                mg_id,
                balance,
                award_type,
                bonus_bet,
                op_fee,
                currency,
                device_type
            )
            SELECT
                trans_id,
                game_ref,
                user_id,
                $amount AS amount,
                created_at,
                mg_id,
                0 AS balance,
                2 AS award_type,
                bonus_bet,
                op_fee,
                currency,
                device_type
            FROM
                bets
            WHERE
                user_id = $userId
                AND trans_id = $transactionId;
        ";

            // Execute the query
            phive('SQL')->sh($userId)->query($query);
        }
    }

}
