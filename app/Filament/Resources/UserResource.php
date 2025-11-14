<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationLabel = 'المستخدمين';

    protected static ?string $modelLabel = 'مستخدم';

    protected static ?string $pluralModelLabel = 'المستخدمين';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label('الاسم')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->label('البريد الإلكتروني')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('phone')
                    ->label('الهاتف')
                    ->tel()
                    ->maxLength(20),
                Forms\Components\Select::make('gender')
                    ->label('الجنس')
                    ->options([
                        'male' => 'ذكر',
                        'female' => 'أنثى',
                    ]),
                Forms\Components\Select::make('user_type')
                    ->label('نوع المستخدم')
                    ->options([
                        'patient' => 'مريض',
                        'hospital' => 'مستشفى',
                        'donner' => 'متبرع',
                        'super_admin' => 'مدير عام',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('blood')
                    ->label('فصيلة الدم')
                    ->maxLength(10),
                Forms\Components\TextInput::make('password')
                    ->label('كلمة المرور')
                    ->password()
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create')
                    ->maxLength(255),
                Forms\Components\TextInput::make('fcm_token')
                    ->label('FCM Token')
                    ->maxLength(65535),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('الهاتف')
                    ->searchable(),
                Tables\Columns\TextColumn::make('gender')
                    ->label('الجنس')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'male' ? 'ذكر' : 'أنثى'),
                Tables\Columns\TextColumn::make('user_type')
                    ->label('نوع المستخدم')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'super_admin' => 'danger',
                        'hospital' => 'success',
                        'patient' => 'info',
                        'donner' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'super_admin' => 'مدير عام',
                        'hospital' => 'مستشفى',
                        'patient' => 'مريض',
                        'donner' => 'متبرع',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('hospital.name')
                    ->label('المستشفى')
                    ->searchable()
                    ->sortable()
                    ->visible(fn ($record) => $record && $record->user_type === 'hospital')
                    ->placeholder('لا يوجد مستشفى'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('تاريخ التحديث')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_type')
                    ->label('نوع المستخدم')
                    ->options([
                        'patient' => 'مريض',
                        'hospital' => 'مستشفى',
                        'donner' => 'متبرع',
                        'super_admin' => 'مدير عام',
                    ]),
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
            'edit' => Pages\EditUser::route('/{record}/edit'),
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
