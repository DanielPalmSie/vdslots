<?php

namespace Localizer\DeepL;

class PlaceholderHandler
{
    /**
     * Wrap placeholders in a value and get placeholders and value
     *
     * @param string|string[] $value
     * @return array{0: string[], 1: string|string[]}
     */
    public function wrapPlaceholders($value): array
    {
        $placeholders = $this->getPlaceholders($value);
        if (!empty($placeholders)) {
            $value = $this->wrapPlaceholdersWithTag($value);
        }

        return [$placeholders, $value];
    }

    /**
     * Unwrap placeholders.
     * "<b>Last login: <m /></b>" -> "<b>Last login: {{date}}</b>", given $placeholders == ["{{date}}"]
     *
     * @param TranslationResult|TranslationResult[] $translation_results
     * @param string[] $placeholders
     * @return TranslationResult|TranslationResult[]
     */
    public function unwrapPlaceholders($translation_results, array $placeholders)
    {
        if (!is_array($translation_results)) {
            $translation_results = [$translation_results];
        }

        $current_placeholder_number = 0;

        foreach ($translation_results as $result) {
            $result->text = preg_replace_callback(
                '/<m \/>/',
                function () use ($placeholders, &$current_placeholder_number) {
                    $current_placeholder_value = $placeholders[$current_placeholder_number];
                    $current_placeholder_number++;
                    return $current_placeholder_value;
                },
                $result->text
            );
        }

        if (count($translation_results) === 1) {
            return $translation_results[0];
        }

        return $translation_results;
    }

    /**
     * Wrap placeholders.
     * "<b>Last login: {{date}}</b>" -> "<b>Last login: <m /></b>".
     * "<m />" stands for Mustache ({{...}} syntax)
     *
     * @param string|string[] $value
     * @return string|string[]
     */
    private function wrapPlaceholdersWithTag($value)
    {
        if (is_string($value)) {
            $value = [$value];
        }

        $regex = $this->getRegex();
        $wrapped_values = [];

        foreach ($value as $string) {
            $wrapped_values[] = preg_replace($regex, '<m />', $string);
        }

        if (count($wrapped_values) === 1) {
            return $wrapped_values[0];
        }

        return $wrapped_values;
    }

    /**
     * Get placeholders from value.
     * For example, "<b>Last login: {{date}}</b>" has {{date}} placeholder.
     * Thus, return value will be ['{{date}}'].
     *
     * @param string|string[] $value
     * @return string[]
     */
    private function getPlaceholders($value): array
    {
        $placeholders = [];

        if (is_string($value)) {
            $value = [$value];
        }

        $regex = $this->getRegex();

        foreach ($value as $string) {
            $matches = [];
            preg_match_all($regex, $string, $matches);

            $placeholders = array_merge($placeholders, $matches[0]);
        }

        return $placeholders;
    }

    /**
     * Get placeholders regex.
     * Following placeholders are supported:
     * 1. {...}
     * 2. {{...}}
     *
     * @return string
     */
    private function getRegex(): string
    {
        $single_bracket_regex = '{[^}]*}';
        $double_bracket_regex = '{{[^}]*}}';

        return "/($double_bracket_regex|$single_bracket_regex)/";
    }
}
