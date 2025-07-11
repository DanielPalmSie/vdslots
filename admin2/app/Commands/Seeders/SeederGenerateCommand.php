<?php

namespace App\Commands\Seeders;

use App\Extensions\Database\Seeder\SeederBootstrapTrait;
use Phpmig\Console\Command\GenerateCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generate seeder command
 */
class SeederGenerateCommand extends GenerateCommand
{
    use SeederBootstrapTrait;

    protected function configure()
    {
        parent::configure();

        $this->setName('seeder:generate')
             ->setDescription('Generate a new database seeder')
             ->addOption('translation', null, null, 'Use translation seeder template')
             ->addOption('template_path', null, null, 'The path of the template to be used to generate the seeder')
             ->addOption('template_variables', null, null, 'The dynamic variables to be replaced in the template')
             ->setHelp(<<<EOT
The <info>generate</info> command creates a new seeder with the name specified

<info>./console seeder:generate name </info>

EOT
            );
    }

    protected function bootstrap(InputInterface $input, OutputInterface $output)
    {
        parent::bootstrap($input, $output);

        $container = $this->getContainer();
        if($input->getOption('template_path')) {
            $container['phpmig.migrations_template_path'] = $input->getOption('template_path');
        }

        $templateVariables = $input->getOption('template_variables');
        if($templateVariables) {
            foreach ($templateVariables as $key => $value) {
                $container[$key] = $value;
            }
        }

        if($input->getOption('translation')) {
            $container['phpmig.migrations_template_path'] = __DIR__ . '/../../Extensions/Database/Seeder/SeederTranslationTemplate.php';
        }
    }
}



