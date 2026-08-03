<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RegistrationPayment;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class RegistrationPaymentPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RegistrationPayment');
    }

    public function view(AuthUser $authUser, RegistrationPayment $registrationPayment): bool
    {
        return $authUser->can('View:RegistrationPayment');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RegistrationPayment');
    }

    public function update(AuthUser $authUser, RegistrationPayment $registrationPayment): bool
    {
        return $authUser->can('Update:RegistrationPayment');
    }

    public function delete(AuthUser $authUser, RegistrationPayment $registrationPayment): bool
    {
        return $authUser->can('Delete:RegistrationPayment');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:RegistrationPayment');
    }

    public function restore(AuthUser $authUser, RegistrationPayment $registrationPayment): bool
    {
        return $authUser->can('Restore:RegistrationPayment');
    }

    public function forceDelete(AuthUser $authUser, RegistrationPayment $registrationPayment): bool
    {
        return $authUser->can('ForceDelete:RegistrationPayment');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RegistrationPayment');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RegistrationPayment');
    }

    public function replicate(AuthUser $authUser, RegistrationPayment $registrationPayment): bool
    {
        return $authUser->can('Replicate:RegistrationPayment');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RegistrationPayment');
    }

    /**
     * Whether the user may settle or reject a tagihan. Deliberately separate
     * from `update`, so a verifikator can decide on a bukti transfer without
     * being able to alter the nominal, kode unik, or batas waktu.
     */
    public function verify(AuthUser $authUser, RegistrationPayment $registrationPayment): bool
    {
        return $authUser->can('Verify:RegistrationPayment');
    }
}
