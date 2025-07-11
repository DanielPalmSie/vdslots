<?php

class JpWheel {

    /**
     * @var SQL
     */
    protected $replica = null;

    /**
     * @var array Audio configuration
     */
    public $wheelAudio = [];

    public function __construct(){
        $this->db = phive('SQL');
        // Colors of the slice separete by | , the : split tones in a gradient (optional).
        $this->wheel_styles = phive('Trophy')->getSetting('wheel_styles');

        $this->replica = $this->db->readOnly();

        $this->wheelAudio = [
            [
                'id' => 'intro',
                'autoplay' => 'autoplay',
                'loop' => 'loop',
                'src' => [
                    'mp3' => [
                        'path' => '/file_uploads/wheel/jackpot.mp3'
                    ],
                    'ogg' => [
                        'id' => 'intro-source',
                        'path' => '/file_uploads/wheel/jackpot.ogg'
                    ]
                ],
            ],
            [
                'id' => 'wheelSpin',
                'autoplay' => false,
                'loop' => false,
                'src' => [
                    'mp3' => [
                        'path' => '/file_uploads/wheel/quiz-game.mp3'
                    ],
                    'ogg' => [
                        'path' => '/file_uploads/wheel/quiz-game.ogg'
                    ]
                ],
            ],
            [
                'id' => 'jackpotWin',
                'autoplay' => false,
                'loop' => false,
                'src' => [
                    'mp3' => [
                        'path' => '/file_uploads/wheel/heroic-orchestral.mp3'
                    ],
                    'ogg' => [
                        'path' => '/file_uploads/wheel/heroic-orchestral.ogg'
                    ]
                ],
            ],
            [
                'id' => 'clapping',
                'autoplay' => false,
                'loop' => false,
                'src' => [
                    'mp3' => [
                        'path' => '/file_uploads/wheel/clapping.mp3'
                    ],
                    'ogg' => [
                        'path' => '/file_uploads/wheel/clapping.ogg'
                    ]
                ],
            ],
            [
                'id' => 'jackpotMoney',
                'autoplay' => false,
                'loop' => false,
                'src' => [
                    'mp3' => [
                        'path' => '/file_uploads/wheel/jackpotmoney.mp3'
                    ],
                    'ogg' => [
                        'path' => '/file_uploads/wheel/jackpotmoney.ogg'
                    ]
                ],
            ]
        ];
    }

    public function img($img, $return = false){
        $url = fupUri("/wheel/$img", $return);

        // Correct the double slashes in the path bug, specifically for "//wheel"
        $url = str_replace('//wheel', '/wheel', $url);

        return $url;
    }

    public function legendImg($img, $return = false){
        return $this->img($img, $return);
    }

    public function updateJpValues() {
        try {
            if (empty(phive('Trophy')->getSetting('wheel_add_contribution'))) {
                return;
            }

            $current_data = phive()->getMiscCache('jp-values');
            $new_data     = $this->db->loadArray("SELECT * FROM jackpots");
            $to_json      = [];
            foreach($new_data as $jp){
                $tmp   = phive()->moveit(['included_countries', 'excluded_countries', 'jpalias'], $jp);
                $jp_id = $jp['id'];
                // We just default to one whole currency unit in case this is the first time we generate the data.
                $tmp['prev_amount'] = $current_data[$jp_id]['curr_amount'] ?? max($jp['amount'] - 100, 0);
                $tmp['curr_amount'] = $jp['amount'];
                $to_json[ $jp_id ]  = $tmp;
            }

            phive()->miscCache('jp-values', $to_json, true);
        } catch (\Throwable $e) {
            phive('Logger')->getLogger('queue_messages')->debug("CronEventHandler::timeoutGameSessions", [$e]);
        }
    }

    public function getCache($as_array = true, $u_obj = null){
        $cache    = json_decode(phive()->getMiscCache('jp-values'), true);
        // We filter out all jackpots that should not show.
        $cache    = phive('UserHandler')->filterByCountry($cache, $u_obj);
        $base_cur = phive('Currencer')->baseCur();
        $ret = [];
        foreach($cache as $c){
            $c['curr_amount'] = chg($base_cur, getCur(), $c['curr_amount'], 1);
            $c['prev_amount'] = chg($base_cur, getCur(), $c['prev_amount'], 1);
            $ret[$c['jpalias']] = $c;
        }
        return $as_array ? $ret : json_encode($ret);
    }

