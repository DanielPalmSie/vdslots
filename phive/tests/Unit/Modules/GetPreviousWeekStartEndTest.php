<?php

namespace Tests\Unit\Modules\DBUserHandler;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../../phive/phive.php';

class GetPreviousWeekStartEndTest extends TestCase
{
    /**
    *Provider with random mondays from 2020 to 2030 
    * @return array[]
    */
    public function randomMondaysProvider() {
        return [

                ['2020-05-25', '2020-05-18', '2020-05-24'],
                ['2021-11-01', '2021-10-25', '2021-10-31'],
                ['2022-12-12', '2022-12-05', '2022-12-11'],
                ['2023-08-21', '2023-08-14', '2023-08-20'],
                ['2024-06-10', '2024-06-03', '2024-06-09'],
                ['2025-02-10', '2025-02-03', '2025-02-09'],
                ['2026-09-14', '2026-09-07', '2026-09-13'],
                ['2027-12-20', '2027-12-13', '2027-12-19'],
                ['2028-04-24', '2028-04-17', '2028-04-23'],
                ['2029-05-07', '2029-04-30', '2029-05-06'],
                ['2030-07-01', '2030-06-24', '2030-06-30'],
                ['2031-03-31', '2031-03-24', '2031-03-30'],
                // Additional 12 test cases
                ['2020-10-19', '2020-10-12', '2020-10-18'],
                ['2021-06-28', '2021-06-21', '2021-06-27'],
                ['2022-02-28', '2022-02-21', '2022-02-27'],
                ['2023-08-07', '2023-07-31', '2023-08-06'],
                ['2024-07-01', '2024-06-24', '2024-06-30'],
                ['2025-12-08', '2025-12-01', '2025-12-07'],
                ['2026-01-05', '2025-12-29', '2026-01-04'],
                ['2027-09-06', '2027-08-30', '2027-09-05'],
                ['2028-03-06', '2028-02-28', '2028-03-05'],
                ['2029-11-26', '2029-11-19', '2029-11-25'],
                ['2030-03-25', '2030-03-18', '2030-03-24'],
                ['2031-12-15', '2031-12-08', '2031-12-14'],
        ];
    }
    
    /** 
    * provider with first monday of the year from 2020 to 2030
    * @return array[]
    */
    public function firstMondaysProvider() {
        return [
                ['2020-01-06', '2019-12-30', '2020-01-05'],
                ['2021-01-04', '2020-12-28', '2021-01-03'],
                ['2022-01-03', '2021-12-27', '2022-01-02'],
                ['2023-01-02', '2022-12-26', '2023-01-01'],
                ['2024-01-01', '2023-12-25', '2023-12-31'],
                ['2025-01-06', '2024-12-30', '2025-01-05'],
                ['2026-01-05', '2025-12-29', '2026-01-04'],
                ['2027-01-04', '2026-12-28', '2027-01-03'],
                ['2028-01-03', '2027-12-27', '2028-01-02'],
                ['2029-01-01', '2028-12-25', '2028-12-31'],
                ['2030-01-07', '2029-12-31', '2030-01-06'],
        ];
    }

    public function testFirstMondaysReturnExpectedDates()
    {
        $datesMock = $this->firstMondaysProvider();
        foreach($datesMock as $date) {
            $mondayDateString = $date[0] . ' 12:50 UTC';
            $monday = date_create($mondayDateString)->format('Y-m-d');
            $mockStartDate = $date[1];
            $mockEndDate = $date[2];
        
            list($sday, $eday) = phive()->getPreviousWeekStartEnd($monday);
            
            $this->assertEquals($mockStartDate, $sday);
            $this->assertEquals($mockEndDate, $eday);
        }
    }
    
    public function testRandomMondaysReturnExpectedDates()
    {
        $datesMock = $this->randomMondaysProvider();
        foreach($datesMock as $data) {
            $mondayDateString = $data[0] . ' 12:50 UTC';
            $monday = date_create($mondayDateString)->format('Y-m-d');
            $mockStartDate = $data[1];
            $mockEndDate = $data[2];
        
            list($sday, $eday) = phive()->getPreviousWeekStartEnd($monday);
            
            $this->assertEquals($mockStartDate, $sday);
            $this->assertEquals($mockEndDate, $eday);
        }
    }
}
