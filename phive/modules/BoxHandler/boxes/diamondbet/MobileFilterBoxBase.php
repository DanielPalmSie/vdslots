<?php
require_once __DIR__ . '/../../../../../diamondbet/boxes/DiamondBox.php';

class MobileFilterBoxBase extends DiamondBox
{

  const JACKPOT_TYPES = [
    'videoslots_jackpot',
    'slots_jackpot',
    'videoslots_jackpotbsg',
    'videoslots_jackpotsheriff'
  ];

  const CUSTOM_FILTER_TYPE = 'custom';
  const USER_FILTER_TYPE = 'user';
  const GET_METHOD = 'get';
  const POST_METHOD = 'post';
  const DELETE_METHOD = 'delete';
  const SEARCH_METHOD = 'search';

  const VOLATILITY_MAP = [
    'volatility-low' => [1, 2],
    'volatility-medium' => [3],
    'volatility-high' => [4, 5],
  ];
  private $PRESELECTED_FORM_VALUES;

  const CATEGORY_MAP = [
    'new.cgames',
    'featured.cgames',
    'popular.cgames',
    'view-all.cgames',
  ];

  const HOT_MAP = [
    ['alias' => 'order.hot', 'text' => 'hot'],
    ['alias' => 'order.popular', 'text' => 'popular'],
    ['alias' => 'order.latest', 'text' => 'latest.games'],
    ['alias' => 'order.a-z', 'text' => 'a-z'],
  ];

  const RTP_SLIDER_DEFAULTS = [
    'from' => 0,
    'to' => 100,
    'postfix' => '%',
  ];

  const CHECKBOX_AVAILABLE_SUBFILTER = ['hot', 'types', 'features', 'volatility', 'providers'];
  const SELECT_AVAILABLE_SUBFILTER = ['what-is-hot-sort', 'rtp', 'rtp-date'];
  const SLIDER_AVAILABLE_SUBFILTER = ['bet-size-slider-range', 'bet-line-slider-range', 'rtp-slider-range'];

  /**
   * @return void
   */
  function init()
  {
    $this->cu = $cu = cu();
    $this->filter_type = self::CUSTOM_FILTER_TYPE;
    $this->mg = phive('MicroGames');

    if (!empty($_POST)) {
      $this->processPost();
    }

    if (!empty($cu) && isset($_GET['filter_id'])) {
      $this->saved_filter = $this->getSavedFilter($_GET['filter_id'], $cu->userId);
      $this->filter_type = self::USER_FILTER_TYPE;
      if ($this->saved_filter == null) {
        $this->jsRedirect('/mobile/game-filter');
      }
    } else if (empty($cu) && isset($_GET['filter_id'])) {
      $this->jsRedirect('/mobile/game-filter');
    }

    $label = t('mobile.games.filter.section.what_is_hot.order_by');
    $hot = t('hot');
    $desc = t('mobile.games.filter.section.what_is_hot.order_by.desc');

    $this->PRESELECTED_FORM_VALUES = <<<HEREDOC
     {
      "radio": {
        "hot": [
          {"value":"order.hot", "name":"$hot", "label": "$label"}
        ]
      },
      "checkbox": {},
      "slider": {},
      "text": {},
      "select": {
          "what-is-hot-sort": {
          "value":"desc",
          "name":"$desc",
          "default_value":"asc",
          "default_name":"Ascending",
          "label":"$label"
        }
      }
    }
    HEREDOC;

    $this->all_tag_arr = phive('Menuer')->forRender('sub-top');
    $this->game_features = $this->mg->getAllSubTags('', 'html5'); //phive('SQL')->loadArray("SELECT DISTINCT name FROM game_features WHERE type = 'feature'");
    $this->game_providers = $this->selectFromMicroGamesQuery("DISTINCT operator", "operator", 'loadArray');
    $this->max_lines = $this->selectFromMicroGamesQuery("MAX(num_lines) AS max_num_lines");
    $this->min_lines = $this->selectFromMicroGamesQuery("MIN(num_lines) AS min_num_lines");
    $this->max_bet = $this->selectFromMicroGamesQuery("MAX(max_bet) AS max_bet") / 100;
    $this->min_bet = $this->selectFromMicroGamesQuery("MIN(min_bet) AS min_bet") / 100;
    $this->rtp_select = $this->selectFromMicroGamesQuery("DISTINCT payout_percent", "payout_percent", 'loadArray');

    if (!empty($_SESSION['filter'] ?? [])) {
      $this->saved_filter['filter'] = $_SESSION['filter'];
    }

    if (empty($this->saved_filter['filter'] ?? [])) {
      $this->saved_filter['filter'] = $this->PRESELECTED_FORM_VALUES;
    }
  }

  /**
   * This function gets the attributes available for the list game by section. Example...order.hot, order.popular, etc.
   *
   * @return array
   */
  public function getListGameByFilters(): array
  {
      return array_column(self::HOT_MAP, 'alias');
  }

  /**
   * This function gets the available categories
   *
   * @return array
   */
  public function getCategories(): array
  {
      return self::CATEGORY_MAP;
  }

  /**
   * This function gets the features and returning their text. Example, getting new.cgames,
   *
   * @param string $deviceType The device type, flash or html5
   * @return array
   */
  public function getFeatures(string $deviceType): array
  {
      $subtags = phive('MicroGames')->getAllSubTags('', $deviceType);

      $features = [];
      foreach ($subtags as $subtag) {
          if (!in_array($subtag, self::CATEGORY_MAP)) {
              $features[] = $subtag;
          }
      }

      return $features;
  }

  /**
   * This function gets all the operators that we have available
   *
   * @return array
   */
  public function getOperators(): array
  {
      $gameProviders = $this->selectFromMicroGamesQuery("DISTINCT operator", "operator", 'loadArray');

      $providers = [];
      foreach ($gameProviders as $provider) {
          $providers[] = $provider['operator'];
      }

      return $providers;
  }

  /**
   * This function gets the min and max bet size ranges available
   *
   * @return array
   */
  public function getBetSizeRange(): array
  {
      $minBet = (string)($this->selectFromMicroGamesQuery("MIN(min_bet) AS min_bet") / 100);
      $maxBet = (string)($this->selectFromMicroGamesQuery("MAX(max_bet) AS max_bet") / 100);

      return [$minBet, $maxBet];
  }

  /**
   * This function gets the min and max bet line ranges available
   *
   * @return array
   */
  public function getBetLineRange(): array
  {
      $minLines = $this->selectFromMicroGamesQuery("MIN(num_lines) AS min_num_lines");
      $maxLines = $this->selectFromMicroGamesQuery("MAX(num_lines) AS max_num_lines");

      return [$minLines, $maxLines];
  }

  /**
   * @param string $filter_id
   * @param string $user_id
   * @return array
   */
  function getSavedFilter(string $filter_id, string $user_id)
  {
    $sql = "
        SELECT *
        FROM users_game_filters
        WHERE id = " . phive('SQL')->escape($filter_id) . "
        AND user_id = '{$user_id}' ";
    return phive('SQL')->sh($user_id)->loadAssoc(
        $sql
      ) ?? [];

  }

  /**
   * Process the submitting of the filter
   * @return void
   */
  public function processPost()
  {
    $cu = cu();
    if ($_POST['method'] === self::POST_METHOD) {

      $insert_array = [
        'user_id' => $cu->userId,
        'title' => $_POST['title'] ?? uniqid(),
        'filter' => $_POST['filter'],
      ];
      if (!isset($_POST['id']) || empty($_POST['id'])) {
        $id = phive('SQL')->sh($cu)->insertArray('users_game_filters', $insert_array);
        $this->jsRedirect("/mobile/game-filter/?type=" . self::USER_FILTER_TYPE . "&filter_id=" . $id);
      } else {
        $insert_array['updated_at'] = date("Y-m-d H:i:s");
        phive('SQL')->sh($cu)->updateArray('users_game_filters', $insert_array, ['id' => $_POST['id']]);
        if ($_POST['redirect'] ?? false) {
          unset($_SESSION['filter_id']);
          unset($_SESSION['filter']);
          $this->jsRedirect($_POST['redirect'] . "?filter_id=" . $_POST['id']);
        }
      }
    } elseif ($_POST['method'] === self::DELETE_METHOD) {
      phive('SQL')->delete("users_game_filters", ['id' => $_POST['id'], 'user_id' => $cu->userId], $cu->userId);
      $this->jsRedirect("/mobile/game-filter/?type=" . self::CUSTOM_FILTER_TYPE);
    } elseif ($_POST['method'] === self::SEARCH_METHOD) {
      unset($_SESSION['filter_id']);
      $_SESSION['filter'] = $_POST['filter'];
    }

  }

