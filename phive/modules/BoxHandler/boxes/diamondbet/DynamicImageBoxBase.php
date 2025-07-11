<?php
require_once __DIR__.'/../../../../../diamondbet/boxes/DiamondBox.php';
class DynamicImageBoxBase extends DiamondBox{

    public function init()
    {
        $box_attributes = [
            'jp_counter',
            'jp_counter_excluded_countries',
            'banner_html',
            'image',
            'url',
            'width',
            'height',
            'logged_url'
        ];
        $box_default_attributes = ['width' => 680, 'height' => 300];

        $this->languages = array_column(phive('Localizer')->getAllLanguages(), 'language');
        // Adding specific box attributes for each language
        foreach($this->languages as $language){
            $box_attributes[] = "url_{$language}";
            $box_attributes[] = "logged_url_{$language}";
            $box_attributes[] = "overlay_link_{$language}";
            $box_attributes[] = "logged_overlay_link_{$language}";
        }

        $this->handlePost($box_attributes, $box_default_attributes);
        if (isLogged()) {
            $this->logged = 'loggedin';
            $this->goto = $this->getBoxAttributeOverrideByLanguage('logged_url');
            $this->overrided_overlay_link = $this->getBoxAttributeOverrideByLanguage('logged_overlay_link');
        } else {
            $this->logged = 'loggedout';
            $this->goto = $this->getBoxAttributeOverrideByLanguage('url');
            $this->overrided_overlay_link = $this->getBoxAttributeOverrideByLanguage('overlay_link');
        }

        if (isset($_POST['save_settings']) && $_POST['box_id'] == $this->getId()) {
            phive('BoxHandler')->createImageAliasesForDynamicImageBox($this->box_id);
        }
    }

    /**
     * @return array|null
     */
    private function extractGameFromUrl()
    {
        $url_segments = explode('/', $this->goto);
        $is_game_url = in_array('play', $url_segments, true);

        $game_url = null;
        if ($is_game_url) {
            $key = array_search('play', $url_segments);
            $game_url = $url_segments[$key + 1];
        }

        $game = null;
        if ($game_url) {
            $mg = phive('MicroGames');
            $game = $mg->getByGameUrl($game_url, "device_type = 'flash'", true);
        }

        return $game;
    }