    /**
     * We get the wheel from "$wheel_id" if passed, else we get the wheel from the user country
     * If no Id and no Country Wheel are available a global one (country==ALL) will be returned, if active.
     *
     * TODO ?? add a check into admin2 that only 1 country wheel is active?? otherwise if no $wheel_id is specified how can we select which "country jackpot" should be used?
     *
     * @param $u_obj
     * @param null $wheel_id
     * @return bool
     */
    public function getWheel($u_obj, $wheel_id = null){
        $country           = $u_obj->getCountry();
        // We always respect excluded countries.
        $where_not_country = "WHERE excluded_countries NOT LIKE '%$country%'";
        // We check wheel id in case it's passed in.
        $where_id          = !empty($wheel_id) ? "AND id = $wheel_id" : '';
        // We look for country in case the wheel id was NOT passed in.
        $where_country     = empty($wheel_id) ? "AND country = '$country'" : '';
        $wheel             = $this->replica->loadAssoc("SELECT * FROM jackpot_wheels $where_not_country $where_id $where_country");
        // In case we can't find a wheel given the above filters we get the country -> ALL wheel.
        $wheel             = empty($wheel) ? $this->replica->loadAssoc('', 'jackpot_wheels', ['country' => 'ALL'], true) : $wheel;
        return empty($wheel['active']) ? false : $wheel;
    }

    public function generateWheel($wheel_id){
        $wheel_id = (int)$wheel_id;

        $str = "SELECT jws.*
                FROM jackpot_wheel_slices jws, jackpot_wheels jw
                WHERE jw.id = $wheel_id
                AND jws.wheel_id = jw.id
                AND jw.active = 1
                ORDER BY jws.sort_order";

        $slices = $this->replica->loadArray($str);

        foreach($slices as &$slice){
            // Will return array with one element in case we only have one slice and no comma in the string so works with the below.
            $award_ids = explode(',', $slice['award_id']);
            $blocked_awards = [];
            $i = 0;
            //shuffle($award_ids);
            //$slice['award_id'] = $award_ids[0];
            while(true){
                if($i > 50){
                    // Fail safe in case something is configured wrongly so we don't go into an infinite loop.
                    break;
                }
                $i++;
                $award_id = $award_ids[mt_rand(0, count($award_ids) - 1)];
                if(in_array($award_id, $blocked_awards)){
                    continue;
                }
                if(!phive('Trophy')->canGiveAward($award_id)){
                    $blocked_awards[] = $award_id;
                    continue;
                }else{
                    break;
                }
            }
            $slice['award_id'] = $award_id;
            //print_r($slice);
        }

        $awards = $this->replica->loadArray("SELECT * FROM trophy_awards WHERE id IN({$this->db->makeInWith($slices, 'award_id')})", 'ASSOC', 'id');

        foreach($slices as &$slice){
            $slice['award'] = $awards[ $slice['award_id'] ];
        }

        return $slices;
    }

    /**
     * Retrieving the wheel for the user, if doesn't exist we generate one.
     * To avoid that on page refresh the wheel could change we store the last created one on redis
     *
     * @param $u_obj
     * @return bool|mixed|string
     */
    public function getCurWheel($u_obj, $wheel_award = []){
        // we need to load the current award in any case, cause we need the wheel id (in this case "jackpots_id") to store things properly in redis cache
        // cause now we will have multiple wheels that can be loaded, and that depends on the currentAward
        $wheel_award = empty($wheel_award) ? phive('Trophy')->getCurAward($u_obj, true) : $wheel_award;

        // we won't display the wheel for other awards
        if($wheel_award['type'] !== 'wheel-of-jackpots') {
            return false;
        }

        // Instead of adding a fairly redundant new column named something like wheel_id we reuse the jackpots_id column
        // even though the name doesn't make sense in this context. (this will work only for type = 'wheel-of-jackpots')
        $wheel = $this->getWheel($u_obj, $wheel_award['jackpots_id']);

        if(empty($wheel)){
            return false;
        }

        $wheel_id  = $wheel['id'];
        $wheel_key = 'cur-wheel-'.$wheel_id;
        $cur_wheel = phMgetShard($wheel_key, $u_obj);

        if(empty($cur_wheel)){
            $cur_wheel = $this->generateWheel($wheel_id);
            if(empty($cur_wheel)){
                return false;
            }
            phMsetShard($wheel_key, json_encode($cur_wheel), $u_obj);
        } else {
            $cur_wheel = json_decode($cur_wheel, true);
        }

        return [$wheel_id, $cur_wheel];
    }

