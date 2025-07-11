<?php
require_once __DIR__ . '/../../../../../diamondbet/boxes/DiamondBox.php';
require_once __DIR__ . '/../../traits/BannersTrait.php';

class MobileStartBoxBase extends DiamondBox
{
    use BannersTrait;

    /** @var array $news */
    public $news;

    /** @var mixed */
    public $banner_in_link;

    /** @var mixed */
    public $banner_link;

    /** @var mixed */
    public $banner_images_logged;

    /** @var mixed */
    public $banner_links_logged;

    /** @var mixed */
    public $banner_links;

    /** @var mixed */
    public $banner_images;

    /** @var string $auto */
    public $auto;

    /** @var string $auto_out */
    public $auto_out;

    /** @var array $auto_banners */
    public $auto_banners;

    /** @var string $auto_rtp */
    public $auto_rtp;

    /** @var string $auto_period */
    public $auto_period;

    /** @var string|array $auto_category */
    public $auto_category;

    /** @var string|array $auto_max */
    public $auto_max;

    /**
     * @var int
     */
    public const IMAGE_WIDTH = 780;

    /**
     * @var int
     */
    public const IMAGE_HEIGHT = 380;

    /**
     * Init function that takes care of handling and loading initial stuff needed in the rest of the functions
     *
     * @param bool $is_api
     *
     * @return void
     */
    public function init(bool $is_api = false)
    {
        $this->initBannersVars($is_api);

        $box_generic_attributes = [
            'jp_counter',
            'jp_counter_excluded_countries',
            'sub_tags',
            'main_tags',
            'show_banner',
            'rotate_top',
            'randomize_games'
        ];

        $box_language_attributes = [
            // Static banner (show_banner = yes)
            'banner_link',
            'banner_in_link',
            // Dynamic banners (show_banner = no)
            'banner_links',
            'banner_images',
            'banner_links_logged',
            'banner_images_logged',
            // Auto banners (autoX = yes)
            'auto',
            'auto_out',
            'auto_rtp',
            'auto_period',
            'auto_category',
            'auto_max'
        ];

        $box_default_attributes = ['show_banner' => 'yes'];

        $box_attributes = array_merge($box_generic_attributes, $box_language_attributes);

        $this->languages = array_column(phive('Localizer')->getAllLanguages(), 'language');
        // Adding specific box attributes for each language
        foreach ($this->languages as $language) {
            foreach ($box_language_attributes as $language_attribute) {
                $box_attributes[] = $language_attribute . '_' . $language;
            }
        }

        $this->handlePost($box_attributes, $box_default_attributes);

        if (isset($_POST['save_settings'])) {
            mCluster('qcache')->delAll('auto.mobile.banners*');
        }

        foreach ($box_language_attributes as $language_attribute) {
            $this->{$language_attribute} = $this->getBoxAttributeOverrideByLanguage($language_attribute);
        }

        $this->use_link = $this->is_logged ? $this->banner_in_link : $this->banner_link;

        if ($this->hasAutoBanners()) {
            $this->auto_banners = $this->getAutoBanners('mobile', phive()->isMobileApp() ? 'desktop_game_id' : 'ext_game_name', $is_api);
        } else {
            if ($this->is_logged) {
                $this->img_tag = empty($this->banner_in_link) ? '' : 'mobile.top.loggedin.pic';
                $this->banner_images_arr = array_combine(explode(',', $this->banner_images_logged),
                    explode(',', $this->banner_links_logged));
            } else {
                $this->img_tag = 'mobile.top.pic';
                $this->banner_images_arr = array_combine(explode(',', $this->banner_images),
                    explode(',', $this->banner_links));
                if (in_array(licJur(), ['DK', 'IT'])) { //TODO panic fix to be implemented in the box properly
                    unset($this->banner_images_arr['mobile.top.3']);
                }
            }
        }

        //Sub tag option, for example to show new.cgames
        $this->stags_arr = explode(',', $this->sub_tags);
        $this->sgames = $this->mg->groupBySub($this->stags_arr, "html5", true, true, $this->shouldShowJpCounter());

        // Main tag option to show a secondary game banner section
        if (!empty($this->main_tags)) {
            $this->mtags_arr = explode(',', $this->main_tags);
            $this->mgames = $this->mg->getTaggedBy($this->mtags_arr, null, 10, null, "mg.played_times DESC",
                "mg.device_type = 'html5'", '', false, true);
            $this->mgames = phive()->orderKeysBy(phive()->group2d($this->mgames, 'tag'), $this->mtags_arr);
        }

        // Loads the news section
        $this->nh = phive("LimitedNewsHandler");
        $this->news = $this->nh->getLatestTopList($this->cur_lang, 'news');
        $this->news = $this->nh->sortByTimeStatus($this->news);
        $number_of_news_main = phive("Config")->getValue("news-mobile", "number-of-news-main", 4);
        $this->news = array_slice($this->news, 0, $number_of_news_main);

    }

