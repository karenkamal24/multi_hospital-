<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SosRequestResource\Pages;
use App\Models\SosRequest;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class SosRequestResource extends Resource
{
    protected static ?string $model = SosRequest::class;

    protected static ?string $navigationLabel = 'طلبات SOS';

    protected static ?string $modelLabel = 'طلب SOS';

    protected static ?string $pluralModelLabel = 'طلبات SOS';

    protected static ?int $navigationSort = 40;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('user_id')
                    ->label('المريض')
                    ->relationship('user', 'name', modifyQueryUsing: function ($query) {
                        return $query->where('user_type', 'patient');
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabled(fn ($record) => $record !== null)
                    ->visible(fn () => Auth::check() && Auth::user()?->user_type === 'super_admin'),

                Forms\Components\Select::make('accepted_donor_id')
                    ->label('المتبرع المقبول')
                    ->relationship('acceptedDonor', 'name', modifyQueryUsing: function ($query) {
                        return $query->where('user_type', 'donner');
                    })
                    ->searchable()
                    ->preload()
                    ->disabled(fn ($record) => $record !== null)
                    ->visible(fn () => Auth::check() && Auth::user()?->user_type === 'super_admin'),

                Forms\Components\Select::make('hospital_id')
                    ->label('المستشفى')
                    ->relationship('hospital', 'name')
                    ->searchable()
                    ->preload()
                    ->disabled(fn ($record) => $record !== null)
                    ->visible(fn () => Auth::check() && Auth::user()?->user_type === 'super_admin'),

                Forms\Components\Select::make('type')
                    ->label('نوع الطلب')
                    ->options([
                        'blood' => 'دم',
                        'organ' => 'عضو',
                    ])
                    ->required()
                    ->disabled(fn ($record) => $record !== null),

                Forms\Components\Select::make('blood')
                    ->label('فصيلة الدم')
                    ->options([
                        'O-' => 'O-',
                        'O+' => 'O+',
                        'A-' => 'A-',
                        'A+' => 'A+',
                        'B-' => 'B-',
                        'B+' => 'B+',
                        'AB-' => 'AB-',
                        'AB+' => 'AB+',
                    ])
                    ->required()
                    ->disabled(fn ($record) => $record !== null),

                Forms\Components\TextInput::make('latitude')
                    ->label('خط العرض')
                    ->numeric()
                    ->disabled(fn ($record) => $record !== null),

                Forms\Components\TextInput::make('longitude')
                    ->label('خط الطول')
                    ->numeric()
                    ->disabled(fn ($record) => $record !== null),

                Forms\Components\Textarea::make('description')
                    ->label('الوصف')
                    ->rows(3)
                    ->maxLength(1000)
                    ->disabled(fn ($record) => $record !== null),

                Forms\Components\Select::make('status')
                    ->label('الحالة')
                    ->options([
                        'active' => 'نشط',
                        'pending' => 'في الانتظار',
                        'completed' => 'مكتمل',
                        'cancelled' => 'ملغي',
                    ])
                    ->required()
                    ->default('active'),

                Forms\Components\Select::make('operation_status')
                    ->label('حالة العملية')
                    ->options([
                        'pending' => 'في الانتظار',
                        'completed' => 'مكتمل',
                        'cancelled' => 'ملغي',
                    ])
                    ->visible(fn ($record) => $record && $record->status === 'pending'),

                // معلومات المريض (قابلة للطي)
                Forms\Components\Placeholder::make('patient_info')
                    ->label('معلومات المريض')
                    ->content(function ($record) {
                        if (!$record || !$record->user) {
                            return 'لا توجد معلومات';
                        }
                        $user = $record->user;
                        $imageUrl = null;
                        if ($user->image) {
                            // إذا كان رابط إنترنت مباشر (يبدأ بـ http:// أو https://)
                            if (str_starts_with($user->image, 'http://') || str_starts_with($user->image, 'https://')) {
                                $imageUrl = $user->image;
                            } else {
                                // إذا كان مسار ملف محلي
                                $imageUrl = asset('storage/' . $user->image);
                            }
                        }
                        $imageId = 'patient-image-' . $record->id;
                        $imageHtml = $imageUrl ?
                            '<div style="text-align: center; margin-bottom: 15px;">
                                <img id="' . $imageId . '" src="' . htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') . '" style="max-width: 200px; height: 200px; border-radius: 50%; object-fit: cover; border: 3px solid #e5e7eb; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); transition: transform 0.2s; cursor: pointer;" onmouseover="this.style.transform=\'scale(1.05)\'" onmouseout="this.style.transform=\'scale(1)\'" onclick="openImageModal(\'' . htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') . '\')" />
                                <p style="margin-top: 10px; color: #6b7280; font-size: 12px;">اضغط على الصورة لعرضها بالحجم الكامل</p>
                            </div>' :
                            '<div style="text-align: center; margin-bottom: 15px; color: #9ca3af;"><p>لا توجد صورة</p></div>';

                        $infoHtml = '<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-top: 15px;">
                            <div><strong>الاسم:</strong> ' . ($user->name ?? 'غير محدد') . '</div>
                            <div><strong>الهاتف:</strong> ' . ($user->phone ?? 'غير محدد') . '</div>
                            <div><strong>البريد:</strong> ' . ($user->email ?? 'غير محدد') . '</div>
                            <div><strong>فصيلة الدم:</strong> <span style="background: #fee2e2; color: #991b1b; padding: 4px 8px; border-radius: 4px;">' . ($user->blood ?? 'غير محدد') . '</span></div>
                        </div>';

                        $html = '
                        <details style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 15px; margin-bottom: 15px; cursor: pointer;">
                            <summary style="font-weight: 600; font-size: 16px; color: #1f2937; padding: 10px; list-style: none; display: flex; align-items: center; justify-content: space-between;">
                                <span>معلومات المريض</span>
                                <span style="font-size: 12px; color: #6b7280;">▼</span>
                            </summary>
                            <div style="margin-top: 15px;">
                                ' . $imageHtml . $infoHtml . '
                            </div>
                        </details>
                        <style>
                            details summary::-webkit-details-marker { display: none; }
                            details[open] summary span:last-child { transform: rotate(180deg); }
                            .image-modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.9); }
                            .image-modal-content { margin: auto; display: block; max-width: 90%; max-height: 90%; margin-top: 5%; }
                            .image-modal-close { position: absolute; top: 15px; right: 35px; color: #f1f1f1; font-size: 40px; font-weight: bold; cursor: pointer; }
                            .image-modal-close:hover { color: #bbb; }
                        </style>
                        <div id="imageModal" class="image-modal" onclick="this.style.display=\'none\'">
                            <span class="image-modal-close">&times;</span>
                            <img class="image-modal-content" id="modalImage">
                        </div>
                        <script>
                            if (typeof openImageModal === "undefined") {
                                function openImageModal(imageUrl) {
                                    var modal = document.getElementById("imageModal");
                                    var modalImg = document.getElementById("modalImage");
                                    if (modal && modalImg) {
                                        modal.style.display = "block";
                                        modalImg.src = imageUrl;
                                    }
                                }
                                window.onclick = function(event) {
                                    var modal = document.getElementById("imageModal");
                                    if (event.target == modal) {
                                        modal.style.display = "none";
                                    }
                                }
                            }
                        </script>';

                        return new \Illuminate\Support\HtmlString($html);
                    })
                    ->visible(fn ($record) => $record && $record->user)
                    ->columnSpanFull(),

                // معلومات المتبرع (قابلة للطي)
                Forms\Components\Placeholder::make('donor_info')
                    ->label('معلومات المتبرع')
                    ->content(function ($record) {
                        if (!$record || !$record->acceptedDonor) {
                            return 'لم يتم القبول بعد';
                        }
                        $donor = $record->acceptedDonor;
                        $imageUrl = null;
                        if ($donor->image) {
                            // إذا كان رابط إنترنت مباشر (يبدأ بـ http:// أو https://)
                            if (str_starts_with($donor->image, 'http://') || str_starts_with($donor->image, 'https://')) {
                                $imageUrl = $donor->image;
                            } else {
                                // إذا كان مسار ملف محلي
                                $imageUrl = asset('storage/' . $donor->image);
                            }
                        }
                        $imageId = 'donor-image-' . $record->id;
                        $imageHtml = $imageUrl ?
                            '<div style="text-align: center; margin-bottom: 15px;">
                                <img id="' . $imageId . '" src="' . htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') . '" style="max-width: 200px; height: 200px; border-radius: 50%; object-fit: cover; border: 3px solid #e5e7eb; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); transition: transform 0.2s; cursor: pointer;" onmouseover="this.style.transform=\'scale(1.05)\'" onmouseout="this.style.transform=\'scale(1)\'" onclick="openImageModal(\'' . htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') . '\')" />
                                <p style="margin-top: 10px; color: #6b7280; font-size: 12px;">اضغط على الصورة لعرضها بالحجم الكامل</p>
                            </div>' :
                            '<div style="text-align: center; margin-bottom: 15px; color: #9ca3af;"><p>لا توجد صورة</p></div>';

                        $infoHtml = '<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-top: 15px;">
                            <div><strong>الاسم:</strong> ' . ($donor->name ?? 'غير محدد') . '</div>
                            <div><strong>الهاتف:</strong> ' . ($donor->phone ?? 'غير محدد') . '</div>
                            <div><strong>البريد:</strong> ' . ($donor->email ?? 'غير محدد') . '</div>
                            <div><strong>فصيلة الدم:</strong> <span style="background: #fee2e2; color: #991b1b; padding: 4px 8px; border-radius: 4px;">' . ($donor->blood ?? 'غير محدد') . '</span></div>
                        </div>';

                        $html = '
                        <details style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 15px; margin-bottom: 15px; cursor: pointer;">
                            <summary style="font-weight: 600; font-size: 16px; color: #1f2937; padding: 10px; list-style: none; display: flex; align-items: center; justify-content: space-between;">
                                <span>معلومات المتبرع</span>
                                <span style="font-size: 12px; color: #6b7280;">▼</span>
                            </summary>
                            <div style="margin-top: 15px;">
                                ' . $imageHtml . $infoHtml . '
                            </div>
                        </details>
                        <style>
                            details summary::-webkit-details-marker { display: none; }
                            details[open] summary span:last-child { transform: rotate(180deg); }
                            .image-modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.9); }
                            .image-modal-content { margin: auto; display: block; max-width: 90%; max-height: 90%; margin-top: 5%; }
                            .image-modal-close { position: absolute; top: 15px; right: 35px; color: #f1f1f1; font-size: 40px; font-weight: bold; cursor: pointer; }
                            .image-modal-close:hover { color: #bbb; }
                        </style>
                        <div id="imageModal" class="image-modal" onclick="this.style.display=\'none\'">
                            <span class="image-modal-close">&times;</span>
                            <img class="image-modal-content" id="modalImage">
                        </div>
                        <script>
                            if (typeof openImageModal === "undefined") {
                                function openImageModal(imageUrl) {
                                    var modal = document.getElementById("imageModal");
                                    var modalImg = document.getElementById("modalImage");
                                    if (modal && modalImg) {
                                        modal.style.display = "block";
                                        modalImg.src = imageUrl;
                                    }
                                }
                                window.onclick = function(event) {
                                    var modal = document.getElementById("imageModal");
                                    if (event.target == modal) {
                                        modal.style.display = "none";
                                    }
                                }
                            }
                        </script>';

                        return new \Illuminate\Support\HtmlString($html);
                    })
                    ->visible(fn ($record) => $record && $record->acceptedDonor)
                    ->columnSpanFull(),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('المريض')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),



                Tables\Columns\TextColumn::make('user.phone')
                    ->label('هاتف المريض')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('acceptedDonor.name')
                    ->label('المتبرع')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->placeholder('لم يتم القبول بعد'),



                Tables\Columns\TextColumn::make('acceptedDonor.phone')
                    ->label('هاتف المتبرع')
                    ->searchable()
                    ->copyable()
                    ->toggleable()
                    ->placeholder('لم يتم القبول بعد'),

                Tables\Columns\TextColumn::make('type')
                    ->label('النوع')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'blood' => 'danger',
                        'organ' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'blood' => 'دم',
                        'organ' => 'عضو',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('blood')
                    ->label('فصيلة الدم')
                    ->badge()
                    ->color('danger'),

                Tables\Columns\TextColumn::make('hospital.name')
                    ->label('المستشفى')
                    ->searchable()
                    ->sortable()
                    ->placeholder('لم يتم تحديد المستشفى بعد')
                    ->visible(fn () => Auth::check() && Auth::user()?->user_type === 'super_admin'),

                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'pending' => 'warning',
                        'completed' => 'info',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'نشط',
                        'pending' => 'في الانتظار',
                        'completed' => 'مكتمل',
                        'cancelled' => 'ملغي',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('operation_status')
                    ->label('حالة العملية')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'pending' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'pending' => 'في الانتظار',
                        'completed' => 'مكتمل',
                        'cancelled' => 'ملغي',
                        default => 'غير محدد',
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('الوصف')
                    ->limit(50)
                    ->wrap()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('d/m/Y h:i A')
                    ->sortable(),

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
                        'active' => 'نشط',
                        'pending' => 'في الانتظار',
                        'completed' => 'مكتمل',
                        'cancelled' => 'ملغي',
                    ]),

                Tables\Filters\SelectFilter::make('type')
                    ->label('النوع')
                    ->options([
                        'blood' => 'دم',
                        'organ' => 'عضو',
                    ]),

                Tables\Filters\SelectFilter::make('blood')
                    ->label('فصيلة الدم')
                    ->options([
                        'O-' => 'O-',
                        'O+' => 'O+',
                        'A-' => 'A-',
                        'A+' => 'A+',
                        'B-' => 'B-',
                        'B+' => 'B+',
                        'AB-' => 'AB-',
                        'AB+' => 'AB+',
                    ]),

                Tables\Filters\SelectFilter::make('operation_status')
                    ->label('حالة العملية')
                    ->options([
                        'pending' => 'في الانتظار',
                        'completed' => 'مكتمل',
                        'cancelled' => 'ملغي',
                    ]),

                Tables\Filters\Filter::make('has_accepted_donor')
                    ->label('يحتوي على متبرع مقبول')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('accepted_donor_id'))
                    ->toggle(),

                Tables\Filters\Filter::make('no_accepted_donor')
                    ->label('بدون متبرع مقبول')
                    ->query(fn (Builder $query): Builder => $query->whereNull('accepted_donor_id'))
                    ->toggle(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\BulkAction::make('complete')
                        ->label('إكمال المحدد')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('إكمال الطلبات المحددة')
                        ->modalDescription('هل أنت متأكد من إكمال جميع الطلبات المحددة؟')
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->status === 'pending' && $record->accepted_donor_id) {
                                    $record->update([
                                        'status' => 'completed',
                                        'operation_status' => 'completed',
                                    ]);
                                    $count++;
                                }
                            }

                            \Filament\Notifications\Notification::make()
                                ->title("تم إكمال {$count} طلب")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Actions\BulkAction::make('cancel')
                        ->label('إلغاء المحدد')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('إلغاء الطلبات المحددة')
                        ->modalDescription('هل أنت متأكد من إلغاء جميع الطلبات المحددة؟')
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                $record->update([
                                    'status' => 'cancelled',
                                ]);
                                $count++;
                            }

                            \Filament\Notifications\Notification::make()
                                ->title("تم إلغاء {$count} طلب")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Actions\BulkAction::make('export')
                        ->label('تصدير المحدد')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->action(function ($records) {
                            // يمكن إضافة منطق التصدير هنا
                            \Filament\Notifications\Notification::make()
                                ->title('تم التصدير بنجاح')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ])
                ->visible(fn () => Auth::check() && Auth::user()?->user_type === 'super_admin'),
            ])
            ->actions([
                Actions\Action::make('complete_operation')
                    ->label('اكتملت العملية')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('تأكيد اكتمال العملية')
                    ->modalDescription('هل أنت متأكد من أن العملية اكتملت؟')
                    ->action(function (SosRequest $record) {
                        $record->update([
                            'operation_status' => 'completed',
                            'status' => 'completed',
                        ]);

                        // إرسال إشعار للمتبرع
                        $notificationService = new \App\Services\NotificationService();
                        $notificationService->sendApprovalNotification($record);

                        \Filament\Notifications\Notification::make()
                            ->title('تم تحديث حالة العملية إلى مكتمل')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (SosRequest $record) =>
                        $record->operation_status !== 'completed' &&
                        $record->status !== 'completed'
                    ),

                Actions\Action::make('cancel_operation')
                    ->label('لم تكتمل العملية')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (SosRequest $record) {
                        $record->update([
                            'operation_status' => 'cancelled',
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('تم إلغاء العملية')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (SosRequest $record) =>
                        $record->status === 'pending' &&
                        $record->operation_status !== 'cancelled'
                    ),

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
            'index' => Pages\ListSosRequests::route('/'),
            'view' => Pages\ViewSosRequest::route('/{record}'),
            'edit' => Pages\EditSosRequest::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['user:id,name,phone,email,blood,image', 'acceptedDonor:id,name,phone,email,blood,image', 'hospital:id,name,address,latitude,longitude,phone']);

        // إذا كان المستخدم من نوع hospital، يعرض فقط طلبات SOS لمستشفاه
        if (Auth::check() && Auth::user()?->user_type === 'hospital') {
            $user = Auth::user();
            $hospital = $user->hospital;

            if ($hospital) {
                // عرض طلبات SOS المرتبطة بمستشفى المستخدم
                return $query->where('hospital_id', $hospital->id);
            } else {
                // إذا لم يكن للمستخدم مستشفى، لا يعرض أي شيء
                return $query->whereRaw('1 = 0');
            }
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
        return false; // لا يمكن إنشاء طلبات SOS من Filament
    }
}