    public function printHTML()
    {
        $page_id = phive('Pager')->getId();

        $jp_counter_excluded_countries = explode(' ', $this->jp_counter_excluded_countries);
        $should_show_jp_counter = $this->jp_counter && !in_array(phive('Licensed')->getLicCountry(), $jp_counter_excluded_countries);

        $game = null;
        if ($should_show_jp_counter) {
            $game = $this->extractGameFromUrl();
        }

        ?>
        <div class="dynamic-image">
            <?php if(!empty($this->image)): ?>
                <a href="<?php echo llink($this->goto) ?>">
                    <img src="<?php echo $this->image ?>" />
                </a>
            <?php elseif(!empty($this->banner_html)): ?>
                <?php echo $this->banner_html ?>
            <?php else: ?>
                <a href="<?php echo llink($this->goto) ?>" style="display: block; float: left;">
                    <?php

                        // NOTE: We are showing different banners for logged-in users
                        if($this->logged == 'loggedout') {
                            // this is for logged out users
                            if($page_id == 3 && $this->getId() == 891) {
                                // check if we have a bonus code, and if so get the image aliases
                                $freespins_all_image_alias = '';
                                $freespins_exceptions_image_alias = '';
                                if(!empty(phive('Bonuses')->getBonusCode())) {
                                    $freespins_all_image_alias          = phive('ImageHandler')->getImageAliasForBonusCode('banner.homepage.freespins.all.');
                                    $freespins_exceptions_image_alias   = phive('ImageHandler')->getImageAliasForBonusCode('banner.homepage.freespins.exceptions.');
                                }

                                if($this->canNetent()) {
                                    $default_alias  = "banner.homepage.freespins.all.default";
                                    $bonus_alias    = $freespins_all_image_alias;
                                } else {
                                    $default_alias  = "banner.homepage.freespins.exceptions.default";
                                    $bonus_alias    = $freespins_exceptions_image_alias;
                                }

                                // check if this default alias exists, if not use the old alias
                                if(!phive('ImageHandler')->getID($default_alias)) {
                                    $default_alias = "fullimage.{$this->logged}.".$this->getId();
                                }
                                img($bonus_alias, $this->width, $this->height, $default_alias);

                            } elseif($page_id == 3 && $this->getId() == 893) {

                                $welcomebonus_image_alias = '';
                                if(!empty(phive('Bonuses')->getBonusCode())) {
                                    $welcomebonus_image_alias   = phive('ImageHandler')->getImageAliasForBonusCode('banner.homepage.welcomebonus.');
                                }

                                $default_alias = "banner.homepage.welcomebonus.default";

                                // check if this default alias exists, if not use the old alias
                                if(!phive('ImageHandler')->getID($default_alias)) {
                                    $default_alias = "fullimage.{$this->logged}.".$this->getId();
                                }
                                img($welcomebonus_image_alias, $this->width, $this->height, $default_alias);

                            } else {
                                img("fullimage.{$this->logged}.".$this->getId(), $this->width, $this->height);
                            }
                        } else {
                            // this is for logged-in users
                            if($page_id == 3 && $this->getId() == 891) {

                                $cashback_loggedin_image_alias = '';
                                if(!empty($_SESSION['affiliate'])) {
                                    $cashback_loggedin_image_alias          = phive('ImageHandler')->getImageAliasForBonusCode('banner.homepage.cashback.loggedin.');
                                }
                                $default_alias = "banner.homepage.cashback.loggedin.default";

                                // check if this default alias exists, if not use the old alias
                                if(!phive('ImageHandler')->getID($default_alias)) {
                                    $default_alias = "fullimage.{$this->logged}.".$this->getId();
                                }
                                img($cashback_loggedin_image_alias, $this->width, $this->height, $default_alias);

                            } elseif($page_id == 3 && $this->getId() == 893) {

                                $races_loggedin_image_alias = '';
                                if(!empty($_SESSION['affiliate'])) {
                                    $races_loggedin_image_alias          = phive('ImageHandler')->getImageAliasForBonusCode('banner.homepage.races.loggedin.');
                                }
                                $default_alias = "banner.homepage.races.loggedin.default";

                                // check if this default alias exists, if not use the old alias
                                if(!phive('ImageHandler')->getID($default_alias)) {
                                    $default_alias = "fullimage.{$this->logged}.".$this->getId();
                                }
                                img($races_loggedin_image_alias, $this->width, $this->height, $default_alias);

                            } else {
                                img("fullimage.{$this->logged}.".$this->getId(), $this->width, $this->height);
                            }
                        }
                    ?>
                </a>
            <?php endif?>
            <?php if ($should_show_jp_counter && isset($game['jp_value'])): ?>
                <?php $unique_id = uniqid() ?>
                <span class="jp-amount-badge jp-amount-badge-<?= $unique_id ?>" style="display: none;">
                    <?= efEuro($game['jp_value']) ?>
                </span>
                <script>
                    animateJackpotBadge('jp-amount-badge-<?= $unique_id?>');
                </script>
            <?php endif; ?>
            <div style="clear: both;">
                <?php if(!empty($this->overrided_overlay_link)): ?>
                <a href="<?php echo llink($this->overrided_overlay_link) ?>"
                   style="display: block; position: absolute; z-index: 1000;  width: <?php echo $this->width?>px;text-align: center;margin-top: -19px;">
                    <?php et("box{$this->getId()}.overlay.link") ?>
                </a>
                <?php endif ?>
            </div>
        </div>
        <?php
    }

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
                type="text"
                name="jp_counter_excluded_countries"
                value="<?= $this->jp_counter_excluded_countries; ?>"
            />
        </p>
        <p>
            <label for="image">Banner HTML: </label>
            <input type="text" name="banner_html" value=""/>
        </p>
        <p>
            <strong>Or input URL to an image:</strong>
        </p>
        <p>
            <label for="image">Image: </label>
            <input type="text" name="image" value="<?= $this->image; ?>"/>
        </p>
        <p>
            <strong>Or input width and height for a content image:</strong>
        </p>
        <p>
            Width:
            <input type="text" name="width" value="<?php echo $this->width ?>"/>
        </p>
        <p>
            Height:
            <input type="text" name="height" value="<?php echo $this->height ?>"/>
        </p>
        <p>URL if the image need to be linked to something (note that any "banner HTML" probably links properly already)</p>
        <?php
        array_unshift($this->languages, 'default');
        foreach($this->languages as $language):
            $title = "Links for '$language' language";
            $url_variable = "url";
            $logged_url_variable = "logged_url";
            if ($language !== 'default') {
                $url_variable = "url_$language";
                $logged_url_variable = "logged_url_$language";
            }
            ?>
            <h3><?= $title?></h3>
            <h4><?= ($language === 'default' ? '(fallback if no specific language is defined)' : '') ?></h4>
            <p>
                <label for="image">Logged out: </label>
                <br/>
                <input type="text" name="<?=$url_variable?>" value="<?= $this->{$url_variable}; ?>" id="<?=$url_variable?>"/>
            </p>
            <p>
                <label for="image">Logged in: </label>
                <br/>
                <input type="text" name="<?=$logged_url_variable?>" value="<?= $this->{$logged_url_variable}; ?>" id="<?=$logged_url_variable?>"/>
            </p>
            <hr>
        <?php endforeach; ?>
        <?php
        array_shift($this->languages);
        foreach($this->languages as $language):
            $logged_out_overlay_link = "overlay_link_{$language}";
            $logged_overlay_link = "logged_overlay_link_{$language}";
            ?>
            <h3><?= "Overlay Link for '{$language}' language" ?></h3>
            <p>
                <label for="image">Logged out: </label>
                <br/>
                <input type="text" name="<?=$logged_out_overlay_link?>" value="<?= $this->{$logged_out_overlay_link}; ?>" id="<?=$logged_out_overlay_link?>"/>
            </p>
            <p>
                <label for="image">Logged in: </label>
                <br/>
                <input type="text" name="<?=$logged_overlay_link?>" value="<?= $this->{$logged_overlay_link}; ?>" id="<?=$logged_overlay_link?>"/>
            </p>
            <hr>
        <?php endforeach; ?>
    <?php
    }
}

