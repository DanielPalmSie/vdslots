<?php
require_once __DIR__ . '/../../../../../diamondbet/boxes/DiamondBox.php';

class MgGameChooseBoxBase extends DiamondBox
{

    protected $aSubtopMenuItems;

    public $col_num;

    public $show_newsnum;

    public $weekend_booster_plus_rows;

    public $weekend_booster_plus_pic;

    public $live_casino_rows_position;

    public $live_casino_rows;

    public $row_selection;

    public $show_favfilter;

    public $prior_month;

    public $this_month;

    public $yesterday;

    public $show_race;

    public $top_relief;

    public $show_subheading;

    public $show_depregbtn;

    public $show_jackpots;

    public $load_sub;

    public $hide_ops;

    public $show_ops;

    public $latest_winners;

    public $sorting;

    public $nlist;

    /** @var MicroGames $mg */
    public $mg;

    /**
     * @var numeric $rtp_rows Number of rows to show as for RTP filtered games
     */
    public $rtp_rows;

    /**
     * @var string $rtp_category Category list comma separated for games or all/empty to have all categories
     */
    public $rtp_category;

    /**
     * @var string $rtp_value Min RTP value used for filtering purposes
     */
    public $rtp_value;

    /**
     * @var string $rtp_period Period we are checking games from can be yesterday or month
     */
    public $rtp_period;

    /**
     * @var array $games is a list of a displayed games
     */
    private array $games;

    /**
     * @var array $category_all_games is a list of all available games in a category
     */
    private array $category_all_games;

    /**
     * @var bool
     */
    private bool $is_api = false;

    /**
     * @var string
     */
    private string $api_provider;

    /**
     * @var string
     */
    private string $api_type;

    public function handlePost($fields, $defaults = array())
    {
        if (isset($_POST['save_settings']) && $_POST['box_id'] == $this->getId()) {
            phQdel('ghost_winners');
            phQdel('winners_users');
        }
        parent::handlePost($fields, $defaults);
    }

    public function init(bool $is_api = false, string $provider = 'all', string $type = 'all')
    {
        $this->is_api = $is_api;
        $this->api_provider = $provider;
        $this->api_type = $type;
        $this->aSubtopMenuItems = phive('Menuer')->forRender('sub-top');
        $aMenu = $aAliases = [];

        foreach ($this->aSubtopMenuItems as $aMenuItem) {
            $aMenu[] = 'tag_' . $aMenuItem['alias'];
            $aMenu[] = 'excl_operators_' . $aMenuItem['alias'];
            $aAliases[] = $aMenuItem['alias'];
        }

        $this->handlePost(
            array_merge($aMenu, [
                'jp_counter',
                'jp_counter_excluded_countries',
                'top_jp_games_slider',
                'top_jp_games_slider_items_count',
                'top_jp_slider_games_list',
                'top_jp_slider_link',
                'show_favfilter',
                'show_jackpots',
                "tag",
                "row_selection",
                'show_headline',
                'show_subheading',
                'show_depregbtn',
                'show_newsnum',
                'latest_winners',
                'top_relief',
                'hide_ops',
                'col_num',
                'show_race',
                'show_ops',
                'weekend_booster_plus_rows',
                'weekend_booster_plus_pic',
                'live_casino_rows',
                'live_casino_rows_position',
                'load_sub',
                'rtp_rows',
                'rtp_category',
                'rtp_value'
            ]),
            [
                "tag" => "all",
                "row_selection" => "3,6,9,12",
                'show_newsnum' => 0,
                'top_relief' => 'yes',
                'col_num' => 4,
                'show_race' => 'no',
                'weekend_booster_plus_rows' => 0,
                'live_casino_rows' => 0,
                'live_casino_rows_position' => 0,
                'weekend_booster_plus_pic' => 'booster',
                'load_sub' => ''
            ]
        );


        $this->mg = phive('MicroGames');
        $this->tag = empty($_REQUEST['tag']) ? $this->tag : phive('SQL')->realEscape($_REQUEST['tag']);
        // get alias by tag
        if (!empty($_REQUEST['tag']) && !empty($_REQUEST['alias']) && in_array($_REQUEST['alias'], $aAliases)) {
            $this->hide_ops_arr = empty($this->{'excl_operators_' . $_REQUEST['alias']}) ? '' : explode(',',
                $this->{'excl_operators_' . $_REQUEST['alias']});
            $this->show_ops_arr = empty($this->{'incl_operators_' . $_REQUEST['alias']}) ? '' : explode(',',
                $this->{'incl_operators_' . $_REQUEST['alias']});
        } else {
            $this->hide_ops_arr = empty($this->hide_ops) ? '' : explode(',', $this->hide_ops);
            $this->show_ops_arr = empty($this->show_ops) ? '' : explode(',', $this->show_ops);
        }

        $this->setupGames();
        $this->operators = phive("MicroGames")->gameOperators($this->category_all_games, 1, 'flash', $this->hide_ops_arr, $this->show_ops_arr, $is_api);
        $this->ellipsis_len = 18;
        $this->loggedin = isLogged();
        $this->cur_lang = phive('Localizer')->getLanguage();

        if (isset($_POST['save_settings']) && $_POST['box_id'] == $this->getId()) {
            $this->setAttribute("category", implode(',', $_POST['category']));
        }

        $categoryAttribute = $this->getAttribute("category");
        if ($this->attributeIsSet("category") && $categoryAttribute) {
            $this->category = explode(',', $categoryAttribute);
        } else {
            $this->category = "ALL";
        }
        $ids = $this->category == "ALL" ? $this->category : implode(',', $this->category);
        $where = $this->category == "ALL" ? "1" : "category_id IN($ids)";
        if ($this->show_newsnum > 0) {
            $this->news = phive('LimitedNewsHandler')->getLatestTopList(cLang(), "ALL", "APPROVED", " AND $where",
                $this->show_newsnum);
        }
    }

