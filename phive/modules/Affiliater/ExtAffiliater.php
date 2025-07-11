<?php
set_include_path( '.' . PATH_SEPARATOR . '/opt/lib/' . PATH_SEPARATOR . get_include_path() );
include_once( 'Zend/Loader.php' );
Zend_Loader::loadClass( 'Zend_Http_Client' );
require_once __DIR__ . '/../../api/PhModule.php';


/*
 * Add Merchant:
 * http://www.emucasino.com:8090/admin/?col=merchant&action=add&mname=emucasino&mpwd=wasoo89la&passphrase=apskall1
 *
 * List Merchants:
 * http://www.emucasino.com:8090/admin/?col=merchant&action=list&passphrase=apskall1
 *
 * Add Affiliate
 * http://localhost:8090/admin/?col=affiliate&action=add&mname=emucasino&mpwd=wasoo89la&aname=test&apwd=test
 *
 * List Affiliates:
 * http://www.emucasino.com:8090/admin/?col=affiliate&action=list&mname=emucasino&mpwd=wasoo89la
 *
 * Load / Click stats daily resolution:
 * http://www.videoslots.com:8090/admin/?mname=videoslots&mpwd=wer4t2ssdd&resolution=day&sdate=1354294800&edate=1356886800&aname=videoslots-hsarvell&apwd=4d87004de89a4&action=clickandload&col=campaign
 *
 * Load affiliate banners
 * https://www.videoslots.com:8443/admin/?mname=videoslots&mpwd=wer4t2ssdd&aname=videoslots-hsarvell&apwd=4d87004de89a4&action=list&col=campaign
 */


class ExtAffiliater extends PhModule {

  function client ( $url, $keepalive = false ) {

    $this->client = new Zend_Http_Client( $this->getSetting( 'ext_url' ) . $url, array(
      'maxredirects' => 2,
      'timeout'      => 3,
      'keepalive'    => $keepalive
    ) );

    return $this;
  }

  function mongoId ( $obj ) {
      return $obj['id'];
  }

  function getClickUrl ( $obj, $bcode = '', $encode = false, $turl ) {

    if ( empty( $bcode ) )
      return $this->getSetting( 'ext_url' ) . "click/?campid=" . $this->mongoId( $obj );
    else {

      $cur_user = cu();

      if ( phive()->moduleExists( 'PRUserHandler', true ) ) {

        $username = phive( 'UserHandler' )->getCompanyManager( phive( 'UserHandler' )->getUserCompany( $_SESSION[ 'user' ] )[ 'company_id' ] )[ 'username' ];

        $url = $this->getLongClickUrl( "{$obj['owner']}-{$username}", $bcode,
                                       "$bcode-banner{$obj['width']}x{$obj['height']}", $obj[ 'img' ], $turl );
      }
      else {
        $url = $this->getLongClickUrl( "{$obj['owner']}-{$cur_user->data['username']}", $bcode,
                                       "$bcode-banner{$obj['width']}x{$obj['height']}", $obj[ 'img' ] );
      }

      return $encode ? urlencode( $url ) : $url;
    }
  }

  function getSize ( $key, $obj ) {

    if ( !empty( $obj[ 'banner' ] ) )
      return $obj[ 'banner' ][ $key ];

    return $obj[ $key ];
  }

  function getExtAid ( $aff_id ) {

    if ( is_numeric( $aff_id ) )
      return $this->getSetting( 'name' ) . '-' . ud( $aff_id )['username'];

    return $aff_id;
  }

  function getLongClickUrl ( $aff_id, $bcode, $label = '', $bid = 'no-banner', $turl = '' ) {

    if ( empty( $turl ) )
      $turl = $this->getSetting( 'default_turl' ) . $bcode;
    else
      $turl .= $bcode;

    $label = ( empty( $label ) && $bid == 'no-banner' ) ? $bcode . "-textlink" : $label;

    return $this->getSetting( 'ext_url' ) . "click/?aid=" . $this->getExtAid( $aff_id ) . "&turl=" .
           urlencode( $turl ) . "&bid=$bid&label=$label&dynamic=";
  }

