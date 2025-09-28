<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('request_logs', function (Blueprint $table) {
            // Add orchestration_id if it doesn't exist
            if (!Schema::hasColumn('request_logs', 'orchestration_id')) {
                $table->string('orchestration_id')->nullable()->after('api_id');
            }

            // Add user column if missing
            if (!Schema::hasColumn('request_logs', 'user')) {
                $table->string('user')->nullable()->after('orchestration_id');
            }

            // Make status_code nullable
            if (Schema::hasColumn('request_logs', 'status_code')) {
                $table->integer('status_code')->nullable()->change();
            }

            // Add indexes if missing
            if (!Schema::hasIndex('request_logs', 'idx_orchestration_id')) {
                $table->index('orchestration_id', 'idx_orchestration_id');
            }
            if (!Schema::hasIndex('request_logs', 'idx_user')) {
                $table->index('user', 'idx_user');
            }
            if (!Schema::hasIndex('request_logs', 'idx_created_at')) {
                $table->index('created_at', 'idx_created_at');
            }
        });
    }

    public function down(): void {
        Schema::table('request_logs', function (Blueprint $table) {
            if (Schema::hasColumn('request_logs', 'orchestration_id')) {
                $table->dropColumn('orchestration_id');
            }
            if (Schema::hasColumn('request_logs', 'user')) {
                $table->dropColumn('user');
            }

            $table->integer('status_code')->nullable(false)->change();

            if (Schema::hasIndex('request_logs', 'idx_orchestration_id')) {
                $table->dropIndex('idx_orchestration_id');
            }
            if (Schema::hasIndex('request_logs', 'idx_user')) {
                $table->dropIndex('idx_user');
            }
            if (Schema::hasIndex('request_logs', 'idx_created_at')) {
                $table->dropIndex('idx_created_at');
            }
        });
    }
};