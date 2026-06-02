<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'clickpesa_transaction_id')) {
                $table->string('clickpesa_transaction_id')->nullable()->after('status');
            }
            if (!Schema::hasColumn('payments', 'clickpesa_response')) {
                $table->text('clickpesa_response')->nullable()->after('clickpesa_transaction_id');
            }
            if (!Schema::hasColumn('payments', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('status');
            }
            // Make sure status supports 'pending'
            $table->enum('status', ['paid', 'unpaid', 'pending', 'failed'])->default('pending')->change();
        });
    }

    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['clickpesa_transaction_id', 'clickpesa_response', 'paid_at']);
        });
    }
};