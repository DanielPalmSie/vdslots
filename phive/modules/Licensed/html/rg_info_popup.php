<?php

$box_id = $_POST['boxid'];

$user = cu();
$userId = $user->getId();
$rgType = $_POST['rgType'];
$responseData = $_POST['data'];
$description = tAssoc(
        $responseData['description']['alias'],
        $responseData['description']['dynamic_variables'],
);
$devicePath = Phive()->isMobile() ? '/mobile' : '';
?>

<div class="rg-info-popup">
    <div class="rg-info-popup__main-content">
        <div class="rg-info-popup__logo">
            <div class="rg-info-popup__logo-wrapper">
                <img
                        class="rg-info-popup__logo-img"
                        alt="error"
                        src="<?= $responseData['description']['logo'] ?>"
                />
            </div>
        </div>
        <h2 class="rg-info-popup__h2"><?= t('rg.info.box.top.headline') ?></h2>
        <div class="rg-info-popup__description">
            <?= $description ?>
        </div>
    </div>
    <div class="rg-info-popup__actions">
        <div class="rg-info-popup__actions-row">
            <?php
                $totalActions = count($responseData['actions']);
                // Define URL mapping for actions that require a link
                $actionUrls = [
                    "LOGOUT" => "/?signout=true",
                    "RESPONSIBLE_GAMBLING" => "{$devicePath}/account/{$userId}/responsible-gambling"
                ];
            ?>

            <div class="rg-info-popup__actions-group">
                <?php foreach ($responseData['actions'] as $index => $action): ?>
                <?php
                $buttonClass = "rg-info-popup__btn rg-info-popup__btn--" . htmlspecialchars($action['type']);
                $dataAction = htmlspecialchars($action['type']);
                $alias = htmlspecialchars($action['alias']);
                $redirectUrl = $actionUrls[$action['redirect_to_page']['page']] ?? $actionUrls[$action['action']] ?? null;
                ?>

                <?php if ($redirectUrl): ?>
                    <a href="<?= htmlspecialchars($redirectUrl); ?>" class="<?= $dataAction; ?>">
                <?php endif; ?>

                <button class="<?= $buttonClass; ?> <?= $dataAction; ?>" data-action="<?= $dataAction; ?>">
                    <?= t($alias); ?>
                </button>

                <?php if ($redirectUrl): ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
    function logAction(eventTag) {
        $.post(
            "/phive/modules/Micro/ajax.php",
            {action: 'rg-popup-action', event: eventTag, trigger: '<?php echo $rgType; ?>'},
            function(ret){}
        );
    }

    $(document).ready(function (){
        const buttons = document.querySelectorAll('.rg-info-popup__btn');
        buttons.forEach(function (button) {
            button.addEventListener('click', function (e) {
                e.preventDefault();
                const eventTag = button.getAttribute('data-action');
                logAction(eventTag);

                if (eventTag === 'continue') {
                    mboxClose('<?php echo $box_id ?>');
                    return;
                }

                const parent = button.closest('a');
                if (parent) {
                    window.location.href = parent.getAttribute('href');
                }
            }, { once: true });
        });
    })
</script>
