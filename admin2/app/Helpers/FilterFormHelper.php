<?php

namespace App\Helpers;

class FilterFormHelper
{
    public static function processSerializedFormData(&$request): void
    {
        $data = [];

        foreach ($request->get('form') as $form_elem) {
            $name = ($isArray = substr($form_elem['name'], -2) === '[]')
                ? substr($form_elem['name'], 0, -2)
                : $form_elem['name'];
            $data[$name] = $isArray ? array_merge(($data[$name] ?? []), [$form_elem['value']]) : $form_elem['value'];
            $request->request->set($name, $data[$name]);
        }
    }
}
