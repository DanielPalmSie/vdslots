<?php

namespace Videoslots\User\NetDepositLimit;

class BaseNetDepositLimitService
{
    /**
     * Checks if a comment needs to be added when RG60 triggers.
     * Adds comment only if user requested the last raise more than 30 days ago.
     *
     * @param int $user_id
     * @return bool
     */
    public function hasRG60LeftCommentLastMonth(int $user_id): bool
    {
        $start_date_time = phive()->hisMod('-30 day');

        $query = "
            SELECT id
            FROM users_comments
            WHERE user_id = {$user_id}
              AND tag = 'rg-evaluation'
              AND comment LIKE 'RG60%'
              AND created_at >= '{$start_date_time}'
            LIMIT 1;
        ";

        $result = phive('SQL')->sh($user_id)->loadArray($query);

        return !empty($result);
    }
}