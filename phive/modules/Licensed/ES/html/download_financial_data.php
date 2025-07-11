<div class="simple-box pad-stuff-ten">
    <h3><?php echo t('game-history.download-financial-data') ?></h3>
    <table class="account-tbl">
        <tr>
            <th style="vertical-align: top;">
                <table class="zebra-tbl">
                    <col width="520"/>
                    <col width="140"/>
                    <tr class="zebra-header">
                        <th class="g-a-download-cell">
                            <?php echo t('game-history.download-financial-data-for-interval') ?>
                        </th>
                        <th class="g-a-download-cell">
                            <div class="g-a-download-btn-wrapper">
                                <button id="g-a-download-btn" class="g-a-download-btn" type="button" onclick="lic('downloadAccountHistory')">
                                    <img
                                        class="g-a-download-icon"
                                        src="/diamondbet/images/<?= brandedCss() ?>download.png"
                                        alt="download"
                                    />
                                </button>
                            </div>
                        </th>
                    </tr>
                </table>
            </th>
        </tr>
    </table>
</div>
<br/>