  function getLongLoadUrl ( $b, $bcode, $turl ) {

    $label = "$bcode-banner{$b['width']}x{$b['height']}";

    $cur_user = cu();

    if ( phive()->moduleExists( 'PRUserHandler', true ) ) {

      $username = phive( 'UserHandler' )->getCompanyManager( phive( 'UserHandler' )->getUserCompany( $_SESSION[ 'user' ] )[ 'company_id' ] )[ 'username' ];

      return $this->getSetting( 'ext_url' ) . "load/?aid={$b['owner']}-{$username}&turl=" .
             urlencode( $turl . $bcode ) . "&bid={$b['img']}&label=$label";
    }
    else {

      return $this->getSetting( 'ext_url' ) . "load/?aid={$b['owner']}-{$cur_user->data['username']}&turl=" .
             urlencode( $this->getSetting( 'default_turl' ) . $bcode ) . "&bid={$b['img']}&label=$label";
    }
    //    return $this->getSetting( 'ext_url' ) . "load/?aid={$b['owner']}-{$this->cur_username}&turl=" .
    //           urlencode( $this->getSetting( 'default_turl' ) . $bcode ) . "&bid={$b['_id']}&label=$label";
  }

  function getLoadUrl ( $obj, $bcode = '', $encode = false, $turl ) {

    if ( empty( $bcode ) )
      return $this->getSetting( 'ext_url' ) . "load/?campid=" . $this->mongoId( $obj );
    else
      return $encode ? urlencode( $this->getLongLoadUrl( $obj, $bcode, $turl ) ) : $this->getLongLoadUrl( $obj, $bcode, $turl );
  }

  function getLoaderUrl () {

    return $this->getSetting( 'ext_url' ) . "banners/loader.swf";
  }

  function uploadBanner ( $change = false, $product = 'videoslots' ) {

    $banner = $_FILES[ 'banner' ][ 'tmp_name' ];
    list( $img, $ext ) = explode( '.', $_FILES[ 'banner' ][ 'name' ] );
    $new_banner = $banner . ".$ext";
    $url = 'addbanner/?' . $this->getMCreds( false, $product ) . "&width={$_POST['width']}&height={$_POST['height']}";

    if ( $change )
      $url .= "&bannerid={$_POST['bannerid']}&banneraction=update";
    else
      $url .= "&banneraction=upload";

    if ( move_uploaded_file( $banner, $new_banner ) ) {
      $this->client( $url )->client->setFileUpload( $new_banner, 'banner' );
      $body = $this->client->request( 'POST' )->getBody();

      return $body;
    }
    else if ( $change ) {
      $result = $this->merchantGet( 'admin', 'update', 'banner',
                                    array( 'width'    => $_POST[ 'width' ], 'height' => $_POST[ 'height' ],
                                           'bannerid' => urlencode( $_POST[ 'bannerid' ] )
                                    ) );

      return $result;
    }
  }

  function bannerUrl ( $b, $col = 'img' ) {

    return $this->getSetting( 'int_url' ) . 'banners/' . $b[ $col ];
  }

  function includeBanner ( $b, $size = 0.5, $col = 'img' ) {

    $width = round( $b[ 'width' ] * $size );
    $height = round( $b[ 'height' ] * $size );
    if ( strpos( $b[ $col ], '.swf' ) !== false ) {
      ?>
      <object width="<?php echo $width ?>" height="<?php echo $height ?>">
        <param name="movie" value="<?php echo $this->bannerUrl( $b ) ?>"></param>
        <param name="allowFullScreen" value="false"></param>
        <embed src="<?php echo $this->bannerUrl( $b ) ?>" type="application/x-shockwave-flash"
               width="<?php echo $width ?>" height="<?php echo $height ?>">
        </embed>
      </object>
    <?php }
    else {

      if ( phive()->moduleExists( "PRUserHandler" ) )
        echo '<img src="' . $this->bannerUrl( $b ) . '" class="img-responsive"/>';
      else
        echo '<img src="' . $this->bannerUrl( $b ) . '" style="width:' . $width . 'px; height:' . $height . 'px;" />';
    }
  }

  //array('ext_id' => $bid)
  function tagBanner ( $bid, $tags ) {

    if ( empty( $tags ) )
      return $this->deleteTag( $bid );

    return phive( 'SQL' )->insertArray( 'ext_banners', array( 'ext_id' => $bid, 'tags' => $tags ), null, true );
  }

  function deleteTag ( $bid ) {

    return phive( 'SQL' )->query( "DELETE FROM ext_banners WHERE ext_id = '$bid'" );
  }

