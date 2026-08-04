<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ContentSection;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ContentSectionPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ContentSection');
    }

    public function view(AuthUser $authUser, ContentSection $contentSection): bool
    {
        return $authUser->can('View:ContentSection');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ContentSection');
    }

    public function update(AuthUser $authUser, ContentSection $contentSection): bool
    {
        return $authUser->can('Update:ContentSection');
    }

    public function delete(AuthUser $authUser, ContentSection $contentSection): bool
    {
        return $authUser->can('Delete:ContentSection');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ContentSection');
    }

    public function restore(AuthUser $authUser, ContentSection $contentSection): bool
    {
        return $authUser->can('Restore:ContentSection');
    }

    public function forceDelete(AuthUser $authUser, ContentSection $contentSection): bool
    {
        return $authUser->can('ForceDelete:ContentSection');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ContentSection');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ContentSection');
    }

    public function replicate(AuthUser $authUser, ContentSection $contentSection): bool
    {
        return $authUser->can('Replicate:ContentSection');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ContentSection');
    }
}
