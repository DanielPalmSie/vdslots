<?php
require_once __DIR__ . '/../../../api.php';

/*
error_log("****** tracking pixel starts *******");
error_log(var_export($_REQUEST, true));
error_log(var_export($_SERVER, true));
error_log("****** tracking pixel end *******");
*/
header('Content-Type: image/png');
echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAAA1BMVEUAAACnej3aAAAAAXRSTlMAQObYZgAAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII=');

if (!empty($_GET['mid'])) {
    phive('Mailer/SMTP')->processEvent($_GET['mid']);
}