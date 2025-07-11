<?php

namespace App\Controllers;

use App\Models\IpLog;
use App\Models\User;
use App\Models\Group;
use App\Models\PermissionGroup;
use App\Models\PermissionTag;
use App\Models\PermissionUser;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Extensions\Database\FManager as DB;
use App\Repositories\ActionRepository;
use App\Repositories\UserRepository;
use App\Repositories\PermissionRepository;
use App\Models\GroupMember;

class SettingsPermissionController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        $factory->get('/group/', 'App\Controllers\SettingsPermissionController::listGroups')
            ->bind('settings.permissions')
            ->before(function () use ($app) {
                if (!p('view.user.groups')) {
                    $app->abort(403);
                }
            });

        $factory->get('/group/{group}/members/', 'App\Controllers\SettingsPermissionController::listGroupMembers')
            ->convert('group', $app['groupProvider'])
            ->assert('group', '\d+')
            ->bind('group-members')
            ->before(function ($request) use ($app) {
                $group_id = $request->get('group');
                if (!p('view.user.group.members') && !p('permission.edit.' . $group_id) && !p('permission.view.' . $group_id)) {
                    $app->abort(403);
                }
            });

        $factory->get('/group/{group}/permissions/', 'App\Controllers\SettingsPermissionController::listGroupPermissions')
            ->convert('group', $app['groupProvider'])
            ->assert('group', '\d+')
            ->bind('group-permissions')
            ->before(function ($request) use ($app) {
                $group_id = $request->get('group');
                if (!p('view.user.group.permissions') && !p('permission.edit.' . $group_id) && !p('permission.view.' . $group_id)) {
                    $app->abort(403);
                }
            });

        $factory->match('/group/permissions/add/', 'App\Controllers\SettingsPermissionController::addPermissionToGroup')
            ->bind('group-add-permissions')
            ->before(function () use ($app) {
                if (!p('add.group.permissions')) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        $factory->get('/group/{group}/permissions/{permission_tag}/remove/', 'App\Controllers\SettingsPermissionController::removePermissionFromGroup')
            ->bind('group-remove-permission-from-group')
            ->before(function () use ($app) {
                if (!p('remove.group.permissions')) {
                    $app->abort(403);
                }
            });

        $factory->match('/group/{group}/delete/', 'App\Controllers\SettingsPermissionController::deletePermissionGroup')
            ->bind('group-delete-group')
            ->before(function () use ($app) {
                if (!p('delete.permission.group')) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        $factory->match('/group/add/', 'App\Controllers\SettingsPermissionController::createPermissionGroup')
            ->bind('group-add-group')
            ->before(function () use ($app) {
                if (!p('create.groups')) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        $factory->match('/group/edit/', 'App\Controllers\SettingsPermissionController::editPermissionGroup')
            ->bind('group-edit-group')
            ->before(function () use ($app) {
                if (!p('rename.groups')) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        $factory->match('/group/removemember/', 'App\Controllers\SettingsPermissionController::removeMemberFromGroup')
            ->bind('group-remove-member')
            ->before(function ($request) use ($app) {
                $group_id = $request->get('group_id');
                if (!p('remove.group.member') && !p('permission.edit.' . $group_id)) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        $factory->match('/group/addmember/', 'App\Controllers\SettingsPermissionController::addMemberToGroup')
            ->bind('group-add-member')
            ->before(function ($request) use ($app) {
                $group_id = $request->get('group_id');
                if (!p('add.group.member') && !p('permission.edit.' . $group_id)) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        $factory->match('/group/getusernames/', 'App\Controllers\SettingsPermissionController::getUsernames')
            ->bind('group-get-usernames')
            ->before(function () use ($app) {
                if (!p('add.group.member') && !p('permission.edit.%')) {   // use same permission as Add member to group
                    $app->abort(403);
                }
            })
            ->method('GET|POST');
        $factory->match('/group/getuserids/', 'App\Controllers\SettingsPermissionController::getUserIds')
            ->bind('group-get-user_ids')
            ->before(function () use ($app) {
                if (!p('add.group.member') && !p('permission.edit.%')) {   // use same permission as Add member to group
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        $factory->get('/tags/', 'App\Controllers\SettingsPermissionController::listPermissions')
            ->bind('permissions.tag-list')
            ->before(function () use ($app) {
                if (!p('view.permission.tags')) {
                    $app->abort(403);
                }
            });

        $factory->match('/tags/create/', 'App\Controllers\SettingsPermissionController::createPermissionTag')
            ->bind('permission-tag-create')
            ->before(function () use ($app) {
                if (!p('create.permission.tag')) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        $factory->match('/tags/delete/', 'App\Controllers\SettingsPermissionController::deletePermissionTag')
            ->bind('permission-tag-delete')
            ->before(function () use ($app) {
                if (!p('delete.permission.tag')) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        return $factory;
    }

    public function listGroups(Application $app)
    {
        $breadcrumb = 'Groups';

        $can_see_all_groups = p('view.user.permissions');

        if ($can_see_all_groups) {
            $groups = Group::all();
        } else {
            $current_user = UserRepository::getCurrentUser();
            $manageable_groups = [];
            foreach ($current_user->groups()->get() as $group) {
                $manageable_groups = array_merge($manageable_groups, $group->manageable_groups());
            }
            $groups = Group::whereIn('group_id', $manageable_groups)->get();
        }

        return $app['blade']->view()->make('admin.settings.permission.group_list',
            compact('groups', 'app', 'breadcrumb'))->render();
    }

    public function listGroupMembers(Application $app, Group $group)
    {

        $breadcrumb = 'Groups';

        $users = User::shs()
            ->whereIn('id', DB::table('groups_members')->where('group_id', $group->getKey())->get()->pluck('user_id')->all())
            ->get();

        return $app['blade']->view()->make('admin.settings.permission.group_members_list',
                compact('users', 'group', 'available_members', 'app', 'breadcrumb'))->render();
    }

    public function listGroupPermissions(Application $app, Group $group)
    {
        $this->generateManagementTags();
        $group_permissions = $group->permission_groups()->get();//todo shards
        $all_permission_tags = PermissionTag::whereNotIn('tag', $group->permission_groups()->pluck('tag'))->get();//todo shards
        $current_permissions = PermissionGroup::where('group_id', $group->group_id)->get();

        return $app['blade']->view()->make('admin.settings.permission.group_permissions_list',
                compact('group_permissions', 'current_permissions', 'all_permission_tags', 'group', 'app'))->render();
    }

    public function listPermissions(Application $app)
    {
        $breadcrumb = 'Tags';

        $this->generateManagementTags();

        $permission_tags = PermissionTag::all();
        return $app['blade']->view()->make('admin.settings.permission.tag_list',
                compact('permission_tags', 'app', 'breadcrumb'))->render();
    }

    private function generateManagementTags()
    {
        foreach (Group::all() as $group) {
            p("permission.edit.{$group['group_id']}");
            p("permission.view.{$group['group_id']}");
        }
    }

    /**
     * Creates a new permission tag
     *
     * @param Application   $app
     * @param Request       $request
     * @return string       JSON encoded message
     */
    public function createPermissionTag(Application $app, Request $request)
    {
        $permission_tag = new PermissionTag();
        $permission_tag->tag = $request->get('tag_name');
        if($permission_tag->save()) {

            $actor = UserRepository::getCurrentUser();
            $description = "created permission tag {$permission_tag->tag}";
            ActionRepository::logAction($actor, $description, 'create_permission_tag', true, $actor->id);
            IpLog::logIp($actor, $actor, IpLog::TAG_GROUP, $description);

            return json_encode(['success' => true, 'message' => 'Permission tag successfully created']);
        }

        return json_encode(['success' => false, 'message' => $permission_tag->getFirstError()]);
    }

    /**
     * Deletes a permission tag
     *
     * @param Application   $app
     * @param Request       $request
     * @return string       JSON encoded message
     */
    public function deletePermissionTag(Application $app, Request $request)
    {
        $permission_tag = PermissionTag::find($request->get('tag_name'));

        $message = '';
        // Check if any groups have this permission
        $permission_groups = PermissionGroup::where('tag', $request->get('tag_name'))->get();
        if(count($permission_groups) !== 0) {
            $message .= 'This permission tag is in use by the following groups:<br/> ';
            foreach ($permission_groups as $permission_group) {
                $group = Group::find($permission_group->group_id);
                $message .= $group->name . '<br/>';
            }
        }

        // Check if any users have this permission directly
        $permission_users = PermissionUser::where('tag', $request->get('tag_name'))->get();
        if(count($permission_users) !== 0) {
            $message .= 'This permission tag is in use by the following users: <br/> ';
            foreach ($permission_users as $permission_user) {
                $user = User::find($permission_user->user_id);
                $message .= $user->username . '<br/>';
            }
        }

        if(!empty($message)) {
            $message = 'Cannot delete this permission tag. <br/>' . $message;
            return json_encode(['success' => false, 'message' => $message]);
        }

        if($permission_tag->delete()) {
            return json_encode(['success' => true, 'message' => 'Permission tag successfully deleted']);
        }

        return json_encode(['success' => false, 'message' => $permission_tag->getFirstError()]);
    }

    /**
     * Adds a permission to a group
     *
     * @param Request       $request
     * @param Application   $app
     * @return string       JSON encoded message
     */
    public function addPermissionToGroup(Request $request, Application $app)
    {
        /** @var Group $group */
        $group = Group::find($request->get('group_id'));

        $permission_group = new PermissionGroup();
        $permission_group->group_id   = $request->get('group_id');
        $permission_group->tag        = $request->get('permission_tag');
        $permission_group->mod_value  = '';
        $permission_group->permission = 'grant';

        if($permission_group->save()) {
            $actor = UserRepository::getCurrentUser();
            $description = "added permission {$request->get('permission_tag')} to group {$group->name}";
            ActionRepository::logAction($actor, $description, 'add_permission', true, $actor->id);
            IpLog::logIp($actor, $actor, IpLog::TAG_GROUP, $description);

            return json_encode(['success' => true, 'message' => 'Permission successfully added to the group']);
        } else {
            return json_encode(['success' => false, 'message' => 'Unable to add permission to group']);
        }
    }

    /**
     * Deletes a permission group, and deletes all references to this group
     *
     * @param Application   $app
     * @param Request       $request
     * @return string       JSON encoded message
     */
    public function deletePermissionGroup(Application $app, Request $request)
    {
        // Validate input
        if(!is_numeric($request->get('group'))) {
            return json_encode(['success' => false, 'message' => 'Group ID is not a number']);
        }

        $group_id = $request->get('group');
        $group = Group::find($request->get('group'));

        DB::beginTransaction();

        try {
            DB::table('groups_members')->where('group_id', $group_id)->delete();
            DB::table('permission_groups')->where('group_id', $group_id)->delete();
            DB::table('groups')->where('group_id', $group_id)->delete();

            DB::commit();

        } catch (\Exception $e) {
            DB::rollback();
            return json_encode(['success' => false, 'message' => 'Unable to delete this group']);
        }
        
        $actor = UserRepository::getCurrentUser();
        $description = "removed group {$group->name}";
        ActionRepository::logAction($actor, $description, 'delete_group', true, $actor->id);
        IpLog::logIp($actor, $actor, IpLog::TAG_GROUP, $description);
        
        return json_encode(['success' => true, 'message' => 'Group successfully deleted']);
    }

    /**
     * Creates a new group
     *
     * @param Application   $app
     * @param Request       $request
     * @return string       JSON encoded message
     */
    public function createPermissionGroup(Application $app, Request $request)
    {
        $group = new Group();
        $group->name = $request->get('group_name');
        if($group->save()) {

            $actor = UserRepository::getCurrentUser();
            $description = "created group {$group->name}";
            ActionRepository::logAction($actor, $description, 'create_group', true, $actor->id);
            IpLog::logIp($actor, $actor, IpLog::TAG_GROUP, $description);

            return json_encode(['success' => true, 'message' => 'Group successfully created']);
        }

        return json_encode(['success' => false, 'message' => $group->getFirstError()]);
    }

    /**
     * Edits a group. The only editable column is name. 
     *
     * @param Application   $app
     * @param Request       $request
     * @return string       JSON encoded message
     */
    public function editPermissionGroup(Application $app, Request $request)
    {
        $group = Group::find($request->get('group_id'));
        $old_name = $group->name;
        $group->name = $request->get('new_name');
        if($group->save()) {

            $actor = UserRepository::getCurrentUser();
            $description = "renamed group {$old_name} to {$group->name}";
            ActionRepository::logAction($actor, $description, 'rename_group', true, $actor->id);
            IpLog::logIp($actor, $actor, IpLog::TAG_GROUP, $description);

            return json_encode(['success' => true, 'message' => 'Group successfully renamed']);
        }
        return json_encode(['success' => false, 'message' => 'Unable to rename group']);
    }

    /**
     * Removes a single permission from a group
     *
     * @param Application   $app
     * @param Request       $request
     * @return string       JSON encoded message
     */
    public function removePermissionFromGroup(Application $app, Request $request)
    {
        $result = DB::table('permission_groups')->where('group_id', $request->get('group'))
                ->where('tag', $request->get('permission_tag'))
                ->where('mod_value', $request->get('mod_value'))
                ->delete();

        if($result === 1) {

            $group = Group::find($request->get('group'));
            $actor = UserRepository::getCurrentUser();
            $description = "removed permission {$request->get('permission_tag')} from {$group->name}";
            ActionRepository::logAction($actor, $description, 'remove_permission', true, $actor->id);
            IpLog::logIp(UserRepository::getCurrentId(), UserRepository::getCurrentId(), IpLog::TAG_GROUP, $description);

            return json_encode(['success' => true, 'message' => 'Permission successfully removed from group']);
        }
        return json_encode(['success' => false, 'message' => 'Unable to remove permission from group']);        
    }

    /**
     * Removes a member from a group
     *
     * @param Application   $app
     * @param Request       $request
     * @return string       JSON encoded message
     */
    public function removeMemberFromGroup(Application $app, Request $request)
    {
        $group = Group::find($request->get('group_id'));
        $user = User::findByUsername($request->get('username'));

        if(!PermissionRepository::isUserInGroup($user->id, $request->get('group_id'))) {
            return json_encode(['success' => false, 'message' => 'Unable to remove member from group']);
        }

        if(PermissionRepository::deleteGroupMember($user->id, $group->group_id)) {

            $actor = UserRepository::getCurrentUser();
            $description = "removed member {$request->get('username')} from {$group->name}";
            ActionRepository::logAction($user->id, $description, 'remove_member', true, $actor->id);
            IpLog::logIp(UserRepository::getCurrentId(), $user->id, IpLog::TAG_GROUP, $description);

            return json_encode(['success' => true, 'message' => 'Member successfully removed from group']);
        }
        return json_encode(['success' => false, 'message' => 'Unable to remove member from group']);
        
    }

    /**
     * Adds a member to a group
     *
     * @param Application   $app
     * @param Request       $request
     * @return string       JSON encoded message
     */
    public function addMemberToGroup(Application $app, Request $request)
    {
        $group = Group::find($request->get('group_id'));

        if (!empty($request->get('username'))) {
            $user = User::findByUsername($request->get('username'));
            $username = $request->get('username');
        } else {
            $user = User::query()->find($request->get('user_id'));
            $username = $request->get('user_id');
        }

        if(PermissionRepository::isUserInGroup($user->id, $request->get('group_id'))) {
            return json_encode(['success' => false, 'message' => "User {$user->username} is already a member of group {$group->name}"]);
        }

        $group_member = new GroupMember();
        $group_member->group_id = $request->get('group_id');
        $group_member->user_id  = $user->id;

        if($group_member->save()) {
            $actor = UserRepository::getCurrentUser();
            $description = "added member {$username} to {$group->name}";
            ActionRepository::logAction($user->id, $description, 'add_member', true, $actor->id);
            IpLog::logIp(UserRepository::getCurrentId(), $user->id, IpLog::TAG_GROUP, $description);

            return json_encode(['success' => true, 'message' => 'Member successfully added to the group']);
        }
        return json_encode(['success' => false, 'message' => 'Unable to add member to group']);
    }

    /**
     * Returns a list of usernames based on a search string
     *
     * @param Application   $app
     * @param Request       $request
     * @return string       JSON encoded message
     */
    public function getUsernames(Application $app, Request $request)
    {
        $usernames = PermissionRepository::getUsernames($request->get('search_string'));

        return json_encode($usernames);
    }
    /**
     * Returns a list of usernames based on a search string
     *
     * @param Application   $app
     * @param Request       $request
     * @return string       JSON encoded message
     */
    public function getUserIds(Application $app, Request $request)
    {
        $ids = PermissionRepository::getUserIds($request->get('search_string'));

        return json_encode($ids);
    }
}