    /**
     * @return bool True if auto banners enabled
     */
    public function hasAutoBanners()
    {
        return $this->auto === 'yes' && ($this->is_logged || $this->auto_out === 'yes');
    }

    /**
     * Prints the battle strip
     */
    public function printUpcomingBattles()
    {
        if (strpos($_SERVER['REQUEST_URI'], 'mobile') !== false && hasMobileMp()) {
            include_once($_SERVER['DOCUMENT_ROOT'] . '/diamondbet/html/mobile-top-upcoming-battles.php');
        }
    }

    /**
     * Handles the news strip
     *
     * @param array $latest_news News list
     */
    public function printLatestNewsSliders($latest_news)
    { ?>
        <?php
        if (empty($latest_news)) {
            return;
        }
        $num_slides_show = isIpad() ? 2 : 1;
        ?>
        <div class="vs-mobile__news-feed">
            <div class="vs-mobile__news-feed-container">
                <?php foreach ($latest_news as $news):
                    $stamp = strtotime($news->getTimeCreated());
                    ?>
                    <div class="vs-mobile__news-feed__news vs-mobile__news-feed__news--<?php echo $num_slides_show ?>">
                        <a class="vs-mobile__news-feed__news-thumbail"
                           href='<?php echo llink('/mobile' . $this->getArticleUrl($news)); ?>'>
                            <?php img($news->getImagePath(), 60, 50) ?>
                        </a>
                        <div class="vs-mobile__news-feed__news-text">
                            <div>
                                <h5 class="vs-mobile__news-feed__news-title">
                                    <?php echo rep($news->getHeadline()); ?>
                                </h5>
                                <p class="vs-mobile__news-feed__news-subtitle">
                                    <?php echo t('posted.in') ?>
                                    <?php et('cat' . $news->getCategoryId()); ?>
                                    &bull;
                                    <?php echo ucfirst(strftime("%b", $stamp)) . ' ' . strftime("%d",
                                            $stamp) . ' ' . strftime("%G", $stamp); ?>
                                </p>
                            </div>
                            <a class="vs-mobile__news-feed__news-link"
                               href='<?php echo llink('/mobile' . $this->getArticleUrl($news)); ?>'><?php et('read.more'); ?>
                                ...</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php }


    /**
     * Prints sub product menu
     */
    public function printFlatBarSection()
    {
        phive('Menuer')->renderSecondaryMobileMenu();
    }

    /**
     * Takes care of the auto game slider
     *
     * @param $games
     */
    public function printGameSliders($games)
    { ?>
        <?php foreach ($games as $salias => $sgames):
        if (empty($sgames)) {
            continue;
        }
        ?>
        <div class="flexslider-item">
            <div class="flexslider-headline"><?php et($salias) ?></div>
            <div class="flexslider-container">
                <div class="flexslider">
                    <ul class="slides">
                        <?php $fetchPriority = true; ?>
                        <?php foreach ($sgames as $sg): ?>
                            <li>
                                <img onclick="playMobileGame('<?php echo $sg['ext_game_name'] ?>');"
                                    <?php echo $fetchPriority ? 'fetchpriority="high"' : 'loading="lazy"'; $fetchPriority = false; ?>
                                    src="<?php fupUri("backgrounds/" . $sg['bkg_pic']) ?>"/>
                                <?php if ($this->shouldShowJpCounter() && $sg['jp_value']): ?>
                                    <?php $unique_id = uniqid() ?>
                                    <span class="jp-amount-badge jp-amount-badge-<?= $unique_id ?>" style="display: none;">
                                        <?= efEuro($sg['jp_value']) ?>
                                    </span>
                                    <script>
                                        animateJackpotBadge('jp-amount-badge-<?= $unique_id ?>');
                                    </script>
                                <?php endif; ?>
                            </li>
                        <?php endforeach ?>
                    </ul>
                </div>
            </div>
        </div>
    <?php endforeach ?>
    <?php }

