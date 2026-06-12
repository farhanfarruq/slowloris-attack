<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('experiments', function (Blueprint $table) {
            $table->id();
            $table->string('experiment_code')->unique(); // EXP-001
            $table->string('name');
            $table->date('experiment_date');
            $table->string('network_interface')->nullable();
            $table->string('target_ip')->nullable();
            $table->string('source_ip')->nullable();
            $table->integer('capture_duration')->nullable(); // seconds
            $table->text('notes')->nullable();
            $table->enum('traffic_type', ['normal', 'slowloris_lab', 'mixed', 'unknown'])
                  ->default('unknown');
            $table->enum('status', [
                'created', 'data_acquired', 'validated', 'analyzed', 'ai_validated', 'completed'
            ])->default('created');
            $table->enum('experiment_status', [
                'normal', 'suspicious', 'attack_detected', 'inconclusive', 'pending'
            ])->default('pending');
            $table->string('ground_truth_label')->nullable(); // normal | slowloris_lab | mixed | unknown
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('experiments');
    }
};