    public function setCommon($key, $def_value)
    {
        if ($_REQUEST['func'] == 'printGameList') {
            if (empty($_REQUEST[$key]) && empty($_REQUEST['page'])) {
                $this->$key = $_SESSION[$key] = $def_value;
            } else {
                $this->$key = $_SESSION[$key] = empty($_REQUEST[$key]) ? $_SESSION[$key] : phive('SQL')->realEscape($_REQUEST[$key]);
            }
        } else {
            $this->$key = $_SESSION[$key] = $def_value;
        }
    }

    public function resetSess()
    {
        $this->subtag = $_SESSION['subtag'] = 'all';
        $this->operator = $_SESSION['operator'] = 'all';
    }

    public function setCommonAjax($key, $val = '')
    {
        $val = empty($val) ? phive('SQL')->realEscape($_REQUEST[$key]) : $val;
        if ($_REQUEST['func'] == 'printGameList') {
            if (!empty($val)) {
                $this->$key = $_SESSION[$key] = $val;
            } else {
                if (!empty($_SESSION[$key])) {
                    $this->$key = $_SESSION[$key];
                }
            }
        } else {
            $this->resetSess();
        }
    }

    public function setSubTag()
    {
        $this->setCommonAjax('subtag');
    }

    public function setSorting()
    {
        $this->setCommon('sorting', $this->fav_sort_col . ' DESC');
    }

    public function setOperator()
    {
        $this->setCommonAjax('operator');
    }

    public function setRows()
    {
        $this->setCommonAjax('rows');
    }

    public function setupSubTags($dev_type = 'flash')
    {
        if($this->is_api) {
            $this->sub_tags = $this->mg->getAllSubTags($this->tag, $dev_type, true, $this->api_provider);
        } else {
            $this->sub_tags = empty($this->load_sub) ? $this->mg->getAllSubTags($this->tag, $dev_type) : [$this->load_sub];
        }

        $this->subsel = array();

        foreach ($this->sub_tags as $alias) {
            $this->subsel[$alias] = t($alias);
        }
    }

    public function setupGames()
    {
        $n_booster_games = $this->weekend_booster_plus_rows * $this->col_num;
        $n_rtp_games = $this->rtp_rows * $this->col_num;
        $n_live_casino = $this->live_casino_rows * $this->col_num;
        $booster_games = [];
        $live_casino_booster = [];
        $rtp_games = [];

        $this->tags = $this->mg->getAllTags();

        $this->fav_sort = array(
            'prior_month' => phive()->lastMonth(),
            'yesterday' => phive()->yesterday(),
            'this_month' => date('Y-m')
        );

        $this->fav_sort_col = !empty($this->show_favfilter) ? 'played_times_in_period' : 'played_times';

        $this->pcount = empty($_REQUEST['period_count']) ? $this->fav_sort[$this->show_favfilter] : $_REQUEST['period_count'];

        $this->setSubTag();
        $this->setSorting();
        $this->setOperator();

        if (!empty($_REQUEST['reset'])) {
            $this->resetSess();
        }

        $this->row_sel = array();
        $tmp = explode(',', $this->row_selection);
        $this->rows = $tmp[0] * $this->col_num;
        foreach ($tmp as $n) {
            $this->row_sel[$n] = "$n " . t('rows');
        }

        $this->setRows();

        $this->str_tag = $this->tag;
        $this->tag = explode(',', $this->str_tag);

        if (!empty($_SESSION['show_gp'])) {
            $this->operator = $_SESSION['show_gp'];
            unset($_SESSION['show_gp']);
        }

        if ($_REQUEST['show'] == 'own-favs') {
            if (isLogged()) {
                $games = $this->mg->getFavorites($_SESSION['mg_id']);
            } else {
                $games = $this->mg->getPopular(60);
            }
        } else {
            if ($_REQUEST['show'] == 'favs') {
                $games = $this->mg->getFavored(100);
            } else {
                if (!empty($this->load_sub) && empty($_SESSION[$this->load_sub])) {
                    $this->subtag = $this->load_sub;
                }

                if($this->is_api) {
                    $this->subtag = phive('SQL')->realEscape($this->api_type);
                }

                $games = $this->mg->getTaggedBy(
                    $this->tag, null, null, $this->subtag, $this->sorting,
                    "mg.device_type = 'flash'", $this->pcount, false, true, $this->operator, $this->hide_ops_arr,
                    $this->show_ops_arr);
            }
        }

        $per_page = $this->rows;
        $this->p = phive('Paginator');
        $this->p->setPages(count($games), '', $per_page);

        /* Booster: Reorder games to show the games with extra payout at first rows and extract live-casino games to show them in a row */
        list($games, $booster_games, $rtp_games, $live_casino_booster) = $this->mg->extractBoostedGamesFromList($games,
            $n_booster_games, $n_live_casino, $n_rtp_games, $this->rtp_value, $this->rtp_category);

        $games = array_merge($booster_games, $rtp_games, $games); // add booster games at beginning
        $this->category_all_games = $games;

        $insert_live_casino_index = $this->live_casino_rows_position > 0 ? ($this->live_casino_rows_position - 1) * $this->col_num : 0;
        array_splice($games, $insert_live_casino_index, 0, $live_casino_booster);

        $games = array_slice($games, $this->p->getOffset($per_page), $per_page);

        if (isLogged()) {
            $fids = $this->mg->favIds($_SESSION['mg_id']);
            foreach ($games as &$g) {
                $g['fav'] = in_array($g['id'], $fids);
            }
        }

        $this->games = array_chunk($games, $this->col_num);
        $this->setupSubTags();
    }

    public function toggleFav()
    {
        echo $this->mg->toggleFavorite($_SESSION['mg_id'], $_REQUEST['gid']);
    }

    public function gameHover($g)
    { ?>
        <div class="game-over">
            <?php btnSmall(t('play.now'), '', "playGameDepositCheckBonus('{$g['game_id']}')") ?>
        </div>
    <?php }

