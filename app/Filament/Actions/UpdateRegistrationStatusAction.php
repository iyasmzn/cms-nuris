<?php

namespace App\Filament\Actions;

use App\Models\SpmbRegistration;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/**
 * Move a registration between statuses without touching the pendaftar's own
 * data. Gated on the `updateStatus` policy so a verifikator can run it while
 * being locked out of the edit form entirely.
 */
class UpdateRegistrationStatusAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'ubahStatus';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Ubah Status')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('primary')
            ->authorize('updateStatus')
            ->modalHeading('Ubah Status Pendaftaran')
            ->modalSubmitActionLabel('Simpan Status')
            ->fillForm(fn (SpmbRegistration $record): array => [
                'status' => $record->status,
                'notes' => $record->notes,
            ])
            ->schema([
                Select::make('status')
                    ->label('Status Pendaftaran')
                    ->options(SpmbRegistration::statusOptions())
                    ->required()
                    ->native(false),

                Textarea::make('notes')
                    ->label('Catatan Panitia')
                    ->rows(3)
                    ->helperText('Opsional. Misalnya alasan penolakan atau catatan hasil verifikasi berkas.'),
            ])
            ->action(function (array $data, SpmbRegistration $record): void {
                $record->forceFill([
                    'status' => $data['status'],
                    'notes' => $data['notes'] ?? null,
                    // Stamp the first time it leaves "menunggu"; keep the
                    // original timestamp on any later status change.
                    'verified_at' => $data['status'] === 'pending'
                        ? null
                        : ($record->verified_at ?? now()),
                ])->save();

                Notification::make()
                    ->success()
                    ->title('Status pendaftaran diperbarui')
                    ->body(SpmbRegistration::statusOptions()[$data['status']] ?? $data['status'])
                    ->send();
            });
    }
}
