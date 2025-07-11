<?php
require_once __DIR__ . '/../../../../../diamondbet/boxes/DiamondBox.php';
require_once __DIR__ . '/../../traits/BannersTrait.php';

class JsBannerRotatorBoxBase extends DiamondBox
{
    use BannersTrait;

    /** @var string $auto Enables the auto banners, can be in for logged in, out for logged out, all or empty */
    public $auto;

    /** @var string $auto_rtp Sets the minimum RTP to show the games */
    public $auto_rtp;

    /** @var string $auto_period Sets the period, either yesterday or month*/
    public $auto_period;

    /** @var string|array $auto_category The category of the games*/
    public $auto_category;

    /** @var string|array $auto_max Maximun number of banners*/
    public $auto_max;

    /** @var array $auto_banners */
    public $auto_banners;

    /** @var array $links */
    public $links;

    /**
     *
     */
    public function init()
    {
        $this->initBannersVars();

        $this->handlePost([
            'jp_counter',
            'jp_counter_excluded_countries',
            'do_shuffle',
            'speed',
            'pause',
            'iterations',
            'easing',
            'rotate',
            'banner_num',
            'width',
            'height',
            'showfor',
            'auto',
            'auto_rtp',
            'auto_period',
            'auto_category',
            'auto_max',
        ], [
            'banner_num' => 4,
            'width' => 740,
            'height' => 280,
            'showfor' => 'all'
        ]);

        $this->banner_arr = range(1, $this->banner_num);

        if ($this->do_shuffle == 'yes') {
            phive()->shuffleAssoc($this->banner_arr);
        }

        if ($this->hasAutoBanners()) {
            $this->auto_banners = $this->getAutoBanners('desktop', 'game_id');
            $this->links = $this->getLinks();
        }

        if (isset($_POST['save_settings']) && $_POST['box_id'] == $this->getId()) {
            foreach ($this->links as $link) {
                $this->setAttribute($link['name'], trim($_POST[$link['name']]));
            }

            // Create image_aliases for the banners (Need to start from 1 here)
            for ($i = 1; $i <= $_POST['banner_num']; $i++) {
                phive('BoxHandler')->createImageDataAndAlias('top.media.' . $i . '.' . $_POST['box_id']);
            }
        }

        $this->setShow();
    }

    /**
     * @return bool True if auto banners enabled
     */
    public function hasAutoBanners()
    {
        return $this->auto === 'all' || ($this->is_logged && $this->auto === 'in') || (!$this->is_logged && $this->auto === 'out');
    }

    /**
     * @param $num
     * @param $lang
     * @return string
     */
    public function liAttr($num, $lang)
    {
        if (!isset($_GET['editstrings'])) {
            $linkname = 'link' . $num . $lang;
            $clink = $this->links[$linkname];
            if (!empty($clink)) {
                $conclick = strpos($clink, '/') === false ? "playGameDepositCheckBonus('$clink')" : "goTo('$clink')";
                return 'onclick="' . $conclick . '"';
            }
        }
        return '';
    }

    /**
     *
     */
    public function printCarouselJs()
    { ?>
        <script>
            function setIndicator(bNum) {
                var bi = $("#banner-indicator");
                bi.find("li").removeClass("current").addClass("not-current");
                if (bNum == undefined)
                    var bNum = $("#carousel").find("li").first().attr('id').split('-').pop();
                $("#bitem-" + bNum).removeClass("not-current").addClass("current");
                return bNum;
            }

            $(document).ready(function () {
                c.onRotate = setIndicator;

                $("[id^='bitem-']").click(function () {
                    var bNum = $(this).attr('id').split('-').pop();
                    c.goTo(bNum);
                    setIndicator(bNum);
                });
            });
        </script>
    <?php }

    /**
     *
     */
    public function printExtraHTML()
    {
    }