  /**
   * @param string $select
   * @param string $order_by
   * @return string
   */
  function selectFromMicroGamesQuery(string $select, string $order_by = "", string $queryFun = '')
  {
    $where_block = phive('MicroGames')->blockCountry(true, '');

    $sql = "SELECT {$select} FROM micro_games WHERE device_type = 'html5' AND operator != '' AND active = 1 {$where_block} ";
    if (!empty($order_by)) {
      $sql .= "ORDER BY {$order_by}";
    }
    $res = phQget($sql);
    if(empty($res)) {
       if($queryFun == 'loadArray') {
           $res = phive("SQL")->readOnly()->loadArray($sql);
       }else {
           $res = phive("SQL")->readOnly()->getValue($sql);
       }
       phQset($sql, $res, 3600);
    }

    return $res;
  }

  /**
   * @return void
   */
  function printHTML()
  {
    loadJS("/phive/js/ionslider/ion.rangeSlider.min.js");
    loadCSS("/phive/js/ionslider/ion.rangeSlider.vs.css");
    loadCss("/phive/js/jQuery-UI/".$this->getJQueryUIVersion()."jquery-ui.min.css");
    loadCss("/phive/js/jQuery-UI/".$this->getJQueryUIVersion()."jquery-ui.theme.min.css?v3");
    ?>
    <div class="game-filter-wrapper">

      <div class="game-filter">
        <!-- game filter header -->
        <?php $this->printFilterHeader(); ?>
        <?php $this->printFilterIcons();
        ?>
        <!-- game filter sections -->
        <?php
        $this->printStorageSection();
        $this->printFilterSection('vs-flame', 'mobile.games.filter.section.what_is_hot', 'printWhatIsHotInput');
        $this->printFilterSection('vs-category-icon', 'mobile.games.filter.section.categories', 'printCategoriesInput');
        $this->printFilterSection('vs-slot-machine', 'mobile.games.filter.section.type', 'printTypesInput');
        $this->printFilterSection('vs-gear', 'mobile.games.filter.section.features', 'printFeaturesInput');

        if (p('game_filter.show_rtp')) {
          $this->printFilterSection('vs-slot-money', 'mobile.games.filter.section.game_payout', 'printPayoutInput');
        }
        $this->printFilterSection('vs-graph', 'mobile.games.filter.section.volatility', 'printVolatilityInput');
        $this->printFilterSection('vs-slot-machine-2', 'mobile.games.filter.section.game_providers', 'printProvidersInput');


        ?>
        <div id="buttons-placeholder" style="margin:0; padding:0; height:52px !important;">
          <?php $this->printSubmitButtons($this->filter_type); ?>
        </div>
      </div>

    </div>
    <?php
    $this->printJsHandlers();
    $this->printHiddenForm(
      $this->saved_filter['id'] ?? '',
      $this->saved_filter['title'] ?? '',
      $this->saved_filter['filter']
    );
    $this->printHiddenPopups();
  }

  /**
   * @return void
   */
  function printStorageSection()
  {
    ?>
    <div class="section">
      <div class="header">
        <?php if (empty($this->saved_filter)) { ?>
          <span class="main-icon icon icon-vs-search "></span>
          <span class="text"><?= t('mobile.games.filter.section.my_filter_search') ?></span>
        <?php } else { ?>
          <span id='filter-name' class="text"><?= $this->saved_filter['title'] ?></span>
        <?php } ?>
        <span class="erase pull-right" style="display: none"
              onclick="gfReset()"><?= t('mobile.games.filter.erase') ?></span>
      </div>
      <div id="storage-area" class="storage contents" style="position: relative">
        <?php $this->printStorageItems($this->saved_filter['filter']); ?>
      </div>
    </div>
    <?php
  }

  /**
   * @return void
   */
  function printFilterHeader()
  {
    ?>
    <div class="header css-flex-uniform-section">
      <div class="css-flex-grow-1">
        <a href="<?= llink('/mobile/casino') ?>">
          <?= t('mobile.games.filter.cancel') ?>
        </a>
      </div>
      <div class="css-flex-grow-4">
        <?= t('mobile.games.filter.heading') ?>
      </div>
      <div class="css-flex-grow-1">
        <a href="<?= llink('/mobile/casino') . '?filter_reset=1' ?>">
          <?= t('mobile.games.filter.reset') ?>
        </a>
      </div>
    </div>
    <?php
  }

  /**
   * @return void
   */
  function printFilterIcons()
  {
    ?>
    <div class="icons css-flex-uniform-section css-flex-v-stretch">
      <div <?= $this->filter_type === self::CUSTOM_FILTER_TYPE ? 'data-active=1' : '' ?>
           onclick="window.location = '/mobile/game-filter/'"
           class="css-flex-container css-flex-grow-1 <?= $this->filter_type === self::CUSTOM_FILTER_TYPE ? 'active' : '' ?>"
           href="<?= llink('/mobile/game-filter?type=custom') ?>">
        <img alt="Filter icon" src="<?php fupUri("icons/Filter_Icon.svg") ?>"/>
      </div>
      <div <?= $this->filter_type === self::USER_FILTER_TYPE ? 'data-active=1' : '' ?>
           class="user-search-profiles css-flex-container css-flex-grow-1 <?= $this->filter_type === self::USER_FILTER_TYPE ? 'active' : '' ?>"
           href="<?= llink('/mobile/game-filter?type=user') ?>">
        <img alt="User filters icon" src="<?php fupUri("icons/SaveFilter_Icon.svg") ?>"/>
      </div>
    </div>
    <script>
      $(function(){
          showProfileList('.user-search-profiles');
      });
    </script>
    <?php
    $this->printProfileList();
  }

  /**
   * @param string $icon
   * @param string $name
   * @param string $inner_html_method
   * @return void
   */
  function printFilterSection(string $icon, string $name, string $inner_html_method)
  {
    $is_cookie_set = $_COOKIE[$inner_html_method];
    $chevron_suffix = $is_cookie_set ? 'down' : 'up';
    $contents_style_snippet = $is_cookie_set ? 'display:none;' : '';

    ?>
    <div class="section">
      <div class="header" data-cookie="<?= $inner_html_method ?>">
        <span class="main-icon icon icon-<?= $icon ?>"></span>
        <span class="text"><?= t($name) ?></span>
        <a href="#" class="toggle-button">
          <span class="icon icon-vs-chevron-right pull-right chevron-<?= $chevron_suffix ?>"></span>
        </a>
      </div>
      <div class="contents" style="<?= $contents_style_snippet ?>">
        <?php $this->{$inner_html_method}() ?>
      </div>
    </div>
    <?php
  }

