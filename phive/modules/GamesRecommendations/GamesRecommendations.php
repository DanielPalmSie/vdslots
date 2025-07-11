<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../api/ExtModule.php';

use GamesRecommendations\Traits\ApiClientTrait;
use GamesRecommendations\Traits\S3UploadTrait;
use GamesRecommendations\Traits\DataCollectionTrait;


class GamesRecommendations extends ExtModule
{
    use S3UploadTrait;
    use DataCollectionTrait;
    use ApiClientTrait;

    private bool $enabled;
    private bool $testPage;

    private string $brandId;

    private bool $cronEnabled;

    private const DATA_FILES = [
        'bets' => 'bets.csv',
        'games' => 'actual-game-list.csv',
        'players' => 'players.csv'
    ];

    public const MAX_CRON_RETRIES = 5;
    public const CRON_RETRY_DELAY = 3600;

    public function __construct()
    {
        parent::__construct();

        $this->enabled = $this->getSetting('enabled', false);
        $this->testPage = $this->getSetting('test_page', false);
        $this->setApiUrl($this->getSetting('api_url'));
        $this->brandId = (string)(phive('Distributed')->getLocalBrandId() ?? 100);
        $this->cronEnabled = $this->getSetting('cron_enabled', false);
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled === true;
    }

    /**
     * @return bool
     */
    public function testPageAllowed(): bool
    {
        return $this->getSetting('test_page') === true;
    }

    public function collectDailyDataCron($delay = 0, $retries = 0, $last_error = "")
    {
        if (!$this->cronEnabled || !$this->isEnabled()) {
            return;
        }
        if ($retries >= self::MAX_CRON_RETRIES) {
            $subject = "<p>Cron failed: Game Recommendations Daily Data Error</p>";
            $content = "<p>The daily collection of data failed. Please inform dev team.</p>";
            $content .= "<pre>$last_error</pre>";
            $to = phive()->getSetting('dev_support_mail') ?? 'devsupport@videoslots.com';

            phive('MailHandler2')->mailLocal($subject, $content, '', $to);
            return;
        }
        $retries = $retries + 1;
        phive()->fire('cron', 'cronDailyGamesRecommendationsEvent', [self::CRON_RETRY_DELAY, $retries], $delay, function () use ($retries) {
            phive('Events/CronEventHandler')->onCronDailyGamesRecommendationsEvent(self::CRON_RETRY_DELAY, $retries);
        });
    }


    /**
     * Collect and upload daily data
     * @return bool
     * @throws Exception
     */
    public function collectDailyData(): bool
    {
        try {

            $daysRange = (int)$this->getSetting('data_collection')['days_range'];
            $endDate = new DateTime('yesterday');
            $startDate = clone $endDate;
            $startDate->modify("-{$daysRange} days");

            return $this->collectAndUploadData($startDate, $endDate, 'daily');
        } catch (Exception $e) {
            error_log("Failed to collect daily data: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Collect and upload full historical data
     * @return bool
     * @throws Exception
     */
    public function collectFullData(): bool
    {
        try {
            $daysRange = (int)$this->getSetting('data_collection')['full_range_days'];
            $endDate = new DateTime('yesterday');
            $startDate = clone $endDate;
            $startDate->modify("-{$daysRange} days");

            return $this->collectAndUploadData($startDate, $endDate, 'full');
        } catch (Exception $e) {
            error_log("Failed to collect full data: " . $e->getMessage());
            $this->log("collect-full-data-error", $e->getMessage());
            throw $e;
        }
    }

    /**
     * Helper function to collect and upload data
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @param string $context
     * @return bool
     * @throws Exception
     */
    private function collectAndUploadData(DateTime $startDate, DateTime $endDate, string $context): bool
    {
        try {
            $this->cleanupFiles();

            // Collect all data types
            $collectionResults = $this->collectAllData($startDate, $endDate);

            // Check if all data types were collected successfully
            $files = $this->checkFilesBeforeUpload($collectionResults, $context);

            // Upload all files to S3
            $this->uploadFiles($files);

            // Cleanup after successful upload
            $this->cleanupFiles();
            $this->log('collect-data-ok', $endDate->format('d-m-Y'));
            return true;
        } catch (Exception $e) {
            $this->cleanupFiles();
            $errorMsg = $e->getMessage();
            error_log("Failed to collect data: " . $errorMsg);
            $this->log("collect-data-error", $errorMsg);
            throw $e;
        }
    }

    /**
     * Helper function to collect all data types
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return array
     */
    private function collectAllData(DateTime $startDate, DateTime $endDate): array
    {
        return [
            'bets' => $this->collectBetsData($startDate, $endDate),
            'games' => $this->collectGamesData(),
            'players' => $this->collectPlayersData($startDate, $endDate)
        ];
    }

    /**
     * Helper function to check files before upload
     * If any file is missing or too small, an exception is thrown. This could be due to a failed collection
     *
     * @param $collectionResults
     * @param $context
     * @return array
     * @throws Exception
     */
    private function checkFilesBeforeUpload($collectionResults, $context): array
    {
        $files = [];
        foreach ($collectionResults as $type => $result) {
            if ($result === false) {
                throw new Exception("Failed to collect data for {$type} during {$context} collection");
            }

            // Get the file path for each result
            $filePath = $this->getFilePath($type);
            $files[$type] = $filePath;

            // Check if the file is at least 5KB in size
            if (filesize($filePath) < 5120) {
                throw new Exception("File for {$type} is too small, less than 5KB. Aborting upload.");
            }
        }

        return $files;
    }

    /**
     * @param array $files
     * @return void
     * @throws Exception
     */
    private function uploadFiles(array $files = [])
    {
        foreach (self::DATA_FILES as $type => $filename) {
            if (isset($files[$type])) {
                $this->uploadToS3($files[$type], $filename);
            }
        }

    }

    protected function log($tag, $message)
    {
        phive()->dumpTbl("GR:$tag", $message);
    }

    protected function getBrandId(): string
    {
        return $this->brandId;
    }


    /**
     * List files in S3 bucket
     * @return array
     * @throws Exception
     */
    public function listS3Files(): array
    {
        try {
            return $this->listS3Contents();
        } catch (Exception $e) {
            error_log("Failed to list S3 files: " . $e->getMessage());
            throw $e;
        }
    }
}
