<?php

namespace Tests\Unit\Modules;

class AcurisV2Test extends AcurisTest
{
    protected function setModuleVersion(): void
    {
        $this->module_version = 'AcurisV2';
    }

    public function responseDataProvider(): array
    {
        return [
            'valid response REFER' => [
                (object) [
                    'score' => 100,
                    'datasets' => ['PEP-CURRENT', 'SAN-CURRENT'],
                ],
                'asset_result' => 'ALERT',
            ],
            'valid response PASS' => [
                (object) [
                    'score' => 50,
                    'datasets' => [],
                ],
                'asset_result' => 'PASS',
            ],
            'failure response ERROR' => [
                (object) [
                    'score' => 50,
                ],
                'asset_result' => 'ERROR',
            ],
        ];
    }
}
