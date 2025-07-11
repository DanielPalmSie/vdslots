<?php
require_once __DIR__ . '/../../api/ExtModule.php';

class Redirect extends ExtModule{
  function __construct(){
    $this->table = 'redirects';
    parent::__construct();
  }

    /**
     * @todo: Use Http/HTTPReq to read request parameters introduced in RHEA-2816
     *
     * TODO update the wiki related to this. http://wiki.videoslots.com/index.php?title=TrackingPixels_How_To_Implement
     * Brief overview on how we handle tracking for third party companies (referral_id  / referral_postback_id)
     *
     * - referral_id: this params is set and used as bonus_code during the registration, it usually comes from affiliate team generated URL.
     * - referral_postback_id: this is an optional parameter that exist only for some affiliates and it triggers a callback to them for a more precise tracking.
     *
     *
     * Ex. this is a link created by partnerroom (affiliate team)
     *
     * http://affiliates.videoslots.loc/click/?aid=_AFFILIATE_ID_&turl=http%3A%2F%2Fwww.videoslots.loc%2F%3Freferral_id%3D_CAMPAIGN_ID_&bid=no-banner&label=_LABEL_&dynamic={dynamic}
     * _AFFILIATE_ID_, _CAMPAIGN_ID_, _LABEL_ are replaced inside partnerroom when creating a link
     * dynamic={dynamic} is optional and {dynamic} is replaced by an affiliate / tracking company with a parameter that we need to send them back when some event (Ex. registration, deposit) happens
     * we are saving that parameter in a cookie as "referral_postback_id" before the registration, and then as a user_settings "affiliate_postback_id" when the user is registered.
     *
     *
     * DEMO LINK TO GET referral_postback_id parameters inside affiliate URL from external companies
     *
     * - TOPTRAFFIC:
     *  code generator - https://platform.ontvip.com/click?ci=128&ai=1958014&fc=true
     *  affiliate URL + generated code - http://affiliates.videoslots.loc/click/?aid=videoslots-milesvideoslots001&turl=http%3A%2F%2Fwww.videoslots.loc%2F%3Freferral_id%3Dsignupa&bid=no-banner&label=signupa&dynamic=27dcf9450dd4c144f1a1fd810b13235f
     *  redirect to videoslots - http://www.videoslots.loc/?referral_id=signupa&referral_postback_id=wIS2DHILOGE0GMSF119K9K20
     *
     * -BRAMEDIA:
     *  code generator - http://track.chooseviral.com/930b7058-667b-4740-8c9a-d4a643b4766e?source=test&adid=test
     *  affiliate URL + generated code - http://affiliates.videoslots.loc/click/?aid=videoslots-milesvideoslots001&turl=http%3A%2F%2Fwww.videoslots.loc%2F%3Freferral_id%3Dsignupa&bid=no-banner&label=signupa&dynamic=wIS2DHILOGE0GMSF119K9K20
     *  redirect to videoslots - http://www.videoslots.loc/?referral_id=signupa&referral_postback_id=wIS2DHILOGE0GMSF119K9K20
     */
    function handleSignupReferal(){
        // Before performing any redirect, capture the origin URL and
        // referrer so that is can be properly tracked
        phive('Http/HTTPReq')->trackOriginURL();

        $bonus_code = $_GET['referral_id'] ?? null;
        $to_ref = false;

        if (hasMp() && ((isset($_REQUEST['eid']) && empty($_REQUEST['eid'])) ||
                (isset($_REQUEST['tid']) && empty($_REQUEST['tid'])))
        ) {
            phive('Logger')
                ->getLogger('bos_logs')
                ->error("User is on a BOS page but is missing eid & tid data", [
                    'request'   => $_REQUEST,
                    'cookies'   => $_COOKIE,
                    'post'      => $_POST,
                    'page'      => phive('Http/HTTPReq')->getCurrentURL(),
                    'user_id'   => cu() ? cu()->getId() : null,
                    'referrer'  => phive('Http/HTTPReq')->getReferrerURL()
                ]);
        }

        // We don't want to do anything further when it comes to bad behaving affiliates, we do not want to redirect players
        // to potential external tracking systems or tag players to any affiliate.
        if(!empty($bonus_code)){
            $escaped = phive('SQL')->escape($bonus_code, true);
            $blocked = phive('SQL')->loadAssoc("SELECT * FROM blocked_bonus_codes WHERE bcode = $escaped");
            if(!empty($blocked)){
                $this->to('/important-information/');
            }
        }

        if($this->getSetting('uses_external_affiliate_system') === true){
            if(!empty($bonus_code)){
                // We want to redirect a legacy referral to the partner we've outsourced affiliation to.
                $url = sprintf($this->getSetting('to_external_referral_url'), $bonus_code);
                $this->toExt($url, 307);
            }
        }

        if(empty($bonus_code)){
            // We might have an incoming referral from the external partner, if that is the case we use the external ID,
            // for instance the btag from raventrack which can look like this: a_15b_c_d_525295958.
            // It will be used when they fetch sales and registrations later, we just store it in the bonus code column.
            $site_name = $_GET['sitename'] ?? null;
            $bonus_code = $_GET[ $this->getSetting('from_external_referral_key') ] ?? null;
            $bonus_code_with_site_name = empty($site_name) ? $bonus_code :$bonus_code . '|' . $site_name;
            $bonus_code = $bonus_code_with_site_name;
        }

        // We have a get but no cookie is set, or the get is different from the cookie so we set the cookie with 30 days expiry.
        // Last referral gets the player.
        if(($_COOKIE['referral_id'] != $bonus_code) && !empty($bonus_code)){
            setCookieSecure('referral_id', $bonus_code);
            $ref_id = $bonus_code;

            // extra code for the tracking companies with postback methods
            if(!empty($_GET['referral_postback_id'])) {
                setCookieSecure('referral_postback_id', $_GET['referral_postback_id']); // define this on cookie policy (rare case just for some affiliate)
                $postback_id = $_GET['referral_postback_id'];
            }
        } else if(!empty($_COOKIE['referral_id'])) { // We don't have a get or the get is the same as the cookie so we use the cookie.
            $ref_id = $_COOKIE['referral_id'];

            // needed to update "referral_postback_id" if a user click banner link multiple times (only the first one was stored before).
            if(($_COOKIE['referral_postback_id'] != $_GET['referral_postback_id']) && !empty($_GET['referral_postback_id'])){
                setCookieSecure('referral_postback_id', $_GET['referral_postback_id']);
                $postback_id = $_GET['referral_postback_id'];
            } else {
                // extra code for the tracking companies with postback methods
                $postback_id = $_COOKIE['referral_postback_id'];
            }
        }

        if(!empty($ref_id)) {
            $_SESSION['affiliate'] = $ref_id;
            // extra code for the tracking companies with postback methods
            if(!empty($postback_id)) {
                $_SESSION['affiliate_postback_id'] = $postback_id;
            }
        }

        // We have an inital GET so we redirect to improve the Google juice.
        if(!empty($bonus_code)){
            $to_ref = true;
        }

        if(!empty($_GET['signup'])){
            header("Cache-Control: no-store, no-cache, must-revalidate");
            $_SESSION['show_signup'] = true;
            $to_ref                  = true;
        }

        if($to_ref){
            $this->toBaseRef();
        }
    }

