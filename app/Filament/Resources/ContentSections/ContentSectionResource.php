<?php

namespace App\Filament\Resources\ContentSections;

use App\Filament\Resources\ContentSections\Pages\CreateContentSection;
use App\Filament\Resources\ContentSections\Pages\EditContentSection;
use App\Filament\Resources\ContentSections\Pages\ListContentSections;
use App\Filament\Resources\ContentSections\Schemas\ContentSectionForm;
use App\Filament\Resources\ContentSections\Tables\ContentSectionsTable;
use App\Models\ContentSection;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ContentSectionResource extends Resource
{
    protected static ?string $model = ContentSection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Konten';

    protected static ?string $navigationLabel = 'Seksi Halaman Depan';

    protected static ?string $modelLabel = 'Seksi Halaman Depan';

    protected static ?string $pluralModelLabel = 'Seksi Halaman Depan';

    protected static ?int $navigationSort = 6;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return ContentSectionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContentSectionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContentSections::route('/'),
            'create' => CreateContentSection::route('/create'),
            'edit' => EditContentSection::route('/{record}/edit'),
        ];
    }
}