    /**
     * @param array $data
     *
     * @return void
     */
    public function printAutoDynamic(array $data)
    {
        ?>
        <div class="flexslider-item">
            <div class="big-flexslider-container">
                <div class="big-flexslider">
                    <ul class="slides">
                        <?php foreach ($data as $item):
                            $game = phive("MicroGames")->getByGameRef($item['gameId'], 1, null, $this->shouldShowJpCounter());
                            ?>
                            <li>
                                <img onclick="playMobileGame('<?= $item['gameId'] ?>');"
                                     src="<?= $item['image'] ?>"/>
                                <?php displayBannerRibbonImage($game) ?>
                                <?php if ($this->shouldShowJpCounter() && isset($game['jp_value'])): ?>
                                    <?php $unique_id = uniqid() ?>
                                    <span class="jp-amount-badge jp-amount-badge-<?= $unique_id ?>" style="display: none;">
                                        <?= efEuro($game['jp_value']) ?>
                                    </span>
                                    <script>
                                        animateJackpotBadge('jp-amount-badge-<?= $unique_id ?>');
                                    </script>
                                <?php endif; ?>
                                <?php
                                isIpad() ?
                                    btnDefaultXs(t($item['button']), '', "playMobileGame('{$item['gameId']}')", 90, 'flexslider-item__button--centered') :
                                    btnDefaultM(t($item['button']), '', "playMobileGame('{$item['gameId']}')", 150, 'flexslider-item__button--centered')
                                ?>
                            </li>
                        <?php endforeach ?>
                    </ul>
                </div>
            </div>
        </div>

    <?php }

    private function shouldShowJpCounter(): bool
    {
        $jp_counter_excluded_countries = explode(' ', $this->jp_counter_excluded_countries);
        $should_show_jp_counter = $this->jp_counter && !in_array(phive('Licensed')->getLicCountry(), $jp_counter_excluded_countries);

        return $should_show_jp_counter;
    }

    /**
     * @API
     *
     * @return array
     */
    private function getAutoDynamicData(): array
    {
        $result = [];

        if (empty($this->auto_banners) || (phive()->isIpad() && count($this->auto_banners) < 2)) {
            return [];
        }

        $shuffled_banners = $this->getShuffledBanners();

        foreach ($shuffled_banners as $ext_game_name => $banner) {
            $result[] = [
                'type' => 'GAME',
                'gameId' => $ext_game_name,
                'image' => fupUri($banner, true),
                'button' => 'play.now',
                'game_name' => $this->games_arr[$ext_game_name] ?? ''
            ];
        }

        return $result;
    }

    /**
     * @API
     *
     * @param bool $is_api
     * @param string $lang
     *
     * @return array
     */
    public function getDynamicData(bool $is_api = false, string $lang = 'en'): array
    {
        $result = [];

        if ($this->hasAutoBanners()) {
            return $this->getAutoDynamicData();
        }

        if (phive()->isEmpty($this->banner_images_arr) || $this->show_banner == 'yes') {
            return [];
        }

        if ($this->randomize_games == 'yes' && $this->is_logged) {
            uksort($this->banner_images_arr, function () {
                return rand() > rand();
            });
        }

        foreach ($this->banner_images_arr as $image_alias => $link) {
            if ($is_api) {
                $banner_data['link'] = $this->formatBannerLinksForApi($link);
                $image_handler = phive("ImageHandler");
                list($banner_data['image']) = $image_handler->img($image_alias, self::IMAGE_WIDTH, self::IMAGE_HEIGHT, '', $lang);
            } else {
                $banner_data = [
                    'type' => 'PAGE',
                    'link' => llink($link),
                    'imageAlias' => $image_alias
                ];
            }

            $result[] = $banner_data;
        }

        return $result;
    }

