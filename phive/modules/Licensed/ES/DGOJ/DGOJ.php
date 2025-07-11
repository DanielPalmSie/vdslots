<?php

require_once __DIR__ . '/Response/IdentityResponse.php';
require_once __DIR__ . '/Response/RGIAJResponse.php';
require_once __DIR__ . '/Response/ServiceResponse.php';
require_once __DIR__ . '/DGOJResponse.php';
require_once __DIR__ . '/Request.php';
require_once __DIR__ . '/SignedSoap.php';

class DGOJ
{
    public const VERIFY_PLAYER = 'verificarJugador';
    public const VERIFY_IDENTITY = 'verificarIdentidad';
    public const VERIFY_RGIAJ = 'verificarRGIAJ';
    public const VERIFY_RGIAJ_CHANGES = 'verificarCambiosRGIAJ';
    public const VERIFY_DEATH_CHANGES = 'verificarCambiosDefunciones';
    public const CHUNK_SIZE = 40;
    public const RESPONSE_KEY = [
        self::VERIFY_PLAYER => 'resultadosJugador',
        self::VERIFY_RGIAJ => 'resultadosRGIAJ',
        self::VERIFY_RGIAJ_CHANGES => 'cambioRGIAJ',
        self::VERIFY_IDENTITY => 'resultadosIdentidad',
        self::VERIFY_DEATH_CHANGES => 'cambioDefuncion'
    ];


    /** @var array $settings DGOJ settings */
    private array $settings;
    /** @var string|mixed $wsdl WSDL location */
    private string $wsdl;
    /** @var bool $mock_enabled Mock enabled */
    private bool $mock_enabled;

    /**
     * DGOJ constructor.
     * @param array $settings
     */
    public function __construct(array $settings)
    {
        $this->settings = $settings;

        $this->wsdl = empty($this->settings['test'])
            ? $this->settings['prod_url']
            : $this->settings['test_url'];

        $this->mock_enabled = !empty($this->settings['mock_enabled']);
    }

    /**
     * Return mock response for provided $type
     * @param string $type
     * @return array|null
     */
    private function getMockResponse(string $type): ?array
    {
        if (empty($this->mock_enabled) || empty($this->settings) || empty($this->settings['mock'])) {
            return null;
        }
        return $this->settings['mock'][$type];
    }

    /**
     * Execute request
     *
     * @param array|null $request
     * @param string $type
     *
     * @return array
     */
    final public function execute(?array $request, string $type): array
    {
        $response = $this->getMockResponse($type);
        if (!empty($response)) {
            return $response;
        }

        $start_time = microtime(true);
        try {
            $use_cache = !empty($request['jugadores']) && count($request['jugadores']) === 1;

            if ($use_cache) {
                $user = reset($request['jugadores']) ?? [];
                $cached_response = $user ? phMget("dgoj-check-response-" . md5(json_encode($user)) . "-" . $type) : '';

                if (!empty($cached_response)) {
                    phive()->dumpTbl("dgoj-{$type}-cached", $cached_response);
                    return json_decode($cached_response, true, 512, JSON_THROW_ON_ERROR);
                }
            }

            $signed_soap = new SignedSoap($this->wsdl, ['ssl' => $this->settings['ssl']]);
            $response = $signed_soap->$type($request);
            $response_encoded = json_encode($response, JSON_THROW_ON_ERROR);

            if ($use_cache) {
                $this->cacheResponse($user, $response_encoded, $type);
            }

            // transform multi level object to array
            $response = json_decode($response_encoded, true, 512, JSON_THROW_ON_ERROR);
        } catch (Exception $e) {
            $response = [
                'faultstring' => $e->getMessage(),
                'faultcode' => $e->getCode(),
            ];
        }

        phive()->externalAuditTbl("dgoj-{$type}", $request, $response, (microtime(true) - $start_time), $response['faultcode'] ?? 200);


        return $response;
    }

    /**
     * @param array $user
     * @param string $response
     * @param string $type
     * @throws JsonException
     */
    private function cacheResponse(array $user, string $response, string $type): void
    {
        phMset("dgoj-check-response-" . md5(json_encode($user)) . "-" . $type, $response, 86400);
    }

    /**
     * Filter out the items with local errors
     * @param $data
     * @return array
     * @throws Exception
     */
    public function validateItems($data): array
    {
        $to_be_validated = [];
        $result = [];

        foreach ($data as $user) {
            $errors = Request::validateRequestObject($user);

            if (empty($user)) {
                throw new Exception("Empty item found in the list.");
            }
            if (!empty($to_be_validated[$user['dni']])) {
                throw new Exception("Duplicate dni found for value {$user['dni']}.");
            }
            if (!empty($errors)) {
                $result[$user['dni']] = new DGOJResponse($user, $errors, true);
                continue;
            }

            $to_be_validated[$user['dni']] = $user;
        }

        return [array_values($to_be_validated), $result];
    }

    /**
     * Execute the request and calculate the correct response
     * @param array $users
     * @param string $type
     *
     * @return DGOJResponse[]
     * @throws Exception
     */
    final public function requestPlayerType(array $users, string $type): array
    {
        $valid_types = [self::VERIFY_PLAYER, self::VERIFY_IDENTITY, self::VERIFY_RGIAJ];

        if (!in_array($type, $valid_types)) {
            throw new InvalidArgumentException(sprintf('Invalid request type: %s, Valid types: %s',
                $type, join(', ', $valid_types)));
        }

        [$users, $result] = $this->validateItems($users);

        $users_assoc = array_reduce($users, function ($carry, $user) {
            $carry[$user['dni']] = $user;
            return $carry;
        }, []);

        // break into chunks
        $users = array_chunk($users, self::CHUNK_SIZE);

        // request data for each chunk
        $response_key = self::RESPONSE_KEY[$type];
        foreach ($users as $users_chunk) {
            $request = ['jugadores' => (new Request($users_chunk))->getCleanData()];
            $response = $this->execute($request, $type);

            // got some sort of exception so we assign the same result to all items in the chunk
            if (!isset($response[$response_key])) {
                foreach ($users_chunk as $user) {
                    $result[$user['dni']] = new DGOJResponse($user, $response);
                }
                continue;
            }

            // external service returned a single user as assoc array instead of list of users
            if (isset($response[$response_key]['dni'])) {
                $response[$response_key] = [$response[$response_key]];
            }

            // got some response so we assign the response to the correct dni
            foreach ($response[$response_key] as $user) {
                $result[$user['dni']] = new DGOJResponse($users_assoc[$user['dni']], $user);
            }
        }

        return $result;
    }

    final public function requestBlankType(string $type): array
    {
        $valid_types = [self::VERIFY_RGIAJ_CHANGES, self::VERIFY_DEATH_CHANGES];

        if (!in_array($type, $valid_types)) {
            throw new InvalidArgumentException(sprintf('Invalid request type: %s, Valid types: %s',
                $type, join(', ', $valid_types)));
        }

        $response_key = self::RESPONSE_KEY[$type];
        $response = $this->execute(null, $type);

        // got some sort of exception so we assign the same result to all items in the chunk
        if (!isset($response[$response_key])) {
            return [];
        }

        // external service returned a single user as assoc array instead of list of users
        if (isset($response[$response_key]['DNI'])) {
            $response[$response_key] = [$response[$response_key]];
        }

        // got some response so we assign the response to the correct dni
        $result = [];

        foreach ($response[$response_key] as $user) {
            $result[$user['DNI']] = new DGOJResponse([], $user);
        }

        return $result;
    }
}