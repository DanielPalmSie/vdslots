<?php

namespace GamesRecommendations\Traits;

use Aws\S3\S3Client;
use Exception;

trait S3UploadTrait
{
    private S3Client $s3Client;

    private string $folder;

    abstract protected function getBrandId();

    /**
     * Initialize S3 client
     * @throws Exception
     */
    private function initializeS3Client(): void
    {
        if (!isset($this->s3Client)) {
            $awsConfig = $this->getSetting('aws');

            if (empty($awsConfig)) {
                throw new Exception('AWS configuration is missing');
            }

            $folderBase = phive('BrandedConfig')->isProduction() ? 'production' : 'staging';
            $this->folder = "$folderBase/{$this->getBrandId()}/";

            $this->s3Client = new S3Client([
                'version' => 'latest',
                'region'  => $awsConfig['region'],
                'credentials' => [
                    'key'    => $awsConfig['key'],
                    'secret' => $awsConfig['secret']
                ]
            ]);
        }
    }

    /**
     * Upload file to S3
     * @param string $localFile
     * @param string $fileName
     * @return bool
     * @throws Exception
     */
    private function uploadToS3(string $localFile, string $fileName): bool
    {
        try {
            $this->initializeS3Client();

            $awsConfig = $this->getSetting('aws');
            $key = $this->folder . $fileName;

            $this->s3Client->putObject([
                'Bucket' => $awsConfig['bucket'],
                'Key'    => $key,
                'Body'   => fopen($localFile, 'rb')
            ]);

            return true;
        } catch (Exception $e) {
            error_log("Failed to upload {$fileName} to S3: " . $e->getMessage());
            throw $e;
        }
    }

    /*
     * Download the files from S3 to the directory specified
     *
     * p.e: $result = phive('GamesRecommendations')->downloadFilesFromS3(__DIR__ . '/data');
     * Result:
         Array
                (
                    [0] => /var/www/videoslots/diamondbet/test/data/actual-game-list.csv
                    [1] => /var/www/videoslots/diamondbet/test/data/bets.csv
                    [2] => /var/www/videoslots/diamondbet/test/data/players.csv
                )
     *
     * @param string $localDir
     * @return array
     * @throws Exception
     */
    public function downloadFilesFromS3(string $localDir): array
    {
        try {
            $this->initializeS3Client();

            $awsConfig = $this->getSetting('aws');

            $results = $this->s3Client->listObjects([
                'Bucket' => $awsConfig['bucket'],
                'Prefix' => $this->folder
            ]);

            $localFiles = [];
            if (isset($results['Contents'])) {
                foreach ($results['Contents'] as $object) {
                    $key = $object['Key'];
                    $localFilePath = $localDir . '/' . basename($key);

                    $this->s3Client->getObject([
                        'Bucket' => $awsConfig['bucket'],
                        'Key'    => $key,
                        'SaveAs' => $localFilePath
                    ]);

                    $localFiles[] = $localFilePath;
                }
            }

            return $localFiles;
        } catch (Exception $e) {
            error_log("Failed to download files from S3: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * List contents of S3 bucket/folder
     * @return array
     * @throws Exception
     */
    private function listS3Contents(): array
    {
        try {
            $this->initializeS3Client();

            $awsConfig = $this->getSetting('aws');

            $results = $this->s3Client->listObjects([
                'Bucket' => $awsConfig['bucket'],
                'Prefix' => $this->folder
            ]);

            return $results['Contents'] ?? [];
        } catch (Exception $e) {
            error_log("Failed to list S3 contents: " . $e->getMessage());
            throw $e;
        }
    }
}
