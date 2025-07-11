<?php
require_once __DIR__ . '/MgGameChooseBoxBase.php';
require_once __DIR__ . '/../../../../../diamondbet/boxes/MobileFilterBox.php';

class MgMobileGameChooseBoxBase extends MgGameChooseBoxBase
{

    const PORTRAIT_PIXEL_THRESHOLD = 768;
    const LANDSCAPE_PIXEL_THRESHOLD = 1024;

    function init($init_from_child = false)
    {
        $this->handlePost(
            array(
                'jp_counter',
                'jp_counter_excluded_countries',
                'top_jp_games_slider',
                'top_jp_games_slider_items_count',
                'top_jp_slider_games_list',
                'tags',
                'rows',
                'show_grouped',
                'show_lastplayed',
                'weekend_booster_plus_rows',
                'weekend_booster_plus_pic',
                'loader_first_page_game_rows',
                'loader_page',
                'loader_next_page_game_rows',
                'col_num_portrait_min',
                'col_num_portrait_max',
                'col_num_landscape_min',
                'col_num_landscape_max',
                'rtp_rows',
                'rtp_category',
                'rtp_value'
            ),
            array(
                'rows' => 4,
                'col_num' => 4,
                'weekend_booster_plus_rows' => 0,
                'weekend_booster_plus_pic' => 'booster',
                'loader_first_page_game_rows' => 6,
                'loader_next_page_game_rows' => 6,
                'loader_page' => 0,
                'col_num_portrait_min' => 4,
                'col_num_portrait_max' => 5,
                'col_num_landscape_min' => 5,
                'col_num_landscape_max' => 5
            )
        );

        $this->ellipsis_len = 20;
        $this->loggedin = isLogged();
        $this->cur_lang = phive('Localizer')->getLanguage();
        $this->mg = phive('MicroGames');
        // When called by MgMobileFavChooseBoxBase we don't want to trigger this method - but we still want all the settings above.
        if (empty($init_from_child)) {
            $this->setupGames();
        }
    }

    function printCSS()
    {
        loadCss("/diamondbet/css/" . brandedCss() . "mobile-game-chooser.css");
    }

    function includes($carousel_skin = "videoslots")
    {
        parent::includes($carousel_skin);
        ?>
        <script>
            var longSide = screen.width > screen.height ? screen.width : screen.height;
            var shortSide = screen.width < screen.height ? screen.width : screen.height;

            function listGames(params, func) {
                if (typeof (func) == 'undefined') {
                    func = function (ret) {
                        $("#gch-list").html(ret);
                        styleGameSection(getRcount());
                    }
                }
                getGamesDataAsync(params, func);
            }

            async function getGamesDataAsync(params, func) {
                var gameContent = '';
                var ajaxParameters = <?php echo json_encode($this->ajaxParameters ?? "[]") ; ?>;
                Object.keys(ajaxParameters).map(function (name) {
                    return params[name] = ajaxParameters[name];
                });
                params.isAjaxCall = 1;

                var sumContent = function(content){gameContent += content;};
                params.func = 'printPersonalisedGameSection';
                await getGamesContentAsync(params, sumContent, func);
                params.func = 'printGameSection';
                await getGamesContentAsync(params, sumContent, func);
                func(gameContent);
            }

            function getGamesContentAsync(params, sumContent) {
                return new Promise(function(resolve, reject){
                    var callback = function(ret) {
                        sumContent(ret);
                        resolve();
                    };
                    ajaxGetBoxHtml(params, cur_lang, <?php echo $this->getId() ?>, callback);
                });
            }

            function styleGameSection(cnt) {
                if (cnt == 3)
                    return;

                var curWcls = "game" + cnt + "-width";
                var curHcls = "game" + cnt + "-height";
                var curTcls = "game" + cnt + "-txt";

                var removeCls = "game4-width game4-height game5-width game5-height game4-txt game5-txt";

                $(".game").find(".game-text").removeClass(removeCls).addClass(curTcls);
                $(".game").find(".game-top").removeClass(removeCls).addClass(curHcls);
                $(".game").find("img").removeClass(removeCls).addClass(curHcls + " " + curWcls);
            }
        
            function getRcount(num) {
                if (typeof num == 'undefined') {
                    if (window.orientation == 0)
                        num = shortSide >= <?= self::PORTRAIT_PIXEL_THRESHOLD ?> ? <?= $this->col_num_portrait_max ?> : <?= $this->col_num_portrait_min ?>;
                    else
                        num = longSide >= <?= self::LANDSCAPE_PIXEL_THRESHOLD ?> ? <?= $this->col_num_landscape_max ?> : <?= $this->col_num_landscape_min ?>;
                }
                return num;
            }

        </script>
    <?php }

