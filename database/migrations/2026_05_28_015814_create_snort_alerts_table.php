<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('snort_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('experiment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('validation_file_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('alert_timestamp')->nullable();
            $table->string('alert_type')->nullable();
            $table->string('severity')->nullable(); // high | medium | low
            $table->string('source_ip')->nullable();
            $table->string('destination_ip')->nullable();
            $table->unsignedInteger('source_port')->nullable();
            $table->unsignedInteger('destination_port')->nullable();
            $table->string('protocol')->nullable();
            $table->text('message')->nullable();
            $table->json('raw')->nullable();
            $table->timestamps();
            $table->index(['experiment_id', 'alert_timestamp']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('snort_alerts');
    }
};
