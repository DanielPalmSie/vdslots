<?php

class DataCleaner
{
    private array $fieldsToClear = [
        'name', 'surname', 'address', 'dob',
        'username', 'email', 'password', 'phone'
    ];

    public function clearSensitiveFields($data)
    {
        try {
            return $this->clearFields($data);
        } catch (\Exception $e) {
            return $data;
        }
    }

    private function maskField(string $value): string
    {
        $length = strlen($value);

        if ($length < 3) {
            return str_repeat('*', $length);
        }

        if ($length < 7) {
            return substr($value, 0, 2) . str_repeat('*', $length - 2);
        }

        return substr($value, 0, 2) . str_repeat('*', $length - 4) . substr($value, -2);
    }

    private function clearFields($item)
    {
        if ($item instanceof Closure) {
            return $item;
        }

        if (is_object($item)) {
            if (method_exists($item, '__toString')) {
                return $item->__toString();
            }
            $item = (array) $item;
        }

        if (!is_array($item)) {
            return $item;
        }

        foreach ($item as $key => &$value) {
            if ($value instanceof Closure) {
                continue;
            }

            if (is_object($value)) {
                if (method_exists($value, '__toString')) {
                    $value = $value->__toString();
                    continue;
                }
                $value = (array) $value;
            }

            if (is_array($value)) {
                $item[$key] = $this->clearFields($value);
            } elseif (in_array($key, $this->fieldsToClear, true)) {
                $item[$key] = is_string($value) ?
                    $this->maskField($value) :
                    '*******';
            }
        }

        return $item;
    }
}