  /**
   * @return void
   */
  function printJsHandlers()
  {
    ?>
    <script>
        $(function () {
            function stickySubmitButtonsHandler() {
                //align submit buttons according to the sticky footer
                var sticky_bottom_height = $('#bottom-sticky').height();
                $('.submit-buttons').css('bottom', sticky_bottom_height - 1);
                $(window).on('scroll resize', function () {
                    var buttons_bottom_line = $('#buttons-placeholder').offset().top + $('#buttons-placeholder').height();
                    var screen_bottom_line = $(window).scrollTop() + $(window).height() - sticky_bottom_height;
                    if (screen_bottom_line > buttons_bottom_line) {
                        $('.submit-buttons').css('position', 'initial');
                    } else {
                        $('.submit-buttons').css('position', 'fixed');
                    }
                });
            }

            stickySubmitButtonsHandler();
            storageLogic();
            fillData(<?=$this->saved_filter['filter']?>);

            $(".game-filter > .section > .header").on('click', function (e) {
                var $icon = $(this).find('.toggle-button .icon');
                e.preventDefault();
                if ($icon.hasClass('chevron-up')) {
                    $icon.removeClass('chevron-up');
                    $icon.addClass('chevron-down');
                    $icon.closest('.section').children('.contents').slideUp();
                    document.cookie = $(this).data('cookie') + "=closed; Path=/ ";
                } else {
                    $icon.removeClass('chevron-down');
                    $icon.addClass('chevron-up');
                    $icon.closest('.section').children('.contents').slideDown();
                    document.cookie = $(this).data('cookie') + '=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
                }
               /* setTimeout(function () {
                    $(window).trigger('resize');
                }, 401);*/
            });

            $(".checkbox-handler").click(function (e) {
                e.preventDefault();
                var value = $(this).data('checked');
                $(this).closest('.section').find('input[type=checkbox]').each(function (index, element) {
                    $(element).prop('checked', value);
                    $(element).trigger('change');
                });
            });

            $("#rtp-slider-range").ionRangeSlider({
                type: "double",
                min: $("#rtp-slider-range-from").data('default'),
                max: $("#rtp-slider-range-to").data('default'),
                from: $("#rtp-slider-range-from").val(),
                to: $("#rtp-slider-range-to").val(),
                grid: false,
                prettify: function (ts) {
                    return ts + $("#rtp-slider-range").data('value-postfix');
                },
                hide_min_max: true,
                onFinish: function (data) {
                    $("#rtp-slider-range-from").val(data.from);
                    $("#rtp-slider-range-to").val(data.to);
                    $("#rtp-slider-range").trigger('change');
                },
                onStart: function () {
                    $(function () {
                        $('#rtp-date').trigger('load');
                    });
                }
            });

            $("#bet-size-slider-range").ionRangeSlider({
                type: "double",
                min: <?=$this->min_bet?>,
                max: <?=$this->max_bet?>,
                from: $("#bet-size-slider-range-from").val(),
                to: $("#bet-size-slider-range-to").val(),
                grid: false,
                prettify: function (ts) {
                    return ts + '€';
                },
                hide_min_max: true,
                onFinish: function (data) {
                    $("#bet-size-slider-range-from").val(data.from);
                    $("#bet-size-slider-range-to").val(data.to);
                    $("#bet-size-slider-range").trigger('change');
                }
            });

            $("#bet-line-slider-range").ionRangeSlider({
                type: "double",
                min: <?=$this->min_lines?>,
                max: <?=$this->max_lines?>,
                from: $("#bet-line-slider-range-from").val(),
                to: $("#bet-line-slider-range-to").val(),
                grid: false,
                hide_min_max: true,
                onFinish: function (data) {
                    $("#bet-line-slider-range-from").val(data.from);
                    $("#bet-line-slider-range-to").val(data.to);
                    $("#bet-line-slider-range").trigger('change');
                }
            });

            $("#rtp-date-from").datepicker({
                showButtonPanel: false,
                dateFormat: 'yy-mm-dd'
            });

            $("#rtp-date-to").datepicker({
                showButtonPanel: false,
                dateFormat: 'yy-mm-dd'
            });

            $("#type-search").on('keyup change', function () {
                var filter = $(this).val();

                $(this).closest('.contents').find('.checkbox-item').each(function () {
                    if ($(this).text().search(new RegExp(filter, "i")) < 0) {
                        $(this).hide();
                    } else {
                        $(this).show();
                    }
                });

                $(window).trigger('resize');
                alignCheckboxes();
            });

            mainButtonHandlers();

            alignCheckboxes();

            rtpHandlers();

            $('.erase').trigger('change');

        });



        function clearInput(id) {
            $('#' + id).val("").trigger("change");
        }

        function submitFilter(title) {
            var $form = $('#game-filter-form');
            var json_data = gatherData();

            if (!title) {
                title = $(".multibox-content .save-popup-html input[name='title']").val();
            }

            $form.find('input[name=\'title\']').val(title);
            $form.find('input[name=\'filter\']').val(JSON.stringify(json_data));
            $form.submit();
        }

        function submitDeleteRequest() {
            var $form = $('#game-filter-form');
            var $selected_filter = $(".filter-select .selected-filter");
            var id = $selected_filter.data('id');

            $form.find('input[name=\'id\']').val(id);
            $form.find('input[name=\'method\']').val('<?=self::DELETE_METHOD?>');
            $form.submit();
        }

        function gatherData() {
            var data_json = {
                radio: {},
                checkbox: {},
                select: {},
                slider: {},
                text: {}
            };

            // radio
            $("input[type=radio]:checked").each(function () {
                var section = $(this).attr('name');
                var name = $(this).data('name') || $(this).val();
                var value = $(this).val();
                var label = $(this).data('label') || '';
                var to_insert = {
                    value: value
                };
                if (value != name) {
                    to_insert.name = name;
                }
                if (label) {
                    to_insert.label = label;
                }
                (data_json.radio[section] = data_json.radio[section] || []).push(to_insert);
            });

            // checkbox
            $("input[type=checkbox]:checked").each(function () {
                var section = $(this).attr('name');
                var name = $(this).data('name') || $(this).val();
                var value = $(this).val();
                var to_insert = {
                    value: value
                };
                if (value != name) {
                    to_insert.name = name;
                }
                (data_json.checkbox[section] = data_json.checkbox[section] || []).push(to_insert);
            });

            // slider
            $(".input-slider").each(function () {
                var name = $(this).attr('id');
                data_json.slider[name] = {
                    'from': $('#' + name + '-from').val(),
                    'to': $('#' + name + '-to').val(),
                    'min': $('#' + name).data('default-from'),
                    'max': $('#' + name).data('default-to'),
                    'postfix': $('#' + name).data('value-postfix'),
                    'name': $('#' + name).data('name')
                };
            });

            // select
            $("select").each(function () {
                var name = $(this).attr('name');
                data_json.select[name] = {
                    'value': $(this).val(),
                    'name': $(this).find('option:selected').text(),
                    'default_value': $(this).find('option:first-child').attr('value'),
                    'default_name': $(this).find('option:first-child').text(),
                    'label': $(this).data('name')
                }
            });

            // text
            $(".section input[type=text]").each(function () {
                var name = $(this).attr('name');
                data_json.text[name] = $(this).val();
            });

            return data_json;
        }

        function fillData(data_json) {
            // radio
            if (data_json.radio) {
                Object.keys(data_json.radio).forEach(function (key) {
                    data_json.radio[key].forEach(function (element) {
                        var selector = 'input[type=radio][id="' + element.value + '"][name="' + key + '"]';
                        $(selector).prop('checked', true);
                    });
                });
            }

            //checkboxes
            if (data_json.checkbox) {
                Object.keys(data_json.checkbox).forEach(function (key) {
                    data_json.checkbox[key].forEach(function (element) {
                        var selector = 'input[type=checkbox][id="' + element.value + '"][name="' + key + '"]';
                        $(selector).prop('checked', true);
                    });
                });
            }

            //selects
            if (data_json.radio) {
                Object.keys(data_json.select).forEach(function (select) {
                    var selector = '#' + select;
                    $(selector).val(data_json.select[select].value);
                    $(selector).trigger('load');
                });
            }

            //sliders
            if (data_json.slider) {
                Object.keys(data_json.slider).forEach(function (slider) {
                    $('#' + slider + '-from').val(data_json.slider[slider].from);
                    $('#' + slider + '-to').val(data_json.slider[slider].to);
                });
            }
        }

        function gfReset() {
            $('input[type=checkbox]').prop('checked', false);
            $('select').val('');
            $('input[type=text]').val('');
            $('.section input[type=hidden]').each(function () {
                $(this).val($(this).data('default'));
            });
            $('.input-slider').each(function () {
                resetSlider($(this));
            });
            $('#storage-area').empty();
            $('.erase').trigger('change');
        }

        function resetSlider($slider) {
            var slider_id = $slider.attr('id');
            var slider_instance = $slider.data("ionRangeSlider");
            $slider.data('locked', 1);
            slider_instance.update({
                from: $slider.data('default-from'),
                to: $slider.data('default-to')
            });
            $('#' + slider_id + '-from').val($slider.data('default-from'));
            $('#' + slider_id + '-to').val($slider.data('default-to'));
            $slider.data('locked', 0);
        }

        function storageLogic() {
            var storage_id = '#storage-area';
            var $storage = $(storage_id);

            //erase button
            $('.erase').on('change', function () {
                if (!$('#storage-area').html().trim()) {
                    $(this).hide();
                } else {
                    $(this).show();
                }
            });

            // radio
            $('input[type=radio]').on('click', function () {
                $storage.find('*[data-type="' + $(this).attr('name') + '"]').remove();
                if ($(this).prop('checked')) {
                    var checkbox_template = '<div data-section="radio" data-type="' + $(this).attr('name') + '" data-value="' + $(this).val() + '"  ' +
                        'data-id="' + $(this).attr('id') + '" data-name="' + $(this).data('name') + '" class="storage-item half">' +
                        '<span class="name">' + ($(this).data('label') ? $(this).data('label') + ' ' : '') + ($(this).data('name') ? $(this).data('name') : $(this).val()) + '</span><span class="close">' +
                        '<span class="icon icon-vs-close-1"></span></span></div>';
                    var $new_checkbox = $(checkbox_template);

                    $storage.append($new_checkbox);
                }

                $('.erase').trigger('change');
            });

            // checkbox
            $('input[type=checkbox]').on('change', function () {
                if ($(this).prop('checked')) {
                    var checkbox_template = '<div data-section="checkbox" data-type="' + $(this).attr('name') + '" data-value="' + $(this).val() + '"  ' +
                        'data-id="' + $(this).attr('id') + '" data-name="' + $(this).data('name') + '" class="storage-item half">' +
                        '<span class="name">' + ($(this).data('name') ? $(this).data('name') : $(this).val()) + '</span><span class="close">' +
                        '<span class="icon icon-vs-close-1"></span></span></div>';
                    var $new_checkbox = $(checkbox_template);

                    $storage.append($new_checkbox);
                } else {
                    $storage.find('*[data-type="' + $(this).attr('name') + '"][data-value="' + $(this).attr('value') + '"]').remove();
                }
                $('.erase').trigger('change');
            });

            //slider
            $('.input-slider').on('change', function () {
                if ($(this).data('locked') == 1)
                    return;

                var slider_id = $(this).attr('id');
                var data = {
                    'from': $('#' + slider_id + '-from').val(),
                    'to': $('#' + slider_id + '-to').val(),
                    'min': $(this).data('default-from'),
                    'max': $(this).data('default-to'),
                    'postfix': $(this).data('value-postfix'),
                    'name': $(this).data('value-postfix')
                };
                var slider_template = '<div class="storage-item half" data-section="slider" ' +
                    'data-id="' + $(this).attr('id') + '" data-data=\'' + JSON.stringify(data) + '\'>' +
                    '<span class="name">' + $(this).data('name') + ': ' + data.from + data.postfix + ' - ' + data.to + data.postfix + '</span>' +
                    '<span class="close"><span class="icon icon-vs-close-1"></span></span></div>';
                var $storage_slider_element = $storage.find('*[data-id="' + slider_id + '"]');
                var $new_storage_slider = $(slider_template);

                replaceStorageElement($storage, $storage_slider_element, $new_storage_slider);
            });

            //select
            $('.select-item > select').on('change', function () {
                var select_id = $(this).attr('id');
                var data = {
                    'value': $(this).val(),
                    'name': $(this).find('option:selected').text(),
                    'default_value': $(this).find('option:first-child').attr('value'),
                    'default_name': $(this).find('option:first-child').text(),
                    'label': $(this).data('name')
                };
                var select_template = '<div data-id="' + $(this).attr('id') + '" data-data=\'' + JSON.stringify(data) + '\' class="storage-item half">' +
                    '<span class="name">' + $(this).data('name') + ': ' + data.name + '</span><span class="close">' +
                    '<span class="icon icon-vs-close-1"></span></span></div>';
                var $storage_select_element = $storage.find('*[data-id="' + select_id + '"]');
                var $new_storage_select = $(select_template);

                replaceStorageElement($storage, $storage_select_element, $new_storage_select);
            });

            // deleting item from storage
            $(document).on('click', storage_id + ' .storage-item .close', function () {
                var $storage_item = $(this).closest('.storage-item');
                var input_type = $storage_item.data('section');
                var $filter_input = $('*[id="' + $storage_item.data('id') + '"]');
                switch (input_type) {
                    case 'checkbox':
                    case 'radio':
                        $filter_input.prop('checked', false);
                        break;
                    case 'slider':
                        resetSlider($filter_input);
                        break;
                    default:
                        $filter_input.val('');

                }
                $storage_item.remove();
                $('.erase').trigger('change');
                alignCheckboxes();
            });

            function replaceStorageElement($storage, $old_element, $new_element) {
                if ($old_element.length) {
                    $old_element.replaceWith($new_element)
                } else {
                    $storage.append($new_element);
                    $('.erase').trigger('change');
                }
            }
        }



        function rtpHandlers() {
            var $rtp_select = $('#rtp');
            var $rtp_date = $('#rtp-date');
            $rtp_select.on('change load', function () {
                var value = $(this).val();
                if (value === '<?= t('actual') ?>') {
                    $rtp_date.closest('.select-item').slideDown();
                    $rtp_date.trigger('change');

                } else {
                    $rtp_date.val('');
                    $rtp_date.closest('select-item').slideUp();

                    updateSliderMaximum(
                        'rtp-slider-range',
                        '<?= self::RTP_SLIDER_DEFAULTS['from'] ?>',
                        '<?= self::RTP_SLIDER_DEFAULTS['to'] ?>',
                        '<?= self::RTP_SLIDER_DEFAULTS['from'] ?>',
                        '<?= self::RTP_SLIDER_DEFAULTS['to'] ?>',
                        '<?= self::RTP_SLIDER_DEFAULTS['postfix'] ?>'
                    );
                }
                $rtp_date.trigger('change');

            });


            $rtp_date.on('change load', function () {
                if ($rtp_select.val() === '<?= t('actual') ?>') {
                    mgAjax({
                        action: "get-highest-rtp",
                        date: $(this).val(),
                        device_type: 'html5'
                    }, function (res) {
                        var result = JSON.parse(res);
                        if (result.rtp == 0) {
                            result.rtp = <?= self::RTP_SLIDER_DEFAULTS['to'] ?>;
                        }
                        updateSliderMaximum(
                            'rtp-slider-range',
                            $('#rtp-slider-range-from').val(),
                            $('#rtp-slider-range-to').val(),
                            '<?= self::RTP_SLIDER_DEFAULTS['from'] ?>',
                            result.rtp,
                            '<?= self::RTP_SLIDER_DEFAULTS['postfix'] ?>');
                    });
                }
            });
        }

        function updateSliderMaximum(slider_id, from, to, min, max, postfix) {
            var $slider = $('#' + slider_id);
            var slider_instance = $slider.data("ionRangeSlider");

            slider_instance.update({
                from: from,
                to: to,
                min: min,
                max: max
            });

            $('#' + slider_id + '-from').attr('data-default', min);
            $('#' + slider_id + '-to').attr('data-default', max);

            $slider.attr('data-default-from', min);
            $slider.attr('data-default-to', max);
            $slider.closest('.range-slider').children('div:first-child').text(min + postfix);
            $slider.closest('.range-slider').children('div:last-child').text(max + postfix);
            $slider.trigger('change');
        }

    </script>
    <?php
    $this->printHelperJs();
  }

