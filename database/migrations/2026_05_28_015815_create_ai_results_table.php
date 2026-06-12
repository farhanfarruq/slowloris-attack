<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('experiment_id')->constrained()->cascadeOnDelete();
            $table->string('model_name'); // groq | openai | gemini | ollama
            $table->string('model_version')->nullable();
            $table->string('classification'); // Normal | Suspicious | Slowloris Detected | Inconclusive
            $table->float('confidence_score')->default(0); // 0-100
            $table->text('reason')->nullable();
            $table->json('supporting_indicators')->nullable();
            $table->json('missing_evidence')->nullable();
            $table->text('recommendation')->nullable();
            $table->json('raw_request')->nullable();
            $table->json('raw_response')->nullable();
            $table->boolean('is_simulated')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_results');
    }
};
