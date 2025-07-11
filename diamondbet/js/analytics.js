 /**
   * Initialize consent with default permissions.
   * This is used when there is no existing consent stored.
 */
function initializeConsent() {
    let defaultPermissions = Cookie.permissions.settings;
    gtag('consent', 'default', defaultPermissions);
}

  /**
  * Update consent based on stored cookie permissions.
  * This is used when consent is already stored in localStorage.
  */
function updateConsent() {
    var permissionLocalStorageSet = Cookie.getLocalStorage(`${brand_name}_cookie_consent`);
    if (permissionLocalStorageSet !== null) {
        let updatePermissions = permissionLocalStorageSet.settings;
        gtag('consent', 'update', updatePermissions);
    }
}

function google_key( key, dataLayer ) {
  (function ( w, d, s, l, i ) {
      var f = d.getElementsByTagName( s )[ 0 ],
        j = d.createElement( s ), dl = l != 'dataLayer' ? '&l=' + l : '';
    j.async = true;
      w[ l ] = w[ l ] || [];
      w[ l ].push( {
          'gtm.start' : new Date().getTime(), event : 'gtm.js'
      } );
    j.src =
      '//www.googletagmanager.com/gtm.js?id=' + i + dl;
    f.parentNode.insertBefore( j, f );
  })( window, document, 'script', 'dataLayer', key );
}
