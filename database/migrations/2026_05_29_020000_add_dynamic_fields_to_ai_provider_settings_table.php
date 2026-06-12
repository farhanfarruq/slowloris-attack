<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_provider_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('ai_provider_settings', 'provider_label')) {
                $table->string('provider_label')->nullable()->after('provider_key');
            }

            if (!Schema::hasColumn('ai_provider_settings', 'driver')) {
                $table->string('driver')->default('openai_compatible')->after('provider_label');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ai_provider_settings', function (Blueprint $table) {
            if (Schema::hasColumn('ai_provider_settings', 'driver')) {
                $table->dropColumn('driver');
            }

            if (Schema::hasColumn('ai_provider_settings', 'provider_label')) {
                $table->dropColumn('provider_label');
            }
        });
    }
};