    public function printGameList($games = array())
    {
        addCacheHeaders("cache3600");

        $games = empty($games) ? $this->games : $games;
        ?>
        <?php foreach ($games as $g_chunk): ?>
        <div class="game-row">
            <?php foreach ($g_chunk as $g): ?>
                <div class="game col-size-<?= $this->col_num ?>">
                    <?php $this->printGame($g) ?>
                </div>
            <?php endforeach ?>
        </div>
        <hr/>
    <?php endforeach ?>
        <div>
            <?php if (!empty($this->p)) {
                $this->p->render('goToPage');
            } ?>
        </div>
    <?php }

    public function printGame($game, $show_jp_counter = false, $show_high_thumbnail = false)
    {
        $is_jp_counter_shown = $this->shouldShowJpCounter() && $show_jp_counter && $game['jp_value'];

        $game_top_extra_classes = '';
        if ($is_jp_counter_shown) {
            $game_top_extra_classes .= 'game-top-with-jp';
        }
        if ($show_high_thumbnail) {
            $game_top_extra_classes .= ' game-top-with-high-thumbnail';
        }

        ?>
        <div class="game-top <?= $game_top_extra_classes ?>">
            <div class="img-wrapper">
                <img class="game-image" loading="lazy" src="<?php echo $this->mg->carouselPic($game, $show_high_thumbnail)?>"
                     title="<?php echo $game['game_name'] ?>" alt="<?php echo $game['game_name'] ?>"/>

                <?php if (!$this->mg->isEnabled($game)): ?>
                    <img src="<?php fupUri("game_under_construction.png") ?>"
                         class="game-under-construction"/>
                <?php else: ?>
                    <?php
                        displayGameRibbonImage($game, ['weekend_booster' => $this->weekend_booster_plus_pic], false, $show_high_thumbnail);
                        $this->gameHover($game);
                    ?>
                <?php endif ?>

                <?php if ($is_jp_counter_shown): ?>
                    <?php $unique_id = uniqid() ?>
                    <div class="thumbnail-jp-amount-badge jp-amount-badge-<?= $unique_id ?>" style="display: none;">
                        <span>
                            <?= efEuro($game['jp_value']) ?>
                        </span>
                    </div>
                    <script>
                        animateJackpotBadge('jp-amount-badge-<?= $unique_id ?>');
                    </script>
                <?php endif; ?>
            </div>
            <div class="game-text">
                <?php if (isset($game['fav'])): ?>
                    <div id="star-<?php echo $game['id'] ?>" onclick="toggleFav(<?php echo $game['id'] ?>)"
                         class="gchoose-fav-icon <?php echo $game['fav'] ? 'ystar' : 'gstar' ?>"></div>
                <?php endif ?>
                <a
                    id="game-bottom-link-<?= $game['id'] ?>"
                    href="<?php echo phive('MicroGames')->getUrl('', $game) ?>"
                >
                    <?php echo $game['game_name'] ?>
                    <?php if (!empty($this->fav_icon)): ?>
                        <img src="<?php echo $this->fav_icon ?>" alt="<?php et('') ?>"
                             title="<?php et('jackpot') ?>"/>
                    <?php endif ?>
                </a>

                <script>
                    $(function() {
                        const id = '<?= $game['id'] ?>';
                        const gameId = '<?= $game['game_id'] ?>';

                        $(`#game-bottom-link-${id}`).on('click', function(event) {
                            event.preventDefault();
                            playGameDepositCheckBonus(gameId);
                        });
                    });
                </script>
            </div>
        </div>
    <?php }

    protected function shouldShowJpCounter(): bool
    {
        $jp_counter_excluded_countries = explode(' ', $this->jp_counter_excluded_countries);
        $should_show_jp_counter = $this->jp_counter && !in_array(phive('Licensed')->getLicCountry(), $jp_counter_excluded_countries);

        return $should_show_jp_counter;
    }

    public function printMainCats()
    { ?>
        <?php foreach ($this->aSubtopMenuItems as $item): ?>
        <li>
            <?php
            if (!empty($this->{'tag_' . $item['alias']})) {
                echo '<a href="javascript:void();" alias="' . str_replace('"', '',
                        $item['alias']) . '" tags="' . str_replace('"', '',
                        $this->{'tag_' . $item['alias']}) . '" class="doAjax">';
            } else {
                echo '<a ' . $item['params'] . '>';
            }
            ?>
            <img src="<?php echo $this->mg->tagIcon($item['alias']) ?>" title="<?php echo $item['txt'] ?>"
                 alt="<?php echo $item['txt'] ?>"/>
            <?php echo $item['txt'] ?>
            </a>
        </li>
    <?php endforeach ?>
    <?php }

    public function css()
    {
        //$this->fancyPlaycss();
    }

    public function searchJs()
    { ?>
        <script>
            $(document).ready(function () {
                setupCasinoSearch(
                    function (i, o) {
                        var isUserLoggedIn = <?php echo isLogged() ? 'true' : 'false'; ?>;
                        var lang = (cur_lang === default_lang) ? '' : ('/' + cur_lang);
                        var ellipsisName = ellipsis(this.game_name, <?php echo $this->ellipsis_len ?>);
                        var imgSrc = '<?php echo getMediaServiceUrl(); ?>' + '/file_uploads/' + this.tag + '_icon.png';
                        const gameId = this.game_id;

                        var listItem = '<li>';
                        if (isUserLoggedIn) {
                            listItem += `<a id="search-result-game-${this.id}" href="${lang}/games/${this.game_url}/">`;
                        } else {
                            listItem += '<a href="' + lang + '/games/' + this.game_url + '/">';
                        }
                        listItem += ellipsisName + '<img src="' + imgSrc + '" />';
                        listItem += '</a></li>';

                        $("#search-result").append(listItem);

                        $(`#search-result-game-${this.id}`).on('click', function(event) {
                            event.preventDefault();

                            onclickSearchGame('' + gameId);
                        });
                    },
                    function () {
                        $("#search-result").html($("#search-default").html());
                    }
                );
            });
        </script>
    <?php }

    public function webkit()
    { ?>
        if($.support.reliableMarginLeft()){
            $(".game-row .game").css({"margin-left": "4px"});
        }
    <?php }

