<?php
require_once __DIR__ . '/../../../../../diamondbet/boxes/DiamondBox.php';
class ClashOfSpinBoxBase extends DiamondBox
{
    const NOT_STARTED = 0;
    const ACTIVE = 1;
    const FINISHED = 2;

    public function init()
    {
        $week = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $this->days = [];
        $day_today = date('N') - 1;
        for ($i = 0; $i < 7; $i++) {
            $this->days[date('Y-m-d', strtotime(" +$i days"))] = $week[($day_today + $i) % 7];
        }
        $this->vars = $this->getVars();
        $this->clashes = $this->getClashes();
    }

    public function getVars()
    {
        $default = [
            'order' => 0,
            'day' => date('Y-m-d'),
            'time_from' => '00:00',
            'time_to' => '23:59',
            'type' => 0,
        ];

        $vars = [];

        foreach ($default as $key => $var) {
            $vars[$key] = isset($_POST[$key]) ? $_POST[$key] : $var;
        }

        if (DateTime::createFromFormat('Y-m-d', $vars['day']) === false) {
            $vars['day'] = $default['day'];
        }
        if (DateTime::createFromFormat('H:i', $vars['time_from']) === false) {
            $vars['time_from'] = $default['time_from'];
        }
        if (DateTime::createFromFormat('H:i', $vars['time_to']) === false) {
            $vars['time_to'] = $default['time_to'];
        }

        return $vars;
    }

    public function getClashes()
    {
        $start = date('Y-m-d H:i:s', strtotime($this->vars['day'] . " " . $this->vars['time_from']));
        $end = date('Y-m-d H:i:s', strtotime($this->vars['day'] . " " . $this->vars['time_to']));

        // Prevent overloading queries
        $diff = phive()->timeDiff($end, $start, $op = '-', $format = 'd');
        if ($start > $end || $diff > 30) {
            return [];
        }

        $clashes = phive('Race')->getRacesInInterval($start, $end, $this->vars['order'] == 1);

        $now = phive()->hisNow();
        foreach ($clashes as $key => $clash) {
            if ($now >= $clash['start_time'] && $now <= $clash['end_time']) {
                $state = $this::ACTIVE;
            } else if ($now < $clash['start_time']) {
                $state = $this::NOT_STARTED;
            } else {
                $state = $this::FINISHED;
            }

            $clashes[$key]['state'] = $state;
            $clashes[$key]['tag'] = $key;
        }

        return $clashes;
    }

    public function printPrize($clash)
    {
        $prize = explode(':', $clash['prizes'], 2)[0];

        if ($clash['prize_type'] == 'award') {
            $award = phive('Race')->getRaceAwardByPrize($prize, '');
            $img = phive('Trophy')->getAwardUri($award, '');
        }

        ?>
            <?php if ($clash['race_type'] == 'spins' || $clash['race_type'] == 'win_multi' || $clash['race_type'] == 'bigwin') { ?>
                <div class="prize-container">
                    <div class="clash-column-prize left" >
                        <div class="title"><?php et('clash.type.' . $clash['race_type'].'.title') ?></div>
                        <div class="subtitle-grey"><?php et('clash.type.' . $clash['race_type'].'.subtitle') ?></div>
                        <div class="subtitle"><?php et('clashes.schedule.prize.pool') ?> </div>
                        <?php if ($clash['template_id'] > 0): ?>
                            <div class="subtitle"><?php et("clash.t{$clash['template_id']}.prize.pool") ?> </div>
                        <?php else: ?>
                            <div class="subtitle"><?php et("clash.c{$clash['id']}.prize.pool") ?> </div>
                        <?php endif; ?>

                    </div>
                    <div class="clash-column-prize right">
                        <?php if ($clash['prize_type'] == 'cash'): ?>
                            <div class="title"><?php echo $this->fmtRacePrize($prize) ?></div>
                        <?php elseif ($clash['prize_type'] == 'award'): ?>
                            <img class="award-img" src="<?php echo $img ?>" >
                        <?php endif; ?>
                    </div>
                </div>
            <?php } else if ($clash['race_type'] == 'bigwin_') { ?>
                <div class="prize-container">
                    <div class="clash-column-prize left" >
                        <div class="title"><?php et('clash.type.' . $clash['race_type'].'.title') ?></div>
                        <div class="subtitle-grey"><?php et('clash.type.' . $clash['race_type'].'.subtitle') ?></div>
                        <div class="subtitle"><?php et('clashes.schedule.prize.pool') ?> </div>
                        <?php if ($clash['template_id'] > 0): ?>
                            <div class="subtitle"><?php et("clash.t{$clash['template_id']}.prize.pool") ?> </div>
                        <?php else: ?>
                            <div class="subtitle"><?php et("clash.c{$clash['id']}.prize.pool") ?> </div>
                        <?php endif; ?>

                    </div>
                    <div class="clash-column-prize right">
                        <?php if ($clash['prize_type'] == 'cash'): ?>
                            <div class="title"><?php echo $this->fmtRacePrize($prize) ?></div>
                        <?php elseif ($clash['prize_type'] == 'award'): ?>
                            <img class="award-img" src="<?php echo $img ?>" >
                        <?php endif; ?>
                    </div>
                </div>
            <?php } ?>
        <?php
    }