    function setupGames()
    {
        $cu = cu();
        $this->ajaxParameters = [];
        $this->game_filter_box = phive('BoxHandler')->getBoxById(MobileFilterBox::BOX_ID);
        $this->game_filter_box->init();
        $this->boosted_games = [];

        // show_grouped is functionality used in mobile homepage
        if ($this->show_grouped == 'yes') {
            $this->ajaxParameters['isHomePage'] = '1';
            $_SESSION['mobile_select_subtag'] = $_SESSION['mobile_select_tag'] = '';
        }

        $this->rcount = empty($_REQUEST['rcount']) ? 3 : $_REQUEST['rcount'];
        $n_booster_games = $this->weekend_booster_plus_rows * $this->rcount;
        $n_rtp_games = $this->rtp_rows * $this->rcount;

        if (!empty($_REQUEST['search_str'])) {
            // Decode xhtml quote added in Phive::htmlQuotes for proper text matching
            $user_input = html_entity_decode($_GET['search_str'], ENT_QUOTES|ENT_XHTML);
            $escaped_user_input = phive('SQL')->escape($user_input, false);
            $games = $this->mg->getAllGames(
                "(mg.game_name LIKE '%{$escaped_user_input}%' OR mg.operator = '{$escaped_user_input}' ) AND mg.active = 1 ",
                "*",
                "html5",
                true
            );

            $this->games = array_chunk($games, $this->rcount);
            $this->hide_paginator = true;
            return;
        }
        if (!empty($_REQUEST['loader_page'])) {
            $this->loader_page = $_REQUEST['loader_page'];
        }

        // if we are searching with tag delete the filter from session
        // or if we are resseting filter remove all sessions that amend the logic which displays games
        if (isset($_GET['tag']) || !empty($_GET['filter_reset'])) {
            unset($_SESSION['filter_id']);
            unset($_SESSION['filter']);
        }

        // if we submiting filter without saving it
        if (!empty($_POST['filter'])) {
            $_SESSION['filter'] = $_POST['filter'];
        }

        // if we are getting our saved filter by id
        if (!empty($_GET['filter_id']) && !empty($cu)) {
            $this->ajaxParameters['filter_id'] = $_GET['filter_id'];
            $_SESSION['filter_id'] = $_GET['filter_id'];
            $saved_filter = $this->game_filter_box->getSavedFilter($_GET['filter_id'], $cu->userId);
            if ($saved_filter) {
                $_SESSION['filter'] = $saved_filter['filter'];
            } else {
                unset($_SESSION['filter_id']);
                unset($_SESSION['filter']);
            }
        }

        // empty($_GET['isAjaxCall']): Remove 'mobile_select_tag' from session when navigating to the new page to avoid usage of previous page's tag
        if (!empty($_GET['filter_reset']) || !empty($_SESSION['filter']) || !empty($_GET['filter_id']) || empty($_GET['isAjaxCall'])) {
            unset($_SESSION['mobile_select_tag']);
            unset($_SESSION['mobile_select_subtag']);
        }

        $this->tag = !isset($_GET['tag']) ? $_SESSION['mobile_select_tag'] : phive("SQL")->stripQuotes($_GET['tag']);

        //$this->subtag 	= !isset($_GET['subtag']) ? $_SESSION['mobile_select_subtag'] : phive("SQL")->stripQuotes($_GET['subtag']);
        // override when lobby param is added instead of taking from session.
        if (isset($_GET['lobby'])) {
            $this->tag = phive("SQL")->stripQuotes($_GET['lobby']);
        }
        if ($_GET['rotation'] == 'yes') {
            $this->subtag = $_SESSION['mobile_select_subtag'];
        } else {
            $this->subtag = phive("SQL")->stripQuotes($_GET['subtag']);
        }

        $_SESSION['mobile_select_subtag'] = $this->subtag;
        $_SESSION['mobile_select_tag'] = $this->tag;

        $this->all_str_tag = $this->getAttribute("tags");
        $this->all_tag_arr = explode(',', $this->all_str_tag);

        if (empty($this->tag) || $this->show_grouped == 'yes') {
            $this->str_tag = $this->all_str_tag;
            $this->tag_arr = $this->all_tag_arr;
            $this->subtag = '';
        } else {
            $this->str_tag = $this->tag;
            $this->tag_arr = array($this->str_tag);
        }

        if ($this->show_lastplayed == 'yes' && !empty($_COOKIE['mobile_last_played'])) {
            $this->rgames = $this->mg->getLastPlayed('mobile_last_played');
        }

        // the value for "weekend_booster_plus_rows" on the "all games" pages is currently set to 0, but with this tag we want to enforce displaying games with "weekend.booster"
        if ($this->tag === 'weekend.booster') {
            $n_booster_games = 100; // TODO check if this value needs to be moved in some config / box_attribute.
        }

        $options = [
            'rtp_value' => $this->rtp_value,
            'rtp_category' => $this->rtp_category,
            'count_rtp' => $n_rtp_games,
            'count_boosted' => $n_booster_games,
            'count_boosted_live_casino' => 0
        ];

        if ($this->show_grouped !== 'yes') {
            $first_page_games_number = $this->loader_first_page_game_rows * $this->rcount;
            $next_page_games_number = $this->loader_next_page_game_rows * $this->rcount;
            $this->pagination_offset = $this->loader_page == 0 ? 0 : $next_page_games_number * ($this->loader_page - 1) + $first_page_games_number;
            $this->pagination_length = $this->loader_page == 0 ? $first_page_games_number : $next_page_games_number;

            $options['pagination'] = [
                'offset' => $this->pagination_offset,
                'length' => $this->pagination_length,
            ];
        }

        $search_by_saved_filter = !empty($_GET['filter_id']) && $this->show_grouped !== 'yes';
        $search_by_unsaved_filter = !empty($_SESSION['filter'])
            && empty($_GET['filter_id'])
            && $this->show_grouped !== 'yes';

        if ($search_by_saved_filter || $search_by_unsaved_filter) {
            if (!$_GET['isAjaxCall'] == "1") {
                return;
            }
            $filter = $search_by_saved_filter ? phive('SQL')->stripQuotes($_GET['filter_id']): $_SESSION['filter'];
            $this->getGamesFromFilter($filter, $search_by_saved_filter ,$cu->userId, $this->tag, $options);
        } else {
            $this->getGamesFromTags($this->show_grouped == 'yes', $this->tag, $this->tag_arr, $options);
        }
    }

