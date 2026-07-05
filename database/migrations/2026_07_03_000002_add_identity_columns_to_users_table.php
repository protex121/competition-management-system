<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);

            $table->foreignId('organization_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->nullOnDelete();

            $table->string('role')->default('participant')->after('email');
            $table->string('avatar_path')->nullable()->after('role');
            $table->timestamp('deactivated_at')->nullable()->after('remember_token');
            $table->softDeletes();

            $table->unique(['organization_id', 'email']);
            $table->index('role');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropUnique(['organization_id', 'email']);
            $table->dropIndex(['role']);
            $table->dropForeign(['organization_id']);

            $table->dropColumn([
                'organization_id',
                'role',
                'avatar_path',
                'deactivated_at',
            ]);

            $table->unique('email');
        });
    }
};
