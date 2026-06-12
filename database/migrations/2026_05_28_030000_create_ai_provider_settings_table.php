<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_provider_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider_key')->unique();
            $table->string('provider_label')->nullable();
            $table->string('driver')->default('openai_compatible');
            $table->text('api_key')->nullable();
            $table->string('api_url', 500)->nullable();
            $table->string('model')->nullable();
            $table->boolean('use_live_api')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_provider_settings');
    }
};