    public function getGamesFromFilter(string $filter, bool $is_saved_filter, $user_id, $tag = '', array $options) {
        if ($is_saved_filter) {
            $saved_filter = $this->game_filter_box->getSavedFilter($filter, $user_id);
            $sql_queries = $this->game_filter_box->buildSQLFromFilter($saved_filter['filter'], $options);
        } else {
            $sql_queries = $this->game_filter_box->buildSQLFromFilter($filter, $options);
        }

        $games = phive('SQL')->loadArray($sql_queries['paginated_query']);
        $games_count = current(phive('SQL')->loadCol($sql_queries['total_count_query'], 'count'));

        list($games, $booster_games, $rtp_games) = $this->mg->extractBoostedGamesFromList(
            $games,
            $options['count_boosted'],
            $options['count_boosted_live_casino'],
            $options['count_rtp'],
            $options['rtp_value'],
            $options['rtp_category'],
        );

        $this->games_count = $games_count;
        $this->games = array_chunk(array_merge($booster_games, $rtp_games, $games), $this->rcount);
    }

    public function getGamesFromTags(bool $is_home_page, $single_tag = '', $multiple_tags = [], array $options) {
        $should_join_jps = $this->shouldShowJpCounter();

        switch ($single_tag) {
            case 'new.cgames':
                $games = $this->mg->getTaggedByWrapper('subtag_footer', 'mobile', $single_tag);
                break;
            case 'popular.cgames':
                $games = $this->mg->getTaggedByWrapper('popular', 'mobile', 'all');
                break;
            case 'last.played':
                $games = $this->mg->getLastPlayed('mobile_last_played');
                $this->rgames = $games;
                break;
            case 'hot':
                $games = $this->mg->getTaggedByWrapper('hot', 'mobile', 'all');
                break;
            case 'all':
            case 'weekend.booster':
                $games = $this->mg->getTaggedByWrapper('subtag', 'mobile', 'all');
                break;
            // If a single tag is not specified then we grab all the "tag_arr" from the Box attribute as a default filter (/mobile => a list of tag, /mobile/casino => "all" )
            // otherwise, the single tag, will be one of the following: videoslots,videoslots_jackpot,casino-playtech,live-casino,table,videopoker,scratch-cards
            // from grouped games on homepage, and set into "tag_arr" as the only element
            default:
                $games = $this->mg->getTaggedByWrapper('primary_tag', 'mobile', $multiple_tags);
                break;
        }

        $booster_games = [];
        $rtp_games = [];
        list($games, $booster_games, $rtp_games) = $this->mg->extractBoostedGamesFromList(
            $games,
            $options['count_boosted'],
            0,
            $options['count_rtp'],
            $options['rtp_value'],
            $options['rtp_category'],
        );

        if ($is_home_page) {
            $this->boosted_games = $booster_games;
            $this->games = phive()->group2d(array_merge($rtp_games, $games), 'tag', false);
        } else {
            if ($single_tag === 'weekend.booster') {
                $games = $booster_games;
            } else {
                $games = array_merge($booster_games, $rtp_games, $games);
            }

            $this->games_count = count($games);
            $this->games = array_chunk(array_slice($games, $this->pagination_offset, $this->pagination_length), $this->rcount);
        }
    }

