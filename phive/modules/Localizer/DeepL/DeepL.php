<?php

use DeepL\DeepLException;
use DeepL\DocumentHandle;
use DeepL\DocumentStatus;
use DeepL\LanguageCode;
use DeepL\TranslateTextOptions;
use DeepL\Translator;
use DeepL\TranslatorOptions;
use Localizer\DeepL\PlaceholderHandler;
use Localizer\DeepL\TextSplitter;
use Localizer\DeepL\TranslationResult;

require_once __DIR__ . '/PlaceholderHandler.php';
require_once __DIR__ . '/TextSplitter.php';
require_once __DIR__ . '/TranslationResult.php';

/**
 * Wrapper class around Official DeepL API Client Library
 *
 * @see https://packagist.org/packages/deeplcom/deepl-php
 * @see https://www.deepl.com/docs-api/introduction
 */
class DeepL
{
    /**
     * Max body size for text translation request that we use. If text is bigger - we should split translation into
     * multiple requests.
     *
     * DeepL actually supports body sizes up to 128 * 1024 bytes (https://www.deepl.com/docs-api/translate-text).
     * We use 100 * 1024 here because we need to save space for other parameters.
     */
    private const MAX_TEXT_TRANSLATION_BODY_SIZE = 100 * 1024;

    private Translator $translator;

    /**
     * Class that splits strings into chunks if they are too big to be handled in one request.
     *
     * @var TextSplitter
     */
    private TextSplitter $text_splitter;

    /**
     * Class responsible for processing placeholders before and after translation.
     * Thus, we make sure placeholders are not translated.
     *
     * Example flow:
     *  1. input string: "<b>Last login: {{date}}</b>"
     *  2. processed string: "<b>Last login: <m /></b>"
     *  3. processed string is sent for translation, and we get response: "<b>Sista inloggning: <m /></b>"
     *  4. return back placeholder: "<b>Sista inloggning: {{date}}</b>"
     *
     * @var PlaceholderHandler
     */
    private PlaceholderHandler $placeholder_handler;

    /**
     * Mapping between language codes used in app and DeepL language codes
     *
     * @var array
     */
    private array $language_map = [
        'br' => LanguageCode::PORTUGUESE_BRAZILIAN,
        'cl' => LanguageCode::SPANISH,
        'dgoj' => LanguageCode::SPANISH,
        'no' => LanguageCode::NORWEGIAN,
        'on' => LanguageCode::ENGLISH_BRITISH,
        'pe' => LanguageCode::SPANISH,
    ];

    public function __construct()
    {
        $auth_key = phive('Localizer')->getSetting('deepl')['PRIVATE_KEY'];

        $this->translator = new Translator($auth_key, [
            TranslatorOptions::TIMEOUT => 60
        ]);

        $this->text_splitter = new TextSplitter(self::MAX_TEXT_TRANSLATION_BODY_SIZE);
        $this->placeholder_handler = new PlaceholderHandler();
    }

    public function getTranslator(): Translator
    {
        return $this->translator;
    }

    public function getUsedCharactersCount(): int
    {
        return $this->translator->getUsage()->character->count;
    }

    public function getCharactersLimit(): int
    {
        return $this->translator->getUsage()->character->limit;
    }

    /**
     * Translates text string or array of text strings into the target language.
     * If string to translate is bigger than DeepL limit - splits translation into multiple requests.
     *
     * @param $value string|string[] A single string or array of strings containing input texts to translate.
     * @param string|null $source_lang Language code of input text language, or null to use auto-detection.
     * @param string $target_lang Language code of language to translate into.
     * @param array $options Translation options to apply. See \DeepL\TranslateTextOptions.
     * @return TranslationResult|TranslationResult[] A TextResult or array of TextResult objects containing translated texts.
     *
     * @see \DeepL\TranslateTextOptions
     */
    public function translate($value, ?string $source_lang, string $target_lang, array $options = [])
    {
        if ($this->areSameLangs($source_lang, $target_lang)) {
            return new TranslationResult($value);
        }

        if (!is_string($value) || strlen($value) < self::MAX_TEXT_TRANSLATION_BODY_SIZE) {
            return $this->translateWithoutSplitting($value, $source_lang, $target_lang, $options);
        }

        $chunks_to_translate = $this->text_splitter->getSplitted($value);
        $translation_result = new TranslationResult('');

        foreach ($chunks_to_translate as $chunk) {
            $chunk_translation_result = $this->translateWithoutSplitting($chunk, $source_lang, $target_lang, $options);
            if ($chunk_translation_result->isErrorResult()) {
                return $chunk_translation_result;
            }

            $translation_result->merge($chunk_translation_result);
        }

        return $translation_result;
    }

