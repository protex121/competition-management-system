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
            $table->string('registration_mode')->default('individual')->after('max_participants');
            $table->unsignedSmallInteger('min_team_size')->nullable()->after('registration_mode');
            $table->unsignedSmallInteger('max_team_size')->nullable()->after('min_team_size');
            $table->boolean('requires_coach')->default(false)->after('max_team_size');
        });
    }

    public function down(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->dropColumn([
                'registration_mode',
                'min_team_size',
                'max_team_size',
                'requires_coach',
            ]);
        });
    }
};