    /**
     *
     */
    public function printHTML()
    {
        if ($this->show):
            loadJs("/phive/js/jquery.easing.1.3.js");
            loadJs("/phive/js/carousel.js");

            $jp_counter_excluded_countries = explode(' ', $this->jp_counter_excluded_countries);
            $should_show_jp_counter = $this->jp_counter && !in_array(phive('Licensed')->getLicCountry(), $jp_counter_excluded_countries);
            ?>
            <script>
                var c = {};
                $(document).ready(function () {
                    c = new Carousel($("#fullFlashBox"));
                    <?php
                        if (!empty($this->speed)) {
                            echo "c.setSpeed($this->speed);";
                        }
                        if (!empty($this->pause)) {
                            echo "c.setPause($this->pause);";
                        }
                        if (!empty($this->iterations)) {
                            echo "c.setIterations($this->iterations);";
                        }
                        if (!empty($this->easing)) {
                            echo "c.setEasing('$this->easing');";
                        }
                        if (!empty($this->rotate)) {
                            echo "c.rotate();";
                        }
                    ?>
                });
            </script>
            <div class="banner-cont">
                <div id="fullFlashBox" <?php if (isset($_GET['editstrings'])) echo "style='overflow: auto;'" ?>>
                    <ul id="carousel" class="carousel">
                        <?php if (!empty($this->auto_banners) && count($banners = $this->getShuffledBanners()) > 0): ?>
                            <?php $i = 0; ?>
                            <?php foreach ($banners as $game_id => $banner):
                                $game = phive("MicroGames")->getByGameId($game_id, 0, null, $should_show_jp_counter);
                                $i++;
                                ?>
                                <li id="<?php echo "banner-$i" ?>">
                                    <img onclick="playGameDepositCheckBonus('<?php echo $game_id ?>');"
                                         src="<?php fupUri($banner) ?>"/>
                                    <?php if ($should_show_jp_counter && $game['jp_value']): ?>
                                        <?php $unique_id = uniqid() ?>
                                        <span class="jp-amount-badge jp-amount-badge-<?= $unique_id ?>" style="display: none;">
                                            <?= efEuro($game['jp_value']) ?>
                                        </span>
                                        <script>
                                            animateJackpotBadge('jp-amount-badge-<?= $unique_id ?>');
                                        </script>
                                    <?php endif; ?>
                                    <?php displayBannerRibbonImage($game) ?>
                                    <?php btnDefaultL(t('play.now'), '', "playGameDepositCheckBonus('{$game_id}')", 150, 'gradient-default') ?>
                                </li>
                            <?php endforeach ?>
                        <?php else: ?>
                            <?php $fetchPriority = true;?>
                            <?php foreach ($this->banner_arr as $num):
                                $click = $this->liAttr($num, phive('Localizer')->getLanguage());
                                ?>
                                <li id="<?php echo "banner-$num" ?>" <?php echo $click ?>>
                                    <?php img("top.media.$num." . $this->getId(), $this->width, $this->height, null, false, null, '', '', $fetchPriority); $fetchPriority = false; ?>
                                </li>
                            <?php endforeach ?>
                        <?php endif; ?>
                    </ul>
                </div>
                <?php $this->printExtraHTML() ?>
            </div>
        <?php endif ?>
    <?php }

    /**
     *
     */
    public function printExtra()
    {
        ?>
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
                type="text" name="jp_counter_excluded_countries"
                value="<?= $this->jp_counter_excluded_countries; ?>"
            />
        </p>
        <?php foreach ($this->links as $link): ?>
            <label for="<?php echo $link['name'] ?>"><?php echo $link['name'] ?></label>
            <?php dbInput($link['name'], $link['link']) ?><br/>
        <?php endforeach ?>
        <br/>
        <label for="pause">Show for logged: (in, out, all): </label><br/>
        <?php dbInput("showfor", $this->showfor) ?><br/>
        Image/swf carousel settings (leave field blank for default value):<br/>
        <label for="speed">Animation speed, in ms (1 second = 1000)</label><br/>
        <?php dbInput("speed", $this->speed) ?><br/>
        <label for="pause">Pausetime between image/swf shift, in ms</label><br/>
        <?php dbInput("pause", $this->pause) ?><br/>
        <label for="iterations">Number of iterations to rotate (0 for infinite)</label><br/>
        <?php dbInput("iterations", $this->iterations) ?><br/>
        <label for="easing">Type of animation-easing (ex easeOutBounce) </label><br/>
        <?php dbInput("easing", $this->easing) ?><br/>
        <label for="rotate">Show only the first swf/img or rotate (empty = only first, rotate = rotate) </label><br/>
        <?php dbInput("rotate", $this->rotate) ?><br/>
        <label for="banner_num"> # of banners</label><br/>
        <?php dbInput("banner_num", $this->banner_num) ?><br/>
        <label for="banner_num">Width:</label><br/>
        <?php dbInput("width", $this->width) ?><br/>
        <label for="banner_num">Height:</label><br/>
        <?php dbInput("height", $this->height) ?><br/>
        <label for="do_shuffle">Shuffle (yes/no):</label><br/>
        <?php dbInput("do_shuffle", $this->do_shuffle) ?><br/>
        <hr>
        <h3>Automatic banners configuration options:</h3>
        <label for="auto">Auto banners (in, out, all. If empty is disabled):</label><br/>
        <?php dbInput("auto", $this->auto) ?><br/>
        <label for="auto_rtp">Auto banners - RTP (ex. 94.9):</label><br/>
        <?php dbInput("auto_rtp", $this->auto_rtp) ?><br/>
        <label for="auto_period">Auto banners - Period (yesterday/month):</label><br/>
        <?php dbInput("auto_period", $this->auto_period) ?><br/>
        <label for="auto_category">Auto banners - Category ('all' for all games or comma separated category list):</label><br/>
        <?php dbInput("auto_category", $this->auto_category) ?><br/>
        <label for="auto_max">Auto banners - Maximum amount of banners to show (default 10, minimum 2):</label><br/>
        <?php dbInput("auto_max", $this->auto_max) ?><br/>

        <br/>
        <?php
    }
}
