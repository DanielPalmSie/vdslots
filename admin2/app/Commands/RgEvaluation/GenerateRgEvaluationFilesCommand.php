<?php

namespace App\Commands\RgEvaluation;

use Ivoba\Silex\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateRgEvaluationFilesCommand extends Command
{
    private const TEMPLATE_PATH = __DIR__ . '/../../RgEvaluation/Templates/';
    private const ADMIN2_RG_EVALUATION_TRIGGER_PATH = __DIR__ . '/../../RgEvaluation/Triggers/';
    private string $phiveRgEvaluationPath;

    public function __construct(string $name = null)
    {
        $this->phiveRgEvaluationPath = getenv('VIDEOSLOTS_PATH') . "/phive/modules/RgEvaluation/Factory/";
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName('rg:generate-evaluation-files');
        $this->setDescription('Creates all the necessary files and seeders that are required for an RG Evaluation');
        $this->addArgument(
            'trigger_name',
            InputArgument::REQUIRED,
            "The name of the trigger to generate evaluation files. Example: RG8"
        );
        $this->addOption(
            'without_popup',
            null,
            InputOption::VALUE_NONE,
            "Pass this option if you don't want to generate RG popup files"
        );
        $this->addOption(
            'warning_emails',
            null,
            InputOption::VALUE_NONE,
            "Pass this option if you want to generate RG popup warning email files"
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $triggerName = $input->getArgument('trigger_name');
        $withoutPopup = $input->getOption('without_popup');
        $warningEmails = $input->getOption('warning_emails');

        $output->writeln("Generating RG Evaluation files...");

        $generate_seeder_command = $this->getApplication()->find('seeder:generate');

        $output->writeln("** Generate seeder to enable RG Evaluation");
        $params = [
            'name' => "EnableRgEvaluationFor{$triggerName}",
            '--template_path' => self::TEMPLATE_PATH . 'Seeders/SeederEnableRgEvaluationTemplate.php',
            '--template_variables' => [
                'trigger_name' => $triggerName
            ],
        ];
        $generate_seeder_command->run(new ArrayInput($params), $output);

        if (!$withoutPopup) {
            sleep(2);
            $output->writeln("** Generate seeder to activate popup");
            $params = [
                'name' => "Add{$triggerName}PopupContent",
                '--template_path' => self::TEMPLATE_PATH . 'Seeders/SeederRgPopupContentTemplate.php',
                '--template_variables' => [
                    'trigger_name' => $triggerName
                ],
            ];
            $generate_seeder_command->run(new ArrayInput($params), $output);
        }

        if ($warningEmails) {
            sleep(2);
            $output->writeln("** Generate seeder for popup warning email");
            $params = [
                'name' => "Add{$triggerName}PopupWarningEmail",
                '--template_path' => self::TEMPLATE_PATH . 'Seeders/SeederRgPopupWarningEmailTemplate.php',
                '--template_variables' => [
                    'trigger_name' => $triggerName
                ],
            ];
            $generate_seeder_command->run(new ArrayInput($params), $output);

            sleep(2);
            $output->writeln("** Generate seeder for warning email config");
            $params = [
                'name' => "Add{$triggerName}PopupEmailConfig",
                '--template_path' => self::TEMPLATE_PATH . 'Seeders/SeederRgPopupEmailConfigTemplate.php',
                '--template_variables' => [
                    'trigger_name' => $triggerName
                ],
            ];
            $generate_seeder_command->run(new ArrayInput($params), $output);
        }

        $output->writeln("** Generate trigger class file");
        $triggerTemplatePath = self::TEMPLATE_PATH . 'RgTriggerTemplate.php';
        $saveTriggerFileTo = self::ADMIN2_RG_EVALUATION_TRIGGER_PATH . $triggerName . '.php';
        $templateVariables = [
            'triggerClassName' => $triggerName,
        ];
        $this->generateFileFromTemplate(
            $output,
            $triggerTemplatePath,
            $templateVariables,
            $saveTriggerFileTo
        );

        $output->writeln("** Generate data supplier class file in phive");
        $dataSupplierTemplatePath = self::TEMPLATE_PATH . 'RgDataSupplierTemplate.php';
        $dataSupplierClassName = "{$triggerName}DataSupplier";
        $saveDataSupplierFileTo =  "{$this->phiveRgEvaluationPath}{$dataSupplierClassName}.php";
        $templateVariables = [
            'dataSupplierClassName' => $dataSupplierClassName,
            'triggerName' => $triggerName,
        ];
        $this->generateFileFromTemplate(
            $output,
            $dataSupplierTemplatePath,
            $templateVariables,
            $saveDataSupplierFileTo
        );

        $output->writeln("** Generate dynamic variable supplier class file in phive");
        $dynamicVariablesSupplierTemplatePath = self::TEMPLATE_PATH . 'RgDynamicVariablesSupplierTemplate.php';
        $dynamicVariablesSupplierClassName = "{$triggerName}DynamicVariablesSupplier";
        $saveDynamicVariablesSupplierFileTo = "{$this->phiveRgEvaluationPath}{$dynamicVariablesSupplierClassName}.php";
        $templateVariables = [
            'dynamicVariablesSupplierClassName' => $dynamicVariablesSupplierClassName,
            'dataSupplierClassName' => $dataSupplierClassName,
        ];
        $this->generateFileFromTemplate(
            $output,
            $dynamicVariablesSupplierTemplatePath,
            $templateVariables,
            $saveDynamicVariablesSupplierFileTo
        );

        return 0;
    }

    protected function generateFileFromTemplate(OutputInterface $output, $templatePath, $templateVariables, $saveTo)
    {
        if (file_exists($saveTo)) {
            $output->writeln("<error>".realpath($saveTo)." already exists.</error>");
            return;
        }
        extract($templateVariables);
        ob_start();
        include $templatePath;
        $contents = ob_get_clean();
        if (false === file_put_contents($saveTo, $contents)) {
            throw new \RuntimeException(sprintf(
                'The file "%s" could not be written to',
                $saveTriggerFileTo
            ));
        }
        $output->writeln("<info>Done</info> " . realpath($saveTo));
    }
}
