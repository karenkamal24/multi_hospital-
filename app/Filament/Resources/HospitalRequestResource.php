<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HospitalRequestResource\Pages;
use App\Models\Hospital;
use App\Models\HospitalRequest;
use App\Services\NotificationService;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class HospitalRequestResource extends Resource
{
    protected static ?string $model = HospitalRequest::class;

    protected static ?string $navigationLabel = 'طلبات المستشفيات';

    protected static ?string $modelLabel = 'طلب مستشفى';

    protected static ?string $pluralModelLabel = 'طلبات المستشفيات';

    protected static ?int $navigationSort = 50;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('hospital_id')
                    ->label('المستشفى')
                    ->relationship('hospital', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabled(fn ($record) => $record !== null)
                    ->visible(fn () => Auth::check() && Auth::user()?->user_type === 'super_admin'),

                Forms\Components\Select::make('user_id')
                    ->label('المستخدم')
                    ->relationship('user', 'name', modifyQueryUsing: function ($query) {
                        return $query->whereIn('user_type', ['patient', 'donner']);
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabled(fn ($record) => $record !== null)
                    ->visible(fn () => Auth::check() && Auth::user()?->user_type === 'super_admin'),

                Forms\Components\Select::make('request_type')
                    ->label('نوع الطلب')
                    ->options([
                        'patient' => 'مريض',
                        'donner' => 'متبرع',
                    ])
                    ->required()
                    ->disabled(fn ($record) => $record !== null),

                Forms\Components\Select::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending' => 'في الانتظار',
                        'approved' => 'موافق عليه',
                        'rejected' => 'مرفوض',
                    ])
                    ->required()
                    ->default('pending')
                    ->disabled(fn ($record) => $record !== null && $record->status !== 'pending'),

                Forms\Components\Textarea::make('user_notes')
                    ->label('ملاحظات المستخدم')
                    ->rows(3)
                    ->maxLength(1000)
                    ->disabled(fn ($record) => $record !== null),

                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات المستشفى')
                    ->rows(3)
                    ->maxLength(1000)
                    ->helperText('يمكنك إضافة ملاحظات عند الموافقة أو الرفض'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('hospital.name')
                    ->label('المستشفى')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->visible(fn () => Auth::check() && Auth::user()?->user_type === 'super_admin'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('المستخدم')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('user.phone')
                    ->label('الهاتف')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('user.blood')
                    ->label('فصيلة الدم')
                    ->badge()
                    ->color('danger')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('request_type')
                    ->label('نوع الطلب')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'patient' => 'warning',
                        'donner' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'patient' => 'مريض',
                        'donner' => 'متبرع',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'في الانتظار',
                        'approved' => 'موافق عليه',
                        'rejected' => 'مرفوض',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('user_notes')
                    ->label('ملاحظات المستخدم')
                    ->limit(50)
                    ->wrap()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('notes')
                    ->label('ملاحظات المستشفى')
                    ->limit(50)
                    ->wrap()
                    ->toggleable(),

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
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending' => 'في الانتظار',
                        'approved' => 'موافق عليه',
                        'rejected' => 'مرفوض',
                    ]),

                Tables\Filters\SelectFilter::make('request_type')
                    ->label('نوع الطلب')
                    ->options([
                        'patient' => 'مريض',
                        'donner' => 'متبرع',
                    ]),

                Tables\Filters\Filter::make('pending_only')
                    ->label('فقط المعلقة')
                    ->query(fn (Builder $query): Builder => $query->where('status', 'pending'))
                    ->toggle(),
            ])
            ->actions([
                Actions\Action::make('approve')
                    ->label('موافقة')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->rows(3)
                            ->maxLength(1000)
                            ->helperText('يمكنك إضافة ملاحظات عند الموافقة'),
                    ])
                    ->action(function (HospitalRequest $record, array $data) {
                        $record->update([
                            'status' => 'approved',
                            'notes' => $data['notes'] ?? null,
                        ]);

                        // إرسال إشعار للمستخدم
                        $requestUser = $record->user;
                        $hospital = $record->hospital;

                        if ($requestUser && $requestUser->fcm_token) {
                            $notificationService = app(NotificationService::class);
                            $title = "تحديث حالة الطلب";
                            $body = "تم الموافقة على طلبك للمستشفى {$hospital->name}";

                            $notificationService->sendToToken(
                                $requestUser->fcm_token,
                                $title,
                                $body,
                                [
                                    'type' => 'hospital_request_update',
                                    'request_id' => (string) $record->id,
                                    'status' => 'approved',
                                    'hospital_id' => (string) $hospital->id,
                                    'hospital_name' => $hospital->name,
                                ]
                            );
                        }
                    })
                    ->visible(fn (HospitalRequest $record) => $record->status === 'pending'),

                Actions\Action::make('reject')
                    ->label('رفض')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->rows(3)
                            ->maxLength(1000)
                            ->required()
                            ->helperText('يجب إضافة ملاحظات عند الرفض'),
                    ])
                    ->action(function (HospitalRequest $record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'notes' => $data['notes'] ?? null,
                        ]);

                        // إرسال إشعار للمستخدم
                        $requestUser = $record->user;
                        $hospital = $record->hospital;

                        if ($requestUser && $requestUser->fcm_token) {
                            $notificationService = app(NotificationService::class);
                            $title = "تحديث حالة الطلب";
                            $body = "تم رفض طلبك للمستشفى {$hospital->name}";

                            $notificationService->sendToToken(
                                $requestUser->fcm_token,
                                $title,
                                $body,
                                [
                                    'type' => 'hospital_request_update',
                                    'request_id' => (string) $record->id,
                                    'status' => 'rejected',
                                    'hospital_id' => (string) $hospital->id,
                                    'hospital_name' => $hospital->name,
                                ]
                            );
                        }
                    })
                    ->visible(fn (HospitalRequest $record) => $record->status === 'pending'),

                Actions\ViewAction::make(),
                Actions\EditAction::make(),
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
            'index' => Pages\ListHospitalRequests::route('/'),
            'create' => Pages\CreateHospitalRequest::route('/create'),
            'view' => Pages\ViewHospitalRequest::route('/{record}'),
            'edit' => Pages\EditHospitalRequest::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // إذا كان المستخدم من نوع hospital، يعرض فقط طلبات مستشفاه
        if (Auth::check() && Auth::user()?->user_type === 'hospital') {
            $userId = Auth::id();
            // استخدام whereHas بدلاً من join لتجنب مشكلة ambiguous id
            return $query->whereHas('hospital', function ($q) use ($userId) {
                $q->where('hospitals.user_id', $userId);
            });
        }

        // super_admin يمكنه رؤية جميع الطلبات
        return $query;
    }

    public static function canViewAny(): bool
    {
        return Auth::check() && in_array(Auth::user()?->user_type, ['super_admin', 'hospital']);
    }

    public static function canEdit($record): bool
    {
        // يمكن التعديل فقط إذا كان pending
        if ($record && $record->status !== 'pending') {
            return false;
        }

        if (Auth::check() && Auth::user()?->user_type === 'hospital') {
            $hospital = Auth::user()->hospital;
            return $hospital && $record->hospital_id === $hospital->id;
        }

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