    public function setupSubTagDropDown()
    { ?>
        const subtagSelectBox = $("#subtag").selectbox({
            onChange: function(val, inst) {
                $('#subtag option:selected').removeAttr('selected');
                $('#subtag option[value="' + val + '"]').prop('selected', true);
                subtagSelectBox.next().attr('title', subtagSelectBox.find(":selected").text());
                filterBySubTag(val);
            }
        });
        subtagSelectBox.next().attr('title', subtagSelectBox.find(":selected").text());
    <?php }

    public function js()
    { ?>
        <script>

            function onclickSearchGame($gameId){
                playGameDepositCheckBonus($gameId);
            }

            function setupOvers() {

                var cur_speed = 100;
                var cur_easing = 'linear';

                $(".game-top").hover(
                    function () {
                        $(this).find(".game-over").animate({opacity: 1}, cur_speed, cur_easing);
                    },
                    function () {
                        $(this).find(".game-over").animate({opacity: 0}, cur_speed, cur_easing);
                    }
                );
            }

            function listGames(params) {
                const sortingFromSession = getSortingOrderFromSession();

                if(sortingFromSession && !params.sorting) {
                    params.sorting = sortingFromSession;
                }

                if(params.reset) {
                    cleanSortingOrderFromSession();
                }

                params.operator = $("#operator :selected").val();
                params.subtag = $("#subtag :selected").val();

                const printGameListParams = Object.assign({}, params, { func: 'printGameList' });
                const getFavouriteGamesParams = Object.assign({}, params, { func: 'getFavouriteGames' });

                const printGameListPromise = new Promise((resolve, reject) => {
                    ajaxGetBoxHtml(printGameListParams, cur_lang, <?php echo $this->getId() ?>, function (ret) {
                        $("#gch-list").html(ret);
                        setupOvers();
                        unifyGameImageHeight($("#gch-list"));
                        resolve();
                    });
                });

                printGameListPromise
                    .then(() => {
                        return new Promise((resolve, reject) => {
                            ajaxGetBoxJson(getFavouriteGamesParams, cur_lang, <?php echo $this->getId() ?>, function (ret) {
                                applyFavoriteGames(ret);
                                resolve();
                            });
                        });
                    });
            }

            function printJackpotsSlider() {
                ajaxGetBoxHtml({ func: 'getJackpotsSlider' }, cur_lang, <?php echo $this->getId() ?>, function (ret) {
                    $("#gch-top-slider").html(ret);
                    setupOvers();
                });
            }

            function applyFavoriteGames(gameIds) {
                $('[id^="star-"]').each(function() {
                    if(this.classList.contains('ystar')){
                        $(this).removeClass('ystar').addClass('gstar');
                    }
                });
                $.each(gameIds, function (index, gameId) {
                    var gameStar = $("#star-" + gameId);
                    if (gameStar.hasClass('gstar')) {
                        gameStar.removeClass('gstar').addClass('ystar');
                    }
                });
            }

            /**
             * Unifies the height of game images in a row. Check CANV-7441 for more details.
             * @param {jQuery} wrapper - The jQuery object representing the game row
             */
            function unifyGameImageHeight(wrapper = null) {
                $(wrapper).find('.game-row .game:first-child .img-wrapper > .game-image').each(function () {
                    const $firstImage = $(this);
                    const $gameRow = $firstImage.closest('.game-row');

                    const adjustHeight = () => {
                        const itemCount = <?= $this->col_num ?>;

                        const gapCount = itemCount - 1;
                        const gapSize = 8;
                        const totalGap = gapCount * gapSize;

                        const containerWidth = $gameRow.width();

                        const itemWidth = Math.round((containerWidth - totalGap) / itemCount);

                        const scalingFactor = itemWidth / 155;

                        const itemHeight = Math.round(scalingFactor * 130);

                        $gameRow.find('.img-wrapper > .game-image, .img-wrapper > .ribbon-pic').css({
                            width: itemWidth,
                            height: itemHeight
                        });

                        $gameRow.find('.img-wrapper > .game-over, .game-text').css({
                            width: itemWidth
                        });
                    };

                    $firstImage.on('load', function () {
                        adjustHeight();
                    });

                    if ($firstImage[0].complete) {
                        adjustHeight();
                    }
                });
            }

            function filterByTag(p_sAlias, p_sTag) {
                listGames({alias: p_sAlias, tag: p_sTag});
            }

            function goToPage(pnr) {
                listGames({page: pnr});
            }

            function filterByoperator(net) {
                listGames({operator: net});
            }

            function filterBySubTag(sub) {
                listGames({subtag: sub});
            }

            function showMostPlayed(periodCount, sortBy) {
                var sortBy = empty(sortBy) ? 'played_times' : sortBy;
                listGames({sorting: sortBy + ' DESC', period_count: periodCount, reset: 'true'});
            }

            function showFavs() {
                listGames({show: 'favs'});
            }

            function showOwnFavs() {
                listGames({show: 'own-favs'});
            }

            function showStart() {
                showMostPlayed(<?php echo "'{$this->pcount}','{$this->fav_sort_col}'" ?>);
            }

            function aToZ() {
                listGames({sorting: 'game_name ASC'});
            }

            function zToA() {
                listGames({sorting: 'game_name DESC'});
            }

            function getSortingOrderFromSession() {
                const sortingOrder = sessionStorage.getItem('aToZ');
                if (sortingOrder === null) return null;

                return sortingOrder === 'true' ? 'game_name ASC' : 'game_name DESC';
            }

            function cleanSortingOrderFromSession() {
                sessionStorage.removeItem('aToZ');
            }

            function toggleFav(gid) {
                star = $("#star-" + gid);
                if (star.hasClass('ystar'))
                    star.removeClass('ystar').addClass('gstar');
                else
                    star.removeClass('gstar').addClass('ystar');

                ajaxGetBoxHtml({func: 'toggleFav', gid: gid}, cur_lang, <?php echo $this->getId() ?>, function (ret) {
                });
            }

            function decodeEncodedString(encodedString) {
                return decodeURIComponent(encodedString.replace(/\+/g, ' '));
            }

            var curGame = '';

            $(document).ready(function () {
                var hash = window.location.hash;
                var fragmentIdentifier = decodeEncodedString(hash.slice(1));
                if ((fragmentIdentifier) && ($("#operator").val().toLowerCase() !== fragmentIdentifier.toLowerCase())) {
                    $('#operator option').each(function() {
                        if($(this).val().toLowerCase() === fragmentIdentifier.toLowerCase()){
                           $(this).attr('selected', 'selected').change();
                           fragmentIdentifier = $(this).val() ;
                           return false;
                        }
                    });
                    filterByoperator(fragmentIdentifier);
                } else {
                    <?php $this->setupSubTagDropDown() ?>

                    <?php if(!empty($_SESSION['show_gp_ajax'])): ?>
                    filterByoperator('<?= $_SESSION['show_gp_ajax']?>');
                    <?php unset($_SESSION['show_gp_ajax']); ?>
                    <?php else: ?>
                    showStart();
                    <?php endif; ?>
                }

                cleanSortingOrderFromSession();
                $('#a-to-z').on('click', function() {
                    if ($(this).html() == 'A-Z') {
                        sessionStorage.setItem('aToZ', 'false');
                        listGames({sorting: 'game_name DESC'});
                        $(this).html('Z-A');
                    } else {
                        sessionStorage.setItem('aToZ', 'true');
                        listGames({sorting: 'game_name ASC'});
                        $(this).html('A-Z');
                    }
                });

                $("#showrows").selectbox({
                    classHolder: 'sbSmallHolder',
                    classOptions: 'sbSmallOptions',
                    onChange: function (val, inst) {
                        listGames({rows: val * <?=(int)$this->col_num?>});
                    }
                });

                const operatorSelectBox = $("#operator").selectbox({
                    classHolder: 'sbMediumHolder',
                    classOptions: 'sbMediumOptions',
                    onChange: function (val, inst) {
                        $('#operator option').prop('selected', false); // clear all
                        $('#operator option[value="' + val + '"]').prop('selected', true); // set new one
                        operatorSelectBox.next().attr('title', operatorSelectBox.find(":selected").text());
                        filterByoperator(val)
                    }
                });
                operatorSelectBox.next().attr('title', operatorSelectBox.find(":selected").text());

                <?php $this->webkit() ?>

                const isJackpotsSliderEnabled = <?= $this->top_jp_games_slider ? 'true' : 'false'; ?>;
                if (isJackpotsSliderEnabled) {
                    printJackpotsSlider();
                }

                setupOvers();
            });
        </script>
    <?php }

