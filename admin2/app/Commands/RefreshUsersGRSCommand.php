<?php

namespace App\Commands;

use Carbon\Carbon;
use App\Helpers\GrsHelper;
use App\Models\RiskProfileRating;
use App\Models\RiskProfileRatingLog;
use App\Repositories\RiskProfileRatingRepository;
use Ivoba\Silex\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Exception;

/**
 * The risk_profile_rating_log table keeps rating_tag
 * [Social Gambler, Low Risk, Medium Risk, High Risk] which is relevant to Rating score range 0 - 100
 * The reason for refreshing the Rating Tag could be changing the Rating score range per jurisdiction.
 * In this case we can refresh the Rating Tag within the new Rating Range.
 * Example:
 * OLD RG Social Gambler range 0 - 59
 * NEW RG Social Gambler range 0 - 40
 * After refreshing users with RG rating > 40 will be tagged as Low Risk (the next risk level)
 */
class RefreshUsersGRSCommand extends Command
{
    private const DEFAULT_TYPE = 'ALL';
    private const LIMIT = 500;

    /**
     * Total amount of updated rows
     * @var int
     */
    private int $counter = 0;
    private array $types = [
        'ALL',
        'AML',
        'RG',
    ];

    private string $types_string = '';

    private array $riskScoreSettingByJurisdiction = [];

    protected function configure()
    {
        $this->types_string = implode('|', $this->types);
        $this->setName("grs:refresh-tags")
            ->setDescription("Refresh users rating tags for AML and RG")
            ->addArgument('type', InputArgument::OPTIONAL,
                "Refresh rating tags for specific type [{$this->types_string}]")
            ->addOption(
                'force',
                '-f',
                InputOption::VALUE_NONE,
                "Force update rating tags for specific type [{$this->types_string}]. Example: --force",
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $start_time = Carbon::now();
            $type = $input->getArgument('type') ?? static::DEFAULT_TYPE;
            $force_update = boolval($input->getOption('force'));

            if (!in_array($type, $this->types)) {
                $output->writeln("The Type is not valid. Acceptable types are {$this->types_string}");
                return 1;
            }

            $query = RiskProfileRatingLog::orderBy(['user_id', 'id'])->whereHas('user');

            if (in_array($type, ['AML', 'RG'])) {
                $query->where('rating_type', $type);
            }

            $last_user_id = "0";
            $jurisdiction = 'MGA';
            $callback = function ($grs) use (&$last_user_id, &$jurisdiction) {
                foreach ($grs as $rating_score_log) {

                    if ($rating_score_log->user_id != $last_user_id) {
                        $user = cu($rating_score_log->user_id);

                        if (empty($user)) {
                            continue;
                        }
                        $last_user_id = $rating_score_log->user_id;
                        $jurisdiction = $user->getJurisdiction();
                    }

                    $rating_score_settings = $this->getRatingScoreSettings(
                        $jurisdiction,
                        $rating_score_log->rating_type
                    );
                    $this->updateRatingTag($rating_score_log, $rating_score_settings);
                }
                usleep(50000);
            };

            if (!$force_update) {
                $query->whereNull('rating_tag');
                $q = $query->clone();
                while ($q->count()) {
                    $query->chunk(static::LIMIT, $callback);
                }
            } else {
                $query->chunk(static::LIMIT, $callback);
            }

            $execution_time = $start_time->floatDiffInSeconds(Carbon::now());
            $output->writeln("Rating Tags for {$type} have been refreshed. Execution time (sec): {$execution_time}");
            $output->writeln("Total amount rows have been updated: {$this->counter}");
        } catch (Exception $e) {
            $output->writeln("Error: {$e->getMessage()}");
            return 1;
        }
        return 0;
    }

    /**
     * @param string $jurisdiction
     * @param string $rating_type
     * @return array
     * @throws \Exception
     */
    private function getRatingScoreSettings(string $jurisdiction, string $rating_type): array
    {
        if (!empty($this->riskScoreSettingByJurisdiction[$jurisdiction][$rating_type])) {
            return $this->riskScoreSettingByJurisdiction[$jurisdiction][$rating_type];
        }

        $this->riskScoreSettingByJurisdiction[$jurisdiction][$rating_type] = RiskProfileRatingRepository::getCategorySettings(
            RiskProfileRating::RATING_SCORE_PARENT_CATEGORY,
            $jurisdiction,
            $rating_type
        );

        return $this->riskScoreSettingByJurisdiction[$jurisdiction][$rating_type];
    }

    /**
     * @param RiskProfileRatingLog $rating_score_log
     * @param array $rating_score_settings
     * @return void
     */
    private function updateRatingTag(RiskProfileRatingLog $rating_score_log, array $rating_score_settings = []): void
    {
        foreach ($rating_score_settings as $key => $item) {
            $start_score = ($key == 0) ? 0 : $rating_score_settings[$key - 1]['score'] + 1;

            if (GrsHelper::isUsersRatingMatchingScore($rating_score_log->rating, $start_score, $item['score'])) {
                $rating_score_log->update(['rating_tag' => $item['title']]);
                $this->counter++;
                break;
            }
        }
    }
}
