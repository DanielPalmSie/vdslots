<?php

use GuzzleHttp\Client;

/**
 * Trait BannersTrait
 * @method getId()
 */
trait BannersTrait
{
    /** @var MicroGames $mg */
    public $mg;

    /** @var bool $is_logged */
    public $is_logged;

    /** @var string $cur_lang */
    public string $cur_lang;

    private $link_arr = [];


    /**
     * @var array
     */
    public array $games_arr = [];

    /**
     * @var bool
     */
    private bool $is_api;

    /**
     * @param bool $is_api
     *
     * @return void
     */
    public function initBannersVars(bool $is_api = false)
    {
        $this->is_api = $is_api;
        $this->mg = phive("MicroGames");

        $this->cur_lang = phive('Localizer')->getSubIndependentLang();

        if ($is_api) {
            $this->is_logged = cu() !== false;
        } else {
            $this->is_logged = isLogged();
        }
    }

    /**
     * @param $context
     * @param $game_key
     * @return mixed
     */
    public function getAutoBanners($context = 'mobile', $game_key = 'ext_game_name', $is_api = false)
    {
        $device_type_key = $context === 'mobile' ? 'html5' : 'flash';
        $max_rtp = round($this->auto_rtp/100, 4);
        $where_device = "mg.device_type = '$device_type_key'";
        $period = $this->auto_period === 'month' ? phive()->lastMonth() : phive()->yesterday();

        $type = $this->is_logged == 'yes' ? 'out' : 'in';
        $banners_cache_key_name = implode('.', [
            'auto',
            $device_type_key,
            'banners',
            $this->getId(),
            $type,
            $this->cur_lang,
            cuCountry(),
        ]);

        if (!phive()->isLocal()) {
            $auto_banners = unserialize(mCluster('qcache')->get($banners_cache_key_name));
        }

        if (empty($auto_banners)) {
           $games_list = $this->mg->getTaggedBy(
                !empty($this->auto_category) ? explode(',', $this->auto_category) : 'all',
                0,
                1000,
                null,
                'played_times_in_period DESC',
                $where_device,
                $period,
                false,
                true,
                '',
                null,
                null,
                false,
                " AND IFNULL(go.payout_percent, mg.payout_percent) <= {$max_rtp} ",
                $is_api
            );

            foreach ($games_list as $game) {
                if ($this->is_api || fileOrImageExists('/file_uploads', "thumbs/{$game['game_id']}_{$context[0]}b.jpg")) {
                    $this->auto_banners[$game[$game_key]] = "thumbs/{$game['game_id']}_{$context[0]}b.jpg";
                    $this->games_arr[$game[$game_key]] = $game['game_name'];
                }
            }

            if (!phive()->isLocal()) {
                mCluster('qcache')->set($banners_cache_key_name, serialize($auto_banners), rand(7000, 10000));
            }
        }
        return $this->auto_banners;
    }

    /**
     * Get the generated links and cache the result in redis
     *
     * @return array
     */
    public function getLinks(): array
    {
        $links = [];

        $links_cache_key_name = implode('.', ['links']);

        if (!phive()->isLocal()) {
            $links = unserialize(mCluster('qcache')->get($links_cache_key_name));
        }

        if (empty($links)) {
            $link_list = $this->setupLinks();
            foreach ($link_list as $name => $link) {
                $links[$name] = ['name' => $name, 'link' => $link];
            }
        }

        if (!phive()->isLocal()) {
            mCluster('qcache')->set($links_cache_key_name, serialize($links), rand(7000, 10000));
        }

        return $links;
    }

    public function getShuffledBanners()
    {
        $length  = empty($this->auto_max) ? 10 : $this->auto_max;

        $banners = $this->is_api ? $this->getBannersForApi($length) : array_slice($this->auto_banners, 0, $length);
        uksort($banners, function () {
            return rand() > rand();
        });

        return $banners;
    }

    /**
     * @param int $length
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function getBannersForApi(int $length): array
    {
        $result = [];
        $client = new Client();

        foreach ($this->auto_banners as $key => $auto_banner)
        {
            $url = fupUri($auto_banner, true);
            try {
                $response = $client->head($url);
                if($response->getStatusCode() == 200) {
                    $result[$key] = $auto_banner;
                }
            } catch (\Exception $e) {
            }
            if(count($result) == $length) {
                break;
            }
        }

        return $result;
    }

    private function setupLinks(): array
    {
        foreach (phive('Localizer')->getAllLanguages() as $language) {
            foreach ($this->banner_arr as $num) {
                $name = "link{$num}{$language['language']}";
                $this->link_arr[$name] = $this->getAttribute($name);
            }
        }

        $games = phive('MicroGames')->getGamesByIds($this->link_arr);
        $country = phive('IpBlock')->getCountry();
        $micro_games = phive('MicroGames');

        $this->link_arr = array_filter($this->link_arr, function ($game_id) use ($country, $games, $micro_games) {
            return !$micro_games->isBlocked($games[$game_id], $country);
        });

        return $this->link_arr;
    }
}
