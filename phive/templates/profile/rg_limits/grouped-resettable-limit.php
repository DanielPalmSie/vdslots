<?php

use Videoslots\Services\Renderer\Extensions\Common;

?>
<div class="simple-box pad-stuff-ten">
    <div class="account-headline">
        <?= t($headline) ?>
    </div>
    <?php foreach($description as $text): ?>
        <?= t2($text, [ 'cooloff_period' => $cooloff_period]) ?>
    <?php endforeach ?>

    <?php foreach($list_of_types as $type): ?>
        <?php if(isset($data[$type])): ?>
            <div class="account-sub-box-wrapper">
                <?php if(!empty($data[$type]['subheadline'])): ?>
                    <div class="account-headline">
                        <?= t($data[$type]['subheadline']) ?>
                    </div>
                <?php endif ?>
                <div class="account-sub-box rg-resettable">
                    <table class="rg-resettable-tbl">
                        <?php if($is_mobile): ?>
                            <tr>
                                <th>
                                    <?= t('my.limits') ?>
                                </th>
                                <?php foreach(['active.limit', 'remaining'] as $alias): ?>
                                    <th>
                                        <div class="left"><?= t($alias) ?></div>
                                        <div class="right">(<?= $data[$type]['disp_unit'] ?>)</div>
                                    </th>
                                <?php endforeach ?>
                            </tr>
                            <?php foreach($data[$type]['limits'] as $limit): ?>
                                <tr>
                                    <td valign="top" style="width: 50px;">
                                        <div class="margin-five-top rg-tspan-headline"><?= t($limit['time_span'] . '.ly') ?></div>
                                        <div class="margin-five-top rg-tspan-descr"><?= t($type . '.' . $limit['time_span'] . '.ly.descr') ?> </div>
                                    </td>
                                    <td valign="top">
                                        <?php dbInput(
                                            $type . '-' . $limit['time_span'] . "-remaining",
                                            $limit['values']['cur'],
                                            'text',
                                            'input-normal input-rg-limit-disabled',
                                            'disabled'
                                        ) ?>
                                    </td>
                                    <td valign="top">
                                        <?php dbInput(
                                            '',
                                            $limit['values']['rem'],
                                            'text',
                                            'input-normal input-rg-limit-disabled',
                                            'disabled'
                                        ) ?>
                                    </td>
                                </tr>
                                <?php if(!phive()->isEmpty($limit['resets_at'])): ?>
                                    <tr>
                                        <td>
                                            &nbsp;
                                        </td>
                                        <td colspan="2">
                                            <div class="right">
                                                <span class="vip-color"><?= t('resets.on') ?>:</span>
                                                <span><?= phive()->lcDate($limit['resets_at'], '%x %R') ?></span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif ?>
                                <tr>
                                    <td>
                                        <?= t('new.limit') ?>
                                    </td>
                                    <td colspan="2">
                                        <?php dbInput(
                                            $type . '-' . $limit['time_span'],
                                            $limit['values']['new'],
                                            'text',
                                            'input-normal input-rg-new-limit'
                                        ) ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3" style="height: 20px;">
                                        &nbsp;
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        <?php else: ?>
                            <tr>
                                <th><?= t('my.limits') ?></th>
                                <?php foreach(['active.limit', 'remaining', 'new.limit'] as $alias): ?>
                                    <th>
                                        <div class="left"><?= t($alias) ?></div>
                                        <div class="right">(<?= $data[$type]['disp_unit'] ?>)</div>
                                    </th>
                                <?php endforeach ?>
                            </tr>
                            <?php foreach ($data[$type]['limits'] as $limit): ?>
                                <tr>
                                    <td valign="top">
                                        <div class="margin-five-top rg-tspan-headline"><?= t($limit['time_span'] . '.ly') ?></div>
                                        <div class="margin-five-top rg-tspan-descr"><?= t($type . '.' . $limit['time_span'] . '.ly.descr') ?></div>
                                    </td>
                                    <td valign="top">
                                        <?php dbInput(
                                            $type . '-' . $limit['time_span'] . '-' . 'remaining',
                                            $limit['values']['cur'],
                                            'text',
                                            'input-normal',
                                            'disabled'
                                        ) ?>
                                    </td>
                                    <td valign="top">
                                        <?php dbInput(
                                            '',
                                            $limit['values']['rem'],
                                            'text',
                                            'input-normal',
                                            'disabled'
                                        ) ?>
                                        <?php if(!phive()->isEmpty($limit['resets_at'])): ?>
                                            <span class="vip-color"><?= t('resets.on') ?>:</span>
                                            <span><?= phive()->lcDate($limit['resets_at'], '%x %R') ?></span>
                                        <?php endif ?>
                                    </td>
                                    <td valign="top">
                                        <?php dbInput(
                                            $type . '-' . $limit['time_span'],
                                            $limit['values']['new'],
                                            'text',
                                            'input-normal'
                                        ) ?>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        <?php endif ?>
                    </table>
                    <?php if(!$is_mobile): ?>
                        <br clear="all"/>
                    <?php endif ?>
                    <div class="right">
                        <button class="btn btn-l btn-default-l w-125" onclick="setResettableLimit('<?= $type ?>')">
                            <?= t('set.a.limit') ?>
                        </button>&nbsp;&nbsp;
                        <?php Common::rgRemoveLimitBtn($type) ?>
                    </div>
                    <br clear="all"/>
                    <br clear="all"/>
                    <?php if(!phive()->isEmpty($data[$type]['changes_at'])): ?>
                        <div class="left">
                            <span class="vip-color"><?= t('changes.on') ?>:</span>
                            <span><?= phive()->lcDate($data[$type]['changes_at'], '%x %R') ?></span>
                        </div>
                    <?php endif ?>
                    <br clear="all"/>
                </div>
            </div>
        <?php endif ?>
    <?php endforeach ?>
</div>
<br clear="all"/>
<script>
    $(document).ready(function() {
        $('.rg-resettable-tbl .input-normal').on('change blur', function (e) {
                e.target.value = getMaxIntValue(e.target.value);
        });

        if (licFuncs.assistOnLimitsChange) {
            <?php foreach ($list_of_types as $type): ?>
                licFuncs.assistOnLimitsChange('<?= $type ?>');
            var type = '<?= $type ?>';
            // Button setup.
            window[type + '_reSpans'] = <?= $time_spans_json ?>;
            <?php endforeach ?>
        }
    });
</script>
