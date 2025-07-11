<?php

namespace App\Repositories;

use App\Extensions\Database\FManager as DB;
use Silex\Application;

class PermissionRepository
{
    /** @var Application $app */
    protected $app;

    /**
     * Gets a list of usernames starting with $search_string in a format for Select2
     *
     * @param string $search_string
     *
     * @return array
     */
    public static function getUsernames($search_string)
    {
        $result = DB::shsSelect('users', 
                "SELECT username FROM users WHERE username LIKE :search_string",
                ['search_string' => $search_string.'%']);

        $usernames = [];
        foreach ($result as $key => $username) {
            $usernames[] = ['id' => $username->username, 'text' => $username->username];
        }

        return ['results' => $usernames, 'more' => false];
    }
    /**
     * Gets a list of user ids starting with $search_string in a format for Select2
     *
     * @param string $search_string
     *
     * @return array
     */
    public static function getUserIds($search_string)
    {
        $result = DB::shsSelect('users',
            "SELECT id as username FROM users WHERE id LIKE :search_string",
            ['search_string' => $search_string.'%']);

        $usernames = [];
        foreach ($result as $key => $username) {
            $usernames[] = ['id' => $username->username, 'text' => $username->username];
        }

        return ['results' => $usernames, 'more' => false];
    }

    /**
     * Checks if a user is in a permission group
     *
     * @param int $user_id
     * @param int $group_id
     * @return boolean
     */
    public static function isUserInGroup($user_id, $group_id)
    {
        $result = DB::shsSelect('groups_members',
                "SELECT * FROM groups_members WHERE user_id = :user_id AND group_id = :group_id",
                ['user_id' => $user_id, 'group_id' => $group_id]);

        if(!empty($result)) {
            return true;
        }

        return false;
    }

    /**
     * Deletes a records from table `groups_members`
     * We need to do a raw query because Eloquent does not support composite primary keys
     *
     * @param int $user_id
     * @param int $group_id
     * @return boolean
     */
    public static function deleteGroupMember($user_id, $group_id)
    {
        $result = DB::shsStatement('groups_members',
                "DELETE FROM groups_members WHERE user_id = :user_id AND group_id = :group_id",
                ['user_id' => $user_id, 'group_id' => $group_id]);

        // Check if member was indeed removed, because DB::shsStatement does not return the needed information
        if(PermissionRepository::isUserInGroup($user_id, $group_id)) {
            return false;
        }

        return true;
    }
}