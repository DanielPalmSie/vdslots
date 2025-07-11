<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class ChatGPT
{
    private Client $client;
    private string $api_key;
    private string $model;
    private array $language_map;

    public function __construct()
    {
        $this->api_key = phive('Localizer')->getSetting('chatgpt')['API_KEY'];
        $this->model = 'gpt-4o-mini';
        $this->language_map = [
            'br' => 'pt-BR',
            'cl' => 'es',
            'dgoj' => 'es',
            'no' => 'nb',
            'on' => 'en',
            'pe' => 'es',
        ];

        $this->client = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key,
            ]
        ]);
    }

    public function translate(string $translation_string, string $from_lang, string $to_lang)
    {
        $system_prompt = phive('Localizer')->getSetting('chatgpt')['SYSTEM_PROMPT'];
        $system_prompt = str_replace('__FROM_LANG__', $this->getLangCode($from_lang), $system_prompt);
        $system_prompt = str_replace('__TO_LANG__', $this->getLangCode($to_lang), $system_prompt);
        $system_prompt = str_replace('__BRAND__', phive('BrandedConfig')->getBrand(), $system_prompt);
        $messages = [['role' => 'system', 'content' => $system_prompt]];
        $messages[] = ['role' => 'user', 'content' => $translation_string];

        try {
            $response = $this->client->post('chat/completions', [
                'json' => [
                    'model' => $this->model,
                    'messages' => $messages,
                    'temperature' => 0.5
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            $response_text = $data['choices'][0]['message']['content'];
            if (!$response_text) {
                return ['error' => 'Request failed: No response.'];
            } else {
                return ['text' => $response_text];
            }
        } catch (RequestException $e) {
            return ['error' => 'Request failed: ' . $e->getMessage()];
        }
    }

    private function getLangCode($lang): string
    {
        return $this->language_map[$lang] ?? $lang;
    }
}
