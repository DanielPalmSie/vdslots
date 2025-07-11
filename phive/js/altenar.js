const CULTURE_MAP = {
    en: "en-GB",
    sv: "sv-SE",
    da: "da-DK",
    br: "pt-BR",
    de: "de-DE",
    es: "es-ES",
    fi: "fi-FI",
    it: "it-IT",
    no: "nb-NO",
};

const ODDS_FORMATS = {
    decimal: 0,
    fractional: 1,
    american: 2,
};

const getCulture = (lang) => {
    return CULTURE_MAP[lang] ?? "en-GB";
};

const getOddsFormat = () => {
    if (JURISDICTION === "GB" || cur_country === "GB") {
        return ODDS_FORMATS["fractional"];
    }

    return ODDS_FORMATS["decimal"];
};

const initializeApp = (integration, token) => {
    window.altenarWSDK.init({
        integration,
        token: token ?? undefined,
        culture: getCulture(cur_lang),
        oddsFormat: getOddsFormat(),
    });

    window.ASB = window.altenarWSDK.addSportsBook({
        props: {
            onSignInButtonClick: () => {
                checkJurisdictionPopupOnLogin();
            },
            onRouteChange: ({ page }) => {
                const activePage = page === "live" ? page : "overview";
                updateActiveMenuItems(activePage);
            },
        },
        container: document.getElementById("altenar-container"),
    });
};

/* ------HIGHLIGHTING MENUS------ */
const getActivePageFromHash = () =>
    window.location.hash.includes("#/live") ? "live" : "overview";

const getMenuItems = () => [...document.querySelectorAll("#secondary-nav a")];

const getMobileMenuItems = () => [
    ...document.querySelectorAll("#mobile-left-menu a"),
];

const setActiveMenuItems = (activePage) => {
    const menuItems = getMenuItems();
    const mobileMenuItems = getMobileMenuItems();

    const target = activePage === "live" ? "#/live" : "#/overview";

    const activeItem = menuItems.find(({ href }) => href.includes(target));
    const activeMobileItem = mobileMenuItems.find(({ href }) =>
        href.includes(target)
    );

    // check if there is no live menu present
    if (activePage === "live" && !activeItem) {
        setActiveMenuItems("overview"); // highlight prematch menu as a fallback
        return;
    }

    activeItem?.parentElement.classList.add("active");
    activeMobileItem?.classList.add("sub-menu-active");
};

const clearActiveMenuItems = () => {
    const items = [...getMenuItems(), ...getMobileMenuItems()];

    items.forEach((item) => {
        item.parentElement.classList.remove("active");
        item.classList.remove("sub-menu-active");
    });
};

const updateActiveMenuItems = (activePage = null) => {
    clearActiveMenuItems();

    if (!activePage) {
        activePage = getActivePageFromHash();
    }

    setActiveMenuItems(activePage);
};

document.addEventListener("DOMContentLoaded", () => updateActiveMenuItems());

window.addEventListener("hashchange", () => updateActiveMenuItems());
