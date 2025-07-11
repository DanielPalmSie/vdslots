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

class ViewsCommand extends Command
{
    protected function configure()
    {
        $this->setName("views:clear")
            ->setDescription("Clear blade cached views folder.");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            if ((new Filesystem())->cleanDirectory(getenv('VIEW_CACHE_PATH'))) {
                $output->writeln('Blade views deleted from cache.');
            } else {
                $output->writeln('Blade views not deleted from cache.');
            }
        } catch (\Exception $e) {
            $output->writeln("Blade views not deleted from cache due to: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }

}