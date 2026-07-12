<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use App\Models\CibilReport;

class LoanApplication extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;
    protected $fillable = [
        'customer_name',
        'customer_phone',
        'customer_email',
        'father_name',
        'mother_name',
        'dob',
        'gender',
        'employment_type',
        'annual_income',
        'loan_type',
        'pincode',
        'residence_type',
        'street_address',
        'city',
        'state',
        'locality',
        'country',
        'pan_upload',
        'aadhaar_upload',
        'pan_number',
        'aadhaar_number',
        'voter_id',
        'driving_license',
        'passport_number',
        'bank_statement_upload',
        'salary_slip_upload',
        'loan_amount',
        'loan_tenure',
        'interest_rate',
        'expected_emi',
        'collateral',
    ];

    public function cibilReport()
    {
        // Relation via PAN number: LoanApplication.pan_number -> CibilReport.pan_number
        return $this->belongsTo(CibilReport::class, 'pan_number', 'pan_number');
    }


}