  function getTagWhere ( $tag ) {

    if ( !empty( $tag ) ) {
      if ( is_string( $tag ) )
        $tag = array( $tag );
      $where = " WHERE 1 ";
      foreach ( $tag as $t ) {
        if ( !empty( $t ) )
          $where .= " AND tags REGEXP '$t,|,$t|^$t|$t$' ";
      }
    }

    return $where;
  }

  function getBannerTags ( $tag ) {

    $where = $this->getTagWhere( $tag );

    return phive( 'SQL' )->loadArray( "SELECT * FROM ext_banners $where", 'ASSOC', 'ext_id' );
  }

  function getMerchantBannersByTag ( $tag ) {

    if ( empty( $tag ) )
      return array();
    $where = $this->getTagWhere( $tag );
    $str = "SELECT * FROM ext_banners $where";

    return phive( 'SQL' )->loadArray( $str, 'ASSOC', 'ext_id' );
  }

  function getExtMerchantBannersByTag ( $tag, $istagged = true ) {

    $banners = $this->getMerchantBanners();
    $tagged = array_keys( $istagged ? $this->getMerchantBannersByTag( $tag ) : $this->getBannerTags() );

    $rarr = array();
    foreach ( $banners as $b ) {
      if ( !empty( $tag ) ) {
        if ( in_array( $b[ 'img' ], $tagged ) == $istagged )
          $rarr[] = $b;
      }
    }

    return $rarr;
  }

  function getUntaggedExtMerchantBanners () {

    return $this->getExtMerchantBannersByTag( 'none', false );
  }

  function bannerTagSelect ( $type = '', $parent = '' ) {

    $rarr = array();
    foreach ( $this->getBannerTags( $parent ) as $t ) {
      $tmp = explode( ',', $t[ 'tags' ] );
      $rarr = array_merge( $rarr, $tmp );
    }

    $rarr = array_unique( $rarr );

    if ( !empty( $type ) ) {
      $langs = array_merge( array( 'fr' ), phive( "Localizer" )->getLanguages() );
      $cisos = cisos();

      $rarr = array_filter( $rarr, function ( $t ) use ( $langs, $type, $cisos ) {

        if ( in_array( $t, $langs ) )
          return false;

        if ( $type == 'currency' && !in_array( $t, $cisos ) )
          return false;
        else if ( $type != 'currency' && in_array( $t, $cisos ) )
          return false;

        if ( $type == 'size' && !preg_match( '|^(\d+?)x(\d+)$|sim', $t )
        ) //TODO fix this, explode on x and compare the parts
          return false;

        if ( $type == 'content' && preg_match( '|^(\d+?)x(\d+)$|sim', $t ) )
          return false;

        return true;
      } );
    }

    asort( $rarr );

    return phive()->remEmpty( array_combine( $rarr, array_map( 'ucfirst', $rarr ) ) );
  }

  function removeDangling () {

    $ext_ids = array();
    foreach ( $this->getMerchantBanners() as $b ) {
      $ext_ids[] = $b[ 'img' ];
    }
    foreach ( $this->getBannerTags() as $id => $tag ) {
      if ( !in_array( $id, $ext_ids ) )
        $this->deleteTag( $id );
    }
  }

  function setUri ( $url ) {

    $this->client->setUri( $this->getSetting( 'int_url' ) . $url );

    return $this;
  }

  function getBody ( $params ) {

    try {
      $json = $this->client->setParameterGet( $params )->request( 'GET' )->getBody();
    } catch ( Exception $e ) {
      $json = "{}";
    }

    $json = utf8_encode( $json );
    if ( $this->getSetting( 'debug' ) == true )
      phive()->dumpTbl( "extaff-{$params['action']}", $json );
    $this->raw_result = $json;

    return json_decode( $json, true );
  }

  function get ( $url, $action, $col, $params = array() ) {
      $params[ 'action' ] = $action;
      $params[ 'col' ] = $col;
      if ( $this->getSetting( 'debug' ) == true )
          phive()->dumpTbl( "extaff-$action", $url . " " . var_export( $params, true ) );
      $res = $this->client( $url )->getBody( $params );
      if ( $this->getSetting( 'debug' ) == true )
          phive()->dumpTbl( "extaff-res-$action", $res);
      return $res;
  }

  function merchantGet ( $url, $action, $col, $params = array(), $product = 'videoslots' ) {

    $params = array_merge( $params, $this->getMCreds( true, $product ) );

    return $this->get( $url, $action, $col, $params );
  }

