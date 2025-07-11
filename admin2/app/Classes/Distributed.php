<?php
/**
 * This is a wrapper for the Distributed module on Phive to group all the requests cross brand in the backoffice
 * so we don't end up with logic all over the place.
 */

namespace App\Classes;

use App\Models\User;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Silex\Application;
use App\Models\RiskProfileRating;
use GuzzleHttp\Exception\RequestException;

class Distributed
{

    /**
     * We get the remote score along with other parameters needed to be displayed.
     *
     * @param Application $app
     * @param User        $user
     * @param             $section
     * @param             $jurisdiction
     *
     * @return array|false
     * @throws GuzzleException
     */
    public static function getRemoteScore($app, $user, $section, $jurisdiction)
    {
        $phive_user = cu($user->getKey());
        $user_id = $user->getKey();
        $result = [];

        if (empty(licSetting('cross_brand', $phive_user)['backoffice_global_risk_score'])) {
            return false;
        }

        /** @var \Distributed $phive_distributed */
        $phive_distributed = phive('Distributed');

        $local_brand = $phive_distributed->getSetting('local_brand');
        $remote_brand = $phive_distributed->getSetting('remote_brand');
        $local_brand_id = $phive_distributed->getBrandIdByName($local_brand);
        $local_brand_pretty = $phive_distributed->getBrandPrettyName($local_brand);
        $customer_id = $phive_user->getRemoteId();
        $base_uri = $phive_distributed->getSetting('machines_bo')[$remote_brand];

        $default_response = [
                'found' => false,
                'remote_brand_name' => $phive_distributed->getBrandPrettyName($remote_brand),
                'local_brand_name' => $local_brand_pretty,
        ];

        if (!$user->hasCompletedRegistration()) {
            return [];
        }

        if (empty($customer_id)) {
            if ($remote_brand === "scv") {
                return [array_merge($default_response, ['error' => true])];
            }

            return [];
        }

        $request_method = 'POST';
        $uri = '/api/risk-profile-rating/get-last-score/';
        $options = [
            'form_params' => [
                'user_id' => $customer_id,
                'section' => $section,
                'jurisdiction' => $jurisdiction,
                'brand' => $local_brand_id,
            ],
            'headers' =>
                ['X-BO-KEY' => $phive_distributed->getSetting('brand_auth')[$local_brand]['admin2_api']]
        ];
        try {
            $client = new Client([
                'base_uri' => $base_uri,
                'timeout' => 5.0,
            ]);
            phive()->dumpTbl(
                "dist_post_data",
                json_encode(array_merge(['method' => $request_method, 'uri' => $uri], $options), JSON_THROW_ON_ERROR),
                $user_id
            );
            $res = $client->request($request_method, $uri, $options);
            $body = $res->getBody();
            phive()->dumpTbl("dist-res", $body, $user_id);
            $response = json_decode($body, true);
            $section_url = $section == 'RG' ? 'responsible-gaming-monitoring' : 'fraud-aml-monitoring';
            $risk_profile_repo = $app['risk_profile_rating.repository'];
            $grs_settings = $risk_profile_repo::getCategorySettings(RiskProfileRating::RATING_SCORE_PARENT_CATEGORY,
                $jurisdiction, $section);
            if (empty($response)) {
                return [array_merge($default_response, ['error' => false])];
            }

            foreach ($response as $data) {
                $rating_tags_statuses = array_map(function($item) use ($data) {
                    return [
                        'title' => $item['title'],
                        'active' => $item['title'] == $data['tag'],
                    ];
                }, $grs_settings);


                $result[] = [
                    'found' => $data['found'],
                    'remote_brand_name' => $phive_distributed->getBrandPrettyName($data['brand_name']),
                    'local_brand_name' => $local_brand_pretty,
                    'remote_link' => "{$data['base_uri']}/admin2/userprofile/{$data['remote_user_id']}/{$section_url}/",
                    'rating_tags_statuses' => $rating_tags_statuses,
                    'last_update' => $data['last_updated']
                ];
            }

        } catch (RequestException | Exception $e) {
            $error = __METHOD__. " Error: " . $e->getMessage();
            $app['monolog']->addError($error);
            phive()->dumpTbl(
                "dist-res",
                json_encode([
                'method' => $request_method,
                'uri' => $uri,
                'request' => $options,
                'response' => $error,
                ]),
                $user_id
            );
            return [array_merge($default_response, ['error' => true])];
        }

        return $result;
    }

    /**
     * @param $app
     * @param $user
     *
     * @return array|false
     * @throws GuzzleException
     */
    public static function getCustomerProfiles($app, $user)
    {
        $phive_user = cu($user->getKey());
        $user_id = $user->getKey();
        $result = [];

        if (empty(licSetting('cross_brand', $phive_user)['backoffice_global_risk_score'])) {
            return false;
        }

        /** @var \Distributed $phive_distributed */
        $phive_distributed = phive('Distributed');

        $local_brand = $phive_distributed->getSetting('local_brand');
        $remote_brand = $phive_distributed->getSetting('remote_brand');
        $local_brand_id = $phive_distributed->getBrandIdByName($local_brand);
        $customer_id = $phive_user->getRemoteId();
        $base_uri = $phive_distributed->getSetting('machines_bo')[$remote_brand];

        if (empty($customer_id) || !$user->hasCompletedRegistration()) {
            return [];
        }

        $request_method = 'POST';
        $uri = '/api/customer-profile/get-customer-profiles/';
        $options = [
            'form_params' => [
                'user_id' => $customer_id,
                'brand' => $local_brand_id,
            ]
        ];
        try {
            $client = new Client([
                'base_uri' => $base_uri,
                'timeout' => 5.0,
            ]);
            phive()->dumpTbl(
                "dist_post_data",
                json_encode(array_merge(['method' => $request_method, 'uri' => $uri], $options), JSON_THROW_ON_ERROR),
                $user_id
            );
            $res = $client->request($request_method, $uri, $options);
            $body = $res->getBody();
            phive()->dumpTbl("dist-res", $body, $user_id);
            $response = json_decode($body, true);

            if (empty($response)) {
                return [];
            }

            foreach ($response as $data) {
                $result[] = [
                    'remote_brand_name' => $phive_distributed->getBrandPrettyName($data['brand_name']),
                    'remote_link' => "{$data['base_uri']}/admin2/userprofile/{$data['remote_user_id']}/",
                ];
            }

        } catch (RequestException | Exception $e) {
            $error = __METHOD__. " Error: " . $e->getMessage();
            $app['monolog']->addError($error);
            phive()->dumpTbl(
                "dist-res",
                json_encode([
                    'method' => $request_method,
                    'uri' => $uri,
                    'request' => $options,
                    'response' => $error,
                ]),
                $user_id
            );
            return [];
        }

        return $result;
    }
}