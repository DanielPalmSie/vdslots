<?php
/**
 * Created by PhpStorm.
 * User: iondum
 * Date: 09/11/18
 * Time: 11:18
 */

namespace App\Models;

use App\Extensions\Database\FModel;
use App\Repositories\RiskProfileRatingRepository;
use Illuminate\Database\Eloquent\Builder;

class RiskProfileRating extends FModel
{
    const AML_SECTION = "AML";
    const RG_SECTION = "RG";
    const RATING_SCORE_PARENT_CATEGORY = 'rating_score';
    const CRIMINAL_RECORD_PARENT_CATEGORY = 'criminal_records';
    public $timestamps = false;
    public $incrementing = false;
    public $fillable = [
        'name',
        'jurisdiction',
        'title',
        'type',
        'score',
        'category',
        'section',
        'data'
    ];
    protected $table = 'risk_profile_rating';
    protected $primaryKey = 'name';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'data' => 'array',
    ];

    /**
     * Workaround: Eloquent doesn’t support composite primary keys.
     * https://blog.maqe.com/solved-eloquent-doesnt-support-composite-primary-keys-62b740120f
     *
     * @param Builder $query
     * @return Builder
     */
    protected function setKeysForSaveQuery(Builder $query)
    {
        $query
            ->where('name', '=', $this->getAttribute('name'))
            ->where('jurisdiction', '=', $this->getAttribute('jurisdiction'))
            ->where('category', '=', $this->getAttribute('category'))
            ->where('section', '=', $this->getAttribute('section'));

        return $query;
    }

    /**
     * This will return all the parents.
     * In order to get all the children for a specific parent A do A->children
     *
     * @param string|null $section
     * @param string|null $jurisdiction
     * @return Builder
     */
    public static function parents(?string $section = null, ?string $jurisdiction = null): Builder
    {
        $query = self::query()->where('category', '');

        if ($section) {
            $query->where('section', $section);
        }

        if ($jurisdiction) {
            $query->where('jurisdiction', $jurisdiction);
        }

        return $query->orderBy('title');
    }

    /**
     * @param $name
     * @param $section
     * @param $jurisdiction
     * @return Builder
     */
    public static function getParentCategoryByName($name, $section = null, $jurisdiction = null)
    {
        return self::parents($section, $jurisdiction)->where('name', $name);
    }

    /**
     *
     * @param $id
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function getElementById($id)
    {
        list($name, $jurisdiction, $category, $section) = self::explodeId($id);

        return self::query()
            ->where('name', $name)
            ->where('jurisdiction', $jurisdiction)
            ->where('category', $category)
            ->where('section', $section);
    }

    /**
     * Returns [name, jurisdiction, category, section]
     *
     * @param $id
     * @return array
     */
    public static function explodeId($id)
    {
        return explode('---', $id);
    }

    /**
     * Enable children attribute on all RiskProfileRating instances
     * It's supposed to be used only by parents
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children()
    {
        return $this->hasMany(RiskProfileRating::class, 'category', 'name')
            ->where('section', '=', $this->section)
            ->where('jurisdiction', '=', $this->jurisdiction);
    }

    /**
     * Dynamically set the id
     *
     * @return string
     */
    public function getIdAttribute()
    {
        return implode('---', [$this->name, $this->jurisdiction, $this->category, $this->section]);
    }

    public function getReplacedTitle()
    {
        if (!empty($this->data) && !empty($this->data['replacers'])) {
            foreach ($this->data['replacers'] as $replacer => $value) {
                $this->title = str_replace($replacer, $value, $this->title);
            }
        }

        return $this->title;
    }

    public function parent() {
        return $this->where('name', '=', $this->category)
            ->where('jurisdiction', '=', $this->jurisdiction)
            ->where('section', '=', $this->section)
            ->first();
    }

    /**
     * Detect if provided interval is the default interval [min, max]
     *
     * @param $start
     * @param $end
     * @return bool
     */
    public static function isDefaultInterval($start, $end)
    {
        return $start == RiskProfileRatingRepository::PROFILE_RATING_MIN_TAG
            && $end == RiskProfileRatingRepository::PROFILE_RATING_MAX_TAG;
    }

}