    public function searchInput($id = "search_str", $alias = 'search.casino.games')
    { ?>
        <div class="search-cont">
            <div class="search-bar">
                <?php dbInput($id, t2($alias, $this->mg->countWhere()), "text", "search-games") ?>
                <span class="icon icon-vs-search"></span>
            </div>
        </div>
    <?php }

    public function search()
    { ?>
        <script type="text/javascript">
            <!--
            $(document).ready(function () {
                $("#search-result a.doAjax").click(function (event) {
                    event.preventDefault();
                    filterByTag($(this).attr('alias'), $(this).attr('tags'));
                });
            });
            //-->
        </script>
        <?php $this->searchInput() ?>
        <ul id="search-result">
            <?php $this->printMainCats() ?>
        </ul>
    <?php }

    public function favBtn()
    {
        btnDefaultL(t("popular.games"), '', 'showStart()', 105);
        //dynBtnYellow(t("favorites"), '', 'mini');
    }

    public function filterBar()
    { ?>
        <table class="gch-right-top-table">
            <tr>
                <td class="gch-item">
                    <?php dbSelect('operator', $this->operators, $this->operator, array('all', t('all.providers'))) ?>
                </td>
                <td id="subtag-section" class="gch-item">
                    <?php dbSelect('subtag', $this->subsel, $this->subtag, array('all', t('all.' . $this->str_tag))) ?>
                </td>
                <td id="fav-btn-section" class="gch-item">
                    <?php echo $this->favBtn() ?>
                </td>
                <td class="gch-item">
                    <button id="a-to-z" class="gch-grey-btn a-to-z">A-Z</button>
                </td>
                <td class="gch-item">
                    <button class="show-own-favs-btn" onclick="showOwnFavs()"></button>
                </td>
                <td class="gch-item">
                    <?php dbSelect('showrows', $this->row_sel) ?>
                </td>
            </tr>
        </table>
    <?php }


    public function showExtraFavFilter()
    {
        if ($this->show_favfilter != 'yes') {
            return;
        }
        ?>
        <br clear="all"/>
        <br clear="all"/>
        <br clear="all"/>
        <?php btnDefaultXl(t('lastmonth.fav'), '',
        "showMostPlayed('{$this->prior_month}', 'played_times_in_period')") ?>
        <br clear="all"/>
        <br clear="all"/>
        <?php btnDefaultXl(t('thismonth.fav'), '', "showMostPlayed('{$this->this_month}', 'played_times_in_period')") ?>
        <br clear="all"/>
        <br clear="all"/>
        <?php btnDefaultXl(t('yesterday.fav'), '', "showMostPlayed('{$this->yesterday}', 'played_times_in_period')") ?>
    <?php }

    public function getTopJackpots($count, $where_device = "gms.device_type = 'flash'")
    {
        $ciso = ciso();

        $country = phive('Licensed')->getLicCountry(cu());
        $jurisdiction = licJur();
        $cache = phive()->getSetting('top_jackpot_cache_time', 7200);

        $jps = phive('MicroGames')->getAllJpsGames("
            jps.currency = '$ciso'
            AND gms.blocked_countries NOT LIKE '%{$country}%'
            AND gms.blocked_provinces NOT LIKE '%{$jurisdiction}%'
            ",
            $where_device,
            "GROUP BY jps.jp_id", [0, $count ?? $this->show_jackpots], $cache);

        return $jps;
    }

