<?php

namespace DBUserHandler\Session;

interface WinlossBalanceInterface
{
    /**
     * Returns amount of win in cents
     * @return int
     */
    public function getWin(): int;

    /**
     * Returns amount of loss in cents
     * @return int
     */
    public function getLoss(): int;

    /**
     * Returns difference between win and loss in cents
     * @return int
     */
    public function getTotal(): int;

    /**
     * @param int $user_game_session_id
     * @param string $refresh_type
     *          win - increase win balance;
     *          loss - increase loss balance;
     *          reset - set win and los balance as 0;
     * @param int $amount
     * @return WinlossBalanceInterface
     */
    public function refresh(int $user_game_session_id, string $refresh_type, int $amount = 0): self;

    /**
     * Determinate user game session ID to retrieve balance within the unique game session
     * @param int $user_game_session_id
     * @return WinlossBalanceInterface
     */
    public function byUserGameSession(int $user_game_session_id): self;
}