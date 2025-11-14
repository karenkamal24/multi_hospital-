<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HospitalResource\Pages;
use App\Models\Hospital;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class HospitalResource extends Resource
{
    protected static ?string $model = Hospital::class;

    protected static ?string $navigationLabel = 'المستشفيات';

    protected static ?string $modelLabel = 'مستشفى';

    protected static ?string $pluralModelLabel = 'المستشفيات';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('user_id')
                    ->label('المستخدم')
                    ->relationship('user', 'name', modifyQueryUsing: function ($query) {
                        return $query->where('user_type', 'hospital')
                            ->whereDoesntHave('hospital');
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->visible(fn () => Auth::check() && Auth::user()?->user_type === 'super_admin')
                    ->disabled(fn () => Auth::check() && Auth::user()?->user_type === 'hospital')
                    ->helperText('اختر مستخدم من نوع hospital ليس لديه مستشفى بعد')
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('name')
                    ->label('اسم المستشفى')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('مثال: مستشفى النور التخصصي')
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('address')
                    ->label('العنوان التفصيلي')
                    ->rows(3)
                    ->placeholder('مثال: شارع الهرم، الجيزة، مصر')
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('latitude')
                    ->label('خط العرض (Latitude)')
                    ->numeric()
                    ->step(0.000001)
                    ->minValue(-90)
                    ->maxValue(90)
                    ->placeholder('30.0444')
                    ->helperText('القيمة بين -90 و 90 • يمكنك الحصول عليها من Google Maps')
                    ->suffix('°')
                    ->live()
                    ->afterStateUpdated(function ($state, $set, $get) {
                        if ($state && $get('longitude')) {
                            $set('location', "Lat: {$state}, Long: {$get('longitude')}");
                        }
                    }),

                Forms\Components\TextInput::make('longitude')
                    ->label('خط الطول (Longitude)')
                    ->numeric()
                    ->step(0.000001)
                    ->minValue(-180)
                    ->maxValue(180)
                    ->placeholder('31.2357')
                    ->helperText('القيمة بين -180 و 180 • يمكنك الحصول عليها من Google Maps')
                    ->suffix('°')
                    ->live()
                    ->afterStateUpdated(function ($state, $set, $get) {
                        if ($state && $get('latitude')) {
                            $set('location', "Lat: {$get('latitude')}, Long: {$state}");
                        }
                    }),

                Forms\Components\TextInput::make('location')
                    ->label('الموقع (نص)')
                    ->maxLength(255)
                    ->placeholder('سيتم ملؤه تلقائياً من الإحداثيات أو أدخل موقع نصي')
                    ->helperText('يمكنك إدخال موقع نصي بدلاً من الإحداثيات (مثال: القاهرة، مصر)')
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('المستخدم')
                    ->searchable()
                    ->sortable()
                    ->visible(fn () => Auth::check() && Auth::user()?->user_type === 'super_admin'),

                Tables\Columns\TextColumn::make('name')
                    ->label('اسم المستشفى')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('address')
                    ->label('العنوان')
                    ->limit(50)
                    ->wrap(),

                Tables\Columns\TextColumn::make('coordinates')
                    ->label('الإحداثيات')
                    ->state(function ($record) {
                        if ($record->latitude && $record->longitude) {
                            return number_format($record->latitude, 6) . ', ' . number_format($record->longitude, 6);
                        }
                        return '-';
                    })
                    ->copyable()
                    ->copyMessage('تم نسخ الإحداثيات!')
                    ->color('success')
                    ->badge()
                    ->url(function ($record) {
                        if ($record->latitude && $record->longitude) {
                            return "https://www.google.com/maps?q={$record->latitude},{$record->longitude}";
                        }
                        return null;
                    }, shouldOpenInNewTab: true),

                Tables\Columns\TextColumn::make('location')
                    ->label('الموقع النصي')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('غير محدد'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d/m/Y h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('تاريخ التحديث')
                    ->dateTime('d/m/Y h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('has_coordinates')
                    ->label('يحتوي على إحداثيات')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('latitude')->whereNotNull('longitude')),
                Tables\Filters\Filter::make('no_coordinates')
                    ->label('بدون إحداثيات')
                    ->query(fn (Builder $query): Builder => $query->whereNull('latitude')->orWhereNull('longitude')),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListHospitals::route('/'),
            'create' => Pages\CreateHospital::route('/create'),
            'edit' => Pages\EditHospital::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // إذا كان المستخدم من نوع hospital، يعرض فقط مستشفاه
        if (Auth::check() && Auth::user()?->user_type === 'hospital') {
            return $query->where('user_id', Auth::id());
        }

        // super_admin يمكنه رؤية جميع المستشفيات
        return $query;
    }

    public static function canViewAny(): bool
    {
        return Auth::check() && in_array(Auth::user()?->user_type, ['super_admin', 'hospital']);
    }

    public static function canEdit($record): bool
    {
        return true;
    }

    public static function canDelete($record): bool
    {
        return Auth::check() && Auth::user()?->user_type === 'super_admin';
    }
}
