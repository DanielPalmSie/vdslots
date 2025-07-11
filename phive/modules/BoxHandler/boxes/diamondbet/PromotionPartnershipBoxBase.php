<?php
require_once __DIR__ . '/../../../../../diamondbet/boxes/DiamondBox.php';


class PromotionPartnershipBoxBase extends DiamondBox
{
    private string $currentUrl;
    private string $brand;
    private ?string $promotTag = null;
    private string $mobile_route = "";
    private array $promotion_enable_jurisdiction;

    /**
     * Predefined marketing categories and their corresponding paths.
     */
    public const URL_CATEGORIES = [
        'wba' => '/wba',
        'shw' => '/shw',
        'bandy' => '/bandy',
        'pdc' => '/pdc',
    ];

    public function __construct()
    {
       parent::__construct();
        $this->currentUrl = $_SERVER['REQUEST_URI'];
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->promotTag = $this->determineUrlCategory();
        $this->init();
    }

    function init()
    {
        $should_add_mobile_routing = empty(phive('Pager')->getSetting('device_dir_mapping'));
        if ($should_add_mobile_routing && phive()->isMobile()) {
            $this->mobile_route = "/mobile";
        }
        $promotions = phive('MailHandler2')->getSetting('seasonal_promotions_partner');
        $this->promotion_enable_jurisdiction = !empty($promotions['ENABLE_JURISDICTION']) ? $promotions['ENABLE_JURISDICTION'] : [];
    }


    function printCSS()
    {
        loadCss("/diamondbet/css/" . brandedCss() . "promotion-partner.css");
        loadCss("/diamondbet/css/" . brandedCss() . "new-registration.css");
    }


    /**
     * Check if the 'editcontent' query string is passed or if the user is privileged.
     *
     * @return bool Returns true if the 'editcontent' query string is passed or if the user is privileged, false otherwise.
     */
    public function isAdminLog(): bool
    {
        return isset($_GET['editcontent']) || privileged();
    }

    /**
     * Determines the category of the current URL based on predefined paths.
     *
     * Iterates through the URL categories and checks if the current URL
     * contains any of the category-specific substrings.
     *
     * @return string|null The category name if a match is found, otherwise null.
     */
    public function determineUrlCategory(): ?string
    {
        foreach (self::URL_CATEGORIES as $category => $path) {
            if (strpos($this->currentUrl, $path) !== false) {
                return $category;
            }
        }
        return null;
    }


    /**
     * @param string $type
     * @param string $href
     * @param string $label
     * @return string
     */
    public static function returnLink(string $type, string $href, string $label): string
    {
        $llink = llink((isMobileSite() ? '/mobile' : '') . $type);
        $action = 'onclick="window.open(\' ' . $llink . ' \',\'sense\',\'width=740,scrollbars=yes,resizable=yes\');"';

        return "<a href='{$href}' {$action}>{$label}</a>";
    }

    /**
     * Determine whether the promo should be shown based on specified conditions.
     *
     * @param string $jurisdiction The jurisdiction of the user.
     *
     * @return bool Returns true if the promo should be shown, false otherwise.
     */
    public function shouldShowPromo(string $jurisdiction): bool
    {
        $is_promo_enabled = phive("Config")->getValue('enable-promo-page', 'show-promotion-page') === 'yes';
        return $jurisdiction === licJur() && $is_promo_enabled || $this->isAdminLog();
    }

    /**
     * Print the main content displayed on the box.
     */
    public function printHTML()
    {
        $this->promotTag ??= $this->determineUrlCategory();

        if ($this->promotTag === null) {
            $this->notAvailableHtml();
            return;
        }

        $validJurisdictions = $this->promotion_enable_jurisdiction;
        $showPromo = false;

        foreach ($validJurisdictions as $jurisdiction) {
            if ($this->shouldShowPromo($jurisdiction)) {
                $showPromo = true;
                break;
            }
        }

        if (!$showPromo) {
            $this->notAvailableHtml();
            return;
        }

        switch ($this->promotTag) {
            case 'bandy':
                $this->printBandyPromotion();
                break;
            default:
                $this->printPromotion();
                break;
        }
    }

    public function printPromotion()
    {
        ?>
        <div class="promotion-partner__container">
            <?php
            $this->printPromotionPartnerImages();
            ?>
            <?php et("$this->promotTag.seasonal.promotion.content.main.html"); ?>

            <?php
            if ($this->promotTag && strpos($this->currentUrl, '/' . $this->promotTag . '2') !== false) {
                $this->PromotionInfo2();
            } else {
                $this->PromotionInfo();
            }
            ?>
            <div class="promotion-partner__container-term-condition">
                <hr class="promotion-partner__custom-line"/>
                <?php et($this->promotTag . '.seasonal.promotion.term.condition.html'); ?>
            </div>
        </div>
        <?php
    }

