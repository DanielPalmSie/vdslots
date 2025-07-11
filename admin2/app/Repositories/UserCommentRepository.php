<?php
/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2015.11.24.
 * Time: 12:13
 */

namespace App\Repositories;

use App\Models\User;
use Carbon\Carbon;
use App\Models\UserComment;
use Illuminate\Database\Query\Builder;
use App\Extensions\Database\FManager as DB;

class UserCommentRepository
{
    public static function getComments(User $user, ?int $max_result_limit = null, ?int $page = null)
    {
        $tag_list = [
            '',
            'complaint',
            'phone_contact',
            'limits',
            'discussion',
            'vip',
            'communication',
            'amlfraud',
            'rg-risk-group',
            'aml-risk-group',
            'manual-flags',
            'sportsbook',
            'automatic-flags',
            'rg-evaluation',
            'rg-action',
            'account-closure',
        ];
        if (p('view.account.comments.sar')) {
            $tag_list[] = 'sar';
        }
        if (p('view.account.comments.mlro')) {
            $tag_list[] = 'mlro';
        }
        /** @var Builder $query */
        $query = DB::shTable($user->getKey(), 'users_comments')
            ->where('user_id', $user->getKey())
            ->whereIn('tag', $tag_list)
            ->orderBy('sticky', 'DESC')
            ->orderBy('created_at', 'DESC');

        if (!p('view.account.comments.hidden')) {
            $query->where('secret', '!=', 1);
        }

        $unstick = $query->get()->filter(function ($comment) {
            return !empty($comment->sticky)
                && (new Carbon($comment->created_at))->diffInDays(new Carbon()) > 90;
        });

        DB::shTable($user->getKey(), 'users_comments')
            ->whereIn('id', $unstick->pluck('id'))
            ->update(['sticky' => 0]);

        if (!empty($max_result_limit)) {
            return $query->paginate($max_result_limit, ['*'], 'page', $page);
        }

        return $query->get();
    }

    public static function createComment(array $form_elements, bool $log_action = true): void
    {
        if (UserComment::sh($form_elements['user_id'])->create($form_elements)) {
            phive('Cashier/Arf')->invoke('onUserComment', $form_elements['user_id'], $form_elements['tag']);
            if ($log_action) {
                ActionRepository::logAction(
                    $form_elements['user_id'],
                    "added a comment: " . var_export($form_elements['comment'], true),
                    $form_elements['secret'] == 1 ? "comment-hidden" : "comment",
                    true
                );
            }
        }
    }
}
