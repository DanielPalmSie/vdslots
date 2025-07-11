<?php

/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2015.11.17.
 * Time: 9:29
 */

namespace App\Controllers;

use App\Models\User;
use App\Models\UserComment;
use App\Repositories\ActionRepository;
use App\Repositories\UserCommentRepository;
use App\Repositories\UserRepository;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class CommentController implements ControllerProviderInterface
{
    public function __construct()
    {
        $this->userCommentRepository = new UserCommentRepository;
    }

    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];
        $factory->post('/new/', 'App\Controllers\CommentController::submitNewComment')->bind('admin.comment-new');

        $factory->get('/{comment}/delete/', 'App\Controllers\CommentController::deleteUserComment')
            ->convert('comment', $app['commentProvider'])
            ->bind('admin.user-delete-comment');

        $factory->get('/{comment}/unstick/', 'App\Controllers\CommentController::unstickUserComment')
            ->convert('comment', $app['commentProvider'])
            ->bind('admin.user-unstick-comment');

        $factory->post('/get/', 'App\Controllers\CommentController::getComments')->bind('admin.comment-get');

        return $factory;
    }

    /**
     * Return all the users
     *
     * @param Application $app
     * @return mixed
     */
    public function submitNewComment(Application $app, Request $request)
    {
        if ($request->get('comment')) {
            $form_elements = $request->request->all();
            $form_elements['comment'] = $form_elements['comment'] .' // '. UserRepository::getCurrentUsername();
            $form_elements['foreign_id_name'] = 'id';
            $form_elements['tag'] = $form_elements['tag'] == 'all' ? '' : $form_elements['tag'];
            $form_elements['secret'] = $form_elements['secret'] ? : 0;

            if ($form_elements['tag'] == 'mlro' && !p('edit.account.comments.mlro')) {
                return json_encode(['success' => false]);
            } elseif ($form_elements['tag'] == 'sar' && !p('edit.account.comments.sar')) {
                return json_encode(['success' => false]);
            }

            UserCommentRepository::createComment($form_elements);
            return json_encode(['success' => true]);
        }
        return json_encode(['success' => false]);
    }

    public function deleteUserComment(Application $app, UserComment $comment)
    {
        if ($comment->tag == 'mlro' && !p('edit.account.comments.mlro')) {
            return json_encode(['success' => false]);
        } elseif ($comment->tag == 'sar' && !p('edit.account.comments.sar')) {
            return json_encode(['success' => false]);
        }
        $comment->delete();
        $actor = UserRepository::getCurrentUser();
        ActionRepository::logAction(
            $comment->user_id,
            "deleted comment '{$comment->comment}' for user {$comment->user_id}",
            $comment->secret == 1 ? "comment-hidden" : "comment",
            true
        );
        return json_encode(['success' => true]);
    }

    public function unstickUserComment(Application $app, UserComment $comment)
    {
        if (!p('unstick.account.comments')) {
            return json_encode(['success' => false]);
        }
        $comment->sticky = 0;
        $comment->save();
        $actor = UserRepository::getCurrentUser();
        ActionRepository::logAction(
            $comment->user_id,
            "unstick comment '{$comment->comment}' for user {$comment->user_id}",
            $comment->secret == 1 ? "comment-hidden" : "comment",
            true
        );
        return json_encode(['success' => true]);
    }

    public function getComments(Application $app, Request $request)
    {
        $user_id = $request->get('user_id');
        if ($user_id) {
            $tags = $request->get('tags');

            $comments = UserComment::sh($user_id)
                    ->where('user_id', '=', $user_id)
                    ->when(!empty($tags), function ($query) use ($tags) {
                        $query->whereIn('tag', $tags);
                    })->get()->toArray();


            return json_encode([
                    'comments' => $comments,
                    'success' => true
            ]);
        }
        return json_encode(['success' => false]);
    }


}
