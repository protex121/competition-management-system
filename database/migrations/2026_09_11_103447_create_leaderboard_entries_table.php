<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leaderboard_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->decimal('aggregate_score', 8, 2)->unsigned();
            $table->unsignedSmallInteger('judge_count');
            $table->unsignedSmallInteger('rank');
            $table->timestamps();

            $table->unique(['competition_category_id', 'submission_id']);
            $table->index(['competition_category_id', 'rank']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leaderboard_entries');
    }
};
