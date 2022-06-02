<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class BetterIndexes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('listings', static function (Blueprint $table) {
            $table->dropIndex(['city']);
            $table->dropIndex(['county']);
            $table->index(['city', 'status']);
            $table->index(['county', 'status']);
            $table->index(['status', 'updated_at']);
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
            $table->dropIndex(['city', 'status']);
            $table->dropIndex(['county', 'status']);
            $table->dropIndex(['status', 'updated_at']);
            $table->index(['city']);
            $table->index(['county']);
        });
    }
}
