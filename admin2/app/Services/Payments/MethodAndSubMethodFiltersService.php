<?php

namespace App\Services\Payments;

class MethodAndSubMethodFiltersService
{
    /*
     * Methods labeled as submethods in the config and database are, in fact, actual methods.
     * For instance, Zimplerbank should be classified as a 'Method' and is currently a 'Submethod' of Zimpler.
    */
    private array $subMethodToMethodMapping = [
        'zimplerbank' => 'zimpler'
    ];

    /*
     * These methods are not needed.
    */
    private array $unNecessaryMethods = [
        'ecashout',
        'interacetransfer',
        'zimplerbank'
    ];

    /*
     * Some methods are logging bank details as submethods alongside actual submethods.
     * Therefore, we are focusing on retaining only the specific submethods list of those methods.
     */
    private array $includedSubMethods = [
        'trustly' => ['swish']
    ];

    /*
     * These should be treated as submethods, not methods.
     * For instance, Cleanpay and Interac are submethods of PaymentIQ.
     * Currently, the system incorrectly classifies them as methods.
    */
    public array $customViaMapping = [
        'cleanpay' => 'paymentiq',
        'epaysolution' => 'paymentiq',
        'interac' => 'paymentiq',
        'ctcevoucher' => 'cashtocode'
    ];

    /*
     * Some methods have configs but lack submethods in the database.
     * This causes query binding issues, such as treating Worldpay methods without scheme or wallet info as 'wpsepa.'
     */
    public array $overrideSubmethodValue = [
        'worldpay' => [
            'subMethod' => 'wpsepa',
            'subMethodOverrideValue' => ''
        ],
        'adyen' => [
            'subMethod' => 'sepa',
            'subMethodOverrideValue' => ''
        ]
    ];

    /*
     * The default columns for deposits and pending withdrawals where we store submethod information.
    */
    private array $defaultSubMethodColumn = [
        'deposit' => 'scheme',
        'withdrawal' => 'wallet'
    ];

    /*
     * Submethod information for various methods is stored in columns other than the default ones.
     * We are addressing this inconsistency by specifying the column names for them.
    */
    private array $subMethodColumnMapping = [
        'deposit' => [
            'cleanpay' => 'dep_type',
            'epaysolution' => 'dep_type',
            'interac' => 'dep_type',
            'ctcevoucher' => 'dep_type'
        ],
        'withdrawal' => [
            'mifinity' => 'scheme',
            'paymentiq' => 'scheme',
            'payretailers' => 'scheme',
            'skrill' => 'scheme'
        ]
    ];

    public function columnUsedForSubMethod(
        ?string $method = null,
        ?string $type = null
    ): array
    {
        $defaultColumns = $this->defaultSubMethodColumn;
        $columnMappings = $this->subMethodColumnMapping;

        // Case 1: When a specific method is provided
        if ($method) {
            // Return the column for the given method or fallback to the default column for deposit and withdrawal
            return [
                $columnMappings['deposit'][$method] ?? $defaultColumns['deposit'],
                $columnMappings['withdrawal'][$method] ?? $defaultColumns['withdrawal']
            ];
        }

        // Case 2: When a specific type is provided (e.g., 'deposit', 'withdrawal'), and it exists in the column mappings
        if ($type && isset($columnMappings[$type])) {
            $mapping = $columnMappings[$type];
            $columnMethods = [];

            foreach ($mapping as $methodKey => $column) {
                if (!isset($columnMethods[$column])) {
                    $columnMethods[$column] = [];
                }
                $columnMethods[$column][] = $methodKey;
            }

            if (empty($columnMethods)) {
                $columnMethods[$defaultColumns[$type]] = [];
            }

            return $columnMethods;
        }

        // Case 3: Return default columns if no method or type is specified
        return array_values($defaultColumns);
    }

    /**
     * Filters the methods and subMethods data based on the provided source and main method.
     *
     * @param array &$data The array to be filtered.
     * @param string $source The source of the data, can be 'config', 'dbMethod', or 'dbSubMethod'.
     * @param ?string $mainMethod The main method to focus on (used only for 'dbSubMethod' source).
     *
     * @return void
     */
    public function filterMethodAndSubMethodData(
        array   &$data,
        string  $source = 'config',
        ?string $mainMethod = null
    ): void
    {
        if ($source === 'config') {
            $this->filterDataByConfigSource($data);
        } elseif ($source === 'dbMethod') {
            $this->filterDataByDbMethodSource($data);
        } elseif ($source === 'dbSubMethod') {
            $this->filterDataByDbSubMethodSource($data, $mainMethod);
        }
    }

