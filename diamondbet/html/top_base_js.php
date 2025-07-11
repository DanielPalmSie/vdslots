<script>
  var sent = false;
  var tcAction = function (mgAjaxAction, action, keyAction, redirectUrl = '/') {
    if (action == 'close' && sent) {
      return;
    } else if (action == 'close') {
      action = 'cancel';
    }

    var params = {action: mgAjaxAction};
    params[keyAction] = action;

    mgAjax(params, function (res) {
      sent = true;
      mboxClose('mbox-msg');
      if (redirectUrl) {
          gotoLang(redirectUrl);
      }
    });

  }
  function generateShowTerms(mgAjaxAction, isMobile, cancelLabel, okLabel, title, keyAction, redirectUrl = '/') {
    $(document).ready(function () {
      setTimeout(function () {
        mboxDialog($("#tac-holder").html(),
          "tcAction('" + mgAjaxAction + "', 'cancel', '" + keyAction + "', '" + redirectUrl + "')",
          cancelLabel,
          "tcAction('" + mgAjaxAction + "', 'accept', '" + keyAction + "', '" + redirectUrl + "')",
          okLabel,
          function () {
            tcAction(mgAjaxAction, 'close', keyAction, redirectUrl);
          },
          600,
          false,
          'btn-cancel-l',
          title,
          '',
          isMobile ? 'tac-mobile' : ''
        );
      }, isMobile ? 0 : 2000);
    });
  }

  function generateShowPrivacyPolicy(redirectUrl = '/') {
      $(document).ready(function () {
          setTimeout(function () {
              mboxMsg(
                  $('#tac-holder').html(),
                  '<?php et('accept') ?>',
                  function () {
                      tcAction('prp-action', 'close', 'prpaction', redirectUrl);
                  },
                  600,
                  false,
                  'btn-cancel-l ',
                  '<?php et('privacy.policy.title') ?>',
                  "<?php echo htmlentities(t('privacy.policy.button')) ?>",
                  'full'
              );
          }, isMobile ? 0 : 2000);
      });

  }
</script>

<?php if (!empty($_GET['showtc']) || !empty($_GET['showstc'])
    || !empty($_GET['showbtc'] || !empty($_GET['showpp']))
):

    $_GET['signup'] = true;

    ?>
    <div style="display: none;" id="tac-holder">
        <div style="height: 200px">
            <?php
            if (!empty($_GET['showstc'])) {
                et(lic('getTermsAndConditionPage', ['sports']));
            } elseif (!empty($_GET['showtc'])) {
                et(lic('getTermsAndConditionPage'));
            } elseif (!empty($_GET['showbtc'])) {
                et(lic('getBonusTermsAndConditionPage'));
            } elseif (!empty($_GET['showpp'])) {
                et(lic('getPrivacyPolicyPage'));
            }
            ?>
        </div>
    </div>
<?php endif ?>
<?php if ($_GET['showtc']): ?>
    <script>
        generateShowTerms(
            'tac-action',
            <?= phive()->isMobile() ? 1 : 0 ?>,
            '<?php et('do.not.accept') ?>',
            '<?php et('accept') ?>',
            '<?php et('new.tac') ?>',
            'tacation',
            '<?= $_GET['tc-redirect'] ? urldecode($_GET['tc-redirect']) : '/' ?>'
        )
    </script>
<?php elseif ($_GET['showbtc']): ?>
    <script>generateShowTerms(
        'bonus-tac-action',
            <?= phive()->isMobile() ? 1 : 0 ?>,
            '<?php et('do.not.accept') ?>',
            '<?php et('accept') ?>',
            '<?php et('new.bonus-tac') ?>',
            'btcaction',
            '<?= $_GET['tc-redirect'] ? urldecode($_GET['tc-redirect']) : '/' ?>'
        )</script>
<?php elseif ($_GET['showstc']): ?>
    <script>
        generateShowTerms('tac-sport-action',
            <?= phive()->isMobile() ? 1 : 0 ?>,
            '<?php et('do.not.accept') ?>',
            '<?php et('accept') ?>',
            '<?php et('new.tac') ?>',
            'tacation',
            '<?= $_GET['tc-redirect'] ? urldecode($_GET['tc-redirect']) : '/' ?>'
        )</script>
<?php elseif ($_GET['showpp'] && phive('DBUserHandler')->getSetting('pp_on') === true): ?>
    <script>
        generateShowPrivacyPolicy('<?= $_GET['tc-redirect'] ? urldecode($_GET['tc-redirect']) : '/' ?>');
    </script>
<?php endif ?>

<?php
$user = cu();

if (
    strpos($_SERVER['REQUEST_URI'], '/privacy-dashboard/') === false
    && !empty($user) && $user instanceof DBUser && !privileged($user)
    && $user->hasCompletedRegistration()
    && !phive('DBUserHandler/PrivacyHandler')->hasPrivacySettings($user)
){
    loadJs("/phive/js/privacy_dashboard.js");
    echo sprintf(
        <<<HTML
            <script type="text/javascript">
                $(document).ready(function () {
                    showPrivacyConfirmBox('%d', '%s');
                });
            </script>
        HTML,
        phive()->isMobile() ? 1 : 0,
        $user->hasDeposited() ? 'popup' : 'registration',
    );
}

wsUpdateBalance();
