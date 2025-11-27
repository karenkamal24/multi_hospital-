<?php

namespace App\Filament\Widgets;

use App\Models\Hospital;
use App\Models\SosRequest;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $completedRequests = SosRequest::where('status', 'completed')->count();
        $pendingRequests = SosRequest::where('status', 'pending')->count();
        $activeRequests = SosRequest::where('status', 'active')->count();
        $totalRequests = SosRequest::count();
        $totalHospitals = Hospital::count();
        $totalUsers = User::count();
        $totalPatients = User::where('user_type', 'patient')->count();
        $totalDonors = User::where('user_type', 'donner')->count();

        return [
            Stat::make('إجمالي طلبات SOS', $totalRequests)
                ->description('جميع الطلبات')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('primary')
                ->chart([7, 3, 4, 5, 6, 3, 4]),

            Stat::make('طلبات مكتملة', $completedRequests)
                ->description('تم إكمالها بنجاح')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->chart([2, 3, 4, 5, 6, 7, 8]),

            Stat::make('طلبات في الانتظار', $pendingRequests)
                ->description('قيد المعالجة')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->chart([5, 4, 3, 2, 1, 2, 3]),

            Stat::make('طلبات نشطة', $activeRequests)
                ->description('في انتظار القبول')
                ->descriptionIcon('heroicon-m-bolt')
                ->color('info')
                ->chart([3, 2, 4, 5, 3, 4, 2]),

            Stat::make('إجمالي المستشفيات', $totalHospitals)
                ->description('المستشفيات المسجلة')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('secondary')
                ->chart([1, 2, 3, 4, 5, 6, 7]),

            Stat::make('إجمالي المستخدمين', $totalUsers)
                ->description('جميع المستخدمين')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary')
                ->chart([10, 12, 14, 16, 18, 20, 22]),

            Stat::make('المرضى', $totalPatients)
                ->description('المسجلين كمرضى')
                ->descriptionIcon('heroicon-m-user')
                ->color('danger')
                ->chart([5, 6, 7, 8, 9, 10, 11]),

            Stat::make('المتبرعون', $totalDonors)
                ->description('المسجلين كمتبرعين')
                ->descriptionIcon('heroicon-m-heart')
                ->color('success')
                ->chart([3, 4, 5, 6, 7, 8, 9]),
        ];
    }
}

