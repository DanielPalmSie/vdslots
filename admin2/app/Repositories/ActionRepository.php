<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 16/03/16
 * Time: 16:48
 */

namespace App\Repositories;

use App\Classes\DateRange;
use App\Models\Action;
use App\Models\User;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\ArchiveFManager as ArchiveDB;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;

class ActionRepository
{

    /**
     * @param $target
     * @param $description
     * @param $tag
     * @param bool $add_username
     * @param null $actor
     * @param bool $system
     * @return Action
     * @throws \Exception
     */
    public static function logAction($target, $description, $tag, $add_username = false, $actor = null, $system = false): Action
    {
        if (is_numeric($target)) {
            $target_id = $target;
        } else {
            $target_id = ($target instanceof User) ? $target->getKey() : $target;
        }

        if ($system === false) {
            if (empty($actor)) {
                $actor = UserRepository::getCurrentUser();
            } elseif (is_numeric($actor)) {
                $actor = User::find($actor);
            }
        }

        if (empty($actor)) {
            $actor_id = User::where('username', 'system')->first()->id ?? 0;
            $actor_username = 'system';
        } else {
            $actor_id = $actor->id;
            $actor_username = $actor->username;
        }

        return Action::sh($target_id)->create([
            'actor' => $actor_id,
            'target' => $target_id,
            'descr' => $add_username ? $actor_username . " $description" : $description,
            'tag' => $tag,
            'actor_username' => mb_strimwidth($actor_username, 0, 25)
        ]);
    }

    /**
     * @param array $params
     * @param DateRange $date_range
     * @param User $user
     * @param $permission
     * @param $archived_db
     * @param $by_admin
     * @return Builder
     */
    public function getUserActionsQuery(array $params, DateRange $date_range, User $user, $permission, $archived_db = false, $by_admin = false)
    {
        if ($permission) {
            $description_select = "actions.descr as descr";
        } else {
            $description_select = "IF(actions.tag = 'comment-hidden', CONCAT_WS(':', SUBSTRING_INDEX(actions.descr, ':', 1), ' ************************'), actions.descr) as descr";
        }

        $select = "actions.actor, actions.target, actions.created_at, $description_select, IF(actions.tag = 'comment-hidden', 'comment', actions.tag) as tag";

        if ($by_admin) {
            if ($archived_db) {
                $actions_query = ArchiveDB::table('actions');
            } else {
                $actions_query = DB::table('actions');
            }
            $actions_query->selectRaw("{$select}, users.username as actor_username")
                ->leftJoin('users', 'users.id', '=', 'actions.target')
                ->where('actor', $user->getKey())->where('target', '!=', $user->getKey());
        } else {
            if ($archived_db) {
                $actions_query = ArchiveDB::shTable($user->getKey(), 'actions')->selectRaw("{$select}, actor_username")
                    ->where('target', $user->getKey());
            } else {
                $actions_query = DB::shTable($user->getKey(), 'actions')->selectRaw("{$select}, actor_username")
                    ->where('target', $user->getKey());
            }
        }

        $actions_query->whereBetween('created_at', $date_range->getWhereBetweenArray());

        if (!empty($params['actor'])) {
            $actions_query->where('actor', $params['actor']);
        }

        if (!empty($params['tag-like'])) {
            $tag_like = explode(',', $params['tag-like']);
            $tag_like = array_filter($tag_like, 'strlen');

            $actions_query->where(function($q) use ($tag_like) {
                /** @var Builder $q */
                foreach ($tag_like as $tag) {
                    // Escape underscore characters to be treated as literals in LIKE
                    $escapedTag = str_replace('_', '!_', $tag);
                    $q->orWhereRaw("tag LIKE ? ESCAPE '!'", ["%{$escapedTag}%"]);
                }
                return $q;
            });
        } else {
            if (!empty($params['tag'])) {
                if ($params['tag'] == 'multiply') {
                    $actions_query->where('tag', 'LIKE', '%multiply%');
                } elseif ($params['tag'] == 'comment') {
                    $actions_query->where('tag', 'LIKE', '%comment%');
                } else {
                    $actions_query->where('tag', $params['tag']);
                }
            }
        }

        return $actions_query;
    }

    public function getUserActorsList(User $user)
    {
        return $user->actionsOnMe()
            ->selectRaw('DISTINCT actor, actor_username as username')
            ->get();
    }

    public function getUserTagsList(User $user, $app, $date_range, $by_admin = false)
    {
        $where = !empty($by_admin) ? "target != {$user->id} AND actor = {$user->id}" : "target = {$user->id} OR actor = {$user->id}";

        /** @var Collection $tags_from_db */
        $tags_from_db = DB::shsSelect('actions', "SELECT DISTINCT tag FROM actions WHERE {$where}");

        if ($app['vs.config']['archive.db.support.actions'] && isArchived('actions', $date_range)) {
            $tags_from_archive_db = ArchiveDB::shSelect($user->getKey(), 'actions', "SELECT DISTINCT tag FROM actions WHERE {$where}");
            $tags_from_db = collect($tags_from_archive_db)->union(collect($tags_from_db));
        }

        $tags = [];
        foreach ($tags_from_db as $value) {
            $value = (array)$value;
            $exploded_value = explode('-', $value['tag']);
            if (in_array('multiply', $exploded_value)) {
                if (!in_array('multiply', $tags)) {
                    $tags[] = 'multiply';
                } else {
                    continue;
                }
            } elseif (in_array('comment', $exploded_value)) {
                if (!in_array('comment', $tags)) {
                    $tags[] = 'comment';
                } else {
                    continue;
                }
            } else {
                $tags[] = $value['tag'];
            }
        };
        return $tags;
    }

    public function getLastActionByTargetUser($user_id, $tag = '', $select_columns = '*')
    {
        $actions_query = DB::table('actions');

        $actions_query->selectRaw("{$select_columns}")->where('target', $user_id);

        if (!empty($tag)) {
            $actions_query->where('tag', '=', $tag);
        }

        $actions_query->orderBy('created_at', 'desc');

        $result = $actions_query->get()->first();

        return empty($result) ? [] : (array)$result;
    }
}
