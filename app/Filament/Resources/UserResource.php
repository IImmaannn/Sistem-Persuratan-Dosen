<?php

namespace App\Filament\Resources;

use App\Models\User;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Select;
use App\Filament\Exports\UserExporter;
use App\Filament\Imports\UserImporter;
use Filament\Forms\Components\Section;
use Filament\Support\Enums\FontWeight;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Actions\ImportAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Actions\ExportBulkAction;
use App\Filament\Resources\UserResource\Pages;
use STS\FilamentImpersonate\Tables\Actions\Impersonate;
use Filament\Infolists\Components\Section as InfolistSection;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationLabel = 'Home';
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('User Information')->schema([
                    TextInput::make('name')->required(),
                    TextInput::make('email')->required()->email(),
                    TextInput::make('password')
                        ->password()
                        ->required(fn ($context) => $context === 'create')
                        ->dehydrated(fn ($state) => filled($state)),
                    
                    // TAMBAHAN: Input Gender
                    Select::make('gender')
                        ->label('Gender')
                        ->options([
                            'Laki-laki' => 'Laki-laki',
                            'Perempuan' => 'Perempuan',
                        ])
                        ->relationship('profile', 'gender') // Simpan ke tabel profile
                        ->required(),
                ])->columns(2),
            ]);
}

    public static function canCreate(): bool
    {
        return true;
    }

    public static function table(Table $table): Table
    {
       return $table
            ->columns([
                // Kolom Nama (Munculin bar search otomatis)
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable() // Agar bar Search di wireframe lo aktif
                    ->sortable(),

                // Kolom Gender (Ambil dari relasi profile)
                Tables\Columns\TextColumn::make('profile.gender')
                    ->label('Gender')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Laki-laki' => 'info',
                        'Perempuan' => 'danger',
                        default => 'gray',
                    }),

                // Kolom Email
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->copyable(),

                // Kolom Role (Pembeda antar aktor)
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->color('success'),
            ]) 
            ->filters([
                //
                SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Action::make('Set Role')
                    ->icon('heroicon-m-adjustments-vertical')
                    ->form([
                        Select::make('role')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->required()
                            ->searchable()
                            ->preload()
                            ->optionsLimit(10)
                            ->getOptionLabelFromRecordUsing(fn($record) => $record->name),
                    ]),
                // Impersonate::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(UserExporter::class),
                ImportAction::make()
                    ->importer(UserImporter::class)
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
                ExportBulkAction::make()
                    ->exporter(UserExporter::class)
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistSection::make('User Information')->schema([
                    TextEntry::make('name'),
                    TextEntry::make('email'),
                ]),
            ]);
    }
}
