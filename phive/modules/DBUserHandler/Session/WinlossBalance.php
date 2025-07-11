<?php

namespace DBUserHandler\Session;

/**
 * Win/loss balance manager. Use for obtaining win, loss, total games balance
 * in overall (including all active game sessions) and by a certain game.
 * Calculation based on obtained data from Redis cache store by key 'winloss'.
 *
 */
class WinlossBalance implements WinlossBalanceInterface
{
    public const TYPE_WIN = 'win';
    public const TYPE_LOSS = 'loss';
    public const TYPE_TOTAL = 'total';
    public const TYPE_RESET = 'reset';

    /**
     * @var \DBUser
     */
    private \DBUser $user;

    /**
     * @var int|null
     */
    private ?int $user_game_session_id;

    /**
     * Data provided from cache store
     * @var array|mixed
     */
    private array $data = [];

    /**
     * Calculated result
     * @var array|int[]
     */
    private array $balance = [];

    /**
     * @param \DBUser $user
     * @param int|null $user_game_session_id - provide if you need to get balance by certain user game session
     * @see byUserGameSession() - alternative method to set $user_game_session_id
     */
    public function __construct(\DBUser $user, ?int $user_game_session_id = null)
    {
        $this->user = $user;
        $this->user_game_session_id = $user_game_session_id;
    }

    /**
     * @inheritDoc
     */
    public function getWin(): int
    {
        $this->loadData();

        return $this->balance[static::TYPE_WIN];
    }

    /**
     * @inheritDoc
     */
    public function getLoss(): int
    {
        $this->loadData();

        return $this->balance[static::TYPE_LOSS];
    }

    /**
     * @inheritDoc
     */
    public function getTotal(): int
    {
        $this->loadData();

        return $this->balance[static::TYPE_TOTAL];
    }

    /**
     * @inheritDoc
     */
    public function refresh(int $user_game_session_id, string $refresh_type, int $amount = 0): self
    {
        try {
            $currentWinlossBalance = json_decode(
                phM('hget', $this->getStoreKey(), $user_game_session_id),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException $e) {
            $currentWinlossBalance = [];
        }

        if (empty($currentWinlossBalance) || $refresh_type === static::TYPE_RESET) {
            $currentWinlossBalance = [
                static::TYPE_WIN => 0,
                static::TYPE_LOSS => 0,
            ];
        }

        if (in_array($refresh_type, [static::TYPE_WIN, static::TYPE_LOSS])) {
            $currentWinlossBalance[$refresh_type] += $amount;
        }

        // refresh the Redis store balance value
        phM('hset', $this->getStoreKey(), $user_game_session_id, json_encode($currentWinlossBalance));
        $this->resetData();

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function byUserGameSession(int $user_game_session_id): WinlossBalanceInterface
    {
        $this->user_game_session_id = $user_game_session_id;

        return $this;
    }

    /**
     * Calculation based on obtained data from 'winloss' which stored in Redis cache store.
     * Set balance by specific game session if user_game_session_id is provided
     * Otherwise set total balance from all games
     *
     * @return void
     */
    private function calculateBalance(): void
    {
        if ($this->user_game_session_id && ! empty($this->data[$this->user_game_session_id])) {
            $winQty = (int) $this->data[$this->user_game_session_id][static::TYPE_WIN] ?? 0;
            $lossQty = (int) $this->data[$this->user_game_session_id][static::TYPE_LOSS] ?? 0;
        } else {
            $winQty = array_sum(array_column($this->data, static::TYPE_WIN)) ?? 0;
            $lossQty = array_sum(array_column($this->data, static::TYPE_LOSS)) ?? 0;
        }

        $this->balance[static::TYPE_WIN] = $winQty;
        $this->balance[static::TYPE_LOSS] = $lossQty;
        $this->balance[static::TYPE_TOTAL] = $winQty - $lossQty;
    }

    /**
     * Load data from cache store and set balance
     * @return void
     */
    private function loadData(): void
    {
        if (empty($this->data)) {
            // this is the valid way to get associative array from Redis store
            $data = phM('hgetall', $this->getStoreKey());
            $data = is_array($data) ? $data : [];


            $callback = function ($value) {
                try {
                    return json_decode(
                        $value,
                        true,
                        512,
                        JSON_THROW_ON_ERROR
                    );
                } catch (\JsonException $e) {
                    return [
                        static::TYPE_WIN => 0,
                        static::TYPE_LOSS => 0,
                    ];
                }
            };
            $this->data = array_map($callback, $data) ?? [];
            $this->calculateBalance();
        }
    }

    /**
     * @return string
     */
    private function getStoreKey(): string
    {
        return mKey($this->user->getId(), 'winloss');
    }

    /**
     * @return void
     */
    private function resetData(): void
    {
        $this->data = [];
    }
}
