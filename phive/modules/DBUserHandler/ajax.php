<?php

require_once __DIR__ . '/../../phive.php';

/** @var PrivacyHandler $pkg */
$pkg = phive('DBUserHandler/PrivacyHandler');

function isPost(): bool {
    return strtoupper($_SERVER['REQUEST_METHOD']) === 'POST';
}

/**
 * Get a request var or null if not set
 * based on if the request is POST or GET
 *
 * @param string $var
 * @return mixed|null
 */
function reqVar(string $var) {
    if (isPost()) return $_POST[$var] ?? null;
    else return $_GET[$var] ?? null;
}

/**
 * Print out json response with provided $status code
 *
 * @param array $data
 * @param int $status
 * @return null
 */
function json(array $data, int $status = 200)
{
    http_response_code($status);
    header('Content-type: application/json');

    echo json_encode($data);

    flush();
    die($status);
}

if (isset($_GET['action'])) {
    $action = trim(strtolower($_GET['action']));

    if ($action === 'privacy-setting') {
        $method = (isPost()) ? 'setPrivacySetting' : 'getPrivacySetting';

        if (!$user_id   = reqVar('user_id'))  return json(['error' => 'user_id is required'], 400);
        if (!$channel   = reqVar('channel'))  return json(['error' => 'channel is required'], 400);
        if (!$type      = reqVar('type'))     return json(['error' => 'type is required'], 400);

        $product = reqVar('product');

        if (!in_array($channel, $pkg::CHANNELS))    return json(['error' => 'channel is not a valid privacy channel'], 400);
        if (!in_array($type, $pkg::TYPES))          return json(['error' => 'type is not a valid privacy type'], 400);

        $key = [$channel, $type, $product];
        $key = implode('.', $key);

        $result = $pkg->$method($user_id, $key, (bool) reqVar('opt-in'));
        isPost() ? json(['saved' => true]) : json(['opt-in' => $result]);
    } else if ($action === 'privacy-settings') {
        if (isPost()) {
            $pkg->setPrivacySettings(reqVar('user_id'), json_decode(reqVar('data'), true));
            json(['saved' => true]);
        } else json($pkg->getPrivacySettings(reqVar('user_id')));
    } else if (isPost() && $action == 'privacy-settings-all') {
        $pkg->setAllPrivacySettings(reqVar('user_id'), (bool) reqVar('opt-in'));
        json(['saved' => true]);
    }
}