    function setupTags()
    {
        $this->tagsel = array();
        foreach ($this->all_tag_arr as $tag) {
            $this->tagsel[$tag] = t($tag);
        }
        if ($this->show_lastplayed == 'yes') {
            $this->tagsel['last.played'] = t('last.played');
        }
    }

    function setupSubTags()
    {
        parent::setupSubTags('html5');
    }

    function searchJs()
    { ?>
        <script>
            $(document).ready(function () {
                $("#tagsel").change(function () {
                    goTo(jsGetBase() + '?tag=' + $(this).val());
                    //$("#subtag").val('all');
                    //listGames({rcount: getRcount(), tag: $("#tagsel").val(), subtag: ''});
                });
                $("#subtag").change(debounce(function () {
                    listGames({rcount: getRcount(), tag: $("#tagsel").val(), subtag: $("#subtag").val()});
                }, 500));
                $("#search-games").click(debounce(function () {
                    $(this).val('');
                }, 500));
                $("#search-games").keyup(debounce(function (event) {
                    var cur = $(this);
                    if (cur.val().length >= 2) {
                        listGames({rcount: getRcount(), search_str: cur.val()});
                        $('.games-paginator').hide();
                    } else {
                        showGames();
                        $('.games-paginator').show();
                    }
                }, 500));
            });
        </script>
    <?php }

