<?php

namespace App\Filament\Widgets;

use App\Models\CibilReport;
use Filament\Widgets\ChartWidget;

class CibilScoreDistributionChart extends ChartWidget
{
    protected static ?string $heading = 'CIBIL Score Distribution';
    protected static ?int $sort = 5;
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $ranges = [
            '300-549' => [300, 549],
            '550-649' => [550, 649],
            '650-749' => [650, 749],
            '750-799' => [750, 799],
            '800-900' => [800, 900],
        ];

        $distribution = cache()->remember('dashboard.cibil_score_distribution', now()->addMinutes(5), function () use ($ranges) {
            $rangeSql = [];
            foreach ($ranges as $label => [$min, $max]) {
                $rangeSql[] = "SUM(CASE WHEN cibil_score BETWEEN {$min} AND {$max} THEN 1 ELSE 0 END) as `{$label}`";
            }

            $counts = CibilReport::selectRaw(implode(', ', $rangeSql), [])->first();

            return array_map(fn($label) => (int) $counts->{$label}, array_keys($ranges));
        });

        return [
            'datasets' => [
                [
                    'label' => 'Number of Customers',
                    'data' => array_values($distribution),
                    'backgroundColor' => [
                        'rgba(239, 68, 68, 0.7)',   // Red (Poor)
                        'rgba(245, 158, 11, 0.7)',  // Orange (Fair)
                        'rgba(16, 185, 129, 0.7)',  // Green (Good)
                        'rgba(59, 130, 246, 0.7)',  // Blue (Very Good)
                        'rgba(139, 92, 246, 0.7)',  // Purple (Excellent)
                    ],
                ],
            ],
            'labels' => array_keys($distribution),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'tooltip' => [
                    'enabled' => true,
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