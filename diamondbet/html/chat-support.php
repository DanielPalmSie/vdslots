<?php
$chat_support_widget = phive()->getSetting('chat_support_widget');
$chat_support_file = __DIR__ . '/' . $chat_support_widget . '.php';

if (!file_exists($chat_support_file) || phive('Pager')->isLanding()) {
    return null;
}

// Include the file with the name as the $chat_support_widget variable (e.g. 'zendesk.php', 'freshdesk.php')
include $chat_support_file;
