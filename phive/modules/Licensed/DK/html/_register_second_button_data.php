<?php

return [
    'type' => Licensed::REGISTER_BUTTON_TYPE_EXTRA,
    'mit_id_disabled' => licSetting('mit_id_disabled'),
    'title_alias' => 'dk.register.with.mitid',
    'image' => 'dk-mitid',
    'unavailable_title_alias' => 'mitid.currently.unavailable',
    'post_data' => '{mitID: 1}',
    'action' => 'START_MIT_ID_VERIFICATION',
];
