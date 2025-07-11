<?php

namespace DBUserHandler\Libraries\GoogleAnalytics;

class Constants
{
    const KEY_STARTED_FIRST_DEPOSIT = 'started-first-deposit'; //Clicked on payment button and before going to payment gateway
    const KEY_STARTED_SUBSEQUENT_DEPOSIT = 'started-subsequent-deposit'; //Clicked on payment button and before going to payment gateway
    const KEY_FIRST_DEPOSIT = 'made-first-deposit';
    const KEY_SUBSEQUENT_DEPOSIT = 'enod';
    const KEY_PARTIALLY_REGISTERED = 'partially-registered';
    const KEY_COMPLETED_REGISTRATION = 'registered';
    const KEY_LOGGED_IN = 'logged-in';
}
