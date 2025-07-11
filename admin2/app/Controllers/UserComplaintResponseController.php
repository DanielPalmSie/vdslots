<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\UserComplaint;
use App\Repositories\UserComplaintResponseRepository;
use App\Repositories\UserRepository;
use JsonException;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class UserComplaintResponseController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        $factory->get('/{user}/complaints/{complaint}/responses/', 'App\Controllers\UserComplaintResponseController::index')
            ->convert('user', $app['userProvider'])
            ->bind('admin.users.complaints.responses.index');

        $factory->post('/{user}/complaints/{complaint}/responses/', 'App\Controllers\UserComplaintResponseController::store')
            ->convert('user', $app['userProvider'])
            ->convert('complaint', $app['userComplaintProvider'])
            ->bind('admin.users.complaints.responses.store');

        return $factory;
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
    public function store(Application $app, User $user, UserComplaint $complaint, Request $request): JsonResponse
    {
        $complaint_response = UserComplaintResponseRepository::store([
            'user_id' => $user->getKey(),
            'complaint_id' => $complaint->id,
            'actor_id' => (int) UserRepository::getCurrentId(),
            'type' => $request->get('type'),
            'description' => $request->get('description'),
        ]);

        if ($complaint_response->exists) {
            $success = true;
            $msg = 'User complaint response created.';
            $errors = [];
        } else {
            $app['monolog']->addError('Error creating user complaint response. Message: ' . json_encode($complaint->getErrors(), JSON_THROW_ON_ERROR));

            $success = false;
            $msg = 'Error creating user complaint response.';
            $errors = $complaint_response->getErrors();
        }

        return $app->json(['success' => $success, 'message' => $msg, 'errors' => $errors]);
    }
}