    /**
     * @param string $string
     * @param bool $language_redirect To ignore language redirect
     * @return bool
     */
    public function isBot($string = '', $language_redirect = false)
    {
        $string = empty($string) ? strtolower($_SERVER['HTTP_USER_AGENT']) : $string;

        if ($language_redirect === true) {
            $ips = phive('IpBlock')->getSetting('bots_no_language_redirect_ips', []);
            if (!empty($ips) && in_array(remIp(), $ips)) {
                return true;
            }
            $bots = phive('IpBlock')->getSetting('bots_no_language_redirect' ,['SeobilityBot']);
        } else {
            $bots = phive('IpBlock')->getSetting('bots_list' ,['googlebot', 'msnbot', 'bingbot', 'yahoo', 'ia_archiver']);
        }

        foreach ($bots as $b) {
            if (strpos($string, $b) !== false) {
                return true;
            }
        }
        return false;
    }

  function redirectToExtByCountry($map){
    if($this->isBot())
      return;
    $res 	= phive('IpBlock')->getCountry();
    $to 	= $map[$res];
    if(!empty($to))
      $this->toExt($to);
  }

  function table(){ return $this->table; }

  function toExt($ext_url){
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: $ext_url");
    header("Connection: close");
    exit;
  }

    /**
     * @todo: Use the URL module introduced in RHEA-2816 to perform URL modifications
     * @return void
     */
    function toBaseRef(){
        list($url, $params) = explode('?', $_SERVER['REQUEST_URI']);
        $append_query_string = [];
        // Google Click ID needs to propagate no matter what.
        $append_query_string = array_merge($append_query_string, $this->getGoogleClickId());
        // Google UTM parameters needs to propagate no matter what (utm_source, utm_medium, utm_campaign, utm_id, utm_term, utm_content)
        $append_query_string = array_merge($append_query_string, $this->getUtmParameters());
        // Preserve ad parameter
        $append_query_string = array_merge($append_query_string, $this->getAdParameters());

        if (!empty($append_query_string)) {
            $url .= '?' . http_build_query($append_query_string);
        }

        // If on a mobile device redirect to the mobile version of the page
        /** @var URL $p_url */
        $p_url = phive('Http/URL');
        $url = $p_url->prependMobileDirPart($url);

        header("HTTP/1.1 301 Moved Permanently");
        header("Location: $url");
        header("Connection: close");
        exit;
    }

