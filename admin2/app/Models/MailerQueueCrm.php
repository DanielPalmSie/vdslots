<?php
/**
 * Created by PhpStorm.
 * User: iondum
 * Date: 21/12/17
 * Time: 15:06
 */

namespace App\Models;

use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\FModel;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;

class MailerQueueCrm extends FModel
{
    protected $table = 'mailer_queue_crm';
    protected $primaryKey = 'id';

    public function getPrimaryKey()
    {
        return $this->getKeyName();
    }

    /**
     * @param $limit
     * @param null $priority - here to keep the method compatible with mailer_queue
     * @return Collection
     * @throws Exception
     */
    public function getPrioritized($limit, $priority = null)
    {
        return DB::table($this->table)->limit($limit)->get()->map(function ($item) {
            $item['priority'] = 3;
            return $item;
        });
    }

    public static function formatEmailFields($email)
    {
        try {
            $replacers = json_decode($email['replacers'], true);
        } catch (Exception $e) {
            $replacers = [];
        }

        return [
            'from_name' => $email['from_name'],
            'from' => $email['from'],
            'subject' => $email['subject'],
            'html' => $email['html'],
            'text' => $email['text'],
            'to_name' => $email['to_name'] ?? "",
            'to' => $email['to'],
            'replacers' => $replacers,
            'campaign_id' => $email['messaging_campaign_id'],
            'description' => Carbon::now()->toDateString(),
        ];
    }

    /**
     *
     * $result = [
     *  "results" => [
     *      "total_rejected_recipients" => 0,
     *      "total_accepted_recipients" => 1,
     *      "id" => int
     *  ]
     * ]
     *
     * @param $item
     * @param $result
     * @throws Exception
     */
    public function postSend($item, $result)
    {
        if (is_array($result) && array_has($result, 'results')) {
            DB::table('messaging_campaign_users')
                ->where('user_id', $item['user_id'])
                ->where('campaign_id', $item['messaging_campaign_id'])
                ->update([
                    "message_id" => $result['results']['id'],
                    "status" => $result['results']['total_accepted_recipients'] > 0 ? 'OK' : 'NOK',
                    "reject" => "",
                    "html" => $item['html'],
                    "text" => $item['text'],
                    "subject" => $item['subject']
                ]);
        }
    }
}
