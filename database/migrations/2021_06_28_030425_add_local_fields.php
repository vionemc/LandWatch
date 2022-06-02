<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLocalFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('listings', static function (Blueprint $table) {
            $table->decimal('local_avg_price_per_acre', 12, 2)
                ->nullable(true)
                ->default(null)
                ->after('price_to_county_ratio');
            $table->decimal('local_median_price_per_acre', 12, 2)
                ->nullable(true)
                ->default(null)
                ->after('local_avg_price_per_acre');
            $table->decimal('local_min_price_per_acre', 12, 2)
                ->nullable(true)
                ->default(null)
                ->after('local_median_price_per_acre');
            $table->decimal('price_to_local_ratio', 8, 4)
                ->storedAs('price_per_acre / local_avg_price_per_acre')
                ->default(null)
                ->nullable(true)
                ->after('local_min_price_per_acre');
            $table->dropColumn('price_to_county_ratio');
            $table->dropColumn('county_avg_price_per_acre');
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
            $table->dropColumn('price_to_local_ratio');
            $table->dropColumn('local_min_price_per_acre');
            $table->dropColumn('local_median_price_per_acre');
            $table->dropColumn('local_avg_price_per_acre');
            $table
                ->decimal('county_avg_price_per_acre', 12, 2)
                ->nullable(true)
                ->after('area');
            $table
                ->decimal('price_to_county_ratio', 8, 4)
                ->storedAs('price_per_acre / county_avg_price_per_acre')
                ->nullable(true)
                ->after('county_avg_price_per_acre');
        });
    }
}
