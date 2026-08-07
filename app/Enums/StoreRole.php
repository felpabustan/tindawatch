<?php

namespace App\Enums;

enum StoreRole: string
{
    case Owner = 'owner';
    case Manager = 'manager';
    case Staff = 'staff';

    public function canManageCatalog(): bool
    {
        return $this === self::Owner || $this === self::Manager;
    }

    public function canManageTeam(): bool
    {
        return $this === self::Owner || $this === self::Manager;
    }

    public function canManageStoreSettings(): bool
    {
        return $this === self::Owner || $this === self::Manager;
    }
}