    /**
     * Takes care of the top banners showing the banners that can be games or just banners
     */
    public function printDynamic()
    {
        $data = $this->getDynamicData();

        if(count($data) === 0) {
            return;
        }

        if($this->hasAutoBanners()) {
            $this->printAutoDynamic($data);
            return;
        }
        ?>
        <?php if (phive('Localizer')->isEditing()): ?>
            <?php foreach ($data as $item): ?>
                <div>
                    <?php img($item['imageAlias'], self::IMAGE_WIDTH, self::IMAGE_HEIGHT); ?>
                </div>
            <?php endforeach ?>
        <?php endif ?>

        <div class="flexslider-item">
            <div class="big-flexslider-container">
                <div class="big-flexslider">
                    <ul class="slides">
                        <?php $setFetchPriority = true; ?>
                        <?php foreach ($data as $item): ?>
                            <li onclick="goTo('<?php echo $item['link'] ?>')">
                                <?php img($item['imageAlias'], self::IMAGE_WIDTH, self::IMAGE_HEIGHT, null, false, null, '', '', $setFetchPriority); $setFetchPriority = false; ?>
                            </li>
                        <?php endforeach ?>
                    </ul>
                </div>
            </div>
        </div>

    <?php }

    /**
     * Handlers JS flex sliders
     */
    public function flexJs()
    { ?>
        <script>
            $(window).on("load", function () {
                $('.mobile-start-box .flexslider').flexslider({
                    animation: "slide",
                    slideshow: false,
                    directionNav: false
                });

                $('.mobile-start-box .big-flexslider').flexslider({
                    animation: "slide",
                    slideshow: <?php echo $this->rotate_top == 'yes' ? 'true' : 'false'  ?>,
                    directionNav: false
                });
            });
        </script>
    <?php }

    /**
     * Main function to take care of the general logic, the order below manages how the top parts on mobile are printed
     * and the order
     */
    public function printHTML()
    { ?>
        <script type="text/javascript" src="/phive/js/jquery.flexslider-min.js"></script>
        <script type="text/javascript" charset="utf-8">

            var cols = 1;

            function rearrangeSliders() {
                var new_cols = parseInt($(window).width() / 400);
                if (new_cols != cols) {
                    cols = new_cols;
                }
            }
        </script>
        <?php $this->flexJs() ?>
        <div class="mobile-start-box">
            <script>
                $(document).ready(function () {
                    let countryCode = '<?php echo phive('Licensed')->getLicCountry() ?>',
                        backToSlot = $('#mobile-top__back-to-battle-of-slots').outerHeight(),
                        mobileTopIt = $('.rg-mobile-top-it').outerHeight(),
                        // .container-holder $container-holder--padding-top: 42px;
                        paddingTopContHold = 42;
                    if (countryCode === 'IT' && mobileTopIt && backToSlot) {
                        let paddingTop = mobileTopIt + paddingTopContHold;
                        $('.container-holder').css('padding-top', paddingTop + "px");
                    }
                });
            </script>
            <?php if (!empty($this->img_tag) && $this->show_banner == 'yes'): ?>
                <div class="mobile-top-banner">
                    <?php atag(img($this->img_tag, self::IMAGE_WIDTH, self::IMAGE_HEIGHT, null, true), llink($this->use_link)) ?>
                </div>
            <?php endif ?>
            <?php $this->printDynamic() ?>
            <?php $this->printFlatBarSection() ?>
            <?php
                if(brandedCss() !== '') {
                    $this->printLatestNewsSliders($this->news);
                }
            ?>
            <?php $this->printGameSliders($this->sgames) ?>
            <?php $this->printGameSliders($this->mgames) ?>
            <?php $this->printUpcomingBattles(); ?>

        </div>
    <?php }

