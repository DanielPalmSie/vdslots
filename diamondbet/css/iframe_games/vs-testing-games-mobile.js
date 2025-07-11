      var testingUrls = {
        // OK
        'netent': 'https://videoslots-static.casinomodule.com/games/starburst_mobile_html/game/starburst_mobile_html.xhtml?lobbyURL=https%3A%2F%2Fwww.videoslots.com%2Fsv%2Fmobile%2F&server=https%3A%2F%2Fvideoslots-game.casinomodule.com%2F&sessId=DEMO1546456605702-47322-EUR&operatorId=default&gameId=starburst_mobile_html_sw&lang=sv&integration=standard&keepAliveURL=',

        'netent-gonzo': 'https://videoslots-static.casinomodule.com/games/eldorado_mobile_html/game/eldorado_mobile_html.xhtml?lobbyURL=https%3A%2F%2Fwww.videoslots.com&server=https%3A%2F%2Fvideoslots-game.casinomodule.com%2F&sessId=DEMO1547552041292-39728-EUR&operatorId=default&gameId=eldorado_mobile_html_sw&lang=en&integration=standard&keepAliveURL=',

        // Wrong in landscape iOS, portrait OK
        'thunderkick': 'https://vs-game.thunderkick.com/gamelauncher/mobileLauncher/tk-fruittime?langIso=en_us&freeAccountCurrencyIso=EUR&lobbyUrl=https%3A%2F%2Fwww.videoslots.com&operatorId=2185',

        // Full Moon Romance
        'thunderkick-romance': 'https://vs-game.thunderkick.com/gamelauncher/mobileLauncher/tk-s1-g12?langIso=en_us&freeAccountCurrencyIso=EUR&lobbyUrl=https%3A%2F%2Fwww.videoslots.com&operatorId=2185',

        // OK
        // Book Of Death
        'playngo': 'https://www.videoslots.com/phive/modules/Micro/html/play_mobile.php?gref=playngo100310&url=https%3A%2F%2Fvideoslots-cw.playngonetwork.com%2Fcasino%2FContainerLauncher%3Fpid%3D118%26gid%3Dbookofdeadmobile%26lang%3Den_GB%26practice%3D0%26user%3Duser%3A%5B5207828%5D%3A4df777eb-1b44-64e8-aae0-00004da85efe%26mp_id%3D%26lobby%3Dhttps%3A%2F%2Fwww.videoslots.com%26embedmode%3Diframe%26div%3DpngCasinoGame%26channel%3Dmobile%26origin%3Dhttps%3A%2F%2Fwww.videoslots.com%2Fmobile%26id%3Dvs-game-container__iframe',

        // OK
        'playtech': 'https://pt-games.videoslots.com/8/platform/index.html?game=bfb&preferedmode=offline&language=&advertiser=ptt&fixedsize=1&',

        'pragmatic': 'https://demogamesfree.pragmaticplay.net/gs2c/html5Game.do?extGame=1&symbol=vs10egypt&gname=Ancient%20Egypt&jurisdictionID=UK&lobbyUrl=https%3A%2F%2Fwww.videoslots.com&mgckey=stylename@generic~SESSION@be2b552b-c527-4195-9e16-862d35f13dfe',

        // OK
        'greentube': 'https://nrgs-b2b.greentube.com.mt/Nrgs/B2B/Web/Videoslots/V5/Fun/Games/110/Sessions/F5CC94A0-BA16-4807-B08B-3D9613938862/Show/html5?ClientType=mobile-device',

        // OK
        'quickspin': 'https://d1k6j4zyghhevb.cloudfront.net/mcasino/videoslots/spinions/index.html?gameid=spinions&language=en_US&ticket=&moneymode=fun&mode=prod&partnerid=12',

        // OK
        'micro': 'https://mobile2.gameassists.co.uk/MobileWebGames_UK/game/mgs/9_0_1_6242?lobbyName=videoslots.uk&languageCode=en&casinoID=1867&loginType=VanguardSessionToken&bankingURL=https%3A%2F%2Fwww.videoslots.com%2Fmobile%2Fregister%2F%3F&gameName=immortalRomance&clientID=40300&moduleID=10145&clientTypeID=40&xmanEndPoints=https%3A%2F%2Fxplay2.gameassists.co.uk%2FXMan%2Fx.x&gameTitle=Immortal%20Romance&lobbyURL=https%3A%2F%2Fwww.videoslots.com%2Fmobile%2F%3F&helpURL=&isPracticePlay=true&isRGI=true&authToken=',
        
        // OK
        'redtiger': 'https://gserver-videoslots.tgp.cash/videoslots/launcher/JingleBells?playMode=demo&lang=en_US',

        // OK
        'yggdrasil': 'https://staticpff.yggdrasilgaming.com/slots/wolfhunters/index.24.html?gameid=7352&lang=en&channel=mobile&org=VideoSlots&curr=EUR&home=https%3A%2F%2Fwww.videoslots.com%2Fen%2Fmobile%2F&base=slots%2Fwolfhunters&appsrv=https%3A%2F%2Fpff.yggdrasilgaming.com&currency=EUR&boostUrl=%2Fboost%2Fboost.1.1.36.js',

        // bad WS
        'amatic': 'https://sgrator.amanetgames.com:10443/gmsl/mphome/amanet/arisingphoenix.html?exit=https%253A%252F%252Fwww.videoslots.com&lang=en&hash=freeplay',

        // OK
        'bsg': 'https://lobby-videoslots.betsoftgaming.com/free/en/launch.jsp?SID=2e4b4492309e276699df00000168a44c&GAMESERVERURL=gs3-sb.betsoftgaming.com&gameId=669&BANKID=382&homeUrl=https%3A%2F%2Fwww.videoslots.com%2Fmobile%2F&LANG=en&cashierUrl=https%3A%2F%2Fwww.videoslots.com%2Fmobile%2Fregister%2F',

        // Bonanza
        // OK, but problematic buttons placement for phones
        'btg': 'https://dpovs7i3r9tz1.cloudfront.net/html5/gcmwrapper.html?envid=eur&gameid=bonanza&operatorid=232&sessionid=Free%3Aon4r85fui4htk1r6jd01goou81j&currency=EUR&lang=en_us&mode=demo&secure=true&type=nextgen&clock=true&device=mobile&lobbyurl=https%3A%2F%2Fwww.videoslots.com%2Fen%2Fmobile%2F&depositurl=&nyxroot=ogs-gdm-mt.nyxop.net/demo/&',

        // blocked on the iPhone
        'edict': 'https://vid-egb.allianceservices.im/gamestart_mobile.html?gameKey=adp_eyeofhorus&templateName=default&gameMode=fun&playerName=&lang=en&casino=videoslots',

        // can not find any mobile game on the live
        'evolution': '',

        // blocked on devices
        // PPAP
        'ganapati': 'https://casino-eu1.ganalogics.net:8080/videoslots/launchGame?game=ppap&mode=fun&brand=videoslots&lobbyURL=https%3A%2F%2Fwww.videoslots.com',

        // blocked after load
        // Not existing on live
        // Apollo Rising
        'igt': 'https://platform.rgsgames.com/skb/gateway?nscode=VSLT&skincode=VS01&softwareid=200-1249-001&channel=MOB&presenttype=STD&technology=FLSH&currencycode=FPY&language=en_us',

        // OK, both the live and local
        // Three Blind Mice
        'leander': 'https://cdn.elf.leandergames.com/mobile/games/threeblindmice/index.html?siteId=videoslots&serverAddress=https%3A%2F%2Fapp.elf.leandergames.com%2F&apiAddress=https%3A%2F%2Fcdn.elf.leandergames.com%2Fmobile%2F&channel=mobile&language=ENG&locale=en&userLocale=en_us&gameId=THREEBLINDMICE7Y7&gameMode=FUN&currency=EUR&useGrouping=0&groupingSeparator=%2C&decimalSeparator=.&music=1&sfx=1&luid=GENMC43NzgwODkwMCAxNTQ2OTQ5MTA0&origin=&windowName=Three%20Blind%20Mice&msn=54m9ct4b8oanfbllbtbgkdiuv6&ssid=LMD-videoslots-THREEBLINDMICE7Y7-FUN&lv=4.3.003&&',


        // OK locally, iOS not keeping landscape on live
        'microgaming': 'https://mobile2.gameassists.co.uk/MobileWebGames_UK/game/MahiGaming/9_0_1_6242?lobbyName=videoslots.uk&languageCode=en&casinoID=1867&loginType=VanguardSessionToken&bankingURL=https%3A%2F%2Fwww.videoslots.com%2Fmobile%2Fregister%2F%3F&gameName=108heroes&clientID=40301&moduleID=12518&clientTypeID=40&xmanEndPoints=https%3A%2F%2Fxplay2.gameassists.co.uk%2FXMan%2Fx.x&gameTitle=108%20Heroes&lobbyURL=https%3A%2F%2Fwww.videoslots.com%2Fmobile%2F%3F&helpURL=&isPracticePlay=true&isRGI=true&authToken=',

        // OK, locally, live
        // Beehive Bedlam
        'nyx': 'https://fog-mt-videoslots.nyxop.net/mobile-games/BeehiveBedlam/index.html?gcmPlayMode=demo&gcmChannel=M&gcmGameName=BeehiveBedlam&gcmLang=en-GB&gcmDevice=mobile&gameid=f548ac8237fd4d41809ddf06c2f2f86c&clienttype=html5&lobbyurl=https://www.videoslots.com/en/mobile/&gameName=BeehiveBedlam&ogsLang=en_us&ipAddress=195.158.92.198&currency=EUR&sessionid=Free:g3u5qg9kar10kes839euah7o8mt&operatorid=232',


        // OK locally
        // Problem with Swipe
        // Alien Spinvasion
        'rival': 'https://www.casinocontroller.com/videoslotscom/html5/dist/?game_id=1104&playerid=&sessionid=&anon=1&anonOnly=1&resize=1',

        // blocked
        // Book of Cleopatra
        'stakelogic': 'https://www.videoslots.com/diamondbet/stakelogic.php?data=YTo0OntzOjM6InVpZCI7czowOiIiO3M6NDoibGFuZyI7czoyOiJlbiI7czo3OiJnYW1lVXJsIjtzOjEwNjoiaHR0cHM6Ly9uZ3MuZ3Mtc3Rha2Vsb2dpYy5jb20vYXBpL2dhbWUvMy0yMjMyMDk1NS0yMjU5NTUwMy0zNTI2NjgwNTM3LTEzNTc5NzNmLTNjMTktNDM1Yi1hY2U1LWM3YzE0MDE3MjEzMCI7czo1OiJqc1VybCI7czo2MzoiaHR0cHM6Ly9jZG4uZ3Mtc3Rha2Vsb2dpYy5jb20vbGlicy9nY3ctc3Rha2Vsb2dpYy0xLjEyLjMvZ2N3LmpzIjt9',

        // connection problem
        // Captain Shark
        'wazdan': 'https://gamelaunch.wazdan.com/dim6rscd/gamelauncher?operator=videoslots&game=278&platform=mobile&mode=demo&lobbyUrl=https%3A%2F%2Fwww.videoslots.com',

        // OK on local
        // Witch of the West
        'nektan': 'https://cdn.tgcn.io/ie/nektan-b2b/games/index.html?host=nektan-b2b&game=west&mode=demo&token=0&interface=demo-wallet&language=EN&ccy=EUR&variant=standard&version=stable'

      }

      var gameUrl = testingUrls['netent'];      

      // swipe thing
      // Fat Rabbit

      // netent
      // iPhone SE
      // landscape orientation, no minimal UI load
      // rotate the device


      // Testing the swipe gesture
      // load in landscape
      // swipe
      // rotate
      // go back
      // click top or bottom
      // swipe
      // repeat with portrait in start

      // bigger phones (6 Plus upwards)
      // test more tabs mode in landscape