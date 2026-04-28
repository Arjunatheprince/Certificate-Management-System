<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('certificates')) {
            Schema::create('certificates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->string('certificate_number')->unique();
                $table->string('certificate_type');
                $table->string('file_path');
                $table->string('hash_sha256')->nullable();
                $table->string('digital_signature')->nullable();
                $table->string('qr_code')->nullable();
                $table->boolean('qr_attached')->default(false);
                $table->integer('qr_x')->default(50);
                $table->integer('qr_y')->default(50);
                $table->integer('qr_size')->default(150);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamp('created_at')->nullable();
            });

            return;
        }

        Schema::table('certificates', function (Blueprint $table) {
            if (!Schema::hasColumn('certificates', 'qr_attached')) {
                $table->boolean('qr_attached')->default(false)->after('qr_code');
            }
            if (!Schema::hasColumn('certificates', 'qr_x')) {
                $table->integer('qr_x')->default(50)->after('qr_attached');
            }
            if (!Schema::hasColumn('certificates', 'qr_y')) {
                $table->integer('qr_y')->default(50)->after('qr_x');
            }
            if (!Schema::hasColumn('certificates', 'qr_size')) {
                $table->integer('qr_size')->default(150)->after('qr_y');
            }
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn(['qr_attached', 'qr_x', 'qr_y', 'qr_size']);
        });
    }
};
