<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPriceToLocalMin extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->decimal('price_to_local_min', 12, 4)
                ->storedAs('price_per_acre / local_min_price_per_acre')
                ->default(null)
                ->nullable(true)
                ->after('price_to_local_ratio');
            $table->renameColumn('price_to_local_ratio', 'price_to_local_avg');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->renameColumn('price_to_local_avg', 'price_to_local_ratio');
            $table->dropColumn('price_to_local_min');
        });
    }
}
