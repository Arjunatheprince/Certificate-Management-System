<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', ['superadmin', 'admin', 'viewer', 'developer', 'user'])
                    ->default('viewer')
                    ->after('password');
            });
        } else {
            // Modify role enum to include new RBAC roles
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('superadmin','admin','viewer','developer','user') NOT NULL DEFAULT 'viewer'");
        }

        // Add is_active column if not exists
        if (!Schema::hasColumn('users', 'is_active')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('role');
            });
        }

        // Map existing roles to new RBAC roles
        DB::table('users')->where('email', 'admin@admin.com')->update(['role' => 'superadmin', 'name' => 'Super Admin']);
        DB::table('users')->where('email', 'dev@admin.com')->update(['role' => 'admin', 'name' => 'Developer Admin']);
        DB::table('users')->where('role', 'developer')->where('email', '!=', 'dev@admin.com')->update(['role' => 'admin']);
        DB::table('users')->where('role', 'user')->update(['role' => 'viewer']);
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('developer','admin','user') NOT NULL DEFAULT 'user'");
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
