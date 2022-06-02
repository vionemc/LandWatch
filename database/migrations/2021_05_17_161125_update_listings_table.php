<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateListingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('listings', static function (Blueprint $table) {
            $table->decimal('county_avg_price_per_acre', 12, 2)->nullable(true)->change();
            $table->decimal('acre_price_to_county', 5, 2)->nullable(true)->change();
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
            $table->decimal('county_avg_price_per_acre', 12, 2)->nullable(false)->change();
            $table->decimal('acre_price_to_county', 5, 2)->nullable(false)->change();
        });
    }
}
