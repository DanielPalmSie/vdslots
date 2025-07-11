<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\UserComment;
use App\Models\UserComplaint;
use App\Repositories\UserCommentRepository;
use App\Repositories\UserComplaintRepository;
use App\Repositories\UserRepository;
use JsonException;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class UserComplaintController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        $factory->post('/{user}/complaints/', 'App\Controllers\UserComplaintController::store')
            ->convert('user', $app['userProvider'])
            ->bind('admin.users.complaints.store');

        $factory->match('/{user}/complaints/{complaint}/', 'App\Controllers\UserComplaintController::update')
            ->convert('user', $app['userProvider'])
            ->convert('complaint', $app['userComplaintProvider'])
            ->bind('admin.users.complaints.update')
            ->method('PUT|PATCH');

        return $factory;
    }

    /**
     * @param Application $app
     * @param User $user
     * @param Request $request
     *
     * @return JsonResponse
     * @throws JsonException
     */
    public function store(Application $app, User $user, Request $request): JsonResponse
    {
        $complaint = UserComplaintRepository::store([
            'user_id' => $user->getKey(),
            'ticket_id' => $request->get('ticket_id'),
            'actor_id' => (int) UserRepository::getCurrentId(),
            'ticket_url' => $request->get('ticket_url'),
            'type' => $request->get('type'),
            'status' => $request->get('status'),
        ]);

        if ($complaint->exists) {
            if (!empty($request->get('complaint_comment'))) {
                UserCommentRepository::createComment([
                    'user_id' => $user->getKey(),
                    'tag' => UserComment::TYPE_COMPLAINT,
                    'comment' => $request->get('complaint_comment') .' // ' . UserRepository::getCurrentUsername(),
                    'foreign_id' => $complaint->id,
                    'foreign_id_name' => 'id',
                ]);
            }

            $success = true;
            $msg = 'User complaint created.';
            $errors = [];
        } else {
            $app['monolog']->addError('Error creating user complaint. Message: ' . json_encode($complaint->getErrors(), JSON_THROW_ON_ERROR));

            $success = false;
            $msg = 'Error creating user complaint.';
            $errors = $complaint->getErrors();
        }

        return $app->json(['success' => $success, 'message' => $msg, 'errors' => $errors]);
    }

    /**
     * @param Application $app
     * @param User $user
     * @param UserComplaint $complaint
     * @param Request $request
     *
     * @return JsonResponse
     * @throws JsonException
     */
    public function update(Application $app, User $user, UserComplaint $complaint, Request $request): JsonResponse
    {
        $form_elements = [
            'status' => (int) $request->get('status'),
            'user_id' => $user->id,
        ];

        $complaint = UserComplaintRepository::update($complaint, $form_elements);

        if (!$complaint->isDirty()) {
            $success = true;
            $msg = 'User complaint updated.';
            $errors = [];
        } else {
            $app['monolog']->addError('Error updating user complaint. Message: ' . json_encode($complaint->getErrors(), JSON_THROW_ON_ERROR));

            $success = false;
            $msg = 'Error updating user complaint.';
            $errors = $complaint->getErrors();
        }

        return $app->json(['success' => $success, 'message' => $msg, 'errors' => $errors]);
    }
}
