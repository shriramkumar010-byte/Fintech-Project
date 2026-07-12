<?php

namespace App\Filament\Resources\EMIResource\Pages;

use Filament\Resources\Pages\Page;
use App\Filament\Resources\EMIResource;
use Illuminate\Support\Facades\Validator;

class EMICalculator extends Page
{
    protected static string $resource = EMIResource::class; // ✅ Correct Resource Reference
    protected static string $view = 'filament.resources.emi-resource.pages.emi-calculator';

    public $principal = 500000;
    public $rate = 7.5;
    public $duration = 60;
    public $emi;
    public $totalPayment;
    public $showAmortization = false;

    public function mount()
    {
        $this->calculateEMI(); // Auto-calculate on load
    }
    public $amortizationSchedule = [];

    public function calculateEMI()
    {
        if (!$this->principal || !$this->rate || !$this->duration) {
            return;
        }

        $monthlyRate = ($this->rate / 100) / 12;
        $emi = ($this->principal * $monthlyRate * pow(1 + $monthlyRate, $this->duration)) / (pow(1 + $monthlyRate, $this->duration) - 1);
        $this->emi = round($emi, 2);
        $this->totalPayment = round($emi * $this->duration, 2);

        $this->generateAmortizationSchedule($emi, $monthlyRate);
    }

    public function generateAmortizationSchedule($emi, $monthlyRate)
    {
        $balance = $this->principal;
        $schedule = [];

        for ($month = 1; $month <= $this->duration; $month++) {
            $monthlyInterest = round($balance * $monthlyRate, 2);
            $monthlyPrincipal = round($emi - $monthlyInterest, 2);
            $outstandingBalance = round($balance - $monthlyPrincipal, 2);

            $schedule[] = [
                'month' => $month,
                'opening_balance' => $balance,
                'monthly_interest' => $monthlyInterest,
                'monthly_principal' => $monthlyPrincipal,
                'outstanding_balance' => max($outstandingBalance, 0)
            ];

            $balance = $outstandingBalance;
        }

        $this->amortizationSchedule = $schedule;
    }

    public function toggleAmortization()
    {
        $this->showAmortization = !$this->showAmortization;
    }

}