    private function filterDataByConfigSource(array &$data): void
    {
        // Handle custom via mapping by moving subMethods under their 'via' method
        foreach ($this->customViaMapping as $subMethod => $via) {
            if (isset($data[$subMethod])) {
                // Remove the original subMethod from the methods list
                unset($data[$subMethod]);

                // Add the subMethod to its corresponding 'via' method's list
                if (!isset($data[$via])) {
                    $data[$via] = [];
                }

                $data[$via][] = $subMethod;
            }
        }

        // Separate sub-methods from their main methods and move them individually as method
        foreach ($this->subMethodToMethodMapping as $subMethod => $method) {
            if (isset($data[$method])) {
                $data[$method] = array_filter($data[$method], fn($sm) => $sm !== $subMethod);
                $data[$subMethod] = $data[$subMethod] ?? [];
            }
        }

        // Remove unnecessary methods from the data (methods list)
        foreach ($this->unNecessaryMethods as $method) {
            unset($data[$method]);
        }

        // Retain only the included sub-methods for specific methods
        foreach ($this->includedSubMethods as $method => $subMethodList) {
            if (isset($data[$method])) {
                $data[$method] = array_intersect($data[$method], $subMethodList);
            }
        }
    }

    private function filterDataByDbMethodSource(array &$data): void
    {
        // Remove submMethods that are mapped via customViaMapping
        foreach ($this->customViaMapping as $subMethod => $via) {
            if (($key = array_search($subMethod, $data)) !== false) {
                unset($data[$key]);
            }
        }

        // Remove unnecessary methods from the data (methods list)
        foreach ($this->unNecessaryMethods as $method) {
            unset($data[array_search($method, $data)]);
        }
    }

    private function filterDataByDbSubMethodSource(array &$data, ?string $mainMethod): void
    {
        // Add sub-methods to the filtered data if their 'via' method matches the main method
        foreach ($this->customViaMapping as $subMethod => $via) {
            if ($via === $mainMethod && !in_array($subMethod, $data)) {
                $data[] = $subMethod;
            }
        }

        // Retain only the included sub-methods for the specified main method
        foreach ($this->includedSubMethods as $method => $subMethodList) {
            if ($mainMethod === $method) {
                $data = array_filter($data, fn($subMethod) => in_array($subMethod, $subMethodList));
            }
        }
    }

    public function getMethodsAndSubMethodsFromConfig(
        ?string $method = null,
        ?string $filterType = null
    ): array
    {
        $cashier = phive('Cashier');
        $psps = array_merge(
            $cashier->getSetting('psp_config_2'),
            $cashier->getSetting('ccard_psps')
        );

        $result = [];

        foreach ($psps as $psp => $pspConfig) {
            if ($pspConfig['type'] === 'ccard') {
                continue;
            }

            if (!empty($filterType) && !isset($pspConfig[$filterType])) {
                continue;
            }

            if (isset($pspConfig['originators'])) {
                foreach ($pspConfig['originators'] as $originator) {
                    $result[$psp][] = $originator;
                }
            } elseif (isset($pspConfig['via']['network'])) {
                $result[$pspConfig['via']['network']][] = $psp;
            } elseif (isset($pspConfig['providers'])) {
                foreach ($pspConfig['providers'] as $provider) {
                    $result[$provider][] = $psp;
                }
            } elseif (isset($pspConfig['alias_of'])) {
                foreach ($pspConfig['alias_of'] as $aliasOf) {
                    $result[$aliasOf][] = $psp;
                }
            } else {
                if (!isset($result[$psp])) {
                    $result[$psp] = [];
                }
            }
        }

        // Apply additional filtering to the method and sub-method data
        $this->filterMethodAndSubMethodData($result);

        // If a specific method is requested, return its sub-methods
        if (!empty($method)) {
            $subMethods = $result[$method] ?? [];
            asort($subMethods);
            return $subMethods;
        }

        ksort($result);
        return $result;
    }
}
