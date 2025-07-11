<?php

namespace App\Models;

use App\Extensions\Database\FModel;

class LocalizedStrings extends FModel
{
    use Traits\HasCompositePrimaryKey;

    public $timestamps    = false;
    protected $table      = 'localized_strings';
    public $incrementing  = false;
    protected $primaryKey = ['alias', 'language'];
    protected $fillable   = ['alias', 'language', 'value'];

    protected function rules()
    {
        return [
            'default' => [
                'regex' => [['alias', '/^[a-zA-Z_0-9\-\.]+$/']],
                'required' => [['alias']],
                'lengthMin' => [['alias', 3]],
            ]
        ];
    }
}
