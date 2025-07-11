<?php

namespace ClosedLoop;

use CasinoCashier;
use DBUser;
use Psr\Log\LoggerInterface;

class ClosedLoopFacade
{
    private LoggerInterface $logger;
    private CasinoCashier $casinoCashier;
    private ClosedLoopHelper $closedLoopHelper;
    private StandardClosedLoop $standardClosedLoop;
    private DepositOnlyClosedLoop $depositOnlyClosedLoop;

    private array $rawClosedLoopData = [];

    public function __construct(
        CasinoCashier         $casinoCashier,
        ClosedLoopHelper      $closedLoopHelper,
        StandardClosedLoop    $standardClosedLoop,
        DepositOnlyClosedLoop $depositOnlyClosedLoop,
        LoggerInterface       $logger
    )
    {
        $this->logger = $logger;
        $this->casinoCashier = $casinoCashier;
        $this->closedLoopHelper = $closedLoopHelper;
        $this->standardClosedLoop = $standardClosedLoop;
        $this->depositOnlyClosedLoop = $depositOnlyClosedLoop;
    }

    public function closedLoopData(DBUser $user): array
    {
        $closedLoopData = $this->standardClosedLoop->process($user);

        if ($this->depositOnlyClosedLoop->applicable()) {
            $closedLoopData = $this->depositOnlyClosedLoop->process($user, $closedLoopData);
        }

        // TODO BAN-12013: Investigate conflicts with identical account identifiers while flattening the array; consider using grouped loop data.
        $closedLoopData = array_replace(...array_values($closedLoopData)) ?? []; // array_replace used because of integer key

        foreach ($closedLoopData as $identifier => $loopData) {
            // Adding the current status of the loop
            $closedLoopData[$identifier]['status'] = $this->closedLoopHelper->determineLoopStatus($loopData);

            if ($this->closedLoopHelper->skipClosedLoop($user, $loopData['source_psp'], $identifier)) {
                unset($closedLoopData[$identifier]);
            }
        }

        $this->logger->debug("closed_loop_details", [
            'user_id' => $user->getId(),
            'closed_loop_data' => $closedLoopData
        ]);

        return $closedLoopData;
    }

    public function getApplicableClosedLoopData(DBUser $user): array
    {
        if ($this->casinoCashier->getAntiFraudScheme($user) != 'closed_loop') {
            return [];
        }

        $closedLoopData = $this->closedLoopData($user);
        $this->setRawClosedLoopData($closedLoopData);

        $applicableClosedLoopData = [];
        foreach ($closedLoopData as $identifier => $loopData) {
            if ($loopData['status'] !== ClosedLoopHelper::STATUS_CLOSED) {
                $applicableClosedLoopData[$identifier] = $loopData;
            }
        }

        return $applicableClosedLoopData;
    }

    public function prepareWithdrawOptionsForDisplay(
        DBUser $user,
        array  $cards,
        array  $bankAccounts,
        array  $allowedWdPsps
    ): array
    {
        if ($this->casinoCashier->getAntiFraudScheme($user) !== 'closed_loop') {
            return [$cards, $bankAccounts, $allowedWdPsps, false]; // Closed Loop not applicable, do nothing
        }

        $closedLoopData = $this->getApplicableClosedLoopData($user);

        // All loops are closed, so we will display all available options, including cards, banks, and other relevant details.
        if (empty($closedLoopData)) {
            return $this->prepareAllOptionsForDisplay($user, $cards, $bankAccounts, $allowedWdPsps, $closedLoopData);
        }

        // If any loop is open or pending, we only display those loops along with their relevant details.
        return $this->prepareOpenOrPendingLoopOptionsForDisplay($user, $cards, $bankAccounts, $allowedWdPsps, $closedLoopData);
    }

    private function prepareAllOptionsForDisplay(
        DBUser $user,
        array  $cards,
        array  $bankAccounts,
        array  $allowedWdPsps,
        array  $closedLoopData
    ): array
    {
        $displayCards = $displayBanks = [];

        foreach ($cards as $card) {
            $key = $card['sub_supplier'] === 'applepay' ? 'applepay' : 'ccard';
            $displayCards[$key][] = $card;
        }

        foreach ($bankAccounts as $account) {
            $displayBanks['banks'][$account['supplier']][] = $account;
        }

        $pspsToDisplay = $this->depositOnlyClosedLoop->filterDepositOnlyLoopOptions(
            $user,
            $allowedWdPsps,
            $closedLoopData,
            $this->getRawClosedLoopData()
        );

        return [$displayCards, $displayBanks, $pspsToDisplay, false];
    }

    private function prepareOpenOrPendingLoopOptionsForDisplay(
        DBUser $user,
        array  $cards,
        array  $bankAccounts,
        array  $allowedWdPsps,
        array  $closedLoopData
    ): array
    {
        $displayCards = $displayBanks = $pspsToDisplay = [];

        foreach ($closedLoopData as $identifier => $loopData) {
            $loopData['remaining_amount'] =
                $this->closedLoopHelper->calculateOverrideRemainingClAmount($loopData, $user);

            $additionalLoopInfo = [
                'closed_loop_cents' => $loopData['status'] === ClosedLoopHelper::STATUS_OPEN
                    ? $loopData['remaining_amount']
                    : $loopData['status'],
                'closed_loop_formatted' => $loopData['status'] === ClosedLoopHelper::STATUS_PENDING_DISABLED
                    ? '- Pending Withdrawal'
                    : rnfCents($loopData['remaining_amount'])
            ];

            $sourceType = $loopData['source_type'];

            // TODO: To be handled (removed) in BAN-12402
            if ($loopData['source_psp'] == 'swish') {
                $sourceType = 'bank';
            }

            switch ($sourceType) {
                case 'card':
                case 'applepay':
                    $key = $sourceType === 'card' ? 'ccard' : 'applepay';
                    $pspsToDisplay[$key] = $allowedWdPsps[$key];

                    foreach ($cards as $card) {
                        if ($identifier == $card['card_num']) {
                            $displayCards[$key][] = array_merge($card, $additionalLoopInfo);
                            break;
                        }
                    }

                    break;

                case 'bank':
                    $source_psp = $loopData['source_psp'];
                    $pspsToDisplay[$source_psp] = $allowedWdPsps[$source_psp];

                    foreach ($bankAccounts as $account) {
                        if (
                            $identifier == $account['account_ref'] && // $identifier is sometimes an integer.
                            $this->casinoCashier->normalizeDepositScheme($loopData['source_scheme']) === $account['sub_supplier']
                        ) {
                            $displayBanks['banks'][$source_psp][] = array_merge($account, $additionalLoopInfo);
                            break;
                        }
                    }

                    break;

                default:
                    foreach ($allowedWdPsps as $psp => $psp_data) {
                        if ($identifier == $psp || $identifier === $psp_data['option_of']) {
                            $pspsToDisplay[$psp] = array_merge($psp_data, $additionalLoopInfo);
                        }
                    }

                    break;
            }
        }

        $pspsToDisplay = $this->depositOnlyClosedLoop->filterDepositOnlyLoopOptions(
            $user,
            array_filter($pspsToDisplay),
            $closedLoopData,
            $this->getRawClosedLoopData(),
            $allowedWdPsps
        );

        return [$displayCards, $displayBanks, $pspsToDisplay, true];
    }

    private function setRawClosedLoopData($rawClosedLoop): void
    {
        $this->rawClosedLoopData = $rawClosedLoop;
    }

    private function getRawClosedLoopData(): array
    {
        return $this->rawClosedLoopData;
    }
}
