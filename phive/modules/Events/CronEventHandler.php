<?php

class CronEventHandler
{
    private DBUserHandler $dbUser;
    private JpWheel $jpWheel;
    private MicroGames $microGames;
    private Logger $logger;

    public function __construct()
    {
        $this->dbUser = phive('DBUserHandler');
        $this->jpWheel = phive('DBUserHandler/JpWheel');
        $this->microGames = phive('MicroGames');
        $this->logger = phive('Logger')->getLogger('queue_messages');
    }

    public function onCronTimeoutSessionsEvent(): void
    {
        $logId = uniqid();
        $this->logger->debug("CronEventHandler::onCronTimeoutSessionsEvent", ["start", $logId]);
        $this->dbUser->timeoutSessions($logId);
        $this->dbUser->trimEvents(99, $logId);
        $this->microGames->timeoutGameSessions(32400, $logId);
        $this->logger->debug("CronEventHandler::onCronTimeoutSessionsEvent", ["end", $logId]);
    }

    public function onCronUpdateJpValuesEvent(): void
    {
        $this->logger->debug("CronEventHandler::onCronUpdateJpValuesEvent", ["start"]);
        $this->jpWheel->updateJpValues();
        $this->logger->debug("CronEventHandler::onCronUpdateJpValuesEvent", ["end"]);
    }

    public function onCronDailyGamesRecommendationsEvent($delay, $retries)
    {
        try {
            phive('GamesRecommendations')->collectDailyData();
        } catch (Exception $e) {
            phive('GamesRecommendations')->collectDailyDataCron($delay, $retries, $e->getMessage());
        }
    }
}
