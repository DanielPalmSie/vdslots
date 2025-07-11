<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../api/PhModule.php';

class UserGameFilter extends PhModule
{

    /**
     * Retrieves all game filters for a given user.
     *
     * @param int $user_id The ID of the user.
     * @return array The list of filters associated with the user.
     */
    function getFilters(int $user_id): array
    {
        $sql = "SELECT * FROM users_game_filters WHERE user_id = '{$user_id}' ";

        return phive('SQL')->sh($user_id)->loadArray($sql) ?? [];
    }

    /**
     * Saves a new game filter for a user.
     *
     * @param int $user_id The ID of the user.
     * @param string $title The title of the filter.
     * @param array $filters The filter data to be saved (will be JSON-encoded).
     * @param string|null $created_at Optional custom creation timestamp (defaults to current time if null).
     * @param string|null $updated_at Optional custom update timestamp (defaults to current time if null).
     *
     * @return int|null The ID of the newly inserted filter, or null on failure.
     */
    function saveFilter(int $user_id, string $title, array $filters, ?string $created_at, ?string $updated_at): ?int
    {
        $now = phive()->hisNow();

        $insert_array = [
            'user_id' => $user_id,
            'title' => $title,
            'filter' => json_encode($filters),
            'created_at' => $created_at ?? $now,
            'updated_at' => $updated_at ?? $now,
        ];

        return (int) phive('SQL')->sh($user_id)->insertArray('users_game_filters', $insert_array);
    }

    /**
     * Deletes a filter for a specific user.
     *
     * @param int $user_id The ID of the user (owner of the filter).
     * @param int $id The ID of the filter.
     * @return bool True if the deletion was successful, false otherwise.
     */
    function deleteFilter(int $user_id, int $id): bool
    {
        $db = phive('SQL')->sh($user_id);

        // Check if record exists
        $checkSql = "SELECT id FROM users_game_filters WHERE id = '$id' AND user_id = '$user_id'";
        $filter = $db->loadAssoc($checkSql);

        if (!$filter) {
            return false;
        }

        // Delete the record
        $deleteSql = "DELETE FROM users_game_filters WHERE id = '$id' AND user_id = '$user_id'";
        return $db->query($deleteSql);
    }
}
