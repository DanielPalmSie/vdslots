<?php
    $user = cu();
    $replacements = [
        '[[BOX_ID]]'        => $_POST['box_id'],
        '[[USER_ID]]'       => ($user instanceof DBUser) ? $user->getId() : 0,
        '[[DEPOSIT_TEXT]]'  => t('deposit'),
        '[[CANCEL_TEXT]]'   => t('Cancel'),
        '[[BODY_TEXT]]'     => t('forfeit.deposit.blocked.popup.error'),
        '[[ICON_URL]]'      => '/diamondbet/images/' . brandedCss() . 'warning.png',
    ];

    $body =
        <<<HTML
        <div class="forfeit-bonus-popup">
            <div class="forfeit-bonus-popup__icon"><img src="[[ICON_URL]]"></div>
            <div class="forfeit-bonus-popup__body">
                <div class="forfeit-bonus-popup__content">[[BODY_TEXT]]</div>
                <div class="forfeit-bonus-popup__actions">
                    <button class="btn btn-l btn-default-l forfeit-bonus-popup__btn--deposit" onclick="forfeitAjaxCall()">[[DEPOSIT_TEXT]]</button>
                    <button class="btn btn-l btn-default-l forfeit-bonus-popup__btn--close"  onclick="closeForfeitBox()">[[CANCEL_TEXT]]</button>
                </div>
            </div>
        </div>
        <script>
            function closeForfeitBox(){mboxClose('[[BOX_ID]]');}
            function forfeitAjaxCall(){mgAjax({action:'forfeit-bonuses-to-deposit', 'user_id':'[[USER_ID]]'},closeForfeitBox);}
        </script>
        HTML;

    // Print HTML
    echo str_replace(array_keys($replacements), array_values($replacements), $body);
