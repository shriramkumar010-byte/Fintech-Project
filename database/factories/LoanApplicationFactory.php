<?php

namespace Database\Factories;

use App\Models\LoanApplication;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LoanApplication>
 */
class LoanApplicationFactory extends Factory
{
    protected $model = LoanApplication::class;

    public function definition(): array
    {
        $loanTypes = ['personal', 'home', 'business', 'education', 'car', 'gold'];
        $loanType = $this->faker->randomElement($loanTypes);
        $loanTenure = match ($loanType) {
            'home' => $this->faker->numberBetween(5, 30),
            'business' => $this->faker->numberBetween(1, 15),
            'education', 'car' => $this->faker->numberBetween(1, 7),
            'gold' => $this->faker->numberBetween(1, 3),
            default => $this->faker->numberBetween(1, 5),
        };
        $loanAmount = $this->faker->numberBetween(50000, 2000000);
        $rates = [
            'personal' => 14,
            'home' => 8.5,
            'business' => 12,
            'education' => 9,
            'car' => 10.5,
            'gold' => 11.5,
        ];
        $interestRate = $rates[$loanType];
        $monthlyRate = ($interestRate / 12) / 100;
        $totalPayments = $loanTenure * 12;
        $expectedEmi = $monthlyRate > 0 && $totalPayments > 0
            ? round($loanAmount * $monthlyRate * pow(1 + $monthlyRate, $totalPayments) / (pow(1 + $monthlyRate, $totalPayments) - 1), 2)
            : 0;

        return [
            'customer_name' => $this->faker->name(),
            'customer_phone' => $this->faker->numerify('9#########'),
            'customer_email' => $this->faker->unique()->safeEmail(),
            'father_name' => $this->faker->name('male'),
            'mother_name' => $this->faker->name('female'),
            'dob' => $this->faker->date('Y-m-d', '-21 years'),
            'gender' => $this->faker->randomElement(['male', 'female', 'other']),
            'employment_type' => $this->faker->randomElement(['salaried', 'self_employed', 'business', 'freelancer', 'unemployed']),
            'annual_income' => $this->faker->randomFloat(2, 100000, 5000000),
            'loan_type' => $loanType,
            'pincode' => $this->faker->numerify('######'),
            'residence_type' => $this->faker->randomElement(['owned', 'rented', 'company_provided', 'other']),
            'street_address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'locality' => $this->faker->citySuffix(),
            'country' => $this->faker->country(),
            'pan_upload' => 'dummy/pan.pdf',
            'aadhaar_upload' => 'dummy/aadhaar.pdf',
            'pan_number' => strtoupper($this->faker->bothify('?????#????')), 
            'aadhaar_number' => $this->faker->numerify('############'),
            'voter_id' => $this->faker->bothify('?????#????'),
            'driving_license' => $this->faker->bothify('?????-##########'),
            'passport_number' => $this->faker->regexify('[A-Z]{1}[0-9]{7}'),
            'bank_statement_upload' => 'dummy/bank_statement.pdf',
            'salary_slip_upload' => 'dummy/salary_slip.pdf',
            'loan_amount' => $loanAmount,
            'loan_tenure' => $loanTenure,
            'interest_rate' => $interestRate,
            'expected_emi' => $expectedEmi,
            'collateral' => $this->faker->optional()->sentence(),
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'created_at' => now()->subDays($this->faker->numberBetween(1, 90)),
            'updated_at' => now(),
        ];
    }
}
