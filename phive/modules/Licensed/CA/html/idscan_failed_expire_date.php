<div>
    <div class="lic-mbox-container limits-info province-main-popup <?= phive()->isMobile() ? 'mobile' : '' ?>">
        <div class="center-stuff">
            <p>
                <?php et2('idscan.failed.expiry.date.message', ['mail' => phive('MailHandler2')->getSetting('support_mail')]) ?>
            </p>
        </div>
    </div>
</div>
