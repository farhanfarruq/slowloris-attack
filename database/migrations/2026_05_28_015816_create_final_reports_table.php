<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('final_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('experiment_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('purpose')->nullable();
            $table->text('topology')->nullable();
            $table->text('tools_used')->nullable();
            $table->text('conclusion')->nullable();
            $table->text('limitations')->nullable();
            $table->text('recommendations')->nullable();
            $table->string('final_decision')->nullable(); // Serangan asli | Traffic normal | Perlu validasi lanjutan
            $table->float('voting_average_confidence')->nullable();
            $table->json('voting_summary')->nullable();
            $table->string('pdf_path')->nullable();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('final_reports');
    }
};
