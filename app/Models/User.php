<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'google_id', 'avatar'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Roles that grant access to the admin panel.
     *
     * @var list<string>
     */
    public const PANEL_ROLES = ['super_admin', 'panel_user', 'author', 'author_super', 'verifikator_ppdb'];

    /**
     * Permission yang membebaskan pemiliknya dari pembatasan per unit: yang
     * memegangnya melihat data seluruh jenjang, bukan hanya jenjang yang
     * ditugaskan kepadanya. `super_admin` memilikinya lewat gate Shield.
     */
    public const SEES_EVERY_INSTITUTION = 'ViewAll:SpmbRegistration';

    /**
     * Jenjang yang boleh dilihat user ini, dihitung sekali per permission
     * karena setiap widget dasbor menanyakannya lagi dalam request yang sama.
     *
     * @var array<string, array<int, int>|null>
     */
    private array $visibleInstitutionIds = [];

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole(self::PANEL_ROLES);
    }

    /**
     * Jenjang yang ditugaskan ke akun ini. Kosong berarti akun tersebut tidak
     * melihat satu pun data pendaftar — kecuali ia memegang permission
     * "lihat semua unit".
     *
     * @return BelongsToMany<Institution, $this>
     */
    public function institutions(): BelongsToMany
    {
        return $this->belongsToMany(Institution::class)->withTimestamps();
    }

    /**
     * Id jenjang yang datanya boleh dilihat user ini, atau `null` bila ia
     * boleh melihat semuanya. Array kosong berarti belum ada unit yang
     * ditugaskan, jadi tidak ada data yang tampil.
     *
     * @return array<int, int>|null
     */
    public function visibleInstitutionIds(string $permission = self::SEES_EVERY_INSTITUTION): ?array
    {
        if (array_key_exists($permission, $this->visibleInstitutionIds)) {
            return $this->visibleInstitutionIds[$permission];
        }

        return $this->visibleInstitutionIds[$permission] = $this->can($permission)
            ? null
            : $this->institutions()->pluck('institutions.id')->map(intval(...))->all();
    }

    /**
     * Whether this user may see data belonging to one jenjang.
     */
    public function seesInstitution(Institution|int|null $institution, string $permission = self::SEES_EVERY_INSTITUTION): bool
    {
        $visible = $this->visibleInstitutionIds($permission);

        if ($visible === null) {
            return true;
        }

        $id = $institution instanceof Institution ? $institution->getKey() : $institution;

        return $id !== null && in_array((int) $id, $visible, true);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * URL avatar — file di storage jika di-upload, URL penuh dari Google,
     * atau fallback ke avatar yang dibuat dari inisial nama.
     */
    public function getAvatarUrlAttribute(): string
    {
        if (blank($this->avatar)) {
            return 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&background=08484A&color=fff&size=200&bold=true';
        }

        if (str_starts_with($this->avatar, 'http://') || str_starts_with($this->avatar, 'https://')) {
            return $this->avatar;
        }

        return asset('storage/'.$this->avatar);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function isAuthor(): bool
    {
        return $this->hasRole('author');
    }
}
