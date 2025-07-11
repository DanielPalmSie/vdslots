<?php

namespace App\Extensions\Database\Seeder;

use Symfony\Component\Config\FileLocator;

trait SeederBootstrapTrait {

    /**
     * @param string $filename
     * @return array|string
     */
    protected function findBootstrapFile($filename)
    {
        $filename = 'phpseed.php';

        $cwd = getcwd();

        $locator = new FileLocator(array(
            $cwd . DIRECTORY_SEPARATOR . 'config',
            $cwd
        ));

        return $locator->locate($filename);
    }
}
