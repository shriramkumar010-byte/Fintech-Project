<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('loan_applications', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_email');
            $table->string('father_name');
            $table->string('mother_name');
            $table->date('dob');
            $table->string('gender');
            $table->string('employment_type');
            $table->decimal('annual_income', 10, 2);
            $table->string('loan_type');
            $table->string('pincode', 6);
            $table->string('residence_type');
            $table->text('street_address');
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('locality')->nullable();
            $table->string('country')->nullable();
            $table->string('pan_upload');
            $table->string('aadhaar_upload');
            $table->string('pan_number');
            $table->string('aadhaar_number', 12);
            $table->string('voter_id')->nullable();
            $table->string('driving_license')->nullable();
            $table->string('passport_number')->nullable();
            $table->string('bank_statement_upload');
            $table->string('salary_slip_upload');
            $table->decimal('loan_amount', 10, 2);
            $table->integer('loan_tenure');
            $table->decimal('interest_rate', 5, 2)->nullable();
            $table->decimal('expected_emi', 10, 2)->nullable();
            $table->text('collateral')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_applications');
    }
};