    public function displayWheel($u, $color1, $color2)
    {
        list($wheel_id, $wheel) = $this->getCurWheel($u);
        $wheel_style_name       = $this->replica->loadAssoc("SELECT style FROM jackpot_wheels", '', array('id' => $wheel_id), true)['style'];
        $wheel_style            = $this->getWheelStyle($wheel_style_name);
        $strokeStyle            = isset($wheel_style['strokeStyle']) ? $wheel_style['strokeStyle'] : '';
        $colors = explode('|', $wheel_style['colors']);
        if(empty($wheel)){
            return false;
        }

        $ret = [];
        $i = 0;
        foreach ($wheel as $slice) {
            $award = $slice['award'];

            // Prepare segment object to be used by wheel
            $color = $colors[$i % count($colors)];
            // the following is used to use another image on the wheel which is
            // different from the award which are 2 completely different images
            if ($award['type'] == "jackpot") {
                $image        = "/file_uploads/wheel/".$award['alias'].".png";
                $legend_alias = $award['alias'];
            } else {
                $image        = empty($award) ? fupUri("events/alpha_1px.png", true) : phive('Trophy')->getAwardUri($award, $u);
                $legend_alias = $award['type'];
            }

            $legend_image  = fupUri("wheel/".$legend_alias . "_reward.png", true);
            $legend_alias .= '.legend';

            $img = [
                'image'        => $image,
                'text'         => rep($award['description']),
                'strokeStyle'  => $strokeStyle,
                'fillStyle'    => $color,
                'award_id'     => $award['id'],
                //'award_type'   => $award['type'],
                //'award_alias'  => $award['alias'],
                'legend_alias' => $legend_alias,
                'legend_image' => $legend_image
            ];

            $ret[] = $img;
            $i++;
        }
        return ['style' => $wheel_style, 'slices' => $ret];
    }

    public function spin($u){

        $jp_spin_award    = phive('Trophy')->getCurAward($u, true);
        $jp_spin_award_id = $jp_spin_award['id'];
        // -- Comment to test ----
        //*
        // If we have the wrong current type or no current award at all we can't execute the spin.
        if($jp_spin_award['type'] != 'wheel-of-jackpots'){
            return ['noAward'];
        }

        // this needs to be done before calling useAward to get the proper data for the wheel from getCurAward before redis is overridden.
        list($wheel_id, $slices) = $this->getCurWheel($u, $jp_spin_award);
        if(empty($slices)){
            return ['noAward'];
        }

        // the new won award is stored in this variable and deleted from redis right away.
        $jp_spin_award = phive('Trophy')->useAward($jp_spin_award_id, $u->getId(), true);
        phive('Trophy')->delCurAward($u);

        // Check if we managed to actually successfully use the spin award.
        if(!is_array($jp_spin_award)){
            return $jp_spin_award; // We return the error, should already have been translated and everything.
        }
        //*/

        $counter    = 0;
        $hit_number = mt_rand(0, 10000000);

        foreach($slices as $slice){
            $counter   += $slice['probability'];
            $win_slice = $slice;
            if($hit_number <= $counter){
                break;
            }
        }

        // Remove comment to test jackpot win
        //$win_slice = $slices[1];

        $th = phive('Trophy');

        // this part builds the log array needed to log the wheel details
        // the wslices array (wslices)is composed of [award_id, probability, order]
        // winseg is the winning segment and wid is the wheel id

        $slicesArray = [];
        foreach ($slices as $slice){
            $sliceArr      = [$slice['award_id'], $slice['probability'], $slice['sort_order']];
            $slicesArray[] = $sliceArr;
        }
        $slicesArr = json_encode($slicesArray, JSON_NUMERIC_CHECK);

        // give award
        $a = $win_slice['award_id'];
        $award = $th->getExtAward($a);

        // We execute as async and wait an amount of seconds (which is set in the config table)
        // in order for the wheel to finish spinning. I have added an extra second so that wheel will be stop
        // for sure.
        $spinTime = (phive('Config')->getValue('spin-time', 'wheel-spin-time') + 1) * 1000000;

        // NOTE that the jackpots table is a MASTER ONLY and trophy_awards is GLOBAL.
        // Therefore queries or contexts using jackpots_id must ONLY be executed in a MASTER ONLY context.
        $th->giveAward($award, $u->data, $spinTime);

        // We delete the current wheel data here as the spin was successful
        phMdelShard('cur-wheel-'.$wheel_id, $u);

        // log the wheel always
        $mg_id = self::logWheelAction($u, $win_slice['wheel_id'], $slicesArr, $win_slice['sort_order'] + 1, $win_slice['award_id'], 0);
        phive('Logger')->getLogger('bos_logs')->info("jackpot-trace", ["Current award:" => $award, "User id:" => $u->getId()]);
        // if award is a jackpot use it immediately and give it as a win
        if ( !empty($award['jackpots_id']) ){
            $jackpots = $this->replica->loadArray("SELECT * FROM jackpots");
            phive('Logger')->getLogger('bos_logs')->info("jackpot-trace", ["Jackpots:" => $jackpots, "User id:" => $u->getId()]);
            phive('Site/Publisher')->single('jp-win', 'Trophy', 'useJpAward', [$award['id'], $u->getId(), $spinTime, $mg_id]);
        }

        return [$win_slice, $slices];
    }

