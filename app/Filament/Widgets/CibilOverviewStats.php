<?php

namespace App\Filament\Widgets;

use App\Models\CibilReport;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CibilOverviewStats extends StatsOverviewWidget
{
    protected ?string $heading = 'Cibil Overview Stats';
    protected static ?string $pollingInterval = null;


    protected function getStats(): array
    {
        $stats = cache()->remember('dashboard.cibil_overview_stats', now()->addMinutes(5), function () {
            return CibilReport::selectRaw(
                'AVG(cibil_score) as avg_score, COUNT(*) as total, SUM(CASE WHEN risk_category = "low" THEN 1 ELSE 0 END) as low_count, SUM(CASE WHEN risk_category = "medium" THEN 1 ELSE 0 END) as medium_count, SUM(CASE WHEN risk_category = "high" THEN 1 ELSE 0 END) as high_count',
                []
            )->first();
        });

        $avgScore = round($stats->avg_score ?? 0);
        $lowRiskCount = $stats->low_count ?? 0;
        $mediumRiskCount = $stats->medium_count ?? 0;
        $highRiskCount = $stats->high_count ?? 0;

        $totalReports = $stats->total ?: 0;
        $lowRiskPercentage = $totalReports > 0 ? round(($lowRiskCount / $totalReports) * 100, 1) : 0;

        return [
            Stat::make('Average CIBIL Score', $avgScore)
                ->description('Overall credit health')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($this->getScoreColor($avgScore)),

            Stat::make('Low Risk Applications', $lowRiskCount)
                ->description($lowRiskPercentage . '% of total applications')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('success'),

            Stat::make('Medium Risk', $mediumRiskCount)
                ->description('Moderate credit risk')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('warning'),

            Stat::make('High Risk', $highRiskCount)
                ->description('Needs attention')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),
        ];
    }

    private function getScoreColor(int $score): string
    {
        return match(true) {
            $score >= 750 => 'success',
            $score >= 650 => 'warning',
            default => 'danger',
        };
    }
}
