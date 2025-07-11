<?php
/**
 * This script only has to be run one time, after adding the new column `desktop_or_mobile` to the table tournaments and tournament_tpls
 */

namespace App\Commands\Helpers;

use Illuminate\Filesystem\Filesystem;
use Ivoba\Silex\Command\Command;
use Silex\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Models\Tournament;
use App\Models\TournamentTemplate;
use App\Repositories\GameRepository;
use App\Extensions\Database\FManager as DB;

class UpdateTournamentsForDesktopOrMobileCommand extends Command
{
    protected function configure()
    {
        $this->setName("tournaments:update_desktop_or_mobile")
            ->setDescription("Update existing records in tables tournaments and tournament_tpls to have the correct value for desktop_or_mobile.");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $repo = new GameRepository();

        $micro_games = DB::table('micro_games')->get();
        foreach ($micro_games as $micro_game) {
            $desktop_or_mobile = $repo->isGameForDesktopOrMobile($micro_game->ext_game_name);

            // Update templates with the same game_ref
            TournamentTemplate::where('game_ref', $micro_game->ext_game_name)
                        ->update(['desktop_or_mobile' => $desktop_or_mobile]);

            // Update tournaments with the same game_ref
            Tournament::where('game_ref', $micro_game->ext_game_name)
                        ->update(['desktop_or_mobile' => $desktop_or_mobile]);
        }

        return 0;
    }
}