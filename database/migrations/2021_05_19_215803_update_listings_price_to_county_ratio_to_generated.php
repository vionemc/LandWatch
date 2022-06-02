<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateListingsPriceToCountyRatioToGenerated extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::dropColumns('listings', ['acre_price_to_county']);
        Schema::table('listings', static function (Blueprint $table) {
            $table
                ->decimal('price_to_county_ratio', 8, 4)
                ->storedAs('price_per_acre / county_avg_price_per_acre')
                ->nullable(true)
                ->after('county_avg_price_per_acre');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropColumns('listings', ['price_to_county_ratio']);
        Schema::table('listings', static function (Blueprint $table) {
            $table->decimal('acre_price_to_county', 5, 2);
        });
    }
}
