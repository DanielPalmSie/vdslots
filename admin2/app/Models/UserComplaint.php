<?php

namespace App\Models;

use App\Extensions\Database\FModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Valitron\Validator;

class UserComplaint extends FModel
{
    public const STATUSES = [
        0 => 'Deactivated',
        1 => 'Active'
    ];

    public const TYPES = [
        1 => [
            'name' => 'Transactions',
            'icon' => 'money-bill-alt'
        ],
        2 => [
            'name' => 'Missing Win',
            'icon' => 'money-bill-alt'
        ],
        3 => [
            'name' => 'Game Issue',
            'icon' => 'search'
        ],
        4 => [
            'name' => 'Game Integrity',
            'icon' => 'search'
        ],
        5 => [
            'name' => 'Bonuses/Rewards',
            'icon' => 'search'
        ],
        6 => [
            'name' => 'Responsible Gaming',
            'icon' => 'search'
        ],
        7 => [
            'name' => 'Verification',
            'icon' => 'search'
        ],
        8 => [
            'name' => 'Account status',
            'icon' => 'search'
        ],
        9 => [
            'name' => 'Sportsbook',
            'icon' => 'search'
        ],
    ];

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users_complaints';

    protected $guarded = ['id'];

    protected $fillable = ['user_id', 'ticket_id', 'actor_id', 'ticket_url', 'type', 'status', 'created_at'];

    protected function rules()
    {
        return [
            'default' => [
                'required' => [['ticket_id'], ['ticket_url'],['type'],['user_id'], ['actor_id']]
            ]
        ];
    }

    protected function labels()
    {
        return [
            'ticket_id' => 'Ticket ID',
            'ticket_url' => 'Ticket URL',
            'type' => 'Complaint Type',
        ];
    }

    /**
     * @return HasOne
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function actor(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'actor_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(UserComplaintResponse::class, 'complaint_id', 'id');
    }

    public function getTypeName(): string
    {
        return self::getType($this->type)['name'];
    }

    public function getTypeIcon(): string
    {
        return self::getType($this->type)['icon'];
    }

    public function getStatus(): string
    {
        $status_map = self::STATUSES;

        return $status_map[$this->status] ?? 'None';
    }

    public static function getType(int $id): array
    {
        $types_map = self::TYPES;

        return $types_map[$id] ?? ['name' => 'None', 'icon' => ''];
    }

    public function validate()
    {
        if (parent::validate() === false) {
            return false;
        }

        $validator = new Validator($this->getAttributes());
        $validator->labels($this->labels());

        if (!$validator->validate()) {
            $this->overrideErrors($validator->errors());
            return false;
        }

        return true;
    }

    /**
     * @return UserComplaintResponse|null
     */
    public function getLastResponse(): ?UserComplaintResponse
    {
        return $this->responses()->latest()->first();
    }

    /**
     * @return Collection
     */
    public function getComments(): Collection
    {
        return UserComment::where('tag', UserComment::TYPE_COMPLAINT)
            ->where('foreign_id', $this->id)
            ->where('user_id', $this->user_id)
            ->latest()
            ->get();
    }
}
