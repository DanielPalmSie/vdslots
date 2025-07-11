<?php
require_once __DIR__ . '/../phive/api.php';
phive()->sessionStart();
$qs         = phive('Relax');
$locale     = $_GET['language'];
$gid        = $_GET['gameid'];
$ticket     = $_GET['ticket'];
$mmode      = $_GET['moneymode'];
$cu         = cu($_GET['uid']);
$base_url   = $qs->getLicSettingWithPlatform("launch_url", false, $cu);
$pid        = $_GET['partnerid'];
$mode       = $_GET['mode'];

$base_and_gid = $base_url . "/" . $gid;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en" xml:lang="en" xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <title>Videoslots.com Flash Casino Game Launcher</title>
    <meta value="notranslate" name="google">
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type">

    <style media="screen" type="text/css">
     html, body { height:100%; }
     body { margin:0; padding:0; overflow:auto; text-align:center;
           background-color: #000000; }
     object:focus { outline:none; }
     #flashContent { display:none; }
    </style>

    <?php loadJs("/phive/js/swfobject.js"); ?>
    
    <script type="text/javascript">
     function myeventlistener(myevent) {
     }
     // For version detection, set to min. required Flash Player version, or 0 (or 0.0.0), for no version detection.
     var swfVersionStr = "11.1.0";
     // To use express install, set to playerProductInstall.swf, otherwise the empty string.
     var xiSwfUrlStr = "playerProductInstall.swf";
     var gameid = '<?php echo $gid ?>';
     var gamever;
     var dimx = "100%";
     var dimy = "100%";
     var flashvars = {
       language: "<?php echo $locale ?>",
       ticket: "<?php echo $ticket ?>",
       partnerid: "<?php echo $pid ?>",
       mode: "<?php echo $mode ?>",
       moneymode:"<?php echo $mmode ?>",
       flash: '<?php echo $gid ?>' + ".swf",
       callback:"myeventlistener"
     };
     // Overwrite flash vars passed in as querystring param
     var query = window.location.search.substring(1).split("&");
     for (var i=0; i<query.length; i++) {
       var pair = query[i].split("=");
       flashvars[pair[0]] = decodeURIComponent(pair[1]);
       if (flashvars.moneymode == "fun") {
         flashvars.ticket = "";
       }
     }
     if (flashvars.gameid) {
       gameid = flashvars.gameid;
       flashvars.flash = gameid+".swf";
     }
     if (flashvars.gamever) {
       gamever = flashvars.gamever;
     }
     var params = {};
     params.quality = "high";
     params.bgcolor = "#000000";
     params.allowscriptaccess = "always";
     params.allowfullscreen = "true";
     params.wmode = "direct";
     if (gamever) {
       params.base = "<?php echo $base_and_gid ?>" + "-"+gamever+"/";
     }
     else {
       params.base = "<?php echo $base_and_gid ?>";
     }
     var attributes = {};
     attributes.id = gameid;
     attributes.name = gameid;
     attributes.align = "middle";
     attributes.wmode = "direct";
     if (flashvars.w) {
       dimx = flashvars.w;
     }
     if (flashvars.h) {
       dimy = flashvars.h;
     }
     swfobject.embedSWF(
       params.base + "RelaxPreloader.swf", "flashContent",
       dimx, dimy,
       swfVersionStr, xiSwfUrlStr,
       flashvars, params, attributes);
     // JavaScript enabled so display the flashContent div in case it is not replaced with a swf object.
     swfobject.createCSS("#flashContent", "display:block;text-align:left;"); 
    </script>
    <style type="text/css" media="screen">
     #flashContent {visibility:hidden}#flashContent {display:block;text-align:left;}
    </style>
  </head>
  <body>
    <object id="charmorama" width="100%" height="100%" align="middle" type="application/x-shockwave-flash" name="<?php echo $gid ?>" wmode="direct" data="<?php echo $base_url.$gid ?>/RelaxPreloader.swf">
      <param name="quality" value="high">
      <param name="bgcolor" value="#000000">
      <param name="allowscriptaccess" value="always">
      <param name="allowfullscreen" value="true">
      <param name="wmode" value="direct">
      <param name="base" value="<?php echo $base_url.$gid ?>/">
      <param name="flashvars" value="language=<?php echo $locale ?>&ticket=<?php echo $ticket ?>&partnerid=<?php echo $pid ?>&mode=<?php echo $mode ?>&moneymode=<?php echo $mmode ?>&flash=<?php echo $gid ?>.swf&callback=myeventlistener&gameid=<?php echo $gid ?>">
    </object>
    <noscript> <p> Either scripts and active content are not permitted to run or Adobe Flash Player version. </p> </noscript>
  </body>
</html>