  function printHelperJs() {
    ?>
    <script>
        function alignCheckboxes() {
            // tag checkboxes for proper alignment
            $(".checkbox-item:visible, .storage-item").each(function () {
                var $section = $(this).closest('.contents');
                var checkbox_right_offset = Math.ceil($(this).offset().left + $(this).width());
                var section_right_offset = $section.offset().left + $section.width();
                if (checkbox_right_offset == section_right_offset) {
                    $(this).addClass('right-col');
                } else {
                    $(this).removeClass('right-col');
                }
            });
            // tag checkboxes for proper alignment
            $(".storage-item").each(function () {
                var $section = $(this).closest('.contents');
                var checkbox_right_offset = Math.ceil($(this).offset().left + $(this).width());
                var section_right_offset = $section.offset().left + $section.width();
                if (checkbox_right_offset == section_right_offset) {
                    $(this).addClass('right-col');
                } else {
                    $(this).removeClass('right-col');
                }
            });
        }

        function showProfileList(toggle_element_selector) {
            $(toggle_element_selector).on('click', function() {
                var should_be_always_active = $(this).data('active');
                var $filter_select = $('.filter-select');
                if ($filter_select.is(":hidden") ) {
                    $('.save-submit').addClass("mobile-button--disabled");
                    if ($('.filter-select').find('.selected-filter').length === 0) {
                        $('.search-submit').addClass("mobile-button--disabled");
                    }
                    $filter_select.slideDown();
                    $(this).addClass('active');
                    $(this).siblings('div[data-active=1]').removeClass('active');
                    $("body").css('overflow', 'hidden');
                    filterListButtonHandlers();
                } else {
                    $filter_select.slideUp();
                    if (!should_be_always_active) {
                        $(this).removeClass('active');
                    }
                    $(this).siblings('div[data-active=1]').addClass('active');
                    $("body").css('overflow', 'initial');
                    mainButtonHandlers();
                    $('.save-submit, .search-submit').removeClass("mobile-button--disabled");
                }
            });
        }

        function filterListButtonHandlers() {
            $('.search-submit').off().on('click', function () {
                if (!$(this).hasClass('mobile-button--disabled')) {
                    var $selected_filter = $('.filter-select').find('.selected-filter');
                    if ($selected_filter) {
                        var selected_id = $selected_filter.data('id');
                        if (!isNaN(selected_id)) {
                            window.location.href = '<?php echo phive('Localizer')->langLink('', llink('/mobile/casino')) ?>?filter_id=' + selected_id;
                        }
                    }
                }
            });

            $('.save-submit').off().on('click', function () {
                if (!$(this).hasClass('mobile-button--disabled')) {
                    var $form = $('#game-filter-form');
                    var $selected_filter = $(".filter-select .selected-filter");
                    var id = $selected_filter.data('id');
                    var title = $selected_filter.text();
                    var json_data = $selected_filter.data('filter');

                    $form.attr('action', '<?=llink('/mobile/game-filter/')?>');
                    $form.find('input[name=\'id\']').val(id);
                    $form.find('input[name=\'title\']').val(title);
                    $form.find('input[name=\'filter\']').val(JSON.stringify(json_data));
                    $form.find('input[name=\'redirect\']').val('<?=llink('/mobile/casino/')?>');
                    $form.submit();
                }
            });
        }

        function mainButtonHandlers() {
            $('#delete-submit').off().click(confirmDelete);
            $('.search-submit').off().click(submitSearchRequest);
            $('.save-submit').off().click(confirmSave);
        }

        var filterPopupImage = !is_old_design ? 'privacy-confirmation.png' : '';

        function confirmSave() {
            var id = $('#game-filter-form input[name=id]').val();
            if (id) {
                submitFilter($('#filter-name').text());
            } else {
                mboxDialog(
                    $(".save-popup-html-wrapper").html(),
                    null,
                    '<?php echo addslashes(t('mobile.games.filter.popup.save.cancel_button')) ?>',
                    submitFilter,
                    '<?php echo addslashes(t('mobile.games.filter.popup.save.accept_button')) ?>',
                    null,
                    null,
                    true,
                    'save-popup-cancel-button',
                    '<?php echo addslashes(t('mobile.games.filter.popup.save.title')) ?>',
                    'save-popup-accept-button',
                    null,
                    filterPopupImage
                );
                $('.mbox-msg-title-bar').addClass('save-popup-accept-title');
                $('.save-popup-cancel-button').css('display', 'none');
                $('.save-popup-accept-button').attr('disabled', 'disabled');

                $(".save-popup-html input[name='title']").on('change keyup', function () {
                    var input_value = $(this).val();
                    var $input_validation = $('.save-popup-html .popup-input-validation');
                    if (input_value.length > 0 && input_value.length <= 35) {
                        $input_validation.css('visibility', 'hidden');
                        $('.save-popup-accept-button').removeAttr('disabled');
                    } else {
                        $input_validation.css('visibility', 'visible');
                        $('.save-popup-accept-button').attr('disabled', 'disabled');
                    }
                });
            }
        }

        function confirmDelete() {
            mboxDialog(
                $(".delete-popup-html-wrapper").html(),
                null,
                '<?php echo addslashes(t('mobile.games.filter.popup.delete.cancel_button')) ?>',
                submitDeleteRequest,
                '<?php echo addslashes(t('mobile.games.filter.popup.delete.accept_button')) ?>',
                null,
                null,
                true,
                'delete-popup-cancel-button',
                '<?php echo addslashes(t('mobile.games.filter.popup.save.title')) ?>',
                'delete-popup-accept-button',
                null,
                filterPopupImage
            );
            $('#mbox-msg .filter-title').html('<?=$this->saved_filter['title']?>');
            $('.delete-popup-cancel-button').click(function () {
                $('.multibox-close').trigger('click');
            });

        }

        function submitSearchRequest() {
            <?php
            // Get the country of the user logged in as it is required for the query.
            $country = getCountry();

            // Get the language as it is required to prevent the page from reverting to English.
            $language = phive('Localizer')->getLanguage();
            $language = $language ? "$language/": ''; // If $language holds a valid value then append a '/' to the end of the string.

            // Get the parent_id for the page we want from the upcoming query.
            $parent_id = phive('SQL')->load1DArr("SELECT page_id FROM pages WHERE alias='mobile' LIMIT 1", 'page_id')[0];

            // Load the page_id of the page we are routing from.
            $page_id = phive('SQL')->load1DArr("SELECT page_id FROM pages WHERE alias='casino' AND parent_id=$parent_id LIMIT 1", 'page_id')[0];

            // Check the routing table for the route, if it doesn't exist then default to 'mobile/casino' as was hard-coded in before.
            $casino = phive('SQL')->load1DArr("SELECT route FROM page_routes WHERE page_id=$page_id AND country='$country' LIMIT 1", 'route')[0] ?? 'mobile/casino';

            /* $casino should have the string 'mobile/' prefixed to it. If the above query doesn't return a string with 'mobile/' prefixed
               Then prefix it to the variable. */
            if (substr($casino, 0, 7) != 'mobile/') {
                $casino = "mobile/$casino";
            }

            //if we have language in uri then lets use it as a part of an action uri
            if ($language && strpos($_SERVER['REQUEST_URI'], $language) !== false){
                $action = "$language$casino";
            } else {
                $action = $casino;
            }

            ?>
            console.log('search_submit');
            var $form = $('#game-filter-form');
            $form.attr('action', '/<?="$action"?>/');
            $form.find('input[name=\'method\']').val('<?=self::SEARCH_METHOD?>');
            submitFilter('');
        }
    </script>
    <?php
  }

