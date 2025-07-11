<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;

/**
 * usage: ./console seeder:up 20221025130001
 * usage: ./console seeder:down 20221025130001
 */
class AddSportSportsListOnMrVegas extends Seeder
{
    private string $table = 'sport_sports_list';
    private Connection $connection;

    protected array $sports = [
        ['sr:sport:1', 'Soccer', 'sb.sport.sr:sport:1.name'],
        ['sr:sport:10', 'Boxing', 'sb.sport.sr:sport:10.name'],
        ['sr:sport:100', 'Superbike', 'sb.sport.sr:sport:100.name'],
        ['sr:sport:101', 'Rally', 'sb.sport.sr:sport:101.name'],
        ['sr:sport:102', 'Figure Skating', 'sb.sport.sr:sport:102.name'],
        ['sr:sport:103', 'Freestyle Skiing', 'sb.sport.sr:sport:103.name'],
        ['sr:sport:104', 'Skeleton', 'sb.sport.sr:sport:104.name'],
        ['sr:sport:105', 'Short Track', 'sb.sport.sr:sport:105.name'],
        ['sr:sport:106', 'Soccer Mythical', 'sb.sport.sr:sport:106.name'],
        ['sr:sport:107', 'eSport', 'sb.sport.sr:sport:107.name'],
        ['sr:sport:108', 'World Lottery', 'sb.sport.sr:sport:108.name'],
        ['sr:sport:109', 'ESport Counter-Strike', 'sb.sport.sr:sport:109.name'],
        ['sr:sport:11', 'Motorsport', 'sb.sport.sr:sport:11.name'],
        ['sr:sport:110', 'ESport League of Legends', 'sb.sport.sr:sport:110.name'],
        ['sr:sport:111', 'ESport Dota', 'sb.sport.sr:sport:111.name'],
        ['sr:sport:112', 'ESport StarCraft', 'sb.sport.sr:sport:112.name'],
        ['sr:sport:113', 'ESport Hearthstone', 'sb.sport.sr:sport:113.name'],
        ['sr:sport:114', 'ESport Heroes of the Storm', 'sb.sport.sr:sport:114.name'],
        ['sr:sport:115', 'ESport World of Tanks', 'sb.sport.sr:sport:115.name'],
        ['sr:sport:116', 'Polo', 'sb.sport.sr:sport:116.name'],
        ['sr:sport:117', 'MMA', 'sb.sport.sr:sport:117.name'],
        ['sr:sport:118', 'ESport Call of Duty', 'sb.sport.sr:sport:118.name'],
        ['sr:sport:119', 'ESport Smite', 'sb.sport.sr:sport:119.name'],
        ['sr:sport:12', 'Rugby', 'sb.sport.sr:sport:12.name'],
        ['sr:sport:120', 'ESport Vainglory', 'sb.sport.sr:sport:120.name'],
        ['sr:sport:121', 'ESport Overwatch', 'sb.sport.sr:sport:121.name'],
        ['sr:sport:122', 'ESport WarCraft III', 'sb.sport.sr:sport:122.name'],
        ['sr:sport:123', 'ESport Crossfire', 'sb.sport.sr:sport:123.name'],
        ['sr:sport:124', 'ESport Halo', 'sb.sport.sr:sport:124.name'],
        ['sr:sport:125', 'ESport Rainbow Six', 'sb.sport.sr:sport:125.name'],
        ['sr:sport:126', 'Sepak Takraw', 'sb.sport.sr:sport:126.name'],
        ['sr:sport:127', 'ESport Street Fighter V', 'sb.sport.sr:sport:127.name'],
        ['sr:sport:128', 'ESport Rocket League', 'sb.sport.sr:sport:128.name'],
        ['sr:sport:129', 'Indy Racing', 'sb.sport.sr:sport:129.name'],
        ['sr:sport:13', 'Aussie Rules', 'sb.sport.sr:sport:13.name'],
        ['sr:sport:130', 'Basque Pelota', 'sb.sport.sr:sport:130.name'],
        ['sr:sport:131', 'Speedway', 'sb.sport.sr:sport:131.name'],
        ['sr:sport:132', 'ESport Gears of War', 'sb.sport.sr:sport:132.name'],
        ['sr:sport:133', 'ESport Clash Royale', 'sb.sport.sr:sport:133.name'],
        ['sr:sport:134', 'ESport King of Glory', 'sb.sport.sr:sport:134.name'],
        ['sr:sport:135', 'Gaelic Football', 'sb.sport.sr:sport:135.name'],
        ['sr:sport:136', 'Gaelic Hurling', 'sb.sport.sr:sport:136.name'],
        ['sr:sport:137', 'eSoccer', 'sb.sport.sr:sport:137.name'],
        ['sr:sport:138', 'Kabaddi', 'sb.sport.sr:sport:138.name'],
        ['sr:sport:139', 'ESport Quake', 'sb.sport.sr:sport:139.name'],
        ['sr:sport:14', 'Winter Sports', 'sb.sport.sr:sport:14.name'],
        ['sr:sport:140', 'ESport PlayerUnknowns Battlegrounds', 'sb.sport.sr:sport:140.name'],
        ['sr:sport:141', 'Cycling Cycle Ball', 'sb.sport.sr:sport:141.name'],
        ['sr:sport:142', 'Formula E', 'sb.sport.sr:sport:142.name'],
        ['sr:sport:143', '7BallRun', 'sb.sport.sr:sport:143.name'],
        ['sr:sport:144', 'Motocross', 'sb.sport.sr:sport:144.name'],
        ['sr:sport:145', 'Sprint Car Racing', 'sb.sport.sr:sport:145.name'],
        ['sr:sport:146', 'Speed Boat Racing', 'sb.sport.sr:sport:146.name'],
        ['sr:sport:147', 'Drag Racing', 'sb.sport.sr:sport:147.name'],
        ['sr:sport:148', 'Stock Car Racing', 'sb.sport.sr:sport:148.name'],
        ['sr:sport:149', 'Modified Racing', 'sb.sport.sr:sport:149.name'],
        ['sr:sport:15', 'Bandy', 'sb.sport.sr:sport:15.name'],
        ['sr:sport:150', 'Off Road', 'sb.sport.sr:sport:150.name'],
        ['sr:sport:151', 'Truck & Tractor Pulling', 'sb.sport.sr:sport:151.name'],
        ['sr:sport:152', 'ESport World of Warcraft', 'sb.sport.sr:sport:152.name'],
        ['sr:sport:153', 'eBasketball', 'sb.sport.sr:sport:153.name'],
        ['sr:sport:154', 'ESport Dragon Ball FighterZ', 'sb.sport.sr:sport:154.name'],
        ['sr:sport:155', 'Basketball 3x3', 'sb.sport.sr:sport:155.name'],
        ['sr:sport:156', 'ESport Tekken', 'sb.sport.sr:sport:156.name'],
        ['sr:sport:157', 'Beach Handball', 'sb.sport.sr:sport:157.name'],
        ['sr:sport:158', 'ESport Arena of Valor', 'sb.sport.sr:sport:158.name'],
        ['sr:sport:159', 'ESport TF2', 'sb.sport.sr:sport:159.name'],
        ['sr:sport:16', 'American Football', 'sb.sport.sr:sport:16.name'],
        ['sr:sport:160', 'ESport SSBM', 'sb.sport.sr:sport:160.name'],
        ['sr:sport:161', 'ESport Paladins', 'sb.sport.sr:sport:161.name'],
        ['sr:sport:162', 'ESport Artifact', 'sb.sport.sr:sport:162.name'],
        ['sr:sport:163', 'Indoor Soccer', 'sb.sport.sr:sport:163.name'],
        ['sr:sport:164', 'ESport Apex Legends', 'sb.sport.sr:sport:164.name'],
        ['sr:sport:165', 'Indy Lights', 'sb.sport.sr:sport:165.name'],
        ['sr:sport:166', 'ESport Pro Evolution Soccer', 'sb.sport.sr:sport:166.name'],
        ['sr:sport:167', 'ESport Madden NFL', 'sb.sport.sr:sport:167.name'],
        ['sr:sport:168', 'ESport Brawl Stars', 'sb.sport.sr:sport:168.name'],
        ['sr:sport:169', 'Petanque', 'sb.sport.sr:sport:169.name'],
        ['sr:sport:17', 'Cycling', 'sb.sport.sr:sport:17.name'],
        ['sr:sport:170', 'ESport Fortnite', 'sb.sport.sr:sport:170.name'],
        ['sr:sport:171', 'ESport MTG', 'sb.sport.sr:sport:171.name'],
        ['sr:sport:172', 'Fishing', 'sb.sport.sr:sport:172.name'],
        ['sr:sport:173', 'Esport Dota Underlords', 'sb.sport.sr:sport:173.name'],
        ['sr:sport:174', 'Esport Teamfight Tactics', 'sb.sport.sr:sport:174.name'],
        ['sr:sport:175', 'Esport Auto Chess', 'sb.sport.sr:sport:175.name'],
        ['sr:sport:176', 'Esport Fighting Games', 'sb.sport.sr:sport:176.name'],
        ['sr:sport:177', 'DEPRECATED sc', 'sb.sport.sr:sport:177.name'],
        ['sr:sport:178', 'ESport Motorsport', 'sb.sport.sr:sport:178.name'],
        ['sr:sport:179', 'Cycling BMX Freestyle', 'sb.sport.sr:sport:179.name'],
        ['sr:sport:18', 'Specials', 'sb.sport.sr:sport:18.name'],
        ['sr:sport:180', 'Cycling BMX Racing', 'sb.sport.sr:sport:180.name'],
        ['sr:sport:181', 'Karate', 'sb.sport.sr:sport:181.name'],
        ['sr:sport:182', 'Marathon Swimming', 'sb.sport.sr:sport:182.name'],
        ['sr:sport:183', 'Skateboarding', 'sb.sport.sr:sport:183.name'],
        ['sr:sport:184', 'Sport Climbing', 'sb.sport.sr:sport:184.name'],
        ['sr:sport:185', 'Nascar Camping World Truck', 'sb.sport.sr:sport:185.name'],
        ['sr:sport:186', 'Nascar Xfinity Series', 'sb.sport.sr:sport:186.name'],
        ['sr:sport:187', 'NHRA', 'sb.sport.sr:sport:187.name'],
        ['sr:sport:188', 'Touring Car Racing', 'sb.sport.sr:sport:188.name'],
        ['sr:sport:189', 'Formula 2', 'sb.sport.sr:sport:189.name'],
        ['sr:sport:19', 'Snooker', 'sb.sport.sr:sport:19.name'],
        ['sr:sport:190', 'Motorcycle Racing', 'sb.sport.sr:sport:190.name'],
        ['sr:sport:191', 'Stock Car Racing', 'sb.sport.sr:sport:191.name'],
        ['sr:sport:192', 'Air Racing', 'sb.sport.sr:sport:192.name'],
        ['sr:sport:193', 'Endurance Racing', 'sb.sport.sr:sport:193.name'],
        ['sr:sport:194', 'ESport Valorant', 'sb.sport.sr:sport:194.name'],
        ['sr:sport:195', 'eIce Hockey', 'sb.sport.sr:sport:195.name'],
        ['sr:sport:196', 'eTennis', 'sb.sport.sr:sport:196.name'],
        ['sr:sport:197', 'eCricket', 'sb.sport.sr:sport:197.name'],
        ['sr:sport:198', 'eVolleyball', 'sb.sport.sr:sport:198.name'],
        ['sr:sport:199', 'ESport Wild Rift', 'sb.sport.sr:sport:199.name'],
        ['sr:sport:2', 'Basketball', 'sb.sport.sr:sport:2.name'],
        ['sr:sport:20', 'Table Tennis', 'sb.sport.sr:sport:20.name'],
        ['sr:sport:200', 'T-Basket', 'sb.sport.sr:sport:200.name'],
        ['sr:sport:21', 'Cricket', 'sb.sport.sr:sport:21.name'],
        ['sr:sport:22', 'Darts', 'sb.sport.sr:sport:22.name'],
        ['sr:sport:23', 'Volleyball', 'sb.sport.sr:sport:23.name'],
        ['sr:sport:24', 'Field hockey', 'sb.sport.sr:sport:24.name'],
        ['sr:sport:25', 'Pool', 'sb.sport.sr:sport:25.name'],
        ['sr:sport:26', 'Waterpolo', 'sb.sport.sr:sport:26.name'],
        ['sr:sport:27', 'Gaelic sports', 'sb.sport.sr:sport:27.name'],
        ['sr:sport:28', 'Curling', 'sb.sport.sr:sport:28.name'],
        ['sr:sport:29', 'Futsal', 'sb.sport.sr:sport:29.name'],
        ['sr:sport:3', 'Baseball', 'sb.sport.sr:sport:3.name'],
        ['sr:sport:30', 'Olympics', 'sb.sport.sr:sport:30.name'],
        ['sr:sport:31', 'Badminton', 'sb.sport.sr:sport:31.name'],
        ['sr:sport:32', 'Bowls', 'sb.sport.sr:sport:32.name'],
        ['sr:sport:33', 'Chess', 'sb.sport.sr:sport:33.name'],
        ['sr:sport:34', 'Beach Volley', 'sb.sport.sr:sport:34.name'],
        ['sr:sport:35', 'Netball', 'sb.sport.sr:sport:35.name'],
        ['sr:sport:36', 'Athletics', 'sb.sport.sr:sport:36.name'],
        ['sr:sport:37', 'Squash', 'sb.sport.sr:sport:37.name'],
        ['sr:sport:38', 'Rink Hockey', 'sb.sport.sr:sport:38.name'],
        ['sr:sport:39', 'Lacrosse', 'sb.sport.sr:sport:39.name'],
        ['sr:sport:4', 'Ice Hockey', 'sb.sport.sr:sport:4.name'],
        ['sr:sport:40', 'Formula 1', 'sb.sport.sr:sport:40.name'],
        ['sr:sport:41', 'Bikes', 'sb.sport.sr:sport:41.name'],
        ['sr:sport:42', 'DTM', 'sb.sport.sr:sport:42.name'],
        ['sr:sport:43', 'Alpine Skiing', 'sb.sport.sr:sport:43.name'],
        ['sr:sport:44', 'Biathlon', 'sb.sport.sr:sport:44.name'],
        ['sr:sport:45', 'Bobsleigh', 'sb.sport.sr:sport:45.name'],
        ['sr:sport:46', 'Cross-Country', 'sb.sport.sr:sport:46.name'],
        ['sr:sport:47', 'Nordic Combined', 'sb.sport.sr:sport:47.name'],
        ['sr:sport:48', 'Ski Jumping', 'sb.sport.sr:sport:48.name'],
        ['sr:sport:49', 'Snowboard', 'sb.sport.sr:sport:49.name'],
        ['sr:sport:5', 'Tennis', 'sb.sport.sr:sport:5.name'],
        ['sr:sport:50', 'Speed Skating', 'sb.sport.sr:sport:50.name'],
        ['sr:sport:51', 'Luge', 'sb.sport.sr:sport:51.name'],
        ['sr:sport:52', 'Swimming', 'sb.sport.sr:sport:52.name'],
        ['sr:sport:53', 'Finnish Baseball', 'sb.sport.sr:sport:53.name'],
        ['sr:sport:54', 'Softball', 'sb.sport.sr:sport:54.name'],
        ['sr:sport:55', 'Horse racing', 'sb.sport.sr:sport:55.name'],
        ['sr:sport:56', 'Schwingen', 'sb.sport.sr:sport:56.name'],
        ['sr:sport:57', 'Inline Hockey', 'sb.sport.sr:sport:57.name'],
        ['sr:sport:58', 'Greyhound', 'sb.sport.sr:sport:58.name'],
        ['sr:sport:59', 'Rugby League', 'sb.sport.sr:sport:59.name'],
        ['sr:sport:6', 'Handball', 'sb.sport.sr:sport:6.name'],
        ['sr:sport:60', 'Beach Soccer', 'sb.sport.sr:sport:60.name'],
        ['sr:sport:61', 'Pesapallo', 'sb.sport.sr:sport:61.name'],
        ['sr:sport:62', 'Streethockey', 'sb.sport.sr:sport:62.name'],
        ['sr:sport:63', 'World Championship', 'sb.sport.sr:sport:63.name'],
        ['sr:sport:64', 'Rowing', 'sb.sport.sr:sport:64.name'],
        ['sr:sport:65', 'Freestyle', 'sb.sport.sr:sport:65.name'],
        ['sr:sport:66', 'Snowboardcross/Parallel', 'sb.sport.sr:sport:66.name'],
        ['sr:sport:67', 'MotoGP', 'sb.sport.sr:sport:67.name'],
        ['sr:sport:68', 'Moto2', 'sb.sport.sr:sport:68.name'],
        ['sr:sport:69', 'Moto3', 'sb.sport.sr:sport:69.name'],
        ['sr:sport:7', 'Floorball', 'sb.sport.sr:sport:7.name'],
        ['sr:sport:70', 'Nascar Cup Series', 'sb.sport.sr:sport:70.name'],
        ['sr:sport:71', 'Padel Tennis', 'sb.sport.sr:sport:71.name'],
        ['sr:sport:72', 'Canoeing', 'sb.sport.sr:sport:72.name'],
        ['sr:sport:73', 'Horseball', 'sb.sport.sr:sport:73.name'],
        ['sr:sport:74', 'Aquatics', 'sb.sport.sr:sport:74.name'],
        ['sr:sport:75', 'Archery', 'sb.sport.sr:sport:75.name'],
        ['sr:sport:76', 'Equestrian', 'sb.sport.sr:sport:76.name'],
        ['sr:sport:77', 'Fencing', 'sb.sport.sr:sport:77.name'],
        ['sr:sport:78', 'Gymnastics', 'sb.sport.sr:sport:78.name'],
        ['sr:sport:79', 'Judo', 'sb.sport.sr:sport:79.name'],
        ['sr:sport:8', 'Trotting', 'sb.sport.sr:sport:8.name'],
        ['sr:sport:80', 'Modern Pentathlon', 'sb.sport.sr:sport:80.name'],
        ['sr:sport:81', 'Sailing', 'sb.sport.sr:sport:81.name'],
        ['sr:sport:82', 'Shooting', 'sb.sport.sr:sport:82.name'],
        ['sr:sport:83', 'Taekwondo', 'sb.sport.sr:sport:83.name'],
        ['sr:sport:84', 'Triathlon', 'sb.sport.sr:sport:84.name'],
        ['sr:sport:85', 'Weightlifting', 'sb.sport.sr:sport:85.name'],
        ['sr:sport:86', 'Wrestling', 'sb.sport.sr:sport:86.name'],
        ['sr:sport:87', 'Olympics Youth', 'sb.sport.sr:sport:87.name'],
        ['sr:sport:88', 'Mountain Bike', 'sb.sport.sr:sport:88.name'],
        ['sr:sport:89', 'Riding', 'sb.sport.sr:sport:89.name'],
        ['sr:sport:9', 'Golf', 'sb.sport.sr:sport:9.name'],
        ['sr:sport:90', 'Surfing', 'sb.sport.sr:sport:90.name'],
        ['sr:sport:91', 'BMX racing', 'sb.sport.sr:sport:91.name'],
        ['sr:sport:92', 'Canoe slalom', 'sb.sport.sr:sport:92.name'],
        ['sr:sport:93', 'Rhythmic gymnastics', 'sb.sport.sr:sport:93.name'],
        ['sr:sport:94', 'Trampoline Gymnastics', 'sb.sport.sr:sport:94.name'],
        ['sr:sport:95', 'Artistic Swimming', 'sb.sport.sr:sport:95.name'],
        ['sr:sport:96', 'Diving', 'sb.sport.sr:sport:96.name'],
        ['sr:sport:97', 'Track cycling', 'sb.sport.sr:sport:97.name'],
        ['sr:sport:98', 'Beach Tennis', 'sb.sport.sr:sport:98.name'],
        ['sr:sport:99', 'Sumo', 'sb.sport.sr:sport:99.name']
    ];

    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        if (getenv('APP_SHORT_NAME') !== 'MV') {
            return;
        }

        foreach ($this->sports as $sport) {
            $exists = $this->connection
                ->table($this->table)
                ->where('ext_id', $sport[0])
                ->where('name', $sport[2])
                ->first();

            if (!empty($exists)) {
                continue;
            }

            $this->connection
                ->table($this->table)
                ->insert([
                    [
                        'ext_id' => $sport[0],
                        'original_name' => $sport[1],
                        'name' => $sport[2],
                    ]
                ]);
        }
    }

    public function down()
    {
        if (getenv('APP_SHORT_NAME') !== 'MV') {
            return;
        }

        foreach ($this->sports as $sport) {
            $this->connection
                ->table($this->table)
                ->where('ext_id', $sport[0])
                ->where('name', $sport[2])
                ->delete();
        }
    }
}
