<?php

namespace CA;

use DBUser;

class UserMigrator
{
    /**
     * @param DBUser $user The user object.
     */
    private DBUser $user;

    public function __construct(DBUser $user)
    {
        $this->user = $user;
    }

    public function requiresMigration(): bool
    {
        return (!$this->hasMigration() && $this->hasRegisteredBeforeCutoffDate()) || ($this->hasCompletedRegistration() && !$this->hasRequiredData());
    }

    /**
     * @return mixed
     */
    private function hasMigration()
    {
        return $this->user->getSetting('migrated');
    }

    private function hasRegisteredBeforeCutoffDate(): bool
    {
        $migrationCutOffDate = licSetting('migration_cutoff_date', $this->user);
        return $migrationCutOffDate && (strtotime($this->user->data['register_date']) < strtotime($migrationCutOffDate));
    }

    private function hasRequiredData(): bool
    {
        $settings = $this->user->getSettingsIn(['main_province', 'building']);
        return count($settings) == 2 && $this->user->data['currency'] == "CAD";
    }

    private function hasCompletedRegistration(): bool
    {
        return !($this->user->hasSetting('registration_in_progress'));
    }
}
