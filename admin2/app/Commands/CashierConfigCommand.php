<?php
declare(strict_types=1);

namespace App\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CashierConfigCommand extends Command
{
    private const HEADERS = [
        'supplier',
        'group',
        'network',
        'type',
        'is_active',
        'included_countries',
        'excluded_countries',
        'included_provinces',
        'excluded_provinces',
        'included_currencies',
        'excluded_currencies',
    ];

    protected function configure()
    {
        $this->setName('cashier:config');
        $this->setDescription('Shows list of suppliers with country rules (in csv format)');
        $this->addOption('supplier', 's', InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 'Filter by suppliers.');
        $this->addOption('debug', 'd', InputOption::VALUE_NONE, 'Show one supplier at the time.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = phive('CasinoCashier')->getFullPspConfig();

        if ($input->getOption('debug')) {
            $style = new SymfonyStyle($input, $output);
            $table = $style->createTable();
            $table->setHorizontal();

            $table->setHeaders(self::HEADERS);
        }

        $suppliers = $this->normalizeInput($input);
        $rows = [];

        foreach ($config as $supplier => $settings) {
            if (!empty($suppliers) && !in_array(strtolower($supplier), $suppliers)) {
                continue;
            }

            $tmpRows = [];
            $tmpRows[] = $this->type($supplier, $settings, 'deposit');
            $tmpRows[] = $this->type($supplier, $settings, 'withdraw');
            $tmpRows = array_filter($tmpRows);

            if (empty($tmpRows)) {
                continue;
            }

            if ($input->getOption('debug')) {
                foreach ($tmpRows as $tmpRow) {
                    $table->addRow($tmpRow);
                }

                $table->render();

                if ($style->confirm('Next?')) {
                    $table->setRows([]);
                    continue;
                }
                break;
            }

            foreach ($tmpRows as $tmpRow) {
                $rows[] = $tmpRow;
            }
        }

        if (!$input->getOption('debug')) {
            $this->exportCsv($rows);
        }

        return 0;
    }

    private function type(string $supplier, array $settings, string $type): array
    {
        if (array_key_exists('display_name', $settings)) {
            $supplier = sprintf('%s (%s)', $settings['display_name'], $supplier);
        }

        $group = $settings['type'] ?? '';

        if (array_key_exists('via', $settings) && $settings['via']['active'] ?? false) {
            $network = $settings['via']['network'];
        } else {
            $network = '';
        }

        $includedCountries = implode('|', $settings[$type]['included_countries'] ?? []);
        $excludedCountries = implode('|', $settings[$type]['excluded_countries'] ?? []);
        $includedProvinces = implode('|', array_flatten($settings[$type]['included_provinces']) ?? []); //FIXME: should be CA_ON instead od ON, but for now we are using it only for Ontario so this is clear
        $excludedProvinces = implode('|', array_flatten($settings[$type]['excluded_provinces']) ?? []);
        $includedCurrencies = implode('|', $settings[$type]['included_currencies'] ?? []);
        $excludedCurrencies = implode('|', $settings[$type]['excluded_currencies'] ?? []);

        if (
            empty($includedCountries) &&
            empty($excludedCountries) &&
            empty($includedProvinces) &&
            empty($excludedProvinces) &&
            empty($includedCurrencies) &&
            empty($excludedCurrencies)
        ) {
            return [];
        }

        return [
            $supplier,
            $group,
            $network,
            $type,
            $settings[$type]['active'] ? 'true' : 'false',
            $includedCountries,
            $excludedCountries,
            $includedProvinces,
            $excludedProvinces,
            $includedCurrencies,
            $excludedCurrencies,
        ];
    }

    public function normalizeInput(InputInterface $input): array
    {
        return array_map(fn($value) => strtolower(ltrim($value, '=')), $input->getOption('supplier'));
    }

    public function exportCsv(array $rows): void
    {
        $buffer = fopen('php://stdout', 'w');
        fputcsv($buffer, self::HEADERS);
        foreach ($rows as $row) {
            fputcsv($buffer, $row);
        }

        fclose($buffer);
    }
}