    public function getWheelHistory($u_id, $page, $start_date, $end_date)
    {
        $limit = ($page - 1) * 15;
        $str = "SELECT * FROM jackpot_wheel_log WHERE user_id = $u_id AND created_at BETWEEN '$start_date' AND '$end_date 23:59:59' LIMIT $limit, 15";
        $res = $this->replica->sh($u_id)->loadArray($str);
        return $res;
    }

    public function getWheelCount($uid, $start_date, $end_date)
    {
        $res = $this->replica->sh($uid)->getValue("SELECT COUNT(id) AS number FROM jackpot_wheel_log WHERE user_id = $uid  AND  created_at BETWEEN  '$start_date' AND  '$end_date 23:59:59' ");
        return $res;

    }

    public function logWheelAction($cu, $wheel_id, $slices, $win_seg, $win_award_id, $win_jp_amount)
    {
        $uid        = $cu->getId();
        $user       = cu($uid);
        $ud         = $user->data;
        $firstname  = explode(' ', $ud['firstname'])[0] ?? '';
        $user_curr  = $ud['currency'];

        $insert = [
            'wheel_id'      => $wheel_id,
            'slices'        => $slices,
            'win_segment'   => $win_seg,
            'user_id'       => $uid,
            'firstname'     => $firstname,
            'win_award_id'  => $win_award_id,
            'user_currency' => $user_curr,
            'win_jp_amount' => $win_jp_amount
        ];

        return $this->db->sh($user)->insertArray('jackpot_wheel_log', $insert);
    }

    public function getLegendAwards($wheel_slices){
        $woj_slices   = [];
        $other_slices = [];

        foreach($wheel_slices as $slice){
            if($slice['legend_alias'] == 'wheel-of-jackpots.legend'){
                $woj_slices[] = $slice;
            } else {
                $other_slices[] = $slice;
            }
        }

        $res = array_filter(phive()->uniqByKey($other_slices, 'legend_alias'), function($legend){
            return !empty($legend['award_id']);
        });

        /*
           // Don't remove this just yet, this is if we want an no win icon after all /Henrik
        foreach($res as &$legend){
            if(empty($legend['award_id'])){
                $legend['legend_alias'] = "woj.nowin.legend";
                $legend['legend_image'] = fupUri("events/reward_placeholder.png", true);
            }
        }
        */

        $jps = ['MEGA_JACKPOT' => 3, 'MAJOR_JACKPOT' => 2, 'MINI_JACKPOT' => 1];

        // Jackpots have to be at the top in order of mega, major and mini at the bottom, the rest below.
        usort($res, function($a, $b) use ($jps){
            list($typea, $not_used) = explode('.', $a['legend_alias']);
            list($typeb, $not_used) = explode('.', $b['legend_alias']);

            if($typea == $typeb){
                return 0;
            }

            // If the type is not a JP it will be marshalled into a 0 so works out OK for our purposes here.
            if((int)$jps[$typea] > (int)$jps[$typeb]){
                return -1;
            }

            if((int)$jps[$typea] < (int)$jps[$typeb]){
                return 1;
            }
        });

        $woj_slices = phive()->uniqByKey($woj_slices, 'award_id');

        foreach($woj_slices as $woj_slice){
            //$woj_slice['legend_alias'] = strtolower(str_replace('..', '.', trim(str_replace(['-', ' '], ['', '.'], $woj_slice['text']))));
            $woj_slice['legend_alias'] = "woj.{$woj_slice['award_id']}.legend";
            $woj_slice['legend_image'] = $woj_slice['image'];
            $res[] = $woj_slice;
        }

        return $res;
    }

