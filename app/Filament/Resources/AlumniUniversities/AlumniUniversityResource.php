<?php

namespace App\Filament\Resources\AlumniUniversities;

use App\Filament\Resources\AlumniUniversities\Pages\CreateAlumniUniversity;
use App\Filament\Resources\AlumniUniversities\Pages\EditAlumniUniversity;
use App\Filament\Resources\AlumniUniversities\Pages\ListAlumniUniversities;
use App\Filament\Resources\AlumniUniversities\Schemas\AlumniUniversityForm;
use App\Filament\Resources\AlumniUniversities\Tables\AlumniUniversitiesTable;
use App\Models\AlumniUniversity;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class AlumniUniversityResource extends Resource
{
    protected static ?string $model = AlumniUniversity::class;

    protected static string|UnitEnum|null $navigationGroup = 'Profil Sekolah';

    protected static ?string $navigationLabel = 'Kampus Alumni';

    protected static ?string $modelLabel = 'Kampus Alumni';

    protected static ?string $pluralModelLabel = 'Kampus Alumni';

    protected static ?int $navigationSort = 8;

    public static function form(Schema $schema): Schema
    {
        return AlumniUniversityForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AlumniUniversitiesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAlumniUniversities::route('/'),
            'create' => CreateAlumniUniversity::route('/create'),
            'edit' => EditAlumniUniversity::route('/{record}/edit'),
        ];
    }
}
