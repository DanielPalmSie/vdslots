<?php
/**
 * Created by PhpStorm.
 * User: iondum
 * Date: 24/01/18
 * Time: 12:41
 */

namespace App\Models;

use App\Extensions\Database\FModel;
use Carbon\Carbon;
use Silex\Application;

/**
 * @property mixed type
 * @property mixed target_id
 * @property mixed status
 * @property mixed file
 * @property mixed data
 * @property mixed schedule_time
 *
 * @method static create(array $attributes = [])
 */
class Export extends FModel
{
    const STATUS_FAILED = 0;
    const STATUS_PROGRESS = 1;
    const STATUS_FINISHED = 2;
    const STATUS_SCHEDULED = 3;

    const MAX_ATTEMPTS = 3;

    /**
     * NOTE: AdminMainController holds the permissions for folders used here
     */
    const EXPORT_MAP
        = [
            'contacts-list' => [
                'method' => 'contactsList',
                'folder' => 'contacts_list',
                'permission' => 'messaging.contacts.export',
                'allow_multiple_exports' => true
            ],
            'offline-campaigns' => [
                'method' => 'offlineCampaigns',
                'folder' => 'offline_campaigns',
                'permission' => 'messaging.offline-campaigns.export',
                'allow_multiple_exports' => false,
                'export_text' => 'Generate users list',
                'download_text' => 'Download users list'
            ],
            'offline-campaigns-get-excluded' => [
                'method' => 'offlineCampaignsGetExcluded',
                'folder' => 'offline_campaigns_get_excluded',
                'permission' => 'messaging.offline-campaigns.export',
                'allow_multiple_exports' => false,
                'export_text' => 'Generate list of excluded users',
                'download_text' => 'Download list of excluded users'
            ],
            'all_user_data' => [
                'method' => 'allUserData',
                'folder' => 'all_user_data',
                'permission' => 'user.account.all_user_data.export',
                'allow_multiple_exports' => true
            ]
        ];

    public $timestamps = true;

    protected $table = 'export';

    /**
     * @param null|Application $app
     *
     * @return mixed
     */
    public function getFile($app = null)
    {
        if (!$app) {
            // used in console commands
            return implode("/", [
                    env('BASE_URL'),
                    env('BO_BASE_URL'),
                    "storage",
                    self::EXPORT_MAP[$this->type]['folder'],
                    $this->file,
                    ''
                ]);
        }
        return $app['url_generator']->generate('download-file', [
            'folder' => self::EXPORT_MAP[$this->type]['folder'],
            'file' => $this->file
        ]);
    }

    /**
     * Get path of the folder where the export will be saved
     *
     * @return string
     */
    public function getTargetFolderPath()
    {
        $target = self::EXPORT_MAP[$this->type];
        $file_path = getenv('STORAGE_PATH') . "/{$target['folder']}/";

        // make sure the folder exists and has the right permissions
        if (!file_exists($file_path)) {
            mkdir($file_path, 0777, true);
        }

        return $file_path;
    }

    /**
     * Get the name of the method configured to take care of this export
     *
     * @return mixed
     */
    public function getHandlerName()
    {
        return self::EXPORT_MAP[$this->type]['method'];
    }

    /**
     * @return bool
     */
    public function shouldProcess()
    {
        if (!empty(env('TEST_EXPORT'))) {
            return true;
        }
        // schedule time is before now
        $res = $this->status == self::STATUS_SCHEDULED
            && Carbon::now()->gt(Carbon::parse($this->schedule_time));

        $shouldRetry = $this->status == self::STATUS_FAILED && $this->attempts < self::MAX_ATTEMPTS;
        // check if status is progress
        $res = $res || $this->status == self::STATUS_PROGRESS || $shouldRetry;

        return $res;
    }

    /**
     * @return string
     */
    public function getStatus() {
        return [
            0 => 'failed',
            1 => 'in progress',
            2 => 'finished',
            3 => 'scheduled'
        ][$this->status];
    }

    /**
     * @return bool
     */
    public function failed() {
        return self::STATUS_FAILED == $this->status;
    }

    /**
     * @return bool
     */
    public function hasReachedMaxAttempts(): bool
    {
        return $this->attempts == static::MAX_ATTEMPTS;
    }


    /**
     * @param $campaign
     * @param $type
     * @param null|string|array $status
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|null
     */
    public static function lastExport($campaign, $type, $status = null) {
        $export = self::query()
            ->where('target_id', '=', $campaign)
            ->where('type', '=', $type);

        if ($status !== null) {
            $export->whereIn('status', is_array($status) ? $status : [$status]);
        }

        return $export->orderBy('id', 'desc')->first();
    }

}
