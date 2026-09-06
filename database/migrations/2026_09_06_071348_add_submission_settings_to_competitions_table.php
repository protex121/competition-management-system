<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->timestamp('submission_starts_at')->nullable()->after('registration_ends_at');
            $table->timestamp('submission_ends_at')->nullable()->after('submission_starts_at');
        });
    }

    public function down(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->dropColumn(['submission_starts_at', 'submission_ends_at']);
        });
    }
};