    public function jackpots()
    {

        if (empty($this->show_jackpots) || lic('hideJackpots') === true) {
            return;
        }

        $jps = $this->getTopJackpots($this->show_jackpots);

        if (empty($jps)) {
            return;
        }

        ?>
        <div class="mgchoose-article-headline margin-ten-top">
            <?php et('mgchoose.jackpots') ?><span></span>
        </div>
        <div class="mg-choose-jackpots">
            <ul class="jcarousel-skin-winners">
                <?php foreach ($jps as $jp): ?>
                    <li>
                        <img title="<?php echo $jp['game_name'] ?>" alt="<?php echo $jp['game_name'] ?>"
                             class="img-left" style="" onclick="<?php echo "playGameDepositCheckBonus('{$jp['game_id']}')" ?>"
                             src="<?php echo $this->mg->carouselPic($jp) ?>"/>
                        <div class="winner-line" onclick="<?php echo "playGameDepositCheckBonus('{$jp['game_id']}')" ?>">
                            <div class="mgchoose-article-headline" title=" <?php echo $jp['game_name'] ?>">
                                <?php echo phive()->ellipsis(rep($jp['game_name'], 20)) ?>
                            </div>
                            <div class="jp-amount"><?php efEuro($jp['jp_value']) ?></div>
                        </div>
                    </li>
                <?php endforeach ?>
            </ul>
            <div class="gch-left-readmore"><a href="/jackpots"><?php et('read.more') ?></a></div>
        </div>
    <?php }

    public function raceLeaderBoard()
    {
        if ($this->show_race == 'no') {
            return;
        }
        $races = phive('Race')->getActiveRaces();
        list($entries, $prizes) = phive('Race')->leaderBoard($races[0]);
        $res = array_slice($entries, 0, 10);
        $this->printRaceJs(true);
        ?>
        <div class="mgchoose-article-headline margin-ten-top">
            <?php et('casino.race') ?><span></span>
        </div>
        <table class="casino-races" border="0" cellspacing="0" cellpadding="0">
            <thead>
            <tr>
                <th colspan="2"><?php et('first.name') ?></th>
                <th><?php et('spins') ?></th>
                <th><?php et('prize') ?></th>
                <th>&nbsp;</th>
            </tr>
            </thead>
            <tbody>

            <?php
            foreach ($res as $key => $aRace):
                ?>
                <tr id="raceuser-<?php echo $aRace['user_id']; ?>">
                    <td class="race-position"><?php echo($key + 1) ?></td>
                    <td class="race-fname"><?php echo $aRace['firstname'] ?></td>
                    <td class="race-amount"><?php $this->printRaceAmount($aRace['race_balance'], $aRace) ?></td>
                    <td class="race-prize"><?php echo $this->fmtRacePrize($prizes[$key]) ?></td>
                    <td class="race-arrow"></td>
                </tr>
            <?php
            endforeach;

            echo '
            </tbody>
        </table>
        <div class="gch-left-readmore"><a href="/races/monthly/">' . t('read.more') . '</a></div>';
    }

    public function printHTML()
    {
        $this->includes();
        $this->css();
        $this->js();
        $this->searchJs();
        ?>
        <div class="frame-block <?php if ($this->top_relief != 'yes') echo 'fb-background' ?>">
            <div class="frame-holder <?php if ($this->top_relief != 'yes') echo 'pad-zero-top' ?>">
                <?php if ($this->show_headline == 'yes'): ?>
                    <h3> <?php et("mgchoose.{$this->str_tag}.headline") ?> </h3>
                <?php endif ?>
                <?php if ($this->show_subheading == 'yes'): ?>
                    <?php et("mgchoose.{$this->str_tag}.subhead.html") ?>
                    <br/>
                <?php endif ?>
                <div class="gch-left">
                    <?php $this->search() ?>
                    <?php if ($this->show_depregbtn == 'yes'): ?>
                        <?php if (isLogged()): ?>
                            <?php btnDefaultXl(t('deposit'), '', depGo(), '', 'deposit-btn') ?>
                        <?php else: ?>
                            <?php btnDefaultXl(t('register'), '?signup=true', '', '', 'register-btn-home') ?>
                        <?php endif ?>
                    <?php endif ?>
                    <?php $this->showExtraFavFilter() ?>

                    <?php if (!empty($this->news)): ?>
                        <div class="mgchoose-article-headline margin-ten-top">
                            <?php et('latest.news') ?><span></span>
                        </div>
                        <div id="news-list-container">
                            <?php foreach ($this->news as $n):
                                $stamp = strtotime($n->getTimeCreated());
                                $cur_link = llink($this->nlist->getArticleUrl($n));
                                ?>
                                <div class="mgchoose-news-list-item">
                                    <div class="img-left">
                                        <a href="<?php echo $cur_link ?>">
                                            <?php img($n->getImagePath(), 40, 38) ?>
                                        </a>
                                    </div>
                                    <div class="mgchoose-article-headline">
                                        <a href="<?php echo $cur_link ?>">
                                            <?php echo rep($n->getHeadline()) ?>
                                        </a>
                                    </div>
                                    <?php
                                    $this->nlist->drawArticleInfo($n, $stamp, 'mgchoose-article-info')
                                    ?>
                                </div>
                            <?php endforeach ?>
                            <div class="gch-left-readmore"><a href="/news"><?php et('read.more') ?></a></div>
                        </div>
                    <?php endif ?>
                    <?php $this->raceLeaderBoard() ?>
                    <?php $this->jackpots() ?>
                </div>
                <div class="gch-right">
                    <div class="gch-right-top">
                        <?php $this->filterBar() ?>
                    </div>
                    <div id="gch-top-slider"></div>
                    <div id="gch-list"></div>
                </div>
                <ul id="search-default" style="display: none;">
                    <?php $this->printMainCats() ?>
                </ul>
            </div>
        </div>
    <?php }

    public function flexJs()
    { ?>
        <script>
            $(function() {
                $('.flexslider').flexslider({
                    animation: 'slide',
                    slideshow: false,
                    directionNav: false,
                    itemWidth: 120,
                    itemMargin: 8,
                    minItems: 5,
                    maxItems: 5
                });
            });
        </script>
    <?php }

