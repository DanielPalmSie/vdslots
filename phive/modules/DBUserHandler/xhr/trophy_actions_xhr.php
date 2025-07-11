<?php
require_once __DIR__ . '/../../../phive.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';

$user = cu();

switch($_REQUEST['action']){
    case 'woj-spin':
        list($win_slice, $slices) = phive('DBUserHandler/JpWheel')->spin($user);
        // Ex. we get back "noAward"
        if(is_string($win_slice)) {
            die(json_encode($win_slice));
        }
        unset($win_slice['probability']);

        // we need to handle the replacement on the description.
        $win_slice['award']['description'] = rep($win_slice['award']['description']);

        // If the image has not been set already, try to fetch the correct image
        if (empty($win_slice['award']['image'])) {
            $win_slice['award']['image'] = phive('Trophy')->getAwardUri($win_slice['award'], $user);
        }

        // If we still cannot load the correct image, load a placeholder, and log the error so the image can be uploaded
        if (empty($win_slice['award']['image'])) {
            $win_slice['award']['image'] = fupUri("events/alpha_1px.png", true);

            // Log the error
            phive('Logger')
                ->getLogger('casino')
                ->info("{$win_slice['award']['alias']} ({$win_slice['award']['id']}): Does not have an image", $win_slice['award']);
        }

        die(json_encode($win_slice));
        break;

    default:
        // Per default we run the trophy tab initialization logic here so it complies with the client side code which does not send an action.
        $tr = phive('Trophy');
        $gid = $_GET['game_id'];  // this is the game ref, like 'netent_starburst_sw'

        if(empty($user))
            die('no user');

        $uid = $user->getId();
        $trophies = $tr->getCurrentGameTrophies($uid, $gid);
        phive('Localizer')->setFromReq();
        foreach($trophies as &$t) {
            $t['trophyname'] = t('trophyname.'.$t['alias']);
            $t['trophydescription'] = rep(tAssoc('trophy.'.phive('Trophy')->getDescrStr($t).'.descr', $t), $user, true);
            $t['teid'] = $t['trophy_event_id'];
            $t['progress_for_sorting'] = (int)$t['progress_for_sorting'];
        }
        $user->mSet('trophy-tab-open', 'yes');
        $result = &$trophies;
        die(json_encode($result));
        break;
}
