<?php 
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForGeoComply extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'before.continue' => "Before you continue...",
            'follow.instruction' => "We are <span class=\"text-bold\">required as per the regulations</span> to verify your location. To continue, please follow the instructions below:",
            'download.install' => "Download and install \"<span class=\"text-bold\">GeoGuard Location Validator</span>\" app on your device.",
            'download.install.desktop' => "Download and install \"<span class=\"text-bold\">Player Location Check</span>\" app on your device.",
            'open.installer' => "Open the installer and follow the instructions to <span class=\"text-bold\">grant location and notification permissions to the app.</span>",
            'return.to.browser' => "<span class=\"text-bold\">Return to the browser</span> to continue.",
            'download.app' => "Download App",
            'open.app' => "Open App",
            'geoguard.app.info' => "The <span class=\"text-bold\">GeoGuard Location Validator App</span> is developed by our <span class=\"text-bold\">trusted location verification partner, GeoComply.</span>",
            'geoguard.app.info.desktop' => "The <span class=\"text-bold\">Player Location Check</span> is developed by our <span class=\"text-bold\">trusted location verification partner, GeoComply.</span>",
            'verify.your.location' => "Verify your location",
            'verify.location.info' => "After opening the application in the background, please proceed with verification.",

            'check.in.progress' => 'Check is in progress',
            'check.in.progress.info' => "Please wait until we confirm your location, <span class=\"text-bold\">it might take up to 30 seconds</span>, don’t close the tab and keep the browser running.",

            'success' => "Success",
            'location.confirmed' => "Your location is confirmed! Enjoy Videoslots!",

            'connection.error' => "Connection Error",
            'connection.error.info' => "Can't connect to an application. Please restart application and retry.",
            'ok' => 'OK',
            'check.troubleshooter' => "Check the troubleshooter for more details",

            'problem.info'  => "A problem occurred while verifying your location!",
            'please.come.back.in.hour' => "Please come back in an hour.",
            'retry'     => 'Retry',
            'verification.in.progress' => 'Verification in progress',
            'install.geocomply' => 'Install GeoComply',
            'verify.location'   => 'Verify location',
            'successful.verification' => 'Successful verification',
            'verification.failed' => 'Verification failed',
            'open.geocomply' => 'Open GeoComply',
            'error.details' => 'Error details',
            'timeout.reason.wrong.location' => 'A problem occurred while verifying your location, please login again.'
        ]
    ];
}