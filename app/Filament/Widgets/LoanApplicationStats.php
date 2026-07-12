<?php

namespace App\Filament\Widgets;

use App\Models\LoanApplication;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LoanApplicationStats extends StatsOverviewWidget
{
    protected ?string $heading = 'Loan Application Stats';
    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $counts = cache()->remember('dashboard.loan_application_stats', now()->addMinutes(5), function () {
            return LoanApplication::selectRaw(
                "COUNT(*) as total, SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending, SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved, SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected",
                []
            )->first();
        });

        $totalApplications = $counts->total ?: 0;
        $pendingApplications = $counts->pending ?: 0;
        $approvedApplications = $counts->approved ?: 0;
        $rejectedApplications = $counts->rejected ?: 0;

        $approvalRate = $totalApplications > 0
            ? round(($approvedApplications / $totalApplications) * 100, 1)
            : 0;

        return [
            Stat::make('Total Applications', $totalApplications)
                ->description('All loan applications')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('gray'),

            Stat::make('Pending Applications', $pendingApplications)
                ->description('Awaiting review')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Approved Applications', $approvedApplications)
                ->description($approvalRate . '% Approval rate')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Rejected Applications', $rejectedApplications)
                ->description('Not eligible')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),
        ];
    }
}