    /**
     * @todo: Use the URL module introduced in RHEA-2816 to perform URL modifications
     * @deprecated
     *
     * @return array
     */
    function getGoogleClickId() {
        // @todo: We have the module used for UTM tracking to fetch this
        $gclid = phive('RavenTrack')->getGoogleClickId();
        $append_query_string = [];
        if(!empty($gclid)) {
            $append_query_string['gclid'] = $gclid;
        }
        return $append_query_string;
    }

    /**
     * Get affiliate ad parameters
     * @return array
     */
    function getAdParameters() {
        $append_query_string = [];
        if(!empty($_GET['ad']))
            $append_query_string['ad'] = $_GET['ad'];
        return $append_query_string;
    }

    /**
     * @todo: Use the URL module introduced in RHEA-2816 to extract this data
     * @deprecated
     *
     * @param empty
     * @return $append_query_string
     */
    function getUtmParameters() {
        $append_query_string = [];
        if(!empty($_GET['utm_source']))
            $append_query_string['utm_source'] = $_GET['utm_source'];
        if(!empty($_GET['utm_medium']))
            $append_query_string['utm_medium'] = $_GET['utm_medium'];
        if(!empty($_GET['utm_campaign']))
            $append_query_string['utm_campaign'] = $_GET['utm_campaign'];
        if(!empty($_GET['utm_id']))
            $append_query_string['utm_id'] = $_GET['utm_id'];
        if(!empty($_GET['utm_source']))
            $append_query_string['utm_source'] = $_GET['utm_source'];
        if(!empty($_GET['utm_content']))
            $append_query_string['utm_content'] = $_GET['utm_content'];
        return $append_query_string;
    }

  function redirect(){
    $goto = explode('/', $_GET['dir']);
    $lang = phive('Localizer')->getLanguage();
    $sql = "SELECT go_link FROM redirects WHERE go_tag = '{$goto[1]}'";
    $url = phive('SQL')->getValue($sql." AND language = '$lang'");
    if(empty($url))
      $url = phive('SQL')->getValue($sql);
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: http://$url");
    header("Connection: close");
    exit;
  }

