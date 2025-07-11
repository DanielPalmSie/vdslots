<?php

namespace App\Models;

use App\Extensions\Database\FModel;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UserComplaintResponse extends FModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users_complaints_responses';

    protected $guarded = ['id'];

    protected $fillable = ['user_id', 'complaint_id', 'actor_id', 'type', 'description'];

    public const TYPES = [
        1 => 'Intervention',
        2 => 'Follow up',
        3 => 'Investigation',
    ];

    protected function rules()
    {
        return [
            'default' => [
                'required' => [['user_id'], ['complaint_id'], ['actor_id'], ['type'], ['description']]
            ]
        ];
    }

    protected function labels()
    {
        return [
            'type' => 'Response Type',
            'description' => 'Response description',
        ];
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function actor(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'actor_id');
    }

    public function complaint(): HasOne
    {
        return $this->hasOne(UserComplaint::class, 'id', 'complaint_id');
    }

    public function getTypeName(): string
    {
        return self::TYPES[$this->type] ?? 'None';
    }
}
