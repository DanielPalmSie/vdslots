<?php

return [
    'type' => Licensed::REGISTER_BUTTON_TYPE_EXTRA,
    'title_alias' => 'register.with.external.verifier',
    'image' => 'ext_verify_logo',
    'post_data' => '{verifyExternalUrl: 1}',
    'action' => 'START_EXTERNAL_URL_VERIFICATION',
];
