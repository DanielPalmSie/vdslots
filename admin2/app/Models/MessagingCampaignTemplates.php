<?php

namespace App\Models;

use App\Extensions\Database\FModel;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Valitron\Validator;

class MessagingCampaignTemplates extends FModel
{
    const TYPE_SMS = 1;
    const TYPE_EMAIL = 2;
    const TYPE_WIRAYA = 3;

    const STATUS_ARCHIVED = 1;

    const RECURRING_ONCE = 'one';
    const RECURRING_DAILY = 'day';
    const RECURRING_WEEKLY = 'week';
    const RECURRING_MONTHLY = 'month';

    protected $table = 'messaging_campaign_templates';

    protected $guarded = ['id', 'scheduled_time'];

    public static $supported_types = [
        self::TYPE_SMS,
        self::TYPE_EMAIL,
        self::TYPE_WIRAYA
    ];

    protected function rules()
    {
        return [
            'default' => [
                'required' => [['template_type'], ['template_id'], ['named_search_id']],
            ]
        ];
    }

    public function validate()
    {
        if (parent::validate() === false) {
            return false;
        }

        $validator = new Validator($this->getAttributes());
        $validator->labels($this->labels());

        if ($this->recurring_type == 'one') {
            try {
                $date = !empty($this->scheduled_time) ? Carbon::parse($this->scheduled_time) : Carbon::parse($this->start_date . ' ' . $this->start_time);
                if ($date->isPast()) {
                    $date = Carbon::parse(Carbon::now());
                }
            } catch (\Exception $e) {
                $date = false;
            }
            if (empty($this->start_time) || empty($this->start_date) || $date === false) {
                $validator->error('scheduled_time', "Scheduled time cannot be empty or date format is wrong.");
            } elseif ($date->isPast()) {
                $validator->error('scheduled_time', "Scheduled time cannot be a past date.");
            }
        } elseif (in_array($this->recurring_type, ['day', 'week', 'month'])) {
            $validator->rule('dateFormat', 'start_time', 'H:i:s');
            $validator->rule('dateFormat', 'recurring_end_date', 'Y-m-d H:i:s');
            $validator->rule('required', 'recurring_end_date');
            if ($this->recurring_type != 'day') {
                $validator->rule('required', 'recurring_days');
            }
            $days_list = !empty($this->recurring_days) ? explode(',', $this->recurring_days) : null;
            if ($this->recurring_type == 'week' && (min($days_list) < 1 || max($days_list) > 7)) {
                $validator->error('recurring_days', "If recurring type is week, days must be between 1 (Monday) and 7 (Sunday)");
            } elseif ($this->recurring_type == 'month') {
                if ((min($days_list) < 1 || max($days_list) > 31)) {
                    $validator->error('recurring_days', "If recurring type is month, days must be between 01 and 31");
                } elseif (in_array('1', $days_list, true) || in_array('2', $days_list, true) || in_array('3', $days_list, true)) {
                    $validator->error('recurring_days', "First days of the month need to be written as 01, 02 and 03.");
                }
            }
        } else {
            $validator->error('recurring_type', "Set as recurring field cannot be empty");
        }

        if (!$validator->validate()) {
            $this->overrideErrors($validator->errors());
            return false;
        } else {
            return true;
        }
    }

    public function fill(array $attributes)
    {
        parent::fill($attributes);

        if ($this->recurring_type == 'one') {
            $date = explode(' ', $attributes['scheduled_time']);
            $this->start_date = $date[0];
            $this->start_time = $date[1];
        }

        if (!empty($this->attributes['scheduled_time'])) {
            unset($this->attributes['scheduled_time']);
        }
    }

    public function generateScheduledTime()
    {
        if (!empty($this->start_date) && !empty($this->start_time)) {
            return $this->start_date . ' ' . $this->start_time;
        } else {
            return null;
        }
    }

