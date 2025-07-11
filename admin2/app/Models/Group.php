<?php

namespace App\Models;

use App\Extensions\Database\FModel;
use Valitron\Validator;

class Group extends FModel
{
    public $timestamps = false;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $primaryKey = 'group_id';

    protected function rules()
    {
        return [
            'default' => [
                'required' => [['name']],       // needs to be unique
                'integer' => [['group_id']],
                'lengthMin' => [['name', 3]],
                'lengthMax' => [['name', 64]],
            ]
        ];
    }

    /**
     *
     * @return boolean
     */
    public function validate()
    {
        if (parent::validate() === false) {
            return false;
        }

        // Make sure group_name is unique
        $validator = new Validator($this->getAttributes());

        $existing_group = Group::where('name', $this->getAttribute('name'))->first();

        if (!empty($existing_group)) {
            $validator->error('name', "The name {$this->getAttribute('name')} is already in use, please choose another name");
        }

        if (!$validator->validate()) {
            $this->overrideErrors($validator->errors());
            return false;
        } else {
            return true;
        }
    }

    public function permission_groups()
    {
        return $this->hasMany('App\Models\PermissionGroup', 'group_id', 'group_id');
    }

    public function groupMembers()
    {
        return $this->hasMany('App\Models\GroupMember', 'group_id', 'group_id');
    }

    public function manageable_groups()
    {
        $manageable_groups = [];
        foreach ($this->permission_groups()->get() as $permission) {
            $found_manageable_group = preg_match('/permission\.(edit|view)\.(\d+)/', $permission->tag, $matches);

            if ($found_manageable_group) {
                $manageable_groups[] = (int)$matches[2];
            }
        }

        return $manageable_groups;
    }
}