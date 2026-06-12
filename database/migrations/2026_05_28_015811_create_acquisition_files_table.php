<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acquisition_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('experiment_id')->constrained()->cascadeOnDelete();
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('extension');
            $table->unsignedBigInteger('size_bytes');
            $table->string('mime_type')->nullable();
            // Parsing summary
            $table->unsignedInteger('total_packets')->nullable();
            $table->unsignedInteger('tcp_packets')->nullable();
            $table->unsignedInteger('http_packets')->nullable();
            $table->float('avg_packet_size')->nullable();
            $table->json('top_source_ips')->nullable();
            $table->json('top_destination_ips')->nullable();
            $table->json('protocol_distribution')->nullable();
            $table->unsignedInteger('total_connections')->nullable();
            $table->float('avg_connection_duration')->nullable();
            $table->unsignedInteger('half_open_connections')->nullable();
            $table->json('parsed_summary')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acquisition_files');
    }
};