    public function printSeparators($clashes) {
        $lightning = [
            $this::NOT_STARTED => '/diamondbet/images/'. brandedCss() . 'clashes/lightening-notactive.png',
            $this::ACTIVE => '/diamondbet/images/'. brandedCss() . 'clashes/Lightening.png',
            $this::FINISHED => '/diamondbet/images/'. brandedCss() . 'clashes/lightening-notactive.png'
        ];

        foreach (array_chunk($clashes , phive()->isMobile() ? 1 : 2) as list($right, $left)) {
            if (phive()->isMobile()) {
                $left = '';
            }

            $right_state = $lightning[$right['state']];

            if (!empty($left)) { // Both left and right.
                $left_state = $lightning[$left['state']];
                ?>
                <tr >
                    <td>
                    <?php if ($right['race_type'] == 'bigwin') { ?>
                        <div class="clashes-container-separator-bigwin" >
                    <?php } else { ?>
                        <div class="clashes-container-separator" >
                    <?php } ?>
                            <div class="vertical-line line-top" ></div>
                            <img class="lightning lightning-right" border="0" src="<?php echo $right_state ?>" />
                            <div class="horizontal-line line-right" ></div>
                            <div class="vertical-line line-middle" ></div>
                            <div class="horizontal-line line-left"></div>
                            <img class="lightning lightning-left" border="0" src="<?php echo $left_state ?>" />
                            <div class="vertical-line line-bottom"></div>
                        </div>
                    </td>
                </tr>
                <?php
            } else { // Left only.
                ?>
                <tr >
                    <td>
                        <div class="clashes-container-separator" >
                            <div class="vertical-line line-top"></div>
                            <img class="lightning lightning-right" border="0" src="<?php echo $right_state ?>" />
                            <?php if (!phive()->isMobile()) : ?>
                            <div class="horizontal-line line-right" ></div>
                            <?php endif; ?>
                            <div class="vertical-line line-end"></div>
                        </div>
                    </td>
                </tr>
                <?php
            }

        }

        if (count($clashes) > 0) { // We have added separators, so append the final lightning
            ?>
            <tr >
                <td>
                    <div class="lightning-tail" >
                        <img class="lightning lightning-end" border="0" src="<?php echo $lightning[$this::ACTIVE] ?>" />
                    </div>
                </td>
            </tr>
            <?php
        }
    }