    /**
     * The box editor part
     */
    public function printExtra()
    { ?>
        <p>
            <label for="jp_counter">Jackpot Counter:</label>
            <select id="jp_counter" name="jp_counter">
                <option value="0" <?php if(empty($this->jp_counter)) echo 'selected="selected"'; ?>>No</option>
                <option value="1" <?php if($this->jp_counter) echo 'selected="selected"'; ?>>Yes</option>
            </select>
        </p>
        <p>
            <label for="jp_counter_excluded_countries">Jackpot Counter excluded countries:</label>
            <input
                id="jp_counter_excluded_countries"
                type="text"
                name="jp_counter_excluded_countries"
                value="<?= $this->jp_counter_excluded_countries; ?>"
            />
        </p>
        <p>
            Show custom custom sub tags (alias1,alias2):
            <?php dbInput('sub_tags', $this->sub_tags) ?>
        </p>
        <p>
            Show main tags (videoslots,videopoker):
            <?php dbInput('main_tags', $this->main_tags) ?>
        </p>
        <?php
        array_unshift($this->languages, 'default');
        foreach ($this->languages as $language):
            $title = "Static Banner config for '$language' language";
            $banner_link_variable = "banner_link";
            $banner_in_link_variable = "banner_in_link";
            if ($language !== 'default') {
                $banner_link_variable = "banner_link_$language";
                $banner_in_link_variable = "banner_in_link_$language";
            }
            ?>
            <h3><?= $title ?></h3>
            <h4><?= ($language === 'default' ? '(fallback if no specific language is defined)' : '') ?></h4>
            <p>
                Static Banner links to (ex: /mobile/cashier/deposit/):
                <?php dbInput($banner_link_variable, $this->{$banner_link_variable}) ?>
            </p>
            <p>
                Static Banner when logged in links to (ex: /mobile/cashier/deposit/):
                <?php dbInput($banner_in_link_variable, $this->{$banner_in_link_variable}) ?>
            </p>
            <hr>
        <?php endforeach; ?>
        <h3>Choose banner type "Static" (yes) / "Dynamic" (no)</h3>
        <p>
            Show top static banner (yes/no), if yes will hide the dynamic top banner:
            <?php dbInput('show_banner', $this->show_banner) ?>
        </p>
        <hr>
        <?php
        foreach ($this->languages as $language):
            $title = "Dynamic Banner config for '$language' language";
            $banner_links_variable = "banner_links";
            $banner_images_variable = "banner_images";
            $banner_links_logged_variable = "banner_links_logged";
            $banner_images_logged_variable = "banner_images_logged";
            $auto = 'auto';
            $auto_out = 'auto_out';
            $auto_rtp = 'auto_rtp';
            $auto_period = 'auto_period';
            $auto_category = 'auto_category';
            $auto_max = 'auto_max';
            if ($language !== 'default') {
                $banner_links_variable = "banner_links_$language";
                $banner_images_variable = "banner_images_$language";
                $banner_links_logged_variable = "banner_links_logged_$language";
                $banner_images_logged_variable = "banner_images_logged_$language";
                $auto = "auto_$language";
                $auto_out = "auto_out_$language";
                $auto_rtp = "auto_rtp_$language";
                $auto_period = "auto_period_$language";
                $auto_category = "auto_category_$language";
                $auto_max = "auto_max_$language";
            }
            ?>
            <h3><?= $title ?></h3>
            <h4><?= ($language === 'default' ? '(fallback if no specific language is defined)' : '') ?></h4>
            <p>
                Auto banners (yes/no, if empty is no as well):
                <?php dbInput($auto, $this->{$auto}) ?>
            </p>
            <p>
                Auto banners logged out (yes/no, if empty is no as well):
                <?php dbInput($auto_out, $this->{$auto_out}) ?>
            </p>
            <p>
                Auto banners - RTP (ex. 94.9):
                <?php dbInput($auto_rtp, $this->{$auto_rtp}) ?>
            </p>
            <p>
                Auto banners - Period (yesterday/month):
                <?php dbInput($auto_period, $this->{$auto_period}) ?>
            </p>
            <p>
                Auto banners - Category ('all' for all games or comma separated category list):
                <?php dbInput($auto_category, $this->{$auto_category}) ?>
            </p>
            <p>
                Auto banners - Maximum amount of banners to show (default 10):
                <?php dbInput($auto_max, $this->{$auto_max}) ?>
            </p>
            <p>
                Dynamic top banner logged out links (/link1/,/link2/):
                <?php dbInput($banner_links_variable, $this->{$banner_links_variable}) ?>
            </p>
            <p>
                Dynamic top banner logged out images (image.alias1,image.alias2):
                <?php dbInput($banner_images_variable, $this->{$banner_images_variable}) ?>
            </p>
            <p>
                Dynamic top banner logged in links (/link1/,/link2/):
                <?php dbInput($banner_links_logged_variable, $this->{$banner_links_logged_variable}) ?>
            </p>
            <p>
                Dynamic top banner logged in images (image.alias1,image.alias2):
                <?php dbInput($banner_images_logged_variable, $this->{$banner_images_logged_variable}) ?>
            </p>
            <hr>
        <?php endforeach; ?>
        <p>
            Rotate (slideshow) top banner (yes/no):
            <?php dbInput('rotate_top', $this->rotate_top) ?>
        </p>
        <p>
            Randomize logged in top games banner (yes/no):
            <?php dbInput('randomize_games', $this->randomize_games) ?>
        </p>
    <?php }

    /**
     * @param string $link
     *
     * @return string
     */
    private function formatBannerLinksForApi(string $link): string
    {
        return str_replace('/mobile', '', $link);
    }
}
