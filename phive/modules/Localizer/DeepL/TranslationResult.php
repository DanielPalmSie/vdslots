<?php

namespace Localizer\DeepL;

use DeepL\DeepLException;
use DeepL\LanguageCode;
use DeepL\TextResult;
use Exception;

class TranslationResult
{
    public string $text;

    public ?string $detected_source_lang;

    public ?string $error;

    public function __construct(string $text, ?string $detectedSourceLang = null, ?string $error = null)
    {
        $this->text = $text;
        $this->detected_source_lang = $detectedSourceLang
            ? LanguageCode::standardizeLanguageCode($detectedSourceLang)
            : null;
        $this->error = $error;
    }

    public static function fromDeepLResult(TextResult $deepl_result): TranslationResult
    {
        return new TranslationResult($deepl_result->text, $deepl_result->detectedSourceLang);
    }

    public static function fromDeepLException(DeepLException $e): TranslationResult
    {
        return new TranslationResult('', null, $e->getMessage());
    }

    public static function fromError(string $error): TranslationResult
    {
        return new TranslationResult('', null, $error);
    }

    public function isErrorResult(): bool
    {
        return !!$this->error;
    }

    public function merge(TranslationResult $other): void
    {
        if ($this->isErrorResult() || $other->isErrorResult()) {
            throw new Exception("Can't merge DeepL error response");
        }

        $this->text .= $other->text;
        $this->detected_source_lang = $other->detected_source_lang;
    }
}
