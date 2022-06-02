<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateListingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('listings', static function (Blueprint $table) {
            $table->bigInteger('id')->unsigned()->primary();
            $table->string('url')->nullable(false);
            $table->string('types');
            $table->string('address');
            $table->string('city');
            $table->string('county')->index();
            $table->string('state');
            $table->string('zip');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->decimal('price', 12, 2);
            $table->decimal('area', 7, 2);
            $table->decimal('price_per_acre', 12, 2);
            $table->decimal('county_avg_price_per_acre', 12, 2);
            $table->decimal('acre_price_to_county', 5, 2);
            $table->smallInteger('status')->unsigned()->nullable(false)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
}
