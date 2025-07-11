<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 8/9/16
 * Time: 12:19 PM
 */

namespace App\Classes;


use Carbon\Carbon;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class DateRange
{
    /** @var  Carbon $start */
    private $start;

    /** @var  Carbon $end */
    private $end;

    /** @var  Application $app */
    public $app;

    const DEFAULT_TODAY = 'day';

    const DEFAULT_YESTERDAY = 'yesterday';

    const DEFAULT_DAY_BEFORE_YESTERDAY = 'daybeforeyesterday';

    const DEFAULT_CUR_MONTH = 'month';

    const DEFAULT_PREV_MONTH = 'preMonth';

    const DEFAULT_LAST_2_DAYS = '2days';

    const DEFAULT_LAST_30_DAYS = '30days';

    const DEFAULT_LAST_6_MONTHS = '6months';

    const DEFAULT_LAST_YEAR = '1year';

    const DEFAULT_CUR_WEEK = 'curWeek';

    const DEFAULT_PAST_WEEK = 'preWeek';

    const DEFAULT_EMPTY = 'empty';

    const DEFAULT_LAST_7_DAYS = '7days';

    /**
     * DateRange constructor.
     * @param Carbon|string $start
     * @param Carbon|string|null $end
     */
    public function __construct($start, $end = null)
    {
        if (is_string($start)) {
            $this->start = Carbon::parse($start)->startOfDay();
            $this->end = empty($end) ? null : Carbon::parse($end)->endOfDay();
        } else {
            $this->start = $start;
            $this->end = empty($end) ? null : $end->endOfDay();
        }
    }

    public static function fromRequest(Request $request, $default = self::DEFAULT_TODAY, $start_key = 'start_date', $end_key = 'end_date')
    {
        return new self($request->get($start_key, self::initDate($default, 'start')), $request->get($end_key, self::initDate($default, 'end')));
    }

    public static function rangeFromRequest(Request $request, $default = self::DEFAULT_TODAY, $key = 'date-range')
    {
        if (empty($request->get($key))) {
            return new self(self::initDate($default, 'start'), self::initDate($default, 'end'));
        } else {
            return new self(explode(' - ', $request->get($key))[0], explode(' - ', $request->get($key))[1]);
        }
    }

    public static function rangeFromRawDate($range, $default = self::DEFAULT_TODAY)
    {
        if (empty($range)) {
            return new self(self::initDate($default, 'start'), self::initDate($default, 'end'));
        } else {
            return new self(explode(' - ', $range)[0], explode(' - ', $range)[1]);
        }
    }

    public function validate(Application $app = null, $months = 6)
    {
        if (empty($this->getStart()) && empty($this->getEnd())) {
            return true;
        } elseif ($this->start > $this->end) {
            $this->storeErrorInSession($app, "Start date [{$this->getStart('date')}] cannot be greater than end date [{$this->getEnd('date')}].");
            $this->start = $this->end;
            return false;
        } elseif ($this->start->diffInMonths($this->end->copy()->addDay()) > $months) {
            $this->start = $this->end->copy()->subMonth($months);
            $this->storeErrorInSession($app, "Date range must be lower than $months months. Start date set to {$this->getStart('date')}");
            return false;
        } else {
            return true;
        }
    }

    public function getWhereBetweenArray($format = 'timestamp')
    {
        return [$this->getStart($format), $this->getEnd($format)];
    }

    /**
     * @param null|string $format
     * @return Carbon|string
     */
    public function getStart($format = null)
    {
        return $this->getDate('start', $format);
    }

    /**
     * @param null|string $format
     * @return Carbon|string
     */
    public function getEnd($format = null)
    {
        return empty($this->end) ? null : $this->getDate('end', $format);
    }

    public function getRange($format = null)
    {
        if (empty($this->start) && empty($this->end)) {
            return null;
        }
        return $this->getStart($format) . ' - ' . $this->getEnd($format);
    }

    /**
     * TODO refactor this
     * @param $default
     * @param $type
     * @return mixed|DateRange
     */
    private static function initDate($default, $type)
    {
        $now = Carbon::now();
        if ($default == self::DEFAULT_TODAY) {
            return $type == 'start' ? $now->startOfDay() : $now->endOfDay();
        } elseif ($default == self::DEFAULT_CUR_MONTH) {
            return $type == 'start' ? $now->startOfMonth() : $now->endOfMonth();
        } elseif ($default == self::DEFAULT_PREV_MONTH) {
            return $type == 'start' ? $now->subMonth()->startOfMonth() : $now->subMonth()->endOfMonth();
        } elseif ($default == self::DEFAULT_CUR_WEEK) {
            return $type == 'start' ? $now->startOfWeek() : $now->endOfWeek();
        } elseif ($default == self::DEFAULT_PAST_WEEK) {
            return $type == 'start' ? $now->subWeek()->startOfWeek() : $now->subWeek()->endOfWeek();
        } elseif ($default == self::DEFAULT_LAST_2_DAYS) {
            return $type == 'start' ? $now->subDays(1)->startOfDay() : $now->endOfDay();
        } elseif ($default == self::DEFAULT_LAST_30_DAYS) {
            return $type == 'start' ? $now->subDays(30)->startOfDay() : $now->endOfDay();
        } elseif ($default == self::DEFAULT_LAST_6_MONTHS) {
            return $type == 'start' ? $now->subMonth(6)->startOfDay() : $now->endOfDay();
        } elseif ($default == self::DEFAULT_LAST_YEAR) {
            return $type == 'start' ? $now->subYear()->startOfDay() : $now->endOfDay();
        } elseif ($default == self::DEFAULT_DAY_BEFORE_YESTERDAY) {
            return $type == 'start' ? $now->subDays(2)->startOfDay() : $now->subDays(2)->endOfDay();
        } elseif ($default == self::DEFAULT_YESTERDAY) {
            return $type == 'start' ? $now->subDays(1)->startOfDay() : $now->subDays(1)->endOfDay();
        } elseif ($default == self::DEFAULT_LAST_7_DAYS) {
            return $type == 'start' ? $now->subDays(6)->startOfDay() : $now->endOfDay();
        } elseif ($default == self::DEFAULT_EMPTY) {
            return null;
        } else {
            return $now;
        }
    }

    private function getDate($name, $format)
    {
        if (empty($format)) {
            return $this->{$name};
        } elseif ($format == 'date') {
            return $this->{$name}->format('Y-m-d');
        } elseif ($format == 'timestamp') {
            return $this->{$name}->format('Y-m-d H:i:s');
        } else {
            return $this->{$name}->format($format);
        }
    }

    private function storeErrorInSession(Application $app, $message)
    {
        if (!is_null($app)) {
            $app['flash']->add('danger', $message);
        }
    }

    public static function monthRangeFromRequest(Request $request, $default = self::DEFAULT_PREV_MONTH, $key_start = 'month-range-start', $key_end = 'month-range-end')
    {
        if (empty($request->get($key_start)) || empty($request->get($key_end))) {
            return new self(self::initDate($default, 'start'), self::initDate($default, 'end'));
        } else {
            return new self($request->get($key_start), $request->get($key_end));
        }
    }


}
