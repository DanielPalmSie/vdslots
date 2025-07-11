<?php

namespace App\Commands\Regulations\SAFE;

use Ivoba\Silex\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RegenerateByToken extends Command
{
    protected function configure()
    {
        $this->setName('regulations:safe:regenerate_by_token')
            ->addArgument('token_id', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Regenerating report');
        $dk = phive('Licensed/DK/DK');

        $new_token = $dk->regenerateData($input->getArgument('token_id'));

        if ($new_token) {
            $output->writeln("The new token is: <info>{$new_token}</info>");
        }

        return 0;
    }
}