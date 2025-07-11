<?php
require_once __DIR__ . '/../../../../../diamondbet/boxes/DiamondBox.php';
class ClashOfSpinScoreBoxBase extends DiamondBox
{
    public function init()
    {
        $this->race_id = $_GET['r_id'];
        $this->race_id = is_numeric($this->race_id) ? $this->race_id : '';
        $this->template_id = $_GET['t_id'];
        $this->template_id = is_numeric($this->template_id) ? $this->template_id : '';

        $start_time = phive()->hisNow(strtotime($_GET['start_time']));
        $end_time = phive()->hisNow(strtotime($_GET['end_time']));

        $this->cur_uid = uid();

        if (!empty($this->race_id)) {
            $this->race = phive('Race')->getRace($this->race_id);
        } else if (!empty($this->template_id)) {
            $this->race = phive('Race')->getRaceData($this->template_id);
            $this->race['start_time'] = $start_time;
            $this->race['end_time'] = $end_time;
        }
    }

    public function printRaceAmount($sum, $race, $return = false)
    {
        if (in_array($race['race_type'], ['spins', 'bigwin', 'win_multi'])) {
            $value = empty($sum) ? 0 : $sum;

            if($return) {
                return $value;
            } else {
                echo $value;
            }
        } else {
            $mc_sum = mc($sum);

            if($return) {
                return efEuro($mc_sum, true);
            } else {
                efEuro($mc_sum);
            }
        }
    }

    public function printUserName($e, $position = null, $return = false)
    {
        $current_user = !empty($e['user_id']) ? cu($e['user_id']) : null;
        $show_name = $current_user ? empty($current_user->getSetting('privacy-pinfo-hidename')) : true;
        $anonymous = 'Anonymous' . base_convert($e['user_id'], 10, 9);
        $has_account_view_permission = p('account.view');

        if ($has_account_view_permission && !empty($e['user_id'])) {
            $condition = 1;
            $data = [
                'link' => getUserBoLink($e['user_id']),
                'value' => $e['user_id']
            ];
        } elseif ($e['user_id'] == $this->cur_uid && !empty($e['user_id']) && $position == 3) {
            $condition = 2;
            $data = ucfirst(phive()->ellipsis(strtolower($e['firstname']), 8));
        } else {
            $data = !$show_name ? phive()->ellipsis($anonymous, 8) : ucfirst(phive()->ellipsis(strtolower($e['firstname']), 8));
        }

        if($return) {
            return $data;
        }

        if ($condition == 1): ?>
        <a href="<?php echo $data['link'] ?>">
            <?php echo $data['value'] ?>
        </a>
        <?php elseif ($condition == 2): ?>
            <span ><?php echo $data ?></span>
        <?php else: ?>
            <?php echo $data ?>
        <?php endif;
    }

    public function getPrize($prize, $type, $user_id = '', $short = false) {
        if ($type == 'cash') {
            return ['image' => null, 'description' => $this->fmtRacePrize($prize, false)];
        }
        else if ($type == 'award') {
            $award = phive('Race')->getRaceAwardByPrize($prize, $user_id);
            $description = $short ? $award['amount'] : ucfirst(rep(tAssoc("rewardtype.{$award['type']}", $award)));
            return [
                'image' => phive('Trophy')->getAwardUri($award, cu($user_id)),
                'description' => empty($award) ? t('clash.not.classified') : $description
            ];
        }
    }

    public function getRaceData($race, $limit = null): array
    {
        // on this race instance we'll have all_prizes after the leaderBoard function is called
        $_race = phive('Race');
        list($entries, $prizes) = $_race->leaderBoard($race, false, $limit);

        if (empty($entries)) // We are looking at a future race
        {
            $entries = [];
            $prizes = explode(':', $race['prizes']);
            if (!empty($limit))
                $prizes = array_slice($prizes, 0, $limit);
            for ($spot = 1; $spot <= count($prizes); $spot++) {
                $entries[] = ['spot' => $spot, 'user_id' => ''];
            }
        }

        $winners = [];
        for ($i = 1; $i <= 3; $i++) {
            $winners[] = ['entry' => $entries[0], 'prize' => $prizes[0], 'icon' => "/diamondbet/images/". brandedCss() . "clashes/".$i."place_Clashofspins.png"];
            array_shift($entries);
            array_shift($prizes);
        }

        if (!empty($this->cur_uid) && !empty($race['id'])) {
            $entry = phive('Race')->raceEntry($race, $this->cur_uid);
            if ($entry['spot'] > 3) {
                if (!empty($entry)) {
                    array_unshift($entries, $entry);
                    array_unshift($prizes, $_race->all_prizes[$entry['spot'] - 1]);
                }
            }
        }

        return [$entries, $prizes, $winners];
    }

