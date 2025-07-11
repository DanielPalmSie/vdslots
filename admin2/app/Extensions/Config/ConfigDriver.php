<?php

namespace App\Extensions\Config;

interface ConfigDriver
{
    function load($filename);
    function supports($filename);
}
