<?php

namespace App\Filament\Resources\RegistrationPayments;

use App\Filament\Resources\RegistrationPayments\Pages\EditRegistrationPayment;
use App\Filament\Resources\RegistrationPayments\Pages\ListRegistrationPayments;
use App\Filament\Resources\RegistrationPayments\Pages\ViewRegistrationPayment;
use App\Filament\Resources\RegistrationPayments\Schemas\RegistrationPaymentForm;
use App\Filament\Resources\RegistrationPayments\Schemas\RegistrationPaymentInfolist;
use App\Filament\Resources\RegistrationPayments\Tables\RegistrationPaymentsTable;
use App\Models\RegistrationPayment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class RegistrationPaymentResource extends Resource
{
    protected static ?string $model = RegistrationPayment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|UnitEnum|null $navigationGroup = 'PPDB / SPMB';

    protected static ?string $navigationLabel = 'Pembayaran';

    protected static ?string $modelLabel = 'Pembayaran';

    protected static ?string $pluralModelLabel = 'Pembayaran Pendaftaran';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return RegistrationPaymentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RegistrationPaymentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RegistrationPaymentsTable::configure($table);
    }

    /**
     * Tagihan are issued by the public registration form, never by hand.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRegistrationPayments::route('/'),
            'view' => ViewRegistrationPayment::route('/{record}'),
            'edit' => EditRegistrationPayment::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::query()->waitingVerification()->count() ?: null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }
}
