
<div style="text-align: center;">
    <img src="/diamondbet/images/<?= brandedCss() ?>deposit-limit-setup.png">
</div>
<div>
    <p class="reg-dep-lim-prompt__text">
        <?= t('want.deposit.limit') ?>
    </p>
    <span class="styled-select">
        <?php dbSelect(
            'deposit-limit-select',
            ['' => t('select'), 'no-limit' => t('no.limit.required'), 'want-limit' => t('i.wish.to.set.deposit.limit')],
            '',
            [],
            'reg-dep-lim-prompt__dropdown'
        ) ?>
    </span>
    <?php btnDefaultXl(t('continue'), '', 'rgDepLimPromptContinue()') ?>
</div>
<script>
 function rgDepLimPromptContinue() {
     var val = $('#deposit-limit-select').val();
     licJson('rgDepLimPromptSetShown', {}, function (ret) {
         if (ret == 'ok') {
             $.multibox('close', 'reg-dep-lim-prompt');
             if (val == 'want-limit') {
                 $.multibox('close', 'reg-dep-lim-prompt');
                 var extraOptions = isMobile() ? {width: '100%'} : {width: 800};
                 var params = {
                     module: 'Licensed',
                     file: 'dep_lim_info_box',
                     noRedirect: true
                 };
                 extBoxAjax('get_raw_html', 'dep-lim-info-box', params, extraOptions, top);
             }
         }
     }, 'html');
 }
</script>
