<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class FeatureSettings extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.feature-settings';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Fitur Website';

    protected static ?string $title = 'Fitur Website';

    protected static ?int $navigationSort = 2;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function getNavigationBadge(): ?string
    {
        $disabled = collect(['login_register'])
            ->reject(fn (string $feature): bool => feature_enabled($feature))
            ->count();

        return $disabled > 0 ? (string) $disabled.' nonaktif' : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'gray';
    }

    public function mount(): void
    {
        $this->form->fill([
            'feature_login_register' => setting_bool('feature_login_register', true),

            // Komentar
            'comment_user_auto_publish' => setting_bool('comment_user_auto_publish', true),
            'comment_guest_auto_publish' => setting_bool('comment_guest_auto_publish', true),
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Autentikasi Pengguna')
                ->description('Aktifkan atau nonaktifkan login dan registrasi pengguna umum.')
                ->icon(Heroicon::OutlinedKey)
                ->schema([
                    Toggle::make('feature_login_register')
                        ->label('Login & Registrasi Pengguna')
                        ->default(true)
                        ->helperText('Nonaktifkan agar pengunjung tidak bisa masuk atau mendaftar akun.'),
                ]),

            Section::make('Komentar Artikel')
                ->description('Atur apakah komentar baru langsung tampil atau menunggu persetujuan admin.')
                ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                ->schema([
                    Toggle::make('comment_user_auto_publish')
                        ->label('Komentar pengguna terdaftar langsung tampil')
                        ->default(true)
                        ->helperText('Nonaktifkan agar komentar dari pengguna yang login harus disetujui admin dulu.'),

                    Toggle::make('comment_guest_auto_publish')
                        ->label('Komentar tamu (tanpa login) langsung tampil')
                        ->default(true)
                        ->helperText('Nonaktifkan agar komentar dari tamu masuk sebagai menunggu persetujuan admin.'),
                ]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::setMany($data);

        Notification::make()
            ->success()
            ->title('Pengaturan fitur berhasil disimpan')
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan Pengaturan')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->action('save'),
        ];
    }
}