    function drawSearchForm()
    {
        $this->setupSubTags();
        $this->setupTags();
        $this->searchJs();
        ?>
        <div class="game-filter" style="box-sizing: border-box;  width: 100vw;">
            <div class="icons css-flex-uniform-section css-flex-v-stretch">
                <a class="css-flex-container w-15-pc border-box-sizing <?php if (isset($_SESSION['filter'])) {
                    echo 'active';
                } ?>"
                   href="<?= llink('/mobile/game-filter?type=custom') ?>">
                    <img class="margin-five-top" src="<?php fupUri("icons/Filter_Icon.svg") ?>"/>
                </a>
                <a class="css-flex-container w-15-pc border-box-sizing user-search-profiles"
                    <?php if (empty(cu())) {
                        if (isPNP()){
                            echo "onclick=showPayNPlayPopupOnLogin()";
                        }else{
                            echo "onclick=showLoginBox('login')";
                        }
                    } ?> href="#">
                    <img class="margin-five-top" src="<?php fupUri("icons/SaveFilter_Icon.svg") ?>"/>
                </a>
                <?php dbInput("search-games", '', 'text', 'w-70-pc search-box border-box-sizing borderless',
                    "placeholder='" . t('search.games_or_providers') . "'") ?>
            </div>
            <?php $this->game_filter_box->printProfileList(); ?>
        </div>
        <script>
            $(function () {
                if (logged_in) {
                    showProfileList('.user-search-profiles');
                }
            });
        </script>
        <?php
    }

    function js()
    { ?>
        <script>
            var showGames = function (num, rot) {
                num = getRcount(num);

                if (typeof rot == 'undefined')
                    rot = 'yes';

                listGames({rcount: num, rotation: rot}, function (ret) {
                    $("#gch-list").html(ret);
                    styleGameSection(num);
                });
            };


            var showMore = function () {
                var current_page = $('.btn-paginator-show-more').attr('data-page');
                if (current_page === undefined) {
                    current_page = 0;
                }
                $('.btn-paginator-show-more').attr('data-page', ++current_page);
                showLoader(listGames({
                    rcount: getRcount(),
                    loader_page: current_page,
                    tag: $("#tagsel").val(),
                    subtag: $("#subtag").val()
                }, function (games) {
                    $('.game-tbl tr').last().after(games);
                    updatePaginatorInfo();
                    styleGameSection(getRcount());
                    hideLoader();
                }, function () {
                }), false);
                $('.btn-paginator-show-more').blur();
            };

            var updatePaginatorInfo = function () {
                $('.games-paginator .paginator-info .length').html(
                    $('.game').length
                );
            };

            const printJackpotsSlider = function() {
                ajaxGetBoxHtml({ func: 'getJackpotsSlider', rcount: getRcount() }, cur_lang, <?php echo $this->getId() ?>, function (ret) {
                    $("#gch-top-slider").html(ret);
                    styleGameSection(getRcount());
                });
            }

            addOrFunc(showGames);

            $(document).ready(function () {
                showGames.call(undefined, getRcount(), 'no');

                const isJackpotsSliderEnabled = <?= $this->top_jp_games_slider ? 'true' : 'false'; ?>;
                if (isJackpotsSliderEnabled) {
                    printJackpotsSlider();
                }
            });
        </script>
    <?php }

    function printGameList($games = array(), $show_jp_counter = true)
    {
        addCacheHeaders("cache3600");

        $games = empty($games) ? $this->games : $games;
        ?>
        <?php foreach ($games as $g_chunk): ?>
        <?php if ($this->loader_page == 0): ?>
            <table class="game-tbl">
        <?php endif ?>
        <tr>
            <?php
                for ($i = 0; $i < $this->rcount; $i++) {
                    $g = $g_chunk[$i];
                    $this->printGame($g);
                }
            ?>
        </tr>
        <?php if ($this->loader_page == 0): ?>
            </table>
            <br clear="all"/>
        <?php endif ?>
    <?php endforeach ?>

        <?php if ($this->show_grouped !== 'yes') {
        $this->printPaginator();
    }
    }

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
        <td style="width: <?php echo 100 / $this->rcount ?>%;">
            <?php if (!empty($game)): ?>
                <div class="game" onclick="playMobileGame('<?php echo $game['ext_game_name'] ?>');">
                    <div class="game-top <?= $game_top_extra_classes ?>">
                        <img
                            src="<?php echo $this->mg->carouselPic($game, $show_high_thumbnail) ?>"
                            title="<?php echo $game['game_name'] ?>" alt="<?php echo $game['game_name'] ?>"
                            loading="lazy"
                        />
                        <?php displayGameRibbonImage($game, ['weekend_booster' => $this->weekend_booster_plus_pic], false, $show_high_thumbnail); ?>
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
                    <div class="game-text"
                         onclick="playMobileGame('<?php echo $game['ext_game_name'] ?>');">
                        <?php echo $game['game_name'] ?>
                    </div>
                </div>
            <?php endif ?>
        </td>
        <?php
    }

    public function flexJs()
    {
        loadJs("/phive/js/jquery.flexslider-min.js");
        ?>
        <script>
            $('.jackpots-slider').flexslider({
                animation: 'slide',
                slideshow: false,
                controlNav: false,
                directionNav: false,
                itemWidth: 105,
                itemMargin: 8,
            });
        </script>
    <?php }

    public function getJackpotsSlider()
    {
        $games = $this->getJackpotSliderGames('html5');
        ?>

        <div class="game-choose-top-slider flexslider-item">
            <div class="game-choose-top-slider__header flexslider-headline">
                <h6 class="game-choose-top-slider__title">
                    <?= t('jackpots-slider.mobile.title') ?>
                </h6>
            </div>
            <div class="flexslider-container">
                <div class="jackpots-slider">
                    <ul class="slides game-row">
                        <?php foreach ($games as $game): ?>
                            <li class="game">
                                <?php $this->printGame($game, true, true) ?>
                            </li>
                        <?php endforeach ?>
                    </ul>
                </div>
            </div>
        </div>
        <?php $this->flexJs() ?>
    <?php }

    public function printPaginator()
    {
        if (
            $this->games_count !== 0 &&
            ($this->pagination_offset + $this->pagination_length) < $this->games_count &&
            $this->hide_paginator !== true
        ) {
            ?>
            <div class="games-paginator">
                <div class="paginator-info">
                    <?=
                    tAssoc('mobile.game.list.paginator.info', [
                        'offset' => $this->pagination_offset,
                        'length' => $this->pagination_length,
                        'games_count' => $this->games_count
                    ])
                    ?>
                </div>
                <?php
                btn('btn-paginator-show-more', t('mobile.game.list.paginator.load.more'), "", 'showMore()', null);
                ?>
            </div>
            <?php
        } else {
            ?>
            <script>$('.games-paginator').hide();</script>
            <?php
        }
    }


    function printGameGroup($tag, $games)
    {
        if ($tag == 'weekend.booster') {
            return $this->printWeekendBoosterGroup($tag, $games);
        }
        if (empty($games)) {
            return '';
        }
        $count = count($games);
        ?>
        <div class="game-choose-headline">
            <?php et($tag) ?>
            <?php if ($count > ($this->rows * $this->rcount)): ?>
                <span>
          <a class="header-3" href="<?php echo llink(phive('Pager')->getPath(326) . '?tag=' . $tag) ?>">
            <?php et2('view.all.x.games', array($count)) ?>
          </a>
        </span>
            <?php endif ?>
        </div>
        <?php $this->printGameList(array_slice(array_chunk($games, $this->rcount), 0, $this->rows)) ?>
        <?php
        return '';
    }

    public function printWeekendBoosterGroup($tag, $games)
    {
        if (empty($games)) {
            return;
        }
        $extra_sql = '1 ORDER BY favdate DESC LIMIT ' . $this->weekend_booster_plus_rows * $this->rcount;
        $fav_games = $this->loggedin ? $this->mg->getFavorites(cuPlId(), $extra_sql, '', 'html5') : [];

        ?>
        <div class="game-choose-headline">
            <div id="game-choose-booster-link" class="game-choose-booster-favourites"
                 onClick="showBoosterGames()"><?php et($tag) ?></div>
            <div id="game-choose-favourites-link" class="header-3 game-choose-booster-favourites"
                 onClick="showFavouritesGames()"><?php et('mobile.game.list.favourite.link') ?></div>
        </div>
        <div id="game-choose-booster-games">
            <?php $this->printGameList(
                array_slice(array_chunk($games, $this->rcount), 0, $this->rows),
                false
            ) ?>
        </div>
        <?php if (count($fav_games)): ?>
        <div id="game-choose-favourites-games" style="display: none;">
            <?php $this->printGameList(
                array_slice(array_chunk($fav_games, $this->rcount), 0, $this->weekend_booster_plus_rows),
                false
            ) ?>
        </div>
    <?php else: ?>
        <div id="game-choose-favourites-games" style="display: none;">
            <div class="pad10">
                <?php et('no.favorites.yet') ?>
                <div class="pad-top-bottom"><a
                            href="<?php echo llink('/mobile/favourites/') ?>"><?php et('add.games') ?></a></div>
            </div>
        </div>
    <?php endif ?>
        <script type="text/javascript">
            function showBoosterGames() {
                document.getElementById('game-choose-booster-games').style.display = 'block';
                document.getElementById('game-choose-favourites-games').style.display = 'none';
                document.getElementById('game-choose-favourites-link').classList.remove('header-3');
                document.getElementById('game-choose-booster-link').classList.remove('header-3');
                document.getElementById('game-choose-favourites-link').classList.add('header-3');
            }

            function showFavouritesGames() {
                document.getElementById('game-choose-booster-games').style.display = 'none';
                document.getElementById('game-choose-favourites-games').style.display = 'block';
                document.getElementById('game-choose-booster-link').classList.remove('header-3');
                document.getElementById('game-choose-booster-link').classList.add('header-3');
                document.getElementById('game-choose-favourites-link').classList.remove('header-3');
            }
        </script>
        <?php
    }

    function printPersonalisedGameSection()
    {
        if ($this->show_grouped === 'yes') {
            $this->printGameGroup('last.played', $this->rgames);
            $this->printGameGroup('weekend.booster', $this->boosted_games);
        }
    }

    function printGameSection()
    {
        addCacheHeaders("cache3600");
        if ($this->show_grouped === 'yes') {
            foreach ($this->tag_arr as $tag) {
                $this->printGameGroup($tag, $this->games[$tag]);
            }
        } else {
            if (empty($this->games)) {
                $this->printEmptyNotice();
            } else {
                $this->printGameList();
            }
        }
    }

    public function printEmptyNotice()
    {
        ?>
        <div class="search-notice">
            <h2 class="search-notice--heading">
                <?= t("mobile.game.empty-seach.heading") ?>
            </h2>
            <p><?= t("mobile.game.empty-seach.text") ?></p>
        </div>
        <?php
    }

    public function printHTML()
    {
        $this->includes();
        $this->js();
        ?>
        <div class="lpad-five left">
            <div class="gch-right">
                <?php if ($this->show_grouped != 'yes'): ?>
                    <?php $this->drawSearchForm() ?>
                <?php endif ?>
                <div id="gch-top-slider"></div>
                <div id="gch-list"></div>
            </div>
        </div>
    <?php }

    function printExtra()
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
            <label for="check_perm">Jackpot Counter excluded countries:</label>
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
                Top Jackpot slider games list (comma separated list of game_id, e.g. <b>qspinmoneytrain3,MGS_HTML5_MajorMillions5Reel</b>).
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
            Game tag(s), ex videoslots,blackjack or just videoslots:
            <input type="text" name="tags" value="<?php echo $this->tags ?>"/>
        </p>
        <p>
            Rows per tag (if multi tag view), ex 3,6,9:
            <input type="text" name="rows" value="<?php echo $this->rows ?>"/>
        </p>
        <p>
            Number of games per row in portrait mode MIN:
            <input type="text" name="col_num_portrait_min" value="<?php echo $this->col_num_portrait_min ?>"/>
        </p>
        <p>
            Number of games per row in portrait mode MAX:
            <input type="text" name="col_num_portrait_max" value="<?php echo $this->col_num_portrait_max ?>"/>
        </p>
        <p>
            Number of games per row in landscape mode MIN:
            <input type="text" name="col_num_landscape_min" value="<?php echo $this->col_num_landscape_min ?>"/>
        </p>
        <p>
            Number of games per row in landscape mode MAX:
            <input type="text" name="col_num_landscape_max" value="<?php echo $this->col_num_landscape_max ?>"/>
        </p>
        <p>
            Show multi tag view (yes/no):
            <input type="text" name="show_grouped" value="<?php echo $this->show_grouped ?>"/>
        </p>
        <p>
            Show last played (yes/no):
            <input type="text" name="show_lastplayed" value="<?php echo $this->show_lastplayed ?>"/>
        </p>
        <p>
            [Weekend Booster +] Number of rows:
            <input type="text" name="weekend_booster_plus_rows" value="<?php echo $this->weekend_booster_plus_rows ?>"/>
        </p>
        <p>
            [Weekend Booster +] Ribbon Pic name (without extension, it must be PNG):
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
        <!-- PAGINATOR -->
        <p>
            [Paginator] Number of game rows on initial page load:
            <input type="text" name="loader_first_page_game_rows"
                   value="<?php echo $this->loader_first_page_game_rows ?>"/>
        </p>
        <p>
            [Paginator] Number of game rows loaded when user clicks "Show More button"
            <input type="text" name="loader_next_page_game_rows"
                   value="<?php echo $this->loader_next_page_game_rows ?>"/>
        </p>
    <?php }
}
