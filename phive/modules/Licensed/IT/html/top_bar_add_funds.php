<?php
//
// Display 'Add Funds' button in Game top bar, that launches the Add Fund popup
//
$disabled_add_funds = phive('MicroGames')->getSetting('balance_reload_disabled_networks', []);
?>
<script>
    $(document).on('extSessionHandlerLoaded', function () {
        var disabled = <?= json_encode($disabled_add_funds)?>;
        var session = window.extSessHandler.activeSessions[0];
        if (session && disabled.indexOf(session.network) < 0) {
            $("#top-bar-add-funds").removeClass('hidden');
        }
    });

</script>
<div id="top-bar-add-funds" class="hidden">
    <button class="btn btn-l btn-default-l btn-action-l hidden" onclick="licFuncs.handleAddFunds()">
        <?= t('add.funds') ?>
    </button>
</div>
