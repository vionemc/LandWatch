<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateListingsConvertPricePerAcreToGenerated extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::dropColumns('listings', ['price_per_acre']);
        Schema::table('listings', static function (Blueprint $table) {
            $table
                ->decimal('price_per_acre', 12, 2)
                ->storedAs('price / area')
                ->nullable(true)
                ->after('price');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropColumns('listings', ['price_per_acre']);
        Schema::table('listings', static function (Blueprint $table) {
            $table->decimal('price_per_acre', 12, 2)->nullable(true)->after('price');
        });

        DB::table('listings')->raw('UPDATE `listings` SET `price_per_acre` = `price` / `area`');
    }
}
