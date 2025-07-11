<?php

namespace App\Commands;

loadPhive();
use App\Extensions\Database\FManager as DB;
use App\Helpers\Common;
use App\Models\MessagingCampaign;
use App\Models\MessagingCampaignTemplates;
use App\Models\TrophyAwards;
use App\Models\VoucherTemplate;
use App\Repositories\MessagingRepository;
use Carbon\Carbon;
use Ivoba\Silex\Command\Command;
use Silex\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MessagingCommand extends Command
{
    /** @var  OutputInterface $output */
    protected $output;

    /** @var  MessagingRepository $repo */
    protected $repo;


    protected function configure()
    {
        $this->setName("messaging:process")
            ->setDescription("Process scheduled campaigns.")
            ->addArgument('campaign', InputArgument::OPTIONAL, 'Campaign ID for testing purposes or to be able to run one only.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        Common::dumpTbl("messagingProcess", "Ran messaging:process command");
        /** @var Application $app */
        $app = $this->getSilexApplication();

        $this->output = $output;
        $this->repo = new MessagingRepository();

        if (!empty($input->getArgument('campaign'))) {
            $campaigns_template_list = MessagingCampaignTemplates::where('id', $input->getArgument('campaign'))->get();
        } else {
            $campaigns_template_list = $this->generateCampaigns();
            Common::dumpTbl("messagingProcessCount", $campaigns_template_list->count());
        }

        if($campaigns_template_list->isNotEmpty()) {
            $app['monolog']->addInfo("Process scheduled campaigns", [
                'campains_template_ids' => $campaigns_template_list->pluck('id')->toArray()
            ]);
        }

        $pending_campaigns_list = [];
        foreach ($campaigns_template_list as $campaign_template) { //First step to process campaigns so we put then as placed and avoid duplicated
            /* @var MessagingCampaignTemplates $campaign_template */
            $campaign = new MessagingCampaign();
            $campaign->campaign_template_id = $campaign_template->id;
            $campaign->status = MessagingCampaign::STATUS_PLACED;
            $campaign->type = $campaign_template->template_type;

            if (!$campaign_template->typeIsSupported()) {
                $campaign->invalidate("Template type not supported");
                $app['monolog']->addError("Template type not supported", [
                    'campaign_template_id' => $campaign->campaign_template_id,
                    'campaign_type' => $campaign->type
                ]);
                continue;
            }

            $save_res = $campaign->save();

            if($save_res === false) {
                $app['monolog']->addError("Could not process messaging campaign", [
                    'campaign_id' => $campaign->id,
                    'campaign_template_id' => $campaign->campaign_template_id,
                    'error' => $campaign->getFirstError()[0]
                ]);
            }

            $pending_campaigns_list[] = [
                'campaign' => $campaign,
                'campaign_template' => $campaign_template
            ];
        }

        foreach ($pending_campaigns_list as $pending_campaign) {
            Common::dumpTbl("processedPendingCampaign", $pending_campaign);
            try {
                if ($pending_campaign['campaign_template']->isBonus()) {
                    $this->processBonusCampaign($pending_campaign['campaign_template'], $pending_campaign['campaign']);
                } elseif ($pending_campaign['campaign_template']->isVoucher()) {
                    $this->processVoucherCampaign($pending_campaign['campaign_template'], $pending_campaign['campaign']);
                } else {
                    $this->repo->process($this->getSilexApplication(), $pending_campaign['campaign_template'], $pending_campaign['campaign']);
                }
            } catch (\Exception $e) {
                $pending_campaign['campaign']->invalidate($e->getMessage());
            }
        }

        return 0;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function generateCampaigns()
    {
        $table = "messaging_campaign_templates";
        $query = "
            SELECT mct.*
            FROM messaging_campaign_templates mct
            LEFT JOIN (
                SELECT 
                    campaign_template_id AS template_id, 
                    COUNT(*) AS count,
                    MAX(created_at) AS created_at
                FROM messaging_campaigns
                GROUP BY campaign_template_id
            ) AS sent
                ON mct.id = sent.template_id 
            WHERE (
                (
                    (
                        mct.recurring_type = 'week' AND 
                        mct.recurring_days LIKE :week_day
                    ) OR
                    (
                        mct.recurring_type = 'month' AND 
                        mct.recurring_days LIKE :month_day
                    ) OR
                    (
                      mct.recurring_type = 'day'
                    )
                )
                AND mct.start_time < :start_time 
                AND mct.recurring_end_date > :recurring_end_date
                AND (
                    sent.created_at IS NULL
                    OR DATE(sent.created_at) != DATE(NOW())
                  )
            ) 
            OR (
                recurring_type = 'one' AND 
                start_date <= :start_date AND 
                start_time < :start_time_one AND (
                    sent.count IS NULL
                    OR sent.count = 0
                  )
            )
        ";

        $bindings = [
            'week_day' => '%' . date('N') . '%',
            'month_day' => '%' . date('d') . '%',
            'start_date' => Carbon::now()->toDateString(),
            'start_time' => Carbon::now()->toTimeString(),
            'start_time_one' => Carbon::now()->toTimeString(),
            'recurring_end_date' => Carbon::now()->toDateTimeString()
        ];

        Common::dumpTbl("generateCampaigns", [
            'table' => $table,
            'query' => $query,
            'bindings' => $bindings
        ]);

        return MessagingCampaignTemplates::query()->hydrate(
            DB::shsSelect($table, $query, $bindings)
        );
    }

    /**
     * @param MessagingCampaignTemplates $campaign_template
     * @param MessagingCampaign $campaign
     * @return bool
     * @throws \Exception
     */
    private function processBonusCampaign($campaign_template, $campaign)
    {
        $bonus_type = $this->repo->generateBonusFromTemplate($campaign_template->bonusTemplate()->first());
        if (is_string($bonus_type)) {
            $campaign->invalidate($bonus_type);
            return false;
        }

        if ($bonus_type) {
            $campaign->bonus_id = $bonus_type->getKey();
            $this->repo->process($this->getSilexApplication(), $campaign_template, $campaign, $bonus_type, $bonus_type->reward()->first());
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param MessagingCampaignTemplates $campaign_template
     * @param MessagingCampaign $campaign
     * @return bool
     * @throws \Exception
     */
    private function processVoucherCampaign($campaign_template, $campaign)
    {
        /** @var VoucherTemplate $voucher_template */
        $voucher_template = $campaign_template->voucherTemplate()->first();

        if ($voucher_template) {
            $campaign->voucher_name = $voucher_template->template_name;
            $campaign->save();
        }

        if (!empty($voucher_template->bonus_type_template_id)) {
            $bonus_type = $this->repo->generateBonusFromTemplate($voucher_template->bonusTypeTemplate()->first());
            if (is_string($bonus_type)) {
                $campaign->invalidate($bonus_type);
                $bonus_type = null;
            }
            $reward = null;
            $res = $this->repo->createVoucherSeries($voucher_template, $bonus_type, false, false, true);
        } elseif (!empty($voucher_template->trophy_award_id)) {
            $bonus_type = null;
            $reward = TrophyAwards::find($voucher_template->trophy_award_id);
            $res = $this->repo->createVoucherSeries($voucher_template, null, false, false, true);
        } else {
            $campaign->invalidate("Voucher template bonus and reward id empty.");
            return false;
        }

        if (!is_string($res) and $res->count > 0) {
            $this->repo->process($this->getSilexApplication(), $campaign_template, $campaign, $bonus_type, $reward, $res);
            return true;
        } else {
            $campaign->invalidate($res);
            return false;
        }
    }
}
