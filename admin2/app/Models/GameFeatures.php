<?php
/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2015.11.16.
 * Time: 16:36
 */

namespace App\Models;

use App\Extensions\Database\FModel;

class GameFeatures extends FModel
{
    protected $table = 'game_features';

    public $timestamps = false;

    public static $types = ['info' => 'Info', 'feature' => 'Feature'];
    public static $sub_types = ['' => 'None', 'free_spin' => 'Free Spin'];

    protected $fillable = [
        'game_id',
        'type',
        'sub_type',
        'name',
        'value'
    ];

    public static function getDefaults() {

        $defaults = [
            'Autospin feature' => 'yes',
            'Bet levels' => 'no',
            'Bonus Games' => 'yes',
            'Bonus games that are longer' => 'no',
            'Bonus games that are short' => 'yes',
            'Bonus games where you can choose between different options' => 'yes',
            'Cloning Wilds' => 'no',
            'Coin Values' => '0.01 , 0.02, 0.05, 0.10, 0.25, 0.50, 1',
            'Collect Items for Main Feature' => 'no',
            'Default bet' => '30',
            'Every Free Spin is a Win' => 'no',
            'Expanding scatters in free spins' => 'no',
            'Expanding Wilds' => 'no',
            'Extra Free Spins Awarded' => 'yes',
            'Extra wilds' => 'no',
            'Extra wilds added on free spins' => 'no',
            'Free spins mode that can retrigger more free spins' => 'yes',
            'Free spins mode where you can choose between different options' => 'no',
            'Free spins mode with expanding wilds' => 'no',
            'Free spins mode with extra wilds' => 'no',
            'Free spins mode with multipliers' => 'no',
            'Free spins mode with Respin' => 'no',
            'Gamble feature' => 'no',
            'Hit Frequency % any win' => '',
            'Hit Frequency % in to bonus mode' => '',
            'Hit Frequency % in to feature' => '',
            'Hit Frequency % in to free spins' => '',
            'Increasing Multiplier in Free Spins' => 'no',
            'Jackpot Trigger Type' => 'no',
            'Main Feature Triggers on Fixed Reels' => 'no',
            'Main Feature Triggers on Payline' => 'yes',
            'Manually select line numbers' => 'yes',
            'Max bet function' => 'yes',
            'Maximum Bet per line' => '100',
            'Maximum win per bet-line' => '',
            'Minimum Bet per line' => '1',
            'Minimum win per bet-line' => '',
            'No. of Jackpot Tiers' => '0',
            'Player can Choose a Feature' => 'no',
            'Quickspin function' => 'no',
            'Random Multiplier in Free Spins' => 'no',
            'Random wild reels in base game' => 'no',
            'Reel animation type' => 'rolling',
            'Reels' => '5',
            'Replicating wilds' => 'no',
            'Scatter symbol is also wild' => 'no',
            'Shifting Multiplier in Free Spins' => 'no',
            //'Slot Themes' => '3-D, Adventure, Animals, History, Jungle',
            'Slots released' => '2014',
            'Slots that are on mobile and desktop' => 'yes',
            'Slots with bright Colours' => 'no',
            'Slots with Dark Colours' => 'yes',
            'Stacked Wilds' => 'no',
            'Sticky wilds in free spins' => 'no',
            'Transferring wilds' => 'no',
            'Walking Wilds' => 'no',
            'Wilds' => 'no',
            'Wilds appear on reels' => 'no',
            'Wilds with Multipliers' => 'no'
        ];

        return $defaults;
    }
}
