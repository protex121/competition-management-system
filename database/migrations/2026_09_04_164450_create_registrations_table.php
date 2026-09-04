<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_category_id')->constrained('competition_categories')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('teams')->cascadeOnDelete();
            $table->string('status')->index();
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamps();

            $table->unique(['competition_category_id', 'user_id']);
            $table->unique(['competition_category_id', 'team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
