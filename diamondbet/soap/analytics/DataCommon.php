<?php

use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class DataCommon
{
    public const exportDaysLimit = 2;
    public array $xlsHeaderStyle = [
        'font' => [
            'bold' => true,
            'name' => 'Arial',
            'size' => 12
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['argb' => 'ddd'],
            ],
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['argb' => 'f2f2f2']
        ]
    ];

    public function fileSettings($filename = '_deposits_')
    {
        $dbHost = phive('SQL')->settings_data['hostname'];

        $config = [
            'platform' => '_Videoslots',
            'file_name' => $filename,
        ];

        if ($dbHost == 'db-prod-vs-m.vs.prod') {
            $config['platform'] = '_Videoslots';
        } elseif ($dbHost == 'db-prod-mrv-m.vs.prod') {
            $config['platform'] = '_Mrvegas';
        } elseif ($dbHost == 'db-prod-kungaslottet.vs.prod') {
            $config['platform'] = '_Kungaslottet';
        } elseif ($dbHost == 'db-prod-megariches.vs.prod') {
            $config['platform'] = '_Megariches';
        }

        return $config;
    }
}
