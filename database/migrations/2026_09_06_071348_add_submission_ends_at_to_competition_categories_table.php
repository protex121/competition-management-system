<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competition_categories', function (Blueprint $table) {
            $table->timestamp('submission_ends_at')->nullable()->after('registration_ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('competition_categories', function (Blueprint $table) {
            $table->dropColumn('submission_ends_at');
        });
    }
};