    public function getJackpotsSlider()
    {
        $games = $this->getJackpotSliderGames();

        $this->flexJs()
        ?>

        <div class="game-choose-top-slider flexslider-item">
            <div class="game-choose-top-slider__header flexslider-headline">
                <h6 class="game-choose-top-slider__title">
                    <?= t('jackpots-slider.title') ?>
                </h6>
                <?php if($this->top_jp_slider_link): ?>
                    <a href="<?= llink('/jackpots') ?>" class="game-choose-top-slider__header-link">
                        <?= t('jackpots-slider.title-link') ?>
                    </a>
                <?php endif ?>
            </div>
            <div class="flexslider-container">
                <div class="flexslider">
                    <ul class="slides game-row">
                        <?php foreach ($games as $game): ?>
                            <li class="game col-size-6">
                                <?php $this->printGame($game, true, true) ?>
                            </li>
                        <?php endforeach ?>
                    </ul>
                </div>
            </div>
        </div>
    <?php }

    public function getJackpotSliderGames(string $device_type = 'flash'): array
    {
        $jackpot_tags = [
            'videoslots_jackpot',
            'slots_jackpot',
            'videoslots_jackpotbsg',
            'videoslots_jackpotsheriff',
        ];

        $games = [];

        // firstly get games from the manually added list
        if ($this->top_jp_slider_games_list) {
            $game_ids = explode(',', $this->top_jp_slider_games_list);
            $game_ids_in = phive('SQL')->makeIn($game_ids);

            $extra_where = $device_type === 'flash'
                ? " AND mg.game_id IN ($game_ids_in) AND micro_jps.jp_value IS NOT NULL"
                : " AND IF(mg.game_id != '', mg.game_id, mg_desktop.game_id) IN ($game_ids_in) AND micro_jps.jp_value IS NOT NULL";

            $games = $this->mg->getTaggedBy(
                $jackpot_tags,
                null,
                null,
                null,
                'mg.game_name ASC',
                "mg.device_type = '$device_type'",
                '',
                true,
                true,
                '',
                [],
                [],
                true,
                $extra_where
            );

            // sort games in a same order as specified in 'top_jp_slider_games_list' attribute
            $flipped = array_flip($game_ids);
            usort($games, function($a, $b) use ($flipped) {
                return $flipped[$a['game_id']] < $flipped[$b['game_id']] ? -1 : 1;
            });
        }

        // then get top played Jackpot games from yesterday
        $yesterday = date('Y-m-d', strtotime('yesterday'));
        $top_played_jackpot_games = $this->mg->getTaggedBy(
            $jackpot_tags,
            0,
            +$this->top_jp_games_slider_items_count,
            null,
            'played_times_in_period desc',
            "mg.device_type = '$device_type'",
            $yesterday,
            true,
            true,
            '',
            [],
            [],
            true,
            " AND micro_jps.jp_value IS NOT NULL "
        );

        $games = phive()->uniqByKey(
            array_merge($games, $top_played_jackpot_games),
            'game_id'
        );

        return array_slice($games, 0, $this->top_jp_games_slider_items_count);
    }

