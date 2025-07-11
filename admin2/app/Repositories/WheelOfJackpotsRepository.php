<?php

namespace App\Repositories;

use App\Classes\DateRange;
use App\Extensions\Database\Builder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\ReplicaFManager as ReplicaDB;
use App\Models\JackpotWheelLog;
use App\Models\User;
use Silex\Application;
use App\Models\JackpotWheelSlice;
use App\Models\Jackpot;
use App\Models\TrophyAwards;


class WheelOfJackpotsRepository
{
    /** @var Application $app */
    protected $app;


    /**
     * Get all awards in format for select in x-editable plugin
     *
     * @return array    Format: [['value' => 1, 'text' => 'award 1'], ['value' => 2, 'text' => 'award 2']]
     */
    public function getAwardsForSelect()
    {
        $awards = TrophyAwards::all();
        
        $awards_for_select = [];
        foreach ($awards as $award) {
            $awards_for_select[] = ['value' => $award->id, 'text' => $award->alias];
        }

        return $awards_for_select;
    }

    /**
     * Get the available awards with filtering for Select2 plugin
     * The return array is important to be with this format
     *
     * @param   int     $wheel_id
     * @param 	string  $filter
     *
     * @return  array   Format: [['id' => 1, 'text' => 'award 1'], ['id' => 2, 'text' => 'award 2']]
     */
    public function getAvailableAwardsForSelectWithFilter($wheel_id, $filter = null)
    {
        // $selected_awards_ids = $this->getAwardIdsFromWheelSlices($wheel_id);

        // we don't want already selected awards, and we filter out the "ticket for the wheel" as an award.
	//$filtered_awards = new TrophyAwards();

        $filtered_awards = [];
        if(!empty($filter)) {
	    $filtered_awards = TrophyAwards::where('alias', 'LIKE', "%$filter%")->get();            
        }

        return $this->formatForXEditablePlugin($filtered_awards);
    }
    

    /**
     * Get all jackpots that are not connected to an award
     */
    public function getAvailableJackpots()
    {
        $used_jackpots = DB::select("SELECT jackpot_id FROM jackpot_wheel_awards WHERE jackpot_id IS NOT NULL");

        $used_jackpot_ids = [];
        foreach ($used_jackpots as $used_jackpot) {
            $used_jackpot_ids[] = $used_jackpot->jackpot_id;
        }

        $jackpots = Jackpot::whereNotIn('id', $used_jackpot_ids)->get();

        return $jackpots;
    }

    /**
     * Get the total probability for a specific wheel
     *
     * @param   int     $wheel_id
     * @return  string
     */
    public function getTotalProbability($wheel_id)
    {

        // get all slices for this wheel, and calculate total probability
        $slices = JackpotWheelSlice::where('wheel_id', $wheel_id)->get();

        $total_probability = 0;
        foreach ($slices as $slice) {
            //$total_probability = bcadd($total_probability, $slice->probability, 4);
            $total_probability += $slice->probability;
        }
        
        return $total_probability;
    }


    public function allSlicesHaveAwards($wheel_id)
    {
        $slices = JackpotWheelSlice::where('wheel_id', $wheel_id)->get();
        $all_slices_have_awards = true;
        foreach ($slices as $slice) {
            if(empty($slice->award_id)) {
                $all_slices_have_awards = false;
            }
        }

        return $all_slices_have_awards;
    }

    public function canActivateWheel($wheel_id)
    {
        $total_probability = $this->getTotalProbability($wheel_id);
        $all_slices_have_awards = $this->allSlicesHaveAwards($wheel_id);
//        $wheels_activated = $this->getWheelsActivated();
//        if($total_probability == '10000000' && $all_slices_have_awards && empty($wheels_activated)) {
        if($total_probability == '10000000' && $all_slices_have_awards) {
            return true;
        }

        return false;
    }

    public function getAwardTypes()
    {
        return [['name' => 'freespins'], ['name' => 'freecash'], ['name' => 'jackpot']];
    }

    public function getAwardName($type, $prize)
    {
        switch ($type) {
            case "freespins":
                return "$prize Free Spins";
                break;
            case "freecash":
                return "Free Cash $prize";
                break;
            default:
                return '';
        }
    }

