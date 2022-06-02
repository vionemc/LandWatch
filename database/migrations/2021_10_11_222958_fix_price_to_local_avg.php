<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixPriceToLocalAvg extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn('price_to_local_avg');
        });

        Schema::table('listings', function (Blueprint $table) {
            $table->decimal('price_to_local_avg', 8, 4)
                ->storedAs('price_per_acre / local_avg_price_per_acre')
                ->default(null)
                ->nullable(true)
                ->after('local_min_price_per_acre');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        //
    }
}
