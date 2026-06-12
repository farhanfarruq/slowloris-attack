<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('validation_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('experiment_id')->constrained()->cascadeOnDelete();
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('extension');
            $table->unsignedBigInteger('size_bytes');
            $table->enum('snort_mode', ['ids', 'ips'])->default('ids');
            $table->string('rule_set')->nullable();
            $table->string('monitoring_interface')->nullable();
            $table->integer('threshold')->nullable();
            $table->text('notes')->nullable();
            // Parsing summary
            $table->unsignedInteger('total_alerts')->nullable();
            $table->string('dominant_alert_type')->nullable();
            $table->string('highest_severity')->nullable();
            $table->json('top_source_ips')->nullable();
            $table->json('top_destination_ports')->nullable();
            $table->json('alert_timeline')->nullable();
            $table->boolean('matches_slow_http_pattern')->default(false);
            $table->json('parsed_summary')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('validation_files');
    }
};