    public function printRace($race, $limit = null)
    {
        [$entries, $prizes, $winners] = $this->getRaceData($race, $limit);

        ?>
         <script>
            jQuery(document).ready(function(){
                $("#clash-hide").click(function(){
                    getWholeRace(<?php echo $this->getId() ?>);
                });

                $("#clash-hideall").click(function(){
                    tbl = $("#clash-cont").find('table');
                    tbl.find('tr').each(function(i){
                    if(i > 10){
                        $(this).hide();
                    }
                    });
                    $(this).find('.hide-rows-viewall').html("<?php et('view.all')?>");
                    $(this).click(function(){
                        getWholeRace(<?php echo $this->getId() ?>);
                    });
                });
            });
        </script>
        <div id="clash-cont" class="clash-container">
            <div class="prizes-row">
                <?php $i = 1; foreach ($winners as $winner): ?>
                <div class="prize-column" >
                        <img class="prize-place" border="0" src="<?php echo $winner['icon']; ?>" />
                        <div class="race-amount-label title yellow gravity-center" ><?php $this->printRaceAmount($winner['entry']['race_balance'], $race); ?></div>
                        <div class="race-type-label subtitle white gravity-center" ><?php et("rakerace.{$race['race_type']}.place")?></div>
                        <div class="prize-label subtitle white gravity-center" ><?php echo $this->getPrize($winner['prize'], $race['prize_type'])['description']; ?></div>
                        <div class="place-label subtitle grey <?php echo phive()->isMobile() ? "gravity-center" : "gravity-right"?>" ><?php et("clash.of.spins.winners$i")?></div>
                        <div class="user-label subtitle yellow <?php echo phive()->isMobile() ? "gravity-center" : "gravity-left"?>" > <?php echo $this->printUserName($winner['entry']); ?></div>
                </div>
                <?php $i++; endforeach;?>
            </div>
            <table class="score-tbl" >
                <tr class="score-header">
                    <td><?php et('clash.of.spins.no')?></td>
                    <td></td>
                    <td><?php et('rakerace.firstname')?></td>
                    <td><?php et("rakerace.{$race['race_type']}.place")?></td>
                    <td></td>
                    <td><?php et('rakerace.prize')?></td>
                    <td></td>
                </tr>
                <?php
                    $i = count(array_filter($entries, function($el) {
                       return $el['user_id'] == $this->cur_uid;
                    })) > 0 ? 3 : 4;
                ?>
                <?php foreach ($prizes as $key => $p): $e = $entries[$key]; $prize = $this->getPrize($p, $race['prize_type'], '', phive()->isMobile()); ?>
				    <tr class="<?php echo !empty($this->cur_uid) && $e['user_id'] == $this->cur_uid && $i == 3 ? "score-row-user" : "score-row"; ?>">
                        <td><?php echo !empty($this->cur_uid) && $e['user_id'] == $this->cur_uid ? $e['spot'] : $i;?>
                        <td>
                            <div></div>
                            <img src="/diamondbet/images/<?= brandedCss() ?>clashes/Award_All_ClashofSpins.png" alt="star"/>
                        </td>
                        <td>
                            <?php echo $this->printUserName($e, $i); ?>
                        </td>
                        <td>
                            <?php $this->printRaceAmount($e['race_balance'], $race); ?>
                        </td>
                        <td align="right"><img border="0" heigth="40" width="40" src="<?php echo $prize['image'] ?>"/></td>
                        <td><?php echo $prize['description'] ?></td>
                        <td></td>
                    </tr>
                <?php $i++; endforeach;?>
            </table>
            <?php if (!empty($limit)): ?>
                <div id="clash-hide">
                    <div class="hide-rows-viewall"><?php et('view.all')?></div>
                </div>
            <?php else : ?>
                <div id="clash-hideall">
                    <div class="hide-rows-viewall"><?php et('hide.all')?></div>
                </div>
            <?php endif?>
        </div>
    <?php
    }

    public function printWholeRace()
    {
        $this->printRace($this->race, null);
    }

