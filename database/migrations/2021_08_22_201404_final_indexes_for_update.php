<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FinalIndexesForUpdate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('listings', static function (Blueprint $table) {
            $table->dropIndex(['state', 'county', 'area']);
            $table->unique(['state', 'county', 'area', 'id', 'status', 'price_per_acre', 'price']);
            $table->unique(['status', 'id']);
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
            $table->dropUnique(['state', 'county', 'area', 'id', 'status', 'price_per_acre', 'price']);
            $table->dropUnique(['status', 'id']);
            $table->index(['state', 'county', 'area']);
        });
    }
}
