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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->onUpdate('cascade');
            $table->string('airtel_money_number')->nullable();
            $table->string('m_pesa_number')->nullable();
            $table->string('mixx_by_yas_number')->nullable();
            $table->string('halopesa_number')->nullable();
            $table->string('nmb_account_number')->nullable();
            $table->string('crdb_account_number')->nullable();
            $table->string('nbc_account_number')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