  /**
   * @return void
   */
  function printWhatIsHotInput()
  {
    foreach (self::HOT_MAP as $tag) {
      ?>
      <span class="checkbox-item pull-left">
        <label for="<?= $tag['alias'] ?>"><?= t($tag['text']) ?></label>
        <input class="pull-right" type="radio" name="hot"
               data-label="<?= t('mobile.games.filter.section.what_is_hot.order_by') ?>"
               data-name="<?= t($tag['text']) ?>" value="<?= $tag['alias'] ?>" id="<?= $tag['alias'] ?>">
      </span>
      <?php
    }
    ?>
    <br class="clear-both">

    <div class="select-item">
      <label for="what-is-hot-sort"><?= t('mobile.games.filter.section.what_is_hot.order_by') ?></label>
      <select data-name="<?= t('mobile.games.filter.section.what_is_hot.order_by') ?>" class="pull-right"
              id="what-is-hot-sort" name="what-is-hot-sort">
        <option value="asc"><?= t('mobile.games.filter.section.what_is_hot.order_by.asc') ?></option>
        <option value="desc"><?= t('mobile.games.filter.section.what_is_hot.order_by.desc') ?></option>
      </select>
    </div>
    <?php
  }

  /**
   * @return void
   */
  function printCategoriesInput()
  {
    $this->printSelectAllHandlers();
    foreach (self::CATEGORY_MAP as $cat_tag) {
      $category = t($cat_tag);
      ?>
      <span class="checkbox-item pull-left">
        <label for="<?= $cat_tag ?>"><?= $category ?></label>
        <input class="pull-right" type="checkbox" name="categories"
               value="<?= $cat_tag ?>" data-name="<?= $category ?>" id="<?= $cat_tag ?>">
      </span>
      <?php
    }
    ?>
    <?php
  }

  /**
   * @return void
   */
  function printTypesInput()
  {
    $this->printSelectAllHandlers();
    foreach ($this->all_tag_arr as $tag) {
      ?>
      <span class="checkbox-item pull-left">
        <label for="<?= $tag['alias'] ?>"><?= $tag['txt'] ?></label>
        <input class="pull-right" type="checkbox" name="types"
               value="<?= $tag['alias'] ?>" data-name="<?= $tag['txt'] ?>" id="<?= $tag['alias'] ?>">
      </span>
      <?php
    }
    ?>
    <?php
  }

  /**
   * @return void
   */
  function printFeaturesInput()
  {
    $this->printSelectAllHandlers();
    foreach ($this->game_features as $feature) {
      if (in_array($feature, self::CATEGORY_MAP)) {
        continue;
      }
      ?>
      <span class="checkbox-item pull-left">
        <label for="<?= $feature ?>"><?= t($feature) ?></label>
        <input class="pull-right" type="checkbox" id="<?= $feature ?>" name="features" value="<?= $feature ?>"
               data-name="<?= t($feature) ?>">
      </span>
      <?php
    }
  }

