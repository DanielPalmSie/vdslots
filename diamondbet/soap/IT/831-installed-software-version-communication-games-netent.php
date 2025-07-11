<?php

require_once '/var/www/videoslots.it/phive/phive.php';
$user = cu($argv[1] ?? 6624618);

$games = [
    [
        'code' => '47459',
        'name' => 'aloha-bundle-production-2.8.0.jar',
        'hash' => '9af1a23efd09bbcab46d4d194b5b06971c31aeb4',
    ],
    [
        'code' => '47465',
        'name' => 'arcane-bundle-production-1.1.0.jar',
        'hash' => 'ac050f42462788ca9c1c677ff7ab275874f4b39b',
    ],
    [
        'code' => '47467',
        'name' => 'archangels-bundle-production-1.0.0.jar',
        'hash' => 'fb6b209cc39e7d9d1ee9a21832ea44c8105ee34d',
    ],
    [
        'code' => '47466',
        'name' => 'asgardian-stones-bundle-production-1.5.0.jar',
        'hash' => 'd37760b8c5db737fc77235c3ed1a4d6c84bca74f',
    ],
    [
        'code' => '47485',
        'name' => 'berry-burst-bundle-production-1.3.0.jar',
        'hash' => 'c81750184530bdface999fc292dab56d78d97082',
    ],
    [
        'code' => '47481',
        'name' => 'blood-suckers-2-bundle-production-1.9.0.jar',
        'hash' => '74dc090d850325b822e840c17f76828bc8efed3a',
    ],
    [
        'code' => '47478',
        'name' => 'blood-suckers-bundle-production-1.5.0.jar',
        'hash' => '8e590a455e25284c196a8989c283eb09bda728f8',
    ],
    [
        'code' => '47488',
        'name' => 'butterfly-staxx-bundle-production-1.8.0.jar',
        'hash' => '5089debc1b9bbbff17b7033a9963b9ed6b6d4f6c',
    ],
    [
        'code' => '47489',
        'name' => 'butterfly-staxx-2-bundle-production-1.4.0.jar',
        'hash' => '84a0a8e709758ef0f5884f48b70bdbbf22b513c4',
    ],
    [
        'code' => '55107',
        'name' => 'cash-noire-bundle-production-1.3.0.jar',
        'hash' => '1fca780336862d1a01e80a2987ca66d3ba5d4426',
    ],
    [
        'code' => '47490',
        'name' => 'cashomatic-bundle-production-1.6.0.jar',
        'hash' => 'd71d0d9930cce2146786f641338593f4342fed53',
    ],
    [
        'code' => '47491',
        'name' => 'hidden-coins-of-egypt-bundle-production-1.0.3.jar',
        'hash' => '85e1a0db0df16d268d36d74ffa8940d672e6952a',
    ],
    [
        'code' => '47494',
        'name' => 'creature-from-the-black-lagoon-bundle-production-1.5.0.jar',
        'hash' => 'f8e1e2887c0b8ebada14e66c64c10a346edf8c3c',
    ],
    [
        'code' => '55117',
        'name' => 'darkking-bundle-production-1.1.0.jar',
        'hash' => 'fb4f98a919809c3f804d05ac0e1d5782aff4677e',
    ],
    [
        'code' => '47495',
        'name' => 'dazzle-me-bundle-production-2.7.0.jar',
        'hash' => '078f42f48e7a984e5afd0c846384433a37d54a12',
    ],
    [
        'code' => '47497',
        'name' => 'dead-or-alive-2-bundle-production-1.0.0.jar',
        'hash' => 'c89c06d2b588a31f9fc604aa917da738d92631f3',
    ],
    [
        'code' => '47496',
        'name' => 'dead-or-alive-bundle-production-1.3.0.jar',
        'hash' => '993f59f48a3ffbe9877d459ef66c2d3ff89174ec',
    ],
    [
        'code' => '55115',
        'name' => 'discodanny-bundle-production-1.2.0.jar',
        'hash' => '24fff31254bf9f0ba18d5795a4e44fc39270bd3b',
    ],
    [
        'code' => '47500',
        'name' => 'doublestacks-bundle-production-1.0.2.jar',
        'hash' => '78569c87f43978b89036fc6031248b2554594234',
    ],
    [
        'code' => '47502',
        'name' => 'multiplier-mayhem-bundle-production-1.6.0.jar',
        'hash' => 'dc697198ef4aa3b17e5aced0102a135a14df2cf9',
    ],
    [
        'code' => '47504',
        'name' => 'eastseadragonking-bundle-production-1.0.0.jar',
        'hash' => '46a10e14a76763a155c43874b1e6227fa5373fd6',
    ],
    [
        'code' => '47510',
        'name' => 'eggomatic-bundle-production-2.0.1.jar',
        'hash' => 'adfea932a33e24523f97759d548ae10a40fa3e30',
    ],
    [
        'code' => '47521',
        'name' => 'elements-bundle-production-2.2.0.jar',
        'hash' => '81cec58e419ada1028ab695ecad3d960bac1d4dc',
    ],
    [
        'code' => '47530',
        'name' => 'excalibur-bundle-production-1.4.0.jar',
        'hash' => '96e1180ef4a2e9a490c32576803edd71c98f0534',
    ],
    [
        'code' => '47532',
        'name' => 'fairy-hansel-bundle-production-1.0.0.jar',
        'hash' => 'fd5371c1dfd9320a7b0b3f323be8105d863430d9',
    ],
    [
        'code' => '47534',
        'name' => 'fairymirror-bundle-production-1.5.0.jar',
        'hash' => 'c3f3b057cc2f3d7e6cf33c1be7a3d99385ed9213',
    ],
    [
        'code' => '47536',
        'name' => 'fairyred-bundle-production-1.7.0.jar',
        'hash' => 'ca7433a96bafcb0038bb3a9c659e4f0cca5d9575',
    ],
    [
        'code' => '47537',
        'name' => 'finn-bundle-production-3.3.0.jar',
        'hash' => '09fd2e02c516168b463b273b01bb7e3b6ed73e1a',
    ],
    [
        'code' => '47538',
        'name' => 'goldentavern-bundle-production-1.3.0.jar',
        'hash' => '2de4b8863e0b7243280436df49b82556698060cf',
    ],
    [
        'code' => '47541',
        'name' => 'flowers-bundle-production-2.3.0.jar',
        'hash' => '0702c92812e65cd1b73c0c0a9499ab71e553c35b',
    ],
    [
        'code' => '47542',
        'name' => 'footballchampionscup-bundle-production-1.0.3.jar',
        'hash' => 'a8bd81c853285620dde42448317a7ae5875d7863',
    ],
    [
        'code' => '47544',
        'name' => 'fortune-rangers-bundle-production-1.2.0.jar',
        'hash' => '0e7fcbd930c98651dfe6b8958908a0c9366fadf2',
    ],
    [
        'code' => '47548',
        'name' => 'fruit-case-bundle-production-1.4.0.jar',
        'hash' => '268c32d8d9bf373ab1225ad0828c1935e77f64e4',
    ],
    [
        'code' => '47549',
        'name' => 'fruit-shop-christmas-edition-bundle-production-2.0.1.jar',
        'hash' => '14dfc8e079b9d7aa8c1c74b6015972db18fe6c10',
    ],
    [
        'code' => '47553',
        'name' => 'fruit-shop-bundle-production-2.6.0.jar',
        'hash' => '2abb1a8461704d1bdca0cf34b3bebb67348ac7aa',
    ],
    [
        'code' => '47554',
        'name' => 'jewelfruits-bundle-production-1.10.0.jar',
        'hash' => '071d52fe01433c956edf58fa5a84c27397963797',
    ],
    [
        'code' => '47555',
        'name' => 'ghost-pirates-bundle-production-2.0.0.jar',
        'hash' => 'cbf93c9518220bc5d8574ee087a6e1517f2fc0cf',
    ],
    [
        'code' => '62408',
        'name' => 'godsofgold-bundle-production-1.7.0.jar',
        'hash' => '6f2debd2c40e915567b46114be253438f0b37e3b',
    ],
    [
        'code' => '47563',
        'name' => 'goldengrimoire-bundle-production-1.0.1.jar',
        'hash' => 'cf067b5382f16ce2629858b0c4d5da43631781fb',
    ],
    [
        'code' => '47567',
        'name' => 'grandspinn-bundle-production-1.10.0.jar',
        'hash' => '74ce8504fe5b45ac7ac1211a8a9f681abb9c5cf6',
    ],
    [
        'code' => '47568',
        'name' => 'guns-n-roses-bundle-production-2.6.0.jar',
        'hash' => '3908cd40ed7642af3c826ac490c6d390854c16d8',
    ],
    [
        'code' => '47569',
        'name' => 'halloweenjack-bundle-production-1.0.0.jar',
        'hash' => '6581bd07133f3926ebf8e8391f9b7de6ea8281bb',
    ],
    [
        'code' => '55110',
        'name' => 'happyriches-bundle-production-1.0.0.jar',
        'hash' => '244c1c0754a55e0f9355913e6c91a3bcaaab257e',
    ],
    [
        'code' => '47571',
        'name' => 'hotline-bundle-production-1.4.0.jar',
        'hash' => '0a02844b0f1738f35f03774869240d4f2be3bd9e',
    ],
    [
        'code' => '55113',
        'name' => 'irishpotluck-bundle-production-1.5.0.jar',
        'hash' => '3255048c8d72435723bf28f4041d591e9e328a54',
    ],
    [
        'code' => '47574',
        'name' => 'jack-hammer-2-bundle-production-2.2.0.jar',
        'hash' => 'b17f42a037112423a943eba5cdc53c13070801ec',
    ],
    [
        'code' => '47575',
        'name' => 'jack-hammer-bundle-production-2.7.0.jar',
        'hash' => '715254901e10b82979d020eb8a872385956f86eb',
    ],
    [
        'code' => '47578',
        'name' => 'jimi-hendrix-bundle-production-1.4.0.jar',
        'hash' => '3b65353f94b8b9b057dc9c155101896b4471bc33',
    ],
    [
        'code' => '47579',
        'name' => 'jinglespin-bundle-production-1.0.1.jar',
        'hash' => 'aa2fbcbdd20ca5e864f1ac852508d53a4046f718',
    ],
    [
        'code' => '47581',
        'name' => 'jumanji-bundle-production-2.0.1.jar',
        'hash' => 'e14b92d2653e4f5857d6c9fba14112561a7521c8',
    ],
    [
        'code' => '47584',
        'name' => 'kingof3kingdoms-bundle-production-1.2.0.jar',
        'hash' => '2f4abb717bdb89a867721e4f3b6c480e0a539919',
    ],
    [
        'code' => '47588',
        'name' => 'koi-princess-bundle-production-2.7.0.jar',
        'hash' => 'a796506b1f5fc4deedc114fb8ae01c0fe6670966',
    ],
    [
        'code' => '47589',
        'name' => 'lights-bundle-production-2.3.0.jar',
        'hash' => 'a722bf2bf4beeb0fdae679bafbb50e14e568d9e1',
    ],
    [
        'code' => '47590',
        'name' => 'longpao-bundle-production-1.6.0.jar',
        'hash' => 'adff5d47857d4355bca3f2d3dfab8c7e0a5508d3',
    ],
    [
        'code' => '62426',
        'name' => 'magicmaidcafe-bundle-production-1.0.0.jar',
        'hash' => '29ea439a15bc2b48c794744bb050a7117e0beeb4',
    ],
    [
        'code' => '47601',
        'name' => 'motorhead-bundle-production-2.0.0.jar',
        'hash' => '82cd809df9148b49fad90f41bee29ea4c95acc2a',
    ],
    [
        'code' => '47602',
        'name' => 'mythic-maiden-bundle-production-2.3.0.jar',
        'hash' => '9a11b27647ed74d080aa54f4e97a477fde088875',
    ],
    [
        'code' => '47603',
        'name' => 'neon-staxx-bundle-production-1.0.0.jar',
        'hash' => 'abcad1157fdb723ed5987723f61373393acdd742',
    ],
    [
        'code' => '47604',
        'name' => 'oceanstreasure-bundle-production-1.4.0.jar',
        'hash' => '38de1851090c2ffdaf85b3c8f712cad42fc64a5a',
    ],
    [
        'code' => '47605',
        'name' => 'ozzy-bundle-production-1.0.0.jar',
        'hash' => '162c103e78613eb19fdba949c18d3a39c1f3ea55',
    ],
    [
        'code' => '47606',
        'name' => 'piggy-riches-bundle-production-1.4.0.jar',
        'hash' => '3a8fd55466aae9fe7e58724e657965a3391f7098',
    ],
    [
        'code' => '47609',
        'name' => 'pyramid-bundle-production-1.15.0.jar',
        'hash' => '0f6a1d48211a3ab32f464482b8f3d3eaeb8a900b',
    ],
    [
        'code' => '55114',
        'name' => 'rage-of-the-seas-bundle-production-1.8.0.jar',
        'hash' => '9b8ed5ea47db8c386146dbe452083cde7a6bddca',
    ],
    [
        'code' => '47610',
        'name' => 'reel-rush-2-bundle-production-1.21.0.jar',
        'hash' => '396ec0b32cc6e29f2adf3b42f8fcdab4f5be7e7c',
    ],
    [
        'code' => '47621',
        'name' => 'reel-steal-bundle-production-1.3.0.jar',
        'hash' => '13e3c76e94c92451099e4408622c0e190015d296',
    ],
    [
        'code' => '47622',
        'name' => 'riseofmaya-bundle-production-1.8.0.jar',
        'hash' => '05e09335ac991135a8d927d9ecebb946ce4f4335',
    ],
    [
        'code' => '47623',
        'name' => 'robin-hood-bundle-production-3.23.0.jar',
        'hash' => '6b8ba74c3d352599f130054c7b0c86fa6df4c40f',
    ],
    [
        'code' => '47630',
        'name' => 'santavsrudolf-bundle-production-1.5.0.jar',
        'hash' => 'ef8d65ab06256a92ff2ecd21fa99358bfac3537a',
    ],
    [
        'code' => '47631',
        'name' => 'scruffy-duck-bundle-production-1.0.1.jar',
        'hash' => 'e1d2d8882dec9e279f6ce8b8ac39dd4f737ba3b5',
    ],
    [
        'code' => '47632',
        'name' => 'scudamore-bundle-production-1.13.0.jar',
        'hash' => '4a1e233d2cbe79b8f1f3b89d45faf371ef5a4e5e',
    ],
    [
        'code' => '47634',
        'name' => 'highlights-bundle-production-1.0.2.jar',
        'hash' => '541170cce002bb8d7226d1284a1186b0e516381e',
    ],
    [
        'code' => '47635',
        'name' => 'secrets-of-christmas-bundle-production-1.0.0.jar',
        'hash' => '0cde5b6b0ab6c2116c34a75e5a3f1b30559db2bc',
    ],
    [
        'code' => '47636',
        'name' => 'serengetikings-bundle-production-1.5.0.jar',
        'hash' => 'bea418e16a743c2d611a1405e8379fce3664d346',
    ],
    [
        'code' => '47637',
        'name' => 'space-wars-bundle-production-1.3.0.jar',
        'hash' => '56c1872483e472a27ffdeb7bfbbaec426d5df334',
    ],
    [
        'code' => '47643',
        'name' => 'steam-tower-bundle-production-2.8.0.jar',
        'hash' => 'c5703057dadc668c54a9e3d0733dcab147461a69',
    ],
    [
        'code' => '47644',
        'name' => 'stickers-bundle-production-1.0.1.jar',
        'hash' => '721c49b68441bce22d59e78caa941983898bb885',
    ],
    [
        'code' => '47645',
        'name' => 'strollingstaxx-bundle-production-1.0.2.jar',
        'hash' => '8ebb1d73d04a5ce8294fe3b7fde4b21bbcd69892',
    ],
    [
        'code' => '47646',
        'name' => 'superstriker-bundle-production-1.3.0.jar',
        'hash' => 'ad321593419cf0e27d2d154253e5bb48ba9cdbcf',
    ],
    [
        'code' => '47655',
        'name' => 'shangrila-bundle-production-2.0.1.jar',
        'hash' => '54a923cf8dc129f0c3dd0bd30eb16458fd6b013a',
    ],
    [
        'code' => '47656',
        'name' => 'wishmasteroct-bundle-production-1.0.0.jar',
        'hash' => 'a68c0d2b40dd174800a0f5263a8a66b60eb9d1c8',
    ],
    [
        'code' => '47657',
        'name' => 'thewolfsnight-bundle-production-1.0.0.jar',
        'hash' => '9f1a295e0fa3a248ac5d8f68b075b96c0343442b',
    ],
    [
        'code' => '55109',
        'name' => 'trollpot-bundle-production-1.1.0.jar',
        'hash' => '14635fe667a1dc052b5fe2987b67b6c9a6878901',
    ],
    [
        'code' => '47660',
        'name' => 'twin-happiness-bundle-production-0.2.0.jar',
        'hash' => 'da5c8a50544f51fdc49d7052e68970083100b106',
    ],
    [
        'code' => '47662',
        'name' => 'twin-spin-bundle-production-2.9.0.jar',
        'hash' => '615bad0fd3ef79b38b4c9d60732b18ce0748e00f',
    ],
    [
        'code' => '57916',
        'name' => 'twinspinmw-bundle-production-1.3.0.jar',
        'hash' => 'c8492c92eb33e1f8bbdd5dca396f032334ec305d',
    ],
    [
        'code' => '47672',
        'name' => 'vikings-bundle-production-1.3.0.jar',
        'hash' => 'e825abbf96a5b1b192823304534dd6483092c30e',
    ],
    [
        'code' => '47673',
        'name' => 'warlords-bundle-production-1.3.0.jar',
        'hash' => 'bb955c7b8b717b4fa54344fdd2a37dac2c9f5323',
    ],
    [
        'code' => '47674',
        'name' => 'whos-the-bride-bundle-production-1.0.0.jar',
        'hash' => '486ba330725cd607b826c2e3f0e17e9616a03875',
    ],
    [
        'code' => '47675',
        'name' => 'wildbazaar-bundle-production-1.0.2.jar',
        'hash' => 'b3659b19df83c63695fc43012163cbf6dbea944b',
    ],
    [
        'code' => '47676',
        'name' => 'wild-turkey-bundle-production-1.6.0.jar',
        'hash' => 'b4a16fb35a3c480978d7d7d0948442b1af2fe4f5',
    ],
    [
        'code' => '47678',
        'name' => 'wild-wild-west-bundle-production-1.5.0.jar',
        'hash' => '6d9820aa240cde442093addb045e6df32a2c5762',
    ],
    [
        'code' => '47679',
        'name' => 'wildworlds-bundle-production-1.2.0.jar',
        'hash' => 'c21673c9040e8ed324ff909b665956646dd21e0d',
    ],
    [
        'code' => '47681',
        'name' => 'wildotron3000-bundle-production-1.0.1.jar',
        'hash' => '04b68d6b04041008227490f69fab84e1eff84236',
    ],
    [
        'code' => '47680',
        'name' => 'wilderland-bundle-production-1.6.0.jar',
        'hash' => 'f30ee0a68d863fcf58b81e8c5dfad15045e6a715',
    ],
    [
        'code' => '55111',
        'name' => 'willyshotchillies-bundle-production-1.6.0.jar',
        'hash' => '2489e07142d90aa106b38791e43a228560c8c36e',
    ],
    [
        'code' => '47682',
        'name' => 'wingsofriches-bundle-production-1.3.0.jar',
        'hash' => '073b5ef2372d6f4cd7b2abad2140f0eb5148ecaa',
    ],
    [
        'code' => '47684',
        'name' => 'wolf-cub-bundle-production-1.0.0.jar',
        'hash' => 'dd508068b5e6d743636ca796415f0d997a00f7d1',
    ],
    [
        'code' => '47501',
        'name' => 'dracula-bundle-production-2.0.2.jar',
        'hash' => '8197d55a480d41de5bf806e4877ea4bfcb87f388',
    ],
    [
        'code' => '47503',
        'name' => 'druidsdream-bundle-production-1.5.0.jar',
        'hash' => '031aca8317f15d5741e6948a4dd334d48f2afd31',
    ],
    [
        'code' => '47641',
        'name' => 'spinsane-bundle-production-1.3.0.jar',
        'hash' => 'e7bdc2baf60c42652e357a519f2156296495efb1',
    ],
    [
        'code' => '47642',
        'name' => 'starburst-bundle-production-3.4.0.jar',
        'hash' => '2d99654352662cbbd84584243ab05015bc65a59f',
    ],
    [
        'code' => '71892',
        'name' => 'dazzlemw-bundle-production-2.18.0.jar',
        'hash' => '31f98d648a4bd5701d601dd4bda6f7c932a06bb7',
    ],
    [
        'code' => '71896',
        'name' => 'starburstxxxtreme-bundle-production-1.2.0.jar',
        'hash' => 'bd5e0f66538d530399378dabe8b2879104d11a63',
    ],
    [
        'code' => '71895',
        'name' => 'reefraider-bundle-production-1.8.0.jar',
        'hash' => 'ea454883be85384558d43dc1f5912889305de031',
    ],
    [
        'code' => '71894',
        'name' => 'parthenon-bundle-production-1.10.0.jar',
        'hash' => 'dbe5796d7e63bfe8799c0cbea26cc19c4301869d',
    ],
    [
        'code' => '71893',
        'name' => 'codexoffortune-bundle-production-1.17.0.jar',
        'hash' => 'd2c863848d0183067dad7e7c53936d07899d48d2',
    ],
    [
        'code' => '84863',
        'name' => 'twin-spin-bundle-production-2.11.0.jar',
        'hash' => '82b37c8d84e47d15dd2d9b897c8f0592124a47e9',
    ],
    [
        'code' => '84862',
        'name' => 'steam-tower-bundle-production-2.8.0.jar',
        'hash' => 'c5703057dadc668c54a9e3d0733dcab147461a69',
    ],
    [
        'code' => '84861',
        'name' => 'starburst-bundle-production-3.4.0.jar',
        'hash' => '2d99654352662cbbd84584243ab05015bc65a59f',
    ],
    [
        'code' => '84860',
        'name' => 'reel-rush-bundle-production-2.11.0.jar',
        'hash' => 'b8f1340d77813ec0715cbb9539b2d42dbb1787a8',
    ],
    [
        'code' => '84859',
        'name' => 'jack-hammer-bundle-production-2.9.0.jar',
        'hash' => 'b41db34cb0771a26921854d942cf6a62cb7dba35',
    ],
    [
        'code' => '84858',
        'name' => 'guns-n-roses-bundle-production-2.6.0.jar',
        'hash' => '3908cd40ed7642af3c826ac490c6d390854c16d8',
    ],
    [
        'code' => '84857',
        'name' => 'fruit-shop-bundle-production-2.9.0.jar',
        'hash' => '6f71e5f5ca52c9853455140848456f067f368d7f',
    ],
    [
        'code' => '84856',
        'name' => 'dead-or-alive-bundle-production-1.5.0.jar',
        'hash' => '91038c4bd6880b89416ed99a16d8ab2b3dcdd0d6',
    ],
    [
        'code' => '84855',
        'name' => 'dazzlemw-bundle-production-2.18.0.jar',
        'hash' => '31f98d648a4bd5701d601dd4bda6f7c932a06bb7',
    ],
    [
        'code' => '84854',
        'name' => 'dazzle-me-bundle-production-2.7.0.jar',
        'hash' => '078f42f48e7a984e5afd0c846384433a37d54a12',
    ],
    [
        'code' => '84853',
        'name' => 'butterfly-staxx-bundle-production-1.8.0.jar',
        'hash' => '5089debc1b9bbbff17b7033a9963b9ed6b6d4f6c',
    ],
    [
        'code' => '84852',
        'name' => 'blood-suckers-bundle-production-1.5.0.jar',
        'hash' => '8e590a455e25284c196a8989c283eb09bda728f8',
    ],
    [
        'code' => '84851',
        'name' => 'aloha-bundle-production-2.11.0.jar',
        'hash' => '9e29e58167f58e753c7e917da59a1e35d2118fb8',
    ],
    [
        'code' => '47492',
        'name' => 'conan-bundle-production-1.1.0.jar',
        'hash' => '2d2c69bc5908b67918b24b6bda1e6d3fcc642c32',
    ],
    [
        'code' => '55116',
        'name' => 'dead-or-alive-2-fb-bundle-production-1.4.0.jar',
        'hash' => 'eec6a7a4054b501da3b72fde490a02f7d943a513',
    ],
    [
        'code' => '47633',
        'name' => 'secret-of-the-stones-bundle-production-1.17.0.jar',
        'hash' => '10b2244c0bd9fe4984d1138f9d37862fbe9d2624',
    ],
    [
        'code' => '47647',
        'name' => 'sweetyhoneyfruity-bundle-production-1.0.0.jar',
        'hash' => '08ab506b08da2279fab8d2f58033ec587a17e330',
    ],
    [
        'code' => '47670',
        'name' => 'victorious-bundle-production-2.5.0.jar',
        'hash' => 'b2c3a901593c69df0c9cf7db54ec1f45a2f3a502',
    ],
    [
        'code' => '55112',
        'name' => 'hotline2-bundle-production-1.17.0.jar',
        'hash' => 'fd9a036af836a3de63d0f169cbb3e09b78e2e879',
    ],
    [
        'code' => '47573',
        'name' => 'jack-and-the-beanstalk-bundle-production-2.4.0.jar',
        'hash' => '5e1e6d1265c51afd43b01c70bc6b0b73dcc4e549',
    ],
    [
        'code' => '47580',
        'name' => 'joker-pro-bundle-production-1.0.0.jar',
        'hash' => '4c57147239d2994a7253bde6faaa8d2bc263954f',
    ],
    [
        'code' => '45016',
        'name' => 'narcos-bundle-production-1.21.0.jar',
        'hash' => '83e46102feeee75906c4d16218cf83b444351e03',
    ],
    [
        'code' => '47640',
        'name' => 'spinata-grande-bundle-production-2.7.0.jar',
        'hash' => '73523acdf0f346fb65eea3a9865964204328b946',
    ],
    [
        'code' => '47649',
        'name' => 'templeofnudges-bundle-production-1.18.0.jar',
        'hash' => '767c8e6f28c426c9429c0939a0d601a9037dc575',
    ],
    [
        'code' => '47651',
        'name' => 'the-invisible-man-bundle-production-2.5.0.jar',
        'hash' => 'f7f79407eaaae86dc28a77092a464941a1dd4868',
    ],
    [
        'code' => '79274',
        'name' => 'silverback-bundle-production-1.1.0.jar',
        'hash' => 'db64ef92f47923e70ef8e38e3720a514a2378717',
    ],
    [
        'code' => 95383,
        'name' => 'beehivebonanza-bundle-production-1.4.0.jar',
        'hash' => 'e7137b2f13b7c3336d8ed5f7fca67aa5f62e43c4',
    ],
    [
        'code' => 103190,
        'name' => 'spacewars2-bundle-production-1.2.0.jar',
        'hash' => '1794ece73b4b84396ff624c0687dc258f91087ad',
    ],
    [
        'code' => 103191,
        'name' => 'funkmaster-bundle-production-1.1.0.jar',
        'hash' => '87b90873376e89661550a2162449d8d0a7072e11',
    ],
    [
        'code' => 103192,
        'name' => 'knightrider-bundle-production-1.3.0.jar',
        'hash' => 'c3f18d4498b12063707bc488cf5206ec486f3761',
    ],
    [
        'code' => 103193,
        'name' => 'fairyred-bundle-production-1.7.0.jar',
        'hash' => 'ca7433a96bafcb0038bb3a9c659e4f0cca5d9575',
    ],
    [
        'code' => 103189,
        'cert_ver' => 1,
        'name' => 'cornelius-bundle-production-1.6.0.jar',
        'hash' => 'fc4024fe3fc09d35abd88e5d339f676dffc768fa',
    ],
    [
        'code' => 103194,
        'cert_ver' => 1,
        'name' => 'wondersofchristmas-bundle-production-1.0.0.jar',
        'hash' => 'a3ad0eac07a3e2c2385b1c286ffd9810409305e2',
    ],
    [
        'code' => 47583,
        'cert_ver' => 2,
        'name' => 'jungle-spirit-bundle-production-1.5.0.jar',
        'hash' => '048b8c40c676ea55ea3ce78c98d0d89aaa49b29c',
    ],
    [
        'code' => 47586,
        'cert_ver' => 2,
        'name' => 'king-of-slots-bundle-production-3.2.0.jar',
        'hash' => '4b820ba7cca2fd94768875b5e20fff5b64306cdf',
    ],
    [
        'code' => 47591,
        'cert_ver' => 2,
        'name' => 'lostrelics-bundle-production-1.16.0.jar',
        'hash' => '66c9a6ab154ab629252d6e9a6146a55eda246af0',
    ],
    [
        'code' => 47659,
        'cert_ver' => 2,
        'name' => 'turnyourfortune-bundle-production-1.15.0.jar',
        'hash' => '811a14442cd4f1d4d239ec0a47f14453746a9c82',
    ],
    [
        'code' => 47677,
        'cert_ver' => 2,
        'name' => 'wild-water-bundle-production-2.12.0.jar',
        'hash' => '3d912ed728221c018235e166f14a15c91f92ca5d',
    ],
    [
        'code' => 57917,
        'cert_ver' => 2,
        'name' => 'richesofmidgard-bundle-production-1.13.0.jar',
        'hash' => '474e6fba821d3217b30ecb645a697679e33cf1ae',
    ],
    [
        'code' => 55108,
        'cert_ver' => 2,
        'name' => 'streetfighter2-bundle-production-2.11.0.jar',
        'hash' => '3b5ee0606ef282096d24ba215627f4e9f73bd770',
    ],
    [
        'code' => 47564,
        'cert_ver' => 2,
        'name' => 'gonzos-quest-bundle-production-2.6.0.jar',
        'hash' => 'dd466c80d7b91262eb26bce35f735093477f1c1d',
    ],
    [
        'code' => 47565,
        'cert_ver' => 2,
        'name' => 'gorilla-kingdom-bundle-production-1.3.0.jar',
        'hash' => '46a12ecb9d1f094872158772902ca6d94058622d',
    ],
    [
        'code' => 112234,
        'cert_ver' => 1,
        'name' => 'milkshakeextreme-bundle-production-1.6.0.jar',
        'hash' => '78c083e388b37844c71ccbe7954d7a430bd3d5bd',
    ],
    [
        'code' => 126544,
        'cert_ver' => 1,
        'name' => 'letitburn-bundle-production-1.1.0.jar',
        'hash' => '6113a10b0f61019bb97eead2a3dbe6729daedf37',
    ],
    [
        'code' => 126545,
        'cert_ver' => 1,
        'name' => 'bustersbones-bundle-production-1.3.0.jar',
        'hash' => '29a93aebe4af1aceab6e72677efd84dddb868fbf',
    ],
    [
        'code' => 126546,
        'cert_ver' => 1,
        'name' => 'tacofury-bundle-production-1.1.0.jar',
        'hash' => '6e9e1c8d1fa9fd92734baa71cae8139d36b9a889',
    ]
];

