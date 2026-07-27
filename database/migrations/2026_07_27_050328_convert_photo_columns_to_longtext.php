<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE rooms MODIFY COLUMN photo LONGTEXT NULL');
            if (Schema::hasTable('room_photos')) {
                DB::statement('ALTER TABLE room_photos MODIFY COLUMN photo LONGTEXT NULL');
            }
        } elseif ($driver === 'sqlite') {
            Schema::table('rooms', function (Blueprint $table) {
                $table->text('photo')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE rooms MODIFY COLUMN photo VARCHAR(255) NULL');
            if (Schema::hasTable('room_photos')) {
                DB::statement('ALTER TABLE room_photos MODIFY COLUMN photo VARCHAR(255) NULL');
            }
        } elseif ($driver === 'sqlite') {
            Schema::table('rooms', function (Blueprint $table) {
                $table->string('photo')->nullable()->change();
            });
        }
    }
};
