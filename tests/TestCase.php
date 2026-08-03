<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    /**
     * Every Filament resource in this app is guarded by a Shield policy, so a
     * bare `User::factory()->create()` is refused by the panel. Tests that
     * exercise a resource's behaviour (rather than its authorisation) need a
     * user holding that resource's permissions.
     *
     * In production these are seeded and synced onto the `super_admin` role;
     * `RefreshDatabase` wipes them, so tests must grant them explicitly.
     *
     * @param  string  ...$models  Model basenames, e.g. `'Greeting'`, `'Comment'`.
     */
    protected function panelUser(string ...$models): User
    {
        $user = User::factory()->create();

        $user->givePermissionTo($this->shieldPermissions(...$models));

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    /**
     * Abilities beyond Shield's generated CRUD set, per model. Without these a
     * `panelUser()` would be refused by the custom-permission gates even though
     * it is meant to stand in for a full administrator.
     *
     * @var array<string, list<string>>
     */
    private const CUSTOM_ABILITIES = [
        'Post' => ['Publish', 'ViewAll'],
        'SpmbRegistration' => ['UpdateStatus'],
        'RegistrationPayment' => ['Verify'],
    ];

    /**
     * The full set of Shield permissions for the given models.
     *
     * @return array<int, Permission>
     */
    protected function shieldPermissions(string ...$models): array
    {
        $actions = [
            'ViewAny', 'View', 'Create', 'Update', 'Delete', 'DeleteAny',
            'Restore', 'RestoreAny', 'ForceDelete', 'ForceDeleteAny',
            'Replicate', 'Reorder',
        ];

        return collect($models)
            ->flatMap(fn (string $model): array => collect($actions)
                ->merge(self::CUSTOM_ABILITIES[$model] ?? [])
                ->map(fn (string $action): Permission => Permission::findOrCreate("{$action}:{$model}", 'web'))
                ->all())
            ->all();
    }
}