foreach ($games as $game) {
    $i = 0;
    if (!empty($game['software_modules'])) {
        foreach ($game['software_modules'] as $module) {
            $software_modules[$i]['lun_nome_modulo_critico'] = strlen($game['software_modules'][$i]['name']);
            $software_modules[$i]['name_critical_module'] = $game['software_modules'][$i]['name'];
            $software_modules[$i]['hash_critical_module'] = $game['software_modules'][$i]['hash'];
            $i++;
        }


    } else {
        $software_modules = [
            [
                'name_critical_module' => $game['name'],
                'hash_critical_module' => $game['hash']
            ]
        ];
    }
    if (!empty($game['cert_ver'])) {
        $cert_vers = $game['cert_ver'];
    } else {
        $cert_vers = 1;
    }

    $payload = [
        'game_code' => 0,
        'game_type' => 0,
        'cod_element_type' => 2,
        'cod_element' => $game['code'],
        'prog_cert_version' => $cert_vers,
        'prog_sub_cert_version' => 0,
        'ctr_lista_moduli_sw' => count($software_modules),
        'software_modules' => $software_modules
    ];

    unset($software_modules);

    $response = lic('installedSoftwareVersionCommunication', [$payload], $user);

    if ($response['code'] !== 0) {
        print_r($payload);
        print_r($response);
    }

    sleep(2);
}

echo "Script complete";

