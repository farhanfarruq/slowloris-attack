<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_results', function (Blueprint $table) {
            if (!Schema::hasColumn('ai_results', 'validation_run_id')) {
                $table->uuid('validation_run_id')->nullable()->after('experiment_id')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('ai_results', function (Blueprint $table) {
            if (Schema::hasColumn('ai_results', 'validation_run_id')) {
                $table->dropIndex(['validation_run_id']);
                $table->dropColumn('validation_run_id');
            }
        });
    }
};
