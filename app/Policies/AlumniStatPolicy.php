<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AlumniStat;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class AlumniStatPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AlumniStat');
    }

    public function view(AuthUser $authUser, AlumniStat $alumniStat): bool
    {
        return $authUser->can('View:AlumniStat');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AlumniStat');
    }

    public function update(AuthUser $authUser, AlumniStat $alumniStat): bool
    {
        return $authUser->can('Update:AlumniStat');
    }

    public function delete(AuthUser $authUser, AlumniStat $alumniStat): bool
    {
        return $authUser->can('Delete:AlumniStat');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:AlumniStat');
    }

    public function restore(AuthUser $authUser, AlumniStat $alumniStat): bool
    {
        return $authUser->can('Restore:AlumniStat');
    }

    public function forceDelete(AuthUser $authUser, AlumniStat $alumniStat): bool
    {
        return $authUser->can('ForceDelete:AlumniStat');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AlumniStat');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AlumniStat');
    }

    public function replicate(AuthUser $authUser, AlumniStat $alumniStat): bool
    {
        return $authUser->can('Replicate:AlumniStat');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AlumniStat');
    }
}
