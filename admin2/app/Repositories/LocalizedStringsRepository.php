<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 27/09/16
 * Time: 09:48
 */

namespace App\Repositories;

use App\Models\LocalizedStrings;
use Silex\Application;

class LocalizedStringsRepository
{
    /** @var Application $app */
    protected $app;

    /**
     * TrophiesRepository constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function getAllByAlias($alias)
    {
        return LocalizedStrings::where('alias', '=', $alias)->get();
    }

    public function getByAliasAndLanguage($alias, $language)
    {
        return LocalizedStrings::where('alias', '=', $alias)->where('language', '=', $language)->first();
    }

    public function getForAllTrophiesAllByAlias($alias)
    {
        return LocalizedStrings::where('alias', 'LIKE', $alias.'%')->get();
    }

    public function updateAlias($previous_alias, $new_alias)
    {
        // TODO: Update using phive('Localizer')->memSet('trophyname.'.$trophy->alias, $language, $text); ?
        return LocalizedStrings::where('alias', '=', 'trophyname.'.$previous_alias)->update(['alias' => 'trophyname.'.$new_alias]);
    }

}