    public function getWheelJackpots($u_obj = null, $where_extra = ''){
        $country = empty($u_obj) ? cuCountry() : $u_obj->getCountry();
        $sql = "SELECT * FROM jackpots
                WHERE ((excluded_countries NOT LIKE '%$country%' AND included_countries = '') OR included_countries LIKE '%$country%')
                $where_extra";

        return $this->replica->loadArray($sql);
    }

    public function getLatestJackpotWinners($limit = null){

        $sql =  "SELECT jackpot_wheel_log.slices,
                        jackpot_wheel_log.win_award_id,
                        jackpot_wheel_log.user_id,
                        jackpot_wheel_log.user_currency,
                        jackpot_wheel_log.win_jp_amount,
                        jackpot_wheel_log.firstname,
                        jackpot_wheel_log.created_at,
                        trophy_awards.description,
                        trophy_awards.alias,
                        trophy_awards.type
                FROM    jackpot_wheel_log,
                        trophy_awards
                WHERE 	trophy_awards.id = jackpot_wheel_log.win_award_id
                AND     trophy_awards.type = 'jackpot'
                AND     trophy_awards.jackpots_id <> 0
                ORDER BY jackpot_wheel_log.created_at DESC LIMIT 10";

        $res = $this->replica->shs('merge', 'created_at', 'desc')->loadArray($sql);

        if(empty($limit)){
            return $res;
        }

        return array_slice($res, 0, $limit);
    }

    public function getWheelStyle($wheel_style_name = 'gold') {
        if (empty($wheel_style_name)) {
            $wheel_style_name = 'gold';
        }
        return  array_shift(array_filter($this->wheel_styles, function($style) use($wheel_style_name) {
            return($style['name'] == $wheel_style_name);
        }));
    }

    public function getAllWheelStyles() {
        return $this->wheel_styles;
    }

    /**
     * Get the Wheel of Vegas data for Jackpot microservice api
     *
     * @return array
     */
    public function getWheelDataForApi(): array
    {
        /** @var BrandedConfig $brand_config */
        $brand_config = phive('BrandedConfig');
        $brand = $brand_config->getBrand();

        /** @var Currencer $currency_handler */
        $currency_handler = phive('Currencer');

        /** @var ImageHandler $image_handler */
        $image_handler = phive('ImageHandler');

        /** @var DBUserHandler $user_handler */
        $user_handler = phive('UserHandler');

        $data       = [];
        $jackpots   = json_decode(phive()->getMiscCache('jp-values'), true);
        $jackpots   = $user_handler->filterByCountry($jackpots);
        $currencies = $currency_handler->getAllCurrencies();
        $base_curr  = $currency_handler->baseCur();

        foreach ($jackpots as $jackpot) {
            $alias          = $jackpot['jpalias'];
            $default_img    = fupUri("wheel/{$alias}_info.png", true);
            $img_alias      = 'jpimage' . $alias;
            $img            = $image_handler->img($img_alias, 275, 97, $img_alias, null, $default_img);

            foreach ($currencies as $code => $currency) {
                $data[] = [
                    'alias'         => $alias,
                    'brand'         => $brand,
                    'currency'      => $code,
                    'prev_amount'   => (float) chg($base_curr, $code, $jackpot['prev_amount'], 1),
                    'curr_amount'   => (float) chg($base_curr, $code, $jackpot['curr_amount'], 1),
                    'image_url'     => $img[0] ?: null
                ];
            }
        }

        return $data;
    }
}
