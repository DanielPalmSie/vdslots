<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 4/26/17
 * Time: 10:41 AM
 */

namespace App\Extensions\Database\Eloquent;

use Illuminate\Database\Eloquent\Builder as BaseEloquentBuilder;

class Builder extends BaseEloquentBuilder
{
    /**
     * @param array $models
     * @param string $name
     * @param \Closure $constraints
     * @return array
     */
    protected function eagerLoadRelation(array $models, $name, \Closure $constraints)
    {
        $relation = $this->getRelation($name);

        $relation->addEagerConstraints($models);

        $constraints($relation);

        return $relation->match(
            $relation->initRelation($models, $name),
            $relation->shs()->getEager(), $name
        );
    }

}