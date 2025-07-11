<?php
require_once __DIR__ . '/../../../../../diamondbet/boxes/DiamondBox.php';

/**
 * CarouselBannerRotationBoxBase
 *
 * Displays a rotating carousel of partner logos.
 */
class CarouselBannerRotationBoxBase extends DiamondBox
{
    private string $brand;

    /** @var array $links */
    public $links;

    public function init(): void
    {

        $fields = [
            'enable_carousel',
            'background_image',
            'partners',
            'excluded_countries',
        ];
        $defaults = [
            'enable_carousel' => 0,
            'background_image' => '',
            'partners' => 7,
            'excluded_countries' => '',
        ];

        $currentPartners = $this->getAttribute('partners') ?: $this->partners;


        for ($i = 1; $i <= $currentPartners; $i++) {
            $fields[] = "partner_logo_$i";
            $defaults["partner_link_$i"] = '';
            $fields[] = "partner_link_$i";
            $defaults["partner_logo_$i"] = '';
        }

        $this->handlePost($fields, $defaults);

        if (isset($_POST['save_settings']) && $_POST['box_id'] == $this->getId()) {
            for ($i = 1; $i <= $this->partners; $i++) {
                $this->setAttribute("partner_logo_$i", trim($_POST["partner_logo_$i"]));
                $this->setAttribute("partner_link_$i", trim($_POST["partner_link_$i"]));
            }
        }

        $this->brand = phive('BrandedConfig')->getBrand();
    }

    function printCSS()
    {
        loadCss("/diamondbet/css/" . brandedCss() . "sponsorship.css");
    }

    /**
     * Hide the carousel if the enable_carousel is 0 or the user is in an excluded country
     *
     * @return bool
     */
    public function hideCarousel()
    {
        if ($this->enable_carousel == 0) {
            return true;
        }
        if (in_array(licJur(), explode(' ', $this->excluded_countries))) {
            return true;
        }
        return false;
    }

    /*
     * Print the HTML for the carousel
     *
     * @return void
     */
    public function printHTML(): void
    {
        if ($this->hideCarousel() === true) {
            return;
        }
        $partners = [];
        for ($i = 1; $i <= $this->partners; $i++) {
            $logo = $this->{"partner_logo_$i"};
            $link = $this->{"partner_link_$i"};
            if (!empty($logo)) {
                $partners[] = [
                    'img' => $logo,
                    'alt' => $logo,
                    'href' => $link
                ];
            }
        }
        $this->printJS();
        ?>

        <div class="frame-block fb-background carousel-box">
            <div class="carousel-container"
                 style="background-image: url(<?= fupUri($this->background_image, true, '') ?>);">
                <div class="carousel-inner">
                    <div class="carousel-heading"></div>
                    <div class="carousel-viewport">
                        <div class="carousel-logos-container">
                            <?php foreach ($partners as $partner): ?>
                                <?php if (!empty($partner['href'])): ?>
                                    <a href="<?= htmlspecialchars($partner['href']) ?>" target="_blank" rel="noopener noreferrer">
                                        <div class="carousel-logo-container">
                                            <img src="<?= fupUri($partner['img'], true, 'no_pic.jpg') ?>" alt="<?= htmlspecialchars($partner['alt']) ?>" class="carousel-logo" loading="lazy" />
                                        </div>
                                    </a>
                                <?php else: ?>
                                    <div class="carousel-logo-container">
                                        <img src="<?= fupUri($partner['img'], true, 'no_pic.jpg') ?>" alt="<?= htmlspecialchars($partner['alt']) ?>" class="carousel-logo not-clickable" loading="lazy" />
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php
    }