    public function printClash($clash)
    {
        $lang = strtoupper(phive('Localizer')->getLanguage());
        $state_flag = [
            "",
            "/diamondbet/images/". brandedCss() . "clashes/Active_$lang.png",
            "/diamondbet/images/". brandedCss() . "clashes/finished_$lang.png"
            ][$clash['state']];

        $background = '/diamondbet/images/'. brandedCss() . 'clashes/SpinClash_Clashofspins.jpg';
        if ($clash['race_type'] == 'win_multi') {
            $background = '/diamondbet/images/'. brandedCss() . 'clashes/MultipierClash_Clashofspins.jpg';
        } else if ($clash['race_type'] == 'bigwin') {
            $background = '/diamondbet/images/'. brandedCss() . 'clashes/BigWin_Clashofspins.jpg';
        }

        $duration = phive()->subtractTimes($clash['end_time'], $clash['start_time'], 'm');

        ?>

        <?php if ($clash['race_type'] == 'spins' || $clash['race_type'] == 'win_multi' || $clash['race_type'] == 'bigwin') { ?>
        <div class="clashes-container-clash">
            <img class="spinclash-img" src="<?php echo $background ?>">
            <?php if(!empty($state_flag)): ?>
                <img class="img-top-right" src="<?php echo $state_flag ?>" >
            <?php endif; ?>
            <div class="container-equal-space">
                <div class="counter-label"><?php et($clash['state'] === $this::ACTIVE ? 'clashes.schedule.time.left' : 'clashes.schedule.starting.in') ?></div>
                <div class="counter" id="counter_<?php echo $clash['tag'] ?>"></div>
                <div class="container-columns">
                    <div class="clash-column left" >
                        <div class="title"><?php echo substr_count($clash['prizes'], ':') + 1 ?></div>
                        <div class="subtitle"><?php et('clashes.schedule.total.winners') ?></div>
                    </div>
                    <div class="clash-column right" >
                        <div class="title"><?php echo $duration ?></div>
                        <div class="subtitle"><?php et('clashes.schedule.time.min') ?></div>
                    </div>
                </div>
                <?php echo $this->printPrize($clash); ?>
                <?php $url = "spin-clash?r_id={$clash['id']}&t_id={$clash['template_id']}&start_time={$clash['start_time']}&end_time={$clash['end_time']}"; ?>
                <a href="<?php echo llink("/clash-of-spins/") . $url?>" class="clashes-info-button"><?php et('clashes.schedule.more.info') ?></a>
            </div>
        </div>
        <?php } else if ($clash['race_type'] == 'bigwin_') { ?>
            <div class="clashes-container-clash clashes-container-clash-bigwin">
                <img class="spinclash-img" src="<?php echo $background ?>">
                <?php if(!empty($state_flag)): ?>
                    <img class="img-top-right" src="<?php echo $state_flag ?>" >
                <?php endif; ?>
                <div class="container-equal-space container-equal-space-bigwin">
                    <!--
                    <div class="counter-label"><?php et($clash['state'] === $this::ACTIVE ? 'clashes.schedule.time.left' : 'clashes.schedule.starting.in') ?></div>
                    <div class="container-columns">
                        <div class="clash-column left" >
                            <div class="title"><?php echo substr_count($clash['prizes'], ':') + 1 ?></div>
                        </div>
                        <div class="clash-column right" >
                            <div class="title"><?php echo $duration ?></div>
                            <div class="subtitle"><?php et('clashes.schedule.time.min') ?></div>
                        </div>
                    </div>
                    -->
                    <?php  echo $this->printPrize($clash); ?>
                    <?php $url = "spin-clash?r_id={$clash['id']}&t_id={$clash['template_id']}&start_time={$clash['start_time']}&end_time={$clash['end_time']}"; ?>
                </div>
            </div>
        <?php } ?>
        <?php
    }

    public function printClashesTable() {
        if (!phive()->isMobile()): ?>
            <div class="clashes-column right-clash">
                <table >
                    <?php for ($i = 0; $i < count($this->clashes); $i += 2): ?>
                    <tr >
                        <td>
                            <?php $this->printClash($this->clashes[$i]); ?>
                        </td>
                    </tr>
                    <?php endfor;?>
                </table>
            </div>
            <div class="clashes-column separator">
                <table >
                    <?php $this->printSeparators($this->clashes) ?>
                </table>
            </div>
            <div class="clashes-column left-clash">
                <table >
                    <?php for ($i = 1; $i < count($this->clashes); $i += 2): ?>
                    <tr >
                        <td>
                        <?php $this->printClash($this->clashes[$i]); ?>
                        </td>
                    </tr>
                    <?php endfor;?>
                </table>
            </div>
        <?php else: ?>
            <div class="clashes-column right-clash-mobile">
                <table >
                    <?php foreach ($this->clashes as $clash): ?>
                    <tr >
                        <td>
                            <?php $this->printClash($clash); ?>
                        </td>
                    </tr>
                    <?php endforeach;?>
                </table>
            </div>
            <div class="clashes-column separator-mobile">
                <table >
                    <?php $this->printSeparators($this->clashes) ?>
                </table>
            </div>
        <?php endif;
    }

