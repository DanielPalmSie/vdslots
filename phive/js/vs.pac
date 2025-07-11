/**
 * Proxy domains by regex
 * 
 * Chrome install
 * Install 'Proxy Helper' using a chrome browser by going to https://chrome.google.com/webstore/detail/proxy-helper/mnloefcpaepkpmhaoipjkpikbnkmbnic
 * After install right click the blue icon, on the right of the browser url, and open options.
 * At: PAC PATH: http:// <domainname-that-hosts-pac-file>/vs.pac
 * 
 * firefox install
 * Install 'foxyproxy basic' using a firefox browser by going to https://addons.mozilla.org/en-US/firefox/addon/foxyproxy-basic/
 * After install open the options and select the Default proxy and click 'Edit Selection'. Select tab 'Proxy Details' and select Automatic Proxy Configuration by PAC' 
 * Enter the url: http://<domainname-that-hosts-pac-file>/vs.pac and click OK
 * @link http://findproxyforurl.com/pac-functions/
 * @link http://findproxyforurl.com/example-pac-file/
 */
function FindProxyForURL(url, host){
    if(shExpMatch(host, "test2.videoslots.com")) {
    //if (shExpMatch(url, "https:**")) {
        // Do proxy
        //return "PROXY 217.174.248.203:8080";
        //return "PROXY 1.1.1.65:80";
        // http://dev.chromium.org/developers/design-documents/secure-web-proxy
        return "HTTPS 88.208.221.127:8080";
    }
}
