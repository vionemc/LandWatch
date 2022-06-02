<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixTheUpdatedAtIndex extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('listings', static function (Blueprint $table) {
            $table->dropIndex(['created_at', 'updated_at']);
            $table->index(['updated_at', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('listings', static function (Blueprint $table) {
            $table->dropIndex(['updated_at', 'created_at']);
            $table->index(['created_at', 'updated_at']);
        });
    }
}
