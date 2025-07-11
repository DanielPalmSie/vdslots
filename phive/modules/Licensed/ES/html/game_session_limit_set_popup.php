<?php
$user = cuPl();
$game_ref = $_POST['game_ref'] ?? phive('MicroGames')->getGameRefByIdWithAutoDeviceDetect($_POST['game_id']);

// We close the existing open session, so once we complete the popup a new session will be spawned.
if ($_POST['has_open_session']) {
    lic('endOpenParticipation', [$user, true], $user);
}
?>

<div id="game_session_balance_set" class="session-balance-popup">
    <div class="session-balance-popup__part">
        <img src="/diamondbet/images/session-balance-setup.png">
        <h3><?= t('rg.info.game-session-limit.title') ?></h3>
        <p><?= t('rg.info.game-session-limit.description') ?></p>
    </div>
    <div class="session-balance-popup__part">
        <form class="session-balance-popup__form session-balance-popup__form--gray">
            <div class="session-balance-popup__form-field">
                <label for="game-limit">
                    <?= t("game.limit") ?>
                </label>
                <div class="select-item styled-select">
                    <?php
                    dbSelect(
                        'game-limit',
                        ['0' => t('rg.info.limits.select'), '60' => '1 ' . t('hour'), '120' => '2 ' . t('hours'), '240' => '4 ' . t('hours'), '600' => '10 ' . t('hours')],
                        ['0' => t('rg.info.limits.select')],
                        [],
                        'padding-placeholder',
                        false,
                        "id='game-limit' onchange='handleGameInput()'"
                    )
                    ?>
                </div>
                <span class="hidden set-session-balance-time-limit error"><?= t('rg.info.game-session-limit.time-limit-reached-error') ?></span>
            </div>
            <div class="session-balance-popup__form-field">
                <label for="spend-limit">
                    <?= t("spend.limit") ?>
                    <div>(<?= cs() ?>)</div>
                </label>
                <input placeholder="<?= t('rg.info.limits.type') ?>"
                       class="input-normal big-input full-width flat-input" type="number" step="any"
                       name="spend-limit"
                       id="spend-limit"
                       required
                       onchange='handleSpendingLimit()'
                />
                <span class="hidden set-session-balance-spend-limit-insufficient error"><?= t('rg.info.game-session-balance.insufficient') ?></span>
                <span class="hidden set-session-balance-spend-limit error"><?= t('rg.info.game-session-balance.over-limit') ?></span>
                <span class="hidden set-session-balance-spend-limit-over-balance error"><?= t('rg.info.game-session-balance.over-balance') ?></span>
            </div>
            <div class="session-balance-popup__form-field">
                <label for="set-reminder">
                    <?= t("set.reminder") ?>
                </label>
                <div class="select-item styled-select">
                    <?php
                    dbSelect(
                        'set-reminder',
                        ['0' => t('rg.info.limits.select'), '5' => '5 ' . t('minutes'), '10' => '10 ' . t('minutes'), '15' => '15 ' . t('minutes')],
                        ['0' => t('rg.info.limits.select')],
                        [],
                        'padding-placeholder',
                        false,
                        "id='set-reminder' onchange='handleReminderInput()'"
                    )
                    ?>
                </div>
                <span class="reminder-max-minutes"><?= t('rg.info.limits.reminders.15_minutes_max') ?></span>
                <span class="hidden set-session-set-reminder-error error"><?= t('set-session-set-reminder-error') ?></span>
                <span class="hidden set-session-set-reminder-max-length error"><?= t('set-session-set-reminder-max-length-error') ?></span>
                <span class="hidden set-session-set-reminder-greater-than error"><?= t('set-session-set-reminder-greater-than-limit') ?></span>
            </div>
            <div class="session-balance-popup__form-field">
                <label for="restrict-future-sessions">
                    <?= t("rg.info.limits.restricts.future_sessions") ?>
                </label>
                <div class="select-item styled-select">
                    <?php
                    dbSelect(
                        'restrict-future-sessions',
                        ['0' => t('rg.info.limits.restricts.not_be_restricted'), '60' => '1 ' . t('hour'), '1440' => '24 ' . t('hours'), '10080' => '1 ' . t('week'), '43200' => '1 ' . t('month')],
                        ['0' => t('rg.info.limits.restricts.not_be_restricted')],
                        [],
                        'padding-placeholder',
                        false,
                        "id='restrict-future-sessions'"
                    )
                    ?>
                </div>
                <span class="optional-check-description"><?= t('rg.info.game-session-limit.optional-check-description') ?></span>
                <span class="hidden set-session-restrict-future-session-error error"><?= t('set-session-restrict-future-session-error') ?></span>
            </div>
            <div class="session-balance-popup__button-container">
                <?php btnDefaultL(t('play.now'), '', "submitDepositBalance(event)", '', 'set-session-balance-button') ?>
            </div>
            <span class="hidden set-session-create-session-generic-error error"><?= t('set-session-create-session-generic-error') ?></span>
        </form>
    </div>
