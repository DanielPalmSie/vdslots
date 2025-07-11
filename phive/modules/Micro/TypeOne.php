<?php
require_once __DIR__ . '/QuickFire.php';

class TypeOne extends QuickFire
{
    /**
     * TODO reformat this function
     * The tournament entry balance has precedence over the rest, but the session balance has to be
     *
     * @param $user
     * @param $req
     * @param bool $as_sum
     * @return array|int|mixed|string
     */
    public function _getBalance($user, $req = null, $as_sum = true)
    {
        if (!empty($this->t_entry)) {
            return $this->tEntryBalance();
        }
        if ($this->hasSessionBalance()) {
            return $this->getSessionBalance($user);
        }

        $balance = empty($this->chg_calls) ? $user['cash_balance'] : phive('UserHandler')->getFreshAttr($user['id'], 'cash_balance');
        $gref 		= $this->getGameRef($req);
        if(empty($gref))
            return $balance;
        $bonus_balances = phive('Bonuses')->getBalanceByRef($gref, $user['id']);
        if($as_sum)
            return (int)$balance + (int)$bonus_balances;
        return array($balance, $bonus_balances);
    }

    /**
     * checks first if already has loaded the game ($this->game), if not, it tries to find the game from the request parameters,
     * if it doesn't find it, it assigns the game to a dummy
     * @param $req
     * @param string $default
     * @return array|false|mixed|string
     */
    public function getGameByRef($req, $default = '')
    {
        if (empty($this->game))
            $this->game = phive('MicroGames')->getByGameRef($this->getGameRef($req));
        if (empty($this->game) && !empty($default))
            $this->game = phive('MicroGames')->getByGameRef($default);
        return $this->game;
    }
}
