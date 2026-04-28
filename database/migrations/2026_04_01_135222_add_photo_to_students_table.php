<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('students')) {
            Schema::create('students', function (Blueprint $table) {
                $table->id();
                $table->string('npm');
                $table->string('name');
                $table->string('photo')->nullable();
                $table->timestamp('created_at')->nullable();
            });

            return;
        }

        if (!Schema::hasColumn('students', 'photo')) {
            Schema::table('students', function (Blueprint $table) {
                $table->string('photo')->nullable()->after('name');
            });
        }
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('photo');
        });
    }
};
