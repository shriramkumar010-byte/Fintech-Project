<?php

namespace Database\Factories;

use App\Models\CibilReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CibilReport>
 */
class CibilReportFactory extends Factory
{
    protected $model = CibilReport::class;

    public function definition(): array
    {
        $score = $this->faker->numberBetween(300, 900);
        $riskCategory = match (true) {
            $score >= 750 => 'low',
            $score >= 650 => 'medium',
            default => 'high',
        };

        return [
            'customer_name' => $this->faker->name(),
            'pan_number' => strtoupper($this->faker->bothify('?????#????')), 
            'aadhaar_number' => $this->faker->numerify('############'),
            'loan_type' => $this->faker->randomElement(['personal', 'home', 'business', 'education', 'car', 'gold']),
            'cibil_score' => $score,
            'risk_category' => $riskCategory,
            'cibil_summary' => $this->faker->sentence(10),
            'remarks' => $this->faker->optional()->sentence(8),
            'active_loans' => $this->faker->numberBetween(0, 5),
            'total_loan_amount' => $this->faker->randomFloat(2, 50000, 2000000),
            'emi_bounces' => $this->faker->numberBetween(0, 4),
            'credit_utilization' => $this->faker->randomFloat(2, 5, 90),
            'created_at' => now()->subDays($this->faker->numberBetween(1, 90)),
            'updated_at' => now(),
        ];
    }
}
