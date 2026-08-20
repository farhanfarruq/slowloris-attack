<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('experiments', function (Blueprint $table) {
            $table->string('target_platform', 120)->default('esp32')->change();

            if (! Schema::hasColumn('experiments', 'runtime_metadata')) {
                $table->json('runtime_metadata')->nullable()->after('target_platform');
            }
        });
    }

    public function down(): void
    {
        Schema::table('experiments', function (Blueprint $table) {
            $table->string('target_platform', 120)->default('vm_ubuntu_server')->change();

            if (Schema::hasColumn('experiments', 'runtime_metadata')) {
                $table->dropColumn('runtime_metadata');
            }
        });
    }
};
