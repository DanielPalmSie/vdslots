<?php

namespace Tests\Unit\Modules\CasinoBonuses;

require_once __DIR__ . '/../../../../../phive/phive.php';

use CasinoBonuses;
use DateTime;
use PHPUnit\Framework\TestCase;

class CasinoBonusesTest extends TestCase
{

    private CasinoBonuses $casinoBonuses;

    protected function setUp(): void
    {
        parent::setUp();
        $this->casinoBonuses = new CasinoBonuses();
    }

    public function testNextOccurrenceForAllDays()
    {
        $weekdays = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday'
        ];

        foreach ($weekdays as $targetDay => $targetName) {

            $welcome_bonus_entry = [
                'auto_activate_bonus_send_out_time' => '15:00:00',
                'auto_activate_bonus_day' => $targetDay,
                'auto_activate_bonus_period' => 3,
            ];

            $date = new DateTime();
            $time = '15:00:00';

            for ($i = 1; $i <= $welcome_bonus_entry['auto_activate_bonus_period']; $i++) {

                $expectedDate = clone $date;
                $expectedDate->modify('next ' . $targetName);
                $expectedDate->setTime(15, 0, 0);

                $nextDate = $this->invokeMethod($this->casinoBonuses, 'getNextOccurrenceDateTime', [$date, $targetDay, $time]);

                if (is_string($nextDate)) {
                    $nextDate = new DateTime($nextDate);
                }

                if ($nextDate <= $date) {
                    $nextDate->modify('+7 days');
                }

                $this->assertEquals(
                    $expectedDate->format('Y-m-d H:i:s'),
                    $nextDate->format('Y-m-d H:i:s'),
                    "Failed for target day: $targetName ($targetDay) -> Expected Next Occurrence: " . $expectedDate->format('Y-m-d H:i:s')
                );

                $date = clone $nextDate;
            }
        }
    }

    protected function invokeMethod(&$object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
