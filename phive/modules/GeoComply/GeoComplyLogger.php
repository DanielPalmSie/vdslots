<?php

namespace GeoComply;

use GeoComply\Exceptions\InvalidArgumentException;

class GeoComplyLogger
{
    private $logger;

    private const BASETAG = "geocomply";

    private const INITIALISATION_ERROR = "Can't connect to a GeoComply Backend. Problem is on our side";
    private const INITIALISATION_MOBILE_OOBEE_ERROR = "Can't connect to a OOBEE.";
    private const INITIALISATION_MOBILE_ERROR = "Can't connect to a GeoComply Mobile.";
    private const MISSING_LICENSE = "Can't retrieve license key from a GeoComply server";
    private const MISSING_USERID = "Trying to connect to a GeoComply in unlogged stage";
    private const CLIENT_CONNECTION = "Connection to GeoComply Client is successful";
    private const CLIENT_MOBILE_REGISTRATION = "Successful connection of a GeoComply app a user's mobile device";
    private const CLIENT_FAILURE_LOCAL_SERVICE_COMMUNICATION = "Connection to GeoComply Client failed. Local service communication failure";
    private const CLIENT_FAILURE_LOCAL_SERVICE_UNSUP_VER = "Connection to GeoComply Client failed. Unsupported version";
    private const CLIENT_FAILURE_LOCAL_SERVICE_UNAVAILABLE = "Connection to GeoComply Client failed. Not installed or not open application";
    private const CLIENT_FAILURE_TRANSACTION_TIMEOUT = "Connection to GeoComply Client failed. Connection timeout";
    private const CLIENT_FAILURE_WRONG_OR_MISSING_PARAMETER = "Connection to GeoComply Client failed. Wrong or missing parameter";
    private const CLIENT_FAILURE_LICENSE_EXPIRED = "Connection to GeoComply Client failed. License expired";
    private const CLIENT_FAILURE_INVALID_LICENSE_FORMAT = "Connection to GeoComply Client failed. Wrong license";
    private const CLIENT_FAILURE_SERVER_COMMUNICATION = "Connection to GeoComply Client failed. Server communication failure.";
    private const CLIENT_FAILURE_MOBILE_LOCATION_PERMISSION = "GeoComply Client failed on mobile. Missing permission to get user's location.";
    private const CLIENT_FAILURE_MOBILE_NOTIFICATION_PERMISSION = "GeoComply Client failed on mobile. Missing notification permission.";
    private const CLIENT_FAILURE_DEFAULT = "Connection to GeoComply Client failed. Error code verification is necessary.";
    private const LOCATION_REQUEST = "Requesting a new GeoLocation packet";
    private const LOCATION_APPROVED = "Valid location of a requester";
    private const LOCATION_FORBIDDEN = "Invalid location of a requester";


    /**
     * @param $logger
     *
     * $logger - instance of a DBUserHandler
     */
    public function __construct($logger){
        $this->logger = $logger;
    }

    /**
     * @param int $userID
     * @param string $logTag
     * @return array
     * @throws InvalidArgumentException
     */
    public function log(int $userID, string $logTag): array {
        if(!$userID){
            throw new InvalidArgumentException("Missing User ID");
        }

        if (!defined("self::$logTag")) {
            throw new InvalidArgumentException("Invalid TAG");
        }

        $this->logger->logAction($userID, $logTag, self::BASETAG);

        return [$logTag, 'success'=>true];
    }

}