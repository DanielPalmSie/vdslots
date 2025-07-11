<?php
require_once __DIR__ . '/../../phive/phive.php';
$net = phive('Netent');
$referer = $_SERVER['HTTP_REFERER'];
$mob_server = $net->getSetting('static_url');
// if (strpos($referer, $mob_server) === false) {
//     header('Location: https://www.videoslots.com/mobile');
//     exit();
// }
$staticHost = $net->getSettingOrProxy('static_url',$user); //$net->getSetting('static_url');
$user = ud();
$username = $user['username'];
unset($user);
$reality_check_interval = phive('Casino')->startAndGetRealityInterval();
$reality_check_interval = empty($reality_check_interval) ? 0 : $reality_check_interval;
$lang = phive('Localizer')->getCurNonSubLang();
?>
<!DOCTYPE html>
<html>
    <head>
        <script>
        var cur_lang = '<?=$lang?>';
        var siteType = "mobile"; <? /* see comment on reality_check.php about siteType */ ?>
        </script>
        <?php loadJs("/phive/js/jquery.min.js"); ?>
        <?php loadJs("/phive/js/mg_casino.js") ?>
        <?php loadJs("/phive/js/reality_checks.js"); ?>
        <?php loadJs("/phive/js/utility.js"); ?>
        <?php loadJs("/phive/js/multibox.js"); ?>
        <?php loadCss("/diamondbet/css/" . brandedCss() . "reality_checks.css"); ?>
        <script type="text/javascript" src="<?= $staticHost ?>gameinclusion/library/gameinclusion.js"></script>
        <script type="text/javascript">
            rc_params.rc_current_interval = <?=$reality_check_interval?>;
//          var realitycheckmessage1 = "You have requested a Reality Check after %1 minutes of play.\nYour gaming session has now reached %2 minutes."; //\nTo continue playing, select Continue Playing or to stop playing, click Close Game.";
          var realitycheckmessage1 = '<? echo str_replace("'", "\'", t('reality-check.msg.elapsedtime',$lang)) ?>';
          var realitycheckmessage2 = "Select Game History to show game history or press Lobby to leave game and go to lobby.";
          var timer;
          var messageToShow;
          var duration = 0;
          var isFirstCall = true;

          window.onload = function () {
            reality_checks_js_mobile.startRc();
          };
          window.onerror = function () {
              haltGame();
          };

          function startRc() {
            pluginInit();
            if (rc_params.rc_current_interval == 0) return;
              timer = setInterval(setPluginTime, 1000);
          }

          function pluginInit() {
              netent.plugin.call("pluginReady", [], function () {});
              netent.plugin.call("showSystemClock", [], function (e) {}, function (e) {});
              netent.plugin.addEventListener("dialogBoxClosed", dialogBoxButtonHandler);
          }

          function realityCheckMsg() {
              netent.plugin.call("stopAutoplay", [], function () {});
              buttons = [{buttonid: "continue", buttontext: "Continue playing"}, {buttonid: "close", buttontext: "Leave game"}];
              showDialogBox("Reality check", messageToShow, buttons);
          }

          function showExtraClock(params){
              netent.plugin.set("inGameMessage", params);
          }

          function dialogBoxButtonHandler(box, buttonid) {
            console.log("button pressed: "+buttonid);
              if (box == "realitycheck") {
                  if (buttonid !== "continue") {
                      clearInterval(timer);
                      showSecondDialogBox();
                      if (buttonid === "history") {
                          window.open("<?= phive()->getSiteUrl() ?>/account/<?=$username?>/game-history/");
                      }
                  }

              }
          }

          function haltGame() {
              clearInterval(timer);
              netent.plugin.call("pluginError", [], function () {});
          }

          function showSecondDialogBox() {
              var params = ["realitycheck"];
              netent.plugin.call("removeDialogbox", params, function (e) {
              }, function (e) {
              });
              buttons = [{buttonid: "history", action: "gotolobby", reason: 10, buttontext: "Game History"}, {buttonid: "lobby", action: "gotolobby", reason: 11, buttontext: "Lobby"}];
              var messageToShow = realitycheckmessage2;
              showDialogBox("Leave game", messageToShow, buttons);
          }

          function showDialogBox(header, text, buttons) {
              if (!isFirstCall)
              {
                  var params = ["realitycheck"];
                  netent.plugin.call("removeDialogbox", params, function (e) {
                  }, function (e) {
                  });
              }
              isFirstCall = false;
              var params = ["realitycheck", header, text, buttons];
              netent.plugin.call("createDialogbox", params, function (e) {

              }, function (e) {
              });
          }

          function reloadBalance() {
              netent.plugin.call("reloadBalance", [], function() {}, function() {
                  setTimeout(reloadBalance, 1000);
              });
          }

          window.addEventListener("message", function(message) {
              if (message.data && message.data.rebuySuccess) {
                  reloadBalance();
              }
          });

        </script>
    </head>
    <body>
    </body>
</html>
