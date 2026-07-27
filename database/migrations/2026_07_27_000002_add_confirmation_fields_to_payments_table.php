<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'payment_method')) {
                $table->string('payment_method')->nullable()->default('clickpesa')->after('status');
            }
            if (!Schema::hasColumn('payments', 'confirmed_by_landlord')) {
                $table->boolean('confirmed_by_landlord')->default(false)->after('payment_method');
            }
            if (!Schema::hasColumn('payments', 'room_selected')) {
                $table->boolean('room_selected')->default(false)->after('confirmed_by_landlord');
            }
            if (!Schema::hasColumn('payments', 'confirmation_message')) {
                $table->text('confirmation_message')->nullable()->after('room_selected');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'confirmed_by_landlord', 'room_selected', 'confirmation_message']);
        });
    }
};
