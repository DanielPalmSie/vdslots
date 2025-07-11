<?php
$user = cu();
$country_data = phive('Localizer')->getCountryData();
$game_ref = $_POST['game_ref'] ?? phive('MicroGames')->getGameRefByIdWithAutoDeviceDetect($_POST['game_id']);
$js_data = json_encode(['game_ref'=> $game_ref, 'game_id' => $_POST['game_id'], 'sessionIndex' => $_POST['sessionIndex'] ?? -1]);

$popup = 'game_session_balance_set';
if (lic('hasAnOpenSession', [$user], $user)) {
    $popup = 'game_session_close_existing_and_open_new_session_prompt';
}
if (lic('hasGameSessionRestrictions', [$user], $user)) {
    $popup = 'game_session_temporary_restriction';
}
$gam_test_url = lic('getGamTestUrl', [$user, $country_data['langtag']], $user);
?>

<script>
    function continuePlaying() {
        if (window.extSessHandler) {
            window.extSessHandler.closePopup('game_session_limit_too_close_new_session_warning');
        } else {
            $.multibox('close', 'game_session_limit_too_close_new_session_warning');
        }

        lic('showExternalGameSessionPopup', ['<?= $popup ?>', <?= $js_data ?>]);
    }
</script>
<div class="session-balance-popup too-close-new-game-session-warning">
    <div class="session-balance-popup__part">
        <a target="_blank" href="https://jugarbien.es/" rel="noopener noreferrer"><img class="session-popup-logo" src="/diamondbet/images/logo_jugarbien.png"></a>
        <p><?php et('rg.info.game-session-limit.before-sixty-minutes'); ?></p>
        <p><?php et2('rg.info.game-session-limit.safety-concerns', ["self_assessment_test" => '<a target="_blank" rel="noopener noreferrer" href="' . $gam_test_url . '" ><strong>'.t('rg.info.game-session-limit.self.assessment') .'</strong></a>',
                'responsible_gambling' => '<a target="_blank" rel="noopener noreferrer" href ="'.phive('UserHandler')->getUserAccountUrl('responsible-gambling').'" ><strong>'.t('rg.info.game-session-limit.self.responsible_gambling') .'.</strong></a>']); ?></p>

        <div class="session-balance-popup__form">
            <div class="session-balance-popup__button-container">
                <?php btnDefaultL(t('continue'), '', "continuePlaying()", '', '') ?>
            </div>
        </div>
    </div>
</div>
