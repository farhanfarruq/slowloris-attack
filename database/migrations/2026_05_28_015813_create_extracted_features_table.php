<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extracted_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('experiment_id')->constrained()->cascadeOnDelete();
            // Numerical features used by AI / scoring
            $table->float('total_packets')->nullable();
            $table->float('tcp_packets')->nullable();
            $table->float('http_packets')->nullable();
            $table->float('avg_packet_size')->nullable();
            $table->float('duration_seconds')->nullable();
            $table->float('total_connections')->nullable();
            $table->float('long_lived_connections')->nullable();
            $table->float('avg_connection_duration')->nullable();
            $table->float('connections_to_http_port')->nullable();
            $table->float('throughput_kbps')->nullable();
            $table->float('total_alerts')->nullable();
            $table->float('high_severity_alerts')->nullable();
            $table->float('medium_severity_alerts')->nullable();
            $table->float('baseline_avg_connections')->default(120);
            $table->float('baseline_throughput_kbps')->default(950);
            $table->float('baseline_alert_count')->default(2);
            // Radar scores (0-100)
            $table->float('connection_duration_score')->nullable();
            $table->float('header_anomaly_score')->nullable();
            $table->float('low_bandwidth_high_connection_score')->nullable();
            $table->float('snort_alert_score')->nullable();
            $table->float('tcp_connection_score')->nullable();
            $table->float('baseline_deviation_score')->nullable();
            $table->float('ai_confidence_score')->nullable();
            // Final aggregate
            $table->float('final_attack_score')->nullable();
            $table->string('attack_category')->nullable(); // Normal | Suspicious | Possible Slowloris | Strong Slowloris Indication
            $table->json('raw_features')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extracted_features');
    }
};
