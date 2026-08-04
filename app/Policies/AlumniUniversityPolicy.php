<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AlumniUniversity;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class AlumniUniversityPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AlumniUniversity');
    }

    public function view(AuthUser $authUser, AlumniUniversity $alumniUniversity): bool
    {
        return $authUser->can('View:AlumniUniversity');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AlumniUniversity');
    }

    public function update(AuthUser $authUser, AlumniUniversity $alumniUniversity): bool
    {
        return $authUser->can('Update:AlumniUniversity');
    }

    public function delete(AuthUser $authUser, AlumniUniversity $alumniUniversity): bool
    {
        return $authUser->can('Delete:AlumniUniversity');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:AlumniUniversity');
    }

    public function restore(AuthUser $authUser, AlumniUniversity $alumniUniversity): bool
    {
        return $authUser->can('Restore:AlumniUniversity');
    }

    public function forceDelete(AuthUser $authUser, AlumniUniversity $alumniUniversity): bool
    {
        return $authUser->can('ForceDelete:AlumniUniversity');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AlumniUniversity');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AlumniUniversity');
    }

    public function replicate(AuthUser $authUser, AlumniUniversity $alumniUniversity): bool
    {
        return $authUser->can('Replicate:AlumniUniversity');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AlumniUniversity');
    }
}
