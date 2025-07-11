<?php


require_once __DIR__ . '/../../../phive/phive.php';
require_once __DIR__ . '/../../../phive/vendor/autoload.php';

use \GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DomCrawler\Crawler;


/**
 * Global GuzzleClient
 **/
$httpClient = new GuzzleClient();


/**
 * Check if url is accessible or not
 *
 * @param $url = The url to check
 **/
function isUrlAccessible($url) {

    global $httpClient;  // Reference the global instance

    try {
        $response = $httpClient->request('GET', $url);
        // Check if the response status code is 200 (OK)
        return $response->getStatusCode() === 200;
    } catch (RequestException $e) {
        // If there was an exception, the URL is not accessible
        return false;
    }
}

function isAbsolute($url): bool
{
    return isset(parse_url($url)['host']);
}

function getPageResponse($url) {
    try {

        global $httpClient; // Reference the global instance
        // Fetch the webpage content
        $response = $httpClient->request('GET', $url, [
            'timeout' => 30, // Total request timeout in seconds
            'connect_timeout' => 20, // Connection timeout in seconds
        ]);

        // Check if the request was successful
        if ($response->getStatusCode() === 200) {
            // Get the HTML content
            $html = (string) $response->getBody();

            // Initialize DomCrawler with the HTML content
            return new Crawler($html);
        } else {
            echo "Failed to fetch the webpage. Status code: " . $response->getStatusCode();
        }
    } catch (Exception $e) {
        echo "An error occurred: " . $e->getMessage();
    }
}

function linkVerificationAndSendEmail ($domain, $queryList, $emailId) {

    $crawler = getPageResponse($domain);
    $brokenLinks = [];

    foreach ($queryList as $keys => $query) {

        $nodes = $crawler->filter($query);
        foreach ($nodes as $key => $node) {

            $url = $node->getAttribute("href");

            if (!isAbsolute($url)
                || strpos($url, 'www.videoslots') === 0
                || strpos($url, 'https://www.videoslots') === 0
                || strpos($url, "videoslots") !== false) {
                continue;
            }

            $res = isUrlAccessible($url);

            if ($res !== true) {
                array_push($brokenLinks, $url);
            }
        }
    }

    if(!empty($brokenLinks)) {
        $mailTrigger = 'page.link.verification';
        $email = $emailId;
        $replacers['__REPORT-TIME__'] = phive()->hisNow();
        $replacers['__BRAND__'] = 'videoslots';
        $replacers['__PRODUCTION-PAGE__'] = $domain;
        $replacers['__BROKEN-LINKS__'] = implode('<br/> ', $brokenLinks);
        phive('MailHandler2')->sendMailToEmail($mailTrigger, $email, $replacers);
    }
}

phive('Licensed')->forceCountry('IT');
$config =  lic('getLicSetting', ['link_verification_pages']);
$emailId = lic('getLicSetting', ['link_verification_support_email']);

$startTime = microtime(true);
$formattedStartTime = date('Y-m-d H:i:s', (int)$startTime);
echo "Start Time: " . $formattedStartTime . PHP_EOL;

foreach ($config as $domain => $queryList) {
    linkVerificationAndSendEmail($domain, $queryList, $emailId);
}

$endTime = microtime(true);
$elapsedTime = $endTime - $startTime;

echo "Elapsed Time: " . number_format($elapsedTime, 4) . " seconds" . PHP_EOL;
