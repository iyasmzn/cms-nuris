<?php

namespace App\Filament\Resources\AlumniStats;

use App\Filament\Resources\AlumniStats\Pages\CreateAlumniStat;
use App\Filament\Resources\AlumniStats\Pages\EditAlumniStat;
use App\Filament\Resources\AlumniStats\Pages\ListAlumniStats;
use App\Filament\Resources\AlumniStats\Schemas\AlumniStatForm;
use App\Filament\Resources\AlumniStats\Tables\AlumniStatsTable;
use App\Models\AlumniStat;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class AlumniStatResource extends Resource
{
    protected static ?string $model = AlumniStat::class;

    protected static string|UnitEnum|null $navigationGroup = 'Profil Sekolah';

    protected static ?string $navigationLabel = 'Statistik Alumni';

    protected static ?string $modelLabel = 'Statistik Alumni';

    protected static ?string $pluralModelLabel = 'Statistik Alumni';

    protected static ?int $navigationSort = 7;

    public static function form(Schema $schema): Schema
    {
        return AlumniStatForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AlumniStatsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAlumniStats::route('/'),
            'create' => CreateAlumniStat::route('/create'),
            'edit' => EditAlumniStat::route('/{record}/edit'),
        ];
    }
}