  /**
   * @return void
   */
  function printPayoutInput()
  {
    $f = new FormerCommon();
    $months = array_merge(array('' => t('all.time')), array_reverse($f->getYearMonths(date('Y') - 1), true));
    ?>
    <div class="select-item">
      <label for="rtp"><?= t('mobile.games.filter.section.game_payout.rtp') ?></label>
      <select data-name="<?= t('mobile.games.filter.section.game_payout.rtp') ?>" class="pull-right"
              id="rtp" name="rtp">
        <option value="<?= t('theoretical') ?>"><?= t('theoretical') ?></option>
        <option value="<?= t('actual') ?>"><?= t('actual') ?></option>
      </select>
    </div>
    <div class="select-item">
      <label for="rtp-date"><?= t('mobile.games.filter.section.game_payout.date') ?></label>
      <?php
      dbSelect('rtp-date', $months, '', [], "pull-right", false, "data-name='" . t('mobile.games.filter.section.game_payout') . ' ' . t('mobile.games.filter.section.game_payout.date') . "'", "rtp-date") ?>
    </div>
    <br style="clear: both">

    <?php
    $this->printRangeSlider('rtp-slider-range', '0', '100', t('mobile.games.filter.section.game_payout'), '%');
  }

  /**
   * @return void
   */
  function printVolatilityInput()
  {
    ?>
    <span class="checkbox-item pull-left" style="width: 50%">
      <label for="volatility-high"><span class="icon icon-vs-flame margin-ten-right"
                                         style="color:#d30706"></span><?= t('mobile.games.filter.section.volatility.high') ?></label>
      <input class="pull-right" type="checkbox" id="volatility-high" name="volatility" value="volatility-high"
             data-name="<?= t('mobile.games.filter.section.volatility.high') ?>">
    </span>
    <span class="checkbox-item pull-left" style="width: 50%">
      <label for="volatility-medium"><span class="icon icon-vs-flame margin-ten-right"
                                           style="color:#277d10"></span><?= t('mobile.games.filter.section.volatility.medium') ?></label>
      <input class="pull-right" type="checkbox" id="volatility-medium" name="volatility" value="volatility-medium"
             data-name="<?= t('mobile.games.filter.section.volatility.medium') ?>">
    </span>
    <span class="checkbox-item pull-left" style="width: 50%">
      <label for="volatility-low"><span class="icon icon-vs-flame margin-ten-right"
                                        style="color:#eaa80b"></span><?= t('mobile.games.filter.section.volatility.low') ?></label>
      <input class="pull-right" type="checkbox" id="volatility-low" name="volatility" value="volatility-low"
             data-name="<?= t('mobile.games.filter.section.volatility.low') ?>">
    </span>
    <br class="clear-both">

    <p style="margin-bottom: 0"><?= t('mobile.games.filter.section.volatility.bet_size') ?></p>
    <? $this->printRangeSlider('bet-size-slider-range', $this->min_bet, $this->max_bet, t('mobile.games.filter.section.volatility.bet_size'), '€') ?>
    <br>
    <p style="margin-bottom: 0"><?= t('mobile.games.filter.section.volatility.bet_line') ?></p>
    <? $this->printRangeSlider('bet-line-slider-range', $this->min_lines, $this->max_lines, t('mobile.games.filter.section.volatility.bet_line')) ?>
    <?php
  }

  /**
   * @return void
   */
  function printProvidersInput()
  {
    ?>
    <div class="type-search">
      <input id="type-search" class='search-input'
             placeholder="<?= t('mobile.games.filter.section.game_providers.search.placeholder') ?>">
      <button class="clear" onclick="clearInput('type-search')"><span class="icon icon-vs-close"
                                                                      style="position: relative; top: 2px"></span>
      </button>
    </div>
    <?php
    $this->printSelectAllHandlers();
    foreach ($this->game_providers as $game_provider) {
      $game_provider = $game_provider['operator'];
      $game_provider_id = strtolower(str_replace(' ', '', $game_provider));
      ?>
      <span class="checkbox-item pull-left">
        <label for="<?= $game_provider_id ?>"><?= $game_provider ?></label>
        <input class="pull-right" type="checkbox" id="<?= $game_provider_id ?>" name="providers"
               data-name='<?= $game_provider ?>' value="<?= $game_provider ?>">
      </span>
      <?php
    }
  }

  function printSelectAllHandlers()
  {
    ?>
    <div class="center-stuff">
      <button class="checkbox-handler grey-framed-btn"
              data-checked="true"><?= t('mobile.games.filter.section.game_providers.select_all') ?></button>
      <button class="checkbox-handler grey-framed-btn"
              data-checked="false"><?= t('mobile.games.filter.section.game_providers.deselect_all') ?></button>
    </div>
    <?php
  }

  /**
   * @param string $filter_type
   * @param bool $hide_ids
   * @return void
   */
  function printSubmitButtons(string $filter_type = self::CUSTOM_FILTER_TYPE, bool $hide_ids = false)
  {
    ?>
    <div class="submit-buttons css-flex-uniform-section">
      <div class="css-flex-grow-1">
        <?php
        if ($filter_type === self::USER_FILTER_TYPE) {
          ?>
          <button data-checked="true" style="background-color: #e00000;"
                  <?php if (!$hide_ids): ?>id="delete-submit"<?php endif; ?>
                  class="mobile-button"><?= t('mobile.games.filter.submit.delete') ?></button>
          <?php
        } else {
          ?>
          <button data-checked="true" class="mobile-search-button mobile-button <?php if (!$hide_ids): ?>search-submit<?php endif; ?>">
            <?= t('mobile.games.filter.submit.search') ?>
          </button>
          <?php
        }
        ?>
      </div>
      <?php if (!empty($this->cu)) { ?>
        <div class="css-flex-grow-1">
          <button data-checked="false" class="mobile-save-button mobile-button <?php if (!$hide_ids): ?>save-submit<?php endif; ?>">
            <?= t('mobile.games.filter.submit.save') ?></button>
        </div>
      <?php } ?>
    </div>
    <?php
  }

  /**
   * @param string $id
   * @param int $min
   * @param int $max
   * @param string $name
   * @param string $value_postfix
   * @param string $classes
   * @return void
   */
  function printRangeSlider(string $id, int $min, int $max, string $name, string $value_postfix = '', string $classes = '')
  {
    ?>
    <div class="css-flex-uniform-section range-slider <?= $classes ?>">
      <div class="css-flex-align-end" style="width: 12%;"><?= $min . $value_postfix ?></div>
      <div style="width: 76%">
        <input data-name="<?= $name ?>" data-value-postfix="<?= $value_postfix ?>" class="input-slider" id="<?= $id ?>"
               data-default-from="<?= $min ?>" data-default-to="<?= $max ?>"/>
      </div>
      <div class="css-flex-align-end" style="width: 12%;"><?= $max . $value_postfix ?></div>
    </div>
    <input type="hidden" id="<?= $id . '-from' ?>" name="<?= $id . '-from' ?>" value="<?= $min ?>"
           data-default="<?= $min ?>"/>
    <input type="hidden" id="<?= $id . '-to' ?>" name="<?= $id . '-to' ?>" value="<?= $max ?>"
           data-default="<?= $max ?>"/>
    <?php
  }

  /**
   * @param string $id
   * @param string $title
   * @param string $json
   * @param string $method
   * @param string $redirect
   * @return void
   */
  function printHiddenForm(string $id = '', string $title = '', string $json = '', string $method = self::POST_METHOD, string $redirect = '')
  {
    ?>
    <form method="post" id="game-filter-form">
      <input type="hidden" name="token" value="<?= $_SESSION['token'] ?>">
      <input type="hidden" name="id" value="<?= $id ?>"/>
      <input type="hidden" name="method" value="<?= $method ?>"/>
      <input type="hidden" name="title" value="<?= $title ?>"/>
      <input type="hidden" name="filter" value="<?= $json ?>"/>
      <input type="hidden" name="redirect" value="<?= $redirect ?>"/>
    </form>
    <?php
  }

  /**
   * @return void
   */
  function printHiddenPopups()
  {
    ?>
    <div class="save-popup-html-wrapper" style="display: none">
      <div class="save-popup-html">
        <label for="title">
          <?= t('mobile.games.filter.popup.save.title_input_label') ?>
        </label>
        <br>
        <input id="title" name='title' placeholder="<?= t('mobile.games.filter.popup.save.title_input_placeholder') ?>">
        <p class="popup-input-validation"><?= t('mobile.games.filter.popup.save.title_input_validation') ?></p>
      </div>
    </div>

    <div class="delete-popup-html-wrapper" style="display: none">
      <div class="delete-popup-html">
        <?= t('mobile.games.filter.popup.delete.text') ?>
      </div>
    </div>
    <?php
  }

