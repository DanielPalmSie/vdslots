<?php

declare(strict_types=1);

namespace Videoslots\User\Notifications;

use Laraphive\Domain\User\DataTransferObjects\NotificationHistoryData;
use Laraphive\Domain\User\DataTransferObjects\NotificationHistoryResponse;

final class NotificationsService
{
    /**
     * Gets the latest notifications for a specific user from the persistent database.
     *
     * @param int $uid
     * @param int $page
     * @param int $limit
     *
     * @api
     *
     * @return array
     */
    public function getNotifications(int $uid, int $page, int $limit): NotificationHistoryResponse
    {
        $offset = ($page - 1) * $limit;
        $result = phive('DBUserHandler')->getLatestNotifications($uid, $limit, $offset);

        $count_query = "SELECT COUNT(un.id) FROM users_notifications un WHERE un.user_id = $uid";
        $total = (int) phive('SQL')->sh($uid, '', 'users')->getValue($count_query);

        $notifications = [];
        $user = cu($uid);
        $lang = lic('getForcedLanguage', [], $user);

        foreach ($result as $notification) {
            $description = strip_tags(phive('DBUserHandler')->eventString($notification, 'you.', $lang ?: null));
            $date = phive()->lcDate($notification->created_at) . ' ' . t('cur.timezone');
            $image = phive('DBUserHandler')->eventImage($notification, true);
            $notifications[] = new NotificationHistoryData($description, $date, $image);
        }

        return new NotificationHistoryResponse($notifications, $total);
    }
}