    public function printExtra(){
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
                type="text"
                name="jp_counter_excluded_countries"
                value="<?= $this->jp_counter_excluded_countries; ?>"
            />
        </p>
        <p>
            <label for="top_jp_games_slider">Top Jackpot games slider:</label>
            <select id="top_jp_games_slider" name="top_jp_games_slider">
                <option value="0" <?php if(empty($this->top_jp_games_slider)) echo 'selected="selected"'; ?>>No</option>
                <option value="1" <?php if($this->top_jp_games_slider) echo 'selected="selected"'; ?>>Yes</option>
            </select>
        </p>
        <p>
            <label for="top_jp_games_slider_items_count">Top Jackpot games slider items count:</label>
            <input
                id="top_jp_games_slider_items_count"
                type="number"
                min="1"
                step="1"
                name="top_jp_games_slider_items_count"
                value="<?php echo $this->top_jp_games_slider_items_count ?>"
            />
        </p>
        <p>
            <label for="top_jp_slider_games_list">
                Top Jackpot slider games list (comma separated list of game_id, e.g. <b>netent_hallofgods_not_mobile_sw,playtech_jpgt</b>).
                <br /> Listed games will be shown first in a slider. Top played games from yesterday will be shown after them.
            </label>
            <br />
            <textarea
                id="top_jp_slider_games_list"
                type="text"
                name="top_jp_slider_games_list"
                style="resize: none"
                cols="80"
                rows="5"
            ><?php echo $this->top_jp_slider_games_list ?></textarea>
        </p>
        <p>
            <label for="top_jp_slider_link">Top Jackpot slider link to jackpots page:</label>
            <select id="top_jp_slider_link" name="top_jp_slider_link">
                <option value="0" <?php if(empty($this->top_jp_slider_link)) echo 'selected="selected"'; ?>>No</option>
                <option value="1" <?php if($this->top_jp_slider_link) echo 'selected="selected"'; ?>>Yes</option>
            </select>
        </p>
        <p>
            Game tag(s), ex videoslots,blackjack or just videoslots:
            <input type="text" name="tag" value="<?php echo $this->str_tag ?>"/>
        </p>
        <p>
            Row selection, ex 4,6,9 (the all option is hardcoded):
            <input type="text" name="row_selection" value="<?php echo $this->row_selection ?>"/>
        </p>
        <p>
            Show headline (yes/no):
            <input type="text" name="show_headline" value="<?php echo $this->show_headline ?>"/>
        </p>
        <p>
            Show subheading (yes/no):
            <input type="text" name="show_subheading" value="<?php echo $this->show_subheading ?>"/>
        </p>
        <p>
            Show deposit/register button (yes/no):
            <input type="text" name="show_depregbtn" value="<?php echo $this->show_depregbtn ?>"/>
        </p>
        <p>
            Show number of news:
            <input type="text" name="show_newsnum" value="<?php echo $this->show_newsnum ?>"/>
        </p>
        <p>Select news categories to view:</p>
        <p>
            <?php phive("LimitedNewsHandler")->getCatDropDownMulti($this->category) ?>
        </p>
        <p>
            Show number of jackpots:
            <input type="text" name="show_jackpots" value="<?php echo $this->show_jackpots ?>"/>
        </p>
        <p>
            Show race leaderboard (yes/no):
            <input type="text" name="show_race" value="<?php echo $this->show_race ?>"/>
        </p>
        <p>
            Top relief (yes/no):
            <input type="text" name="top_relief" value="<?php echo $this->top_relief ?>"/>
        </p>
        <p>
            Show number of latest winners (minimum 10):
            <input type="text" name="latest_winners" value="<?php echo $this->latest_winners ?>"/>
        </p>
        <p>
            Favorites button shows (prior_month/this_month/yesterday, leave empty for all time):
            <input type="text" name="show_favfilter" value="<?php echo $this->show_favfilter ?>"/>
        </p>
        <p>
            Don't display the following operators (playtech,op2):
            <input type="text" name="hide_ops" id="hide_ops" value="<?php echo $this->hide_ops ?>"/>
        </p>
        <p>
            Display the following operators (playtech,op2):
            <input type="text" name="show_ops" id="show_ops" value="<?php echo $this->show_ops ?>"/>
        </p>
        <p>
            Number of games per row:
            <input type="text" name="col_num" value="<?php echo $this->col_num ?>"/>
        </p>

        <p>
            Show page prefiltered by game tag:
            <input type="text" name="load_sub" value="<?php echo $this->load_sub ?>"/>
        </p>
        <!-- BOOSTER + ROWS-->
        <p>
            [ Weekend Booster + ] - Number of rows:
            <input type="text" name="weekend_booster_plus_rows" value="<?php echo $this->weekend_booster_plus_rows ?>"/>
        </p>
        <p>
            [ Weekend Booster + ] - Ribbon Pic name (without extension, it must be PNG):
            <input type="text" name="weekend_booster_plus_pic" value="<?php echo $this->weekend_booster_plus_pic ?>"/>
        </p>
        <!-- GAMES BY RTP -->
        <p>
            [ Games by RTP ] - Number of rows:
            <input type="text" name="rtp_rows" value="<?php echo $this->rtp_rows ?>"/>
        </p>
        <p>
            [ Games by RTP ] - RTP (example 94.9):
            <input type="text" name="rtp_value" value="<?php echo $this->rtp_value ?>"/>
        </p>
        <p>
            [ Games by RTP ] - Category (Comma separated category list, if empty will use the box game tags):
            <input type="text" name="rtp_category" value="<?php echo $this->rtp_category ?>"/>
        </p>
        <!-- LIVE CASINO ROWS -->
        <p>
            [ Live Casino ] = Number of rows:
            <input type="text" name="live_casino_rows" value="<?php echo $this->live_casino_rows ?>"/>
        </p>
        <p>
            [ Live Casino ] = Line at which live casino rows will be displayed
            <input type="text" name="live_casino_rows_position" value="<?php echo $this->live_casino_rows_position ?>"/>
        </p>
        <p style="margin-bottom:0px;">Sub-top menu settings:</p>
        <fieldset style="width:520px;border:1px solid #333;margin:1px 1px 10px 1px;padding:10px;">
            <p style="margin-bottom: 20px;">If a menu item contains "Filter game tags" than the games for that menu item
                will be loaded trough an ajax call (no page refresh). Otherwise the page will be reloaded to the url as
                defined in the menu.</p>
            <table cellpadding="0" cellspacing="0">
                <tr>
                    <td width="150"><strong>Menu item</strong></td>
                    <td><strong>Filter game tags</strong> <em style="font-size:9px;">(comma separated)</em></td>
                    <td><strong>Excl. Operators</strong> <em style="font-size:9px;">(comma separated)</em></td>
                    <td><strong>Incl. Operators</strong> <em style="font-size:9px;">(comma separated)</em></td>
                </tr>
                <?php
                foreach ($this->aSubtopMenuItems as $aMenuItem) {
                    echo '<tr>';
                    echo '<td><label for="tag_' . $aMenuItem['alias'] . '">' . $aMenuItem['alias'] . '</label></td>';
                    echo '<td><input type="text" name="tag_' . $aMenuItem['alias'] . '" value="' . $this->{'tag_' . $aMenuItem['alias']} . '" id="tag_' . $aMenuItem['alias'] . '" /></td>';
                    echo '<td><input type="text" name="excl_operators_' . $aMenuItem['alias'] . '" value="' . $this->{'excl_operators_' . $aMenuItem['alias']} . '" id="excl_operators_' . $aMenuItem['alias'] . '" /></td>';
                    echo '<td><input type="text" name="incl_operators_' . $aMenuItem['alias'] . '" value="' . $this->{'incl_operators_' . $aMenuItem['alias']} . '" id="incl_operators_' . $aMenuItem['alias'] . '" /></td>';
                    echo '</tr>';
                }
                ?>
            </table>
        </fieldset>

        <script>
            //this script is to check that you have only included or not included, you cannot have both
            $("#hide_ops").click(function () {
                if ($("#show_ops").val() != "")
                    alert("You cannot have BOTH excluded and included operators for the same category or menu item");
            });

            $("#show_ops").click(function () {
                if ($("#hide_ops").val() != "")
                    alert("You cannot have BOTH excluded and included operators for the same category or menu item");
            });

            <?php foreach($this->aSubtopMenuItems as $aMenuItem){ ?>
            $("#<?php echo("excl_operators_" . $aMenuItem['alias'])?>").click(function () {
                if ($("#<?php echo("incl_operators_" . $aMenuItem['alias'])?>").val() != "")
                    alert("You cannot have BOTH excluded and included operators for the same category or menu item");
            });

            $("#<?php echo("incl_operators_" . $aMenuItem['alias'])?>").click(function () {
                if ($("#<?php echo("excl_operators_" . $aMenuItem['alias'])?>").val() != "")
                    alert("You cannot have BOTH excluded and included operators for the same category or menu item");
            });
            <?php } ?>
        </script>

<?php }
}
