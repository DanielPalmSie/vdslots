<?php

/**
 * "/phive/modules/IpBlock/IpBlock.php" does "require_once '/opt/lib/udger/vendor/autoload.php';",
 * which loads an old version of Codeception causing conflicts when executing tests, so we rename it.
 * Ideally we should restore the original folder name after Codeception finishes but there is no hook for this,
 * so perhaps we could use a custom script to launch Codeception tests then restore the folder name.
 */
shell_exec("mv /opt/lib/udger/codeception.yml /opt/lib/udger/codeception.yml.org");
shell_exec("mv /opt/lib/udger/vendor/codeception/ /opt/lib/udger/vendor/codeception.org/");

/**
 * Loads all Phive modules.
 */
require_once __DIR__ . '/../../videoslots/phive/phive.php';
