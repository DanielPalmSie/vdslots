<?php

namespace App\Observers;

use App\Constants\RibbonPic;
use App\Models\Game;
use App\Models\GameTag;
use App\Models\GameTagConnection;
use App\Constants\GameTag as GameTagConstant;

class GameTagConnectionObserver
{
    /**
     * @var array
     */
    private array $newGameTagIds;

    public function __construct()
    {
        $this->newGameTagIds = $this->getNewGameTags();
    }

    /**
     * Handle the GameTagConnection "deleted" event.
     *
     * @param GameTagConnection $gameTagConnection
     * @return void
     */
    public function deleted(GameTagConnection $gameTagConnection): void
    {
        if (empty($this->newGameTagIds) || !in_array($gameTagConnection->tag_id, $this->newGameTagIds)) {
            return;
        }

        Game::query()
            ->where('id', '=', $gameTagConnection->game_id)
            ->where('ribbon_pic', '=', RibbonPic::NEW_GAME)
            ->update(['ribbon_pic' => null]);
    }

    /**
     * Handle the GameTagConnection "created" event.
     *
     * @param GameTagConnection $gameTagConnection
     * @return void
     */
    public function created(GameTagConnection $gameTagConnection): void
    {
        if (empty($this->newGameTagIds) || !in_array($gameTagConnection->tag_id, $this->newGameTagIds)) {
            return;
        }

        Game::query()
            ->where('id', '=', $gameTagConnection->game_id)
            ->where('ribbon_pic', '=', '')
            ->update(['ribbon_pic' => RibbonPic::NEW_GAME]);
    }

    /**
     * Get the "new.cgames" and "esnew.cgames" tags.
     *
     * @return array
     */
    private function getNewGameTags(): array
    {
        return GameTag::query()
            ->whereIn('alias',  [GameTagConstant::NEW_GAME, GameTagConstant::ES_NEW_GAME])
            ->pluck('id')
            ->toArray();
    }
}
