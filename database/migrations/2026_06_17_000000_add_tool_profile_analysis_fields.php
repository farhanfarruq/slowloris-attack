<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('experiments', function (Blueprint $table) {
            if (!Schema::hasColumn('experiments', 'tool_profile')) {
                $table->string('tool_profile')->default('slowloris')->after('ground_truth_label')->index();
            }
            if (!Schema::hasColumn('experiments', 'attack_pattern')) {
                $table->string('attack_pattern')->nullable()->after('tool_profile')->index();
            }
            if (!Schema::hasColumn('experiments', 'analysis_profile_key')) {
                $table->string('analysis_profile_key')->nullable()->after('attack_pattern')->index();
            }
            if (!Schema::hasColumn('experiments', 'target_platform')) {
                $table->string('target_platform')->default('vm_ubuntu_server')->after('analysis_profile_key');
            }
        });

        Schema::table('ai_provider_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('ai_provider_settings', 'tool_profile')) {
                $table->string('tool_profile')->nullable()->after('provider_label')->index();
            }
        });

        Schema::table('ai_results', function (Blueprint $table) {
            if (!Schema::hasColumn('ai_results', 'tool_profile')) {
                $table->string('tool_profile')->default('slowloris')->after('experiment_id')->index();
            }
            if (!Schema::hasColumn('ai_results', 'attack_pattern')) {
                $table->string('attack_pattern')->nullable()->after('tool_profile')->index();
            }
            if (!Schema::hasColumn('ai_results', 'analysis_profile_key')) {
                $table->string('analysis_profile_key')->nullable()->after('attack_pattern')->index();
            }
            if (!Schema::hasColumn('ai_results', 'logic_classification')) {
                $table->string('logic_classification')->nullable()->after('confidence_score');
            }
            if (!Schema::hasColumn('ai_results', 'logic_score')) {
                $table->float('logic_score')->nullable()->after('logic_classification');
            }
            if (!Schema::hasColumn('ai_results', 'logic_gate_reasons')) {
                $table->json('logic_gate_reasons')->nullable()->after('logic_score');
            }
            if (!Schema::hasColumn('ai_results', 'ai_chart_data')) {
                $table->json('ai_chart_data')->nullable()->after('logic_gate_reasons');
            }
            if (!Schema::hasColumn('ai_results', 'comparison_summary')) {
                $table->json('comparison_summary')->nullable()->after('ai_chart_data');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ai_results', function (Blueprint $table) {
            foreach ([
                'comparison_summary',
                'ai_chart_data',
                'logic_gate_reasons',
                'logic_score',
                'logic_classification',
                'analysis_profile_key',
                'attack_pattern',
                'tool_profile',
            ] as $column) {
                if (Schema::hasColumn('ai_results', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('ai_provider_settings', function (Blueprint $table) {
            if (Schema::hasColumn('ai_provider_settings', 'tool_profile')) {
                $table->dropColumn('tool_profile');
            }
        });

        Schema::table('experiments', function (Blueprint $table) {
            foreach (['target_platform', 'analysis_profile_key', 'attack_pattern', 'tool_profile'] as $column) {
                if (Schema::hasColumn('experiments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