    public function printPromotionPartnerImages()
    {
        if (empty($this->brand)) {
            $this->brand = phive('BrandedConfig')->getBrand();
        }

        $backgroundImage = sprintf(
            '%s-season-ticket-banner%s-%s.png',
            $this->promotTag,
            phive()->isMobile() ? '-mobile' : '',
            $this->brand
        );

        $altText = pathinfo($backgroundImage, PATHINFO_FILENAME);
        ?>
        <div class="promotion-partner__image-container">
            <img src="<?= fupUri($backgroundImage, true, '') ?>"
                 alt="<?= htmlspecialchars($altText, ENT_QUOTES, 'UTF-8') ?>" />
        </div>
        <?php
    }

    public function promotionInfo2()
    {
        ?>
        <div class="promotion-partner__button-container">
            <?php  $this->printButton();?>
        </div>
        <div class="promotion-partner__contact-details promotion-partner__participate-check-info">
            <label for="conditions" class="promotion-partner__checkbox-label">
                <input id="participate_check" name="participate_check" type="checkbox"/>
                <span><?= t($this->promotTag . '.seasonal.winner.prize.agree'); ?></span>
            </label>
        </div>
        <?php
    }

    public function checkPromotionPrivacy()
    {
        ?>
        <div class="promotion-partner__input-container privacy-condition">
            <p>
                <label for="privacy" class="promotion-partner__checkbox-label">
                    <input id="privacy" name="privacy_check" type="checkbox"/>
                    <span
                        class="checkbox-text--privacy"> <?= et2('seasonal.privacy.agree.personal.data.marketing', "$this->brand"); ?>
                        <?= self::returnLink('/privacy-policy/', "$this->currentUrl", t('privacy-policy')); ?> </span>
                </label>
            </p>
            <p>
                <label for="conditions" class="promotion-partner__checkbox-label">
                    <input id="conditions" name="conditions_check" type="checkbox"/>
                    <span class="checkbox-text--condition"><?= t('seasonal.conform.18.years'); ?></span>
                </label>
            </p>
        </div>
        <?php
    }

    public function promotionInfo()
    {
        ?>
        <div class="promotion-partner__contact-details">
            <div class="promotion-partner__contact-details--section">
                <?php et("seasonal.promotion.content.contact.html"); ?>
                <form id="promotion-form">
                    <?php $this->promotionContactForm(); ?>
                    <?php $this->checkPromotionPrivacy(); ?>

                        <?php  $this->printButton();?>

                    <p id="promotion-success-message"></p>
                </form>
            </div>
        </div>
        <?php
    }

    public function promotionContactForm()
    {
        $prefix = phive('Cashier')->phoneFromIso(licJur()) ?? '';
        $calling_codes = phive('DBUserHandler')->getCallingCodesForDropdown();
        ?>
        <div class="promotion-partner__input-container email-input-container">
            <label for="email">
                <input id="email_input"
                       class="promotion-partner__custom-input custom-input__email"
                       name="email"
                       type="email"
                       autocapitalize="off"
                       autocorrect="off"
                       autocomplete="email"
                       placeholder='<?= htmlspecialchars(t('register.email.nostar')); ?>'
                       required
                       pattern="[^\s@]+@[^\s@]+\.[^\s@]+"/>
            </label>
        </div>

        <div class="promotion-partner__input-container mobile-input-container">
            <label for="phone">
                <span id="mobile-prefix-select">
                    <?php dbSelect('country_prefix', $calling_codes, $prefix, [], 'promotion-partner__custom-input custom-input__country',); ?>
                </span>
                <input id="mobile_input"
                       class="promotion-partner__custom-input custom-input__phone"
                       name="mobile"
                       type="tel"
                       autocomplete="tel"
                       placeholder="<?= t('user-details.phone-placeholder') ?>"
                       required
                       pattern="\d*"/>
            </label>
        </div>
        <?php
    }

    public function redirectUrl() {
        $seasonalRefMapping = phive('Redirect')->getSetting('seasonal_promotion_referral_url');
        $tag = $this->promotTag;
        return $seasonalRefMapping[$tag];
    }


