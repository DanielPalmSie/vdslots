<?php

namespace Tests\Unit\Modules;

class AcurisV1Test extends AcurisTest
{
    protected function setModuleVersion(): void
    {
        $this->module_version = 'AcurisV1';
    }

    public function responseDataProvider(): array
    {
        return [
            'valid response REFER' => [
                [
                    'score' => 100,
                    'person' => [
                        'isPEP' => true,
                        'isSanctionsCurrent' => true,
                    ],
                ],
                'asset_result' => 'ALERT',
            ],
            'valid response PASS' => [
                [
                    'score' => 50,
                    'isPEP' => false,
                    'isSanctionsCurrent' => false,
                ],
                'asset_result' => 'PASS',
            ],
            'failure response ERROR' => [
                [
                    'score' => 50,
                ],
                'asset_result' => 'ERROR',
            ],
        ];
    }
}
