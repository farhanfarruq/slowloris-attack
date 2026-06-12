<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('experiments', function (Blueprint $table) {
            $table->string('scenario_key')->nullable()->index();
        });

        Schema::table('acquisition_files', function (Blueprint $table) {
            $table->string('capture_label')->nullable()->index();
            $table->string('scenario_key')->nullable()->index();
            $table->string('source_ip')->nullable();
            $table->string('target_ip')->nullable();
            $table->timestamp('capture_started_at')->nullable();
            $table->timestamp('capture_ended_at')->nullable();
        });

        Schema::table('validation_files', function (Blueprint $table) {
            $table->foreignId('acquisition_file_id')
                ->nullable()
                ->constrained('acquisition_files')
                ->nullOnDelete();
            $table->string('capture_label')->nullable()->index();
            $table->string('scenario_key')->nullable()->index();
            $table->string('source_ip')->nullable();
            $table->string('target_ip')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('validation_files', function (Blueprint $table) {
            $table->dropConstrainedForeignId('acquisition_file_id');
            $table->dropColumn(['capture_label', 'scenario_key', 'source_ip', 'target_ip']);
        });

        Schema::table('acquisition_files', function (Blueprint $table) {
            $table->dropColumn([
                'capture_label',
                'scenario_key',
                'source_ip',
                'target_ip',
                'capture_started_at',
                'capture_ended_at',
            ]);
        });

        Schema::table('experiments', function (Blueprint $table) {
            $table->dropColumn('scenario_key');
        });
    }
};
