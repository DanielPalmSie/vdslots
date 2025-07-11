<?php

namespace App\Models;

use App\Extensions\Database\Builder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\FModel;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;

class MailerQueue extends FModel
{
    public $table = 'mailer_queue';
    protected $primaryKey = 'mail_id';

    public function getPrimaryKey()
    {
        return $this->getKeyName();
    }

    /**
     * @param $limit
     * @param null $priority
     * @return Collection
     * @throws Exception
     */
    public function getPrioritized($limit, $priority = null)
    {
        /** @var Builder $q */
        $q = DB::table($this->table)->orderBy('priority')->orderBy('time_queued')->limit($limit);
        if (!empty($priority)) {
            $q = $q->where('priority', '=', $priority);
        }

        return $q->get();
    }

    public static function formatEmailFields($email)
    {
        $data = [
            'from_name' => $email['from_name'],
            'from' => $email['from'],
            'subject' => $email['subject'],
            'html' => $email['messageHTML'],
            'text' => $email['messageText'],
            'to_name' => $email['to_name'] ?? "",
            'to' => $email['to'],
            'cc' => $email['cc'],
            'bcc'=> $email['bcc'],
        ];

        if (!empty($email['headers'])) {
            try {
                $headers = json_decode($email['headers'], true);
                $data['campaign_id'] = $headers['campaign_id'];
                $data['description'] = $headers['campaign_description'];
            } catch (Exception $e) {
                $data['campaign_id'] = Carbon::now()->toDateString();
                $data['description'] = Carbon::now()->toDateString();
                //TODO log
            }
        }

        return $data;
    }

    public function postSend($item, $result)
    {
        // nothing to do for mailer_queue
    }
}