    public function submitFormHandler()
    {
        $device_type = phive()->deviceType();
        $device = $device_type;
        $tag = $this->promotTag;
        $redirect = $this->redirectUrl();
        ?>
        <script>
            function validateCheckboxes() {
                var privacyChecked = $('#privacy').prop('checked');
                var conditionsChecked = $('#conditions').prop('checked');


                if (!privacyChecked) {
                    $('.checkbox-text--privacy').css('color', 'red');
                } else {
                    $('.checkbox-text--privacy').css('color', '');
                }

                if (!conditionsChecked) {
                    $('.checkbox-text--condition').css('color', 'red');
                } else {
                    $('.checkbox-text--condition').css('color', '');
                }

                return privacyChecked && conditionsChecked;
            }

            function handleFormSubmission() {
                if (!validateCheckboxes()) {
                    return;
                }

                const userDetails = {
                    email: $('#email_input').val().toLowerCase(),
                    country_prefix: $('#country_prefix').val().replace(/\D/g, ''),
                    mobile: $('#mobile_input').val(),
                    privacy: $('#privacy').val() ? 'true' : 'false',
                    age: $('#conditions').val() ? 'true' : 'false',
                };

                const toupdate = {
                    tag: <?= json_encode($tag) ?>,
                    page: <?= json_encode($device) ?>,
                    email: userDetails.email,
                    country_prefix: userDetails.country_prefix,
                    mobile: userDetails.mobile,
                    privacy: userDetails.privacy,
                    age: userDetails.age,
                };

                $.ajax({
                    type: 'POST',
                    url: '/phive/modules/Micro/ajax.php',
                    data: {action: 'save-seasonal-promotion-info', saveSeasonalPromotion: JSON.stringify(toupdate)},
                    dataType: 'json',
                })
                .done(function (response) {
                    const successMessage = $('#promotion-success-message');
                    successMessage.show();

                    if (response?.status == 'emailExit') {
                        //  'Email is already Exit on Seasonal promotion',
                        successMessage.text("<?= t('seasonal.promotion.already-participated') ?>").css('color', 'red');
                    }
                    if (response?.status == 'success') {
                        $('#email_input').val('');
                        $('#mobile_input').val('');
                        $('#privacy').prop('checked', false);
                        $('#conditions').prop('checked', false);
                        successMessage.text("<?= t('seasonal.promotion.form.submitted') ?>");
                    }
                    setTimeout(function () {
                        successMessage.text('');
                        successMessage.css('color', '');
                        successMessage.hide();
                    }, 5000);
                })
                .fail(function (error) {
                })
            }

            // bandy form submit
            function handleFormSubmissionBandy() {
                if (!validateCheckboxes()) {
                    return;
                }

                const userDetails = {
                    email: $('#email_input').val().toLowerCase(),
                    privacy: $('#privacy').val() ? 'true' : 'false',
                    age: $('#conditions').val() ? 'true' : 'false',
                };

                const toupdate = {
                    tag: <?= json_encode($tag) ?>,
                    page: <?= json_encode($device) ?>,
                    bandySelection: $('#bandy_form-selection').val(),
                    email: userDetails.email,
                    privacy: userDetails.privacy,
                    age: userDetails.age,
                };

                $.ajax({
                    type: 'POST',
                    url: '/phive/modules/Micro/ajax.php',
                    data: {action: 'save-seasonal-promotion-info', saveSeasonalPromotion: JSON.stringify(toupdate)},
                    dataType: 'json',
                })
                    .done(function (response) {
                        const successMessage = $('#promotion-success-message');
                        successMessage.show();

                        if (response?.status == 'emailExit') {
                            //  'Email is already Exit on Seasonal promotion',
                            successMessage.text("<?= t('seasonal.promotion.already-participated') ?>").css('color', 'red');
                        }
                        if (response?.status == 'success') {
                            $('#email_input').val('');
                            $('#privacy').prop('checked', false);
                            $('#conditions').prop('checked', false);
                            $('#bandy_form-selection').prop('selectedIndex', 0);
                            successMessage.text("<?= t('seasonal.promotion.form.submitted') ?>");
                            window.location.href = '<?= $redirect ?>';
                        }
                        setTimeout(function () {
                            successMessage.text('');
                            successMessage.css('color', '');
                            successMessage.hide();
                        }, 5000);
                    })
                    .fail(function (error) {
                    })
            }

            $(document).ready(function () {
                $('#promotion-form').submit(function (e) {
                    e.preventDefault();
                    handleFormSubmission();
                });

                $('#promotion-form-bandy').submit(function (e) {
                    e.preventDefault();
                    handleFormSubmissionBandy();
                });

                //  Add event listener to checkboxes for real-time validation
                $('#privacy, #conditions').on('click', function () {
                    validateCheckboxes();
                });
            });
        </script>
        <?php
    }

