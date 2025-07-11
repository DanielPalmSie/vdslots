<?php
require_once __DIR__ . '/../phive/phive.php';
phive()->sessionStart();
phive("Localizer")->setLanguage($_GET['lang'], true);
$lobbyUrl = phive('Casino')->getLobbyUrl(false, $_GET['lang']);

$style_parameters = http_build_query([
                'div' => 'pngCasinoGame',
                'background' => '000000',
                'width'=> '100%',
                'height' => '100%'
        ]);
$show_demo = filter_var($_GET['show_demo'] ?? false, FILTER_VALIDATE_BOOLEAN);
$url = phive('Playngo')->getFlashUrl($_GET['gid'], $_GET['lang'], $_GET['mp_id'],  $show_demo);
$url .= "&{$style_parameters}";
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title></title>
        <script src="<?php echo $url ?>" type="text/javascript"></script>

        <?php
        loadJs("/phive/js/underscore.js");
        loadJs("/phive/js/jquery.min.js");
        loadJs("/phive/js/utility.js");
        loadJs("/phive/js/gameplay.js");
        ?>
    </head>
    <body style="margin: 0; padding: 0; background-color: #000000;">
        <script type="text/javascript">
         var debug = true;
         function fixGameSize(){
             $('#pngCasinoGame').attr('width', $(window).width()).attr('height', $(window).height());
             $('body').css({
               width: $(window).width(),
               height: $(window).height()
             });
         }
         $(document).ready(function(){
             map = {
                 "gameRoundStarted": "roundStarted",
                 "freeSpinStarted": "freespinStarted",
                 "gameRoundEnded": "roundEnded",
                 "freeSpinEnded": "freespinEnded"
             };

             if(typeof Engage == 'object'){
                 <?php // echo 'Engage.enableDebug();'; ?>
                 <?php if (!empty($_GET['mp_id'])): ?>
                 _.each(gameEvents, function(doFunc, funcName){
                     console.log(funcName);
                     funcName = empty(map[funcName]) ? funcName : map[funcName];
                     Engage.addEventListener(funcName, doFunc);
                 });
                 <?php else: ?>
                     Engage.addEventListener('roundStarted', function() {
                         window.top.postMessage('spinStarted')
                     });
                     Engage.addEventListener('roundEnded', function() {
                         window.top.postMessage('spinEnded')
                     });

                <?php endif ?>

                 parent.gameFi = {
                     "toFrame": function(action, ret, onSuccess, onError){
                         if(action == 'reloadBalance')
                             Engage.request({req: "refreshBalance"});
                     }
                 };

                 Engage.addEventListener('backToLobby', function() {
                     window.top.location.href = '<?= $lobbyUrl ?>';
                 });

                 Engage.addEventListener('logout', function() {
                     window.top.location.href = '<?= $lobbyUrl ?>';
                 });
             }

             fixGameSize();
             $(window).on("resize", function(){
                 fixGameSize();
             });
         });

         function ShowCashier() {
             parent.mboxDeposit('<?php echo llink('/cashier/deposit/') ?>');
         }

         function PlayForReal() {
             parent.goTo('<?php echo llink('/?signup=true') ?>');
         }

         function Logout() {
             //parent.goTo('<?php echo llink('/') ?>');
         }

         function reloadgame(){
             parent.jsReloadBase();
         }

        </script>
        <div id="pngCasinoGame"></div>
    </body>
</html>
