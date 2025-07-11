<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 2016.02.18.
 * Time: 13:00
 */

namespace App\Models;

use App\Extensions\Database\FModel;
use App\Helpers\IPHelper;

class IpLog extends FModel
{
    public $timestamps = false;

    protected $table = 'ip_log';

    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    const TAG_CASH_TRANSACTIONS = 'cash_transactions';

    const TAG_BONUS_ACTIVATING = 'bonus_activating';

    const TAG_WITHDRAWAL = 'pending_withdrawals';

    const TAG_MANUAL_WITHDRAWAL = 'manual_withdrawals';

    const TAG_CANCEL_WITHDRAWAL = 'cancel_withdrawals';

    const TAG_DEPOSITS = 'deposits';

    const TAG_GROUP = 'group';

    const TAG_LOGIN = 'login';

    const TAG_DOCUMENTS = 'documents';

    public function transaction()
    {
        return $this->hasOne(CashTransaction::class, 'id', 'tr_id');
    }

    public function actorUser()
    {
        return $this->hasOne(User::class, 'id', 'actor');
    }

    public function deposit()
    {
        return $this->hasOne(Deposit::class, 'id', 'tr_id');
    }

    /**
     * @param User|int $actor_id
     * @param User|int $target_id
     * @param string $tag
     * @param string $description
     * @param int $transaction_id
     * @return mixed
     */
    public static function logIp($actor_id, $target_id, $tag, $description, $transaction_id = 0)
    {
        if ($target_id instanceof User) {
            $target_id = $target_id->getKey();
        }

        if ($actor_id instanceof User) {
            $actor_username = $actor_id->username;
            $actor_id = $actor_id->getKey();
        } else {
            $actor_username = User::find($actor_id)->username;
        }
        return self::sh($target_id)->create([
            'ip_num' => IPHelper::remIp(),
            'actor' => $actor_id,
            'target' => $target_id,
            'descr' => $description,
            'tag' => $tag,
            'tr_id' => $transaction_id,
            'actor_username' => $actor_username
        ]);
    }

}