  function affiliateGet ( $url, $action, $col, $params = array(), $aname = '', $apwd = '' ) {
    $params = array_merge( $params, $this->getACreds(true, $aname, $apwd) );
    return $this->get( $url, $action, $col, $params );
  }

  function getMCreds ( $as_array = true, $product = 'videoslots' ) {

    if ( $as_array )
      return array( 'mname' => $product, 'mpwd' => $this->getSetting( 'pwd' ) );
    else
      return "mname=" . $product . "&mpwd=" . $this->getSetting( 'pwd' );
  }

  function getAPwd ( $aff_id ) {

    return phive( 'SQL' )->getValue( "SELECT ext_pwd FROM affiliate_info WHERE affe_id = $aff_id" );
  }

  function setACreds ( $name, $uid ) {

    $this->creds[ 'name' ] = $name;
    $this->creds[ 'apwd' ] = $this->getAPwd( $uid );
  }

    function getACreds ( $as_array = true, $aname = '', $apwd = '') {

        if(!empty($aname) && !empty($apwd)){
            $this->creds[ 'name' ] = $aname;
            $this->creds[ 'apwd' ] = $apwd;
        }

        if ( empty( $this->creds ) ) {
            $this->creds[ 'name' ] = $_SESSION[ 'mg_username' ];
            $this->creds[ 'apwd' ] = $this->getAPwd( $_SESSION[ 'user_id' ] );
        }

        if ( $as_array )
            return array( 'aname' => $this->getSetting( 'name' ) . '-' . $this->creds[ 'name' ],
                          'apwd'  => $this->creds[ 'apwd' ]
            );
        else
            return "aname=" . $this->getSetting( 'name' ) . '-' . $this->creds[ 'name' ] . "&apwd=" . $this->creds[ 'apwd' ];
    }

  function insertAffiliate ( $username, $pwd = '' ) {
      phive()->dumpTbl('extaff-insert', func_get_args());
      $pwd = empty( $pwd ) ? uniqid() : $pwd;
      $params = array( 'aname' => $username, 'apwd' => $pwd );
      $result = $this->merchantGet( 'admin', 'add', 'affiliate', $params );

    return empty( $result ) ? $this->raw_result : $result;
  }

  function getMerchantBanners ( $product = 'videoslots' ) {

    return $this->merchantGet( 'admin', 'list', 'banner', [ ], $product );
  }

  function getMerchantAffiliates () {

    $affs = $this->merchantGet( 'admin', 'list', 'affiliate' );
    foreach ( $affs as &$a ) {
        $local = phive( 'SQL' )->loadAssoc( "SELECT * FROM affiliate_info WHERE ext_pwd = '{$a['pw']}'" );
        $a[ 'local_exists' ] = empty( $local ) ? 'no' : 'yes';
        $a['username'] = $a['nm'];
      //list( $a[ 'merchant' ], $a[ 'username' ] ) = explode( '-', $a[ '_id' ] );
    }

    return $affs;
  }

  function deleteBanner () {

    return $this->merchantGet( 'admin', 'delete', 'banner', array( 'bannerid' => $_POST[ 'bannerid' ] ) );
  }

  function insertCampaign ( $bid, $turl, $label ) {

    return $this->affiliateGet( 'admin', 'add', 'campaign',
                                array( 'turl' => $turl, 'bannerid' => $bid, 'label' => $label ) );
  }

  function deleteCampaign ( $camp_id ) {

    return $this->affiliateGet( 'admin', 'delete', 'campaign', array( 'campid' => $camp_id ) );
  }

  function getAffiliateCampaigns () {

    return $this->affiliateGet( 'admin', 'list', 'campaign', [ ] );
  }

  function getAffiliateDynamics ( $campaignids ) {
    $results = array();
    foreach($campaignids as $key => $value){
      //var_dump($value);
      //return $this->affiliateGet( 'admin', 'list', 'dynamics', array( 'campid' => $value ) );
      $results[] = $this->affiliateGet( 'admin', 'get', 'dynamics', array( 'campid' => $value ) );
    }
    var_dump($results);
    die;
  }

  function getBannersTextLinks ( $stats, $type = 'banner' ) {

    $banners = array();
    foreach ( $stats[ 'loads' ] as $b => $data ) {
      $banners[] = $b;
    }
    if ( $type == 'banner' )
      return $banners;
    else if ( $type == 'textlink' ) {
      $txtlinks = array();
      foreach ( $stats[ 'clicks' ] as $l => $data ) {
        if ( !in_array( $l, $banners ) )
          $txtlinks[] = $l;
      }

      return $txtlinks;
    }
  }

