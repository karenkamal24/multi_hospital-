<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettingResource\Pages;
use App\Models\Setting;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;

    protected static ?string $navigationLabel = 'الإعدادات';

    protected static ?string $modelLabel = 'إعداد';

    protected static ?string $pluralModelLabel = 'الإعدادات';


    protected static ?int $navigationSort = 100;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('key')
                    ->label('المفتاح')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->disabled(fn ($record) => $record !== null)
                    ->helperText('المفتاح لا يمكن تعديله بعد الإنشاء')
                    ->live(),

                Forms\Components\TextInput::make('value')
                    ->label('القيمة')
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, $set, $get) {
                        // Auto-format for sos_radius_km
                        if ($get('key') === 'sos_radius_km' && !is_numeric($state)) {
                            $set('value', '');
                        }
                    })
                    ->reactive()
                    ->numeric(fn ($get, $record) => ($get('key') ?? $record?->key) === 'sos_radius_km')
                    ->suffix(fn ($get, $record) => ($get('key') ?? $record?->key) === 'sos_radius_km' ? 'كم' : null)
                    ->helperText(fn ($get, $record) => ($get('key') ?? $record?->key) === 'sos_radius_km' ? 'المسافة بالكيلومتر للبحث عن المتبرعين القريبين' : '')
                    ->minValue(fn ($get, $record) => ($get('key') ?? $record?->key) === 'sos_radius_km' ? 1 : null)
                    ->maxValue(fn ($get, $record) => ($get('key') ?? $record?->key) === 'sos_radius_km' ? 1000 : null),

                Forms\Components\Textarea::make('description')
                    ->label('الوصف')
                    ->rows(2)
                    ->maxLength(500),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->label('المفتاح')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('value')
                    ->label('القيمة')
                    ->searchable()
                    ->limit(50)
                    ->wrap(),

                Tables\Columns\TextColumn::make('description')
                    ->label('الوصف')
                    ->limit(50)
                    ->wrap()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('آخر تحديث')
                    ->dateTime('d/m/Y h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->defaultSort('key');
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
            'index' => Pages\ListSettings::route('/'),
            'create' => Pages\CreateSetting::route('/create'),
            'edit' => Pages\EditSetting::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return Auth::check() && Auth::user()?->user_type === 'super_admin';
    }

    public static function canEdit($record): bool
    {
        return Auth::check() && Auth::user()?->user_type === 'super_admin';
    }

    public static function canDelete($record): bool
    {
        return Auth::check() && Auth::user()?->user_type === 'super_admin';
    }

    public static function canCreate(): bool
    {
        return Auth::check() && Auth::user()?->user_type === 'super_admin';
    }
}

