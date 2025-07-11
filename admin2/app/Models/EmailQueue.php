<?php

namespace App\Models;

use App\Extensions\Database\FModel;

class EmailQueue extends FModel
{
    protected $table = 'mailer_queue';

    protected $primaryKey = 'mail_id';

    protected $guarded = ['mail_id'];

    public $timestamps = false;

    /**
     * @param string $subject
     * @param string $body
     * @param string|array $to
     * @return bool
     * @throws \Exception
     */
    public static function sendInternalNotification($subject, $body, $to)
    {
        //todo add validations

        //todo parametrize this from notifications@videoslots.com

        $new_instance = new self();

        $mail = [
            "replyto" => "notifications@videoslots.com",
            "from" => "notifications@videoslots.com",
            "subject" => $subject,
            "messageHTML" => $body,
            "messageText" => strip_tags($body),
            "priority" => 0,
        ];

        if (is_string($to)) {
            $mail['to'] = $to;
            return $new_instance->fill($mail)->save();
        } elseif (is_array($to)) {
            if (count($to) == 0) {
                throw new \Exception("'To' array is empty.");
            } else {
                foreach ($to as $email) {
                    $new_instance->newInstance($mail)->setAttribute('to', $email)->save();
                }
                return true;
            }
        } else {
            throw new \Exception("'To' needs to be a string or array.");
        }
    }

}