<?php

namespace App\Repositories;

use App\Models\UserComplaint;
use Carbon\Carbon;
use JsonException;
use Videoslots\HistoryMessages\ComplaintHistoryMessage;

class UserComplaintRepository
{
    /**
     * @param array $form_elements
     *
     * @return UserComplaint
     * @throws JsonException
     */
    public static function store(array $form_elements): UserComplaint
    {
        $user_complaint = new UserComplaint();
        $form_elements['status'] = 1;
        $user_complaint->fill($form_elements);

        if ($user_complaint->validate() && $user_complaint->save()) {
            $user_id = $user_complaint->user_id;

            ActionRepository::logAction(
                $user_id,
                'Complaint was saved' . json_encode($form_elements, JSON_THROW_ON_ERROR),
                'complaint-create',
                true
            );

            $created_at = $user_complaint->created_at instanceof Carbon
                ? $user_complaint->created_at->format('Y-m-d H:i:s')
                : $user_complaint->created_at;

            /** @uses Licensed::addRecordToHistory() */
            lic('addRecordToHistory', [
                'complaint',
                new ComplaintHistoryMessage([
                    'id'         => $user_complaint->id,
                    'user_id'    => $user_id,
                    'type'       => (int)$user_complaint->type,
                    'created_at' => $created_at,
                    'status'     => $user_complaint->status,
                    'event_timestamp'  => Carbon::now()->timestamp
                ])
            ], $user_id);
        }

        return $user_complaint;
    }

    /**
     * @param UserComplaint $user_complaint
     * @param array $form_elements
     *
     * @return UserComplaint
     * @throws JsonException
     */
    public static function update(UserComplaint $user_complaint, array $form_elements): UserComplaint
    {
        $user_complaint->status = (int) $form_elements['status'];

        if ($user_complaint->save()) {
            $user_id = $user_complaint->user_id;

            ActionRepository::logAction(
                $user_id,
                'Complaint was updated' . json_encode($form_elements, JSON_THROW_ON_ERROR),
                'complaint-update',
                true
            );

            // When complaint is deactivated
            if ($user_complaint->status === 0) {
                $created_at = $user_complaint->created_at instanceof Carbon
                    ? $user_complaint->created_at->format('Y-m-d H:i:s')
                    : $user_complaint->created_at;

                /** @uses Licensed::addRecordToHistory() */
                lic('addRecordToHistory', [
                    'complaint',
                    new ComplaintHistoryMessage([
                        'id'         => $user_complaint->id,
                        'user_id'    => (int)$user_id,
                        'type'       => (int)$user_complaint->type,
                        'created_at' => $created_at,
                        'status'     => $user_complaint->status,
                        'event_timestamp'  => Carbon::now()->timestamp
                    ])
                ], $user_id);
            }
        }

        return $user_complaint;
    }
}
