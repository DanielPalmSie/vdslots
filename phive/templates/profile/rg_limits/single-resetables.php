<?php

use Videoslots\Services\Renderer\Extensions\Common;

?>
<?php if (phive()->isEmpty($headline)): ?>
<?php else: ?>
<div class="simple-box pad-stuff-ten">
    <div class="account-headline">
        <?= t($headline) ?>
    </div>
    <?php endif ?>

    <?php foreach ($description as $text): ?>
        <?php if (phive()->isEmpty($text)): ?>
        <?php else: ?>
            <?= t2($text, ['cooloff_period' => $cooloff_period]) ?>
        <?php endif ?>
    <?php endforeach ?>

    <?php if ($type == "betmax"): ?>
        <div class="rg-duration" style="margin-left: 9px;margin-bottom: 10px;margin-top: 10px;">
            <form id='rg-duration-form'>
                <div id="rg-duration-<?= $type ?>" class="left">
                    <?php foreach ($bullet_options as $option): ?>
                        <?php if (phive()->isEmpty($option)): ?>
                        <?php else: ?>
                            <div class="left">
                                <input class="left"
                                       type="radio"
                                       name="rg_duration"
                                       value="<?= $option['value'] ?>"
                                    <?= $option['checked'] ? 'checked' : '' ?>
                                />
                                <div class="left" style="margin-top: 2px;">
                                    <?= t($option['alias']) ?>
                                </div>
                            </div>
                        <?php endif ?>
                    <?php endforeach ?>
                </div>
            </form>
        </div>
    <?php endif ?>
    <?php if (phive()->isEmpty($limit_parts)): ?>
    <?php else: ?>
        <div class="account-sub-box rg-single">
            <table class="rg-single-tbl">
                <tr>
                    <?php foreach ($limit_parts as $alias): ?>
                        <?php if (phive()->isEmpty($alias)): ?>
                        <?php else: ?>
                            <th>
                                <div class="left"><?= t($alias) ?></div>
                                <div class="right">(<?= $disp_unit ?>)</div>
                            </th>
                        <?php endif ?>
                    <?php endforeach ?>
                </tr>
                <?php foreach ($data['limits'] as $limit): ?>
                    <?php if (phive()->isEmpty($limit)): ?>
                    <?php else: ?>
                        <tr>
                            <td valign="top">
                                <?php dbInput(
                                    $type . '-' . 'remaining',
                                    $limit['values']['cur'],
                                    'text',
                                    'input-normal',
                                    'disabled'
                                ) ?>
                            </td>
                            <?php if (in_array('remaining', $limit_parts)): ?>
                                <td valign="top">
                                    <?php dbInput(
                                        '',
                                        $limit['values']['rem'],
                                        'text',
                                        'input-normal',
                                        'disabled'
                                    ) ?>
                                    <?php if (!phive()->isEmpty($limit['resets_at'])): ?>
                                        <span class="vip-color"><?= t('resets.on') ?>:</span>
                                        <span><?= phive()->lcDate($limit['resets_at'], '%x %R') ?></span>
                                    <?php endif ?>
                                </td>
                            <?php endif ?>
                            <td valign="top">
                                <?php if ($type == 'rc'): ?>
                                    <?php dbInput(
                                        $type,
                                        $limit['values']['new'],
                                        'number',
                                        'input-normal',
                                        '',
                                        true,
                                        $limit['values']['min'],
                                        $limit['values']['max']
                                    ) ?>
                                <?php else: ?>
                                    <?php dbInput(
                                        $type,
                                        $limit['values']['new'],
                                        'text',
                                        'input-normal'
                                    ) ?>
                                <?php endif ?>
                            </td>
                        </tr>
                    <?php endif ?>
                <?php endforeach ?>
            </table>
            <br clear="all"/>
            <div class="right">
                <?php if (phive()->isEmpty($data)): ?>
                <?php else: ?>
                    <button class="btn btn-l btn-default-l w-125" onclick="setSingleLimit('<?= $type ?>')">
                        <?= t('set.a.limit') ?>
                    </button>
                <?php endif ?>

                <?php if (($type != 'balance') && !(phive()->isEmpty($data))): ?>
                    <?php Common::rgRemoveLimitBtn($type) ?>
                <?php endif ?>

            </div>
            <br clear="all"/>
            <br clear="all"/>

            <?php if (!phive()->isEmpty($data['changes_at'])): ?>
                <div class="left">
                    <span class="vip-color"><?= t('changes.on') ?>:</span>
                    <span><?= phive()->lcDate($data['changes_at'], '%x %R') ?></span>
                </div>
            <?php endif ?>
            <br clear="all"/>
        </div>
    <?php endif ?>
</div>
<br clear="all"/>
