<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 4/20/16
 * Time: 12:28 PM
 */

namespace App\Commands\Helpers;

use Illuminate\Filesystem\Filesystem;
use Ivoba\Silex\Command\Command;
use Silex\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PostInstallCommand extends Command
{
    protected function configure()
    {
        $this->setName("install:post")
            ->setDescription("Execute post install routines.");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            (new Filesystem())->makeDirectory('storage', 0777);
            (new Filesystem())->makeDirectory('storage/view', 0777);

        } catch (\Exception $e) {
            $output->writeln("Post install error: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }

}