    public function printOrderSelector() {
        ?>
        <div class="clashes-filter-label">
            <div class="clashes-order-select">
                <select name="order">
                    <option value="0" <?php echo $this->vars['order'] == 0 ? ' selected="selected"' : ''; ?>><?php et('clashes.schedule.newest')?></option>
                    <option value="1" <?php echo $this->vars['order'] == 1 ? ' selected="selected"' : ''; ?>><?php et('clashes.schedule.oldest')?></option>
                </select>
            </div>
        </div>
        <?php
    }

    public function printHTML()
    {
        $now = phive()->hisNow();

        $active = ($now >= $clash['start_time'] && $now <= $clash['end_time']);

        if (!phive()->isMobile()) {
            loadCss("/diamondbet/css/" . brandedCss() . "clash.css");
        } else {
            loadCss("/diamondbet/css/" . brandedCss() . "clash-mobile.css");
        }

        loadJs('/phive/js/clash_of_spins_page.js');
        ?>
        <script src="/phive/js/jquery.flot.js"></script>
        <script src="/phive/js/jquery.flot.time.js"></script>
        <script>
            $(document).ready(function () {
                <?php foreach ($this->clashes as $clash): ?>
                    updateTimeLeft("counter_<?php echo $clash['tag'] ?>", <?php echo strtotime($now < $clash['start_time'] ? $clash['start_time'] : $clash['end_time']) * 1000 ?>);
                <?php endforeach;?>
            });
        </script>

        <div class="container-holder">
            <div class="clash-banner">
                <?php img('clash.info.top.image', 960, 307, 'clash.info.top.image', false, null, '', "/diamondbet/images/". brandedCss() ."clashes/clash_of_spins_banner.jpeg"); ?>
            </div>
            <form class="clashes-filter" id="clashes_search" method="post">
                <input type="hidden" name="token" value="<?= $_SESSION['token'] ?>">
                <fieldset>
                    <?php if (phive()->isMobile()): ?>
                        <div class="clashes-title-label" >
                            <label><?php et('clashes.schedule.search.title'); ?></label>
                        </div>
                    <?php $this->printOrderSelector(); endif; ?>
                    <div class="clashes-filter-label">
                        <?php if (!phive()->isMobile()): ?>
                            <label><?php et('search')?></label>
                        <?php endif; ?>
                        <select class="clashes-select clashes-all" name="type">
                                <option value="0"><?php et('clashes.schedule.all.clash.of.spins')?></option>
                        </select>
                    </div>
                    <div class="clashes-filter-label">
                        <?php if (!phive()->isMobile()): ?>
                            <label><?php et('day')?></label>
                        <?php endif; ?>
                        <select class="clashes-select" name="day">
                            <?php foreach ($this->days as $date => $day): ?>
                            <option value=<?php echo $date ?> <?php echo $this->vars['day'] == $date ? ' selected="selected"' : ''; ?>><?php et($day) ?></option>
                            <?php endforeach;?>
                        </select>
                    </div>
                    <div class="clashes-filter-label no-margins">
                        <?php if (!phive()->isMobile()): ?>
                            <label><?php et('time')?></label>
                        <?php endif; ?>
                        <input class="time-picker" type="text" name="time_from" value="<?php echo $this->vars['time_from'] ?>"></input>
                    </div>

                    <div class="clashes-filter-label single-row-label" >
                        <label><?php echo !phive()->isMobile() ? t('to') : "-" ?></label>
                    </div>
                    <div class="clashes-filter-label single-row" >
                        <input class="time-picker" type="text" name="time_to" value="<?php echo $this->vars['time_to'] ?>"></input>
                    </div>

                    <?php if (!phive()->isMobile()): $this->printOrderSelector(); ?>
                        <button  type="submit" class="clashes-btn clashes-btn-search icon icon-vs-search"></button>
                    <?php else: ?>
                        <button  type="submit" class="clashes-info-button search-button" ><?php et('clashes.schedule.search')?></button>
                    <?php endif; ?>
                </fieldset>
            </form>
            <div class="clashes-center-img">
                <?php img('clashes.center.img', 345, 79, 'clashes.center.img', false, null, '', '/diamondbet/images/'. brandedCss() .'clashes/Logo-Clashofspins.png'); ?>
            </div>
            <?php $this->printClashesTable() ?>
        </div>
        <hr></hr>
        <div class="clashes-text">
            <?php et('clashes.schedule.how.clash.works') ?>
        </div>
        <?php
    }
}
