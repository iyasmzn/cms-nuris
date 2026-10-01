<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Institutions\Pages\CreateInstitution;
use App\Filament\Resources\Institutions\Pages\EditInstitution;
use App\Filament\Resources\Institutions\Pages\ListInstitutions;
use App\Filament\Resources\Institutions\RelationManagers\PpdbFieldsRelationManager;
use App\Models\Institution;
use App\Models\Media;
use App\Models\PpdbField;
use App\Models\Setting;
use App\Models\User;
use App\Policies\InstitutionPolicy;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class InstitutionResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->grantInstitutionPermissions($user);
        $this->actingAs($user);
    }

    /**
     * Grant the Shield permissions the InstitutionResource pages require.
     */
    private function grantInstitutionPermissions(User $user): void
    {
        $permissions = collect(['ViewAny', 'View', 'Create', 'Update', 'Delete', 'DeleteAny', 'Reorder'])
            ->map(fn (string $action): Permission => Permission::findOrCreate("{$action}:Institution", 'web'));

        $user->givePermissionTo($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_it_saves_the_rekening_and_keterangan_of_one_jenjang(): void
    {
        Setting::set('spmb_payment_enabled', '1');

        $institution = Institution::factory()->create();

        Livewire::test(EditInstitution::class, ['record' => $institution->id])
            ->fillForm([
                'bank_accounts' => [
                    ['bank' => 'BRI', 'number' => '0099887766', 'holder' => 'SD IT Nurul Islam'],
                ],
                'payment_instructions' => 'Transfer ke rekening SD, lalu unggah buktinya.',
                'success_message' => 'Terima kasih {nama}, nomor Anda {nomor_pendaftaran}.',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $institution->refresh();

        $this->assertSame([
            ['bank' => 'BRI', 'number' => '0099887766', 'holder' => 'SD IT Nurul Islam'],
        ], $institution->resolvedBankAccounts());
        $this->assertSame('Transfer ke rekening SD, lalu unggah buktinya.', $institution->payment_instructions);
        $this->assertSame('Terima kasih {nama}, nomor Anda {nomor_pendaftaran}.', $institution->success_message);
    }

    public function test_it_saves_the_per_unit_ppdb_switches(): void
    {
        $institution = Institution::factory()->create();

        Livewire::test(EditInstitution::class, ['record' => $institution->id])
            ->fillForm([
                'form_enabled' => 0,
                'payment_enabled' => 1,
                'payment_unique_code' => 0,
                'payment_deadline_hours' => 6,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $institution->refresh();

        $this->assertFalse($institution->formEnabled());
        $this->assertTrue($institution->paymentEnabled());
        $this->assertFalse($institution->usesUniqueCode());
        $this->assertSame(6, $institution->paymentDeadlineHours());
    }

    public function test_it_saves_the_quota_and_auto_close_switch(): void
    {
        $institution = Institution::factory()->create();

        Livewire::test(EditInstitution::class, ['record' => $institution->id])
            ->fillForm([
                'quota' => 120,
                'close_when_full' => true,
                'quota_full_message' => 'Kursi SMP sudah penuh.',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $institution->refresh();

        $this->assertSame(120, $institution->quota);
        $this->assertTrue($institution->closesWhenFull());
        $this->assertSame('Kursi SMP sudah penuh.', $institution->resolvedQuotaFullMessage());
    }

    public function test_quota_must_be_a_positive_whole_number(): void
    {
        $institution = Institution::factory()->create();

        Livewire::test(EditInstitution::class, ['record' => $institution->id])
            ->fillForm(['quota' => 0])
            ->call('save')
            ->assertHasFormErrors(['quota' => 'min']);
    }

    public function test_clearing_a_switch_puts_the_jenjang_back_on_the_global_setting(): void
    {
        Setting::set('spmb_payment_enabled', '1');

        $institution = Institution::factory()->create(['payment_enabled' => false]);

        Livewire::test(EditInstitution::class, ['record' => $institution->id])
            ->fillForm(['payment_enabled' => null])
            ->call('save')
            ->assertHasNoFormErrors();

        $institution->refresh();

        $this->assertNull($institution->payment_enabled);
        $this->assertTrue($institution->paymentEnabled());
    }

    public function test_it_lists_institutions(): void
    {
        $institutions = Institution::factory()->count(3)->create();

        Livewire::test(ListInstitutions::class)
            ->assertCanSeeTableRecords($institutions);
    }

    public function test_it_creates_an_institution(): void
    {
        Livewire::test(CreateInstitution::class)
            ->fillForm([
                'name' => 'SMA',
                'slug' => 'sma',
                'short_name' => 'SMA',
                'color' => 'warning',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(Institution::class, [
            'name' => 'SMA',
            'slug' => 'sma',
            'short_name' => 'SMA',
            'is_active' => true,
        ]);
    }

    public function test_it_creates_an_external_link_jenjang(): void
    {
        Livewire::test(CreateInstitution::class)
            ->fillForm([
                'name' => 'SMK',
                'slug' => 'smk',
                'form_mode' => Institution::FORM_MODE_EXTERNAL_LINK,
                'external_url' => 'https://ppdb.smk.example',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(Institution::class, [
            'slug' => 'smk',
            'form_mode' => Institution::FORM_MODE_EXTERNAL_LINK,
            'external_url' => 'https://ppdb.smk.example',
        ]);
    }

    public function test_it_saves_per_jenjang_document_requirements(): void
    {
        $institution = Institution::factory()->create();

        Livewire::test(EditInstitution::class, ['record' => $institution->id])
            ->fillForm([
                'show_requirements' => true,
                'requirements' => [
                    ['requirement' => 'Sertifikat hafalan Al-Quran'],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['Sertifikat hafalan Al-Quran'], $institution->refresh()->requirements);
    }

    public function test_it_hides_document_requirements_for_a_jenjang(): void
    {
        $institution = Institution::factory()->create();

        Livewire::test(EditInstitution::class, ['record' => $institution->id])
            ->fillForm(['show_requirements' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertFalse($institution->refresh()->showsRequirements());
    }

    public function test_it_requires_a_name_and_slug(): void
    {
        Livewire::test(CreateInstitution::class)
            ->fillForm(['name' => null, 'slug' => null])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required', 'slug' => 'required']);
    }

    public function test_it_rejects_a_duplicate_slug(): void
    {
        Institution::factory()->create(['slug' => 'sd']);

        Livewire::test(CreateInstitution::class)
            ->fillForm(['name' => 'SD Lain', 'slug' => 'sd'])
            ->call('create')
            ->assertHasFormErrors(['slug']);
    }

    public function test_it_updates_an_institution(): void
    {
        $institution = Institution::factory()->create();

        Livewire::test(EditInstitution::class, ['record' => $institution->id])
            ->fillForm(['name' => 'Nama Jenjang Baru'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(Institution::class, [
            'id' => $institution->id,
            'name' => 'Nama Jenjang Baru',
        ]);
    }

    public function test_field_builder_is_only_available_for_internal_jenjang(): void
    {
        $internal = Institution::factory()->create();
        $external = Institution::factory()->externalLink()->create();

        $this->assertTrue(PpdbFieldsRelationManager::canViewForRecord($internal, EditInstitution::class));
        $this->assertFalse(PpdbFieldsRelationManager::canViewForRecord($external, EditInstitution::class));
    }

    /**
     * The nomor HP is half of the credential for the status page, so the field
     * builder must not offer any way to drop it.
     */
    public function test_field_builder_shields_the_locked_fields(): void
    {
        $institution = Institution::factory()->create();
        $custom = $institution->ppdbFields()->create([
            'key' => 'hobby', 'label' => 'Hobi', 'type' => 'text', 'is_required' => false, 'sort_order' => 1,
        ]);

        $manager = Livewire::test(PpdbFieldsRelationManager::class, [
            'ownerRecord' => $institution,
            'pageClass' => EditInstitution::class,
        ])->assertOk();

        foreach (PpdbField::lockedKeys() as $key) {
            $manager->assertTableActionHidden(
                DeleteAction::class,
                $institution->ppdbFields()->where('key', $key)->firstOrFail(),
            );
        }

        $manager->assertTableActionVisible(DeleteAction::class, $custom);
    }

    public function test_field_builder_is_not_blocked_by_a_phantom_permission(): void
    {
        // PpdbField has no dedicated policy (like the RegistrationWave relation
        // manager), so the field builder follows Institution edit access rather
        // than an ungenerated *:PpdbField permission that Shield never creates.
        $this->assertNull(Gate::getPolicyFor(PpdbField::class));
        $this->assertInstanceOf(InstitutionPolicy::class, Gate::getPolicyFor(Institution::class));
    }

    public function test_uploaded_icon_is_added_to_the_media_library(): void
    {
        Storage::fake('public');

        Livewire::test(CreateInstitution::class)
            ->fillForm([
                'name' => 'SMA',
                'slug' => 'sma',
                'short_name' => 'SMA',
                'color' => 'warning',
                'icon_image' => UploadedFile::fake()->createWithContent('sekolah.svg', '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/></svg>'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $path = Institution::query()->where('slug', 'sma')->value('icon_image');

        Storage::disk('public')->assertExists($path);
        $this->assertDatabaseHas(Media::class, ['path' => $path, 'name' => 'SMA']);
    }

    public function test_icon_can_be_picked_from_the_media_library(): void
    {
        $institution = Institution::factory()->create();
        $media = Media::factory()->create(['path' => 'media/sekolah.png', 'mime_type' => 'image/png']);

        Livewire::test(EditInstitution::class, ['record' => $institution->id])
            ->fillForm([
                'icon_image_source' => 'library',
                'icon_image_library' => $media->id,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('media/sekolah.png', $institution->fresh()->icon_image);
    }
}
