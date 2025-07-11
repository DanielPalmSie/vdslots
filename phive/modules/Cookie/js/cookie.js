const Cookie = {

    cookieConsentManagement: 'cookie_consent_management',

    /**
     * Function to display cookie popup on first
     */
    showCookiePopup: function () {
        $('#cookie-banner').show();
    },

    showManageCookiePopup: function () {
        const extraOptions = isMobile() ? {
            width: '100vw',
            height: '100vh',
            containerClass: 'cookie-popup__container cookie-popup__container--mobile'
        } : {
            width: '450px',
            containerClass: 'cookie-popup__container cookie-popup__container--desktop'
        };

        const params = {
            module: 'Cookie',
            file: 'cookie_manage',
            boxid: 'cookie_notification_popup',
            boxtitle: 'cookie.manage',
            extra_css: 'cookie-popup__container',
            closebtn: 'yes'
        };
        extBoxAjax('get_raw_html', 'cookie_notification_popup-manage-box', params, extraOptions);
    },

    /**
     * Displays the toggle container for the cookie popup.
     * Hides the manage button and adjusts the position and height of the cookie container.
     * Note: Assumes the existence of the isMobile() function for mobile detection.
     */
    showToggleContainer: function () {
        const toggleContainer = $('.cookie-popup__toggle-container');
        const cookieManage = $('.cookie-popup__action-manage');

        toggleContainer.css('display', 'block');
        cookieManage.css('display', 'none');

        if (!isMobile()) {
            let targetPosition = 60;
            let cookieContainer = document.querySelector('.cookie-popup__container');

            cookieContainer.style.transition = 'top 0.3s ease-out, max-height 0.3s ease-out';
            cookieContainer.style.top = targetPosition + 'px';
            cookieContainer.style.maxHeight = '70vh';
        }
    },

    /**
     * This function is responsible for rendering the necessary cookies
     * list of necessary cookies
     * -- __cfduid, PHPSESSID, tStatus,afStatus, afContent, flash_last_played, __mmapiwsid, referral_id
     */
    isNecessaryCookiesAccepted: function () {
        let cookieValue = this.getCookieValue();
        return cookieValue !== undefined ? cookieValue.includes('_strict') : false;
    },

    /**
     * This function is responsible for rendering the functional types cookies
     *
     */
    isFunctionalCookiesAccepted: function () {
        let cookieValue = this.getCookieValue();
        return cookieValue !== undefined ? cookieValue.includes('_functionality') : false;
    },

    /**
     * This function is responsible for rendering the analytics types cookies
     *
     */
    isAnalyticsCookiesAccepted: function () {
        let cookieValue = this.getCookieValue();
        return cookieValue !== undefined ? cookieValue.includes('_performance') : false;
    },

    /**
     * This function is responsible for rendering the marketing types cookies
     *
     */
    isMarketingCookiesAccepted: function () {
        let cookieValue = this.getCookieValue();
        return cookieValue !== undefined ? cookieValue.includes('_marketing') : false;
    },

    /**
     * This function is responsible for checking if both analytics or marketing cookies are enabled.
     */
    areThirdPartyCookiesEnable: function () {
        return this.isAnalyticsCookiesAccepted() || this.isMarketingCookiesAccepted();
    },


    /**
     * This function is responsible for closing cookie popup
     *
     */
    closeCookiePopup: function (boxId, cookieValue) {
        sCookieExpiry('cookies_consent_info', cookieValue, 365);
        mboxClose(boxId);
        // window.location.href = "";
    },

    getCookieValue: function () {
        const cookieValue = $.cookie('cookies_consent_info');
        if (cookieValue) {
            return cookieValue;
        }
    },

    /**
     * Checks if consent information is stored in both the cookie and local storage.
     *
     * @returns {boolean} - Returns true if both the cookie and local storage contain consent information, otherwise false.
     */
    isConsentStored: function () {
        let brand = `${brand_name}`;
        let cookieValue = this.getCookieValue();
        let cookieLocalStorageSet = this.getLocalStorage(`${brand}_cookie_consent`);

        if (cookieValue && cookieLocalStorageSet) {
            return true;
        }
        return false;
    },


    /**
     * Check whether is already accepted or not if not display popup
     */
    isCookieAccepted: function () {
         let brand = `${brand_name}`;

        if (this.isConsentStored()) {
            return;
        }

        this.deleteLocalStorage(`${brand}_cookie_consent`);
        this.showCookiePopup();
    },


    /**
     * Represents user consent settings for data storage and processing activities for google tag manager
     * https://support.google.com/tagmanager/answer/10718549#
     * https://developers.google.com/tag-platform/tag-manager/templates/consent-apis?sjid=12038654390652269466-EU
     * https://github.com/googleanalytics/ga4-tutorials/tree/main
     */
     permissions: {
        acceptedTime: new Date().toISOString(),
        settings: {
            ad_storage: 'denied',
            ad_user_data: 'denied',
            ad_personalization: 'denied',
            analytics_storage: 'denied',
            functionality_storage: 'denied',
            personalization_storage: 'denied',
            security_storage: 'denied'
        },
        cookieUpdates: {
            necessaryCookie: true,
            functionalCookie: false,
            analyticsCookie: false,
            marketingCookie: false
        }
    },

    /**
     * Handles the submission of the cookie popup.
     * Checks the status of specific checkboxes and performs
     * corresponding actions based on their values.
     * and create cookie value
     * load GTM
     */
    handleCheckSubmission: function (boxId, cookieUpdate, gtmKey) {
        let cookieValue = `${brand_name}_cookie_consent`;

        // Capture a deep copy of the permissions.settings before any manipulation
        let defaultSetting = $.extend(true, {}, this.permissions.settings);
        gtag('consent', 'default', defaultSetting);

        for (const cookieType in cookieUpdate) {
            if (cookieUpdate.hasOwnProperty(cookieType)) {
                switch (cookieType) {
                    case 'necessaryChecked':
                        if (cookieUpdate[cookieType]) {
                            cookieValue += '_strict';
                            this.permissions.settings.security_storage = 'granted';
                        }
                        break;

                    case 'functionalChecked':
                        if (cookieUpdate[cookieType]) {
                            cookieValue += '_functionality';
                            this.permissions.settings.functionality_storage = 'granted';
                            this.permissions.cookieUpdates.functionalCookie = true;
                        }
                        break;

                    case 'analyticsChecked':
                        if (cookieUpdate[cookieType]) {
                            cookieValue += '_performance';
                            this.permissions.settings.analytics_storage = 'granted';
                            this.permissions.cookieUpdates.analyticsCookie = true;
                        }
                        break;

                    case 'marketingChecked':
                        if (cookieUpdate[cookieType]) {
                            cookieValue += '_marketing';
                            this.permissions.settings.ad_storage = 'granted';
                            this.permissions.settings.ad_user_data = 'granted';
                            this.permissions.settings.ad_personalization = 'granted';
                            this.permissions.settings.personalization_storage = 'granted';
                            this.permissions.cookieUpdates.marketingCookie = true;
                        }
                        break;

                    default:
                        break;
                }
            }
        }
        gtag('consent', 'update', this.permissions.settings);
        if (this.inDataLayer('_ou')) gtag('event', 'origin_pageview');
        this.setLocalStorage(`${brand_name}_cookie_consent`, this.permissions);
        google_key(gtmKey, dataLayer);
        Cookie.closeCookiePopup(boxId, cookieValue.replace(/\s/g, ""));
    },

    /**
     * Checks if a key exists in the dataLayer array
     *
     * @param {string} key
     * @returns {boolean}
     */
    inDataLayer: function (key) {
        if (!dataLayer || !Array.isArray(dataLayer)) dataLayer = window.dataLayer;
        if (!dataLayer || !Array.isArray(dataLayer) || dataLayer.length < 1) return false;
        for (const i in dataLayer) if (dataLayer[i].hasOwnProperty(key)) return true;
        return false;
    },

    /**
     * Sets a key-value pair in the local storage.
     *
     */
    setLocalStorage: function (key, value) {
        window.localStorage.setItem(key, JSON.stringify(value));
    },

    /**
     * Retrieves the value of a key from the local storage.
     *
     */
    getLocalStorage: function (key) {
        var value = window.localStorage.getItem(key);
        return value ? JSON.parse(value) : null;
    },

    /**
     * Deletes a key-value pair from the local storage.
     *
     */
    deleteLocalStorage: function (key) {
        window.localStorage.removeItem(key);
    },

    fixCookiePopupFromFooter: function () {
        // mobile
        let footer_sticky_height = $('#bottom-sticky').height();
        if(!footer_sticky_height) {
            footer_sticky_height = this.elementHeightFromBottom('.games-footer');
        }
        $("#cookie-banner").css({'bottom': footer_sticky_height ?? 0 + 'px'});
    },

     elementHeightFromBottom: function (elementSelector) {
        var element = $(elementSelector);
        if(!element || element.length === 0 || element.is(':hidden')) {
            return 0;
        }
        var windowHeight = $(window).height();
        var elementOffset = element.offset().top;

        return windowHeight - (elementOffset);
     }

};
