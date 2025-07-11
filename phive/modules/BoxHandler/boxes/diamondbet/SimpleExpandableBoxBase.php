<?php
require_once __DIR__.'/../../../../../diamondbet/boxes/DiamondBox.php';
class SimpleExpandableBoxBase extends DiamondBox{

    function init()
    {
        /**
         * replacers will be part of box_attributes table.
         * format like value1:replacedValue1, value2:replacedValue2
         */
        $this->handlePost(
            array(
                'string_name',
                'box_class',
                'replacers'
            ),
            array(
                'string_name' => 'simple.'.$this->getId().'.html',
                'box_class' => 'frame-block',
                'replacers' => ''
            )
        );

        $this->cur_lang = phive('Localizer')->getLanguage();
    }

    /**
    *
    * Check if editcontent query string is passed
    *
    * @return bool
    */
    public function isEditing()
    {
        return isset($_GET['editcontent']);
    }

   /**
   *
   * Used to write javascript blocks
   *
   * @return void
   */
    public function js()
    { ?>
        <script>
            function showContent(params) {
                params.operator = $("#operator :selected").val();
                var func1Params = Object.assign(
                    {},
                    params,
                    { func: 'printContent' },
                    { page_id: '<?= phive('Pager')->getId() ?>' },
                    { is_user_play_blocked: '<?= (lic('isUserPlayBlocked')) ?? 0 ?>'}
                );

                new Promise((resolve, reject) => {
                    ajaxGetBoxHtml(func1Params, '<?php echo $this->cur_lang ?>', <?php echo $this->getId() ?>, function (ret) {
                        $("#show-page-content").html(ret);
                        insertButtonOnBattlePage();
                        resolve();
                    });
                });
            }

            $(document).ready(function () {
                showContent({});
            });

            /**
             * On 'battle.of.slots.battle-of-slots.html' alias
             * Button to redirect to BOS battle or encore lobby from BoS FAQ section
             * Battle lobby popup
             *
             */
            function insertButtonOnBattlePage() {
                let bos_url = '<?php echo phive('Tournament')->getSetting('mobile_bos_url'); ?>',
                    is_bos = '<?php echo(hasMobileMp() || hasMp()) ?>',
                    alias_go_to_battle_lobby = '<?php echo t('battle.of.slots.battle-of-slots.bos.button'); ?>',
                    brand_name = '<?php echo phive('BrandedConfig')->getBrand() ?>',
                    faqButtonHTMLContent = $(`<div class = "faq__button-container" >
                <button class = "btn btn-xl btn-default-xl gradient-default faq__button">${alias_go_to_battle_lobby}</button>
                </div>`),
                    firstAnchor;

                if ($(".mrvegas-encores-faq__content").length >= 1 && brand_name == 'mrvegas') {
                    firstAnchor = $(".mrvegas-encores-faq__content h2").eq(0);
                    faqButtonHTMLContent.insertBefore(firstAnchor);
                } else {
                    firstAnchor = $(".battle-of-slots-faq__content h2").eq(0);
                    faqButtonHTMLContent.insertBefore(firstAnchor);
                }

                //Hide button for desktop version, show only for API
                $('.faq__button-container-mobile').hide();

                if (!is_bos) {
                    $('.faq__button-container').hide();
                }

                $('.faq__button').on('click', function (e) {
                    if (siteType == 'mobile') {
                        licJson('beforePlay', {}, function (ret) {
                            if (ret.url) {
                                goTo(ret.url + '?redirect_after_action=' + bos_url);
                            } else {
                                goToMobileBattleOfSlots(bos_url);
                            }
                        });
                    } else {
                        showMpBox('/tournament/')
                    }
                });
            }
        </script>
        <?php
    }

    function printHtml()
    {
        // Show plain content if no page found OR isEditing is true
        // Else show ajax content
        if (!phive()->isAjaxCacheAdded() || $this->isEditing() || phive()->isMobileApp()) {
            $this->printContent();
        } else {
            $this->js();
            ?>
                <div id="show-page-content"></div>
            <?php
        }
    }