    public function canDeleteAward($award_id)
    {
        $slices_with_awards = JackpotWheelSlice::where('award_id', $award_id)->first();

        if(empty($slices_with_awards)) {
            return true;
        }

        return false;
    }

    /**
     * Gets the total contribution of all jackpots
     */
    public function getTotalContribution()
    {
        $total_contribution = DB::select("SELECT SUM(contribution_share) AS total FROM jackpots");
        return $total_contribution[0]->total;
    }


    /**
     * Returns a Builder object with the query to get all the wheel of jackpots log.
     *
     * @param int $wheel_id
     * @param DateRange|null $date_range
     * @return Builder|\Illuminate\Database\Query\Builder
     */
    public function getWheelLog($wheel_id, $date_range = null)
    {
        $wheel_log = DB::table('jackpot_wheel_log AS jwl')
            ->selectRaw('jwl.*, ta.description AS reward_desc')
            ->where('jwl.wheel_id', $wheel_id)
            ->join('trophy_awards AS ta', 'ta.id', '=', 'jwl.win_award_id');

        if (!empty($date_range)) {
            $wheel_log->whereBetween('jwl.created_at', $date_range->getWhereBetweenArray());
        }

        return $wheel_log;
    }


    /**
     * Returns an Array with the data from wheel of jackpots log.
     * Special note: slices is an array composed by [['award_id', 'probability', 'sort_order'], [..]]
     *
     * @param log_id $logId
     * @param log_id $userId
     * @return array Builder
     * @throws \Exception
     */
    public function getWheelLogByAction($logId , $userId)
    {
        $wheelLog = DB::table("jackpot_wheel_log")
                        ->where("id" , $logId)
                        ->where("user_id" , $userId)
                        ->get()
                        ->toArray();
        return $wheelLog;
    }
    
    
    
    /**
     * Gets all the action of the wheel log
     */
    public function getWheelsActivated()
    {
        $activeWheels = DB::select("SELECT * FROM jackpot_wheels WHERE active = 1 ");
        return $activeWheels;
    }

    /**
     * Return all the existing award_id from a wheel slices
     *
     * @param $wheel_id - id of the wheel
     * @return array of ids
     */
    public function getAwardIdsFromWheelSlices($wheel_id) {
        $selected_awards = DB::select("SELECT award_id FROM jackpot_wheel_slices WHERE wheel_id = {$wheel_id}");

        $selected_awards_ids = [];
        foreach ($selected_awards as $object) {
            // award_id now is a coma separated list that need to be splitted by ","
            $splitted_award_ids = explode(',',$object->award_id);
            foreach($splitted_award_ids as $award_id) {
                $selected_awards_ids[] = $award_id;
            }
        }

        return $selected_awards_ids;
    }

    /**
     * I just need to format the data to have this structure
     * ['id'=>ID, 'text'=>ALIAS]
     *
     * @param $awards
     * @return array
     */
    public function formatForXEditablePlugin($awards){
        $awards_for_select = [];

        foreach ($awards as $award) {
            if($award->id && $award->alias) {
                $awards_for_select[] = ['id' => $award->id, 'text' => $award->alias];
            }
        }

        return $awards_for_select;
    }

    public function getOrderedSlices($wheel) {
        return $wheel->slices()->orderBy('sort_order', 'asc')->get();
    }


    /**
     * @param DateRange $date_range
     * @param User $user
     * @param string $selected_wheel
     * @return mixed
     */
    public function getWheelHistory(DateRange $date_range, User $user, string $selected_wheel = 'all' ) {
        $qb = ReplicaDB::table("jackpot_wheel_log", replicaDatabaseSwitcher(true))
            ->select(
            'id',
            'user_id',
            'wheel_id',
            'created_at',
            'win_award_id'
        )
            ->where([
                ['user_id', '=', $user->id],
            ]);
        if ($selected_wheel && $selected_wheel != 'all') {
            $qb->where([
                ['wheel_id', '=', $selected_wheel],
            ]);
        }
        $data = $qb
            ->whereBetween(
                'jackpot_wheel_log.created_at', [$date_range->getStart('timestamp'), $date_range->getEnd('timestamp')]
            )->get()->toArray();

        return $data;
    }
}

