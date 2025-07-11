<?php

namespace ClosedLoop;

use CasinoCashier;
use Psr\Log\LoggerInterface;

class ClosedLoopFactory
{
    protected LoggerInterface $logger;
    protected CasinoCashier $casinoCashier;

    public function __construct(
        CasinoCashier   $casinoCashier,
        LoggerInterface $logger
    )
    {
        $this->casinoCashier = $casinoCashier;
        $this->logger = $logger;
    }

    public function create(string $loopStartTime): ClosedLoopFacade
    {
        $closedLoopHelper = new ClosedLoopHelper(
            $this->casinoCashier
        );

        $closedLoopDataProvider = new ClosedLoopDataProvider(
            $this->casinoCashier,
            $closedLoopHelper,
            $loopStartTime
        );

        $standardClosedLoop = new StandardClosedLoop(
            $closedLoopDataProvider,
            $closedLoopHelper
        );

        $depositOnlyLoop = new DepositOnlyClosedLoop(
            $this->casinoCashier,
            $standardClosedLoop
        );

        return new ClosedLoopFacade(
            $this->casinoCashier,
            $closedLoopHelper,
            $standardClosedLoop,
            $depositOnlyLoop,
            $this->logger
        );
    }
}