    /**
     * Translates text string or array of text strings into the target language.
     * Does not support splitting for large input strings. If you need splitting @see translate()
     *
     * @param $value string|string[] A single string or array of strings containing input texts to translate.
     * @param string|null $source_lang Language code of input text language, or null to use auto-detection.
     * @param string $target_lang Language code of language to translate into.
     * @param array $options Translation options to apply. See \DeepL\TranslateTextOptions.
     * @return TranslationResult|TranslationResult[] A TextResult or array of TextResult objects containing translated texts.
     *
     * @see \DeepL\TranslateTextOptions
     */
    public function translateWithoutSplitting($value, ?string $source_lang, string $target_lang, array $options = [])
    {
        $error = $this->validateTranslationInput($value, $target_lang);
        if ($error) {
            return TranslationResult::fromError($error);
        }

        [$placeholders, $value] = $this->placeholder_handler->wrapPlaceholders($value);

        try {
            $deepl_result = $this->translator->translateText(
                $value,
                $source_lang ? $this->normalizeLang($source_lang) : null,
                $this->normalizeLang($target_lang),
                $options
            );

            if (is_array($deepl_result)) {
                $translation_result = array_map(function($result) {
                    return TranslationResult::fromDeepLResult($result);
                }, $deepl_result);
            } else {
                $translation_result = TranslationResult::fromDeepLResult($deepl_result);
            }

            if (!empty($placeholders)) {
                $translation_result = $this->placeholder_handler->unwrapPlaceholders($translation_result, $placeholders);
            }

            return $translation_result;
        } catch (DeepLException $e) {
            return TranslationResult::fromDeepLException($e);
        }
    }

    /**
     * Translates HTML string or array of HTML strings into the target language.
     *
     * @see translate()
     */
    public function translateHtml($value, ?string $source_lang, string $target_lang, array $options = [])
    {
        $patched_options = array_merge($options, [TranslateTextOptions::TAG_HANDLING => 'html']);
        return $this->translate($value, $source_lang, $target_lang, $patched_options);
    }

    /**
     * Translates text string or array of text strings into many target languages.
     *
     * @param string|string[] $value A single string or array of strings containing input texts to translate.
     * @param string|null $source_lang Language code of input text language, or null to use auto-detection.
     * @param array $target_langs
     * @param array $options Translation options to apply. See \DeepL\TranslateTextOptions.
     * @return array Array in format [target_lang1 => translated_value1, target_lang2 => translated_value2, ...].
     *
     * @see \DeepL\TranslateTextOptions
     */
    public function translateToManyLangs($value, ?string $source_lang, array $target_langs, array $options = []): array
    {
        $lang_value_map = [];
        $normalized_lang_value_map = [];

        foreach ($target_langs as $target_lang) {
            $normalized_lang = $this->normalizeLang($target_lang);

            // skip API call if we already have translation for a normalized language
            // (e.g. if we have $target_langs === ['cl', 'dgoj'], we can translate to es once)
            if (!array_key_exists($normalized_lang, $normalized_lang_value_map)) {
                $translated_value = $this->translate($value, $source_lang, $target_lang, $options);

                $normalized_lang_value_map[$normalized_lang] = $translated_value;
            }

            $lang_value_map[$target_lang] = $normalized_lang_value_map[$normalized_lang];
        }

        return $lang_value_map;
    }

    /**
     * Translates HTML string or array of HTML strings into many target languages.
     *
     * @see translateToManyLangs()
     */
    public function translateHtmlToManyLangs($value, ?string $source_lang, array $target_langs, array $options = []): array
    {
        $patched_options = array_merge($options, [TranslateTextOptions::TAG_HANDLING => 'html']);
        return $this->translateToManyLangs($value, $source_lang, $target_langs, $patched_options);
    }

    /**
     * @see Translator::getSourceLanguages()
     */
    public function getSourceLanguages(): array
    {
        return $this->translator->getSourceLanguages();
    }

    /**
     * @see Translator::getTargetLanguages()
     */
    public function getTargetLanguages(): array
    {
        return $this->translator->getTargetLanguages();
    }

    /**
     * @see Translator::uploadDocument()
     */
    public function uploadDocument(
        string $input_file,
        ?string $source_lang,
        string $target_lang,
        array $options = []
    ) : DocumentHandle {
        $source_lang = $source_lang ? $this->normalizeLang($source_lang) : null;
        $target_lang = $this->normalizeLang($target_lang);

        return $this->translator->uploadDocument($input_file, $source_lang, $target_lang, $options);
    }

    /**
     * @see Translator::getDocumentStatus()
     */
    public function getDocumentStatus(DocumentHandle $handle): DocumentStatus
    {
        return $this->translator->getDocumentStatus($handle);
    }

    /**
     * @see Translator::downloadDocument()
     */
    public function downloadDocument(DocumentHandle $handle, string $outputFile): void
    {
        $this->translator->downloadDocument($handle, $outputFile);
    }

    public function createDocumentHandle(string $document_id, string $document_key): DocumentHandle
    {
        return new DocumentHandle($document_id, $document_key);
    }

    private function areSameLangs(?string $source_lang, string $target_lang): bool
    {
        $normalized_source = $this->normalizeLang($source_lang);
        $normalized_target = $this->normalizeLang($target_lang);

        if ($normalized_source === $normalized_target) {
            return true;
        }

        if ($normalized_source === LanguageCode::ENGLISH && str_starts_with($normalized_target, 'en-')) {
            return true;
        }

        return false;
    }

    private function normalizeLang($lang): string
    {
        return $this->language_map[$lang] ?? $lang;
    }

    private function validateTranslationInput($value, $target_lang): ?string
    {
        if ($value === '') {
            return "Provide 'en' value";
        }

        if ($target_lang === 'hi') {
            return "Translation to 'hi' is not supported by DeepL at the moment";
        }

        return null;
    }
}
