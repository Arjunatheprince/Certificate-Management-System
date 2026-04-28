<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('certificates', 'valid_until')) {
            Schema::table('certificates', function (Blueprint $table) {
                $table->date('valid_until')->nullable()->after('certificate_type');
            });
        }
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn('valid_until');
        });
    }
};