    public function getFirstFutureScheduled()
    {
        if ($this->recurring_type == self::RECURRING_ONCE) {
            return $this->generateScheduledTime();
        } elseif ($this->recurring_type == self::RECURRING_DAILY) {
            return Carbon::now()->setTimeFromTimeString($this->start_time)->toDateTimeString();
        } else {
            /** @var Collection $recurring_days */
            $recurring_days = collect(explode(',', $this->recurring_days));
            $recurring_days->sort();
            if ($this->recurring_type == self::RECURRING_MONTHLY) {
                $res = $recurring_days->search(function ($item, $key) {
                    return Carbon::now()->day($item)->setTimeFromTimeString($this->start_time)->isFuture();
                });
                return Carbon::now()->day($recurring_days[$res])->setTimeFromTimeString($this->start_time)->toDateTimeString();
            } elseif ($this->recurring_type == self::RECURRING_WEEKLY) {
                if ($recurring_days->search(date('N')) && Carbon::now()->setTimeFromTimeString($this->start_time)->isFuture()) { //is today and future
                    return Carbon::now()->setTimeFromTimeString($this->start_time)->toDateTimeString();
                } else {
                    $res = $recurring_days->search(function ($item, $key) {
                        return $item > date('N');
                    });
                    return Carbon::now()->next($recurring_days[$res])->setTimeFromTimeString($this->start_time)->toDateTimeString();
                }
            } else {
                return null;
            }
        }
    }

    protected function labels()
    {
        return [
            'named_search_id' => 'Contacts filter list',
            'scheduled_time' => 'Scheduled time',
            'recurring_type' => 'Recurring type',
            'start_time' => 'Recurring start time',
            'recurring_days' => 'Recurring days'
        ];
    }

    public function getRecurringTypeName()
    {
        $map = [
            self::RECURRING_ONCE => "One time only",
            self::RECURRING_DAILY => "Daily",
            self::RECURRING_WEEKLY => "Weekly",
            self::RECURRING_MONTHLY => "Monthly",
        ];

        return isset($map[$this->recurring_type]) ? $map[$this->recurring_type] : ucfirst($this->recurring_type);
    }

    public function campaigns()
    {
        return $this->hasMany(MessagingCampaign::class, 'campaign_template_id', 'id');
    }

    public function namedSearch()
    {
        return $this->hasOne(NamedSearch::class, 'id', 'named_search_id');
    }

    public function smsTemplate()
    {
        return $this->hasOne(SMSTemplate::class, 'id', 'template_id');
    }

    public function emailTemplate()
    {
        return $this->hasOne(EmailTemplate::class, 'id', 'template_id');
    }

    public function template()
    {
        if ($this->isSMS()) {
            return $this->smsTemplate();
        } elseif ($this->isEmail()) {
            return $this->emailTemplate();
        } else {
            throw new \Exception("Template type not supported", 404);
        }
    }

    public function bonusTemplate()
    {
        return $this->hasOne(BonusTypeTemplate::class, 'id', 'bonus_template_id');
    }

    public function voucherTemplate()
    {
        return $this->hasOne(VoucherTemplate::class, 'id', 'voucher_template_id');
    }

    public function isSMS()
    {
        return $this->template_type == self::TYPE_SMS;
    }

    public function isEmail()
    {
        return $this->template_type == self::TYPE_EMAIL;
    }

    public function typeIsSupported()
    {
        return in_array($this->template_type, self::$supported_types);
    }

    public function isBonus()
    {
        return !empty($this->bonus_template_id);
    }

    public function isVoucher()
    {
        return !empty($this->voucher_template_id);
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getTemplateType() {
        switch ($this->template_type) {
            case self::TYPE_EMAIL:
                $type = 'email';
                break;
            case self::TYPE_SMS:
                $type = 'sms';
                break;
            default:
                throw new \Exception("Invalid template type: {$this->template_type}");
        }
        return $type;
    }

}