<?php

namespace App\Classes\Mailer;

use Exception;
use GuzzleHttp\Client;
use Http\Adapter\Guzzle7\Client as GuzzleAdapter;
use Illuminate\Support\Collection;
use SparkPost\SparkPostResponse;

class SparkPost extends Mailer
{
    /** @var \SparkPost\SparkPost $service */
    private $service;

    /**
     * SparkPost constructor.
     * @param $app
     * @param $queue
     */
    public function __construct($app, $queue)
    {
        parent::__construct($app, $queue);

        $this->bulk_capability = true;

        $this->service = new \SparkPost\SparkPost(new GuzzleAdapter(new Client()), [
            'host' => env('SPARKPOST_API_HOST'),
            'key' => env('SPARKPOST_API_KEY'),
            'async' => false
        ]);
    }

    /**
     * TODO do async calls and use \GuzzleHttp\Promise\all() to resolve all
     *
     * @param $item
     * @param null $callback
     * @return array
     */
    public function sendItem($item, $callback = null)
    {
        $item = $this->queue::formatEmailFields($item);
        return $this->send([
            'content' => [
                'from' => [
                    'name' => $item['from_name'],
                    'email' => $item['from'],
                ],
                'subject' => $item['subject'],
                'html' => $item['html'],
                'text' => $item['text'],
            ],
            'campaign_id' => $item['campaign_id'],
            'description' => $item['description'],
            'recipients' => [
                [
                    'address' => [
                        'name' => $item['to_name'],
                        'email' => $item['to'],
                    ],
                ]
            ]
        ] + $this->getCcBcc($item));
    }

    private function getCcBcc($item): array
    {
        $result = [];
        $types = ['cc', 'bcc'];

        foreach ($types as $type) {
            if (!empty($item[$type])) {
                $result[$type][] =
                    [
                        'address' => [
                            'name' => $item['to_name'],
                            'email' => $item[$type],
                        ],
                    ];
            }
        }

        return $result;
    }

    /**
     * @param Collection $items
     * @return array|bool|mixed|null
     */
    public function sendBulk($items)
    {
        if ($items->isEmpty()) {
            return null;
        }

        $default = $this->queue::formatEmailFields($items[0]);

        // make the replacers compatible with sparkpost
        foreach (array_keys($default['replacers']) as $key) {
            $default['text'] = str_replace($key, "{{{$key}}}", $default['text']);
            $default['html'] = str_replace($key, "{{{$key}}}", $default['html']);
            $default['subject'] = str_replace($key, "{{{$key}}}", $default['subject']);
        }

        return $this->send([
            'content' => [
                'from' => [
                    'name' => $default['from_name'],
                    'email' => $default['from'],
                ],
                'subject' => $default['subject'],
                'html' => $default['html'],
                'text' => $default['text'],
            ],
            'recipients' => $items->map(function ($item) {
                $item = $this->queue::formatEmailFields($item);

                return [
                    'address' => [
                        'name' => $item['to_name'],
                        'email' => $item['to'],
                    ],
                    "substitution_data" => $item['replacers']
                ];
            })->toArray()
        ]);
    }

    /**
     * @param $request
     * @return array
     */
    private function send($request)
    {
        try {
            /** @var SparkPostResponse $response */
            $response = $this->service->transmissions->post($request);

            return $response->getBody();
        } catch (Exception $e) {
            error_log($e->getMessage());
            return [];
        }
    }
}