    public function printHTML()
    {
        $now = phive()->hisNow();
        $levels = explode('|', $this->race['levels']);
        foreach ($levels as $key => $level) {
            $levels[$key] = explode(':', $level);
        }

        $max_bet = end($levels)[0];
        $min_bet = reset($levels)[0];
        $active = ($now >= $this->race['start_time'] && $now <= $this->race['end_time']);

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
            jQuery(document).ready(function(){
                updateTimeLeft("counter", <?php echo strtotime($now < $this->race['start_time'] ? $this->race['start_time'] : $this->race['end_time']) * 1000 ?>);
            });
            function getWholeRace(boxId) {
                var params = {func: 'printWholeRace'};

                <?php if (!empty($this->race_id)): ?>
                params['r_id'] = <?php echo $this->race_id ?>;
                <?endif?>
                <?php if (!empty($this->template_id)): ?>
                params['t_id'] = <?php echo $this->template_id ?>;
                <?endif?>

                ajaxGetBoxHtml(params, cur_lang, boxId, function(ret){
                    $("#clash-cont").replaceWith(ret);
                });
            }
        </script>

        <div class="container-holder">
            <div class="clash-banner">
                <?php
                    if ($this->race['race_type'] == "bigwin") {
                        img('clash.bigwin.top.image', 960, 307, 'clash.bigwin.top.image', false, null, '', "/diamondbet/images/" . brandedCss() . "clashes/bigwinclash_clashofspin.jpg");
                    } else {
                        img('clash.spin.top.image', 960, 307, 'clash.spin.top.image', false, null, '', "/diamondbet/images/" . brandedCss() . "clashes/spinclash_Banner.jpg");
                    }
                ?>
            </div>
            
            <div class="clash-header">
                <div class="clash-title title white gravity-left" ><?php et("clash.of.spins.race.".$this->race['race_type'])?></div>
                <div id="counter" class="clash-counter subtitle white" ></div>
                <div class="clash-counter-label subtitle yellow" ><?php echo strtoupper($active ? t('clashes.schedule.time.left') : t('clashes.schedule.starting.in')) ?></div>
            </div>

            <?php $this->printRace($this->race, 10); ?>
            <div class="clashes-text">
                <?php if ($this->race['race_type'] == "bigwin"): ?>
                    <?php et('clash.of.spins.bigwin.description') ?>
                <?php else: ?>
                    <?php et('clash.of.spins.description') ?>
                <?php endif ?>
            </div>
            <div class="table-outer">
                <table class="clash-point-tbl zebra-tbl" >
                    <tr class="points-header odd">
                        <th align="left"></th>
                        <th align="left"><?php et('clash.of.spins.bet.spin.amount')?></th>
                        <th align="left"></th>
                        <th align="left"><?php et('clash.of.spins.number.of.points')?></th>
                    </tr>
                    <?php $i = 0;foreach ($levels as $level): ?>
                    <tr class="points-row <?php echo $i % 2 == 0 ? "even" : "odd"; ?>">
                        <td align="left"><img border="0" src="/diamondbet/images/<?= brandedCss() ?>clashes/Bet_ClashofSpins.png" /></td>
                        <td align="left">
                            <?php
                                if ($this->race['race_type'] == 'bigwin') {
                                    switch ($level[0]) {
                                        case 15:
                                            et('clash.of.spins.bigwin.bigwin');
                                        break;
                                        case 30:
                                            et('clash.of.spins.bigwin.megawin');
                                        break;
                                        case 60:
                                            et('clash.of.spins.bigwin.supermegawin');
                                        break;
                                    }
                                } else {
                                    echo $this->fmtRacePrize($level[0], false);
                                }
                            ?>
                        </td>
                        <td align="left"><img border="0" src="/diamondbet/images/<?= brandedCss() ?>clashes/points_Clashofspins.png"/></td>
                        <td align="left"><?php echo $level[1] . ' '; $level[1] == 1 ? et('clash.of.spins.point') : et('clash.of.spins.points') ?></td>
                    </tr>
                    <?php $i++;endforeach;?>
                </table>
            </div>
            <div class="clashes-text">
                <?php if ($this->race['race_type'] == "bigwin"): ?>
                    <?php et('clash.of.spins.bigwin.prices.terms.conditions') ?>
                <?php else: ?>
                    <?php et('clash.of.spins.prices.terms.conditions') ?>
                <?php endif ?>
            </div>
        </div>
     <?php
    }
}