</div>

<script>
    function submitDepositBalance(e) {
        e.preventDefault();
        var gameLimit = $('#game-limit').val();
        var spendLimit = $('#spend-limit').val();
        var setReminder = $('#set-reminder').val();
        var restrictFutureSessions = $('#restrict-future-sessions').val();

        window.extSessHandler = window.extSessHandler || lic('extSessHandler', []);
        var sessionIndex = <?= $_POST['sessionIndex'] ?? -1 ?>;
        window.extSessHandler.submitBalance(0, gameLimit, spendLimit, setReminder, restrictFutureSessions, '<?= $game_ref ?>', function () {
            if (sessionIndex >= 0) {
                window.extSessHandler.closePopup('game_session_balance_set');

                if (isMobile()) {
                    playMobileGameShowLoader('<?=$game_ref?>', undefined, 2000);
                } else {
                    window.extSessHandler.loadGame(sessionIndex);
                }
            } else {
                // Session is set correctly, we redirect to game page.
                isMobile() ? playMobileGameShowLoader('<?=$game_ref?>') : playGameDepositCheckBonus('<?=$game_ref?>');
            }
        });
    }

    function handleCheckboxClick(e) {
        if (e.checked) {
            $("#restrict-future-sessions").prop("disabled", false);
        } else {
            $("#restrict-future-sessions").val('').prop("disabled", true);
            $(".set-session-restrict-future-session-error").addClass('hidden');
            $(".set-session-balance-button").attr("disabled", false);
        }
    }

    function handleSpendingLimit() {
        var spendLimit = Number($('#spend-limit').val()) * 100;
        var userAccountBalance = <?= $user->getBalance() ?>;

        if (spendLimit <= 0) {
            $(".set-session-balance-spend-limit-insufficient").removeClass('hidden');
            $(".set-session-balance-button").attr("disabled", true);
        } else {
            $(".set-session-balance-spend-limit-insufficient").addClass('hidden');
            $(".set-session-balance-button").attr("disabled", false);
        }

        if (spendLimit > Number(userAccountBalance)) {
            $(".set-session-balance-spend-limit-over-balance").removeClass('hidden');
            $(".set-session-balance-button").attr("disabled", true);
        } else {
            $(".set-session-balance-spend-limit-over-balance").addClass('hidden');
            $(".set-session-balance-button").attr("disabled", false);
        }
    }

    function handleReminderInput(e) {
        var setReminderRaw = $('#set-reminder').val();
        var setReminder = Number(setReminderRaw);
        $(".set-session-set-reminder-error").addClass('hidden');
        if (setReminder <= 0 && setReminderRaw !== '') {
            $(".set-session-set-reminder-error").removeClass('hidden');
        } else {
            $(".set-session-set-reminder-max-length").addClass('hidden');
            $(".set-session-balance-button").attr("disabled", false);
        }
    }

    function handleGameInput(e) {
        var gameLimitRaw = $('#game-limit').val();
        var gameLimit = Number(gameLimitRaw);
        $(".set-session-balance-time-limit").addClass('hidden');

        if (gameLimit <= 0 && gameLimitRaw !== '') {
            $(".set-session-balance-time-limit").removeClass('hidden');
        } else {
            $(".set-session-set-reminder-greater-than").addClass('hidden');
            $(".set-session-balance-button").attr("disabled", false);
        }
    }

    function closePopup() {
        gotoLang('/');
    }

</script>