  /**
   * @return void
   */
  function printProfileList()
  {
    if (!empty($this->cu)) {
      $this->user_filters = $this->rtp_select = phive('SQL')->sh($this->cu)->loadArray("
        SELECT * FROM users_game_filters WHERE user_id = {$this->cu->userId}
      ");
      ?>
      <div class="filter-select">
        <?php
        if (!empty($this->user_filters)) {
          foreach ($this->user_filters as $game_filter) {
            $chevron_class = 'chevron-down';
            $selected_class = '';
            $display_style = 'display: none';
            if ($game_filter['id'] == $_GET['filter_id']) {
              $chevron_class = 'chevron-up';
              $selected_class = 'selected-filter';
              $display_style = 'display: auto';
            }
            ?>
            <div class="section">
              <div class="header" style="position: relative">
                <a href="<?= llink('/mobile/game-filter?filter_id=' . $game_filter['id']) ?>" class="filter-edit">
                  <span class="icon icon-vs-pencil"></span>
                </a>
                <a data-id="<?= $game_filter['id'] ?>" data-filter='<?= $game_filter['filter'] ?>'
                   class="filter-title <?= $selected_class ?>"><?= $game_filter['title'] ?>&nbsp;</a>

                <a href="#" class="toggle-button">
                  <span class="icon icon-vs-chevron-right pull-right <?= $chevron_class ?>"></span>
                </a>
              </div>
              <div class="contents storage" style="<?= $display_style ?>">
                <?php $this->printStorageItems($game_filter['filter']) ?>
              </div>
            </div>
            <?php
          }

        $this->printSubmitButtons();
        $this->printHiddenForm();
        } else {
          ?>
          <div class="section">
            <div class="contents">
              <?= t('mobile.games.filter.no-saved-filters'); ?>
            </div>
          </div>
          <?php
        }
        ?>
      </div>
      <script>
          $(function () {
              $('.filter-select .storage-item .close').on('click', function () {
                  var $storage_item = $(this).closest('.storage-item');
                  var $filter_title = $(this).closest('.section').find('.filter-title');
                  var filter_json = $filter_title.data('filter');
                  var section = $storage_item.data('section');
                  var type = $storage_item.data('type');
                  var id;
                  switch (section) {
                      case 'checkbox':
                          var data = $storage_item.data('data');
                          var index = filter_json.checkbox[type].findIndex(function (value) {
                              return value.value == data.value && value.name == data.name;
                          });

                          if (index > -1) {
                              filter_json.checkbox[type].splice(index, 1);
                          }
                          break;

                      case 'slider':
                          id = $storage_item.data('id');
                          filter_json.slider[id].from = filter_json.slider[id].min;
                          filter_json.slider[id].to = filter_json.slider[id].max;
                          break;

                      case 'select':
                          id = $storage_item.data('id');
                          filter_json.select[id].value = filter_json.select[id].default_value;
                          filter_json.select[id].name = filter_json.select[id].default_name;
                          break;
                  }

                  $storage_item.remove();
                  $filter_title.attr('data-filter', JSON.stringify(filter_json));
                  $('.save-submit').removeClass("mobile-button--disabled");
                  alignCheckboxes();
              });

              $('.filter-select > .section > .header').on('click', function () {
                  if (!$(this).children('.filter-title').hasClass('selected-filter')) {
                      closeSelectedFilter($(this).closest('.filter-select'));
                      openFilter($(this));
                      alignCheckboxes();
                      $('.search-submit').removeClass("mobile-button--disabled");
                  }
              });
          });

          function closeSelectedFilter($selected_filters_div_object) {
              $selected_profile_title = $selected_filters_div_object.find('.selected-filter');
              $selected_profile_title.removeClass('selected-filter');
              $selected_profile_title.closest('.header').find('.toggle-button > .icon').removeClass('chevron-up');
              $selected_profile_title.closest('.header').find('.toggle-button > .icon').addClass('chevron-down');
              $selected_profile_title.closest('.section').find('.contents').slideUp();
          }

          function openFilter($filter_title_object) {
              // $filter_title_object.closest('.header').find('.toggle-button > .icon').trigger('click');
              $filter_title_object.children('.filter-title').addClass('selected-filter');
              $filter_title_object.find('.toggle-button > .icon').removeClass('chevron-down');
              $filter_title_object.find('.toggle-button > .icon').addClass('chevron-up');
              $filter_title_object.closest('.section').find('.contents').slideDown();
          }
      </script>
      <?php
      $this->printHelperJs();
    }
  }

  /**
   * @param string $json_filter
   * @return void
   */
  function printStorageItems(string $json_filter)
  {
    $game_filter = json_decode($json_filter, true);

    // render radio
    foreach ($game_filter['radio'] as $id => $types) {
      foreach ($types as $value) {
        ?>
        <div data-section="checkbox" data-type="<?= $id ?>"
             class="storage-item half" data-id="<?= $value['value'] ?>" data-name="<?= $value['name'] ?>">
          <span class="name"><?= $value['label'] . ': ' . ($value['name'] ?? $value['value']) ?></span>
          <span class="close"><span class="icon icon-vs-close-1"></span></span>
        </div>
        <?php
      }
    }

    // render checkboxes
    foreach ($game_filter['checkbox'] as $id => $types) {
      foreach ($types as $value) {
        ?>
        <div data-section="checkbox" data-type="<?= $id ?>"
             class="storage-item half" data-id="<?= $value['value'] ?>" data-name="<?= $value['name'] ?>">
          <span class="name"><?= $value['name'] ?? $value['value'] ?></span>
          <span class="close"><span class="icon icon-vs-close-1"></span></span>
        </div>
        <?php
      }
    }

    // render sliders
    foreach ($game_filter['slider'] as $id => $value) {
      if ($value['from'] != $value['min'] || $value['to'] != $value['max']) {
        ?>
        <div data-section="slider" data-id="<?= $id ?>" data-data='<?= json_encode($value) ?>'
             class="storage-item half">
          <span
            class="name"><?= $value['name'] . ': ' . $value['from'] . $value['postfix'] . ' - ' . $value['to'] . $value['postfix'] ?></span>
          <span class="close"><span class="icon icon-vs-close-1"></span></span>
        </div>
        <?php
      }
    }

    // render selects
    foreach ($game_filter['select'] as $id => $value) {
      if ($value['value'] != $value['default_value']) {
        ?>
        <div data-section="select" data-id="<?= $id ?>" data-data='<?= json_encode($value) ?>'
             class="storage-item half">
          <span
            class="name"><?= $value['label'] . ': ' . $value['name'] ?></span>
          <span class="close"><span class="icon icon-vs-close-1"></span></span>
        </div>
        <?php
      }
    }
    ?>
    <?php
  }

  /**
   * @param string $json_filter
   * @param array $options
   * @return array
   */
  function buildSQLFromFilter(string $json_filter, array $options = []): array
  {
    $game_filter = json_decode($json_filter, true);
    $this->normalizeFilterWithEmptyData($game_filter);

    return $this->buildMasterSQLFromFilter($game_filter, $options);
  }

  // To avoid weird checking on IF conditions
  function normalizeFilterWithEmptyData(&$filter)
  {

    $available = [];
    foreach ($filter as $filter_type => $subfilter) {
      switch ($filter_type) {
        case 'checkbox':
          $available = self::CHECKBOX_AVAILABLE_SUBFILTER;
          break;
        case 'select':
          $available = self::SELECT_AVAILABLE_SUBFILTER;
          break;
        case 'slider':
          $available = self::SLIDER_AVAILABLE_SUBFILTER;
          break;
      }
      foreach ($available as $type) {
        if (!isset($filter[$filter_type][$type])) {
          $filter[$filter_type][$type] = [];
        }
      }
    }
  }

  /**
   * @param array $game_filter
   * @return string
   */
  function buildPreconditionsFromFilters(array $game_filter): string
  {
    $where = '';

    // volatility
    if (!empty($game_filter['checkbox']['volatility'])) {
      $volatility_range = [];
      foreach ($game_filter['checkbox']['volatility'] as $volatility_array) {
        $volatility_range = array_merge($volatility_range, self::VOLATILITY_MAP[$volatility_array['value']]);
      }

      $volatility_in = phive('SQL')->makeIn($volatility_range);
      $where .= "\n AND mg.volatility IN ({$volatility_in})";
    }

    // game providers
    if (!empty($game_filter['checkbox']['providers'])) {
      $providers = [];
      foreach ($game_filter['checkbox']['providers'] as $provider_array) {
        $providers[] = $provider_array['value'];
      }

      $operators_in = phive('SQL')->makeIn($providers);
      $where .= "\n AND mg.operator IN ({$operators_in})";
    }

    // types
    if (!empty($game_filter['checkbox']['types'])) {
      $tag_array = [];
      foreach ($game_filter['checkbox']['types'] as $type_array) {
          if($type_array['value'] === 'jackpots') {
            $tag_array = array_merge($tag_array, self::JACKPOT_TYPES);
          }
        $tag_array[] = $type_array['value'];
      }
      $tags_in = phive('SQL')->makeIn($tag_array);
      $where .= "\n AND mg.tag IN ({$tags_in})";
    }

    //bet-size
    $min_bet_in_cents = $game_filter['slider']['bet-size-slider-range']['from'] * 100;
    $max_bet_in_cents = $game_filter['slider']['bet-size-slider-range']['to'] * 100;
    $where .= "
      AND (
        ( mg.min_bet BETWEEN {$min_bet_in_cents} AND {$max_bet_in_cents} )
        OR
        ( mg.max_bet BETWEEN {$min_bet_in_cents} AND {$max_bet_in_cents} )
      )
    ";

      //bet-lines

      $num_lines_from = (int)$game_filter['slider']['bet-line-slider-range']['from'];
      $num_lines_to = (int)$game_filter['slider']['bet-line-slider-range']['to'];
      $where .= " AND mg.num_lines BETWEEN {$num_lines_from} AND {$num_lines_to} ";

    $where .= " AND mg.device_type = 'html5' AND mg.active = 1 ";

    return $where;
  }

  /**
   * @param array $game_filter
   * @param array $options
   * @return array
   */
  function buildMasterSQLFromFilter(array $game_filter, array $options = []): array
  {
    $where_preconditions = $this->buildPreconditionsFromFilters($game_filter);
    $block_where = $this->mg->blockCountry();

    $select = '';
    $join = '';
    $where = '';
    $order_direction = phive('SQL')->escapeAscDesc($game_filter['select']['what-is-hot-sort']['value'] ?? 'ASC');
    $order_snippet = 'mg.game_name';
    $play_count_period = '';

    $tags = [];
    // what is hot
    if (!empty($game_filter['radio']['hot'])) {
      foreach ($game_filter['radio']['hot'] as $value_array) {
        switch ($value_array['value']) {
          case 'order.latest':
            $order_snippet = 'mg.id';
            break;
          case 'order.a-z':
            $order_snippet = 'mg.game_name';
            break;
          case 'order.hot':
            $play_count_period = phive()->yesterday();
            break;
          case 'order.popular':
            $play_count_period = phive()->lastMonth();
            break;
        }
      }

      if (!empty($play_count_period)) {
        $country_join = '';
        $country = cuCountry();
        $countries = explode(" ", phive("Config")->getValue('countries', 'gamechoose'));
        if (!empty($country) && !phive()->isEmpty($countries) && in_array($country, $countries)) {
          $country_join = "AND gc.country = '$country'";
        }
        $gcache_tbl = phive()->isDate($play_count_period) ? 'game_cache' : ' game_month_cache';
        $order_snippet = 'played_times_in_period';
        $join = "LEFT JOIN $gcache_tbl AS gc ON mg.ext_game_name = gc.game_ref AND gc.day_date = '$play_count_period' $country_join";
        $select = " , SUM(gc.played_times) as played_times_in_period ";
      }
    }

    $category_array = $game_filter['checkbox']['categories'] ?? [];
    $category_values = array_column($category_array, 'value');
    $is_view_all_chosen = in_array('view-all.cgames', $category_values, true);

    // categories
    if (!$is_view_all_chosen) {
      foreach ($category_array as $category) {
        $tags[] = $category['value'];
      }
    }

    // features
    if (!empty($game_filter['checkbox']['features'])) {
      foreach ($game_filter['checkbox']['features'] as $feature_array) {
        $tags[] = $feature_array['value'];
      }
    }

    if (!empty($tags)) {
      $join .= "
              LEFT JOIN game_tag_con AS gtc ON mg.id = gtc.game_id
              LEFT JOIN game_tags AS gt ON gtc.tag_id = gt.id AND gt.filterable = 1
            ";

      $features_where_snippet = ' AND ';

      if (!empty($tags)) {
        $tags_in = phive('SQL')->makeIn($tags);
        $tags_query ="
            SELECT game_id
            FROM game_tag_con
            INNER JOIN game_tags gt ON game_tag_con.tag_id = gt.id
            WHERE alias IN ({$tags_in})
            GROUP BY game_id
        ";

        $features_where_snippet .= " mg.id IN  ({$tags_query}) ";
      }

      $where .= $features_where_snippet;
    }

    // rtp
    if (!empty($game_filter['slider']['rtp-slider-range'])) {

      $rtp_from = $game_filter['slider']['rtp-slider-range']['from'] / 100;
      $rtp_to = $game_filter['slider']['rtp-slider-range']['to'] / 100;

      if (strtolower($game_filter['select']['rtp']['value']) == 'actual') {
        $payout_games = $this->mg->getByPaymentRatio($game_filter['select']['rtp-date']['value'], 'payout_ratio DESC', 'html5');
        $ids_array = [];
        foreach ($payout_games as $value) {
          if ($value['payout_ratio'] >= $rtp_from && $value['payout_ratio'] <= $rtp_to) {
            $ids_array[] = $value['id'];
          }
        }
          // No point in creating a filter if there are no ids
          if(!empty($ids_array)){
              $ids_in = phive('SQL')->makeIn($ids_array);
              $where .= " AND mg.id IN ({$ids_in})";
          }
      } else {
        $where .= "  AND mg.payout_percent BETWEEN {$rtp_from} AND {$rtp_to} ";
      }
    }

    $sql = "
      SELECT mg.* {$select}
      FROM micro_games AS mg {$join}
      WHERE mg.device_type = 'html5'
        AND mg.active = 1
        {$where_preconditions}
        {$where}
        {$block_where}
      GROUP BY mg.id
    ";

    $sql_total_count = "
      SELECT COUNT(*) as count
      FROM (
        SELECT mg.id {$select}
        FROM micro_games AS mg {$join}
        WHERE mg.device_type = 'html5'
          AND mg.active = 1
          {$where_preconditions}
          {$where}
          {$block_where}
        GROUP BY mg.id
      ) AS games_total
    ";

    $boosted_game_ids = "";
    $sql_boosted_games = $this->buildBoostedGamesQueryFromMasterSQL($sql, $options);
    if ($sql_boosted_games !== null) {
      $boosted_game_ids = phive('SQL')->loadCol($sql_boosted_games, 'id');
      $boosted_game_ids = phive('SQL')->makeIn($boosted_game_ids);
    }

    $default_sorting = "{$order_snippet} {$order_direction}";
    $sql = $this->addSortClauseWithBoostedGamesFirst($sql, $default_sorting, $boosted_game_ids);

    if (!empty($options['pagination'])) {
      $sql .= " LIMIT {$options['pagination']['length']} OFFSET {$options['pagination']['offset']}";
    }

    return [
      'paginated_query' => $sql,
      'total_count_query' => $sql_total_count,
    ];
  }

    /**
     * Returns a SQL query based on the query of the function buildMasterSQLFromFilter,
     * modifying where clause and limit.
     *
     * This query will be useful to fetch the boosted games in the given query.
     *
     * @param string $base_master_query
     * @param array $options
     * @return string|null
     */
    function buildBoostedGamesQueryFromMasterSQL(string $base_master_query, array $options): ?string {
        $count_boosted = $options['count_boosted'];
        $boosted_games_query = $base_master_query;
        if (empty($count_boosted)) {
            return null;
        }

        $where_boosted_games = " WHERE mg.payout_extra_percent > 0 AND ";
        $boosted_games_query .= " LIMIT {$options['count_boosted']}";
        $boosted_games_query = preg_replace("/WHERE/i", $where_boosted_games, $boosted_games_query);

        return $boosted_games_query;
    }

    /**
     * Returns a query base on param $base_master_query with the sort clause following the next criteria:
     *
     * If the parameter $boosted_games with the following structure is given "id, id, id, id", so the
     * "ORDER BY" will include a "CASE" to return first these ids and after the normal order from $base_master_query
     *
     * @param string $base_master_query
     * @param string $default_sorting
     * @param string $boosted_games
     * @return string
     */
    function addSortClauseWithBoostedGamesFirst(string $base_master_query, string $default_sorting, $boosted_games = ''): string {
        if (empty($boosted_games)) {
            return "{$base_master_query} ORDER BY {$default_sorting} ";
        }

        return "{$base_master_query} ORDER BY CASE WHEN mg.id IN ({$boosted_games}) THEN 1 ELSE 3 END ASC, $default_sorting ";
    }
}
