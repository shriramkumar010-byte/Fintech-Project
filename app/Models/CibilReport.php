<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CibilReport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_name',
        'pan_number',
        'aadhaar_number',
        'loan_type',
        'cibil_score',
        'risk_category',
        'cibil_summary',
        'remarks',
        'active_loans',
        'total_loan_amount',
        'emi_bounces',
        'credit_utilization',
    ];

    protected $casts = [
        'cibil_score' => 'integer',
        'active_loans' => 'integer',
        'total_loan_amount' => 'decimal:2',
        'emi_bounces' => 'integer',
        'credit_utilization' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function loanApplications()
    {
        return $this->hasMany(LoanApplication::class, 'pan_number', 'pan_number');
    }

    public function getIsEligibleAttribute()
    {
        return $this->cibil_score >= 650 && 
               $this->emi_bounces <= 2 && 
               $this->credit_utilization <= 70;
    }

    public function getScoreCategoryAttribute()
    {
        return match(true) {
            $this->cibil_score >= 750 => 'Excellent',
            $this->cibil_score >= 700 => 'Very Good',
            $this->cibil_score >= 650 => 'Good',
            $this->cibil_score >= 600 => 'Fair',
            default => 'Poor',
        };
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            // Ensure numeric fields are properly formatted
            if (isset($model->cibil_score)) {
                $model->cibil_score = (int) $model->cibil_score;
            }
            if (isset($model->active_loans)) {
                $model->active_loans = (int) $model->active_loans;
            }
            if (isset($model->emi_bounces)) {
                $model->emi_bounces = (int) $model->emi_bounces;
            }
            if (isset($model->total_loan_amount)) {
                $model->total_loan_amount = number_format((float) $model->total_loan_amount, 2, '.', '');
            }
            if (isset($model->credit_utilization)) {
                $model->credit_utilization = number_format((float) $model->credit_utilization, 2, '.', '');
            }
        });
    }
}
