<?php

namespace App\Filament\Resources\RegistrationPayments\Pages;

use App\Filament\Resources\RegistrationPayments\RegistrationPaymentResource;
use App\Models\RegistrationPayment;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListRegistrationPayments extends ListRecords
{
    protected static string $resource = RegistrationPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * Antrian verifikasi comes first: panitia spend most of their time on bukti
     * that still need a decision.
     */
    public function getTabs(): array
    {
        return [
            'menunggu' => Tab::make('Menunggu Verifikasi')
                ->modifyQueryUsing(fn (Builder $query) => $query->waitingVerification())
                ->badge(RegistrationPayment::query()->waitingVerification()->count() ?: null)
                ->badgeColor('warning'),

            'belum_bayar' => Tab::make('Belum Dibayar')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', RegistrationPayment::STATUS_UNPAID)),

            'lunas' => Tab::make('Lunas')
                ->modifyQueryUsing(fn (Builder $query) => $query->paid()),

            'semua' => Tab::make('Semua'),
        ];
    }
}