    public function printContent()
    {
        $page_id                = (!empty($_REQUEST['page_id'])) ? $_REQUEST['page_id'] : phive('Pager')->getId();
        $box_id                 = $this->getId();
        $is_user_play_blocked   = (!empty($_REQUEST['is_user_play_blocked'])) ? $_REQUEST['is_user_play_blocked'] : lic('isUserPlayBlocked');
        ?>
        <?php if (!$is_user_play_blocked) { ?>
            <div class="<?php echo $this->box_class; ?>">
                <div class="frame-holder cms-page <?php echo $page_id; echo $box_id; ?>">
                    <?php
                    // TODO: make sure the image and the text are showing the same offer for the bonus code
                    // If either the image or the text for a bonus code is missing, we fall back to the default.
                    // So we need to make sure both will fall back to default if one of those is missing
                    $bonus_code = phive('SQL')->escape(phive('Bonuses')->getBonusCode());    // $bonus_code will be a quoted string, so do not use quotes inside the sql query in getBonusAlias() !!

                    if($page_id == 324 && $box_id == 945) {
                        // show correct banner and text for freespins
                        $default_alias  = "bannertext.freespins.freespins.all.default.html";
                        $bonus_alias    = $this->getBonusAlias($bonus_code, 'bannertext.freespins.freespins.all.');
                        if(!$this->canNetent()) {
                            $default_alias  = "bannertext.freespins.freespins.exceptions.default.html";
                            $bonus_alias    = $this->getBonusAlias($bonus_code, 'bannertext.freespins.freespins.exceptions.');
                        }
                    } elseif($page_id == 298 && $box_id == 887) {
                        // show correct banner and text for welcome bonus
                        if(!isLogged()) {
                            $default_alias  = "bannertext.welcomebonus.welcomebonus.default.html";
                        }else {
                            $default_alias  = "bannertext.welcomebonus.welcomebonus.default.loggedIn.html";
                        }
                        $bonus_alias    = $this->getBonusAlias($bonus_code, 'bannertext.welcomebonus.welcomebonus.');
                    } elseif($page_id == 322 && $box_id == 943) {
                        // show correct text for mobile/promotions/welcome-bonus
                        if(!isLogged()) {
                            $default_alias = "bannertext.mobilehomepage.welcomebonus.default.html";
                        }else {
                            $default_alias = "bannertext.mobilehomepage.welcomebonus.default.loggedIn.html";
                        }
                        $bonus_alias    = $this->getBonusAlias($bonus_code, 'bannertext.mobilehomepage.welcomebonus.');
                    } elseif($page_id == 321 && $box_id == 940) {
                        // show correct text for mobile/promotions/welcome-free-spins
                        $default_alias  = "bannertext.mobilehomepage.freespins.all.default.html";
                        $bonus_alias    = $this->getBonusAlias($bonus_code, 'bannertext.mobilehomepage.freespins.all.');
                        if(!$this->canNetent()) {
                            $default_alias  = "bannertext.mobilehomepage.freespins.exceptions.default.html";
                            $bonus_alias    = $this->getBonusAlias($bonus_code, 'bannertext.mobilehomepage.freespins.exceptions.');
                        }
                    } /*elseif($page_id == 361 && $box_id == 1019) {
                            // show correct text for Terms and Conditions MGA Specific
                            $default_alias  = "bannertext.termsconditionsmgaspecific.default.html";    // todo: insert this string on live
                            $bonus_alias    = $this->getBonusAlias($bonus_code, 'bannertext.termsconditions.');
                        } elseif($page_id == 360 && $box_id == 1018) {
                            // show correct text for Terms and Conditions General
                            $default_alias  = "bannertext.termsconditionsgeneral.default.html";    // todo: insert this string on live
                            $bonus_alias    = $this->getBonusAlias($bonus_code, 'bannertext.termsconditions.');
                        }*/
                    else {
                        $default_alias  = $this->string_name;
                        $bonus_alias    = $this->string_name;
                    }
                    if(!empty(phive('Bonuses')->getBonusCode()) && !empty(t($bonus_alias))) {
                        $this->echoLocalizedString($bonus_alias);
                    } else {
                        $this->echoLocalizedString($default_alias);
                    }
                    ?>
                </div>
            </div>
        <?php }
    }

    /**
     * @param string $alias
     */
    function echoLocalizedString($alias)
    {
        $formattedReplacers = $this->getReplacers($this->replacers);
        $localized_string = !empty($this->replacers) ? tAssoc($alias, $formattedReplacers, null, true) : t($alias);
        if($localized_string != "($alias)" && $localized_string != '') {
            echo $localized_string;
        } else {
            // translation not found, use old translation
            echo !empty($this->replacers) ? tAssoc($this->string_name, $formattedReplacers, null, true) : t($this->string_name);
        }
    }

    /**
     *
     * @param string $bonus_code
     * @param string $base_target_alias
     *
     * @return string
     */
    function getBonusAlias($bonus_code, $base_target_alias)
    {
        $sql = "SELECT target_alias FROM localized_strings_connections
                WHERE bonus_code = {$bonus_code}
                AND target_alias LIKE '{$base_target_alias}%'";
        $bonus_alias = phive('SQL')->getValue($sql);

        return $bonus_alias;
    }

    function printExtra(){ ?>
        <p>
            <label>String name: </label>
            <input type="text" name="string_name" value="<?php echo $this->string_name ?>" />
        </p>
        <p>
            <label>Box class: </label>
            <input type="text" name="box_class" value="<?php echo $this->box_class ?>" />
        </p>
        <p>
            <label>Replacers: </label>
            <br/>
            <span> format will be like value1:replacedValue1,value2:replacedValue2 </span>
            <br/>
            <input type="text" name="replacers" value="<?php echo $this->replacers ?>" />
        </p>
        <?php
    }


    /**
     * replacers will be part of box_attributes table.
     * format like value1:replacedValue1, value2:replacedValue2
     */

    function getReplacers($replacers): array
    {
        $pairs = explode(',', $replacers);

        $assoc_array = [];
        foreach ($pairs as $pair) {
            list($key, $value) = explode(':', $pair);
            $assoc_array[$key] = $value;
        }

        return $assoc_array;
    }
}
