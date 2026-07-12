<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('cibil_reports', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('pan_number')->unique();
            $table->string('aadhaar_number')->unique();
            $table->string('loan_type');
            $table->integer('cibil_score')->nullable();
            $table->enum('risk_category', ['low', 'medium', 'high'])->nullable();
            $table->text('cibil_summary')->nullable();
            $table->text('remarks')->nullable();
            $table->integer('active_loans')->nullable();
            $table->decimal('total_loan_amount', 12, 2)->nullable();
            $table->integer('emi_bounces')->nullable();
            $table->decimal('credit_utilization', 5, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('cibil_reports');
    }
}; 