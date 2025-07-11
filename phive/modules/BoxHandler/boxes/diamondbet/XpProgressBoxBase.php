<?php

require_once __DIR__ . '/../../../../../diamondbet/boxes/DiamondBox.php';

class XpProgressBoxBase extends DiamondBox
{
    /** @var null|DBUser $user */
    protected $user = null;

    /** @var null|Paginator $paginator */
    private $paginator;

    /** @var SQL $db */
    private $db;

    /**
     * @param $user
     * @return $this
     */
    public function init($user)
    {
        $this->user = cu($user);
        $this->paginator = phive('Paginator');
        $this->db = phive('SQL')->sh($this->user->getId());
        return $this;
    }

    public function printHTML()
    {
        ?>
        <div class="simple-box pad-stuff-ten">
            <h3><?= t('xp-history') ?></h3>
            <table class="zebra-tbl">
                <colgroup>
                    <col width="200">
                    <col width="360">
                    <col width="100">
                </colgroup>
                <tbody>
                <tr class="zebra-header">
                    <td><?= t('account.xp-history.date') ?></td>
                    <td><?= t('account.xp-history.game_name') ?></td>
                    <td><?= t('account.xp-history.xp_progress') ?></td>
                </tr>
                <? foreach ($this->getUserSessions() as $index => $session): ?>
                    <tr class="<?= $index % 2 == 0 ? 'even' : 'odd' ?>">
                        <td><?= phive()->lcDate($session['created_at']) . ' ' . t('cur.timezone') ?></td>
                        <td><?= $session['game_name'] ?></td>
                        <td><?= $session['xp_progress'] ?></td>
                    </tr>
                <? endforeach; ?>
                </tbody>
            </table>
            <br>
            <?php $this->paginator->render() ?>
        </div>
        <?php
    }

    /**
     * @return mixed
     */
    private function getUserSessions()
    {
        $user_id = $this->user->getId();

        $slots_type = implode("','", phive('Casino')->getSetting('slot_game_types', ['videoslots', 'slots', 'casino-playtech']));

        $entries_count = $this->db->getValue("
            SELECT count(*) FROM bets 
            LEFT JOIN micro_games AS mg ON bets.game_ref = mg.ext_game_name AND bets.device_type = mg.device_type_num
            WHERE user_id = {$user_id} AND mg.tag IN ('{$slots_type}')
        ");

        $this->paginator->setPages($entries_count, '', $page_size = 11);

        $bets = $this->db->loadArray("
            SELECT bets.id, bets.created_at, bets.amount, bets.currency, bets.game_ref, mg.game_name
            FROM bets 
            LEFT JOIN micro_games AS mg ON bets.game_ref = mg.ext_game_name AND bets.device_type = mg.device_type_num
            WHERE user_id = {$user_id} AND mg.tag IN ('{$slots_type}')
            ORDER BY created_at DESC 
            LIMIT {$page_size}
            OFFSET {$this->paginator->db_offset}
        ");

        $bets = array_map(function ($bet) {
            $bet['xp_progress'] = $this->calculateXp($this->user, $bet['amount'], $bet['currency'], $bet['game_ref']);
            return $bet;
        }, $bets);

        return $bets;
    }

    /**
     * @param DBUser $user
     * @param $bet_amount
     * @param $currency
     * @param $cur_game
     * @return mixed
     */
    private function calculateXp($user, $bet_amount, $currency, $cur_game)
    {
        $xp_multi = max($user->getSetting('xp-multiply'), 1);
        $amount = mc(($bet_amount / 100) * $xp_multi, $currency, 'div', false);
        return phive('Casino')->getRtpProgress($amount, $cur_game);
    }

}
