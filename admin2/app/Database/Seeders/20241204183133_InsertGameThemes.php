<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;

class InsertGameThemes extends Seeder
{
    private Connection $connection;
    private string $table;
    private array $newThemes;
    private array $oldThemes;

    protected $schema;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->table = 'themes';
        $this->newThemes = $this->getNewThemes();
        $this->oldThemes = $this->getOldThemes();
        $this->schema = $this->get('schema');
    }

    public function up()
    {
        DB::loopNodes(function (Connection $shardConnection) {
            $shardConnection->table($this->table)->truncate();
        }, true);

        $this->connection->table($this->table)->insert($this->transformArray($this->newThemes));
    }

    public function down()
    {
        DB::loopNodes(function (Connection $shardConnection) {
            $shardConnection->table($this->table)->truncate();
        }, true);

        $this->connection->table($this->table)->insert($this->transformArray($this->oldThemes));
    }

    private function getNewThemes(): array
    {
        return [
            "Adventure",
            "Animals",
            "Branded",
            "Candy",
            "Cartoon",
            "Cash",
            "China",
            "Christmas",
            "Detectives and thieves",
            "Dragons",
            "Easter",
            "Egypt",
            "Explosives",
            "Fairy Tales",
            "Fall",
            "Fantasy",
            "Fishin",
            "Food",
            "Fruits",
            "Game Shows",
            "Gems",
            "Gold",
            "Greek Gods",
            "Halloween",
            "Holidays",
            "Horror",
            "Irish",
            "Japan",
            "Jungle",
            "Knights",
            "Luxury",
            "Mexico",
            "Middle East",
            "Music",
            "Mystique & magic",
            "Nightlife",
            "Numbers",
            "Pirates",
            "Retro",
            "Romance",
            "Rome",
            "Space",
            "Sports",
            "Spring",
            "Summer",
            "Underwater",
            "Vehicles",
            "Vikings",
            "War",
            "Wild West",
            "Winter"
        ];
    }

    private function getOldThemes(): array
    {
        return [
            "3-D",
            "Action",
            "Adventure",
            "Animals",
            "Arctic",
            "Asian",
            "Autumn",
            "Board",
            "Black jack",
            "Branded Slots",
            "Cartoon Slots",
            "Cards",
            "Celebrity",
            "Christmas Slots",
            "Circus",
            "Cleopatra",
            "Comics",
            "Countries",
            "Desert",
            "Easter Slots",
            "Egyptian",
            "Explorer",
            "Fairy Tales",
            "Fantasy",
            "Farm Animals",
            "Film",
            "Food",
            "Fruit",
            "Futuristic",
            "Gold",
            "Halloween Slots",
            "History",
            "Holidays",
            "Horror",
            "Irish",
            "Jewels and Gems",
            "Jackpot slots",
            "Jungle",
            "Love and Romance",
            "Luxury",
            "Mafia",
            "Magic",
            "Middle East",
            "Money",
            "Movies",
            "Music",
            "Mystery",
            "Nightlife",
            "Pirates",
            "Retro",
            "Scatters",
            "Seasons",
            "Space",
            "Sports",
            "Spring Slots",
            "Spy",
            "Steampunk",
            "Summer Slots",
            "Super Heroes",
            "Tv Show Slots",
            "TV Game Show Slots",
            "Travel",
            "Triple 7´s",
            "Vegas",
            "Video Poker (1 hand)",
            "Video Poker (Multiple Hands)",
            "War",
            "Water Themed slots",
            "Wild West"
        ];
    }

    private function transformArray($themes): array
    {
        return array_map(function($theme) {
            return ['name' => $theme];
            }, $themes);

    }
}
