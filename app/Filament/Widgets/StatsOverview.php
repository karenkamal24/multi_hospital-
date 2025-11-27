<?php

namespace App\Filament\Widgets;

use App\Models\Hospital;
use App\Models\SosRequest;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();
        $hospital = null;

        // إذا كان المستخدم من نوع hospital، احصل على مستشفاه
        if ($user && $user->user_type === 'hospital') {
            $hospital = $user->hospital;
        }

        // بناء الاستعلامات بناءً على نوع المستخدم
        $sosRequestQuery = SosRequest::query();

        // إذا كان هناك مستشفى محدد، فلتر البيانات بناءً عليه
        if ($hospital) {
            $sosRequestQuery->where('hospital_id', $hospital->id);
            // للمستخدمين: يمكن إضافة فلترة إضافية إذا لزم الأمر
        }

        $completedRequests = (clone $sosRequestQuery)->where('status', 'completed')->count();
        $pendingRequests = (clone $sosRequestQuery)->where('status', 'pending')->count();
        $activeRequests = (clone $sosRequestQuery)->where('status', 'active')->count();
        $totalRequests = $sosRequestQuery->count();

        // إحصائيات المستشفيات (فقط للمشرف)
        $totalHospitals = $user && $user->user_type === 'super_admin'
            ? Hospital::count()
            : ($hospital ? 1 : 0);

        // إحصائيات المستخدمين (فقط للمشرف)
        $totalUsers = $user && $user->user_type === 'super_admin'
            ? User::count()
            : 0;

        $totalPatients = $user && $user->user_type === 'super_admin'
            ? User::where('user_type', 'patient')->count()
            : 0;

        $totalDonors = $user && $user->user_type === 'super_admin'
            ? User::where('user_type', 'donner')->count()
            : 0;

        $stats = [
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
        ];

        // إحصائيات إضافية للمشرف فقط
        if ($user && $user->user_type === 'super_admin') {
            $stats[] = Stat::make('إجمالي المستشفيات', $totalHospitals)
                ->description('المستشفيات المسجلة')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('secondary')
                ->chart([1, 2, 3, 4, 5, 6, 7]);

            $stats[] = Stat::make('إجمالي المستخدمين', $totalUsers)
                ->description('جميع المستخدمين')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary')
                ->chart([10, 12, 14, 16, 18, 20, 22]);

            $stats[] = Stat::make('المرضى', $totalPatients)
                ->description('المسجلين كمرضى')
                ->descriptionIcon('heroicon-m-user')
                ->color('danger')
                ->chart([5, 6, 7, 8, 9, 10, 11]);

            $stats[] = Stat::make('المتبرعون', $totalDonors)
                ->description('المسجلين كمتبرعين')
                ->descriptionIcon('heroicon-m-heart')
                ->color('success')
                ->chart([3, 4, 5, 6, 7, 8, 9]);
        }

        return $stats;
    }
}

