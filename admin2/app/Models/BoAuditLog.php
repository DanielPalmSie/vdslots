<?php
/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2015.11.16.
 * Time: 16:36
 */

namespace App\Models;

use App\Extensions\Database\FModel;
use App\Repositories\UserRepository;

class BoAuditLog extends FModel
{
    const ACTION_CREATE = 'CREATE';
    const ACTION_UPDATE = 'UPDATE';
    const ACTION_DELETE = 'DELETE';
    const DEACTIVATED = 'DEACTIVATED';
    const ACTIVATED = 'ACTIVATED';

    /**
     * Prevent created_at/updated_at missing error
     *
     * @var bool $timestamps
     */
    public $timestamps = false;
    protected $table = 'bo_audit_log';

    /**
     * @var string[]
     */
    protected $appends = [
        'human_readable_desc'
    ];


    /**
     * BoAuditLog constructor.
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setAttribute('actor_id', UserRepository::getCurrentUserId());
        $this->setAttribute('ip', remIp()); // phive function
    }

    /**
     * @return BoAuditLog
     */
    public static function instance()
    {
        return new self;
    }

    /**
     * @param $table
     * @param $id
     * @return $this
     */
    public function setTarget($table, $id)
    {
        $this->setAttribute('target_table', $table);
        $this->setAttribute('target_id', $id);
        return $this;
    }

    /**
     * @param $context
     * @param $id
     * @return $this
     */
    public function setContext($context, $id)
    {
        $this->setAttribute('context', $context);
        $this->setAttribute('context_id', $id);
        return $this;
    }

    /**
     * @param array $options
     * @return bool
     * @throws \Exception
     */
    public function save(array $options = [])
    {
        $attrs = ['actor_id', 'action', 'target_table', 'target_id', 'changes', 'ip'];

        foreach ($attrs as $attr) {
            if (empty($this->getAttribute($attr))) {
                throw new \Exception("Attribute $attr is empty.");
            }
        }

        return parent::save($options);
    }

    /**
     * @param $old
     * @param $new
     * @return array
     */
    public function getOnlyChanges($old, $new)
    {
        $aux = [];

        foreach ($new as $k => $new_v) {
            if ($old[$k] != $new_v) {
                $aux[$k] = $new_v;
            }
        }

        return $aux;
    }

    /**
     * @param $object
     * @return bool
     * @throws \Exception
     */
    public function registerCreate($object)
    {
        $this->setAttribute('action', self::ACTION_CREATE);
        $this->setAttribute('changes', json_encode($object));
        return $this->save();
    }

    /**
     * @param $old
     * @param $new
     * @return bool
     * @throws \Exception
     */
    public function registerUpdate($old, $new)
    {
        $new = $this->getOnlyChanges($old, $new);

        if (empty($new)) {
            return true;
        }

        $this->setAttribute('action', self::ACTION_UPDATE);
        $this->setAttribute('changes', json_encode(compact('new', 'old')));
        return $this->save();
    }

    /**
     * @param $object
     * @return bool
     * @throws \Exception
     */
    public function registerDelete($object)
    {
        $this->setAttribute('action', self::ACTION_DELETE);
        $this->setAttribute('changes', json_encode($object));
        return $this->save();
    }

    /**
     * @param $object
     * @return bool
     * @throws \Exception
     */
    public function registerActivated($object)
    {
        $this->setAttribute('action', self::ACTIVATED);
        $this->setAttribute('changes', is_string($object) ? $object : json_encode($object));
        return $this->save();
    }

    /**
     * @param $object
     * @return bool
     * @throws \Exception
     */
    public function registerDeactivated($object)
    {
        $this->setAttribute('action', self::DEACTIVATED);
        $this->setAttribute('changes', is_string($object) ? $object : json_encode($object));
        return $this->save();
    }

    /**
     * Returns a human-readable description with the list of changes.
     *
     * @return string
     */
    public function getHumanReadableDescAttribute(): string {
        $changes = $this->getAttribute('changes');
        $changes = json_decode($changes, true);

        if (empty($changes['new']) || empty($changes['old'])) {
            return $this->getAttribute('changes');
        }

        return array_reduce(array_keys($changes['new']), function ($description, $field_name) use ($changes) {
            $description .= "old-[{$field_name}: {$changes['old'][$field_name]}]<br>";
            $description .= "new-[{$field_name}: {$changes['new'][$field_name]}]<br>";
            return $description;
        }, "");
    }
}