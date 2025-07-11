<?php

abstract class TestCasino extends TestPhive
{

    /**
     * Instance of GP
     * @var Gp
     */
    protected $_m_oGp;

    /**
     * Stores global configurations for the test module
     *
     * @var array
     */
    protected $test_settings;

    /**
     * Stores individual configurations/test cases for each method
     *
     * <example>
     * [
     * 'testMethod' => [ // <- name of the method
     *      // test case #1
     *      [
     *          'data' => [
     *                //test data
     *           ],
     *           'successful' => false, // <- assertion
     *      ],
     *      // test case #2
     *      ...
     * ]
     * </example>
     *
     * @var array
     */
    protected $test_data;


    /**
     * This method stores the configuration for the test module and test scenarios for the test methods it has.
     *
     * @return void|mixed
     */
    public abstract function initScenarios();

    /**
     * Method is used to check the correct way of data insertion to bets or wins table depending on the request.
     *
     * @param string $type
     * @param string $bet_mg_id
     * @param string $win_mg_id
     * @param string $user_id
     *
     * @return bool
     */
    public function checkBetsWins(string $type, string $bet_mg_id, string $win_mg_id, string $user_id): bool
    {
        $this->validateType($type);

        // gp doesnt use rounds table
        if ($type === 'bets') {
            $bet = $this->_m_oGp->getBetByMgId($bet_mg_id, 'bets', 'mg_id', $user_id);
            if (empty($bet)) {
                return false;
            }
        }
        else if ($type === 'wins') {
            $win = $this->_m_oGp->getBetByMgId($win_mg_id, 'wins', 'mg_id', $user_id);
            if (empty($win)) {
                return false;
            }
        } else if ($type === 'bets_wins') {
            $bet = $this->_m_oGp->getBetByMgId($bet_mg_id, 'bets', 'mg_id', $user_id);
            $win = $this->_m_oGp->getBetByMgId($win_mg_id, 'wins', 'mg_id', $user_id);
            if (empty($bet) || empty($win)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Method is used to check the correct way of data insertion to rounds table depending on the win or bet request.
     *
     * @param string $type
     * @param string $bet_mg_id
     * @param string $win_mg_id
     * @param string $user_id
     * @param string $prefixed_ext_round_id
     *
     * @return bool
     */
    public function checkRounds(string $type, string $bet_mg_id, string $win_mg_id, string $user_id, string $prefixed_ext_round_id = null): bool
    {
        $this->validateType($type);

        // gp uses rounds table
        if (method_exists( $this->_m_oGp, 'doConfirmByRoundId' ) && $this->_m_oGp->doConfirmByRoundId()) {
            if ($prefixed_ext_round_id === null || $user_id === null) {
                throw new InvalidArgumentException('The $round_id and $user_id parameters are mandatory for GPs that use \'rounds\' table.');
            }

            switch ($type) {
                case 'bets':
                    return $this->checkRoundsBet($bet_mg_id, $user_id, $prefixed_ext_round_id);
                    break;
                case 'wins':
                    return $this->checkRoundsWin($win_mg_id, $user_id, $prefixed_ext_round_id);
                    break;
                case 'bets_wins':
                    return (
                        $this->checkRoundsBet($bet_mg_id, $user_id, $prefixed_ext_round_id)
                        &&
                        $this->checkRoundsWin($win_mg_id, $user_id, $prefixed_ext_round_id)
                    );
                    break;
            }
        } else {
            new RuntimeException('Injected provider does not implement or use "rounds" table!');
        }

        return true;
    }

    private function validateType(string $type) {
        switch ($type) {
            case 'bets':
            case 'wins':
            case 'bets_wins':
                break;
            default:
                throw new InvalidArgumentException('Invalid type argument provided!');
        }
    }

    private function getRoundsRow($bets_or_wins_column_name, $bets_or_wins_id, $ext_round_id, $user_id) {
        $round_entity = phive('SQL')->sh($user_id)->fetchResult("
                SELECT * FROM rounds 
                WHERE {$bets_or_wins_column_name} = '{$bets_or_wins_id}' AND 
                      ext_round_id = '{$ext_round_id}' AND 
                      user_id = '{$user_id}'
                LIMIT 1;
            ");

        return $round_entity;
    }

    private function checkRoundsBet($bet_mg_id, $user_id, $prefixed_ext_round_id) {
        $bet = $this->_m_oGp->getBetByMgId($bet_mg_id, 'bets', 'mg_id', $user_id);
        if (empty($bet)) {
            return false;
        }

        $round_entity = $this->getRoundsRow('bet_id', $bet['id'], $prefixed_ext_round_id, $user_id);
        if (empty($round_entity)) {
            return false;
        }

        return true;
    }

    private function checkRoundsWin($win_mg_id, $user_id, $prefixed_ext_round_id) {
        $win = $this->_m_oGp->getBetByMgId($win_mg_id, 'wins', 'mg_id', $user_id);
        if (empty($win)) {
            return false;
        }

        $round_entity = $this->getRoundsRow('win_id', $win['id'], $prefixed_ext_round_id, $user_id);
        if (empty($round_entity) || empty($round_entity['bet_id'])) {
            return false;
        }

        return true;
    }


    /**
     * Test if the providers correctly processes bet - win scenarios
     *
     * @param null $test_case_type_param
     * @return void
     */
    public abstract function testConfirmedWins($test_case_type_param = null);
}
  
