<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Asset;

class AssetPolicy
{
    public function viewAny(User $user): bool { return $user->can('asset-view'); }
    public function view(User $user, Asset $asset): bool { return $user->can('asset-view'); }
    public function create(User $user): bool { return $user->can('asset-create'); }
    public function update(User $user, Asset $asset): bool { return $user->can('asset-edit'); }
    public function delete(User $user, Asset $asset): bool { return $user->can('asset-delete'); }
    public function manage(User $user): bool { return $user->can('asset-manage'); }
    public function approve(User $user, Asset $asset): bool { return $user->can('asset-approve'); }
    public function bulkImport(User $user): bool { return $user->can('asset-bulk-import'); }
    public function export(User $user): bool { return $user->can('asset-export'); }
}

