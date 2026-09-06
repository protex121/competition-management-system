<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rubric_criterion_id')->constrained()->cascadeOnDelete();
            $table->foreignId('judge_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('score');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['submission_id', 'rubric_criterion_id', 'judge_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scores');
    }
};
