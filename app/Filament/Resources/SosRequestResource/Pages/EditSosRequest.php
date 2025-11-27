<?php

namespace App\Filament\Resources\SosRequestResource\Pages;

use App\Filament\Resources\SosRequestResource;
use App\Models\SosRequest;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSosRequest extends EditRecord
{
    protected static string $resource = SosRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),

            Actions\Action::make('complete_operation')
                ->label('اكتمال عملية')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('تأكيد اكتمال العملية')
                ->modalDescription('هل أنت متأكد من أن عملية التبرع اكتملت؟ سيتم إرسال إشعار للمريض والمتبرع.')
                ->action(function (SosRequest $record) {
                    $record->update([
                        'operation_status' => 'completed',
                        'status' => 'completed',
                    ]);

                    // إرسال إشعار للمريض والمتبرع
                    $notificationService = new \App\Services\NotificationService();
                    $notificationService->sendOperationCompletionNotification($record);

                    \Filament\Notifications\Notification::make()
                        ->title('تم إكمال العملية بنجاح')
                        ->body('تم إرسال إشعار للمريض والمتبرع')
                        ->success()
                        ->send();
                })
                ->visible(function (SosRequest $record) {
                    // يظهر إذا كان هناك متبرع مقبول
                    return $record->accepted_donor_id !== null;
                }),
        ];
    }
}