    /**
     * @todo: Use the URL module introduced in RHEA-2816 to perform URL modifications
     *
     * @param $url
     * @param $sublang
     * @param $same_page
     * @return mixed|string
     */
    function getToUrl($url, $sublang = '', $same_page = false){
        $fdomain    = phive()->getSetting('full_domain');
        $http_type  = phive()->getSetting('http_type');

        if ($sublang == phive('Localizer')->getDefaultLanguage()) {
            $sublang = '';
        }

        $page = $same_page ? '/'.phive('Pager')->cur_path.'/' : '';

        if(empty($page)){
            $sublang = empty($sublang) ? '' : "/$sublang/";
        }else{
            $sublang = '';
        }

        // Only when the URL doesn't already contain "protocol and domain" we want to add the missing part to the URL and clean the extra "/"
        // otherwise we end up with the "protocol_and_domain being prepended twice creating broken URL
        $protocol_and_domain = "http{$http_type}://{$fdomain}";
        if(strpos($url, $protocol_and_domain) === false) {
            $url = $sublang.$page.$url;
            $url = preg_replace('|/+|', '/', $url);
            $url = $protocol_and_domain.$url;
        }
        return $url;
    }

  /*
   * @param $url can be /foo/bar
   * @param $sublang can be sv
   */
  function to($url, $sublang = '', $same_page = false, $redir_code = "307 Temporary Redirect", $extra_headers = [], $extra_get_args = [], $return_url = false){
      $url = $this->getToUrl($url, $sublang, $same_page);
      if(!empty($extra_get_args)){
          $url .= '?'.http_build_query($extra_get_args);
      }

      if($return_url){
          return $url;
      }

      header("HTTP/1.1 $redir_code");
      header("Location: $url");
      foreach($extra_headers as $h){
          header($h);
      }
      header("Connection: close");
      exit;
  }

  function startGo($redir_code = "301 Moved Permanently", $sublang = '', $inverse = false){
    if ($sublang == phive('Localizer')->getDefaultLanguage())
      $sublang = '';

    $redir_links = array();

    foreach(phive('SQL')->loadArray("SELECT * FROM start_go") as $row) {
        if (empty($inverse)) {
            $redir_links[$row['from']] = $row['to'];
        } else {
            $redir_links[$row['to']] = $row['from'];
        }
    }

    list($cur_dir, $qstr) = explode('?', $_SERVER['REQUEST_URI']);

    if($sublang != '')
      $cur_dir = str_replace("/$sublang", '', $cur_dir);

    $to_link = $redir_links[ $cur_dir ];

    if(!empty($to_link)){
      $fdomain 	= phive()->getSetting('full_domain');
      $rurl 		= empty($sublang) ? $fdomain.$to_link : $fdomain."/$sublang".$to_link;
      if(!empty($qstr))
	$rurl .= "?$qstr";
    }

    $http_type  = phive()->getSetting('http_type');

    if(!empty($rurl)){
      header("HTTP/1.1 $redir_code");
      header("Location: http{$http_type}://".$rurl);
      header("Connection: close");
      exit;
    }
  }

    function jsRedirect($url, $sublang = '', $same_page = false, $extra_get_args = [], $top = false){
        $base_obj = $top === false ? 'window' : 'window.top';

        if(!$same_page){
            $url = $this->to($url, $sublang, $same_page, '', [], $extra_get_args, true);
            $js = "$base_obj.location.href = '$url';";
        } else {
            $args = http_build_query($extra_get_args);
            $js = "if($base_obj.location.href.indexOf('?') == -1){ $base_obj.location.href = $base_obj.location.href + '?$args'; }";
        }
        ?>
            <script> <?php echo $js ?> </script>
        <?php
    }

}