    public function registerToParticipateInPromo() {
        $redirect = $this->redirectUrl();
        ?>
        <script>
            $(document).ready(function () {
                $('#participate_check').on('click', function (e) {
                    participateValidate();
                });

                function participateValidate() {
                    var participateChecked = $('#participate_check').prop('checked');

                    if (!participateChecked) {
                        $('.promotion-partner__participate-check-info').css('color', 'red');
                    } else {
                        $('.promotion-partner__participate-check-info').css('color', '');
                    }

                    return participateChecked;
                }

                $('.promotion-partner__button').off().on('click', function () {
                    if (participateValidate()) {
                        mgAjax({
                            action: 'save-seasonal-promotion-session',
                            tag: JSON.stringify(<?= json_encode($tag) ?>)
                        });
                        window.location.href = '<?= $redirect ?>';
                    }
                });
            });
        </script>
        <?php
    }

    public function setButtonAction(): array
    {
        $redirect = $this->submitFormHandler();
        $alias = t('submit');

        if (strpos($this->currentUrl, '/' . $this->promotTag . '2') !== false) {
            $aff_reg_url = $this->registerToParticipateInPromo();
            $redirect = "top.goTo('$aff_reg_url')";
            $alias = t('register.to.participate');
        }

        return [
            'redirect' => $redirect,
            'alias' => $alias,
        ];
    }

    public function printButton(string $customClass = 'promotion-partner__button'): void
    {
        $buttonAttributes = $this->setButtonAction();
        $redirect = $buttonAttributes['redirect'];
        $alias = $buttonAttributes['alias'];
        $class = $customClass;
        ?>
        <div class="promotion-partner__button-container">
            <?php btnDefaultL("$alias", '', "$redirect", "", $class); ?>
        </div>
        <?php
    }

    public function notAvailableHtml()
    {
        ?>
        <div class="frame-block">
            <div class="frame-holder">
                <h1><?php et('404.header') ?></h1>
                <?php et("404.content.html") ?>
            </div>
        </div>
        <?php
    }

    //bandy Promotion
    public function printBandyPromotion()
    {
        if ($this->promotTag === 'bandy') {
            ?>
            <div class="promotion-partner__container">
                <div class="promotion-partner__image-container-bandy promotion-partner__full-image-first">
                    <img src="/file_uploads/<?php echo $this->promotTag; ?>-promotion-banner-first.png"
                         alt="<?php echo $this->promotTag; ?>-promotion-banner-first"/>
                </div>

                <div class="promotion-partner__image-container-bandy promotion-partner__full-image-second">
                    <img src="/file_uploads/<?php echo $this->promotTag; ?>-promotion-banner-second.png"
                         alt="<?php echo $this->promotTag; ?>-promotion-banner-second"/>
                </div>

                <div class="promotion-partner__content-bandy">
                    <h1 class="promotion-partner__heading-bandy"><?php et("seasonal.promotion.content.main.bandy"); ?></h1>
                    <form id="promotion-form-bandy">
                        <div class="promotion-partner__form-bandy">
                            <label for="email_input"></label>
                            <input id="email_input"
                                   class="promotion-partner__form-input-bandy"
                                   name="email"
                                   type="email"
                                   autocapitalize="off"
                                   autocorrect="off"
                                   autocomplete="email"
                                   placeholder='<?= htmlspecialchars(t('register.email.nostar')); ?>'
                                   required
                                   pattern="[^\s@]+@[^\s@]+\.[^\s@]+"/>
                            <div class="promotion-partner__select-wrapper">
                            <label for="bandy_form-selection"></label>
                            <select id="bandy_form-selection" class="promotion-partner__form-select-bandy" required>
                                <option value="" disabled selected><?php et("bandy.goals"); ?></option>
                                <option value="6-or-less">≤6 <?php et("goals"); ?></option>
                                <option value="7">7 <?php et("goals"); ?></option>
                                <option value="8">8 <?php et("goals"); ?></option>
                                <option value="9">9 <?php et("goals"); ?></option>
                                <option value="10">10 <?php et("goals"); ?></option>
                                <option value="11">11 <?php et("goals"); ?></option>
                                <option value="12-or-more"> ≥12 <?php et("goals"); ?></option>
                            </select>
                            </div>
                            <?php $this->printButton('promotion-partner__submit-button-bandy'); ?>
                        </div>
                        <?php $this->checkPromotionPrivacy(); ?>
                        <p id="promotion-success-message"></p>
                    </form>
                </div>
            </div>

            <div class="promotion-partner__container-term-condition-bandy">
                <hr class="promotion-partner__custom-line"/>
                <?php et("seasonal.promotion.term.condition.bandy.html"); ?>
            </div>
            <?php
        }
    }
}
