<?php
require_once __DIR__.'/../../../../../diamondbet/boxes/DiamondBox.php';
class HomeLandingPageBoxBase extends DiamondBox{

    function printHtml() {

        $pager = phive('Pager');

        $landing_bkg = $pager->fetchSetting("landing_logo");

        $menuer = phive('Menuer');

        if (phive()->isMobile()) {
            $top_logos = lic('topMobileLogos');
            $secondary_menu = $menuer->forRender($menuer->getSecondaryMobileMenuId());
        } else {
            $top_logos = lic('topLogos', ['landing']);
            $secondary_menu = $menuer->forRender($menuer->getSecondaryMenuId());
        }

        $footer_menu_result = $menuer->forRender('footer');

        $filterLists = lic('getLicSetting', ['filter_landing_page_footer_menu']);

        $footer_menu = array_filter($footer_menu_result, function($item) use ($filterLists) {
            return !in_array($item['alias'], $filterLists);
        });

        $first_item = array_shift($footer_menu);

        $show_custom_content = phive("Pager")->getSetting('show_custom_content');

        ?>

        <div class="landing-page-container">
            <div class="landing-page-background">
                <img src="<?php echo fupUri($landing_bkg, true) ?>">
            </div>
            <div class="landing-page">
                <header class="landing-page__header">

                    <?php if(!empty($top_logos)): ?>
                        <div class="gradient-normal rg-top-<?php echo lic('getIso') ?>" id="rg-top-bar">
                            <div class="rg-top__container">
                                <?= lic('rgOverAge', [ 'logged-in-time' ,'over-age-desktop']); ?>
                                <?= lic('rgLoginTime', ['rg-top__item logged-in-time']); ?>
                                <?= $top_logos ?>
                            </div>
                        </div>
                        <br clear="all"/>
                    <?php endif ?>

                </header>

                <main class="landing-page__main-section">

                    <?php if($show_custom_content === true): ?>
                        <?php et('ks.landing.page.opening-soon.description') ?>
                    <?php else: ?>
                        <div class="top-game-section">

                            <?php et('ks.landing.page.game-section-offer'); ?>

                            <div class="bottom">

                                <div class="bottom-title"><?php et('ks.landing.page.game-section-title'); ?></div>

                                <div class="game-filter">

                                    <?php if ( phive()->isMobile() ): ?>

                                        <?php phive('Menuer')->renderSecondaryMobileMenu(); ?>

                                    <?php else: ?>
                                        <?php if(phive('Menuer')->getSetting('secondary_nav', false) && $secondary_menu): ?>
                                            <div id="<?= phive('Menuer')->getSecondaryMenuHtmlId() ?>">
                                                <ul>
                                                    <?php foreach($secondary_menu as $item): ?>
                                                        <li <?php echo $item['current'] ? 'class="active"' : '' ?> id=<?php echo 'sec-menu--' . ($item['alias'] != 'sportsbook'? $item['alias'] : 'sportsbook-prematch') ?>  onclick="secondaryMenuClickHandler('<?php echo $item['alias'] ?>')">
                                                            <a <?php echo $item['params'] ?>>
                                                                <span class="icon <?=$item['icon']?>"></span>
                                                                <?php echo $item['txt']?>
                                                            </a>
                                                        </li>
                                                    <?php endforeach ?>
                                                </ul>
                                            </div>
                                        <?php endif ?>
                                    <?php endif ?>

                                </div>

                                <div class="game-list">
                                    <?php et('ks.landing.page.game-cards') ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </main>
            </div>
        </div>

        <div class="landing-page">
            <footer class="landing-page__footer">
                <div class="footer-section">
                    <div class="footer-links-container">
                        <div class="footer-menu-links">
                            <div class="footer-icons">
                                <?php if(phive()->isMobile()): ?>
                                    <?php et('ks.landing.page.footer.icons.mobile') ?>
                                <?php else:?>
                                    <?php et('ks.landing.page.footer.icons') ?>
                                <?php endif;?>
                            </div>
                            <div class="footer-links">
                            <div class="list">
                                <ul>
                                    <li class="first"><a <?php echo $first_item['params']?>><?php echo $first_item['txt']?></a></li>
                                    <?php foreach($footer_menu as $item): ?>
                                        <li><a <?php echo $item['params']?>><?php echo $item['txt']?></a></li>
                                    <?php endforeach ?>
                                </ul>
                            </div>
                            <div class="customer-links">
                                <a href="/customer-service/" target="_blank">
                                    <div class="customer-service-section">
                                        <img src="/diamondbet/images/kungaslottet/mobile/cust-service-color.svg" alt="customer service icon" />
                                    </div>
                                </a>&nbsp;
                            </div>
                        </div>
                        </div>
                    </div>
                    <div class="footer-desc">
                        <?php et('ks.landing.page.footer.description'); ?>
                    </div>
                </div>
            </footer>
        </div>


    <?php }
}
