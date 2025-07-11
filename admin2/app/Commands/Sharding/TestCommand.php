<?php

namespace App\Commands\Sharding;

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Models\IpLog;
use App\Models\User;
use Ivoba\Silex\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestCommand extends Command
{
    /** @var  OutputInterface */
    protected $output;

    protected function configure()
    {
        $this->setName("sh:test")
            ->setDescription("Test command with samples");
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        return 0;
    }
}