    /*
     * Print the JS for the carousel
     *
     * @return void
     */
    function printJS(){ ?>
<script type="text/javascript">
$(document).ready(function () {
    const $logos = $('.carousel-box .carousel-logos-container');
    const $imgs = $logos.find('.carousel-logo');
    let logoWidth = 0;
    let animId = null;
    let speed = <?= (int)$this->partners ?> > 4 ? 0.3 : 0.0;
    let interactionOngoing = false;
    let resizeTimeout;

    function recalc() {
        logoWidth = $logos.find('.carousel-logo-container').outerWidth(true) || 0;
    }

    function getViewportRect() {
        const $viewport = $('.carousel-viewport');
        const $inner = $('.carousel-inner');

        // On mobile, use carousel-inner as viewport, on desktop use carousel-viewport
        const isMobileDevices = $(window).width() <= 768;
        const $targetContainer = isMobileDevices ? $inner : $viewport;

        return $targetContainer.length ? $targetContainer[0].getBoundingClientRect() : {left: 0, right: $(window).width()};
    }

    function updateLogoVisibility() {

        const viewport = getViewportRect();
        const isMobileDevices = $(window).width() <= 768;

        $('.carousel-logo-container').each(function() {
            const $container = $(this);
            const $img = $container.find('.carousel-logo');
            const rect = this.getBoundingClientRect();

            let targetOpacity = 1;
            let isFullyInside = false;

            if (!isMobileDevices) {
                isFullyInside = rect.left >= viewport.left && rect.right <= viewport.right;
                const isComingFromRight = rect.left >= viewport.left && rect.left <= viewport.right && rect.right > viewport.right;
                targetOpacity = (isFullyInside || isComingFromRight) ? 1 : 0.05;
            }
            $img.css('opacity', targetOpacity);
       });
    }


    function loop() {
        let current = parseFloat($logos.data('x')) || 0;
        current -= speed;

        const loopThreshold = logoWidth * 0.75; // 75% of logo width instead of 100%
        
        if (Math.abs(current) >= loopThreshold) {
            $logos.append($logos.find('.carousel-logo-container').first());
            current += logoWidth;
        }

        $logos.css('transform', `translate3d(${current}px, 0, 0)`);
        $logos.data('x', current);

        updateLogoVisibility();

        animId = requestAnimationFrame(loop);
    }

    function start() {
        if (!animId) animId = requestAnimationFrame(loop);
    }

    function stop() {
        if (animId) {
            cancelAnimationFrame(animId);
            animId = null;
        }
    }

    function imagesLoaded($imgs, callback) {
        let loaded = 0;
        const total = $imgs.length;
        if (!total) return callback();

        $imgs.each(function() {
            if (this.complete) {
                if (++loaded === total) callback();
            } else {
                $(this).one('load error', function() {
                    if (++loaded === total) callback();
                });
            }
        });
    }

    imagesLoaded($imgs, function() {
        recalc();
        $logos.data('x', 0);
        start();
        updateLogoVisibility();
    });

    $(window).on('scroll resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            recalc();
            updateLogoVisibility();
        }, 200);
    });

    $logos.on('mouseenter touchstart', function() {
        if (!interactionOngoing) {
            interactionOngoing = true;
            stop();
        }
    }).on('mouseleave touchend', function() {
        interactionOngoing = false;
        start();
    });
});
</script>

    <?php }
    /*
     * Print the extra settings for the carousel
     *
     * @return void
     */
    function printExtra()
    { ?>
    <p>
        <label><strong>Enable Carousel:</strong></label>
        <?php dbInput("enable_carousel", $this->enable_carousel); ?>
    </p>
    <p>
        <label><strong>Background Image:</strong></label>
        <?php dbInput("background_image", $this->background_image); ?>
    </p>
    <p>
        <label><strong>Total Partners:</strong></label>
        <?php dbInput("partners", $this->partners); ?>
    </p>
    <p>
        <label><strong>Excluded Countries:</strong></label>
        <?php dbInput("excluded_countries", $this->excluded_countries); ?>
    </p>
    <?php
    for ($i = 1; $i <= $this->partners; $i++) { ?>
        <p>
            <label><strong> Partner <?= $i ?> Logo: </strong></label>
            <?php dbInput("partner_logo_$i", $this->{"partner_logo_$i"}); ?>
            <br/>
            <br/>
            <label><strong> Partner <?= $i ?> Redirect Link: </strong></label>
            <?php dbInput("partner_link_$i", $this->{"partner_link_$i"}); ?>
        </p>
    <?php }
    }

}