  function sumDaily ( $stats, $sday, $eday, $type, $rtype = 'banner' ) {

    $sum = 0;
    foreach ( $stats[ $type ] as $lbl => $s ) {
      foreach ( $s as $day => $count ) {
        if ( (int)$day >= $sday && (int)$day <= $eday ) {
          if ( $rtype == 'banner' && !empty( $stats[ 'loads' ][ $lbl ][ $day ] ) )
            $sum += $count;
          else if ( $rtype == 'textlink' && empty( $stats[ 'loads' ][ $lbl ][ $day ] ) )
            $sum += $count;
        }
      }
    }

    return $sum;
  }

  function sumDailyLabel ( $stats, $sday, $eday, $type, $label ) {

    $sum = 0;
    foreach ( $stats[ $type ][ $label ] as $day => $count ) {
      if ( (int)$day >= $sday && (int)$day <= $eday )
        $sum += $count;
    }

    return $sum;
  }

  function sumThisWeek ( $stats, $type = 'loads', $rtype = 'banner', $label = '' ) {

    $eday = date( 'j' );
    $sday = $eday < 7 ? 1 : $eday - 7;
    if ( empty( $label ) )
      return $this->sumDaily( $stats, $sday, $eday, $type, $rtype );
    else
      return $this->sumDailyLabel( $stats, $sday, $eday, $type, $label );
  }

  function sumThisDay ( $stats, $type = 'loads', $rtype = 'banner', $label = '' ) {

    if ( empty( $label ) )
      return $this->sumDaily( $stats, date( 'j' ), date( 'j' ), $type, $rtype );
    else
      return $this->sumDailyLabel( $stats, date( 'j' ), date( 'j' ), $type, $label );
  }

  function sumViewsThisDay ( $stats ) {

    return $this->sumDaily( $stats, date( 'j' ), date( 'j' ), 'loads', 'banner' );
  }

  function sumClicksThisDay ( $stats, $rtype ) {

    return $this->sumDaily( $stats, date( 'j' ), date( 'j' ), 'clicks', $rtype );
  }

  function sumViewsThisWeek ( $stats ) {

    return $this->sumThisWeek( $stats );
  }

  function sumClicksThisWeek ( $stats, $rtype ) {

    return $this->sumThisWeek( $stats, 'clicks', $rtype );
  }

  function sumViews ( $stats, $ym = '', $label = '' ) {

    $sum = 0;
    if ( empty( $label ) ) {
      foreach ( $stats[ 'loads' ] as $lbl => $s ) {
        $sum += empty( $ym ) ? array_sum( $s ) : $s[ $ym ];
      }

      return $sum;
    }
    else if ( empty( $ym ) )
      return array_sum( $stats[ 'loads' ][ $label ] );
    else
      return $stats[ 'loads' ][ $label ][ $ym ];
  }

  function sumClicksLabel ( $stats, $label, $ym = '' ) {

    $sum = 0;
    foreach ( $stats[ 'clicks' ][ $label ] as $date => $count ) {
      if ( empty( $ym ) || $ym == $date )
        $sum += $count;
    }

    return $sum;
  }

  function sumClyicks ( $stats, $type = 'banner', $ym = '' ) {

    $sum = 0;
    foreach ( $stats[ 'clicks' ] as $lbl => $s ) {
      if ( $type == 'banner' && !empty( $stats[ 'loads' ][ $lbl ] ) )
        $sum += empty( $ym ) ? array_sum( $s ) : $s[ $ym ];
      else if ( $type == 'textlink' && empty( $stats[ 'loads' ][ $lbl ] ) )
        $sum += empty( $ym ) ? array_sum( $s ) : $s[ $ym ];
    }

    return $sum;
  }

  function formatStats ( $stats, $resolution ) {

    $rarr = array();
    foreach ( $stats as $camp ) {
      foreach ( $camp as $label => $months ) {
        foreach ( $months as $m ) {
          $date = $resolution == 'day' ? date( 'd', $m[ 'stamp' ] ) : $m[ 'stamp' ];
          $rarr[ $label ][ $date ] = $m[ 'view' ];
        }
      }
    }

    return $rarr;
  }

