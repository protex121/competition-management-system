<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competition_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('status')->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unsignedInteger('max_participants')->nullable();
            $table->timestamp('registration_ends_at')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['competition_id', 'slug']);
            $table->index('is_default');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_categories');
    }
};
