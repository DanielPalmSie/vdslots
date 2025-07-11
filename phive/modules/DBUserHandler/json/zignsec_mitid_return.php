<?php

require_once __DIR__ . '/../../../phive.php';

// intent is to just pass the response $_GET data after external service response of MitID, for further validation


lic('validateLoginMitID', [$_GET], null, null, 'DK');