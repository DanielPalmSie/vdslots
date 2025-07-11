<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Models\Config;

class SetBannedBonusGames extends Seeder
{
    public function up()
    {
        DB::shBeginTransaction(true);

        $bannedGames = [
            'Lil Devil',
            'Royal Mint',
            '10001 Nights',
            '10001 Nights MegaWays',
            'The Creepy Carnival',
            'Crab Trap',
            'Cursed Treasure',
            'Finn and the Candy Spin',
            'Thrill To Grill',
            'Codex of Fortune',
            'Cornelius',
            'EggOMatic',
            'Finn and the Swirly Spin',
            'jinglespin',
            'knight rider',
            'letitburn',
            'Narcos',
            'Rage of the Seas',
            'Reel Rush 2',
            'Robin Hood: Shifting Riches',
            'Rome: The Golden Age',
            'Scudamore\'s Super Stakes',
            'Serengeti Kings',
            'Street Fighter II: The World Warrior Slot',
            'Super Striker',
            'Wilderland',
            'The Wish Master',
            'Wonders of Christmas',
            'Baker\'s Treat',
            'Cash Vandal',
            'Cloud Quest',
            'European Roulette Pro',
            'Eye of the Kraken',
            'Gemix',
            'Gemix 2',
            'Gerard\'s Gambit',
            'Gunslinger: Reloaded',
            'HammerFall',
            'Mahjong 88',
            'Pearls of India',
            'Rage to Riches',
            'Rich Wilde and the Pearls of Vishnu',
            'Sea Hunter',
            'Sweet Alchemy',
            'Tower Quest',
            'Viking Runecraft',
            'Viking Runecraft Bingo',
            'Hellcatraz',
            'Book of 99',
            'Titan Strike',
            'Jurassic Party',
            'Marching Legions',
            'Sultan Spins',
            'Feather Fury - RELEASED SOON',
            'Lucky McGee\'s SuperSlice Swirl',
            'Joker & the Thief',
            'Joker & the Thief 2',
            'Forge of Hephaestus',
            'The Wild Bunch',
            'Yatzy'
        ];

        $config = new Config();
        $config->fill([
            'config_name'  => 'banned-bonus-games',
            'config_tag'   => 'games',
            'config_value' => implode("\n", $bannedGames),
            'config_type'  => json_encode([
                "type"                => "template",
                "delimiter"           => "",
                "next_data_delimiter" => "\n",
                "format"             => "<:String>"
            ]),
        ]);

        $config->save();

        DB::shCommit(true);
    }

    public function down()
    {
        Config::where('config_name', 'banned-bonus-games')->delete();
    }
}
