<?php

use Mosms\ZignsecV5\ZignsecV5WebhookHandler;

require_once __DIR__ . '/../../../../api.php';

/*
Example of webhook body:

{
    "event": "session_finished",
    "id": "8f8a80f8-4302-4dd5-82f7-f744684b3bc9",
    "integration_id": "Zignsec.v5.SMS",
    "number_of_message_parts": 1,
    "relay_state":"test",
    "status": "dispatched",
    "workflow_session_id": null
}
*/

$active_sms_sender = phive('Mosms')->getSetting('active_sms_sender');
if ($active_sms_sender !== 'zignsec_v5') {
    echo json_encode(['success' => false, 'result' => 'Zignsec V5 Sms Sender is not enabled']);
    die;
}

$body = json_decode(file_get_contents('php://input'), true);

$handler = new ZignsecV5WebhookHandler();
$handler->handle($body);

echo json_encode(['success' => true, 'result' => 'ok']);
die;
