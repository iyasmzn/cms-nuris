<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SpmbRegistration;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SpmbRegistrationPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SpmbRegistration');
    }

    public function view(AuthUser $authUser, SpmbRegistration $spmbRegistration): bool
    {
        return $authUser->can('View:SpmbRegistration')
            && $this->handlesJenjangOf($authUser, $spmbRegistration);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SpmbRegistration');
    }

    public function update(AuthUser $authUser, SpmbRegistration $spmbRegistration): bool
    {
        return $authUser->can('Update:SpmbRegistration')
            && $this->handlesJenjangOf($authUser, $spmbRegistration);
    }

    public function delete(AuthUser $authUser, SpmbRegistration $spmbRegistration): bool
    {
        return $authUser->can('Delete:SpmbRegistration')
            && $this->handlesJenjangOf($authUser, $spmbRegistration);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SpmbRegistration');
    }

    public function restore(AuthUser $authUser, SpmbRegistration $spmbRegistration): bool
    {
        return $authUser->can('Restore:SpmbRegistration')
            && $this->handlesJenjangOf($authUser, $spmbRegistration);
    }

    public function forceDelete(AuthUser $authUser, SpmbRegistration $spmbRegistration): bool
    {
        return $authUser->can('ForceDelete:SpmbRegistration')
            && $this->handlesJenjangOf($authUser, $spmbRegistration);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SpmbRegistration');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SpmbRegistration');
    }

    public function replicate(AuthUser $authUser, SpmbRegistration $spmbRegistration): bool
    {
        return $authUser->can('Replicate:SpmbRegistration')
            && $this->handlesJenjangOf($authUser, $spmbRegistration);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SpmbRegistration');
    }

    /**
     * Whether the user may move a registration between statuses (menunggu,
     * terverifikasi, diterima, ditolak) without being allowed to edit the
     * pendaftar's own data. This is what the panitia verifikasi role holds.
     */
    public function updateStatus(AuthUser $authUser, SpmbRegistration $spmbRegistration): bool
    {
        return $authUser->can('UpdateStatus:SpmbRegistration')
            && $this->handlesJenjangOf($authUser, $spmbRegistration);
    }

    /**
     * Whether this registration belongs to a jenjang the user handles. The
     * resource query already hides other jenjang from every list; this is what
     * stops an action reaching a record fetched some other way.
     */
    private function handlesJenjangOf(AuthUser $authUser, SpmbRegistration $spmbRegistration): bool
    {
        return $authUser instanceof User
            && $authUser->seesInstitution($spmbRegistration->institution_id);
    }
}
