<?php
class FormerCommon{
  
  function getPaddedRange($start, $end){
    $result = array_map(function($el){ return str_pad($el, 2, '0', STR_PAD_LEFT); }, range($start, $end));
    return array_combine($result, $result);
  }
  
  function pad($str){
    return str_pad($str, 2, '0', STR_PAD_LEFT);
  }
  
  function  getMonths(){
    return $this->getPaddedRange(1, 12);
  }

  function getFullMonths(){
    $rarr = array();
    foreach(range(1, 12) as $num)
      $rarr[ $this->pad( $num ) ] = t( "month.$num" );
    return $rarr;
  }
  

    function getDays()
    {
        $days = $this->getPaddedRange(1, 31);

        if (!phive('Localizer')->doExtraLocalization()) {
            return $days;
        }

        $day_symbol = t( "day.symbol");
        return array_map(
            function ($day) use ($day_symbol) {
                return $day . $day_symbol;
            },
            $days
        );

    }
  
  function getWeekDays(){
    return $this->getPaddedRange(1, 7);
  }
  
  function getWeeks(){
    return $this->getPaddedRange(1, 53);
  }
  
  function monthShort(){
    return array('01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr', '05' => 'May', '06' => 'Jun', '07' => 'Jul', '08' => 'Aug', '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dec');
  }
  
  function getDaysInMonth($year, $month){
    $end_day = date('t', strtotime("$year-$month"));
    return $this->getPaddedRange(1, $end_day);
  }
  
  function getYears($deduct = 0, $yearDesc = false)
  {
      if($yearDesc) {
          $years = range(date('Y')-$deduct, 1900);
      }else{
          $years = range(1900, date('Y')-$deduct);
      }

      $years = array_combine($years, $years);

      if (!phive('Localizer')->doExtraLocalization()) {
          return $years;
      }

      $year_symbol = t( "year.symbol");
      return array_map(
          function ($year) use ($year_symbol) {
              return $year . $year_symbol;
          },
          $years
      );
  }
  
  function getYearsFrom($start = 2000){
    return array_combine(range($start, date('Y')), range($start, date('Y')));
  }
  
  function getYearRange($start, $num = 5){
    $start = empty($start) ? date('Y') : $start;
    $end = $start + $num;
    return array_combine(range($start, $end), range($start, $end));
  }

  function getDaysInYear($year){
    $end = date("z", mktime(0,0,0,12,31,$year)) + 1;
    return range(1, $end);
  }
  
  function getYearMonths($start_year, $numeric_months = false, $end_date = ''){
    $current   = empty($end_date) ? date('Y-m') : date('Y-m', strtotime($end_date));
    $end_year  = empty($end_date) ? date('Y') : date('Y', strtotime($end_date));
    $rarr      = array();
    if(!is_numeric($start_year))
      $start_year = date('Y', strtotime($start_year));
    $months    = $numeric_months ? $this->getMonths() : $this->monthShort(); 
    foreach(range($start_year, $end_year) as $year){
      foreach($months as $mnum => $m){
	$rarr["$year-$mnum"] = $numeric_months ? "$year-$m" : t($m). " $year";
	if($current == "$year-$mnum")
	  return $rarr;
      }
    }
    return $rarr;
  }
}
