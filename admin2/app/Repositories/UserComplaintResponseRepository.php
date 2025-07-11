<?php

namespace App\Repositories;

use App\Models\UserComplaintResponse;
use Exception;
use Carbon\Carbon;
use Videoslots\HistoryMessages\ComplaintResponseHistoryMessage;

class UserComplaintResponseRepository
{
    /**
     * @param array $form_elements
     *
     * @return UserComplaintResponse
     * @throws Exception
     */
    public static function store(array $form_elements): UserComplaintResponse
    {
        $complaint_response = new UserComplaintResponse();
        $complaint_response->fill($form_elements);

        if(UserComplaintResponse::where('complaint_id', $form_elements['complaint_id'])->count() > 0) {
            // We should have only 1 response for now. Probably it will be changed in the feature
            return $complaint_response;
        }

        if ($complaint_response->validate()  && $complaint_response->save()) {
            ActionRepository::logAction(
                $form_elements['user_id'],
                'Complaint response was created' . json_encode($form_elements, JSON_THROW_ON_ERROR),
                'complaint-response-create',
                true
            );

            $created_at = $complaint_response->created_at instanceof Carbon
                ? $complaint_response->created_at->format('Y-m-d H:i:s')
                : $complaint_response->created_at;

            /** @uses Licensed::addRecordToHistory() */
            lic('addRecordToHistory', [
                'complaint_response',
                new ComplaintResponseHistoryMessage([
                    'complaint_id' => $complaint_response->complaint_id,
                    'user_id'      => $complaint_response->user_id,
                    'type'         => (int)$complaint_response->type,
                    'created_at'   => $created_at,
                    'description'  => $complaint_response->description,
                    'event_timestamp'  => Carbon::now()->timestamp
                ])
            ], $complaint_response->user_id);
        }

        return $complaint_response;
    }
}
