<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ShieldSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Flush cache sebelum generate agar state bersih
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 2. Generate semua permissions (resources, pages, widgets)
        Artisan::call('shield:generate', [
            '--all' => true,
            '--panel' => 'admin',
            '--ignore-existing-policies' => true,
            '--no-interaction' => true,
        ]);

        // 2b. Permission kustom di luar CRUD baku Shield. Keduanya memisahkan
        //     "boleh memutuskan status" dari "boleh mengubah data", sehingga
        //     panitia verifikasi tidak perlu diberi akses ubah data pendaftar.
        foreach (self::customPermissions() as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // 3. Flush cache lagi agar Permission::all() baca dari DB bukan cache lama
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 4. Buat role super_admin dan beri semua permissions
        $superAdmin = Role::firstOrCreate(
            ['name' => 'super_admin', 'guard_name' => 'web']
        );

        $superAdmin->syncPermissions(Permission::all());

        // 5. Buat role panel_user — akses panel dasar tanpa permission khusus.
        //    Dashboard (kartu sambutan) terbuka untuk semua user panel.
        Role::firstOrCreate(
            ['name' => 'panel_user', 'guard_name' => 'web']
        );

        // 5b. Role penulis blog.
        //
        // author       : hanya kelola artikel miliknya sendiri, tanpa publikasi.
        //                Draft-nya harus dipublikasikan oleh admin/author_super.
        // author_super : editor blog — bisa melihat, mengubah, menghapus, dan
        //                mempublikasikan artikel semua penulis.
        $authorPermissions = [
            'ViewAny:Post', 'View:Post', 'Create:Post', 'Update:Post',
            'Replicate:Post', 'Reorder:Post',
        ];

        $author = Role::firstOrCreate(['name' => 'author', 'guard_name' => 'web']);
        $author->syncPermissions(Permission::whereIn('name', $authorPermissions)->get());

        $superAuthorPermissions = array_merge($authorPermissions, [
            'Delete:Post', 'DeleteAny:Post', 'Publish:Post', 'ViewAll:Post',
        ]);

        $superAuthor = Role::firstOrCreate(['name' => 'author_super', 'guard_name' => 'web']);
        $superAuthor->syncPermissions(Permission::whereIn('name', $superAuthorPermissions)->get());

        // 5c. Role panitia verifikasi PPDB.
        //
        // Hanya boleh MEMBACA data pendaftar & tagihan, lalu memutuskan status
        // (verifikasi/terima/tolak pendaftaran, dan lunas/tolak bukti bayar).
        // Sengaja tanpa Update/Create/Delete agar data pendaftar dan nominal
        // tagihan tidak bisa berubah — sengaja maupun tidak.
        $verifier = Role::firstOrCreate(['name' => 'verifikator_ppdb', 'guard_name' => 'web']);
        $verifier->syncPermissions(Permission::whereIn('name', self::verifierPermissions())->get());

        // 6. Assign super_admin ke user admin (fallback ke user pertama jika email tidak ditemukan)
        $admin = User::where('email', 'admin@email.com')->first()
            ?? User::first();

        $admin?->assignRole('super_admin');
    }

    /**
     * Permission di luar CRUD baku yang tidak dihasilkan `shield:generate`.
     *
     * @return list<string>
     */
    public static function customPermissions(): array
    {
        return [
            'UpdateStatus:SpmbRegistration',
            'Verify:RegistrationPayment',
        ];
    }

    /**
     * Permission role `verifikator_ppdb`: baca + putuskan status, tanpa ubah data.
     *
     * @return list<string>
     */
    public static function verifierPermissions(): array
    {
        return [
            'ViewAny:SpmbRegistration', 'View:SpmbRegistration', 'UpdateStatus:SpmbRegistration',
            'ViewAny:RegistrationPayment', 'View:RegistrationPayment', 'Verify:RegistrationPayment',
            'View:Dashboard',
        ];
    }
}
