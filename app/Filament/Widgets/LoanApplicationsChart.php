<?php

namespace App\Filament\Widgets;

use App\Models\LoanApplication;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class LoanApplicationsChart extends ChartWidget
{
    protected static ?string $heading = 'Loan Applications Trend';
    protected static ?int $sort = 4;
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $data = cache()->remember('dashboard.loan_applications_trend', now()->addMinutes(5), function () {
            return Trend::model(LoanApplication::class)
                ->between(
                    start: now()->subMonths(6),
                    end: now(),
                )
                ->perMonth()
                ->dateColumn('created_at')
                ->count();
        });

        return [
            'datasets' => [
                [
                    'label' => 'Loan Applications',
                    'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
                    'borderColor' => '#7c3aed',
                    'fill' => 'start',
                    'backgroundColor' => 'rgba(124, 58, 237, 0.1)',
                ],
            ],
            'labels' => $data->map(fn (TrendValue $value) => $value->date),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
        ];
    }
}