  function getLoadClickStats ( $sdate = '', $edate = '', $resolution = 'day', $aname = '', $apwd = '', $dynamic ) {

    $sdate = empty( $sdate ) ? date( 'Y-01-01' ) : $sdate;
    $edate = empty( $edate ) ? date( 'Y-12-31' ) : $edate;
    $result = $this->affiliateGet( 'admin', 'clickandload', 'campaign',
                                   array( 'resolution' => $resolution, 'sdate' => strtotime( $sdate ),
                                          'edate'      => strtotime( $edate )
                                   ), $aname, $apwd );

      return $result;
    //return array( 'clicks' => $this->formatStats( $result[ 'clicks' ], $resolution ), 'loads'  => $this->formatStats( $result[ 'loads' ], $resolution ));
  }

  public function getTagByType ( $type, $banners = null ) {
    $type = phive('SQL')->escape($type,false);
    $bid_where = '';
    $b_ids = $this->getBIDs( $banners, null );

    if ( !empty( $b_ids ) ) {

      $b_ids = phive( 'UserHandler' )->helperArrayToInStr( $b_ids );
      $bid_where = " AND b_id IN ( $b_ids )";
    }

    return phive( 'SQL' )->loadArray( "SELECT tag FROM ext_banners_tags
                                        WHERE type = '$type'
                                        $bid_where
                                        GROUP BY tag" );
  }

  public function getTagByTypeSearch ( $type, $search, $product = null ) {
    $type = phive('SQL')->escape($type,false);
    $search = phive('SQL')->escape($search,false);
    $product_where = '';

    if ( !empty( $product ) )
      $product_where = " AND b_id IN ( SELECT b_id FROM ext_banners_tags WHERE type = 'product' AND tag = '$product' )";

    return phive( 'SQL' )->loadArray( "SELECT tag FROM ext_banners_tags WHERE type = '$type' AND tag LIKE '%$search%' $product_where GROUP BY tag" );
  }

  public function getTagByTypeMarketSource ( $type, $banners = null ) {
    $type = phive('SQL')->escape($type,false);
    $bid_where = '';
    $b_ids = $this->getBIDs( $banners, null );

    if ( !empty( $b_ids ) ) {

      $b_ids = phive( 'UserHandler' )->helperArrayToInStr( $b_ids );
      $bid_where = " AND b_id IN ( $b_ids )";
    }

    return phive( 'SQL' )->loadArray( "SELECT tag
                                        FROM ext_banners_tags
                                        WHERE TYPE =  '$type'
                                        $bid_where
                                        GROUP BY tag" );
  }

  public function getTagByTypeFilteredMarketSource ( $type, $banners = null, $filters = null ) {
    $type = phive('SQL')->escape($type,false);
    $b_ids = $this->getBIDs( $banners, $filters );

    $b_ids = phive( 'UserHandler' )->helperArrayToInStr( $b_ids );
    $bid_where = " AND b_id IN ( $b_ids )";

    return phive( 'SQL' )->loadArray( "SELECT tag FROM ext_banners_tags
                                        WHERE type = '$type'
                                        $bid_where
                                        GROUP BY tag" );
  }

  public function getFilteredTagByType ( $type, $banners = null, $filters = null ) {

    $bid_where = '';
    $b_ids = $this->getBIDs( $banners, $filters );

    if ( !empty( $b_ids ) ) {

      $b_ids = phive( 'UserHandler' )->helperArrayToInStr( $b_ids );
      $bid_where = " AND b_id IN ( $b_ids )";
    }
    $type = phive('SQL')->escape($type,false);
    return phive( 'SQL' )->loadArray( "SELECT tag
                                        FROM ext_banners_tags
                                        WHERE type = '$type'
                                         $bid_where
                                        GROUP BY tag" );
  }

  public function getBIDs ( $banners = null, $filters = null ) {

    $banners_bids = [ ];
    $filters_bids = [ ];
    $banners_where = '';

    if ( !empty( $banners ) ) {

      foreach ( $banners as $banner ) {

        if ( key_exists( 'bannerimage', $banner ) )
          $key = $banner[ 'bannerimage' ];
        else
          $key = $banner[ 'img' ];

        $banners_bids[] = $this->getBannerDetailsByFile( $key )[ 'ban_id' ];
      }

      $banners_bids_str = phive( 'UserHandler' )->helperArrayToInStr( $banners_bids );
      $banners_where = " AND b_id IN ( $banners_bids_str )";
    }

    if ( !empty( $filters ) ) {

      foreach ( $filters as $filter ) {

        if ( !empty( $filter ) )
          $filter = phive('SQL')->escape($filter,false);
          array_merge( $filters_bids, phive( 'SQL' )->loadArray( "SELECT b_id FROM ext_banners_tags WHERE tag = '$filter' $banners_where AND  GROUP BY b_id" ) );
      }
    }

    if ( !empty( $banners ) )
      $b_ids = $banners_bids;
    else
      $b_ids = $filters_bids;

    return $b_ids;
  }

  public function getBannersByTags ( $filters ) {

    $sub_query = '';

    foreach ( $filters as $filter ) {

      $filter = phive('SQL')->escape($filter,false);
      
      if ( $filter != '' ) {

        if ( $sub_query == '' ) {
          $sub_query .= "id IN ( SELECT b_id
                        FROM ext_banners_tags
                        WHERE tag = '$filter' )";
        }
        else {

          $sub_query .= " AND id IN (
                        SELECT b_id
                        FROM ext_banners_tags
                        WHERE tag = '$filter'
                      )";
        }
      }
    }

    return phive( 'SQL' )->loadArray( "SELECT name FROM ext_banners WHERE $sub_query" );
  }

  public function getExtBannersbyTags ( $filters, $product, $affiliate = false ) {

    $key = 'img';

    if ( $affiliate ) {

      $banners = $this->getAffiliateCampaigns();
      $key = 'bannerimage';
    }
    else
      $banners = $this->getMerchantBanners( $product );

    $tagged = $this->getBannersByTags( $filters );

    $rarr = array();

    foreach ( $banners as $b ) {

      foreach ( $tagged as $tag ) {

        if ( $b[ $key ] == $tag[ 'name' ] ) {

          $rarr[] = $b;
          break;
        }
      }
    }

    return $rarr;
  }

  public function getCampCode ( $obj, $bcode = '', $turl = '' ) {

    $loadurl = $this->getLoadUrl( $obj, $bcode, true, $turl );
    $clickurl = $this->getClickUrl( $obj, $bcode, true, $turl );
    $size_w = $this->getSize( 'width', $obj );
    $size_h = $this->getSize( 'height', $obj );

    $loaderurl = $this->getLoaderUrl();
    ?>
    <?php if ( strpos( $obj[ 'bannerimage' ], '.swf' ) !== false || strpos( $obj[ 'img' ], '.swf' ) !== false ): ?>
      <object width="<?php echo $size_w ?>" height="<?php echo $size_h ?>">
        <param name="movie" value="<?php echo $loaderurl ?>"></param>
        <param name="allowFullScreen" value="false"></param>
        <param name="wmode" value="window"></param>
        <param name="scale" value="default"></param>
        <param name="FlashVars"
               value="loadtarget=<?php echo $loadurl ?>&clickUrl=<?php echo $clickurl ?>&width=<?php echo $size_w ?>&height=<?php echo $size_h ?>"></param>
      </object>
    <?php else: ?>
      <a href="<?php echo urldecode( $clickurl ) ?>">
        <img src="<?php echo urldecode( $loadurl ) ?>"/>
      </a>
    <?php endif ?>
    <?php
  }

  public function getBannerName ( $banner ) {
    $banner = phive('SQL')->escape($banner,false);
    return phive( 'SQL' )->loadAssoc( "SELECT tag FROM ext_banners_tags bt
                                      INNER JOIN ext_banners b
                                      ON b.id = bt.b_id
                                      WHERE bt.type = 'name'
                                      AND b.name = '$banner'" );
  }

  public function getBannerFileByName ( $name ) {

    $name = phive('SQL')->escape($name,false);
    return phive( 'SQL' )->loadArray( "SELECT b.id, b.name FROM ext_banners b
                                      INNER JOIN ext_banners_tags bt
                                      ON b.id = bt.b_id
                                      WHERE bt.type = 'name'
                                      AND bt.tag = '$name'" );
  }

  public function getBannerFileByID ( $id ) {
    $id = phive('SQL')->escape($id,false);
    return phive( 'SQL' )->loadAssoc( "SELECT * FROM ext_banners
                                      WHERE id = '$id'" );
  }

  public function getBannerDetailsByFile ( $file ) {
    $file = phive('SQL')->escape($file,false);
    return phive( 'SQL' )->loadAssoc( "SELECT b.id as ban_id, b.*, bt.* FROM ext_banners b
                                      INNER JOIN ext_banners_tags bt
                                      ON b.id = bt.b_id
                                      WHERE b.name = '$file'" );
  }

  public function getBannerDetailsByFileAndType ( $bid, $type, $multi = false ) {
    $bid = phive('SQL')->escape($bid,false);
    $type = phive('SQL')->escape($type,false);
    $sql = "SELECT * FROM ext_banners b
                                      INNER JOIN ext_banners_tags bt
                                      ON b.id = bt.b_id
                                      WHERE b.name = '$bid'
                                      AND bt.type = '$type'";

    if ( !$multi )
      return phive( 'SQL' )->loadAssoc( $sql );
    else
      return phive( 'SQL' )->loadArray( $sql );
  }

  public function getBannerDetailsByID ( $bid, $type ) {
    $bid = phive('SQL')->escape($bid,false);
    $type = phive('SQL')->escape($type, false);
    return phive( 'SQL' )->loadArray( "SELECT * FROM ext_banners b
                                      INNER JOIN ext_banners_tags bt
                                      ON b.id = bt.b_id
                                      WHERE b.id = '$bid'
                                      AND bt.type = '$type'" );
  }

    public function getExtBannerByName ( $name, $product = 'videoslots' ) {
        $banners = $this->getMerchantBanners( $product );
        $tagged = $this->getBannerFileByName( $name );
        $banner = array();
        foreach ( $banners as $b ) {
            foreach( $tagged as $t ) {
                if ( $b[ 'img' ] == $t[ 'name' ] ) {
                    $banner[] = $b;
                    break;
                }
            }
        }

        return $banner;
    }

  public function getExtBannerByID ( $id, $product = 'videoslots' ) {

    $banners = $this->getMerchantBanners( $product );
    $tagged = $this->getBannerFileByID( $id );

    $banner = array();

    foreach ( $banners as $b ) {

      if ( $b[ 'img' ] == $tagged[ 'name' ] ) {

        $banner[] = $b;
        break;
      }
    }

    return $banner;
  }

  public function updateBannerTag ( $b_id, $type, $tag ) {

    if ( $type != 'type' )
      phive( 'SQL' )->updateArray( 'ext_banners_tags', array( 'tag' => $tag ), "b_id = $b_id AND type = '$type'" );
    else {

      if ( !empty( $tag ) ) {

        $banner_types = $this->getBannerDetailsByID( $b_id, 'type' );

        foreach ( $banner_types as $index => $banner_type ) {

          phive( 'SQL' )->updateArray( 'ext_banners_tags', array( 'tag' => $tag[ $index ] ), "id = {$banner_type['id']}" );
          unset( $tag[ $index ] );
        }

        foreach ( $tag as $value ) {

          $insert = [
            'tag'  => $value,
            'type' => 'type',
            'b_id' => $b_id
          ];

          phive( 'SQL' )->insertArray( 'ext_banners_tags', $insert );
        }

        $banner_types = $this->getBannerDetailsByID( $b_id, 'type' );

        foreach ( $banner_types as $banner_type ) {

          if ( empty( $banner_type[ 'tag' ] ) )
            phive( 'SQL' )->delete( 'ext_banners_tags', "id = {$banner_type['id']}" );
        }
      }
    }
  }

  public function insertBanner ( $banner, $product ) {

    phive( 'SQL' )->insertArray( 'ext_banners', [ 'name' => $banner, 'product' => $product ] );

    return phive( 'SQL' )->insertBigId();
  }

  public function insertBannerTags ( $b_id, $type, $tag ) {

    $insert = [
      'tag'  => $tag,
      'type' => $type,
      'b_id' => $b_id
    ];

    return phive( 'SQL' )->save( 'ext_banners_tags', $insert );
  }

  public function deleteBannerAndTags ( $banner_file ) {

    $b_id = $this->getBannerByFileName( $banner_file )[ 'id' ];

    phive( 'SQL' )->delete( 'ext_banners', "id = $b_id" );
    phive( 'SQL' )->delete( 'ext_banners_tags', "b_id = $b_id" );
  }

  public function getBannerByFileName ( $banner_file ) {
    $banner_file = phive('SQL')->escape($banner_file,false);
    return phive( 'SQL' )->loadAssoc( "SELECT * FROM ext_banners
                                      WHERE name = '$banner_file'" );
  }
}
