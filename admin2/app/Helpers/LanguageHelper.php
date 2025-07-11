<?php
namespace App\Helpers;

use App\Models\LocalizedStrings as LocalizedStrings;
use App\Extensions\Database\FManager as DB;

class LanguageHelper
{
    /**
     * Get languages name for English version passing the acronym
     *
     * @param string $lang en / dk / fi / mt ...
     * @return string English / Danish / Finnish / Maltese ...
     */

    public static function languageMapEnglish($lang)
    {
        return LocalizedStrings::where(['alias' => 'lang.' . $lang, 'language' => 'en'])->get()->first()->value;
    }

    /**
     * @return mixed
     */
    public static function getAllLanguagesWithEngVersion()
    {
        $language_in = [];
        foreach (DB::table('languages')->get(['language']) as $lang) {
            $language_in[] = "lang.{$lang->language}";
        }

        return DB::table('localized_strings')
            ->selectRaw('SUBSTR(alias,6) as alias, value')
            ->whereIn('alias', $language_in)
            ->where('language', 'en')
            ->get();
    }
}
